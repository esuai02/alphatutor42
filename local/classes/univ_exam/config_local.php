<?php
/**
 * 로컬 개발용 데이터베이스 설정 파일
 * SQLite를 사용하여 간단하게 테스트할 수 있습니다
 */

// SQLite 데이터베이스 파일 경로
define('DB_TYPE', 'sqlite');
define('DB_FILE', __DIR__ . '/database.sqlite');

// 테이블 prefix 설정
define('TABLE_PREFIX', 'alpha_');

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
 * SQLite 데이터베이스 연결 함수
 * @return PDO
 * @throws PDOException
 */
function getDBConnection() {
    try {
        $pdo = new PDO('sqlite:' . DB_FILE, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        
        // SQLite에서 외래 키 제약 조건 활성화
        $pdo->exec("PRAGMA foreign_keys = ON");
        
        // 테이블이 없으면 생성
        createTablesIfNotExists($pdo);
        
        writeLog("SQLite 데이터베이스 연결 성공", 'INFO');
        return $pdo;
        
    } catch (PDOException $e) {
        writeLog("SQLite 연결 실패: " . $e->getMessage(), 'ERROR');
        throw new PDOException("데이터베이스 연결 실패: " . $e->getMessage());
    }
}

/**
 * 테이블 생성 함수
 */
function createTablesIfNotExists($pdo) {
    $prefix = TABLE_PREFIX;
    
    // 문제집 테이블
    $pdo->exec("CREATE TABLE IF NOT EXISTS {$prefix}problem_sets (
        id INTEGER PRIMARY KEY,
        title TEXT NOT NULL,
        description TEXT,
        total_problems INTEGER DEFAULT 0,
        version TEXT DEFAULT '1.0',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    // 문제 테이블
    $pdo->exec("CREATE TABLE IF NOT EXISTS {$prefix}problems (
        id INTEGER PRIMARY KEY,
        problem_set_id INTEGER,
        problem_number INTEGER,
        title TEXT NOT NULL,
        content TEXT,
        category TEXT,
        difficulty TEXT DEFAULT 'medium',
        correct_answer TEXT,
        explanation TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (problem_set_id) REFERENCES {$prefix}problem_sets(id)
    )");
    
    // 선택지 테이블
    $pdo->exec("CREATE TABLE IF NOT EXISTS {$prefix}choices (
        id INTEGER PRIMARY KEY,
        problem_id INTEGER,
        choice_number INTEGER,
        content TEXT NOT NULL,
        is_correct INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (problem_id) REFERENCES {$prefix}problems(id)
    )");
    
    // 사용자 답안 테이블
    $pdo->exec("CREATE TABLE IF NOT EXISTS {$prefix}user_answers (
        id INTEGER PRIMARY KEY,
        problem_id INTEGER,
        user_answer TEXT,
        is_correct INTEGER DEFAULT 0,
        answered_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (problem_id) REFERENCES {$prefix}problems(id)
    )");
    
    // 샘플 데이터 생성
    createSampleData($pdo);
    
    writeLog("테이블 생성 완료", 'INFO');
}

/**
 * 샘플 데이터 생성
 */
function createSampleData($pdo) {
    $prefix = TABLE_PREFIX;
    
    // 문제집이 이미 있는지 확인
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM {$prefix}problem_sets");
    $stmt->execute();
    $count = $stmt->fetch()['count'];
    
    if ($count == 0) {
        // 기본 문제집 생성
        $stmt = $pdo->prepare("INSERT INTO {$prefix}problem_sets (title, description, total_problems) VALUES (?, ?, ?)");
        $stmt->execute(['기본 문제집', '수학 문제 학습 시스템 테스트용 문제집', 3]);
        
        $problemSetId = $pdo->lastInsertId();
        
        // 샘플 문제 생성
        $problems = [
            [
                'problem_number' => 1,
                'title' => '기본 산술 - 덧셈',
                'content' => '5 + 3 = ?',
                'category' => '산술',
                'difficulty' => 'easy',
                'correct_answer' => '8',
                'explanation' => '5와 3을 더하면 8입니다.'
            ],
            [
                'problem_number' => 2,
                'title' => '기본 산술 - 곱셈',
                'content' => '7 × 4 = ?',
                'category' => '산술',
                'difficulty' => 'medium',
                'correct_answer' => '28',
                'explanation' => '7에 4를 곱하면 28입니다.'
            ],
            [
                'problem_number' => 3,
                'title' => '대수 - 방정식',
                'content' => 'x + 5 = 12일 때, x의 값은?',
                'category' => '대수',
                'difficulty' => 'medium',
                'correct_answer' => '7',
                'explanation' => 'x = 12 - 5 = 7입니다.'
            ]
        ];
        
        foreach ($problems as $problem) {
            $stmt = $pdo->prepare("INSERT INTO {$prefix}problems (problem_set_id, problem_number, title, content, category, difficulty, correct_answer, explanation) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $problemSetId,
                $problem['problem_number'],
                $problem['title'],
                $problem['content'],
                $problem['category'],
                $problem['difficulty'],
                $problem['correct_answer'],
                $problem['explanation']
            ]);
        }
        
        writeLog("샘플 데이터 생성 완료", 'INFO');
    }
}

// 나머지 헬퍼 함수들 (원본 config.php에서 복사)
function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function sendErrorResponse($message, $statusCode = 400) {
    sendJsonResponse(['error' => $message], $statusCode);
}

function sendSuccessResponse($data = null, $message = 'Success') {
    $response = ['success' => true, 'message' => $message];
    if ($data !== null) {
        $response['data'] = $data;
    }
    sendJsonResponse($response);
}

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

function validateInput($data, $rules) {
    $errors = [];
    
    foreach ($rules as $field => $rule) {
        $value = $data[$field] ?? null;
        
        if (isset($rule['required']) && $rule['required'] && empty($value)) {
            $errors[] = "{$field}는 필수 입력 항목입니다.";
            continue;
        }
        
        if (!empty($value) && isset($rule['type'])) {
            switch ($rule['type']) {
                case 'string':
                    if (!is_string($value)) $errors[] = "{$field}는 문자열이어야 합니다.";
                    break;
                case 'integer':
                    if (!is_int($value) && !ctype_digit($value)) $errors[] = "{$field}는 정수여야 합니다.";
                    break;
                case 'array':
                    if (!is_array($value)) $errors[] = "{$field}는 배열이어야 합니다.";
                    break;
            }
        }
        
        if (!empty($value) && isset($rule['min_length']) && strlen($value) < $rule['min_length']) {
            $errors[] = "{$field}는 최소 {$rule['min_length']}자 이상이어야 합니다.";
        }
        
        if (!empty($value) && isset($rule['max_length']) && strlen($value) > $rule['max_length']) {
            $errors[] = "{$field}는 최대 {$rule['max_length']}자 이하여야 합니다.";
        }
    }
    
    return empty($errors) ? true : $errors;
}
?> 