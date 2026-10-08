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

        // Search engines: the plain shop and each category page are listed. Any other filter, search or sort is a
        // duplicate view of the same products, so it is kept out of search results (links on it are still followed).
        $base = $categorySlug !== '' ? '/shop?category=' . rawurlencode($categorySlug) : '/shop';
        $extraFilters = $filters['q'] !== '' || $filters['min'] !== null || $filters['max'] !== null || $filters['size'] !== ''
            || $filters['material'] !== '' || $filters['instock'] || $sort !== 'newest';

        if ($extraFilters) {
            seo_noindex(true);
            seo(['canonical' => site_url() . $base]);
        } else {
            seo(['canonical' => site_url() . $base . ($page > 1 ? ($categorySlug !== '' ? '&' : '?') . 'page=' . $page : '')]);
        }

        if ($page > 1 && !$extraFilters) {
            $pageNote = ' - Page ' . $page;
        } else {
            $pageNote = '';
        }

        echo view('shop/index', [
            'title'          => ($activeCategory !== null ? 'Buy ' . $activeCategory['name'] . ' Online' : 'Shop Tuff Tiles, Doors, Gates & More') . $pageNote,
            'description'    => $activeCategory !== null
                ? 'Buy ' . $activeCategory['name'] . ' from AM Tuff Tiles. See sizes and prices in PKR and order online with cash on delivery.'
                : 'Browse tuff tiles, doors, garden products, metal gates, roof ceilings and more from AM Tuff Tiles. Prices in PKR, cash on delivery.',
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

        $images = ProductImage::forProduct((int) $product['id']);
        $site   = site_url();
        $url    = $site . '/product/' . $product['slug'];
        $stock  = (int) $product['stock_qty'];

        $schema = [
            '@context'    => 'https://schema.org',
            '@type'       => 'Product',
            'name'        => (string) $product['name'],
            'description' => $description !== '' ? $description : (string) $product['name'],
            'sku'         => 'AM-' . $product['id'],
            'category'    => (string) $product['category_name'],
            'brand'       => ['@type' => 'Brand', 'name' => (string) config('app.name')],
            'url'         => $url,
            'offers'      => [
                '@type'           => 'Offer',
                'url'             => $url,
                'priceCurrency'   => 'PKR',
                'price'           => number_format((int) $product['price_paisa'] / 100, 2, '.', ''),
                'availability'    => $stock > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                'itemCondition'   => 'https://schema.org/NewCondition',
                'seller'          => ['@type' => 'Organization', 'name' => (string) config('app.name')],
            ],
        ];
        if ((string) ($product['material'] ?? '') !== '') {
            $schema['material'] = (string) $product['material'];
        }
        if ($images !== []) {
            $schema['image'] = array_map(static fn (array $i): string => $site . upload_url((string) $i['file_path']), array_slice($images, 0, 5));
        }

        seo([
            'canonical' => $url,
            'type'      => 'product',
            'image'     => $images !== [] ? $site . upload_url((string) $images[0]['file_path']) : null,
            'jsonld'    => [
                $schema,
                breadcrumb_schema([
                    'Home' => '/', 'Shop' => '/shop',
                    (string) $product['category_name'] => '/shop?category=' . rawurlencode((string) $product['category_slug']),
                    (string) $product['name'] => '/product/' . $product['slug'],
                ]),
            ],
        ]);
        if ($images === []) {
            seo(['image' => $site . asset_path('img/og-default.jpg')]);
        }

        echo view('shop/show', [
            'title'       => $product['name'] . ' - ' . $product['category_name'],
            'description' => $description !== '' ? $description : $product['name'] . ' from AM Tuff Tiles.',
            'product'     => $product,
            'images'      => $images,
            'related'     => Product::related((int) $product['category_id'], (int) $product['id'], 4),
        ]);
    }
}
