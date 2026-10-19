<?php

namespace Tests\Feature;

use App\Services\ContractPdfRenderer;
use RuntimeException;
use Tests\TestCase;

/**
 * Guards the handful of settings that a failing server export turned out to
 * hinge on, none of which announce themselves when they are wrong.
 */
class ContractPdfRendererTest extends TestCase
{
    public function test_the_render_asks_chrome_to_write_the_pdf_to_disk(): void
    {
        $command = app(ContractPdfRenderer::class)
            ->browsershot('<h1>x</h1>')
            ->createPdfCommand('/tmp/out.pdf');

        // Without a path the PDF travels back through node's stdout as base64,
        // and Browsershot reads a stdout it cannot parse as an empty success.
        $this->assertSame('/tmp/out.pdf', $command['options']['path'] ?? null);

        // Chromium removed the old headless mode in M132; asking for it makes a
        // current /usr/bin/chromium refuse to launch.
        $this->assertTrue($command['options']['newHeadless'] ?? false);
    }

    public function test_chrome_is_given_a_writable_config_home(): void
    {
        $renderer = app(ContractPdfRenderer::class);

        $configHome = $renderer->browsershot('<h1>x</h1>')
            ->createPdfCommand()['options']['env']['CHROME_CONFIG_HOME'] ?? null;

        // Chrome reads this before XDG_CONFIG_HOME and exits at startup when it
        // cannot write there, so the directory must exist by the time the
        // command is built, not merely be named.
        $this->assertSame($renderer->workDir().'/.config', $configHome);
        $this->assertDirectoryIsWritable((string) $configHome);
    }

    public function test_the_printed_page_keeps_its_backgrounds_and_page_size(): void
    {
        $options = app(ContractPdfRenderer::class)
            ->browsershot('<h1>x</h1>')
            ->createPdfCommand()['options'];

        // Both shape the output rather than the launch: without them the
        // brand-red headers print white and the stylesheet's @page is ignored.
        $this->assertTrue($options['printBackground'] ?? false);
        $this->assertTrue($options['preferCSSPageSize'] ?? false);
    }

    public function test_chrome_is_told_not_to_run_a_separate_gpu_process(): void
    {
        $args = app(ContractPdfRenderer::class)
            ->browsershot('<h1>x</h1>')
            ->createPdfCommand()['options']['args'] ?? [];

        // A host that hides /sys/bus/pci kills the GPU process every time it
        // tries to identify the card, and Chrome aborts the whole browser after
        // six deaths: "GPU process isn't usable. Goodbye."
        $this->assertContains('--in-process-gpu', $args);
        $this->assertContains('--disable-gpu', $args);

        // This one would remove the SwiftShader fallback that makes running
        // without a GPU possible at all, so it must stay out.
        $this->assertNotContains('--disable-software-rasterizer', $args);
    }

    public function test_a_render_that_produces_no_pdf_throws(): void
    {
        config(['browsershot.chrome_path' => __DIR__.'/no-such-chrome']);

        $this->expectException(RuntimeException::class);

        app(ContractPdfRenderer::class)->renderHtml('<h1>x</h1>');
    }
}
