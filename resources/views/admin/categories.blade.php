@extends('layouts.admin')
@section('title', 'Управление категориями')
@section('content')
<div class="container-fluid">
    <h1 class="mb-4"><i class="fas fa-folder"></i> Управление категориями</h1>
    
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-list"></i> Список категорий</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Название</th>
                                    <th>Slug</th>
                                    <th>Описание</th>
                                    <th>Количество статей</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($categories as $category)
                                <tr>
                                    <td>{{ $category->term_taxonomy_id }}</td>
                                    <td>
                                        <strong>{{ $category->term->name }}</strong>
                                    </td>
                                    <td>
                                        <code>{{ $category->term->slug }}</code>
                                    </td>
                                    <td>{{ Str::limit($category->description, 50) }}</td>
                                    <td>
                                        <span class="badge bg-primary">{{ $category->count }} статей</span>
                                    </td>
                                    <td>
                                        <a href="{{ route('category', $category->term->slug) }}" class="btn btn-sm btn-outline-primary" target="_blank" title="Просмотр">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <button class="btn btn-sm btn-primary" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editCategoryModal{{ $category->term_taxonomy_id }}" 
                                                title="Редактировать">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </td>
                                </tr>
                                
                                <!-- Модальное окно редактирования -->
                                <div class="modal fade" id="editCategoryModal{{ $category->term_taxonomy_id }}" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form action="{{ route('admin.categories.update', $category->term_taxonomy_id) }}" method="POST">
                                                @csrf
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Редактирование: {{ $category->term->name }}</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label for="name{{ $category->term_taxonomy_id }}" class="form-label">Название</label>
                                                        <input type="text" 
                                                               class="form-control" 
                                                               id="name{{ $category->term_taxonomy_id }}" 
                                                               name="name" 
                                                               value="{{ $category->term->name }}" 
                                                               required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="slug{{ $category->term_taxonomy_id }}" class="form-label">Slug</label>
                                                        <input type="text" 
                                                               class="form-control" 
                                                               id="slug{{ $category->term_taxonomy_id }}" 
                                                               name="slug" 
                                                               value="{{ $category->term->slug }}" 
                                                               required>
                                                        <small class="text-muted">URL: /category/{{ $category->term->slug }}</small>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="description{{ $category->term_taxonomy_id }}" class="form-label">Описание</label>
                                                        <textarea class="form-control" 
                                                                  id="description{{ $category->term_taxonomy_id }}" 
                                                                  name="description" 
                                                                  rows="3">{{ $category->description }}</textarea>
                                                    </div>
                                                    <div class="alert alert-info">
                                                        <i class="fas fa-info-circle"></i> В этой категории {{ $category->count }} статей
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
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center">Категорий пока нет</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Информация -->
    <div class="card mt-4">
        <div class="card-header bg-secondary text-white">
            <h5 class="mb-0"><i class="fas fa-info-circle"></i> Информация</h5>
        </div>
        <div class="card-body">
            <p class="mb-0">
                <i class="fas fa-lightbulb text-warning"></i> 
                Здесь вы можете редактировать названия, URL (slug) и описания категорий. 
                Категории используются для организации статей на сайте.
            </p>
        </div>
    </div>
</div>
@endsection

<style>
.table tr:hover {
    background-color: #f8f9fa;
}
</style>




