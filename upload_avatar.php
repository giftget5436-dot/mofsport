<?php
// upload_avatar.php - อัปโหลดและจัดการรูปภาพประจำตัวของบุคลากร
header("Content-Type: application/json; charset=UTF-8");
header("Cache-Control: no-cache, no-store, must-revalidate");

$uploadDir = __DIR__ . '/uploads/avatars/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$action = $_GET['action'] ?? ($_POST['action'] ?? 'upload');

// 1. ดึงรายการรูปทั้งหมดที่มีในโฟลเดอร์
if ($action === 'list') {
    $avatars = [];
    if (is_dir($uploadDir)) {
        $files = scandir($uploadDir);
        foreach ($files as $f) {
            if ($f === '.' || $f === '..') continue;
            // Format: avatar_{id}.{ext}
            if (preg_match('/^avatar_([a-zA-Z0-9_-]+)\.([a-zA-Z0-9]+)$/', $f, $m)) {
                $uid = $m[1];
                $avatars[$uid] = 'uploads/avatars/' . $f . '?v=' . filemtime($uploadDir . $f);
            }
        }
    }
    echo json_encode(['success' => true, 'avatars' => $avatars]);
    exit;
}

// 2. ลบรูป
if ($action === 'delete') {
    $userId = $_POST['userId'] ?? '';
    $userId = preg_replace('/[^a-zA-Z0-9_-]/', '', $userId);
    if (!empty($userId)) {
        $matches = glob($uploadDir . 'avatar_' . $userId . '.*');
        foreach ($matches as $m) {
            @unlink($m);
        }
        echo json_encode(['success' => true, 'message' => 'Deleted']);
        exit;
    }
    echo json_encode(['success' => false, 'message' => 'Invalid userId']);
    exit;
}

$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// 3. อัปโหลดรูป (รองรับทั้ง multipart/form-data และ Base64 JSON)
if ($requestMethod === 'POST') {
    $userId = $_POST['userId'] ?? '';
    
    // ตรวจสอบ JSON payload กรณีส่ง Base64
    if (empty($userId)) {
        $raw = file_get_contents('php://input');
        if (!empty($raw)) {
            $jsonData = json_decode($raw, true);
            if ($jsonData) {
                $userId = $jsonData['userId'] ?? '';
                $base64 = $jsonData['imageBase64'] ?? '';
                if (!empty($userId) && !empty($base64)) {
                    $userId = preg_replace('/[^a-zA-Z0-9_-]/', '', $userId);
                    // ตัด header data:image/xxx;base64,
                    if (preg_match('/^data:image\/(\w+);base64,/', $base64, $type)) {
                        $base64 = substr($base64, strpos($base64, ',') + 1);
                        $ext = strtolower($type[1]);
                        if ($ext === 'jpeg') $ext = 'jpg';
                    } else {
                        $ext = 'png';
                    }
                    $data = base64_decode($base64);
                    if ($data !== false) {
                        // ลบไฟล์เดิมถ้ามี
                        foreach (glob($uploadDir . 'avatar_' . $userId . '.*') as $old) {
                            @unlink($old);
                        }
                        $targetPath = $uploadDir . 'avatar_' . $userId . '.' . $ext;
                        file_put_contents($targetPath, $data);
                        $url = 'uploads/avatars/avatar_' . $userId . '.' . $ext . '?v=' . time();
                        echo json_encode(['success' => true, 'avatarUrl' => $url, 'userId' => $userId]);
                        exit;
                    }
                }
            }
        }
    }

    $userId = preg_replace('/[^a-zA-Z0-9_-]/', '', $userId);
    if (empty($userId)) {
        echo json_encode(['success' => false, 'message' => 'Missing userId']);
        exit;
    }

    if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error']);
        exit;
    }

    $file = $_FILES['avatar'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($ext, $allowed)) {
        $ext = 'jpg';
    }

    // ลบรูปเดิมของคนนี้
    foreach (glob($uploadDir . 'avatar_' . $userId . '.*') as $old) {
        @unlink($old);
    }

    $fileName = 'avatar_' . $userId . '.' . $ext;
    $target = $uploadDir . $fileName;

    if (move_uploaded_file($file['tmp_name'], $target)) {
        $url = 'uploads/avatars/' . $fileName . '?v=' . time();
        echo json_encode(['success' => true, 'avatarUrl' => $url, 'userId' => $userId]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save file']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request']);
