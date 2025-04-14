<?php
// API Client/Helper cho Lọc Phim

// Định nghĩa hằng số để bảo vệ file
define('SECURE_ACCESS', true);

// Include cấu hình API
require_once 'config.php';

/**
 * Hàm gọi API thông qua cURL
 */
function call_api($url, $method = 'GET', $data = null, $headers = [], $options = []) {
    $curl = curl_init();
    
    $curl_options = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_SSL_VERIFYPEER => false, // Bỏ qua xác thực SSL (có thể gây vấn đề bảo mật)
        CURLOPT_USERAGENT => 'LocPhim/1.0', // User agent để tránh bị chặn
    ];
    
    // Nếu có data và method không phải GET, thêm data vào request
    if ($data !== null && $method !== 'GET') {
        if (is_array($data)) {
            $curl_options[CURLOPT_POSTFIELDS] = json_encode($data);
            $headers[] = 'Content-Type: application/json';
            $curl_options[CURLOPT_HTTPHEADER] = $headers;
        } else {
            $curl_options[CURLOPT_POSTFIELDS] = $data;
        }
    }
    
    // Merge custom options
    foreach ($options as $key => $value) {
        $curl_options[$key] = $value;
    }
    
    // Set options
    curl_setopt_array($curl, $curl_options);
    
    // Execute
    $response = curl_exec($curl);
    $err = curl_error($curl);
    $info = curl_getinfo($curl);
    
    curl_close($curl);
    
    // Trả về kết quả
    if ($err) {
        return [
            'success' => false,
            'error' => $err,
            'code' => $info['http_code'] ?? 0,
            'info' => $info,
        ];
    } else {
        // Tự động decode JSON nếu response là JSON
        $data = $response;
        if (isset($info['content_type']) && strpos($info['content_type'], 'application/json') !== false) {
            $data = json_decode($response, true);
        }
        
        return [
            'success' => true,
            'data' => $data,
            'code' => $info['http_code'] ?? 200,
            'info' => $info,
        ];
    }
}

/**
 * Gọi Jikan API (MyAnimeList unofficial API)
 */
function call_jikan_api($endpoint, $params = []) {
    if (!is_api_configured('jikan') || !can_access_api('jikan')) {
        return [
            'success' => false,
            'error' => 'Jikan API is not configured or rate limited'
        ];
    }
    
    $api_config = get_api_config('jikan');
    $base_url = $api_config['base_url'];
    
    // Xây dựng query string từ params
    $query_string = '';
    if (!empty($params)) {
        $query_string = '?' . http_build_query($params);
    }
    
    $url = $base_url . $endpoint . $query_string;
    
    // Cập nhật thời gian truy cập gần nhất
    update_api_last_access('jikan');
    
    // Gọi API
    $response = call_api($url);
    
    return $response;
}

/**
 * Gọi Kitsu API
 */
function call_kitsu_api($endpoint, $params = []) {
    if (!is_api_configured('kitsu') || !can_access_api('kitsu')) {
        return [
            'success' => false,
            'error' => 'Kitsu API is not configured or rate limited'
        ];
    }
    
    $api_config = get_api_config('kitsu');
    $base_url = $api_config['base_url'];
    
    // Xây dựng query string từ params
    $query_string = '';
    if (!empty($params)) {
        $query_string = '?' . http_build_query($params);
    }
    
    $url = $base_url . $endpoint . $query_string;
    
    // Headers
    $headers = [
        'Accept: application/vnd.api+json',
        'Content-Type: application/vnd.api+json'
    ];
    
    // Cập nhật thời gian truy cập gần nhất
    update_api_last_access('kitsu');
    
    // Gọi API
    $response = call_api($url, 'GET', null, $headers);
    
    return $response;
}

/**
 * Gọi AniList API (GraphQL)
 */
