<?php

return [
    'isRemoteEnabled'      => true,
    'isHtml5ParserEnabled' => true,
    'chroot'               => base_path(),

    'font_dir'   => storage_path('fonts/'),
    'font_cache' => storage_path('fonts/'),

    'default_font' => 'somar',

    'fonts' => [
        'somar' => [
            'R' => 'SomarSans-Regular.otf',
            'B' => 'SOMARSANS-BOLD.OTF',
        ],
    ],
];

