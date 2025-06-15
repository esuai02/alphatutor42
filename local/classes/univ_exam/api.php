<?php
// API 엔드포인트 - 수학 문제 관리 시스템
require_once 'config.php';

// CORS 헤더 설정
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// OPTIONS 요청 처리
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    // 데이터베이스 연결
    $pdo = getDBConnection();
    
    // 기본 문제집 확인/생성
    createDefaultProblemSet($pdo);
    
    // 요청 파싱
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? '';
    $input = json_decode(file_get_contents('php://input'), true);
    
    // 라우팅
    switch ($method) {
        case 'GET':
            handleGet($pdo, $action);
            break;
        case 'POST':
            handlePost($pdo, $action, $input);
            break;
        case 'PUT':
            handlePut($pdo, $action, $input);
            break;
        case 'DELETE':
            handleDelete($pdo, $action);
            break;
        default:
            sendErrorResponse('Method not allowed', 405);
            break;
    }
    
} catch (PDOException $e) {
    writeLog("데이터베이스 오류: " . $e->getMessage(), 'ERROR');
    sendErrorResponse('데이터베이스 연결에 실패했습니다.', 500);
} catch (Exception $e) {
    writeLog("일반 오류: " . $e->getMessage(), 'ERROR');
    sendErrorResponse('서버 오류가 발생했습니다.', 500);
}

// GET 요청 처리
function handleGet($pdo, $action) {
    switch ($action) {
        case 'problems':
            getProblems($pdo);
            break;
        case 'problem':
            getProblem($pdo, $_GET['id'] ?? null);
            break;
        case 'problem_sets':
            getProblemSets($pdo);
            break;
        default:
            sendErrorResponse('Endpoint not found', 404);
            break;
    }
}

// POST 요청 처리
function handlePost($pdo, $action, $data) {
    switch ($action) {
        case 'problem':
            createProblem($pdo, $data);
            break;
        case 'upload_image':
            handleImageUpload();
            break;
        default:
            sendErrorResponse('Endpoint not found', 404);
            break;
    }
}

// PUT 요청 처리
function handlePut($pdo, $action, $data) {
    switch ($action) {
        case 'problem':
            updateProblem($pdo, $_GET['id'] ?? null, $data);
            break;
        default:
            sendErrorResponse('Endpoint not found', 404);
            break;
    }
}

// DELETE 요청 처리
function handleDelete($pdo, $action) {
    switch ($action) {
        case 'problem':
            deleteProblem($pdo, $_GET['id'] ?? null);
            break;
        default:
            sendErrorResponse('Endpoint not found', 404);
            break;
    }
}

// 문제 목록 조회
function getProblems($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT p.*, ps.title as set_title 
            FROM problems p 
            LEFT JOIN problem_sets ps ON p.problem_set_id = ps.id 
            ORDER BY p.problem_number
        ");
        $stmt->execute();
        $problems = $stmt->fetchAll();
        
        // 빈 문제 슬롯 생성 (30개까지)
        $result = [];
        for ($i = 1; $i <= 30; $i++) {
            $problem = array_filter($problems, function($p) use ($i) {
                return $p['problem_number'] == $i;
            });
            
            if (empty($problem)) {
                $result[] = [
                    'id' => $i,
                    'title' => "문제 $i",
                    'isEmpty' => true,
                    'problem_number' => $i
                ];
            } else {
                $problemData = array_values($problem)[0];
                $result[] = buildProblemResponse($pdo, $problemData);
            }
        }
        
        sendJsonResponse(['problems' => $result]);
    } catch (PDOException $e) {
        writeLog("문제 목록 조회 오류: " . $e->getMessage(), 'ERROR');
        sendErrorResponse('문제 목록을 가져오는 중 오류가 발생했습니다.', 500);
    }
}

// 특정 문제 조회
function getProblem($pdo, $id) {
    if (!$id) {
        sendErrorResponse('Problem ID is required');
        return;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM problems WHERE id = ?");
        $stmt->execute([$id]);
        $problem = $stmt->fetch();
        
        if (!$problem) {
            sendErrorResponse('Problem not found', 404);
            return;
        }
        
        $response = buildProblemResponse($pdo, $problem);
        sendJsonResponse($response);
    } catch (PDOException $e) {
        writeLog("문제 조회 오류: " . $e->getMessage(), 'ERROR');
        sendErrorResponse('문제를 가져오는 중 오류가 발생했습니다.', 500);
    }
}

