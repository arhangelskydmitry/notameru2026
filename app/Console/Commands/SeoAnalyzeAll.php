<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WordPress\Post;
use App\Models\PostSeo;
use App\Services\SeoGeneratorService;
use App\Services\ChatInfoService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Команда для массового SEO-анализа всех статей
 * Генерирует SQL-файл с рекомендуемыми правками
 */
class SeoAnalyzeAll extends Command
{
    protected $signature = 'seo:analyze-all 
                            {--regenerate : Перегенерировать SEO через AI для проблемных статей}
                            {--limit=0 : Ограничить количество статей для анализа}
                            {--offset=0 : Пропустить первые N статей}
                            {--min-score=50 : Минимальный SEO-score для пропуска регенерации}
                            {--output=storage/seo_fixes.sql : Путь к выходному SQL-файлу}
                            {--dry-run : Только анализ, без генерации AI}';
    
    protected $description = 'Анализирует SEO всех статей и генерирует SQL с правками';
    
    protected ?SeoGeneratorService $seoService = null;
    protected array $issues = [];
    protected array $sqlStatements = [];
    protected int $analyzed = 0;
    protected int $needsFix = 0;
    protected int $regenerated = 0;
    
    // Критерии SEO-анализа
    protected array $criteria = [
        'title' => [
            'min_length' => 30,
            'max_length' => 60,
            'weight' => 20,
        ],
        'description' => [
            'min_length' => 120,
            'max_length' => 160,
            'weight' => 20,
        ],
        'keywords' => [
            'min_count' => 3,
            'max_count' => 10,
            'weight' => 15,
        ],
        'focus_keyword' => [
            'required' => true,
            'in_title' => true,
            'in_description' => true,
            'weight' => 25,
        ],
        'og_data' => [
            'required' => ['og_title', 'og_description'],
            'weight' => 10,
        ],
        'twitter_data' => [
            'required' => ['twitter_title', 'twitter_description'],
            'weight' => 10,
        ],
    ];

