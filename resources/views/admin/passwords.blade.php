@extends('layouts.admin')

@section('title', 'Управление паролями')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1 class="h3 mb-3">
                <i class="fas fa-key"></i> Управление паролями
            </h1>
            
            {{-- Показываем новый пароль ОДИН раз после сброса --}}
            @if (session('new_password'))
                @php $newPass = session('new_password'); @endphp
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <h5><i class="fas fa-check-circle"></i> Пароль успешно сброшен!</h5>
                    <div class="mt-3 p-3 bg-light border rounded">
                        <p class="mb-2"><strong>Пользователь:</strong> {{ $newPass['user_name'] }}</p>
                        <p class="mb-2"><strong>Email:</strong> {{ $newPass['user_email'] }}</p>
                        <p class="mb-0"><strong>Новый пароль:</strong></p>
                        <div class="input-group mt-2" style="max-width: 350px;">
                            <input type="text" 
                                   class="form-control font-monospace fw-bold" 
                                   value="{{ $newPass['password'] }}" 
                                   readonly 
                                   id="newPasswordField">
                            <button class="btn btn-primary" type="button" onclick="copyNewPassword()">
                                <i class="fas fa-copy"></i> Копировать
                            </button>
                        </div>
                    </div>
                    <div class="mt-3 text-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Внимание!</strong> Этот пароль показывается только ОДИН раз! 
                        Скопируйте его и передайте пользователю по защищённому каналу.
                        После обновления страницы пароль будет недоступен.
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            
            <div class="alert alert-info">
                <i class="fas fa-shield-alt"></i>
                <strong>Безопасность:</strong> Пароли хранятся в зашифрованном виде и не отображаются. 
                При сбросе новый пароль показывается только один раз.
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-users"></i> Пользователи системы
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Имя</th>
                                    <th>Email</th>
                                    <th>Логин</th>
                                    <th>Роль</th>
                                    <th>Должность</th>
                                    <th>Статус пароля</th>
                                    <th>Последний вход</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($users as $user)
                                <tr>
                                    <td>{{ $user['id'] }}</td>
                                    <td>{{ $user['name'] }}</td>
                                    <td>
                                        <a href="mailto:{{ $user['email'] }}">{{ $user['email'] }}</a>
                                    </td>
                                    <td>
                                        <code>{{ $user['login'] }}</code>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">{{ $user['role'] }}</span>
                                    </td>
                                    <td>{{ $user['position'] ?? '-' }}</td>
                                    <td>
                                        @if($user['has_password'])
                                            <span class="badge bg-success">
                                                <i class="fas fa-lock"></i> Установлен
                                            </span>
                                        @else
                                            <span class="badge bg-warning text-dark">
                                                <i class="fas fa-unlock"></i> Не установлен
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $user['last_login'] }}</small>
                                    </td>
                                    <td>
                                        <form action="{{ route('admin.passwords.reset', $user['id']) }}" 
                                              method="POST" 
                                              style="display: inline;"
                                              onsubmit="return confirm('Сбросить пароль для {{ $user['name'] }}?\n\nНовый пароль будет показан один раз после сброса.')">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-warning" title="Сбросить пароль">
                                                <i class="fas fa-redo"></i> Сбросить
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted">
                                        Нет пользователей с назначенными ролями
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="alert alert-secondary">
                <h6><i class="fas fa-info-circle"></i> Инструкция:</h6>
                <ul class="mb-0">
                    <li><strong>Сбросить пароль:</strong> Нажмите кнопку "Сбросить" - будет сгенерирован новый безопасный пароль</li>
                    <li><strong>Важно:</strong> Новый пароль показывается ТОЛЬКО ОДИН раз! Обязательно скопируйте его</li>
                    <li><strong>Передача пароля:</strong> Используйте защищенные каналы связи (личная встреча, мессенджер с шифрованием)</li>
                    <li><strong>Смена пароля:</strong> Рекомендуйте пользователям сменить пароль при первом входе</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
function copyNewPassword() {
    const field = document.getElementById('newPasswordField');
    field.select();
    field.setSelectionRange(0, 99999);
    
    navigator.clipboard.writeText(field.value).then(function() {
        const btn = event.target.closest('button');
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i> Скопировано!';
        btn.classList.remove('btn-primary');
        btn.classList.add('btn-success');
        
        setTimeout(function() {
            btn.innerHTML = originalHtml;
            btn.classList.remove('btn-success');
            btn.classList.add('btn-primary');
        }, 2000);
    }).catch(function(err) {
        alert('Ошибка копирования: ' + err);
    });
}
</script>
@endsection
