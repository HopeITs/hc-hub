<?php

/**
 * Router for PHP's built-in web server.
 *
 * `php artisan serve` cannot spawn its own server process on this Windows
 * setup — it reports "Failed to listen" on every port while `php -S` binds
 * the same port fine — so the dev server is started directly:
 *
 *     php -S 127.0.0.1:8000 server.php
 *
 * The router the framework ships resolves the public directory from getcwd(),
 * which forces the server to be started from inside public/. This one resolves
 * it from its own location instead, so it works from the project root.
 *
 * Laravel's ServeCommand prefers this file when it exists, so `artisan serve`
 * picks it up too wherever that command does work.
 */

$publicPath = __DIR__.'/public';

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? ''
);

// Emulate mod_rewrite: serve the file when it exists, hand everything else to
// the framework's front controller.
if ($uri !== '/' && file_exists($publicPath.$uri) && ! is_dir($publicPath.$uri)) {
    return false;
}

file_put_contents(
    'php://stdout',
    sprintf("[%s] %s:%s [%s] URI: %s\n",
        date('D M j H:i:s Y'),
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['REMOTE_PORT'],
        $_SERVER['REQUEST_METHOD'],
        $uri,
    ),
);

require_once $publicPath.'/index.php';
