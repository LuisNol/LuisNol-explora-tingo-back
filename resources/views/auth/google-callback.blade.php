<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Autenticando con Google...</title>
    <style>
        body { font-family: sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; background: #f5f5f5; }
        .message { text-align: center; color: #333; }
        .spinner { border: 4px solid #f3f3f3; border-top: 4px solid #000; border-radius: 50%; width: 30px; height: 30px; animation: spin 1s linear infinite; margin: 0 auto 1rem; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <div class="message">
        <div class="spinner"></div>
        <p>Autenticando con Google...</p>
    </div>
    <script>
        (function() {
            window.location.href = "{{ $callbackUrlJs }}";
        })();
    </script>
</body>
</html>