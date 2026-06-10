@extends('layouts.admin')
@section('title', 'Редактирование пресс-карты')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-edit"></i> {{ $card->card_number }}</h2>
    <a href="{{ route('admin.press-cards.show', $card->id) }}" class="btn btn-secondary">Назад</a>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('admin.press-cards.update', $card->id) }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="mb-3">
                <label class="form-label">ФИО *</label>
                <input type="text" name="full_name" class="form-control" value="{{ old('full_name', $card->full_name) }}" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Должность</label>
                <input type="text" name="position" class="form-control" value="{{ old('position', $card->position) }}">
            </div>

            <div class="mb-3">
                <label class="form-label">Организация *</label>
                <input type="text" name="organization" class="form-control" value="{{ old('organization', $card->organization) }}" required>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Дата выдачи *</label>
                    <input type="date" name="issued_at" class="form-control" value="{{ old('issued_at', $card->issued_at->toDateString()) }}" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Действительна до *</label>
                    <input type="date" name="expires_at" class="form-control" value="{{ old('expires_at', $card->expires_at->toDateString()) }}" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Статус *</label>
                    <select name="status" class="form-select" required>
                        @foreach(['active' => 'Действует', 'revoked' => 'Отозвана', 'expired' => 'Истекла'] as $val => $label)
                            <option value="{{ $val }}" {{ old('status', $card->status) === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Новое фото</label>
                <input type="file" name="photo" class="form-control" accept="image/*">
                @if($card->photo_path)
                    <small class="text-muted">Текущее фото сохранится, если не загрузить новое.</small>
                @endif
            </div>

            <div class="mb-3">
                <label class="form-label">Примечание</label>
                <textarea name="notes" class="form-control" rows="2">{{ old('notes', $card->notes) }}</textarea>
            </div>

            <button type="submit" class="btn btn-primary">Сохранить</button>
        </form>
    </div>
</div>

@endsection
