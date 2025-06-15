@echo off
echo ================================================
echo        GCP MariaDB 연결 빠른 테스트
echo ================================================
echo.

echo 🔍 1. 현재 설정 확인 중...
echo 호스트: 34.64.175.237
echo 사용자: bessi02
echo 포트: 3306
echo.

echo 🌐 2. GCP 서버 연결 테스트 중...
curl -I http://34.64.175.237 --connect-timeout 5 --max-time 10 2>nul
if %errorlevel% equ 0 (
    echo ✅ GCP 서버 연결 성공
) else (
    echo ❌ GCP 서버 연결 실패
)
echo.

echo 📁 3. 파일 준비 상태 확인...
if exist "config.php" (
    echo ✅ config.php 존재
) else (
    echo ❌ config.php 누락
)

if exist "api.php" (
    echo ✅ api.php 존재
) else (
    echo ❌ api.php 누락
)

if exist "sample.html" (
    echo ✅ sample.html 존재
) else (
    echo ❌ sample.html 누락
)

if exist "test_connection.php" (
    echo ✅ test_connection.php 존재
) else (
    echo ❌ test_connection.php 누락
)

if exist "setup_database_gcp.php" (
    echo ✅ setup_database_gcp.php 존재
) else (
    echo ❌ setup_database_gcp.php 누락
)

echo.

echo 🚀 4. 다음 단계 안내
echo ================================================
echo 1. GCP 서버에 파일들을 업로드하세요
echo 2. 브라우저에서 다음 URL들을 테스트하세요:
echo.
echo    📋 연결 테스트:
echo    http://34.64.175.237/alphatutor42/test_connection.php
echo.
echo    🛠️ DB 자동 설정:
echo    http://34.64.175.237/alphatutor42/setup_database_gcp.php
echo.
echo    🔍 API 헬스 체크:
echo    http://34.64.175.237/alphatutor42/api.php?action=health
echo.
echo    🎯 메인 시스템:
echo    http://34.64.175.237/alphatutor42/sample.html
echo.
echo ================================================
echo.

echo 📚 추가 도움말:
echo - 파일 업로드가 완료되면 test_connection.php를 먼저 실행하세요
echo - DB 연결 문제가 있으면 gcp_deployment_guide.md를 참조하세요
echo - 모든 테스트가 성공하면 sample.html에서 시스템을 사용할 수 있습니다
echo.

pause 