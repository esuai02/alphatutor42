<?php
// 모든 오류 표시 활성화
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "<!DOCTYPE html>";
echo "<html lang='ko'>";
echo "<head><meta charset='UTF-8'><title>디버그 테스트</title></head>";
echo "<body>";

echo "<h1>🔍 PHP 디버그 테스트</h1>";

echo "<h2>1. PHP 기본 정보</h2>";
echo "<p><strong>PHP Version:</strong> " . phpversion() . "</p>";
echo "<p><strong>현재 시간:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "<p><strong>현재 디렉토리:</strong> " . getcwd() . "</p>";

echo "<h2>2. 파일 존재 확인</h2>";
$files = ['config.php', 'config_local.php', 'test_full_system.php'];
foreach ($files as $file) {
    $exists = file_exists($file);
    echo "<p><strong>$file:</strong> " . ($exists ? '✅ 존재' : '❌ 없음') . "</p>";
}

echo "<h2>3. config.php 로드 테스트</h2>";
if (file_exists('config.php')) {
    try {
        echo "<p>config.php 로드 시도...</p>";
        ob_start();
        require_once 'config.php';
        $output = ob_get_clean();
        
        echo "<p>✅ config.php 로드 성공!</p>";
        if (!empty($output)) {
            echo "<p><strong>출력:</strong> <pre>" . htmlspecialchars($output) . "</pre></p>";
        }
        
        // 상수 확인
        if (defined('DB_HOST')) {
            echo "<p><strong>DB_HOST:</strong> " . DB_HOST . "</p>";
        }
        if (defined('DEBUG_MODE')) {
            echo "<p><strong>DEBUG_MODE:</strong> " . (DEBUG_MODE ? 'true' : 'false') . "</p>";
        }
        
        // 함수 확인
        if (function_exists('getDBConnection')) {
            echo "<p>✅ getDBConnection 함수 존재</p>";
        }
        
    } catch (ParseError $e) {
        echo "<p>❌ <strong>구문 오류:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p><strong>파일:</strong> " . $e->getFile() . "</p>";
        echo "<p><strong>라인:</strong> " . $e->getLine() . "</p>";
    } catch (Error $e) {
        echo "<p>❌ <strong>치명적 오류:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p><strong>파일:</strong> " . $e->getFile() . "</p>";
        echo "<p><strong>라인:</strong> " . $e->getLine() . "</p>";
    } catch (Exception $e) {
        echo "<p>❌ <strong>예외:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p><strong>파일:</strong> " . $e->getFile() . "</p>";
        echo "<p><strong>라인:</strong> " . $e->getLine() . "</p>";
    }
} else {
    echo "<p>❌ config.php 파일을 찾을 수 없습니다.</p>";
}

echo "<h2>4. 데이터베이스 연결 테스트</h2>";
if (function_exists('getDBConnection')) {
    try {
        echo "<p>데이터베이스 연결 시도...</p>";
        $pdo = getDBConnection();
        echo "<p>✅ 데이터베이스 연결 성공!</p>";
    } catch (Exception $e) {
        echo "<p>❌ <strong>DB 연결 실패:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    }
} else {
    echo "<p>⚠️ getDBConnection 함수가 정의되지 않았습니다.</p>";
}

echo "<h2>5. 시스템 정보</h2>";
echo "<p><strong>서버 소프트웨어:</strong> " . ($_SERVER['SERVER_SOFTWARE'] ?? 'N/A') . "</p>";
echo "<p><strong>Document Root:</strong> " . ($_SERVER['DOCUMENT_ROOT'] ?? 'N/A') . "</p>";
echo "<p><strong>Script Name:</strong> " . ($_SERVER['SCRIPT_NAME'] ?? 'N/A') . "</p>";

echo "<h2>6. 메모리 및 시간 제한</h2>";
echo "<p><strong>Memory Limit:</strong> " . ini_get('memory_limit') . "</p>";
echo "<p><strong>Max Execution Time:</strong> " . ini_get('max_execution_time') . "초</p>";

echo "<hr>";
echo "<p><em>테스트 완료 - " . date('Y-m-d H:i:s') . "</em></p>";
echo "</body></html>";
?> 