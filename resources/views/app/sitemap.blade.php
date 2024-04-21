<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="https://www.sitemaps.org/schemas/sitemap/0.9"
    xmlns:image="https://www.google.com/schemas/sitemap-image/1.1">
    @foreach ($products as $product)
        <url>
            <loc>{{ route('product', ['product' => $product->id]) }}</loc>
            @foreach ($product->skus as $sku)
                @foreach ($sku->getMedia('sharees') as $media)
                    <image:image>
                        <image:loc>{{ $media->getUrl() }}</image:loc>
                    </image:image>
                @endforeach
            @endforeach
            <lastmod>{{ $product->updated_at->toAtomString() }}</lastmod>
        </url>
    @endforeach
</urlset>
