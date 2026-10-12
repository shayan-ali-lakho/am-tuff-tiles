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
            $featured   = Product::featured(12);
            $hero       = $this->heroCircles($categories);
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
            $hero     = $this->heroCircles([]);
        }

        seo(['jsonld' => [business_schema()]]);

        echo view('home', [
            'title'      => '',
            'categories' => $categories,
            'featured'   => $featured,
            'hero'       => $hero,
        ]);
    }

    /**
     * Three pictures for the top of the home page: photos of Tuff Tiles products, each with the product's name.
     * Without any product photo yet, the supplied tile picture is shown once under the category name.
     *
     * @return list<array{label: string, href: string, photo: ?string, fallback: ?string}>
     */
    private function heroCircles(array $categories): array
    {
        $circles = [];

        try {
            foreach (Product::categoryPhotos('tuff-tiles', 3) as $product) {
                $circles[] = [
                    'label'    => (string) $product['name'],
                    'href'     => '/product/' . rawurlencode((string) $product['slug']),
                    'photo'    => (string) $product['image_path'],
                    'fallback' => null,
                ];
            }
        } catch (Throwable $e) {
            error_log('Hero photos failed: ' . $e->getMessage());
        }

        if ($circles === []) {
            $circles[] = ['label' => 'Tuff Tiles', 'href' => '/shop?category=tuff-tiles', 'photo' => null, 'fallback' => 'img/about-tiles.jpg'];
        }

        return $circles;
    }
}
