<?php
/**
 * 수학 문제 학습 시스템 REST API
 * GCP MariaDB 서버 연동 버전
 */

require_once 'config.php';

// CORS 헤더 설정
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json; charset=utf-8');

// OPTIONS 요청 처리
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    // 데이터베이스 연결
    $pdo = getDBConnection();
    $prefix = defined('TABLE_PREFIX') ? TABLE_PREFIX : '';
    
    // 테이블 존재 여부 확인
    if (!checkTablesExist($pdo)) {
        writeLog("테이블이 존재하지 않습니다. 먼저 create_database_gcp.sql을 실행해주세요.", 'WARNING');
        sendErrorResponse('데이터베이스 테이블이 존재하지 않습니다. 관리자에게 문의하세요.', 503);
    }
    
    // 라우팅
    $action = $_GET['action'] ?? '';
    $method = $_SERVER['REQUEST_METHOD'];
    
    writeLog("API 요청: {$method} {$action}", 'INFO');
    
    switch ($action) {
        case 'problems':
            handleProblemsRequest($pdo, $method, $prefix);
            break;
            
        case 'problem':
            handleProblemRequest($pdo, $method, $prefix);
            break;
            
        case 'upload':
            handleUploadRequest($method);
            break;
            
        case 'health':
            handleHealthCheck($pdo, $prefix);
            break;
            
        default:
            sendErrorResponse('알 수 없는 API 액션입니다: ' . $action, 404);
    }
    
} catch (PDOException $e) {
    writeLog("데이터베이스 에러: " . $e->getMessage(), 'ERROR');
    sendErrorResponse('데이터베이스 연결 실패: ' . ($DEBUG_MODE ? $e->getMessage() : '관리자에게 문의하세요'), 500);
} catch (Exception $e) {
    writeLog("일반 에러: " . $e->getMessage(), 'ERROR');
    sendErrorResponse('서버 에러: ' . ($DEBUG_MODE ? $e->getMessage() : '관리자에게 문의하세요'), 500);
}

/**
 * 문제 목록 관련 요청 처리
 */
function handleProblemsRequest($pdo, $method, $prefix) {
    switch ($method) {
        case 'GET':
            getProblems($pdo, $prefix);
            break;
        case 'POST':
            createProblem($pdo, $prefix);
            break;
        default:
            sendErrorResponse('지원하지 않는 HTTP 메소드입니다: ' . $method, 405);
    }
}

/**
 * 개별 문제 관련 요청 처리  
 */
function handleProblemRequest($pdo, $method, $prefix) {
    $problemId = $_GET['id'] ?? null;
    
    if (!$problemId || !is_numeric($problemId)) {
        sendErrorResponse('유효한 문제 ID가 필요합니다.', 400);
    }
    
    switch ($method) {
        case 'GET':
            getProblem($pdo, $problemId, $prefix);
            break;
        case 'PUT':
            updateProblem($pdo, $problemId, $prefix);
            break;
        case 'DELETE':
            deleteProblem($pdo, $problemId, $prefix);
            break;
        default:
            sendErrorResponse('지원하지 않는 HTTP 메소드입니다: ' . $method, 405);
    }
}

/**
 * 모든 문제 조회 (GCP MariaDB)
 */
