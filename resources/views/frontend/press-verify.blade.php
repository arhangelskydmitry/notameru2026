@extends('frontend.layout')

@section('title', 'Проверка пресс-карты')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h1 class="h4 mb-3">Проверка удостоверения прессы</h1>
                    <p class="text-muted">Нота Миру · {{ $cardNumber }}</p>

                    @if($card && $card->isActive())
                        <div class="alert alert-success">
                            <strong>Удостоверение действительно.</strong><br>
                            {{ $card->full_name }} · {{ $card->position ?? 'журналист' }}<br>
                            {{ $card->organization }}<br>
                            Действительна до {{ $card->expires_at->format('d.m.Y') }}
                        </div>
                    @elseif($card)
                        <div class="alert alert-warning">
                            <strong>Удостоверение недействительно.</strong> Статус: {{ $card->statusLabel() }}
                        </div>
                    @else
                        <div class="alert alert-danger">
                            <strong>Удостоверение не найдено.</strong>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
