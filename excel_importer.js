/**
 * excel_importer.js - โมดูลนำเข้าไฟล์ Excel (.xlsx, .xls) สำหรับทุกหน้าในระบบ
 */

function openGlobalExcelModal() {
  let modal = document.getElementById('globalExcelModal');
  if (!modal) {
    createGlobalExcelModalHtml();
    modal = document.getElementById('globalExcelModal');
  }
  const statusEl = document.getElementById('globalExcelStatus');
  if (statusEl) statusEl.classList.add('hidden');
  modal.classList.remove('hidden');
}

function closeGlobalExcelModal() {
  const modal = document.getElementById('globalExcelModal');
  if (modal) modal.classList.add('hidden');
}

function createGlobalExcelModalHtml() {
  const div = document.createElement('div');
  div.id = 'globalExcelModal';
  div.className = 'fixed inset-0 z-50 bg-[#0b1f48]/70 backdrop-blur-sm hidden flex items-center justify-center p-4';
  div.innerHTML = `
    <div class="bg-white rounded-3xl shadow-2xl border border-slate-200 w-full max-w-xl overflow-hidden flex flex-col animate-pop">
      <!-- Modal Header -->
      <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/80">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center text-lg shadow-2xs">
            <i class="fa-solid fa-file-excel"></i>
          </div>
          <div>
            <h3 class="font-bold text-base text-slate-900 font-heading">นำเข้าไฟล์ Excel (.xlsx / .xls)</h3>
            <p class="text-xs text-slate-500">อัปเดตรายชื่อบุคลากรใหม่เข้าสู่ระบบทั้ง 4 หน้าพร้อมกัน</p>
          </div>
        </div>
        <button onclick="closeGlobalExcelModal()" class="w-8 h-8 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100 flex items-center justify-center cursor-pointer transition">
          <i class="fa-solid fa-xmark text-sm"></i>
        </button>
      </div>

      <!-- Dropzone -->
      <div class="p-6">
        <div id="globalExcelDropzone"
             ondragover="event.preventDefault(); this.classList.add('border-emerald-500', 'bg-emerald-50/50');"
             ondragleave="event.preventDefault(); this.classList.remove('border-emerald-500', 'bg-emerald-50/50');"
             ondrop="handleGlobalExcelDrop(event)"
             onclick="document.getElementById('globalExcelFileInput').click()"
             class="border-2 border-dashed border-slate-200 hover:border-emerald-500 hover:bg-emerald-50/30 rounded-2xl p-8 text-center cursor-pointer transition flex flex-col items-center justify-center gap-3">
          <input type="file" id="globalExcelFileInput" accept=".xlsx, .xls" class="hidden" onchange="handleGlobalExcelFileSelected(event)">
          <div class="w-14 h-14 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-2xl shadow-xs">
            <i class="fa-solid fa-cloud-arrow-up"></i>
          </div>
          <div>
            <div class="font-bold text-sm text-slate-800">คลิกเพื่อเลือกไฟล์ หรือลากไฟล์ Excel มาวางที่นี่</div>
            <div class="text-xs text-slate-400 mt-1">รองรับไฟล์ Excel (.xlsx, .xls) เช่น ไฟล์รายชื่อสัมมนา / เจ้าหน้าที่</div>
          </div>
          <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
            <i class="fa-solid fa-check text-[10px]"></i> รองรับคอลัมน์ ชื่อ, สกุล, ตำแหน่ง, อายุ, สำนัก, ส่วน
          </span>
        </div>

        <div id="globalExcelStatus" class="mt-4 hidden p-3 rounded-xl text-xs font-semibold flex items-center gap-2"></div>
      </div>

      <!-- Modal Footer -->
      <div class="p-4 bg-slate-50 border-t border-slate-100 flex items-center justify-between">
        <span class="text-[11px] text-slate-400">ระบบจะประมวลผลและเกลี่ยอายุให้อัตโนมัติ</span>
        <button onclick="closeGlobalExcelModal()" class="px-4 py-2 rounded-xl bg-slate-200 hover:bg-slate-300 text-slate-700 font-semibold text-xs transition cursor-pointer">
          ปิดหน้าต่าง
        </button>
      </div>
    </div>
  `;
  document.body.appendChild(div);
}

