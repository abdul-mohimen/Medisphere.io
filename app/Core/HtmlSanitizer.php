<?php
namespace App\Core;

class HtmlSanitizer
{
    public static function clean(string $html): string
    {
        $allowedTags = '<p><br><strong><b><em><i><u><ul><ol><li><h2><h3><h4><blockquote><a><span>';
        $html = trim($html);
        $html = strip_tags($html, $allowedTags);
        $html = preg_replace('/on\w+\s*=\s*(["\']).*?\1/i', '', $html) ?? $html;
        $html = preg_replace('/javascript:/i', '', $html) ?? $html;
        $html = preg_replace('/data:text\/html/i', '', $html) ?? $html;
        $html = preg_replace('/<(script|style)[^>]*>.*?<\/\1>/is', '', $html) ?? $html;
        return $html;
    }
}
