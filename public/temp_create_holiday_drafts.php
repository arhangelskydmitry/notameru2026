<?php

declare(strict_types=1);

use App\Models\PostSeo;
use App\Models\WordPress\Post;
use App\Services\SeoGeneratorService;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

date_default_timezone_set('Europe/Moscow');

$authorId = 1;
$categoryIds = [1, 367];
$now = Carbon::now();

$articles = [
    [
        'title' => 'Страна отмечает День космонавтики: память о подвиге и взгляд в будущее',
        'slug' => 'strana-otmechaet-den-kosmonavtiki-pamyat-o-podvige-i-vzglyad-v-budushhee',
        'excerpt' => 'Россия отмечает День космонавтики: по всей стране проходят памятные акции, выставки и просветительские события в честь первого полета человека в космос.',
        'featured_image' => '/images/generated/cosmonautics-day-2026.svg',
        'content' => <<<HTML
<p><strong>12 апреля Россия отмечает День космонавтики</strong> — дату, которая навсегда вошла в мировую историю после легендарного полета Юрия Гагарина. Этот праздник объединяет не только специалистов отрасли, но и миллионы людей, для которых космос остается символом национальной гордости, научного прогресса и веры в большие свершения.</p>

<p>В разных регионах страны в этот день проходят памятные церемонии, тематические выставки, лекции, экскурсии и открытые уроки. Музеи, планетарии, школы и культурные площадки напоминают о том, какой масштабный путь прошла отечественная космонавтика — от первого полета человека в космос до современных научных миссий и технологических разработок.</p>

<p>Особое внимание в День космонавтики традиционно уделяется фигуре Юрия Гагарина. Его имя остается одним из главных символов XX века, а знаменитое «Поехали!» по-прежнему воспринимается как начало новой эры. Для многих поколений этот день связан не только с памятью о достижении, но и с чувством вдохновения, которое дает история первого космического полета.</p>

<p>Праздничная дата становится и поводом поговорить о будущем. Сегодня космическая тема все активнее возвращается в общественную повестку: обсуждаются новые проекты, развитие космической инфраструктуры, научные программы и роль технологий в повседневной жизни. Именно поэтому День космонавтики остается не просто памятной датой, а живым праздником, в котором соединяются история, наука и образ будущего.</p>

<p>Для зрителей, читателей и всех, кто следит за большими символическими датами, 12 апреля — это еще и день особой атмосферы. В медиапространстве вспоминают архивные кадры, слова очевидцев, культовые цитаты и редкие факты о космической эпохе. И каждый год этот день вновь напоминает: путь к звездам начинается с мечты, смелости и способности мыслить шире привычных границ.</p>
HTML,
    ],
    [
        'title' => 'Благодатный огонь сошел в Иерусалиме: верующие встречают Светлую Пасху',
        'slug' => 'blagodatnyj-ogon-soshel-v-ierusalime-veruyushhie-vstrechayut-svetluyu-pashu',
        'excerpt' => 'В Иерусалиме сошел Благодатный огонь — одно из главных событий Пасхи. Верующие по всему миру встречают праздник Светлого Христова Воскресения.',
        'featured_image' => '/images/generated/holy-fire-easter-2026.svg',
        'content' => <<<HTML
<p><strong>В Иерусалиме сошел Благодатный огонь</strong> — одно из самых ожидаемых и символичных событий пасхальных дней для миллионов верующих по всему миру. По традиции это знамение связано с наступлением Светлого Христова Воскресения и воспринимается как особый момент духовного единения, надежды и радости.</p>

<p>Схождение огня в храме Гроба Господня ежегодно привлекает огромное внимание не только паломников, но и широкой аудитории, которая следит за событием через трансляции, новости и церковные сообщения. Для многих семей именно этот момент становится эмоциональным началом пасхального торжества — с особым настроением, молитвой и ожиданием добрых вестей.</p>

<p>После схождения Благодатный огонь развозят в разные страны, где его встречают в храмах и передают верующим. В России и других православных странах это событие традиционно занимает важное место в информационной повестке накануне Пасхи. Оно сопровождается богослужениями, праздничными обращениями и подготовкой к ночным службам.</p>

<p>Светлая Пасха — один из главных христианских праздников, который связан с темами обновления, милосердия и внутреннего света. Поэтому новости о Благодатном огне всегда вызывают особый отклик: для верующих это знак духовной поддержки, а для широкой аудитории — напоминание о живой религиозной традиции, которая сохраняет свое значение и в современном мире.</p>

<p>В этот день звучат поздравления, добрые пожелания и слова мира. Праздничная атмосфера объединяет людей вокруг ценностей, которые особенно важны сегодня: сострадания, взаимной поддержки, семейного тепла и веры в лучшее. С праздником Светлой Пасхи!</p>
HTML,
    ],
];

$created = [];

foreach ($articles as $article) {
    $existing = Post::where('post_name', $article['slug'])->first();
    if ($existing) {
        $created[] = [
            'id' => $existing->ID,
            'slug' => $existing->post_name,
            'status' => 'exists',
        ];
        continue;
    }

    $post = Post::create([
        'post_author' => $authorId,
        'post_date' => $now,
        'post_date_gmt' => $now->copy()->utc(),
        'post_content' => $article['content'],
        'post_title' => $article['title'],
        'post_excerpt' => $article['excerpt'],
        'post_status' => 'draft',
        'post_name' => $article['slug'],
        'post_modified' => $now,
        'post_modified_gmt' => $now->copy()->utc(),
        'post_type' => 'post',
        'comment_status' => 'closed',
        'ping_status' => 'closed',
        'to_ping' => '',
        'pinged' => '',
        'post_content_filtered' => '',
    ]);

    $post->setMeta('_thumbnail_url', $article['featured_image']);

    foreach ($categoryIds as $categoryId) {
        DB::table('wp_term_relationships')->insert([
            'object_id' => $post->ID,
            'term_taxonomy_id' => $categoryId,
        ]);
    }

    $seoGenerator = new SeoGeneratorService();
    $seoData = $seoGenerator->generateSeoData(
        $article['title'],
        $article['excerpt'],
        $article['content']
    );

    PostSeo::create([
        'post_id' => $post->ID,
        'seo_title' => $seoData['seo_title'] ?? $article['title'],
        'seo_description' => $seoData['seo_description'] ?? Str::limit(strip_tags($article['content']), 155),
        'seo_keywords' => $seoData['seo_keywords'] ?? [],
        'canonical_url' => url('/' . $article['slug']),
        'robots' => 'index, follow',
        'og_title' => $seoData['og_title'] ?? ($seoData['seo_title'] ?? $article['title']),
        'og_description' => $seoData['og_description'] ?? ($seoData['seo_description'] ?? $article['excerpt']),
        'og_image' => url($article['featured_image']),
        'og_type' => 'article',
        'twitter_card' => 'summary_large_image',
        'twitter_title' => $seoData['twitter_title'] ?? ($seoData['seo_title'] ?? $article['title']),
        'twitter_description' => $seoData['twitter_description'] ?? ($seoData['seo_description'] ?? $article['excerpt']),
        'twitter_image' => url($article['featured_image']),
        'focus_keywords' => array_filter([$seoData['focus_keyword'] ?? null]),
        'seo_score' => $seoData['seo_score'] ?? 0,
    ]);

    $created[] = [
        'id' => $post->ID,
        'slug' => $post->post_name,
        'status' => 'created',
        'title' => $post->post_title,
    ];
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'ok' => true,
    'created' => $created,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