    public function handle(): int
    {
        $this->info('🔍 SEO-анализ всех статей');
        $this->info('=' . str_repeat('=', 50));
        
        $outputFile = $this->option('output');
        $regenerate = $this->option('regenerate');
        $dryRun = $this->option('dry-run');
        $limit = (int) $this->option('limit');
        $offset = (int) $this->option('offset');
        $minScore = (int) $this->option('min-score');
        
        // Инициализируем SEO-сервис если нужна регенерация
        if ($regenerate && !$dryRun) {
            try {
                $this->seoService = new SeoGeneratorService();
                $providers = $this->seoService->getAvailableProviders();
                $available = array_filter($providers, fn($p) => $p['configured']);
                
                if (empty($available)) {
                    $this->error('❌ Нет настроенных AI-провайдеров для регенерации!');
                    $this->info('Настройте ChatInfo или GigaChat в панели администратора.');
                    return 1;
                }
                
                $this->info('✅ Доступные AI-провайдеры: ' . implode(', ', array_keys($available)));
            } catch (\Exception $e) {
                $this->error('❌ Ошибка инициализации AI: ' . $e->getMessage());
                return 1;
            }
        }
        
        // Получаем статьи
        $query = Post::where('post_status', 'publish')
            ->where('post_type', 'post')
            ->orderBy('ID', 'desc');
        
        if ($offset > 0) {
            $query->skip($offset);
        }
        
        if ($limit > 0) {
            $query->take($limit);
        }
        
        $posts = $query->get();
        $total = $posts->count();
        
        $this->info("📊 Найдено статей: {$total}");
        $this->newLine();
        
        // Начинаем SQL-файл
        $this->initSqlFile($outputFile);
        
        // Прогресс-бар
        $bar = $this->output->createProgressBar($total);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% | %message%');
        $bar->start();
        
        foreach ($posts as $post) {
            $bar->setMessage("Анализ: {$post->ID}");
            
            $result = $this->analyzePost($post, $minScore, $regenerate, $dryRun);
            
            if ($result['needs_fix']) {
                $this->needsFix++;
                
                if ($result['regenerated']) {
                    $this->regenerated++;
                }
                
                // Добавляем SQL-запросы
                if (!empty($result['sql'])) {
                    $this->sqlStatements[] = $result['sql'];
                }
                
                // Сохраняем проблемы для отчёта
                $this->issues[$post->ID] = $result['issues'];
            }
            
            $this->analyzed++;
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine(2);
        
        // Записываем SQL-файл
        $this->writeSqlFile($outputFile);
        
        // Итоговый отчёт
        $this->printReport($outputFile);
        
        return 0;
    }
    
    /**
     * Анализирует одну статью
     */
    protected function analyzePost(Post $post, int $minScore, bool $regenerate, bool $dryRun): array
    {
        $result = [
            'needs_fix' => false,
            'issues' => [],
            'score' => 100,
            'regenerated' => false,
            'sql' => null,
        ];
        
        // Получаем SEO-данные
        $seo = PostSeo::where('post_id', $post->ID)->first();
        
        if (!$seo) {
            // Нет SEO-данных вообще
            $result['needs_fix'] = true;
            $result['issues'][] = '❌ SEO-данные отсутствуют';
            $result['score'] = 0;
            
            if ($regenerate && !$dryRun && $this->seoService) {
                $newSeo = $this->generateSeoForPost($post);
                if ($newSeo) {
                    $result['regenerated'] = true;
                    $result['sql'] = $this->buildInsertSql($post->ID, $newSeo);
                }
            }
            
            return $result;
        }
        
        // Анализируем каждый критерий
        $totalWeight = 0;
        $earnedScore = 0;
        
        // 1. Проверяем SEO Title
        $titleIssues = $this->analyzeSeoTitle($seo, $post);
        if (!empty($titleIssues['issues'])) {
            $result['issues'] = array_merge($result['issues'], $titleIssues['issues']);
        }
        $totalWeight += $this->criteria['title']['weight'];
        $earnedScore += $titleIssues['score'] * $this->criteria['title']['weight'] / 100;
        
        // 2. Проверяем SEO Description
        $descIssues = $this->analyzeSeoDescription($seo, $post);
        if (!empty($descIssues['issues'])) {
            $result['issues'] = array_merge($result['issues'], $descIssues['issues']);
        }
        $totalWeight += $this->criteria['description']['weight'];
        $earnedScore += $descIssues['score'] * $this->criteria['description']['weight'] / 100;
        
        // 3. Проверяем ключевые слова
        $keywordIssues = $this->analyzeKeywords($seo);
        if (!empty($keywordIssues['issues'])) {
            $result['issues'] = array_merge($result['issues'], $keywordIssues['issues']);
        }
        $totalWeight += $this->criteria['keywords']['weight'];
        $earnedScore += $keywordIssues['score'] * $this->criteria['keywords']['weight'] / 100;
        
        // 4. Проверяем Focus Keyword
        $focusIssues = $this->analyzeFocusKeyword($seo, $post);
        if (!empty($focusIssues['issues'])) {
            $result['issues'] = array_merge($result['issues'], $focusIssues['issues']);
        }
        $totalWeight += $this->criteria['focus_keyword']['weight'];
        $earnedScore += $focusIssues['score'] * $this->criteria['focus_keyword']['weight'] / 100;
        
        // 5. Проверяем Open Graph
        $ogIssues = $this->analyzeOpenGraph($seo);
        if (!empty($ogIssues['issues'])) {
            $result['issues'] = array_merge($result['issues'], $ogIssues['issues']);
        }
        $totalWeight += $this->criteria['og_data']['weight'];
        $earnedScore += $ogIssues['score'] * $this->criteria['og_data']['weight'] / 100;
        
        // 6. Проверяем Twitter
        $twitterIssues = $this->analyzeTwitter($seo);
        if (!empty($twitterIssues['issues'])) {
            $result['issues'] = array_merge($result['issues'], $twitterIssues['issues']);
        }
        $totalWeight += $this->criteria['twitter_data']['weight'];
        $earnedScore += $twitterIssues['score'] * $this->criteria['twitter_data']['weight'] / 100;
        
        // Рассчитываем итоговый score
        $result['score'] = $totalWeight > 0 ? round($earnedScore / $totalWeight * 100) : 0;
        
        // Определяем нужны ли правки
        if ($result['score'] < $minScore) {
            $result['needs_fix'] = true;
            
            // Регенерируем через AI если нужно
            if ($regenerate && !$dryRun && $this->seoService) {
                $newSeo = $this->generateSeoForPost($post);
                if ($newSeo) {
                    $result['regenerated'] = true;
                    $result['sql'] = $this->buildUpdateSql($seo->id, $post->ID, $newSeo);
                }
            }
        }
        
        return $result;
    }
    
    /**
     * Анализирует SEO Title
     */
    protected function analyzeSeoTitle(PostSeo $seo, Post $post): array
    {
        $issues = [];
        $score = 100;
        
        $title = $seo->seo_title ?? '';
        $postTitle = $post->post_title ?? '';
        
        if (empty($title)) {
            $issues[] = '⚠️ SEO Title пустой';
            return ['issues' => $issues, 'score' => 0];
        }
        
        $length = mb_strlen($title);
        
        // Проверяем длину
        if ($length < $this->criteria['title']['min_length']) {
            $issues[] = "⚠️ SEO Title слишком короткий ({$length} символов, минимум {$this->criteria['title']['min_length']})";
            $score -= 30;
        }
        
        if ($length > $this->criteria['title']['max_length']) {
            $issues[] = "⚠️ SEO Title слишком длинный ({$length} символов, максимум {$this->criteria['title']['max_length']})";
            $score -= 20;
        }
        
        // Проверяем дублирование с заголовком поста
        $similarity = similar_text(mb_strtolower($title), mb_strtolower($postTitle)) / max(mb_strlen($title), 1) * 100;
        if ($similarity > 90) {
            $issues[] = '⚠️ SEO Title почти идентичен заголовку поста (нужен рерайт)';
            $score -= 25;
        }
        
        return ['issues' => $issues, 'score' => max(0, $score)];
    }
    
    /**
     * Анализирует SEO Description
     */
    protected function analyzeSeoDescription(PostSeo $seo, Post $post): array
    {
        $issues = [];
        $score = 100;
        
        $description = $seo->seo_description ?? '';
        $excerpt = strip_tags($post->post_excerpt ?? '');
        
        if (empty($description)) {
            $issues[] = '⚠️ SEO Description пустой';
            return ['issues' => $issues, 'score' => 0];
        }
        
        $length = mb_strlen($description);
        
        // Проверяем длину
        if ($length < $this->criteria['description']['min_length']) {
            $issues[] = "⚠️ SEO Description слишком короткий ({$length} символов, минимум {$this->criteria['description']['min_length']})";
            $score -= 30;
        }
        
        if ($length > $this->criteria['description']['max_length']) {
            $issues[] = "⚠️ SEO Description слишком длинный ({$length} символов, максимум {$this->criteria['description']['max_length']})";
            $score -= 20;
        }
        
        // Проверяем дублирование с excerpt
        if (!empty($excerpt)) {
            $similarity = similar_text(mb_strtolower($description), mb_strtolower($excerpt)) / max(mb_strlen($description), 1) * 100;
            if ($similarity > 85) {
                $issues[] = '⚠️ SEO Description почти идентичен excerpt (нужна переформулировка)';
                $score -= 25;
            }
        }
        
        return ['issues' => $issues, 'score' => max(0, $score)];
    }
    
    /**
     * Анализирует ключевые слова
     */
    protected function analyzeKeywords(PostSeo $seo): array
    {
        $issues = [];
        $score = 100;
        
        $keywords = $seo->seo_keywords;
        
        if (empty($keywords)) {
            $issues[] = '⚠️ Ключевые слова отсутствуют';
            return ['issues' => $issues, 'score' => 0];
        }
        
        // Преобразуем в массив если строка
        if (is_string($keywords)) {
            $keywords = array_filter(array_map('trim', explode(',', $keywords)));
        }
        
        $count = is_array($keywords) ? count($keywords) : 0;
        
        if ($count < $this->criteria['keywords']['min_count']) {
            $issues[] = "⚠️ Мало ключевых слов ({$count}, минимум {$this->criteria['keywords']['min_count']})";
            $score -= 40;
        }
        
        if ($count > $this->criteria['keywords']['max_count']) {
            $issues[] = "⚠️ Слишком много ключевых слов ({$count}, максимум {$this->criteria['keywords']['max_count']})";
            $score -= 20;
        }
        
        return ['issues' => $issues, 'score' => max(0, $score)];
    }
    
    /**
     * Анализирует Focus Keyword
     */
    protected function analyzeFocusKeyword(PostSeo $seo, Post $post): array
    {
        $issues = [];
        $score = 100;
        
        $focusKeywords = $seo->focus_keywords;
        
        if (empty($focusKeywords)) {
            $issues[] = '❌ Focus Keyword отсутствует';
            return ['issues' => $issues, 'score' => 0];
        }
        
        // Получаем первое focus keyword
        $focusKeyword = is_array($focusKeywords) ? ($focusKeywords[0] ?? '') : $focusKeywords;
        $focusKeyword = mb_strtolower(trim($focusKeyword));
        
        if (empty($focusKeyword)) {
            $issues[] = '❌ Focus Keyword пустой';
            return ['issues' => $issues, 'score' => 0];
        }
        
        // Проверяем наличие в SEO Title
        $seoTitle = mb_strtolower($seo->seo_title ?? '');
        if (mb_strpos($seoTitle, $focusKeyword) === false) {
            $issues[] = '⚠️ Focus Keyword не найден в SEO Title';
            $score -= 30;
        }
        
        // Проверяем наличие в SEO Description
        $seoDescription = mb_strtolower($seo->seo_description ?? '');
        if (mb_strpos($seoDescription, $focusKeyword) === false) {
            $issues[] = '⚠️ Focus Keyword не найден в SEO Description';
            $score -= 30;
        }
        
        return ['issues' => $issues, 'score' => max(0, $score)];
    }
    
    /**
     * Анализирует Open Graph данные
     */
    protected function analyzeOpenGraph(PostSeo $seo): array
    {
        $issues = [];
        $score = 100;
        
        if (empty($seo->og_title)) {
            $issues[] = '⚠️ OG Title пустой';
            $score -= 50;
        }
        
        if (empty($seo->og_description)) {
            $issues[] = '⚠️ OG Description пустой';
            $score -= 50;
        }
        
        return ['issues' => $issues, 'score' => max(0, $score)];
    }
    
    /**
     * Анализирует Twitter Card данные
     */
    protected function analyzeTwitter(PostSeo $seo): array
    {
        $issues = [];
        $score = 100;
        
        if (empty($seo->twitter_title)) {
            $issues[] = '⚠️ Twitter Title пустой';
            $score -= 50;
        }
        
        if (empty($seo->twitter_description)) {
            $issues[] = '⚠️ Twitter Description пустой';
            $score -= 50;
        }
        
        return ['issues' => $issues, 'score' => max(0, $score)];
    }
    
    /**
     * Генерирует SEO через AI
     */
    protected function generateSeoForPost(Post $post): ?array
    {
        try {
            $seoData = $this->seoService->generateSeoData(
                $post->post_title,
                $post->post_excerpt,
                $post->post_content
            );
            
            return $seoData;
        } catch (\Exception $e) {
            $this->warn("AI ошибка для поста {$post->ID}: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Инициализирует SQL-файл
     */
    protected function initSqlFile(string $path): void
    {
        $dir = dirname($path);
        if (!File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }
        
        $header = "-- =====================================================\n";
        $header .= "-- SEO FIXES - Generated by seo:analyze-all\n";
        $header .= "-- Date: " . now()->format('Y-m-d H:i:s') . "\n";
        $header .= "-- =====================================================\n\n";
        $header .= "SET NAMES utf8mb4;\n";
        $header .= "SET CHARACTER SET utf8mb4;\n\n";
        
        File::put($path, $header);
    }
    
    /**
     * Записывает SQL-файл
     */
    protected function writeSqlFile(string $path): void
    {
        if (empty($this->sqlStatements)) {
            File::append($path, "-- Нет изменений для применения\n");
            return;
        }
        
        File::append($path, "-- Всего изменений: " . count($this->sqlStatements) . "\n\n");
        File::append($path, "START TRANSACTION;\n\n");
        
        foreach ($this->sqlStatements as $sql) {
            File::append($path, $sql . "\n\n");
        }
        
        File::append($path, "COMMIT;\n");
    }
    
    /**
     * Строит INSERT SQL для новой SEO записи
     */
    protected function buildInsertSql(int $postId, array $seoData): string
    {
        $now = now()->format('Y-m-d H:i:s');
        
        $keywords = $seoData['seo_keywords'] ?? '';
        if (is_array($keywords)) {
            $keywords = json_encode($keywords, JSON_UNESCAPED_UNICODE);
        }
        
        $focusKeyword = $seoData['focus_keyword'] ?? '';
        if (!empty($focusKeyword)) {
            $focusKeyword = json_encode([$focusKeyword], JSON_UNESCAPED_UNICODE);
        }
        
        $sql = "-- Post ID: {$postId}\n";
        $sql .= "INSERT INTO post_seo (post_id, seo_title, seo_description, seo_keywords, focus_keywords, og_title, og_description, og_image, twitter_title, twitter_description, twitter_image, seo_score, created_at, updated_at) VALUES (\n";
        $sql .= "    {$postId},\n";
        $sql .= "    " . $this->escapeSql($seoData['seo_title'] ?? '') . ",\n";
        $sql .= "    " . $this->escapeSql($seoData['seo_description'] ?? '') . ",\n";
        $sql .= "    " . $this->escapeSql($keywords) . ",\n";
        $sql .= "    " . $this->escapeSql($focusKeyword) . ",\n";
        $sql .= "    " . $this->escapeSql($seoData['og_title'] ?? '') . ",\n";
        $sql .= "    " . $this->escapeSql($seoData['og_description'] ?? '') . ",\n";
        $sql .= "    " . $this->escapeSql($seoData['og_image'] ?? '') . ",\n";
        $sql .= "    " . $this->escapeSql($seoData['twitter_title'] ?? '') . ",\n";
        $sql .= "    " . $this->escapeSql($seoData['twitter_description'] ?? '') . ",\n";
        $sql .= "    " . $this->escapeSql($seoData['twitter_image'] ?? '') . ",\n";
        $sql .= "    100,\n";
        $sql .= "    '{$now}',\n";
        $sql .= "    '{$now}'\n";
        $sql .= ");";
        
        return $sql;
    }
    
    /**
     * Строит UPDATE SQL для существующей SEO записи
     */
    protected function buildUpdateSql(int $seoId, int $postId, array $seoData): string
    {
        $now = now()->format('Y-m-d H:i:s');
        
        $keywords = $seoData['seo_keywords'] ?? '';
        if (is_array($keywords)) {
            $keywords = json_encode($keywords, JSON_UNESCAPED_UNICODE);
        }
        
        $focusKeyword = $seoData['focus_keyword'] ?? '';
        if (!empty($focusKeyword)) {
            $focusKeyword = json_encode([$focusKeyword], JSON_UNESCAPED_UNICODE);
        }
        
        $sql = "-- Post ID: {$postId}, SEO ID: {$seoId}\n";
        $sql .= "UPDATE post_seo SET\n";
        $sql .= "    seo_title = " . $this->escapeSql($seoData['seo_title'] ?? '') . ",\n";
        $sql .= "    seo_description = " . $this->escapeSql($seoData['seo_description'] ?? '') . ",\n";
        $sql .= "    seo_keywords = " . $this->escapeSql($keywords) . ",\n";
        $sql .= "    focus_keywords = " . $this->escapeSql($focusKeyword) . ",\n";
        $sql .= "    og_title = " . $this->escapeSql($seoData['og_title'] ?? '') . ",\n";
        $sql .= "    og_description = " . $this->escapeSql($seoData['og_description'] ?? '') . ",\n";
        $sql .= "    og_image = " . $this->escapeSql($seoData['og_image'] ?? '') . ",\n";
        $sql .= "    twitter_title = " . $this->escapeSql($seoData['twitter_title'] ?? '') . ",\n";
        $sql .= "    twitter_description = " . $this->escapeSql($seoData['twitter_description'] ?? '') . ",\n";
        $sql .= "    twitter_image = " . $this->escapeSql($seoData['twitter_image'] ?? '') . ",\n";
        $sql .= "    seo_score = 100,\n";
        $sql .= "    updated_at = '{$now}'\n";
        $sql .= "WHERE id = {$seoId};";
        
        return $sql;
    }
    
    /**
     * Экранирует строку для SQL
     */
    protected function escapeSql(?string $value): string
    {
        if (empty($value)) {
            return 'NULL';
        }
        
        $escaped = str_replace("'", "''", $value);
        $escaped = str_replace("\\", "\\\\", $escaped);
        
        return "'{$escaped}'";
    }
    
    /**
     * Выводит итоговый отчёт
     */
    protected function printReport(string $outputFile): void
    {
        $this->info('📊 ИТОГОВЫЙ ОТЧЁТ');
        $this->info('=' . str_repeat('=', 50));
        $this->info("✅ Проанализировано статей: {$this->analyzed}");
        $this->info("⚠️ Требуют исправления: {$this->needsFix}");
        $this->info("🤖 Перегенерировано через AI: {$this->regenerated}");
        $this->newLine();
        $this->info("📝 SQL-файл сохранён: {$outputFile}");
        
        if (!empty($this->issues)) {
            $this->newLine();
            $this->info('📋 Топ-10 проблемных статей:');
            
            $topIssues = array_slice($this->issues, 0, 10, true);
            
            foreach ($topIssues as $postId => $issues) {
                $this->warn("  Post #{$postId}:");
                foreach ($issues as $issue) {
                    $this->line("    {$issue}");
                }
            }
        }
        
        $this->newLine();
        $this->info('💡 Применить изменения на сервере:');
        $this->line("   mysql -u USER -p DATABASE < {$outputFile}");
    }
}
