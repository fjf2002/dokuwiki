<?php

if (!defined('DOKU_INC')) define('DOKU_INC', __DIR__ . '/../../');
if (!defined('NL')) define('NL', "\n");
require_once(DOKU_INC . 'inc/init.php');
init_request(noSession: true, disableGzipOutput: true); // we do not use a session or authentication here (better caching)
require_once(DOKU_INC . 'inc/jquery.php');

// MAIN
doku_header('Content-Type: application/javascript; charset=utf-8');
jquery_out();
