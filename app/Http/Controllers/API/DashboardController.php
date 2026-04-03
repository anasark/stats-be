<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\DashboardDataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardDataService $service) {}

    /**
     * @OA\Get(
     *     path="/api/dashboard",
     *     summary="Get dashboard data with optional filters",
     *     tags={"Dashboard"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="start_date", in="query", required=false, @OA\Schema(type="string", format="date"), description="Filter from date (YYYY-MM-DD)"),
     *     @OA\Parameter(name="end_date", in="query", required=false, @OA\Schema(type="string", format="date"), description="Filter to date (YYYY-MM-DD)"),
     *     @OA\Parameter(name="keyword", in="query", required=false, @OA\Schema(type="string"), description="Search keyword in content"),
     *     @OA\Parameter(name="platform", in="query", required=false, @OA\Schema(type="string", enum={"facebook","instagram","tiktok","twitter"}), description="Filter by platform"),
     *     @OA\Parameter(name="region", in="query", required=false, @OA\Schema(type="string"), description="Filter by region"),
     *     @OA\Response(
     *         response=200,
     *         description="Dashboard data",
     *         @OA\JsonContent(
     *             @OA\Property(property="filters", type="object",
     *                 @OA\Property(property="platforms", type="array", @OA\Items(type="string")),
     *                 @OA\Property(property="regions", type="array", @OA\Items(type="string"))
     *             ),
     *             @OA\Property(property="net_sentiment", type="number", example=42.5),
     *             @OA\Property(property="sentiment_percentage", type="object",
     *                 @OA\Property(property="positive", type="number"),
     *                 @OA\Property(property="neutral", type="number"),
     *                 @OA\Property(property="negative", type="number")
     *             ),
     *             @OA\Property(property="negative_words", type="array", @OA\Items(type="object",
     *                 @OA\Property(property="word", type="string"),
     *                 @OA\Property(property="count", type="integer")
     *             )),
     *             @OA\Property(property="positive_words", type="array", @OA\Items(type="object",
     *                 @OA\Property(property="word", type="string"),
     *                 @OA\Property(property="count", type="integer")
     *             )),
     *             @OA\Property(property="trend", type="array", @OA\Items(type="object",
     *                 @OA\Property(property="date", type="string", format="date"),
     *                 @OA\Property(property="positive", type="integer"),
     *                 @OA\Property(property="neutral", type="integer"),
     *                 @OA\Property(property="negative", type="integer"),
     *                 @OA\Property(property="total", type="integer")
     *             )),
     *             @OA\Property(property="platform_sentiment", type="array", @OA\Items(type="object",
     *                 @OA\Property(property="platform", type="string"),
     *                 @OA\Property(property="positive", type="integer"),
     *                 @OA\Property(property="neutral", type="integer"),
     *                 @OA\Property(property="negative", type="integer"),
     *                 @OA\Property(property="total", type="integer")
     *             )),
     *             @OA\Property(property="mention_by_platform", type="array", @OA\Items(type="object",
     *                 @OA\Property(property="platform", type="string"),
     *                 @OA\Property(property="count", type="integer")
     *             )),
     *             @OA\Property(property="mention_by_media", type="array", @OA\Items(type="object",
     *                 @OA\Property(property="media", type="string"),
     *                 @OA\Property(property="count", type="integer")
     *             )),
     *             @OA\Property(property="mention_by_province", type="array", @OA\Items(type="object",
     *                 @OA\Property(property="province", type="string"),
     *                 @OA\Property(property="count", type="integer")
     *             )),
     *             @OA\Property(property="top_topics", type="array", @OA\Items(type="object",
     *                 @OA\Property(property="topic", type="string"),
     *                 @OA\Property(property="count", type="integer")
     *             )),
     *             @OA\Property(property="engagement", type="array", @OA\Items(type="object",
     *                 @OA\Property(property="platform", type="string"),
     *                 @OA\Property(property="likes", type="integer"),
     *                 @OA\Property(property="comments", type="integer"),
     *                 @OA\Property(property="shares", type="integer"),
     *                 @OA\Property(property="views", type="integer"),
     *                 @OA\Property(property="total", type="integer")
     *             )),
     *             @OA\Property(property="table", type="array", @OA\Items(type="object",
     *                 @OA\Property(property="id", type="string"),
     *                 @OA\Property(property="platform", type="string"),
     *                 @OA\Property(property="text", type="string"),
     *                 @OA\Property(property="author", type="string"),
     *                 @OA\Property(property="date", type="string", format="date"),
     *                 @OA\Property(property="sentiment", type="string", enum={"positive","neutral","negative"}),
     *                 @OA\Property(property="region", type="string"),
     *                 @OA\Property(property="likes", type="integer"),
     *                 @OA\Property(property="comments", type="integer"),
     *                 @OA\Property(property="shares", type="integer"),
     *                 @OA\Property(property="views", type="integer"),
     *                 @OA\Property(property="hashtags", type="array", @OA\Items(type="string")),
     *                 @OA\Property(property="url", type="string")
     *             ))
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'start_date' => $request->query('start_date'),
            'end_date'   => $request->query('end_date'),
            'keyword'    => $request->query('keyword'),
            'platform'   => $request->query('platform'),
            'region'     => $request->query('region'),
        ];

        $allItems      = $this->service->getFilteredData([]);
        $filtered      = $this->service->getFilteredData($filters);
        $stats         = $this->service->getStatistics($filtered);
        $filterOptions = $this->service->getAvailableFilters($allItems);

        return response()->json([
            'filters'              => $filterOptions,
            'net_sentiment'        => $stats['net_sentiment'],
            'sentiment_percentage' => $stats['sentiment_percentage'],
            'negative_words'       => $stats['negative_words'],
            'positive_words'       => $stats['positive_words'],
            'trend'                => $stats['trend'],
            'platform_sentiment'   => $stats['platform_sentiment'],
            'mention_by_platform'  => $stats['mention_by_platform'],
            'mention_by_media'     => $stats['mention_by_media'] ?? [],
            'mention_by_province'  => $stats['mention_by_province'],
            'top_topics'           => $stats['top_topics'],
            'engagement'           => $stats['engagement'],
            'table'                => $filtered,
        ]);
    }
}

//     public function index(Request $request): JsonResponse
//     {
//         $user      = $request->user();
//         $platforms = $user->preference?->platforms ?? [];

//         if (empty($platforms)) {
//             return response()->json([
//                 'message' => 'No platforms selected.',
//                 'stats'   => [],
//             ]);
//         }

//         $stats = ApifyStat::query()
//             ->whereIn('platform', $platforms)
//             ->orderByDesc('fetched_at')
//             ->get()
//             ->groupBy('platform')
//             ->map(fn($group) => $group->first());

//         return response()->json([
//             'stats' => $stats->values(),
//         ]);
//     }

//     private function getDummyTable(): array
//     {
//         return [
//             ['date' => '2026-01-05', 'platform' => 'Facebook',    'region' => 'DKI Jakarta', 'content' => 'Brand CTX ramai dibahas publik di Jakarta',       'like' => 1244, 'comment' => 512,  'share' => 3011, 'sentiment' => 'positive'],
//             ['date' => '2026-01-08', 'platform' => 'Instagram',   'region' => 'Jawa Barat',  'content' => 'Brand CTX viral di media sosial Bandung',         'like' => 1354, 'comment' => 764,  'share' => 3021, 'sentiment' => 'positive'],
//             ['date' => '2026-01-12', 'platform' => 'TikTok',      'region' => 'Jawa Timur',  'content' => 'Kendala layanan CTX dikeluhkan pengguna Surabaya', 'like' => 1320, 'comment' => 5004, 'share' => 512,  'sentiment' => 'negative'],
//             ['date' => '2026-01-15', 'platform' => 'X (Twitter)', 'region' => 'Jawa Tengah', 'content' => 'CTX meluncurkan fitur baru monitoring sosmed',     'like' => 1355, 'comment' => 1821, 'share' => 931,  'sentiment' => 'neutral'],
//             ['date' => '2026-01-20', 'platform' => 'Online News', 'region' => 'Bali',        'content' => 'CTX Analytics dinilai ungguli kompetitor',         'like' => 2140, 'comment' => 3004, 'share' => 3004, 'sentiment' => 'positive'],
//             ['date' => '2026-01-22', 'platform' => 'Instagram',   'region' => 'Sumatera U.', 'content' => 'Pengguna CTX di Sumatera terus bertambah',         'like' => 1823, 'comment' => 2140, 'share' => 1004, 'sentiment' => 'positive'],
//             ['date' => '2026-02-03', 'platform' => 'Facebook',    'region' => 'DKI Jakarta', 'content' => 'CTX dashboard baru mendapat respons positif',      'like' => 980,  'comment' => 430,  'share' => 1200, 'sentiment' => 'positive'],
//             ['date' => '2026-02-07', 'platform' => 'TikTok',      'region' => 'Jawa Barat',  'content' => 'Review CTX oleh kreator konten Bandung',           'like' => 4500, 'comment' => 890,  'share' => 2300, 'sentiment' => 'positive'],
//             ['date' => '2026-02-10', 'platform' => 'X (Twitter)', 'region' => 'DKI Jakarta', 'content' => 'CTX lambat dikeluhkan pengguna Jakarta',           'like' => 320,  'comment' => 1200, 'share' => 450,  'sentiment' => 'negative'],
//             ['date' => '2026-02-14', 'platform' => 'Online News', 'region' => 'Jawa Timur',  'content' => 'CTX berhasil raih penghargaan startup terbaik',    'like' => 560,  'comment' => 230,  'share' => 800,  'sentiment' => 'positive'],
//             ['date' => '2026-02-18', 'platform' => 'Instagram',   'region' => 'Jawa Tengah', 'content' => 'Promo CTX menarik perhatian ribuan pengguna',      'like' => 3200, 'comment' => 1450, 'share' => 980,  'sentiment' => 'positive'],
//             ['date' => '2026-02-21', 'platform' => 'Facebook',    'region' => 'Bali',        'content' => 'CTX digunakan brand lokal di Bali untuk analitik', 'like' => 760,  'comment' => 340,  'share' => 520,  'sentiment' => 'neutral'],
//             ['date' => '2026-02-25', 'platform' => 'TikTok',      'region' => 'Sumatera U.', 'content' => 'Total kerugian pengguna akibat gangguan CTX',      'like' => 2100, 'comment' => 6700, 'share' => 1200, 'sentiment' => 'negative'],
//             ['date' => '2026-03-02', 'platform' => 'Online News', 'region' => 'DKI Jakarta', 'content' => 'CTX perluas layanan ke seluruh Indonesia',         'like' => 430,  'comment' => 120,  'share' => 670,  'sentiment' => 'positive'],
//             ['date' => '2026-03-05', 'platform' => 'X (Twitter)', 'region' => 'Jawa Barat',  'content' => 'Masalah login CTX belum terselesaikan',            'like' => 890,  'comment' => 2300, 'share' => 340,  'sentiment' => 'negative'],
//             ['date' => '2026-03-08', 'platform' => 'Instagram',   'region' => 'Jawa Timur',  'content' => 'Influencer Surabaya promosikan CTX Analytics',     'like' => 5600, 'comment' => 3200, 'share' => 1800, 'sentiment' => 'positive'],
//             ['date' => '2026-03-11', 'platform' => 'Facebook',    'region' => 'DKI Jakarta', 'content' => 'CTX update terbaru sangat membantu tim marketing',  'like' => 1100, 'comment' => 560,  'share' => 940,  'sentiment' => 'positive'],
//             ['date' => '2026-03-13', 'platform' => 'TikTok',      'region' => 'Jawa Tengah', 'content' => 'Video viral CTX ditonton jutaan orang',            'like' => 12400, 'comment' => 4500, 'share' => 8900, 'sentiment' => 'positive'],
//             ['date' => '2026-03-15', 'platform' => 'Online News', 'region' => 'Bali',        'content' => 'CTX gandeng media lokal Bali untuk monitoring',    'like' => 320,  'comment' => 145,  'share' => 430,  'sentiment' => 'neutral'],
//             ['date' => '2026-03-17', 'platform' => 'X (Twitter)', 'region' => 'Sumatera U.', 'content' => 'Pengguna CTX Sumatera laporkan bug fitur export',  'like' => 450,  'comment' => 1800, 'share' => 230,  'sentiment' => 'negative'],
//             ['date' => '2026-03-18', 'platform' => 'Instagram',   'region' => 'DKI Jakarta', 'content' => 'CTX hadir di pameran teknologi Jakarta 2026',      'like' => 2300, 'comment' => 870,  'share' => 1340, 'sentiment' => 'positive'],
//             ['date' => '2026-03-19', 'platform' => 'Facebook',    'region' => 'Jawa Barat',  'content' => 'Diskusi CTX ramai di grup Facebook Bandung',       'like' => 670,  'comment' => 890,  'share' => 540,  'sentiment' => 'neutral'],
//             ['date' => '2026-03-20', 'platform' => 'TikTok',      'region' => 'Jawa Timur',  'content' => 'Kendala nya CTX masih dikeluhkan di Jawa Timur',   'like' => 980,  'comment' => 3400, 'share' => 670,  'sentiment' => 'negative'],
//             ['date' => '2026-03-21', 'platform' => 'Online News', 'region' => 'Jawa Barat',  'content' => 'CTX raih investasi baru dari modal ventura',       'like' => 230,  'comment' => 90,   'share' => 340,  'sentiment' => 'positive'],
//             ['date' => '2026-03-22', 'platform' => 'Instagram',   'region' => 'Jawa Tengah', 'content' => 'CTX Analytics bantu UMKM Jawa Tengah berkembang',  'like' => 1890, 'comment' => 760,  'share' => 1230, 'sentiment' => 'positive'],
//             ['date' => '2026-03-22', 'platform' => 'X (Twitter)', 'region' => 'DKI Jakarta', 'content' => 'Mohon info proses langsung kendala CTX kami',      'like' => 340,  'comment' => 2100, 'share' => 180,  'sentiment' => 'negative'],
//             ['date' => '2026-03-23', 'platform' => 'Facebook',    'region' => 'Bali',        'content' => 'CTX fitur baru sangat membantu tim digital Bali',  'like' => 540,  'comment' => 230,  'share' => 670,  'sentiment' => 'positive'],
//             ['date' => '2026-03-23', 'platform' => 'TikTok',      'region' => 'DKI Jakarta', 'content' => 'Total kerugian akibat downtime CTX minggu ini',    'like' => 3400, 'comment' => 7800, 'share' => 2100, 'sentiment' => 'negative'],
//             ['date' => '2026-03-23', 'platform' => 'Online News', 'region' => 'Jawa Timur',  'content' => 'CTX resmi bermitra dengan perusahaan media besar', 'like' => 560,  'comment' => 210,  'share' => 890,  'sentiment' => 'positive'],
//             ['date' => '2026-03-23', 'platform' => 'Instagram',   'region' => 'Sumatera U.', 'content' => 'Update CTX sangat membantu monitoring sosmed',     'like' => 1200, 'comment' => 540,  'share' => 780,  'sentiment' => 'positive'],
//         ];
//     }

//     /**
//      * @OA\Get(
//      *     path="/api/dashboard",
//      *     summary="Get dashboard data with optional filters",
//      *     tags={"Dashboard"},
//      *     security={{"sanctum":{}}},
//      *     @OA\Parameter(name="start_date", in="query", required=false, @OA\Schema(type="string", format="date"), description="Filter from date (YYYY-MM-DD)"),
//      *     @OA\Parameter(name="end_date", in="query", required=false, @OA\Schema(type="string", format="date"), description="Filter to date (YYYY-MM-DD)"),
//      *     @OA\Parameter(name="keyword", in="query", required=false, @OA\Schema(type="string"), description="Search keyword in content"),
//      *     @OA\Parameter(name="platform", in="query", required=false, @OA\Schema(type="string", enum={"Facebook","Instagram","TikTok","X (Twitter)","Online News"}), description="Filter by platform"),
//      *     @OA\Parameter(name="region", in="query", required=false, @OA\Schema(type="string"), description="Filter by region"),
//      *     @OA\Response(
//      *         response=200,
//      *         description="Dashboard data",
//      *         @OA\JsonContent(
//      *             @OA\Property(property="filters", type="object",
//      *                 @OA\Property(property="platforms", type="array", @OA\Items(type="string")),
//      *                 @OA\Property(property="regions", type="array", @OA\Items(type="string"))
//      *             ),
//      *             @OA\Property(property="net_sentiment", type="number", example=10.51),
//      *             @OA\Property(property="sentiment_percentage", type="object",
//      *                 @OA\Property(property="positive", type="integer"),
//      *                 @OA\Property(property="neutral", type="integer"),
//      *                 @OA\Property(property="negative", type="integer")
//      *             ),
//      *             @OA\Property(property="negative_words", type="array", @OA\Items(type="object",
//      *                 @OA\Property(property="text", type="string"),
//      *                 @OA\Property(property="size", type="integer")
//      *             )),
//      *             @OA\Property(property="positive_words", type="array", @OA\Items(type="object",
//      *                 @OA\Property(property="text", type="string"),
//      *                 @OA\Property(property="size", type="integer")
//      *             )),
//      *             @OA\Property(property="trend", type="array", @OA\Items(type="object",
//      *                 @OA\Property(property="month", type="string"),
//      *                 @OA\Property(property="instagram", type="integer"),
//      *                 @OA\Property(property="online_news", type="integer")
//      *             )),
//      *             @OA\Property(property="platform_sentiment", type="array", @OA\Items(type="object",
//      *                 @OA\Property(property="platform", type="string"),
//      *                 @OA\Property(property="positive", type="integer"),
//      *                 @OA\Property(property="neutral", type="integer"),
//      *                 @OA\Property(property="negative", type="integer")
//      *             )),
//      *             @OA\Property(property="mention_by_platform", type="array", @OA\Items(type="object",
//      *                 @OA\Property(property="platform", type="string"),
//      *                 @OA\Property(property="count", type="integer"),
//      *                 @OA\Property(property="percentage", type="integer")
//      *             )),
//      *             @OA\Property(property="mention_by_media", type="array", @OA\Items(type="object",
//      *                 @OA\Property(property="media", type="string"),
//      *                 @OA\Property(property="count", type="integer")
//      *             )),
//      *             @OA\Property(property="mention_by_province", type="array", @OA\Items(type="object",
//      *                 @OA\Property(property="name", type="string"),
//      *                 @OA\Property(property="value", type="integer")
//      *             )),
//      *             @OA\Property(property="top_topics", type="array", @OA\Items(type="string")),
//      *             @OA\Property(property="engagement", type="array", @OA\Items(type="object",
//      *                 @OA\Property(property="platform", type="string"),
//      *                 @OA\Property(property="post", type="integer"),
//      *                 @OA\Property(property="like", type="integer"),
//      *                 @OA\Property(property="comment", type="integer"),
//      *                 @OA\Property(property="share", type="integer")
//      *             )),
//      *             @OA\Property(property="table", type="array", @OA\Items(type="object",
//      *                 @OA\Property(property="date", type="string", format="date"),
//      *                 @OA\Property(property="platform", type="string"),
//      *                 @OA\Property(property="region", type="string"),
//      *                 @OA\Property(property="content", type="string"),
//      *                 @OA\Property(property="like", type="integer"),
//      *                 @OA\Property(property="comment", type="integer"),
//      *                 @OA\Property(property="share", type="integer"),
//      *                 @OA\Property(property="sentiment", type="string", enum={"positive","neutral","negative"})
//      *             ))
//      *         )
//      *     ),
//      *     @OA\Response(response=401, description="Unauthenticated")
//      * )
//      */
//     public function index(Request $request): JsonResponse
//     {
//         $startDate        = $request->query('start_date');
//         $endDate          = $request->query('end_date');
//         $keyword          = $request->query('keyword');
//         $platformFilter   = $request->query('platform');
//         $regionFilter     = $request->query('region');

