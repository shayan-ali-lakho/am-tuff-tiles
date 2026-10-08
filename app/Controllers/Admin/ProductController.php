<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\ImageUploader;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use PDOException;
use RuntimeException;

/**
 * Admin > Products. Access control (admin login) and CSRF checks happen in the router.
 */
final class ProductController
{
    private const PER_PAGE   = 15;
    private const MAX_IMAGES = 8;

    public function index(): void
    {
        $filters = [
            'q'        => trim((string) ($_GET['q'] ?? '')),
            'category' => (int) ($_GET['category'] ?? 0),
            'status'   => (string) ($_GET['status'] ?? ''),
        ];

        if (!in_array($filters['status'], ['', 'active', 'hidden', 'out'], true)) {
            $filters['status'] = '';
        }

        $total = Product::count($filters);
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page  = min($pages, max(1, (int) ($_GET['page'] ?? 1)));

        echo view('admin/products/index', [
            'title'      => 'Products',
            'products'   => Product::search($filters, self::PER_PAGE, ($page - 1) * self::PER_PAGE),
            'categories' => Category::all(),
            'filters'    => $filters,
            'total'      => $total,
            'page'       => $page,
            'pages'      => $pages,
        ]);
    }

    public function create(): void
    {
        echo view('admin/products/form', [
            'title'      => 'Add product',
            'product'    => [],
            'images'     => [],
            'categories' => Category::all(),
            'errors'     => flash_get('errors', []),
            'old'        => flash_get('old', []),
            'maxImages'  => self::MAX_IMAGES,
        ]);
    }

    public function store(): void
    {
        [$data, $errors, $old] = $this->validate($_POST);

        if ($errors !== []) {
            flash('errors', $errors);
            flash('old', $old);
            redirect('/admin/products/new');
        }

        $id = Product::create($data);
        [$saved, $problems] = $this->handleUploads($id);

        $this->report('Product added.', $saved, $problems);
        redirect('/admin/products/' . $id . '/edit');
    }

    public function edit(string $id): void
    {
        $product = $this->findOr404($id);

        echo view('admin/products/form', [
            'title'      => 'Edit product',
            'product'    => $product,
            'images'     => ProductImage::forProduct((int) $product['id']),
            'categories' => Category::all(),
            'errors'     => flash_get('errors', []),
            'old'        => flash_get('old', []),
            'maxImages'  => self::MAX_IMAGES,
        ]);
    }

    public function update(string $id): void
    {
        $product = $this->findOr404($id);
        $productId = (int) $product['id'];

        [$data, $errors, $old] = $this->validate($_POST);

        if ($errors !== []) {
            flash('errors', $errors);
            flash('old', $old);
            flash('error', 'Please fix the highlighted fields. Any photos you selected need to be chosen again.');
            redirect('/admin/products/' . $productId . '/edit');
        }

        Product::update($productId, $data);
        [$saved, $problems] = $this->handleUploads($productId);

        $this->report('Product saved.', $saved, $problems);
        redirect('/admin/products/' . $productId . '/edit');
    }

    public function toggle(string $id): void
    {
        $product = $this->findOr404($id);
        $makeActive = (int) $product['is_active'] !== 1;

        Product::setActive((int) $product['id'], $makeActive);
        flash('success', '"' . $product['name'] . '" is now ' . ($makeActive ? 'visible in the shop.' : 'hidden from the shop.'));
        redirect($this->backTo());
    }

    public function destroy(string $id): void
    {
        $product = $this->findOr404($id);
        $productId = (int) $product['id'];

        $images = ProductImage::forProduct($productId);
        Product::delete($productId); // photo rows are removed by the database

        foreach ($images as $image) {
            ImageUploader::remove((string) $image['file_path']);
        }

        flash('success', '"' . $product['name'] . '" was deleted.');
        redirect('/admin/products');
    }

    public function makePrimary(string $id, string $imageId): void
    {
        $product = $this->findOr404($id);
        $image = $this->findImageOr404($product, $imageId);

        ProductImage::setPrimary((int) $product['id'], (int) $image['id']);
        flash('success', 'Main photo updated.');
        redirect('/admin/products/' . (int) $product['id'] . '/edit');
    }

    public function deleteImage(string $id, string $imageId): void
    {
        $product = $this->findOr404($id);
        $image = $this->findImageOr404($product, $imageId);

        ProductImage::delete((int) $product['id'], (int) $image['id']);
        ImageUploader::remove((string) $image['file_path']);

        flash('success', 'Photo deleted.');
        redirect('/admin/products/' . (int) $product['id'] . '/edit');
    }

