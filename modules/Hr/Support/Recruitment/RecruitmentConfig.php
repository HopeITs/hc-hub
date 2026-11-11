<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Recruitment;

/**
 * Recruitment settings (the old config/recruitment*.php files), kept with the
 * area until they are published to config/. Reading through here loads them
 * on first use, and adds the two private upload disks when the application
 * has not defined them.
 */
final class RecruitmentConfig
{
    private const FILES = ['recruitment', 'recruitment-privacy', 'recruitment-uploads'];

    private static bool $loaded = false;

    public static function get(string $key, mixed $default = null): mixed
    {
        self::load();

        return config($key, $default);
    }

    public static function load(): void
    {
        if (self::$loaded) {
            return;
        }

        self::$loaded = true;

        foreach (self::FILES as $file) {
            if (config($file) === null) {
                config()->set($file, require __DIR__.'/config/'.$file.'.php');
            }
        }

        if (config('uploads.allowed_image_mimes') === null) {
            config()->set('uploads.allowed_image_mimes', ['image/jpeg', 'image/png', 'image/webp']);
        }

        if (config('uploads.images.recruitment_document') === null) {
            config()->set('uploads.images.recruitment_document', [
                'max_bytes' => 10 * 1024 * 1024,
                'max_width' => 8000,
                'max_height' => 8000,
                'max_pixels' => 32_000_000,
                'max_aspect_ratio' => 10.0,
                'output' => 'preserve',
                'quality' => 90,
            ]);
        }

        foreach (['quarantine_disk' => 'recruitment-quarantine', 'clean_disk' => 'recruitment-files'] as $setting => $folder) {
            $disk = (string) config('recruitment-uploads.'.$setting);

            if ($disk !== '' && config('filesystems.disks.'.$disk) === null) {
                config()->set('filesystems.disks.'.$disk, [
                    'driver' => 'local',
                    'root' => storage_path('app/private/'.$folder),
                    'serve' => false,
                    'throw' => true,
                    'report' => true,
                ]);
            }
        }
    }
}
