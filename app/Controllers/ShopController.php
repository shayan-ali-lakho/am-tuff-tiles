<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;

/**
 * Public shop: product list with filters, and the product page. Read-only, no login needed.
 * Everything from the address bar is validated or matched against a fixed list before it reaches the database.
 */
final class ShopController
{
    private const PER_PAGE = 12;

    public function index(): void
    {
        $categories = Category::forShop();
        $options    = Product::shopOptions();

        // Category must be one of the real, turned-on categories
        $categorySlug = (string) ($_GET['category'] ?? '');
        $validSlugs   = array_column($categories, 'slug');
        $categorySlug = in_array($categorySlug, $validSlugs, true) ? $categorySlug : '';

        // Size and material must be one of the values that exist
        $size     = (string) ($_GET['size'] ?? '');
        $material = (string) ($_GET['material'] ?? '');
        $size     = in_array($size, $options['sizes'], true) ? $size : '';
        $material = in_array($material, $options['materials'], true) ? $material : '';

        // Prices typed in PKR; anything that is not a valid amount is ignored. A reversed range is swapped.
        $min = parse_price((string) ($_GET['min'] ?? ''));
        $max = parse_price((string) ($_GET['max'] ?? ''));
        if ($min !== null && $max !== null && $min > $max) {
            [$min, $max] = [$max, $min];
        }

        $sort = (string) ($_GET['sort'] ?? 'newest');
        $sort = array_key_exists($sort, Product::SORTS) ? $sort : 'newest';

        $filters = [
            'q'        => trim(mb_substr((string) ($_GET['q'] ?? ''), 0, 100)),
            'category' => $categorySlug,
            'min'      => $min,
            'max'      => $max,
            'size'     => $size,
            'material' => $material,
            'instock'  => ($_GET['instock'] ?? '') === '1',
        ];

        $total = Product::shopCount($filters);
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page  = min($pages, max(1, (int) ($_GET['page'] ?? 1)));

        $activeCategory = null;
        foreach ($categories as $category) {
            if ($category['slug'] === $categorySlug) {
                $activeCategory = $category;
            }
        }

        echo view('shop/index', [
            'title'          => $activeCategory !== null ? $activeCategory['name'] : 'Shop',
            'description'    => $activeCategory !== null
                ? 'Buy ' . $activeCategory['name'] . ' from AM Tuff Tiles.'
                : 'Browse tuff tiles, doors, garden products, metal gates, roof ceilings and more from AM Tuff Tiles.',
            'products'       => Product::shopSearch($filters, $sort, self::PER_PAGE, ($page - 1) * self::PER_PAGE),
            'categories'     => $categories,
            'options'        => $options,
            'filters'        => $filters,
            'sort'           => $sort,
            'activeCategory' => $activeCategory,
            'total'          => $total,
            'page'           => $page,
            'pages'          => $pages,
        ]);
    }

    public function show(string $slug): void
    {
        $product = preg_match('/^[a-z0-9-]{1,160}$/', $slug) === 1 ? Product::findForShop($slug) : null;

        if ($product === null) {
            abort(404);
        }

        $description = (string) ($product['short_description'] ?? '');
        if ($description === '') {
            $description = mb_substr(trim((string) preg_replace('/\s+/', ' ', (string) ($product['description'] ?? ''))), 0, 160);
        }

        echo view('shop/show', [
            'title'       => $product['name'],
            'description' => $description !== '' ? $description : $product['name'] . ' from AM Tuff Tiles.',
            'product'     => $product,
            'images'      => ProductImage::forProduct((int) $product['id']),
            'related'     => Product::related((int) $product['category_id'], (int) $product['id'], 4),
        ]);
    }
}
