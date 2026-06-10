@extends('layouts.admin')

@section('title', 'Статистика 404 ошибок')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <h1 class="h3 mb-4">🔍 Статистика 404 ошибок</h1>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- Фильтр периода -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row align-items-end">
                        <div class="col-md-3">
                            <label class="form-label">Период:</label>
                            <select name="period" class="form-select" onchange="this.form.submit()">
                                <option value="1" {{ $period == 1 ? 'selected' : '' }}>Последний день</option>
                                <option value="7" {{ $period == 7 ? 'selected' : '' }}>Последние 7 дней</option>
                                <option value="30" {{ $period == 30 ? 'selected' : '' }}>Последние 30 дней</option>
                                <option value="90" {{ $period == 90 ? 'selected' : '' }}>Последние 90 дней</option>
                                <option value="365" {{ $period == 365 ? 'selected' : '' }}>Последний год</option>
                            </select>
                        </div>
                        <div class="col-md-9 text-end">
                            <a href="{{ route('admin.404-logs.export', ['period' => $period]) }}" class="btn btn-success">
                                <i class="fas fa-download"></i> Экспорт CSV
                            </a>
                            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#cleanupModal">
                                <i class="fas fa-trash"></i> Очистить старые
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Общая статистика -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card text-white bg-danger">
                        <div class="card-body">
                            <h5 class="card-title">Всего 404</h5>
                            <h2 class="mb-0">{{ number_format($totalHits) }}</h2>
                            <small>за {{ $period }} {{ $period == 1 ? 'день' : 'дней' }}</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-white bg-warning">
                        <div class="card-body">
                            <h5 class="card-title">Уникальных URL</h5>
                            <h2 class="mb-0">{{ number_format($uniqueUrls) }}</h2>
                            <small>различных страниц</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-white bg-info">
                        <div class="card-body">
                            <h5 class="card-title">Среднее в день</h5>
                            <h2 class="mb-0">{{ $period > 0 ? number_format($totalHits / $period, 1) : 0 }}</h2>
                            <small>ошибок</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- График по дням -->
            @if($dailyStats->isNotEmpty())
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">📊 График 404 ошибок по дням</h5>
                </div>
                <div class="card-body">
                    <canvas id="dailyChart" height="80"></canvas>
                </div>
            </div>
            @endif

            <!-- Топ URL с 404 -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">🔥 Топ-50 несуществующих страниц</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>URL</th>
                                    <th>Количество обращений</th>
                                    <th>Последнее обращение</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($topUrls as $index => $log)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            <small class="text-muted">{{ $log->url }}</small>
                                        </td>
                                        <td>
                                            <span class="badge bg-danger" style="font-size: 14px;">
                                                {{ number_format($log->hits) }}
                                            </span>
                                        </td>
                                        <td>
                                            <small>{{ \Carbon\Carbon::parse($log->last_hit)->format('d.m.Y H:i') }}</small>
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.404-logs.details', ['url' => $log->url, 'period' => $period]) }}" 
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-search"></i> Детали
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center">
                                            <em>Нет данных за выбранный период</em>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Топ рефереров -->
            @if($topReferers->isNotEmpty())
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">🔗 Откуда приходят на несуществующие страницы</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Источник (Referer)</th>
                                    <th>Количество переходов</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($topReferers as $index => $ref)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            <small class="text-muted">{{ $ref->referer }}</small>
                                        </td>
                                        <td>
                                            <span class="badge bg-warning" style="font-size: 14px;">
                                                {{ number_format($ref->hits) }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            <!-- Последние 404 -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">🕒 Последние 100 ошибок 404</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Время</th>
                                    <th>URL</th>
                                    <th>Откуда</th>
                                    <th>IP</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentLogs as $log)
                                    <tr>
                                        <td style="white-space: nowrap;">
                                            <small>{{ $log->created_at->format('d.m.Y H:i:s') }}</small>
                                        </td>
                                        <td>
                                            <small class="text-muted">{{ Str::limit($log->url, 80) }}</small>
                                        </td>
                                        <td>
                                            @if($log->referer)
                                                <small class="text-muted">{{ Str::limit($log->referer, 50) }}</small>
                                            @else
                                                <small class="text-muted"><em>Прямой переход</em></small>
                                            @endif
                                        </td>
                                        <td>
                                            <small>{{ $log->ip_address }}</small>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center">
                                            <em>Нет данных</em>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Модальное окно очистки -->
<div class="modal fade" id="cleanupModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.404-logs.cleanup') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Очистка старых логов</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Удалить записи старше:</label>
                        <select name="days" class="form-select" required>
                            <option value="30">30 дней</option>
                            <option value="60">60 дней</option>
                            <option value="90" selected>90 дней</option>
                            <option value="180">180 дней</option>
                            <option value="365">1 года</option>
                        </select>
                    </div>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        Это действие нельзя отменить!
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-danger">Удалить</button>
                </div>
            </form>
        </div>
    </div>
</div>

@if($dailyStats->isNotEmpty())
<script src="https://cdn.jsdelivr.net/npm/chart.js@3"></script>
<script>
const ctx = document.getElementById('dailyChart');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: {!! json_encode($dailyStats->pluck('date')->map(fn($d) => \Carbon\Carbon::parse($d)->format('d.m'))) !!},
        datasets: [{
            label: '404 ошибок',
            data: {!! json_encode($dailyStats->pluck('hits')) !!},
            borderColor: 'rgb(220, 53, 69)',
            backgroundColor: 'rgba(220, 53, 69, 0.1)',
            tension: 0.1,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});
</script>
@endif
@endsection