function call_anilist_api($query, $variables = []) {
    if (!is_api_configured('anilist')) {
        return [
            'success' => false,
            'error' => 'AniList API is not configured'
        ];
    }
    
    $api_config = get_api_config('anilist');
    $url = $api_config['base_url'];
    
    // Chuẩn bị data
    $data = [
        'query' => $query,
        'variables' => $variables
    ];
    
    // Headers
    $headers = [
        'Content-Type: application/json',
        'Accept: application/json',
    ];
    
    // Thêm Authorization nếu có client id và secret
    if (!empty($api_config['client_id']) && !empty($api_config['client_secret'])) {
        // Lấy token từ session hoặc tạo mới
        $token = get_anilist_token();
        if ($token) {
            $headers[] = 'Authorization: Bearer ' . $token;
        }
    }
    
    // Gọi API
    $response = call_api($url, 'POST', $data, $headers);
    
    return $response;
}

/**
 * Lấy token cho AniList API
 */
function get_anilist_token() {
    // Nếu đã có token trong session và chưa hết hạn
    if (isset($_SESSION['anilist_token']) && isset($_SESSION['anilist_token_expires']) && $_SESSION['anilist_token_expires'] > time()) {
        return $_SESSION['anilist_token'];
    }
    
    // Không có token hoặc token hết hạn, lấy token mới
    $api_config = get_api_config('anilist');
    
    if (empty($api_config['client_id']) || empty($api_config['client_secret'])) {
        return null;
    }
    
    $url = 'https://anilist.co/api/v2/oauth/token';
    
    $data = [
        'grant_type' => 'client_credentials',
        'client_id' => $api_config['client_id'],
        'client_secret' => $api_config['client_secret']
    ];
    
    $headers = [
        'Content-Type: application/json',
        'Accept: application/json'
    ];
    
    $response = call_api($url, 'POST', $data, $headers);
    
    if ($response['success'] && isset($response['data']['access_token'])) {
        $_SESSION['anilist_token'] = $response['data']['access_token'];
        $_SESSION['anilist_token_expires'] = time() + ($response['data']['expires_in'] ?? 3600);
        
        return $_SESSION['anilist_token'];
    }
    
    return null;
}

/**
 * Gọi TheMovieDB API
 */
function call_tmdb_api($endpoint, $params = []) {
    if (!is_api_configured('tmdb')) {
        return [
            'success' => false,
            'error' => 'TheMovieDB API is not configured'
        ];
    }
    
    $api_config = get_api_config('tmdb');
    $base_url = $api_config['base_url'];
    $api_key = $api_config['api_key'];
    
    // Thêm api_key và language vào params
    $params['api_key'] = $api_key;
    $params['language'] = $api_config['language'];
    
    // Xây dựng query string từ params
    $query_string = '?' . http_build_query($params);
    
    $url = $base_url . $endpoint . $query_string;
    
    // Gọi API
    $response = call_api($url);
    
    return $response;
}

/**
 * Gọi YouTube API
 */
function call_youtube_api($endpoint, $params = []) {
    if (!is_api_configured('youtube')) {
        return [
            'success' => false,
            'error' => 'YouTube API is not configured'
        ];
    }
    
    $api_config = get_api_config('youtube');
    $base_url = $api_config['base_url'];
    $api_key = $api_config['api_key'];
    
    // Thêm api_key vào params
    $params['key'] = $api_key;
    
    // Xây dựng query string từ params
    $query_string = '?' . http_build_query($params);
    
    $url = $base_url . $endpoint . $query_string;
    
    // Gọi API
    $response = call_api($url);
    
    return $response;
}

/**
 * Tìm kiếm anime từ MyAnimeList (Jikan API)
 */
function search_anime_mal($query, $page = 1, $limit = 10) {
    $params = [
        'q' => $query,
        'page' => $page,
        'limit' => $limit
    ];
    
    $response = call_jikan_api('/anime', $params);
    
    if ($response['success'] && isset($response['data']['data'])) {
        $results = [];
        
        foreach ($response['data']['data'] as $anime) {
            $results[] = process_api_response('jikan', ['data' => $anime], 'anime');
        }
        
        return [
            'success' => true,
            'data' => $results,
            'pagination' => [
                'current_page' => $response['data']['pagination']['current_page'] ?? 1,
                'last_page' => $response['data']['pagination']['last_visible_page'] ?? 1,
                'total' => $response['data']['pagination']['items']['total'] ?? 0,
                'count' => count($results),
            ]
        ];
    }
    
    return [
        'success' => false,
        'error' => $response['error'] ?? 'Failed to search anime on MyAnimeList'
    ];
}

