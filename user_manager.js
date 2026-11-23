/**
 * user_manager.js - ระบบจัดการรายชื่อผู้เข้าร่วมสำหรับทุกหน้า (เปิด/ปิด, ค้นหา, บันทึกการเข้าร่วม)
 * ซิงค์สถานะการเปิด/ปิดผู้เข้าร่วมตรงกันทุกหน้าผ่าน LocalStorage ('sport_user_active_map')
 */

let globalUserList = [];
let onUsersUpdatedCallback = null;

// โหลดข้อมูลสถานะ Active ของแต่ละบุคคลจาก LocalStorage
function getStoredUserActiveMap() {
  try {
    const raw = localStorage.getItem('sport_user_active_map');
    if (raw) return JSON.parse(raw);
  } catch (e) {}
  return {};
}

// บันทึกสถานะ Active ลง LocalStorage และซิงค์ผลการสุ่มล่าสุด
function saveStoredUserActiveMap(activeMap) {
  try {
    localStorage.setItem('sport_user_active_map', JSON.stringify(activeMap));
  } catch (e) {}

  // ตัดคนที่ไม่ถูกเลือก (active = false) ออกจาก state การสุ่มล่าสุดที่เคยบันทึกไว้ในทุก storage
  const storageKeys = ['sport_latest_spin', 'sport_shared_stage_state', 'sport_div_sections_state'];
  storageKeys.forEach(k => {
    try {
      const raw = localStorage.getItem(k);
      if (raw) {
        const parsed = JSON.parse(raw);
        if (parsed && Array.isArray(parsed.teams)) {
          parsed.teams = parsed.teams.map(team => {
            if (!Array.isArray(team)) return [];
            return team.filter(u => isUserActiveInMap(u, activeMap));
          });
          localStorage.setItem(k, JSON.stringify(parsed));
        }
      }
    } catch (e) {}
  });
}
function isUserActiveInMap(u, activeMap) {
  if (!activeMap) activeMap = getStoredUserActiveMap();
  const kSeq = u.seq !== undefined && u.seq !== null ? String(u.seq) : null;
  const kId = u.id !== undefined && u.id !== null ? String(u.id) : null;
  const kName = u.name ? String(u.name).trim() : null;

  if (kSeq && activeMap[kSeq] !== undefined) return activeMap[kSeq] !== false;
  if (kId && activeMap[kId] !== undefined) return activeMap[kId] !== false;
  if (kName && activeMap[kName] !== undefined) return activeMap[kName] !== false;
  return u.active !== false;
}

// ฟังก์ชันคัดกรองทีมที่สุ่มไว้แล้ว ให้เหลือเฉพาะผู้ที่มีสถานะ Active
function filterTeamsByActiveUsers(teams) {
  if (!Array.isArray(teams)) return teams;
  const activeMap = getStoredUserActiveMap();
  return teams.map(team => {
    if (!Array.isArray(team)) return [];
    return team.filter(u => isUserActiveInMap(u, activeMap));
  });
}

function initGlobalUserManager(users, onUpdated) {
  globalUserList = users || [];
  onUsersUpdatedCallback = onUpdated;

  // นำสถานะที่เคยบันทึกไว้มาผูกกับรายชื่อผู้ใช้
  const activeMap = getStoredUserActiveMap();
  globalUserList.forEach(u => {
    const key = String(u.seq || u.id || u.name);
    if (activeMap[key] !== undefined) {
      u.active = activeMap[key];
    } else if (u.active === undefined) {
      u.active = true;
    }
  });

  // อัปเดตตัวเลขบน Badge ทุกปุ่มในหน้า
  updateAllManageBadges();

  // ฟัง event การเปลี่ยนแปลงข้ามแท็บ/ข้ามหน้าต่าง
  window.removeEventListener('storage', handleStorageUserSync);
  window.addEventListener('storage', handleStorageUserSync);
}

