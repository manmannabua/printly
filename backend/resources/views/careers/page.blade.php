<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <title>{{ $page->meta_title ?? $page->title }} — {{ $companyName }}</title>
    @if($page->meta_description)
        <meta name="description" content="{{ $page->meta_description }}">
    @endif
    <meta property="og:title" content="{{ $page->meta_title ?? $page->title }} — {{ $companyName }}">
    @if($page->meta_description)
        <meta property="og:description" content="{{ $page->meta_description }}">
    @endif
    <meta property="og:type" content="website">
    <meta name="careers-base" content="{{ careers_path() === '/' ? '' : careers_path() }}">
    <style>[v-cloak] { display: none; }</style>
    @vite('resources/js/careers/main.ts')
</head>
<body class="bg-gray-50 min-h-screen">
    <div id="careers-app" v-cloak
        data-page="cms-page"
        data-cms-page="{{ json_encode(['title' => $page->title, 'slug' => $page->slug, 'content' => $pageContent, 'subtitle' => $page->subtitle, 'hero_text_align' => $page->hero_text_align]) }}"
        data-navigation="{{ json_encode($navigation) }}"
        data-site-settings="{{ json_encode($siteSettings) }}"
        data-company="{{ $companyName }}"
    >
        {{-- Server-rendered fallback for SEO crawlers --}}
        <noscript>
            <header style="padding: 2rem; text-align: center;">
                <h1>{{ $page->title }}</h1>
            </header>
            <main style="max-width: 800px; margin: 0 auto; padding: 1rem;">
                @foreach($pageContent as $block)
                    @if($block['type'] === 'rich_text')
                        <div>{!! $block['data']['html'] ?? '' !!}</div>
                    @elseif($block['type'] === 'hero')
                        <section style="padding: 2rem; text-align: center;">
                            <h2>{{ $block['data']['title'] ?? '' }}</h2>
                            <p>{{ $block['data']['subtitle'] ?? '' }}</p>
                            @if(!empty($block['data']['cta_text']))
                                <a href="{{ $block['data']['cta_url'] ?? careers_path() }}">{{ $block['data']['cta_text'] }}</a>
                            @endif
                        </section>
                    @elseif($block['type'] === 'cta')
                        <section style="padding: 2rem; text-align: center; background: #f3f4f6;">
                            <h2>{{ $block['data']['title'] ?? '' }}</h2>
                            <p>{{ $block['data']['subtitle'] ?? '' }}</p>
                            @if(!empty($block['data']['button_text']))
                                <a href="{{ $block['data']['button_url'] ?? careers_path() }}">{{ $block['data']['button_text'] }}</a>
                            @endif
                        </section>
                    @elseif($block['type'] === 'image')
                        @if(!empty($block['data']['image_url']))
                            <figure style="text-align: center; margin: 2rem 0;">
                                <img src="{{ $block['data']['image_url'] }}" alt="{{ $block['data']['alt'] ?? $block['data']['image_alt'] ?? '' }}" style="max-width: 100%;">
                                @if(!empty($block['data']['caption']))
                                    <figcaption>{{ $block['data']['caption'] }}</figcaption>
                                @endif
                            </figure>
                        @endif
                    @elseif($block['type'] === 'values' || $block['type'] === 'benefits')
                        <section style="padding: 2rem 0;">
                            <h2>{{ $block['data']['title'] ?? '' }}</h2>
                            @foreach($block['data']['items'] ?? [] as $item)
                                <div style="margin: 1rem 0;">
                                    <h3>{{ $item['title'] ?? '' }}</h3>
                                    <p>{{ $item['description'] ?? '' }}</p>
                                </div>
                            @endforeach
                        </section>
                    @elseif($block['type'] === 'testimonials')
                        <section style="padding: 2rem 0;">
                            <h2>{{ $block['data']['title'] ?? '' }}</h2>
                            @foreach($block['data']['items'] ?? [] as $item)
                                <blockquote style="margin: 1rem 0; padding: 1rem; border-left: 3px solid #6366f1;">
                                    <p>"{{ $item['quote'] ?? '' }}"</p>
                                    <cite>— {{ $item['name'] ?? '' }}{{ !empty($item['role']) ? ', ' . $item['role'] : '' }}</cite>
                                </blockquote>
                            @endforeach
                        </section>
                    @elseif($block['type'] === 'featured_jobs')
                        <section style="padding: 2rem 0;">
                            <h2>{{ $block['data']['title'] ?? 'Featured Positions' }}</h2>
                            <p><a href="{{ careers_path() }}">View all open positions</a></p>
                        </section>
                    @endif
                @endforeach
            </main>
        </noscript>
    </div>
</body>
</html>
