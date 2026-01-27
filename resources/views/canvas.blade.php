<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <link rel="icon" href="/favicon/favicon-0.png">
    <meta name="description" content="Place pixels on a shared online canvas. Join the community art experiment.">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="preload" href="https://unpkg.com/maplibre-gl/dist/maplibre-gl.css" as="style" crossorigin>
    <link rel="stylesheet" href="https://unpkg.com/maplibre-gl/dist/maplibre-gl.css" crossorigin>

    <link rel="dns-prefetch" href="https://js.hcaptcha.com">

    <style> body { margin: 0; padding: 0; background: #fff; } </style>
    <title>Pixel Place</title>
    @vite(['resources/css/app.css'])
</head>
<body>
<div id="app">
    <canvas-view></canvas-view>
</div>
</body>
@vite(['resources/js/app.js'])
</html>
