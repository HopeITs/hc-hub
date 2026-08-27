<?php

return [
    'isHtml5ParserEnabled' => true,
    'isRemoteEnabled' => false,
    'enable_font_subsetting' => true,

    'chroot' => public_path(),
    'font_dir' => public_path('fonts'),
    'font_cache' => storage_path('fonts'),
    'default_font' => 'somar',
    'fonts' => [
        'somar' => [
            'R' => 'SomarSans/SomarSans-Regular.ttf',
            'B' => 'SomarSans/SomarSans-Bold.ttf',
        ],
        'NotoNaskhArabic' => [
            'R' => 'NotoNaskh/NotoNaskhArabic-Regular.ttf',
            'B' => 'NotoNaskh/NotoNaskhArabic-Bold.ttf',
        ],
    ],

];