function getProblems($pdo, $prefix) {
    try {
        $problemSetId = $_GET['problem_set_id'] ?? 1;
        
        // 문제집 정보 조회
        $stmt = $pdo->prepare("
            SELECT * FROM {$prefix}problem_sets 
            WHERE id = :problem_set_id
        ");
        $stmt->execute(['problem_set_id' => $problemSetId]);
        $problemSet = $stmt->fetch();
        
        if (!$problemSet) {
            // 기본 문제집 생성
            createDefaultProblemSet($pdo);
            $problemSet = [
                'id' => 1,
                'title' => 'GCP 연동 문제집',
                'description' => 'GCP MariaDB 서버와 연동된 수학 문제 학습 시스템',
                'total_problems' => 30,
                'version' => '1.0'
            ];
        }
        
        // 모든 문제 조회 (연관 데이터 포함)
        $stmt = $pdo->prepare("
            SELECT 
                p.*,
                GROUP_CONCAT(DISTINCT pc.condition_text ORDER BY pc.condition_order SEPARATOR '|||') as conditions,
                GROUP_CONCAT(DISTINCT CONCAT(ai.insight_type, ':::', ai.insight_text) ORDER BY ai.insight_order SEPARATOR '|||') as insights,
                GROUP_CONCAT(DISTINCT CONCAT(ht.tag_text, ':::', COALESCE(ht.tag_color, '#ffeb3b')) ORDER BY ht.tag_order SEPARATOR '|||') as highlight_tags,
                GROUP_CONCAT(DISTINCT CONCAT(ss.step_number, ':::', ss.step_title, ':::', ss.step_content, ':::', COALESCE(ss.step_image, ''), ':::', COALESCE(ss.step_explanation, '')) ORDER BY ss.step_number SEPARATOR '|||') as solution_steps,
                GROUP_CONCAT(DISTINCT CONCAT(kp.point_title, ':::', kp.point_description, ':::', kp.point_type) ORDER BY kp.point_order SEPARATOR '|||') as key_points
            FROM {$prefix}problems p
            LEFT JOIN {$prefix}problem_conditions pc ON p.id = pc.problem_id
            LEFT JOIN {$prefix}analysis_insights ai ON p.id = ai.problem_id  
            LEFT JOIN {$prefix}highlight_tags ht ON p.id = ht.problem_id
            LEFT JOIN {$prefix}solution_steps ss ON p.id = ss.problem_id
            LEFT JOIN {$prefix}key_points kp ON p.id = kp.problem_id
            WHERE p.problem_set_id = :problem_set_id
            GROUP BY p.id
            ORDER BY p.problem_number
        ");
        $stmt->execute(['problem_set_id' => $problemSetId]);
        $problems = $stmt->fetchAll();
        
        // 데이터 구조 변환
        $processedProblems = [];
        foreach ($problems as $problem) {
            $processedProblems[] = processProblemData($problem);
        }
        
        // 30개 슬롯으로 확장
        $fullProblems = [];
        for ($i = 1; $i <= 30; $i++) {
            $existingProblem = null;
            foreach ($processedProblems as $problem) {
                if ($problem['problem_number'] == $i) {
                    $existingProblem = $problem;
                    break;
                }
            }
            
            $fullProblems[] = $existingProblem ?: [
                'id' => null,
                'problem_number' => $i,
                'title' => '',
                'category' => '대수',
                'difficulty' => '3등급',
                'estimated_time' => 20,
                'description' => '',
                'question_text' => '',
                'key_strategy' => '',
                'author_notes' => '',
                'conditions' => [],
                'analysis_insights' => [],
                'highlight_tags' => [],
                'solution_steps' => [],
                'key_points' => [],
                'created_at' => null,
                'updated_at' => null
            ];
        }
        
        writeLog("GCP MariaDB에서 문제 목록 조회 성공: " . count($processedProblems) . "개", 'INFO');
        
        sendSuccessResponse([
            'problem_set' => $problemSet,
            'problems' => $fullProblems,
            'total_count' => count($processedProblems),
            'server_info' => 'GCP MariaDB 연결'
        ]);
        
    } catch (PDOException $e) {
        writeLog("문제 목록 조회 실패: " . $e->getMessage(), 'ERROR');
        sendErrorResponse('문제 목록을 불러올 수 없습니다: ' . $e->getMessage(), 500);
    }
}

/**
 * 문제 데이터 처리 함수
 */
function processProblemData($problem) {
    // 조건 파싱
    $conditions = [];
    if (!empty($problem['conditions'])) {
        $conditionList = explode('|||', $problem['conditions']);
        foreach ($conditionList as $condition) {
            if (!empty($condition)) {
                $conditions[] = $condition;
            }
        }
    }
    
    // 인사이트 파싱
    $insights = [];
    if (!empty($problem['insights'])) {
        $insightList = explode('|||', $problem['insights']);
        foreach ($insightList as $insight) {
            if (!empty($insight) && strpos($insight, ':::') !== false) {
                list($type, $text) = explode(':::', $insight, 2);
                $insights[] = [
                    'type' => $type,
                    'text' => $text
                ];
            }
        }
    }
    
    // 하이라이트 태그 파싱
    $highlightTags = [];
    if (!empty($problem['highlight_tags'])) {
        $tagList = explode('|||', $problem['highlight_tags']);
        foreach ($tagList as $tag) {
            if (!empty($tag) && strpos($tag, ':::') !== false) {
                list($text, $color) = explode(':::', $tag, 2);
                $highlightTags[] = [
                    'text' => $text,
                    'color' => $color
                ];
            }
        }
    }
    
    // 해결 단계 파싱
    $solutionSteps = [];
    if (!empty($problem['solution_steps'])) {
        $stepList = explode('|||', $problem['solution_steps']);
        foreach ($stepList as $step) {
            if (!empty($step)) {
                $stepParts = explode(':::', $step);
                if (count($stepParts) >= 3) {
                    $solutionSteps[] = [
                        'step_number' => (int)$stepParts[0],
                        'step_title' => $stepParts[1],
                        'step_content' => $stepParts[2],
                        'step_image' => $stepParts[3] ?? '',
                        'step_explanation' => $stepParts[4] ?? ''
                    ];
                }
            }
        }
    }
    
    // 핵심 포인트 파싱
    $keyPoints = [];
    if (!empty($problem['key_points'])) {
        $pointList = explode('|||', $problem['key_points']);
        foreach ($pointList as $point) {
            if (!empty($point)) {
                $pointParts = explode(':::', $point);
                if (count($pointParts) >= 3) {
                    $keyPoints[] = [
                        'title' => $pointParts[0],
                        'description' => $pointParts[1],
                        'type' => $pointParts[2]
                    ];
                }
            }
        }
    }
    
    return [
        'id' => $problem['id'],
        'problem_number' => (int)$problem['problem_number'],
        'title' => $problem['title'],
        'category' => $problem['category'],
        'difficulty' => $problem['difficulty'],
        'estimated_time' => (int)$problem['estimated_time'],
        'description' => $problem['description'],
        'question_text' => $problem['question_text'],
        'key_strategy' => $problem['key_strategy'],
        'author_notes' => $problem['author_notes'],
        'conditions' => $conditions,
        'analysis_insights' => $insights,
        'highlight_tags' => $highlightTags,
        'solution_steps' => $solutionSteps,
        'key_points' => $keyPoints,
        'created_at' => $problem['created_at'],
        'updated_at' => $problem['updated_at']
    ];
}

/**
 * 개별 문제 조회
 */
function getProblem($pdo, $problemId, $prefix) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                p.*,
                GROUP_CONCAT(DISTINCT pc.condition_text ORDER BY pc.condition_order SEPARATOR '|||') as conditions,
                GROUP_CONCAT(DISTINCT CONCAT(ai.insight_type, ':::', ai.insight_text) ORDER BY ai.insight_order SEPARATOR '|||') as insights,
                GROUP_CONCAT(DISTINCT CONCAT(ht.tag_text, ':::', COALESCE(ht.tag_color, '#ffeb3b')) ORDER BY ht.tag_order SEPARATOR '|||') as highlight_tags,
                GROUP_CONCAT(DISTINCT CONCAT(ss.step_number, ':::', ss.step_title, ':::', ss.step_content, ':::', COALESCE(ss.step_image, ''), ':::', COALESCE(ss.step_explanation, '')) ORDER BY ss.step_number SEPARATOR '|||') as solution_steps,
                GROUP_CONCAT(DISTINCT CONCAT(kp.point_title, ':::', kp.point_description, ':::', kp.point_type) ORDER BY kp.point_order SEPARATOR '|||') as key_points
            FROM {$prefix}problems p
            LEFT JOIN {$prefix}problem_conditions pc ON p.id = pc.problem_id
            LEFT JOIN {$prefix}analysis_insights ai ON p.id = ai.problem_id
            LEFT JOIN {$prefix}highlight_tags ht ON p.id = ht.problem_id
            LEFT JOIN {$prefix}solution_steps ss ON p.id = ss.problem_id
            LEFT JOIN {$prefix}key_points kp ON p.id = kp.problem_id
            WHERE p.id = :problem_id
            GROUP BY p.id
        ");
        $stmt->execute(['problem_id' => $problemId]);
        $problem = $stmt->fetch();
        
        if (!$problem) {
            sendErrorResponse('문제를 찾을 수 없습니다.', 404);
        }
        
        $processedProblem = processProblemData($problem);
        
        writeLog("GCP MariaDB에서 문제 조회 성공: ID {$problemId}", 'INFO');
        sendSuccessResponse($processedProblem);
        
    } catch (PDOException $e) {
        writeLog("문제 조회 실패: " . $e->getMessage(), 'ERROR');
        sendErrorResponse('문제를 조회할 수 없습니다: ' . $e->getMessage(), 500);
    }
}