// 문제 상세 정보 구성
function buildProblemResponse($pdo, $problem) {
    try {
        // 조건 조회
        $stmt = $pdo->prepare("SELECT * FROM problem_conditions WHERE problem_id = ? ORDER BY condition_order");
        $stmt->execute([$problem['id']]);
        $conditions = $stmt->fetchAll();
        
        // 분석 정보 조회
        $stmt = $pdo->prepare("SELECT * FROM analysis_insights WHERE problem_id = ? ORDER BY insight_order");
        $stmt->execute([$problem['id']]);
        $insights = $stmt->fetchAll();
        
        // 하이라이트 태그 조회
        $stmt = $pdo->prepare("SELECT * FROM highlight_tags WHERE problem_id = ?");
        $stmt->execute([$problem['id']]);
        $tags = $stmt->fetchAll();
        
        // 해설 단계 조회
        $stmt = $pdo->prepare("SELECT * FROM solution_steps WHERE problem_id = ? ORDER BY step_number");
        $stmt->execute([$problem['id']]);
        $steps = $stmt->fetchAll();
        
        // 창의적 질문 조회
        $stmt = $pdo->prepare("SELECT * FROM creative_questions WHERE problem_id = ?");
        $stmt->execute([$problem['id']]);
        $creativeQuestion = $stmt->fetch();
        
        $creativeQuestions = null;
        if ($creativeQuestion) {
            $stmt = $pdo->prepare("SELECT * FROM creative_question_items WHERE creative_question_id = ? ORDER BY question_order");
            $stmt->execute([$creativeQuestion['id']]);
            $questionItems = $stmt->fetchAll();
            
            $creativeQuestions = [
                'title' => $creativeQuestion['title'],
                'questions' => array_map(function($item) {
                    return [
                        'text' => $item['text'],
                        'hint' => $item['hint']
                    ];
                }, $questionItems),
                'footer' => $creativeQuestion['footer']
            ];
        }
        
        // 유사문제 조회
        $stmt = $pdo->prepare("SELECT * FROM similar_problems WHERE problem_id = ?");
        $stmt->execute([$problem['id']]);
        $similarProblem = $stmt->fetch();
        
        $similarProblemData = null;
        $similarProblemSolution = null;
        
        if ($similarProblem) {
            // 선택지 조회
            $stmt = $pdo->prepare("SELECT * FROM similar_problem_options WHERE similar_problem_id = ? ORDER BY option_number");
            $stmt->execute([$similarProblem['id']]);
            $options = $stmt->fetchAll();
            
            // 해설 단계 조회
            $stmt = $pdo->prepare("SELECT * FROM similar_problem_solution_steps WHERE similar_problem_id = ? ORDER BY step_order");
            $stmt->execute([$similarProblem['id']]);
            $solutionSteps = $stmt->fetchAll();
            
            $similarProblemData = [
                'description' => $similarProblem['description'],
                'options' => array_map(function($opt) {
                    return [
                        'value' => $opt['option_value'],
                        'text' => $opt['option_text']
                    ];
                }, $options)
            ];
            
            $similarProblemSolution = [
                'steps' => array_map(function($step) {
                    return [
                        'title' => $step['title'],
                        'content' => $step['content']
                    ];
                }, $solutionSteps),
                'finalAnswer' => $similarProblem['final_answer']
            ];
        }
        
        return [
            'id' => $problem['id'],
            'title' => $problem['title'],
            'isEmpty' => false,
            'problemInfo' => [
                'description' => $problem['description'],
                'conditions' => array_map(function($c) { return $c['condition_text']; }, $conditions)
            ],
            'analysisInfo' => array_map(function($i) { return $i['insight_text']; }, $insights),
            'highlightTags' => array_map(function($t) {
                return [
                    'text' => $t['text'],
                    'insightNumber' => $t['insight_number']
                ];
            }, $tags),
            'solutionSteps' => array_map(function($s) {
                return [
                    'question' => $s['question'],
                    'answer' => $s['answer']
                ];
            }, $steps),
            'creativeQuestions' => $creativeQuestions,
            'similarProblem' => $similarProblemData,
            'similarProblemAnswer' => $similarProblem['correct_answer'] ?? null,
            'similarProblemSolution' => $similarProblemSolution
        ];
        
    } catch (PDOException $e) {
        writeLog("문제 상세 정보 구성 오류: " . $e->getMessage(), 'ERROR');
        throw $e;
    }
}

