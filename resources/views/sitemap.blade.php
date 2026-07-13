{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>{{ route('academy.home') }}</loc>
        <changefreq>weekly</changefreq>
    </url>
@foreach ($courses as $course)
    <url>
        <loc>{{ route('academy.course', $course) }}</loc>
        <lastmod>{{ $course->updated_at->toAtomString() }}</lastmod>
    </url>
@foreach ($course->publishedLessons as $lesson)
    <url>
        <loc>{{ route('academy.lesson', [$course, $lesson]) }}</loc>
        <lastmod>{{ $lesson->updated_at->toAtomString() }}</lastmod>
    </url>
@endforeach
@endforeach
</urlset>
