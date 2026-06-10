@extends('layouts.admin')
@section('title', 'Выдать пресс-карту')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-id-card"></i> Выдать пресс-карту</h2>
    <a href="{{ route('admin.press-cards.index') }}" class="btn btn-secondary">Назад</a>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('admin.press-cards.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label">Журналист *</label>
                        <select name="user_id" id="user_id" class="form-select @error('user_id') is-invalid @enderror" required>
                            <option value="">— выберите —</option>
                            @foreach($users as $user)
                                <option value="{{ $user->ID }}"
                                    data-name="{{ $user->display_name }}"
                                    data-position="{{ $user->getPosition() }}"
                                    {{ old('user_id', $selectedUser?->ID) == $user->ID ? 'selected' : '' }}>
                                    {{ $user->display_name }} ({{ $user->user_email }})
                                </option>
                            @endforeach
                        </select>
                        @error('user_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">ФИО на карте *</label>
                        <input type="text" name="full_name" id="full_name" class="form-control @error('full_name') is-invalid @enderror"
                               value="{{ old('full_name', $selectedUser?->display_name) }}" required>
                        @error('full_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Должность</label>
                        <input type="text" name="position" id="position" class="form-control"
                               value="{{ old('position', $selectedUser?->getPosition()) }}"
                               placeholder="Журналист, корреспондент…">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Организация *</label>
                        <input type="text" name="organization" class="form-control"
                               value="{{ old('organization', 'Интернет-издание «Нота Миру»') }}" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Дата выдачи *</label>
                            <input type="date" name="issued_at" class="form-control"
                                   value="{{ old('issued_at', now()->toDateString()) }}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Действительна до *</label>
                            <input type="date" name="expires_at" class="form-control"
                                   value="{{ old('expires_at', now()->addYear()->toDateString()) }}" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Фото (3×4)</label>
                        <input type="file" name="photo" class="form-control" accept="image/*">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Примечание</label>
                        <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-check"></i> Выдать пресс-карту
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.getElementById('user_id')?.addEventListener('change', function () {
    const opt = this.options[this.selectedIndex];
    if (!opt.value) return;
    document.getElementById('full_name').value = opt.dataset.name || '';
    document.getElementById('position').value = opt.dataset.position || '';
});
</script>
@endpush
@endsection
