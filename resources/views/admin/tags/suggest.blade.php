@extends('layouts.admin')

@section('title', 'Предложение тегов на основе анализа контента')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">🔍 Анализ контента и предложение тегов</h1>
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
                    <li>Анализируется содержимое последних статей</li>
                    <li>Выделяются наиболее часто встречающиеся слова</li>
                    <li>Исключаются стоп-слова (предлоги, местоимения и т.д.)</li>
                    <li>Исключаются уже существующие теги</li>
                    <li>Результат - список потенциальных новых тегов</li>
                </ul>
            </div>

            <!-- Параметры анализа -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">⚙️ Параметры анализа</h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.tags.suggest') }}" class="row g-3">
                        <div class="col-md-3">
                            <label for="limit" class="form-label">Количество статей для анализа</label>
                            <input type="number" name="limit" id="limit" class="form-control" value="{{ $limit }}" min="50" max="2000" step="50">
                            <small class="text-muted">Чем больше - тем точнее, но медленнее</small>
                        </div>
                        <div class="col-md-3">
                            <label for="min_frequency" class="form-label">Минимальная частота слова</label>
                            <input type="number" name="min_frequency" id="min_frequency" class="form-control" value="{{ $minFrequency }}" min="1" max="100">
                            <small class="text-muted">Слово должно встретиться минимум N раз</small>
                        </div>
                        <div class="col-md-3">
                            <label for="top" class="form-label">Показать топ результатов</label>
                            <input type="number" name="top" id="top" class="form-control" value="{{ $topResults }}" min="10" max="500" step="10">
                            <small class="text-muted">Количество предлагаемых тегов</small>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-sync"></i> Пересчитать
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Статистика анализа -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="text-primary">Статей проанализировано</h5>
                            <h2>{{ $stats['analyzed_posts'] }}</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="text-success">Найдено слов</h5>
                            <h2>{{ $stats['total_unique_words'] }}</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="text-warning">Стоп-слов исключено</h5>
                            <h2>{{ $stats['stop_words'] }}</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="text-info">Существующих тегов</h5>
                            <h2>{{ $stats['existing_tags'] }}</h2>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Результаты -->
            @if(empty($wordFrequency))
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    Не найдено подходящих слов. Попробуйте изменить параметры анализа.
                </div>
            @else
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">📝 Предложенные теги ({{ count($wordFrequency) }})</h5>
                        <div>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="selectAll()">
                                Выбрать все
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="deselectAll()">
                                Снять все
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-success" onclick="selectTop(20)">
                                Топ-20
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.tags.bulk-create') }}" id="createTagsForm">
                            @csrf
                            
                            <!-- Опция автоматического тегирования -->
                            <div class="alert alert-success mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="auto_assign" name="auto_assign" value="1" checked>
                                    <label class="form-check-label" for="auto_assign">
                                        <strong>🤖 Умное автоматическое тегирование</strong>
                                    </label>
                                </div>
                                <small class="text-muted ms-4">
                                    Автоматически привязать созданные теги к статьям, где встречаются эти слова
                                </small>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <input type="text" id="searchWord" class="form-control" placeholder="🔍 Поиск по словам...">
                                </div>
                                <div class="col-md-6 text-end">
                                    <button type="button" class="btn btn-sm btn-outline-info" onclick="showPreview()">
                                        <i class="fas fa-eye"></i> Предпросмотр тегирования
                                    </button>
                                    <span class="badge bg-info ms-2" style="font-size: 1rem;">
                                        Выбрано: <span id="selectedCount">0</span>
                                    </span>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th width="30">
                                                <input type="checkbox" id="selectAllCheckbox" class="form-check-input">
                                            </th>
                                            <th width="50">#</th>
                                            <th>Слово</th>
                                            <th>Частота</th>
                                            <th width="200">Визуализация</th>
                                        </tr>
                                    </thead>
                                    <tbody id="wordsTable">
                                        @php $maxFreq = !empty($wordFrequency) ? max($wordFrequency) : 1; @endphp
                                        @foreach($wordFrequency as $word => $frequency)
                                            <tr class="word-row" data-word="{{ $word }}">
                                                <td>
                                                    <input type="checkbox" 
                                                           name="words[]" 
                                                           value="{{ $word }}" 
                                                           class="form-check-input word-checkbox">
                                                </td>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>
                                                    <strong>{{ ucfirst($word) }}</strong>
                                                </td>
                                                <td>
                                                    <span class="badge bg-primary">{{ $frequency }}</span>
                                                </td>
                                                <td>
                                                    <div class="progress" style="height: 25px;">
                                                        <div class="progress-bar bg-success" 
                                                             role="progressbar" 
                                                             style="width: {{ ($frequency / $maxFreq) * 100 }}%">
                                                            {{ $frequency }}
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="d-flex justify-content-between mt-4">
                                <a href="{{ route('admin.tags.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Отмена
                                </a>
                                <button type="submit" class="btn btn-success" id="createBtn" disabled>
                                    <i class="fas fa-plus"></i> Создать выбранные теги (<span id="createCount">0</span>)
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAllCheckbox = document.getElementById('selectAllCheckbox');
    const checkboxes = document.querySelectorAll('.word-checkbox');
    const createBtn = document.getElementById('createBtn');
    const selectedCount = document.getElementById('selectedCount');
    const createCount = document.getElementById('createCount');
    const searchInput = document.getElementById('searchWord');

    // Обновление счетчика
    function updateCount() {
        const count = document.querySelectorAll('.word-checkbox:checked').length;
        selectedCount.textContent = count;
        createCount.textContent = count;
        createBtn.disabled = count === 0;
    }

    // Выбор всех
    selectAllCheckbox.addEventListener('change', function() {
        checkboxes.forEach(cb => {
            const row = cb.closest('.word-row');
            if (row.style.display !== 'none') {
                cb.checked = this.checked;
            }
        });
        updateCount();
    });

    // Изменение чекбоксов
    checkboxes.forEach(cb => {
        cb.addEventListener('change', updateCount);
    });

    // Поиск
    searchInput.addEventListener('input', function() {
        const query = this.value.toLowerCase();
        document.querySelectorAll('.word-row').forEach(row => {
            const word = row.dataset.word;
            row.style.display = word.includes(query) ? 'table-row' : 'none';
        });
        updateCount();
    });

    // Подтверждение создания
    document.getElementById('createTagsForm').addEventListener('submit', function(e) {
        const count = document.querySelectorAll('.word-checkbox:checked').length;
        if (!confirm(`Вы уверены, что хотите создать ${count} нов${count === 1 ? 'ый тег' : count < 5 ? 'ых тега' : 'ых тегов'}?`)) {
            e.preventDefault();
        }
    });
});

