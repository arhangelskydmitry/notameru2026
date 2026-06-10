@extends('layouts.admin')

@section('title', 'Управление тегами')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1 class="h3 mb-3">🏷️ Управление тегами</h1>
            
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- Статистика -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title text-primary">Всего тегов</h5>
                            <h2 class="mb-0">{{ $stats['total'] }}</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title text-success">Активные</h5>
                            <h2 class="mb-0">{{ $stats['active'] }}</h2>
                            <small class="text-muted">С постами</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title text-warning">Одиночные</h5>
                            <h2 class="mb-0">{{ $stats['single'] }}</h2>
                            <small class="text-muted">1 пост</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title text-danger">Неиспользуемые</h5>
                            <h2 class="mb-0">{{ $stats['unused'] }}</h2>
                            <small class="text-muted">Без постов</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Действия -->
            <div class="d-flex justify-content-between mb-3">
                <div>
                    <a href="{{ route('admin.tags.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Создать тег
                    </a>
                    <a href="{{ route('admin.tags.suggest') }}" class="btn btn-success">
                        <i class="fas fa-magic"></i> Предложить теги
                    </a>
                    <a href="{{ route('admin.tags.mass-auto-tagging') }}" class="btn btn-warning">
                        <i class="fas fa-robot"></i> Автотегирование
                    </a>
                    <a href="{{ route('admin.tags.merge-index') }}" class="btn btn-danger">
                        <i class="fas fa-code-branch"></i> Умное слияние
                    </a>
                    <a href="{{ route('admin.tags.statistics') }}" class="btn btn-info">
                        <i class="fas fa-chart-bar"></i> Статистика
                    </a>
                    <a href="{{ route('admin.tags.merge') }}" class="btn btn-secondary">
                        <i class="fas fa-object-group"></i> Объединить вручную
                    </a>
                </div>
                <div>
                    <button type="button" class="btn btn-danger" id="bulkDeleteBtn" style="display:none;">
                        <i class="fas fa-trash"></i> Удалить выбранные (<span id="selectedCount">0</span>)
                    </button>
                </div>
            </div>

            <!-- Поиск и фильтры -->
            <div class="card mb-3">
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.tags.index') }}" class="row g-3">
                        <div class="col-md-4">
                            <input type="text" name="search" class="form-control" placeholder="Поиск по названию или slug..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-3">
                            <select name="filter" class="form-select">
                                <option value="">Все теги</option>
                                <option value="active" {{ request('filter') === 'active' ? 'selected' : '' }}>Активные (с постами)</option>
                                <option value="unused" {{ request('filter') === 'unused' ? 'selected' : '' }}>Неиспользуемые</option>
                                <option value="single" {{ request('filter') === 'single' ? 'selected' : '' }}>Одиночные (1 пост)</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="sort" class="form-select">
                                <option value="name" {{ request('sort') === 'name' ? 'selected' : '' }}>По названию</option>
                                <option value="count" {{ request('sort') === 'count' ? 'selected' : '' }}>По кол-ву статей</option>
                                <option value="id" {{ request('sort') === 'id' ? 'selected' : '' }}>По ID</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="direction" class="form-select">
                                <option value="asc" {{ request('direction') === 'asc' ? 'selected' : '' }}>По возрастанию</option>
                                <option value="desc" {{ request('direction') === 'desc' ? 'selected' : '' }}>По убыванию</option>
                            </select>
                        </div>
                        <div class="col-md-1">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Список тегов -->
            <div class="card">
                <div class="card-body">
                    @if($tags->isEmpty())
                        <p class="text-center text-muted">Теги не найдены</p>
                    @else
                        <form id="bulkDeleteForm" method="POST" action="{{ route('admin.tags.bulk-delete') }}">
                            @csrf
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th width="30">
                                                <input type="checkbox" id="selectAll" class="form-check-input">
                                            </th>
                                            <th>ID</th>
                                            <th>Название</th>
                                            <th>Slug</th>
                                            <th>Описание</th>
                                            <th>Кол-во статей</th>
                                            <th>Действия</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($tags as $tag)
                                            <tr>
                                                <td>
                                                    <input type="checkbox" name="tag_ids[]" value="{{ $tag->term_taxonomy_id }}" class="form-check-input tag-checkbox">
                                                </td>
                                                <td>{{ $tag->term_taxonomy_id }}</td>
                                                <td>
                                                    <strong>{{ $tag->term->name ?? 'N/A' }}</strong>
                                                    @if($tag->count == 0)
                                                        <span class="badge bg-danger">Неиспользуемый</span>
                                                    @elseif($tag->count == 1)
                                                        <span class="badge bg-warning">1 пост</span>
                                                    @endif
                                                </td>
                                                <td><code>{{ $tag->term->slug ?? 'N/A' }}</code></td>
                                                <td>
                                                    @if($tag->description)
                                                        {{ Str::limit($tag->description, 50) }}
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="badge {{ $tag->count > 0 ? 'bg-success' : 'bg-secondary' }}">
                                                        {{ $tag->count }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="{{ route('admin.tags.edit', $tag->term_taxonomy_id) }}" class="btn btn-sm btn-outline-primary" title="Редактировать">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        @if($tag->count > 0)
                                                            <a href="{{ route('tag', $tag->term->slug) }}" class="btn btn-sm btn-outline-info" target="_blank" title="Просмотр на сайте">
                                                                <i class="fas fa-external-link-alt"></i>
                                                            </a>
                                                        @endif
                                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                                                onclick="deleteTag({{ $tag->term_taxonomy_id }}, '{{ $tag->term->name ?? 'N/A' }}', {{ $tag->count }})" 
                                                                title="Удалить">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </form>

                        <!-- Пагинация -->
                        <div class="mt-3">
                            {{ $tags->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Форма для удаления отдельного тега -->
<form id="deleteTagForm" method="POST" style="display:none;">
    @csrf
    @method('DELETE')
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Выбор всех чекбоксов
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.tag-checkbox');
    const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
    const selectedCount = document.getElementById('selectedCount');

    selectAll.addEventListener('change', function() {
        checkboxes.forEach(cb => cb.checked = this.checked);
        updateBulkDeleteButton();
    });

    checkboxes.forEach(cb => {
        cb.addEventListener('change', updateBulkDeleteButton);
    });

    function updateBulkDeleteButton() {
        const selected = document.querySelectorAll('.tag-checkbox:checked').length;
        selectedCount.textContent = selected;
        bulkDeleteBtn.style.display = selected > 0 ? 'inline-block' : 'none';
    }

    // Массовое удаление
    bulkDeleteBtn.addEventListener('click', function() {
        const selected = document.querySelectorAll('.tag-checkbox:checked').length;
        if (selected === 0) return;

        if (confirm(`Вы уверены, что хотите удалить ${selected} тег(ов)? Это действие необратимо!`)) {
            document.getElementById('bulkDeleteForm').submit();
        }
    });
});

// Удаление отдельного тега
function deleteTag(id, name, count) {
    let message = `Вы уверены, что хотите удалить тег "${name}"?`;
    if (count > 0) {
        message += `\n\nВнимание! Этот тег используется в ${count} статье(ях). Все связи будут удалены.`;
    }

    if (confirm(message)) {
        const form = document.getElementById('deleteTagForm');
        form.action = `/notaadmin/tags/${id}`;
        form.submit();
    }
}
</script>
@endsection
