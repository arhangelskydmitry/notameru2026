@extends('frontend.layout')

@php
    $seoService = app(\App\Services\SeoService::class);
    $seo = $seoService->getPageSeo($page);
@endphp

@section('title', $seo['title'])
@section('description', $seo['description'])
@section('keywords', $seo['keywords'])
@section('canonical', $seo['canonical'])
@section('robots', $seo['robots'])

@section('og_type', $seo['og']['type'])
@section('og_title', $seo['og']['title'])
@section('og_description', $seo['og']['description'])
@section('og_url', $seo['og']['url'])
@section('og_image', $seo['og']['image'] ?? '')

@section('twitter_card', $seo['twitter']['card'])
@section('twitter_title', $seo['twitter']['title'])
@section('twitter_description', $seo['twitter']['description'])
@section('twitter_image', $seo['twitter']['image'] ?? '')

@if(!empty($seo['schema']))
@push('schema')
<script type="application/ld+json">
{!! json_encode($seo['schema'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
@endpush
@endif

@section('content')
@php
    // Определяем, нужен ли сайдбар для этой страницы (например, для "Редакция")
    $showSidebar = in_array($page->post_name, ['redakciya', 'redaktsiya', 'о-редакции']);
@endphp

@if($showSidebar)
<div class="page-with-sidebar">
    <article class="page-main-content">
        <h1>{{ $page->post_title }}</h1>
        
        <div class="page-body">
            {!! \App\Helpers\ContentHelper::getContent($page) !!}
        </div>
    </article>
    
    <!-- Сайдбар как на главной -->
    <aside class="page-sidebar-sticky">
        @include('partials.sidebar')
    </aside>
</div>
@else
<article style="background: #fff; padding: 40px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); max-width: 900px; margin: 0 auto;">
    <h1 style="font-size: 36px; line-height: 1.2; margin-bottom: 30px; color: #2c3e50;">
        {{ $page->post_title }}
    </h1>
    
    <div class="page-body" style="font-size: 18px; line-height: 1.8; color: #444;">
        {!! \App\Helpers\ContentHelper::getContent($page) !!}
    </div>
</article>
@endif

<style>
/* Макет страницы с сайдбаром */
.page-with-sidebar {
    display: grid;
    grid-template-columns: 3fr 1fr;
    gap: 30px;
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 20px;
}

.page-main-content {
    background: #fff;
    padding: 40px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.page-main-content h1 {
    font-size: 36px;
    line-height: 1.2;
    margin-bottom: 30px;
    color: #2c3e50;
}

.page-main-content .page-body {
    font-size: 18px;
    line-height: 1.8;
    color: #444;
}

.page-sidebar-sticky {
    position: sticky;
    top: 80px;
    align-self: start;
    height: fit-content;
    max-height: calc(100vh - 100px);
    overflow-y: auto;
}

/* Responsive */
@media (max-width: 1024px) {
    .page-with-sidebar {
        display: flex;
        flex-direction: column;
        gap: 0;
    }
    
    .page-sidebar-sticky {
        position: static;
        max-height: none;
        margin-top: 30px;
        order: 2;
    }
    
    .page-main-content {
        order: 1;
    }
}

/* Стили для изображений на странице */
.page-body img {
    max-width: 100%;
    height: auto;
    border-radius: 5px;
    margin: 20px 0;
}

.page-body .aligncenter {
    display: block;
    margin: 20px auto;
}

.page-body .alignleft {
    float: left;
    margin: 10px 20px 20px 0;
}

.page-body .alignright {
    float: right;
    margin: 10px 0 20px 20px;
}
</style>
@endsection

