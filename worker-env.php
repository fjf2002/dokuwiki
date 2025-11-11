<?php

$_SERVER = [
    'SCRIPT_FILENAME' => __FILE__, // neccessary for init.php fullpath function
    'SCRIPT_NAME' => '/index.php', // neccessary for init.php getBaseURL function
    'SERVER_NAME' => getenv('EXTERNAL_DNS_SERVER_NAME'),
    'SERVER_PORT' => getenv('LISTEN_PORT'),
    // 'HTTPS' => 'on' // neccessary for Ip::isSsl function
];

$_SERVER['HTTP_HOST'] = "{$_SERVER['SERVER_NAME']}:{$_SERVER['SERVER_PORT']}"; // neccessary for getBaseURL(?)


unset($LISTEN_PORT);


const ADDITIONAL_IGNORE_GLOBALS = [];
