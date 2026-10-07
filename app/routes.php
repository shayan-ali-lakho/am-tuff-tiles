<?php

declare(strict_types=1);

use App\Controllers\HomeController;
use App\Controllers\PageController;

/** @var App\Core\Router $router */

$router->get('/', [HomeController::class, 'index']);
$router->get('/about', [PageController::class, 'about']);
