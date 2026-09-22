<?php

namespace Tests\Feature;

use App\Services\ContractPdfRenderer;
use App\Support\InspectableBrowsershot;
use RuntimeException;
use Tests\TestCase;

/**
 * Guards the two things that made the server-side export failure so expensive
 * to chase.
 *
 * The render used to come back from node as base64 inside a JSON line on
 * stdout, and Browsershot treats a stdout it cannot parse as a success: it
 * base64-decodes null and hands back an empty string. On the server that line
 * ran to roughly 635 KB — the PDF plus, because the fonts and images are
 * inlined as data URIs, the whole document echoed again as a list of requested
 * URLs — and whatever truncated it left the controller holding 0 bytes with no
 * way to tell why. The PDF now goes to disk and stdout stays a few hundred
 * bytes; when a render does fail, the message says what failed.
 */
class ContractPdfRendererTest extends TestCase
{
    public function test_the_render_asks_chrome_to_write_the_pdf_to_disk(): void
    {
        $browsershot = app(ContractPdfRenderer::class)->browsershot('<h1>x</h1>', sys_get_temp_dir().'/probe');

        $this->assertInstanceOf(InspectableBrowsershot::class, $browsershot);

        $command = $browsershot->createPdfCommand('/tmp/out.pdf');

        $this->assertSame('/tmp/out.pdf', $command['options']['path'] ?? null,
            'Without a path, the PDF travels back through stdout as base64 — the transport that failed.');
        $this->assertTrue($command['options']['disableCaptureURLS'] ?? false,
            'Capturing URLs echoes every inlined data URI back on stdout, hundreds of KB of it.');
    }

    public function test_the_headless_switch_follows_which_chrome_binary_is_configured(): void
    {
        $renderer = app(ContractPdfRenderer::class);
        $profile = sys_get_temp_dir().'/probe';

        config(['browsershot.chrome_path' => '/opt/chrome/chrome']);
        $this->assertTrue(
            $renderer->browsershot('<h1>x</h1>', $profile)->createPdfCommand()['options']['newHeadless'] ?? false,
            'Full Chrome needs --headless=new: the legacy mode it asks for otherwise was removed in M132.',
        );

        // The shell is headless by construction and takes no such switch.
        config(['browsershot.chrome_path' => '/opt/chrome-headless-shell/chrome-headless-shell']);
        $this->assertArrayNotHasKey(
            'newHeadless',
            $renderer->browsershot('<h1>x</h1>', $profile)->createPdfCommand()['options'],
        );
    }

    public function test_a_failed_render_reports_the_cause_and_not_just_a_byte_count(): void
    {
        config(['browsershot.chrome_path' => __DIR__.'/no-such-chrome']);

        try {
            app(ContractPdfRenderer::class)->renderHtml('<h1>x</h1>');
            $this->fail('Expected the render to throw.');
        } catch (RuntimeException $e) {
            $message = $e->getMessage();

            // Which failure this host produces depends on what it has installed
            // — a missing Chrome, a missing node, a puppeteer it cannot require.
            // What must hold everywhere is that whichever it was reaches the
            // caller, instead of being flattened into a byte count.
            $this->assertStringStartsWith('Browsershot threw ', $message);
            $this->assertGreaterThan(60, strlen($message),
                'The cause was swallowed: '.$message);
        }
    }
}
