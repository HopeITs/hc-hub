<?php

return [

    'pdf' => [
        'enabled' => true,
        'binary' => 'C:\Program Files\wkhtmltopdf\bin\wkhtmltopdf.exe',
        'timeout' => false,
        'options' => [
            'encoding' => 'UTF-8',
            'page-size' => 'A4',
            'margin-top' => 0,
            'margin-right' => 0,
            'margin-bottom' => 0,
            'margin-left' => 0,
            'print-media-type' => true,
            'enable-local-file-access' => true,
            'dpi' => 96,
            'image-dpi' => 96,
            'disable-smart-shrinking' => true,
        ],
        'env' => [],
    ],

    'image' => [
        'enabled' => true,
        'binary' => env('WKHTML_IMG_BINARY', '/usr/local/bin/wkhtmltoimage'),
        'timeout' => false,
        'options' => [],
        'env' => [],
    ],

];
