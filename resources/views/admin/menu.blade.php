@extends('layouts.admin')
@section('title', 'Управление меню')
@section('content')
<div class="container-fluid">
    <h1 class="mb-4"><i class="fas fa-bars"></i> Управление меню сайта</h1>
    
    <div class="row">
        <!-- Форма добавления -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-plus"></i> Добавить пункт меню</h5>
                </div>
                <div class="card-body">
                    <!-- Отображение ошибок -->
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <strong>Ошибки валидации:</strong>
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    
                    <form action="{{ route('admin.menu.create') }}" method="POST">
                        @csrf
                        
                        <div class="mb-3">
                            <label for="title" class="form-label">Название</label>
                            <input type="text" 
                                   class="form-control @error('title') is-invalid @enderror" 
                                   id="title" 
                                   name="title" 
                                   value="{{ old('title') }}" 
                                   required>
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-3">
                            <label for="type" class="form-label">Тип</label>
                            <select class="form-select @error('type') is-invalid @enderror" 
                                    id="type" 
                                    name="type" 
                                    required 
                                    onchange="toggleMenuFields()">
                                <option value="category">Категория статей</option>
                                <option value="page">Страница WordPress</option>
                                <option value="url">Прямая ссылка</option>
                            </select>
                            @error('type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-3" id="category_field">
                            <label for="category_id" class="form-label">Категория</label>
                            <select class="form-select" id="category_id" name="category_id">
                                <option value="">Выберите категорию</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->term_taxonomy_id }}">{{ $category->term->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="mb-3" id="page_field" style="display: none;">
                            <label for="page_id" class="form-label">Страница</label>
                            <select class="form-select" id="page_id" name="page_id">
                                <option value="">Выберите страницу</option>
                                @foreach($pages as $page)
                                    <option value="{{ $page->ID }}">{{ $page->post_title }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="mb-3" id="url_field" style="display: none;">
                            <label for="slug" class="form-label">URL</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="slug" 
                                   name="slug" 
                                   placeholder="https://example.com">
                        </div>
                        
                        <div class="mb-3">
                            <label for="order" class="form-label">Порядок</label>
                            <input type="number" 
                                   class="form-control" 
                                   id="order" 
                                   name="order" 
                                   value="0"
                                   required>
                            <small class="text-muted">Чем меньше число, тем раньше отображается</small>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" 
                                   class="form-check-input" 
                                   id="is_active" 
                                   name="is_active" 
                                   checked>
                            <label class="form-check-label" for="is_active">
                                Активен
                            </label>
                        </div>
                        
                        <button type="submit" class="btn btn-success w-100">
                            <i class="fas fa-plus"></i> Добавить
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Список меню -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-list"></i> Текущее меню</h5>
                </div>
                <div class="card-body">
                    @if($menuItems->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Порядок</th>
                                        <th>Название</th>
                                        <th>Тип</th>
                                        <th>URL</th>
                                        <th>Статус</th>
                                        <th>Действия</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($menuItems as $item)
                                    <tr>
                                        <td>
                                            <span class="badge bg-secondary">{{ $item->order }}</span>
                                        </td>
                                        <td><strong>{{ $item->title }}</strong></td>
                                        <td>
                                            @if($item->type == 'category')
                                                <span class="badge bg-primary">Категория</span>
                                            @elseif($item->type == 'page')
                                                <span class="badge bg-info">Страница</span>
                                            @else
                                                <span class="badge bg-warning">Ссылка</span>
                                            @endif
                                        </td>
                                        <td>
                                            <code>{{ $item->url }}</code>
                                        </td>
                                        <td>
                                            @if($item->is_active)
                                                <span class="badge bg-success">Активен</span>
                                            @else
                                                <span class="badge bg-secondary">Неактивен</span>
                                            @endif
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-primary" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editMenuModal{{ $item->id }}">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <a href="{{ route('admin.menu.delete', $item->id) }}" 
                                               class="btn btn-sm btn-danger" 
                                               onclick="return confirm('Удалить пункт меню?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    
                                    <!-- Модальное окно редактирования -->
                                    <div class="modal fade" id="editMenuModal{{ $item->id }}" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form action="{{ route('admin.menu.update', $item->id) }}" method="POST">
                                                    @csrf
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Редактирование: {{ $item->title }}</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <!-- Отображение ошибок -->
                                                        @if ($errors->any())
                                                            <div class="alert alert-danger">
                                                                <ul class="mb-0">
                                                                    @foreach ($errors->all() as $error)
                                                                        <li>{{ $error }}</li>
                                                                    @endforeach
                                                                </ul>
                                                            </div>
                                                        @endif
                                                        
                                                        <div class="mb-3">
                                                            <label class="form-label">Название</label>
                                                            <input type="text" class="form-control" name="title" value="{{ $item->title }}" required>
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <label class="form-label">Тип</label>
                                                            <select class="form-select" name="type" id="edit_type_{{ $item->id }}" required onchange="toggleEditMenuFields({{ $item->id }})">
                                                                <option value="category" {{ $item->type == 'category' ? 'selected' : '' }}>Категория</option>
                                                                <option value="page" {{ $item->type == 'page' ? 'selected' : '' }}>Страница</option>
                                                                <option value="url" {{ $item->type == 'url' ? 'selected' : '' }}>Ссылка</option>
                                                            </select>
                                                        </div>
                                                        
                                                        <div class="mb-3" id="edit_category_field_{{ $item->id }}" style="display: {{ $item->type == 'category' ? 'block' : 'none' }};">
                                                                <label class="form-label">Категория</label>
                                                                <select class="form-select" name="category_id">
                                                                <option value="">Выберите категорию</option>
                                                                    @foreach($categories as $category)
                                                                        <option value="{{ $category->term_taxonomy_id }}" {{ $item->category_id == $category->term_taxonomy_id ? 'selected' : '' }}>
                                                                            {{ $category->term->name }}
                                                                        </option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                        
                                                        <div class="mb-3" id="edit_page_field_{{ $item->id }}" style="display: {{ $item->type == 'page' ? 'block' : 'none' }};">
                                                                <label class="form-label">Страница</label>
                                                                <select class="form-select" name="page_id">
                                                                <option value="">Выберите страницу</option>
                                                                    @foreach($pages as $page)
                                                                        <option value="{{ $page->ID }}" {{ $item->page_id == $page->ID ? 'selected' : '' }}>
                                                                            {{ $page->post_title }}
                                                                        </option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                        
                                                        <div class="mb-3" id="edit_url_field_{{ $item->id }}" style="display: {{ $item->type == 'url' ? 'block' : 'none' }};">
                                                                <label class="form-label">URL</label>
                                                            <input type="text" class="form-control" name="slug" value="{{ $item->slug }}" placeholder="https://example.com">
                                                            </div>
                                                        
                                                        <div class="mb-3">
                                                            <label class="form-label">Порядок</label>
                                                            <input type="number" class="form-control" name="order" value="{{ $item->order }}" required>
                                                            <small class="text-muted">Чем меньше число, тем раньше отображается</small>
                                                        </div>
                                                        
                                                        <div class="form-check">
                                                            <input type="checkbox" class="form-check-input" id="edit_is_active_{{ $item->id }}" name="is_active" {{ $item->is_active ? 'checked' : '' }}>
                                                            <label class="form-check-label" for="edit_is_active_{{ $item->id }}">Активен</label>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                                                        <button type="submit" class="btn btn-primary">
                                                            <i class="fas fa-save"></i> Сохранить
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> Пунктов меню пока нет. Добавьте первый пункт слева.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleMenuFields() {
    const type = document.getElementById('type').value;
    const categoryField = document.getElementById('category_field');
    const pageField = document.getElementById('page_field');
    const urlField = document.getElementById('url_field');
    
    categoryField.style.display = 'none';
    pageField.style.display = 'none';
    urlField.style.display = 'none';
    
    if (type === 'category') {
        categoryField.style.display = 'block';
    } else if (type === 'page') {
        pageField.style.display = 'block';
    } else if (type === 'url') {
        urlField.style.display = 'block';
    }
}

function toggleEditMenuFields(itemId) {
    const type = document.getElementById('edit_type_' + itemId).value;
    const categoryField = document.getElementById('edit_category_field_' + itemId);
    const pageField = document.getElementById('edit_page_field_' + itemId);
    const urlField = document.getElementById('edit_url_field_' + itemId);
    
    categoryField.style.display = 'none';
    pageField.style.display = 'none';
    urlField.style.display = 'none';
    
    if (type === 'category') {
        categoryField.style.display = 'block';
    } else if (type === 'page') {
        pageField.style.display = 'block';
    } else if (type === 'url') {
        urlField.style.display = 'block';
    }
}

// Вызываем при загрузке страницы
document.addEventListener('DOMContentLoaded', function() {
    toggleMenuFields();
});
</script>
@endsection

