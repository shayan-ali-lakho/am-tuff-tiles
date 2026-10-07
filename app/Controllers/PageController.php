<?php

declare(strict_types=1);

namespace App\Controllers;

final class PageController
{
    public function about(): void
    {
        echo view('about', ['title' => 'About us']);
    }
}
