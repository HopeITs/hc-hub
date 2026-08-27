<?php

namespace Tests\Feature\Security;

use App\Livewire\ActivityLog\ActivityLogTable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\Concerns\CreatesHrFixtures;
use Tests\TestCase;

/**
 * The activity log renders values that users typed into audited records, and
 * its audience is administrators. Markup must never survive that round trip.
 */
class ActivityLogRenderingTest extends TestCase
{
    use CreatesHrFixtures, RefreshDatabase;

    /**
     * @return array<string, array{0: string}>
     */
    public static function payloads(): array
    {
        return [
            'script tag' => ['<script>alert(1)</script>'],
            'img onerror' => ['<img src=x onerror=alert(1)>'],
            'svg onload' => ['<svg/onload=alert(1)>'],
            'event handler attribute' => ['" onmouseover="alert(1)'],
            'malformed markup' => ['<<div>>alert</div'],
            'javascript uri' => ['<a href="javascript:alert(1)">x</a>'],
        ];
    }

    /**
     * @dataProvider payloads
     */
    public function test_markup_in_a_logged_value_is_escaped(string $payload): void
    {
        $rendered = $this->summarise(['first_name_en' => $payload]);

        // The payload must not survive verbatim...
        $this->assertStringNotContainsString($payload, $rendered);

        // ...and no character that could open a tag or break out of an
        // attribute may remain unencoded. Asserting on the characters rather
        // than on a list of known payloads is what makes this cover the next
        // payload nobody thought of.
        foreach (['<', '>', '"', "'"] as $dangerous) {
            $this->assertStringNotContainsString($dangerous, $rendered, sprintf(
                'Unencoded %s survived rendering of: %s',
                $dangerous,
                $payload,
            ));
        }
    }

    public function test_markup_in_a_logged_attribute_name_is_escaped(): void
    {
        $rendered = $this->summarise(['<script>x</script>' => 'value']);

        $this->assertStringNotContainsString('<script', $rendered);
    }

    /** A nested array must not be able to smuggle markup through a child node. */
    public function test_markup_nested_inside_an_array_value_is_escaped(): void
    {
        $rendered = $this->summarise(['meta' => ['note' => '<script>alert(1)</script>']]);

        $this->assertStringNotContainsString('<script', $rendered);
    }

    private function summarise(array $attributes): string
    {
        $method = new ReflectionMethod(ActivityLogTable::class, 'summarise');
        $method->setAccessible(true);

        return $method->invoke(new ActivityLogTable, $attributes);
    }
}
