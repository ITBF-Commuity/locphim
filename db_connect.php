<?php
/**
 * Kết nối đến cơ sở dữ liệu dựa trên cấu hình trong config.php
 * Hỗ trợ nhiều loại cơ sở dữ liệu: MySQL, PostgreSQL, SQLite, MariaDB
 */

// Nếu chưa include config thì include vào
if (!defined('SITE_NAME')) {
    require_once __DIR__ . '/config.php';
}

/**
 * Kết nối đến cơ sở dữ liệu và trả về đối tượng PDO
 * 
 * @return PDO Đối tượng PDO kết nối đến cơ sở dữ liệu
 * @throws PDOException Nếu kết nối thất bại
 */
function db_connect() {
    static $pdo = null;
    
    if ($pdo !== null) {
        return $pdo;
    }
    
    try {
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        switch (DB_TYPE) {
            case 'mysql':
            case 'mariadb':
                $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
                $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
                break;
                
            case 'pgsql':
                if (defined('DB_CONNECTION_STRING') && !empty(DB_CONNECTION_STRING)) {
                    // Sử dụng DATABASE_URL trực tiếp
                    $pdo = new PDO(DB_CONNECTION_STRING, null, null, $options);
                } else {
                    $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME;
                    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
                }
                break;
                
            case 'sqlite':
                $pdo = new PDO("sqlite:" . SQLITE_PATH, null, null, $options);
                break;
                
            default:
                throw new PDOException("Loại cơ sở dữ liệu không được hỗ trợ: " . DB_TYPE);
        }
        
        return $pdo;
    } catch (PDOException $e) {
        // Nếu ở chế độ debug, hiển thị lỗi chi tiết
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            echo "Lỗi kết nối cơ sở dữ liệu: " . $e->getMessage();
        } else {
            error_log("Lỗi kết nối cơ sở dữ liệu: " . $e->getMessage());
        }
        
        throw $e;
    }
}

/**
 * Thực thi một truy vấn và trả về tất cả kết quả
 * 
 * @param string $query Câu truy vấn SQL
 * @param array $params Tham số của truy vấn (nếu có)
 * @return array Mảng kết quả truy vấn
 */
function db_query($query, $params = []) {
    try {
        $pdo = db_connect();
        $statement = $pdo->prepare($query);
        $statement->execute($params);
        return $statement->fetchAll();
    } catch (PDOException $e) {
        error_log("Lỗi truy vấn: " . $e->getMessage() . " - Query: $query");
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            echo "Lỗi truy vấn: " . $e->getMessage();
        }
        return [];
    }
}

/**
 * Lấy một dòng dữ liệu duy nhất
 * 
 * @param string $query Câu truy vấn SQL
 * @param array $params Tham số của truy vấn (nếu có)
 * @return array|null Dòng dữ liệu hoặc null nếu không tìm thấy
 */
function db_fetch_row($query, $params = []) {
    try {
        $pdo = db_connect();
        $statement = $pdo->prepare($query);
        $statement->execute($params);
        $result = $statement->fetch();
        return $result !== false ? $result : null;
    } catch (PDOException $e) {
        error_log("Lỗi truy vấn: " . $e->getMessage() . " - Query: $query");
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            echo "Lỗi truy vấn: " . $e->getMessage();
        }
        return null;
    }
}

/**
 * Lấy nhiều dòng dữ liệu
 * 
 * @param string $query Câu truy vấn SQL
 * @param array $params Tham số của truy vấn (nếu có)
 * @return array Mảng dữ liệu
 */
function db_fetch_all($query, $params = []) {
    return db_query($query, $params);
}

/**
 * Lấy một giá trị duy nhất
 * 
 * @param string $query Câu truy vấn SQL
 * @param array $params Tham số của truy vấn (nếu có)
 * @param mixed $default Giá trị mặc định nếu không tìm thấy
 * @return mixed Giá trị hoặc giá trị mặc định nếu không tìm thấy
 */
function db_fetch_value($query, $params = [], $default = null) {
    try {
        $pdo = db_connect();
        $statement = $pdo->prepare($query);
        $statement->execute($params);
        $result = $statement->fetchColumn();
        return $result !== false ? $result : $default;
    } catch (PDOException $e) {
        error_log("Lỗi truy vấn: " . $e->getMessage() . " - Query: $query");
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            echo "Lỗi truy vấn: " . $e->getMessage();
        }
        return $default;
    }
}

/**
 * Chèn dữ liệu vào bảng
 * 
 * @param string $table Tên bảng
 * @param array $data Dữ liệu cần chèn (associative array)
 * @return int|bool ID được tạo hoặc false nếu thất bại
 */
function db_insert($table, $data) {
    try {
        // Xây dựng câu truy vấn INSERT
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $query = "INSERT INTO $table ($columns) VALUES ($placeholders)";
        
        $pdo = db_connect();
        $statement = $pdo->prepare($query);
        $result = $statement->execute(array_values($data));
        
        if ($result) {
            return $pdo->lastInsertId();
        }
        
        return false;
    } catch (PDOException $e) {
        error_log("Lỗi chèn dữ liệu: " . $e->getMessage() . " - Table: $table");
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            echo "Lỗi chèn dữ liệu: " . $e->getMessage();
        }
        return false;
    }
}

/**
 * Cập nhật dữ liệu trong bảng
 * 
 * @param string $table Tên bảng
 * @param array $data Dữ liệu cần cập nhật (associative array)
 * @param string $where Điều kiện WHERE
 * @param array $params Tham số cho điều kiện WHERE
 * @return int Số hàng bị ảnh hưởng
 */
function db_update($table, $data, $where, $params = []) {
    try {
        // Xây dựng câu truy vấn UPDATE
        $set = [];
        foreach ($data as $column => $value) {
            $set[] = "$column = ?";
        }
        
        $set_clause = implode(', ', $set);
        $query = "UPDATE $table SET $set_clause WHERE $where";
        
        // Kết hợp mảng tham số
        $all_params = array_merge(array_values($data), $params);
        
        $pdo = db_connect();
        $statement = $pdo->prepare($query);
        $statement->execute($all_params);
        
        return $statement->rowCount();
    } catch (PDOException $e) {
        error_log("Lỗi cập nhật dữ liệu: " . $e->getMessage() . " - Table: $table");
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            echo "Lỗi cập nhật dữ liệu: " . $e->getMessage();
        }
        return 0;
    }
}

/**
 * Xóa dữ liệu từ bảng
 * 
 * @param string $table Tên bảng
 * @param string $where Điều kiện WHERE
 * @param array $params Tham số cho điều kiện WHERE
 * @return int Số hàng bị ảnh hưởng
 */
function db_delete($table, $where, $params = []) {
    try {
        $query = "DELETE FROM $table WHERE $where";
        
        $pdo = db_connect();
        $statement = $pdo->prepare($query);
        $statement->execute($params);
        
        return $statement->rowCount();
    } catch (PDOException $e) {
        error_log("Lỗi xóa dữ liệu: " . $e->getMessage() . " - Table: $table");
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            echo "Lỗi xóa dữ liệu: " . $e->getMessage();
        }
        return 0;
    }
}

/**
 * Bắt đầu một giao dịch
 */
function db_begin_transaction() {
    $pdo = db_connect();
    $pdo->beginTransaction();
}

/**
 * Hoàn tất giao dịch
 */
function db_commit() {
    $pdo = db_connect();
    $pdo->commit();
}

/**
 * Hủy bỏ giao dịch
 */
function db_rollback() {
    $pdo = db_connect();
    $pdo->rollBack();
}
?>