@echo off
echo 알파튜터42 개발 서버 시작 중...
echo.

REM PHP 경로들 확인
set PHP_PATHS=C:\xampp\php\php.exe;C:\php\php.exe;C:\Program Files\PHP\php.exe

for %%i in (%PHP_PATHS%) do (
    if exist "%%i" (
        echo PHP 발견: %%i
        echo 내장 서버 시작 중... (포트 8000)
        echo 브라우저에서 http://localhost:8000/sample.html 을 열어주세요
        echo.
        echo 서버를 종료하려면 Ctrl+C 를 누르세요
        "%%i" -S localhost:8000
        goto :end
    )
)

echo.
echo ❌ PHP를 찾을 수 없습니다.
echo.
echo 해결 방법:
echo 1. XAMPP 설치: https://www.apachefriends.org/download.html
echo 2. PHP 직접 설치: https://windows.php.net/download/
echo 3. WSL 사용: wsl --install
echo.
pause

:end 