@extends('layouts.admin')
@section('title', 'Управление Бекапами')
@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-gradient-primary text-white" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <div class="card-body py-4">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h1 class="mb-2"><i class="fas fa-database"></i> Управление Бекапами</h1>
                            <p class="mb-0 opacity-75">Автоматические резервные копии сайта</p>
                        </div>
                        <div class="col-md-4 text-end">
                            <button class="btn btn-light btn-lg" data-bs-toggle="modal" data-bs-target="#createBackupModal">
                                <i class="fas fa-plus"></i> Создать Бекап
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <div class="avatar mx-auto mb-3" style="width: 50px; height: 50px; background: #4e73df; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-archive text-white fa-lg"></i>
                    </div>
                    <h4 class="mb-1">{{ $stats['total_backups'] }}</h4>
                    <p class="text-muted mb-0 small">Всего бекапов</p>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <div class="avatar mx-auto mb-3" style="width: 50px; height: 50px; background: #1cc88a; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-check-circle text-white fa-lg"></i>
                    </div>
                    <h4 class="mb-1">{{ $stats['completed_backups'] }}</h4>
                    <p class="text-muted mb-0 small">Завершенных</p>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <div class="avatar mx-auto mb-3" style="width: 50px; height: 50px; background: #e74a3b; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-exclamation-triangle text-white fa-lg"></i>
                    </div>
                    <h4 class="mb-1">{{ $stats['failed_backups'] }}</h4>
                    <p class="text-muted mb-0 small">Ошибок</p>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <div class="avatar mx-auto mb-3" style="width: 50px; height: 50px; background: #36b9cc; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-hdd text-white fa-lg"></i>
                    </div>
                    <h4 class="mb-1">{{ number_format($stats['total_size'] / 1073741824, 2) }} GB</h4>
                    <p class="text-muted mb-0 small">Общий размер</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Disk Space -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-3"><i class="fas fa-chart-pie"></i> Использование диска</h5>
                    <div class="progress" style="height: 30px;">
                        <div class="progress-bar bg-{{ $diskInfo['used_percent'] > 80 ? 'danger' : 'success' }}" 
                             style="width: {{ $diskInfo['used_percent'] }}%">
                            {{ number_format($diskInfo['used_percent'], 1) }}%
                        </div>
                    </div>
                    <p class="mt-2 mb-0 small text-muted">
                        <strong>Использовано:</strong> {{ number_format($diskInfo['used'] / 1073741824, 2) }} GB / 
                        <strong>Всего:</strong> {{ number_format($diskInfo['total'] / 1073741824, 2) }} GB / 
                        <strong>Свободно:</strong> {{ number_format($diskInfo['free'] / 1073741824, 2) }} GB
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Actions Row -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="btn-toolbar justify-content-between">
                <div>
                    <button class="btn btn-outline-secondary" onclick="runCleanup()">
                        <i class="fas fa-broom"></i> Очистить Старые
                    </button>
                </div>
                <div>
                    <button class="btn btn-outline-info" onclick="location.reload()">
                        <i class="fas fa-sync"></i> Обновить
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Backups Table -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-list"></i> Список Бекапов</h5>
                </div>
                <div class="card-body p-0">
                    @if($backups->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 40px;">#</th>
                                    <th>Имя файла</th>
                                    <th style="width: 120px;">Тип</th>
                                    <th style="width: 100px;">Размер</th>
                                    <th style="width: 120px;">Статус</th>
                                    <th style="width: 150px;">Дата</th>
                                    <th style="width: 80px;">Кем</th>
                                    <th style="width: 180px;" class="text-end">Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($backups as $backup)
                                <tr>
                                    <td>{{ $backup->id }}</td>
                                    <td>
                                        <span class="me-2">{{ $backup->type_icon }}</span>
                                        <code>{{ $backup->filename }}</code>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">{{ $backup->type_name }}</span>
                                    </td>
                                    <td>{{ $backup->human_size }}</td>
                                    <td>
                                        <span class="badge bg-{{ $backup->status === 'completed' ? 'success' : ($backup->status === 'in_progress' ? 'warning' : 'danger') }}">
                                            {{ $backup->status_icon }} {{ $backup->status_name }}
                                        </span>
                                    </td>
                                    <td>
                                        <small>{{ $backup->created_at->format('d.m.Y H:i') }}</small><br>
                                        @if($backup->duration)
                                        <small class="text-muted">{{ $backup->duration }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $backup->triggered_by }}</small>
                                    </td>
                                    <td class="text-end">
                                        @if($backup->status === 'completed')
                                        <a href="{{ route('admin.backups.download', $backup->id) }}" 
                                           class="btn btn-sm btn-success" 
                                           title="Скачать">
                                            <i class="fas fa-download"></i>
                                        </a>
                                        <button class="btn btn-sm btn-danger" 
                                                onclick="deleteBackup({{ $backup->id }})" 
                                                title="Удалить">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        @elseif($backup->status === 'failed')
                                        <button class="btn btn-sm btn-outline-secondary" 
                                                title="Ошибка: {{ $backup->error_message }}">
                                            <i class="fas fa-info-circle"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger" 
                                                onclick="deleteBackup({{ $backup->id }})">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        @else
                                        <span class="text-muted">В процессе...</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="text-center py-5">
                        <i class="fas fa-database fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Бекапы отсутствуют</p>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createBackupModal">
                            <i class="fas fa-plus"></i> Создать Первый Бекап
                        </button>
                    </div>
                    @endif
                </div>
                @if($backups->hasPages())
                <div class="card-footer">
                    {{ $backups->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Create Backup Modal -->
<div class="modal fade" id="createBackupModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus-circle"></i> Создать Бекап</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted">Выберите тип бекапа для создания:</p>
                <div class="list-group">
                    <button type="button" class="list-group-item list-group-item-action" onclick="createBackup('full')">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1">📦 Полный Бекап</h6>
                            <small class="text-muted">~5-10 мин</small>
                        </div>
                        <p class="mb-0 small">База данных + файлы + конфигурация</p>
                    </button>
                    <button type="button" class="list-group-item list-group-item-action" onclick="createBackup('database')">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1">🗄️ Только База Данных</h6>
                            <small class="text-muted">~1-2 мин</small>
                        </div>
                        <p class="mb-0 small">Дамп всех таблиц MySQL</p>
                    </button>
                    <button type="button" class="list-group-item list-group-item-action" onclick="createBackup('files')">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1">📁 Только Файлы</h6>
                            <small class="text-muted">~3-8 мин</small>
                        </div>
                        <p class="mb-0 small">Изображения и загруженные файлы</p>
                    </button>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
            </div>
        </div>
    </div>
</div>

<script>
function createBackup(type) {
    const modal = bootstrap.Modal.getInstance(document.getElementById('createBackupModal'));
    modal.hide();

    // Показываем уведомление
    Swal.fire({
        title: 'Создание бекапа...',
        html: 'Это может занять несколько минут. Пожалуйста, не закрывайте страницу.',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    fetch('{{ route('admin.backups.create') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ type: type })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Готово!',
                text: data.message,
                confirmButtonText: 'OK'
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Ошибка',
                text: data.message
            });
        }
    })
    .catch(error => {
        Swal.fire({
            icon: 'error',
            title: 'Ошибка',
            text: 'Не удалось создать бекап'
        });
    });
}

function deleteBackup(id) {
    Swal.fire({
        title: 'Удалить бекап?',
        text: 'Это действие нельзя отменить!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Да, удалить',
        cancelButtonText: 'Отмена'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`{{ url('/notaadmin/backups') }}/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Удалено!', data.message, 'success').then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Ошибка', data.message, 'error');
                }
            });
        }
    });
}

function runCleanup() {
    Swal.fire({
        title: 'Очистить старые бекапы?',
        text: 'Будут удалены бекапы согласно политике ротации.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Да, очистить',
        cancelButtonText: 'Отмена'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('{{ route('admin.backups.cleanup') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Готово!', data.message, 'success').then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Ошибка', data.message, 'error');
                }
            });
        }
    });
}
</script>
@endsection
