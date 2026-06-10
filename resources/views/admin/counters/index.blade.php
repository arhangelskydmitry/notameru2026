@extends('layouts.admin')

@section('title', 'Управление счетчиками')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Счетчики (Яндекс Метрика, Google Analytics и т.д.)</h1>
        <a href="{{ route('admin.counters.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Добавить счетчик
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            @if($counters->isEmpty())
                <div class="text-center py-5">
                    <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
                    <p class="text-muted">Нет добавленных счетчиков</p>
                    <a href="{{ route('admin.counters.create') }}" class="btn btn-primary">
                        Добавить первый счетчик
                    </a>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th style="width: 50px;">ID</th>
                                <th>Название</th>
                                <th style="width: 120px;">Позиция</th>
                                <th style="width: 100px;">Порядок</th>
                                <th style="width: 100px;">Статус</th>
                                <th style="width: 200px;">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($counters as $counter)
                                <tr>
                                    <td>{{ $counter->id }}</td>
                                    <td>
                                        <strong>{{ $counter->name }}</strong>
                                        <br>
                                        <small class="text-muted">
                                            {{ Str::limit(strip_tags($counter->code), 50) }}
                                        </small>
                                    </td>
                                    <td>
                                        @if($counter->position === 'sidebar')
                                            <span class="badge bg-info">Сайдбар</span>
                                        @elseif($counter->position === 'footer')
                                            <span class="badge bg-secondary">Футер</span>
                                        @else
                                            <span class="badge bg-dark">Хедер</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark">{{ $counter->sort_order }}</span>
                                    </td>
                                    <td>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input counter-toggle" 
                                                   type="checkbox" 
                                                   data-id="{{ $counter->id }}"
                                                   {{ $counter->is_active ? 'checked' : '' }}>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('admin.counters.edit', $counter) }}" 
                                               class="btn btn-sm btn-outline-primary"
                                               title="Редактировать">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('admin.counters.destroy', $counter) }}" 
                                                  method="POST" 
                                                  class="d-inline"
                                                  onsubmit="return confirm('Вы уверены что хотите удалить этот счетчик?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" 
                                                        class="btn btn-sm btn-outline-danger"
                                                        title="Удалить">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header">
            <i class="fas fa-info-circle"></i> Справка
        </div>
        <div class="card-body">
            <h6>Как использовать:</h6>
            <ul>
                <li><strong>Яндекс Метрика:</strong> Вставьте код счетчика из личного кабинета Метрики</li>
                <li><strong>Google Analytics:</strong> Вставьте Global Site Tag (gtag.js)</li>
                <li><strong>Позиция:</strong> Выберите где показывать счетчик (сайдбар, футер или хедер)</li>
                <li><strong>Порядок:</strong> Укажите порядок отображения (меньшее число - выше)</li>
                <li><strong>Статус:</strong> Включите/выключите счетчик одним кликом</li>
            </ul>
            
            <h6 class="mt-3">Примеры кодов:</h6>
            <p><strong>Яндекс Метрика:</strong></p>
            <pre class="bg-light p-3 rounded"><code>&lt;!-- Yandex.Metrika counter --&gt;
&lt;script type="text/javascript" &gt;
   (function(m,e,t,r,i,k,a){m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
   m[i].l=1*new Date();
   for (var j = 0; j < document.scripts.length; j++) {if (document.scripts[j].src === r) { return; }}
   k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)})
   (window, document, "script", "https://mc.yandex.ru/metrika/tag.js", "ym");

   ym(XXXXXXXX, "init", {
        clickmap:true,
        trackLinks:true,
        accurateTrackBounce:true
   });
&lt;/script&gt;</code></pre>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle активности счетчика
    document.querySelectorAll('.counter-toggle').forEach(toggle => {
        toggle.addEventListener('change', function() {
            const counterId = this.dataset.id;
            const isChecked = this.checked;
            
            fetch(`/notaadmin/counters/${counterId}/toggle`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ is_active: isChecked })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Показываем уведомление
                    const alert = document.createElement('div');
                    alert.className = 'alert alert-success alert-dismissible fade show position-fixed top-0 end-0 m-3';
                    alert.style.zIndex = '9999';
                    alert.innerHTML = `
                        Счетчик ${data.is_active ? 'включен' : 'выключен'}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    `;
                    document.body.appendChild(alert);
                    
                    setTimeout(() => alert.remove(), 3000);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                this.checked = !isChecked; // Возвращаем прежнее состояние
                alert('Ошибка при изменении статуса');
            });
        });
    });
});
</script>
@endsection
