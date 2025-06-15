# 🗄️ 데이터베이스 설정 가이드

## 📋 시스템 요구사항
- MySQL 5.7+ 또는 MariaDB 10.2+
- PHP 7.4+ (PDO MySQL 확장 포함)

## 🚀 빠른 설정 (권장)

### 옵션 1: XAMPP 사용
1. [XAMPP 다운로드](https://www.apachefriends.org/download.html)
2. XAMPP 제어판에서 Apache와 MySQL 시작
3. 브라우저에서 `http://localhost/phpmyadmin` 접속
4. "새로 만들기" 클릭하여 `alphatutor42` 데이터베이스 생성
5. "가져오기" 탭에서 `create_database.sql` 파일 업로드 실행

### 옵션 2: MySQL 직접 설치
1. [MySQL Community Server 다운로드](https://dev.mysql.com/downloads/mysql/)
2. 설치 후 MySQL 서비스 시작
3. 명령 프롬프트에서 실행:
```bash
mysql -u root -p
```
4. MySQL 콘솔에서 실행:
```sql
source C:/path/to/create_database.sql
```

## 🛠️ 수동 설정

### 1단계: 명령줄에서 MySQL 접속
```bash
# Windows (MySQL 설치된 경우)
mysql -u root -p

# XAMPP 사용 시
cd C:\xampp\mysql\bin
mysql -u root -p
```

### 2단계: 데이터베이스 생성
```sql
CREATE DATABASE IF NOT EXISTS alphatutor42 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE alphatutor42;
```

### 3단계: 테이블 생성
`create_database.sql` 파일의 내용을 실행하거나 직접 복사하여 실행

## 🔧 연결 정보 확인

현재 `config.php`에 설정된 정보:
- **호스트**: localhost
- **데이터베이스**: alphatutor42
- **사용자**: root
- **비밀번호**: (빈 문자열)

## 📊 테이블 구조

생성되는 테이블들:
1. `problem_sets` - 문제집 정보
2. `problems` - 문제 기본 정보
3. `problem_conditions` - 문제 조건
4. `analysis_insights` - 분석 정보
5. `highlight_tags` - 하이라이트 태그
6. `solution_steps` - 해설 단계
7. `creative_questions` - 창의적 질문
8. `creative_question_items` - 창의적 질문 항목
9. `key_points` - 핵심 포인트
10. `similar_problems` - 유사문제
11. `similar_problem_options` - 유사문제 선택지
12. `similar_problem_solution_steps` - 유사문제 해설

## 🔐 보안 권장사항

### 운영환경 설정
```php
// config.php 수정
define('DB_HOST', 'localhost');
define('DB_NAME', 'alphatutor42');
define('DB_USER', 'alphatutor_user');  // 전용 사용자 생성
define('DB_PASS', 'secure_password');   // 강력한 비밀번호 설정
define('DEBUG_MODE', false);            // 디버그 모드 비활성화
```

### 전용 사용자 생성
```sql
CREATE USER 'alphatutor_user'@'localhost' IDENTIFIED BY 'secure_password';
GRANT SELECT, INSERT, UPDATE, DELETE ON alphatutor42.* TO 'alphatutor_user'@'localhost';
FLUSH PRIVILEGES;
```

## 🧪 연결 테스트

데이터베이스 설정 완료 후 `sample.html` 페이지를 열어서:
1. 문제 목록이 로드되는지 확인
2. JSON 입력 모달이 정상 작동하는지 확인
3. 개발자 콘솔에서 API 에러가 없는지 확인

## 🐛 문제 해결

### 연결 오류 시
1. MySQL 서비스 실행 확인
2. 방화벽 설정 확인
3. `config.php`의 연결 정보 확인
4. `logs/app.log` 파일에서 상세 에러 확인

### 권한 오류 시
```sql
GRANT ALL PRIVILEGES ON alphatutor42.* TO 'root'@'localhost';
FLUSH PRIVILEGES;
```

## 📁 파일 구조
```
/alphatutor42/local/classes/univ_exam/
├── config.php              # DB 설정
├── api.php                 # REST API
├── sample.html             # 메인 페이지
├── create_database.sql     # DB 생성 스크립트
├── uploads/images/         # 이미지 업로드 폴더
└── logs/                   # 로그 파일
```

## 💡 추가 정보

- 기본 문제집(ID: 1)이 자동으로 생성됩니다
- 이미지 업로드는 `uploads/images/` 폴더에 저장됩니다
- 로그 파일은 `logs/app.log`에 기록됩니다
- 디버그 모드에서는 상세한 에러 정보가 표시됩니다 