<?php

namespace App\Services;

class DashboardDataService
{
    private array $instagram = [];
    private array $facebook  = [];
    private array $tiktok    = [];
    private array $twitter   = [];

    private array $normalized = [];

    public function __construct()
    {
        $this->loadAll();
        $this->normalized = array_merge(
            $this->normalizeInstagram(),
            $this->normalizeFacebook(),
            $this->normalizeTikTok(),
            $this->normalizeTwitter()
        );
    }

    // ──────────────────────────────────────────
    // Data loading
    // ──────────────────────────────────────────

    private function loadJson(string $filename): array
    {
        $raw     = file_get_contents(storage_path("app/data/{$filename}"));
        $decoded = json_decode($raw, true);
        if (is_string($decoded)) {
            $decoded = json_decode($decoded, true);
        }
        if (!is_array($decoded)) {
            $decoded = [];
        }
        foreach (['data', 'items', 'collector', 'posts', 'results'] as $wk) {
            if (isset($decoded[$wk]) && is_array($decoded[$wk])) {
                $decoded = $decoded[$wk];
                break;
            }
        }
        return $decoded;
    }

    private function loadAll(): void
    {
        $this->instagram = $this->loadJson('instagram.json');
        $this->facebook  = $this->loadJson('facebook.json');
        $this->tiktok    = $this->loadJson('tiktok.json');
        $this->twitter   = $this->loadJson('twitter.json');
    }

    // ──────────────────────────────────────────
    // Python-dict string parser
    // ──────────────────────────────────────────

    private function parsePythonDict(string $str): array
    {
        if (empty($str) || in_array(trim($str), ['{}', '[]', ''], true)) {
            return [];
        }

        $str = preg_replace('/\bNone\b/', 'null', $str);
        $str = preg_replace('/\bTrue\b/', 'true', $str);
        $str = preg_replace('/\bFalse\b/', 'false', $str);

        // Replace single-quote string delimiters with double quotes,
        // being careful not to replace apostrophes inside words.
        $str = preg_replace_callback(
            "/'((?:[^'\\\\]|\\\\.)*)'/",
            fn ($m) => '"' . str_replace('"', '\\"', $m[1]) . '"',
            $str
        );

        $result = json_decode($str, true);
        return is_array($result) ? $result : [];
    }

