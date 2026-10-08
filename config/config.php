<?php

declare(strict_types=1);

use App\Core\Env;

return [
    'app' => [
        'name'     => Env::get('APP_NAME', 'AM Tuff Tiles'),
        'env'      => Env::get('APP_ENV', 'production'),
        'debug'    => Env::get('APP_DEBUG', false) === true,
        'url'      => rtrim((string) Env::get('APP_URL', ''), '/'),
        'timezone' => Env::get('APP_TIMEZONE', 'Asia/Karachi'),
    ],

    'shop' => [
        'currency' => 'PKR',
        'email'    => Env::get('SHOP_EMAIL', 'tufftilesam@gmail.com'),
        'phone'    => Env::get('SHOP_PHONE') ?: '03003715684',
        'whatsapp' => Env::get('SHOP_WHATSAPP') ?: '+923003715684',
        'address'  => Env::get('SHOP_ADDRESS', ''),
        'facebook'  => Env::get('SHOP_FACEBOOK') ?: 'https://www.facebook.com/p/AM-Tuff-Tiles-61555668119271/',
        'instagram' => Env::get('SHOP_INSTAGRAM') ?: '',
    ],

    'db' => [
        'host'    => Env::get('DB_HOST', 'localhost'),
        'port'    => (int) Env::get('DB_PORT', 3306),
        'name'    => Env::get('DB_NAME', ''),
        'user'    => Env::get('DB_USER', ''),
        'pass'    => Env::get('DB_PASS', ''),
        'charset' => 'utf8mb4',
    ],
];
