@extends('layouts.admin')

@section('title', 'Мета-описания')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h1><i class="fas fa-file-alt"></i> Управление мета-описаниями</h1>
            <p class="text-muted">Анализ и автоматическая генерация SEO-описаний для статей</p>
            
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> 
                <strong>Как работает система:</strong>
                <ul class="mb-0 mt-2">
                    <li><strong>"Без сохраненного"</strong> - статьи используют автогенерацию из excerpt/контента (не сохранено в БД)</li>
                    <li><strong>"Короткие/Длинные"</strong> - сохраненные descriptions с неоптимальной длиной</li>
                    <li><strong>"Хорошие"</strong> - сохраненные descriptions оптимальной длины (100-160 символов)</li>
                    <li><strong>Цель:</strong> Сохранить уникальные, оптимизированные descriptions для всех статей</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Статистика -->
    <div class="row mb-4">
        <div class="col-md-2">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h3>{{ number_format($stats['total']) }}</h3>
                    <p class="mb-0">Всего статей</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h3>{{ number_format($stats['no_description']) }}</h3>
                    <p class="mb-0" style="font-size: 11px;">Без сохраненного</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h3>{{ number_format($stats['short']) }}</h3>
                    <p class="mb-0">Короткие (&lt;100)</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h3>{{ number_format($stats['long']) }}</h3>
                    <p class="mb-0">Длинные (&gt;160)</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-secondary text-white">
                <div class="card-body">
                    <h3>{{ number_format($stats['duplicates']) }}</h3>
                    <p class="mb-0">Дубликаты</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h3>{{ number_format($stats['good']) }}</h3>
                    <p class="mb-0">Хорошие</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Фильтры и действия -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Фильтр</label>
                    <select class="form-select" id="filterSelect">
                        <option value="all" {{ $filter == 'all' ? 'selected' : '' }}>Все статьи</option>
                        <option value="no_description" {{ $filter == 'no_description' ? 'selected' : '' }}>Без сохраненного description</option>
                        <option value="short" {{ $filter == 'short' ? 'selected' : '' }}>Короткие (&lt;100)</option>
                        <option value="long" {{ $filter == 'long' ? 'selected' : '' }}>Длинные (&gt;160)</option>
                        <option value="duplicates" {{ $filter == 'duplicates' ? 'selected' : '' }}>Дубликаты</option>
                        <option value="good" {{ $filter == 'good' ? 'selected' : '' }}>Хорошие (100-160)</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Поиск</label>
                    <input type="text" class="form-control" id="searchInput" placeholder="Название статьи..." value="{{ $search }}">
                </div>
                <div class="col-md-5">
                    <button type="button" class="btn btn-primary" id="applyFilterBtn">
                        <i class="fas fa-search"></i> Применить
                    </button>
                    <button type="button" class="btn btn-success" id="bulkGenerateBtn" disabled>
                        <i class="fas fa-magic"></i> Сгенерировать для выбранных (<span id="selectedCount">0</span>)
                    </button>
                    <a href="{{ route('admin.meta-descriptions.export', ['filter' => $filter]) }}" class="btn btn-secondary">
                        <i class="fas fa-download"></i> Экспорт CSV
                    </a>
                </div>
            </div>
        </div>
    </div>

    @if($filter != 'duplicates')
    <!-- Список статей -->
    <div class="card">
        <div class="card-body">
            @if($posts->count() == 0)
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> 
                    @if($filter == 'no_description')
                        Все статьи имеют сохраненные мета-описания!
                    @elseif($filter == 'short')
                        Нет слишком коротких мета-описаний!
                    @elseif($filter == 'long')
                        Нет слишком длинных мета-описаний!
                    @else
                        Статьи не найдены
                    @endif
                </div>
            @else
                <div class="mb-3">
                    <input type="checkbox" id="selectAll" class="form-check-input">
                    <label for="selectAll" class="form-check-label ms-2">
                        <strong>Выбрать все на странице</strong>
                    </label>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th width="50">
                                    <input type="checkbox" id="selectAllHeader" class="form-check-input">
                                </th>
                                <th>Статья</th>
                                <th width="150">Дата</th>
                                <th width="200">Текущее description</th>
                                <th width="80">Длина</th>
                                <th width="150">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($posts as $post)
                            @php
                                $seoService = app(\App\Services\SeoService::class);
                                $description = $seoService->getDescription($post);
                                $hasSaved = $post->seo && !empty($post->seo->seo_description);
                                $length = mb_strlen($description);
                                $statusClass = '';
                                if (!$hasSaved) $statusClass = 'bg-secondary';
                                elseif ($length < 100) $statusClass = 'bg-warning';
                                elseif ($length > 160) $statusClass = 'bg-info';
                                else $statusClass = 'bg-success';
                            @endphp
                            <tr data-post-id="{{ $post->ID }}">
                                <td>
                                    <input type="checkbox" class="form-check-input post-checkbox" value="{{ $post->ID }}">
                                </td>
                                <td>
                                    <strong>{{ $post->post_title }}</strong>
                                    <br>
                                    <small class="text-muted">
                                        <a href="{{ route('post', $post->post_name) }}" target="_blank">
                                            Просмотр <i class="fas fa-external-link-alt"></i>
                                        </a>
                                    </small>
                                </td>
                                <td>
                                    <small>{{ $post->post_date->format('d.m.Y H:i') }}</small>
                                </td>
                                <td>
                                    @if($hasSaved)
                                        <small>{{ Str::limit($description, 80) }}</small>
                                    @else
                                        <span class="text-muted"><em>Автогенерация</em></span>
                                        <br>
                                        <small class="text-muted">{{ Str::limit($description, 60) }}</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge {{ $statusClass }} text-white">
                                        {{ $length }}
                                    </span>
                                    @if(!$hasSaved)
                                        <br>
                                        <small class="text-muted">Авто</small>
                                    @endif
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-primary preview-btn" data-post-id="{{ $post->ID }}">
                                        <i class="fas fa-eye"></i> Предпросмотр
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $posts->appends(['filter' => $filter, 'search' => $search])->links() }}
                </div>
            @endif
        </div>
    </div>
    @endif
