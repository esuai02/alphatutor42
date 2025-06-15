# 🌐 GCP 서버 배포 및 연결 가이드

## 📋 현재 상황
- ✅ GCP MariaDB 서버 정보 설정 완료
- ✅ 데이터베이스 생성 스크립트 준비 완료  
- ✅ API GCP 연결 지원 완료
- ✅ 연결 테스트 스크립트 준비 완료

## 🚀 배포 절차

### 1단계: GCP 서버에 파일 업로드

다음 파일들을 GCP 서버의 웹 디렉토리에 업로드하세요:

```
/var/www/html/alphatutor42/
├── config.php                  # GCP DB 연결 설정
├── api.php                     # REST API (GCP 지원)
├── sample.html                 # 메인 시스템
├── setup_database_gcp.php      # 자동 DB 설정
├── create_database_gcp.sql     # 수동 DB 설정  
├── test_connection.php         # 연결 테스트
└── uploads/images/             # 업로드 디렉토리
```

### 2단계: 데이터베이스 설정

#### 옵션 A: 자동 설정 (권장)
```bash
# 브라우저에서 접속
http://34.64.175.237/alphatutor42/setup_database_gcp.php
```

#### 옵션 B: 수동 설정
```bash
# SSH로 GCP 서버 접속 후
mysql -u bessi02 -p'@MCtrigd7128' < create_database_gcp.sql
```

### 3단계: 연결 테스트

#### 기본 연결 테스트
```bash
http://34.64.175.237/alphatutor42/test_connection.php
```

#### API 테스트
```bash
# 헬스 체크
http://34.64.175.237/alphatutor42/api.php?action=health

# 문제 목록 조회  
http://34.64.175.237/alphatutor42/api.php?action=problems
```

#### 메인 시스템 실행
```bash
http://34.64.175.237/alphatutor42/sample.html
```

## 🔧 설정 확인사항

### MariaDB 서버 설정
```sql
-- 원격 접속 허용 확인
SELECT User, Host FROM mysql.user WHERE User='bessi02';

-- 권한 확인
SHOW GRANTS FOR 'bessi02'@'%';

-- 바인드 주소 확인 (my.cnf)
bind-address = 0.0.0.0
```

### 방화벽 설정
```bash
# GCP 방화벽 규칙 확인
gcloud compute firewall-rules list

# MySQL 포트 허용 (필요시)
gcloud compute firewall-rules create allow-mysql \
  --allow tcp:3306 \
  --source-ranges 0.0.0.0/0 \
  --description "Allow MySQL"
```

### 웹서버 설정
```bash
# Apache/Nginx 상태 확인
sudo systemctl status apache2
# 또는
sudo systemctl status nginx

# PHP 모듈 확인
php -m | grep -i pdo
php -m | grep -i mysql
```

## 🛠️ 문제 해결

### 연결 실패 시
1. **MariaDB 서비스 상태 확인**
   ```bash
   sudo systemctl status mariadb
   sudo systemctl start mariadb  # 필요시
   ```

2. **사용자 권한 재설정**
   ```sql
   CREATE USER 'bessi02'@'%' IDENTIFIED BY '@MCtrigd7128';
   GRANT ALL PRIVILEGES ON *.* TO 'bessi02'@'%';
   FLUSH PRIVILEGES;
   ```

3. **포트 접근성 확인**
   ```bash
   netstat -tlnp | grep :3306
   telnet localhost 3306
   ```

### 권한 오류 시
```sql
-- alphatutor42 DB 전용 권한
GRANT ALL PRIVILEGES ON alphatutor42.* TO 'bessi02'@'%';

-- moodle DB의 alpha_ 테이블 권한
GRANT ALL PRIVILEGES ON moodle.* TO 'bessi02'@'%';
FLUSH PRIVILEGES;
```

### 웹서버 오류 시
```bash
# 로그 확인
sudo tail -f /var/log/apache2/error.log
sudo tail -f /var/log/nginx/error.log

# 권한 설정
sudo chown -R www-data:www-data /var/www/html/alphatutor42/
sudo chmod -R 755 /var/www/html/alphatutor42/
```

## 📊 성능 최적화

### MariaDB 설정
```sql
-- 연결 타임아웃 설정
SET GLOBAL wait_timeout = 300;
SET GLOBAL interactive_timeout = 300;

-- 최대 연결 수 확인
SHOW VARIABLES LIKE 'max_connections';
```

### PHP 설정
```ini
; php.ini 설정
max_execution_time = 30
memory_limit = 256M
upload_max_filesize = 10M
post_max_size = 10M
```

## 🔒 보안 권장사항

### 1. 사용자 권한 최소화
```sql
-- 전용 사용자 생성
CREATE USER 'alphatutor_user'@'%' IDENTIFIED BY 'strong_password';
GRANT SELECT, INSERT, UPDATE, DELETE ON alphatutor42.* TO 'alphatutor_user'@'%';
```

### 2. 방화벽 제한
```bash
# 특정 IP만 허용
gcloud compute firewall-rules create allow-mysql-limited \
  --allow tcp:3306 \
  --source-ranges YOUR_IP/32
```

### 3. SSL 연결 설정
```php
// config.php에 SSL 옵션 추가
$pdo = new PDO($dsn, $user, $pass, [
    PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
    PDO::MYSQL_ATTR_SSL_CA => '/path/to/ca-cert.pem'
]);
```

## 📈 모니터링

### 로그 확인
```bash
# 애플리케이션 로그
tail -f /var/www/html/alphatutor42/logs/app.log

# MariaDB 로그
sudo tail -f /var/log/mysql/error.log
```

### 성능 모니터링
```sql
-- 활성 연결 확인
SHOW PROCESSLIST;

-- 슬로우 쿼리 확인
SHOW VARIABLES LIKE 'slow_query_log';
```

## ✅ 배포 체크리스트

- [ ] GCP 서버에 파일 업로드 완료
- [ ] MariaDB 서버 실행 중
- [ ] 사용자 권한 설정 완료  
- [ ] 방화벽 규칙 설정 완료
- [ ] 데이터베이스 테이블 생성 완료
- [ ] 웹서버 실행 중
- [ ] PHP 및 확장 모듈 설치 완료
- [ ] 연결 테스트 성공
- [ ] API 테스트 성공
- [ ] 메인 시스템 동작 확인

## 🎯 다음 단계

1. **배포 후 테스트**: 모든 기능이 정상 동작하는지 확인
2. **데이터 백업**: 정기적인 데이터베이스 백업 설정
3. **모니터링 설정**: 시스템 상태 모니터링 도구 구성
4. **보안 강화**: SSL 인증서 및 추가 보안 설정

---

💡 **문제 발생 시**: `test_connection.php`를 먼저 실행하여 연결 상태를 확인하세요! 