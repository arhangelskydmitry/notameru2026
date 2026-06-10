<?php

declare(strict_types=1);

use App\Models\PostSeo;
use App\Models\WordPress\Post;
use App\Models\WordPress\User;
use App\Services\SeoGeneratorService;
use Carbon\Carbon;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

$key = $_GET['key'] ?? '';

if ($key !== 'notaadmin2026-create-pvo') {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    exit('Forbidden');
}

header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

date_default_timezone_set('Europe/Moscow');

try {
    $authorId = User::query()->value('ID') ?? 1;
    $categoryIds = [1, 367];
    $publishAt = Carbon::today('Europe/Moscow')->setTime(13, 25, 0);

    $title = 'День войск ПВО РФ: слова благодарности и пожелания мирного неба';
    $slug = 'den-vojsk-pvo-rf-slova-blagodarnosti-i-pozhelaniya-mirnogo-neba';
    $excerpt = 'В День войск ПВО РФ звучат слова уважения и благодарности тем, кто несет круглосуточное дежурство и отвечает за безопасность воздушного пространства страны.';
    $content = <<<'HTML'
<p><strong>В День войск противовоздушной обороны Российской Федерации звучат слова уважения и благодарности тем, кто несет круглосуточную службу, требующую собранности, выдержки и высокой ответственности.</strong> Этот профессиональный праздник напоминает о важности ежедневной и часто незаметной для широкой аудитории работы, от которой напрямую зависит чувство защищенности и спокойствия.</p>

<p>Служба в подразделениях ПВО требует постоянной готовности, внимания к деталям и умения действовать четко в любой момент. За каждым дежурством стоят профессионализм, дисциплина, серьезная подготовка и верность своему делу. Именно поэтому в этот день особенно важно сказать теплые слова тем, кто остается на посту и днем, и ночью.</p>

<p>Праздничная дата становится поводом выразить искреннюю признательность военнослужащим, специалистам и ветеранам войск ПВО за службу, надежность и преданность своей профессии. Многие семьи, жители городов и регионов воспринимают их ежедневную работу как часть той опоры, на которой держатся уверенность, порядок и стабильность.</p>

<p>В этот день хочется пожелать всем, кто связан с войсками ПВО, спокойных дежурств, крепких сил, здоровья, поддержки близких и благополучия. Пусть в жизни будет как можно больше тихих дней, добрых новостей и уверенности в завтрашнем дне, а над головой как можно скорее воцарится мирное небо.</p>
HTML;

    $existing = Post::where('post_name', $slug)->first();
    if ($existing) {
        $existingSeo = PostSeo::where('post_id', $existing->ID)->first();
        echo json_encode([
            'ok' => true,
            'status' => 'exists',
            'post_id' => $existing->ID,
            'post_status' => $existing->post_status,
            'post_date' => optional($existing->post_date)->format('Y-m-d H:i:s'),
            'publicly_visible_now' => Post::publiclyVisible()->where('ID', $existing->ID)->exists(),
            'seo_title' => $existingSeo?->seo_title,
            'seo_description' => $existingSeo?->seo_description,
            'edit_url' => url('/notaadmin/posts/' . $existing->ID . '/edit'),
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    $post = Post::create([
        'post_author' => $authorId,
        'post_date' => $publishAt,
        'post_date_gmt' => $publishAt->copy()->utc(),
        'post_content' => $content,
        'post_title' => $title,
        'post_excerpt' => $excerpt,
        'post_status' => 'future',
        'post_name' => $slug,
        'post_modified' => now(),
        'post_modified_gmt' => now()->utc(),
        'post_type' => 'post',
        'comment_status' => 'closed',
        'ping_status' => 'closed',
        'to_ping' => '',
        'pinged' => '',
        'post_content_filtered' => '',
    ]);

    foreach ($categoryIds as $categoryId) {
        DB::table('wp_term_relationships')->insert([
            'object_id' => $post->ID,
            'term_taxonomy_id' => $categoryId,
            'term_order' => 0,
        ]);
    }

    try {
        $seoGenerator = new SeoGeneratorService();
        $seoData = $seoGenerator->generateSeoData($title, $excerpt, $content);
    } catch (Throwable $e) {
        $seoData = [];
    }

    PostSeo::updateOrCreate(
        ['post_id' => $post->ID],
        [
            'seo_title' => $seoData['seo_title'] ?? $title,
            'seo_description' => $seoData['seo_description'] ?? Str::limit(strip_tags($content), 155),
            'seo_keywords' => $seoData['seo_keywords'] ?? [],
            'canonical_url' => url('/' . $slug),
            'robots' => 'index, follow',
            'og_title' => $seoData['og_title'] ?? ($seoData['seo_title'] ?? $title),
            'og_description' => $seoData['og_description'] ?? ($seoData['seo_description'] ?? $excerpt),
            'og_image' => '',
            'og_type' => 'article',
            'twitter_card' => 'summary_large_image',
            'twitter_title' => $seoData['twitter_title'] ?? ($seoData['seo_title'] ?? $title),
            'twitter_description' => $seoData['twitter_description'] ?? ($seoData['seo_description'] ?? $excerpt),
            'twitter_image' => '',
            'focus_keywords' => array_filter([$seoData['focus_keyword'] ?? null]),
            'seo_score' => $seoData['seo_score'] ?? 0,
        ]
    );

    $post->refresh();
    $seo = PostSeo::where('post_id', $post->ID)->first();

    echo json_encode([
        'ok' => true,
        'status' => 'created',
        'post_id' => $post->ID,
        'post_title' => $post->post_title,
        'post_status' => $post->post_status,
        'post_date' => optional($post->post_date)->format('Y-m-d H:i:s'),
        'publicly_visible_now' => Post::publiclyVisible()->where('ID', $post->ID)->exists(),
        'seo_title' => $seo?->seo_title,
        'seo_description' => $seo?->seo_description,
        'edit_url' => url('/notaadmin/posts/' . $post->ID . '/edit'),
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