/**
 * Lấy chi tiết anime từ MyAnimeList (Jikan API)
 */
function get_anime_mal($id) {
    $response = call_jikan_api('/anime/' . $id);
    
    if ($response['success'] && isset($response['data']['data'])) {
        $anime = process_api_response('jikan', $response['data'], 'anime');
        
        return [
            'success' => true,
            'data' => $anime
        ];
    }
    
    return [
        'success' => false,
        'error' => $response['error'] ?? 'Failed to get anime details from MyAnimeList'
    ];
}

/**
 * Tìm kiếm anime từ Kitsu
 */
function search_anime_kitsu($query, $page = 1, $limit = 10) {
    $params = [
        'filter[text]' => $query,
        'page[limit]' => $limit,
        'page[offset]' => ($page - 1) * $limit
    ];
    
    $response = call_kitsu_api('/anime', $params);
    
    if ($response['success'] && isset($response['data']['data'])) {
        $results = [];
        
        foreach ($response['data']['data'] as $anime) {
            $results[] = process_api_response('kitsu', ['data' => $anime], 'anime');
        }
        
        return [
            'success' => true,
            'data' => $results,
            'pagination' => [
                'current_page' => $page,
                'last_page' => ceil(($response['data']['meta']['count'] ?? 0) / $limit),
                'total' => $response['data']['meta']['count'] ?? 0,
                'count' => count($results),
            ]
        ];
    }
    
    return [
        'success' => false,
        'error' => $response['error'] ?? 'Failed to search anime on Kitsu'
    ];
}

/**
 * Lấy chi tiết anime từ Kitsu
 */
function get_anime_kitsu($id) {
    $response = call_kitsu_api('/anime/' . $id);
    
    if ($response['success'] && isset($response['data']['data'])) {
        $anime = process_api_response('kitsu', $response['data'], 'anime');
        
        return [
            'success' => true,
            'data' => $anime
        ];
    }
    
    return [
        'success' => false,
        'error' => $response['error'] ?? 'Failed to get anime details from Kitsu'
    ];
}

/**
 * Tìm kiếm anime từ AniList
 */
function search_anime_anilist($query, $page = 1, $limit = 10) {
    $gql_query = '
    query ($search: String, $page: Int, $perPage: Int) {
        Page(page: $page, perPage: $perPage) {
            pageInfo {
                total
                currentPage
                lastPage
                hasNextPage
                perPage
            }
            media(search: $search, type: ANIME) {
                id
                title {
                    romaji
                    english
                    native
                }
                description
                coverImage {
                    large
                }
                bannerImage
                startDate {
                    year
                    month
                    day
                }
                endDate {
                    year
                    month
                    day
                }
                seasonYear
                season
                format
                status
                episodes
                duration
                genres
                averageScore
                source
                studios {
                    nodes {
                        name
                    }
                }
            }
        }
    }';
    
    $variables = [
        'search' => $query,
        'page' => $page,
        'perPage' => $limit
    ];
    
    $response = call_anilist_api($gql_query, $variables);
    
    if ($response['success'] && isset($response['data']['data']['Page']['media'])) {
        $results = [];
        
        foreach ($response['data']['data']['Page']['media'] as $anime) {
            $results[] = process_api_response('anilist', ['data' => ['Media' => $anime]], 'anime');
        }
        
        return [
            'success' => true,
            'data' => $results,
            'pagination' => [
                'current_page' => $response['data']['data']['Page']['pageInfo']['currentPage'] ?? 1,
                'last_page' => $response['data']['data']['Page']['pageInfo']['lastPage'] ?? 1,
                'total' => $response['data']['data']['Page']['pageInfo']['total'] ?? 0,
                'count' => count($results),
            ]
        ];
    }
    
    return [
        'success' => false,
        'error' => $response['error'] ?? 'Failed to search anime on AniList'
    ];
}