    /**
     * @return array{0: array<string, mixed>, 1: array<string, string>, 2: array<string, string>} data, errors, old input
     */
    private function validate(array $in): array
    {
        $errors = [];

        $name        = trim((string) ($in['name'] ?? ''));
        $categoryId  = (int) ($in['category_id'] ?? 0);
        $priceText   = trim((string) ($in['price'] ?? ''));
        $short       = trim((string) ($in['short_description'] ?? ''));
        $description = trim((string) ($in['description'] ?? ''));
        $size        = trim((string) ($in['size'] ?? ''));
        $material    = trim((string) ($in['material'] ?? ''));
        $stockText   = trim((string) ($in['stock_qty'] ?? ''));

        $nameLength = mb_strlen($name);
        if ($nameLength < 2 || $nameLength > 190) {
            $errors['name'] = 'Enter a product name (2 to 190 characters).';
        }

        if ($categoryId < 1 || Category::find($categoryId) === null) {
            $errors['category_id'] = 'Choose a category.';
        }

        $price = parse_price($priceText);
        if ($price === null) {
            $errors['price'] = 'Enter the price in PKR, for example 1250 or 1250.50.';
        } elseif ($price < 100 || $price > 1000000000) {
            $errors['price'] = 'The price must be between PKR 1 and PKR 10,000,000.';
        }

        if (mb_strlen($short) > 255) {
            $errors['short_description'] = 'The short description can be at most 255 characters.';
        }

        if (mb_strlen($description) > 5000) {
            $errors['description'] = 'The description can be at most 5,000 characters.';
        }

        if (mb_strlen($size) > 80) {
            $errors['size'] = 'Size can be at most 80 characters.';
        }

        if (mb_strlen($material) > 80) {
            $errors['material'] = 'Material can be at most 80 characters.';
        }

        if (preg_match('/^\d{1,7}$/', $stockText) !== 1) {
            $errors['stock_qty'] = 'Enter the quantity in stock as a whole number (0 or more).';
        }

        $data = [
            'category_id'       => $categoryId,
            'name'              => $name,
            'short_description' => $short !== '' ? $short : null,
            'description'       => $description !== '' ? $description : null,
            'price_paisa'       => $price ?? 0,
            'size'              => $size !== '' ? $size : null,
            'material'          => $material !== '' ? $material : null,
            'stock_qty'         => (int) $stockText,
            'is_active'         => isset($in['is_active']) ? 1 : 0,
            'is_featured'       => isset($in['is_featured']) ? 1 : 0,
        ];

        $old = [
            'name'              => $name,
            'category_id'       => (string) $categoryId,
            'price'             => $priceText,
            'short_description' => $short,
            'description'       => $description,
            'size'              => $size,
            'material'          => $material,
            'stock_qty'         => $stockText,
            'is_active'         => $data['is_active'] === 1 ? '1' : '0',
            'is_featured'       => $data['is_featured'] === 1 ? '1' : '0',
        ];

        return [$data, $errors, $old];
    }

    /**
     * Save the photos sent with the form. A bad photo is skipped and reported; the others are kept.
     *
     * @return array{0: int, 1: list<string>} number saved, problems
     */
    private function handleUploads(int $productId): array
    {
        $saved = 0;
        $problems = [];
        $existing = ProductImage::count($productId);

        foreach ($this->uploadedFiles('images') as $file) {
            if ((int) $file['error'] === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            $label = mb_substr((string) $file['name'], 0, 60);

            if ($existing + $saved >= self::MAX_IMAGES) {
                $problems[] = $label . ': a product can have at most ' . self::MAX_IMAGES . ' photos.';
                continue;
            }

            try {
                $path = ImageUploader::store($file);
            } catch (RuntimeException $e) {
                $problems[] = $label . ': ' . $e->getMessage();
                continue;
            }

            try {
                ProductImage::add($productId, $path, null);
                $saved++;
            } catch (PDOException $e) {
                ImageUploader::remove($path); // do not leave a file with no database row
                throw $e;
            }
        }

        return [$saved, $problems];
    }

    /** Turn PHP's awkward $_FILES layout for multiple files into a simple list. */
    private function uploadedFiles(string $field): array
    {
        $raw = $_FILES[$field] ?? null;

        if (!is_array($raw) || !is_array($raw['name'] ?? null)) {
            return [];
        }

        $files = [];

        foreach (array_keys($raw['name']) as $i) {
            $files[] = [
                'name'     => $raw['name'][$i],
                'tmp_name' => $raw['tmp_name'][$i],
                'size'     => $raw['size'][$i],
                'error'    => $raw['error'][$i],
            ];
        }

        return $files;
    }

    private function report(string $message, int $saved, array $problems): void
    {
        if ($saved > 0) {
            $message .= ' ' . $saved . ($saved === 1 ? ' photo' : ' photos') . ' added.';
        }

        flash('success', $message);

        if ($problems !== []) {
            flash('warning', 'Some photos were not added: ' . implode(' | ', $problems));
        }
    }

    private function findOr404(string $id): array
    {
        $product = ctype_digit($id) ? Product::find((int) $id) : null;

        return $product ?? abort(404);
    }

    private function findImageOr404(array $product, string $imageId): array
    {
        $image = ctype_digit($imageId) ? ProductImage::find((int) $imageId) : null;

        if ($image === null || (int) $image['product_id'] !== (int) $product['id']) {
            abort(404);
        }

        return $image;
    }

    /** After hiding or showing from the list, return to the same filtered list (local paths only). */
    private function backTo(): string
    {
        return safe_next($_POST['back'] ?? null, '/admin/products');
    }
}
