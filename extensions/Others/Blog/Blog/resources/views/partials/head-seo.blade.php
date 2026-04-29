@php
    $seoTitle = $post->seoTitle();
    $seoDescription = $post->seoDescription();
    $seoImage = $post->seoImageUrl();
    $canonicalUrl = $post->seoCanonicalUrl();
    $seoKeywords = $post->seoKeywords();
    $seoRobots = $post->seoRobots();
    $publishedAt = $post->published_at ?? $post->created_at;
    $updatedAt = $post->updated_at ?? $publishedAt;
    $articleSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => $seoTitle,
        'description' => $seoDescription,
        'url' => $canonicalUrl,
        'mainEntityOfPage' => $canonicalUrl,
        'datePublished' => optional($publishedAt)?->toIso8601String(),
        'dateModified' => optional($updatedAt)?->toIso8601String(),
        'author' => [
            '@type' => 'Organization',
            'name' => config('app.name', 'Paymenter'),
        ],
        'publisher' => [
            '@type' => 'Organization',
            'name' => config('app.name', 'Paymenter'),
        ],
    ];

    if ($seoImage) {
        $articleSchema['image'] = [$seoImage];
    }

    if (!empty($post->tags)) {
        $articleSchema['keywords'] = implode(', ', array_filter($post->tags));
    }
@endphp

<link rel="canonical" href="{{ $canonicalUrl }}">
<meta name="robots" content="{{ $seoRobots }}">

@if($seoKeywords !== '')
    <meta name="keywords" content="{{ $seoKeywords }}">
@endif

<meta property="og:type" content="article">
<meta property="og:title" content="{{ $seoTitle }}">
<meta property="og:description" content="{{ $seoDescription }}">
<meta property="og:url" content="{{ $canonicalUrl }}">

@if($seoImage)
    <meta property="og:image" content="{{ $seoImage }}">
    <meta name="twitter:image" content="{{ $seoImage }}">
@endif

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $seoTitle }}">
<meta name="twitter:description" content="{{ $seoDescription }}">

@if($publishedAt)
    <meta property="article:published_time" content="{{ $publishedAt->toIso8601String() }}">
@endif

@if($updatedAt)
    <meta property="article:modified_time" content="{{ $updatedAt->toIso8601String() }}">
@endif

@foreach(array_filter($post->tags ?? []) as $tag)
    <meta property="article:tag" content="{{ $tag }}">
@endforeach

<script type="application/ld+json">{!! json_encode($articleSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
