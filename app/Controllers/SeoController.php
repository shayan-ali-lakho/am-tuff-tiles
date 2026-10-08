<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Env;
use App\Models\Category;
use App\Models\Product;
use Throwable;

/** robots.txt and sitemap.xml, made fresh from the database so new products are found by search engines. */
final class SeoController
{
    public function robots(): void
    {
        header('Content-Type: text/plain; charset=utf-8');
        header('X-Robots-Tag: noindex'); // the robots.txt file itself should not be listed

        if (Env::get('SITE_NOINDEX', false) === true) {
            echo "User-agent: *\nDisallow: /\n";

            return;
        }

        echo "User-agent: *\n";
        foreach (['/admin', '/login', '/register', '/forgot-password', '/reset-password', '/cart', '/checkout', '/order', '/orders'] as $path) {
            echo 'Disallow: ' . $path . "\n";
        }
        echo "\nSitemap: " . site_url() . "/sitemap.xml\n";
    }

    public function sitemap(): void
    {
        $site = site_url();
        $urls = [
            [$site . '/', null, 'weekly', '1.0'],
            [$site . '/shop', null, 'daily', '0.9'],
            [$site . '/about', null, 'monthly', '0.5'],
        ];

        try {
            foreach (Category::forShop() as $category) {
                if ((int) $category['product_count'] > 0) {
                    $urls[] = [$site . '/shop?category=' . rawurlencode((string) $category['slug']), null, 'weekly', '0.8'];
                }
            }

            foreach (Product::sitemapRows() as $row) {
                $urls[] = [$site . '/product/' . $row['slug'], date('c', strtotime((string) $row['updated_at'])), 'weekly', '0.7'];
            }
        } catch (Throwable $e) {
            error_log('Sitemap could not read the database: ' . $e->getMessage());
        }

        header('Content-Type: application/xml; charset=utf-8');
        header('X-Robots-Tag: noindex');

        $x = static fn (string $v): string => htmlspecialchars($v, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as [$loc, $last, $freq, $priority]) {
            echo '  <url><loc>' . $x($loc) . '</loc>' . ($last !== null ? '<lastmod>' . $x($last) . '</lastmod>' : '')
                . '<changefreq>' . $freq . '</changefreq><priority>' . $priority . "</priority></url>\n";
        }
        echo "</urlset>\n";
    }
}