function handleStorageUserSync(e) {
  if (e.key === 'sport_user_active_map') {
    const activeMap = getStoredUserActiveMap();
    globalUserList.forEach(u => {
      const key = String(u.seq || u.id || u.name);
      if (activeMap[key] !== undefined) {
        u.active = activeMap[key];
      }
    });
    renderGlobalManageList();
    updateAllManageBadges();
    if (typeof onUsersUpdatedCallback === 'function') {
      onUsersUpdatedCallback(globalUserList);
    }
  }
}

function updateAllManageBadges() {
  const activeCount = globalUserList.filter(u => u.active !== false).length;
  const btnCounts = document.querySelectorAll('.user-manage-badge-count');
  btnCounts.forEach(el => el.textContent = activeCount);
}

function openGlobalManageModal() {
  let modal = document.getElementById('globalManageModal');
  if (!modal) {
    createGlobalManageModalHtml();
    modal = document.getElementById('globalManageModal');
  }
  renderGlobalManageList();
  modal.classList.remove('hidden');
}

function closeGlobalManageModal() {
  const modal = document.getElementById('globalManageModal');
  if (modal) modal.classList.add('hidden');
}

function createGlobalManageModalHtml() {
  const div = document.createElement('div');
  div.id = 'globalManageModal';
  div.className = 'fixed inset-0 z-50 bg-[#0b1f48]/70 backdrop-blur-sm hidden flex items-center justify-center p-4';
  div.innerHTML = `
    <div class="bg-white rounded-3xl shadow-2xl border border-slate-200 w-full max-w-4xl max-h-[90vh] flex flex-col overflow-hidden animate-pop">
      <!-- Modal Header -->
      <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/80">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-2xl bg-indigo-100 text-indigo-700 flex items-center justify-center text-lg shadow-2xs">
            <i class="fa-solid fa-users-gear"></i>
          </div>
          <div>
            <h3 class="font-bold text-base text-slate-900 font-heading">จัดการรายชื่อผู้เข้าร่วมกิจกรรม</h3>
            <p class="text-xs text-slate-500">เลือกติ๊กเปิด/ปิดผู้เข้าร่วม หรือค้นหารายชื่อเพื่อเปิดสิทธิ์สุ่ม</p>
          </div>
        </div>
        <button onclick="closeGlobalManageModal()" class="w-8 h-8 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100 flex items-center justify-center cursor-pointer transition">
          <i class="fa-solid fa-xmark text-sm"></i>
        </button>
      </div>

      <!-- Action Toolbar -->
      <div class="p-4 bg-white border-b border-slate-100 flex flex-wrap items-center justify-between gap-3 text-xs">
        <div class="flex items-center gap-2">
          <button onclick="toggleAllUsersActive(true)" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 rounded-xl text-slate-700 font-semibold transition cursor-pointer">
            <i class="fa-solid fa-check-double mr-1 text-emerald-600"></i> เลือกทุกคน
          </button>
          <button onclick="toggleAllUsersActive(false)" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 rounded-xl text-slate-700 font-semibold transition cursor-pointer">
            <i class="fa-solid fa-ban mr-1 text-rose-500"></i> ไม่เลือกทุกคน
          </button>
          <span class="text-slate-500 text-xs ml-2 font-medium">
            พร้อมสุ่ม: <b id="globalManageActiveCount" class="text-indigo-600 font-en">0</b> / <span id="globalManageTotalCount" class="font-en">0</span> คน
          </span>
        </div>

        <div class="flex items-center gap-2">
          <button onclick="openGlobalExcelModal(); closeGlobalManageModal();" class="px-3.5 py-1.5 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 text-emerald-700 font-semibold rounded-xl text-xs flex items-center gap-1.5 transition cursor-pointer">
            <i class="fa-solid fa-cloud-arrow-up text-emerald-600"></i>
            <span>อัปโหลดไฟล์ Excel ใหม่</span>
          </button>
        </div>
      </div>

      <!-- Search Input -->
      <div class="px-4 py-2.5 border-b border-slate-100 bg-slate-50/50">
        <div class="relative">
          <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
          <input type="text" id="globalManageSearchInput" oninput="renderGlobalManageList()" placeholder="พิมพ์ชื่อ, ตำแหน่ง, สำนัก หรือส่วนงานเพื่อค้นหา..." class="w-full pl-9 pr-3.5 py-2 rounded-xl border border-slate-200 text-xs bg-white focus:ring-2 focus:ring-indigo-500 focus:outline-none transition">
        </div>
      </div>

      <!-- User Table -->
      <div class="flex-1 overflow-y-auto p-4 custom-scrollbar">
        <table class="w-full text-xs text-left">
          <thead class="sticky top-0 bg-slate-100 text-slate-600 font-semibold z-10 shadow-2xs">
            <tr>
              <th class="py-2.5 px-3 w-16 text-center">สถานะ</th>
              <th class="py-2.5 px-3">ชื่อ - นามสกุล</th>
              <th class="py-2.5 px-3 text-center">เพศ</th>
              <th class="py-2.5 px-3 text-center">อายุ</th>
              <th class="py-2.5 px-3">ตำแหน่ง</th>
              <th class="py-2.5 px-3">สำนัก / กอง</th>
              <th class="py-2.5 px-3">ส่วนงานย่อย</th>
            </tr>
          </thead>
          <tbody id="globalManageTableBody" class="divide-y divide-slate-100">
            <!-- Render via JS -->
          </tbody>
        </table>
      </div>

      <!-- Modal Footer -->
      <div class="p-4 border-t border-slate-100 bg-slate-50 flex items-center justify-between">
        <span class="text-xs text-slate-400">การเปลี่ยนแปลงสถานะจะถูกบันทึกและซิงค์ทันที</span>
        <button onclick="closeGlobalManageModal()" class="px-5 py-2 rounded-xl bg-slate-900 hover:bg-black text-white font-semibold text-xs transition shadow-xs cursor-pointer">
          เสร็จสิ้น
        </button>
      </div>
    </div>
  `;
  document.body.appendChild(div);
}

