@extends('layouts.admin')
@section('title', 'Управление пользователями')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-users"></i> Управление пользователями</h2>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Имя</th>
                        <th>Email</th>
                        <th>Роль</th>
                        <th>Должность</th>
                        <th>Статей</th>
                        <th>Последняя активность</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td>{{ $user->ID }}</td>
                            <td>
                                <strong>{{ $user->display_name }}</strong><br>
                                <small class="text-muted">{{ $user->user_login }}</small>
                            </td>
                            <td>{{ $user->user_email }}</td>
                            <td>
                                @if($user->getRole())
                                    <span class="badge bg-{{ $user->isSuperAdmin() ? 'danger' : ($user->isEditor() ? 'warning' : 'info') }}">
                                        {{ $user->getRole()->display_name }}
                                    </span>
                                @else
                                    <span class="badge bg-secondary">Нет роли</span>
                                @endif
                            </td>
                            <td>{{ $user->getPosition() ?? '—' }}</td>
                            <td>
                                @if($user->total_posts > 0)
                                    <a href="{{ route('admin.posts', ['author' => $user->ID]) }}" 
                                       class="text-decoration-none"
                                       title="Показать статьи автора">
                                        <strong>{{ $user->total_posts }}</strong> опубл.
                                        @if($user->draft_posts > 0)
                                            <br><small class="text-warning">{{ $user->draft_posts }} черновиков</small>
                                        @endif
                                    </a>
                                @else
                                    <span class="text-muted">0</span>
                                @endif
                            </td>
                            <td>
                                @if($user->statistics && $user->statistics->last_post_date)
                                    {{ $user->statistics->last_post_date->format('d.m.Y') }}
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                @if(admin_can('edit_users'))
                                    <a href="{{ route('admin.users.edit', $user->ID) }}" class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i> Редактировать
                                    </a>
                                @endif
                                
                                @if(admin_user() && admin_user()->isSuperAdmin() && $user->ID !== admin_user()->ID)
                                    <form action="{{ route('admin.users.impersonate', $user->ID) }}" method="POST" class="d-inline ms-1">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-secondary" 
                                                onclick="return confirm('Войти как {{ $user->display_name }}?')">
                                            <i class="fas fa-user-secret"></i> Войти как
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4">
                                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Пользователи не найдены</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($users->count() >= 50)
        <div class="alert alert-info mt-3 mb-0">
            <i class="fas fa-info-circle"></i>
            Показаны первые 50 пользователей. Если нужного пользователя нет в списке, свяжитесь с администратором.
        </div>
        @endif
    </div>
</div>

@endsection

