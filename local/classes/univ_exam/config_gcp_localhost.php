<?php
/**
 * GCP 서버 내부용 데이터베이스 설정 파일
 * localhost 연결 방식 (서버 내부에서 사용)
 */

// GCP 서버 내부 데이터베이스 연결 정보 (localhost 사용)
define('DB_HOST', 'localhost');        // 서버 내부에서는 localhost 사용
define('DB_NAME', 'moodle');           // 기존 moodle DB 사용
define('DB_USER', 'bessi02');          // GCP DB 사용자
define('DB_PASS', '@MCtrigd7128');     // GCP DB 비밀번호
define('DB_PORT', 3306);               // MariaDB 포트
define('DB_CHARSET', 'utf8mb4');

// 테이블 prefix (moodle DB 내에서 충돌 방지)
define('TABLE_PREFIX', 'alpha_');      // 테이블 이름 충돌 방지

// 디버그 모드 
define('DEBUG_MODE', true);

// 업로드 설정
define('UPLOAD_DIR', './uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml']);

// 에러 로깅 설정
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

/**
 * 데이터베이스 연결 함수 (localhost 연결)
 * @return PDO
 * @throws PDOException
 */
function getDBConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 10,            // 연결 타임아웃 10초 (localhost이므로 단축)
            PDO::ATTR_PERSISTENT => false,      // 지속적 연결 비활성화
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ]);
        
        writeLog("GCP MariaDB (localhost) 연결 성공", 'INFO');
        return $pdo;
        
    } catch (PDOException $e) {
        writeLog("GCP MariaDB 연결 실패: " . $e->getMessage(), 'ERROR');
        
        if (DEBUG_MODE) {
            throw new PDOException("DB 연결 실패 (localhost): " . $e->getMessage());
        } else {
            throw new PDOException("데이터베이스 연결 실패");
        }
    }
}

/**
 * JSON 응답 헬퍼 함수
 */
function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * 에러 응답 헬퍼 함수
 */
function sendErrorResponse($message, $statusCode = 400) {
    sendJsonResponse(['error' => $message], $statusCode);
}

/**
 * 성공 응답 헬퍼 함수
 */
function sendSuccessResponse($data = null, $message = 'Success') {
    $response = ['success' => true, 'message' => $message];
    if ($data !== null) {
        $response['data'] = $data;
    }
    sendJsonResponse($response);
}

/**
 * 로그 기록 함수
 */
function writeLog($message, $level = 'INFO') {
    if (DEBUG_MODE) {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
        
        $logFile = './logs/app.log';
        $logDir = dirname($logFile);
        
        if (!file_exists($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }
}

/**
 * 문제집 기본 데이터 생성 함수 (테이블 prefix 지원)
 */
function createDefaultProblemSet($pdo) {
    try {
        $prefix = TABLE_PREFIX;
        
        // 기본 문제집이 있는지 확인
        $stmt = $pdo->prepare("SELECT id FROM {$prefix}problem_sets WHERE id = 1");
        $stmt->execute();
        
        if (!$stmt->fetch()) {
            // 기본 문제집 생성
            $stmt = $pdo->prepare("
                INSERT INTO {$prefix}problem_sets (id, title, description, total_problems, version) 
                VALUES (1, '기본 문제집', '수학 문제 학습을 위한 기본 문제집입니다.', 30, '1.0')
            ");
            $stmt->execute();
            
            writeLog("기본 문제집 생성 완료", 'INFO');
            return true;
        } else {
            writeLog("기본 문제집 이미 존재", 'INFO');
            return true;
        }
        
    } catch (PDOException $e) {
        writeLog("기본 문제집 생성 실패: " . $e->getMessage(), 'ERROR');
        throw $e;
    }
}

/**
 * 테이블 존재 여부 확인 함수
 */
function checkTablesExist($pdo) {
    $prefix = TABLE_PREFIX;
    $requiredTables = [
        $prefix . 'problem_sets',
        $prefix . 'problems',
        $prefix . 'problem_conditions',
        $prefix . 'analysis_insights',
        $prefix . 'highlight_tags',
        $prefix . 'solution_steps',
        $prefix . 'key_points'
    ];
    
    $existingTables = [];
    $missingTables = [];
    
    foreach ($requiredTables as $table) {
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                $existingTables[] = $table;
            } else {
                $missingTables[] = $table;
            }
        } catch (PDOException $e) {
            $missingTables[] = $table;
        }
    }
    
    return [
        'existing' => $existingTables,
        'missing' => $missingTables
    ];
}
?> 