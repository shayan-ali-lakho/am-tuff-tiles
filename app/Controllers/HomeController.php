<?php

declare(strict_types=1);

namespace App\Controllers;

final class HomeController
{
    public function index(): void
    {
        // Static for now; once the database exists (step 2) these come from the categories table.
        $categories = [
            ['name' => 'Tuff Tiles',    'text' => 'Durable tiles for floors, paths and outdoor areas.'],
            ['name' => 'Doors',         'text' => 'Doors for homes, shops and offices.'],
            ['name' => 'Gardens',       'text' => 'Products for gardens and outdoor spaces.'],
            ['name' => 'Metal Gates',   'text' => 'Metal gates and grills made to last.'],
            ['name' => 'Roof Ceilings', 'text' => 'Roof and ceiling solutions for every building.'],
        ];

        echo view('home', [
            'title'      => '',
            'categories' => $categories,
        ]);
    }
}
