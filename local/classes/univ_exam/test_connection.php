<?php
/**
 * GCP MariaDB 연결 테스트 스크립트
 */

// 에러 출력 활성화
ini_set('display_errors', 1);
error_reporting(E_ALL);

// config.php 포함
require_once 'config.php';

echo "<h1>🌐 GCP MariaDB 연결 테스트</h1>";
echo "<div style='font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 20px;'>";

// 1. 설정 정보 확인
echo "<h2>📋 1. 연결 설정 정보</h2>";
echo "<div style='background: #f0f8ff; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<strong>호스트:</strong> " . DB_HOST . "<br>";
echo "<strong>포트:</strong> " . DB_PORT . "<br>";
echo "<strong>사용자:</strong> " . DB_USER . "<br>";
echo "<strong>기본 DB:</strong> " . DB_NAME . "<br>";
echo "<strong>대안 DB:</strong> " . DB_NAME_ALT . "<br>";
echo "<strong>테이블 Prefix:</strong> " . TABLE_PREFIX . "<br>";
echo "</div>";

// 2. 데이터베이스 연결 테스트
echo "<h2>🔌 2. 데이터베이스 연결 테스트</h2>";

try {
    $pdo = getDBConnection();
    echo "<div style='background: #f0fff4; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 5px solid #38a169;'>";
    echo "✅ <strong>GCP MariaDB 연결 성공!</strong>";
    echo "</div>";
    
    // 데이터베이스 정보 조회
    $stmt = $pdo->query("SELECT VERSION() as version, DATABASE() as current_db, USER() as current_user");
    $dbInfo = $stmt->fetch();
    
    echo "<div style='background: #f0f8ff; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<strong>서버 정보:</strong><br>";
    echo "- MariaDB 버전: " . $dbInfo['version'] . "<br>";
    echo "- 현재 데이터베이스: " . $dbInfo['current_db'] . "<br>";
    echo "- 현재 사용자: " . $dbInfo['current_user'] . "<br>";
    echo "- 연결 시간: " . date('Y-m-d H:i:s') . "<br>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div style='background: #fff5f5; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 5px solid #e53e3e;'>";
    echo "❌ <strong>데이터베이스 연결 실패:</strong><br>";
    echo htmlspecialchars($e->getMessage());
    echo "</div>";
    echo "</div>";
    exit;
}

// 3. 테이블 존재 여부 확인
echo "<h2>🗃️ 3. 테이블 존재 여부 확인</h2>";

$prefix = TABLE_PREFIX;
$expectedTables = [
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

foreach ($expectedTables as $table) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            $existingTables[] = $table;
        } else {
            $missingTables[] = $table;
        }
    } catch (PDOException $e) {
        $missingTables[] = $table . " (오류: " . $e->getMessage() . ")";
    }
}

if (!empty($existingTables)) {
    echo "<div style='background: #f0fff4; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "✅ <strong>존재하는 테이블들:</strong><br>";
    foreach ($existingTables as $table) {
        echo "- " . $table . "<br>";
    }
    echo "</div>";
}

if (!empty($missingTables)) {
    echo "<div style='background: #fff5f5; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "❌ <strong>누락된 테이블들:</strong><br>";
    foreach ($missingTables as $table) {
        echo "- " . $table . "<br>";
    }
    echo "</div>";
    
    echo "<div style='background: #fffbeb; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "⚠️ <strong>해결 방법:</strong><br>";
    echo "1. <a href='setup_database_gcp.php'>setup_database_gcp.php</a>를 실행하여 테이블을 생성하세요.<br>";
    echo "2. 또는 create_database_gcp.sql 파일을 수동으로 실행하세요.";
    echo "</div>";
}

// 4. 기본 데이터 확인
if (!empty($existingTables)) {
    echo "<h2>📊 4. 기본 데이터 확인</h2>";
    
    try {
        // 문제집 수
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM {$prefix}problem_sets");
        $problemSetCount = $stmt->fetch()['count'];
        
        // 문제 수
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM {$prefix}problems");
        $problemCount = $stmt->fetch()['count'];
        
        echo "<div style='background: #f0f8ff; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<strong>데이터 현황:</strong><br>";
        echo "- 문제집 수: " . $problemSetCount . "개<br>";
        echo "- 문제 수: " . $problemCount . "개<br>";
        echo "</div>";
        
        if ($problemSetCount == 0) {
            echo "<div style='background: #fffbeb; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
            echo "⚠️ 기본 문제집이 없습니다. 생성하시겠습니까?<br>";
            echo "<a href='?create_default=1' style='background: #667eea; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px;'>기본 문제집 생성</a>";
            echo "</div>";
        }
        
    } catch (PDOException $e) {
        echo "<div style='background: #fff5f5; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "❌ 데이터 확인 실패: " . htmlspecialchars($e->getMessage());
        echo "</div>";
    }
}

// 5. API 테스트
echo "<h2>🔗 5. API 연결 테스트</h2>";

if (!empty($existingTables)) {
    echo "<div style='background: #f0fff4; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "✅ <strong>API 테스트 링크들:</strong><br>";
    echo "- <a href='api.php?action=health' target='_blank'>헬스 체크</a><br>";
    echo "- <a href='api.php?action=problems' target='_blank'>문제 목록 조회</a><br>";
    echo "- <a href='sample.html' target='_blank'>메인 시스템 실행</a><br>";
    echo "</div>";
} else {
    echo "<div style='background: #fff5f5; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "❌ 테이블이 없어서 API 테스트를 할 수 없습니다.";
    echo "</div>";
}

// 기본 문제집 생성 처리
if (isset($_GET['create_default']) && $_GET['create_default'] == '1') {
    echo "<h2>🛠️ 기본 문제집 생성</h2>";
    try {
        createDefaultProblemSet($pdo);
        echo "<div style='background: #f0fff4; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "✅ 기본 문제집이 생성되었습니다!<br>";
        echo "<a href='?' style='background: #667eea; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px;'>새로고침</a>";
        echo "</div>";
    } catch (Exception $e) {
        echo "<div style='background: #fff5f5; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "❌ 기본 문제집 생성 실패: " . htmlspecialchars($e->getMessage());
        echo "</div>";
    }
}

echo "<div style='text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e2e8f0;'>";
echo "<p style='color: #718096;'>GCP MariaDB 연결 테스트 완료 | " . date('Y-m-d H:i:s') . "</p>";
echo "</div>";

echo "</div>";
?> 