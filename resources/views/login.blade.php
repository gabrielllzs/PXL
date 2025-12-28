<!DOCTYPE html>
<html>
<head>
    <title>Laravel</title>
    <style> body { margin: 0; padding: 0; }</style>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css'])
</head>
<body>
<div id="app">
    <login-view></login-view>
</div>
</body>
@vite(['resources/js/app.js'])
</html>
