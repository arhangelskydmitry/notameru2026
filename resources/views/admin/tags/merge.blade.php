@extends('layouts.admin')

@section('title', 'Объединение тегов')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-10 offset-md-1">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">🔀 Объединение тегов</h1>
                <a href="{{ route('admin.tags.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Назад к списку
                </a>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                <strong>Как это работает:</strong>
                <ul class="mb-0 mt-2">
                    <li>Выберите один или несколько исходных тегов для объединения</li>
                    <li>Выберите целевой тег, в который будут объединены все исходные</li>
                    <li>Все статьи с исходными тегами будут переназначены на целевой тег</li>
                    <li>Исходные теги будут удалены после объединения</li>
                    <li>Дубликаты автоматически удаляются</li>
                </ul>
            </div>

            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.tags.merge.execute') }}" id="mergeForm">
                        @csrf

                        <!-- Исходные теги -->
                        <div class="mb-4">
                            <label class="form-label">
                                <h5>1️⃣ Выберите исходные теги для объединения</h5>
                            </label>
                            <div class="row mb-2">
                                <div class="col-md-6">
                                    <input type="text" id="searchSource" class="form-control" placeholder="🔍 Поиск тегов...">
                                </div>
                                <div class="col-md-6">
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="selectAll()">Выбрать все</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="deselectAll()">Снять все</button>
                                    <button type="button" class="btn btn-sm btn-outline-warning" onclick="selectUnused()">Неиспользуемые</button>
                                </div>
                            </div>
                            <div class="border rounded p-3" style="max-height: 400px; overflow-y: auto;">
                                <div id="sourceTagsList">
                                    @foreach($tags as $tag)
                                        <div class="form-check tag-item" data-name="{{ strtolower($tag->term->name ?? '') }}">
                                            <input class="form-check-input source-tag" 
                                                   type="checkbox" 
                                                   name="source_tags[]" 
                                                   value="{{ $tag->term_taxonomy_id }}" 
                                                   id="source_{{ $tag->term_taxonomy_id }}"
                                                   data-unused="{{ $tag->count == 0 ? '1' : '0' }}">
                                            <label class="form-check-label w-100" for="source_{{ $tag->term_taxonomy_id }}">
                                                <strong>{{ $tag->term->name ?? 'N/A' }}</strong>
                                                <span class="badge {{ $tag->count > 0 ? 'bg-success' : 'bg-secondary' }} float-end">
                                                    {{ $tag->count }} {{ $tag->count == 1 ? 'статья' : 'статей' }}
                                                </span>
                                                <br>
                                                <small class="text-muted">slug: {{ $tag->term->slug ?? 'N/A' }}</small>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <small class="text-muted">Выбрано: <span id="selectedCount">0</span> тег(ов)</small>
                        </div>

                        <!-- Целевой тег -->
                        <div class="mb-4">
                            <label for="target_tag" class="form-label">
                                <h5>2️⃣ Выберите целевой тег (куда объединить)</h5>
                            </label>
                            <div class="row mb-2">
                                <div class="col-md-12">
                                    <input type="text" id="searchTarget" class="form-control" placeholder="🔍 Поиск целевого тега...">
                                </div>
                            </div>
                            <select class="form-select" name="target_tag" id="target_tag" size="15" required>
                                <option value="">-- Выберите целевой тег --</option>
                                @foreach($tags->sortByDesc('count') as $tag)
                                    <option value="{{ $tag->term_taxonomy_id }}" 
                                            data-name="{{ strtolower($tag->term->name ?? '') }}"
                                            data-count="{{ $tag->count }}">
                                        {{ $tag->term->name ?? 'N/A' }} ({{ $tag->count }} {{ $tag->count == 1 ? 'статья' : 'статей' }})
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">Рекомендуется выбирать наиболее используемый тег</small>
                        </div>

                        <!-- Предпросмотр -->
                        <div class="alert alert-warning" id="previewAlert" style="display:none;">
                            <h5><i class="fas fa-exclamation-triangle"></i> Предпросмотр объединения:</h5>
                            <div id="previewContent"></div>
                        </div>

                        <!-- Кнопки -->
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('admin.tags.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Отмена
                            </a>
                            <button type="button" class="btn btn-warning" onclick="showPreview()">
                                <i class="fas fa-eye"></i> Предпросмотр
                            </button>
                            <button type="submit" class="btn btn-danger" id="submitBtn" disabled>
                                <i class="fas fa-code-branch"></i> Выполнить объединение
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Поиск исходных тегов
document.getElementById('searchSource').addEventListener('input', function() {
    const query = this.value.toLowerCase();
    document.querySelectorAll('#sourceTagsList .tag-item').forEach(item => {
        const name = item.dataset.name;
        item.style.display = name.includes(query) ? 'block' : 'none';
    });
});

// Поиск целевого тега
document.getElementById('searchTarget').addEventListener('input', function() {
    const query = this.value.toLowerCase();
    const select = document.getElementById('target_tag');
    Array.from(select.options).forEach(option => {
        if (option.value === '') return;
        const name = option.dataset.name;
        option.style.display = name.includes(query) ? 'block' : 'none';
    });
});

// Подсчет выбранных тегов
document.querySelectorAll('.source-tag').forEach(checkbox => {
    checkbox.addEventListener('change', updateSelectedCount);
});

function updateSelectedCount() {
    const count = document.querySelectorAll('.source-tag:checked').length;
    document.getElementById('selectedCount').textContent = count;
    document.getElementById('submitBtn').disabled = count === 0;
}

function selectAll() {
    document.querySelectorAll('.source-tag').forEach(cb => {
        if (cb.closest('.tag-item').style.display !== 'none') {
            cb.checked = true;
        }
    });
    updateSelectedCount();
}

function deselectAll() {
    document.querySelectorAll('.source-tag').forEach(cb => cb.checked = false);
    updateSelectedCount();
}

function selectUnused() {
    document.querySelectorAll('.source-tag').forEach(cb => {
        cb.checked = cb.dataset.unused === '1';
    });
    updateSelectedCount();
}

function showPreview() {
    const sourceTags = Array.from(document.querySelectorAll('.source-tag:checked'));
    const targetSelect = document.getElementById('target_tag');
    const targetValue = targetSelect.value;

    if (sourceTags.length === 0) {
        alert('Выберите хотя бы один исходный тег!');
        return;
    }

    if (!targetValue) {
        alert('Выберите целевой тег!');
        return;
    }

    // Проверка что целевой не в списке исходных
    const sourceIds = sourceTags.map(cb => cb.value);
    if (sourceIds.includes(targetValue)) {
        alert('Целевой тег не может быть в списке исходных тегов!');
        return;
    }

    const targetName = targetSelect.options[targetSelect.selectedIndex].text;
    const sourceNames = sourceTags.map(cb => cb.nextElementSibling.querySelector('strong').textContent);

    let html = `
        <p><strong>Будет выполнено:</strong></p>
        <ul>
            <li><strong>Исходные теги (${sourceTags.length}):</strong> ${sourceNames.join(', ')}</li>
            <li><strong>Целевой тег:</strong> ${targetName}</li>
        </ul>
        <p class="text-danger mb-0"><i class="fas fa-exclamation-triangle"></i> <strong>Внимание!</strong> После объединения исходные теги будут удалены. Это действие необратимо!</p>
    `;

    document.getElementById('previewContent').innerHTML = html;
    document.getElementById('previewAlert').style.display = 'block';
    document.getElementById('submitBtn').disabled = false;
}

// Подтверждение при отправке
document.getElementById('mergeForm').addEventListener('submit', function(e) {
    const count = document.querySelectorAll('.source-tag:checked').length;
    if (!confirm(`Вы уверены, что хотите объединить ${count} тег(ов)? Это действие необратимо!`)) {
        e.preventDefault();
    }
});
</script>
@endsection
