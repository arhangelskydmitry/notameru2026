@extends('layouts.admin')
@section('title', 'Пресс-карта ' . $card->card_number)
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-id-card"></i> {{ $card->card_number }}</h2>
    <div>
        <a href="{{ route('admin.press-cards.preview', $card->id) }}" class="btn btn-outline-secondary" target="_blank">Предпросмотр</a>
        <a href="{{ route('admin.press-cards.pdf', $card->id) }}" class="btn btn-primary" target="_blank">Скачать PDF</a>
        <a href="{{ route('admin.press-cards.edit', $card->id) }}" class="btn btn-outline-primary">Редактировать</a>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-3">
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-3">ФИО</dt><dd class="col-sm-9">{{ $card->full_name }}</dd>
                    <dt class="col-sm-3">Должность</dt><dd class="col-sm-9">{{ $card->position ?? '—' }}</dd>
                    <dt class="col-sm-3">Организация</dt><dd class="col-sm-9">{{ $card->organization }}</dd>
                    <dt class="col-sm-3">Журналист</dt>
                    <dd class="col-sm-9">
                        @if($card->wpUser)
                            <a href="{{ route('admin.users.edit', $card->wpUser->ID) }}">{{ $card->wpUser->display_name }}</a>
                        @else
                            ID {{ $card->user_id }}
                        @endif
                    </dd>
                    <dt class="col-sm-3">Выдана</dt><dd class="col-sm-9">{{ $card->issued_at->format('d.m.Y') }}</dd>
                    <dt class="col-sm-3">Действует до</dt><dd class="col-sm-9">{{ $card->expires_at->format('d.m.Y') }}</dd>
                    <dt class="col-sm-3">Статус</dt>
                    <dd class="col-sm-9"><span class="badge bg-{{ $card->statusBadgeClass() }}">{{ $card->statusLabel() }}</span></dd>
                    <dt class="col-sm-3">Проверка</dt>
                    <dd class="col-sm-9"><a href="{{ $card->verifyUrl() }}" target="_blank">{{ $card->verifyUrl() }}</a></dd>
                    @if($card->notes)
                        <dt class="col-sm-3">Примечание</dt><dd class="col-sm-9">{{ $card->notes }}</dd>
                    @endif
                </dl>
            </div>
        </div>

        @if($card->status === 'active')
            <form action="{{ route('admin.press-cards.revoke', $card->id) }}" method="POST" onsubmit="return confirm('Отозвать пресс-карту?');">
                @csrf
                <button type="submit" class="btn btn-outline-danger">
                    <i class="fas fa-ban"></i> Отозвать карту
                </button>
            </form>
        @endif
    </div>

    <div class="col-md-4">
        @if($card->photo_path)
            <div class="card">
                <div class="card-header">Фото</div>
                <div class="card-body text-center">
                    <img src="{{ asset('storage/' . $card->photo_path) }}" alt="" class="img-fluid rounded" style="max-height:220px">
                </div>
            </div>
        @endif
    </div>
</div>

@endsection
