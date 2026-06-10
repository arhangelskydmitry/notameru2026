@extends('layouts.admin')
@section('title', 'История действий')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-history"></i> История действий</h2>
</div>

<!-- Фильтры -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.activity-log') }}">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Пользователь</label>
                    <select class="form-select" name="user_id">
                        <option value="">Все пользователи</option>
                        @foreach($users as $user)
                            <option value="{{ $user->ID }}" {{ request('user_id') == $user->ID ? 'selected' : '' }}>
                                {{ $user->display_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Действие</label>
                    <select class="form-select" name="action">
                        <option value="">Все действия</option>
                        <option value="login" {{ request('action') == 'login' ? 'selected' : '' }}>Вход</option>
                        <option value="logout" {{ request('action') == 'logout' ? 'selected' : '' }}>Выход</option>
                        <option value="created" {{ request('action') == 'created' ? 'selected' : '' }}>Создание</option>
                        <option value="updated" {{ request('action') == 'updated' ? 'selected' : '' }}>Обновление</option>
                        <option value="deleted" {{ request('action') == 'deleted' ? 'selected' : '' }}>Удаление</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Дата от</label>
                    <input type="date" class="form-control" name="date_from" value="{{ request('date_from') }}">
                </div>

                <div class="col-md-2">
                    <label class="form-label">Дата до</label>
                    <input type="date" class="form-control" name="date_to" value="{{ request('date_to') }}">
                </div>

                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter"></i> Фильтровать
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Таблица логов -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th style="width: 160px;">Дата и время</th>
                        <th>Пользователь</th>
                        <th>Действие</th>
                        <th>Описание</th>
                        <th style="width: 120px;">IP адрес</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td>
                                <small>{{ $log->created_at->format('d.m.Y H:i:s') }}</small>
                            </td>
                            <td>
                                @if($log->user)
                                    <strong>{{ $log->user->display_name }}</strong><br>
                                    <small class="text-muted">{{ $log->user->user_email }}</small>
                                @else
                                    <span class="text-muted">Системное действие</span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $actionColors = [
                                        'login' => 'success',
                                        'logout' => 'secondary',
                                        'created' => 'info',
                                        'updated' => 'warning',
                                        'deleted' => 'danger',
                                    ];
                                    $actionNames = [
                                        'login' => 'Вход',
                                        'logout' => 'Выход',
                                        'created' => 'Создание',
                                        'updated' => 'Обновление',
                                        'deleted' => 'Удаление',
                                    ];
                                    $color = $actionColors[$log->action] ?? 'primary';
                                    $name = $actionNames[$log->action] ?? $log->action;
                                @endphp
                                <span class="badge bg-{{ $color }}">{{ $name }}</span>
                            </td>
                            <td>
                                {{ $log->description }}
                                @if($log->model_type)
                                    <br><small class="text-muted">{{ class_basename($log->model_type) }} #{{ $log->model_id }}</small>
                                @endif
                            </td>
                            <td><small class="font-monospace">{{ $log->ip_address }}</small></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4">
                                <i class="fas fa-history fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Записи не найдены</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-center mt-4">
            {{ $logs->appends(request()->query())->links() }}
        </div>
    </div>
</div>

@endsection

