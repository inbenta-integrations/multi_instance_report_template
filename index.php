<?php

use Inbenta\MultiInstanceReporting;
/*
|--------------------------------------------------------------------------
| Register The Auto Loader
|--------------------------------------------------------------------------
|
| Composer provides a convenient, automatically generated class loader for
| our application. 
|
*/

require __DIR__ . '/vendor/autoload.php';

// Get app config
$config = include_once __DIR__ . '/config/app.php';

$app = new MultiInstanceReporting($config, __DIR__);

$request = isset($_REQUEST['action']) ? $_REQUEST : json_decode(file_get_contents('php://input'), true);

if (isset($request['action'])) {
    $app->handleRequest($request);
} else {
    $app->showHome();
}
