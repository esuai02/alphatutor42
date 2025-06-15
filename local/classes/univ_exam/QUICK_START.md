# 🚀 빠른 시작 가이드

## 현재 상황
현재 시스템에 PHP/MySQL이 설치되어 있지 않습니다. 가장 쉬운 설정 방법을 안내해드립니다.

## 🎯 가장 빠른 방법: XAMPP 설치

### 1단계: XAMPP 다운로드 및 설치
1. [XAMPP 공식 사이트](https://www.apachefriends.org/download.html)에서 Windows용 다운로드
2. 다운로드한 파일을 관리자 권한으로 실행
3. 기본 설정으로 설치 (보통 C:\xampp에 설치됨)

### 2단계: XAMPP 시작
1. XAMPP Control Panel 실행
2. **Apache** 와 **MySQL** 의 **Start** 버튼 클릭
3. 두 서비스가 모두 초록색으로 표시되면 준비 완료

### 3단계: 데이터베이스 자동 설정
브라우저에서 다음 주소로 접속:
```
http://localhost/your-project-path/setup_database.php
```

**또는** 프로젝트를 XAMPP htdocs로 복사:
1. 현재 프로젝트 폴더를 `C:\xampp\htdocs\alphatutor42`로 복사
2. 브라우저에서 `http://localhost/alphatutor42/local/classes/univ_exam/setup_database.php` 접속

### 4단계: 시스템 테스트
설정 완료 후 `sample.html` 페이지로 이동하여 시스템 테스트

## 🛠️ 대안 방법들

### 옵션 A: phpMyAdmin 사용
1. XAMPP 실행 후 `http://localhost/phpmyadmin` 접속
2. "새로 만들기" → 데이터베이스명: `alphatutor42`
3. "가져오기" 탭에서 `create_database.sql` 파일 업로드

### 옵션 B: MySQL 직접 설치
1. [MySQL Community Server](https://dev.mysql.com/downloads/mysql/) 다운로드
2. [PHP for Windows](https://windows.php.net/download/) 다운로드
3. 각각 설치 후 환경변수 PATH에 추가
4. 명령 프롬프트에서: `mysql -u root -p < create_database.sql`

### 옵션 C: Docker 사용 (고급)
```bash
docker run --name mysql-alphatutor -e MYSQL_ROOT_PASSWORD=password -p 3306:3306 -d mysql:8.0
```

## 🎉 설정 완료 후 확인사항

✅ **성공적으로 설정되었다면:**
- `sample.html` 페이지가 정상 로드
- 문제 목록에 30개 빈 슬롯 표시
- JSON 입력 모달이 정상 작동
- 개발자 콘솔에 에러 없음

❌ **문제가 있다면:**
- `logs/app.log` 파일 확인
- 브라우저 개발자 도구 콘솔 확인
- XAMPP Control Panel에서 Apache/MySQL 상태 확인

## 📞 문제 해결

### 포트 충돌 오류
- Apache 포트 변경: XAMPP → Config → apache → httpd.conf → `Listen 8080`
- MySQL 포트 변경: XAMPP → Config → mysql → my.ini → `port=3307`

### 권한 오류
- XAMPP Control Panel을 관리자 권한으로 실행
- Windows 방화벽에서 Apache/MySQL 허용

### 연결 오류
- `config.php`에서 DB 연결 정보 확인
- MySQL 서비스가 실행 중인지 확인

## 💡 유용한 링크

- [XAMPP 사용법](https://www.apachefriends.org/faq.html)
- [phpMyAdmin 가이드](https://docs.phpmyadmin.net/)
- [MySQL 기본 사용법](https://dev.mysql.com/doc/mysql-getting-started/en/)

---

**🎯 추천:** 개발 초기 단계라면 **XAMPP 설치**가 가장 간단하고 안전한 방법입니다! 