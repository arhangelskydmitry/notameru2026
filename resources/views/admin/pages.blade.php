@extends('layouts.admin')
@section('title', 'Управление страницами')
@section('content')
<div class="container-fluid">
    <h1 class="mb-4"><i class="fas fa-file-alt"></i> Управление страницами</h1>
    
    <!-- Статистика -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <h6 class="card-title text-muted">Всего страниц</h6>
                    <p class="card-text fs-4">{{ $stats['total'] }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <h6 class="card-title text-muted">Опубликовано</h6>
                    <p class="card-text fs-4 text-success">{{ $stats['published'] }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <h6 class="card-title text-muted">Черновики</h6>
                    <p class="card-text fs-4 text-warning">{{ $stats['draft'] }}</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Фильтры и поиск -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-filter"></i> Поиск и фильтры</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <input type="text" id="searchInput" class="form-control" placeholder="Поиск по названию...">
                </div>
                <div class="col-md-6 mb-3">
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-outline-dark filter-btn active" data-filter="all">Все ({{ $stats['total'] }})</button>
                        <button type="button" class="btn btn-outline-success filter-btn" data-filter="publish">Опубликованные ({{ $stats['published'] }})</button>
                        <button type="button" class="btn btn-outline-warning filter-btn" data-filter="draft">Черновики ({{ $stats['draft'] }})</button>
                    </div>
                </div>
            </div>
            <small class="text-muted">Найдено: <span id="foundCount">{{ count($pages) }}</span> из {{ count($pages) }}</small>
        </div>
    </div>
    
    <!-- Список страниц -->
    <div class="card">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="fas fa-list"></i> Список страниц</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Название</th>
                            <th>Дата публикации</th>
                            <th>Статус</th>
                            <th>Просмотры</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody id="pagesList">
                        @forelse($pages as $page)
                        <tr class="page-item" 
                            data-status="{{ $page->post_status }}"
                            data-title="{{ $page->post_title }}">
                            <td>{{ $page->ID }}</td>
                            <td>
                                <a href="{{ route('admin.pages.edit', $page->ID) }}" class="text-dark text-decoration-none">
                                    {{ Str::limit($page->post_title, 60) }}
                                </a>
                            </td>
                            <td>{{ $page->post_date ? \Carbon\Carbon::parse($page->post_date)->format('d.m.Y H:i') : '-' }}</td>
                            <td>
                                @if($page->post_status == 'publish')
                                    <span class="badge bg-success">Опубликовано</span>
                                @elseif($page->post_status == 'draft')
                                    <span class="badge bg-warning">Черновик</span>
                                @else
                                    <span class="badge bg-secondary">{{ $page->post_status }}</span>
                                @endif
                            </td>
                            <td>{{ $page->getMeta('views', 0) }}</td>
                            <td>
                                @if($page->post_name)
                                <a href="{{ route('post', $page->post_name) }}" class="btn btn-sm btn-outline-primary" target="_blank" title="Просмотр">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @else
                                <button class="btn btn-sm btn-outline-secondary" disabled title="URL не задан">
                                    <i class="fas fa-eye-slash"></i>
                                </button>
                                @endif
                                <a href="{{ route('admin.pages.edit', $page->ID) }}" class="btn btn-sm btn-primary" title="Редактировать">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button class="btn btn-sm btn-danger" onclick="deletePage({{ $page->ID }}, '{{ addslashes($page->post_title) }}')" title="Удалить">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center">Страниц пока нет</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <!-- Пагинация -->
            <div class="d-flex justify-content-center mt-4">
                {{ $pages->links() }}
            </div>
        </div>
    </div>
</div>

<script>
function deletePage(id, title) {
    if (!confirm('Вы уверены, что хотите удалить страницу "' + title + '"?')) {
        return false;
    }
    window.location.href = '/notaadmin/pages/' + id + '/delete';
}

// Фильтрация и поиск
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const pageItems = document.querySelectorAll('.page-item');
    const foundCount = document.getElementById('foundCount');
    const filterBtns = document.querySelectorAll('.filter-btn');
    let currentFilter = 'all';

    function filterPages() {
        const searchTerm = searchInput.value.toLowerCase().trim();
        let visibleCount = 0;

        pageItems.forEach(function(item) {
            const title = (item.dataset.title || '').toLowerCase();
            const status = item.dataset.status || '';
            
            const matchesSearch = searchTerm === '' || title.includes(searchTerm);
            const matchesFilter = currentFilter === 'all' || status === currentFilter;
            
            if (matchesSearch && matchesFilter) {
                item.style.display = '';
                visibleCount++;
            } else {
                item.style.display = 'none';
            }
        });

        foundCount.textContent = visibleCount;
    }

    if (searchInput) {
        searchInput.addEventListener('input', filterPages);
    }

    filterBtns.forEach(function(btn) {
        btn.addEventListener('click', function() {
            filterBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            currentFilter = this.dataset.filter;
            filterPages();
        });
    });
});
</script>

<style>
.page-item {
    transition: background-color 0.2s;
}

.page-item:hover {
    background-color: #f8f9fa;
}

.filter-btn.active {
    background-color: #0d6efd;
    color: white;
    border-color: #0d6efd;
}
</style>
@endsection

