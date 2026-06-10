@extends('layouts.admin')

@section('title', 'SEO Анализ')

@push('styles')
<style>
    /* Темный фон для всей страницы SEO анализа */
    #content {
        background: #0f172a !important;
        min-height: 100vh;
    }
    
    body {
        background: #0f172a !important;
    }
    
    h2, h3, h4, h5, h6 {
        color: #f1f5f9 !important;
    }
    
    p {
        color: #cbd5e1 !important;
    }
    
    .seo-stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }
    
    .stat-card {
        background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
        border-radius: 16px;
        padding: 1.5rem;
        border: 1px solid rgba(255,255,255,0.1);
        transition: transform 0.2s, box-shadow 0.2s;
    }
    
    .stat-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 40px rgba(0,0,0,0.3);
    }
    
    .stat-card.good { border-left: 4px solid #10b981; }
    .stat-card.warning { border-left: 4px solid #f59e0b; }
    .stat-card.bad { border-left: 4px solid #ef4444; }
    .stat-card.info { border-left: 4px solid #3b82f6; }
    
    .stat-value {
        font-size: 2.5rem;
        font-weight: 700;
        background: linear-gradient(135deg, #667eea, #764ba2);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        line-height: 1;
        display: inline-block; /* Важно для корректной работы gradient на тексте */
    }
    
    .stat-card.good .stat-value {
        background: linear-gradient(135deg, #10b981, #059669);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    
    .stat-card.bad .stat-value {
        background: linear-gradient(135deg, #ef4444, #dc2626);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    
    .stat-label {
        color: #94a3b8;
        font-size: 0.875rem;
        margin-top: 0.5rem;
    }
    
    .provider-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 1rem;
        margin-bottom: 2rem;
    }
    
    .provider-card {
        background: rgba(30, 41, 59, 0.5);
        border-radius: 12px;
        padding: 1.25rem;
        border: 1px solid rgba(255,255,255,0.08);
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    
    .provider-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }
    
    .provider-icon.gigachat { background: linear-gradient(135deg, #22c55e, #16a34a); }
    .provider-icon.chatinfo { background: linear-gradient(135deg, #3b82f6, #2563eb); }
    .provider-icon.openai { background: linear-gradient(135deg, #6366f1, #4f46e5); }
    
    .provider-status {
        font-size: 0.75rem;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        display: inline-block;
    }
    
    .provider-status.active {
        background: rgba(16, 185, 129, 0.2);
        color: #10b981;
    }
    
    .provider-status.inactive {
        background: rgba(239, 68, 68, 0.2);
        color: #ef4444;
    }
    
    .action-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 1.5rem;
    }
    
    .action-card {
        background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        border-radius: 16px;
        padding: 2rem;
        border: 1px solid rgba(255,255,255,0.1);
        text-align: center;
        transition: all 0.3s;
    }
    
    .action-card:hover {
        transform: translateY(-4px);
        border-color: rgba(99, 102, 241, 0.5);
    }
    
    .action-icon {
        width: 64px;
        height: 64px;
        margin: 0 auto 1.5rem;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        background: linear-gradient(135deg, #667eea, #764ba2);
    }
    
    .action-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: #f1f5f9;
        margin-bottom: 0.5rem;
    }
    
    .action-desc {
        color: #94a3b8;
        font-size: 0.875rem;
        margin-bottom: 1.5rem;
    }
    
    .btn-action {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1.5rem;
        border-radius: 10px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.2s;
    }
    
    .btn-action.primary {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
    }
    
    .btn-action.primary:hover {
        transform: scale(1.05);
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
    }
    
    .btn-action.secondary {
        background: rgba(255,255,255,0.1);
        color: #e2e8f0;
        border: 1px solid rgba(255,255,255,0.2);
    }
    
    .btn-action.secondary:hover {
        background: rgba(255,255,255,0.15);
    }
    
    .progress-ring {
        width: 120px;
        height: 120px;
        margin: 0 auto 1rem;
    }
    
    .progress-ring-circle {
        transition: stroke-dashoffset 0.35s;
        transform: rotate(-90deg);
        transform-origin: 50% 50%;
    }
    
    .progress-text {
        font-size: 1.75rem;
        font-weight: 700;
        fill: #f1f5f9;
    }
    
    .section-title {
        font-size: 1.5rem;
        font-weight: 600;
        color: #f1f5f9;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    
    .section-title span {
        font-size: 1.5rem;
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-white">🔍 SEO Анализ и Оптимизация</h1>
        <a href="{{ route('admin.seo-settings') }}" class="btn btn-outline-light btn-sm">
            <i class="fas fa-cog"></i> Настройки AI
        </a>
    </div>
    
    <!-- Statistics -->
    <div class="seo-stats-grid">
        <div class="stat-card info">
            <div class="stat-value">{{ number_format($stats['total_posts']) }}</div>
            <div class="stat-label">Всего статей</div>
        </div>
        <div class="stat-card good">
            <div class="stat-value">{{ number_format($stats['good_seo']) }}</div>
            <div class="stat-label">Хорошее SEO (≥70)</div>
        </div>
        <div class="stat-card bad">
            <div class="stat-value">{{ number_format($stats['bad_seo']) }}</div>
            <div class="stat-label">Требуют улучшения</div>
        </div>
        <div class="stat-card warning">
            <div class="stat-value">{{ $stats['percent_good'] }}%</div>
            <div class="stat-label">Оптимизировано</div>
        </div>
    </div>
    
    <!-- AI Providers -->
    <h2 class="section-title"><span>🤖</span> AI Провайдеры</h2>
    <div class="provider-grid">
        @foreach($providers as $key => $provider)
        <div class="provider-card">
            <div class="provider-icon {{ $key }}">
                @if($key === 'gigachat') 🟢
                @elseif($key === 'chatinfo') 🔵
                @else 🟣
                @endif
            </div>
            <div class="flex-grow-1">
                <div class="fw-semibold text-white">{{ $provider['name'] }}</div>
                <div class="small text-muted">{{ $provider['description'] }}</div>
            </div>
            <span class="provider-status {{ $provider['configured'] ? 'active' : 'inactive' }}">
                {{ $provider['configured'] ? '✓ Готов' : '✗ Не настроен' }}
            </span>
        </div>
        @endforeach
    </div>
    
    <!-- Actions -->
    <h2 class="section-title"><span>🛠️</span> Инструменты</h2>
    <div class="action-grid">
        <div class="action-card">
            <div class="action-icon">📊</div>
            <div class="action-title">Анализ статей</div>
            <div class="action-desc">
                Просмотр всех статей с оценкой SEO качества. 
                Фильтрация по статусу и пагинация.
            </div>
            <a href="{{ route('admin.seo-analysis.analyze') }}" class="btn-action primary">
                <i class="fas fa-search"></i> Открыть анализ
            </a>
        </div>
        
        <div class="action-card">
            <div class="action-icon">⚡</div>
            <div class="action-title">Быстрая оптимизация</div>
            <div class="action-desc">
                Автоматическая генерация SEO через AI с 
                предпросмотром перед применением.
            </div>
            <a href="{{ route('admin.seo-analysis.analyze', ['filter' => 'bad']) }}" class="btn-action primary">
                <i class="fas fa-magic"></i> Оптимизировать плохие
            </a>
        </div>
        
        <div class="action-card">
            <div class="action-icon">📄</div>
            <div class="action-title">Экспорт SQL</div>
            <div class="action-desc">
                Экспорт изменений в SQL-файл для применения 
                на production сервере.
            </div>
            <a href="{{ route('admin.seo-analysis.analyze') }}" class="btn-action secondary">
                <i class="fas fa-download"></i> Создать экспорт
            </a>
        </div>
    </div>
    
    <!-- Quick Stats Chart -->
    <div class="mt-5">
        <h2 class="section-title"><span>📈</span> Распределение качества SEO</h2>
        <div class="row">
            <div class="col-md-4">
                <div class="stat-card text-center">
                    <svg class="progress-ring" viewBox="0 0 120 120">
                        <circle
                            stroke="rgba(255,255,255,0.1)"
                            stroke-width="10"
                            fill="transparent"
                            r="50"
                            cx="60"
                            cy="60"
                        />
                        <circle
                            class="progress-ring-circle"
                            stroke="url(#gradient)"
                            stroke-width="10"
                            fill="transparent"
                            r="50"
                            cx="60"
                            cy="60"
                            stroke-dasharray="314"
                            stroke-dashoffset="{{ 314 - (314 * $stats['percent_good'] / 100) }}"
                            stroke-linecap="round"
                        />
                        <defs>
                            <linearGradient id="gradient" x1="0%" y1="0%" x2="100%" y2="0%">
                                <stop offset="0%" stop-color="#10b981" />
                                <stop offset="100%" stop-color="#059669" />
                            </linearGradient>
                        </defs>
                        <text x="60" y="65" text-anchor="middle" class="progress-text">
                            {{ $stats['percent_good'] }}%
                        </text>
                    </svg>
                    <div class="stat-label">Оптимизировано</div>
                </div>
            </div>
            <div class="col-md-8">
                <div class="stat-card">
                    <h5 class="text-white mb-3">Рекомендации</h5>
                    <ul class="list-unstyled mb-0">
                        @if($stats['bad_seo'] > 0)
                        <li class="mb-2">
                            <span class="badge bg-danger me-2">{{ $stats['bad_seo'] }}</span>
                            статей требуют оптимизации SEO
                        </li>
                        @endif
                        <li class="mb-2">
                            <span class="badge bg-info me-2">Совет</span>
                            Используйте пакетную обработку для массовой оптимизации
                        </li>
                        <li class="mb-2">
                            <span class="badge bg-warning me-2">Важно</span>
                            Проверяйте предпросмотр перед применением изменений
                        </li>
                        <li>
                            <span class="badge bg-success me-2">Лучшая практика</span>
                            Регулярно анализируйте новые статьи
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
