<?php
// Tệp cấu hình API cho Lọc Phim

// Định nghĩa hằng số để bảo vệ file
define('SECURE_ACCESS', true);

// Cần file cấu hình chính
require_once dirname(__DIR__) . '/config.php';
require_once 'functions.php';

// Danh sách API key và cấu hình
$api_config = [
    // Jikan API (MyAnimeList unofficial API, không cần API key)
    'jikan' => [
        'base_url' => 'https://api.jikan.moe/v4',
        'rate_limit' => 3, // 3 request mỗi giây (60 request mỗi phút)
        'cooldown' => 1, // Thời gian chờ giữa các request (giây)
    ],
    
    // Kitsu API (không cần API key)
    'kitsu' => [
        'base_url' => 'https://kitsu.io/api/edge',
        'rate_limit' => 5, // request mỗi giây
        'cooldown' => 0.5,
    ],
    
    // AniList API (GraphQL)
    'anilist' => [
        'base_url' => 'https://graphql.anilist.co',
        'client_id' => getenv('ANILIST_CLIENT_ID') ?: '',
        'client_secret' => getenv('ANILIST_CLIENT_SECRET') ?: '',
    ],
    
    // TheMovieDB API
    'tmdb' => [
        'base_url' => 'https://api.themoviedb.org/3',
        'api_key' => getenv('TMDB_API_KEY') ?: '',
        'language' => 'vi-VN',
    ],
    
    // YouTube API (dùng để lấy trailer)
    'youtube' => [
        'base_url' => 'https://www.googleapis.com/youtube/v3',
        'api_key' => getenv('YOUTUBE_API_KEY') ?: '',
    ],
];

/**
 * Kiểm tra API key đã được cấu hình chưa
 */
function is_api_configured($api_name) {
    global $api_config;
    
    if (!isset($api_config[$api_name])) {
        return false;
    }
    
    // API không cần key
    if (in_array($api_name, ['jikan', 'kitsu'])) {
        return true;
    }
    
    // Kiểm tra API key/client_id tồn tại và không rỗng
    switch ($api_name) {
        case 'anilist':
            return !empty($api_config[$api_name]['client_id']) && !empty($api_config[$api_name]['client_secret']);
        case 'tmdb':
        case 'youtube':
            return !empty($api_config[$api_name]['api_key']);
        default:
            return false;
    }
}

/**
 * Lấy cấu hình API
 */
function get_api_config($api_name) {
    global $api_config;
    
    if (isset($api_config[$api_name])) {
        return $api_config[$api_name];
    }
    
    return null;
}

/**
 * Lưu thời gian truy cập API gần nhất để kiểm soát rate limit
 */
function update_api_last_access($api_name) {
    $_SESSION['api_last_access'][$api_name] = microtime(true);
}

/**
 * Kiểm tra có thể truy cập API không (rate limit)
 */
function can_access_api($api_name) {
    global $api_config;
    
    if (!isset($api_config[$api_name])) {
        return false;
    }
    
    // Nếu không có cooldown, luôn cho phép truy cập
    if (!isset($api_config[$api_name]['cooldown']) || $api_config[$api_name]['cooldown'] <= 0) {
        return true;
    }
    
    // Kiểm tra thời gian truy cập gần nhất
    if (isset($_SESSION['api_last_access'][$api_name])) {
        $last_access = $_SESSION['api_last_access'][$api_name];
        $current_time = microtime(true);
        $diff = $current_time - $last_access;
        
        // Nếu chưa đủ thời gian cooldown, không cho phép truy cập
        if ($diff < $api_config[$api_name]['cooldown']) {
            return false;
        }
    }
    
    return true;
}

/**
 * Xử lý response từ các API khác nhau về định dạng thống nhất
 */
function process_api_response($api_name, $response_data, $type = 'anime') {
    switch ($api_name) {
        case 'jikan':
            return process_jikan_response($response_data, $type);
        case 'kitsu':
            return process_kitsu_response($response_data, $type);
        case 'anilist':
            return process_anilist_response($response_data, $type);
        case 'tmdb':
            return process_tmdb_response($response_data, $type);
        default:
            return null;
    }
}

/**
 * Xử lý response từ Jikan API
 */
