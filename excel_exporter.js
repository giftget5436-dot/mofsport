/**
 * excel_exporter.js - โมดูลกลางสำหรับส่งออกไฟล์ Excel ผลการแบ่งสี
 * ถอดแบบโครงสร้าง ตาราง (Table Borders), ส่วนหัวเน้นสี (Navy Header), และแบ่งส่วนงานชัดเจน
 * ตามไฟล์ต้นแบบ "ตัวอย่างรายชื่อสัมมนา ซ้อมสุ่มสี.xlsx"
 */

function exportSportResultExcel(teamsData, options = {}) {
  if (!teamsData || teamsData.length === 0) {
    alert('ยังไม่มีข้อมูลการแบ่งสีสำหรับส่งออก');
    return;
  }

  // ส่งข้อมูลชุดปัจจุบันไปยัง export_excel.php เพื่อสร้างไฟล์ XLSX ที่มีตาราง เส้นขอบ (Borders) และสีสันสวยงาม 100%
  const payload = {
    teams: teamsData,
    options: options
  };

  fetch('export_excel.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify(payload)
  })
  .then(res => {
    if (!res.ok) throw new Error('เกิดข้อผิดพลาดในการสร้างไฟล์ Excel');
    return res.blob();
  })
  .then(blob => {
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    const fileName = options.fileName || `ผลการแบ่งกลุ่ม4สี_กีฬาสัมพันธ์_${new Date().toISOString().slice(0, 10)}.xlsx`;
    a.download = fileName;
    document.body.appendChild(a);
    a.click();
    window.URL.revokeObjectURL(url);
    a.remove();
  })
  .catch(err => {
    console.error('Export error, fallback to client-side XLSX:', err);
    fallbackClientExport(teamsData, options);
  });
}

