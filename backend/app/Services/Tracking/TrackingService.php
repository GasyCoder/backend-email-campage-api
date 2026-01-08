<?php

namespace App\Services\Tracking;

use App\Models\Message;
use App\Models\TrackingLink;

class TrackingService
{
    public function apply(Message $message, string $html): string
    {
        $html = $this->rewriteLinks($message, $html);
        $html = $this->injectOpenPixel($message, $html);
        return $html;
    }

    private function rewriteLinks(Message $message, string $html): string
    {
        libxml_use_internal_errors(true);

        $dom = new \DOMDocument();
        $dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        $links = $dom->getElementsByTagName('a');

        $nodes = [];
        foreach ($links as $a) $nodes[] = $a;

        foreach ($nodes as $a) {
            $href = trim((string)$a->getAttribute('href'));
            if ($href === '') continue;

            if (
                str_starts_with($href, 'mailto:') ||
                str_starts_with($href, 'tel:') ||
                str_starts_with($href, '#') ||
                str_starts_with($href, 'javascript:')
            ) continue;

            // ne track pas l’unsubscribe
            if (str_contains($href, '/api/v1/u/')) continue;

            if (!preg_match('#^https?://#i', $href)) continue;

            $hash = substr(hash('sha256', $href), 0, 16);

            TrackingLink::updateOrCreate(
                ['message_id' => $message->id, 'hash' => $hash],
                ['url' => $href]
            );

            $trackUrl = rtrim(config('app.url'), '/') . "/api/v1/t/c/{$message->id}/{$hash}";
            $a->setAttribute('href', $trackUrl);
        }

        return $dom->saveHTML();
    }

    private function injectOpenPixel(Message $message, string $html): string
    {
        $sig = $this->signature($message->id);
        $pixelUrl = rtrim(config('app.url'), '/') . "/api/v1/t/o/{$message->id}/{$sig}.gif";
        $pixel = '<img src="'.$pixelUrl.'" width="1" height="1" style="display:none" alt="" />';

        if (stripos($html, '</body>') !== false) {
            return preg_replace('/<\/body>/i', $pixel.'</body>', $html, 1) ?? ($html.$pixel);
        }

        return $html . $pixel;
    }

    private function signature(int $messageId): string
    {
        return hash_hmac('sha256', (string)$messageId, (string)config('services.unsubscribe.signing_key'));
    }
}
