<?php

use dokuwiki\Remote\JsonRpcServer;

if (!defined('DOKU_INC')) define('DOKU_INC', __DIR__ . '/../../');

require_once(DOKU_INC . 'inc/init.php');
init_request();
session_write_close();  //close session

doku_header('Content-Type: application/json');

$server = new JsonRpcServer();
try {
    $result = $server->serve();
} catch (\Exception $e) {
    $result = $server->returnError($e);
}

echo json_encode($result, JSON_THROW_ON_ERROR);
