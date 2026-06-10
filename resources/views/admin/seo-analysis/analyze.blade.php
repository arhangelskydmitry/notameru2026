@extends('layouts.admin')

@section('title', 'SEO Анализ статей')

@push('styles')
<style>
    .filters-bar {
        background: #1e293b;
        border-radius: 12px;
        padding: 1rem 1.5rem;
        margin-bottom: 1.5rem;
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        align-items: center;
        border: 1px solid rgba(255,255,255,0.1);
    }
    
    .filter-group {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .filter-group label {
        color: #cbd5e1;
        font-size: 0.875rem;
        white-space: nowrap;
        font-weight: 500;
    }
    
    .filter-group select,
    .filter-group input {
        background: #0f172a;
        border: 1px solid rgba(255,255,255,0.2);
        color: #f1f5f9;
        border-radius: 8px;
        padding: 0.5rem 1rem;
        font-size: 0.875rem;
    }
    
    .seo-table {
        background: #1e293b;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 6px rgba(0,0,0,0.3);
    }
    
    .seo-table table {
        width: 100%;
        margin: 0;
    }
    
    .seo-table th {
        background: #0f172a;
        color: #cbd5e1;
        font-weight: 600;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        padding: 1rem;
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }
    
    .seo-table td {
        padding: 1rem;
        border-bottom: 1px solid rgba(255,255,255,0.08);
        color: #f1f5f9;
        vertical-align: middle;
        background: #1e293b;
    }
    
    .seo-table tr:hover td {
        background: #334155;
    }
    
    .score-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 48px;
        height: 48px;
        border-radius: 12px;
        font-weight: 700;
        font-size: 1rem;
    }
    
    .score-badge.excellent {
        background: linear-gradient(135deg, rgba(16, 185, 129, 0.2), rgba(5, 150, 105, 0.2));
        color: #10b981;
        border: 2px solid #10b981;
    }
    
    .score-badge.good {
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.2), rgba(37, 99, 235, 0.2));
        color: #3b82f6;
        border: 2px solid #3b82f6;
    }
    
    .score-badge.fair {
        background: linear-gradient(135deg, rgba(245, 158, 11, 0.2), rgba(217, 119, 6, 0.2));
        color: #f59e0b;
        border: 2px solid #f59e0b;
    }
    
    .score-badge.bad, .score-badge.empty {
        background: linear-gradient(135deg, rgba(239, 68, 68, 0.2), rgba(220, 38, 38, 0.2));
        color: #ef4444;
        border: 2px solid #ef4444;
    }
    
    .issue-tag {
        display: inline-block;
        font-size: 0.7rem;
        padding: 0.3rem 0.6rem;
        border-radius: 6px;
        margin: 0.15rem;
        background: rgba(239, 68, 68, 0.25);
        color: #fca5a5;
        border: 1px solid rgba(239, 68, 68, 0.4);
        font-weight: 500;
    }
    
    .issue-tag.warning {
        background: rgba(245, 158, 11, 0.25);
        color: #fbbf24;
        border: 1px solid rgba(245, 158, 11, 0.4);
    }
    
    .post-title {
        font-weight: 600;
        color: #f8fafc;
        max-width: 400px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    
    .post-id {
        font-size: 0.75rem;
        color: #94a3b8;
        font-weight: 500;
    }
    
    .btn-sm-action {
        padding: 0.4rem 0.8rem;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
    }
    
    .btn-sm-action.generate {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
    }
    
    .btn-sm-action.generate:hover {
        transform: scale(1.05);
    }
    
    .btn-sm-action.view {
        background: rgba(255,255,255,0.1);
        color: #e2e8f0;
    }
    
    .checkbox-cell {
        width: 40px;
    }
    
    .checkbox-cell input {
        width: 18px;
        height: 18px;
        accent-color: #667eea;
    }
    
    .batch-actions {
        position: sticky;
        bottom: 0;
        background: linear-gradient(to top, rgba(15, 23, 42, 0.98) 80%, transparent);
        padding: 1.5rem;
        margin: 0 -1.5rem -1.5rem;
        display: none;
    }
    
    .batch-actions.visible {
        display: block;
    }
    
    .batch-actions-inner {
        background: #1e293b;
        border-radius: 12px;
        padding: 1rem 1.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border: 2px solid rgba(102, 126, 234, 0.5);
        box-shadow: 0 -4px 20px rgba(0,0,0,0.5);
    }
    
    .selected-count {
        color: #cbd5e1;
        font-size: 1rem;
    }
    
    .selected-count strong {
        color: #818cf8;
        font-size: 1.25rem;
    }
    
    /* Modal styles */
    .preview-modal {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.8);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        padding: 2rem;
    }
    
    .preview-modal.visible {
        display: flex;
    }
    
    .preview-content {
        background: #1e293b;
        border-radius: 16px;
        max-width: 900px;
        width: 100%;
        max-height: 90vh;
        overflow-y: auto;
    }
    
    .preview-header {
        padding: 1.5rem;
        border-bottom: 1px solid rgba(255,255,255,0.1);
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: sticky;
        top: 0;
        background: #1e293b;
        z-index: 10;
    }
    
    .preview-body {
        padding: 1.5rem;
    }
    
    .comparison-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.5rem;
    }
    
    .comparison-side {
        background: rgba(15, 23, 42, 0.5);
        border-radius: 12px;
        padding: 1.25rem;
    }
    
    .comparison-side h4 {
        font-size: 0.875rem;
        color: #94a3b8;
        margin-bottom: 1rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    
    .comparison-side.current { border-left: 3px solid #64748b; }
    .comparison-side.new { border-left: 3px solid #10b981; }
    
    .field-row {
        margin-bottom: 1rem;
    }
    
    .field-label {
        font-size: 0.75rem;
        color: #64748b;
        margin-bottom: 0.5rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    
    .field-value {
        color: #e2e8f0;
        font-size: 0.875rem;
        word-break: break-word;
    }
    
    .field-value.empty {
        color: #ef4444;
        font-style: italic;
    }
    
    .field-value.editable {
        width: 100%;
        background: transparent;
        border: 1px solid transparent;
        color: #f1f5f9;
        padding: 0.5rem;
        border-radius: 6px;
        font-size: 0.875rem;
        font-family: inherit;
        transition: all 0.2s;
        resize: vertical;
        line-height: 1.5;
    }
    
    .field-value.editable:hover {
        background: rgba(16, 185, 129, 0.05);
        border-color: rgba(16, 185, 129, 0.2);
    }
    
    .field-value.editable:focus {
        outline: none;
        background: rgba(15, 23, 42, 0.5);
        border-color: #10b981;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
    }
    
    .field-value.editable.input {
        min-height: 42px;
        display: block;
    }
    
    .field-value.editable.textarea {
        min-height: 80px;
        display: block;
    }
    
    .char-counter {
        font-size: 0.7rem;
        color: #64748b;
        margin-top: 0.25rem;
        text-align: right;
    }
    
    .char-counter.warning {
        color: #f59e0b;
    }
    
    .char-counter.error {
        color: #ef4444;
    }
    
    .edit-hint {
        font-size: 0.7rem;
        color: #64748b;
        margin-top: 0.5rem;
        font-style: italic;
    }
    
    .preview-footer {
        padding: 1.5rem;
        border-top: 1px solid rgba(255,255,255,0.1);
        display: flex;
        justify-content: flex-end;
        gap: 1rem;
        position: sticky;
        bottom: 0;
        background: #1e293b;
    }
    
    .btn-modal {
        padding: 0.75rem 1.5rem;
        border-radius: 8px;
        font-weight: 600;
        border: none;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .btn-modal.cancel {
        background: rgba(255,255,255,0.1);
        color: #e2e8f0;
    }
    
    .btn-modal.apply {
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
    }
    
    .btn-modal:hover {
        transform: translateY(-2px);
    }
    
    .loading-spinner {
        display: inline-block;
        width: 16px;
        height: 16px;
        border: 2px solid rgba(255,255,255,0.3);
        border-radius: 50%;
        border-top-color: white;
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
    
    .pagination-custom {
        display: flex;
        justify-content: center;
        gap: 0.5rem;
        margin-top: 1.5rem;
    }
    
    .pagination-custom a,
    .pagination-custom span {
        padding: 0.5rem 1rem;
        border-radius: 8px;
        text-decoration: none;
        font-size: 0.875rem;
    }
    
    .pagination-custom a {
        background: rgba(255,255,255,0.1);
        color: #e2e8f0;
    }
    
    .pagination-custom a:hover {
        background: rgba(255,255,255,0.2);
    }
    
    .pagination-custom span.current {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
    }
    
    .pagination-custom span.disabled {
        color: #64748b;
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="{{ route('admin.seo-analysis') }}" class="text-muted text-decoration-none small">
                ← Назад к обзору
            </a>
            <h1 class="h3 mb-0 text-white mt-2">📊 Анализ статей</h1>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-success btn-sm" id="btnSelectAll">
                <i class="fas fa-check-double"></i> Выбрать все плохие
            </button>
            <select class="form-select form-select-sm" id="providerSelect" style="width: auto; background: #1e293b; color: #e2e8f0; border-color: rgba(255,255,255,0.1);">
                <option value="auto">AI: Авто</option>
                <option value="chatinfo">ChatInfo</option>
                <option value="gigachat">GigaChat</option>
                <option value="openai">OpenAI</option>
            </select>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="filters-bar">
        <div class="filter-group">
            <label>Фильтр:</label>
            <select id="filterStatus" onchange="applyFilter()">
                <option value="all" {{ $filter === 'all' ? 'selected' : '' }}>Все статьи</option>
                <option value="bad" {{ $filter === 'bad' ? 'selected' : '' }}>Требуют улучшения</option>
                <option value="good" {{ $filter === 'good' ? 'selected' : '' }}>Хорошее SEO</option>
                <option value="empty" {{ $filter === 'empty' ? 'selected' : '' }}>Без SEO</option>
            </select>
        </div>
        <div class="filter-group">
            <label>Мин. Score:</label>
            <input type="number" id="minScore" value="{{ $minScore }}" min="0" max="100" style="width: 80px;" onchange="applyFilter()">
        </div>
        <div class="filter-group">
            <label>На странице:</label>
            <select id="perPage" onchange="applyFilter()">
                <option value="20" {{ request('per_page') == 20 ? 'selected' : '' }}>20</option>
                <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
            </select>
        </div>
        <div class="ms-auto">
            <span class="text-muted">Найдено: <strong class="text-white">{{ $posts->total() }}</strong> статей</span>
        </div>
    </div>
    
    <!-- Table -->
    <div class="seo-table">
        <table>
            <thead>
                <tr>
                    <th class="checkbox-cell">
                        <input type="checkbox" id="selectAllCheckbox" onchange="toggleSelectAll()">
                    </th>
                    <th>Score</th>
                    <th>Статья</th>
                    <th>Проблемы</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                @foreach($analyzed as $item)
                <tr data-post-id="{{ $item['post_id'] }}" data-needs-fix="{{ $item['needs_fix'] ? '1' : '0' }}">
                    <td class="checkbox-cell">
                        <input type="checkbox" class="post-checkbox" value="{{ $item['post_id'] }}" onchange="updateBatchActions()">
                    </td>
                    <td>
                        <div class="score-badge {{ $item['status'] }}">
                            {{ $item['score'] }}
                        </div>
                    </td>
                    <td>
                        <div class="post-title">{{ $item['title'] }}</div>
                        <div class="post-id">
                            ID: {{ $item['post_id'] }} 
                            <span style="color: #94a3b8; margin-left: 8px;">
                                📅 {{ \Carbon\Carbon::parse($item['post_date'])->format('d.m.Y') }}
                            </span>
                        </div>
                    </td>
                    <td>
                        @if(empty($item['issues']))
                            <span class="text-success">✓ Всё в порядке</span>
                        @else
                            @foreach(array_slice($item['issues'], 0, 3) as $issue)
                                <span class="issue-tag {{ str_contains($issue, '❌') ? '' : 'warning' }}">
                                    {{ str_replace(['⚠️', '❌'], '', $issue) }}
                                </span>
                            @endforeach
                            @if(count($item['issues']) > 3)
                                <span class="issue-tag">+{{ count($item['issues']) - 3 }}</span>
                            @endif
                        @endif
                    </td>
                    <td>
                        <div class="d-flex gap-2">
                            <button class="btn-sm-action generate" onclick="previewSeo({{ $item['post_id'] }})">
                                <i class="fas fa-magic"></i> Генерировать
                            </button>
                            <a href="{{ route('admin.posts.edit', $item['post_id']) }}" class="btn-sm-action view" target="_blank">
                                <i class="fas fa-edit"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <div class="pagination-custom">
        @if($posts->onFirstPage())
            <span class="disabled">← Назад</span>
        @else
            <a href="{{ $posts->previousPageUrl() }}&filter={{ $filter }}&min_score={{ $minScore }}">← Назад</a>
        @endif
        
        @for($i = max(1, $posts->currentPage() - 2); $i <= min($posts->lastPage(), $posts->currentPage() + 2); $i++)
            @if($i == $posts->currentPage())
                <span class="current">{{ $i }}</span>
            @else
                <a href="{{ $posts->url($i) }}&filter={{ $filter }}&min_score={{ $minScore }}">{{ $i }}</a>
            @endif
        @endfor
        
        @if($posts->hasMorePages())
            <a href="{{ $posts->nextPageUrl() }}&filter={{ $filter }}&min_score={{ $minScore }}">Далее →</a>
        @else
            <span class="disabled">Далее →</span>
        @endif
    </div>
    
    <!-- Batch Actions -->
    <div class="batch-actions" id="batchActions">
        <div class="batch-actions-inner">
            <div class="selected-count">
                Выбрано: <strong id="selectedCount">0</strong> статей
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-secondary btn-sm" onclick="clearSelection()">
                    Отменить выбор
                </button>
                <button class="btn btn-primary btn-sm" onclick="batchPreview()">
                    <i class="fas fa-eye"></i> Предпросмотр
                </button>
                <button class="btn btn-info btn-sm" onclick="exportSql()">
                    <i class="fas fa-download"></i> Экспорт SQL
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Preview Modal -->
<div class="preview-modal" id="previewModal">
    <div class="preview-content">
        <div class="preview-header">
            <h4 class="mb-0 text-white">🔄 Предпросмотр изменений</h4>
            <button class="btn-close btn-close-white" onclick="closeModal()"></button>
        </div>
        <div class="preview-body" id="previewBody">
            <div class="text-center py-5">
                <div class="loading-spinner"></div>
                <p class="text-muted mt-3">Генерация SEO через AI...</p>
            </div>
        </div>
        <div class="preview-footer">
            <button class="btn-modal cancel" onclick="closeModal()">Отмена</button>
            <button class="btn-modal apply" id="btnApply" onclick="applySeo()" disabled>
                <i class="fas fa-check"></i> Применить изменения
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let currentPreviewData = null;
    let selectedPosts = [];
    
    function applyFilter() {
        const filter = document.getElementById('filterStatus').value;
        const minScore = document.getElementById('minScore').value;
        const perPage = document.getElementById('perPage').value;
        window.location.href = `{{ route('admin.seo-analysis.analyze') }}?filter=${filter}&min_score=${minScore}&per_page=${perPage}`;
    }
    
    function toggleSelectAll() {
        const checked = document.getElementById('selectAllCheckbox').checked;
        document.querySelectorAll('.post-checkbox').forEach(cb => {
            cb.checked = checked;
        });
        updateBatchActions();
    }
    
    function updateBatchActions() {
        const checkboxes = document.querySelectorAll('.post-checkbox:checked');
        selectedPosts = Array.from(checkboxes).map(cb => parseInt(cb.value));
        
        document.getElementById('selectedCount').textContent = selectedPosts.length;
        
        const batchActions = document.getElementById('batchActions');
        if (selectedPosts.length > 0) {
            batchActions.classList.add('visible');
        } else {
            batchActions.classList.remove('visible');
        }
    }
    
    function clearSelection() {
        document.querySelectorAll('.post-checkbox').forEach(cb => cb.checked = false);
        document.getElementById('selectAllCheckbox').checked = false;
        updateBatchActions();
    }
    
    document.getElementById('btnSelectAll').addEventListener('click', function() {
        document.querySelectorAll('tr[data-needs-fix="1"] .post-checkbox').forEach(cb => {
            cb.checked = true;
        });
        updateBatchActions();
    });
    
    async function previewSeo(postId) {
        const modal = document.getElementById('previewModal');
        const body = document.getElementById('previewBody');
        const btnApply = document.getElementById('btnApply');
        
        modal.classList.add('visible');
        btnApply.disabled = true;
        body.innerHTML = `
            <div class="text-center py-5">
                <div class="loading-spinner"></div>
                <p class="text-muted mt-3">Генерация SEO через AI...</p>
            </div>
        `;
        
        try {
            const provider = document.getElementById('providerSelect').value;
            const response = await fetch('{{ route("admin.seo-analysis.preview") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ post_id: postId, provider: provider })
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Сохраняем данные ПЕРЕД рендером
                currentPreviewData = {
                    post_id: data.post_id,
                    current: data.current,
                    new: data.new,
                    fullData: data.new // Сохраняем полные данные сразу
                };
                
                renderPreview(data);
                btnApply.disabled = false;
            } else {
                body.innerHTML = `<div class="alert alert-danger">${data.error || 'Ошибка генерации'}</div>`;
            }
        } catch (error) {
            body.innerHTML = `<div class="alert alert-danger">Ошибка: ${error.message}</div>`;
        }
    }
    
    function renderPreview(data) {
        const body = document.getElementById('previewBody');
        const current = data.current || {};
        const newData = data.new || {};
        
        // НЕ изменяем currentPreviewData здесь, он уже установлен в previewSeo
        
        body.innerHTML = `
            <div class="alert alert-info mb-3">
                <i class="fas fa-info-circle"></i> <strong>Совет:</strong> Вы можете отредактировать любое поле справа перед применением
            </div>
            <div class="comparison-grid">
                <div class="comparison-side current">
                    <h4>📋 Текущее</h4>
                    ${renderFieldReadOnly('SEO Title', current.seo_title)}
                    ${renderFieldReadOnly('Description', current.seo_description)}
                    ${renderFieldReadOnly('Keywords', current.seo_keywords)}
                    ${renderFieldReadOnly('Focus Keyword', current.focus_keyword)}
                    ${renderFieldReadOnly('OG Title', current.og_title)}
                    ${renderFieldReadOnly('OG Description', current.og_description)}
                    ${renderFieldReadOnly('Twitter Title', current.twitter_title)}
                    ${renderFieldReadOnly('Twitter Description', current.twitter_description)}
                </div>
                <div class="comparison-side new">
                    <h4>✨ Новое (Редактируемое)</h4>
                    ${renderFieldEditable('seo_title', 'SEO Title', newData.seo_title, 60)}
                    ${renderFieldEditable('seo_description', 'Description', newData.seo_description, 160)}
                    ${renderFieldEditable('seo_keywords', 'Keywords', newData.seo_keywords)}
                    ${renderFieldEditable('focus_keyword', 'Focus Keyword', newData.focus_keyword)}
                    ${renderFieldEditable('og_title', 'OG Title', newData.og_title, 60)}
                    ${renderFieldEditable('og_description', 'OG Description', newData.og_description, 160)}
                    ${renderFieldEditable('twitter_title', 'Twitter Title', newData.twitter_title, 60)}
                    ${renderFieldEditable('twitter_description', 'Twitter Description', newData.twitter_description, 160)}
                </div>
            </div>
        `;
        
        // Добавляем счётчики символов
        document.querySelectorAll('.field-value.editable').forEach(field => {
            field.addEventListener('input', function() {
                const counter = this.nextElementSibling;
                if (counter && counter.classList.contains('char-counter')) {
                    const length = this.value.length;
                    const maxLength = parseInt(this.dataset.maxLength || 9999);
                    counter.textContent = `${length} / ${maxLength} символов`;
                    
                    counter.classList.remove('warning', 'error');
                    if (length > maxLength) {
                        counter.classList.add('error');
                    } else if (length > maxLength * 0.9) {
                        counter.classList.add('warning');
                    }
                }
            });
        });
    }
    
    function renderFieldReadOnly(label, value) {
        const displayValue = value || '<span class="empty">Пусто</span>';
        return `
            <div class="field-row">
                <div class="field-label">${label}</div>
                <div class="field-value">${displayValue}</div>
            </div>
        `;
    }
    
    function renderFieldEditable(fieldName, label, value, maxLength = null) {
        const escapedValue = (value || '').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        const isTextarea = fieldName.includes('description');
        
        if (isTextarea) {
            return `
                <div class="field-row">
                    <div class="field-label">${label}</div>
                    <textarea 
                        class="field-value editable textarea" 
                        data-field="${fieldName}"
                        ${maxLength ? `data-max-length="${maxLength}"` : ''}
                        placeholder="Введите ${label.toLowerCase()}..."
                        rows="3"
                    >${escapedValue}</textarea>
                    ${maxLength ? `<div class="char-counter">${(value || '').length} / ${maxLength} символов</div>` : ''}
                </div>
            `;
        } else {
            return `
                <div class="field-row">
                    <div class="field-label">${label}</div>
                    <input 
                        type="text"
                        class="field-value editable input" 
                        data-field="${fieldName}"
                        value="${escapedValue}"
                        ${maxLength ? `data-max-length="${maxLength}"` : ''}
                        placeholder="Введите ${label.toLowerCase()}..."
                    />
                    ${maxLength ? `<div class="char-counter">${(value || '').length} / ${maxLength} символов</div>` : ''}
                </div>
            `;
        }
    }
    
    async function applySeo() {
        if (!currentPreviewData) {
            console.error('currentPreviewData is null!');
            showNotification('Ошибка: данные не загружены', 'error');
            return;
        }
        
        console.log('currentPreviewData:', currentPreviewData); // Отладка
        
        // Собираем отредактированные данные из полей
        const editedData = {};
        
        // Копируем все данные из оригинала (включая изображения)
        if (currentPreviewData.fullData) {
            Object.assign(editedData, currentPreviewData.fullData);
        }
        
        // Обновляем редактируемыми значениями
        document.querySelectorAll('.field-value.editable').forEach(field => {
            const fieldName = field.dataset.field;
            editedData[fieldName] = field.value;
        });
        
        console.log('Отправляем данные:', editedData); // Для отладки
        
        const btnApply = document.getElementById('btnApply');
        btnApply.disabled = true;
        btnApply.innerHTML = '<span class="loading-spinner"></span> Сохранение...';
        
        try {
            const response = await fetch('{{ route("admin.seo-analysis.apply") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    post_id: currentPreviewData.post_id,
                    seo_data: editedData
                })
            });
            
            const data = await response.json();
            
            console.log('Ответ сервера:', data); // Для отладки
            
            if (data.success) {
                console.log('Сохранённые данные:', data.saved_data); // Проверяем что сохранилось
                
                const savedPostId = currentPreviewData.post_id; // Сохраняем ID до закрытия
                closeModal();
                
                // Обновляем строку в таблице
                const row = document.querySelector(`tr[data-post-id="${savedPostId}"]`);
                if (row) {
                    const scoreBadge = row.querySelector('.score-badge');
                    scoreBadge.className = 'score-badge excellent';
                    scoreBadge.textContent = '100';
                    
                    const issuesTd = row.querySelectorAll('td')[3];
                    issuesTd.innerHTML = '<span class="text-success">✓ Оптимизировано</span>';
                }
                
                // Показываем уведомление
                showNotification('SEO успешно применено!', 'success');
            } else {
                showNotification(data.error || 'Ошибка сохранения', 'error');
            }
        } catch (error) {
            console.error('Ошибка:', error);
            showNotification('Ошибка: ' + error.message, 'error');
        }
        
        btnApply.disabled = false;
        btnApply.innerHTML = '<i class="fas fa-check"></i> Применить изменения';
    }
    
    async function batchPreview() {
        if (selectedPosts.length === 0) return;
        if (selectedPosts.length > 10) {
            alert('Максимум 10 статей за раз для предпросмотра. Выбрано: ' + selectedPosts.length);
            return;
        }
        
        const modal = document.getElementById('previewModal');
        const body = document.getElementById('previewBody');
        
        modal.classList.add('visible');
        body.innerHTML = `
            <div class="text-center py-5">
                <div class="loading-spinner"></div>
                <p class="text-muted mt-3">Генерация SEO для ${selectedPosts.length} статей...</p>
            </div>
        `;
        
        try {
            const provider = document.getElementById('providerSelect').value;
            const response = await fetch('{{ route("admin.seo-analysis.batch-preview") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ post_ids: selectedPosts, provider: provider })
            });
            
            const data = await response.json();
            
            if (data.success) {
                renderBatchPreview(data.results);
            } else {
                body.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
            }
        } catch (error) {
            body.innerHTML = `<div class="alert alert-danger">Ошибка: ${error.message}</div>`;
        }
    }
    
    function renderBatchPreview(results) {
        const body = document.getElementById('previewBody');
        const btnApply = document.getElementById('btnApply');
        
        currentPreviewData = { batch: true, results: results };
        btnApply.disabled = false;
        
        let html = '<div class="batch-results">';
        results.forEach(r => {
            if (r.success) {
                html += `
                    <div class="mb-4 p-3" style="background: rgba(15,23,42,0.5); border-radius: 8px;">
                        <div class="d-flex justify-content-between mb-2">
                            <strong class="text-white">${r.title}</strong>
                            <span class="badge bg-success">ID: ${r.post_id}</span>
                        </div>
                        <div class="small">
                            <div class="text-muted">Новый Title: <span class="text-info">${r.new.seo_title}</span></div>
                        </div>
                    </div>
                `;
            } else {
                html += `
                    <div class="mb-4 p-3" style="background: rgba(239,68,68,0.1); border-radius: 8px;">
                        <strong>ID: ${r.post_id}</strong> - <span class="text-danger">${r.error}</span>
                    </div>
                `;
            }
        });
        html += '</div>';
        
        body.innerHTML = html;
    }
    
    async function exportSql() {
        if (selectedPosts.length === 0) return;
        
        alert('Функция экспорта в разработке. Используйте пакетное применение.');
    }
    
    function closeModal() {
        document.getElementById('previewModal').classList.remove('visible');
        currentPreviewData = null;
    }
    
    function showNotification(message, type) {
        const div = document.createElement('div');
        div.className = `alert alert-${type === 'success' ? 'success' : 'danger'} position-fixed`;
        div.style.cssText = 'top: 20px; right: 20px; z-index: 10000; min-width: 300px;';
        div.textContent = message;
        document.body.appendChild(div);
        setTimeout(() => div.remove(), 3000);
    }
    
    // Close modal on escape
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') closeModal();
    });
    
    // Close modal on backdrop click
    document.getElementById('previewModal').addEventListener('click', e => {
        if (e.target.id === 'previewModal') closeModal();
    });
</script>
@endpush
