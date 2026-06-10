@extends('layouts.admin')

@section('title', 'Редактировать тег')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <h1 class="h3 mb-4">🏷️ Редактировать тег</h1>

            @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="card mb-4">
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.tags.update', $taxonomy->term_taxonomy_id) }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="name" class="form-label">Название <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control @error('name') is-invalid @enderror" 
                                   id="name" 
                                   name="name" 
                                   value="{{ old('name', $taxonomy->term->name) }}" 
                                   required 
                                   autofocus>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="slug" class="form-label">Slug (URL) <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control @error('slug') is-invalid @enderror" 
                                   id="slug" 
                                   name="slug" 
                                   value="{{ old('slug', $taxonomy->term->slug) }}" 
                                   required>
                            @error('slug')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">Используется в URL: /tag/{{ $taxonomy->term->slug }}</small>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Описание</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" 
                                      name="description" 
                                      rows="4">{{ old('description', $taxonomy->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('admin.tags.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Назад
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Сохранить изменения
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Статьи с этим тегом -->
            @if($posts->count() > 0)
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">📰 Статьи с этим тегом ({{ $taxonomy->count }})</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Название</th>
                                        <th>Дата</th>
                                        <th>Статус</th>
                                        <th>Действия</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($posts as $post)
                                        <tr>
                                            <td>{{ $post->ID }}</td>
                                            <td>{{ Str::limit($post->post_title, 60) }}</td>
                                            <td>{{ \Carbon\Carbon::parse($post->post_date)->format('d.m.Y') }}</td>
                                            <td>
                                                <span class="badge {{ $post->getAdminStatusBadgeClass() }}">
                                                    {{ $post->getAdminStatusLabel() }}
                                                </span>
                                            </td>
                                            <td>
                                                <a href="{{ route('admin.posts.edit', $post->ID) }}" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                @if($post->isPubliclyAccessible())
                                                    <a href="{{ route('post', $post->post_name) }}" class="btn btn-sm btn-outline-info" target="_blank">
                                                        <i class="fas fa-external-link-alt"></i>
                                                    </a>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if($taxonomy->count > 100)
                            <p class="text-muted mt-2">Показано первые 100 статей из {{ $taxonomy->count }}</p>
                        @endif
                    </div>
                </div>
            @else
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> Этот тег пока не используется ни в одной статье.
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
