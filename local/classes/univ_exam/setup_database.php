<?php
/**
 * 데이터베이스 자동 설정 스크립트
 * 브라우저에서 실행하여 데이터베이스를 자동으로 생성합니다.
 */

// 에러 출력 활성화
ini_set('display_errors', 1);
error_reporting(E_ALL);

// HTML 출력 시작
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>데이터베이스 설정</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { color: #4CAF50; background: #f0f8f0; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .error { color: #f44336; background: #fff0f0; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .info { color: #2196F3; background: #f0f8ff; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .step { margin: 20px 0; padding: 15px; border-left: 4px solid #2196F3; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🗄️ 알파튜터42 데이터베이스 설정</h1>
    
    <?php
    // 설정 정보
    $db_host = 'localhost';
    $db_name = 'alphatutor42';
    $db_user = 'root';
    $db_pass = '';
    $db_charset = 'utf8mb4';

    echo "<div class='step'>";
    echo "<h2>1단계: 데이터베이스 연결 확인</h2>";
    
    try {
        // MySQL 서버에 연결 (데이터베이스 지정하지 않음)
        $pdo = new PDO("mysql:host=$db_host;charset=$db_charset", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo "<div class='success'>✅ MySQL 서버 연결 성공!</div>";
        
        // 데이터베이스 생성
        echo "<h2>2단계: 데이터베이스 생성</h2>";
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "<div class='success'>✅ 데이터베이스 '$db_name' 생성 완료!</div>";
        
        // 생성된 데이터베이스에 연결
        $pdo->exec("USE `$db_name`");
        echo "<div class='success'>✅ 데이터베이스 '$db_name' 선택 완료!</div>";
        
        echo "<h2>3단계: 테이블 생성</h2>";
        
        // 테이블 생성 SQL들
        $tables = [
            'problem_sets' => "
                CREATE TABLE IF NOT EXISTS problem_sets (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    title VARCHAR(255) NOT NULL,
                    description TEXT,
                    total_problems INT DEFAULT 30,
                    version VARCHAR(10) DEFAULT '1.0',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )",
            
            'problems' => "
                CREATE TABLE IF NOT EXISTS problems (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    problem_set_id INT,
                    problem_number INT,
                    title VARCHAR(255) NOT NULL,
                    category ENUM('대수', '기하', '해석', '확률통계'),
                    difficulty ENUM('1등급', '2등급', '3등급', '4등급', '5등급'),
                    estimated_time INT,
                    description TEXT,
                    question_text TEXT,
                    key_strategy TEXT,
                    author VARCHAR(100),
                    source VARCHAR(255),
                    tags JSON,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (problem_set_id) REFERENCES problem_sets(id),
                    INDEX idx_problem_set_number (problem_set_id, problem_number)
                )",
            
            'problem_conditions' => "
                CREATE TABLE IF NOT EXISTS problem_conditions (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    problem_id INT,
                    condition_order INT,
                    condition_text TEXT,
                    FOREIGN KEY (problem_id) REFERENCES problems(id),
                    INDEX idx_problem_order (problem_id, condition_order)
                )",
            
            'analysis_insights' => "
                CREATE TABLE IF NOT EXISTS analysis_insights (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    problem_id INT,
                    insight_order INT,
                    insight_text TEXT,
                    FOREIGN KEY (problem_id) REFERENCES problems(id)
                )",
            
            'highlight_tags' => "
                CREATE TABLE IF NOT EXISTS highlight_tags (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    problem_id INT,
                    text VARCHAR(255),
                    insight_number INT,
                    explanation TEXT,
                    FOREIGN KEY (problem_id) REFERENCES problems(id)
                )",
            
            'solution_steps' => "
                CREATE TABLE IF NOT EXISTS solution_steps (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    problem_id INT,
                    step_number INT,
                    question TEXT,
                    answer TEXT,
                    key_point TEXT,
                    difficulty ENUM('easy', 'medium', 'hard'),
                    estimated_time INT,
                    FOREIGN KEY (problem_id) REFERENCES problems(id),
                    INDEX idx_problem_step (problem_id, step_number)
                )",
            
            'creative_questions' => "
                CREATE TABLE IF NOT EXISTS creative_questions (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    problem_id INT,
                    title VARCHAR(255),
                    footer TEXT,
                    FOREIGN KEY (problem_id) REFERENCES problems(id)
                )",
            
            'creative_question_items' => "
                CREATE TABLE IF NOT EXISTS creative_question_items (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    creative_question_id INT,
                    question_order INT,
                    text TEXT,
                    hint TEXT,
                    category ENUM('확장', '일반화', '변형', '연결'),
                    FOREIGN KEY (creative_question_id) REFERENCES creative_questions(id)
                )",
            
            'key_points' => "
                CREATE TABLE IF NOT EXISTS key_points (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    problem_id INT,
                    point_order INT,
                    point_text TEXT,
                    FOREIGN KEY (problem_id) REFERENCES problems(id)
                )",
            
            'similar_problems' => "
                CREATE TABLE IF NOT EXISTS similar_problems (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    problem_id INT,
                    description TEXT,
                    math_expression TEXT,
                    correct_answer INT,
                    final_answer TEXT,
                    explanation TEXT,
                    FOREIGN KEY (problem_id) REFERENCES problems(id)
                )",
            
            'similar_problem_options' => "
                CREATE TABLE IF NOT EXISTS similar_problem_options (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    similar_problem_id INT,
                    option_number INT,
                    option_value INT,
                    option_text VARCHAR(255),
                    explanation TEXT,
                    FOREIGN KEY (similar_problem_id) REFERENCES similar_problems(id)
                )",
            
            'similar_problem_solution_steps' => "
                CREATE TABLE IF NOT EXISTS similar_problem_solution_steps (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    similar_problem_id INT,
                    step_order INT,
                    title VARCHAR(255),
                    content TEXT,
                    math_formula TEXT,
                    FOREIGN KEY (similar_problem_id) REFERENCES similar_problems(id)
                )"
        ];
        
        // 각 테이블 생성
        foreach ($tables as $table_name => $sql) {
            try {
                $pdo->exec($sql);
                echo "<div class='success'>✅ 테이블 '$table_name' 생성 완료!</div>";
            } catch (PDOException $e) {
                echo "<div class='error'>❌ 테이블 '$table_name' 생성 실패: " . $e->getMessage() . "</div>";
            }
        }
        
        echo "<h2>4단계: 기본 데이터 삽입</h2>";
        
        // 기본 문제집 생성
        try {
            $stmt = $pdo->prepare("SELECT id FROM problem_sets WHERE id = 1");
            $stmt->execute();
            
            if (!$stmt->fetch()) {
                $pdo->exec("INSERT INTO problem_sets (id, title, description, total_problems, version) 
                           VALUES (1, '기본 문제집', '수학 문제 학습 시스템 기본 문제집', 30, '1.0')");
                echo "<div class='success'>✅ 기본 문제집 생성 완료!</div>";
            } else {
                echo "<div class='info'>ℹ️ 기본 문제집이 이미 존재합니다.</div>";
            }
        } catch (PDOException $e) {
            echo "<div class='error'>❌ 기본 문제집 생성 실패: " . $e->getMessage() . "</div>";
        }
        
        echo "<h2>5단계: 디렉토리 확인</h2>";
        
        // 업로드 디렉토리 확인/생성
        $upload_dir = './uploads/images/';
        if (!file_exists($upload_dir)) {
            if (mkdir($upload_dir, 0755, true)) {
                echo "<div class='success'>✅ 업로드 디렉토리 생성 완료: $upload_dir</div>";
            } else {
                echo "<div class='error'>❌ 업로드 디렉토리 생성 실패: $upload_dir</div>";
            }
        } else {
            echo "<div class='info'>ℹ️ 업로드 디렉토리가 이미 존재합니다: $upload_dir</div>";
        }
        
        // 로그 디렉토리 확인/생성
        $log_dir = './logs/';
        if (!file_exists($log_dir)) {
            if (mkdir($log_dir, 0755, true)) {
                echo "<div class='success'>✅ 로그 디렉토리 생성 완료: $log_dir</div>";
            } else {
                echo "<div class='error'>❌ 로그 디렉토리 생성 실패: $log_dir</div>";
            }
        } else {
            echo "<div class='info'>ℹ️ 로그 디렉토리가 이미 존재합니다: $log_dir</div>";
        }
        
        echo "<h2>🎉 설정 완료!</h2>";
        echo "<div class='success'>";
        echo "<h3>데이터베이스 설정이 완료되었습니다!</h3>";
        echo "<p><strong>데이터베이스명:</strong> $db_name</p>";
        echo "<p><strong>테이블 수:</strong> " . count($tables) . "개</p>";
        echo "<p><strong>다음 단계:</strong> <a href='sample.html'>sample.html</a> 페이지를 열어서 시스템을 테스트해보세요.</p>";
        echo "</div>";
        
        echo "<div class='info'>";
        echo "<h3>⚠️ 보안 권장사항</h3>";
        echo "<ul>";
        echo "<li>운영환경에서는 root 사용자 대신 전용 사용자를 생성하세요</li>";
        echo "<li>강력한 비밀번호를 설정하세요</li>";
        echo "<li>config.php에서 DEBUG_MODE를 false로 설정하세요</li>";
        echo "<li>이 설정 파일(setup_database.php)을 삭제하거나 접근을 제한하세요</li>";
        echo "</ul>";
        echo "</div>";
        
    } catch (PDOException $e) {
        echo "<div class='error'>";
        echo "<h3>❌ 데이터베이스 연결 실패</h3>";
        echo "<p><strong>오류 메시지:</strong> " . $e->getMessage() . "</p>";
        echo "<h4>해결 방법:</h4>";
        echo "<ul>";
        echo "<li>MySQL/MariaDB 서버가 실행 중인지 확인하세요</li>";
        echo "<li>XAMPP를 사용하는 경우 MySQL 서비스를 시작하세요</li>";
        echo "<li>config.php의 데이터베이스 연결 정보를 확인하세요</li>";
        echo "<li>방화벽이나 보안 소프트웨어가 차단하고 있는지 확인하세요</li>";
        echo "</ul>";
        echo "</div>";
    }
    
    echo "</div>";
    ?>
    
    <div class="step">
        <h2>📚 추가 리소스</h2>
        <ul>
            <li><a href="DATABASE_SETUP.md">상세 설정 가이드</a></li>
            <li><a href="create_database.sql">SQL 스크립트 파일</a></li>
            <li><a href="api.php?action=problems">API 테스트</a></li>
        </ul>
    </div>
</body>
</html> 