</div>

<!-- Модальное окно предпросмотра -->
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Предпросмотр мета-описания</h5>
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

// Фильтр и поиск
document.getElementById('applyFilterBtn')?.addEventListener('click', function() {
    const filter = document.getElementById('filterSelect').value;
    const search = document.getElementById('searchInput').value;
    window.location.href = `{{ route('admin.meta-descriptions.index') }}?filter=${filter}&search=${encodeURIComponent(search)}`;
});

// Enter для поиска
document.getElementById('searchInput')?.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        document.getElementById('applyFilterBtn').click();
    }
});

// Выбор всех
document.getElementById('selectAll')?.addEventListener('change', function() {
    const checked = this.checked;
    document.querySelectorAll('.post-checkbox').forEach(cb => cb.checked = checked);
    updateSelectedCount();
});

document.getElementById('selectAllHeader')?.addEventListener('change', function() {
    const checked = this.checked;
    document.querySelectorAll('.post-checkbox').forEach(cb => cb.checked = checked);
    updateSelectedCount();
});

// Обновление счетчика выбранных
document.querySelectorAll('.post-checkbox').forEach(cb => {
    cb.addEventListener('change', updateSelectedCount);
});

function updateSelectedCount() {
    const count = document.querySelectorAll('.post-checkbox:checked').length;
    document.getElementById('selectedCount').textContent = count;
    document.getElementById('bulkGenerateBtn').disabled = count === 0;
}

// Предпросмотр
document.querySelectorAll('.preview-btn').forEach(btn => {
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
    
    content.innerHTML = '<div class="text-center"><div class="spinner-border"></div><p class="mt-2">Генерация...</p></div>';
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
                    <h6>Текущее description</h6>
                    ${data.current ? `
                        <div class="text">${data.current}</div>
                        <div class="mt-2">
                            <span class="length-indicator ${currentLengthClass}">${data.current_length} символов</span>
                        </div>
                    ` : '<p class="text-danger"><em>Отсутствует</em></p>'}
                </div>
                
                <div class="description-preview">
                    <h6>Сгенерированное description</h6>
                    <div class="text">${data.generated}</div>
                    <div class="mt-2">
                        <span class="length-indicator ${generatedLengthClass}">${data.generated_length} символов</span>
                        ${data.is_good_length ? '<span class="ms-2 text-success"><i class="fas fa-check-circle"></i> Оптимальная длина</span>' : '<span class="ms-2 text-warning"><i class="fas fa-exclamation-triangle"></i> Вне оптимального диапазона (100-160)</span>'}
                    </div>
                </div>
                
                <div class="alert alert-info mb-0">
                    <i class="fas fa-info-circle"></i> 
                    <strong>Google показывает 150-160 символов</strong> в результатах поиска.
                    Оптимальная длина: 100-160 символов.
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
            
            // Обновляем строку в таблице
            const row = document.querySelector(`tr[data-post-id="${currentPostId}"]`);
            if (row) {
                location.reload(); // Проще перезагрузить
            }
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

// Массовая генерация
document.getElementById('bulkGenerateBtn')?.addEventListener('click', async function() {
    const selected = Array.from(document.querySelectorAll('.post-checkbox:checked')).map(cb => cb.value);
    
    if (selected.length === 0) return;
    
    const overwrite = confirm(`Вы выбрали ${selected.length} статей.\n\n❓ Перезаписать существующие descriptions?\n\n• ДА - обновить все\n• НЕТ - только пустые`);
    
    this.disabled = true;
    this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Генерация...';
    
    try {
        const response = await fetch('{{ route("admin.meta-descriptions.bulk-generate") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ 
                post_ids: selected,
                overwrite: overwrite
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            const results = data.results;
            alert(`✅ Готово!\n\nУспешно: ${results.success}\nПропущено: ${results.skipped}\nОшибок: ${results.errors}`);
            location.reload();
        } else {
            alert('Ошибка: ' + (data.error || 'Неизвестная ошибка'));
        }
    } catch (error) {
        alert('Ошибка: ' + error.message);
    } finally {
        this.disabled = false;
        this.innerHTML = '<i class="fas fa-magic"></i> Сгенерировать для выбранных (<span id="selectedCount">0</span>)';
    }
});
</script>
@endsection