function renderGlobalManageList() {
  const tbody = document.getElementById('globalManageTableBody');
  if (!tbody) return;

  const q = (document.getElementById('globalManageSearchInput')?.value || '').trim().toLowerCase();
  tbody.innerHTML = '';

  let list = globalUserList;
  if (!list || list.length === 0) {
    if (window.INITIAL_USER_DATA && Array.isArray(window.INITIAL_USER_DATA)) {
      list = window.INITIAL_USER_DATA;
      globalUserList = list;
    }
  }

  let activeCount = 0;
  const filtered = list.filter((u, idx) => {
    if (u.active === undefined) u.active = true;
    if (u.active) activeCount++;

    if (!q) return true;
    const name = (u.name || `${u.FIRSTNAME || ''} ${u.LASTNAME || ''}`).toLowerCase();
    const pos = (u.position || u.POSITION || '').toLowerCase();
    const div = (u.division || u.DEPARTMENT || '').toLowerCase();
    const sec = (u.section || u.subdiv1 || '').toLowerCase();
    return name.includes(q) || pos.includes(q) || div.includes(q) || sec.includes(q);
  });

  const activeCountEl = document.getElementById('globalManageActiveCount');
  const totalCountEl = document.getElementById('globalManageTotalCount');
  if (activeCountEl) activeCountEl.textContent = activeCount;
  if (totalCountEl) totalCountEl.textContent = list.length;

  // อัปเดตปุ่มจัดการรายชื่อบนหน้าเว็บหลัก
  const btnCounts = document.querySelectorAll('.user-manage-badge-count');
  btnCounts.forEach(el => el.textContent = activeCount);

  if (filtered.length === 0) {
    tbody.innerHTML = `<tr><td colspan="7" class="text-center py-8 text-slate-400 italic">ไม่พบรายชื่อตามเงื่อนไขที่ค้นหา</td></tr>`;
    return;
  }

  filtered.forEach(u => {
    const tr = document.createElement('tr');
    tr.className = `hover:bg-slate-50 transition ${u.active ? '' : 'opacity-40 bg-slate-50/50'}`;
    const seq = u.seq || u.id;
    const name = u.name || `${u.FIRSTNAME || ''} ${u.LASTNAME || ''}`.trim();
    const pos = u.position || u.POSITION || '-';
    const div = u.division || u.DEPARTMENT || '-';
    const sec = u.section || u.subdiv1 || '-';
    const age = u.age || 0;
    let gender = (u.gender || '').trim();
    if (gender !== 'ชาย' && gender !== 'หญิง') {
      const text = `${u.prename_th || ''} ${name}`.trim();
      if (/^(นาย|ว่าที่ร้อยตรี|ว่าที่ร้อยโท|ร้อยตรี|ร้อยโท|ร้อยตำรวจโท|พลฯ)/u.test(text) || text.startsWith('นาย')) {
        gender = 'ชาย';
      } else if (/^(นางสาว|นาง|ว่าที่ร้อยตรีหญิง|ว่าที่ร้อยโทหญิง|ว่าที่ร้อยตำรวจโทหญิง)/u.test(text) || text.startsWith('นางสาว') || text.startsWith('นาง')) {
        gender = 'หญิง';
      } else if (text.includes('นาย')) {
        gender = 'ชาย';
      } else if (text.includes('นาง')) {
        gender = 'หญิง';
      } else {
        gender = 'ไม่ระบุ';
      }
    }

    tr.innerHTML = `
      <td class="py-2.5 px-3 text-center">
        <input type="checkbox" ${u.active ? 'checked' : ''} onchange="toggleSingleUserActive('${seq}', this.checked)" class="w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500 cursor-pointer">
      </td>
      <td class="py-2.5 px-3 font-bold text-slate-900">${name}</td>
      <td class="py-2.5 px-3 text-center text-slate-600">${gender}</td>
      <td class="py-2.5 px-3 text-center font-bold text-slate-700 font-en">${age ? `${age} ปี` : '-'}</td>
      <td class="py-2.5 px-3 text-slate-600">${pos}</td>
      <td class="py-2.5 px-3 text-slate-700 font-medium">${div}</td>
      <td class="py-2.5 px-3 text-slate-500">${sec}</td>
    `;
    tbody.appendChild(tr);
  });
}