function fallbackClientExport(teamsData, options) {
  if (typeof XLSX === 'undefined') {
    alert('ระบบไม่พบไลบรารีสำหรับสร้างไฟล์ Excel');
    return;
  }

  const defaultTeamNames = ['สีแดง', 'สีชมพู', 'สีเขียว', 'สีเหลือง'];
  const teamNames = options.teamNames || defaultTeamNames;
  const fileName = options.fileName || `ผลการแบ่งกลุ่ม4สี_กีฬาสัมพันธ์_${new Date().toISOString().slice(0, 10)}.xlsx`;

  const wb = XLSX.utils.book_new();

  function splitFullName(fullName) {
    let clean = (fullName || '').trim();
    let parts = clean.split(/\s+/);
    if (parts.length >= 2) {
      return { firstName: parts[0], lastName: parts.slice(1).join(' ') };
    }
    return { firstName: clean, lastName: '' };
  }

  const standardDivisionOrder = [
    'ผู้บริหารระดับสูง',
    'สำนักงานรัฐมนตรี',
    'สำนักบริหารกลาง',
    'กองยุทธศาสตร์และแผนงาน',
    'กองบริหารทรัพยากรบุคคล',
    'กองการเงินและพัสดุ',
    'กองกฎหมาย',
    'ศูนย์เทคโนโลยีสารสนเทศและการสื่อสาร',
    'กลุ่มพัฒนาระบบบริหาร',
    'กลุ่มตรวจสอบภายใน',
    'กลุ่มสารนิเทศการคลัง'
  ];

  function buildTemplateRows(members, titleHeader) {
    const rows = [];
    rows.push([titleHeader || 'รายชื่อผู้เข้าร่วมโครงการสัมมนาและแข่งขันกีฬาสัมพันธ์']);
    rows.push(['โครงการสัมมนาและแข่งขันกีฬาสัมพันธ์ สป.กค. — ตารางสรุปผลแบ่ง 4 สีอย่างสมดุล']);
    rows.push(['ลำดับที่', 'ชื่อ', 'สกุล', 'ตำแหน่ง', 'อายุ', 'สำนัก / กอง (Division)', 'ส่วนงานย่อย (Section)', 'สีที่สังกัด']);

    let templateStructure = null;
    if (typeof window !== 'undefined' && window.EXCEL_TEMPLATE_STRUCTURE) {
      templateStructure = window.EXCEL_TEMPLATE_STRUCTURE;
    }

    if (templateStructure && Array.isArray(templateStructure)) {
      // ใช้โครงสร้างแม่แบบเดียวกับ export_excel.php
      const userMap = {};
      members.forEach(u => {
        let seq = u.seq;
        let name = (u.name || '').trim();
        let clean = name.replace(/\s+/g, '');
        // ผูกด้วย Composite Key ก่อนเพื่อให้การจับคู่แม่นยำ 100%
        if (seq && name) {
          userMap['seq_name_' + seq + '_' + name] = u;
          userMap['seq_clean_' + seq + '_' + clean] = u;
        }
        if (name) userMap['name_' + name] = u;
        if (clean) userMap['clean_' + clean] = u;
        if (seq) userMap['seq_' + seq] = u;
      });

      let seqCount = 1;
      let pendingH = [];

      templateStructure.forEach(item => {
        if (item.type === 'header') {
          pendingH.push(item.title);
        } else if (item.type === 'user') {
          let s = item.seq;
          let n = (item.name || '').trim();
          let c = n.replace(/\s+/g, '');
          let matched = null;
          if (s && n && userMap['seq_name_' + s + '_' + n]) {
            matched = userMap['seq_name_' + s + '_' + n];
          } else if (s && c && userMap['seq_clean_' + s + '_' + c]) {
            matched = userMap['seq_clean_' + s + '_' + c];
          } else if (n && userMap['name_' + n]) {
            matched = userMap['name_' + n];
          } else if (c && userMap['clean_' + c]) {
            matched = userMap['clean_' + c];
          } else if (s && userMap['seq_' + s]) {
            matched = userMap['seq_' + s];
          }

          if (!matched) return;

          if (pendingH.length > 0) {
            pendingH.forEach(h => {
              rows.push([h, '', '', '', '', '', '', '']);
            });
            pendingH = [];
          }

          let nameObj = splitFullName(item.name || matched.name);
          let pos = item.position || matched.position || '-';
          let age = item.age || matched.age || '-';
          let teamName = (matched.assignedTeam !== undefined && matched.assignedTeam !== null && matched.assignedTeam >= 0)
            ? teamNames[matched.assignedTeam]
            : (matched.teamName || '-');

          rows.push([
            seqCount++,
            nameObj.firstName,
            nameObj.lastName,
            pos,
            age,
            matched.division || '-',
            matched.section || '-',
            teamName
          ]);
        }
      });

      return rows;
    }
  }

  const allMembers = [];
  teamsData.forEach((teamArr, tIdx) => {
    teamArr.forEach(u => {
      allMembers.push({ ...u, assignedTeam: tIdx });
    });
  });

  const wsAll = XLSX.utils.aoa_to_sheet(buildTemplateRows(allMembers, 'รายชื่อผู้เข้าร่วมโครงการสัมมนาและแข่งขันกีฬาสัมพันธ์ สป.กค. (สรุปภาพรวม 4 สี)'));
  wsAll['!cols'] = [
    { wch: 10 }, { wch: 22 }, { wch: 24 }, { wch: 45 },
    { wch: 10 }, { wch: 32 }, { wch: 30 }, { wch: 22 }
  ];
  XLSX.utils.book_append_sheet(wb, wsAll, 'รายชื่อรวม 4 สี');

  const sheetShortNames = ['สีแดง', 'สีชมพู', 'สีเขียว', 'สีเหลือง'];
  teamsData.forEach((teamMembers, tIdx) => {
    const wsTeam = XLSX.utils.aoa_to_sheet(buildTemplateRows(teamMembers, `รายชื่อผู้เข้าร่วมโครงการสัมมนาและแข่งขันกีฬาสัมพันธ์ — สังกัด: ${teamNames[tIdx]}`));
    wsTeam['!cols'] = [
      { wch: 10 }, { wch: 22 }, { wch: 24 }, { wch: 45 },
      { wch: 10 }, { wch: 32 }, { wch: 30 }, { wch: 22 }
    ];
    XLSX.utils.book_append_sheet(wb, wsTeam, sheetShortNames[tIdx] || `สีที่ ${tIdx + 1}`);
  });

  XLSX.writeFile(wb, fileName);
}

if (typeof window !== 'undefined') {
  window.exportSportResultExcel = exportSportResultExcel;
}
