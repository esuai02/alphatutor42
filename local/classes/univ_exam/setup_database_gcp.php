<?php
/**
 * GCP MariaDB 서버 자동 설정 스크립트
 * 브라우저에서 실행하여 GCP 데이터베이스를 자동으로 생성합니다.
 */

// 에러 출력 활성화
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 설정 상수
define('GCP_HOST', '34.64.175.237');
define('GCP_USER', 'bessi02');
define('GCP_PASS', '@MCtrigd7128');
define('GCP_PORT', 3306);

// HTML 출력 시작
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name=\"width=device-width, initial-scale=1.0\">
    <title>GCP MariaDB 서버 설정</title>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            max-width: 900px; 
            margin: 30px auto; 
            padding: 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #333;
        }
        .container {
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        h1 {
            color: #4a5568;
            text-align: center;
            margin-bottom: 30px;
            font-size: 2.2em;
        }
        .success { 
            color: #38a169; 
            background: #f0fff4; 
            padding: 15px; 
            border-radius: 8px; 
            margin: 15px 0; 
            border-left: 5px solid #38a169;
        }
        .error { 
            color: #e53e3e; 
            background: #fff5f5; 
            padding: 15px; 
            border-radius: 8px; 
            margin: 15px 0; 
            border-left: 5px solid #e53e3e;
        }
        .info { 
            color: #3182ce; 
            background: #ebf8ff; 
            padding: 15px; 
            border-radius: 8px; 
            margin: 15px 0; 
            border-left: 5px solid #3182ce;
        }
        .warning {
            color: #d69e2e;
            background: #fffbeb;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            border-left: 5px solid #d69e2e;
        }
        .step { 
            margin: 25px 0; 
            padding: 20px; 
            border-left: 4px solid #667eea; 
            background: #f8f9ff;
            border-radius: 0 8px 8px 0;
        }
        .code {
            background: #2d3748;
            color: #e2e8f0;
            padding: 15px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            margin: 10px 0;
            overflow-x: auto;
        }
        .server-info {
            background: #e6fffa;
            border: 2px solid #319795;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            color: #718096;
        }
        .btn {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            margin: 10px 5px;
            transition: background 0.3s;
        }
        .btn:hover {
            background: #5a67d8;
        }
        pre {
            white-space: pre-wrap;
            word-wrap: break-word;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🌐 GCP MariaDB 서버 설정</h1>
        
        <div class="server-info">
            <h3>🔧 GCP 서버 정보</h3>
            <p><strong>서버 IP:</strong> <?php echo GCP_HOST; ?></p>
            <p><strong>사용자:</strong> <?php echo GCP_USER; ?></p>
            <p><strong>포트:</strong> <?php echo GCP_PORT; ?></p>
            <p><strong>DB 타입:</strong> MariaDB</p>
        </div>

<?php

// 데이터베이스 설정 시작
echo "<div class='step'><h3>🚀 1단계: GCP MariaDB 연결 테스트</h3>";

try {
    // 먼저 moodle DB에 연결 시도
    $dsn = "mysql:host=" . GCP_HOST . ";port=" . GCP_PORT . ";dbname=moodle;charset=utf8mb4";
    $pdo = new PDO($dsn, GCP_USER, GCP_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 10
    ]);
    
    $stmt = $pdo->query("SELECT VERSION() as version, DATABASE() as current_db");
    $dbInfo = $stmt->fetch();
    
    echo "<div class='success'>✅ GCP MariaDB 연결 성공!</div>";
    echo "<div class='info'>";
    echo "<strong>MariaDB 버전:</strong> " . $dbInfo['version'] . "<br>";
    echo "<strong>현재 DB:</strong> " . $dbInfo['current_db'] . "<br>";
    echo "<strong>연결 시간:</strong> " . date('Y-m-d H:i:s');
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div class='error'>❌ GCP MariaDB 연결 실패: " . $e->getMessage() . "</div>";
    echo "<div class='warning'>";
    echo "<h4>해결 방법:</h4>";
    echo "1. GCP 서버의 MariaDB 서비스가 실행 중인지 확인<br>";
    echo "2. 방화벽에서 3306 포트가 열려있는지 확인<br>";
    echo "3. 사용자 권한이 원격 접속을 허용하는지 확인<br>";
    echo "4. GCP의 네트워크 보안 규칙 확인";
    echo "</div>";
    exit;
}
echo "</div>";

// 2단계: alphatutor42 데이터베이스 생성
echo "<div class='step'><h3>📊 2단계: alphatutor42 데이터베이스 생성</h3>";

try {
    // alphatutor42 데이터베이스 생성
    $pdo->exec("CREATE DATABASE IF NOT EXISTS alphatutor42 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "<div class='success'>✅ alphatutor42 데이터베이스 생성 성공!</div>";
    
    // alphatutor42 DB로 전환
    $pdo->exec("USE alphatutor42");
    echo "<div class='info'>🔄 alphatutor42 데이터베이스로 전환 완료</div>";
    
} catch (PDOException $e) {
    echo "<div class='warning'>⚠️ 별도 DB 생성 실패, moodle DB에 테이블 생성을 시도합니다...</div>";
    echo "<div class='info'>원인: " . $e->getMessage() . "</div>";
    
    // moodle DB 사용
    $pdo->exec("USE moodle");
    $usePrefix = true;
    echo "<div class='info'>🔄 moodle 데이터베이스 사용 (테이블 prefix: alpha_)</div>";
}

echo "</div>";

// 3단계: 테이블 생성
echo "<div class='step'><h3>🗃️ 3단계: 테이블 생성</h3>";

$prefix = isset($usePrefix) ? 'alpha_' : '';
$tablesCreated = 0;

// 테이블 생성 SQL 배열
$tables = [
    "problem_sets" => "
        CREATE TABLE IF NOT EXISTS {$prefix}problem_sets (
            id INT PRIMARY KEY AUTO_INCREMENT,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            total_problems INT DEFAULT 30,
            version VARCHAR(10) DEFAULT '1.0',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_created_at (created_at),
            INDEX idx_title (title)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
    "problems" => "
        CREATE TABLE IF NOT EXISTS {$prefix}problems (
            id INT PRIMARY KEY AUTO_INCREMENT,
            problem_set_id INT DEFAULT 1,
            problem_number INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            category ENUM('대수', '기하', '해석', '확률통계') DEFAULT '대수',
            difficulty ENUM('1등급', '2등급', '3등급', '4등급', '5등급') DEFAULT '3등급',
            estimated_time INT DEFAULT 20,
            description TEXT,
            question_text TEXT,
            key_strategy TEXT,
            author_notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_problem_set_number (problem_set_id, problem_number),
            INDEX idx_category (category),
            INDEX idx_difficulty (difficulty),
            UNIQUE KEY unique_problem_in_set (problem_set_id, problem_number)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
    "problem_conditions" => "
        CREATE TABLE IF NOT EXISTS {$prefix}problem_conditions (
            id INT PRIMARY KEY AUTO_INCREMENT,
            problem_id INT NOT NULL,
            condition_text TEXT NOT NULL,
            condition_order INT DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_problem_order (problem_id, condition_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
    "analysis_insights" => "
        CREATE TABLE IF NOT EXISTS {$prefix}analysis_insights (
            id INT PRIMARY KEY AUTO_INCREMENT,
            problem_id INT NOT NULL,
            insight_type ENUM('핵심개념', '문제해결전략', '주의사항', '확장문제') DEFAULT '핵심개념',
            insight_text TEXT NOT NULL,
            insight_order INT DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_problem_type (problem_id, insight_type),
            INDEX idx_problem_order (problem_id, insight_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
    "highlight_tags" => "
        CREATE TABLE IF NOT EXISTS {$prefix}highlight_tags (
            id INT PRIMARY KEY AUTO_INCREMENT,
            problem_id INT NOT NULL,
            tag_text VARCHAR(255) NOT NULL,
            tag_color VARCHAR(7) DEFAULT '#ffeb3b',
            tag_order INT DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_problem_order (problem_id, tag_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
    "solution_steps" => "
        CREATE TABLE IF NOT EXISTS {$prefix}solution_steps (
            id INT PRIMARY KEY AUTO_INCREMENT,
            problem_id INT NOT NULL,
            step_number INT NOT NULL,
            step_title VARCHAR(255) NOT NULL,
            step_content TEXT NOT NULL,
            step_image VARCHAR(255) NULL,
            step_explanation TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_problem_step (problem_id, step_number),
            UNIQUE KEY unique_step_in_problem (problem_id, step_number)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
    "key_points" => "
        CREATE TABLE IF NOT EXISTS {$prefix}key_points (
            id INT PRIMARY KEY AUTO_INCREMENT,
            problem_id INT NOT NULL,
            point_title VARCHAR(255) NOT NULL,
            point_description TEXT NOT NULL,
            point_type ENUM('개념', '공식', '팁', '주의사항') DEFAULT '개념',
            point_order INT DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_problem_type (problem_id, point_type),
            INDEX idx_problem_order (problem_id, point_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
];

foreach ($tables as $tableName => $sql) {
    try {
        $pdo->exec($sql);
        echo "<div class='success'>✅ {$prefix}{$tableName} 테이블 생성 성공</div>";
        $tablesCreated++;
    } catch (PDOException $e) {
        echo "<div class='error'>❌ {$prefix}{$tableName} 테이블 생성 실패: " . $e->getMessage() . "</div>";
    }
}

// 기본 데이터 삽입
try {
    $stmt = $pdo->prepare("INSERT IGNORE INTO {$prefix}problem_sets (id, title, description, total_problems, version) VALUES (1, ?, ?, 30, '1.0')");
    $stmt->execute(['GCP 연동 문제집', 'GCP MariaDB 서버와 연동된 수학 문제 학습 시스템']);
    echo "<div class='success'>✅ 기본 문제집 데이터 생성 성공</div>";
} catch (PDOException $e) {
    echo "<div class='warning'>⚠️ 기본 데이터 생성 실패: " . $e->getMessage() . "</div>";
}

echo "<div class='info'><strong>총 {$tablesCreated}개 테이블이 생성되었습니다.</strong></div>";
echo "</div>";

// 4단계: 설정 파일 업데이트
echo "<div class='step'><h3>⚙️ 4단계: 설정 파일 확인</h3>";

if ($prefix) {
    echo "<div class='warning'>⚠️ moodle DB를 사용하므로 config.php에서 TABLE_PREFIX 설정이 필요합니다.</div>";
    echo "<div class='code'>";
    echo "// config.php에서 다음 설정을 확인하세요:<br>";
    echo "define('DB_NAME_ALT', 'moodle');<br>";
    echo "define('TABLE_PREFIX', 'alpha_');";
    echo "</div>";
} else {
    echo "<div class='success'>✅ 별도 alphatutor42 데이터베이스 사용</div>";
}

echo "<div class='info'>현재 config.php 설정이 GCP 서버에 맞게 구성되어 있습니다.</div>";
echo "</div>";

// 5단계: 연결 테스트
echo "<div class='step'><h3>🔍 5단계: API 연결 테스트</h3>";

try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM {$prefix}problems");
    $result = $stmt->fetch();
    
    echo "<div class='success'>✅ 데이터베이스 쿼리 테스트 성공</div>";
    echo "<div class='info'>현재 문제 수: " . $result['count'] . "개</div>";
    
    // 업로드 디렉토리 확인
    $uploadDir = './uploads/images/';
    if (!file_exists($uploadDir)) {
        if (mkdir($uploadDir, 0755, true)) {
            echo "<div class='success'>✅ 업로드 디렉토리 생성 성공</div>";
        } else {
            echo "<div class='warning'>⚠️ 업로드 디렉토리 생성 실패</div>";
        }
    } else {
        echo "<div class='success'>✅ 업로드 디렉토리 존재</div>";
    }
    
} catch (PDOException $e) {
    echo "<div class='error'>❌ 연결 테스트 실패: " . $e->getMessage() . "</div>";
}

echo "</div>";

// 완료 메시지
echo "<div class='step' style='background: #f0fff4; border-left-color: #38a169;'>";
echo "<h3>🎉 설정 완료!</h3>";
echo "<div class='success'>";
echo "<h4>다음 단계:</h4>";
echo "1. <a href='api.php?action=health' class='btn'>API 헬스 체크</a><br><br>";
echo "2. <a href='sample.html' class='btn'>메인 시스템 실행</a><br><br>";
echo "3. 브라우저 개발자 도구(F12)에서 네트워크 탭을 열어 API 호출 상태 확인";
echo "</div>";
echo "</div>";

// 문제 해결 가이드
echo "<div class='step'>";
echo "<h3>🛠️ 문제 해결 가이드</h3>";
echo "<div class='info'>";
echo "<h4>연결 실패 시:</h4>";
echo "1. GCP 방화벽 규칙에서 3306 포트 허용 확인<br>";
echo "2. MariaDB 설정에서 bind-address = 0.0.0.0 확인<br>";
echo "3. 사용자 권한: GRANT ALL ON *.* TO 'bessi02'@'%';<br>";
echo "4. GCP 인스턴스 외부 IP 주소 확인<br><br>";

echo "<h4>권한 오류 시:</h4>";
echo "1. MySQL/MariaDB 콘솔에서 실행:<br>";
echo "<div class='code'>";
echo "CREATE USER 'bessi02'@'%' IDENTIFIED BY '@MCtrigd7128';<br>";
echo "GRANT ALL PRIVILEGES ON *.* TO 'bessi02'@'%';<br>";
echo "FLUSH PRIVILEGES;";
echo "</div>";
echo "</div>";
echo "</div>";

?>

        <div class="footer">
            <p>🌐 <strong>GCP MariaDB 서버 연동 완료</strong></p>
            <p>서버: <?php echo GCP_HOST; ?> | 생성 시간: <?php echo date('Y-m-d H:i:s'); ?></p>
        </div>
    </div>
</body>
</html> 