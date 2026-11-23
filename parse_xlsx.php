<?php
/**
 * parse_xlsx.php - ตัวแปลงไฟล์ Excel XLSX สำหรับเจ้าหน้าที่สำนักงานปลัดกระทรวงการคลัง
 */
function parseOfficialXlsx($filePath) {
    if (!file_exists($filePath)) return null;
    if (!class_exists('ZipArchive')) return null;

    $zip = new ZipArchive();
    if ($zip->open($filePath) !== TRUE) return null;

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
        return null;
    }

    $sXml = simplexml_load_string($sheetXml);

    $currentDept = 'สำนักงานปลัดกระทรวงการคลัง';
    $currentGroup = '';
    $currentUnit = '';
    $currentMainDivision = '';

    // แมปสำนัก/กองหลัก จากชื่อย่อ
    $mainDivMap = [
        'สบก' => 'สำนักบริหารกลาง',
        'ศทส' => 'ศูนย์เทคโนโลยีสารสนเทศและการสื่อสาร',
        'สตป' => 'สำนักตรวจสอบและประเมินผล',
        'สนย' => 'สำนักนโยบายและยุทธศาสตร์',
        'สบค' => 'สำนักบริหารทรัพยากรบุคคล',
        'ศปท' => 'ศูนย์ปฏิบัติการต่อต้านการทุจริต',
        'กกม' => 'กลุ่มกฎหมาย',
        'กตน' => 'กลุ่มตรวจสอบภายใน',
        'กพร' => 'กลุ่มพัฒนาระบบบริหาร',
        'สศค' => 'สำนักงานเศรษฐกิจการคลัง',
    ];

    $subdivToMainMap = [
        'ส่วนพัฒนาบุคคล' => 'สำนักบริหารทรัพยากรบุคคล',
        'ส่วนแผนและประเมินผล' => 'สำนักตรวจสอบและประเมินผล',
        'ส่วนพัฒนาระบบตรวจสอบ' => 'สำนักตรวจสอบและประเมินผล',
        'ส่วนตรวจราชการ' => 'สำนักตรวจสอบและประเมินผล',
        'ส่วนบริหารการคลัง' => 'สำนักบริหารกลาง',
        'ส่วนอำนวยการ' => 'สำนักบริหารกลาง',
        'ส่วนบริหารทั่วไป' => 'สำนักบริหารกลาง',
        'ฝ่ายการพัสดุ' => 'สำนักบริหารกลาง',
        'ฝ่ายสารบรรณ' => 'สำนักบริหารกลาง',
    ];

    $users = [];

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

        $colA = $rowCells['A'] ?? '';
        $colB = $rowCells['B'] ?? '';
        $colC = $rowCells['C'] ?? '';
        $colD = $rowCells['D'] ?? '';
        $colE = $rowCells['E'] ?? '';

        // 1. หัวข้อ: ชื่อหน่วยงาน
        if (strpos($colA, 'ชื่อหน่วยงาน') !== false) {
            $currentDept = trim(str_replace(['ชื่อหน่วยงาน :', 'ชื่อหน่วยงาน:'], '', $colA));
            $currentDept = ltrim($currentDept, " \t\n\r\0\x0B:");
            continue;
        }

        // 2. หัวข้อ: กลุ่มบุคลากร
        if (strpos($colA, 'กลุ่มบุคลากร') !== false) {
            $g = trim(str_replace(['กลุ่มบุคลากร :', 'กลุ่มบุคลากร:'], '', $colA));
            $currentGroup = ltrim($g, " \t\n\r\0\x0B:");
            $currentUnit = '';
            $currentMainDivision = $currentGroup;
            continue;
        }

        // 3. หัวข้อ: หน่วยงาน
        if (strpos($colA, 'หน่วยงาน') !== false && strpos($colA, 'ชื่อหน่วยงาน') === false) {
            $u = trim(str_replace(['หน่วยงาน :', 'หน่วยงาน:'], '', $colA));
            $currentUnit = ltrim($u, " \t\n\r\0\x0B:");
            $currentGroup = '';
            $currentMainDivision = '';
            $currentSection = '';

            // 3.1 ตรวจหารูปแบบ สบก.ส่วนอำนวยการ
            if (preg_match('/^([ก-๙]{2,4})\s*\.\s*(.+)$/u', $currentUnit, $m)) {
                $abbr = $m[1];
                $subPart = trim($m[2]);
                if (isset($mainDivMap[$abbr])) {
                    $currentMainDivision = $mainDivMap[$abbr];
                    $currentSection = $subPart;
                } else {
                    $currentMainDivision = $currentUnit;
                    $currentSection = 'ส่วนงานทั่วไป';
                }
            }
            // 3.2 ตรวจหาจากวงเล็บ เช่น (สตป.) หรือ (สบค.)
            elseif (preg_match('/\(([ก-๙.]+)\)/u', $currentUnit, $m)) {
                $abbr = rtrim($m[1], '.');
                if (isset($mainDivMap[$abbr])) {
                    $currentMainDivision = $mainDivMap[$abbr];
                }
                $currentSection = $currentUnit;
            }
            // 3.3 ตรวจหาชื่อย่อเดี่ยวๆ เช่น สตป. กกม. สนย. กตน. กพร.
            elseif (isset($mainDivMap[rtrim($currentUnit, '.')])) {
                $currentMainDivision = $mainDivMap[rtrim($currentUnit, '.')];
                $currentSection = 'ฝ่าย/งานบริหารทั่วไป';
            }
            // 3.4 ตรวจหาชื่อเต็ม เช่น สำนัก..., ศูนย์..., กลุ่ม..., กรม...
            elseif (strpos($currentUnit, 'สำนัก') === 0 || 
                    strpos($currentUnit, 'ศูนย์') === 0 || 
                    strpos($currentUnit, 'กลุ่ม') === 0 ||
                    strpos($currentUnit, 'กรม') === 0) {
                $currentMainDivision = $currentUnit;
                $currentSection = 'ฝ่าย/งานบริหารทั่วไป';
            } else {
                $currentMainDivision = $currentUnit;
                $currentSection = $currentUnit;
            }
            continue;
        }

        // 4. แถวข้อมูลเจ้าหน้าที่ (เมื่อ Col A เป็นตัวเลขลำดับ และ Col B มีชื่อ)
        if (is_numeric($colA) && !empty($colB) && $colB !== 'ชื่อ-นามสกุล') {
            $seq = (int)$colA;
            $fullName = $colB;
            $pos = $colC;
            $email = $colD;
            $phone = $colE;

            // ตรวจหาคำนำหน้าและเพศ
            $gender = 'ไม่ระบุ';
            $prename = '';
            $perName = '';
            $perSurname = '';

            if (preg_match('/^(นาย|ว่าที่ร้อยตรี|ว่าที่ร้อยโท|ร้อยตรี|ร้อยโท|ร้อยตำรวจโท|พลฯ)(.*)$/u', $fullName, $pm)) {
                $gender = 'ชาย';
                $prename = $pm[1];
                $remain = trim($pm[2]);
            } elseif (preg_match('/^(นางสาว|นาง|ว่าที่ร้อยตำรวจโทหญิง|ว่าที่ร้อยตรีหญิง)(.*)$/u', $fullName, $pm)) {
                $gender = 'หญิง';
                $prename = $pm[1];
                $remain = trim($pm[2]);
            } else {
                $remain = $fullName;
            }

            $nameParts = preg_split('/\s+/u', $remain, 2);
            $perName = $nameParts[0] ?? '';
            $perSurname = $nameParts[1] ?? '';

            $div = !empty($currentMainDivision) ? $currentMainDivision : (!empty($currentUnit) ? $currentUnit : $currentGroup);
            $sec = !empty($currentSection) ? $currentSection : (!empty($currentUnit) ? $currentUnit : 'ทั่วไป');

            // วิเคราะห์ระดับตำแหน่ง
            $level = 'ทั่วไป';
            if (preg_match('/(ทรงคุณวุฒิ|เชี่ยวชาญ|ชำนาญการพิเศษ|ชำนาญการ|ชำนาญงาน|ปฏิบัติการ|ปฏิบัติงาน)/u', $pos, $lm)) {
                $level = $lm[1];
            } elseif ($div === 'ผู้บริหารระดับสูง' || 
                      strpos($pos, 'ปลัด') !== false || 
                      strpos($pos, 'ผู้ตรวจ') !== false || 
                      strpos($pos, 'ที่ปรึกษา') !== false || 
                      strpos($pos, 'อธิบดี') !== false || 
                      strpos($pos, 'ผู้อำนวยการ') !== false) {
                $level = 'ผู้บริหารระดับสูง';
            } elseif (!empty($currentGroup)) {
                $level = $currentGroup;
            }

            $users[] = [
                'seq' => $seq,
                'name' => $fullName,
                'prename_th' => $prename,
                'per_name' => $perName,
                'per_surname' => $perSurname,
                'gender' => $gender,
                'POSITION' => $pos,
                'position' => $pos,
                'department' => $currentDept,
                'division' => $div,
                'section' => $sec,
                'subdiv1' => $sec,
                'subdiv2' => '',
                'group' => $currentGroup,
                'unit' => $currentUnit,
                'levelname_th' => $level,
                'email' => $email,
                'phone' => $phone
            ];
        }
    }

    $zip->close();
    return $users;
}

