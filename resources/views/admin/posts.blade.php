@extends('layouts.admin')
@section('title', 'Управление статьями')
@section('content')
<div class="container-fluid">
    <h1 class="mb-4"><i class="fas fa-newspaper"></i> Управление статьями</h1>
    
    @if(session('info'))
        <div class="alert alert-info alert-dismissible fade show">
            <i class="fas fa-info-circle"></i> {{ session('info') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    
    <!-- Статистика -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h6 class="card-title text-muted">Всего статей</h6>
                    <p class="card-text fs-4">{{ $stats['total'] }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h6 class="card-title text-muted">Опубликовано</h6>
                    <p class="card-text fs-4 text-success">{{ $stats['published'] }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h6 class="card-title text-muted">Черновики</h6>
                    <p class="card-text fs-4 text-warning">{{ $stats['draft'] }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h6 class="card-title text-muted">Отложено</h6>
                    <p class="card-text fs-4 text-info">{{ $stats['future'] }}</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Действия -->
    <div class="mb-4">
        <a href="{{ route('admin.posts.create') }}" class="btn btn-success">
            <i class="fas fa-plus"></i> Создать новую статью
        </a>
        <a href="{{ route('home') }}" target="_blank" class="btn btn-outline-primary">
            <i class="fas fa-external-link-alt"></i> Посмотреть на сайте
        </a>
    </div>
    
    @if(admin_user() && admin_user()->isSuperAdmin())
        @if(session('views_boost_report'))
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-bolt"></i> Просмотры обновлены для {{ count(session('views_boost_report')) }} статей.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-chart-line"></i> Отчёт по накрутке просмотров</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Статья</th>
                                    <th>Было</th>
                                    <th>Добавлено</th>
                                    <th>Стало</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach(session('views_boost_report') as $item)
                                    <tr>
                                        <td>{{ $item['id'] }}</td>
                                        <td>{{ $item['title'] }}</td>
                                        <td>{{ number_format($item['previous']) }}</td>
                                        <td class="text-success">+{{ number_format($item['added']) }}</td>
                                        <td><strong>{{ number_format($item['current']) }}</strong></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
        
        <div class="card mb-4">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-fire"></i> Сервис накрутки просмотров</h5>
                <span class="badge bg-warning text-dark">Только для суперадмина</span>
            </div>
            <div class="card-body">
                <form class="row g-3 align-items-end" method="POST" action="{{ route('admin.posts.boost-views') }}">
                    @csrf
                    <div class="col-md-3">
                        <label class="form-label">Порог текущих просмотров</label>
                        <input type="number" name="max_current_views" class="form-control" value="300" min="0" required>
                        <small class="text-muted">Статьи с просмотрами ниже этого порога будут выбраны</small>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Мин. добавление</label>
                        <input type="number" name="min_increment" class="form-control" value="2000" min="1" required>
                        <small class="text-muted">Минимум случайного прироста</small>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Макс. добавление</label>
                        <input type="number" name="max_increment" class="form-control" value="4000" min="1" required>
                        <small class="text-muted">Максимум случайного прироста</small>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Лимит статей</label>
                        <input type="number" name="limit" class="form-control" value="20" min="1" max="200" required>
                    </div>
                    <div class="col-md-1 text-end">
                        <button type="submit" class="btn btn-danger w-100">
                            <i class="fas fa-rocket"></i><br>
                            Запуск
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
    
    <!-- Фильтры и поиск -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-filter"></i> Поиск и фильтры</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-{{ admin_user() && admin_user()->isAuthor() ? '6' : '4' }} mb-3">
                    <input type="text" 
                           id="searchInput" 
                           class="form-control" 
                           placeholder="Начните вводить название..."
                           value="{{ request('search') }}">
                </div>
                @if(!admin_user() || !admin_user()->isAuthor())
                    <div class="col-md-4 mb-3">
                        <select id="authorFilter" class="form-select">
                            <option value="">Все авторы</option>
                            @foreach($authors as $author)
                                <option value="{{ $author->ID }}" {{ request('author') == $author->ID ? 'selected' : '' }}>
                                    {{ $author->display_name }} ({{ $author->posts_count }} статей)
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="col-md-{{ admin_user() && admin_user()->isAuthor() ? '6' : '4' }} mb-3">
                    <select id="statusFilter" class="form-select">
                        <option value="">Все статусы</option>
                        <option value="publish" {{ request('status') == 'publish' ? 'selected' : '' }}>Опубликованные</option>
                        <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Черновики</option>
                        <option value="future" {{ request('status') == 'future' ? 'selected' : '' }}>Отложенные</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Ожидают модерации</option>
                    </select>
                </div>
            </div>
            @if(request('author') || request('status') || request('search'))
                <div class="row">
                    <div class="col-md-12">
                        <button id="resetFilters" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-times"></i> Сбросить фильтры
                        </button>
                    </div>
                </div>
            @endif
            <small class="text-muted mt-2 d-block">Найдено: <span id="foundCount">{{ $posts->total() }}</span> статей</small>
        </div>
    </div>
    
    <!-- Список статей -->
    <div class="card">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="fas fa-list"></i> Список статей</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Название</th>
                            @if(!admin_user() || !admin_user()->isAuthor())
                                <th>Автор</th>
                            @endif
                            <th>Дата публикации</th>
                            <th>Статус</th>
                            <th>Просмотры</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody id="postsList">
                        @include('admin.partials.posts-list', ['posts' => $posts->items()])
                    </tbody>
                </table>
            </div>
            
            <!-- Индикатор загрузки -->
            <div id="loadingIndicator" class="text-center py-4" style="display: none;">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Загрузка...</span>
                </div>
                <p class="mt-2 text-muted">Загрузка статей...</p>
            </div>
            
            <!-- Конец списка -->
            <div id="endOfList" class="text-center py-4" style="display: none;">
                <i class="fas fa-check-circle text-success fa-2x"></i>
                <p class="mt-2 text-muted">Все статьи загружены</p>
            </div>
        </div>
    </div>
</div>

<script>
let currentPage = 1;
let loading = false;
let hasMorePages = {{ $posts->hasMorePages() ? 'true' : 'false' }};
let searchTimeout = null;

// Живой поиск
document.getElementById('searchInput').addEventListener('input', function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(function() {
        resetAndSearch();
    }, 500); // Задержка 500ms после ввода
});

// Фильтры
@if(!admin_user() || !admin_user()->isAuthor())
document.getElementById('authorFilter').addEventListener('change', function() {
    resetAndSearch();
});
@endif

document.getElementById('statusFilter').addEventListener('change', function() {
    resetAndSearch();
});

// Сброс фильтров
document.getElementById('resetFilters')?.addEventListener('click', function() {
    document.getElementById('searchInput').value = '';
    @if(!admin_user() || !admin_user()->isAuthor())
    document.getElementById('authorFilter').value = '';
    @endif
    document.getElementById('statusFilter').value = '';
    resetAndSearch();
});

function resetAndSearch() {
    currentPage = 1;
    hasMorePages = true;
    document.getElementById('postsList').innerHTML = '';
    loadPosts();
}

function loadPosts() {
    if (loading || !hasMorePages) return;
    
    loading = true;
    document.getElementById('loadingIndicator').style.display = 'block';
    document.getElementById('endOfList').style.display = 'none';
    
    const params = new URLSearchParams({
        page: currentPage,
        search: document.getElementById('searchInput').value,
        @if(!admin_user() || !admin_user()->isAuthor())
        author: document.getElementById('authorFilter').value,
        @endif
        status: document.getElementById('statusFilter').value
    });
    
    fetch('{{ route("admin.posts") }}?' + params.toString(), {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('postsList').insertAdjacentHTML('beforeend', data.html);
        document.getElementById('foundCount').textContent = document.querySelectorAll('.post-item').length;
        
        hasMorePages = data.has_more;
        currentPage = data.next_page;
        loading = false;
        
        document.getElementById('loadingIndicator').style.display = 'none';
        
        if (!hasMorePages) {
            document.getElementById('endOfList').style.display = 'block';
        }
    })
    .catch(error => {
        console.error('Error loading posts:', error);
        loading = false;
        document.getElementById('loadingIndicator').style.display = 'none';
    });
}

// Бесконечная подгрузка при скролле
let scrollTimeout = null;
window.addEventListener('scroll', function() {
    if (scrollTimeout) return;
    
    scrollTimeout = setTimeout(function() {
        scrollTimeout = null;
        
        const scrollPosition = window.innerHeight + window.scrollY;
        const threshold = document.body.offsetHeight - 500;
        
        if (scrollPosition >= threshold) {
            loadPosts();
        }
    }, 100);
});

function deletePost(id, title) {
    if (!confirm('Вы уверены, что хотите удалить статью "' + title + '"?')) {
        return false;
    }
    
    // Создаем форму для отправки DELETE-запроса
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/notaadmin/posts/' + id;
    
    // CSRF токен
    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = '{{ csrf_token() }}';
    form.appendChild(csrfInput);
    
    // Метод DELETE
    const methodInput = document.createElement('input');
    methodInput.type = 'hidden';
    methodInput.name = '_method';
    methodInput.value = 'DELETE';
    form.appendChild(methodInput);
    
    // Добавляем форму в body и отправляем
    document.body.appendChild(form);
    form.submit();
}

// Фильтрация и поиск
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const postItems = document.querySelectorAll('.post-item');
    const foundCount = document.getElementById('foundCount');
    const filterBtns = document.querySelectorAll('.filter-btn');
    let currentFilter = 'all';

    function filterPosts() {
        const searchTerm = searchInput.value.toLowerCase().trim();
        let visibleCount = 0;

        postItems.forEach(function(item) {
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
        searchInput.addEventListener('input', filterPosts);
    }

    filterBtns.forEach(function(btn) {
        btn.addEventListener('click', function() {
            filterBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            currentFilter = this.dataset.filter;
            filterPosts();
        });
    });
});
</script>

<style>
.post-item {
    transition: background-color 0.2s;
}

.post-item:hover {
    background-color: #f8f9fa;
}

.filter-btn.active {
    background-color: #0d6efd;
    color: white;
    border-color: #0d6efd;
}
</style>
@endsection

