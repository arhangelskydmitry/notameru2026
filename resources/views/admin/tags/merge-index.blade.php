@extends('layouts.admin')

@section('title', 'Слияние тегов')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h1><i class="fas fa-code-branch"></i> Слияние дубликатов тегов</h1>
            <p class="text-muted">Поиск и объединение похожих тегов для очистки базы данных</p>
        </div>
    </div>

    <!-- Статистика -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h3>{{ $stats['total_tags'] }}</h3>
                    <p class="mb-0">Всего тегов</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h3>{{ $stats['total_groups'] }}</h3>
                    <p class="mb-0">Групп похожих</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h3>{{ $stats['potential_duplicates'] }}</h3>
                    <p class="mb-0">Потенциальных дубликатов</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h3>{{ $stats['potential_cleanup'] }}</h3>
                    <p class="mb-0">Можно удалить</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Фильтры -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.tags.merge-index') }}" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Порог похожести (%)</label>
                    <input type="range" class="form-range" name="threshold" 
                           min="60" max="100" step="5" 
                           value="{{ $similarityThreshold }}"
                           oninput="this.nextElementSibling.value = this.value">
                    <output>{{ $similarityThreshold }}</output>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Минимум статей</label>
                    <input type="number" class="form-control" name="min_count" 
                           value="{{ $minCount }}" min="0">
                </div>
                <div class="col-md-4">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary d-block w-100">
                        <i class="fas fa-search"></i> Найти похожие
                    </button>
                </div>
            </form>
        </div>
    </div>

    @if(empty($similarGroups))
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> 
            Похожих тегов не найдено! База данных в порядке.
        </div>
    @else
        <!-- Массовые действия -->
        <div class="card mb-4">
            <div class="card-body">
                <button type="button" class="btn btn-success" id="selectAllGroups">
                    <i class="fas fa-check-square"></i> Выбрать все группы
                </button>
                <button type="button" class="btn btn-secondary" id="deselectAllGroups">
                    <i class="fas fa-square"></i> Снять выделение
                </button>
                <button type="button" class="btn btn-primary" id="mergeSelectedGroups" disabled>
                    <i class="fas fa-code-branch"></i> Объединить выбранные (<span id="selectedCount">0</span>)
                </button>
            </div>
        </div>

        <!-- Группы похожих тегов -->
        @foreach($similarGroups as $index => $group)
        <div class="card mb-3 tag-group" data-group-index="{{ $index }}">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="form-check">
                        <input class="form-check-input group-checkbox" type="checkbox" 
                               id="group-{{ $index }}"
                               data-primary-id="{{ $group['suggested_primary']->term_id }}"
                               data-merge-ids="{{ json_encode(collect($group['tags'])->except(0)->pluck('term_id')->values()) }}">
                        <label class="form-check-label" for="group-{{ $index }}">
                            <strong>Группа {{ $index + 1 }}</strong>
                            <span class="badge bg-info">{{ count($group['tags']) }} тегов</span>
                            <span class="badge bg-primary">{{ $group['total_articles'] }} статей всего</span>
                        </label>
                    </div>
                    <div>
                        <button type="button" class="btn btn-sm btn-outline-primary preview-merge-btn"
                                data-group-index="{{ $index }}">
                            <i class="fas fa-eye"></i> Предпросмотр
                        </button>
                        <button type="button" class="btn btn-sm btn-success execute-merge-btn"
                                data-group-index="{{ $index }}">
                            <i class="fas fa-code-branch"></i> Объединить
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12">
                        <h6>Основной тег (рекомендуется):</h6>
                        <div class="primary-tag-selector mb-3">
                            @foreach($group['tags'] as $tagIndex => $tag)
                            <div class="form-check form-check-inline">
                                <input class="form-check-input primary-radio" type="radio" 
                                       name="primary_{{ $index }}" 
                                       id="primary_{{ $index }}_{{ $tagIndex }}"
                                       value="{{ $tag->term_id }}"
                                       {{ $tagIndex === 0 ? 'checked' : '' }}>
                                <label class="form-check-label" for="primary_{{ $index }}_{{ $tagIndex }}">
                                    <strong>{{ $tag->term->name }}</strong>
                                    <span class="badge bg-secondary">{{ $tag->count }} статей</span>
                                    @if($tagIndex === 0)
                                        <i class="fas fa-star text-warning" title="Рекомендуется"></i>
                                    @endif
                                </label>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12">
                        <h6>Теги для слияния:</h6>
                        <div class="merge-tags-list">
                            @foreach($group['tags'] as $tagIndex => $tag)
                                @if($tagIndex > 0)
                                <span class="badge bg-light text-dark me-2 mb-2" style="font-size: 14px;">
                                    {{ $tag->term->name }}
                                    <span class="badge bg-secondary">{{ $tag->count }}</span>
                                </span>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    @endif