//         $table = $this->getDummyTable();

//         // Apply filters
//         $filtered = array_filter($table, function ($row) use ($startDate, $endDate, $keyword, $platformFilter, $regionFilter) {
//             if ($startDate && $row['date'] < $startDate) return false;
//             if ($endDate   && $row['date'] > $endDate)   return false;
//             if ($keyword   && stripos($row['content'], $keyword) === false) return false;
//             if ($platformFilter && $row['platform'] !== $platformFilter) return false;
//             if ($regionFilter   && $row['region']   !== $regionFilter)   return false;
//             return true;
//         });

//         $filtered = array_values($filtered);

//         // Dynamic options
//         $allPlatforms = array_values(array_unique(array_column($table, 'platform')));
//         $allRegions   = array_values(array_unique(array_column($table, 'region')));
//         sort($allPlatforms);
//         sort($allRegions);

//         // Mention by platform from filtered
//         $platformCounts = [];
//         foreach ($filtered as $row) {
//             $p = $row['platform'];
//             $platformCounts[$p] = ($platformCounts[$p] ?? 0) + 1;
//         }
//         arsort($platformCounts);
//         $total = array_sum($platformCounts) ?: 1;
//         $mentionByPlatform = array_map(fn($p, $c) => [
//             'platform'   => $p,
//             'count'      => $c,
//             'percentage' => round($c / $total * 100),
//         ], array_keys($platformCounts), $platformCounts);