function toggleSingleUserActive(seq, isActive) {
  const user = globalUserList.find(u => String(u.seq || u.id) === String(seq) || String(u.name) === String(seq));
  if (user) {
    user.active = isActive;

    // บันทึกสถานะลง LocalStorage เพื่อให้ทุกหน้าลิงก์กันอัตโนมัติ (บันทึกทั้ง seq, id และ name เพื่อความแม่นยำ 100%)
    const activeMap = getStoredUserActiveMap();
    if (user.seq !== undefined && user.seq !== null) activeMap[String(user.seq)] = isActive;
    if (user.id !== undefined && user.id !== null) activeMap[String(user.id)] = isActive;
    if (user.name) activeMap[String(user.name).trim()] = isActive;
    saveStoredUserActiveMap(activeMap);

    renderGlobalManageList();
    updateAllManageBadges();
    if (typeof onUsersUpdatedCallback === 'function') {
      onUsersUpdatedCallback(globalUserList);
    }
  }
}

function toggleAllUsersActive(isActive) {
  const activeMap = getStoredUserActiveMap();
  globalUserList.forEach(u => {
    u.active = isActive;
    if (u.seq !== undefined && u.seq !== null) activeMap[String(u.seq)] = isActive;
    if (u.id !== undefined && u.id !== null) activeMap[String(u.id)] = isActive;
    if (u.name) activeMap[String(u.name).trim()] = isActive;
  });
  saveStoredUserActiveMap(activeMap);

  renderGlobalManageList();
  updateAllManageBadges();
  if (typeof onUsersUpdatedCallback === 'function') {
    onUsersUpdatedCallback(globalUserList);
  }
}
