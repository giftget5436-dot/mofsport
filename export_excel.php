<?php
// export_excel.php - Backend script for exporting Excel matching exact structure and styling of 'ตัวอย่างรายชื่อสัมมนา ซ้อมสุ่มสี.xlsx'
header("Cache-Control: no-cache, no-store, must-revalidate");

// รับข้อมูลจาก POST หรือดึงจาก latest_spin.json
$input = file_get_contents('php://input');
$data = null;
if (!empty($input)) {
    $data = json_decode($input, true);
}

if (!$data || empty($data['teams'])) {
    $spinFile = __DIR__ . '/latest_spin.json';
    if (file_exists($spinFile)) {
        $data = json_decode(file_get_contents($spinFile), true);
    }
}

if (!$data || empty($data['teams'])) {
    die("No spin data available to export.");
}

$teamNames = ['สีแดง', 'สีชมพู', 'สีเขียว', 'สีเหลือง'];
$teamColors = ['FFE51C23', 'FFE91E63', 'FF259B24', 'FFFFEB3B']; // AARRGGBB: #e51c23, #e91e63, #259b24, #ffeb3b
$teams = $data['teams'];

// โหลดโครงสร้างสำนัก/กอง/ส่วนงานย่อยจากแม่แบบ template_structure.json หรือ excel_template_structure.json
$templateFile = __DIR__ . '/excel_template_structure.json';
$templateItems = [];
if (file_exists($templateFile)) {
    $templateItems = json_decode(file_get_contents($templateFile), true);
}

// สร้าง Map ข้อมูลทีมของผู้ใช้ โดยรองรับทั้ง Composite Key (ลำดับที่ + ชื่อ) เพื่อความแม่นยำ 100%
$userTeamMap = [];
foreach ($teams as $tIdx => $tMembers) {
    if (is_array($tMembers)) {
        foreach ($tMembers as $u) {
            $seq = isset($u['seq']) ? (int)$u['seq'] : null;
            $name = trim($u['name'] ?? '');
            $cleanName = preg_replace('/\s+/u', '', $name);
            
            $payload = [
                'team' => $tIdx,
                'teamName' => $teamNames[$tIdx],
                'user' => $u
            ];

            // ผูกด้วย Composite Key ก่อน (ทั้ง seq และ name) เพื่อรองรับกรณีชื่อซ้ำแต่คนละลำดับ
            if ($seq && $name) {
                $userTeamMap['seq_name_' . $seq . '_' . $name] = $payload;
                $userTeamMap['seq_clean_' . $seq . '_' . $cleanName] = $payload;
            }
            if ($name) $userTeamMap['name_' . $name] = $payload;
            if ($cleanName) $userTeamMap['clean_' . $cleanName] = $payload;
            if ($seq) $userTeamMap['seq_' . $seq] = $payload;
        }
    }
}

// ฟังก์ชันแยกชื่อ - สกุล
function splitName($fullName) {
    $parts = preg_split('/\s+/u', trim($fullName));
    if (count($parts) >= 2) {
        return [$parts[0], implode(' ', array_slice($parts, 1))];
    }
    return [$fullName, ''];
}

/**
 * สร้าง XML Sheet โดยยึดลำดับและโครงสร้าง สำนัก/กอง/ส่วนงานย่อย ตามไฟล์แม่แบบ 100%
 * @param array $templateItems รายการแม่แบบ (หัวข้อ และรายชื่อ)
 * @param array $userTeamMap แผนผังทีมของผู้ใช้
 * @param int|null $filterTeam ถ้ากำหนดจะคัดกรองเฉพาะสมาชิกทีมนั้น
 * @param string $sheetTitle ชื่อหัวเรื่องเอกสาร
 */
