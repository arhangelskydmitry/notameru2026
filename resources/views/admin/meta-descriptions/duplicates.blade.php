@extends('layouts.admin')

@section('title', 'Дубликаты мета-описаний')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h1><i class="fas fa-clone"></i> Дубликаты мета-описаний</h1>
            <p class="text-muted">Найдены descriptions, используемые в нескольких статьях</p>
        </div>
    </div>

    <!-- Статистика -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-secondary text-white">
                <div class="card-body">
                    <h3>{{ $duplicates->total() }}</h3>
                    <p class="mb-0">Дублирующихся descriptions</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h3>{{ $stats['duplicates'] }}</h3>
                    <p class="mb-0">Всего статей с дублями</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Навигация -->
    <div class="card mb-4">
        <div class="card-body">
            <a href="{{ route('admin.meta-descriptions.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Назад к списку
            </a>
        </div>
    </div>

    <!-- Список дубликатов -->
    @if($duplicates->count() > 0)
        @foreach($duplicates as $index => $duplicate)
        <div class="card mb-3">
            <div class="card-header bg-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <strong>Дубликат #{{ ($duplicates->currentPage() - 1) * $duplicates->perPage() + $index + 1 }}</strong>
                        <span class="badge bg-warning text-dark ms-2">
                            {{ $duplicate->count }} статей используют это description
                        </span>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="alert alert-secondary mb-3">
                    <h6>Дублирующееся description:</h6>
                    <p class="mb-1">{{ $duplicate->meta_value }}</p>
                    <small class="text-muted">Длина: {{ mb_strlen($duplicate->meta_value) }} символов</small>
                </div>

                <h6 class="mb-3">Статьи с этим description:</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Заголовок</th>
                                <th>Дата</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($duplicate->posts as $post)
                            <tr>
                                <td>{{ $post->ID }}</td>
                                <td>
                                    <strong>{{ $post->post_title }}</strong>
                                </td>
                                <td>
                                    <small>{{ $post->post_date->format('d.m.Y') }}</small>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-primary preview-btn" 
                                            data-post-id="{{ $post->ID }}">
                                        <i class="fas fa-eye"></i> Предпросмотр
                                    </button>
                                    <button class="btn btn-sm btn-success generate-new-btn" 
                                            data-post-id="{{ $post->ID }}">
                                        <i class="fas fa-magic"></i> Новое
                                    </button>
                                    <a href="{{ route('post', $post->post_name) }}" 
                                       class="btn btn-sm btn-secondary" 
                                       target="_blank">
                                        <i class="fas fa-external-link-alt"></i>
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($duplicate->count > 10)
                    <div class="alert alert-info mt-2 mb-0">
                        <i class="fas fa-info-circle"></i> 
                        Показаны первые 10 из {{ $duplicate->count }} статей с этим description
                    </div>
                @endif
            </div>
        </div>
        @endforeach

        <div class="mt-3">
            {{ $duplicates->appends(['filter' => $filter, 'search' => $search])->links() }}
        </div>
    @else
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> 
            Дублирующихся мета-описаний не найдено! SEO в порядке.
        </div>
    @endif
</div>

<!-- Модальное окно предпросмотра (переиспользуем из index) -->
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Предпросмотр нового мета-описания</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="previewContent">
                <div class="text-center">
                    <div class="spinner-border" role="status"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
                <button type="button" class="btn btn-success" id="applyDescriptionBtn" disabled>
                    <i class="fas fa-check"></i> Применить
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.description-preview {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 5px;
    padding: 15px;
    margin: 10px 0;
}
.description-preview h6 {
    margin-bottom: 10px;
    color: #495057;
}
.description-preview .text {
    font-size: 14px;
    line-height: 1.6;
}
.length-indicator {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: bold;
}
.length-indicator.good {
    background: #28a745;
    color: white;
}
.length-indicator.warning {
    background: #ffc107;
    color: #000;
}
.length-indicator.danger {
    background: #dc3545;
    color: white;
}
</style>

