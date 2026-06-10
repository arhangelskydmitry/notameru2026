@extends('layouts.admin')

@section('title', 'Детали 404')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">🔍 Детали ошибки 404</h1>
                <a href="{{ route('admin.404-logs.index', ['period' => $period]) }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Назад к статистике
                </a>
            </div>

            <!-- URL -->
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">URL:</h5>
                    <p class="mb-0"><code>{{ $url }}</code></p>
                </div>
            </div>

            <!-- Статистика -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card text-white bg-danger">
                        <div class="card-body">
                            <h5 class="card-title">Всего обращений</h5>
                            <h2 class="mb-0">{{ number_format($totalHits) }}</h2>
                            <small>за {{ $period }} дней</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-white bg-warning">
                        <div class="card-body">
                            <h5 class="card-title">Уникальных источников</h5>
                            <h2 class="mb-0">{{ number_format($uniqueReferers) }}</h2>
                            <small>различных referer'ов</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-white bg-info">
                        <div class="card-body">
                            <h5 class="card-title">В среднем</h5>
                            <h2 class="mb-0">{{ $period > 0 ? number_format($totalHits / $period, 1) : 0 }}</h2>
                            <small>обращений в день</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Топ источников -->
            @if($topReferers->isNotEmpty())
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">🔗 Откуда приходят на эту страницу</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Источник (Referer)</th>
                                    <th>Количество</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($topReferers as $index => $ref)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            @if($ref->referer)
                                                <small>{{ $ref->referer }}</small>
                                            @else
                                                <small class="text-muted"><em>Прямой переход</em></small>
                                            @endif
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

            <!-- Все обращения -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">📋 Все обращения</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Дата/Время</th>
                                    <th>Откуда</th>
                                    <th>IP</th>
                                    <th>User Agent</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($logs as $log)
                                    <tr>
                                        <td style="white-space: nowrap;">
                                            <small>{{ $log->created_at->format('d.m.Y H:i:s') }}</small>
                                        </td>
                                        <td>
                                            @if($log->referer)
                                                <small class="text-muted">{{ Str::limit($log->referer, 60) }}</small>
                                            @else
                                                <small class="text-muted"><em>Прямой переход</em></small>
                                            @endif
                                        </td>
                                        <td>
                                            <small>{{ $log->ip_address }}</small>
                                        </td>
                                        <td>
                                            <small class="text-muted">{{ Str::limit($log->user_agent, 80) }}</small>
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

                    <!-- Пагинация -->
                    <div class="mt-3">
                        {{ $logs->appends(['url' => $url, 'period' => $period])->links() }}
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
