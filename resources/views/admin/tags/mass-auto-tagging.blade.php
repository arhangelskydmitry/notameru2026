@extends('layouts.admin')

@section('title', 'Массовое автотегирование')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">🤖 Массовое автотегирование статей</h1>
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

            <!-- Описание -->
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                <strong>Как это работает:</strong>
                <ul class="mb-0 mt-2">
                    <li>Выберите существующий тег из списка</li>
                    <li>Система найдет все статьи, где встречается название тега</li>
                    <li>Автоматически привяжет тег ко всем найденным статьям</li>
                    <li>Обновит счетчик использования тега</li>
                </ul>
            </div>

            <!-- Список тегов -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">📋 Выберите тег для автотегирования</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <input type="text" id="searchTag" class="form-control" placeholder="🔍 Поиск тега...">
                        </div>
                        <div class="col-md-6 text-end">
                            <span class="text-muted">Всего тегов: {{ $tags->count() }}</span>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Тег</th>
                                    <th>Текущее кол-во статей</th>
                                    <th>Режим поиска</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody id="tagsTable">
                                @foreach($tags as $tag)
                                    <tr class="tag-row" data-name="{{ mb_strtolower($tag->term->name ?? '') }}">
                                        <td>
                                            <strong>{{ $tag->term->name ?? 'N/A' }}</strong><br>
                                            <small class="text-muted">slug: {{ $tag->term->slug ?? 'N/A' }}</small>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary" style="font-size: 14px;">
                                                {{ $tag->count }}
                                            </span>
                                        </td>
                                        <td>
                                            <select class="form-select form-select-sm search-mode-{{ $tag->term_taxonomy_id }}" style="width: 200px;">
                                                <option value="word">Любое вхождение</option>
                                                <option value="exact">Как отдельное слово</option>
                                            </select>
                                            <small class="text-muted d-block mt-1">
                                                <span class="mode-hint-word-{{ $tag->term_taxonomy_id }}">Найдет "музыкант", "музыканта"</span>
                                                <span class="mode-hint-exact-{{ $tag->term_taxonomy_id }}" style="display:none;">Найдет только "музыкант"</span>
                                            </small>
                                        </td>
                                        <td>
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-info"
                                                    onclick="previewTagging({{ $tag->term_taxonomy_id }}, '{{ addslashes($tag->term->name ?? '') }}')">
                                                <i class="fas fa-eye"></i> Предпросмотр
                                            </button>
                                            <button type="button" 
                                                    class="btn btn-sm btn-success"
                                                    onclick="executeTagging({{ $tag->term_taxonomy_id }}, '{{ addslashes($tag->term->name ?? '') }}')">
                                                <i class="fas fa-play"></i> Применить
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Поиск тегов
document.getElementById('searchTag').addEventListener('input', function() {
    const query = this.value.toLowerCase();
    document.querySelectorAll('.tag-row').forEach(row => {
        const name = row.dataset.name;
        row.style.display = name.includes(query) ? 'table-row' : 'none';
    });
});

// Переключение режима поиска
document.querySelectorAll('select[class*="search-mode"]').forEach(select => {
    select.addEventListener('change', function() {
        const tagId = this.className.match(/search-mode-(\d+)/)[1];
        const isExact = this.value === 'exact';
        document.querySelector(`.mode-hint-word-${tagId}`).style.display = isExact ? 'none' : 'block';
        document.querySelector(`.mode-hint-exact-${tagId}`).style.display = isExact ? 'block' : 'none';
    });
});

// Предпросмотр
function previewTagging(tagId, tagName) {
    const searchMode = document.querySelector(`.search-mode-${tagId}`).value;
    
    // Показываем загрузку
    showLoadingModal();
    
    fetch(`/notaadmin/tags/${tagId}/preview-single-tagging`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ search_mode: searchMode })
    })
    .then(response => response.json())
    .then(data => {
        hideLoadingModal();
        if (data.success) {
            showPreviewModal(data);
        } else {
            alert('Ошибка при анализе');
        }
    })
    .catch(error => {
        hideLoadingModal();
        console.error('Error:', error);
        alert('Ошибка при запросе');
    });
}

