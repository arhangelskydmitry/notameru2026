@extends('layouts.admin')
@section('title', 'Тестирование API Яндекс сервисов')
@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="fas fa-vial"></i> Тестирование API Яндекс сервисов</h1>
        <a href="{{ route('admin.yandex') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Назад к настройкам
        </a>
    </div>

    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i>
        <strong>Информация:</strong> Эта страница проверяет подключение к API Яндекс.Метрики и Яндекс.Вебмастер.
        Если подключение не работает, проверьте правильность токенов и прав доступа.
    </div>

    <div class="row">
        <!-- Яндекс Метрика -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-line"></i> Яндекс Метрика API
                    </h5>
                </div>
                <div class="card-body">
                    @if($results['metrika']['configured'])
                        @if($results['metrika']['connected'])
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle"></i>
                                <strong>Подключение успешно!</strong>
                            </div>

                            <div class="row">
                                <div class="col-sm-4"><strong>Название:</strong></div>
                                <div class="col-sm-8">{{ $results['metrika']['data']['name'] }}</div>

                                <div class="col-sm-4"><strong>Сайт:</strong></div>
                                <div class="col-sm-8">{{ $results['metrika']['data']['site'] }}</div>

                                <div class="col-sm-4"><strong>Статус кода:</strong></div>
                                <div class="col-sm-8">
                                    <span class="badge bg-{{ $results['metrika']['data']['code_status'] === 'CS_OK' ? 'success' : 'warning' }}">
                                        {{ $results['metrika']['data']['code_status'] }}
                                    </span>
                                </div>
                            </div>
                        @else
                            <div class="alert alert-danger">
                                <i class="fas fa-times-circle"></i>
                                <strong>Ошибка подключения:</strong> {{ $results['metrika']['error'] }}
                            </div>

                            <div class="small text-muted">
                                Возможные причины:
                                <ul class="mb-0 mt-2">
                                    <li>Неверный токен доступа</li>
                                    <li>Недостаточно прав доступа</li>
                                    <li>Неверный ID счетчика</li>
                                    <li>Проблемы с сетью</li>
                                </ul>
                            </div>
                        @endif
                    @else
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>API не настроено</strong><br>
                            Заполните ID счетчика и API токен в настройках.
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Яндекс Вебмастер -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-search"></i> Яндекс Вебмастер API
                    </h5>
                </div>
                <div class="card-body">
                    @if($results['webmaster']['configured'])
                        @if($results['webmaster']['connected'])
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle"></i>
                                <strong>Подключение успешно!</strong>
                            </div>

                            <div class="row">
                                <div class="col-sm-4"><strong>Host ID:</strong></div>
                                <div class="col-sm-8">{{ $results['webmaster']['data']['host_id'] }}</div>

                                <div class="col-sm-4"><strong>URL:</strong></div>
                                <div class="col-sm-8">{{ $results['webmaster']['data']['url'] }}</div>

                                <div class="col-sm-4"><strong>Верифицирован:</strong></div>
                                <div class="col-sm-8">
                                    <span class="badge bg-{{ $results['webmaster']['data']['verified'] ? 'success' : 'warning' }}">
                                        {{ $results['webmaster']['data']['verified'] ? 'Да' : 'Нет' }}
                                    </span>
                                </div>
                            </div>
                        @else
                            <div class="alert alert-danger">
                                <i class="fas fa-times-circle"></i>
                                <strong>Ошибка подключения:</strong> {{ $results['webmaster']['error'] }}
                            </div>

                            <div class="small text-muted">
                                Возможные причины:
                                <ul class="mb-0 mt-2">
                                    <li>Неверный токен доступа</li>
                                    <li>Недостаточно прав доступа</li>
                                    <li>Неверный Host ID</li>
                                    <li>Сайт не добавлен в Вебмастер</li>
                                </ul>
                            </div>
                        @endif
                    @else
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>API не настроено</strong><br>
                            Заполните API токен и Host ID в настройках.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Кнопки действий -->
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <a href="{{ route('admin.yandex') }}" class="btn btn-primary">
                        <i class="fas fa-cogs"></i> Вернуться к настройкам
                    </a>
                </div>
                <div class="col-md-6 text-end">
                    <button onclick="location.reload()" class="btn btn-success">
                        <i class="fas fa-sync"></i> Проверить снова
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Дополнительная информация -->
    <div class="card mt-4">
        <div class="card-header bg-light">
            <h5 class="mb-0">
                <i class="fas fa-question-circle"></i> Дополнительная информация
            </h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6>Яндекс Метрика</h6>
                    <ul class="small">
                        <li>API предоставляет доступ к статистике посещений</li>
                        <li>Данные кешируются на 1 час</li>
                        <li>Требуются права "metrika:read"</li>
                        <li>Поддерживает все стандартные метрики</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h6>Яндекс Вебмастер</h6>
                    <ul class="small">
                        <li>API предоставляет данные об индексации</li>
                        <li>Доступны популярные запросы и позиции</li>
                        <li>Требуются права "webmaster:read"</li>
                        <li>Работает только с верифицированными сайтами</li>
                    </ul>
                </div>
            </div>

            <hr>

            <div class="alert alert-info small">
                <i class="fas fa-lightbulb"></i>
                <strong>Совет:</strong> Если подключение не работает, проверьте:
                <ol class="mb-0 mt-2">
                    <li>Правильность токенов доступа</li>
                    <li>Наличие необходимых прав в приложении OAuth</li>
                    <li>Корректность ID счетчика и Host ID</li>
                    <li>Статус верификации сайта в Вебмастер</li>
                </ol>
            </div>
        </div>
    </div>
</div>
@endsection







