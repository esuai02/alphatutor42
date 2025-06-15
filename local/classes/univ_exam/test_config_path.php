<?php
echo "=== Config.php 경로 테스트 ===\n<br>";

echo "현재 작업 디렉토리: " . getcwd() . "\n<br>";
echo "현재 스크립트 경로: " . __FILE__ . "\n<br>";
echo "현재 스크립트 디렉토리: " . __DIR__ . "\n<br>";

// config.php 파일 존재 확인
$configPath = 'config.php';
$fullConfigPath = __DIR__ . '/config.php';

echo "\n<br>=== 파일 존재 확인 ===\n<br>";
echo "상대 경로 'config.php' 존재: " . (file_exists($configPath) ? '✅ 예' : '❌ 아니오') . "\n<br>";
echo "절대 경로 '$fullConfigPath' 존재: " . (file_exists($fullConfigPath) ? '✅ 예' : '❌ 아니오') . "\n<br>";

// config.php 로드 시도
echo "\n<br>=== config.php 로드 테스트 ===\n<br>";

if (file_exists($configPath)) {
    try {
        require_once $configPath;
        echo "✅ config.php 로드 성공!\n<br>";
        
        // 정의된 상수들 확인
        if (defined('DB_HOST')) {
            echo "DB_HOST: " . DB_HOST . "\n<br>";
        }
        if (defined('DB_NAME')) {
            echo "DB_NAME: " . DB_NAME . "\n<br>";
        }
        if (defined('DEBUG_MODE')) {
            echo "DEBUG_MODE: " . (DEBUG_MODE ? 'true' : 'false') . "\n<br>";
        }
        
        // 함수 존재 확인
        if (function_exists('getDBConnection')) {
            echo "✅ getDBConnection 함수 정의됨\n<br>";
        } else {
            echo "❌ getDBConnection 함수 없음\n<br>";
        }
        
    } catch (Exception $e) {
        echo "❌ config.php 로드 실패: " . $e->getMessage() . "\n<br>";
    }
} else {
    echo "❌ config.php 파일을 찾을 수 없습니다.\n<br>";
}

echo "\n<br>=== 디렉토리 내용 ===\n<br>";
$files = scandir('.');
foreach ($files as $file) {
    if ($file != '.' && $file != '..' && strpos($file, 'config') !== false) {
        echo "- $file\n<br>";
    }
}
?> 