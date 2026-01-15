<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <link id="favicon" rel="icon" href="/favicon/favicon-0.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Feedback - Pixel Place</title>
    @vite(['resources/css/app.css'])
</head>
<body>
<div id="app">
    <feedback-view></feedback-view>
</div>
@vite(['resources/js/app.js'])
</body>
</html>
