<?php

return [
    // Path to the Chrome/Chromium executable Browsershot should drive.
    //
    // On a Linux server prefer the distribution's own build — /usr/bin/chromium
    // or /usr/bin/chromium-browser — because the package manager installs the
    // libraries and system integration it expects alongside it. A Chrome
    // unpacked by hand into a home directory usually has neither, and then dies
    // during startup with nothing on stderr but D-Bus and PCI complaints.
    'chrome_path' => env('BROWSERSHOT_CHROME_PATH', 'C:\Program Files\Google\Chrome\Application\chrome.exe'),

    // Path to the Node.js binary Browsershot shells out to. Override via
    // BROWSERSHOT_NODE_BINARY if Node isn't on the web server's PATH.
    'node_binary' => env('BROWSERSHOT_NODE_BINARY', null),

    // Seconds to allow for the whole node/Chrome render.
    'timeout' => (int) env('BROWSERSHOT_TIMEOUT', 120),
];
