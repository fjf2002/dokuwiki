<?php

require_once(__DIR__ . '/AbstractWorker.php');

class FrankenphpWorker extends AbstractWorker {

    public function __construct() {
        // Since error logging goes to the console, switch off html.
        ini_set('html_errors', 0);

        // frankenphp does not define STDERR.
        define('STDERR', fopen('php://stderr', 'wb'));
    }

    public function run(): void {
        echo "*** Starting worker\n";

        // Prevent worker script termination when a client connection is interrupted
        ignore_user_abort(true);

        $this->bootDokuWikiKernel();


        /******************************************************************************
         * frankenphp worker main loop
         ******************************************************************************/
        $maxRequests = (int)($_SERVER['MAX_REQUESTS'] ?? 0);
        $keepRunning = true;

        // Called when a request is received,
        // superglobals, php://input and the like are reset
        //echo $myApp->handle($_GET, $_POST, $_COOKIE, $_FILES, $_SERVER);
        $handler = function() {
            $this->handleRequest(
                requestUrlWithoutQueryString: $_SERVER['PHP_SELF'],
                staticallyServeContent: function(string $filePath): void {
                    $mimeType = mimetype($filePath)[1];

                    header("Content-Type: $mimeType");

                    $ret = readfile($filePath);

                    if (!$ret) {
                        if (!file_exists($filePath)) {
                            http_response_code(404);
                        } else {
                            throw new \Exception("readfile failed with $filePath");
                        }
                    }
                },
                prePhpRequest: function() {
                    // frankenphp does care about $_GET, $_POST, $_COOKIE, $_FILES, $_SERVER - BUT NOT about $_REQUEST
                    $_REQUEST = array_merge($_GET, $_POST);
                }
            );
        };

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
    }
}