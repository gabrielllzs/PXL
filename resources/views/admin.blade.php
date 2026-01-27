<!DOCTYPE html>
<html lang="en">
<head>
    <title>Admin</title>
    <style> body { margin: 0; padding: 0; }</style>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="/favicon/favicon-0.png">    @vite(['resources/css/app.css'])
</head>
<body>
<div id="app">
    <admin-panel></admin-panel>
</div>
</body>
@vite(['resources/js/app.js'])
</html>
