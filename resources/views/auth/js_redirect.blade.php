<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Redirecting...</title>
    <style>body{margin:0;display:flex;align-items:center;justify-content:center;height:100vh;font-family:sans-serif;background:#fff;}</style>
</head>
<body>
    <p>Signing you in...</p>
    <script>
        (function() {
            var url = @json($url);
            var sep = url.indexOf('?') !== -1 ? '&' : '?';
            window.location.replace(url + sep + '_nc=' + Date.now());
        })();
    </script>
</body>
</html>