function process_jikan_response($data, $type = 'anime') {
    if ($type === 'anime' && isset($data['data'])) {
        $anime = $data['data'];
        
        return [
            'id' => $anime['mal_id'] ?? null,
            'source_id' => 'mal_' . ($anime['mal_id'] ?? ''),
            'title' => $anime['title'] ?? '',
            'alt_title' => $anime['title_english'] ?? '',
            'slug' => create_slug($anime['title'] ?? ''),
            'description' => $anime['synopsis'] ?? '',
            'thumbnail' => $anime['images']['jpg']['large_image_url'] ?? '',
            'banner' => $anime['images']['jpg']['large_image_url'] ?? '',
            'release_year' => isset($anime['year']) ? (int)$anime['year'] : null,
            'release_date' => $anime['aired']['from'] ?? null,
            'status' => map_status($anime['status'] ?? ''),
            'episode_count' => $anime['episodes'] ?? 0,
            'rating' => isset($anime['score']) ? (float)$anime['score'] : 0,
            'categories' => array_map(function($genre) {
                return [
                    'id' => $genre['mal_id'] ?? 0,
                    'name' => $genre['name'] ?? ''
                ];
            }, $anime['genres'] ?? []),
            'source_api' => 'jikan',
            'details' => [
                'type' => $anime['type'] ?? '',
                'duration' => $anime['duration'] ?? '',
                'rating' => $anime['rating'] ?? '',
                'studios' => array_map(function($studio) {
                    return $studio['name'] ?? '';
                }, $anime['studios'] ?? []),
                'source_material' => $anime['source'] ?? '',
                'season' => $anime['season'] ?? '',
                'year' => $anime['year'] ?? '',
            ]
        ];
    }
    
    return null;
}

/**
 * Xử lý response từ Kitsu API
 */
function process_kitsu_response($data, $type = 'anime') {
    if ($type === 'anime' && isset($data['data'])) {
        $anime = $data['data'];
        $attributes = $anime['attributes'] ?? [];
        
        return [
            'id' => $anime['id'] ?? null,
            'source_id' => 'kitsu_' . ($anime['id'] ?? ''),
            'title' => $attributes['canonicalTitle'] ?? '',
            'alt_title' => ($attributes['titles']['en'] ?? $attributes['titles']['en_jp'] ?? ''),
            'slug' => create_slug($attributes['canonicalTitle'] ?? ''),
            'description' => $attributes['synopsis'] ?? '',
            'thumbnail' => $attributes['posterImage']['large'] ?? '',
            'banner' => $attributes['coverImage']['large'] ?? '',
            'release_year' => isset($attributes['startDate']) ? (int)substr($attributes['startDate'], 0, 4) : null,
            'release_date' => $attributes['startDate'] ?? null,
            'status' => map_status($attributes['status'] ?? ''),
            'episode_count' => $attributes['episodeCount'] ?? 0,
            'rating' => isset($attributes['averageRating']) ? (float)$attributes['averageRating'] / 10 : 0,
            'categories' => [], // Cần thêm request riêng cho categories
            'source_api' => 'kitsu',
            'details' => [
                'age_rating' => $attributes['ageRating'] ?? '',
                'age_rating_guide' => $attributes['ageRatingGuide'] ?? '',
                'episode_length' => $attributes['episodeLength'] ?? 0,
                'subtype' => $attributes['subtype'] ?? '',
                'original_language' => $attributes['originalLanguage'] ?? '',
            ]
        ];
    }
    
    return null;
}

/**
 * Xử lý response từ AniList API
 */
function process_anilist_response($data, $type = 'anime') {
    if ($type === 'anime' && isset($data['data']['Media'])) {
        $anime = $data['data']['Media'];
        
        return [
            'id' => $anime['id'] ?? null,
            'source_id' => 'anilist_' . ($anime['id'] ?? ''),
            'title' => $anime['title']['romaji'] ?? '',
            'alt_title' => $anime['title']['english'] ?? '',
            'slug' => create_slug($anime['title']['romaji'] ?? ''),
            'description' => $anime['description'] ?? '',
            'thumbnail' => $anime['coverImage']['large'] ?? '',
            'banner' => $anime['bannerImage'] ?? '',
            'release_year' => isset($anime['seasonYear']) ? (int)$anime['seasonYear'] : null,
            'release_date' => $anime['startDate']['year'] . '-' . $anime['startDate']['month'] . '-' . $anime['startDate']['day'] ?? null,
            'status' => map_status($anime['status'] ?? ''),
            'episode_count' => $anime['episodes'] ?? 0,
            'rating' => isset($anime['averageScore']) ? (float)$anime['averageScore'] / 10 : 0,
            'categories' => array_map(function($genre) {
                return [
                    'id' => 0, // AniList không có ID cho thể loại
                    'name' => $genre
                ];
            }, $anime['genres'] ?? []),
            'source_api' => 'anilist',
            'details' => [
                'format' => $anime['format'] ?? '',
                'duration' => $anime['duration'] ?? 0,
                'season' => $anime['season'] ?? '',
                'year' => $anime['seasonYear'] ?? '',
                'source_material' => $anime['source'] ?? '',
                'studios' => array_map(function($studio) {
                    return $studio['name'] ?? '';
                }, $anime['studios']['nodes'] ?? []),
            ]
        ];
    }
    
    return null;
}

