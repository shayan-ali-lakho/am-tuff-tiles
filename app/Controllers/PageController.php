<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Category;
use Throwable;

final class PageController
{
    public function about(): void
    {
        // The page must still open if the database has a problem, so fall back to a fixed list.
        try {
            $categories = Category::forShop();
        } catch (Throwable $e) {
            error_log('About page could not load categories: ' . $e->getMessage());

            $categories = [
                ['name' => 'Tuff Tiles',    'slug' => 'tuff-tiles',    'description' => 'Durable tiles for floors, paths and outdoor areas.'],
                ['name' => 'Doors',         'slug' => 'doors',         'description' => 'Doors for homes, shops and offices.'],
                ['name' => 'Gardens',       'slug' => 'gardens',       'description' => 'Products for gardens and outdoor spaces.'],
                ['name' => 'Metal Gates',   'slug' => 'metal-gates',   'description' => 'Metal gates and grills made to last.'],
                ['name' => 'Roof Ceilings', 'slug' => 'roof-ceilings', 'description' => 'Roof and ceiling solutions for every building.'],
            ];
        }

        seo(['jsonld' => [business_schema()]]);

        echo view('about', [
            'title'       => 'About us',
            'description' => 'About AM Tuff Tiles: tuff tiles, doors, garden products, metal gates, roof ceilings and more, with cash on delivery.',
            'categories'  => $categories,
        ]);
    }
}
