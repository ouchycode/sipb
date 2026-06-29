<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>{{ url('/') }}</loc>
        <priority>1.0</priority>
    </url>
    <url>
        <loc>{{ url('/cari') }}</loc>
        <priority>0.9</priority>
    </url>
    <url>
        <loc>{{ url('/bantuan') }}</loc>
        <priority>0.5</priority>
    </url>
    @foreach ($items as $item)
    <url>
        <loc>{{ url('/barang/' . $item->id) }}</loc>
        <lastmod>{{ $item->published_at?->format('Y-m-d') ?? now()->format('Y-m-d') }}</lastmod>
        <priority>0.8</priority>
    </url>
    @endforeach
</urlset>