/**
 * Xử lý response từ TMDB API
 */
function process_tmdb_response($data, $type = 'anime') {
    if ($type === 'movie' && isset($data['id'])) {
        $movie = $data;
        
        return [
            'id' => $movie['id'] ?? null,
            'source_id' => 'tmdb_' . ($movie['id'] ?? ''),
            'title' => $movie['title'] ?? '',
            'alt_title' => $movie['original_title'] ?? '',
            'slug' => create_slug($movie['title'] ?? ''),
            'description' => $movie['overview'] ?? '',
            'thumbnail' => 'https://image.tmdb.org/t/p/w500' . ($movie['poster_path'] ?? ''),
            'banner' => 'https://image.tmdb.org/t/p/original' . ($movie['backdrop_path'] ?? ''),
            'release_year' => isset($movie['release_date']) ? (int)substr($movie['release_date'], 0, 4) : null,
            'release_date' => $movie['release_date'] ?? null,
            'status' => map_status($movie['status'] ?? ''),
            'episode_count' => 1, // Movie chỉ có 1
            'rating' => isset($movie['vote_average']) ? (float)$movie['vote_average'] : 0,
            'categories' => array_map(function($genre) {
                return [
                    'id' => $genre['id'] ?? 0,
                    'name' => $genre['name'] ?? ''
                ];
            }, $movie['genres'] ?? []),
            'source_api' => 'tmdb',
            'details' => [
                'runtime' => $movie['runtime'] ?? 0,
                'budget' => $movie['budget'] ?? 0,
                'revenue' => $movie['revenue'] ?? 0,
                'original_language' => $movie['original_language'] ?? '',
                'production_companies' => array_map(function($company) {
                    return $company['name'] ?? '';
                }, $movie['production_companies'] ?? []),
            ]
        ];
    } elseif ($type === 'tv' && isset($data['id'])) {
        $tv = $data;
        
        return [
            'id' => $tv['id'] ?? null,
            'source_id' => 'tmdb_' . ($tv['id'] ?? ''),
            'title' => $tv['name'] ?? '',
            'alt_title' => $tv['original_name'] ?? '',
            'slug' => create_slug($tv['name'] ?? ''),
            'description' => $tv['overview'] ?? '',
            'thumbnail' => 'https://image.tmdb.org/t/p/w500' . ($tv['poster_path'] ?? ''),
            'banner' => 'https://image.tmdb.org/t/p/original' . ($tv['backdrop_path'] ?? ''),
            'release_year' => isset($tv['first_air_date']) ? (int)substr($tv['first_air_date'], 0, 4) : null,
            'release_date' => $tv['first_air_date'] ?? null,
            'status' => map_status($tv['status'] ?? ''),
            'episode_count' => $tv['number_of_episodes'] ?? 0,
            'rating' => isset($tv['vote_average']) ? (float)$tv['vote_average'] : 0,
            'categories' => array_map(function($genre) {
                return [
                    'id' => $genre['id'] ?? 0,
                    'name' => $genre['name'] ?? ''
                ];
            }, $tv['genres'] ?? []),
            'source_api' => 'tmdb',
            'details' => [
                'number_of_seasons' => $tv['number_of_seasons'] ?? 0,
                'episode_run_time' => $tv['episode_run_time'][0] ?? 0,
                'original_language' => $tv['original_language'] ?? '',
                'production_companies' => array_map(function($company) {
                    return $company['name'] ?? '';
                }, $tv['production_companies'] ?? []),
                'networks' => array_map(function($network) {
                    return $network['name'] ?? '';
                }, $tv['networks'] ?? []),
            ]
        ];
    }
    
    return null;
}

/**
 * Chuyển đổi trạng thái từ các API về định dạng thống nhất
 */
function map_status($status) {
    $status = strtolower($status);
    
    switch ($status) {
        case 'currently airing':
        case 'airing':
        case 'ongoing':
        case 'releasing':
        case 'current':
            return 'ongoing';
        
        case 'finished airing':
        case 'finished':
        case 'completed':
        case 'ended':
            return 'completed';
            
        case 'not yet aired':
        case 'upcoming':
        case 'unreleased':
        case 'planned':
            return 'upcoming';
            
        case 'on hiatus':
        case 'paused':
            return 'hiatus';
            
        case 'cancelled':
        case 'discontinued':
            return 'cancelled';
            
        default:
            return 'unknown';
    }
}
?>