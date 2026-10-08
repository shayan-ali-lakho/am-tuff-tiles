<?php

declare(strict_types=1);

use App\Controllers\Admin\CategoryController;
use App\Controllers\Admin\DashboardController;
use App\Controllers\Admin\OrderController;
use App\Controllers\Admin\ProductController;
use App\Controllers\Admin\ReportController;
use App\Controllers\Admin\UserController;
use App\Controllers\AuthController;
use App\Controllers\CartController;
use App\Controllers\CheckoutController;
use App\Controllers\HomeController;
use App\Controllers\PageController;
use App\Controllers\SeoController;
use App\Controllers\ShopController;

/** @var App\Core\Router $router */

// Public pages
$router->get('/', [HomeController::class, 'index']);
$router->get('/about', [PageController::class, 'about']);
$router->get('/shop', [ShopController::class, 'index']);
$router->get('/product/{slug}', [ShopController::class, 'show']);

// Cart and checkout (cash on delivery)
$router->get('/cart', [CartController::class, 'show']);
$router->post('/cart/add', [CartController::class, 'add']);
$router->post('/cart/update', [CartController::class, 'update']);
$router->post('/cart/remove', [CartController::class, 'remove']);
$router->get('/checkout', [CheckoutController::class, 'show']);
$router->post('/checkout', [CheckoutController::class, 'place']);
$router->get('/order/{number}', [CheckoutController::class, 'confirmation']);
$router->get('/orders', [CheckoutController::class, 'orders']);

// Search engines
$router->get('/robots.txt', [SeoController::class, 'robots']);
$router->get('/sitemap.xml', [SeoController::class, 'sitemap']);

// Accounts
$router->get('/register', [AuthController::class, 'showRegister']);
$router->post('/register', [AuthController::class, 'register']);
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/forgot-password', [AuthController::class, 'showForgot']);
$router->post('/forgot-password', [AuthController::class, 'forgot']);
$router->get('/reset-password/{token}', [AuthController::class, 'showReset']);
$router->post('/reset-password/{token}', [AuthController::class, 'reset']);
$router->post('/logout', [AuthController::class, 'logout']);

// Admin. The router already requires an admin login for every /admin path.
$router->get('/admin', [DashboardController::class, 'index']);

$router->get('/admin/products', [ProductController::class, 'index']);
$router->get('/admin/products/new', [ProductController::class, 'create']);
$router->post('/admin/products', [ProductController::class, 'store']);
$router->get('/admin/products/{id}/edit', [ProductController::class, 'edit']);
$router->post('/admin/products/{id}', [ProductController::class, 'update']);
$router->post('/admin/products/{id}/toggle', [ProductController::class, 'toggle']);
$router->post('/admin/products/{id}/delete', [ProductController::class, 'destroy']);
$router->post('/admin/products/{id}/images/{imageId}/primary', [ProductController::class, 'makePrimary']);
$router->post('/admin/products/{id}/images/{imageId}/delete', [ProductController::class, 'deleteImage']);

$router->get('/admin/orders', [OrderController::class, 'index']);
$router->get('/admin/orders/{id}', [OrderController::class, 'show']);
$router->post('/admin/orders/{id}/status', [OrderController::class, 'status']);

$router->get('/admin/reports', [ReportController::class, 'index']);
$router->get('/admin/reports/export', [ReportController::class, 'export']);

$router->get('/admin/users', [UserController::class, 'index']);
$router->post('/admin/users/{id}/role', [UserController::class, 'role']);

$router->get('/admin/categories', [CategoryController::class, 'index']);
$router->post('/admin/categories', [CategoryController::class, 'store']);
$router->post('/admin/categories/{id}', [CategoryController::class, 'update']);
$router->post('/admin/categories/{id}/delete', [CategoryController::class, 'destroy']);
