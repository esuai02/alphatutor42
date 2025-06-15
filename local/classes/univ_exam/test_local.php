<?php
/**
 * 로컬 개발환경 시스템 동작 테스트
 * SQLite를 사용하여 모든 기능이 정상 작동하는지 확인
 */

require_once 'config_local.php';

// HTML 헤더
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>로컬 개발환경 시스템 테스트</title>
    <style>
        body { font-family: 'Noto Sans KR', Arial, sans-serif; margin: 20px; background-color: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #2196F3; text-align: center; margin-bottom: 30px; }
        .test-section { margin: 20px 0; padding: 20px; border: 1px solid #ddd; border-radius: 8px; }
        .status { padding: 15px; margin: 10px 0; border-radius: 5px; font-weight: bold; }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .info { background-color: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        .warning { background-color: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f2f2f2; font-weight: bold; }
        tr:hover { background-color: #f5f5f5; }
        .btn { display: inline-block; padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 10px 5px; }
        .btn:hover { background-color: #0056b3; }
        .btn.success { background-color: #28a745; }
        .btn.danger { background-color: #dc3545; }
        .code-block { background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0; font-family: 'Courier New', monospace; font-size: 14px; border-left: 4px solid #007bff; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0; }
        .stat-card { background: #f8f9fa; padding: 20px; border-radius: 8px; text-align: center; border-left: 4px solid #007bff; }
        .stat-number { font-size: 24px; font-weight: bold; color: #007bff; }
        .stat-label { font-size: 14px; color: #666; margin-top: 5px; }
    </style>
</head>
<body>
<div class="container">
    <h1>🚀 로컬 개발환경 시스템 동작 테스트</h1>
    
    <?php
    $testResults = [];
    $overallStatus = 'success';
    
    // 1. 데이터베이스 연결 테스트
    echo "<div class='test-section'>";
    echo "<h2>📡 1. SQLite 데이터베이스 연결 테스트</h2>";
    
    try {
        $pdo = getDBConnection();
        echo "<div class='status success'>✅ SQLite 데이터베이스 연결 성공!</div>";
        $testResults['db_connection'] = 'success';
        
        // DB 정보 표시
        $stmt = $pdo->query("SELECT sqlite_version() as version");
        $dbInfo = $stmt->fetch();
        echo "<div class='code-block'>";
        echo "<strong>데이터베이스 타입:</strong> SQLite<br>";
        echo "<strong>SQLite 버전:</strong> " . $dbInfo['version'] . "<br>";
        echo "<strong>DB 파일:</strong> " . DB_FILE;
        echo "</div>";
        
    } catch (Exception $e) {
        echo "<div class='status error'>❌ 데이터베이스 연결 실패: " . htmlspecialchars($e->getMessage()) . "</div>";
        $testResults['db_connection'] = 'failed';
        $overallStatus = 'failed';
    }
    echo "</div>";
    
    // 2. 테이블 존재 확인 테스트
    echo "<div class='test-section'>";
    echo "<h2>🗂️ 2. 테이블 존재 확인</h2>";
    
    if (isset($pdo)) {
        try {
            $stmt = $pdo->query("SELECT name, sql FROM sqlite_master WHERE type='table' AND name LIKE 'alpha_%' ORDER BY name");
            $tables = $stmt->fetchAll();
            
            if (count($tables) >= 4) {
                echo "<div class='status success'>✅ 모든 테이블이 존재합니다 (" . count($tables) . "개)</div>";
                $testResults['tables_exist'] = 'success';
            } else {
                echo "<div class='status warning'>⚠️ 일부 테이블이 누락되었습니다 (" . count($tables) . "/4개)</div>";
                $testResults['tables_exist'] = 'partial';
                $overallStatus = 'warning';
            }
            
            echo "<table>";
            echo "<tr><th>테이블명</th><th>생성 SQL</th></tr>";
            foreach ($tables as $table) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($table['name']) . "</td>";
                echo "<td style='font-size: 12px; max-width: 400px; word-break: break-all;'>" . htmlspecialchars(substr($table['sql'], 0, 100)) . "...</td>";
                echo "</tr>";
            }
            echo "</table>";
            
        } catch (Exception $e) {
            echo "<div class='status error'>❌ 테이블 확인 실패: " . htmlspecialchars($e->getMessage()) . "</div>";
            $testResults['tables_exist'] = 'failed';
            $overallStatus = 'failed';
        }
    }
    echo "</div>";
    
    // 3. 데이터 조회 테스트
    echo "<div class='test-section'>";
    echo "<h2>🌐 3. 데이터 조회 테스트</h2>";
    
    if (isset($pdo)) {
        try {
            $prefix = TABLE_PREFIX;
            
            // 문제 개수 확인
            $stmt = $pdo->prepare("SELECT COUNT(*) as problem_count FROM {$prefix}problems");
            $stmt->execute();
            $problemCount = $stmt->fetch()['problem_count'];
            
            // 문제집 개수 확인
            $stmt = $pdo->prepare("SELECT COUNT(*) as set_count FROM {$prefix}problem_sets");
            $stmt->execute();
            $setCount = $stmt->fetch()['set_count'];
            
            echo "<div class='status success'>✅ 데이터 조회 테스트 통과</div>";
            $testResults['data_test'] = 'success';
            
            echo "<div class='stats-grid'>";
            echo "<div class='stat-card'>";
            echo "<div class='stat-number'>{$setCount}</div>";
            echo "<div class='stat-label'>문제집 수</div>";
            echo "</div>";
            echo "<div class='stat-card'>";
            echo "<div class='stat-number'>{$problemCount}</div>";
            echo "<div class='stat-label'>등록된 문제 수</div>";
            echo "</div>";
            echo "</div>";
            
        } catch (Exception $e) {
            echo "<div class='status error'>❌ 데이터 조회 테스트 실패: " . htmlspecialchars($e->getMessage()) . "</div>";
            $testResults['data_test'] = 'failed';
            $overallStatus = 'failed';
        }
    }
    echo "</div>";
    
    // 4. 샘플 데이터 확인
    echo "<div class='test-section'>";
    echo "<h2>📝 4. 샘플 데이터 확인</h2>";
    
    if (isset($pdo)) {
        try {
            $prefix = TABLE_PREFIX;
            $stmt = $pdo->query("SELECT ps.title AS problem_set_title, p.problem_number, p.title AS problem_title, p.category, p.difficulty, p.content
                                FROM {$prefix}problem_sets ps
                                LEFT JOIN {$prefix}problems p ON ps.id = p.problem_set_id
                                ORDER BY p.problem_number");
            $sampleData = $stmt->fetchAll();
            
            if (count($sampleData) > 0) {
                echo "<div class='status success'>✅ 샘플 데이터가 존재합니다</div>";
                $testResults['sample_data'] = 'success';
                
                echo "<table>";
                echo "<tr><th>문제집</th><th>번호</th><th>문제제목</th><th>내용</th><th>카테고리</th><th>난이도</th></tr>";
                foreach ($sampleData as $data) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($data['problem_set_title']) . "</td>";
                    echo "<td>" . htmlspecialchars($data['problem_number'] ?: 'N/A') . "</td>";
                    echo "<td>" . htmlspecialchars($data['problem_title'] ?: 'N/A') . "</td>";
                    echo "<td>" . htmlspecialchars($data['content'] ?: 'N/A') . "</td>";
                    echo "<td>" . htmlspecialchars($data['category'] ?: 'N/A') . "</td>";
                    echo "<td>" . htmlspecialchars($data['difficulty'] ?: 'N/A') . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<div class='status warning'>⚠️ 샘플 데이터가 없습니다.</div>";
                $testResults['sample_data'] = 'empty';
                $overallStatus = 'warning';
            }
            
        } catch (Exception $e) {
            echo "<div class='status error'>❌ 샘플 데이터 확인 실패: " . htmlspecialchars($e->getMessage()) . "</div>";
            $testResults['sample_data'] = 'failed';
            $overallStatus = 'failed';
        }
    }
    echo "</div>";
    
    // 5. 시스템 종합 결과
    echo "<div class='test-section'>";
    echo "<h2>📊 5. 시스템 종합 결과</h2>";
    
    $successCount = count(array_filter($testResults, function($result) { return $result === 'success'; }));
    $totalTests = count($testResults);
    
    if ($overallStatus === 'success') {
        echo "<div class='status success'>";
        echo "<h3>🎉 로컬 시스템이 완벽하게 동작합니다!</h3>";
        echo "<p>모든 테스트가 통과했습니다. ({$successCount}/{$totalTests})</p>";
        echo "</div>";
    } elseif ($overallStatus === 'warning') {
        echo "<div class='status warning'>";
        echo "<h3>⚠️ 시스템이 부분적으로 동작합니다</h3>";
        echo "<p>일부 기능에 문제가 있을 수 있습니다. ({$successCount}/{$totalTests})</p>";
        echo "</div>";
    } else {
        echo "<div class='status error'>";
        echo "<h3>❌ 시스템에 문제가 있습니다</h3>";
        echo "<p>중요한 기능들이 동작하지 않습니다. ({$successCount}/{$totalTests})</p>";
        echo "</div>";
    }
    
    echo "<div class='stats-grid'>";
    foreach ($testResults as $test => $result) {
        $icon = $result === 'success' ? '✅' : ($result === 'failed' ? '❌' : '⚠️');
        $status = $result === 'success' ? '성공' : ($result === 'failed' ? '실패' : '경고');
        echo "<div class='stat-card'>";
        echo "<div class='stat-number'>{$icon}</div>";
        echo "<div class='stat-label'>" . str_replace('_', ' ', ucfirst($test)) . "<br>{$status}</div>";
        echo "</div>";
    }
    echo "</div>";
    echo "</div>";
    
    // 6. 다음 단계 안내
    echo "<div class='test-section'>";
    echo "<h2>🎯 6. 다음 단계</h2>";
    
    if ($overallStatus === 'success') {
        echo "<div class='status info'>";
        echo "<h4>✨ 로컬 개발환경 준비 완료!</h4>";
        echo "<p>이제 로컬에서 수학 문제 학습 시스템을 개발하고 테스트할 수 있습니다.</p>";
        echo "</div>";
    } else {
        echo "<div class='status warning'>";
        echo "<h4>🔧 추가 설정이 필요합니다</h4>";
        echo "<ul>";
        if ($testResults['db_connection'] !== 'success') {
            echo "<li>SQLite 데이터베이스 연결을 확인하세요</li>";
        }
        if ($testResults['tables_exist'] !== 'success') {
            echo "<li>테이블 생성을 다시 시도하세요</li>";
        }
        if ($testResults['sample_data'] === 'empty') {
            echo "<li>샘플 데이터를 다시 생성하세요</li>";
        }
        echo "</ul>";
        echo "</div>";
    }
    
    echo "</div>";
    ?>
    
    <div style="text-align: center; margin-top: 30px;">
        <a href="test_simple.php" class="btn">🧪 간단한 PHP 테스트</a>
        <a href="api_local.php?action=problems" class="btn">🌐 로컬 API 테스트</a>
        <a href="sample.html" class="btn success">📚 메인 시스템</a>
    </div>
    
    <div class="code-block">
        <h3>🔗 로컬 테스트 링크</h3>
        <p><strong>간단한 테스트:</strong> <a href="http://localhost:8000/test_simple.php" target="_blank">http://localhost:8000/test_simple.php</a></p>
        <p><strong>로컬 시스템 테스트:</strong> <a href="http://localhost:8000/test_local.php" target="_blank">http://localhost:8000/test_local.php</a></p>
        <p><strong>메인 시스템:</strong> <a href="http://localhost:8000/sample.html" target="_blank">http://localhost:8000/sample.html</a></p>
    </div>
</div>
</body>
</html> 