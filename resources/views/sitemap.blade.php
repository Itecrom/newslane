<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url><loc>{{ route('home') }}</loc></url>
    @foreach ($categories as $category)
        <url><loc>{{ route('category.show', $category->slug) }}</loc></url>
    @endforeach
    @foreach ($articles as $article)
        <url><loc>{{ route('articles.show', $article->slug) }}</loc><lastmod>{{ $article->updated_at->toAtomString() }}</lastmod></url>
    @endforeach
</urlset>
