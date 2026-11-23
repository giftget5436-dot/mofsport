<?php
// all_random.php - หน้าสุ่มแบ่งกลุ่ม 4 สี สำนักงานปลัดกระทรวงการคลัง (แบบรวมทั้งหมด / Quick Spin)
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

$userData = null;
$seminarJsonPath = __DIR__ . '/seminar_users.json';
$officialsJsonPath = __DIR__ . '/officials_504.json';

if (file_exists($seminarJsonPath)) {
    $userData = json_decode(file_get_contents($seminarJsonPath), true);
} elseif (file_exists($officialsJsonPath)) {
    $userData = json_decode(file_get_contents($officialsJsonPath), true);
}

// โหลดผลการสุ่มล่าสุด
$latestSpin = null;
$spinPath = __DIR__ . '/latest_spin.json';
if (file_exists($spinPath)) {
    $latestSpin = json_decode(file_get_contents($spinPath), true);
}

// โหลดรูปภาพประจำตัวทั้งหมดที่มี
$userAvatars = [];
$avatarDir = __DIR__ . '/uploads/avatars/';
if (is_dir($avatarDir)) {
    foreach (scandir($avatarDir) as $f) {
        if (preg_match('/^avatar_([a-zA-Z0-9_-]+)\.([a-zA-Z0-9]+)$/', $f, $m)) {
            $userAvatars[$m[1]] = 'uploads/avatars/' . $f . '?v=' . filemtime($avatarDir . $f);
        }
    }
}

// Read template
$html = file_get_contents(__DIR__ . '/all_random.html');

// Inject pre-loaded data script and latest spin data
$injection = "<script>\n" .
    "  window.INITIAL_USER_DATA = " . json_encode($userData, JSON_UNESCAPED_UNICODE) . ";\n" .
    "  window.LATEST_SPIN_DATA = " . json_encode($latestSpin, JSON_UNESCAPED_UNICODE) . ";\n" .
    "  window.INITIAL_USER_AVATARS = " . json_encode($userAvatars, JSON_UNESCAPED_UNICODE) . ";\n" .
"</script>";
$html = str_replace('</head>', $injection . "\n</head>", $html);

echo $html;
