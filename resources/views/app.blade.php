<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Яндекс.Отзывы</title>

    {{-- @vite подключает собранные Vite'ом css/js. В dev — с горячей перезагрузкой,
         в проде — минифицированные файлы с хешами. --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    {{-- Точка монтирования Vue: всё приложение отрисуется внутри этого div. --}}
    <div id="app"></div>
</body>
</html>
