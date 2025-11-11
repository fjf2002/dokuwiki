<?php

require_once(__DIR__ . '/worker-env.php');
require_once(__DIR__ . '/inc/workers/FrankenphpWorker.php');

new FrankenphpWorker()->run();
