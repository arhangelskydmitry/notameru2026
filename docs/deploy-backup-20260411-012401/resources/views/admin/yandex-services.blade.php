@extends('layouts.admin')
@section('title', 'Яндекс сервисы')
@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="fab fa-yandex"></i> Яндекс сервисы</h1>
        <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Назад к панели
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <!-- Яндекс Метрика -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-line"></i> Яндекс Метрика
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-3">
                        Система аналитики веб-сайтов от Яндекса. Позволяет отслеживать посещаемость,
                        поведение пользователей и конверсии.
                    </p>

                    <div class="mb-3">
                        <strong>Текущий статус:</strong>
                        @if($metrikaConfigured)
                            <span class="badge bg-success ms-2">
                                <i class="fas fa-check-circle"></i> Настроено (ID: {{ $settings['metrika_id'] }})
                            </span>
                        @elseif($settings['metrika_id'] || $settings['metrika_token'])
                            <span class="badge bg-warning ms-2">
                                <i class="fas fa-exclamation-triangle"></i> Частично настроено
                            </span>
                        @else
                            <span class="badge bg-secondary ms-2">
                                <i class="fas fa-cog"></i> Не настроено
                            </span>
                        @endif
                    </div>
                    
                    <div class="alert alert-info small">
                        <i class="fas fa-info-circle"></i>
                        <strong>Примечание:</strong> Статус "Настроено" означает, что ID и токен заполнены. 
                        Для проверки <strong>реального подключения к API</strong> используйте кнопку 
                        <strong>"Протестировать API"</strong> ниже.
                    </div>

                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle"></i> Возможности Метрики:</h6>
                        <ul class="mb-0 small">
                            <li>Карта кликов по сайту</li>
                            <li>Вебвизор (записи сессий)</li>
                            <li>Анализ источников трафика</li>
                            <li>Отслеживание конверсий</li>
                            <li>Подробная статистика устройств</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Яндекс Вебмастер -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-search"></i> Яндекс Вебмастер
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-3">
                        Инструмент для вебмастеров от Яндекса. Помогает управлять индексацией сайта
                        и улучшать позиции в поисковой выдаче.
                    </p>

                    <div class="mb-3">
                        <strong>Текущий статус:</strong>
                        @if($webmasterConfigured)
                            <span class="badge bg-success ms-2">
                                <i class="fas fa-check-circle"></i> Настроено
                            </span>
                        @elseif($settings['webmaster_verification'])
                            <span class="badge bg-success ms-2">
                                <i class="fas fa-check"></i> Мета-тег верификации настроен
                            </span>
                        @elseif($settings['webmaster_token'] || $settings['webmaster_host_id'])
                            <span class="badge bg-warning ms-2">
                                <i class="fas fa-exclamation-triangle"></i> Частично настроено
                            </span>
                        @else
                            <span class="badge bg-info ms-2">
                                <i class="fas fa-info-circle"></i> Мета-тег не требуется (если сайт уже верифицирован)
                            </span>
                        @endif
                    </div>
                    
                    @if($webmasterError)
                        <div class="alert alert-danger">
                            <i class="fas fa-times-circle me-1"></i>{{ $webmasterError }}
                        </div>
                    @elseif($webmasterConnected)
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-1"></i>API подключено – данные обновляются каждые 30 минут
                        </div>
                    @else
                        <div class="alert alert-info small">
                            <i class="fas fa-info-circle"></i>
                            <strong>Примечание:</strong> Для проверки реального подключения используйте кнопку "Протестировать API".
                        </div>
                    @endif

                    @if($webmasterConnected)
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <div class="border rounded p-3 h-100">
                                    <h6 class="text-uppercase text-muted small mb-2">Основной хост</h6>
                                    <p class="h5 mb-1">{{ $webmasterHostInfo['host_display_name'] ?? $settings['webmaster_host_id'] }}</p>
                                    <span class="badge bg-{{ ($webmasterHostInfo['verified'] ?? false) ? 'success' : 'warning' }}">
                                        {{ ($webmasterHostInfo['verified'] ?? false) ? 'Верифицирован' : 'Требует проверки' }}
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border rounded p-3 h-100">
                                    <h6 class="text-uppercase text-muted small mb-2">Индексация</h6>
                                    <p class="mb-1"><strong>SQI:</strong> {{ $webmasterIndexingStats['sqi'] ?? '—' }}</p>
                                    <p class="mb-1"><strong>В выдаче:</strong> {{ number_format($webmasterIndexingStats['searchable_pages_count'] ?? 0, 0, ',', ' ') }}</p>
                                    <p class="mb-0 text-muted small">Исключено: {{ number_format($webmasterIndexingStats['excluded_pages_count'] ?? 0, 0, ',', ' ') }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <h6 class="mb-2"><i class="fas fa-list-ul"></i> Доступные сайты в аккаунте</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-striped align-middle">
                                    <thead>
                                        <tr>
                                            <th>Сайт</th>
                                            <th class="text-center">Верификация</th>
                                            <th>Host ID</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach(array_slice($webmasterHosts, 0, 6) as $host)
                                            <tr>
                                                <td>{{ $host['unicode_host_url'] ?? $host['ascii_host_url'] }}</td>
                                                <td class="text-center">
                                                    <span class="badge bg-{{ ($host['verified'] ?? false) ? 'success' : 'secondary' }}">
                                                        {{ ($host['verified'] ?? false) ? 'Да' : 'Нет' }}
                                                    </span>
                                                </td>
                                                <td class="text-muted small">{{ $host['host_id'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @if(count($webmasterHosts) > 6)
                                <p class="small text-muted mb-0">Показаны первые 6 хостов (всего {{ count($webmasterHosts) }}).</p>
                            @endif
                        </div>

                        <div>
                            <h6 class="mb-2"><i class="fas fa-search"></i> Топ поисковых запросов (30 дней)</h6>
                            @if(!empty($webmasterPopularQueries))
                                <ul class="list-group list-group-flush small">
                                    @foreach($webmasterPopularQueries as $query)
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <span>{{ $query['query_text'] ?? '—' }}</span>
                                            <span class="badge bg-primary">
                                                {{ $query['totals'][0] ?? 0 }} кликов
                                            </span>
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <p class="text-muted small mb-0">Нет данных за выбранный период.</p>
                            @endif
                        </div>
                    @else
                        <div class="alert alert-info">
                            <h6><i class="fas fa-info-circle"></i> Возможности Вебмастера:</h6>
                            <ul class="mb-0 small">
                                <li>Мониторинг индексации страниц</li>
                                <li>Анализ поисковых запросов</li>
                                <li>Управление robots.txt</li>
                                <li>Проверка мобильной версии</li>
                                <li>Статистика по регионам</li>
                            </ul>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Настройки -->
    <div class="card">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0">
                <i class="fas fa-cogs"></i> Настройки интеграции
            </h5>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.yandex.update') }}" method="POST">
                @csrf

                <div class="row">
                    <!-- Яндекс Метрика ID -->
                    <div class="col-md-6 mb-3">
                        <label for="metrika_id" class="form-label">
                            <i class="fas fa-hashtag"></i> ID счетчика Яндекс Метрики
                        </label>
                        <input type="text"
                               class="form-control @error('metrika_id') is-invalid @enderror"
                               id="metrika_id"
                               name="metrika_id"
                               value="{{ old('metrika_id', $settings['metrika_id']) }}"
                               placeholder="Например: 12345678"
                               maxlength="20">
                        <div class="form-text">
                            Числовой ID счетчика из кода Яндекс Метрики
                        </div>
                        @error('metrika_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Яндекс Метрика API Token -->
                    <div class="col-md-6 mb-3">
                        <label for="metrika_token" class="form-label">
                            <i class="fas fa-key"></i> API токен Яндекс Метрики
                        </label>
                        <input type="password"
                               class="form-control @error('metrika_token') is-invalid @enderror"
                               id="metrika_token"
                               name="metrika_token"
                               value="{{ old('metrika_token', $settings['metrika_token']) }}"
                               placeholder="OAuth токен для API"
                               maxlength="100">
                        <div class="form-text">
                            <a href="https://oauth.yandex.ru/" target="_blank">Получить токен</a> →
                            Создать приложение → API Яндекс.Метрики
                        </div>
                        @error('metrika_token')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Яндекс Вебмастер код верификации -->
                    <div class="col-md-6 mb-3">
                        <label for="webmaster_verification" class="form-label">
                            <i class="fas fa-shield-alt"></i> Код верификации Вебмастера
                            <small class="text-muted">(опционально)</small>
                        </label>
                        <input type="text"
                               class="form-control @error('webmaster_verification') is-invalid @enderror"
                               id="webmaster_verification"
                               name="webmaster_verification"
                               value="{{ old('webmaster_verification', $settings['webmaster_verification']) }}"
                               placeholder="Оставьте пустым, если сайт уже верифицирован"
                               maxlength="100">
                        <div class="form-text">
                            <strong>Не требуется,</strong> если сайт уже верифицирован в Яндекс.Вебмастер другими способами
                            (HTML-файл, DNS-запись и т.д.)
                        </div>
                        @error('webmaster_verification')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Яндекс Вебмастер API Token -->
                    <div class="col-md-6 mb-3">
                        <label for="webmaster_token" class="form-label">
                            <i class="fas fa-key"></i> API токен Яндекс Вебмастер
                        </label>
                        <input type="password"
                               class="form-control @error('webmaster_token') is-invalid @enderror"
                               id="webmaster_token"
                               name="webmaster_token"
                               value="{{ old('webmaster_token', $settings['webmaster_token']) }}"
                               placeholder="OAuth токен для API"
                               maxlength="100">
                        <div class="form-text">
                            <a href="https://oauth.yandex.ru/" target="_blank">Получить токен</a> →
                            Создать приложение → API Яндекс.Вебмастер
                        </div>
                        @error('webmaster_token')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Яндекс Вебмастер Host ID -->
                    <div class="col-md-6 mb-3">
                        <label for="webmaster_host_id" class="form-label">
                            <i class="fas fa-globe"></i> Host ID Яндекс Вебмастер
                        </label>
                        <input type="text"
                               class="form-control @error('webmaster_host_id') is-invalid @enderror"
                               id="webmaster_host_id"
                               name="webmaster_host_id"
                               value="{{ old('webmaster_host_id', $settings['webmaster_host_id']) }}"
                               placeholder="https:example.com:443"
                               maxlength="100">
                        <div class="form-text">
                            Идентификатор хоста из API Яндекс.Вебмастер
                        </div>
                        @error('webmaster_host_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <hr>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Важно!</strong> После сохранения настроек потребуется перезагрузка сервера
                            для применения изменений на сайте.
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save"></i> Сохранить настройки
                            </button>
                            <a href="{{ route('admin.yandex.test-api') }}" class="btn btn-info">
                                <i class="fas fa-vial"></i> Протестировать API
                            </a>
                            <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Отмена
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Инструкции -->
    <div class="card mt-4">
        <div class="card-header bg-light">
            <h5 class="mb-0">
                <i class="fas fa-question-circle"></i> Как получить ключи доступа
            </h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6>Яндекс Метрика:</h6>
                    <ol class="small">
                        <li>Перейдите на <a href="https://metrika.yandex.ru/" target="_blank">metrika.yandex.ru</a></li>
                        <li>Нажмите "Добавить счетчик"</li>
                        <li>Введите адрес вашего сайта</li>
                        <li>После создания скопируйте ID счетчика из кода</li>
                    </ol>
                </div>
                <div class="col-md-6">
                    <h6>Яндекс Вебмастер:</h6>
                    <div class="alert alert-success small mb-2">
                        <i class="fas fa-check-circle"></i>
                        <strong>Если сайт уже добавлен:</strong> Мета-тег не требуется!
                        Просто оставьте поле пустым.
                    </div>
                    <div class="small text-muted mb-2">
                        <strong>Для API доступа:</strong>
                    </div>
                    <ol class="small mb-3">
                        <li><a href="https://oauth.yandex.ru/" target="_blank">Создайте приложение</a> в Яндекс.OAuth</li>
                        <li>Добавьте права: "Яндекс.Вебмастер"</li>
                        <li>Получите токен авторизации</li>
                        <li>Найдите Host ID через API или интерфейс</li>
                    </ol>
                    <div class="small text-muted mb-2">
                        <strong>Если сайт еще не добавлен:</strong>
                    </div>
                    <ol class="small">
                        <li>Перейдите на <a href="https://webmaster.yandex.ru/" target="_blank">webmaster.yandex.ru</a></li>
                        <li>Нажмите "Добавить сайт"</li>
                        <li>Выберите метод верификации "Мета-тег"</li>
                        <li>Скопируйте код из предложенного мета-тега</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
