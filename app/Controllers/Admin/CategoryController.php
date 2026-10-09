<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\Category;

/**
 * Admin > Categories (the groups used by the shop filter). Admin access and CSRF are enforced by the router.
 */
final class CategoryController
{
    public function index(): void
    {
        echo view('admin/categories/index', [
            'title'      => 'Categories',
            'categories' => Category::all(),
            'errors'     => flash_get('errors', []),
            'old'        => flash_get('old', []),
        ]);
    }

    public function store(): void
    {
        [$name, $description, $sort, $problem] = $this->read($_POST);

        if ($problem === null && Category::nameTaken($name)) {
            $problem = 'A category with this name already exists.';
        }

        if ($problem !== null) {
            flash('errors', ['new' => $problem]);
            flash('old', ['name' => $name, 'description' => (string) $description, 'sort_order' => (string) $sort]);
            notify('error', 'Category not added', $problem);
            redirect('/admin/categories');
        }

        Category::create($name, $description, $sort, isset($_POST['is_active']));
        notify('success', 'Category added', 'Category "' . $name . '" was added.');
        redirect('/admin/categories');
    }

    public function update(string $id): void
    {
        $category = $this->findOr404($id);
        $categoryId = (int) $category['id'];

        [$name, $description, $sort, $problem] = $this->read($_POST);

        if ($problem === null && Category::nameTaken($name, $categoryId)) {
            $problem = 'A category with this name already exists.';
        }

        if ($problem !== null) {
            flash('errors', [$categoryId => $problem]);
            notify('error', 'Category not saved', $problem);
            redirect('/admin/categories');
        }

        Category::update($categoryId, $name, $description, $sort, isset($_POST['is_active']));
        notify('success', 'Category saved', 'Category "' . $name . '" was saved.');
        redirect('/admin/categories');
    }

    public function destroy(string $id): void
    {
        $category = $this->findOr404($id);
        $categoryId = (int) $category['id'];

        if (Category::productCount($categoryId) > 0) {
            notify('error', 'Cannot delete category', 'You cannot delete "' . $category['name'] . '" while products use it. Move or delete those products first, or just turn the category off.');
            redirect('/admin/categories');
        }

        Category::delete($categoryId);
        notify('success', 'Category deleted', 'Category "' . $category['name'] . '" was deleted.');
        redirect('/admin/categories');
    }

    /** @return array{0: string, 1: ?string, 2: int, 3: ?string} name, description, sort order, problem */
    private function read(array $in): array
    {
        $name        = trim((string) ($in['name'] ?? ''));
        $description = trim((string) ($in['description'] ?? ''));
        $sortText    = trim((string) ($in['sort_order'] ?? '0'));
        $sort        = preg_match('/^-?\d{1,4}$/', $sortText) === 1 ? (int) $sortText : 0;
        $problem     = null;

        $length = mb_strlen($name);

        if ($length < 2 || $length > 100) {
            $problem = 'Enter a category name (2 to 100 characters).';
        } elseif (mb_strlen($description) > 255) {
            $problem = 'The description can be at most 255 characters.';
        } elseif ($sortText !== '' && preg_match('/^-?\d{1,4}$/', $sortText) !== 1) {
            $problem = 'The order must be a whole number.';
        }

        return [$name, $description !== '' ? $description : null, $sort, $problem];
    }

    private function findOr404(string $id): array
    {
        $category = ctype_digit($id) ? Category::find((int) $id) : null;

        return $category ?? abort(404);
    }
}