    /**
     * Try json_decode first; fall back to parsePythonDict for Python-style strings.
     */
    private function decodeField(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }
        if (!is_string($value) || empty($value)) {
            return [];
        }
        $decoded = json_decode($value, true);
        if (is_array($decoded)) {
            return $decoded;
        }
        return $this->parsePythonDict($value);
    }

    // ──────────────────────────────────────────
    // Sentiment derivation
    // ──────────────────────────────────────────

    private function deriveSentimentTikTok(string $text, array $hashtags): string
    {
        $content  = strtolower($text . ' ' . implode(' ', $hashtags));
        $negWords = ['banjir', 'bencana', 'korban', 'rusak', 'sedih', 'parah'];
        $posWords = ['bagus', 'keren', 'sukses', 'senang', 'indah'];

        $neg = 0;
        $pos = 0;
        foreach ($negWords as $w) {
            if (str_contains($content, $w)) {
                $neg++;
            }
        }
        foreach ($posWords as $w) {
            if (str_contains($content, $w)) {
                $pos++;
            }
        }

        if ($neg > $pos) {
            return 'negative';
        }
        if ($pos > $neg) {
            return 'positive';
        }
        return 'neutral';
    }

    private function deriveSentimentTwitter(string $fullText, array $hashtags): string
    {
        $content  = strtolower($fullText . ' ' . implode(' ', $hashtags));
        $negWords = ['turun', 'anjlok', 'rugi', 'crash'];
        $posWords = ['naik', 'cuan', 'untung', 'bagus'];

        $neg = 0;
        $pos = 0;
        foreach ($negWords as $w) {
            if (str_contains($content, $w)) {
                $neg++;
            }
        }
        foreach ($posWords as $w) {
            if (str_contains($content, $w)) {
                $pos++;
            }
        }

        if ($neg > $pos) {
            return 'negative';
        }
        if ($pos > $neg) {
            return 'positive';
        }
        return 'neutral';
    }

    private function deriveSentimentFacebook(array $reactions): string
    {
        $total = array_sum(array_values($reactions));
        if ($total === 0) {
            return 'neutral';
        }
        $neg = ($reactions['angry'] ?? 0) + ($reactions['sad'] ?? 0);
        $pos = ($reactions['like'] ?? 0) + ($reactions['love'] ?? 0) + ($reactions['wow'] ?? 0);

        if ($neg > $total * 0.5) {
            return 'negative';
        }
        if ($pos > $total * 0.5) {
            return 'positive';
        }
        return 'neutral';
    }

    private function deriveSentimentInstagram(string $searchTerm, float $postsCount): string
    {
        $term        = strtolower($searchTerm);
        $negKeywords = ['banjir', 'bencana', 'korban', 'rusak', 'sedih', 'parah', 'keluhan', 'masalah', 'buruk'];
        $posKeywords = ['bagus', 'keren', 'sukses', 'senang', 'indah', 'viral', 'trending', 'love', 'beauty', 'care', 'skin', 'fashion'];

        foreach ($negKeywords as $w) {
            if (str_contains($term, $w)) {
                return 'negative';
            }
        }
        foreach ($posKeywords as $w) {
            if (str_contains($term, $w)) {
                return 'positive';
            }
        }

        if ($postsCount >= 1_000_000) {
            return 'positive';
        }
        if ($postsCount >= 100_000) {
            return 'neutral';
        }
        return 'neutral';
    }

    // ──────────────────────────────────────────
    // Region extraction
    // ──────────────────────────────────────────

    private function extractRegionFromText(string $text): string
    {
        static $provinces = [
            'Aceh', 'Sumatera Utara', 'Sumatera Barat', 'Riau', 'Jambi',
            'Sumatera Selatan', 'Bengkulu', 'Lampung', 'Bangka Belitung',
            'Kepulauan Riau', 'DKI Jakarta', 'Jakarta', 'Jawa Barat',
            'Jawa Tengah', 'DI Yogyakarta', 'Yogyakarta', 'Jawa Timur',
            'Banten', 'Bali', 'Nusa Tenggara Barat', 'Nusa Tenggara Timur',
            'Kalimantan Barat', 'Kalimantan Tengah', 'Kalimantan Selatan',
            'Kalimantan Timur', 'Kalimantan Utara', 'Sulawesi Utara',
            'Sulawesi Tengah', 'Sulawesi Selatan', 'Sulawesi Tenggara',
            'Gorontalo', 'Sulawesi Barat', 'Maluku', 'Maluku Utara',
            'Papua Barat', 'Papua',
        ];

        foreach ($provinces as $province) {
            if (stripos($text, $province) !== false) {
                return $province;
            }
        }
        return '';
    }

    // ──────────────────────────────────────────
    // Normalizers
    // ──────────────────────────────────────────

    private function normalizeInstagram(): array
    {
        $result = [];

        foreach ($this->instagram as $record) {
            if (!is_array($record)) {
                continue;
            }

            // Collect hashtags from all related-hash fields
            $hashtags = [];
            foreach (['average', 'rare', 'relatedFrequent', 'relatedAverage', 'relatedRare', 'related'] as $field) {
                $h = $record[$field] ?? [];
                if (is_string($h)) {
                    $h = json_decode($h, true) ?? $this->parsePythonDict($h);
                }
                if (!is_array($h)) {
                    $h = [];
                }
                foreach ($h as $item) {
                    if (is_array($item) && isset($item['hash'])) {
                        $hashtags[] = ltrim($item['hash'], '#');
                    } elseif (is_string($item)) {
                        $hashtags[] = ltrim($item, '#');
                    }
                }
            }
            if (!empty($record['name'])) {
                $hashtags[] = (string) $record['name'];
            }
            $hashtags = array_values(array_unique(array_filter($hashtags)));

            $searchTerm = (string) ($record['searchTerm'] ?? '');
            $postsCount = (float) ($record['postsCount'] ?? 0);
            $sentiment  = $this->deriveSentimentInstagram($searchTerm, $postsCount);

            $result[] = [
                'id'        => (string) ($record['id'] ?? $record['name'] ?? uniqid('ig_')),
                'platform'  => 'instagram',
                'text'      => trim('Hashtag: #' . ($record['name'] ?? '') . ' — search: ' . $searchTerm),
                'author'    => $searchTerm,
                'date'      => '2026-01-01',
                'sentiment' => $sentiment,
                'region'    => '',
                'likes'     => 0,
                'comments'  => 0,
                'shares'    => 0,
                'views'     => (int) $postsCount,
                'hashtags'  => $hashtags,
                'url'       => (string) ($record['url'] ?? ''),
            ];
        }

        return $result;
    }

    private function normalizeFacebook(): array
    {
        $result = [];

        foreach ($this->facebook as $record) {
            if (!is_array($record)) {
                continue;
            }

            // Author
            $authorRaw  = $record['author'] ?? '';
            $authorData = $this->decodeField($authorRaw);
            $author     = is_array($authorData) ? (string) ($authorData['name'] ?? '') : (string) $authorRaw;

            // Reactions
            $reactionsRaw  = $record['reactions'] ?? '';
            $reactionsData = $this->decodeField($reactionsRaw);
            if (!is_array($reactionsData)) {
                $reactionsData = [];
            }
            $sentiment = $this->deriveSentimentFacebook($reactionsData);

            // Hashtags from message
            $message = (string) ($record['message'] ?? '');
            preg_match_all('/#(\w+)/u', $message, $matches);
            $hashtags = $matches[1] ?? [];

            // Date from Unix timestamp
            $ts   = (int) ($record['timestamp'] ?? 0);
            $date = $ts > 0 ? date('Y-m-d', $ts) : '2026-01-01';

            // Region from message text
            $region = $this->extractRegionFromText($message);

            $result[] = [
                'id'        => (string) ($record['post_id'] ?? uniqid('fb_')),
                'platform'  => 'facebook',
                'text'      => $message,
                'author'    => $author,
                'date'      => $date,
                'sentiment' => $sentiment,
                'region'    => $region,
                'likes'     => (int) ($reactionsData['like'] ?? $record['reactions_count'] ?? 0),
                'comments'  => (int) ($record['comments_count'] ?? 0),
                'shares'    => (int) ($record['reshare_count'] ?? 0),
                'views'     => 0,
                'hashtags'  => $hashtags,
                'url'       => (string) ($record['url'] ?? ''),
            ];
        }

        return $result;
    }

    private function normalizeTikTok(): array
    {
        $result = [];

        foreach ($this->tiktok as $record) {
            if (!is_array($record)) {
                continue;
            }

            // Author
            $authorRaw  = $record['authorMeta'] ?? '';
            $authorData = $this->decodeField($authorRaw);
            $author     = is_array($authorData)
                ? (string) ($authorData['nickName'] ?? $authorData['name'] ?? '')
                : '';

            // Hashtags
            $h = $record['hashtags'] ?? [];
            if (is_string($h)) {
                $h = json_decode($h, true) ?? $this->parsePythonDict($h);
            }
            if (!is_array($h)) {
                $h = [];
            }
            $hashtags = [];
            foreach ($h as $item) {
                if (is_array($item) && isset($item['name'])) {
                    $hashtags[] = (string) $item['name'];
                } elseif (is_string($item)) {
                    $hashtags[] = ltrim($item, '#');
                }
            }

            $text      = (string) ($record['text'] ?? '');
            $sentiment = $this->deriveSentimentTikTok($text, $hashtags);

            // Region from locationMeta then text
            $locationRaw = $record['locationMeta'] ?? '';
            $region      = '';
            if (is_string($locationRaw) && strlen($locationRaw) > 2) {
                $locationData = $this->parsePythonDict($locationRaw);
                $region       = is_array($locationData)
                    ? (string) ($locationData['city'] ?? $locationData['country'] ?? '')
                    : '';
            }
            if (empty($region)) {
                $region = $this->extractRegionFromText($text);
            }

            // Date from ISO string
            $isoDate = (string) ($record['createTimeISO'] ?? '');
            $date    = strlen($isoDate) >= 10 ? substr($isoDate, 0, 10) : '2026-01-01';

            $result[] = [
                'id'        => (string) ($record['id'] ?? uniqid('tt_')),
                'platform'  => 'tiktok',
                'text'      => $text,
                'author'    => $author,
                'date'      => $date,
                'sentiment' => $sentiment,
                'region'    => $region,
                'likes'     => (int) ($record['diggCount'] ?? 0),
                'comments'  => (int) ($record['commentCount'] ?? 0),
                'shares'    => (int) ($record['shareCount'] ?? 0),
                'views'     => (int) ($record['playCount'] ?? 0),
                'hashtags'  => $hashtags,
                'url'       => (string) ($record['webVideoUrl'] ?? ''),
            ];
        }

        return $result;
    }

    private function normalizeTwitter(): array
    {
        $result = [];

        foreach ($this->twitter as $record) {
            if (!is_array($record)) {
                continue;
            }

            // Author
            $authorRaw  = $record['author'] ?? '';
            $authorData = $this->decodeField($authorRaw);
            $author     = is_array($authorData)
                ? (string) ($authorData['name'] ?? $authorData['userName'] ?? '')
                : (string) $authorRaw;

            // Hashtags from entities
            $entitiesRaw  = $record['entities'] ?? '';
            $entitiesData = $this->decodeField($entitiesRaw);
            $h            = is_array($entitiesData) ? ($entitiesData['hashtags'] ?? []) : [];
            if (is_string($h)) {
                $h = json_decode($h, true) ?? [];
            }
            if (!is_array($h)) {
                $h = [];
            }
            $hashtags = [];
            foreach ($h as $item) {
                if (is_array($item)) {
                    $tag = $item['tag'] ?? $item['text'] ?? '';
                    if (!empty($tag)) {
                        $hashtags[] = (string) $tag;
                    }
                } elseif (is_string($item)) {
                    $hashtags[] = ltrim($item, '#');
                }
            }

            $fullText  = (string) ($record['fullText'] ?? $record['text'] ?? '');
            $sentiment = $this->deriveSentimentTwitter($fullText, $hashtags);

            // Date from "Fri Feb 06 03:05:19 +0000 2026"
            $createdAt = (string) ($record['createdAt'] ?? '');
            $ts        = $createdAt ? strtotime($createdAt) : 0;
            $date      = $ts ? date('Y-m-d', $ts) : '2026-01-01';

            // Region from place then text
            $placeRaw = $record['place'] ?? '';
            $region   = '';
            if (is_string($placeRaw) && strlen($placeRaw) > 2) {
                $placeData = $this->parsePythonDict($placeRaw);
                if (is_array($placeData)) {
                    $region = (string) ($placeData['full_name'] ?? $placeData['name'] ?? $placeData['country'] ?? '');
                }
            }
            if (empty($region)) {
                $region = $this->extractRegionFromText($fullText);
            }

            $result[] = [
                'id'        => (string) ($record['id'] ?? uniqid('tw_')),
                'platform'  => 'twitter',
                'text'      => $fullText,
                'author'    => $author,
                'date'      => $date,
                'sentiment' => $sentiment,
                'region'    => $region,
                'likes'     => (int) ($record['likeCount'] ?? 0),
                'comments'  => (int) ($record['replyCount'] ?? 0),
                'shares'    => (int) ($record['retweetCount'] ?? 0),
                'views'     => (int) ((float) ($record['viewCount'] ?? 0)),
                'hashtags'  => $hashtags,
                'url'       => (string) ($record['url'] ?? $record['twitterUrl'] ?? ''),
            ];
        }

        return $result;
    }

    // ──────────────────────────────────────────
    // Public API
    // ──────────────────────────────────────────

    public function getFilteredData(array $filters): array
    {
        $data = $this->normalized;

        if (!empty($filters['start_date'])) {
            $data = array_filter($data, fn ($r) => $r['date'] >= $filters['start_date']);
        }
        if (!empty($filters['end_date'])) {
            $data = array_filter($data, fn ($r) => $r['date'] <= $filters['end_date']);
        }
        if (!empty($filters['platform'])) {
            $data = array_filter($data, fn ($r) => $r['platform'] === $filters['platform']);
        }
        if (!empty($filters['region'])) {
            $data = array_filter($data, fn ($r) => stripos($r['region'], $filters['region']) !== false);
        }
        if (!empty($filters['keyword'])) {
            $kw   = $filters['keyword'];
            $data = array_filter($data, function ($r) use ($kw) {
                if (stripos($r['text'], $kw) !== false) {
                    return true;
                }
                if (stripos($r['author'], $kw) !== false) {
                    return true;
                }
                foreach ($r['hashtags'] as $tag) {
                    if (stripos($tag, $kw) !== false) {
                        return true;
                    }
                }
                return false;
            });
        }

        return array_values($data);
    }

    public function getStatistics(array $items): array
    {
        $total    = count($items);
        $positive = count(array_filter($items, fn ($r) => $r['sentiment'] === 'positive'));
        $neutral  = count(array_filter($items, fn ($r) => $r['sentiment'] === 'neutral'));
        $negative = count(array_filter($items, fn ($r) => $r['sentiment'] === 'negative'));

        $netSentiment = $total > 0 ? round(($positive - $negative) / $total * 100, 2) : 0;
        $sentimentPct = [
            'positive' => $total > 0 ? round($positive / $total * 100, 2) : 0,
            'neutral'  => $total > 0 ? round($neutral  / $total * 100, 2) : 0,
            'negative' => $total > 0 ? round($negative / $total * 100, 2) : 0,
        ];

        // Trend: group by date
        $trendMap = [];
        foreach ($items as $r) {
            $d = $r['date'];
            if (!isset($trendMap[$d])) {
                $trendMap[$d] = ['date' => $d, 'positive' => 0, 'neutral' => 0, 'negative' => 0, 'total' => 0];
            }
            $trendMap[$d][$r['sentiment']]++;
            $trendMap[$d]['total']++;
        }
        ksort($trendMap);
        $trend = array_values($trendMap);

        // Platform sentiment
        $platformSentimentMap = [];
        foreach ($items as $r) {
            $p = $r['platform'];
            if (!isset($platformSentimentMap[$p])) {
                $platformSentimentMap[$p] = ['platform' => $p, 'positive' => 0, 'neutral' => 0, 'negative' => 0, 'total' => 0];
            }
            $platformSentimentMap[$p][$r['sentiment']]++;
            $platformSentimentMap[$p]['total']++;
        }
        $platformSentiment = array_values($platformSentimentMap);

        // Mention by platform (sorted desc)
        $platformCountMap = [];
        foreach ($items as $r) {
            $platformCountMap[$r['platform']] = ($platformCountMap[$r['platform']] ?? 0) + 1;
        }
        arsort($platformCountMap);
        $mentionByPlatform = array_values(array_map(
            fn ($p, $c) => ['platform' => $p, 'count' => $c],
            array_keys($platformCountMap),
            $platformCountMap
        ));

        // Mention by province (top 20, sorted desc, skip empty)
        $provinceMap = [];
        foreach ($items as $r) {
            if (empty($r['region'])) {
                continue;
            }
            $provinceMap[$r['region']] = ($provinceMap[$r['region']] ?? 0) + 1;
        }
        arsort($provinceMap);
        $mentionByProvince = array_slice(array_values(array_map(
            fn ($n, $v) => ['province' => $n, 'count' => $v],
            array_keys($provinceMap),
            $provinceMap
        )), 0, 20);

        // Top topics (top 20)
        $topicMap = [];
        foreach ($items as $r) {
            foreach ($r['hashtags'] as $tag) {
                if (empty($tag)) {
                    continue;
                }
                $topicMap[$tag] = ($topicMap[$tag] ?? 0) + 1;
            }
        }
        arsort($topicMap);
        $topTopics = array_slice(array_values(array_map(
            fn ($t, $c) => ['topic' => $t, 'count' => $c],
            array_keys($topicMap),
            $topicMap
        )), 0, 20);

        // Engagement by platform
        $engMap = [];
        foreach ($items as $r) {
            $p = $r['platform'];
            if (!isset($engMap[$p])) {
                $engMap[$p] = ['platform' => $p, 'likes' => 0, 'comments' => 0, 'shares' => 0, 'views' => 0, 'total' => 0];
            }
            $engMap[$p]['likes']    += $r['likes'];
            $engMap[$p]['comments'] += $r['comments'];
            $engMap[$p]['shares']   += $r['shares'];
            $engMap[$p]['views']    += $r['views'];
            $engMap[$p]['total']++;
        }
        $engagement = array_values($engMap);

        // Word frequency helper
        $stopwords = array_flip([
            'yang','dan','di','ke','dari','untuk','ini','itu','dengan','adalah','pada','atau','juga',
            'saya','kita','kami','mereka','akan','sudah','ada',
            'the','is','in','on','at','to','of','a','an','and','or','for','not','be','as','by',
            'it','was','are','has','have','this','that','with','http','https','rt',
        ]);

        $wordFreq = function (array $records) use ($stopwords): array {
            $counts = [];
            foreach ($records as $r) {
                $cleaned = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $r['text']);
                $words   = preg_split('/\s+/u', mb_strtolower($cleaned));
                foreach ($words as $w) {
                    $w = trim($w);
                    if (mb_strlen($w) < 3 || isset($stopwords[$w])) {
                        continue;
                    }
                    $counts[$w] = ($counts[$w] ?? 0) + 1;
                }
            }
            arsort($counts);
            return array_slice(array_values(array_map(
                fn ($w, $c) => ['word' => $w, 'count' => $c],
                array_keys($counts),
                $counts
            )), 0, 30);
        };

        $negativeRecords = array_values(array_filter($items, fn ($r) => $r['sentiment'] === 'negative'));
        $positiveRecords = array_values(array_filter($items, fn ($r) => $r['sentiment'] === 'positive'));

        return [
            'total'                => $total,
            'positive'             => $positive,
            'neutral'              => $neutral,
            'negative'             => $negative,
            'net_sentiment'        => $netSentiment,
            'sentiment_percentage' => $sentimentPct,
            'trend'                => $trend,
            'platform_sentiment'   => $platformSentiment,
            'mention_by_platform'  => $mentionByPlatform,
            'mention_by_province'  => $mentionByProvince,
            'top_topics'           => $topTopics,
            'engagement'           => $engagement,
            'negative_words'       => $wordFreq($negativeRecords),
            'positive_words'       => $wordFreq($positiveRecords),
        ];
    }

    public function getAvailableFilters(array $allItems): array
    {
        $platforms = [];
        $regions   = [];

        foreach ($allItems as $r) {
            $platforms[] = $r['platform'];
            if (!empty($r['region'])) {
                $regions[] = $r['region'];
            }
        }

        $platforms = array_values(array_unique($platforms));
        $regions   = array_values(array_unique($regions));
        sort($platforms);
        sort($regions);

        return ['platforms' => $platforms, 'regions' => $regions];
    }
}
