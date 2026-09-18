<?php

return [
    // Path to the Chrome/Chromium executable Browsershot should drive.
    // Override via BROWSERSHOT_CHROME_PATH when the binary lives elsewhere
    // (e.g. a different install path, or a Linux/production host).
    'chrome_path' => env('BROWSERSHOT_CHROME_PATH', 'C:\Program Files\Google\Chrome\Application\chrome.exe'),

    // Path to the Node.js binary Browsershot shells out to. Override via
    // BROWSERSHOT_NODE_BINARY if Node isn't on the web server's PATH.
    'node_binary' => env('BROWSERSHOT_NODE_BINARY', null),

    // Seconds to allow for the whole node/Chrome render.
    'timeout' => (int) env('BROWSERSHOT_TIMEOUT', 120),

    // Shared hosting / constrained VPS accounts often cap the number of
    // processes (CloudLinux LVE nproc) an account may spawn. Chrome's normal
    // multi-process model trips that cap and the browser is killed the moment
    // it starts, which surfaces as "Target closed" during launch. Running it
    // single-process keeps it to one PID.
    'single_process' => filter_var(env('BROWSERSHOT_SINGLE_PROCESS', false), FILTER_VALIDATE_BOOL),

    // Chrome needs a writable profile/cache dir. When $HOME isn't writable by
    // the web user (common on cPanel-style hosts) it dies at startup. Point
    // these at a directory the web user definitely owns.
    'user_data_dir' => env('BROWSERSHOT_USER_DATA_DIR', null),
    'temp_path' => env('BROWSERSHOT_TEMP_PATH', null),

    // Comma-separated extra chromium flags (without the leading --), for
    // host-specific tweaking without a code deploy.
    'extra_args' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('BROWSERSHOT_CHROMIUM_ARGS', '')),
    ))),
];