// Применение автотегирования
function executeTagging(tagId, tagName) {
    const searchMode = document.querySelector(`.search-mode-${tagId}`).value;
    
    if (!confirm(`Применить автотегирование для тега "${tagName}"?\n\nЭто действие добавит тег ко всем найденным статьям.`)) {
        return;
    }
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = `/notaadmin/tags/${tagId}/execute-auto-tagging`;
    
    const csrf = document.createElement('input');
    csrf.type = 'hidden';
    csrf.name = '_token';
    csrf.value = '{{ csrf_token() }}';
    form.appendChild(csrf);
    
    const modeInput = document.createElement('input');
    modeInput.type = 'hidden';
    modeInput.name = 'search_mode';
    modeInput.value = searchMode;
    form.appendChild(modeInput);
    
    document.body.appendChild(form);
    form.submit();
}

function showLoadingModal() {
    const modal = document.createElement('div');
    modal.id = 'loadingModal';
    modal.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.7);z-index:9999;display:flex;align-items:center;justify-content:center;';
    modal.innerHTML = '<div style="background:white;padding:30px;border-radius:10px;text-align:center;"><h3>⏳ Анализ...</h3><p>Пожалуйста, подождите</p></div>';
    document.body.appendChild(modal);
}

function hideLoadingModal() {
    const modal = document.getElementById('loadingModal');
    if (modal) modal.remove();
}

function showPreviewModal(data) {
    const modal = document.createElement('div');
    modal.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.7);z-index:9999;display:flex;align-items:center;justify-content:center;padding:20px;overflow-y:auto;';
    
    let html = `
        <div style="background:white;padding:30px;border-radius:10px;max-width:900px;max-height:90vh;overflow-y:auto;">
            <h3>🔍 Предпросмотр автотегирования: ${data.tag_name}</h3>
            
            <div style="background:#e7f3ff;padding:20px;border-radius:5px;margin:20px 0;">
                <div class="row">
                    <div class="col-md-3 text-center">
                        <h4 style="color:#667eea;margin:0;">${data.current_count}</h4>
                        <small>Сейчас</small>
                    </div>
                    <div class="col-md-3 text-center">
                        <h4 style="color:#28a745;margin:0;">+${data.to_add}</h4>
                        <small>Будет добавлено</small>
                    </div>
                    <div class="col-md-3 text-center">
                        <h4 style="color:#ffa500;margin:0;">${data.already_have}</h4>
                        <small>Уже имеют</small>
                    </div>
                    <div class="col-md-3 text-center">
                        <h4 style="color:#667eea;margin:0;">${data.new_total}</h4>
                        <small>Будет всего</small>
                    </div>
                </div>
            </div>
    `;

    if (data.to_add > 0) {
        html += `
            <div style="border:1px solid #28a745;padding:15px;margin:15px 0;border-radius:5px;">
                <h5 style="color:#28a745;margin:0 0 10px 0;">✅ Будет добавлено к ${data.to_add} статье(ям):</h5>
                <ul style="margin:0;padding-left:20px;font-size:13px;">
        `;
        data.examples_to_add.forEach(post => {
            const date = new Date(post.post_date).toLocaleDateString('ru-RU');
            html += `<li>${post.post_title} <small style="color:#999;">(${date})</small></li>`;
        });
        if (data.to_add > 10) {
            html += `<li><em>... и еще ${data.to_add - 10} статей</em></li>`;
        }
        html += '</ul></div>';
    }

    if (data.already_have > 0) {
        html += `
            <div style="border:1px solid #ffa500;padding:15px;margin:15px 0;border-radius:5px;">
                <h5 style="color:#ffa500;margin:0 0 10px 0;">⚠️ Уже имеют тег (${data.already_have} статей):</h5>
                <ul style="margin:0;padding-left:20px;font-size:13px;">
        `;
        data.examples_already_have.forEach(post => {
            const date = new Date(post.post_date).toLocaleDateString('ru-RU');
            html += `<li>${post.post_title} <small style="color:#999;">(${date})</small></li>`;
        });
        if (data.already_have > 5) {
            html += `<li><em>... и еще ${data.already_have - 5} статей</em></li>`;
        }
        html += '</ul></div>';
    }

    if (data.to_add === 0) {
        html += `
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                Новых статей для добавления не найдено. Все подходящие статьи уже имеют этот тег.
            </div>
        `;
    }

    html += `
            <div style="margin-top:20px;text-align:center;">
                <button onclick="this.closest('div').parentElement.remove()" style="background:#6c757d;color:white;border:none;padding:10px 30px;border-radius:5px;cursor:pointer;font-size:16px;">
                    Закрыть
                </button>
            </div>
        </div>
    `;

    modal.innerHTML = html;
    document.body.appendChild(modal);
    
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            document.body.removeChild(modal);
        }
    });
}
</script>
@endsection
