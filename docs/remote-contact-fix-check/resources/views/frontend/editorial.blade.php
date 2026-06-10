@extends('frontend.layout')

@section('title', 'Редакция и контакты - Нота Миру')
@section('description', 'Редакция и контакты сетевого издания «Нота Миру»: состав редакции, официальный адрес, телефон и email для редакционных и технических вопросов.')

@section('breadcrumbs')
    <a href="{{ route('home') }}">Главная</a>
    <span class="separator">›</span>
    <span class="current">Редакция и контакты</span>
@endsection

@section('content')
<div style="max-width: 980px; margin: 0 auto; display: grid; gap: 24px;">
    <article style="background: #fff; padding: 40px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
        <h1 style="font-size: 34px; margin-bottom: 18px; color: #2c3e50;">Редакция и контакты</h1>
        <p style="font-size: 18px; line-height: 1.8; color: #444; margin-bottom: 0;">
            «Нота Миру» освещает новости музыки, культуры и шоу-бизнеса, публикует редакционные материалы,
            интервью, афиши и тематические подборки. На этой странице собраны основные редакционные сведения
            и контакты для связи по вопросам публикаций, сотрудничества и технической поддержки.
        </p>
    </article>

    <section style="background: #fff; padding: 32px 40px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
        <h2 style="font-size: 26px; margin-bottom: 20px; color: #c80000;">Состав редакции</h2>
        <div style="display: grid; gap: 14px; line-height: 1.7; color: #333;">
            <div><strong>Дмитрий Архангельский</strong> — учредитель</div>
            <div><strong>Александр Киселёв</strong> — главный редактор, автор рубрики «Психология»</div>
            <div><strong>Евгений Овчинников</strong> — ответственный секретарь</div>
            <div><strong>Полина Уральская</strong> — корреспондент</div>
            <div><strong>Воробьев Георгий</strong> — директор по развитию</div>
            <div><strong>Сущевская Анастасия</strong> — корреспондент</div>
        </div>
    </section>

    <section style="background: #fff; padding: 32px 40px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
        <h2 style="font-size: 26px; margin-bottom: 20px; color: #c80000;">Контактная информация</h2>
        <div style="display: grid; gap: 12px; line-height: 1.8; color: #333;">
            <div><strong>Адрес:</strong> 105568, г. Москва, Большой Купавенский проезд, д.1, оф.18</div>
            <div><strong>Телефон:</strong> <a href="tel:+79999250975">+7 999 925-09-75</a></div>
            <div><strong>По редакционным вопросам:</strong> <a href="mailto:rotermelmax@notame.ru">rotermelmax@notame.ru</a></div>
            <div><strong>По техническим вопросам:</strong> <a href="mailto:notameru@yandex.ru">notameru@yandex.ru</a></div>
        </div>
    </section>

    <section style="background: linear-gradient(135deg, #fff6f6 0%, #ffffff 100%); border: 1px solid #f3d4d4; padding: 28px 32px; border-radius: 12px;">
        <h2 style="font-size: 24px; margin-bottom: 12px; color: #2c3e50;">Для рекламодателей</h2>
        <p style="line-height: 1.8; color: #444; margin-bottom: 16px;">
            Если вас интересует баннерное размещение, спецпроекты или информационное партнёрство,
            используйте страницу с рекламными форматами и схемой доступных зон.
        </p>
        <a href="{{ route('advertising') }}" style="display: inline-block; background: #c80000; color: #fff; text-decoration: none; padding: 12px 18px; border-radius: 8px; font-weight: 700;">
            Перейти на страницу «Реклама»
        </a>
    </section>
</div>
@endsection
