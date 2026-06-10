<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class LinkGorodMedia extends Command
{
    protected $signature = 'gorod:link-media
                            {source : Absolute path to gorod uploads directory}';

    protected $description = 'Link gorod-magazine uploads into notamerularavel public paths';

    public function handle(): int
    {
        $source = rtrim((string) $this->argument('source'), '/');

        if (! is_dir($source)) {
            $this->error("Uploads directory not found: {$source}");
            return self::FAILURE;
        }

        $links = [
            public_path('uploads') => $source,
            public_path('wp-content/uploads') => $source,
            public_path('imgnews') => $source,
        ];

        foreach ($links as $target => $linkSource) {
            $parentDir = dirname($target);
            if (! is_dir($parentDir)) {
                mkdir($parentDir, 0755, true);
            }

            if (is_link($target) || file_exists($target)) {
                if (is_link($target)) {
                    unlink($target);
                } else {
                    $this->warn("Skipping existing non-link path: {$target}");
                    continue;
                }
            }

            symlink($linkSource, $target);
            $this->info("Linked {$target} -> {$linkSource}");
        }

        return self::SUCCESS;
    }
}
