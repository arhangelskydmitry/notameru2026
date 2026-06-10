<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>Удостоверение прессы {{ $card->card_number }}</title>
    <style>
        @page { margin: 0; size: 85.6mm 54mm; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: DejaVu Sans, Arial, sans-serif;
            background: #f0f0f0;
        }
        .card {
            width: 85.6mm;
            height: 54mm;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 55%, #0f3460 100%);
            color: #fff;
            border-radius: 3mm;
            overflow: hidden;
            position: relative;
            padding: 3mm;
        }
        .header {
            text-align: center;
            border-bottom: 0.4mm solid rgba(255,255,255,.25);
            padding-bottom: 1.5mm;
            margin-bottom: 2mm;
        }
        .header .site {
            font-size: 8pt;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: #f54f47;
            font-weight: 700;
        }
        .header .title {
            font-size: 6.5pt;
            letter-spacing: .5px;
            text-transform: uppercase;
            opacity: .9;
        }
        .body {
            display: table;
            width: 100%;
        }
        .photo {
            display: table-cell;
            width: 18mm;
            vertical-align: top;
        }
        .photo-box {
            width: 16mm;
            height: 20mm;
            background: #fff;
            border: 0.3mm solid #ccc;
            overflow: hidden;
        }
        .photo-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .info {
            display: table-cell;
            vertical-align: top;
            padding-left: 2mm;
        }
        .name {
            font-size: 8pt;
            font-weight: 700;
            line-height: 1.2;
            margin-bottom: 1mm;
        }
        .position, .org {
            font-size: 6pt;
            line-height: 1.25;
            opacity: .92;
        }
        .meta {
            margin-top: 2mm;
            font-size: 5.5pt;
            opacity: .85;
        }
        .number {
            position: absolute;
            right: 3mm;
            bottom: 2mm;
            font-size: 5pt;
            font-family: monospace;
            opacity: .75;
        }
        .valid {
            position: absolute;
            left: 3mm;
            bottom: 2mm;
            font-size: 5pt;
            opacity: .75;
        }
        @if(!empty($forPrint))
        body { background: #fff; }
        @endif
    </style>
</head>
<body>
    <div class="card">
        <div class="header">
            <div class="site">Нота Миру</div>
            <div class="title">Удостоверение прессы</div>
        </div>
        <div class="body">
            <div class="photo">
                <div class="photo-box">
                    @if(!empty($photoSrc))
                        <img src="{{ $photoSrc }}" alt="">
                    @endif
                </div>
            </div>
            <div class="info">
                <div class="name">{{ $card->full_name }}</div>
                @if($card->position)
                    <div class="position">{{ $card->position }}</div>
                @endif
                <div class="org">{{ $card->organization }}</div>
                <div class="meta">№ {{ $card->card_number }}</div>
            </div>
        </div>
        <div class="valid">Действительна до {{ $card->expires_at->format('d.m.Y') }}</div>
        <div class="number">{{ $card->verifyUrl() }}</div>
    </div>
</body>
</html>