/**
 * 새 문제 생성
 */
function createProblem($pdo, $prefix) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            sendErrorResponse('유효한 JSON 데이터가 필요합니다.', 400);
        }
        
        // 입력 검증
        $validation = validateInput($input, [
            'problem_number' => ['required' => true, 'type' => 'integer'],
            'title' => ['required' => true, 'type' => 'string', 'min_length' => 1],
            'category' => ['required' => false, 'type' => 'string'],
            'difficulty' => ['required' => false, 'type' => 'string']
        ]);
        
        if ($validation !== true) {
            sendErrorResponse('입력 검증 실패: ' . implode(', ', $validation), 400);
        }
        
        $pdo->beginTransaction();
        
        try {
            // 중복 문제 번호 확인
            $stmt = $pdo->prepare("
                SELECT id FROM {$prefix}problems 
                WHERE problem_set_id = :problem_set_id AND problem_number = :problem_number
            ");
            $stmt->execute([
                'problem_set_id' => $input['problem_set_id'] ?? 1,
                'problem_number' => $input['problem_number']
            ]);
            
            if ($stmt->fetch()) {
                $pdo->rollBack();
                sendErrorResponse('이미 존재하는 문제 번호입니다: ' . $input['problem_number'], 409);
            }
            
            // 문제 삽입
            $stmt = $pdo->prepare("
                INSERT INTO {$prefix}problems (
                    problem_set_id, problem_number, title, category, difficulty, 
                    estimated_time, description, question_text, key_strategy, author_notes
                ) VALUES (
                    :problem_set_id, :problem_number, :title, :category, :difficulty,
                    :estimated_time, :description, :question_text, :key_strategy, :author_notes
                )
            ");
            
            $stmt->execute([
                'problem_set_id' => $input['problem_set_id'] ?? 1,
                'problem_number' => $input['problem_number'],
                'title' => $input['title'],
                'category' => $input['category'] ?? '대수',
                'difficulty' => $input['difficulty'] ?? '3등급',
                'estimated_time' => $input['estimated_time'] ?? 20,
                'description' => $input['description'] ?? '',
                'question_text' => $input['question_text'] ?? '',
                'key_strategy' => $input['key_strategy'] ?? '',
                'author_notes' => $input['author_notes'] ?? ''
            ]);
            
            $problemId = $pdo->lastInsertId();
            
            // 추가 데이터 삽입 (조건, 인사이트 등)
            if (!empty($input['conditions'])) {
                insertProblemConditions($pdo, $problemId, $input['conditions'], $prefix);
            }
            
            if (!empty($input['analysis_insights'])) {
                insertAnalysisInsights($pdo, $problemId, $input['analysis_insights'], $prefix);
            }
            
            if (!empty($input['highlight_tags'])) {
                insertHighlightTags($pdo, $problemId, $input['highlight_tags'], $prefix);
            }
            
            if (!empty($input['solution_steps'])) {
                insertSolutionSteps($pdo, $problemId, $input['solution_steps'], $prefix);
            }
            
            if (!empty($input['key_points'])) {
                insertKeyPoints($pdo, $problemId, $input['key_points'], $prefix);
            }
            
            $pdo->commit();
            
            writeLog("GCP MariaDB에 새 문제 생성 성공: ID {$problemId}", 'INFO');
            sendSuccessResponse(['id' => $problemId], '문제가 성공적으로 생성되었습니다.');
            
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
        
    } catch (PDOException $e) {
        writeLog("문제 생성 실패: " . $e->getMessage(), 'ERROR');
        sendErrorResponse('문제를 생성할 수 없습니다: ' . $e->getMessage(), 500);
    }
}

/**
 * 헬스 체크
 */
function handleHealthCheck($pdo, $prefix) {
    try {
        $stmt = $pdo->query("SELECT VERSION() as version");
        $dbInfo = $stmt->fetch();
        
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM {$prefix}problems");
        $problemCount = $stmt->fetch();
        
        sendSuccessResponse([
            'status' => 'healthy',
            'database' => 'connected',
            'server' => 'GCP MariaDB',
            'version' => $dbInfo['version'],
            'problem_count' => $problemCount['count'],
            'timestamp' => date('Y-m-d H:i:s')
        ], 'GCP 서버 정상 동작 중');
        
    } catch (PDOException $e) {
        sendErrorResponse('헬스 체크 실패: ' . $e->getMessage(), 503);
    }
}

// ... 나머지 helper 함수들은 기존과 동일 ...
?> 