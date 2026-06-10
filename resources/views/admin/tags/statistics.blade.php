@extends('layouts.admin')

@section('title', 'Статистика тегов')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">📊 Статистика тегов</h1>
                <a href="{{ route('admin.tags.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Назад к списку
                </a>
            </div>

            <!-- Общая статистика -->
            <div class="row mb-4">
                <div class="col-md-2">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="text-primary">Всего</h5>
                            <h2>{{ $stats['total'] }}</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="text-success">Активные</h5>
                            <h2>{{ $stats['active'] }}</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="text-warning">Одиночные</h5>
                            <h2>{{ $stats['single'] }}</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="text-danger">Неиспольз.</h5>
                            <h2>{{ $stats['unused'] }}</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="text-info">Самый популярный</h5>
                            <h2>{{ $stats['top_count'] }} статей</h2>
                            @if($topTags->isNotEmpty())
                                <small>{{ $topTags->first()->term->name }}</small>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Топ-20 популярных тегов -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">🏆 Топ-20 популярных тегов</h5>
                </div>
                <div class="card-body">
                    @if($topTags->isEmpty())
                        <p class="text-muted">Нет активных тегов</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th width="50">#</th>
                                        <th>Название</th>
                                        <th>Slug</th>
                                        <th width="150">Кол-во статей</th>
                                        <th width="150">Действия</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($topTags as $index => $tag)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>
                                                <strong>{{ $tag->term->name ?? 'N/A' }}</strong>
                                            </td>
                                            <td><code>{{ $tag->term->slug ?? 'N/A' }}</code></td>
                                            <td>
                                                <div class="progress" style="height: 25px;">
                                                    <div class="progress-bar bg-success" 
                                                         role="progressbar" 
                                                         style="width: {{ ($tag->count / $stats['top_count']) * 100 }}%"
                                                         aria-valuenow="{{ $tag->count }}" 
                                                         aria-valuemin="0" 
                                                         aria-valuemax="{{ $stats['top_count'] }}">
                                                        {{ $tag->count }}
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <a href="{{ route('admin.tags.edit', $tag->term_taxonomy_id) }}" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="{{ route('tag', $tag->term->slug) }}" class="btn btn-sm btn-outline-info" target="_blank">
                                                    <i class="fas fa-external-link-alt"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Неиспользуемые теги -->
            @if($unusedTags->isNotEmpty())
                <div class="card mb-4">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0">⚠️ Неиспользуемые теги ({{ count($unusedTags) }})</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Эти теги не используются ни в одной статье и могут быть удалены.</p>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Название</th>
                                        <th>Slug</th>
                                        <th>Действия</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($unusedTags->take(50) as $tag)
                                        <tr>
                                            <td>{{ $tag->term_taxonomy_id }}</td>
                                            <td>{{ $tag->term->name ?? 'N/A' }}</td>
                                            <td><code>{{ $tag->term->slug ?? 'N/A' }}</code></td>
                                            <td>
                                                <a href="{{ route('admin.tags.edit', $tag->term_taxonomy_id) }}" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button class="btn btn-sm btn-outline-danger" onclick="deleteUnusedTag({{ $tag->term_taxonomy_id }}, '{{ $tag->term->name }}')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if(count($unusedTags) > 50)
                            <p class="text-muted mt-2">Показано первые 50 из {{ count($unusedTags) }} неиспользуемых тегов</p>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Теги с одной статьей -->
            @if($singleUseTags->isNotEmpty())
                <div class="card mb-4">
                    <div class="card-header bg-warning">
                        <h5 class="mb-0">⚡ Теги с одной статьей ({{ count($singleUseTags) }})</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Эти теги используются только в одной статье. Возможно, их стоит объединить с другими тегами.</p>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Название</th>
                                        <th>Slug</th>
                                        <th>Действия</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($singleUseTags->take(50) as $tag)
                                        <tr>
                                            <td>{{ $tag->term_taxonomy_id }}</td>
                                            <td>{{ $tag->term->name ?? 'N/A' }}</td>
                                            <td><code>{{ $tag->term->slug ?? 'N/A' }}</code></td>
                                            <td>
                                                <a href="{{ route('admin.tags.edit', $tag->term_taxonomy_id) }}" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="{{ route('tag', $tag->term->slug) }}" class="btn btn-sm btn-outline-info" target="_blank">
                                                    <i class="fas fa-external-link-alt"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if(count($singleUseTags) > 50)
                            <p class="text-muted mt-2">Показано первые 50 из {{ count($singleUseTags) }} тегов с одной статьей</p>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<form id="deleteTagForm" method="POST" style="display:none;">
    @csrf
    @method('DELETE')
</form>

<script>
function deleteUnusedTag(id, name) {
    if (confirm(`Вы уверены, что хотите удалить неиспользуемый тег "${name}"?`)) {
        const form = document.getElementById('deleteTagForm');
        form.action = `/notaadmin/tags/${id}`;
        form.submit();
    }
}
</script>
@endsection