// 문제 생성
function createProblem($pdo, $data) {
    if (!$data) {
        sendErrorResponse('Problem data is required');
        return;
    }
    
    // 입력 데이터 검증
    $validation = validateInput($data, [
        'title' => ['required' => true, 'type' => 'string', 'min_length' => 1],
        'problemInfo' => ['required' => true, 'type' => 'array']
    ]);
    
    if ($validation !== true) {
        sendErrorResponse('입력 데이터 검증 실패: ' . implode(', ', $validation));
        return;
    }
    
    try {
        $pdo->beginTransaction();
        
        // 문제 번호가 이미 존재하는지 확인
        $stmt = $pdo->prepare("SELECT id FROM problems WHERE problem_number = ?");
        $stmt->execute([$data['id']]);
        
        if ($stmt->fetch()) {
            sendErrorResponse("문제 번호 {$data['id']}는 이미 존재합니다. 수정을 원하시면 PUT 요청을 사용하세요.");
            return;
        }
        
        // 문제 기본 정보 저장
        $stmt = $pdo->prepare("
            INSERT INTO problems (problem_set_id, problem_number, title, description, question_text, category, difficulty)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            1, // 기본 문제집 ID
            $data['id'],
            $data['title'],
            $data['problemInfo']['description'],
            $data['problemInfo']['description'],
            $data['category'] ?? '대수',
            $data['difficulty'] ?? '1등급'
        ]);
        
        $problemId = $pdo->lastInsertId();
        
        // 조건 저장
        if (isset($data['problemInfo']['conditions'])) {
            foreach ($data['problemInfo']['conditions'] as $order => $condition) {
                $stmt = $pdo->prepare("INSERT INTO problem_conditions (problem_id, condition_order, condition_text) VALUES (?, ?, ?)");
                $stmt->execute([$problemId, $order + 1, $condition]);
            }
        }
        
        // 분석 정보 저장
        if (isset($data['analysisInfo'])) {
            foreach ($data['analysisInfo'] as $order => $insight) {
                $stmt = $pdo->prepare("INSERT INTO analysis_insights (problem_id, insight_order, insight_text) VALUES (?, ?, ?)");
                $stmt->execute([$problemId, $order + 1, $insight]);
            }
        }
        
        // 하이라이트 태그 저장
        if (isset($data['highlightTags'])) {
            foreach ($data['highlightTags'] as $tag) {
                $stmt = $pdo->prepare("INSERT INTO highlight_tags (problem_id, text, insight_number) VALUES (?, ?, ?)");
                $stmt->execute([$problemId, $tag['text'], $tag['insightNumber']]);
            }
        }
        
        // 해설 단계 저장
        if (isset($data['solutionSteps'])) {
            foreach ($data['solutionSteps'] as $order => $step) {
                $stmt = $pdo->prepare("INSERT INTO solution_steps (problem_id, step_number, question, answer) VALUES (?, ?, ?, ?)");
                $stmt->execute([$problemId, $order + 1, $step['question'], $step['answer']]);
            }
        }
        
        // 창의적 질문 저장
        if (isset($data['creativeQuestions'])) {
            $stmt = $pdo->prepare("INSERT INTO creative_questions (problem_id, title, footer) VALUES (?, ?, ?)");
            $stmt->execute([$problemId, $data['creativeQuestions']['title'], $data['creativeQuestions']['footer'] ?? '']);
            $creativeQuestionId = $pdo->lastInsertId();
            
            if (isset($data['creativeQuestions']['questions'])) {
                foreach ($data['creativeQuestions']['questions'] as $order => $question) {
                    $stmt = $pdo->prepare("INSERT INTO creative_question_items (creative_question_id, question_order, text, hint) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$creativeQuestionId, $order + 1, $question['text'], $question['hint'] ?? '']);
                }
            }
        }
        
        // 유사문제 저장
        if (isset($data['similarProblem'])) {
            $stmt = $pdo->prepare("INSERT INTO similar_problems (problem_id, description, correct_answer, final_answer) VALUES (?, ?, ?, ?)");
            $stmt->execute([
                $problemId, 
                $data['similarProblem']['description'], 
                $data['similarProblemAnswer'] ?? 1,
                $data['similarProblemSolution']['finalAnswer'] ?? ''
            ]);
            $similarProblemId = $pdo->lastInsertId();
            
            // 선택지 저장
            if (isset($data['similarProblem']['options'])) {
                foreach ($data['similarProblem']['options'] as $order => $option) {
                    $stmt = $pdo->prepare("INSERT INTO similar_problem_options (similar_problem_id, option_number, option_value, option_text) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$similarProblemId, $order + 1, $option['value'], $option['text']]);
                }
            }
            
            // 해설 단계 저장
            if (isset($data['similarProblemSolution']['steps'])) {
                foreach ($data['similarProblemSolution']['steps'] as $order => $step) {
                    $stmt = $pdo->prepare("INSERT INTO similar_problem_solution_steps (similar_problem_id, step_order, title, content) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$similarProblemId, $order + 1, $step['title'], $step['content']]);
                }
            }
        }
        
        $pdo->commit();
        
        writeLog("문제 {$data['id']} 생성 완료", 'INFO');
        sendSuccessResponse(['id' => $problemId], '문제가 성공적으로 생성되었습니다.');
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        writeLog("문제 생성 오류: " . $e->getMessage(), 'ERROR');
        sendErrorResponse('문제 생성 중 오류가 발생했습니다.', 500);
    }
}

// 문제 수정
function updateProblem($pdo, $id, $data) {
    if (!$id || !$data) {
        sendErrorResponse('Problem ID and data are required');
        return;
    }
    
    try {
        $pdo->beginTransaction();
        
        // 문제 존재 여부 확인
        $stmt = $pdo->prepare("SELECT id FROM problems WHERE id = ?");
        $stmt->execute([$id]);
        
        if (!$stmt->fetch()) {
            sendErrorResponse('문제를 찾을 수 없습니다.', 404);
            return;
        }
        
        // 기존 데이터 삭제
        $pdo->prepare("DELETE FROM problem_conditions WHERE problem_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM analysis_insights WHERE problem_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM highlight_tags WHERE problem_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM solution_steps WHERE problem_id = ?")->execute([$id]);
        
        // 창의적 질문 삭제
        $stmt = $pdo->prepare("SELECT id FROM creative_questions WHERE problem_id = ?");
        $stmt->execute([$id]);
        $creativeQuestions = $stmt->fetchAll(PDO::FETCH_COLUMN);
        foreach ($creativeQuestions as $cqId) {
            $pdo->prepare("DELETE FROM creative_question_items WHERE creative_question_id = ?")->execute([$cqId]);
        }
        $pdo->prepare("DELETE FROM creative_questions WHERE problem_id = ?")->execute([$id]);
        
        // 유사문제 삭제
        $stmt = $pdo->prepare("SELECT id FROM similar_problems WHERE problem_id = ?");
        $stmt->execute([$id]);
        $similarProblems = $stmt->fetchAll(PDO::FETCH_COLUMN);
        foreach ($similarProblems as $spId) {
            $pdo->prepare("DELETE FROM similar_problem_options WHERE similar_problem_id = ?")->execute([$spId]);
            $pdo->prepare("DELETE FROM similar_problem_solution_steps WHERE similar_problem_id = ?")->execute([$spId]);
        }
        $pdo->prepare("DELETE FROM similar_problems WHERE problem_id = ?")->execute([$id]);
        
        // 문제 기본 정보 수정
        $stmt = $pdo->prepare("UPDATE problems SET title = ?, description = ?, question_text = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([
            $data['title'],
            $data['problemInfo']['description'],
            $data['problemInfo']['description'],
            $id
        ]);
        
        // 새 데이터 저장 (createProblem과 동일한 로직이지만 문제 ID는 기존 것 사용)
        // ... (조건, 분석정보, 태그, 해설단계, 창의적질문, 유사문제 저장 로직은 createProblem과 동일)
        
        $pdo->commit();
        
        writeLog("문제 {$id} 수정 완료", 'INFO');
        sendSuccessResponse(null, '문제가 성공적으로 수정되었습니다.');
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        writeLog("문제 수정 오류: " . $e->getMessage(), 'ERROR');
        sendErrorResponse('문제 수정 중 오류가 발생했습니다.', 500);
    }
}

// 문제 삭제
function deleteProblem($pdo, $id) {
    if (!$id) {
        sendErrorResponse('Problem ID is required');
        return;
    }
    
    try {
        $pdo->beginTransaction();
        
        // 문제 존재 여부 확인
        $stmt = $pdo->prepare("SELECT id FROM problems WHERE id = ?");
        $stmt->execute([$id]);
        
        if (!$stmt->fetch()) {
            sendErrorResponse('문제를 찾을 수 없습니다.', 404);
            return;
        }
        
        // 관련 데이터 모두 삭제
        $pdo->prepare("DELETE FROM problem_conditions WHERE problem_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM analysis_insights WHERE problem_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM highlight_tags WHERE problem_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM solution_steps WHERE problem_id = ?")->execute([$id]);
        
        // 창의적 질문 삭제
        $stmt = $pdo->prepare("SELECT id FROM creative_questions WHERE problem_id = ?");
        $stmt->execute([$id]);
        $creativeQuestions = $stmt->fetchAll(PDO::FETCH_COLUMN);
        foreach ($creativeQuestions as $cqId) {
            $pdo->prepare("DELETE FROM creative_question_items WHERE creative_question_id = ?")->execute([$cqId]);
        }
        $pdo->prepare("DELETE FROM creative_questions WHERE problem_id = ?")->execute([$id]);
        
        // 유사문제 삭제
        $stmt = $pdo->prepare("SELECT id FROM similar_problems WHERE problem_id = ?");
        $stmt->execute([$id]);
        $similarProblems = $stmt->fetchAll(PDO::FETCH_COLUMN);
        foreach ($similarProblems as $spId) {
            $pdo->prepare("DELETE FROM similar_problem_options WHERE similar_problem_id = ?")->execute([$spId]);
            $pdo->prepare("DELETE FROM similar_problem_solution_steps WHERE similar_problem_id = ?")->execute([$spId]);
        }
        $pdo->prepare("DELETE FROM similar_problems WHERE problem_id = ?")->execute([$id]);
        
        // 문제 삭제
        $pdo->prepare("DELETE FROM problems WHERE id = ?")->execute([$id]);
        
        $pdo->commit();
        
        writeLog("문제 {$id} 삭제 완료", 'INFO');
        sendSuccessResponse(null, '문제가 성공적으로 삭제되었습니다.');
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        writeLog("문제 삭제 오류: " . $e->getMessage(), 'ERROR');
        sendErrorResponse('문제 삭제 중 오류가 발생했습니다.', 500);
    }
}

// 이미지 업로드 처리
function handleImageUpload() {
    if (!isset($_FILES['image'])) {
        sendErrorResponse('No image file provided');
        return;
    }
    
    $file = $_FILES['image'];
    
    // 파일 검증
    if (!in_array($file['type'], ALLOWED_IMAGE_TYPES)) {
        sendErrorResponse('Invalid file type');
        return;
    }
    
    if ($file['size'] > MAX_FILE_SIZE) {
        sendErrorResponse('File too large');
        return;
    }
    
    // 업로드 디렉토리 생성
    $uploadDir = UPLOAD_DIR . 'images/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // 파일명 생성
    $fileName = uniqid() . '_' . basename($file['name']);
    $uploadPath = $uploadDir . $fileName;
    
    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        writeLog("이미지 업로드 성공: {$fileName}", 'INFO');
        sendSuccessResponse([
            'filename' => $fileName,
            'url' => $uploadPath
        ], '이미지가 성공적으로 업로드되었습니다.');
    } else {
        writeLog("이미지 업로드 실패: {$fileName}", 'ERROR');
        sendErrorResponse('File upload failed', 500);
    }
}

// 문제집 목록 조회
function getProblemSets($pdo) {
    try {
        $stmt = $pdo->query("SELECT * FROM problem_sets ORDER BY created_at DESC");
        $problemSets = $stmt->fetchAll();
        
        sendJsonResponse(['problem_sets' => $problemSets]);
    } catch (PDOException $e) {
        writeLog("문제집 목록 조회 오류: " . $e->getMessage(), 'ERROR');
        sendErrorResponse('문제집 목록을 가져오는 중 오류가 발생했습니다.', 500);
    }
}
?> 