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

        echo view('home', [
            'title'      => '',
            'categories' => $categories,
            'featured'   => $featured,
            'hero'       => $hero,
        ]);
    }

    /**
     * Three round pictures for the top of the home page: Doors, Tuff Tiles and Tiles. Each shows the photo of a real product
     * from that category when there is one; otherwise the supplied tile photo (Tuff Tiles) or a plain coloured circle.
     *
     * @param list<array<string, mixed>> $categories active shop categories
     * @return list<array{label: string, href: string, photo: ?string, fallback: ?string}>
     */
    private function heroCircles(array $categories): array
    {
        $slugs = array_column($categories, 'slug');

        // "Tiles": the first other category that has "tile" in its name (for example Soft Tiles)
        $tilesSlug = null;
        foreach ($categories as $category) {
            if ($category['slug'] !== 'tuff-tiles' && stripos((string) $category['slug'], 'tile') !== false) {
                $tilesSlug = (string) $category['slug'];
                break;
            }
        }

        $wanted = [
            ['Doors', 'doors', null],
            ['Tuff Tiles', 'tuff-tiles', 'img/about-tiles.jpg'],
            ['Tiles', $tilesSlug ?? 'tuff-tiles', null],
        ];

        $circles = [];
        foreach ($wanted as [$label, $slug, $fallback]) {
            $photo = null;

            if (in_array($slug, $slugs, true)) {
                try {
                    $photo = Product::categoryPhoto($slug);
                } catch (Throwable $e) {
                    error_log('Hero photo failed: ' . $e->getMessage());
                }
            }

            $circles[] = [
                'label'    => $label,
                'href'     => '/shop' . (in_array($slug, $slugs, true) ? '?category=' . rawurlencode($slug) : ''),
                'photo'    => $photo,
                'fallback' => $fallback,
            ];
        }

        return $circles;
    }
}
