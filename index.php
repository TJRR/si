<?php

require __DIR__ . '/config/bootstrap.php';

use App\Core\Router;

$rota = isset($_GET['r']) ? $_GET['r'] : 'home/index';

$router = new Router();
$router->despachar($rota);
