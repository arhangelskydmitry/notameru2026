@extends('frontend.layout')

@section('title', 'Страница не найдена - 404 | Нота Миру')
@section('description', 'К сожалению, запрашиваемая страница не найдена.')
@section('robots', 'noindex, follow')

@section('content')

<div class="error-404-page">
    <div class="error-404-block">
        <div class="error-code">404</div>
        <h1>Материал не найден</h1>
        <p>Запрашиваемая страница не существует, была перемещена или временно недоступна.</p>

        <div class="error-actions">
            <a href="{{ url('/') }}" class="error-action-primary">Перейти на главную</a>
            <a href="{{ route('search') }}" class="error-action-secondary">Открыть поиск</a>
        </div>
    </div>
</div>

<style>
.error-404-page {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 55vh;
}

.error-404-block {
    max-width: 720px;
    width: 100%;
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    border-radius: 8px;
    padding: 48px 28px;
    text-align: center;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
}

.error-code {
    font-size: 80px;
    font-weight: 900;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    line-height: 1;
    margin-bottom: 12px;
}

.error-404-block h1 {
    font-size: 30px;
    font-weight: 700;
    margin-bottom: 10px;
    color: #333;
}

.error-404-block p {
    font-size: 16px;
    color: #666;
    margin: 0;
}

.error-actions {
    display: flex;
    justify-content: center;
    gap: 12px;
    flex-wrap: wrap;
    margin-top: 28px;
}

.error-actions a {
    display: inline-block;
    padding: 12px 28px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 600;
}

.error-action-primary {
    background: #c80000;
    color: #fff;
}

.error-action-secondary {
    background: #fff;
    color: #333;
    border: 1px solid #ddd;
}

@media (max-width: 992px) {
    .error-404-page {
        min-height: 45vh;
    }
}

@media (max-width: 768px) {
    .error-404-block {
        padding: 32px 18px;
    }

    .error-code {
        font-size: 60px;
    }

    .error-404-block h1 {
        font-size: 22px;
    }

    .error-404-block p {
        font-size: 14px;
    }
}
</style>
@endsection
