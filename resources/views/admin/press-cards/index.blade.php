@extends('layouts.admin')
@section('title', 'Пресс-карты')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="h3 mb-1"><i class="fas fa-id-card me-2"></i>Пресс-карты</h2>
        <p class="text-muted mb-0">Удостоверения прессы для журналистов «Нота Миру»</p>
    </div>
    <a href="{{ route('admin.press-cards.create') }}" class="btn btn-primary">
        <i class="fas fa-plus"></i> Выдать карту
    </a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>№</th>
                    <th>ФИО</th>
                    <th>Должность</th>
                    <th>Выдана</th>
                    <th>До</th>
                    <th>Статус</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($cards as $card)
                    <tr>
                        <td><code>{{ $card->card_number }}</code></td>
                        <td>{{ $card->full_name }}</td>
                        <td>{{ $card->position ?? '—' }}</td>
                        <td>{{ $card->issued_at->format('d.m.Y') }}</td>
                        <td>{{ $card->expires_at->format('d.m.Y') }}</td>
                        <td>
                            <span class="badge bg-{{ $card->statusBadgeClass() }}">{{ $card->statusLabel() }}</span>
                        </td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('admin.press-cards.show', $card->id) }}" class="btn btn-sm btn-outline-secondary">Открыть</a>
                            <a href="{{ route('admin.press-cards.pdf', $card->id) }}" class="btn btn-sm btn-outline-primary" target="_blank">PDF</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">Пресс-карты ещё не выдавались</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $cards->links() }}</div>

@endsection
