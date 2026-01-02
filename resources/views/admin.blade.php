<!DOCTYPE html>
<html lang="en">
<head>
    <title>Admin</title>
    <style> body { margin: 0; padding: 0; }</style>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link id="favicon" rel="icon" href="/favicon/favicon-0.png">

    <script>
        const frames = [
            '/favicon/favicon-0.png',
            '/favicon/favicon-1.png',
            '/favicon/favicon-2.png',
            '/favicon/favicon-3.png',
            '/favicon/favicon-4.png',
            '/favicon/favicon-5.png',
            '/favicon/favicon-6.png',
            '/favicon/favicon-8.png',
            '/favicon/favicon-9.png',
            '/favicon/favicon-10.png',
            '/favicon/favicon-11.png',
            '/favicon/favicon-12.png',
            '/favicon/favicon-13.png',
            '/favicon/favicon-14.png',
            '/favicon/favicon-15.png',
            '/favicon/favicon-16.png',
            '/favicon/favicon-17.png',
            '/favicon/favicon-18.png',
            '/favicon/favicon-20.png',
        ];

        // Preload all frames
        frames.forEach(src => {
            const img = new Image();
            img.src = src;
        });



        let i = 0;
        setInterval(() => {
            const favicon = document.getElementById('favicon');
            if (favicon) {
                favicon.href = frames[i];
                i = (i + 1) % frames.length;
            }
        }, 100);
    </script>    @vite(['resources/css/app.css'])
</head>
<body>
<div id="app">
    <admin-panel></admin-panel>
</div>
</body>
@vite(['resources/js/app.js'])
</html>
