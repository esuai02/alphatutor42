<?php
/**
 * GCP 서버 MySQL 테이블 자동 생성 스크립트
 * 브라우저에서 실행하여 모든 테이블을 한번에 생성
 */

require_once 'config.php';

// HTML 헤더
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GCP 서버 DB 테이블 생성</title>
    <style>
        body { font-family: 'Noto Sans KR', Arial, sans-serif; margin: 20px; background-color: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #2196F3; text-align: center; margin-bottom: 30px; }
        .status { padding: 15px; margin: 10px 0; border-radius: 5px; font-weight: bold; }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .info { background-color: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        .warning { background-color: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
        .code-block { background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0; font-family: 'Courier New', monospace; font-size: 14px; border-left: 4px solid #007bff; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f2f2f2; font-weight: bold; }
        tr:hover { background-color: #f5f5f5; }
        .btn { display: inline-block; padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 10px 5px; }
        .btn:hover { background-color: #0056b3; }
        .progress { width: 100%; background-color: #f0f0f0; border-radius: 5px; margin: 10px 0; }
        .progress-bar { height: 20px; background-color: #28a745; text-align: center; line-height: 20px; color: white; border-radius: 5px; }
    </style>
</head>
<body>
<div class="container">
    <h1>🚀 GCP 서버 MySQL 테이블 자동 생성</h1>
    
    <?php
    $startTime = microtime(true);
    $totalTables = 12;
    $createdTables = 0;
    $errors = [];
    
    try {
        echo "<div class='status info'>📡 GCP 서버 연결 중...</div>";
        $pdo = getDBConnection();
        echo "<div class='status success'>✅ GCP MariaDB 연결 성공!</div>";
        
        // SQL 명령어 배열
        $sqlCommands = [
            // 1. 문제집 테이블
            "CREATE TABLE IF NOT EXISTS alpha_problem_sets (
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
            
            // 2. 문제 테이블
            "CREATE TABLE IF NOT EXISTS alpha_problems (
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
                FOREIGN KEY (problem_set_id) REFERENCES alpha_problem_sets(id) ON DELETE CASCADE,
                INDEX idx_problem_set_number (problem_set_id, problem_number),
                INDEX idx_category (category),
                INDEX idx_difficulty (difficulty),
                UNIQUE KEY unique_problem_in_set (problem_set_id, problem_number)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
            // 3. 문제 조건 테이블
            "CREATE TABLE IF NOT EXISTS alpha_problem_conditions (
                id INT PRIMARY KEY AUTO_INCREMENT,
                problem_id INT NOT NULL,
                condition_text TEXT NOT NULL,
                condition_order INT DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (problem_id) REFERENCES alpha_problems(id) ON DELETE CASCADE,
                INDEX idx_problem_order (problem_id, condition_order)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
            // 4. 분석 인사이트 테이블
            "CREATE TABLE IF NOT EXISTS alpha_analysis_insights (
                id INT PRIMARY KEY AUTO_INCREMENT,
                problem_id INT NOT NULL,
                insight_type ENUM('핵심개념', '문제해결전략', '주의사항', '확장문제') DEFAULT '핵심개념',
                insight_text TEXT NOT NULL,
                insight_order INT DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (problem_id) REFERENCES alpha_problems(id) ON DELETE CASCADE,
                INDEX idx_problem_type (problem_id, insight_type),
                INDEX idx_problem_order (problem_id, insight_order)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
            // 5. 하이라이트 태그 테이블
            "CREATE TABLE IF NOT EXISTS alpha_highlight_tags (
                id INT PRIMARY KEY AUTO_INCREMENT,
                problem_id INT NOT NULL,
                tag_text VARCHAR(255) NOT NULL,
                tag_color VARCHAR(7) DEFAULT '#ffeb3b',
                tag_order INT DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (problem_id) REFERENCES alpha_problems(id) ON DELETE CASCADE,
                INDEX idx_problem_order (problem_id, tag_order)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
            // 6. 해결 단계 테이블
            "CREATE TABLE IF NOT EXISTS alpha_solution_steps (
                id INT PRIMARY KEY AUTO_INCREMENT,
                problem_id INT NOT NULL,
                step_number INT NOT NULL,
                step_title VARCHAR(255) NOT NULL,
                step_content TEXT NOT NULL,
                step_image VARCHAR(255) NULL,
                step_explanation TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (problem_id) REFERENCES alpha_problems(id) ON DELETE CASCADE,
                INDEX idx_problem_step (problem_id, step_number),
                UNIQUE KEY unique_step_in_problem (problem_id, step_number)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
            // 7. 창의적 질문 테이블
            "CREATE TABLE IF NOT EXISTS alpha_creative_questions (
                id INT PRIMARY KEY AUTO_INCREMENT,
                problem_id INT NOT NULL,
                question_text TEXT NOT NULL,
                question_type ENUM('확장', '변형', '응용', '연결') DEFAULT '확장',
                difficulty_level ENUM('기초', '발전', '심화') DEFAULT '발전',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (problem_id) REFERENCES alpha_problems(id) ON DELETE CASCADE,
                INDEX idx_problem_type (problem_id, question_type)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
            // 8. 창의적 질문 항목 테이블
            "CREATE TABLE IF NOT EXISTS alpha_creative_question_items (
                id INT PRIMARY KEY AUTO_INCREMENT,
                creative_question_id INT NOT NULL,
                item_text TEXT NOT NULL,
                item_order INT DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (creative_question_id) REFERENCES alpha_creative_questions(id) ON DELETE CASCADE,
                INDEX idx_question_order (creative_question_id, item_order)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
            // 9. 유사 문제 테이블
            "CREATE TABLE IF NOT EXISTS alpha_similar_problems (
                id INT PRIMARY KEY AUTO_INCREMENT,
                problem_id INT NOT NULL,
                similar_title VARCHAR(255) NOT NULL,
                similar_description TEXT,
                difficulty_level ENUM('쉬움', '보통', '어려움') DEFAULT '보통',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (problem_id) REFERENCES alpha_problems(id) ON DELETE CASCADE,
                INDEX idx_problem_difficulty (problem_id, difficulty_level)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
            // 10. 유사 문제 선택지 테이블
            "CREATE TABLE IF NOT EXISTS alpha_similar_problem_options (
                id INT PRIMARY KEY AUTO_INCREMENT,
                similar_problem_id INT NOT NULL,
                option_number INT NOT NULL,
                option_text TEXT NOT NULL,
                is_correct BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (similar_problem_id) REFERENCES alpha_similar_problems(id) ON DELETE CASCADE,
                INDEX idx_similar_option (similar_problem_id, option_number)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
            // 11. 유사 문제 해결 단계 테이블
            "CREATE TABLE IF NOT EXISTS alpha_similar_problem_solution_steps (
                id INT PRIMARY KEY AUTO_INCREMENT,
                similar_problem_id INT NOT NULL,
                step_number INT NOT NULL,
                step_description TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (similar_problem_id) REFERENCES alpha_similar_problems(id) ON DELETE CASCADE,
                INDEX idx_similar_step (similar_problem_id, step_number)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
            // 12. 핵심 포인트 테이블
            "CREATE TABLE IF NOT EXISTS alpha_key_points (
                id INT PRIMARY KEY AUTO_INCREMENT,
                problem_id INT NOT NULL,
                point_title VARCHAR(255) NOT NULL,
                point_description TEXT NOT NULL,
                point_type ENUM('개념', '공식', '팁', '주의사항') DEFAULT '개념',
                point_order INT DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (problem_id) REFERENCES alpha_problems(id) ON DELETE CASCADE,
                INDEX idx_problem_type (problem_id, point_type),
                INDEX idx_problem_order (problem_id, point_order)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        ];
        
        $tableNames = [
            'alpha_problem_sets', 'alpha_problems', 'alpha_problem_conditions',
            'alpha_analysis_insights', 'alpha_highlight_tags', 'alpha_solution_steps',
            'alpha_creative_questions', 'alpha_creative_question_items', 'alpha_similar_problems',
            'alpha_similar_problem_options', 'alpha_similar_problem_solution_steps', 'alpha_key_points'
        ];
        
        echo "<div class='status info'>🛠️ 테이블 생성 중...</div>";
        echo "<div class='progress'><div class='progress-bar' id='progressBar' style='width: 0%'>0%</div></div>";
        
        // 테이블 생성 실행
        foreach ($sqlCommands as $index => $sql) {
            try {
                $pdo->exec($sql);
                $createdTables++;
                $progress = round(($createdTables / $totalTables) * 100);
                echo "<div class='status success'>✅ {$tableNames[$index]} 테이블 생성 완료</div>";
                echo "<script>
                    document.getElementById('progressBar').style.width = '{$progress}%';
                    document.getElementById('progressBar').innerText = '{$progress}%';
                </script>";
                flush();
                usleep(100000); // 0.1초 대기
            } catch (PDOException $e) {
                $errors[] = "{$tableNames[$index]}: " . $e->getMessage();
                echo "<div class='status error'>❌ {$tableNames[$index]} 테이블 생성 실패: " . $e->getMessage() . "</div>";
            }
        }
        
        // 기본 데이터 삽입
        echo "<div class='status info'>📝 기본 데이터 삽입 중...</div>";
        
        $pdo->exec("INSERT IGNORE INTO alpha_problem_sets (id, title, description, total_problems, version) 
                   VALUES (1, 'GCP 연동 수학 문제집', 'GCP MariaDB 서버와 연동된 수학 문제 학습 시스템', 30, '1.0')");
        
        $pdo->exec("INSERT IGNORE INTO alpha_problems (id, problem_set_id, problem_number, title, category, difficulty, estimated_time, description, question_text, key_strategy, author_notes) 
                   VALUES (1, 1, 1, '이차방정식의 해', '대수', '3등급', 15, '이차방정식을 풀어 실근을 구하는 문제입니다.', 'x² - 5x + 6 = 0의 해를 구하시오.', '인수분해 또는 근의 공식을 사용합니다.', 'GCP 서버 연동 테스트용 샘플 문제')");
        
        $pdo->exec("INSERT IGNORE INTO alpha_solution_steps (problem_id, step_number, step_title, step_content, step_explanation) VALUES
                   (1, 1, '인수분해', 'x² - 5x + 6 = (x - 2)(x - 3)', '두 수의 곱이 6이고 합이 5인 수를 찾습니다'),
                   (1, 2, '해 구하기', 'x - 2 = 0 또는 x - 3 = 0', '각 인수를 0으로 놓고 해를 구합니다'),
                   (1, 3, '최종 답', 'x = 2 또는 x = 3', '이차방정식의 두 실근입니다')");
        
        echo "<div class='status success'>✅ 기본 데이터 삽입 완료</div>";
        
        // 생성된 테이블 확인
        $stmt = $pdo->query("SELECT TABLE_NAME, TABLE_ROWS, CREATE_TIME 
                            FROM information_schema.tables 
                            WHERE table_schema = 'moodle' AND table_name LIKE 'alpha_%' 
                            ORDER BY table_name");
        $tables = $stmt->fetchAll();
        
        echo "<h2>📊 생성된 테이블 목록</h2>";
        echo "<table>";
        echo "<tr><th>테이블명</th><th>레코드 수</th><th>생성 시간</th></tr>";
        foreach ($tables as $table) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($table['TABLE_NAME']) . "</td>";
            echo "<td>" . ($table['TABLE_ROWS'] ?: '0') . "</td>";
            echo "<td>" . ($table['CREATE_TIME'] ?: 'N/A') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // 샘플 데이터 확인
        $stmt = $pdo->query("SELECT ps.title AS '문제집', p.problem_number AS '문제번호', p.title AS '문제제목'
                            FROM alpha_problem_sets ps
                            LEFT JOIN alpha_problems p ON ps.id = p.problem_set_id
                            ORDER BY p.problem_number");
        $sampleData = $stmt->fetchAll();
        
        if ($sampleData) {
            echo "<h2>📝 샘플 데이터 확인</h2>";
            echo "<table>";
            echo "<tr><th>문제집</th><th>문제번호</th><th>문제제목</th></tr>";
            foreach ($sampleData as $data) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($data['문제집']) . "</td>";
                echo "<td>" . htmlspecialchars($data['문제번호'] ?: 'N/A') . "</td>";
                echo "<td>" . htmlspecialchars($data['문제제목'] ?: 'N/A') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
        $endTime = microtime(true);
        $executionTime = round($endTime - $startTime, 2);
        
        echo "<div class='status success'>";
        echo "<h2>🎉 설치 완료!</h2>";
        echo "<p><strong>생성된 테이블:</strong> {$createdTables}/{$totalTables}개</p>";
        echo "<p><strong>실행 시간:</strong> {$executionTime}초</p>";
        echo "<p><strong>데이터베이스:</strong> moodle (GCP MariaDB)</p>";
        echo "</div>";
        
        if (count($errors) > 0) {
            echo "<div class='status warning'>";
            echo "<h3>⚠️ 경고 사항:</h3>";
            echo "<ul>";
            foreach ($errors as $error) {
                echo "<li>" . htmlspecialchars($error) . "</li>";
            }
            echo "</ul>";
            echo "</div>";
        }
        
    } catch (Exception $e) {
        echo "<div class='status error'>";
        echo "<h2>❌ 설치 실패</h2>";
        echo "<p><strong>에러:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p><strong>해결방법:</strong> config.php의 데이터베이스 설정을 확인하세요.</p>";
        echo "</div>";
    }
    ?>
    
    <div style="text-align: center; margin-top: 30px;">
        <a href="sample.html" class="btn">📚 수학 문제 시스템 바로가기</a>
        <a href="test_connection.php" class="btn">🔍 연결 테스트</a>
        <a href="api.php?action=health" class="btn">🏥 API 상태 확인</a>
    </div>
    
    <div class="code-block">
        <h3>🔗 빠른 링크</h3>
        <p><strong>메인 시스템:</strong> http://34.64.175.237/local/classes/univ_exam/sample.html</p>
        <p><strong>API 엔드포인트:</strong> http://34.64.175.237/local/classes/univ_exam/api.php</p>
        <p><strong>테이블 생성:</strong> http://34.64.175.237/local/classes/univ_exam/run_mysql_commands.php</p>
    </div>
</div>
</body>
</html> 