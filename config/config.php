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
        // EasyPaisa account that customers send payment to. The EasyPaisa option at checkout only appears when a number is set.
        'easypaisa_number' => Env::get('SHOP_EASYPAISA_NUMBER') ?: '',
        'easypaisa_name'   => Env::get('SHOP_EASYPAISA_NAME') ?: '',
        'facebook'  => Env::get('SHOP_FACEBOOK') ?: 'https://www.facebook.com/p/AM-Tuff-Tiles-61555668119271/',
        'instagram' => Env::get('SHOP_INSTAGRAM') ?: 'https://www.instagram.com/amtufftiles/',
        // Map: either a ready "embed" address from Google Maps, or a place/address text to search for
        'map_embed' => Env::get('SHOP_MAP_EMBED') ?: 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3618.436761286689!2d66.96014607642219!3d24.917186842983497!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3eb315b803eb9189%3A0xa6265def8e3db95e!2sAM%20Tuff%20Tiles-%20Site%20Area!5e0!3m2!1sen!2s!4v1791454968208!5m2!1sen!2s',
        'map_query' => Env::get('SHOP_MAP_QUERY') ?: 'AM Tuff Tiles - Site Area, Karachi',
        'map_link'  => Env::get('SHOP_MAP_LINK') ?: '',
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
