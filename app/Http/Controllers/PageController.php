<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class PageController extends Controller
{
    public function home(): View
    {
        $config = config('tools');
        $brand = (string) ($config['brand'] ?? 'Covertsanything');

        return view('pages.home', [
            'config' => $config,
            'categories' => $config['categories'],
            'features' => $config['features'],
            'title' => $brand.' - Fast PDF, Word, Image & Text Tools',
            'metaDescription' => 'Use '.$brand.' for smooth PDF to Word, Word to PDF, Merge PDF, Split PDF and more. Fast, secure, and easy file conversion online.',
        ]);
    }

    public function category(string $category): View
    {
        $categories = config('tools.categories');
        $brand = (string) config('tools.brand', 'Covertsanything');
        abort_unless(isset($categories[$category]), 404);

        return view('pages.category', [
            'categoryKey' => $category,
            'category' => $categories[$category],
            'title' => $categories[$category]['title'].' - '.$brand,
            'metaDescription' => $categories[$category]['description'].' Powered by '.$brand.'.',
        ]);
    }

    public function tool(string $category, string $tool): View
    {
        $categories = config('tools.categories');
        $pages = config('tools.tool_pages');
        $brand = (string) config('tools.brand', 'Covertsanything');

        abort_unless(isset($categories[$category]), 404);
        abort_unless(isset($pages[$tool]) && $pages[$tool]['category'] === $category, 404);

        return view($pages[$tool]['view'], [
            'tool' => $pages[$tool],
            'category' => $categories[$category],
            'categories' => $categories,
            'title' => $pages[$tool]['title'].' - '.$brand,
            'metaDescription' => $pages[$tool]['description'],
        ]);
    }
}