/**
 * Lấy chi tiết anime từ AniList
 */
function get_anime_anilist($id) {
    $gql_query = '
    query ($id: Int) {
        Media(id: $id, type: ANIME) {
            id
            title {
                romaji
                english
                native
            }
            description
            coverImage {
                large
            }
            bannerImage
            startDate {
                year
                month
                day
            }
            endDate {
                year
                month
                day
            }
            seasonYear
            season
            format
            status
            episodes
            duration
            genres
            averageScore
            source
            studios {
                nodes {
                    name
                }
            }
        }
    }';
    
    $variables = [
        'id' => (int)$id
    ];
    
    $response = call_anilist_api($gql_query, $variables);
    
    if ($response['success'] && isset($response['data']['data']['Media'])) {
        $anime = process_api_response('anilist', $response['data'], 'anime');
        
        return [
            'success' => true,
            'data' => $anime
        ];
    }
    
    return [
        'success' => false,
        'error' => $response['error'] ?? 'Failed to get anime details from AniList'
    ];
}

/**
 * Tìm kiếm trên TMDB
 */
function search_tmdb($query, $page = 1, $type = 'movie') {
    if ($type !== 'movie' && $type !== 'tv') {
        $type = 'movie';
    }
    
    $params = [
        'query' => $query,
        'page' => $page,
        'include_adult' => false
    ];
    
    $response = call_tmdb_api('/search/' . $type, $params);
    
    if ($response['success'] && isset($response['data']['results'])) {
        $results = [];
        
        foreach ($response['data']['results'] as $result) {
            // Lấy chi tiết đầy đủ cho từng kết quả
            $detail_response = call_tmdb_api('/' . $type . '/' . $result['id']);
            
            if ($detail_response['success']) {
                $results[] = process_api_response('tmdb', $detail_response['data'], $type);
            }
        }
        
        return [
            'success' => true,
            'data' => $results,
            'pagination' => [
                'current_page' => $response['data']['page'] ?? 1,
                'last_page' => $response['data']['total_pages'] ?? 1,
                'total' => $response['data']['total_results'] ?? 0,
                'count' => count($results),
            ]
        ];
    }
    
    return [
        'success' => false,
        'error' => $response['error'] ?? 'Failed to search on TMDB'
    ];
}

/**
 * Lấy chi tiết từ TMDB
 */
function get_tmdb_detail($id, $type = 'movie') {
    if ($type !== 'movie' && $type !== 'tv') {
        $type = 'movie';
    }
    
    $response = call_tmdb_api('/' . $type . '/' . $id);
    
    if ($response['success'] && isset($response['data']['id'])) {
        $detail = process_api_response('tmdb', $response['data'], $type);
        
        return [
            'success' => true,
            'data' => $detail
        ];
    }
    
    return [
        'success' => false,
        'error' => $response['error'] ?? 'Failed to get details from TMDB'
    ];
}

/**
 * Tìm trailer từ YouTube
 */
function search_trailer($query, $max_results = 5) {
    if (!is_api_configured('youtube')) {
        return [
            'success' => false,
            'error' => 'YouTube API is not configured'
        ];
    }
    
    $params = [
        'part' => 'snippet',
        'q' => $query . ' trailer official',
        'type' => 'video',
        'videoDefinition' => 'high',
        'maxResults' => $max_results
    ];
    
    $response = call_youtube_api('/search', $params);
    
    if ($response['success'] && isset($response['data']['items'])) {
        $results = [];
        
        foreach ($response['data']['items'] as $item) {
            $results[] = [
                'id' => $item['id']['videoId'],
                'title' => $item['snippet']['title'],
                'description' => $item['snippet']['description'],
                'thumbnail' => $item['snippet']['thumbnails']['high']['url'],
                'url' => 'https://www.youtube.com/watch?v=' . $item['id']['videoId'],
                'embed_url' => 'https://www.youtube.com/embed/' . $item['id']['videoId'],
            ];
        }
        
        return [
            'success' => true,
            'data' => $results
        ];
    }
    
    return [
        'success' => false,
        'error' => $response['error'] ?? 'Failed to search trailers on YouTube'
    ];
}

