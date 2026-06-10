@extends('frontend.layout')

@section('title', 'Реклама на Нота Миру - баннерные размещения и медиакит')
@section('description', 'Реклама на сайте «Нота Миру»: баннерные форматы, доступные зоны размещения, схема показов по типам страниц и контакты для запуска рекламной кампании.')

@push('schema')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'WebPage',
    'name' => 'Реклама',
    'description' => 'Информация о баннерных форматах и рекламных возможностях на сайте «Нота Миру».',
    'url' => route('advertising'),
    'about' => [
        '@type' => 'Service',
        'name' => 'Размещение рекламы на Нота Миру',
        'provider' => [
            '@type' => 'Organization',
            'name' => config('app.name', 'Нота Миру'),
            'url' => url('/'),
        ],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
@endpush

@section('breadcrumbs')
    <a href="{{ route('home') }}">Главная</a>
    <span class="separator">›</span>
    <span class="current">Реклама</span>
@endsection

@section('content')
<div style="max-width: 1080px; margin: 0 auto; display: grid; gap: 24px;">
    <section style="background: linear-gradient(135deg, #fff6f6 0%, #ffffff 100%); border: 1px solid #f1d6d6; padding: 40px; border-radius: 14px; box-shadow: 0 2px 8px rgba(0,0,0,0.06);">
        <h1 style="font-size: 36px; line-height: 1.2; margin-bottom: 16px; color: #2c3e50;">Реклама на «Нота Миру»</h1>
        <p style="font-size: 18px; line-height: 1.8; color: #444; margin-bottom: 14px;">
            Предлагаем размещение баннерной рекламы и интеграций на страницах сетевого издания «Нота Миру».
            Страница построена на основе фактически доступных рекламных зон в системе сайта, чтобы схема размещения
            совпадала с тем, что реально можно быстро и безопасно запустить без доработок платформы.
        </p>
        <p style="font-size: 17px; line-height: 1.8; color: #444; margin-bottom: 20px;">
            Подход подходит для анонсов концертов, релизов, театральных и кинопроектов, бренд-кампаний,
            партнёрских публикаций и имиджевого присутствия в культурной и шоу-бизнес повестке.
        </p>
        <div style="display: flex; flex-wrap: wrap; gap: 12px;">
            <a href="mailto:pr@notame.ru?subject=Реклама%20на%20Нота%20Миру" style="display: inline-block; background: #c80000; color: #fff; text-decoration: none; padding: 13px 18px; border-radius: 8px; font-weight: 700;">Запросить размещение</a>
            <a href="{{ route('editorial') }}" style="display: inline-block; background: #fff; color: #c80000; text-decoration: none; padding: 13px 18px; border-radius: 8px; font-weight: 700; border: 1px solid #e3b4b4;">Редакция и контакты</a>
        </div>
    </section>

    <section style="background: #fff; padding: 32px 36px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
        <h2 style="font-size: 28px; margin-bottom: 10px; color: #2c3e50;">Схема возможного размещения баннеров</h2>
        <p style="line-height: 1.8; color: #555; margin-bottom: 22px;">
            Ниже приведены базовые рекламные позиции, уже поддерживаемые сайтом. Это позволяет согласовать кампанию
            без нестандартной вёрстки и уменьшает риск для стабильности live-сайта.
        </p>

        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; min-width: 760px;">
                <thead>
                    <tr>
                        <th style="text-align: left; padding: 14px 12px; background: #faf2f2; border-bottom: 2px solid #ead1d1; color: #2c3e50;">Позиция</th>
                        <th style="text-align: left; padding: 14px 12px; background: #faf2f2; border-bottom: 2px solid #ead1d1; color: #2c3e50;">Зона в системе</th>
                        <th style="text-align: left; padding: 14px 12px; background: #faf2f2; border-bottom: 2px solid #ead1d1; color: #2c3e50;">Рекомендованный размер</th>
                        <th style="text-align: left; padding: 14px 12px; background: #faf2f2; border-bottom: 2px solid #ead1d1; color: #2c3e50;">Где показывается</th>
                        <th style="text-align: left; padding: 14px 12px; background: #faf2f2; border-bottom: 2px solid #ead1d1; color: #2c3e50;">Задача формата</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($placements as $placement)
                    <tr>
                        <td style="padding: 14px 12px; border-bottom: 1px solid #eee; font-weight: 700; color: #2c3e50;">{{ $placement['title'] }}</td>
                        <td style="padding: 14px 12px; border-bottom: 1px solid #eee; color: #666;"><code>{{ $placement['zone'] }}</code></td>
                        <td style="padding: 14px 12px; border-bottom: 1px solid #eee; color: #333;">{{ $placement['size'] }}</td>
                        <td style="padding: 14px 12px; border-bottom: 1px solid #eee; color: #333;">{{ $placement['pages'] }}</td>
                        <td style="padding: 14px 12px; border-bottom: 1px solid #eee; color: #555;">{{ $placement['note'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <section style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 24px;">
        <article style="background: #fff; padding: 28px 30px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
            <h2 style="font-size: 24px; margin-bottom: 16px; color: #c80000;">Таргетинг показа</h2>
            <ul style="padding-left: 20px; line-height: 1.8; color: #444;">
                @foreach($targeting as $item)
                <li>{{ $item }}</li>
                @endforeach
            </ul>
        </article>

        <article style="background: #fff; padding: 28px 30px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
            <h2 style="font-size: 24px; margin-bottom: 16px; color: #c80000;">Поддерживаемые форматы</h2>
            <ul style="padding-left: 20px; line-height: 1.8; color: #444;">
                @foreach($formats as $item)
                <li>{{ $item }}</li>
                @endforeach
            </ul>
        </article>
    </section>

    <section style="background: #fff; padding: 32px 36px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
        <h2 style="font-size: 28px; margin-bottom: 18px; color: #2c3e50;">Как продумана схема размещения</h2>
        <div style="display: grid; gap: 14px; line-height: 1.8; color: #444;">
            <p><strong>1. Верхняя воронка охвата:</strong> `header` и `sidebar-top` подходят для быстрого контакта с аудиторией на первом экране.</p>
            <p><strong>2. Средина чтения:</strong> `content-top` и `content-middle` работают ближе к моменту вовлечённого просмотра материала и лучше подходят для performance-размещений.</p>
            <p><strong>3. Поддерживающее присутствие:</strong> `sidebar-middle` и `footer` удобны для длительных кампаний, партнёрских анонсов и имиджевого присутствия.</p>
            <p><strong>4. Безопасный запуск:</strong> размещения опираются на существующие зоны баннерной системы сайта, поэтому не требуют вмешательства в структуру публикаций и не должны ломать текущую работу сайта.</p>
        </div>
    </section>

    <section style="background: #fff; padding: 32px 36px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
        <h2 style="font-size: 28px; margin-bottom: 18px; color: #2c3e50;">Порядок запуска размещения</h2>
        <ol style="padding-left: 22px; line-height: 1.9; color: #444;">
            <li>Вы присылаете цель кампании, желаемые сроки, ссылку и рекламные материалы.</li>
            <li>Мы подбираем зоны, формат показа и приоритет размещения под задачу.</li>
            <li>После согласования баннер загружается в систему с ограничением по датам и типам страниц.</li>
            <li>Для запуска и согласования используйте редакционный адрес: <a href="mailto:pr@notame.ru">pr@notame.ru</a>.</li>
        </ol>
    </section>
</div>
@endsection