function handleGlobalExcelDrop(e) {
  e.preventDefault();
  const dz = document.getElementById('globalExcelDropzone');
  if (dz) dz.classList.remove('border-emerald-500', 'bg-emerald-50/50');
  const files = e.dataTransfer.files;
  if (files && files.length > 0) {
    uploadExcelDirectly(files[0]);
  }
}

function handleGlobalExcelFileSelected(e) {
  const files = e.target.files;
  if (files && files.length > 0) {
    uploadExcelDirectly(files[0]);
  }
}

/**
 * อัปโหลดไฟล์ Excel ตรงไปยัง backend (upload_excel.php) แบบ FormData เพื่อความแม่นยำ 100%
 */
function uploadExcelDirectly(file) {
  if (!file) return;
  if (!file.name.match(/\.(xlsx|xls)$/i)) {
    alert('กรุณาเลือกไฟล์ที่เป็น Excel (.xlsx หรือ .xls) เท่านั้น');
    return;
  }

  const statusEl = document.getElementById('globalExcelStatus');
  if (statusEl) {
    statusEl.className = 'mt-4 p-3 rounded-xl text-xs font-semibold flex items-center gap-2 bg-blue-50 text-blue-700 border border-blue-200';
    statusEl.innerHTML = `<i class="fa-solid fa-spinner fa-spin text-sm"></i> กำลังอัปโหลดและประมวลผลไฟล์: ${file.name}...`;
    statusEl.classList.remove('hidden');
  }

  const formData = new FormData();
  formData.append('file', file);

  fetch('upload_excel.php', {
    method: 'POST',
    body: formData
  })
  .then(res => res.json())
  .then(res => {
    if (res.success) {
      if (statusEl) {
        statusEl.className = 'mt-4 p-3 rounded-xl text-xs font-semibold flex items-center gap-2 bg-emerald-50 text-emerald-700 border border-emerald-200';
        statusEl.innerHTML = `<i class="fa-solid fa-circle-check text-sm"></i> ${res.message}! กำลังรีเฟรชหน้าเว็บ...`;
      }

      // ล้างข้อมูลการสุ่มเดิมใน LocalStorage ทั้งหมดเพื่อให้สุ่มชุดใหม่
      try {
        localStorage.removeItem('sport_div_sections_state');
        localStorage.removeItem('sport_shared_stage_state');
        localStorage.removeItem('sport_latest_spin');
      } catch (e) {}

      setTimeout(() => {
        location.reload();
      }, 800);
    } else {
      throw new Error(res.message || 'เกิดข้อผิดพลาดในการประมวลผลไฟล์');
    }
  })
  .catch(err => {
    console.error('Server upload failed, fallback to client parse:', err);
    // หาก FormData ล้มเหลว ให้ Fallback ใช้การ Parse ฝั่ง Client
    parseExcelOnClientAndUpload(file);
  });
}

/**
 * Fallback: ใช้ SheetJS อ่านบนเบราว์เซอร์แล้วส่ง JSON ไปบันทึก
 */
