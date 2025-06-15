-- 데이터베이스 생성 및 사용
CREATE DATABASE IF NOT EXISTS alphatutor42 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE alphatutor42;

-- 문제집 테이블
CREATE TABLE problem_sets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    total_problems INT DEFAULT 30,
    version VARCHAR(10) DEFAULT '1.0',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 문제 테이블
CREATE TABLE problems (
    id INT PRIMARY KEY AUTO_INCREMENT,
    problem_set_id INT,
    problem_number INT,
    title VARCHAR(255) NOT NULL,
    category ENUM('대수', '기하', '해석', '확률통계'),
    difficulty ENUM('1등급', '2등급', '3등급', '4등급', '5등급'),
    estimated_time INT,
    description TEXT,
    question_text TEXT,
    key_strategy TEXT,
    author VARCHAR(100),
    source VARCHAR(255),
    tags JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (problem_set_id) REFERENCES problem_sets(id),
    INDEX idx_problem_set_number (problem_set_id, problem_number)
);

-- 문제 조건 테이블
CREATE TABLE problem_conditions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    problem_id INT,
    condition_order INT,
    condition_text TEXT,
    FOREIGN KEY (problem_id) REFERENCES problems(id),
    INDEX idx_problem_order (problem_id, condition_order)
);

-- 분석 정보 테이블
CREATE TABLE analysis_insights (
    id INT PRIMARY KEY AUTO_INCREMENT,
    problem_id INT,
    insight_order INT,
    insight_text TEXT,
    FOREIGN KEY (problem_id) REFERENCES problems(id)
);

-- 하이라이트 태그 테이블
CREATE TABLE highlight_tags (
    id INT PRIMARY KEY AUTO_INCREMENT,
    problem_id INT,
    text VARCHAR(255),
    insight_number INT,
    explanation TEXT,
    FOREIGN KEY (problem_id) REFERENCES problems(id)
);

-- 해설 단계 테이블
CREATE TABLE solution_steps (
    id INT PRIMARY KEY AUTO_INCREMENT,
    problem_id INT,
    step_number INT,
    question TEXT,
    answer TEXT,
    key_point TEXT,
    difficulty ENUM('easy', 'medium', 'hard'),
    estimated_time INT,
    FOREIGN KEY (problem_id) REFERENCES problems(id),
    INDEX idx_problem_step (problem_id, step_number)
);

-- 창의적 질문 테이블
CREATE TABLE creative_questions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    problem_id INT,
    title VARCHAR(255),
    footer TEXT,
    FOREIGN KEY (problem_id) REFERENCES problems(id)
);

-- 창의적 질문 항목 테이블
CREATE TABLE creative_question_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    creative_question_id INT,
    question_order INT,
    text TEXT,
    hint TEXT,
    category ENUM('확장', '일반화', '변형', '연결'),
    FOREIGN KEY (creative_question_id) REFERENCES creative_questions(id)
);

-- 핵심 포인트 테이블
CREATE TABLE key_points (
    id INT PRIMARY KEY AUTO_INCREMENT,
    problem_id INT,
    point_order INT,
    point_text TEXT,
    FOREIGN KEY (problem_id) REFERENCES problems(id)
);

-- 유사문제 테이블
CREATE TABLE similar_problems (
    id INT PRIMARY KEY AUTO_INCREMENT,
    problem_id INT,
    description TEXT,
    math_expression TEXT,
    correct_answer INT,
    final_answer TEXT,
    explanation TEXT,
    FOREIGN KEY (problem_id) REFERENCES problems(id)
);

-- 유사문제 선택지 테이블
CREATE TABLE similar_problem_options (
    id INT PRIMARY KEY AUTO_INCREMENT,
    similar_problem_id INT,
    option_number INT,
    option_value INT,
    option_text VARCHAR(255),
    explanation TEXT,
    FOREIGN KEY (similar_problem_id) REFERENCES similar_problems(id)
);

-- 유사문제 해설 단계 테이블
CREATE TABLE similar_problem_solution_steps (
    id INT PRIMARY KEY AUTO_INCREMENT,
    similar_problem_id INT,
    step_order INT,
    title VARCHAR(255),
    content TEXT,
    math_formula TEXT,
    FOREIGN KEY (similar_problem_id) REFERENCES similar_problems(id)
);

-- 기본 문제집 데이터 삽입
INSERT INTO problem_sets (id, title, description, total_problems, version) 
VALUES (1, '기본 문제집', '수학 문제 학습 시스템 기본 문제집', 30, '1.0');

-- 업로드 디렉토리 생성을 위한 알림
-- uploads/images/ 디렉토리를 수동으로 생성해주세요. 