<?php

echo "*** Starting worker\n";


class EndRequestError extends Error {
}

/**
 * DokuWiki doku_end_request() call workaround
 */
function doku_end_request(string|int $status = 0): never {
    throw new EndRequestError();
}

// Prevent worker script termination when a client connection is interrupted
ignore_user_abort(true);


/******************************************************************************
 * boot dokuwiki application kernel
 ******************************************************************************/

// FJF - Attention: temporary solution: set this to match your server/Caddyfile:
$_SERVER = [
    'SCRIPT_FILENAME' => __FILE__, // for initialization in fullpath line 619
    'SCRIPT_NAME' => '/index.php', // getBaseURL
    'SERVER_NAME' => 'localhost', //FJF neccessary?
    'SERVER_PORT' => 8080, // FJF neccessary?
    'HTTP_HOST' => 'localhost:8080', // FJF workaround for Ip::hostName - but you could consider sending relative urls(!?)
    // 'HTTPS' => 'on' // FJF workaround for Ip::isSsl
];

require_once(__DIR__ . '/inc/init.php');


/******************************************************************************
 * frankenphp request handler
 ******************************************************************************/
$handler = static function () use ($conf) {
    try {
        // Called when a request is received,
        // superglobals, php://input and the like are reset
        //echo $myApp->handle($_GET, $_POST, $_COOKIE, $_FILES, $_SERVER);

        // frankenphp does care about $_GET, $_POST, $_COOKIE, $_FILES, $_SERVER - BUT NOT about $_REQUEST
        $_REQUEST = array_merge($_GET, $_POST);

        try {
            include __DIR__ . $_SERVER['PHP_SELF'];
        } catch(EndRequestError $exc) {
            // Dokuwiki wanted to terminate the request. Not the worker! Ignore.
        }

        session_write_close();

    } catch (\Throwable $exception) {
        // `set_exception_handler` is called only when the worker script ends,
        // which may not be what you expect, so catch and handle exceptions here
        // (new \MyCustomExceptionHandler)->handleException($exception);
        echo "Error:" . $exception->getMessage();
    }
};


/******************************************************************************
 * frankenphp worker main loop
 ******************************************************************************/
$maxRequests = (int)($_SERVER['MAX_REQUESTS'] ?? 0);
$keepRunning = true;

for (
    $nbRequests = 0;
    $keepRunning && (!$maxRequests || $nbRequests < $maxRequests);
    $nbRequests++
) {
    $keepRunning = \frankenphp_handle_request($handler);

    // Call the garbage collector to reduce the chances of it being triggered in the middle of a page generation
    gc_collect_cycles();
}


echo "*** Terminating worker\n";
