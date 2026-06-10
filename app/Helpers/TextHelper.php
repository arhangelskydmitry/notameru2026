<?php

namespace App\Helpers;

class TextHelper
{
    /**
     * Плюрализация русских слов
     * 
     * @param int $number Число
     * @param string $one Форма для 1 (статья)
     * @param string $few Форма для 2-4 (статьи)
     * @param string $many Форма для 5+ (статей)
     * @return string
     */
    public static function pluralize(int $number, string $one, string $few, string $many): string
    {
        $number = abs($number) % 100;
        $lastDigit = $number % 10;
        
        if ($number > 10 && $number < 20) {
            return $many;
        }
        
        if ($lastDigit > 1 && $lastDigit < 5) {
            return $few;
        }
        
        if ($lastDigit == 1) {
            return $one;
        }
        
        return $many;
    }
    
    /**
     * Плюрализация с числом
     * 
     * @param int $number
     * @param string $one
     * @param string $few
     * @param string $many
     * @return string
     */
    public static function pluralizeWithNumber(int $number, string $one, string $few, string $many): string
    {
        return $number . ' ' . self::pluralize($number, $one, $few, $many);
    }
    
    /**
     * Обрезка текста с сохранением целых слов
     * 
     * @param string $text
     * @param int $length
     * @param string $suffix
     * @return string
     */
    public static function excerpt(string $text, int $length = 150, string $suffix = '...'): string
    {
        $text = strip_tags($text);
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        
        if (mb_strlen($text) <= $length) {
            return $text;
        }
        
        $text = mb_substr($text, 0, $length);
        $lastSpace = mb_strrpos($text, ' ');
        
        if ($lastSpace !== false) {
            $text = mb_substr($text, 0, $lastSpace);
        }
        
        return $text . $suffix;
    }
    
    /**
     * Форматирование числа с разделителями тысяч
     * 
     * @param int|float $number
     * @param int $decimals
     * @return string
     */
    public static function formatNumber($number, int $decimals = 0): string
    {
        return number_format($number, $decimals, ',', ' ');
    }
    
    /**
     * Склонение слова "просмотр"
     * 
     * @param int $count
     * @return string
     */
    public static function viewsText(int $count): string
    {
        return self::pluralizeWithNumber($count, 'просмотр', 'просмотра', 'просмотров');
    }
    
    /**
     * Склонение слова "комментарий"
     * 
     * @param int $count
     * @return string
     */
    public static function commentsText(int $count): string
    {
        return self::pluralizeWithNumber($count, 'комментарий', 'комментария', 'комментариев');
    }
    
    /**
     * Склонение слова "статья"
     * 
     * @param int $count
     * @return string
     */
    public static function articlesText(int $count): string
    {
        return self::pluralizeWithNumber($count, 'статья', 'статьи', 'статей');
    }
}