@endsection

@section('scripts')
<script>
let currentPostId = null;
let currentDescription = null;

// Предпросмотр (копия из index.blade.php)
document.querySelectorAll('.preview-btn, .generate-new-btn').forEach(btn => {
    btn.addEventListener('click', async function() {
        const postId = this.dataset.postId;
        await showPreview(postId);
    });
});

async function showPreview(postId) {
    const modal = new bootstrap.Modal(document.getElementById('previewModal'));
    const content = document.getElementById('previewContent');
    const applyBtn = document.getElementById('applyDescriptionBtn');
    
    currentPostId = postId;
    currentDescription = null;
    applyBtn.disabled = true;
    
    content.innerHTML = '<div class="text-center"><div class="spinner-border"></div><p class="mt-2">Генерация уникального description...</p></div>';
    modal.show();
    
    try {
        const response = await fetch('{{ route("admin.meta-descriptions.preview") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ post_id: postId })
        });
        
        const data = await response.json();
        
        if (data.success) {
            currentDescription = data.generated;
            
            const currentLengthClass = data.current_length === 0 ? 'danger' : 
                                      data.current_length < 100 ? 'warning' : 
                                      data.current_length > 160 ? 'warning' : 'good';
            
            const generatedLengthClass = data.is_good_length ? 'good' : 'warning';
            
            content.innerHTML = `
                <div class="mb-3">
                    <h6><i class="fas fa-newspaper"></i> Статья</h6>
                    <p class="mb-1"><strong>${data.post.title}</strong></p>
                    <p class="mb-0 text-muted"><small>${data.post.date}</small></p>
                </div>
                
                <div class="description-preview">
                    <h6>Текущее description <span class="badge bg-warning text-dark">Дубликат!</span></h6>
                    ${data.current ? `
                        <div class="text">${data.current}</div>
                        <div class="mt-2">
                            <span class="length-indicator ${currentLengthClass}">${data.current_length} символов</span>
                        </div>
                    ` : '<p class="text-danger"><em>Отсутствует</em></p>'}
                </div>
                
                <div class="description-preview">
                    <h6>Новое уникальное description</h6>
                    <div class="text">${data.generated}</div>
                    <div class="mt-2">
                        <span class="length-indicator ${generatedLengthClass}">${data.generated_length} символов</span>
                        ${data.is_good_length ? '<span class="ms-2 text-success"><i class="fas fa-check-circle"></i> Оптимальная длина</span>' : '<span class="ms-2 text-warning"><i class="fas fa-exclamation-triangle"></i> Вне оптимального диапазона (100-160)</span>'}
                    </div>
                </div>
                
                <div class="alert alert-warning mb-0">
                    <i class="fas fa-exclamation-triangle"></i> 
                    <strong>Дублирующиеся descriptions вредят SEO!</strong>
                    Рекомендуется сгенерировать уникальное описание для каждой статьи.
                </div>
            `;
            
            applyBtn.disabled = false;
        } else {
            content.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
        }
    } catch (error) {
        content.innerHTML = `<div class="alert alert-danger">Ошибка: ${error.message}</div>`;
    }
}

// Применить description
document.getElementById('applyDescriptionBtn')?.addEventListener('click', async function() {
    if (!currentPostId || !currentDescription) return;
    
    this.disabled = true;
    this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Сохранение...';
    
    try {
        const response = await fetch('{{ route("admin.meta-descriptions.apply") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ 
                post_id: currentPostId,
                description: currentDescription
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('previewModal')).hide();
            location.reload();
        } else {
            alert('Ошибка: ' + (data.error || 'Неизвестная ошибка'));
            this.disabled = false;
            this.innerHTML = '<i class="fas fa-check"></i> Применить';
        }
    } catch (error) {
        alert('Ошибка: ' + error.message);
        this.disabled = false;
        this.innerHTML = '<i class="fas fa-check"></i> Применить';
    }
});
</script>
@endsection