/**
 * Lấy thông tin anime từ nhiều nguồn khác nhau và kết hợp lại
 */
function get_anime_info($title, $force_update = false) {
    // Nếu force_update = false, kiểm tra cache trước
    if (!$force_update) {
        $cached_data = get_anime_from_cache($title);
        if ($cached_data) {
            return [
                'success' => true,
                'data' => $cached_data,
                'source' => 'cache'
            ];
        }
    }
    
    // Thử từng API theo thứ tự ưu tiên
    $apis = ['jikan', 'kitsu', 'anilist'];
    $result = null;
    
    foreach ($apis as $api) {
        if (is_api_configured($api)) {
            switch ($api) {
                case 'jikan':
                    $search_result = search_anime_mal($title, 1, 1);
                    if ($search_result['success'] && !empty($search_result['data'])) {
                        $result = $search_result['data'][0];
                    }
                    break;
                
                case 'kitsu':
                    $search_result = search_anime_kitsu($title, 1, 1);
                    if ($search_result['success'] && !empty($search_result['data'])) {
                        $result = $search_result['data'][0];
                    }
                    break;
                
                case 'anilist':
                    $search_result = search_anime_anilist($title, 1, 1);
                    if ($search_result['success'] && !empty($search_result['data'])) {
                        $result = $search_result['data'][0];
                    }
                    break;
            }
            
            if ($result) {
                // Tìm thêm trailer từ YouTube nếu có cấu hình
                if (is_api_configured('youtube')) {
                    $trailer_result = search_trailer($title . ' ' . ($result['release_year'] ?? ''), 1);
                    if ($trailer_result['success'] && !empty($trailer_result['data'])) {
                        $result['trailer'] = $trailer_result['data'][0];
                    }
                }
                
                // Lưu kết quả vào cache
                save_anime_to_cache($result);
                
                return [
                    'success' => true,
                    'data' => $result,
                    'source' => $api
                ];
            }
        }
    }
    
    return [
        'success' => false,
        'error' => 'No information found for this anime from any API source'
    ];
}

/**
 * Lấy thông tin anime từ cache
 */
function get_anime_from_cache($title_or_id) {
    // Chuẩn bị truy vấn
    if (is_numeric($title_or_id)) {
        // Tìm theo ID
        $sql = "SELECT * FROM anime_api_cache WHERE id = ? OR source_id LIKE ?";
        $params = [$title_or_id, '%_' . $title_or_id];
    } else {
        // Tìm theo title
        $sql = "SELECT * FROM anime_api_cache WHERE LOWER(title) = LOWER(?) OR LOWER(alt_title) = LOWER(?)";
        $params = [$title_or_id, $title_or_id];
    }
    
    $result = db_query($sql, $params, false);
    
    // Kiểm tra kết quả
    if (get_config('db.type') === 'postgresql') {
        if (pg_num_rows($result) > 0) {
            $anime = pg_fetch_assoc($result);
            
            // Giải mã dữ liệu chi tiết
            if (!empty($anime['details_json'])) {
                $anime['details'] = json_decode($anime['details_json'], true);
            }
            
            // Giải mã dữ liệu trailer
            if (!empty($anime['trailer_json'])) {
                $anime['trailer'] = json_decode($anime['trailer_json'], true);
            }
            
            return $anime;
        }
    } else {
        if ($result->num_rows > 0) {
            $anime = $result->fetch_assoc();
            
            // Giải mã dữ liệu chi tiết
            if (!empty($anime['details_json'])) {
                $anime['details'] = json_decode($anime['details_json'], true);
            }
            
            // Giải mã dữ liệu trailer
            if (!empty($anime['trailer_json'])) {
                $anime['trailer'] = json_decode($anime['trailer_json'], true);
            }
            
            return $anime;
        }
    }
    
    return null;
}

