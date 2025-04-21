<?php
/**
 * Tập hợp các hàm bổ sung cho thao tác với cơ sở dữ liệu
 * File này tránh định nghĩa lại các hàm đã có trong db_connect.php gốc
 */

// Đảm bảo đã include file kết nối cơ sở dữ liệu chính
if (!function_exists('db_connect')) {
    require_once __DIR__ . '/../db_connect.php';
}

/**
 * Thực hiện truy vấn SELECT và trả về một bản ghi
 * 
 * @param string $query Câu truy vấn SQL
 * @param array $params Tham số cho câu truy vấn (tùy chọn)
 * @return array|null Mảng chứa bản ghi hoặc null nếu không tìm thấy
 */
function db_query_single($query, $params = []) {
    try {
        $db = db_connect();
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Lỗi truy vấn: " . $e->getMessage() . " - Query: " . $query);
        return null;
    }
}

/**
 * Lấy danh sách bảng trong cơ sở dữ liệu
 * 
 * @return array Danh sách tên bảng
 */
function db_get_tables() {
    $tables = [];
    
    try {
        $db = db_connect();
        
        switch (DB_TYPE) {
            case 'mysql':
            case 'mariadb':
                $stmt = $db->query("SHOW TABLES");
                while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
                    $tables[] = $row[0];
                }
                break;
                
            case 'postgresql':
                $stmt = $db->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'");
                while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
                    $tables[] = $row[0];
                }
                break;
                
            case 'sqlite':
                $stmt = $db->query("SELECT name FROM sqlite_master WHERE type='table'");
                while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
                    $tables[] = $row[0];
                }
                break;
        }
    } catch (PDOException $e) {
        if (DEBUG_MODE) {
            echo "Lỗi lấy danh sách bảng: " . $e->getMessage();
        }
    }
    
    return $tables;
}

/**
 * Kiểm tra sự tồn tại của bảng
 * 
 * @param string $table_name Tên bảng cần kiểm tra
 * @return bool True nếu bảng tồn tại, ngược lại False
 */
function db_table_exists($table_name) {
    try {
        $db = db_connect();
        
        switch (DB_TYPE) {
            case 'mysql':
            case 'mariadb':
                $stmt = $db->prepare("SHOW TABLES LIKE ?");
                $stmt->execute([$table_name]);
                return $stmt->rowCount() > 0;
                
            case 'postgresql':
                $stmt = $db->prepare("SELECT 1 FROM information_schema.tables WHERE table_schema = 'public' AND table_name = ?");
                $stmt->execute([$table_name]);
                return $stmt->rowCount() > 0;
                
            case 'sqlite':
                $stmt = $db->prepare("SELECT 1 FROM sqlite_master WHERE type='table' AND name = ?");
                $stmt->execute([$table_name]);
                return $stmt->rowCount() > 0;
        }
    } catch (PDOException $e) {
        if (DEBUG_MODE) {
            echo "Lỗi kiểm tra bảng: " . $e->getMessage();
        }
    }
    
    return false;
}

/**
 * Lấy thông tin cột của bảng
 * 
 * @param string $table_name Tên bảng
 * @return array Thông tin các cột
 */
function db_get_columns($table_name) {
    $columns = [];
    
    try {
        $db = db_connect();
        
        switch (DB_TYPE) {
            case 'mysql':
            case 'mariadb':
                $stmt = $db->query("DESCRIBE `$table_name`");
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $columns[] = [
                        'name' => $row['Field'],
                        'type' => $row['Type'],
                        'null' => $row['Null'] === 'YES',
                        'key' => $row['Key'],
                        'default' => $row['Default'],
                        'extra' => $row['Extra']
                    ];
                }
                break;
                
            case 'postgresql':
                $stmt = $db->query("
                    SELECT column_name, data_type, is_nullable, column_default
                    FROM information_schema.columns
                    WHERE table_schema = 'public' AND table_name = '$table_name'
                ");
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $columns[] = [
                        'name' => $row['column_name'],
                        'type' => $row['data_type'],
                        'null' => $row['is_nullable'] === 'YES',
                        'default' => $row['column_default']
                    ];
                }
                break;
                
            case 'sqlite':
                $stmt = $db->query("PRAGMA table_info(`$table_name`)");
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $columns[] = [
                        'name' => $row['name'],
                        'type' => $row['type'],
                        'null' => !$row['notnull'],
                        'default' => $row['dflt_value'],
                        'pk' => $row['pk']
                    ];
                }
                break;
        }
    } catch (PDOException $e) {
        if (DEBUG_MODE) {
            echo "Lỗi lấy thông tin cột: " . $e->getMessage();
        }
    }
    
    return $columns;
}

/**
 * Tạo backup cơ sở dữ liệu
 * 
 * @param string $file_path Đường dẫn file lưu backup
 * @return bool True nếu thành công, ngược lại False
 */