</div>

<!-- Модальное окно предпросмотра -->
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Предпросмотр слияния тегов</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="previewContent">
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Загрузка...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
                <button type="button" class="btn btn-success" id="confirmMergeBtn">
                    <i class="fas fa-code-branch"></i> Подтвердить слияние
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.tag-group {
    transition: all 0.3s;
}
.tag-group.merged {
    opacity: 0.5;
    background: #d4edda;
}
.primary-tag-selector .form-check {
    margin-right: 15px;
    padding: 10px;
    border: 2px solid transparent;
    border-radius: 5px;
}
.primary-tag-selector .form-check:has(input:checked) {
    border-color: #0d6efd;
    background: #e7f1ff;
}
</style>

@endsection

@section('scripts')
<script>
let currentGroupIndex = null;
let currentPrimaryId = null;
let currentMergeIds = [];

// Выбор/снятие всех групп
document.getElementById('selectAllGroups')?.addEventListener('click', function() {
    document.querySelectorAll('.group-checkbox').forEach(cb => cb.checked = true);
    updateSelectedCount();
});

document.getElementById('deselectAllGroups')?.addEventListener('click', function() {
    document.querySelectorAll('.group-checkbox').forEach(cb => cb.checked = false);
    updateSelectedCount();
});

// Обновление счетчика выбранных
function updateSelectedCount() {
    const count = document.querySelectorAll('.group-checkbox:checked').length;
    document.getElementById('selectedCount').textContent = count;
    document.getElementById('mergeSelectedGroups').disabled = count === 0;
}

document.querySelectorAll('.group-checkbox').forEach(cb => {
    cb.addEventListener('change', updateSelectedCount);
});

// Предпросмотр слияния
document.querySelectorAll('.preview-merge-btn').forEach(btn => {
    btn.addEventListener('click', async function() {
        const groupIndex = this.dataset.groupIndex;
        const card = document.querySelector(`[data-group-index="${groupIndex}"]`);
        
        const primaryId = card.querySelector('.primary-radio:checked').value;
        const allTags = Array.from(card.querySelectorAll('.primary-radio')).map(r => r.value);
        const mergeIds = allTags.filter(id => id !== primaryId);
        
        currentGroupIndex = groupIndex;
        currentPrimaryId = primaryId;
        currentMergeIds = mergeIds;
        
        await showPreview(primaryId, mergeIds);
    });
});

async function showPreview(primaryId, mergeIds) {
    const modal = new bootstrap.Modal(document.getElementById('previewModal'));
    const content = document.getElementById('previewContent');
    
    content.innerHTML = '<div class="text-center"><div class="spinner-border"></div></div>';
    modal.show();
    
    try {
        const response = await fetch('{{ route("admin.tags.merge-preview") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ primary_id: primaryId, merge_ids: mergeIds })
        });
        
        const data = await response.json();
        
        if (data.success) {
            content.innerHTML = `
                <div class="alert alert-info">
                    <h5><i class="fas fa-info-circle"></i> Основной тег</h5>
                    <p class="mb-0"><strong>${data.primary_tag.name}</strong> (сейчас: ${data.primary_tag.current_count} статей)</p>
                </div>
                
                <div class="alert alert-warning">
                    <h5><i class="fas fa-code-branch"></i> Будут объединены</h5>
                    <ul class="mb-0">
                        ${data.merge_tags.map(t => `<li><strong>${t.name}</strong> (${t.count} статей)</li>`).join('')}
                    </ul>
                </div>
                
                <div class="alert alert-success">
                    <h5><i class="fas fa-chart-line"></i> Результат</h5>
                    <ul class="mb-0">
                        <li>Текущее количество: <strong>${data.statistics.current_count}</strong></li>
                        <li>Будет добавлено: <strong>${data.statistics.adding_count}</strong></li>
                        <li>Уже есть в обоих: <strong>${data.statistics.overlapping_count}</strong></li>
                        <li><strong>Итого статей: ${data.statistics.new_total}</strong></li>
                        <li>Будет удалено тегов: <strong>${data.statistics.tags_to_remove}</strong></li>
                    </ul>
                </div>
                
                ${data.example_articles.length > 0 ? `
                    <div class="mt-3">
                        <h6>Примеры статей, которые получат новый тег:</h6>
                        <ul class="small">
                            ${data.example_articles.map(a => `<li>${a.post_title} <span class="text-muted">(${a.post_date})</span></li>`).join('')}
                        </ul>
                    </div>
                ` : ''}
            `;
        } else {
            content.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
        }
    } catch (error) {
        content.innerHTML = `<div class="alert alert-danger">Ошибка: ${error.message}</div>`;
    }
}

