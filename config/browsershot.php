<?php

return [
    // Path to the Chrome/Chromium executable Browsershot should drive.
    // Override via BROWSERSHOT_CHROME_PATH when the binary lives elsewhere
    // (e.g. a different install path, or a Linux/production host).
    'chrome_path' => env('BROWSERSHOT_CHROME_PATH', 'C:\Program Files\Google\Chrome\Application\chrome.exe'),

    // Path to the Node.js binary Browsershot shells out to. Override via
    // BROWSERSHOT_NODE_BINARY if Node isn't on the web server's PATH.
    'node_binary' => env('BROWSERSHOT_NODE_BINARY', null),

    // Directory Node should resolve `puppeteer` from. Left unset, Browsershot
    // asks `npm root -g` — which yields nothing on a host with no global npm,
    // leaving browser.cjs unable to require puppeteer at all. Point this at the
    // project's own node_modules (e.g. /home/user/app/node_modules) on any host
    // where `npm` isn't on the web user's PATH.
    'node_module_path' => env('BROWSERSHOT_NODE_MODULE_PATH', null),

    // Seconds to allow for the whole node/Chrome render. Also used as the CDP
    // protocol timeout, which otherwise defaults to 30s inside browser.cjs.
    'timeout' => (int) env('BROWSERSHOT_TIMEOUT', 120),

    // Last resort for hosts that cap the number of processes an account may
    // spawn (CloudLinux LVE nproc). It keeps Chrome to one PID, but modern
    // Chrome supports --single-process poorly and it can itself cause crashes
    // during rendering — only turn it on if the default multi-process launch
    // is demonstrably being killed by the process cap.
    'single_process' => filter_var(env('BROWSERSHOT_SINGLE_PROCESS', false), FILTER_VALIDATE_BOOL),

    // Parent directory for the throwaway Chrome profile each render gets (the
    // profile itself, its cache, crash dumps and the temp HTML all live in a
    // random subdirectory that is deleted afterwards). Chrome dies at startup
    // if it has nowhere writable to put a profile, so on cPanel-style hosts
    // where $HOME isn't writable by the web user, point this at a directory
    // that user definitely owns. Defaults to the system temp directory.
    'user_data_dir' => env('BROWSERSHOT_USER_DATA_DIR', null),

    // Chrome creates its process-singleton unix socket under TMPDIR, and a
    // unix socket path cannot exceed ~108 bytes: with the ~45-byte suffix
    // Chrome appends, anything longer than about 60 characters aborts the
    // launch with "Socket path too long". The per-render profile directory is
    // usually too deep for that, so the socket gets its own short directory,
    // separate from the profile. Must be writable by the web user.
    'tmp_dir' => env('BROWSERSHOT_TMP_DIR', null),

    // Comma-separated extra chromium flags (without the leading --), for
    // host-specific tweaking without a code deploy.
    'extra_args' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('BROWSERSHOT_CHROMIUM_ARGS', '')),
    ))),
];
