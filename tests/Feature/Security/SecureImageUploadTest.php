<?php

namespace Tests\Feature\Security;

use App\Exceptions\UnsafeImageException;
use App\Services\SecureImageUploadService;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * The upload pipeline's job is to make it impossible for bytes chosen by the
 * client to end up executable on disk. These tests attack it from both ends:
 * files that should be rejected outright, and files that are accepted but must
 * come out the other side re-encoded, renamed, and inert.
 */
class SecureImageUploadTest extends TestCase
{
    public function test_it_rejects_a_php_script_renamed_as_an_image(): void
    {
        $this->expectException(UnsafeImageException::class);

        $this->service()->process(
            $this->tempFile('avatar.jpg', "<?php system(\$_GET['c']); ?>"),
            'avatar',
        );
    }

    public function test_it_rejects_a_double_extension_php_upload(): void
    {
        $this->expectException(UnsafeImageException::class);

        $this->service()->process(
            $this->tempFile('avatar.php.jpg', "<?php echo 'x'; ?>"),
            'avatar',
        );
    }

    public function test_it_rejects_svg_even_though_it_is_a_real_image_format(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10">'
            .'<script>alert(1)</script></svg>';

        $this->expectException(UnsafeImageException::class);

        $this->service()->process($this->tempFile('logo.svg', $svg), 'logo');
    }

    public function test_it_rejects_gif_and_other_formats_outside_the_allow_list(): void
    {
        $this->expectException(UnsafeImageException::class);

        $this->service()->process($this->realImage('gif'), 'avatar');
    }

    /**
     * The core case: a file that is genuinely a valid PNG *and* contains PHP
     * source. Content sniffing alone would accept it.
     */
    public function test_it_strips_appended_php_from_a_valid_image_polyglot(): void
    {
        $png = $this->realImage('png');
        $polyglotPath = tempnam(sys_get_temp_dir(), 'poly');
        file_put_contents(
            $polyglotPath,
            file_get_contents($png->getRealPath())."\n<?php system('id'); ?>",
        );

        $upload = new UploadedFile($polyglotPath, 'harmless.png', null, null, true);

        // Accepted — it really is a valid PNG.
        $processed = $this->service()->process($upload, 'logo');
        $stored = file_get_contents($processed->path);

        // But the stored bytes came from the server's encoder, so the appended
        // script did not survive.
        $this->assertStringNotContainsString('<?php', $stored);
        $this->assertStringNotContainsString('system(', $stored);

        $processed->discard();
    }

    public function test_it_never_reuses_the_client_filename_or_extension(): void
    {
        $upload = new UploadedFile(
            $this->realImage('png')->getRealPath(),
            'shell.php.png',
            null,
            null,
            true,
        );

        $processed = $this->service()->process($upload, 'logo');

        $this->assertStringNotContainsString('shell', $processed->filename);
        $this->assertStringNotContainsString('.php', $processed->filename);
        $this->assertSame('png', pathinfo($processed->filename, PATHINFO_EXTENSION));
        $this->assertMatchesRegularExpression('/^[a-z0-9]{40}\.png$/', $processed->filename);

        $processed->discard();
    }

    public function test_it_rejects_an_image_that_exceeds_the_pixel_budget(): void
    {
        // Well inside the byte limit, far outside the pixel limit — the shape
        // of a decompression bomb.
        config()->set('uploads.images.avatar.max_pixels', 100);

        $this->expectException(UnsafeImageException::class);

        $this->service()->process($this->realImage('png', 200, 200), 'avatar');
    }

    public function test_it_rejects_an_oversized_file(): void
    {
        config()->set('uploads.images.avatar.max_bytes', 10);

        $this->expectException(UnsafeImageException::class);

        $this->service()->process($this->realImage('png'), 'avatar');
    }

    public function test_it_rejects_an_extreme_aspect_ratio(): void
    {
        $this->expectException(UnsafeImageException::class);

        $this->service()->process($this->realImage('png', 2000, 100), 'avatar');
    }

    public function test_it_rejects_a_truncated_image(): void
    {
        $valid = file_get_contents($this->realImage('png')->getRealPath());

        $this->expectException(UnsafeImageException::class);

        $this->service()->process(
            $this->tempFile('broken.png', substr($valid, 0, 40)),
            'avatar',
        );
    }

    public function test_the_avatar_profile_normalises_everything_to_jpeg(): void
    {
        $processed = $this->service()->process($this->realImage('png'), 'avatar');

        $this->assertSame('image/jpeg', $processed->mime);
        $this->assertSame('jpg', pathinfo($processed->filename, PATHINFO_EXTENSION));
        $this->assertSame('image/jpeg', mime_content_type($processed->path));

        $processed->discard();
    }

    public function test_the_logo_profile_preserves_png_so_transparency_survives(): void
    {
        $processed = $this->service()->process($this->realImage('png'), 'logo');

        $this->assertSame('image/png', $processed->mime);
        $this->assertSame('image/png', mime_content_type($processed->path));

        $processed->discard();
    }

    private function service(): SecureImageUploadService
    {
        return app(SecureImageUploadService::class);
    }

    private function tempFile(string $name, string $contents): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'up');
        file_put_contents($path, $contents);

        return new UploadedFile($path, $name, null, null, true);
    }

    /** A real, decodable image of the given size and type. */
    private function realImage(string $type = 'png', int $width = 200, int $height = 200): UploadedFile
    {
        $image = imagecreatetruecolor($width, $height);
        imagefilledrectangle($image, 0, 0, $width - 1, $height - 1, imagecolorallocate($image, 10, 120, 200));

        $path = tempnam(sys_get_temp_dir(), 'img');
        match ($type) {
            'png' => imagepng($image, $path),
            'jpeg' => imagejpeg($image, $path),
            'webp' => imagewebp($image, $path),
            'gif' => imagegif($image, $path),
        };
        imagedestroy($image);

        return new UploadedFile($path, "photo.{$type}", null, null, true);
    }
}