//         // Mention by province from filtered
//         $provinceCounts = [];
//         foreach ($filtered as $row) {
//             $r = $row['region'];
//             $provinceCounts[$r] = ($provinceCounts[$r] ?? 0) + 1;
//         }
//         arsort($provinceCounts);
//         $mentionByProvince = array_map(
//             fn($n, $v) => ['name' => $n, 'value' => $v],
//             array_keys($provinceCounts),
//             $provinceCounts
//         );

//         // Engagement from filtered (sum per platform)
//         $engMap = [];
//         foreach ($filtered as $row) {
//             $p = $row['platform'];
//             if (!isset($engMap[$p])) {
//                 $engMap[$p] = ['platform' => $p, 'post' => 0, 'like' => 0, 'comment' => 0, 'share' => 0];
//             }
//             $engMap[$p]['post']++;
//             $engMap[$p]['like']    += $row['like'];
//             $engMap[$p]['comment'] += $row['comment'];
//             $engMap[$p]['share']   += $row['share'];
//         }
//         $engagement = array_values($engMap);

//         // Mention by media (static for demo)
//         $mentionByMedia = [
//             ['media' => 'Detik.com',    'count' => 88],
//             ['media' => 'Kompas.com',   'count' => 72],
//             ['media' => 'CNNIndonesia', 'count' => 65],
//             ['media' => 'Okezone',      'count' => 58],
//             ['media' => 'Republika',    'count' => 51],
//             ['media' => 'Bisnis',       'count' => 46],
//             ['media' => 'Tempo',        'count' => 38],
//         ];

//         return response()->json([
//             'filters' => [
//                 'platforms' => $allPlatforms,
//                 'regions'   => $allRegions,
//             ],
//             'net_sentiment' => 10.51,
//             'sentiment_percentage' => [
//                 'positive' => 55,
//                 'neutral'  => 35,
//                 'negative' => 10,
//             ],
//             'negative_words' => [
//                 ['text' => 'kendala nya',    'size' => 5],
//                 ['text' => 'Total Kerugian', 'size' => 4],
//                 ['text' => 'Word persiapan', 'size' => 3],
//                 ['text' => 'tidak tertib',   'size' => 3],
//                 ['text' => 'Latih fokus',    'size' => 2],
//                 ['text' => 'pemipaan dk',    'size' => 2],
//                 ['text' => 'masalah besar',  'size' => 2],
//                 ['text' => 'buruk sekali',   'size' => 1],
//             ],
//             'positive_words' => [
//                 ['text' => 'mohon info',      'size' => 5],
//                 ['text' => 'akan segera',     'size' => 4],
//                 ['text' => 'proses langsung', 'size' => 4],
//                 ['text' => 'layanan cepat',   'size' => 3],
//                 ['text' => 'untuk layanan',   'size' => 3],
//                 ['text' => 'pengelolaan',     'size' => 2],
//                 ['text' => 'sukses bro',      'size' => 2],
//                 ['text' => 'sangat bagus',    'size' => 1],
//             ],
//             'trend' => [
//                 ['month' => 'Jan', 'instagram' => 5,  'online_news' => 8],
//                 ['month' => 'Feb', 'instagram' => 7,  'online_news' => 9],
//                 ['month' => 'Mar', 'instagram' => 6,  'online_news' => 10],
//                 ['month' => 'Apr', 'instagram' => 8,  'online_news' => 7],
//                 ['month' => 'May', 'instagram' => 6,  'online_news' => 8],
//                 ['month' => 'Jun', 'instagram' => 9,  'online_news' => 7],
//                 ['month' => 'Jul', 'instagram' => 7,  'online_news' => 9],
//                 ['month' => 'Aug', 'instagram' => 5,  'online_news' => 10],
//                 ['month' => 'Sep', 'instagram' => 6,  'online_news' => 8],
//                 ['month' => 'Oct', 'instagram' => 8,  'online_news' => 7],
//                 ['month' => 'Nov', 'instagram' => 7,  'online_news' => 6],
//                 ['month' => 'Dec', 'instagram' => 5,  'online_news' => 8],
//             ],
//             'platform_sentiment' => [
//                 ['platform' => 'Facebook',    'positive' => 45, 'neutral' => 30, 'negative' => 25],
//                 ['platform' => 'Instagram',   'positive' => 40, 'neutral' => 30, 'negative' => 30],
//                 ['platform' => 'Online News', 'positive' => 35, 'neutral' => 39, 'negative' => 26],
//                 ['platform' => 'TikTok',      'positive' => 45, 'neutral' => 23, 'negative' => 32],
//                 ['platform' => 'X (Twitter)', 'positive' => 44, 'neutral' => 26, 'negative' => 30],
//             ],
//             'mention_by_platform' => array_values($mentionByPlatform),
//             'mention_by_media'    => $mentionByMedia,
//             'mention_by_province' => array_values($mentionByProvince),
//             'top_topics'          => ['Analytics', 'Monitoring', 'Social Media', 'Development', 'Digitalize'],
//             'engagement'          => $engagement,
//             'table'               => $filtered,
//         ]);
//     }
// }
