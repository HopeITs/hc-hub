<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Enable Blaze
    |--------------------------------------------------------------------------
    |
    | When set to false, Blaze will skip all compilation and optimization.
    | Templates will be rendered using standard Blade without any Blaze
    | processing. This is useful for debugging or disabling Blaze in
    | specific environments.
    |
    | On by default, with folding switched off for every component
    | (App\Support\Blaze\NonFoldingBlazeManager): folding baked translated
    | labels into the cached view in whichever language rendered first. The
    | compile step alone roughly halves page render time (931 component renders
    | per page → plain function calls), and AppServiceProvider::configureBlaze()
    | lists the component files that must stay plain Blade.
    |
    */

    'enabled' => env('BLAZE_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Debug Mode
    |--------------------------------------------------------------------------
    |
    | When enabled, Blaze will register the debugger middleware and output
    | additional diagnostic information about the compilation pipeline.
    |
    */

    'debug' => env('BLAZE_DEBUG', false),

];
