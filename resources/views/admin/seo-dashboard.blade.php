@extends('layouts.admin')
@section('title', 'SEO Дашборд')
@section('content')
<div class="container-fluid">
    <h1 class="mb-4"><i class="fas fa-chart-line"></i> SEO Дашборд</h1>
    
    @php
        $seoService = app(\App\Services\SeoService::class);
        $sitemapStats = app(\App\Http\Controllers\SitemapController::class)->stats();
        
        // Статистика SEO по всем статьям
        $posts = \App\Models\WordPress\Post::where('post_type', 'post')
            ->where('post_status', 'publish')
            ->get();
        
        $seoScores = [];
        $excellent = 0;
        $good = 0;
        $fair = 0;
        $poor = 0;
        
        foreach ($posts as $post) {
            $score = $seoService->analyzeSeoScore($post);
            $seoScores[] = [
                'post' => $post,
                'score' => $score['score'],
                'status' => $score['status'],
                'issues' => $score['issues'],
                'recommendations' => $score['recommendations'],
            ];
            
            if ($score['status'] === 'excellent') $excellent++;
            elseif ($score['status'] === 'good') $good++;
            elseif ($score['status'] === 'fair') $fair++;
            else $poor++;
        }
        
        // Сортируем по score
        usort($seoScores, function($a, $b) {
            return $b['score'] - $a['score'];
        });
        
        $avgScore = $posts->count() > 0 ? array_sum(array_column($seoScores, 'score')) / $posts->count() : 0;
    @endphp
    
    <!-- Общая статистика -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center bg-primary text-white">
                <div class="card-body">
                    <h6 class="card-title">Всего статей</h6>
                    <p class="card-text fs-2">{{ $posts->count() }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center bg-info text-white">
                <div class="card-body">
                    <h6 class="card-title">Средний SEO Score</h6>
                    <p class="card-text fs-2">{{ number_format($avgScore, 1) }}/100</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h6 class="card-title">URL в Sitemap</h6>
                    <p class="card-text fs-2">{{ $sitemapStats['total'] }}</p>
                    <small class="text-muted">
                        Посты: {{ $sitemapStats['posts'] }} | 
                        Категории: {{ $sitemapStats['categories'] }}
                    </small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center bg-success text-white">
                <div class="card-body">
                    <h6 class="card-title">Отличный SEO</h6>
                    <p class="card-text fs-2">{{ $excellent }}</p>
                    <small>{{ $posts->count() > 0 ? round($excellent / $posts->count() * 100) : 0 }}% статей</small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Распределение по качеству -->
    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="fas fa-chart-pie"></i> Распределение качества SEO</h5>
        </div>
        <div class="card-body">
            <div class="row text-center">
                <div class="col-md-3">
                    <div class="p-3 bg-success text-white rounded">
                        <h3>{{ $excellent }}</h3>
                        <p class="mb-0">Отличный (80+)</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-3 bg-info text-white rounded">
                        <h3>{{ $good }}</h3>
                        <p class="mb-0">Хороший (60-79)</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-3 bg-warning text-white rounded">
                        <h3>{{ $fair }}</h3>
                        <p class="mb-0">Средний (40-59)</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-3 bg-danger text-white rounded">
                        <h3>{{ $poor }}</h3>
                        <p class="mb-0">Плохой (< 40)</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Топ-10 лучших статей -->
    <div class="card mb-4">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0"><i class="fas fa-trophy"></i> Топ-10 лучших статей по SEO</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Заголовок</th>
                            <th>SEO Score</th>
                            <th>Статус</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(array_slice($seoScores, 0, 10) as $index => $item)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ Str::limit($item['post']->post_title, 60) }}</td>
                            <td>
                                <strong class="
                                    @if($item['status'] === 'excellent') text-success
                                    @elseif($item['status'] === 'good') text-info
                                    @elseif($item['status'] === 'fair') text-warning
                                    @else text-danger
                                    @endif
                                ">{{ $item['score'] }}/100</strong>
                            </td>
                            <td>
                                <span class="badge 
                                    @if($item['status'] === 'excellent') bg-success
                                    @elseif($item['status'] === 'good') bg-info
                                    @elseif($item['status'] === 'fair') bg-warning
                                    @else bg-danger
                                    @endif
                                ">
                                    @if($item['status'] === 'excellent') Отлично
                                    @elseif($item['status'] === 'good') Хорошо
                                    @elseif($item['status'] === 'fair') Средне
                                    @else Плохо
                                    @endif
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('admin.posts.edit', $item['post']->ID) }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-edit"></i> Редактировать
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Статьи требующие внимания -->
    <div class="card mb-4">
        <div class="card-header bg-danger text-white">
            <h5 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Статьи требующие оптимизации (Score < 60)</h5>
        </div>
        <div class="card-body">
            @php
                $poorPosts = array_filter($seoScores, function($item) {
                    return $item['score'] < 60;
                });
            @endphp
            
            @if(count($poorPosts) > 0)
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Заголовок</th>
                            <th>Score</th>
                            <th>Проблемы</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(array_slice($poorPosts, 0, 20) as $item)
                        <tr>
                            <td>{{ Str::limit($item['post']->post_title, 50) }}</td>
                            <td>
                                <span class="badge bg-danger">{{ $item['score'] }}/100</span>
                            </td>
                            <td>
                                @if(count($item['issues']) > 0)
                                    <ul class="mb-0 small">
                                        @foreach($item['issues'] as $issue)
                                            <li class="text-danger">{{ $issue }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                                @if(count($item['recommendations']) > 0)
                                    <ul class="mb-0 small">
                                        @foreach(array_slice($item['recommendations'], 0, 2) as $rec)
                                            <li class="text-warning">{{ $rec }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('admin.posts.edit', $item['post']->ID) }}" class="btn btn-sm btn-warning">
                                    <i class="fas fa-wrench"></i> Исправить
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> Отлично! Все статьи имеют хороший SEO Score (60+)
            </div>
            @endif
        </div>
    </div>
    
    <!-- Быстрые действия -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-tools"></i> Быстрые действия</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <a href="{{ url('/sitemap.xml') }}" target="_blank" class="btn btn-outline-primary w-100 mb-2">
                        <i class="fas fa-sitemap"></i> Просмотреть Sitemap.xml
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="{{ url('/robots.txt') }}" target="_blank" class="btn btn-outline-primary w-100 mb-2">
                        <i class="fas fa-robot"></i> Просмотреть Robots.txt
                    </a>
                </div>
                <div class="col-md-4">
                    <button class="btn btn-outline-success w-100 mb-2" onclick="runImport()">
                        <i class="fas fa-download"></i> Импорт из Yoast SEO
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function runImport() {
    if (confirm('Запустить импорт SEO данных из WordPress Yoast SEO?\n\nЭто может занять некоторое время.')) {
        alert('Функция импорта доступна через команду:\nphp artisan seo:import-yoast');
    }
}
</script>

<style>
.card-text.fs-2 {
    font-size: 2rem !important;
    font-weight: bold;
}
</style>
@endsection




