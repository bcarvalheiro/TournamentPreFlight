<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Page background (--color-bg-2) before the CSS loads. Single light look by spec. --}}
        <style>
            html {
                background-color: #f8fafc;
            }
        </style>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        {{-- Link previews in chat apps (M09 AC4): Open Graph and X card tags. The event page
             passes $meta (title, description, url); every other page gets the general preview.
             Previews read this HTML and run no JavaScript, so the tags live here. --}}
        @php
            $preview = [
                'title' => $meta['title'] ?? config('app.name'),
                'description' => $meta['description'] ?? 'See how many trainers are heading to each TCG tournament in Portugal.',
                'url' => $meta['url'] ?? url()->current(),
            ];
        @endphp
        <meta name="description" content="{{ $preview['description'] }}">
        <meta property="og:type" content="website">
        <meta property="og:site_name" content="{{ config('app.name') }}">
        <meta property="og:title" content="{{ $preview['title'] }}">
        <meta property="og:description" content="{{ $preview['description'] }}">
        <meta property="og:url" content="{{ $preview['url'] }}">
        <meta property="og:image" content="{{ asset('og-image.png') }}">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
        <meta property="og:image:alt" content="Pre-Flight: should I go or should I stay at home?">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $preview['title'] }}">
        <meta name="twitter:description" content="{{ $preview['description'] }}">

        @fonts

        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/app.tsx', "resources/js/pages/{$page['component']}.tsx"])
        <x-inertia::head>
            <title>{{ config('app.name', 'Laravel') }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
