<?php

namespace App\Services;

use Illuminate\Support\Str;

class ArticleSummarizeService
{
    public function summarize(string $title, string $content): array
    {
        $plain = trim(strip_tags($content));
        $sentences = preg_split('/(?<=[.!?…])\s+/u', $plain, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $summaryParts = array_slice($sentences, 0, 3);
        $summary = $summaryParts ? implode(' ', $summaryParts) : Str::limit($plain, 320);

        $bullets = [];
        foreach (array_slice($sentences, 0, 6) as $sentence) {
            $line = Str::limit(trim($sentence), 120);
            if (mb_strlen($line) > 40) {
                $bullets[] = $line;
            }
            if (count($bullets) >= 4) {
                break;
            }
        }

        $words = count(preg_split('/\s+/u', $plain, -1, PREG_SPLIT_NO_EMPTY) ?: []);

        return [
            'summary' => $summary,
            'bullets' => $bullets,
            'reading_time_minutes' => max(1, (int) ceil($words / 200)),
        ];
    }
}
