<?php
require_once __DIR__.'/helpers.php';

function youtube_search(string $q): array {
    $c = cfg();
    if (!is_configured($c['youtube_api_key'])) return [];

    $p = [
        'part' => 'snippet',
        'q' => '"' . $q . '"',
        'type' => 'video',
        'order' => 'date',
        'maxResults' => 25,
        'publishedAfter' => gmdate('c', time() - 30 * 86400),
        'videoEmbeddable' => 'true',
        'regionCode' => 'BR',
        'relevanceLanguage' => 'pt',
        'key' => $c['youtube_api_key']
    ];

    $d = http_get_json(
        'https://www.googleapis.com/youtube/v3/search?' . http_build_query($p)
    );

    $out = [];

    foreach (($d['items'] ?? []) as $x) {
        $id = $x['id']['videoId'] ?? null;
        if (!$id) continue;

        $s = $x['snippet'] ?? [];
        $title = html_entity_decode(
            $s['title'] ?? '',
            ENT_QUOTES | ENT_HTML5,
            'UTF-8'
        );
        $description = html_entity_decode(
            $s['description'] ?? '',
            ENT_QUOTES | ENT_HTML5,
            'UTF-8'
        );

        /*
         * FILTRO RÍGIDO:
         * O conteúdo só entra se título ou descrição contiver
         * exatamente "Carmesim Roleplay" ou "Condado Carmesim".
         */
        $text = mb_strtolower($title . ' ' . $description, 'UTF-8');

        $matchesCarmesimRoleplay = mb_strpos(
            $text,
            mb_strtolower('Carmesim Roleplay', 'UTF-8')
        ) !== false;

        $matchesCondadoCarmesim = mb_strpos(
            $text,
            mb_strtolower('Condado Carmesim', 'UTF-8')
        ) !== false;

        if (!$matchesCarmesimRoleplay && !$matchesCondadoCarmesim) {
            continue;
        }

        $creator = $s['channelTitle'] ?? 'Criador';

        $out[] = [
            'platform' => 'youtube',
            'creator' => $creator,
            'creator_key' => normalize_creator($creator),
            'title' => $title,
            'description' => $description,
            'category' => 'YouTube',
            'views' => null,
            'url' => 'https://www.youtube.com/watch?v=' . rawurlencode($id),
            'embedUrl' => 'https://www.youtube.com/embed/' . rawurlencode($id) . '?rel=0',
            'live' => false,
            'published_at' => $s['publishedAt'] ?? null
        ];
    }

    return $out;
}
