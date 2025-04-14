<?php
/**
 * AJAX - Kiểm tra API
 * Lọc Phim - Admin Panel
 */

// Kết nối file chính
require_once dirname(dirname(__DIR__)) . '/config.php';
require_once dirname(dirname(__DIR__)) . '/admin/includes/auth.php';

// Thiết lập header
header('Content-Type: application/json');

// Kiểm tra đăng nhập và quyền hạn
if (!is_admin_logged_in() || !check_admin_permission('manage_api')) {
    echo json_encode([
        'success' => false,
        'message' => 'Không có quyền truy cập.'
    ]);
    exit;
}

// Kiểm tra CSRF token
if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode([
        'success' => false,
        'message' => 'CSRF token không hợp lệ.'
    ]);
    exit;
}

// Lấy loại API cần kiểm tra
$api_type = $_POST['api_type'] ?? '';

// Kiểm tra loại API hợp lệ
$valid_api_types = ['youtube', 'anilist', 'tmdb', 'jikan', 'kitsu'];

if (!in_array($api_type, $valid_api_types)) {
    echo json_encode([
        'success' => false,
        'message' => 'Loại API không hợp lệ.'
    ]);
    exit;
}

// Xử lý từng loại API
switch ($api_type) {
    case 'youtube':
        $api_key = $_POST['api_key'] ?? '';
        
        if (empty($api_key)) {
            echo json_encode([
                'success' => false,
                'message' => 'Vui lòng nhập YouTube API Key.'
            ]);
            exit;
        }
        
        $result = test_youtube_api($api_key);
        break;
        
    case 'anilist':
        $client_id = $_POST['client_id'] ?? '';
        $client_secret = $_POST['client_secret'] ?? '';
        
        if (empty($client_id) || empty($client_secret)) {
            echo json_encode([
                'success' => false,
                'message' => 'Vui lòng nhập đầy đủ AniList Client ID và Client Secret.'
            ]);
            exit;
        }
        
        $result = test_anilist_api($client_id, $client_secret);
        break;
        
    case 'tmdb':
        $api_key = $_POST['api_key'] ?? '';
        
        if (empty($api_key)) {
            echo json_encode([
                'success' => false,
                'message' => 'Vui lòng nhập TMDB API Key.'
            ]);
            exit;
        }
        
        $result = test_tmdb_api($api_key);
        break;
        
    case 'jikan':
        $result = test_jikan_api();
        break;
        
    case 'kitsu':
        $result = test_kitsu_api();
        break;
}

// Trả về kết quả
echo json_encode($result);

// Các hàm kiểm tra API
function test_youtube_api($api_key) {
    $url = "https://www.googleapis.com/youtube/v3/search?part=snippet&maxResults=1&q=anime&type=video&key=$api_key";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    if ($http_code == 200) {
        $data = json_decode($response, true);
        return [
            'success' => true,
            'message' => 'API key hợp lệ.',
            'data' => isset($data['items'][0]['snippet']['title']) ? 'Video: ' . $data['items'][0]['snippet']['title'] : 'Kết nối thành công!'
        ];
    } else {
        return [
            'success' => false,
            'message' => "Lỗi ($http_code): " . ($error ? $error : 'API key không hợp lệ hoặc đã hết quota.')
        ];
    }
}

function test_anilist_api($client_id, $client_secret) {
    $url = "https://graphql.anilist.co";
    $headers = ["Content-Type: application/json", "Accept: application/json"];
    
    $query = '
    query {
        Page(page: 1, perPage: 1) {
            media(type: ANIME, sort: POPULARITY_DESC) {
                id
                title {
                    romaji
                    english
                    native
                }
            }
        }
    }
    ';
    
    $data = json_encode(['query' => $query]);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    if ($http_code == 200) {
        $json = json_decode($response, true);
        if (isset($json['data']['Page']['media'][0]['title'])) {
            $title = $json['data']['Page']['media'][0]['title'];
            $anime_title = $title['english'] ?? $title['romaji'] ?? 'Unknown';
            return [
                'success' => true,
                'message' => 'Kết nối thành công.',
                'data' => 'Anime phổ biến nhất: ' . $anime_title
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Kết nối thành công nhưng không nhận được dữ liệu hợp lệ.'
            ];
        }
    } else {
        return [
            'success' => false,
            'message' => "Lỗi ($http_code): " . ($error ? $error : 'Không thể kết nối đến AniList API.')
        ];
    }
}

function test_tmdb_api($api_key) {
    $url = "https://api.themoviedb.org/3/movie/popular?api_key=$api_key&language=vi-VN&page=1";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    if ($http_code == 200) {
        $json = json_decode($response, true);
        if (isset($json['results'][0]['title'])) {
            return [
                'success' => true,
                'message' => 'API key hợp lệ.',
                'data' => 'Phim phổ biến: ' . $json['results'][0]['title'] . ' (Tổng phim: ' . $json['total_results'] . ')'
            ];
        } else {
            return [
                'success' => true,
                'message' => 'API key hợp lệ, nhưng không nhận được dữ liệu phim.'
            ];
        }
    } else {
        return [
            'success' => false,
            'message' => "Lỗi ($http_code): " . ($error ? $error : 'API key không hợp lệ.')
        ];
    }
}

function test_jikan_api() {
    $url = "https://api.jikan.moe/v4/anime/1";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    if ($http_code == 200) {
        $json = json_decode($response, true);
        if (isset($json['data']['title'])) {
            return [
                'success' => true,
                'message' => 'Kết nối thành công.',
                'data' => 'Anime: ' . $json['data']['title'] . ' (' . $json['data']['year'] . ')'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Kết nối thành công nhưng không nhận được dữ liệu hợp lệ.'
            ];
        }
    } else {
        return [
            'success' => false,
            'message' => "Lỗi ($http_code): " . ($error ? $error : 'Không thể kết nối đến Jikan API.')
        ];
    }
}

function test_kitsu_api() {
    $url = "https://kitsu.io/api/edge/anime?page[limit]=1&sort=-averageRating";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/vnd.api+json', 'Content-Type: application/vnd.api+json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    if ($http_code == 200) {
        $json = json_decode($response, true);
        if (isset($json['data'][0]['attributes']['canonicalTitle'])) {
            $anime = $json['data'][0]['attributes'];
            return [
                'success' => true,
                'message' => 'Kết nối thành công.',
                'data' => 'Anime: ' . $anime['canonicalTitle'] . ' (Đánh giá: ' . $anime['averageRating'] . ')'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Kết nối thành công nhưng không nhận được dữ liệu hợp lệ.'
            ];
        }
    } else {
        return [
            'success' => false,
            'message' => "Lỗi ($http_code): " . ($error ? $error : 'Không thể kết nối đến Kitsu API.')
        ];
    }
}