function buildWorksheetXml($templateItems, $userTeamMap, $filterTeam = null, $sheetTitle = 'รายชื่อผู้เข้าร่วมโครงการสัมมนาและแข่งขันกีฬาสัมพันธ์') {
    global $teamNames;

    $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
    $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' . "\n";

    // ตั้งค่าความกว้างคอลัมน์เหมือนต้นแบบ
    // A: 8 (ลำดับที่), B: 20 (ชื่อ), C: 22 (สกุล), D: 46 (ตำแหน่ง), E: 8 (อายุ), F: 18 (สีที่สังกัด)
    $xml .= '  <cols>' . "\n";
    $xml .= '    <col min="1" max="1" width="8.5" customWidth="1"/>' . "\n";
    $xml .= '    <col min="2" max="2" width="20" customWidth="1"/>' . "\n";
    $xml .= '    <col min="3" max="3" width="22" customWidth="1"/>' . "\n";
    $xml .= '    <col min="4" max="4" width="46" customWidth="1"/>' . "\n";
    $xml .= '    <col min="5" max="5" width="8" customWidth="1"/>' . "\n";
    if ($filterTeam === null) {
        $xml .= '    <col min="6" max="6" width="20" customWidth="1"/>' . "\n"; // สีที่สังกัด
    }
    $xml .= '  </cols>' . "\n";

    $xml .= '  <sheetData>' . "\n";

    $r = 1;
    // Row 1: ชื่อโครงการ
    $xml .= "    <row r=\"$r\" ht=\"28\" customHeight=\"1\">\n";
    $xml .= "      <c r=\"A$r\" t=\"inlineStr\" s=\"1\"><is><t>" . htmlspecialchars($sheetTitle) . "</t></is></c>\n";
    $xml .= "    </row>\n";
    $r++;

    // Row 2: ดอกจันเส้นคั่น
    $xml .= "    <row r=\"$r\" ht=\"18\" customHeight=\"1\">\n";
    $xml .= "      <c r=\"A$r\" t=\"inlineStr\" s=\"2\"><is><t>********************************************************************************</t></is></c>\n";
    $xml .= "    </row>\n";
    $r++;

    // Row 3: Header ตาราง
    $xml .= "    <row r=\"$r\" ht=\"26\" customHeight=\"1\">\n";
    $xml .= "      <c r=\"A$r\" t=\"inlineStr\" s=\"3\"><is><t>ลำดับที่</t></is></c>\n";
    $xml .= "      <c r=\"B$r\" t=\"inlineStr\" s=\"4\"><is><t>ชื่อ - สกุล</t></is></c>\n";
    $xml .= "      <c r=\"C$r\" s=\"5\"/>\n";
    $xml .= "      <c r=\"D$r\" t=\"inlineStr\" s=\"3\"><is><t>ตำแหน่ง</t></is></c>\n";
    $xml .= "      <c r=\"E$r\" t=\"inlineStr\" s=\"3\"><is><t>อายุ</t></is></c>\n";
    if ($filterTeam === null) {
        $xml .= "      <c r=\"F$r\" t=\"inlineStr\" s=\"3\"><is><t>สีที่สังกัด</t></is></c>\n";
    }
    $xml .= "    </row>\n";
    $r++;

    $runningSeq = 1;
    $mergeCells = [];
    $mergeCells[] = "A1:" . ($filterTeam === null ? "F1" : "E1");
    $mergeCells[] = "A2:" . ($filterTeam === null ? "F2" : "E2");
    $mergeCells[] = "B3:C3";

    // สำหรับชีตทีมเดี่ยว ถ้าไม่มีสมาชิกในหัวข้อนั้น เราจะไม่แสดงหัวข้อที่ว่าง
    // รวบรวมข้อมูลที่จะแสดงก่อน
    $displayRows = [];
    $pendingHeaders = [];

    foreach ($templateItems as $item) {
        if ($item['type'] === 'header') {
            $pendingHeaders[] = $item['title'];
        } elseif ($item['type'] === 'user') {
            $seq = (int)($item['seq'] ?? 0);
            $name = trim($item['name'] ?? '');
            $cleanName = preg_replace('/\s+/u', '', $name);

            // ค้นหาการจับคู่โดยให้ความสำคัญกับ Composite Key (seq+name) ก่อน แล้วตามด้วยชื่อ และ seq
            $matched = null;
            if ($seq && $name && isset($userTeamMap['seq_name_' . $seq . '_' . $name])) {
                $matched = $userTeamMap['seq_name_' . $seq . '_' . $name];
            } elseif ($seq && $cleanName && isset($userTeamMap['seq_clean_' . $seq . '_' . $cleanName])) {
                $matched = $userTeamMap['seq_clean_' . $seq . '_' . $cleanName];
            } elseif ($name && isset($userTeamMap['name_' . $name])) {
                $matched = $userTeamMap['name_' . $name];
            } elseif ($cleanName && isset($userTeamMap['clean_' . $cleanName])) {
                $matched = $userTeamMap['clean_' . $cleanName];
            } elseif ($seq && isset($userTeamMap['seq_' . $seq])) {
                $matched = $userTeamMap['seq_' . $seq];
            }

            $tIdx = $matched ? $matched['team'] : null;

            // คัดกรองตามทีมหากระบุ
            if ($filterTeam !== null && $tIdx !== $filterTeam) {
                continue;
            }

            // ถ้ามีสมาชิกให้ปล่อย pending headers ออกมาก่อน
            if (!empty($pendingHeaders)) {
                foreach ($pendingHeaders as $hTitle) {
                    $displayRows[] = ['type' => 'header', 'title' => $hTitle];
                }
                $pendingHeaders = [];
            }

            $displayRows[] = [
                'type' => 'user',
                'item' => $item,
                'team' => $tIdx,
                'teamName' => $tIdx !== null ? $teamNames[$tIdx] : '-'
            ];
        }
    }

    // เขียนลง sheetData
    foreach ($displayRows as $dRow) {
        if ($dRow['type'] === 'header') {
            $h = $dRow['title'];
            // เช็คความลึกระดับหัวข้อเพื่อกำหนดสไตล์
            // สำนัก/กอง หลัก -> สไตล์ 6 (พื้นหลังเทาอ่อน 15% ตัวหนา)
            // ส่วนงานย่อย -> สไตล์ 7 (ตัวหนา มีเส้นคั่น)
            // ฝ่าย -> สไตล์ 8 (ตัวหนาปกติ)
            $isMainDiv = in_array($h, [
                'ผู้บริหารระดับสูง',
                'ระดับอำนวยการและระดับเชี่ยวชาญ',
                'ระดับเชี่ยวชาญ',
                'ระดับชำนาญการพิเศษ',
                'สำนักงานรัฐมนตรี',
                'สำนักบริหารกลาง',
                'ศูนย์เทคโนโลยีสารสนเทศและการสื่อสาร',
                'สำนักตรวจสอบและประเมินผล',
                'สำนักนโยบายและยุทธศาสตร์',
                'สำนักบริหารทรัพยากรบุคคล',
                'กลุ่มกฎหมาย',
                'กลุ่มตรวจสอบภายใน',
                'กลุ่มพัฒนาระบบบริหาร',
                'ศูนย์ปฏิบัติการต่อต้านการทุจริต',
                'กลุ่มสารนิเทศการคลัง'
            ]);

            $hStyle = $isMainDiv ? '6' : '7';
            $endCol = $filterTeam === null ? 'F' : 'E';

            $xml .= "    <row r=\"$r\" ht=\"24\" customHeight=\"1\">\n";
            $xml .= "      <c r=\"A$r\" t=\"inlineStr\" s=\"$hStyle\"><is><t>" . htmlspecialchars($h) . "</t></is></c>\n";
            $xml .= "      <c r=\"B$r\" s=\"$hStyle\"/>\n";
            $xml .= "      <c r=\"C$r\" s=\"$hStyle\"/>\n";
            $xml .= "      <c r=\"D$r\" s=\"$hStyle\"/>\n";
            $xml .= "      <c r=\"E$r\" s=\"$hStyle\"/>\n";
            if ($filterTeam === null) {
                $xml .= "      <c r=\"F$r\" s=\"$hStyle\"/>\n";
            }
            $xml .= "    </row>\n";

            $mergeCells[] = "A$r:{$endCol}$r";
            $r++;
        } elseif ($dRow['type'] === 'user') {
            $u = $dRow['item'];
            $fName = $u['firstName'] ?? '';
            $lName = $u['lastName'] ?? '';
            if (empty($fName) && !empty($u['name'])) {
                list($fName, $lName) = splitName($u['name']);
            }
            $pos = $u['position'] ?? '-';
            $age = !empty($u['age']) ? (int)$u['age'] : '-';
            $tName = $dRow['teamName'];

            $xml .= "    <row r=\"$r\" ht=\"22\" customHeight=\"1\">\n";
            // A: ลำดับที่ (Center, Border รอบ) -> Style 10
            $xml .= "      <c r=\"A$r\" t=\"n\" s=\"10\"><v>$runningSeq</v></c>\n";
            // B: ชื่อ (Left, Border บน-ล่าง ไม่มีขวา) -> Style 11
            $xml .= "      <c r=\"B$r\" t=\"inlineStr\" s=\"11\"><is><t>" . htmlspecialchars($fName) . "</t></is></c>\n";
            // C: สกุล (Left, Border บน-ล่าง-ขวา ไม่มีซ้าย) -> Style 12
            $xml .= "      <c r=\"C$r\" t=\"inlineStr\" s=\"12\"><is><t>" . htmlspecialchars($lName) . "</t></is></c>\n";
            // D: ตำแหน่ง (Left, Border รอบ) -> Style 13
            $xml .= "      <c r=\"D$r\" t=\"inlineStr\" s=\"13\"><is><t>" . htmlspecialchars($pos) . "</t></is></c>\n";
            // E: อายุ (Center, Border รอบ) -> Style 10
            $xml .= "      <c r=\"E$r\" t=\"" . (is_numeric($age) ? 'n' : 'inlineStr') . "\" s=\"10\"><v>" . htmlspecialchars((string)$age) . "</v></c>\n";
            // F: สีที่สังกัด (ถ้ามี)
            if ($filterTeam === null) {
                // Style 14: Center, Border รอบ, ตัวหนาสี
                $xml .= "      <c r=\"F$r\" t=\"inlineStr\" s=\"14\"><is><t>" . htmlspecialchars($tName) . "</t></is></c>\n";
            }
            $xml .= "    </row>\n";

            $r++;
            $runningSeq++;
        }
    }

    $xml .= "  </sheetData>\n";

    // รวม MergeCells
    if (!empty($mergeCells)) {
        $xml .= "  <mergeCells count=\"" . count($mergeCells) . "\">\n";
        foreach ($mergeCells as $mc) {
            $xml .= "    <mergeCell ref=\"$mc\"/>\n";
        }
        $xml .= "  </mergeCells>\n";
    }

    $xml .= "</worksheet>";
    return $xml;
}

