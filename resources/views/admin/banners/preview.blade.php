<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Предпросмотр - {{ $banner->title }}</title>
    <style>
        body {
            margin: 0;
            padding: 20px;
            background: #f8f9fa;
            font-family: Arial, sans-serif;
        }
        .preview-container {
            max-width: 900px;
            margin: 0 auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,.1);
        }
        .banner-info {
            margin-bottom: 20px;
            padding: 15px;
            background: #e7f3ff;
            border-left: 4px solid #0066cc;
        }
        .banner-preview {
            border: 2px dashed #dee2e6;
            padding: 20px;
            text-align: center;
            background: #fff;
        }
    </style>
</head>
<body>
    <div class="preview-container">
        <div class="banner-info">
            <h2>{{ $banner->title }}</h2>
            <p><strong>Зона:</strong> {{ $banner->zone }}</p>
            <p><strong>Тип:</strong> {{ $banner->type }}</p>
            <p><strong>Статус:</strong> {{ $banner->status }}</p>
        </div>
        
        <div class="banner-preview">
            {!! $banner->getHtml() !!}
        </div>
    </div>
</body>
</html>




