<?php
// save_state.php - API สำหรับบันทึกและโหลดผลการสุ่มระหว่าง stage_random.php และ index.php
header("Content-Type: application/json; charset=UTF-8");
header("Cache-Control: no-cache, no-store, must-revalidate");

$file = __DIR__ . '/latest_spin.json';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    if (!empty($input)) {
        $data = json_decode($input, true);
        if ($data !== null) {
            $data['updated_at'] = date('Y-m-d H:i:s');
            file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            echo json_encode(['success' => true, 'message' => 'Saved successfully']);
            exit;
        }
    }
    echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (file_exists($file)) {
        echo file_get_contents($file);
    } else {
        echo json_encode(['success' => false, 'teams' => null]);
    }
    exit;
}

