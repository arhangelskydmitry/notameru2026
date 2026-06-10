@extends('layouts.admin')

@section('title', 'Аналитика - Яндекс Метрика')

@section('content')
@php($webmaster = $webmasterData ?? [
    'configured' => false,
    'connected' => false,
    'hostInfo' => [],
    'indexingStats' => [],
    'popularQueries' => [],
    'lowCtrQueries' => [],
    'highCtrQueries' => [],
    'hosts' => [],
    'issues' => [],
    'recommendations' => [],
    'error' => null,
])

<div class="container-fluid">
    <!-- Заголовок -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <i class="fas fa-chart-line me-2"></i>Аналитика - Яндекс Метрика
        </h1>
        <div>
            <small class="text-muted">Последние 30 дней</small>
        </div>
    </div>

    @if(!$isConnected)
    <!-- Предупреждение о настройке -->
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <strong>Яндекс Метрика не подключена!</strong> 
        API не отвечает или настройки заданы неверно. 
        Пожалуйста, проверьте настройки в разделе 
        <a href="{{ route('admin.yandex') }}" class="alert-link">Яндекс сервисы</a> 
        и убедитесь, что API токен и ID счетчика указаны правильно.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <!-- Сводная статистика - Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-primary bg-opacity-10 rounded-3 p-3">
                                <i class="fas fa-eye text-primary fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1 small">Визиты</h6>
                            <h3 class="mb-0">{{ number_format($summary['visits']) }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-success bg-opacity-10 rounded-3 p-3">
                                <i class="fas fa-users text-success fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1 small">Уникальные</h6>
                            <h3 class="mb-0">{{ number_format($summary['users']) }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-info bg-opacity-10 rounded-3 p-3">
                                <i class="fas fa-file-alt text-info fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1 small">Просмотры</h6>
                            <h3 class="mb-0">{{ number_format($summary['pageviews']) }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-warning bg-opacity-10 rounded-3 p-3">
                                <i class="fas fa-clock text-warning fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1 small">Среднее время</h6>
                            <h3 class="mb-0">{{ gmdate('i:s', $summary['avgTime']) }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- График посещений -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-area me-2 text-primary"></i>
                        Динамика посещений
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="visitsChart" height="80"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Две колонки: Популярные страницы и Источники -->
    <div class="row g-3 mb-4">
        <!-- Популярные страницы -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0">
                        <i class="fas fa-fire me-2 text-danger"></i>
                        Топ-10 страниц
                    </h5>
                </div>
                <div class="card-body">
                    @if(!empty($popularPages['data']))
                    <div class="table-responsive">
                        <table class="table table-hover table-sm">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Страница</th>
                                    <th class="text-end">Просмотры</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($popularPages['data'] as $index => $page)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        <small class="text-truncate d-inline-block" style="max-width: 300px;" title="{{ $page['dimensions'][0]['name'] }}">
                                            {{ $page['dimensions'][0]['name'] }}
                                        </small>
                                    </td>
                                    <td class="text-end">
                                        <span class="badge bg-primary">{{ number_format($page['metrics'][0]) }}</span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <p class="text-muted text-center py-4 mb-0">
                        <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                        Нет данных о популярных страницах
                    </p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Источники трафика -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0">
                        <i class="fas fa-share-alt me-2 text-success"></i>
                        Источники трафика
                    </h5>
                </div>
                <div class="card-body d-flex align-items-center justify-content-center">
                    @if(!empty($trafficSources['data']))
                    <canvas id="sourcesChart" height="200"></canvas>
                    @else
                    <p class="text-muted text-center py-4 mb-0">
                        <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                        Нет данных об источниках
                    </p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Три колонки: Устройства, Браузеры, География -->
    <div class="row g-3">
        <!-- Устройства -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0">
                        <i class="fas fa-mobile-alt me-2 text-info"></i>
                        Устройства
                    </h5>
                </div>
                <div class="card-body d-flex align-items-center justify-content-center">
                    @if(!empty($deviceStats['data']))
                    <canvas id="devicesChart" height="200"></canvas>
                    @else
                    <p class="text-muted text-center py-4 mb-0">
                        <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                        Нет данных
                    </p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Браузеры -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0">
                        <i class="fas fa-window-restore me-2 text-warning"></i>
                        Браузеры
                    </h5>
                </div>
                <div class="card-body">
                    @if(!empty($browserStats['data']))
                    <div class="list-group list-group-flush">
                        @foreach(array_slice($browserStats['data'], 0, 5) as $browser)
                        <div class="list-group-item px-0 border-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="small">{{ $browser['dimensions'][0]['name'] }}</span>
                                <span class="badge bg-secondary">{{ number_format($browser['metrics'][0]) }}</span>
                            </div>
                            <div class="progress mt-2" style="height: 6px;">
                                <div class="progress-bar" role="progressbar" 
                                     style="width: {{ ($browser['metrics'][0] / $browserStats['totals'][0] * 100) }}%">
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <p class="text-muted text-center py-4 mb-0">
                        <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                        Нет данных
                    </p>
                    @endif
                </div>
            </div>
        </div>

        <!-- География -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0">
                        <i class="fas fa-globe me-2 text-danger"></i>
                        География
                    </h5>
                </div>
                <div class="card-body">
                    @if(!empty($geographyStats['data']))
                    <div class="list-group list-group-flush">
                        @foreach(array_slice($geographyStats['data'], 0, 5) as $country)
                        <div class="list-group-item px-0 border-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="small">{{ $country['dimensions'][0]['name'] }}</span>
                                <span class="badge bg-secondary">{{ number_format($country['metrics'][0]) }}</span>
                            </div>
                            <div class="progress mt-2" style="height: 6px;">
                                <div class="progress-bar bg-danger" role="progressbar" 
                                     style="width: {{ ($country['metrics'][0] / $geographyStats['totals'][0] * 100) }}%">
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <p class="text-muted text-center py-4 mb-0">
                        <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                        Нет данных
                    </p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Информация о показателях -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm bg-light">
                <div class="card-body">
                    <h6 class="mb-3"><i class="fas fa-info-circle me-2"></i>Дополнительные показатели</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <small class="text-muted">
                                <strong>Показатель отказов:</strong> {{ $summary['bounceRate'] }}%
                            </small>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted">
                                <strong>Глубина просмотра:</strong> {{ $summary['pageviews'] > 0 ? round($summary['pageviews'] / $summary['visits'], 2) : 0 }} страниц/визит
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Вебмастер -->
    <div class="mt-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h4 mb-0">
                <i class="fas fa-search me-2 text-info"></i>Аналитика - Яндекс Вебмастер
            </h2>
            <small class="text-muted">Обновление раз в ~30 минут</small>
        </div>

        @if(!$webmaster['configured'])
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                Чтобы увидеть данные из Вебмастера, укажите API токен и Host ID в разделе
                <a href="{{ route('admin.yandex') }}">Яндекс сервисы</a>.
            </div>
        @elseif($webmaster['error'])
            <div class="alert alert-danger">
                <i class="fas fa-times-circle me-2"></i>{{ $webmaster['error'] }}
            </div>
        @elseif(!$webmaster['connected'])
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>
                Вебмастер недоступен. Проверьте права токена и статус сайта.
            </div>
        @else
            <div class="row g-3 mb-4">
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <h6 class="text-muted text-uppercase small mb-2">Основной хост</h6>
                            <p class="h5 mb-1">{{ $webmaster['hostInfo']['host_display_name'] ?? '—' }}</p>
                            <p class="text-muted small mb-2">{{ $webmaster['hostInfo']['unicode_host_url'] ?? $webmaster['hostInfo']['ascii_host_url'] ?? '' }}</p>
                            <span class="badge bg-{{ ($webmaster['hostInfo']['verified'] ?? false) ? 'success' : 'warning' }}">
                                {{ ($webmaster['hostInfo']['verified'] ?? false) ? 'Верифицирован' : 'Требует проверки' }}
                            </span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <h6 class="text-muted text-uppercase small mb-2">SQI</h6>
                            <div class="d-flex align-items-center">
                                <span class="display-6 me-3">{{ $webmaster['indexingStats']['sqi'] ?? '—' }}</span>
                                <div>
                                    <p class="mb-1 small text-muted">Индекс качества сайта</p>
                                    <span class="badge bg-{{ ($webmaster['indexingStats']['sqi'] ?? 0) >= 40 ? 'success' : 'warning' }}">
                                        {{ ($webmaster['indexingStats']['sqi'] ?? 0) >= 40 ? 'Норма' : 'Нужны улучшения' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <h6 class="text-muted text-uppercase small mb-2">Индексация</h6>
                            <p class="mb-1"><strong>В выдаче:</strong> {{ number_format($webmaster['indexingStats']['searchable_pages_count'] ?? 0, 0, ',', ' ') }}</p>
                            <p class="mb-1"><strong>Исключено:</strong> {{ number_format($webmaster['indexingStats']['excluded_pages_count'] ?? 0, 0, ',', ' ') }}</p>
                            <p class="mb-0 text-muted small">
                                Проблемы: {{ !empty($webmaster['issues']) ? implode(', ', $webmaster['issues']) : 'нет' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white border-0 py-3">
                            <h5 class="mb-0"><i class="fas fa-search me-2 text-primary"></i>Популярные запросы</h5>
                        </div>
                        <div class="card-body">
                            @if(!empty($webmaster['popularQueries']))
                                <div class="table-responsive">
                                    <table class="table table-sm align-middle">
                                        <thead>
                                            <tr>
                                                <th>Запрос</th>
                                                <th class="text-end">Показы</th>
                                                <th class="text-end">Клики</th>
                                                <th class="text-end">CTR</th>
                                                <th class="text-end">Поз.</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($webmaster['popularQueries'] as $query)
                                                <tr>
                                                    <td style="min-width: 160px;">{{ $query['query_text'] ?? '—' }}</td>
                                                    <td class="text-end">
                                                        <span class="badge bg-light text-dark">{{ number_format($query['shows'] ?? 0, 0, ',', ' ') }}</span>
                                                    </td>
                                                    <td class="text-end">
                                                        <span class="badge bg-primary">{{ number_format($query['clicks'] ?? 0, 0, ',', ' ') }}</span>
                                                    </td>
                                                    <td class="text-end">
                                                        <span class="badge bg-{{ ($query['ctr'] ?? 0) >= 2 ? 'success' : 'warning' }}">
                                                            {{ number_format($query['ctr'] ?? 0, 2, ',', ' ') }}%
                                                        </span>
                                                    </td>
                                                    <td class="text-end text-muted">
                                                        {{ $query['avg_position'] ? number_format($query['avg_position'], 1, ',', ' ') : '—' }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <p class="text-muted text-center mb-0">Недостаточно данных по поисковым запросам.</p>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white border-0 py-3">
                            <h5 class="mb-0"><i class="fas fa-lightbulb me-2 text-warning"></i>Рекомендации</h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush small">
                                @foreach($webmaster['recommendations'] as $recommendation)
                                    <li class="list-group-item border-0 px-0">
                                        <i class="fas fa-check-circle text-success me-2"></i>{{ $recommendation }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            @if(!empty($webmaster['lowCtrQueries']) || !empty($webmaster['highCtrQueries']))
            <div class="row g-3 mt-1">
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white border-0 py-3">
                            <h5 class="mb-0"><i class="fas fa-arrow-down me-2 text-danger"></i>Запросы с низким CTR</h5>
                            <small class="text-muted">Стоит переписать сниппеты и заголовки</small>
                        </div>
                        <div class="card-body">
                            @if(!empty($webmaster['lowCtrQueries']))
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Запрос</th>
                                        <th class="text-end">CTR</th>
                                        <th class="text-end">Показы</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($webmaster['lowCtrQueries'] as $query)
                                    <tr>
                                        <td>{{ $query['query_text'] }}</td>
                                        <td class="text-end text-danger">{{ number_format($query['ctr'], 2, ',', ' ') }}%</td>
                                        <td class="text-end">{{ number_format($query['shows'], 0, ',', ' ') }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            @else
                            <p class="text-muted mb-0">Нет запросов с низким CTR — отличная работа!</p>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white border-0 py-3">
                            <h5 class="mb-0"><i class="fas fa-arrow-up me-2 text-success"></i>Запросы-лидеры</h5>
                            <small class="text-muted">Лучшие CTR — используйте тему шире</small>
                        </div>
                        <div class="card-body">
                            @if(!empty($webmaster['highCtrQueries']))
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Запрос</th>
                                        <th class="text-end">CTR</th>
                                        <th class="text-end">Клики</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($webmaster['highCtrQueries'] as $query)
                                    <tr>
                                        <td>{{ $query['query_text'] }}</td>
                                        <td class="text-end text-success">{{ number_format($query['ctr'], 2, ',', ' ') }}%</td>
                                        <td class="text-end">{{ number_format($query['clicks'], 0, ',', ' ') }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            @else
                            <p class="text-muted mb-0">Собираем статистику — данных пока недостаточно.</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @endif
        @endif
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // График посещений
    @if(!empty($visitsData['data']))
    const visitsData = @json($visitsData['data']);
    const labels = visitsData.map(item => {
        const date = new Date(item.dimensions[0].name);
        return date.toLocaleDateString('ru-RU', { day: 'numeric', month: 'short' });
    });
    const visits = visitsData.map(item => item.metrics[0]);
    const users = visitsData.map(item => item.metrics[1]);
    const pageviews = visitsData.map(item => item.metrics[2]);

    const visitsCtx = document.getElementById('visitsChart');
    new Chart(visitsCtx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Визиты',
                    data: visits,
                    borderColor: 'rgb(54, 162, 235)',
                    backgroundColor: 'rgba(54, 162, 235, 0.1)',
                    tension: 0.4,
                    fill: true
                },
                {
                    label: 'Уникальные',
                    data: users,
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.1)',
                    tension: 0.4,
                    fill: true
                },
                {
                    label: 'Просмотры',
                    data: pageviews,
                    borderColor: 'rgb(255, 99, 132)',
                    backgroundColor: 'rgba(255, 99, 132, 0.1)',
                    tension: 0.4,
                    fill: true
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            interaction: {
                intersect: false,
                mode: 'index'
            },
            plugins: {
                legend: {
                    position: 'top',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + context.parsed.y.toLocaleString('ru-RU');
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value.toLocaleString('ru-RU');
                        }
                    }
                }
            }
        }
    });
    @endif

    // График источников трафика (круговая диаграмма)
    @if(!empty($trafficSources['data']))
    const sourcesData = @json($trafficSources['data']);
    const sourcesLabels = sourcesData.map(item => item.dimensions[0].name);
    const sourcesValues = sourcesData.map(item => item.metrics[0]);

    const sourcesCtx = document.getElementById('sourcesChart');
    new Chart(sourcesCtx, {
        type: 'doughnut',
        data: {
            labels: sourcesLabels,
            datasets: [{
                data: sourcesValues,
                backgroundColor: [
                    'rgb(54, 162, 235)',
                    'rgb(75, 192, 192)',
                    'rgb(255, 205, 86)',
                    'rgb(255, 99, 132)',
                    'rgb(153, 102, 255)',
                    'rgb(255, 159, 64)',
                    'rgb(201, 203, 207)',
                    'rgb(99, 255, 132)',
                    'rgb(235, 54, 162)',
                    'rgb(192, 75, 75)'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((context.parsed / total) * 100).toFixed(1);
                            return context.label + ': ' + context.parsed.toLocaleString('ru-RU') + ' (' + percentage + '%)';
                        }
                    }
                }
            }
        }
    });
    @endif

    // График устройств
    @if(!empty($deviceStats['data']))
    const devicesData = @json($deviceStats['data']);
    const devicesLabels = devicesData.map(item => item.dimensions[0].name);
    const devicesValues = devicesData.map(item => item.metrics[0]);

    const devicesCtx = document.getElementById('devicesChart');
    new Chart(devicesCtx, {
        type: 'pie',
        data: {
            labels: devicesLabels,
            datasets: [{
                data: devicesValues,
                backgroundColor: [
                    'rgb(54, 162, 235)',
                    'rgb(255, 99, 132)',
                    'rgb(75, 192, 192)'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((context.parsed / total) * 100).toFixed(1);
                            return context.label + ': ' + context.parsed.toLocaleString('ru-RU') + ' (' + percentage + '%)';
                        }
                    }
                }
            }
        }
    });
    @endif
});
</script>
@endsection