function selectAll() {
    document.querySelectorAll('.word-checkbox').forEach(cb => {
        if (cb.closest('.word-row').style.display !== 'none') {
            cb.checked = true;
        }
    });
    document.querySelectorAll('.word-checkbox')[0].dispatchEvent(new Event('change'));
}

function deselectAll() {
    document.querySelectorAll('.word-checkbox').forEach(cb => cb.checked = false);
    document.querySelectorAll('.word-checkbox')[0].dispatchEvent(new Event('change'));
}

function selectTop(n) {
    deselectAll();
    const visibleRows = Array.from(document.querySelectorAll('.word-row')).filter(row => row.style.display !== 'none');
    visibleRows.slice(0, n).forEach(row => {
        row.querySelector('.word-checkbox').checked = true;
    });
    document.querySelectorAll('.word-checkbox')[0].dispatchEvent(new Event('change'));
}

// Предпросмотр автотегирования
function showPreview() {
    const autoAssignChecked = document.getElementById('auto_assign').checked;
    
    if (!autoAssignChecked) {
        alert('Включите опцию "Умное автоматическое тегирование" для просмотра');
        return;
    }

    const selectedCheckboxes = document.querySelectorAll('.word-checkbox:checked');
    if (selectedCheckboxes.length === 0) {
        alert('Выберите хотя бы один тег для предпросмотра');
        return;
    }

    const words = Array.from(selectedCheckboxes).map(cb => cb.value);

    // Показываем загрузку
    const modal = document.createElement('div');
    modal.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.7);z-index:9999;display:flex;align-items:center;justify-content:center;';
    modal.innerHTML = '<div style="background:white;padding:30px;border-radius:10px;max-width:800px;max-height:80vh;overflow-y:auto;"><h3>⏳ Анализ статей...</h3><p>Пожалуйста, подождите</p></div>';
    document.body.appendChild(modal);

    // Отправляем запрос
    fetch('{{ route('admin.tags.preview-auto-tagging') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ words: words })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showPreviewModal(data.preview, data.total_posts, data.total_tags);
        } else {
            alert('Ошибка при анализе');
        }
        document.body.removeChild(modal);
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Ошибка при запросе');
        document.body.removeChild(modal);
    });
}

function showPreviewModal(preview, totalPosts, totalTags) {
    const modal = document.createElement('div');
    modal.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.7);z-index:9999;display:flex;align-items:center;justify-content:center;padding:20px;';
    
    let html = `
        <div style="background:white;padding:30px;border-radius:10px;max-width:900px;max-height:90vh;overflow-y:auto;">
            <h3>🔍 Предпросмотр автоматического тегирования</h3>
            <div style="background:#e7f3ff;padding:15px;border-radius:5px;margin:20px 0;">
                <strong>Итого:</strong> ${totalTags} тегов будет применено к ${totalPosts} статьям
            </div>
            <div style="max-height:500px;overflow-y:auto;">
    `;

    preview.forEach(item => {
        const status = item.exists ? '<span style="color:#ffa500;">⚠️ Уже существует</span>' : '<span style="color:#28a745;">✅ Будет создан</span>';
        html += `
            <div style="border:1px solid #ddd;padding:15px;margin:10px 0;border-radius:5px;">
                <h5 style="margin:0 0 10px 0;">
                    ${item.word} ${status}
                    <span style="float:right;background:#667eea;color:white;padding:3px 10px;border-radius:15px;font-size:14px;">
                        ${item.posts_count} статей
                    </span>
                </h5>
        `;

        if (item.example_posts.length > 0) {
            html += '<p style="margin:10px 0 5px 0;font-size:12px;color:#666;"><strong>Примеры статей:</strong></p><ul style="margin:0;padding-left:20px;font-size:13px;">';
            item.example_posts.forEach(post => {
                const date = new Date(post.post_date).toLocaleDateString('ru-RU');
                html += `<li>${post.post_title} <small style="color:#999;">(${date})</small></li>`;
            });
            html += '</ul>';
        }

        html += '</div>';
    });

    html += `
            </div>
            <div style="margin-top:20px;text-align:center;">
                <button onclick="this.closest('div').parentElement.remove()" style="background:#6c757d;color:white;border:none;padding:10px 30px;border-radius:5px;cursor:pointer;font-size:16px;">
                    Закрыть
                </button>
            </div>
        </div>
    `;

    modal.innerHTML = html;
    document.body.appendChild(modal);

    // Закрытие по клику вне модального окна
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            document.body.removeChild(modal);
        }
    });
}
</script>
@endsection
