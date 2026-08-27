<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

/**
 * Guards the accessibility wiring that lives in the shared Blade components.
 *
 * These are cheap render-level assertions on purpose: the components are used
 * by hundreds of views, so a regression here degrades the whole application at
 * once and is invisible to every other test in the suite.
 */
class AccessibilityTest extends TestCase
{
    public function test_input_links_to_its_error_message_when_validation_fails(): void
    {
        $html = $this->renderWithErrors(
            '<x-forms.input id="first_name" model="form.first_name" />',
            ['form.first_name' => ['The first name field is required.']],
        );

        $this->assertStringContainsString('aria-invalid="true"', $html);
        $this->assertStringContainsString('aria-describedby="first_name-error"', $html);
    }

    public function test_input_is_not_marked_invalid_when_there_is_no_error(): void
    {
        $html = $this->renderWithErrors('<x-forms.input id="first_name" model="form.first_name" />');

        $this->assertStringNotContainsString('aria-invalid', $html);
        $this->assertStringNotContainsString('aria-describedby', $html);
    }

    public function test_error_message_id_matches_the_input_it_describes(): void
    {
        $html = $this->renderWithErrors(
            '<x-forms.input id="first_name" model="form.first_name" />'
            .'<x-forms.input-error field="form.first_name" input-id="first_name" />',
            ['form.first_name' => ['The first name field is required.']],
        );

        // The describedby target must actually exist, or the link is dead.
        $this->assertStringContainsString('aria-describedby="first_name-error"', $html);
        $this->assertStringContainsString('id="first_name-error"', $html);
        $this->assertStringContainsString('role="alert"', $html);
    }

    public function test_required_input_exposes_its_required_state(): void
    {
        $html = $this->renderWithErrors('<x-forms.input id="email" model="form.email" required />');

        $this->assertStringContainsString('aria-required="true"', $html);
        $this->assertStringContainsString('required', $html);
    }

    public function test_required_label_has_a_text_alternative_for_the_asterisk(): void
    {
        $html = $this->renderWithErrors('<x-forms.input-label for="email" required>Email</x-forms.input-label>');

        // The asterisk itself must be hidden, with a readable word in its place.
        $this->assertStringContainsString('aria-hidden="true"', $html);
        $this->assertMatchesRegularExpression('/sr-only[^>]*>\(\s*required\s*\)/i', $html);
    }

    public function test_field_caption_is_not_a_label_element(): void
    {
        $html = $this->renderWithErrors('<x-field-caption>Employee</x-field-caption>');

        // A <label> with no control is announced as an orphaned form field.
        $this->assertStringNotContainsString('<label', $html);
        $this->assertStringContainsString('Employee', $html);
    }

    public function test_every_image_in_the_view_layer_declares_an_alt_attribute(): void
    {
        $offenders = [];

        foreach ($this->bladeFiles() as $path) {
            $contents = file_get_contents($path);

            // Neutralise Blade and Alpine expressions first: an unescaped "->"
            // inside {{ }} would otherwise terminate the tag match early and
            // hide a real alt attribute.
            $contents = preg_replace('/\{\{.*?\}\}/s', 'EXPR', $contents);
            $contents = preg_replace('/\{!!.*?!!\}/s', 'EXPR', $contents);

            preg_match_all('/<img\b[^>]*>/s', $contents, $matches);

            foreach ($matches[0] as $tag) {
                if (! preg_match('/\balt\s*=/', $tag)) {
                    $offenders[] = str_replace(base_path().DIRECTORY_SEPARATOR, '', $path);
                }
            }
        }

        $this->assertSame([], array_values(array_unique($offenders)),
            'Every <img> needs an alt attribute (use alt="" for decorative images).');
    }

    public function test_the_app_layout_exposes_a_skip_link_to_the_main_landmark(): void
    {
        $layout = file_get_contents(resource_path('views/components/layouts/app.blade.php'));

        $this->assertStringContainsString('href="#main-content"', $layout);
        $this->assertStringContainsString('id="main-content"', $layout);

        // The decorative preloader must stay out of the accessibility tree.
        $this->assertMatchesRegularExpression(
            '/id="page-gif-overlay"\s+aria-hidden="true"/',
            $layout,
        );
    }

    public function test_accessibility_strings_are_translated_in_every_locale(): void
    {
        $keys = ['skip-to-content', 'main-content', 'required-field', 'error-prefix'];

        foreach (['en', 'ar'] as $locale) {
            $strings = require resource_path("lang/{$locale}/general.php");

            foreach ($keys as $key) {
                $this->assertArrayHasKey($key, $strings['a11y'] ?? [],
                    "Missing a11y.{$key} translation for [{$locale}].");
                $this->assertNotSame('', trim($strings['a11y'][$key]));
            }
        }
    }

    /** Render a Blade string with an error bag bound, the way a failed form does. */
    private function renderWithErrors(string $template, array $errors = []): string
    {
        $bag = new ViewErrorBag;

        if ($errors !== []) {
            $bag->put('default', new \Illuminate\Support\MessageBag($errors));
        }

        View::share('errors', $bag);

        return Blade::render($template);
    }

    /** @return iterable<string> */
    private function bladeFiles(): iterable
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(resource_path('views')),
        );

        foreach ($iterator as $file) {
            if (str_ends_with($file->getPathname(), '.blade.php')) {
                yield $file->getPathname();
            }
        }
    }
}
