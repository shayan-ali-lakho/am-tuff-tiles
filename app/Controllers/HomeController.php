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
            $hero     = [];
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
     * Four pictures for the top of the home page: Tuff Tiles, Fancy Pavers, Heat Insulation Tiles and CC Jali Texture Tile.
     * Each uses the real category name as its label and shows the photo of a product from that category. Categories are
     * found by a word in their name; if one is missing, the next unused category fills the place.
     *
     * @param list<array<string, mixed>> $categories active shop categories
     * @return list<array{label: string, href: string, photo: ?string, fallback: ?string}>
     */
    private function heroCircles(array $categories): array
    {
        $used = [];
        $picked = [];

        foreach (['tuff', 'paver', 'heat', 'jali'] as $word) {
            foreach ($categories as $category) {
                if (!in_array($category['slug'], $used, true) && stripos((string) $category['name'], $word) !== false) {
                    $used[] = $category['slug'];
                    $picked[] = $category;
                    break;
                }
            }
        }

        foreach ($categories as $category) {
            if (count($picked) >= 4) {
                break;
            }

            if (!in_array($category['slug'], $used, true)) {
                $used[] = $category['slug'];
                $picked[] = $category;
            }
        }

        $circles = [];
        foreach ($picked as $category) {
            $slug = (string) $category['slug'];
            $photo = null;

            try {
                $photo = Product::categoryPhoto($slug);
            } catch (Throwable $e) {
                error_log('Hero photo failed: ' . $e->getMessage());
            }

            $circles[] = [
                'label'    => (string) $category['name'],
                'href'     => '/shop?category=' . rawurlencode($slug),
                'photo'    => $photo,
                'fallback' => $slug === 'tuff-tiles' ? 'img/about-tiles.jpg' : null,
            ];
        }

        return $circles;
    }
}
