<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Pixel Place</title>
    <meta name="description" content="Place pixels on a shared online canvas. Join the community art experiment.">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://unpkg.com/maplibre-gl/dist/maplibre-gl.css" rel="stylesheet" media="print" onload="this.media='all'"/>
    <noscript><link href="https://unpkg.com/maplibre-gl/dist/maplibre-gl.css" rel="stylesheet"></noscript>
    <style> body { margin: 0; padding: 0; background: #fff; } </style>
    @vite(['resources/css/app.css'])
</head>
<body>
<div id="app">
    <canvas-view></canvas-view>
</div>
</body>
@vite(['resources/js/app.js'])
</html>
