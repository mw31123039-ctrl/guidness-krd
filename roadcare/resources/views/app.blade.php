<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ckb' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0b1220">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    @vite('resources/js/app.js')
    <x-inertia::head />
</head>
<body><x-inertia::app /></body>
</html>
