@extends('frontend.layout')

@section('title', $page->post_title . ' - Нота Миру')

@section('content')
<article style="background: #fff; padding: 40px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); max-width: 900px; margin: 0 auto;">
    <h1 style="font-size: 36px; line-height: 1.2; margin-bottom: 30px; color: #2c3e50;">
        {{ $page->post_title }}
    </h1>
    
    <div class="page-body" style="font-size: 18px; line-height: 1.8; color: #444;">
        {!! $page->post_content !!}
    </div>
</article>
@endsection