function db_backup($file_path) {
    try {
        $db = db_connect();
        $tables = db_get_tables();
        $output = '';
        
        // Tạo các câu lệnh DROP TABLE và CREATE TABLE cho từng bảng
        foreach ($tables as $table) {
            // Bỏ qua các bảng hệ thống
            if (strpos($table, 'sqlite_') === 0) {
                continue;
            }
            
            $output .= "-- Table structure for table `$table`\n";
            $output .= "DROP TABLE IF EXISTS `$table`;\n";
            
            switch (DB_TYPE) {
                case 'mysql':
                case 'mariadb':
                    $row = $db->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_NUM);
                    $output .= $row[1] . ";\n\n";
                    break;
                    
                case 'postgresql':
                    // PostgreSQL CREATE TABLE statement
                    $create_stmt = $db->query("
                        SELECT 
                            'CREATE TABLE ' || tablename || ' (' ||
                            string_agg(column_definition, ', ') ||
                            ');' as create_table
                        FROM (
                            SELECT 
                                tablename,
                                column_name || ' ' || data_type ||
                                CASE WHEN is_nullable = 'NO' THEN ' NOT NULL' ELSE '' END as column_definition
                            FROM information_schema.columns
                            WHERE table_schema = 'public' AND table_name = '$table'
                            ORDER BY ordinal_position
                        ) AS t
                        GROUP BY tablename
                    ");
                    $row = $create_stmt->fetch(PDO::FETCH_ASSOC);
                    $output .= $row['create_table'] . ";\n\n";
                    break;
                    
                case 'sqlite':
                    // SQLite CREATE TABLE statement
                    $stmt = $db->query("SELECT sql FROM sqlite_master WHERE type='table' AND name='$table'");
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    $output .= $row['sql'] . ";\n\n";
                    break;
            }
            
            // Lấy dữ liệu để INSERT
            $rows = $db->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
            if (count($rows) > 0) {
                $output .= "-- Dumping data for table `$table`\n";
                
                foreach ($rows as $row) {
                    $columns = implode('`, `', array_keys($row));
                    $values = [];
                    
                    foreach ($row as $value) {
                        if ($value === null) {
                            $values[] = 'NULL';
                        } elseif (is_numeric($value)) {
                            $values[] = $value;
                        } else {
                            $values[] = "'" . addslashes($value) . "'";
                        }
                    }
                    
                    $values_str = implode(', ', $values);
                    $output .= "INSERT INTO `$table` (`$columns`) VALUES ($values_str);\n";
                }
                
                $output .= "\n";
            }
        }
        
        // Lưu vào file
        return file_put_contents($file_path, $output) !== false;
    } catch (PDOException $e) {
        if (DEBUG_MODE) {
            echo "Lỗi backup cơ sở dữ liệu: " . $e->getMessage();
        }
        return false;
    }
}

/**
 * Thực thi file SQL
 * 
 * @param string $file_path Đường dẫn file SQL
 * @return bool True nếu thành công, ngược lại False
 */
function db_execute_file($file_path) {
    if (!file_exists($file_path)) {
        return false;
    }
    
    try {
        $db = db_connect();
        $sql = file_get_contents($file_path);
        
        // Phân tách thành các câu lệnh SQL riêng biệt
        switch (DB_TYPE) {
            case 'pgsql':
                // PostgreSQL yêu cầu xử lý đặc biệt
                $queries = explode(';', $sql);
                foreach ($queries as $query) {
                    $query = trim($query);
                    if (!empty($query)) {
                        $db->exec($query);
                    }
                }
                break;
                
            default:
                // MySQL, MariaDB, SQLite
                $db->exec($sql);
                break;
        }
        
        return true;
    } catch (PDOException $e) {
        if (DEBUG_MODE) {
            echo "Lỗi thực thi file SQL: " . $e->getMessage();
        }
        return false;
    }
}

/**
 * Lấy thông tin người dùng theo ID
 * 
 * @param int $id ID của người dùng
 * @return array|false Thông tin người dùng hoặc false nếu không tìm thấy
 */
function get_user_by_id($id) {
    try {
        $query = "SELECT users.*, roles.name as role_name, roles.slug as role_slug, roles.permissions
         FROM users
         JOIN roles ON users.role_id = roles.id
         WHERE users.id = ? AND users.status = 1";
        return db_query_single($query, [$id]);
    } catch (PDOException $e) {
        error_log("Lỗi truy vấn: " . $e->getMessage() . " - Query: " . $query);
        return false;
    }
}

/**
 * Lấy thông tin người dùng theo email
 * 
 * @param string $email Email của người dùng
 * @return array|false Thông tin người dùng hoặc false nếu không tìm thấy
 */
function get_user_by_email($email) {
    try {
        $query = "SELECT users.*, roles.name as role_name, roles.slug as role_slug, roles.permissions
         FROM users
         JOIN roles ON users.role_id = roles.id
         WHERE users.email = ? AND users.status = 1";
        return db_query_single($query, [$email]);
    } catch (PDOException $e) {
        error_log("Lỗi truy vấn: " . $e->getMessage() . " - Query: " . $query);
        return false;
    }
}

/**
 * Lấy thông tin người dùng theo số điện thoại
 * 
 * @param string $phone Số điện thoại của người dùng
 * @return array|false Thông tin người dùng hoặc false nếu không tìm thấy
 */
function get_user_by_phone($phone) {
    try {
        $query = "SELECT users.*, roles.name as role_name, roles.slug as role_slug, roles.permissions
         FROM users
         JOIN roles ON users.role_id = roles.id
         WHERE users.phone = ? AND users.status = 1";
        return db_query_single($query, [$phone]);
    } catch (PDOException $e) {
        error_log("Lỗi truy vấn: " . $e->getMessage() . " - Query: " . $query);
        return false;
    }
}

// Hàm get_user_settings() đã được khai báo trong functions.php