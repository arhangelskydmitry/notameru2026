@foreach($posts as $post)
<tr class="post-item" 
    data-status="{{ $post->post_status }}"
    data-title="{{ $post->post_title }}">
    <td>{{ $post->ID }}</td>
    <td>
        <a href="{{ route('admin.posts.edit', $post->ID) }}" class="text-dark text-decoration-none">
            {{ Str::limit($post->post_title, 60) }}
        </a>
    </td>
    @if(!admin_user() || !admin_user()->isAuthor())
    <td>
        @if($post->author)
            <a href="{{ route('admin.posts', ['author' => $post->author->ID]) }}" 
               class="text-decoration-none"
               title="Показать статьи автора">
                {{ $post->author->display_name }}
            </a>
        @else
            <span class="text-muted">-</span>
        @endif
    </td>
    @endif
    <td>{{ $post->post_date ? \Carbon\Carbon::parse($post->post_date)->format('d.m.Y H:i') : '-' }}</td>
    <td>
        @php
            $statusLabel = match ($post->post_status) {
                'publish' => 'Опубликовано',
                'draft' => 'Черновик',
                'pending' => 'Ожидает проверки',
                'future' => 'Отложенная публикация',
                default => (string) $post->post_status,
            };
            $statusBadgeClass = match ($post->post_status) {
                'publish' => 'bg-success',
                'draft' => 'bg-warning',
                'pending' => 'bg-secondary',
                'future' => 'bg-info',
                default => 'bg-secondary',
            };
            $isPubliclyAccessible = filled($post->post_name)
                && $post->post_status === 'publish'
                && $post->post_date
                && \Carbon\Carbon::parse($post->post_date)->lte(now());
        @endphp
        <span class="badge {{ $statusBadgeClass }}">{{ $statusLabel }}</span>
    </td>
    <td>{{ $post->getMeta('post_views_count', 0) }}</td>
    <td>
        @if($isPubliclyAccessible)
        <a href="{{ route('post', $post->post_name) }}" class="btn btn-sm btn-outline-primary" target="_blank" title="Просмотр">
            <i class="fas fa-eye"></i>
        </a>
        @else
            <button class="btn btn-sm btn-outline-secondary" disabled title="Недоступно">
                <i class="fas fa-eye-slash"></i>
            </button>
        @endif
        
        <a href="{{ route('admin.posts.edit', $post->ID) }}" class="btn btn-sm btn-primary" title="Редактировать">
            <i class="fas fa-edit"></i>
        </a>
        
        <form action="{{ route('admin.posts.delete', $post->ID) }}" method="POST" style="display: inline;" onsubmit="return confirm('Вы уверены, что хотите удалить эту статью?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-sm btn-danger" title="Удалить">
                <i class="fas fa-trash"></i>
            </button>
        </form>
                <i class="fas fa-trash"></i>
            </button>
        </form>
    </td>
</tr>
@endforeach