/**
 * Lưu thông tin anime vào cache
 */
function save_anime_to_cache($anime_data) {
    if (empty($anime_data) || empty($anime_data['title'])) {
        return false;
    }
    
    // Chuẩn bị dữ liệu
    $source_id = $anime_data['source_id'] ?? null;
    $title = $anime_data['title'] ?? '';
    $alt_title = $anime_data['alt_title'] ?? '';
    $slug = $anime_data['slug'] ?? create_slug($title);
    $description = $anime_data['description'] ?? '';
    $thumbnail = $anime_data['thumbnail'] ?? '';
    $banner = $anime_data['banner'] ?? '';
    $release_year = $anime_data['release_year'] ?? null;
    $release_date = $anime_data['release_date'] ?? null;
    $status = $anime_data['status'] ?? 'unknown';
    $episode_count = $anime_data['episode_count'] ?? 0;
    $rating = $anime_data['rating'] ?? 0;
    
    // JSON data
    $details_json = !empty($anime_data['details']) ? json_encode($anime_data['details']) : null;
    $trailer_json = !empty($anime_data['trailer']) ? json_encode($anime_data['trailer']) : null;
    
    // Loại bỏ HTML tags từ description
    $description = strip_tags($description);
    
    // Kiểm tra xem anime đã có trong cache chưa
    $check_sql = "SELECT id FROM anime_api_cache WHERE source_id = ? OR (LOWER(title) = LOWER(?) AND release_year = ?)";
    $check_params = [$source_id, $title, $release_year];
    
    $check_result = db_query($check_sql, $check_params, false);
    $exists = false;
    $existing_id = null;
    
    if (get_config('db.type') === 'postgresql') {
        $exists = pg_num_rows($check_result) > 0;
        if ($exists) {
            $row = pg_fetch_assoc($check_result);
            $existing_id = $row['id'];
        }
    } else {
        $exists = $check_result->num_rows > 0;
        if ($exists) {
            $row = $check_result->fetch_assoc();
            $existing_id = $row['id'];
        }
    }
    
    if ($exists && $existing_id) {
        // Update existing record
        $update_sql = "
            UPDATE anime_api_cache SET 
                source_id = ?,
                title = ?,
                alt_title = ?,
                slug = ?,
                description = ?,
                thumbnail = ?,
                banner = ?,
                release_year = ?,
                release_date = ?,
                status = ?,
                episode_count = ?,
                rating = ?,
                details_json = ?,
                trailer_json = ?,
                updated_at = NOW()
            WHERE id = ?
        ";
        
        $update_params = [
            $source_id,
            $title,
            $alt_title,
            $slug,
            $description,
            $thumbnail,
            $banner,
            $release_year,
            $release_date,
            $status,
            $episode_count,
            $rating,
            $details_json,
            $trailer_json,
            $existing_id
        ];
        
        $result = db_query($update_sql, $update_params);
        return $result['affected_rows'] > 0;
    } else {
        // Insert new record
        $insert_sql = "
            INSERT INTO anime_api_cache (
                source_id, title, alt_title, slug, description, 
                thumbnail, banner, release_year, release_date, status,
                episode_count, rating, details_json, trailer_json, created_at, updated_at
            ) VALUES (
                ?, ?, ?, ?, ?, 
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?, NOW(), NOW()
            )
        ";
        
        $insert_params = [
            $source_id,
            $title,
            $alt_title,
            $slug,
            $description,
            $thumbnail,
            $banner,
            $release_year,
            $release_date,
            $status,
            $episode_count,
            $rating,
            $details_json,
            $trailer_json
        ];
        
        $result = db_query($insert_sql, $insert_params);
        return $result['affected_rows'] > 0;
    }
    
    return false;
}