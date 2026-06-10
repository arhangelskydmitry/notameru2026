@extends('layouts.admin')

@section('title', 'Управление Sitemap')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="fas fa-sitemap"></i> Управление Sitemap</h1>
                <div>
                    <a href="{{ url('/sitemap.xml') }}" target="_blank" class="btn btn-outline-primary">
                        <i class="fas fa-external-link-alt"></i> Открыть sitemap.xml
                    </a>
                    <button type="button" class="btn btn-success" id="regenerate-sitemap">
                        <i class="fas fa-sync-alt"></i> Обновить Sitemap
                    </button>
                </div>
            </div>
            
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            
            <!-- Статистика -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h3 class="text-primary">{{ $stats['total'] }}</h3>
                            <p class="text-muted mb-0">Всего URL</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card">
                        <div class="card-body text-center">
                            <h3 class="text-success">{{ $stats['posts'] }}</h3>
                            <p class="text-muted mb-0">Статей</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card">
                        <div class="card-body text-center">
                            <h3 class="text-info">{{ $stats['pages'] }}</h3>
                            <p class="text-muted mb-0">Страниц</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card">
                        <div class="card-body text-center">
                            <h3 class="text-warning">{{ $stats['categories'] }}</h3>
                            <p class="text-muted mb-0">Категорий</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h3 class="text-secondary">{{ number_format($stats['file_size'] / 1024, 2) }} KB</h3>
                            <p class="text-muted mb-0">Размер файла</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Превью XML -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-code"></i> Превью Sitemap XML</h5>
                </div>
                <div class="card-body">
                    <pre class="bg-light p-3" style="max-height: 600px; overflow-y: auto; border-radius: 8px;"><code>{{ htmlspecialchars($sitemap) }}</code></pre>
                </div>
            </div>
            
            <!-- Инструкции -->
            <div class="card mt-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-info-circle"></i> Как использовать Sitemap</h5>
                </div>
                <div class="card-body">
                    <h6>1. Google Search Console</h6>
                    <p>Добавьте URL sitemap в Google Search Console:</p>
                    <div class="alert alert-light">
                        <code>{{ url('/sitemap.xml') }}</code>
                    </div>
                    
                    <h6 class="mt-3">2. Яндекс.Вебмастер</h6>
                    <p>Добавьте sitemap в Яндекс.Вебмастер для лучшей индексации</p>
                    
                    <h6 class="mt-3">3. Автоматическое обновление</h6>
                    <p>Sitemap автоматически кешируется на 1 час. Нажмите "Обновить Sitemap" для принудительной регенерации.</p>
                    
                    <h6 class="mt-3">4. Robots.txt</h6>
                    <p>Убедитесь, что в файле <code>robots.txt</code> указан путь к sitemap:</p>
                    <div class="alert alert-light">
                        <code>Sitemap: {{ url('/sitemap.xml') }}</code>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('regenerate-sitemap')?.addEventListener('click', function() {
    const btn = this;
    const originalText = btn.innerHTML;
    
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Обновление...';
    
    fetch('{{ route("admin.sitemap.regenerate") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({})
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Показываем уведомление
            const alert = document.createElement('div');
            alert.className = 'alert alert-success alert-dismissible fade show';
            alert.innerHTML = '<i class="fas fa-check-circle"></i> ' + data.message + 
                '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
            
            const container = document.querySelector('.container-fluid');
            if (container && container.firstChild) {
                container.insertBefore(alert, container.firstChild);
            } else if (container) {
                container.appendChild(alert);
            } else if (document.body) {
                document.body.prepend(alert);
            }
            
            // Обновляем статистику
            setTimeout(function() { 
                location.reload(); 
            }, 1000);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Ошибка при обновлении sitemap');
    })
    .finally(function() {
        btn.disabled = false;
        btn.innerHTML = originalText;
    });
});
</script>
@endsection


