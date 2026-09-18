<?php

return [
    // Path to the Chrome/Chromium executable Browsershot should drive.
    // Override via BROWSERSHOT_CHROME_PATH when the binary lives elsewhere
    // (e.g. a different install path, or a Linux/production host).
    'chrome_path' => env('BROWSERSHOT_CHROME_PATH', 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe'),

    // Path to the Node.js binary Browsershot shells out to. Override via
    // BROWSERSHOT_NODE_BINARY if Node isn't on the web server's PATH.
    'node_binary' => env('BROWSERSHOT_NODE_BINARY', null),
];