// Подтверждение слияния из модального окна
document.getElementById('confirmMergeBtn')?.addEventListener('click', async function() {
    if (!currentPrimaryId || currentMergeIds.length === 0) return;
    
    await executeMerge(currentGroupIndex, currentPrimaryId, currentMergeIds);
    bootstrap.Modal.getInstance(document.getElementById('previewModal')).hide();
});

// Прямое выполнение слияния
document.querySelectorAll('.execute-merge-btn').forEach(btn => {
    btn.addEventListener('click', async function() {
        const groupIndex = this.dataset.groupIndex;
        const card = document.querySelector(`[data-group-index="${groupIndex}"]`);
        
        const primaryId = card.querySelector('.primary-radio:checked').value;
        const allTags = Array.from(card.querySelectorAll('.primary-radio')).map(r => r.value);
        const mergeIds = allTags.filter(id => id !== primaryId);
        
        if (!confirm('Вы уверены, что хотите объединить эти теги?')) return;
        
        await executeMerge(groupIndex, primaryId, mergeIds);
    });
});

async function executeMerge(groupIndex, primaryId, mergeIds) {
    const btn = document.querySelector(`[data-group-index="${groupIndex}"] .execute-merge-btn`);
    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Выполняется...';
    
    try {
        const response = await fetch('{{ route("admin.tags.merge-execute") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ primary_id: primaryId, merge_ids: mergeIds })
        });
        
        const data = await response.json();
        
        if (data.success) {
            const card = document.querySelector(`[data-group-index="${groupIndex}"]`);
            card.classList.add('merged');
            card.querySelector('.card-header').innerHTML = `
                <div class="alert alert-success mb-0">
                    <i class="fas fa-check-circle"></i> 
                    Успешно объединено! 
                    Обновлено статей: ${data.statistics.updated_articles}, 
                    Новое количество: ${data.statistics.new_total}
                </div>
            `;
            setTimeout(() => card.remove(), 3000);
        } else {
            alert('Ошибка: ' + (data.error || 'Неизвестная ошибка'));
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
    } catch (error) {
        alert('Ошибка: ' + error.message);
        btn.disabled = false;
        btn.innerHTML = originalHtml;
    }
}

// Массовое слияние выбранных групп
document.getElementById('mergeSelectedGroups')?.addEventListener('click', async function() {
    const selected = document.querySelectorAll('.group-checkbox:checked');
    
    if (selected.length === 0) {
        alert('Выберите хотя бы одну группу');
        return;
    }
    
    if (!confirm(`Вы уверены, что хотите объединить ${selected.length} групп тегов?`)) {
        return;
    }
    
    const groups = Array.from(selected).map(cb => ({
        primary_id: cb.dataset.primaryId,
        merge_ids: JSON.parse(cb.dataset.mergeIds)
    }));
    
    this.disabled = true;
    this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Выполняется...';
    
    try {
        const response = await fetch('{{ route("admin.tags.merge-bulk") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ groups })
        });
        
        const data = await response.json();
        
        alert(`Результат:\nУспешно: ${data.summary.success}\nОшибок: ${data.summary.errors}`);
        
        if (data.success) {
            location.reload();
        } else {
            this.disabled = false;
            this.innerHTML = '<i class="fas fa-code-branch"></i> Объединить выбранные';
        }
    } catch (error) {
        alert('Ошибка: ' + error.message);
        this.disabled = false;
        this.innerHTML = '<i class="fas fa-code-branch"></i> Объединить выбранные';
    }
});
</script>
@endsection
