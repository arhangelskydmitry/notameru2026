@extends('layouts.admin')

@section('title', 'Качество контента')

@section('content')
<div class="container-fluid">
    <!-- Заголовок и фильтры -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="fas fa-chart-bar"></i> Качество контента</h1>
        <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> К дашборду
        </a>
    </div>
    
    <!-- Общая статистика -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-white bg-primary">
                <div class="card-body">
                    <h5 class="card-title">Всего статей</h5>
                    <h2 class="mb-0">{{ $stats['total'] }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-success">
                <div class="card-body">
                    <h5 class="card-title">Ср. качество</h5>
                    <h2 class="mb-0">{{ $stats['avg_quality'] }}%</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-info">
                <div class="card-body">
                    <h5 class="card-title">Ср. SEO</h5>
                    <h2 class="mb-0">{{ $stats['avg_seo'] }}%</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-danger">
                <div class="card-body">
                    <h5 class="card-title">Проблемные</h5>
                    <h2 class="mb-0">{{ $stats['low_quality'] }}</h2>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Детальная статистика -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted">🖼️ Без изображений</h6>
                    <h4>{{ $stats['no_image'] }} статей</h4>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted">⚠️ С placeholder</h6>
                    <h4>{{ $stats['with_placeholders'] }} статей</h4>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted">📉 Плохое SEO</h6>
                    <h4>{{ $stats['poor_seo'] }} статей</h4>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Фильтры и сортировка -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('admin.content-quality') }}" method="GET" class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Фильтр</label>
                    <select name="filter" class="form-select" onchange="this.form.submit()">
                        <option value="all" {{ $currentFilter == 'all' ? 'selected' : '' }}>Все статьи</option>
                        <option value="no-image" {{ $currentFilter == 'no-image' ? 'selected' : '' }}>Без изображений</option>
                        <option value="placeholder" {{ $currentFilter == 'placeholder' ? 'selected' : '' }}>С placeholder</option>
                        <option value="poor-seo" {{ $currentFilter == 'poor-seo' ? 'selected' : '' }}>Плохое SEO (< 60%)</option>
                        <option value="low-quality" {{ $currentFilter == 'low-quality' ? 'selected' : '' }}>Низкое качество (< 60%)</option>
                        <option value="no-categories" {{ $currentFilter == 'no-categories' ? 'selected' : '' }}>Без категорий</option>
                        <option value="short-content" {{ $currentFilter == 'short-content' ? 'selected' : '' }}>Короткий контент (< 500 символов)</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Сортировка</label>
                    <select name="sort" class="form-select" onchange="this.form.submit()">
                        <option value="quality" {{ $currentSort == 'quality' ? 'selected' : '' }}>По качеству (сначала худшие)</option>
                        <option value="seo" {{ $currentSort == 'seo' ? 'selected' : '' }}>По SEO (сначала худшие)</option>
                        <option value="issues" {{ $currentSort == 'issues' ? 'selected' : '' }}>По количеству проблем</option>
                        <option value="date" {{ $currentSort == 'date' ? 'selected' : '' }}>По дате (новые первые)</option>
                    </select>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Таблица статей -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Анализ статей ({{ count($postsAnalysis) }})</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 40%">Статья</th>
                            <th style="width: 10%" class="text-center">Качество</th>
                            <th style="width: 10%" class="text-center">SEO</th>
                            <th style="width: 15%">Изображения</th>
                            <th style="width: 15%">Проблемы</th>
                            <th style="width: 10%" class="text-center">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($postsAnalysis as $analysis)
                            @php
                                $post = $analysis['post'];
                                $qualityClass = 
                                    $analysis['quality_score'] >= 80 ? 'success' : 
                                    ($analysis['quality_score'] >= 60 ? 'warning' : 'danger');
                                $seoClass = 
                                    $analysis['seo_score'] >= 80 ? 'success' : 
                                    ($analysis['seo_score'] >= 60 ? 'warning' : 'danger');
                            @endphp
                            <tr>
                                <td>
                                    <div class="d-flex align-items-start">
                                        @if($analysis['is_placeholder'])
                                            <i class="fas fa-image text-danger me-2 mt-1"></i>
                                        @elseif($analysis['has_featured_image'])
                                            <i class="fas fa-check-circle text-success me-2 mt-1"></i>
                                        @else
                                            <i class="fas fa-times-circle text-muted me-2 mt-1"></i>
                                        @endif
                                        <div>
                                            <strong>{{ Str::limit($post->post_title, 60) }}</strong>
                                            <br>
                                            <small class="text-muted">
                                                <i class="fas fa-calendar"></i> {{ $post->post_date->format('d.m.Y') }}
                                                <i class="fas fa-align-left ms-2"></i> {{ number_format($analysis['content_length']) }} символов
                                            </small>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-{{ $qualityClass }} fs-6">
                                        {{ $analysis['quality_score'] }}%
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-{{ $seoClass }} fs-6">
                                        {{ $analysis['seo_score'] }}%
                                    </span>
                                </td>
                                <td>
                                    @if($analysis['is_placeholder'])
                                        <span class="badge bg-danger">Placeholder</span>
                                    @elseif(!$analysis['has_featured_image'])
                                        <span class="badge bg-warning text-dark">Нет миниатюры</span>
                                    @else
                                        <span class="badge bg-success">✓ Есть</span>
                                    @endif
                                    
                                    @if($analysis['placeholders_in_content'] > 0)
                                        <br><small class="text-danger">⚠️ {{ $analysis['placeholders_in_content'] }} в тексте</small>
                                    @endif
                                    
                                    @if($analysis['content_images'] > 0)
                                        <br><small class="text-muted">📷 {{ $analysis['content_images'] }} в контенте</small>
                                    @endif
                                </td>
                                <td>
                                    @if(count($analysis['issues']) > 0)
                                        <span class="badge bg-danger">{{ count($analysis['issues']) }}</span>
                                        <div class="mt-1">
                                            @foreach(array_slice($analysis['issues'], 0, 2) as $issue)
                                                <small class="text-danger d-block">• {{ $issue }}</small>
                                            @endforeach
                                            @if(count($analysis['issues']) > 2)
                                                <small class="text-muted">+{{ count($analysis['issues']) - 2 }} ещё</small>
                                            @endif
                                        </div>
                                    @else
                                        <span class="badge bg-success">Нет проблем</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('admin.posts.edit', $post->ID) }}" 
                                       class="btn btn-sm btn-primary" 
                                       title="Редактировать">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="{{ route('post', $post->post_name) }}" 
                                       class="btn btn-sm btn-secondary" 
                                       target="_blank" 
                                       title="Просмотр">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
.card {
    margin-bottom: 1.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.table td {
    vertical-align: middle;
}

.badge.fs-6 {
    font-size: 1rem !important;
    padding: 0.5rem 0.75rem;
}
</style>
@endsection











