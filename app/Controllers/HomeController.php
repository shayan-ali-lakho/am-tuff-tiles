<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Category;
use App\Models\Product;
use Throwable;

final class HomeController
{
    public function index(): void
    {
        // The home page must still open if the database has a problem, so fall back to a fixed list.
        try {
            $categories = Category::forShop();
            $featured   = Product::featured(4);
        } catch (Throwable $e) {
            error_log('Home page could not load shop data: ' . $e->getMessage());

            $categories = [
                ['name' => 'Tuff Tiles',    'slug' => 'tuff-tiles',    'description' => 'Durable tiles for floors, paths and outdoor areas.'],
                ['name' => 'Doors',         'slug' => 'doors',         'description' => 'Doors for homes, shops and offices.'],
                ['name' => 'Gardens',       'slug' => 'gardens',       'description' => 'Products for gardens and outdoor spaces.'],
                ['name' => 'Metal Gates',   'slug' => 'metal-gates',   'description' => 'Metal gates and grills made to last.'],
                ['name' => 'Roof Ceilings', 'slug' => 'roof-ceilings', 'description' => 'Roof and ceiling solutions for every building.'],
            ];
            $featured = [];
        }

        echo view('home', [
            'title'      => '',
            'categories' => $categories,
            'featured'   => $featured,
        ]);
    }
}
