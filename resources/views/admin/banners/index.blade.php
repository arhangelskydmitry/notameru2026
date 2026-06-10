@extends('layouts.admin')

@section('title', 'Баннеры')

@section('content')
<style>
    .card.banner-card {
        border: none;
        box-shadow: 0 2px 10px rgba(0,0,0,.05);
        margin-bottom: 20px;
        transition: transform 0.2s;
    }
    .card.banner-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0,0,0,.1);
    }
    .banner-preview {
        max-width: 100%;
        max-height: 100px;
        object-fit: contain;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        padding: 5px;
        background: #fff;
    }
    .zone-badge {
        background: #e7f3ff;
        color: #0066cc;
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
    }
    .stats-box {
        background: #f8f9fa;
        padding: 10px;
        border-radius: 8px;
        text-align: center;
        margin: 5px 0;
    }
    .stats-box .number {
        font-size: 20px;
        font-weight: 700;
        color: #667eea;
    }
    .stats-box .label {
        font-size: 11px;
        color: #6c757d;
        text-transform: uppercase;
    }
</style>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h2 class="h3 mb-1"><i class="fas fa-ad me-2"></i>Управление баннерами</h2>
            <p class="text-muted mb-0">Реклама и баннеры на сайте</p>
        </div>
        <div class="col-md-6 text-md-end mt-3 mt-md-0">
            <a href="{{ route('admin.banners.create') }}" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i>Добавить баннер
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row mb-4 g-3">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <h3 class="text-primary mb-1">{{ $banners->count() }}</h3>
                    <p class="text-muted mb-0">Всего баннеров</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <h3 class="text-success mb-1">{{ $banners->where('status', 'active')->count() }}</h3>
                    <p class="text-muted mb-0">Активных</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <h3 class="text-warning mb-1">{{ $banners->where('status', 'paused')->count() }}</h3>
                    <p class="text-muted mb-0">На паузе</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <h3 class="text-info mb-1">{{ $zones->count() }}</h3>
                    <p class="text-muted mb-0">Зон размещения</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        @forelse($banners as $banner)
            <div class="col-md-6 col-lg-4">
                <div class="card banner-card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <strong>{{ $banner->title }}</strong>
                        @php
                            $statusClass = [
                                'active' => 'bg-success',
                                'paused' => 'bg-warning text-dark',
                                'expired' => 'bg-secondary',
                            ][$banner->status] ?? 'bg-secondary';
                            $statusLabel = [
                                'active' => 'Активен',
                                'paused' => 'На паузе',
                                'expired' => 'Истек',
                            ][$banner->status] ?? ucfirst($banner->status);
                        @endphp
                        <span class="badge badge-status {{ $statusClass }}">
                            {{ $statusLabel }}
                        </span>
                    </div>
                    <div class="card-body">
                        @if($banner->type === 'image')
                            <div class="text-center mb-3">
                                <img src="{{ $banner->content }}" alt="{{ $banner->title }}" class="banner-preview">
                            </div>
                        @elseif($banner->type === 'html')
                            <div class="alert alert-info text-center mb-3">
                                <i class="fas fa-code me-1"></i>HTML код
                            </div>
                        @else
                            <div class="alert alert-warning text-center mb-3">
                                <i class="fas fa-file-code me-1"></i>JavaScript
                            </div>
                        @endif

                        <div class="mb-2">
                            <span class="zone-badge">
                                <i class="fas fa-map-marker-alt me-1"></i>{{ $banner->zone }}
                            </span>
                            <span class="badge bg-secondary ms-1">
                                Приоритет: {{ $banner->priority }}
                            </span>
                        </div>

                        @if($banner->start_date || $banner->end_date)
                            <div class="small text-muted mb-2">
                                <i class="far fa-calendar me-1"></i>
                                @if($banner->start_date)
                                    с {{ $banner->start_date->format('d.m.Y') }}
                                @endif
                                @if($banner->end_date)
                                    до {{ $banner->end_date->format('d.m.Y') }}
                                @endif
                            </div>
                        @endif

                        <div class="row g-2 mt-2">
                            <div class="col-4">
                                <div class="stats-box">
                                    <div class="number">{{ number_format($banner->total_stats['impressions']) }}</div>
                                    <div class="label">Показов</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="stats-box">
                                    <div class="number">{{ number_format($banner->total_stats['clicks']) }}</div>
                                    <div class="label">Кликов</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="stats-box">
                                    <div class="number">{{ $banner->total_stats['ctr'] }}%</div>
                                    <div class="label">CTR</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="btn-group w-100" role="group">
                            <a href="{{ route('admin.banners.edit', $banner->id) }}" class="btn btn-sm btn-outline-primary" title="Редактировать">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="{{ route('admin.banners.statistics', $banner->id) }}" class="btn btn-sm btn-outline-info" title="Статистика">
                                <i class="fas fa-chart-line"></i>
                            </a>
                            <a href="{{ route('admin.banners.toggle', $banner->id) }}" class="btn btn-sm btn-outline-warning" title="Вкл/Выкл">
                                <i class="fas fa-power-off"></i>
                            </a>
                            <a href="{{ route('admin.banners.preview', $banner->id) }}" class="btn btn-sm btn-outline-secondary" target="_blank" title="Предпросмотр">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('admin.banners.delete', $banner->id) }}" class="btn btn-sm btn-outline-danger"
                               onclick="return confirm('Удалить баннер?')" title="Удалить">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-ad fa-3x text-muted mb-3"></i>
                        <h4>Баннеров пока нет</h4>
                        <p class="text-muted">Создайте первый баннер для вашего сайта</p>
                        <a href="{{ route('admin.banners.create') }}" class="btn btn-primary mt-3">
                            <i class="fas fa-plus"></i> Создать баннер
                        </a>
                    </div>
                </div>
            </div>
        @endforelse
    </div>

    <div class="card border-0 shadow-sm mt-4">
        <div class="card-header bg-white border-0 py-3">
            <h5 class="mb-0"><i class="fas fa-map-marked-alt me-2"></i>Зоны размещения</h5>
        </div>
        <div class="card-body">
            <div class="row">
                @foreach($zones as $zone)
                    <div class="col-md-4 mb-3">
                        <div class="border rounded p-3 h-100">
                            <h6 class="mb-1"><i class="fas fa-map-pin me-1"></i>{{ $zone->display_name }}</h6>
                            <p class="small text-muted mb-2">{{ $zone->description }}</p>
                            <div class="small">
                                <span class="badge bg-light text-dark">{{ $zone->recommended_width }}x{{ $zone->recommended_height }}</span>
                                <span class="badge bg-light text-dark">Макс: {{ $zone->max_banners }} банн.</span>
                                <span class="badge bg-primary text-white">{{ $zone->banners()->count() }} активно</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection

