<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ApifyStat;
use App\Services\DashboardDataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    // public function index(Request $request): JsonResponse
    // {
    //     $user      = $request->user();
    //     $platforms = $user->preference?->platforms ?? [];

    //     if (empty($platforms)) {
    //         return response()->json([
    //             'message' => 'No platforms selected.',
    //             'stats'   => [],
    //         ]);
    //     }

    //     $stats = ApifyStat::query()
    //         ->whereIn('platform', $platforms)
    //         ->orderByDesc('fetched_at')
    //         ->get()
    //         ->groupBy('platform')
    //         ->map(fn($group) => $group->first());

    //     return response()->json([
    //         'stats' => $stats->values(),
    //     ]);
    // }

    private function getDummyTable(): array
    {
        return [
            ['date' => '2026-01-05', 'platform' => 'Facebook',    'region' => 'DKI Jakarta', 'content' => 'Brand CTX ramai dibahas publik di Jakarta',       'like' => 1244, 'comment' => 512,  'share' => 3011, 'sentiment' => 'positive'],
            ['date' => '2026-01-08', 'platform' => 'Instagram',   'region' => 'Jawa Barat',  'content' => 'Brand CTX viral di media sosial Bandung',         'like' => 1354, 'comment' => 764,  'share' => 3021, 'sentiment' => 'positive'],
            ['date' => '2026-01-12', 'platform' => 'TikTok',      'region' => 'Jawa Timur',  'content' => 'Kendala layanan CTX dikeluhkan pengguna Surabaya', 'like' => 1320, 'comment' => 5004, 'share' => 512,  'sentiment' => 'negative'],
            ['date' => '2026-01-15', 'platform' => 'X (Twitter)', 'region' => 'Jawa Tengah', 'content' => 'CTX meluncurkan fitur baru monitoring sosmed',     'like' => 1355, 'comment' => 1821, 'share' => 931,  'sentiment' => 'neutral'],
            ['date' => '2026-01-20', 'platform' => 'Online News', 'region' => 'Bali',        'content' => 'CTX Analytics dinilai ungguli kompetitor',         'like' => 2140, 'comment' => 3004, 'share' => 3004, 'sentiment' => 'positive'],
            ['date' => '2026-01-22', 'platform' => 'Instagram',   'region' => 'Sumatera U.', 'content' => 'Pengguna CTX di Sumatera terus bertambah',         'like' => 1823, 'comment' => 2140, 'share' => 1004, 'sentiment' => 'positive'],
            ['date' => '2026-02-03', 'platform' => 'Facebook',    'region' => 'DKI Jakarta', 'content' => 'CTX dashboard baru mendapat respons positif',      'like' => 980,  'comment' => 430,  'share' => 1200, 'sentiment' => 'positive'],
            ['date' => '2026-02-07', 'platform' => 'TikTok',      'region' => 'Jawa Barat',  'content' => 'Review CTX oleh kreator konten Bandung',           'like' => 4500, 'comment' => 890,  'share' => 2300, 'sentiment' => 'positive'],
            ['date' => '2026-02-10', 'platform' => 'X (Twitter)', 'region' => 'DKI Jakarta', 'content' => 'CTX lambat dikeluhkan pengguna Jakarta',           'like' => 320,  'comment' => 1200, 'share' => 450,  'sentiment' => 'negative'],
            ['date' => '2026-02-14', 'platform' => 'Online News', 'region' => 'Jawa Timur',  'content' => 'CTX berhasil raih penghargaan startup terbaik',    'like' => 560,  'comment' => 230,  'share' => 800,  'sentiment' => 'positive'],
            ['date' => '2026-02-18', 'platform' => 'Instagram',   'region' => 'Jawa Tengah', 'content' => 'Promo CTX menarik perhatian ribuan pengguna',      'like' => 3200, 'comment' => 1450, 'share' => 980,  'sentiment' => 'positive'],
            ['date' => '2026-02-21', 'platform' => 'Facebook',    'region' => 'Bali',        'content' => 'CTX digunakan brand lokal di Bali untuk analitik', 'like' => 760,  'comment' => 340,  'share' => 520,  'sentiment' => 'neutral'],
            ['date' => '2026-02-25', 'platform' => 'TikTok',      'region' => 'Sumatera U.', 'content' => 'Total kerugian pengguna akibat gangguan CTX',      'like' => 2100, 'comment' => 6700, 'share' => 1200, 'sentiment' => 'negative'],
            ['date' => '2026-03-02', 'platform' => 'Online News', 'region' => 'DKI Jakarta', 'content' => 'CTX perluas layanan ke seluruh Indonesia',         'like' => 430,  'comment' => 120,  'share' => 670,  'sentiment' => 'positive'],
            ['date' => '2026-03-05', 'platform' => 'X (Twitter)', 'region' => 'Jawa Barat',  'content' => 'Masalah login CTX belum terselesaikan',            'like' => 890,  'comment' => 2300, 'share' => 340,  'sentiment' => 'negative'],
            ['date' => '2026-03-08', 'platform' => 'Instagram',   'region' => 'Jawa Timur',  'content' => 'Influencer Surabaya promosikan CTX Analytics',     'like' => 5600, 'comment' => 3200, 'share' => 1800, 'sentiment' => 'positive'],
            ['date' => '2026-03-11', 'platform' => 'Facebook',    'region' => 'DKI Jakarta', 'content' => 'CTX update terbaru sangat membantu tim marketing',  'like' => 1100, 'comment' => 560,  'share' => 940,  'sentiment' => 'positive'],
            ['date' => '2026-03-13', 'platform' => 'TikTok',      'region' => 'Jawa Tengah', 'content' => 'Video viral CTX ditonton jutaan orang',            'like' => 12400, 'comment' => 4500, 'share' => 8900, 'sentiment' => 'positive'],
            ['date' => '2026-03-15', 'platform' => 'Online News', 'region' => 'Bali',        'content' => 'CTX gandeng media lokal Bali untuk monitoring',    'like' => 320,  'comment' => 145,  'share' => 430,  'sentiment' => 'neutral'],
            ['date' => '2026-03-17', 'platform' => 'X (Twitter)', 'region' => 'Sumatera U.', 'content' => 'Pengguna CTX Sumatera laporkan bug fitur export',  'like' => 450,  'comment' => 1800, 'share' => 230,  'sentiment' => 'negative'],
            ['date' => '2026-03-18', 'platform' => 'Instagram',   'region' => 'DKI Jakarta', 'content' => 'CTX hadir di pameran teknologi Jakarta 2026',      'like' => 2300, 'comment' => 870,  'share' => 1340, 'sentiment' => 'positive'],
            ['date' => '2026-03-19', 'platform' => 'Facebook',    'region' => 'Jawa Barat',  'content' => 'Diskusi CTX ramai di grup Facebook Bandung',       'like' => 670,  'comment' => 890,  'share' => 540,  'sentiment' => 'neutral'],
            ['date' => '2026-03-20', 'platform' => 'TikTok',      'region' => 'Jawa Timur',  'content' => 'Kendala nya CTX masih dikeluhkan di Jawa Timur',   'like' => 980,  'comment' => 3400, 'share' => 670,  'sentiment' => 'negative'],
            ['date' => '2026-03-21', 'platform' => 'Online News', 'region' => 'Jawa Barat',  'content' => 'CTX raih investasi baru dari modal ventura',       'like' => 230,  'comment' => 90,   'share' => 340,  'sentiment' => 'positive'],
            ['date' => '2026-03-22', 'platform' => 'Instagram',   'region' => 'Jawa Tengah', 'content' => 'CTX Analytics bantu UMKM Jawa Tengah berkembang',  'like' => 1890, 'comment' => 760,  'share' => 1230, 'sentiment' => 'positive'],
            ['date' => '2026-03-22', 'platform' => 'X (Twitter)', 'region' => 'DKI Jakarta', 'content' => 'Mohon info proses langsung kendala CTX kami',      'like' => 340,  'comment' => 2100, 'share' => 180,  'sentiment' => 'negative'],
            ['date' => '2026-03-23', 'platform' => 'Facebook',    'region' => 'Bali',        'content' => 'CTX fitur baru sangat membantu tim digital Bali',  'like' => 540,  'comment' => 230,  'share' => 670,  'sentiment' => 'positive'],
            ['date' => '2026-03-23', 'platform' => 'TikTok',      'region' => 'DKI Jakarta', 'content' => 'Total kerugian akibat downtime CTX minggu ini',    'like' => 3400, 'comment' => 7800, 'share' => 2100, 'sentiment' => 'negative'],
            ['date' => '2026-03-23', 'platform' => 'Online News', 'region' => 'Jawa Timur',  'content' => 'CTX resmi bermitra dengan perusahaan media besar', 'like' => 560,  'comment' => 210,  'share' => 890,  'sentiment' => 'positive'],
            ['date' => '2026-03-23', 'platform' => 'Instagram',   'region' => 'Sumatera U.', 'content' => 'Update CTX sangat membantu monitoring sosmed',     'like' => 1200, 'comment' => 540,  'share' => 780,  'sentiment' => 'positive'],
        ];
    }

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date'   => ['nullable', 'date'],
            'keyword'    => ['nullable', 'string', 'max:255'],
            'platform'   => ['nullable', 'string'],
            'region'     => ['nullable', 'string', 'max:255'],
            'page'       => ['nullable', 'integer', 'min:1'],
            'per_page'   => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $service = new DashboardDataService();

        // All data for available filter options
        $allData         = $service->getFilteredData([]);
        $availableFilters = $service->getAvailableFilters($allData);

        // Filtered data based on request params
        $filteredData = $service->getFilteredData(
            $request->only(['start_date', 'end_date', 'keyword', 'platform', 'region'])
        );

        $stats = $service->getStatistics($filteredData);

        // Pagination
        $page    = (int) $request->input('page', 1);
        $perPage = (int) $request->input('per_page', 20);
        $total   = count($filteredData);
        $pages   = $total > 0 ? (int) ceil($total / $perPage) : 1;
        $offset  = ($page - 1) * $perPage;
        $slice   = array_slice($filteredData, $offset, $perPage);

        // Table rows
        $table = array_map(fn ($r) => [
            'id'        => $r['id'],
            'platform'  => $r['platform'],
            'author'    => $r['author'],
            'text'      => $r['text'],
            'sentiment' => $r['sentiment'],
            'region'    => $r['region'],
            'likes'     => $r['likes'],
            'comments'  => $r['comments'],
            'shares'    => $r['shares'],
            'views'     => $r['views'],
            'date'      => $r['date'],
            'url'       => $r['url'],
        ], $slice);

        return response()->json([
            'filters' => [
                'applied'   => array_filter($request->only(['start_date', 'end_date', 'keyword', 'platform', 'region'])),
                'platforms' => $availableFilters['platforms'],
                'regions'   => $availableFilters['regions'],
            ],
            'net_sentiment'        => $stats['net_sentiment'],
            'sentiment_percentage' => $stats['sentiment_percentage'],
            'trend'                => $stats['trend'],
            'platform_sentiment'   => $stats['platform_sentiment'],
            'mention_by_platform'  => $stats['mention_by_platform'],
            'mention_by_province'  => $stats['mention_by_province'],
            'top_topics'           => $stats['top_topics'],
            'engagement'           => $stats['engagement'],
            'negative_words'       => $stats['negative_words'],
            'positive_words'       => $stats['positive_words'],
            'table'                => $table,
            'meta'                 => [
                'total'    => $total,
                'page'     => $page,
                'per_page' => $perPage,
                'pages'    => $pages,
            ],
        ]);
    }
}
