<?php echo '<?xml version="1.0" encoding="UTF-8"?>'."\n"; ?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:content="http://purl.org/rss/1.0/modules/content/">
<channel>
    <title>{{ $siteName }}</title>
    <link>{{ $siteUrl }}</link>
    <description>{{ $description }}</description>
    <language>{{ app()->getLocale() === 'en' ? 'en-US' : 'id-ID' }}</language>
    <lastBuildDate>{{ $updated->toRfc822String() }}</lastBuildDate>
    <atom:link href="{{ $selfUrl ?? $siteUrl.'/feed' }}" rel="self" type="application/rss+xml" />
@foreach ($posts as $post)
    <item>
        <title>{{ $post->title }}</title>
        <link>{{ $base }}/berita/{{ $post->slug }}</link>
        <guid isPermaLink="true">{{ $base }}/berita/{{ $post->slug }}</guid>
        <pubDate>{{ $post->published_at?->toRfc822String() }}</pubDate>
        @if ($post->author?->name)<dc:creator xmlns:dc="http://purl.org/dc/elements/1.1/">{{ $post->author->name }}</dc:creator>@endif
        <description><![CDATA[{{ $post->displayExcerpt() }}]]></description>
@if ($fullText)
{{-- wp "Full text": isi lengkap artikel. RSS reader tidak menjalankan <script>,
     jadi kirim body mentah tanpa AutoEmbed, cukup buang quicktag <!--more-->. --}}
        <content:encoded><![CDATA[{!! preg_replace('/<!--\s*more(?:\s+.*?)?\s*-->/is', '', (string) $post->body) !!}]]></content:encoded>
@endif
    </item>
@endforeach
</channel>
</rss>