/**
 * สร้าง styles.xml ที่ใช้ Font TH SarabunIT๙ ขนาด 16pt พร้อม Borders และ Fills ตรงตามไฟล์ตัวอย่าง
 */
function generateTemplateStylesXml() {
    $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
    $xml .= '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' . "\n";

    // 1. Fonts (ใช้ TH SarabunIT๙ ตามไฟล์ตัวอย่าง)
    $xml .= '  <fonts count="5">' . "\n";
    // Font 0: ปกติ 16pt TH SarabunIT๙
    $xml .= '    <font><sz val="16"/><color rgb="FF000000"/><name val="TH SarabunIT๙"/></font>' . "\n";
    // Font 1: หัวเรื่องใหญ่ 18pt หนา
    $xml .= '    <font><b/><sz val="18"/><color rgb="FF000000"/><name val="TH SarabunIT๙"/></font>' . "\n";
    // Font 2: ตัวหนา 16pt สำหรับ Header และ หมวดหมู่
    $xml .= '    <font><b/><sz val="16"/><color rgb="FF000000"/><name val="TH SarabunIT๙"/></font>' . "\n";
    // Font 3: ตัวหนา 16pt สีน้ำเงินเข้ม
    $xml .= '    <font><b/><sz val="16"/><color rgb="FF0B1F48"/><name val="TH SarabunIT๙"/></font>' . "\n";
    // Font 4: ตัวเอียง 16pt
    $xml .= '    <font><i/><sz val="16"/><color rgb="FF333333"/><name val="TH SarabunIT๙"/></font>' . "\n";
    $xml .= '  </fonts>' . "\n";

    // 2. Fills
    $xml .= '  <fills count="4">' . "\n";
    $xml .= '    <fill><patternFill patternType="none"/></fill>' . "\n";
    $xml .= '    <fill><patternFill patternType="gray125"/></fill>' . "\n";
    // Fill 2: สีเทาอ่อน 15% สำหรับแถวหัวตารางและหัวหมวด (#E8E8E8 / tint -0.15)
    $xml .= '    <fill><patternFill patternType="solid"><fgColor rgb="FFE8E8E8"/></patternFill></fill>' . "\n";
    // Fill 3: สีเทาจางมาก 5% (#F4F4F4)
    $xml .= '    <fill><patternFill patternType="solid"><fgColor rgb="FFF4F4F4"/></patternFill></fill>' . "\n";
    $xml .= '  </fills>' . "\n";

    // 3. Borders
    $xml .= '  <borders count="5">' . "\n";
    // Border 0: ไม่มีเส้น
    $xml .= '    <border><left/><right/><top/><bottom/><diagonal/></border>' . "\n";
    // Border 1: เส้นบางรอบด้าน 4 ทิศ (thin)
    $xml .= '    <border>' . "\n";
    $xml .= '      <left style="thin"><color indexed="64"/></left>' . "\n";
    $xml .= '      <right style="thin"><color indexed="64"/></right>' . "\n";
    $xml .= '      <top style="thin"><color indexed="64"/></top>' . "\n";
    $xml .= '      <bottom style="thin"><color indexed="64"/></bottom>' . "\n";
    $xml .= '    </border>' . "\n";
    // Border 2: มีเส้นซ้าย-บน-ล่าง แต่ไม่มีขวา (left+top+bottom)
    $xml .= '    <border>' . "\n";
    $xml .= '      <left style="thin"><color indexed="64"/></left>' . "\n";
    $xml .= '      <right/>' . "\n";
    $xml .= '      <top style="thin"><color indexed="64"/></top>' . "\n";
    $xml .= '      <bottom style="thin"><color indexed="64"/></bottom>' . "\n";
    $xml .= '    </border>' . "\n";
    // Border 3: มีเส้นบน-ล่าง เท่านั้น (top+bottom)
    $xml .= '    <border>' . "\n";
    $xml .= '      <left/>' . "\n";
    $xml .= '      <right/>' . "\n";
    $xml .= '      <top style="thin"><color indexed="64"/></top>' . "\n";
    $xml .= '      <bottom style="thin"><color indexed="64"/></bottom>' . "\n";
    $xml .= '    </border>' . "\n";
    // Border 4: มีเส้นขวา-บน-ล่าง แต่ไม่มีซ้าย (right+top+bottom)
    $xml .= '    <border>' . "\n";
    $xml .= '      <left/>' . "\n";
    $xml .= '      <right style="thin"><color indexed="64"/></right>' . "\n";
    $xml .= '      <top style="thin"><color indexed="64"/></top>' . "\n";
    $xml .= '      <bottom style="thin"><color indexed="64"/></bottom>' . "\n";
    $xml .= '    </border>' . "\n";
    $xml .= '  </borders>' . "\n";

    // 4. CellXfs
    $xml .= '  <cellStyleXfs count="1">' . "\n";
    $xml .= '    <xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>' . "\n";
    $xml .= '  </cellStyleXfs>' . "\n";

    $xml .= '  <cellXfs count="16">' . "\n";
    // 0: default
    $xml .= '    <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>' . "\n";
    // 1: Title (Bold 18pt Center)
    $xml .= '    <xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"><alignment horizontal="center" vertical="center"/></xf>' . "\n";
    // 2: Stars row (Center)
    $xml .= '    <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" applyFont="1"><alignment horizontal="center" vertical="center"/></xf>' . "\n";
    // 3: Table Header Cell รอบด้าน (Center, Fill 2, Border 1, Bold)
    $xml .= '    <xf numFmtId="0" fontId="2" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"><alignment horizontal="center" vertical="center"/></xf>' . "\n";
    // 4: Table Header Cell "ชื่อ-สกุล" ฝั่งซ้าย (Center, Fill 2, Border 2, Bold)
    $xml .= '    <xf numFmtId="0" fontId="2" fillId="2" borderId="2" xfId="0" applyFont="1" applyFill="1" applyBorder="1"><alignment horizontal="center" vertical="center"/></xf>' . "\n";
    // 5: Table Header Cell "ชื่อ-สกุล" ฝั่งขวา (Center, Fill 2, Border 4, Bold)
    $xml .= '    <xf numFmtId="0" fontId="2" fillId="2" borderId="4" xfId="0" applyFont="1" applyFill="1" applyBorder="1"><alignment horizontal="center" vertical="center"/></xf>' . "\n";
    // 6: หมวดหลัก สำนัก/กอง (Bold, Fill 2, Border 1, Left)
    $xml .= '    <xf numFmtId="0" fontId="2" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"><alignment horizontal="left" vertical="center"/></xf>' . "\n";
    // 7: หมวดย่อย ส่วน/ฝ่าย (Bold, Fill 0, Border 1, Left)
    $xml .= '    <xf numFmtId="0" fontId="2" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1"><alignment horizontal="left" vertical="center"/></xf>' . "\n";
    // 8: หมวดย่อยปกติ (Font 0, Fill 0, Border 1, Left)
    $xml .= '    <xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1"><alignment horizontal="left" vertical="center"/></xf>' . "\n";
    // 9: Spare
    $xml .= '    <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>' . "\n";
    // 10: ข้อมูลตัวเลข/อายุ (Center, Border 1, 16pt)
    $xml .= '    <xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1"><alignment horizontal="center" vertical="center"/></xf>' . "\n";
    // 11: ข้อมูลชื่อ (Left, Border 3 บน-ล่าง ไม่มีซ้าย-ขวา, 16pt)
    $xml .= '    <xf numFmtId="0" fontId="0" fillId="0" borderId="3" xfId="0" applyFont="1" applyBorder="1"><alignment horizontal="left" vertical="center"/></xf>' . "\n";
    // 12: ข้อมูลสกุล (Left, Border 4 ขวา-บน-ล่าง ไม่มีซ้าย, 16pt)
    $xml .= '    <xf numFmtId="0" fontId="0" fillId="0" borderId="4" xfId="0" applyFont="1" applyBorder="1"><alignment horizontal="left" vertical="center"/></xf>' . "\n";
    // 13: ข้อมูลตำแหน่ง (Left, Border 1 รอบด้าน, 16pt)
    $xml .= '    <xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1"><alignment horizontal="left" vertical="center"/></xf>' . "\n";
    // 14: สีที่สังกัด (Center, Border 1, Bold 16pt)
    $xml .= '    <xf numFmtId="0" fontId="3" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1"><alignment horizontal="center" vertical="center"/></xf>' . "\n";
    // 15: Spare
    $xml .= '    <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>' . "\n";
    $xml .= '  </cellXfs>' . "\n";

    $xml .= '</styleSheet>';
    return $xml;
}

