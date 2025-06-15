# 🚨 DB 연결 실패 문제 해결

## 문제: "DB 연결에 실패했습니다. 오프라인 모드로 실행됩니다."

이 메시지는 다음과 같은 원인으로 발생할 수 있습니다:

## 🔍 진단 체크리스트

### 1단계: 서버 상태 확인
- [ ] 웹서버 (Apache/IIS) 실행 중
- [ ] MySQL 서버 실행 중
- [ ] PHP 설치 및 설정 완료

### 2단계: 연결 정보 확인
- [ ] `config.php`의 DB 정보가 올바른지 확인
- [ ] 데이터베이스 `alphatutor42`가 존재하는지 확인
- [ ] 테이블들이 생성되었는지 확인

## 🛠️ 해결 방법

### 🎯 방법 1: XAMPP 설치 (권장)

**1단계: XAMPP 다운로드**
```
https://www.apachefriends.org/download.html
```

**2단계: 설치 및 실행**
1. 관리자 권한으로 설치
2. XAMPP Control Panel 실행
3. Apache와 MySQL 시작 (초록색 표시 확인)

**3단계: 프로젝트 복사**
```
현재 폴더 전체를 C:\xampp\htdocs\alphatutor42\ 로 복사
```

**4단계: 데이터베이스 설정**
```
브라우저 접속: http://localhost/alphatutor42/local/classes/univ_exam/setup_database.php
```

**5단계: 시스템 실행**
```
브라우저 접속: http://localhost/alphatutor42/local/classes/univ_exam/sample.html
```

### 🎯 방법 2: 빠른 테스트 (PHP 내장 서버)

**PHP가 이미 설치되어 있다면:**
1. `start_server.bat` 더블 클릭
2. 브라우저에서 `http://localhost:8000/sample.html` 접속

### 🎯 방법 3: Docker 사용 (고급)

```bash
# MySQL 컨테이너 실행
docker run --name mysql-alphatutor \
  -e MYSQL_ROOT_PASSWORD=password \
  -e MYSQL_DATABASE=alphatutor42 \
  -p 3306:3306 -d mysql:8.0

# config.php 수정 후 PHP 서버 실행
php -S localhost:8000
```

## 🔧 문제별 상세 해결책

### 문제 1: "CORS 에러"
**해결책:**
- 파일을 웹서버를 통해 접속 (file:// 대신 http://)
- 브라우저에서 CORS 정책 우회: `--disable-web-security` 플래그

### 문제 2: "500 Internal Server Error"
**해결책:**
1. `logs/app.log` 파일 확인
2. 웹서버 에러 로그 확인
3. PHP 에러 메시지 확인

### 문제 3: "Access denied for user"
**해결책:**
```sql
# MySQL 콘솔에서 실행
CREATE USER 'root'@'localhost' IDENTIFIED BY '';
GRANT ALL PRIVILEGES ON *.* TO 'root'@'localhost';
FLUSH PRIVILEGES;
```

### 문제 4: "Database 'alphatutor42' doesn't exist"
**해결책:**
```sql
CREATE DATABASE alphatutor42 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

## 🧪 연결 테스트

### 테스트 1: MySQL 연결
```bash
mysql -u root -p
```

### 테스트 2: 웹서버 테스트
```
http://localhost/
```

### 테스트 3: API 테스트
```
http://localhost/alphatutor42/local/classes/univ_exam/api.php?action=problems
```

## 📱 브라우저 개발자 도구 확인

**F12 키를 눌러 개발자 도구를 열고:**
1. **Console 탭**: JavaScript 에러 확인
2. **Network 탭**: API 호출 실패 여부 확인
3. **Application 탭**: 로컬스토리지 데이터 확인

## 🆘 여전히 문제가 있다면

### 로그 파일 확인
- `logs/app.log` - 애플리케이션 로그
- `C:\xampp\apache\logs\error.log` - Apache 에러 로그
- `C:\xampp\mysql\data\*.err` - MySQL 에러 로그

### 포트 변경이 필요한 경우
**Apache 포트 변경:**
```
C:\xampp\apache\conf\httpd.conf
Listen 8080
```

**MySQL 포트 변경:**
```
C:\xampp\mysql\bin\my.ini
port=3307
```

### config.php 수정
```php
define('DB_HOST', 'localhost:3307');  // 포트가 변경된 경우
define('DB_USER', 'root');
define('DB_PASS', '');  // XAMPP 기본값은 빈 문자열
```

---

## 🎯 최종 권장사항

1. **초보자**: XAMPP 설치 → phpMyAdmin으로 DB 생성
2. **개발자**: Docker 또는 직접 설치
3. **임시 테스트**: PHP 내장 서버 사용

**💡 대부분의 문제는 XAMPP 설치로 해결됩니다!** 