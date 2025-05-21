<?php
/**
 * Lọc Phim - Xử lý database
 */

/**
 * Class Database
 * Lớp xử lý kết nối và truy vấn database
 */
class Database {
    /** @var PDO */
    private $pdo;
    
    /** @var string */
    private $type;
    
    /** @var string */
    private $dbType;
    
    /** @var array */
    private $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    /**
     * Database constructor
     */
    public function __construct() {
        $this->type = DB_TYPE;
        $this->dbType = DB_TYPE;
        $this->connect();
    }
    
    /**
     * Lấy loại database đang sử dụng
     * @return string
     */
    public function getDbType() {
        return $this->dbType;
    }
    
    /**
     * Thiết lập kết nối đến database
     */
    private function connect() {
        try {
            switch ($this->type) {
                case 'mysql':
                    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
                    $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $this->options);
                    break;
                    
                case 'pgsql':
                    $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME;
                    $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $this->options);
                    break;
                    
                case 'sqlite':
                    $dsn = "sqlite:" . DB_NAME;
                    $this->pdo = new PDO($dsn, null, null, $this->options);
                    break;
                    
                default:
                    throw new Exception("Loại database không được hỗ trợ: " . $this->type);
            }
        } catch (PDOException $e) {
            $this->handleError($e);
        }
    }
    
    /**
     * Xử lý lỗi kết nối database
     */
    private function handleError($e) {
        $error = $e->getMessage();
        
        // Kiểm tra lỗi "could not find driver"
        if (strpos($error, 'could not find driver') !== false) {
            if ($this->type === 'mysql') {
                $error = "Không tìm thấy driver MySQL (pdo_mysql). Vui lòng cài đặt extension php-mysql.";
            } elseif ($this->type === 'pgsql') {
                $error = "Không tìm thấy driver PostgreSQL (pdo_pgsql). Vui lòng cài đặt extension php-pgsql.";
            } elseif ($this->type === 'sqlite') {
                $error = "Không tìm thấy driver SQLite (pdo_sqlite). Vui lòng cài đặt extension php-sqlite3.";
            }
        }
        
        // Kiểm tra lỗi kết nối
        else if (strpos($error, 'Connection refused') !== false) {
            $error = "Không thể kết nối đến máy chủ database. Vui lòng kiểm tra lại thông tin kết nối và đảm bảo máy chủ đang hoạt động.";
        }
        
        // Kiểm tra lỗi xác thực
        else if (strpos($error, 'password authentication failed') !== false) {
            $error = "Sai thông tin đăng nhập database. Vui lòng kiểm tra lại tên đăng nhập hoặc mật khẩu.";
        }
        
        // Kiểm tra lỗi database không tồn tại
        else if (strpos($error, 'database') !== false && strpos($error, 'does not exist') !== false) {
            $error = "Database '" . DB_NAME . "' không tồn tại. Vui lòng tạo database trước khi kết nối.";
        }
        
        if (DEBUG_MODE) {
            die("Lỗi kết nối database: " . $error);
        } else {
            die("Có lỗi xảy ra khi kết nối đến cơ sở dữ liệu. Vui lòng liên hệ quản trị viên.");
        }
    }
    
    /**
     * Thực thi truy vấn và trả về tất cả các kết quả
     * 
     * @param string $sql Câu truy vấn SQL
     * @param array $params Tham số truy vấn
     * @return array Kết quả truy vấn
     */
    public function getAll($sql, $params = []) {
        try {
            // Điều chỉnh SQL cho phù hợp với loại database
            $sql = $this->adjustSqlForDbType($sql);
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            if (DEBUG_MODE) {
                die("Database query error: " . $e->getMessage() . " - SQL: " . $sql);
            } else {
                die("Có lỗi xảy ra khi truy vấn dữ liệu.");
            }
        }
    }
    
    /**
     * Điều chỉnh câu lệnh SQL dựa trên loại database đang sử dụng
     * @param string $sql Câu lệnh SQL gốc
     * @return string Câu lệnh SQL đã được điều chỉnh
     */
    private function adjustSqlForDbType($sql) {
        if ($this->dbType === 'pgsql') {
            // Thay thế tham chiếu từ m.country_id sang m.country
            $sql = str_replace('LEFT JOIN countries c ON m.country_id = c.id', 'LEFT JOIN countries c ON m.country = c.name', $sql);
            $sql = str_replace('LEFT JOIN countries c ON m.country_id=c.id', 'LEFT JOIN countries c ON m.country=c.name', $sql);
            
            // Thay thế m.status = 'published' sang is_published = TRUE
            $sql = str_replace("m.status = 'published'", 'm.is_published = TRUE', $sql);
            $sql = str_replace("m.status='published'", 'm.is_published=TRUE', $sql);
            $sql = str_replace("e.status = 'published'", 'e.is_published = TRUE', $sql);
            $sql = str_replace("e.status='published'", 'e.is_published=TRUE', $sql);
            
            // Thay thế tham chiếu khác
            $sql = str_replace('ORDER BY m.release_date', 'ORDER BY m.release_year', $sql);
            
            // Xử lý trường season_number trong episodes
            if (strpos($sql, 'season_number') !== false) {
                // Cột season_number không tồn tại trong PostgreSQL, bỏ qua nó trong ORDER BY
                $sql = str_replace('ORDER BY season_number ASC, episode_number ASC', 'ORDER BY episode_number ASC', $sql);
                
                // Nếu có tham chiếu khác đến season_number, thêm 1 làm giá trị mặc định
                $sql = preg_replace('/e\.season_number\s*=\s*(\d+)/', '1 = $1', $sql);
                $sql = preg_replace('/episode\[\'season_number\'\]\s*===\s*(\w+)/', '1 === $1', $sql);
                $sql = preg_replace('/season_number\s*=\s*1/', '1=1', $sql);
            }
            
            // Xử lý trường hợp bảng actors không tồn tại trong PostgreSQL
            if (strpos($sql, 'actors') !== false) {
                // Trong PostgreSQL, bảng actors có thể được thay thế bằng persons
                $sql = str_replace('FROM actors', 'FROM persons', $sql);
                $sql = str_replace('JOIN actors', 'JOIN persons', $sql);
                
                // Điều chỉnh tham chiếu từ actor_id thành person_id nếu cần
                $sql = str_replace('JOIN movie_actors ma ON a.id = ma.actor_id', 'JOIN movie_directors md ON a.id = md.person_id', $sql);
                
                // Nếu không có bảng thay thế, có thể trả về empty set một cách an toàn
                if (strpos($sql, 'SELECT a.*') !== false && strpos($sql, 'WHERE ma.movie_id') !== false) {
                    // Thay thế bằng một truy vấn trả về empty set
                    $sql = "SELECT NULL AS id, '' AS name, '' AS slug, '' AS biography, '' AS avatar, NOW() AS created_at, NOW() AS updated_at WHERE 1=0";
                }
            }
        }
        
        return $sql;
    }
    
    /**
     * Thực thi truy vấn và trả về kết quả đầu tiên
     * 
     * @param string $sql Câu truy vấn SQL
     * @param array $params Tham số truy vấn
     * @return array|null Kết quả truy vấn hoặc null nếu không có
     */
    public function get($sql, $params = []) {
        try {
            // Điều chỉnh SQL cho phù hợp với loại database
            $sql = $this->adjustSqlForDbType($sql);
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch();
            return $result !== false ? $result : null;
        } catch (PDOException $e) {
            if (DEBUG_MODE) {
                die("Database query error: " . $e->getMessage() . " - SQL: " . $sql);
            } else {
                die("Có lỗi xảy ra khi truy vấn dữ liệu.");
            }
        }
    }
    
    /**
     * Thực thi truy vấn và trả về một giá trị
     * 
     * @param string $sql Câu truy vấn SQL
     * @param array $params Tham số truy vấn
     * @return mixed Giá trị đầu tiên của kết quả đầu tiên hoặc null
     */
    public function getValue($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            if (DEBUG_MODE) {
                die("Database query error: " . $e->getMessage() . " - SQL: " . $sql);
            } else {
                die("Có lỗi xảy ra khi truy vấn dữ liệu.");
            }
        }
    }
    
    /**
     * Thực thi truy vấn không trả về kết quả
     * 
     * @param string $sql Câu truy vấn SQL
     * @param array $params Tham số truy vấn
     * @return int Số dòng bị ảnh hưởng
     */
    public function execute($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            if (DEBUG_MODE) {
                die("Database query error: " . $e->getMessage() . " - SQL: " . $sql);
            } else {
                die("Có lỗi xảy ra khi thực thi truy vấn.");
            }
        }
    }
    
    /**
     * Chèn dữ liệu vào bảng
     * 
     * @param string $table Tên bảng
     * @param array $data Dữ liệu cần chèn
     * @return int ID của dòng vừa chèn
     */
    public function insert($table, $data) {
        try {
            $columns = implode(', ', array_keys($data));
            $placeholders = implode(', ', array_fill(0, count($data), '?'));
            
            $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(array_values($data));
            
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            if (DEBUG_MODE) {
                die("Database query error: " . $e->getMessage() . " - Table: {$table}");
            } else {
                die("Có lỗi xảy ra khi thêm dữ liệu.");
            }
        }
    }
    
    /**
     * Cập nhật dữ liệu trong bảng
     * 
     * @param string $table Tên bảng
     * @param array $data Dữ liệu cần cập nhật
     * @param string $where Điều kiện WHERE
     * @param array $params Tham số cho điều kiện WHERE
     * @return int Số dòng bị ảnh hưởng
     */
    public function update($table, $data, $where, $params = []) {
        try {
            $set = [];
            foreach ($data as $column => $value) {
                $set[] = "{$column} = ?";
            }
            
            $sql = "UPDATE {$table} SET " . implode(', ', $set) . " WHERE {$where}";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(array_merge(array_values($data), $params));
            
            return $stmt->rowCount();
        } catch (PDOException $e) {
            if (DEBUG_MODE) {
                die("Database query error: " . $e->getMessage() . " - Table: {$table}");
            } else {
                die("Có lỗi xảy ra khi cập nhật dữ liệu.");
            }
        }
    }
    
    /**
     * Xóa dữ liệu từ bảng
     * 
     * @param string $table Tên bảng
     * @param string $where Điều kiện WHERE
     * @param array $params Tham số cho điều kiện WHERE
     * @return int Số dòng bị ảnh hưởng
     */
    public function delete($table, $where, $params = []) {
        try {
            $sql = "DELETE FROM {$table} WHERE {$where}";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->rowCount();
        } catch (PDOException $e) {
            if (DEBUG_MODE) {
                die("Database query error: " . $e->getMessage() . " - Table: {$table}");
            } else {
                die("Có lỗi xảy ra khi xóa dữ liệu.");
            }
        }
    }
    
    /**
     * Bắt đầu transaction
     */
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }
    
    /**
     * Commit transaction
     */
    public function commit() {
        return $this->pdo->commit();
    }
    
    /**
     * Rollback transaction
     */
    public function rollback() {
        return $this->pdo->rollBack();
    }
    
    /**
     * Tạo bảng mới
     * 
     * @param string $table Tên bảng
     * @param array $columns Định nghĩa các cột
     */
    public function createTable($table, $columns) {
        try {
            $columnsStr = implode(', ', $columns);
            
            // Điều chỉnh cú pháp SQL cho từng loại database
            switch ($this->type) {
                case 'mysql':
                    $sql = "CREATE TABLE IF NOT EXISTS {$table} ({$columnsStr}) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
                    break;
                    
                case 'pgsql':
                    $sql = "CREATE TABLE IF NOT EXISTS {$table} ({$columnsStr})";
                    break;
                    
                case 'sqlite':
                    $sql = "CREATE TABLE IF NOT EXISTS {$table} ({$columnsStr})";
                    break;
                    
                default:
                    throw new Exception("Loại database không được hỗ trợ: " . $this->type);
            }
            
            return $this->execute($sql);
        } catch (PDOException $e) {
            if (DEBUG_MODE) {
                die("Database query error: " . $e->getMessage() . " - Table: {$table}");
            } else {
                die("Có lỗi xảy ra khi tạo bảng.");
            }
        }
    }
    
    /**
     * Tạo index cho bảng
     * 
     * @param string $table Tên bảng
     * @param string $indexName Tên index
     * @param string|array $columns Cột hoặc các cột để tạo index
     */
    public function createIndex($table, $indexName, $columns) {
        try {
            // Chuyển đổi các cột thành chuỗi
            if (is_array($columns)) {
                $columns = implode(', ', $columns);
            }
            
            $sql = "CREATE INDEX IF NOT EXISTS {$indexName} ON {$table} ({$columns})";
            return $this->execute($sql);
        } catch (PDOException $e) {
            if (DEBUG_MODE) {
                die("Database query error: " . $e->getMessage() . " - Table: {$table}");
            } else {
                die("Có lỗi xảy ra khi tạo index.");
            }
        }
    }
    
    /**
     * Kiểm tra xem bảng có tồn tại không
     * 
     * @param string $table Tên bảng
     * @return bool True nếu bảng tồn tại, ngược lại là false
     */
    public function tableExists($table) {
        try {
            switch ($this->type) {
                case 'mysql':
                    $result = $this->getValue("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = ? AND table_name = ?", [DB_NAME, $table]);
                    break;
                    
                case 'pgsql':
                    $result = $this->getValue("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'public' AND table_name = ?", [$table]);
                    break;
                    
                case 'sqlite':
                    $result = $this->getValue("SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name = ?", [$table]);
                    break;
                    
                default:
                    throw new Exception("Loại database không được hỗ trợ: " . $this->type);
            }
            
            return $result > 0;
        } catch (PDOException $e) {
            if (DEBUG_MODE) {
                die("Database query error: " . $e->getMessage());
            } else {
                die("Có lỗi xảy ra khi kiểm tra bảng.");
            }
        }
    }
    
    /**
     * Thêm cột vào bảng
     * 
     * @param string $table Tên bảng
     * @param string $column Tên cột
     * @param string $definition Định nghĩa cột
     */
    public function addColumn($table, $column, $definition) {
        try {
            $sql = "ALTER TABLE {$table} ADD COLUMN {$column} {$definition}";
            return $this->execute($sql);
        } catch (PDOException $e) {
            if (DEBUG_MODE) {
                die("Database query error: " . $e->getMessage() . " - Table: {$table}");
            } else {
                die("Có lỗi xảy ra khi thêm cột.");
            }
        }
    }
    
    /**
     * Lấy tên của tất cả các bảng
     * 
     * @return array Danh sách tên bảng
     */
    public function getTables() {
        try {
            switch ($this->type) {
                case 'mysql':
                    return $this->getAll("SELECT table_name FROM information_schema.tables WHERE table_schema = ?", [DB_NAME]);
                    
                case 'pgsql':
                    return $this->getAll("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'");
                    
                case 'sqlite':
                    return $this->getAll("SELECT name FROM sqlite_master WHERE type='table'");
                    
                default:
                    throw new Exception("Loại database không được hỗ trợ: " . $this->type);
            }
        } catch (PDOException $e) {
            if (DEBUG_MODE) {
                die("Database query error: " . $e->getMessage());
            } else {
                die("Có lỗi xảy ra khi lấy danh sách bảng.");
            }
        }
    }
    
    /**
     * Lấy thông tin cấu trúc của bảng
     * 
     * @param string $table Tên bảng
     * @return array Thông tin cấu trúc bảng
     */
    public function getTableStructure($table) {
        try {
            switch ($this->type) {
                case 'mysql':
                    return $this->getAll("DESCRIBE {$table}");
                    
                case 'pgsql':
                    return $this->getAll("
                        SELECT column_name, data_type, is_nullable, column_default
                        FROM information_schema.columns
                        WHERE table_schema = 'public' AND table_name = ?
                        ORDER BY ordinal_position
                    ", [$table]);
                    
                case 'sqlite':
                    return $this->getAll("PRAGMA table_info({$table})");
                    
                default:
                    throw new Exception("Loại database không được hỗ trợ: " . $this->type);
            }
        } catch (PDOException $e) {
            if (DEBUG_MODE) {
                die("Database query error: " . $e->getMessage() . " - Table: {$table}");
            } else {
                die("Có lỗi xảy ra khi lấy cấu trúc bảng.");
            }
        }
    }
    
    /**
     * Trả về PDO instance
     * 
     * @return PDO Instance của PDO
     */
    public function getPdo() {
        return $this->pdo;
    }
    
    /**
     * Lấy loại database đang sử dụng
     * 
     * @return string Loại database
     */
    public function getDatabaseType() {
        return $this->dbType;
    }
}