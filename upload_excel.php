<?php
// upload_excel.php - รับอัปโหลดไฟล์ Excel (.xlsx, .xls) ทั้งแบบ Multipart Form และ JSON Base64/Parsed
header("Content-Type: application/json; charset=UTF-8");
header("Cache-Control: no-cache, no-store, must-revalidate");

require_once __DIR__ . '/parse_xlsx.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$seminarJsonPath = __DIR__ . '/seminar_users.json';
$officialsJsonPath = __DIR__ . '/officials_504.json';
$spinPath = __DIR__ . '/latest_spin.json';

// 1. กรณีส่งมาเป็น Multipart File Upload ($_FILES['file'])
if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
    $tmpName = $_FILES['file']['tmp_name'];
    $origName = $_FILES['file']['name'];
    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

    if (!in_array($ext, ['xlsx', 'xls'])) {
        echo json_encode(['success' => false, 'message' => 'รองรับเฉพาะไฟล์ Excel (.xlsx, .xls) เท่านั้น']);
        exit;
    }

    $destPath = __DIR__ . '/uploads/' . time() . '_' . $origName;
    if (!is_dir(__DIR__ . '/uploads')) {
        @mkdir(__DIR__ . '/uploads', 0777, true);
    }
    move_uploaded_file($tmpName, $destPath);

    // ทำการ Parse Excel
    $users = parseAnyExcelFile($destPath);

    if (empty($users)) {
        echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูลบุคลากรในไฟล์ หรือโครงสร้างไฟล์ไม่ถูกต้อง']);
        exit;
    }

    file_put_contents($seminarJsonPath, json_encode($users, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    file_put_contents($officialsJsonPath, json_encode($users, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

    // ทำการสุ่มเริ่มต้นและเกลี่ยอายุทันที (Initial Auto-Balance Spin) เพื่อให้หน้าสรุป index.php มีข้อมูลแสดงผลทันที!
    autoAssignAndSaveSpin($users, $spinPath, $origName);

    echo json_encode([
        'success' => true,
        'count' => count($users),
        'filename' => $origName,
        'message' => 'อัปโหลดและประมวลผลสำเร็จ ' . count($users) . ' คน'
    ]);
    exit;
}

// 2. กรณีส่งมาเป็น JSON payload (Array ของ users ที่อ่านผ่าน JavaScript XLSX)
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if ($data && !empty($data['users']) && is_array($data['users'])) {
    $users = $data['users'];

    file_put_contents($seminarJsonPath, json_encode($users, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    file_put_contents($officialsJsonPath, json_encode($users, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

    // ทำการสุ่มเริ่มต้นและเกลี่ยอายุทันที (Initial Auto-Balance Spin) เพื่อให้หน้าสรุป index.php มีข้อมูลแสดงผลทันที!
    $fName = $data['filename'] ?? 'uploaded.xlsx';
    autoAssignAndSaveSpin($users, $spinPath, $fName);

    echo json_encode([
        'success' => true,
        'count' => count($users),
        'filename' => $fName,
        'message' => 'บันทึกข้อมูลบุคลากร ' . count($users) . ' คน เรียบร้อยแล้ว'
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูลไฟล์ที่ส่งมา']);
exit;

/**
 * ฟังก์ชันอ่านไฟล์ Excel XLSX ใดๆ โดยใช้ ZipArchive + SimpleXML
 */
function parseAnyExcelFile($filePath) {
    if (!class_exists('ZipArchive')) return [];

    $zip = new ZipArchive();
    if ($zip->open($filePath) !== TRUE) return [];

    $sharedStrings = [];
    $strXml = $zip->getFromName('xl/sharedStrings.xml');
    if ($strXml) {
        $xml = simplexml_load_string($strXml);
        foreach ($xml->si as $si) {
            if (isset($si->t)) $sharedStrings[] = (string)$si->t;
            elseif (isset($si->r)) {
                $text = '';
                foreach ($si->r as $r) $text .= (string)$r->t;
                $sharedStrings[] = $text;
            } else $sharedStrings[] = '';
        }
    }

    $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
    if (!$sheetXml) {
        $zip->close();
        return [];
    }

    $sXml = simplexml_load_string($sheetXml);
    $rows = [];

    foreach ($sXml->sheetData->row as $row) {
        $rowCells = [];
        foreach ($row->c as $c) {
            $ref = (string)$c['r'];
            $col = preg_replace('/[0-9]/', '', $ref);
            $t = (string)$c['t'];
            $val = (string)$c->v;
            if ($t === 's') $val = $sharedStrings[(int)$val] ?? $val;
            $rowCells[$col] = trim($val);
        }
        $rows[] = $rowCells;
    }
    $zip->close();

    $users = [];
    $currentDivision = 'ผู้บริหารระดับสูง';
    $currentSection = 'งานบริหารทั่วไป';
    $seq = 1;

    foreach ($rows as $r) {
        $colA = $r['A'] ?? '';
        $colB = $r['B'] ?? '';
        $colC = $r['C'] ?? '';
        $colD = $r['D'] ?? '';
        $colE = $r['E'] ?? '';

        $line = trim("$colA $colB $colC");

        // ตรวจสอบแถวหัวข้อหน่วยงาน
        if (preg_match('/(สำนัก|ศูนย์|กลุ่ม|กอง|ส่วนพัฒนาระบบ|ส่วนบริหาร|ส่วนอำนวยการ)/u', $line) && empty($colD) && empty($colE)) {
            if (preg_match('/(สำนัก|ศูนย์|กลุ่ม|กอง)/u', $line)) {
                $currentDivision = $line;
                $currentSection = 'งานบริหารทั่วไป/บุคลากรในสังกัด';
            } elseif (strpos($line, 'ส่วน') !== false) {
                $currentSection = $line;
            }
            continue;
        }

        $name = '';
        $position = '';
        $age = 0;

        // รูปแบบ 1: Col B = ชื่อ, Col C = สกุล, Col D = ตำแหน่ง, Col E = อายุ
        if (!empty($colB) && !empty($colC)) {
            $name = trim("$colB $colC");
            $position = $colD;
            $age = intval($colE);
        } elseif (!empty($colA) && (strpos($colA, 'นาย') === 0 || strpos($colA, 'นาง') === 0)) {
            // รูปแบบ 2: Col A มีชื่อเต็ม
            $name = $colA;
            $position = $colB;
            $age = intval($colC);
        }

        if (empty($name) || $name === 'ชื่อ สกุล' || mb_strlen($name, 'UTF-8') < 3) continue;

        // แยกเพศ: ขึ้นต้นด้วย นาย เป็นชาย, นาง หรือ นางสาว เป็นหญิง
        $gender = 'ไม่ระบุ';
        if (preg_match('/^(นาย|ว่าที่ร้อยตรี|ว่าที่ร้อยโท|ร้อยตรี|ร้อยโท|ร้อยตำรวจโท|พลฯ)/u', $name) || mb_strpos($name, 'นาย') === 0) {
            $gender = 'ชาย';
        } elseif (preg_match('/^(นางสาว|นาง|ว่าที่ร้อยตรีหญิง|ว่าที่ร้อยโทหญิง|ว่าที่ร้อยตำรวจโทหญิง)/u', $name) || mb_strpos($name, 'นางสาว') === 0 || mb_strpos($name, 'นาง') === 0) {
            $gender = 'หญิง';
        } elseif (mb_strpos($name, 'นาย') !== false) {
            $gender = 'ชาย';
        } elseif (mb_strpos($name, 'นางสาว') !== false || mb_strpos($name, 'นาง') !== false) {
            $gender = 'หญิง';
        }

        $div = $currentDivision;
        $sec = $currentSection;
        if (preg_match('/(รองปลัด|ผู้ตรวจ|ที่ปรึกษา)/u', $position)) {
            $div = 'ผู้บริหารระดับสูง';
            $sec = (strpos($position, 'รองปลัด') !== false) ? 'รองปลัดกระทรวงการคลัง' : ((strpos($position, 'ผู้ตรวจ') !== false) ? 'ผู้ตรวจราชการกระทรวงการคลัง' : 'ที่ปรึกษา');
        }

        $users[] = [
            'seq' => $seq++,
            'name' => $name,
            'position' => $position,
            'POSITION' => $position,
            'gender' => $gender,
            'age' => $age ?: 38,
            'department' => 'สำนักงานปลัดกระทรวงการคลัง',
            'division' => $div,
            'section' => $sec,
            'subdiv1' => $sec,
            'subdiv2' => '',
            'assignedTeam' => -1,
            'assignedTag' => ''
        ];
    }

    return $users;
}

/**
 * สุ่มแบ่งกลุ่มเริ่มต้นพร้อมเกลี่ยเพศและอายุทันทีเมื่อมีการอัปโหลดไฟล์ใหม่
 */
function autoAssignAndSaveSpin($users, $spinPath, $filename) {
    $teams = [[], [], [], []];
    
    // แยกตามกลุ่มเพศ
    $males = [];
    $females = [];
    $others = [];
    foreach ($users as $u) {
        if (($u['gender'] ?? '') === 'ชาย') {
            $males[] = $u;
        } elseif (($u['gender'] ?? '') === 'หญิง') {
            $females[] = $u;
        } else {
            $others[] = $u;
        }
    }

    shuffle($males);
    usort($males, function($a, $b) {
        return ($b['age'] ?? 38) - ($a['age'] ?? 38);
    });

    shuffle($females);
    usort($females, function($a, $b) {
        return ($b['age'] ?? 38) - ($a['age'] ?? 38);
    });

    $distributeGroup = function(&$group) use (&$teams) {
        foreach ($group as $u) {
            $uAge = $u['age'] ?? 38;
            $uGender = $u['gender'] ?? '';

            $teamStats = [];
            for ($t = 0; $t < 4; $t++) {
                $m = $teams[$t];
                $count = count($m);
                $genderCount = 0;
                $totalAge = 0;
                foreach ($m as $item) {
                    if (($item['gender'] ?? '') === $uGender) $genderCount++;
                    $totalAge += ($item['age'] ?? 38);
                }
                $avgAge = $count > 0 ? ($totalAge / $count) : 38;
                $teamStats[] = [
                    'teamId' => $t,
                    'count' => $count,
                    'genderCount' => $genderCount,
                    'avgAge' => $avgAge
                ];
            }

            // 1. เกลี่ยเพศเดียวกันให้เท่ากันก่อน
            $minGender = min(array_column($teamStats, 'genderCount'));
            $cand = [];
            foreach ($teamStats as $s) {
                if ($s['genderCount'] === $minGender) $cand[] = $s;
            }

            // 2. ถ้าเพศเท่ากัน ดูจำนวนคนรวม
            if (count($cand) > 1) {
                $minCount = min(array_column($cand, 'count'));
                $cand2 = [];
                foreach ($cand as $s) {
                    if ($s['count'] === $minCount) $cand2[] = $s;
                }
                $cand = $cand2;
            }

            // 3. เกลี่ยอายุ
            if (count($cand) > 1) {
                usort($cand, function($a, $b) use ($uAge) {
                    if ($uAge >= 38) {
                        return $a['avgAge'] <=> $b['avgAge'];
                    } else {
                        return $b['avgAge'] <=> $a['avgAge'];
                    }
                });
            }

            $chosenTeamId = $cand[0]['teamId'];
            $u['assignedTeam'] = $chosenTeamId;
            $u['teamIndex'] = $chosenTeamId;
            $teams[$chosenTeamId][] = $u;
        }
    };

    $distributeGroup($males);
    $distributeGroup($females);
    $distributeGroup($others);

    $payload = [
        'timestamp' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
        'source' => 'uploaded_excel (' . $filename . ')',
        'teams' => $teams
    ];

    file_put_contents($spinPath, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}
