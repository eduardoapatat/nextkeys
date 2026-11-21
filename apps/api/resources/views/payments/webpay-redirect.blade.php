<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Redirigiendo a Webpay</title>
</head>
<body>
    <form id="webpay" method="post" action="{{ $url }}">
        <input type="hidden" name="token_ws" value="{{ $token }}">
        <noscript><button type="submit">Ir a Webpay</button></noscript>
    </form>
    <p>Redirigiendo a Webpay…</p>
    <script>document.getElementById('webpay').submit();</script>
</body>
</html>
