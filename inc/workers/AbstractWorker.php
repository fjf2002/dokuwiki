<?php

/**
 * DokuWiki doku_end_request() call workaround; currently MUST BE globally available.
 */
function doku_end_request(string|int $status = 0): never {
    throw new EndRequestError();
}

// currently MUST BE globally available.
class EndRequestError extends Error {
}

abstract class AbstractWorker {

    // unset request-specific globals - cf. doku.php.
    // VERY DANGEROUS was Edit.php line 58: "if (!isset($TEXT)) {"
    // - the edit window always loaded TEXT from the very first request that edited a file!

    private const GLOBALS_REQUEST_DEPENDENT_LIST = [
        // destilled from doku.php
        'ACT', 'INPUT', 'QUERY', 'ID', 'REV', 'DATE_AT', 'IDX',
        'DATE', 'RANGE', 'HIGH', 'TEXT', 'PRE', 'SUF', 'SUM', 'INFO', 'JSINFO',
        // manually added:
        'MIME', 'EXT', 'CACHE',
        'USERINFO', 'AUTH_ACL', 'MSG', 'MSG_shown', //<--unsure why the latter is neccessary
        // is this mostly media specific?:
        'JUMPTO', 'AUTH', 'NS', 'IMG', 'SRC', 'DEL', 'ERROR', 'INUSE', 'imgMeta', 'fullscreen',
        // Unsure what this is about the 'move' plugin. Why can't it use a class property?
        'PLUGIN_MOVE_WORKING'
    ];

    private const GLOBALS_REQUEST_INDEPENDENT_LIST = [
        // destilled from init.php
        'config_cascade',
        'cache_revinfo', 'cache_wikifn', 'cache_cleanid', 'cache_authname', 'cache_metadata',
        'conf', 'initialConf', 'license',
        'plugin_controller_class', 'plugin_controller',
        'EVENT_HANDLER', 'lang',
        // manually added
        'updateVersion',
        'DOKU_PLUGINS', // TODO address plugin changes?
        'auth', // TODO prevent re-setting this variable o neach request; & address auth plugin changes?
        // parserutils - TODO is this correct?
        'PARSER_MODES', 'METADATA_RENDERERS',
        // not quite sure - when does the TOC have to be reloaded?:
        'TOC',
        //some other stuff
        '__composer_autoload_files',
        'eval_cache', // vscode debug relict?
        ...ADDITIONAL_IGNORE_GLOBALS ?? []
    ];

    // https://www.php.net/manual/en/language.variables.superglobals.php
    private const KNOWN_PHP_SUPERGLOBALS = [
        '_SERVER', '_GET', '_POST', '_FILES', '_COOKIE', '_SESSION', '_REQUEST', '_ENV', 'argv', 'argc',
    ];

    private const KNOWN_WORKER_GLOBALS = [
        'HEADERS', 'STATUS', 'ERR_CONTENT', 'RESPONSE_COOKIES',
        // perhaps frankenphp_worker.php? 'preload', 'config_group', 'config_file', 'local', 'LC'
    ];

    protected int $obLevelToKeep = 0;

    private static function unsetGlobals(): void {
        foreach (self::GLOBALS_REQUEST_DEPENDENT_LIST as $globalVarName) {
            unset($GLOBALS[$globalVarName]);
        }

        $unknownGlobals = array_diff(
            array_keys($GLOBALS),
            self::GLOBALS_REQUEST_INDEPENDENT_LIST,
            self::KNOWN_PHP_SUPERGLOBALS,
            self::KNOWN_WORKER_GLOBALS
        );

        if (count($unknownGlobals) > 0) {
            throw new \Exception("New unknown global variables: " . implode(', ', $unknownGlobals));
        }
    }

    /**
     * Executes PHP script.
     * Can also serve static content.
     *
     * @return false iff the request was not served (just as php -S expects)
     */
    private static function execScript(string $script): void {
        try {
            if (!str_ends_with($script, '.php')) {
                $mimeType = mimetype($script)[1];
                doku_header("Content-Type: $mimeType");
            }

            include $script;
        } catch(EndRequestError $exc) {
            // Dokuwiki wanted to terminate the request. Ignore.
        } finally {
            session_write_close();
            self::unsetGlobals();
        }
    }

    protected function handleRequest(
        string $requestUrlWithoutQueryString,
        Closure $staticallyServeContent,
        Closure $prePhpRequest,
        ?Closure $postPhpRequest = null,
        int $obLevelToKeep = 0
    ): void {
        $postPhpRequest ??= fn() => null;

        $filePath = __DIR__
            . '/../..'
            . $requestUrlWithoutQueryString
            . ($requestUrlWithoutQueryString === '/' ? 'index.php' : '');

        if (!str_ends_with($filePath, '.php')) {
            // fast path: statically serve file
            $staticallyServeContent($filePath);
            return;
        }

        try {
            $prePhpRequest();

            self::execScript($filePath);

            $postPhpRequest();

        } catch (\Throwable $exception) {
            // End output buffers:
            $numObLevels = ob_get_level();
            for ($i = $this->obLevelToKeep; $i < $numObLevels; $i++) {
                ob_end_clean();
            }

            fwrite(STDERR, "Error: {$exception->getMessage()}\n{$exception->getTraceAsString()}\n");
        }
    }

    protected function bootDokuWikiKernel(): void {
        require_once(__DIR__ . '/../init.php');
    }

    public abstract function run(): void;
}
