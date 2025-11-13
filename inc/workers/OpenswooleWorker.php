<?php

use OpenSwoole\Http\Server;
use OpenSwoole\Http\Request;
use OpenSwoole\Http\Response;


require_once(__DIR__ . '/AbstractWorker.php');


/**
 * DokuWiki header() call workaround
 * Currently MUST BE globally available.
 */
function doku_header(string $header, bool $replace = true, int $response_code = 0): void {
    global $HEADERS; // key: the HTTP Header name; value: an array of HTTP Header values
    global $STATUS;
    global $ERR_CONTENT;

    // https://www.php.net/manual/en/function.header.php
    // There are two special-case header calls...
    if (str_starts_with($header, 'HTTP/')) {
        $arr = explode(' ', $header, 3);
        $STATUS = $arr[1];
        $ERR_CONTENT = $arr[2];
    } else {
        $kv = explode(': ', $header, 2);
        if (count($kv) < 2) {
            throw new \Exception("Malformed Header: $header");
        }

        if ($replace) {
            $HEADERS[$kv[0]] = [$kv[1]];
        } else {
            $HEADERS[$kv[0]] = [...($HEADERS[$kv[0]] ?? []), $kv[1]];
        }

        // The second special case is the "Location:" header.
        // Not only does it send this header back to the browser,
        // but it also returns a REDIRECT (302) status code to the browser
        // unless the 201 or a 3xx status code has already been set.
        if ($kv[0] === 'Location' && $STATUS != 201 && ($STATUS % 100) != 300) {
            $STATUS = 302;
        }
    }

    if ($response_code != 0) {
        $STATUS = $response_code;
    }
}

/**
 * DokuWiki setcookie call workaround
 * Currently MUST BE globally available.
 * Only second form of parameters is supported, see https://www.php.net/manual/de/function.setcookie.php .
 */
function doku_set_cookie(string $name, string $value = "", array $options = []): bool {
    global $RESPONSE_COOKIES;
    $RESPONSE_COOKIES[$name] = [
        'value' => $value,
        ...$options
    ];
    return true;
}

/**
 * Partially from upscale/swoole-session SessionDecorator.php
 * Currently MUST BE globally available.
 */
function doku_session_start(array $options = []): bool {
    global $RESPONSE_COOKIES;

    session_start(); // also potentially generates the session_id value.

    // re-model the session Set-Cookie Header:
    $cookie = session_get_cookie_params();

    $RESPONSE_COOKIES[session_name()] = [
        'value' => session_id(),
        'expires' => $cookie['lifetime'] ? time() + $cookie['lifetime'] : 0,
        'path' => $cookie['path'],
        'domain' => $cookie['domain'],
        'secure' => $cookie['secure'],
        'httponly' => $cookie['httponly'],
        'samesite' => $cookie['samesite']
    ];

    return true;
}


class OpenswooleWorker extends AbstractWorker {

    public function __construct() {
        $this->obLevelToKeep = 1;

        // https://openswoole.com/docs/modules/swoole-runtime-flags
        OpenSwoole\Runtime::enableCoroutine(false);
    }

    private static function initializeSuperglobalsFromRequest(Request $request): void {
        $_SERVER = [
            ...array_change_key_case($request->server, CASE_UPPER),
            'SCRIPT_NAME' => $request->server['request_uri'] === '/'
                ? '/index.php'
                : $request->server['request_uri'], // request_uri is without query string
            'DOCUMENT_ROOT' => DOKU_INC
        ];
        $_GET = $request->get ?? [];
        $_POST = $request->post ?? [];
        $_FILES = $request->files ?? [];
        $_COOKIE = $request->cookie ?? [];

        $_REQUEST = array_merge($_GET, $_POST);

        foreach ($request->header as $key => $value) {
            $_SERVER['HTTP_' . strtoupper(str_replace('-', '_', $key))] = $value;
        }

        if (isset($request->header['host'])) {
            $_SERVER['HTTP_HOST'] = $request->header['host'];
        }

        if (isset($request->cookie)) {
            foreach ($request->cookie as $key => $value) {
                $_COOKIE[$key] = $value;
            }
        }
    }

    protected function prePhpRequest(Request $request): void {
        global $HEADERS, $STATUS, $ERR_CONTENT, $RESPONSE_COOKIES;
        // superglobals need not be declared here.

        self::initializeSuperglobalsFromRequest($request);

        // reset other globals:
        $HEADERS = [];
        $STATUS = null;
        $ERR_CONTENT = null;
        $RESPONSE_COOKIES = [];

        ob_start();
    }

    protected function postPhpRequest(Server $server, Response $response): void {
        global $HEADERS, $STATUS, $ERR_CONTENT, $RESPONSE_COOKIES;

        $content = ob_get_clean();

        foreach ($HEADERS as $headerKey => $headerValues) {
            foreach ($headerValues as $headerValue) {
                $response->header($headerKey, $headerValue);
            }
        }

        foreach ($RESPONSE_COOKIES as $name => $options) {
            $response->setCookie(
                key: $name,
                value: $options['value'],
                expire: $options['expire'] ?? 0,
                path: $options['path'] ?? '',
                domain: $options['domain'] ?? '',
                secure: $options['secure'] ?? false,
                httpOnly: $options['httponly'] ?? false,
                sameSite: $options['samesite'] ?? false,
                priority: ''
            );
        }

        $response->status($STATUS ?? 200);

        // alternatively: won't work - strangely headers_list is always empty - perhaps due to CLI SAPI?
        // foreach (headers_list() as $header) {
        //     [$key, $value] = explode(': ', $header, 2);
        //     $response->header($key, $value);
        // }
        // $response->status(http_response_code() ?: 200);

        $content = $ERR_CONTENT ?? $content;

        $response->header('Content-Length', strlen($content));
        $response->header('Swoole-Worker-Id', $server->worker_id);
        $response->end($content);
    }

    protected function staticallyServeContent(Response $response, string $filePath): void {
        $mimeType = mimetype($filePath)[1];

        $response->header('Content-Type', $mimeType);

        $ret = $response->sendfile($filePath);
        // After the call of sendfile, $response->end() is called automatically, the response is terminated.

        if (!$ret) {
            if (!file_exists($filePath)) {
                $response->status(404);
                $response->end();
            } else {
                throw new \Exception("sendfile failed with $filePath");
            }
        }
    }

    public function run(): void {
        $server = new Server("0.0.0.0", $_SERVER['SERVER_PORT']);

        // https://openswoole.com/article/isolating-variables-with-coroutine-context
        $server->set([
            'enable_coroutine' => false,
            'worker_num' => 20
        ]);

        $server->on('WorkerStart', function($server, $workerId) use (&$conf){
            $this->bootDokuWikiKernel();

            // DO NOT write to stdout - or else you get "headers already sent" problems using headers(), setcookie(), session_start().
            fwrite(STDERR, "Worker $workerId started.\n");
        });

        $server->on('request', function (Request $request, Response $response) use ($server) {
            $this->handleRequest(
                requestUrlWithoutQueryString: $request->server['request_uri'],
                staticallyServeContent: fn($filePath) => $this->staticallyServeContent($response, $filePath),
                prePhpRequest: fn() => $this->prePhpRequest($request),
                postPhpRequest: fn() => $this->postPhpRequest($server, $response),
            );
        });

        $server->start();
    }
}
