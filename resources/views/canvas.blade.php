<!DOCTYPE html>
<html>
<head>
    <title>Laravel</title>
    <link href="https://unpkg.com/maplibre-gl/dist/maplibre-gl.css" rel="stylesheet" />
    <style> body { margin: 0; padding: 0; }</style>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @vite(['resources/css/app.css'])
</head>
<body>
<div id="app">
    <canvas-view></canvas-view>
</div>
</body>
@vite(['resources/js/app.js'])
</html>
