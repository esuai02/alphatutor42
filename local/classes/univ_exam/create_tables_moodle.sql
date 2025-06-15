-- GCP 서버 moodle DB용 테이블 생성 스크립트
-- 실행 방법: MySQL 콘솔에서 직접 실행 또는 phpMyAdmin 사용
-- 모든 테이블은 alpha_ prefix 사용하여 moodle 테이블과 충돌 방지

USE moodle;

-- 1. 문제집 테이블
CREATE TABLE IF NOT EXISTS alpha_problem_sets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    total_problems INT DEFAULT 30,
    version VARCHAR(10) DEFAULT '1.0',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_created_at (created_at),
    INDEX idx_title (title)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. 문제 테이블 (메인 테이블)
CREATE TABLE IF NOT EXISTS alpha_problems (
    id INT PRIMARY KEY AUTO_INCREMENT,
    problem_set_id INT DEFAULT 1,
    problem_number INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    category ENUM('대수', '기하', '해석', '확률통계') DEFAULT '대수',
    difficulty ENUM('1등급', '2등급', '3등급', '4등급', '5등급') DEFAULT '3등급',
    estimated_time INT DEFAULT 20,
    description TEXT,
    question_text TEXT,
    key_strategy TEXT,
    author_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (problem_set_id) REFERENCES alpha_problem_sets(id) ON DELETE CASCADE,
    INDEX idx_problem_set_number (problem_set_id, problem_number),
    INDEX idx_category (category),
    INDEX idx_difficulty (difficulty),
    UNIQUE KEY unique_problem_in_set (problem_set_id, problem_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. 문제 조건 테이블
CREATE TABLE IF NOT EXISTS alpha_problem_conditions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    problem_id INT NOT NULL,
    condition_text TEXT NOT NULL,
    condition_order INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (problem_id) REFERENCES alpha_problems(id) ON DELETE CASCADE,
    INDEX idx_problem_order (problem_id, condition_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. 분석 인사이트 테이블
CREATE TABLE IF NOT EXISTS alpha_analysis_insights (
    id INT PRIMARY KEY AUTO_INCREMENT,
    problem_id INT NOT NULL,
    insight_type ENUM('핵심개념', '문제해결전략', '주의사항', '확장문제') DEFAULT '핵심개념',
    insight_text TEXT NOT NULL,
    insight_order INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (problem_id) REFERENCES alpha_problems(id) ON DELETE CASCADE,
    INDEX idx_problem_type (problem_id, insight_type),
    INDEX idx_problem_order (problem_id, insight_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. 하이라이트 태그 테이블
CREATE TABLE IF NOT EXISTS alpha_highlight_tags (
    id INT PRIMARY KEY AUTO_INCREMENT,
    problem_id INT NOT NULL,
    tag_text VARCHAR(255) NOT NULL,
    tag_color VARCHAR(7) DEFAULT '#ffeb3b',
    tag_order INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (problem_id) REFERENCES alpha_problems(id) ON DELETE CASCADE,
    INDEX idx_problem_order (problem_id, tag_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. 해결 단계 테이블
CREATE TABLE IF NOT EXISTS alpha_solution_steps (
    id INT PRIMARY KEY AUTO_INCREMENT,
    problem_id INT NOT NULL,
    step_number INT NOT NULL,
    step_title VARCHAR(255) NOT NULL,
    step_content TEXT NOT NULL,
    step_image VARCHAR(255) NULL,
    step_explanation TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (problem_id) REFERENCES alpha_problems(id) ON DELETE CASCADE,
    INDEX idx_problem_step (problem_id, step_number),
    UNIQUE KEY unique_step_in_problem (problem_id, step_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. 창의적 질문 테이블
CREATE TABLE IF NOT EXISTS alpha_creative_questions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    problem_id INT NOT NULL,
    question_text TEXT NOT NULL,
    question_type ENUM('확장', '변형', '응용', '연결') DEFAULT '확장',
    difficulty_level ENUM('기초', '발전', '심화') DEFAULT '발전',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (problem_id) REFERENCES alpha_problems(id) ON DELETE CASCADE,
    INDEX idx_problem_type (problem_id, question_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. 창의적 질문 항목 테이블
CREATE TABLE IF NOT EXISTS alpha_creative_question_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    creative_question_id INT NOT NULL,
    item_text TEXT NOT NULL,
    item_order INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (creative_question_id) REFERENCES alpha_creative_questions(id) ON DELETE CASCADE,
    INDEX idx_question_order (creative_question_id, item_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. 유사 문제 테이블
CREATE TABLE IF NOT EXISTS alpha_similar_problems (
    id INT PRIMARY KEY AUTO_INCREMENT,
    problem_id INT NOT NULL,
    similar_title VARCHAR(255) NOT NULL,
    similar_description TEXT,
    difficulty_level ENUM('쉬움', '보통', '어려움') DEFAULT '보통',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (problem_id) REFERENCES alpha_problems(id) ON DELETE CASCADE,
    INDEX idx_problem_difficulty (problem_id, difficulty_level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. 유사 문제 선택지 테이블
CREATE TABLE IF NOT EXISTS alpha_similar_problem_options (
    id INT PRIMARY KEY AUTO_INCREMENT,
    similar_problem_id INT NOT NULL,
    option_number INT NOT NULL,
    option_text TEXT NOT NULL,
    is_correct BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (similar_problem_id) REFERENCES alpha_similar_problems(id) ON DELETE CASCADE,
    INDEX idx_similar_option (similar_problem_id, option_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. 유사 문제 해결 단계 테이블
CREATE TABLE IF NOT EXISTS alpha_similar_problem_solution_steps (
    id INT PRIMARY KEY AUTO_INCREMENT,
    similar_problem_id INT NOT NULL,
    step_number INT NOT NULL,
    step_description TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (similar_problem_id) REFERENCES alpha_similar_problems(id) ON DELETE CASCADE,
    INDEX idx_similar_step (similar_problem_id, step_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. 핵심 포인트 테이블
CREATE TABLE IF NOT EXISTS alpha_key_points (
    id INT PRIMARY KEY AUTO_INCREMENT,
    problem_id INT NOT NULL,
    point_title VARCHAR(255) NOT NULL,
    point_description TEXT NOT NULL,
    point_type ENUM('개념', '공식', '팁', '주의사항') DEFAULT '개념',
    point_order INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (problem_id) REFERENCES alpha_problems(id) ON DELETE CASCADE,
    INDEX idx_problem_type (problem_id, point_type),
    INDEX idx_problem_order (problem_id, point_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 기본 데이터 삽입
INSERT IGNORE INTO alpha_problem_sets (id, title, description, total_problems, version) 
VALUES (1, 'GCP 연동 수학 문제집', 'GCP MariaDB 서버와 연동된 수학 문제 학습 시스템', 30, '1.0');

-- 샘플 문제 데이터 삽입 (테스트용)
INSERT IGNORE INTO alpha_problems (id, problem_set_id, problem_number, title, category, difficulty, estimated_time, description, question_text, key_strategy, author_notes) 
VALUES (1, 1, 1, '이차방정식의 해', '대수', '3등급', 15, '이차방정식을 풀어 실근을 구하는 문제입니다.', 'x² - 5x + 6 = 0의 해를 구하시오.', '인수분해 또는 근의 공식을 사용합니다.', 'GCP 서버 연동 테스트용 샘플 문제');

-- 샘플 해결 단계 삽입
INSERT IGNORE INTO alpha_solution_steps (problem_id, step_number, step_title, step_content, step_explanation) VALUES
(1, 1, '인수분해', 'x² - 5x + 6 = (x - 2)(x - 3)', '두 수의 곱이 6이고 합이 5인 수를 찾습니다'),
(1, 2, '해 구하기', 'x - 2 = 0 또는 x - 3 = 0', '각 인수를 0으로 놓고 해를 구합니다'),
(1, 3, '최종 답', 'x = 2 또는 x = 3', '이차방정식의 두 실근입니다');

-- 성공 메시지 및 확인
SELECT 'GCP 서버 moodle DB에 alpha_ 테이블이 성공적으로 생성되었습니다!' AS '설치 완료';

-- 생성된 테이블 목록 확인
SELECT TABLE_NAME AS '생성된 테이블', 
       TABLE_ROWS AS '레코드 수',
       CREATE_TIME AS '생성 시간'
FROM information_schema.tables 
WHERE table_schema = 'moodle' 
  AND table_name LIKE 'alpha_%' 
ORDER BY table_name;

-- 기본 데이터 확인
SELECT ps.title AS '문제집', p.problem_number AS '문제번호', p.title AS '문제제목'
FROM alpha_problem_sets ps
LEFT JOIN alpha_problems p ON ps.id = p.problem_set_id
ORDER BY p.problem_number; 