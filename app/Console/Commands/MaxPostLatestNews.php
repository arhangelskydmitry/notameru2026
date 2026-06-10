<?php

namespace App\Console\Commands;

use App\Models\WordPress\Post;
use App\Services\MaxBotService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use RuntimeException;

class MaxPostLatestNews extends Command
{
    protected $signature = 'max:post-latest
                            {--limit=5 : How many latest published posts to scan}
                            {--ascending : Post oldest-to-newest within selected latest set}
                            {--force : Re-send even if already sent}
                            {--dry-run : Do not send, only print candidates}';

    protected $description = 'Posts latest published news to MAX group';

    public function handle(MaxBotService $maxBotService): int
    {
        if (! $maxBotService->isConfigured()) {
            $this->error('MAX is not configured. Set MAX_BOT_TOKEN and MAX_CHAT_ID in .env');
            return self::FAILURE;
        }

        $limit = max(1, (int) $this->option('limit'));
        $ascending = (bool) $this->option('ascending');
        $force = (bool) $this->option('force');
        $dryRun = (bool) $this->option('dry-run');
        $now = Carbon::now();

        $posts = Post::query()
            ->publiclyVisible()
            ->where('post_date', '<=', $now)
            ->when(! $force, function ($query) {
                $query
                    ->whereDoesntHave('meta', function ($metaQuery) {
                        $metaQuery
                            ->where('meta_key', '_max_posted_at')
                            ->where('meta_value', '!=', '');
                    })
                    ->whereDoesntHave('meta', function ($metaQuery) {
                        $metaQuery
                            ->where('meta_key', '_max_posted_message_id')
                            ->where('meta_value', '!=', '');
                    });
            })
            ->orderByDesc('post_date')
            ->limit($limit)
            ->get();

        if ($ascending) {
            $posts = $posts->sortBy('post_date')->values();
        }

        if ($posts->isEmpty()) {
            $this->warn('No posts found.');
            return self::SUCCESS;
        }

        $sent = 0;
        $skipped = 0;

        foreach ($posts as $post) {
            $alreadySentAt = (string) $post->getMeta('_max_posted_at', '');
            $alreadySentMessageId = (string) $post->getMeta('_max_posted_message_id', '');

            if (! $force && ($alreadySentAt !== '' || $alreadySentMessageId !== '')) {
                $skipped++;
                $this->line("SKIP #{$post->ID} {$post->post_name} (already sent)");
                continue;
            }

            $url = rtrim((string) config('app.url'), '/') . '/' . ltrim((string) $post->post_name, '/');
            $title = trim((string) $post->post_title);
            $message = $this->buildMessage($title, $url);

            if ($dryRun) {
                $this->line("DRY-RUN #{$post->ID} {$post->post_name}");
                $this->line($message);
                $this->newLine();
                continue;
            }

            try {
                $response = $maxBotService->sendMessageToConfiguredChat($message);
            } catch (RuntimeException $e) {
                $this->error("FAIL #{$post->ID} {$post->post_name}: {$e->getMessage()}");
                continue;
            }

            $messageId = (string) data_get($response, 'message.body.mid', data_get($response, 'message.mid', ''));

            $post->setMeta('_max_posted_at', Carbon::now()->toDateTimeString());
            if ($messageId !== '') {
                $post->setMeta('_max_posted_message_id', $messageId);
            }

            $sent++;
            $this->info("SENT #{$post->ID} {$post->post_name}");
        }

        $this->newLine();
        $this->info("Done. sent={$sent}, skipped={$skipped}, scanned={$posts->count()}");

        return self::SUCCESS;
    }

    private function buildMessage(string $title, string $url): string
    {
        return "📰 {$title}\n\n{$url}";
    }
}
