@extends('layouts.admin')

@section('title', 'Создать тег')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <h1 class="h3 mb-4">🏷️ Создать новый тег</h1>

            @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.tags.store') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="name" class="form-label">Название <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control @error('name') is-invalid @enderror" 
                                   id="name" 
                                   name="name" 
                                   value="{{ old('name') }}" 
                                   required 
                                   autofocus>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">Основное название тега (например: "Новости", "Музыка", "Кино")</small>
                        </div>

                        <div class="mb-3">
                            <label for="slug" class="form-label">Slug (URL)</label>
                            <input type="text" 
                                   class="form-control @error('slug') is-invalid @enderror" 
                                   id="slug" 
                                   name="slug" 
                                   value="{{ old('slug') }}">
                            @error('slug')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">Оставьте пустым для автоматической генерации. Используется в URL: /tag/slug</small>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Описание</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" 
                                      name="description" 
                                      rows="4">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">Краткое описание тега (опционально)</small>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('admin.tags.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Назад
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Создать тег
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Автогенерация slug из названия
document.getElementById('name').addEventListener('input', function() {
    const slugInput = document.getElementById('slug');
    if (slugInput.value === '') {
        const name = this.value;
        const slug = name
            .toLowerCase()
            .replace(/[а-я]/g, function(char) {
                const map = {
                    'а':'a','б':'b','в':'v','г':'g','д':'d','е':'e','ё':'e','ж':'zh',
                    'з':'z','и':'i','й':'y','к':'k','л':'l','м':'m','н':'n','о':'o',
                    'п':'p','р':'r','с':'s','т':'t','у':'u','ф':'f','х':'h','ц':'ts',
                    'ч':'ch','ш':'sh','щ':'sch','ъ':'','ы':'y','ь':'','э':'e','ю':'yu','я':'ya'
                };
                return map[char] || char;
            })
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/(^-|-$)/g, '');
        slugInput.value = slug;
    }
});
</script>
@endsection
