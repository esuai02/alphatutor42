<?php
/**
 * GCP 서버에 추가 샘플 문제 삽입
 * 더 나은 사용자 경험을 위한 다양한 샘플 문제 제공
 */

require_once 'config.php';

// HTML 헤더
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>샘플 문제 추가</title>
    <style>
        body { font-family: 'Noto Sans KR', Arial, sans-serif; margin: 20px; background-color: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #2196F3; text-align: center; margin-bottom: 30px; }
        .status { padding: 15px; margin: 10px 0; border-radius: 5px; font-weight: bold; }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .info { background-color: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        .btn { display: inline-block; padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 10px 5px; }
        .btn:hover { background-color: #0056b3; }
        .progress { width: 100%; background-color: #f0f0f0; border-radius: 5px; margin: 10px 0; }
        .progress-bar { height: 20px; background-color: #28a745; text-align: center; line-height: 20px; color: white; border-radius: 5px; }
    </style>
</head>
<body>
<div class="container">
    <h1>📝 샘플 문제 추가</h1>
    
    <?php
    try {
        echo "<div class='status info'>📡 GCP 서버 연결 중...</div>";
        $pdo = getDBConnection();
        echo "<div class='status success'>✅ GCP MariaDB 연결 성공!</div>";
        
        // 샘플 문제 데이터
        $sampleProblems = [
            [
                'problem_number' => 2,
                'title' => '삼각함수의 덧셈공식',
                'category' => '해석',
                'difficulty' => '2등급',
                'estimated_time' => 25,
                'description' => '삼각함수의 덧셈공식을 활용하여 주어진 값을 구하는 문제입니다.',
                'question_text' => 'sin 15°의 값을 구하시오.',
                'key_strategy' => '15° = 45° - 30°로 나타내어 덧셈공식을 사용합니다.',
                'author_notes' => '삼각함수 덧셈공식의 기본적인 활용 문제'
            ],
            [
                'problem_number' => 3,
                'title' => '원의 방정식',
                'category' => '기하',
                'difficulty' => '3등급',
                'estimated_time' => 20,
                'description' => '원의 중심과 반지름을 구하여 원의 방정식을 구하는 문제입니다.',
                'question_text' => '점 A(2, 3)을 중심으로 하고 점 B(5, 7)을 지나는 원의 방정식을 구하시오.',
                'key_strategy' => '중심과 한 점 사이의 거리가 반지름임을 이용합니다.',
                'author_notes' => '원의 방정식 기본 문제'
            ],
            [
                'problem_number' => 4,
                'title' => '확률의 기본 성질',
                'category' => '확률통계',
                'difficulty' => '4등급',
                'estimated_time' => 15,
                'description' => '확률의 기본 성질을 이용하여 문제를 해결합니다.',
                'question_text' => '한 개의 주사위를 던질 때, 3의 배수가 나올 확률을 구하시오.',
                'key_strategy' => '전체 경우의 수와 조건을 만족하는 경우의 수를 구합니다.',
                'author_notes' => '확률의 기본 개념 문제'
            ],
            [
                'problem_number' => 5,
                'title' => '지수법칙',
                'category' => '대수',
                'difficulty' => '2등급',
                'estimated_time' => 18,
                'description' => '지수법칙을 활용하여 식을 간단히 정리하는 문제입니다.',
                'question_text' => '2³ × 2⁵ ÷ 2² 의 값을 구하시오.',
                'key_strategy' => '같은 밑을 가진 지수의 곱셈과 나눗셈 법칙을 사용합니다.',
                'author_notes' => '지수법칙의 기본 활용'
            ]
        ];
        
        echo "<div class='status info'>📝 샘플 문제 삽입 중...</div>";
        echo "<div class='progress'><div class='progress-bar' id='progressBar' style='width: 0%'>0%</div></div>";
        
        $insertedCount = 0;
        $totalProblems = count($sampleProblems);
        
        foreach ($sampleProblems as $index => $problem) {
            try {
                // 문제 삽입
                $stmt = $pdo->prepare("
                    INSERT IGNORE INTO alpha_problems 
                    (problem_set_id, problem_number, title, category, difficulty, estimated_time, description, question_text, key_strategy, author_notes) 
                    VALUES (1, :problem_number, :title, :category, :difficulty, :estimated_time, :description, :question_text, :key_strategy, :author_notes)
                ");
                
                $stmt->execute([
                    'problem_number' => $problem['problem_number'],
                    'title' => $problem['title'],
                    'category' => $problem['category'],
                    'difficulty' => $problem['difficulty'],
                    'estimated_time' => $problem['estimated_time'],
                    'description' => $problem['description'],
                    'question_text' => $problem['question_text'],
                    'key_strategy' => $problem['key_strategy'],
                    'author_notes' => $problem['author_notes']
                ]);
                
                // 해당 문제의 ID 가져오기
                $problemId = $pdo->lastInsertId();
                if (!$problemId) {
                    // 이미 존재하는 문제의 경우 ID 조회
                    $stmt = $pdo->prepare("SELECT id FROM alpha_problems WHERE problem_set_id = 1 AND problem_number = :problem_number");
                    $stmt->execute(['problem_number' => $problem['problem_number']]);
                    $result = $stmt->fetch();
                    $problemId = $result ? $result['id'] : null;
                }
                
                if ($problemId) {
                    // 문제별 샘플 해결 단계 추가
                    $solutionSteps = [];
                    
                    switch ($problem['problem_number']) {
                        case 2:
                            $solutionSteps = [
                                ['step_number' => 1, 'step_title' => '각도 분해', 'step_content' => 'sin 15° = sin(45° - 30°)', 'step_explanation' => '15°를 45°와 30°의 차로 나타냅니다'],
                                ['step_number' => 2, 'step_title' => '덧셈공식 적용', 'step_content' => '= sin 45° cos 30° - cos 45° sin 30°', 'step_explanation' => '사인의 차각 공식을 적용합니다'],
                                ['step_number' => 3, 'step_title' => '값 대입', 'step_content' => '= (√2/2)(√3/2) - (√2/2)(1/2)', 'step_explanation' => '각 삼각함수 값을 대입합니다'],
                                ['step_number' => 4, 'step_title' => '계산', 'step_content' => '= (√6 - √2)/4', 'step_explanation' => '계산하여 최종 답을 구합니다']
                            ];
                            break;
                        case 3:
                            $solutionSteps = [
                                ['step_number' => 1, 'step_title' => '반지름 구하기', 'step_content' => 'r = √[(5-2)² + (7-3)²] = √[9 + 16] = 5', 'step_explanation' => '중심 A에서 점 B까지의 거리가 반지름입니다'],
                                ['step_number' => 2, 'step_title' => '원의 방정식', 'step_content' => '(x - 2)² + (y - 3)² = 25', 'step_explanation' => '중심이 (2, 3)이고 반지름이 5인 원의 방정식입니다']
                            ];
                            break;
                        case 4:
                            $solutionSteps = [
                                ['step_number' => 1, 'step_title' => '전체 경우의 수', 'step_content' => '주사위의 눈: 1, 2, 3, 4, 5, 6 (총 6개)', 'step_explanation' => '주사위를 던질 때 나올 수 있는 모든 경우입니다'],
                                ['step_number' => 2, 'step_title' => '조건을 만족하는 경우', 'step_content' => '3의 배수: 3, 6 (총 2개)', 'step_explanation' => '3의 배수는 3과 6입니다'],
                                ['step_number' => 3, 'step_title' => '확률 계산', 'step_content' => 'P = 2/6 = 1/3', 'step_explanation' => '확률 = 조건을 만족하는 경우의 수 / 전체 경우의 수']
                            ];
                            break;
                        case 5:
                            $solutionSteps = [
                                ['step_number' => 1, 'step_title' => '지수법칙 적용', 'step_content' => '2³ × 2⁵ = 2³⁺⁵ = 2⁸', 'step_explanation' => '같은 밑의 거듭제곱의 곱은 지수를 더합니다'],
                                ['step_number' => 2, 'step_title' => '나눗셈 적용', 'step_content' => '2⁸ ÷ 2² = 2⁸⁻² = 2⁶', 'step_explanation' => '같은 밑의 거듭제곱의 나눗셈은 지수를 뺍니다'],
                                ['step_number' => 3, 'step_title' => '최종 계산', 'step_content' => '2⁶ = 64', 'step_explanation' => '2의 6제곱을 계산합니다']
                            ];
                            break;
                    }
                    
                    // 해결 단계 삽입
                    foreach ($solutionSteps as $step) {
                        $stmt = $pdo->prepare("
                            INSERT IGNORE INTO alpha_solution_steps 
                            (problem_id, step_number, step_title, step_content, step_explanation) 
                            VALUES (:problem_id, :step_number, :step_title, :step_content, :step_explanation)
                        ");
                        $stmt->execute([
                            'problem_id' => $problemId,
                            'step_number' => $step['step_number'],
                            'step_title' => $step['step_title'],
                            'step_content' => $step['step_content'],
                            'step_explanation' => $step['step_explanation']
                        ]);
                    }
                }
                
                $insertedCount++;
                $progress = round(($insertedCount / $totalProblems) * 100);
                echo "<div class='status success'>✅ 문제 {$problem['problem_number']}: {$problem['title']} 추가 완료</div>";
                echo "<script>
                    document.getElementById('progressBar').style.width = '{$progress}%';
                    document.getElementById('progressBar').innerText = '{$progress}%';
                </script>";
                flush();
                usleep(300000); // 0.3초 대기
                
            } catch (PDOException $e) {
                echo "<div class='status error'>❌ 문제 {$problem['problem_number']} 추가 실패: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
        }
        
        echo "<div class='status success'>";
        echo "<h2>🎉 샘플 문제 추가 완료!</h2>";
        echo "<p><strong>추가된 문제:</strong> {$insertedCount}/{$totalProblems}개</p>";
        echo "</div>";
        
        // 현재 문제 현황 확인
        $stmt = $pdo->query("SELECT COUNT(*) as total_problems FROM alpha_problems");
        $totalInDB = $stmt->fetch()['total_problems'];
        
        $stmt = $pdo->query("SELECT problem_number, title, category FROM alpha_problems ORDER BY problem_number LIMIT 10");
        $currentProblems = $stmt->fetchAll();
        
        echo "<h3>📊 현재 등록된 문제 ({$totalInDB}개)</h3>";
        echo "<table style='width: 100%; border-collapse: collapse; margin: 20px 0;'>";
        echo "<tr style='background-color: #f2f2f2;'><th style='padding: 12px; border-bottom: 1px solid #ddd;'>문제번호</th><th style='padding: 12px; border-bottom: 1px solid #ddd;'>제목</th><th style='padding: 12px; border-bottom: 1px solid #ddd;'>카테고리</th></tr>";
        
        foreach ($currentProblems as $problem) {
            echo "<tr>";
            echo "<td style='padding: 12px; border-bottom: 1px solid #ddd;'>" . $problem['problem_number'] . "</td>";
            echo "<td style='padding: 12px; border-bottom: 1px solid #ddd;'>" . htmlspecialchars($problem['title']) . "</td>";
            echo "<td style='padding: 12px; border-bottom: 1px solid #ddd;'>" . htmlspecialchars($problem['category']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } catch (Exception $e) {
        echo "<div class='status error'>";
        echo "<h2>❌ 샘플 문제 추가 실패</h2>";
        echo "<p><strong>에러:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "</div>";
    }
    ?>
    
    <div style="text-align: center; margin-top: 30px;">
        <a href="sample.html" class="btn">📚 수학 문제 시스템 확인하기</a>
        <a href="test_full_system.php" class="btn">🔍 전체 시스템 테스트</a>
    </div>
</div>
</body>
</html> 