// สร้างไฟล์ ZIP (XLSX)
$tmpFile = tempnam(sys_get_temp_dir(), 'sport_excel_');
$zip = new ZipArchive();
if ($zip->open($tmpFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
    die("Cannot create zip archive.");
}

// [Content_Types].xml
$contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
$contentTypes .= '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">' . "\n";
$contentTypes .= '  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>' . "\n";
$contentTypes .= '  <Default Extension="xml" ContentType="application/xml"/>' . "\n";
$contentTypes .= '  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>' . "\n";
$contentTypes .= '  <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>' . "\n";
for ($s = 1; $s <= 5; $s++) {
    $contentTypes .= "  <Override PartName=\"/xl/worksheets/sheet{$s}.xml\" ContentType=\"application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml\"/>\n";
}
$contentTypes .= '</Types>';
$zip->addFromString('[Content_Types].xml', $contentTypes);

// _rels/.rels
$rootRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
$rootRels .= '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . "\n";
$rootRels .= '  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>' . "\n";
$rootRels .= '</Relationships>';
$zip->addFromString('_rels/.rels', $rootRels);

// xl/_rels/workbook.xml.rels
$wbRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
$wbRels .= '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . "\n";
$wbRels .= '  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>' . "\n";
$wbRels .= '  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet2.xml"/>' . "\n";
$wbRels .= '  <Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet3.xml"/>' . "\n";
$wbRels .= '  <Relationship Id="rId4" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet4.xml"/>' . "\n";
$wbRels .= '  <Relationship Id="rId5" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet5.xml"/>' . "\n";
$wbRels .= '  <Relationship Id="rId6" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>' . "\n";
$wbRels .= '</Relationships>';
$zip->addFromString('xl/_rels/workbook.xml.rels', $wbRels);

// xl/workbook.xml
$wbXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
$wbXml .= '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' . "\n";
$wbXml .= '  <sheets>' . "\n";
$wbXml .= '    <sheet name="รายชื่อรวม 4 สี" sheetId="1" r:id="rId1"/>' . "\n";
$wbXml .= '    <sheet name="สีแดง" sheetId="2" r:id="rId2"/>' . "\n";
$wbXml .= '    <sheet name="สีชมพู" sheetId="3" r:id="rId3"/>' . "\n";
$wbXml .= '    <sheet name="สีเขียว" sheetId="4" r:id="rId4"/>' . "\n";
$wbXml .= '    <sheet name="สีเหลือง" sheetId="5" r:id="rId5"/>' . "\n";
$wbXml .= '  </sheets>' . "\n";
$wbXml .= '</workbook>';
$zip->addFromString('xl/workbook.xml', $wbXml);

// xl/styles.xml
$zip->addFromString('xl/styles.xml', generateTemplateStylesXml());

// Sheet 1: รายชื่อรวมทั้งหมด
$zip->addFromString('xl/worksheets/sheet1.xml', buildWorksheetXml($templateItems, $userTeamMap, null, 'รายชื่อผู้เข้าร่วมโครงการสัมมนาและแข่งขันกีฬาสัมพันธ์ สป.กค. (สรุปผลรวม 4 สี)'));

// Sheet 2-5: แต่ละสี
for ($t = 0; $t < 4; $t++) {
    $sheetNum = $t + 2;
    $sheetName = $t === 0 ? "สีแดง (Ruby)" : ($t === 1 ? "สีชมพู (Rose Pink)" : ($t === 2 ? "สีเขียว (Emerald)" : "สีเหลือง (Gold)"));
    $title = "รายชื่อผู้เข้าร่วมโครงการสัมมนาและแข่งขันกีฬาสัมพันธ์ สป.กค. — สังกัด: " . $teamNames[$t];
    $zip->addFromString("xl/worksheets/sheet{$sheetNum}.xml", buildWorksheetXml($templateItems, $userTeamMap, $t, $title));
}

$zip->close();

// ส่งไฟล์ให้ดาวน์โหลด
$filename = 'สรุปผลการแบ่งกลุ่ม4สี_ตามโครงสร้างหน่วยงาน_' . date('Y-m-d') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . rawurlencode($filename) . '"');
header('Content-Length: ' . filesize($tmpFile));
readfile($tmpFile);
unlink($tmpFile);
exit;
