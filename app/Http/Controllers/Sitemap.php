<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class Sitemap extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
        <urlset xmlns="https://www.sitemaps.org/schemas/sitemap/0.9"
            xmlns:image="https://www.google.com/schemas/sitemap-image/1.1">';

        $urls = '';
        Product::query()->with(['skus.media'])->chunk(100, function ($products) use (&$urls) {

            foreach ($products as $product) {
                $url = '';
                $url .= "<url> <loc>" . route('product', ['product' => $product->id]) . "</loc>";
                foreach ($product->skus as $sku) {
                    $images = '';
                    foreach ($sku->getMedia('sharees') as $media) {
                        $images .= " <image:image><image:loc>" . $media->getUrl() . "</image:loc></image:image>";
                    }
                }
                $url .= $images . " <lastmod>" . $product->updated_at->toAtomString() . "</lastmod></url>";
                $urls .= $url;
            }
        });

        $xml .= $urls . '</urlset>';

        file_put_contents(public_path('sitemap.xml'), $xml);

        return response(file_get_contents(public_path('sitemap.xml')), 200, [
            'Content-Type' => 'application/xml'
        ]);
    }
}