function parseExcelOnClientAndUpload(file) {
  const statusEl = document.getElementById('globalExcelStatus');
  const reader = new FileReader();
  reader.onload = function(e) {
    try {
      const data = new Uint8Array(e.target.result);
      const workbook = XLSX.read(data, { type: 'array' });
      const firstSheetName = workbook.SheetNames[0];
      const worksheet = workbook.Sheets[firstSheetName];
      const rawRows = XLSX.utils.sheet_to_json(worksheet, { header: 1 });

      const parsedUsers = parseRowsToUsers(rawRows);
      if (parsedUsers.length === 0) throw new Error('ไม่พบรายชื่อในไฟล์');

      fetch('upload_excel.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          users: parsedUsers,
          filename: file.name
        })
      })
      .then(res => res.json())
      .then(res => {
        if (res.success) {
          if (statusEl) {
            statusEl.className = 'mt-4 p-3 rounded-xl text-xs font-semibold flex items-center gap-2 bg-emerald-50 text-emerald-700 border border-emerald-200';
            statusEl.innerHTML = `<i class="fa-solid fa-circle-check text-sm"></i> นำเข้าข้อมูลสำเร็จ ${parsedUsers.length} คน! กำลังรีเฟรช...`;
          }
          try {
            localStorage.removeItem('sport_div_sections_state');
            localStorage.removeItem('sport_shared_stage_state');
            localStorage.removeItem('sport_latest_spin');
          } catch (e) {}
          setTimeout(() => location.reload(), 800);
        } else {
          throw new Error(res.message);
        }
      })
      .catch(e => {
        alert('เกิดข้อผิดพลาด: ' + e.message);
        if (statusEl) statusEl.classList.add('hidden');
      });

    } catch (err) {
      alert('ไม่สามารถอ่านไฟล์ Excel ได้: ' + err.message);
      if (statusEl) statusEl.classList.add('hidden');
    }
  };
  reader.readAsArrayBuffer(file);
}

function parseRowsToUsers(rows) {
  const users = [];
  let currentDivision = 'ผู้บริหารระดับสูง';
  let currentSection = 'งานบริหารทั่วไป';
  let seq = 1;

  for (let r = 0; r < rows.length; r++) {
    const row = rows[r];
    if (!row || row.length === 0) continue;

    const colA = String(row[0] || '').trim();
    const colB = String(row[1] || '').trim();
    const colC = String(row[2] || '').trim();
    const colD = String(row[3] || '').trim();
    const colE = String(row[4] || '').trim();

    const line = `${colA} ${colB} ${colC}`.trim();
    if (/(สำนัก|ศูนย์|กลุ่ม|กอง|ส่วนพัฒนาระบบ|ส่วนบริหาร|ส่วนอำนวยการ)/u.test(line) && !colD && !colE) {
      if (/(สำนัก|ศูนย์|กลุ่ม|กอง)/u.test(line)) {
        currentDivision = line;
        currentSection = 'งานบริหารทั่วไป/บุคลากรในสังกัด';
      } else if (line.includes('ส่วน')) {
        currentSection = line;
      }
      continue;
    }

    let name = '';
    let position = '';
    let age = 0;

    if (colB && colC && colB !== 'ชื่อ' && colC !== 'สกุล') {
      name = `${colB} ${colC}`.trim();
      position = colD;
      age = parseInt(colE) || 0;
    } else if (colA && (colA.startsWith('นาย') || colA.startsWith('นาง'))) {
      name = colA;
      position = colB;
      age = parseInt(colC) || 0;
    }

    if (!name || name === 'ชื่อ สกุล' || name.length < 3) continue;

    const gender = name.startsWith('นาย') ? 'ชาย' : 'หญิง';
    let div = currentDivision;
    let sec = currentSection;

    if (/(รองปลัด|ผู้ตรวจ|ที่ปรึกษา)/u.test(position)) {
      div = 'ผู้บริหารระดับสูง';
      sec = position.includes('รองปลัด') ? 'รองปลัดกระทรวงการคลัง' : (position.includes('ผู้ตรวจ') ? 'ผู้ตรวจราชการกระทรวงการคลัง' : 'ที่ปรึกษา');
    }

    users.push({
      seq: seq++,
      name: name,
      position: position,
      POSITION: position,
      gender: gender,
      age: age || 38,
      department: 'สำนักงานปลัดกระทรวงการคลัง',
      division: div,
      section: sec,
      subdiv1: sec,
      subdiv2: '',
      assignedTeam: -1,
      assignedTag: ''
    });
  }

  return users;
}
