<!DOCTYPE html>
<html lang="ru">
<head>
    @php
        $siteName = config('app.name', 'Нота Миру');
        $metaTitle = trim($__env->yieldContent('title', 'Нота Миру - Новости звезд шоу-бизнеса'));
        $rawDescription = trim(preg_replace('/\s+/u', ' ', strip_tags($__env->yieldContent('description', ''))));
        $defaultDescription = 'Новости музыки, культуры и шоу-бизнеса';
        $metaDescription = $rawDescription !== ''
            ? \Illuminate\Support\Str::limit($rawDescription, 160)
            : \Illuminate\Support\Str::limit(
                $metaTitle !== ''
                    ? "{$metaTitle}. Свежие новости, статьи и материалы на сайте {$siteName}."
                    : $defaultDescription,
                160
            );
    @endphp
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $metaTitle }}</title>
    <meta name="description" content="{{ $metaDescription }}">
    <meta name="keywords" content="@yield('keywords', 'новости, музыка, шоу-бизнес, звезды')">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}" sizes="any">
    <link rel="shortcut icon" href="{{ asset('favicon.svg') }}">
    <link rel="alternate" type="application/rss+xml" title="Нота Миру RSS" href="{{ route('rss.feed') }}">
    <link rel="alternate" type="application/rss+xml" title="Нота Миру Rambler RSS" href="{{ route('rss.rambler-news') }}">
    
    {{-- Canonical URL --}}
    <link rel="canonical" href="@yield('canonical', url()->current())">
    
    {{-- Meta Robots - закрываем индексацию на notame.pro --}}
    @if(str_contains(request()->getHost(), 'notame.pro'))
        <meta name="robots" content="noindex, nofollow">
    @else
        <meta name="robots" content="@yield('robots', 'index, follow')">
    @endif
    
    {{-- Open Graph теги --}}
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:title" content="@yield('og_title', $siteName)">
    <meta property="og:description" content="@yield('og_description', $metaDescription)">
    <meta property="og:url" content="@yield('og_url', url()->current())">
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:locale" content="ru_RU">
    @if(View::hasSection('og_image'))
    <meta property="og:image" content="@yield('og_image')">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    @endif
    
    {{-- Twitter Card --}}
    <meta name="twitter:card" content="@yield('twitter_card', 'summary_large_image')">
    <meta name="twitter:title" content="@yield('twitter_title', $siteName)">
    <meta name="twitter:description" content="@yield('twitter_description', $metaDescription)">
    @if(View::hasSection('twitter_image'))
    <meta name="twitter:image" content="@yield('twitter_image')">
    @endif
    
    @stack('meta')
    
    {{-- CSRF Token для AJAX запросов --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    {{-- Structured Data (Schema.org) --}}
    @stack('schema')
    
    <!-- Базовые стили в стиле NewsCard -->
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', 'Helvetica Neue', sans-serif;
            line-height: 1.6;
            color: #444;
            background: #f8f8f8;
        }
        
        .container {
            max-width: 1400px;  /* Увеличили с 1200px до 1400px */
            margin: 0 auto;
            padding: 0 15px;
        }
        
        /* Шапка с логотипом и баннером */
        .top-header {
            background: #fff;
            border-bottom: 1px solid #eee;
            padding: 5px 0;
        }
        
        .top-header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo-link {
            display: block;
            text-decoration: none;
        }
        
        .site-logo {
            height: 90px;
            width: auto;
            display: block;
        }
        
        .header-banner {
            text-align: center;
        }
        
        .header-banner a {
            display: inline-block;
            line-height: 0;
        }
        
        /* Красная строка с меню и поиском */
        .main-navigation {
            background: #c80000;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .nav-content {
            display: flex;
            justify-content: flex-start; /* Прижимаем меню к левому краю */
            align-items: center;
            padding: 0;
        }
        
        .nav-menu {
            list-style: none;
            display: flex;
            margin: 0;
        }
        
        .nav-menu li {
            margin: 0;
        }
        
        .nav-menu a {
            display: block;
            padding: 15px 20px;
            text-decoration: none;
            color: #fff;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            transition: background 0.3s;
        }
        
        .nav-menu a:hover {
            background: rgba(0,0,0,0.1);
        }
        
        .search-form-nav {
            padding: 8px 15px;
            margin-left: auto; /* Прижимаем поиск к правому краю */
        }
        
        .search-form-nav input {
            padding: 8px 15px;
            border: none;
            border-radius: 3px;
            width: 200px;
            font-size: 13px;
        }
        
        /* Гамбургер меню для мобильных */
        .mobile-menu-toggle {
            display: none;
            background: none;
            border: none;
            color: #fff;
            font-size: 28px;
            cursor: pointer;
            padding: 10px 15px;
            line-height: 1;
        }
        
        /* Мобильные стили */
        @media (max-width: 992px) {
            /* Обеспечиваем, что body и html не блокируют меню */
            body, html {
                overflow-x: hidden;
            }
            
            .mobile-menu-toggle {
                display: block;
                position: relative;
                z-index: 10003;
            }
            
            .nav-menu {
                position: fixed;
                top: 0;
                right: -100%;
                width: 280px;
                height: 100vh;
                background: #fff;
                flex-direction: column;
                padding: 80px 0 20px;
                box-shadow: -2px 0 10px rgba(0,0,0,0.2);
                transition: right 0.3s ease-in-out;
                overflow-y: auto;
                z-index: 10001;
            }
            
            .nav-menu.active {
                right: 0;
            }
            
            .nav-menu li {
                margin: 0;
                border-bottom: 1px solid #eee;
            }
            
            .nav-menu a {
                color: #333;
                padding: 15px 25px;
                font-size: 16px;
            }
            
            .nav-menu a:hover {
                background: #f5f5f5;
                color: #c80000;
            }
            
            /* Кнопка закрытия меню */
            .nav-menu::before {
                content: '×';
                position: absolute;
                top: 20px;
                right: 20px;
                font-size: 40px;
                color: #333;
                cursor: pointer;
                width: 40px;
                height: 40px;
                display: flex;
                align-items: center;
                justify-content: center;
                line-height: 1;
                z-index: 10002;
            }
            
            /* Затемнение фона */
            .mobile-menu-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.5);
                z-index: 10000;
            }
            
            .mobile-menu-overlay.active {
                display: block;
            }
            
            .search-form-nav {
                padding: 10px 15px;
            }
            
            .search-form-nav input {
                width: 100%;
                max-width: none;
            }
        }
        
        @media (max-width: 768px) {
            .top-header-content {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start; /* Прижимаем к левому краю */
            }
            
            .site-logo {
                height: 60px;
            }
            
            /* Скрываем рекламный баннер на мобильных */
            .header-banner {
                display: none;
            }
        }
        
        /* Бегущая строка новостей */
        .news-ticker {
            background: #f0f0f0;
            padding: 12px 0;
            border-bottom: 2px solid #c80000;
            overflow: hidden;
        }
        
        .ticker-content {
            display: flex;
            align-items: center;
        }
        
        .ticker-label {
            background: #c80000;
            color: #fff;
            padding: 8px 20px;
            font-weight: bold;
            font-size: 12px;
            text-transform: uppercase;
            margin-right: 20px;
            flex-shrink: 0;
        }
        
        .ticker-items {
            display: flex;
            gap: 40px;
            animation: ticker 30s linear infinite;
        }
        
        .ticker-items a {
            color: #333;
            text-decoration: none;
            white-space: nowrap;
            font-size: 14px;
        }
        
        .ticker-items a:hover {
            color: #c80000;
        }
        
        @keyframes ticker {
            0% { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }
        
        main {
            padding: 15px 0 30px 0;
        }
        
        /* Хлебные крошки */
        .breadcrumbs-wrapper {
            background: #f8f8f8;
            border-bottom: 1px solid #e5e5e5;
            padding: 12px 0;
        }
        
        .breadcrumbs {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            font-size: 13px;
            color: #666;
        }
        
        .breadcrumbs a {
            color: #666;
            text-decoration: none;
            transition: color 0.2s;
        }
        
        .breadcrumbs a:hover {
            color: #c80000;
        }
        
        .breadcrumbs .separator {
            margin: 0 8px;
            color: #999;
        }
        
        .breadcrumbs .current {
            color: #333;
            font-weight: 500;
        }
        
        /* Ссылки на авторов */
        .post-meta a:hover {
            color: #c80000 !important;
            text-decoration: underline !important;
        }
        
        /* Слайдер главной страницы */
        .main-slider {
            background: #fff;
            border-radius: 4px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: relative;
            height: 450px;  /* Фиксированная высота для единообразия */
        }
        
        .slider-item {
            display: none;
            position: relative;
            height: 100%;
            overflow: hidden;
        }
        
        .slider-item.active {
            display: block;
        }
        
        .slider-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;  /* Заполняем всё пространство, обрезая при необходимости */
            object-position: center center;  /* Центрируем изображение */
            transition: transform 0.5s ease;
        }
        
        .slider-item:hover img {
            transform: scale(1.05);  /* Уменьшили эффект zoom для лучшей читаемости */
        }
        
        .slider-caption {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(to top, rgba(0,0,0,0.85) 0%, rgba(0,0,0,0.6) 60%, transparent 100%);  /* Усиленное затемнение */
            color: #fff;
            padding: 30px;
        }
        
        .slider-caption h2 {
            font-size: 24px;
            margin-bottom: 10px;
            text-shadow: 0 2px 8px rgba(0,0,0,0.5);  /* Тень для лучшей читаемости */
        }
        
        .slider-caption h2 a {
            color: #fff;
            text-decoration: none;
        }
        
        .slider-controls {
            position: absolute;
            bottom: 20px;
            right: 20px;
            display: flex;
            gap: 10px;
        }
        
        .slider-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: rgba(255,255,255,0.5);
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .slider-dot.active {
            background: #c80000;
        }
        
        /* Колонки для интервью и релизов */
        .sidebar-widget {
            background: #fff;
            border-radius: 4px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .widget-title {
            background: #c80000;
            color: #fff;
            padding: 12px 20px;
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            margin: 0;
        }
        
        .widget-content {
            padding: 15px;
        }
        
        .widget-post {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .widget-post:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }
        
        .widget-post-thumb {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 4px;
            flex-shrink: 0;
        }
        
        .widget-post a .widget-post-thumb {
            transition: transform 0.3s ease;
        }
        
        .widget-post a:hover .widget-post-thumb {
            transform: scale(1.05);
        }
        
        .widget-post-content h4 {
            font-size: 14px;
            line-height: 1.4;
            margin-bottom: 5px;
        }
        
        .widget-post-content h4 a {
            color: #333;
            text-decoration: none;
        }
        
        .widget-post-content h4 a:hover {
            color: #c80000;
        }
        
        .widget-post-meta {
            font-size: 12px;
            color: #999;
        }
        
        .posts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .post-card {
            background: #fff;
            border-radius: 4px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .post-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .post-thumbnail {
            width: 100%;
            height: 275px; /* Увеличено с 220px на 25% (220 * 1.25 = 275) */
            object-fit: cover;
        }
        
        .post-content {
            padding: 20px;
        }
        
        .post-title {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 8px;
            line-height: 1.3;
        }
        
        .post-title a {
            text-decoration: none;
            color: #222;
            transition: color 0.3s;
        }
        
        .post-title a:hover {
            color: #c80000;
        }
        
        .post-meta {
            font-size: 14px;
            color: #999;
            margin-bottom: 10px;
        }
        
        .post-excerpt {
            color: #666;
            line-height: 1.6;
        }
        
        .categories {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 12px;
        }
        
        .category-tag {
            background: #c80000;
            color: white;
            padding: 4px 12px;
            border-radius: 3px;
            font-size: 11px;
            text-decoration: none;
            transition: background 0.3s;
            text-transform: uppercase;
            font-weight: 600;
        }
        
        .category-tag:hover {
            background: #a00000;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin: 40px 0;
        }
        
        .pagination a,
        .pagination span {
            padding: 8px 14px;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 3px;
            text-decoration: none;
            color: #333;
            font-size: 14px;
        }
        
        .pagination .active {
            background: #c80000;
            color: white;
            border-color: #c80000;
        }
        
        /* Стили для кликабельных изображений в статьях */
        .post-body .post-image-link {
            display: inline-block;
            position: relative;
            cursor: pointer;
            transition: opacity 0.3s ease;
        }
        
        .post-body .post-image-link:hover {
            opacity: 0.9;
        }
        
        .post-body .post-image-link img {
            display: block;
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: box-shadow 0.3s ease, transform 0.3s ease;
        }
        
        .post-body .post-image-link:hover img {
            box-shadow: 0 4px 16px rgba(0,0,0,0.2);
            transform: scale(1.02);
        }
        
        /* Индикатор увеличения для изображений */
        .post-body .post-image-link::after {
            content: '🔍';
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 8px 12px;
            border-radius: 50%;
            font-size: 16px;
            opacity: 0;
            transition: opacity 0.3s ease;
            pointer-events: none;
        }
        
        .post-body .post-image-link:hover::after {
            opacity: 1;
        }
        
        footer {
            background: #222;
            color: #999;
            padding: 50px 0 30px;
            margin-top: 60px;
            border-top: 3px solid #c80000;
        }
        
        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .footer-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 40px;
            margin-bottom: 30px;
        }
        
        .footer-info h3 {
            color: #fff;
            font-size: 18px;
            margin-bottom: 15px;
            font-weight: 600;
        }
        
        .footer-info p {
            margin-bottom: 10px;
            line-height: 1.6;
            font-size: 14px;
        }
        
        .footer-info strong {
            color: #ddd;
        }
        
        .footer-links {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .footer-links h3 {
            color: #fff;
            font-size: 18px;
            margin-bottom: 15px;
            font-weight: 600;
        }
        
        .footer-links a {
            color: #999;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s;
        }
        
        .footer-links a:hover {
            color: #c80000;
        }
        
        .footer-bottom {
            border-top: 1px solid #444;
            padding-top: 20px;
            text-align: center;
            font-size: 13px;
        }
        
        .footer-content a {
            color: #c80000;
            text-decoration: none;
        }
        
        .footer-content a:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 768px) {
            .footer-grid {
                grid-template-columns: 1fr;
                gap: 30px;
            }
            
            footer {
                padding: 40px 0 20px;
            }
        }
        
        .sidebar {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .sidebar h3 {
            margin-bottom: 20px;
            color: #e74c3c;
        }
        
        .sidebar ul {
            list-style: none;
        }
        
        .sidebar li {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .sidebar a {
            text-decoration: none;
            color: #333;
        }
        
        @media (max-width: 768px) {
            .posts-grid {
                grid-template-columns: 1fr;
            }
            
            nav ul {
                flex-direction: column;
                gap: 10px;
            }
            
            /* Убираем overflow на всех контейнерах чтобы меню работало */
            .container,
            main,
            div[style*="display: grid"],
            div[style*="grid-template-columns"] {
                overflow: visible !important;
            }
            
            /* НЕ скрываем сайдбары, а переносим их вниз */
            .sidebar,
            .home-sidebar-sticky,
            .page-sidebar-sticky,
            aside.sidebar,
            aside[class*="sidebar"],
            aside[style*="position: sticky"] {
                position: static !important;
                max-height: none !important;
                overflow-y: visible !important;
                margin-top: 30px !important;
            }
            
            /* Контейнеры с сайдбаром на мобильных становятся flex-column */
            .home-content-with-sidebar,
            .page-with-sidebar,
            .content-with-sidebar,
            div[style*="grid-template-columns: 3fr 1fr"],
            div[style*="grid-template-columns: 1fr 300px"],
            div[style*="display: grid"] {
                display: flex !important;
                flex-direction: column !important;
                gap: 0 !important;
            }
            
            /* Основной контент - первым */
            .home-content-with-sidebar > *:first-child,
            .page-with-sidebar > *:first-child,
            div[style*="grid-template-columns: 3fr 1fr"] > *:first-child,
            div[style*="grid-template-columns: 1fr 300px"] > *:first-child,
            div[style*="display: grid"] > *:first-child {
                order: 1;
            }
            
            /* Сайдбар - вторым (внизу) */
            .home-content-with-sidebar > aside,
            .page-with-sidebar > aside,
            div[style*="grid-template-columns: 3fr 1fr"] > aside,
            div[style*="grid-template-columns: 1fr 300px"] > aside,
            div[style*="display: grid"] > aside {
                order: 2;
                width: 100% !important;
            }
            
            /* УМЕНЬШАЕМ ВСЕ ЗАГОЛОВКИ НА МОБИЛЬНЫХ */
            /* H1 заголовки - 18px */
            h1,
            h1[style*="font-size"] {
                font-size: 18px !important;
                line-height: 1.4 !important;
            }
            
            /* H2 заголовки - 16px */
            h2,
            h2[style*="font-size"] {
                font-size: 16px !important;
                line-height: 1.3 !important;
            }
            
            /* УМЕНЬШАЕМ РАЗМЕР ЗАГОЛОВКОВ В СЛАЙДЕРЕ */
            .main-slider {
                height: 300px !important;  /* Меньшая высота для мобильных */
            }
            
            .slider-caption h2 {
                font-size: 16px !important;
                line-height: 1.3;
                margin-bottom: 8px;
            }
            
            .slider-caption {
                padding: 15px !important;
            }
            
            /* СКРЫВАЕМ ПЛАШКИ КАТЕГОРИЙ В СЛАЙДЕРЕ */
            .slider-caption > div:first-child {
                display: none !important;
            }
            
            /* Уменьшаем размер метаданных */
            .slider-caption > div[style*="font-size: 13px"] {
                font-size: 11px !important;
            }
            
            /* БЕГУЩАЯ СТРОКА НА МОБИЛЬНЫХ */
            /* Уменьшаем высоту бегущей строки */
            .top-stories-bar {
                height: 36px !important;
            }
            
            /* Делаем блок "Лента" уже */
            .top-stories-label {
                min-width: 90px !important;
                padding: 0 15px 0 10px !important;
            }
            
            /* Уменьшаем скошенный блок */
            .top-stories-label:after {
                right: -15px !important;
                width: 30px !important;
            }
            
            /* Уменьшаем размер текста "Лента" */
            .label-text {
                font-size: 12px !important;
            }
            
            /* Уменьшаем размер кружка */
            .flash-dot {
                width: 8px !important;
                height: 8px !important;
            }
            
            /* ЕДИНЫЙ РАЗМЕР ЗАГОЛОВКОВ В БЕГУЩЕЙ СТРОКЕ */
            .marquee a {
                font-size: 13px !important;
                font-weight: 600 !important;
                margin-right: 40px !important;
            }
            
            /* Уменьшаем отступ контента от блока "Лента" */
            .top-stories-content {
                padding-left: 20px !important;
            }
            
            /* СТРАНИЦА СТАТЬИ НА МОБИЛЬНЫХ */
            /* Основной контейнер статьи - делаем в одну колонку */
            div[style*="grid-template-columns: 1fr 300px"] {
                grid-template-columns: 1fr !important;
                gap: 20px !important;
            }
            
            /* Основной контейнер статьи */
            article[style*="padding: 40px"] {
                padding: 20px !important;
            }
            
            /* ЗАГОЛОВОК СТАТЬИ - уменьшаем в 2 раза */
            article h1[style*="font-size: 36px"] {
                font-size: 18px !important;
                line-height: 1.4 !important;
                margin-bottom: 12px !important;
                word-wrap: break-word;
                overflow-wrap: break-word;
                hyphens: auto;
            }
            
            /* Категории под заголовком */
            .category-tag {
                font-size: 10px !important;
                padding: 3px 8px !important;
                margin-right: 4px !important;
                margin-bottom: 4px !important;
            }
            
            /* Тело статьи */
            .post-body {
                font-size: 16px !important;
            }
            
            /* Мета-информация */
            .post-meta {
                font-size: 12px !important;
                flex-wrap: wrap !important;
                gap: 10px !important;
            }
            
            /* Теги */
            div[style*="border-top: 1px solid #eee"] a {
                font-size: 12px !important;
                padding: 4px 8px !important;
            }
        }
        
        /* Глобальные стили для sticky сайдбара */
        aside[style*="position: sticky"] {
            position: sticky;
            top: 50px;
            align-self: start;
            max-height: calc(100vh - 60px);
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: rgba(200, 0, 0, 0.2) transparent;
        }
        
        aside[style*="position: sticky"]::-webkit-scrollbar {
            width: 4px;
        }
        
        aside[style*="position: sticky"]::-webkit-scrollbar-track {
            background: transparent;
        }
        
        aside[style*="position: sticky"]::-webkit-scrollbar-thumb {
            background: rgba(200, 0, 0, 0.2);
            border-radius: 3px;
        }
        
        aside[style*="position: sticky"]::-webkit-scrollbar-thumb:hover {
            background: rgba(200, 0, 0, 0.4);
        }
        
        @media (max-width: 768px) {
            aside[style*="position: sticky"] {
                position: static;
                max-height: none;
                overflow-y: visible;
            }
        }
    </style>
    
    @stack('styles')
    
    {{-- Счетчики аналитики (header) --}}
    @php
        $headerCounters = \App\Models\Counter::getActiveForPosition('header');
    @endphp
    @foreach($headerCounters as $counter)
        {!! $counter->code !!}
    @endforeach
</head>
<body class="@if(request()->is('/')) home @endif">
    <!-- Шапка с логотипом и баннером -->
    <div class="top-header">
        <div class="container">
            <div class="top-header-content">
                <a href="{{ route('home') }}" class="logo-link">
                    <img src="{{ asset('images/logo.png') }}" alt="Нота Миру" class="site-logo">
                </a>
                <div class="header-banner">
                    @banner('header')
                </div>
            </div>
        </div>
    </div>
    
    <!-- Красная строка с меню и поиском -->
    <nav class="main-navigation">
        <div class="container">
            <div class="nav-content">
                <!-- Кнопка гамбургера (только на мобильных) -->
                <button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Открыть меню">
                    <span>☰</span>
                </button>
                
                <!-- Затемнение фона для мобильного меню -->
                <div class="mobile-menu-overlay" id="mobileMenuOverlay"></div>
                
                <ul class="nav-menu" id="navMenu">
                    @php
                        $menuItems = \App\Models\MenuItem::active()
                            ->ordered()
                            ->with(['category.term', 'page'])
                            ->get();
                    @endphp
                    @foreach($menuItems as $menuItem)
                        <li><a href="{{ $menuItem->url }}">{{ $menuItem->title }}</a></li>
                    @endforeach
                </ul>
                <div class="search-form-nav">
                    <form action="{{ route('search') }}" method="GET">
                        <input type="text" name="s" placeholder="Поиск..." value="{{ request('s') }}">
                    </form>
                </div>
            </div>
        </div>
    </nav>
    
    <!-- Бегущая строка (только на главной) или хлебные крошки (на остальных) -->
    @yield('ticker')
    
    @if(View::hasSection('breadcrumbs'))
    <div class="breadcrumbs-wrapper">
        <div class="container">
            <nav class="breadcrumbs" aria-label="breadcrumb">
                @yield('breadcrumbs')
            </nav>
        </div>
    </div>
    @endif
    
    <main>
        <div class="container">
                    @yield('content')
        </div>
    </main>
    
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-grid">
                    <!-- Информация о компании -->
                    <div class="footer-info">
                        <h3>Нота Миру</h3>
                        <p><strong>Полное наименование:</strong> Нота Миру</p>
                        <p><strong>Правообладатель:</strong> ИП Архангельский Дмитрий Николаевич</p>
                        <p>Сетевое издание «Нота Миру» является зарегистрированным СМИ<br>
                        <strong>Свидетельство:</strong> ЭЛ ФС 77-85677 от 28.07.2023</p>
                        <p><strong>Адрес:</strong> 105568, г. Москва, Большой Купавенский проезд, д.1, оф.18</p>
                        <p style="margin-top: 15px; font-size: 13px;">Новости музыки, культуры и шоу-бизнеса</p>
                    </div>
                    
                    <!-- Ссылки -->
                    <div class="footer-links">
                        <h3>Информация</h3>
                        <a href="{{ route('privacy') }}">Политика конфиденциальности</a>
                        <a href="{{ route('sitemap.html') }}">Карта сайта</a>
                        <a href="{{ route('home') }}">Главная</a>
                        <a href="{{ route('search') }}">Поиск</a>
                    </div>
                </div>
                
                <!-- Копирайт -->
                <div class="footer-bottom">
                <p>&copy; {{ date('Y') }} <a href="{{ route('home') }}">Нота Миру</a>. Все права защищены.</p>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- jQuery Marquee Plugin -->
    <script src="{{ asset('js/jquery.marquee.min.js') }}"></script>
    
    <!-- Мобильное меню -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.getElementById('mobileMenuToggle');
            const navMenu = document.getElementById('navMenu');
            const menuOverlay = document.getElementById('mobileMenuOverlay');
            
            // Открытие/закрытие меню по кнопке
            menuToggle.addEventListener('click', function() {
                navMenu.classList.toggle('active');
                menuOverlay.classList.toggle('active');
                document.body.style.overflow = navMenu.classList.contains('active') ? 'hidden' : '';
            });
            
            // Закрытие меню по клику на overlay
            menuOverlay.addEventListener('click', function() {
                navMenu.classList.remove('active');
                menuOverlay.classList.remove('active');
                document.body.style.overflow = '';
            });
            
            // Закрытие меню по клику на крестик (::before элемент)
            navMenu.addEventListener('click', function(e) {
                const rect = navMenu.getBoundingClientRect();
                // Проверяем клик в области крестика (верхний правый угол)
                if (e.clientX > rect.right - 60 && e.clientY < rect.top + 60) {
                    navMenu.classList.remove('active');
                    menuOverlay.classList.remove('active');
                    document.body.style.overflow = '';
                }
            });
            
            // Закрытие меню по ESC
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && navMenu.classList.contains('active')) {
                    navMenu.classList.remove('active');
                    menuOverlay.classList.remove('active');
                    document.body.style.overflow = '';
                }
            });
        });
    </script>
    
    <!-- Инициализация бегущей строки -->
    <script>
        $(document).ready(function() {
            if ($('.marquee').length) {
                $('.marquee').marquee({
                    duration: 80000,      // Увеличили с 40000 до 80000 (еще медленнее в 2 раза)
                    gap: 0,
                    delayBeforeStart: 0,
                    direction: 'left',
                    duplicated: true,
                    pauseOnHover: true,
                    startVisible: true
                });
            }
        });
    </script>
    
    <!-- Простой JS для слайдера -->
    <script>
        let currentSlide = 0;
        const slides = document.querySelectorAll('.slider-item');
        const dots = document.querySelectorAll('.slider-dot');
        
        function showSlide(index) {
            slides.forEach((slide, i) => {
                slide.classList.toggle('active', i === index);
            });
            dots.forEach((dot, i) => {
                dot.classList.toggle('active', i === index);
            });
        }
        
        function nextSlide() {
            currentSlide = (currentSlide + 1) % slides.length;
            showSlide(currentSlide);
        }
        
        // Автопереключение каждые 5 секунд
        if (slides.length > 0) {
            setInterval(nextSlide, 5000);
            
            // Клик по точкам
            dots.forEach((dot, index) => {
                dot.addEventListener('click', () => {
                    currentSlide = index;
                    showSlide(currentSlide);
                });
            });
        }
    </script>
    
    <!-- Banner Tracking -->
    <script>
        // Трекинг показов и кликов по баннерам
        document.addEventListener('DOMContentLoaded', function() {
            // Находим все баннеры
            const banners = document.querySelectorAll('.banner-container');
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
            
            banners.forEach(function(container) {
                const bannerId = container.dataset.bannerId;
                
                if (!bannerId) return;
                
                // 1. Отслеживаем ПОКАЗ баннера при загрузке страницы
                fetch('/api/banner/impression', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        banner_id: bannerId
                    })
                }).catch(function(error) {
                    console.error('Banner impression tracking failed:', error);
                });
                
                // 2. Отслеживаем КЛИКИ по ссылкам внутри баннера
                const links = container.querySelectorAll('a[data-banner-click]');
                
                links.forEach(function(link) {
                    link.addEventListener('click', function(e) {
                        // Записываем клик
                        fetch('/api/banner/click', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                banner_id: bannerId
                            })
                        }).catch(function(error) {
                            console.error('Banner click tracking failed:', error);
                        });
                        
                        // Разрешаем переход по ссылке
                        // (не используем e.preventDefault())
                    });
                });
            });
        });
    </script>
    
    <!-- Модальное окно для изображений -->
    <div id="imageLightbox" class="lightbox-modal">
        <span class="lightbox-close">&times;</span>
        <img class="lightbox-content" id="lightboxImage">
        <div class="lightbox-caption" id="lightboxCaption"></div>
        <button class="lightbox-prev" id="lightboxPrev">&#10094;</button>
        <button class="lightbox-next" id="lightboxNext">&#10095;</button>
    </div>
    
    <style>
    /* Модальное окно для изображений */
    .lightbox-modal {
        display: none;
        position: fixed;
        z-index: 10000;
        padding-top: 60px;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0, 0, 0, 0.95);
        animation: fadeIn 0.3s ease;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    .lightbox-modal.active {
        display: block;
    }
    
    .lightbox-content {
        margin: auto;
        display: block;
        max-width: 90%;
        max-height: 80vh;
        animation: zoomIn 0.3s ease;
        border-radius: 8px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5);
    }
    
    @keyframes zoomIn {
        from { transform: scale(0.8); }
        to { transform: scale(1); }
    }
    
    .lightbox-close {
        position: absolute;
        top: 20px;
        right: 35px;
        color: #fff;
        font-size: 50px;
        font-weight: 300;
        transition: 0.3s;
        cursor: pointer;
        z-index: 10001;
    }
    
    .lightbox-close:hover,
    .lightbox-close:focus {
        color: #c80000;
        text-decoration: none;
    }
    
    .lightbox-caption {
        margin: auto;
        display: block;
        width: 80%;
        max-width: 700px;
        text-align: center;
        color: #ccc;
        padding: 20px 0;
        height: auto;
        font-size: 16px;
    }
    
    /* Кнопки навигации */
    .lightbox-prev,
    .lightbox-next {
        cursor: pointer;
        position: absolute;
        top: 50%;
        width: auto;
        margin-top: -30px;
        padding: 16px 20px;
        color: white;
        font-weight: bold;
        font-size: 30px;
        transition: 0.3s ease;
        border-radius: 0 3px 3px 0;
        user-select: none;
        background-color: rgba(200, 0, 0, 0.5);
        border: none;
        z-index: 10001;
    }
    
    .lightbox-next {
        right: 0;
        border-radius: 3px 0 0 3px;
    }
    
    .lightbox-prev:hover,
    .lightbox-next:hover {
        background-color: rgba(200, 0, 0, 0.8);
    }
    
    /* Адаптация для мобильных */
    @media (max-width: 768px) {
        .lightbox-content {
            max-width: 95%;
            max-height: 70vh;
        }
        
        .lightbox-close {
            top: 10px;
            right: 20px;
            font-size: 40px;
        }
        
        .lightbox-prev,
        .lightbox-next {
            padding: 12px 15px;
            font-size: 24px;
        }
        
        .lightbox-caption {
            font-size: 14px;
            padding: 15px 10px;
        }
    }
    </style>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const lightbox = document.getElementById('imageLightbox');
        const lightboxImg = document.getElementById('lightboxImage');
        const lightboxCaption = document.getElementById('lightboxCaption');
        const closeBtn = document.querySelector('.lightbox-close');
        const prevBtn = document.getElementById('lightboxPrev');
        const nextBtn = document.getElementById('lightboxNext');
        
        let currentImageIndex = 0;
        let imageLinks = [];
        
        // Собираем все ссылки на изображения на странице
        function updateImageLinks() {
            imageLinks = Array.from(document.querySelectorAll('a.post-image-link[data-lightbox="post-images"]'));
        }
        
        // Открытие модального окна
        function openLightbox(index) {
            if (imageLinks.length === 0) return;
            
            currentImageIndex = index;
            const link = imageLinks[currentImageIndex];
            const img = link.querySelector('img');
            
            lightboxImg.src = link.href;
            lightboxCaption.textContent = img.alt || '';
            lightbox.classList.add('active');
            document.body.style.overflow = 'hidden';
            
            // Показываем/скрываем кнопки навигации
            prevBtn.style.display = imageLinks.length > 1 ? 'block' : 'none';
            nextBtn.style.display = imageLinks.length > 1 ? 'block' : 'none';
        }
        
        // Закрытие модального окна
        function closeLightbox() {
            lightbox.classList.remove('active');
            document.body.style.overflow = '';
        }
        
        // Предыдущее изображение
        function showPrevImage() {
            currentImageIndex = (currentImageIndex - 1 + imageLinks.length) % imageLinks.length;
            openLightbox(currentImageIndex);
        }
        
        // Следующее изображение
        function showNextImage() {
            currentImageIndex = (currentImageIndex + 1) % imageLinks.length;
            openLightbox(currentImageIndex);
        }
        
        // Инициализация
        updateImageLinks();
        
        // Обработчики событий для всех изображений
        document.addEventListener('click', function(e) {
            const link = e.target.closest('a.post-image-link[data-lightbox="post-images"]');
            if (link) {
                e.preventDefault();
                updateImageLinks();
                const index = imageLinks.indexOf(link);
                if (index !== -1) {
                    openLightbox(index);
                }
            }
        });
        
        // Закрытие по клику на крестик
        closeBtn.addEventListener('click', closeLightbox);
        
        // Закрытие по клику вне изображения
        lightbox.addEventListener('click', function(e) {
            if (e.target === lightbox) {
                closeLightbox();
            }
        });
        
        // Навигация
        prevBtn.addEventListener('click', showPrevImage);
        nextBtn.addEventListener('click', showNextImage);
        
        // Управление клавиатурой
        document.addEventListener('keydown', function(e) {
            if (!lightbox.classList.contains('active')) return;
            
            if (e.key === 'Escape') {
                closeLightbox();
            } else if (e.key === 'ArrowLeft') {
                showPrevImage();
            } else if (e.key === 'ArrowRight') {
                showNextImage();
            }
            });
        });
    </script>
    
    @stack('scripts')
    
    <!-- Lazy Loading Polyfill -->
    <script>
    // Polyfill для старых браузеров (IE, старые Safari)
    (function() {
        if ('loading' in HTMLImageElement.prototype) {
            return; // Браузер поддерживает loading="lazy" нативно
        }
        
        // Для старых браузеров используем Intersection Observer
        if ('IntersectionObserver' in window) {
            const images = document.querySelectorAll('img[loading="lazy"]');
            const imageObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.removeAttribute('loading');
                        imageObserver.unobserve(img);
                    }
                });
            }, {
                rootMargin: '50px' // Начинаем загрузку за 50px до появления
            });
            
            images.forEach(img => imageObserver.observe(img));
        } else {
            // Совсем старые браузеры - загружаем все
            document.querySelectorAll('img[loading="lazy"]').forEach(img => {
                img.removeAttribute('loading');
            });
        }
    })();
    </script>
    
    {{-- Счетчики аналитики (footer) --}}
    @php
        $footerCounters = \App\Models\Counter::getActiveForPosition('footer');
    @endphp
    @foreach($footerCounters as $counter)
        {!! $counter->code !!}
    @endforeach
    
    <!-- Cookie Notice -->
    @include('components.cookie-notice')
</body>
</html>



