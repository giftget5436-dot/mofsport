<?php
// index.php - หน้าสรุปผลภาพรวมทั้งหมดของการกดสุ่มแบ่งกลุ่ม 4 สี (Executive Summary & Realtime Dashboard)
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
?>
<!DOCTYPE html>
<html lang="th" class="h-full">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
  <meta http-equiv="Pragma" content="no-cache" />
  <meta http-equiv="Expires" content="0" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Executive Summary & Live Dashboard — สรุปผลแบ่งกลุ่ม 4 สี สป.กค.</title>
  
  <!-- Modern Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Prompt:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=Sarabun:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
  
  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>
  
  <!-- Font Awesome Pro 6.5.1 -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  
  <!-- SheetJS (Excel) -->
  <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
  <!-- Excel Importer & Exporter Modules -->
  <script src="excel_importer.js"></script>
  <script src="excel_exporter.js"></script>
  <!-- User Manager Module -->
  <script src="user_manager.js"></script>

  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            sans: ['Prompt', 'Sarabun', 'sans-serif'],
            heading: ['Prompt', 'sans-serif'],
            en: ['Plus Jakarta Sans', 'sans-serif']
          },
          colors: {
            brand: {
              50: '#f0f7ff',
              100: '#e0effe',
              200: '#bae0fd',
              500: '#0284c7',
              600: '#0369a1',
              700: '#075985',
              800: '#0c4a6e',
              900: '#0b1f48',
              950: '#06122c'
            }
          },
          boxShadow: {
            'glow-red': '0 10px 30px -10px rgba(229, 28, 35, 0.35)',
            'glow-pink': '0 10px 30px -10px rgba(233, 30, 99, 0.35)',
            'glow-green': '0 10px 30px -10px rgba(37, 155, 36, 0.35)',
            'glow-yellow': '0 10px 30px -10px rgba(255, 235, 59, 0.35)',
            'card': '0 1px 3px 0 rgba(0, 0, 0, 0.05), 0 10px 15px -5px rgba(0, 0, 0, 0.02)',
            'card-hover': '0 20px 25px -5px rgba(0, 0, 0, 0.06), 0 8px 10px -6px rgba(0, 0, 0, 0.04)'
          }
        }
      }
    }
  </script>

  <style>
    @media print {
      .no-print { display: none !important; }
      body { background: white !important; }
      .print-break { page-break-before: always; }
    }
    
    /* Background subtle mesh grid */
    .bg-mesh {
      background-color: #f8fafc;
      background-image: 
        radial-gradient(at 0% 0%, rgba(2, 132, 199, 0.07) 0px, transparent 50%),
        radial-gradient(at 100% 0%, rgba(139, 92, 246, 0.06) 0px, transparent 50%),
        radial-gradient(at 50% 100%, rgba(244, 63, 94, 0.04) 0px, transparent 50%);
    }

    /* Custom Slim Scrollbar */
    .custom-scrollbar::-webkit-scrollbar {
      width: 5px;
      height: 5px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
      background: transparent;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
      background: #cbd5e1;
      border-radius: 9999px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
      background: #94a3b8;
    }

    /* Glass effect */
    .glass-nav {
      background: rgba(255, 255, 255, 0.85);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
    }
    .glass-card {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(8px);
    }
  </style>

  <script>
    window.INITIAL_USER_DATA = <?php echo json_encode($userData, JSON_UNESCAPED_UNICODE); ?>;
    window.LATEST_SPIN_DATA = <?php echo json_encode($latestSpin, JSON_UNESCAPED_UNICODE); ?>;
    window.INITIAL_USER_AVATARS = <?php echo json_encode($userAvatars, JSON_UNESCAPED_UNICODE); ?>;
  </script>
</head>
<body class="bg-mesh min-h-screen text-slate-800 font-sans antialiased flex flex-col selection:bg-brand-500 selection:text-white">

  <!-- 1. Top Navigation Bar with Glassmorphism -->
  <header class="glass-nav border-b border-slate-200/80 sticky top-0 z-40 shadow-xs">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      
      <!-- บรรทัดที่ 1: โลโก้ และ ชื่อระบบ -->
      <div class="flex items-center justify-between py-3 border-b border-slate-100">
        <!-- Left: Logo & Title -->
        <div class="flex items-center gap-3.5">
          <div class="relative group">
            <div class="w-11 h-11 rounded-2xl bg-white p-1 shadow-md shadow-indigo-950/15 ring-2 ring-slate-200/80 transition transform group-hover:scale-105 flex items-center justify-center overflow-hidden">
              <img src="img/Ministry.webp" alt="ตรากระทรวงการคลัง" class="w-full h-full object-contain">
            </div>
            <span class="absolute -bottom-1 -right-1 w-3.5 h-3.5 rounded-full bg-emerald-500 ring-2 ring-white flex items-center justify-center text-[7px] text-white" title="สถานะพร้อมทำงาน">
              <i class="fa-solid fa-check"></i>
            </span>
          </div>

          <div>
            <div class="flex items-center gap-2 flex-wrap">
              <h1 class="text-base sm:text-lg font-extrabold text-slate-900 font-heading tracking-tight leading-snug">
                MOF Smart Random
              </h1>
            </div>
            <p class="text-xs text-slate-500 flex items-center gap-2 flex-wrap mt-0.5">
              <span class="font-medium text-slate-600"><i class="fa-solid fa-building-columns text-slate-400 mr-1"></i>สำนักงานปลัดกระทรวงการคลัง (สป.กค.)</span>
            </p>
          </div>
        </div>
      </div>

      <!-- บรรทัดที่ 2: เมนูสลับหน้าสุ่ม (Tabs) และชุดปุ่มจัดการ/ส่งออก (Action Buttons) -->
      <div class="flex flex-col lg:flex-row lg:items-center justify-between py-2.5 gap-2.5 no-print">
        
        <!-- Dropdown/Links to Random Modes -->
        <div class="inline-flex items-center p-1 bg-slate-100/90 rounded-2xl border border-slate-200/60 shadow-inner overflow-x-auto custom-scrollbar">
          <a href="index.php" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold text-brand-900 bg-white shadow-xs transition shrink-0">
            <i class="fa-solid fa-chart-pie text-[#1a237e]"></i>
            <span>หน้าสรุปผล</span>
          </a>
          <a href="all_random.php" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold text-sky-700 hover:bg-white hover:shadow-xs transition active:scale-95 shrink-0">
            <i class="fa-solid fa-shuffle text-sky-600"></i>
            <span>สุ่มแบบรวม</span>
          </a>
          <a href="division_random.php" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold text-purple-700 hover:bg-white hover:shadow-xs transition active:scale-95 shrink-0">
            <i class="fa-solid fa-sitemap text-purple-600"></i>
            <span>สุ่มแยกสำนัก &amp; ส่วน</span>
          </a>
          <a href="stage_random.php" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold text-indigo-700 hover:bg-white hover:shadow-xs transition active:scale-95 shrink-0">
            <i class="fa-solid fa-layer-group text-indigo-600"></i>
            <span>สุ่ม 5 ขั้น</span>
          </a>
        </div>

        <!-- Utility & Export Buttons -->
        <div class="flex items-center flex-wrap gap-2">
          
          <button onclick="openGlobalManageModal()" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 text-xs font-semibold shadow-xs transition active:scale-95 cursor-pointer" title="จัดการรายชื่อผู้เข้าร่วม">
            <i class="fa-solid fa-user-gear text-slate-500"></i>
            <span>จัดการรายชื่อ (<span class="user-manage-badge-count text-[#1a237e] font-bold">0</span>)</span>
          </button>

          <button onclick="openGlobalExcelModal()" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 text-xs font-semibold shadow-xs transition active:scale-95 cursor-pointer" title="นำเข้าไฟล์ Excel (.xlsx)">
            <i class="fa-solid fa-cloud-arrow-up text-xs text-emerald-600"></i>
            <span>อัปโหลด Excel</span>
          </button>

          <button onclick="refreshData()" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 text-xs font-semibold shadow-xs transition active:scale-95 cursor-pointer" title="รีเฟรชข้อมูลผลสุ่มล่าสุด">
            <i class="fa-solid fa-arrows-rotate text-xs text-slate-500"></i>
            <span>รีเฟรช</span>
          </button>

          <button onclick="exportToExcel()" class="inline-flex items-center gap-2 px-4 py-1.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white text-xs font-bold shadow-md shadow-emerald-600/20 transition active:scale-95 cursor-pointer">
            <i class="fa-solid fa-file-excel text-sm"></i>
            <span>ส่งออก Excel</span>
          </button>

        </div>

      </div>

    </div>
  </header>

  <!-- 2. Main Content Container -->
  <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

    <!-- Hero Stat Banner -->
    <div class="bg-gradient-to-r from-[#1a237e] via-[#283593] to-[#0d1442] rounded-3xl p-6 sm:p-7 text-white shadow-xl shadow-indigo-950/20 relative overflow-hidden flex flex-col md:flex-row md:items-center justify-between gap-6 border border-white/10">
      <div class="absolute -right-16 -top-16 w-64 h-64 bg-indigo-400/15 rounded-full blur-3xl pointer-events-none"></div>
      <div class="absolute right-40 -bottom-20 w-64 h-64 bg-blue-500/15 rounded-full blur-3xl pointer-events-none"></div>
      
      <div class="relative z-10 space-y-1.5 max-w-2xl">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 backdrop-blur-md text-xs font-medium text-indigo-100 border border-white/10">
          <i class="fa-solid fa-trophy text-amber-300"></i>
          โครงการสัมมนาและแข่งขันกีฬาสัมพันธ์ สป.กค.
        </div>
        <h2 class="text-2xl sm:text-3xl font-extrabold font-heading tracking-tight text-white">
          ภาพรวมผลการแบ่งสี <span class="text-transparent bg-clip-text bg-gradient-to-r from-amber-300 via-sky-200 to-teal-200">4 สีอย่างสมดุล</span>
        </h2>
        <p class="text-xs sm:text-sm text-indigo-100/90 leading-relaxed font-light">
          เกลี่ยอายุเฉลี่ย (Age Balancing) ควบคู่กับสัดส่วนเพศและกระจายบุคลากรจากแต่ละสำนัก/ศูนย์/กลุ่มอย่างเท่าเทียม
        </p>
      </div>

      <div class="relative z-10 flex items-center gap-3 sm:gap-4 shrink-0 bg-white/10 backdrop-blur-md p-3 sm:p-4 rounded-2xl border border-white/10">
        <div class="text-center px-3 border-r border-white/15">
          <div class="text-[10px] uppercase font-bold text-indigo-200 tracking-wider">สุ่มแล้วทั้งหมด</div>
          <div class="text-2xl sm:text-3xl font-extrabold font-en text-white mt-0.5" id="heroAssignedCount">0</div>
          <div class="text-[10px] text-emerald-300 font-medium">คนพร้อมแข่งขัน</div>
        </div>
        <div class="text-center px-3 border-r border-white/15">
          <div class="text-[10px] uppercase font-bold text-indigo-200 tracking-wider">อายุเฉลี่ยรวม</div>
          <div class="text-2xl sm:text-3xl font-extrabold font-en text-amber-300 mt-0.5" id="heroAvgAge">38.7</div>
          <div class="text-[10px] text-indigo-100">ปี (ทุกสีใกล้เคียงกัน)</div>
        </div>
        <div class="text-center px-3">
          <div class="text-[10px] uppercase font-bold text-indigo-200 tracking-wider">สำนัก / กอง</div>
          <div class="text-2xl sm:text-3xl font-extrabold font-en text-teal-300 mt-0.5" id="heroDivCount">12</div>
          <div class="text-[10px] text-indigo-100">หน่วยงาน</div>
        </div>
      </div>
    </div>

    <!-- 3. Colorful KPI Cards for 4 Teams (Ruby, Sapphire, Emerald, Topaz) -->
    <section>
      <div class="flex items-center justify-between mb-3 px-1">
        <h3 class="text-sm sm:text-base font-bold text-slate-900 font-heading flex items-center gap-2">
          <i class="fa-solid fa-gem text-brand-600"></i>
          สถานะความสมดุล 4 สี (Team Overview Cards)
        </h3>
        <span class="text-xs text-slate-400 font-medium font-en" id="targetInfoText">Target: ~83 คน/สี</span>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4" id="teamKpiCards">
        <!-- Render ผ่าน JS -->
      </div>
    </section>

    <!-- 4. Matrix Breakdown per Division -->
    <section class="glass-card rounded-3xl shadow-card border border-slate-200/80 overflow-hidden transition duration-200 hover:shadow-card-hover">
      
      <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white/70">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-lg border border-indigo-100 shadow-2xs">
            <i class="fa-solid fa-sitemap"></i>
          </div>
          <div>
            <h3 class="font-bold text-slate-900 text-sm sm:text-base font-heading flex items-center gap-2">
              การกระจายตัวตามสำนัก / ศูนย์ / กลุ่ม (Division Matrix)
            </h3>
            <p class="text-xs text-slate-500 mt-0.5">
              แสดงจำนวนคนที่กระจายลงในแต่ละสี เพื่อยืนยันว่าบุคลากรแต่ละหน่วยงานกระจายครบทุกสีอย่างยุติธรรม
            </p>
          </div>
        </div>

        <div class="flex items-center gap-2">
          <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-50 border border-slate-200 text-xs font-semibold text-slate-600">
            <i class="fa-solid fa-chart-simple text-indigo-500"></i>
            <span>รวม <b id="divCountBadge" class="text-indigo-600 font-en">0</b> หน่วยงาน</span>
          </span>
        </div>
      </div>

      <div class="overflow-x-auto custom-scrollbar">
        <table class="w-full text-left text-xs sm:text-sm text-slate-700">
          <thead class="bg-slate-50/80 text-slate-600 font-bold text-[11px] uppercase tracking-wider border-b border-slate-200/80">
            <tr>
              <th class="py-3.5 px-5">สำนัก / ศูนย์ / กลุ่ม</th>
              <th class="py-3.5 px-4 text-center">จำนวนคนทั้งหมด</th>
              <th class="py-3.5 px-4 text-center text-[#e51c23] bg-red-50/50">สีแดง</th>
              <th class="py-3.5 px-4 text-center text-[#e91e63] bg-pink-50/50">สีชมพู</th>
              <th class="py-3.5 px-4 text-center text-[#259b24] bg-green-50/50">สีเขียว</th>
              <th class="py-3.5 px-4 text-center text-amber-700 bg-yellow-50/50">สีเหลือง</th>
              <th class="py-3.5 px-4 text-center">ระดับความสมดุล</th>
            </tr>
          </thead>
          <tbody id="matrixTableBody" class="divide-y divide-slate-100">
            <!-- Render Matrix rows through JavaScript -->
          </tbody>
        </table>
      </div>
    </section>

    <!-- 5. Member Display Section (Columns 4 สี / Table View) -->
    <section class="space-y-4">
      
      <!-- View Mode Toolbar -->
      <div class="glass-card rounded-2xl p-3 border border-slate-200/80 shadow-xs flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 no-print">
        
        <!-- Toggle Tabs -->
        <div class="inline-flex p-1 bg-slate-100/90 rounded-xl text-xs font-semibold shadow-inner">
          <button id="btnViewColumns" onclick="switchView('columns')" class="px-4 py-2 rounded-lg bg-white text-brand-900 shadow-xs font-bold transition flex items-center gap-2 cursor-pointer">
            <i class="fa-solid fa-table-columns text-brand-600"></i>
            <span>คอลัมน์การ์ด 4 สี</span>
          </button>
          <button id="btnViewTable" onclick="switchView('table')" class="px-4 py-2 rounded-lg text-slate-600 hover:text-slate-900 transition flex items-center gap-2 cursor-pointer">
            <i class="fa-solid fa-list text-slate-400"></i>
            <span>ตารางรายชื่อทั้งหมด</span>
          </button>
        </div>

        <!-- Filter & Search -->
        <div class="flex items-center gap-2.5">
          <div class="relative flex-1 sm:w-72">
            <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
            <input type="text" id="searchInput" oninput="renderViews()" placeholder="ค้นหาชื่อ, ตำแหน่ง, สำนัก, ส่วน..." class="w-full pl-9 pr-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs sm:text-sm focus:ring-2 focus:ring-brand-500 focus:bg-white focus:outline-none transition shadow-2xs">
          </div>
          
          <select id="teamFilter" onchange="renderViews()" class="px-3.5 py-2 border border-slate-200 rounded-xl text-xs sm:text-sm bg-slate-50 text-slate-700 focus:outline-none focus:ring-2 focus:ring-brand-500 shadow-2xs font-medium cursor-pointer">
            <option value="all">ทุกสี (4 สี)</option>
            <option value="0">เฉพาะ สีแดง</option>
            <option value="1">เฉพาะ สีชมพู</option>
            <option value="2">เฉพาะ สีเขียว</option>
            <option value="3">เฉพาะ สีเหลือง</option>
          </select>
        </div>

      </div>

      <!-- 5.1 Columns 4 สี -->
      <div id="viewColumns" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Render Columns ผ่าน JS -->
      </div>

      <!-- 5.2 Table View รายชื่อทั้งหมด -->
      <div id="viewTable" class="hidden glass-card rounded-3xl shadow-card border border-slate-200/80 overflow-hidden">
        <div class="overflow-x-auto custom-scrollbar">
          <table class="w-full text-left text-xs sm:text-sm text-slate-700">
            <thead class="bg-slate-50/80 text-slate-600 font-bold text-[11px] uppercase tracking-wider border-b border-slate-200">
              <tr>
                <th class="py-3.5 px-4 text-center">ลำดับ</th>
                <th class="py-3.5 px-3 text-center">รูปถ่าย</th>
                <th class="py-3.5 px-4">สีที่สังกัด</th>
                <th class="py-3.5 px-4">ชื่อ - นามสกุล</th>
                <th class="py-3.5 px-3 text-center">เพศ</th>
                <th class="py-3.5 px-3 text-center">อายุ</th>
                <th class="py-3.5 px-4">ตำแหน่ง</th>
                <th class="py-3.5 px-4">สำนัก / กอง (Division)</th>
                <th class="py-3.5 px-4">ส่วนงานย่อย (Section)</th>
              </tr>
            </thead>
            <tbody id="memberTableBody" class="divide-y divide-slate-100">
              <!-- Render Table rows ผ่าน JS -->
            </tbody>
          </table>
        </div>
      </div>

    </section>

  </main>

  <!-- Modern Footer -->
  <footer class="bg-white border-t border-slate-200/80 py-6 mt-12 w-full no-print">
    <div class="w-full px-4 text-center text-xs text-slate-500">
      <span>ระบบสุ่มแบ่งกลุ่ม 4 สี &bull; สำนักงานปลัดกระทรวงการคลัง (สป.กค.) กระทรวงการคลัง</span>
    </div>
  </footer>

  <!-- Core JavaScript Logic -->
  <script>
    const TEAMS = [
      { 
        name: 'สีแดง', 
        short: 'สีแดง',
        color: '#e51c23', 
        bg: 'bg-red-50', 
        border: 'border-[#e51c23]/40', 
        ring: 'ring-[#e51c23]',
        text: 'text-[#e51c23]', 
        badge: 'bg-[#e51c23] text-white', 
        icon: 'fa-fire',
        shadow: 'hover:shadow-glow-red',
        gradient: 'from-[#e51c23] via-[#f44336] to-[#d32f2f]'
      },
      { 
        name: 'สีชมพู', 
        short: 'สีชมพู',
        color: '#e91e63', 
        bg: 'bg-pink-50', 
        border: 'border-[#e91e63]/40', 
        ring: 'ring-[#e91e63]',
        text: 'text-[#e91e63]', 
        badge: 'bg-[#e91e63] text-white', 
        icon: 'fa-heart',
        shadow: 'hover:shadow-glow-pink',
        gradient: 'from-[#e91e63] via-[#f06292] to-[#c2185b]'
      },
      { 
        name: 'สีเขียว', 
        short: 'สีเขียว',
        color: '#259b24', 
        bg: 'bg-green-50', 
        border: 'border-[#259b24]/40', 
        ring: 'ring-[#259b24]',
        text: 'text-[#259b24]', 
        badge: 'bg-[#259b24] text-white', 
        icon: 'fa-leaf',
        shadow: 'hover:shadow-glow-green',
        gradient: 'from-[#259b24] via-[#4caf50] to-[#2e7d32]'
      },
      { 
        name: 'สีเหลือง', 
        short: 'สีเหลือง',
        color: '#ffeb3b', 
        bg: 'bg-yellow-50', 
        border: 'border-[#fbc02d]/50', 
        ring: 'ring-[#fbc02d]',
        text: 'text-amber-800', 
        badge: 'bg-[#fbc02d] text-slate-900', 
        icon: 'fa-sun',
        shadow: 'hover:shadow-glow-yellow',
        gradient: 'from-[#ffeb3b] via-[#fbc02d] to-[#f57f17]'
      }
    ];

    function detectGender(u) {
      if (!u) return 'ไม่ระบุ';
      const cur = (u.gender || u.GENDER || '').trim();
      if (cur === 'ชาย' || cur === 'หญิง') return cur;
      const pre = (u.prename_th || '').trim();
      const name = (u.name || `${u.FIRSTNAME || ''} ${u.LASTNAME || ''}`).trim();
      const text = `${pre} ${name}`.trim();
      if (/^(นาย|ว่าที่ร้อยตรี|ว่าที่ร้อยโท|ร้อยตรี|ร้อยโท|ร้อยตำรวจโท|พลฯ)/u.test(text) || text.startsWith('นาย')) {
        return 'ชาย';
      }
      if (/^(นางสาว|นาง|ว่าที่ร้อยตรีหญิง|ว่าที่ร้อยโทหญิง|ว่าที่ร้อยตำรวจโทหญิง)/u.test(text) || text.startsWith('นางสาว') || text.startsWith('นาง')) {
        return 'หญิง';
      }
      if (text.includes('นาย ') || text.includes('นาย')) return 'ชาย';
      if (text.includes('นางสาว ') || text.includes('นาง ') || text.includes('นางสาว') || text.includes('นาง')) return 'หญิง';
      return 'ไม่ระบุ';
    }

    let allUsers = [];
    let teamMembers = [[], [], [], []];
    let currentView = 'columns';
    let userAvatars = window.INITIAL_USER_AVATARS || {};

    function initData() {
      // โหลดอวาตาร์จาก LocalStorage เสริม
      try {
        const local = localStorage.getItem('sport_user_avatars');
        if (local) {
          const parsed = JSON.parse(local);
          userAvatars = { ...userAvatars, ...parsed };
        }
      } catch (e) {}

      // โหลดผู้ใช้ทั้งหมด
      if (window.INITIAL_USER_DATA && Array.isArray(window.INITIAL_USER_DATA)) {
        allUsers = window.INITIAL_USER_DATA.map((u, i) => {
          let age = u.age ? parseInt(u.age) : (u.AGE ? parseInt(u.AGE) : 0);
          return {
            seq: u.seq || (i + 1),
            name: u.name || `${u.FIRSTNAME || ''} ${u.LASTNAME || ''}`.trim(),
            gender: detectGender(u),
            age: age || 0,
            position: u.position || u.POSITION || '',
            division: u.division || u.DEPARTMENT || 'ไม่ระบุสำนัก/กอง',
            section: u.section || u.SUBDEPARTMENT || '',
            assignedTeam: null,
            assignedTag: ''
          };
        });
      }

      loadLatestSpin();
      if (typeof initGlobalUserManager === 'function') {
        initGlobalUserManager(allUsers, (updated) => {
          // ซิงค์สถานะ active ให้ allUsers
          const activeMap = typeof getStoredUserActiveMap === 'function' ? getStoredUserActiveMap() : {};
          allUsers.forEach(u => {
            if (typeof isUserActiveInMap === 'function') {
              u.active = isUserActiveInMap(u, activeMap);
            }
          });

          // ตัดผู้ใช้ที่ไม่ active ออกจากผลสุ่มที่แสดงผลอยู่
          if (Array.isArray(teamMembers)) {
            if (typeof filterTeamsByActiveUsers === 'function') {
              teamMembers = filterTeamsByActiveUsers(teamMembers);
            } else {
              teamMembers = teamMembers.map(t => (t || []).filter(u => u.active !== false));
            }
          }

          // รีเฟรชและอัปเดตจำนวนผู้ที่ Active
          renderAll();
        });
      }
      renderAll();
    }

    function loadLatestSpin() {
      teamMembers = [[], [], [], []];
      let spinData = window.LATEST_SPIN_DATA;

      try {
        const localSpin = localStorage.getItem('sport_latest_spin');
        if (localSpin) {
          const parsed = JSON.parse(localSpin);
          if (parsed && parsed.teams) {
            spinData = parsed;
          }
        }
      } catch (e) {}

      const sourceEl = document.getElementById('syncSourceLabel');

      if (spinData && spinData.teams && Array.isArray(spinData.teams)) {
        let count = 0;
        spinData.teams.forEach((tArr, tIdx) => {
          if (Array.isArray(tArr)) {
            tArr.forEach(u => {
              u.assignedTeam = tIdx;
              u.gender = detectGender(u);
              teamMembers[tIdx].push(u);
              count++;
            });
          }
        });

        const timeStr = spinData.updated_at || spinData.timestamp || '';
        let srcName = 'การสุ่มล่าสุด';
        if (spinData.source === 'division_random_seminar' || spinData.source === 'division_random') {
          srcName = 'สุ่มแยกสำนัก & ส่วนงาน';
        } else if (spinData.source === 'stage_random') {
          srcName = 'สุ่ม 5 ลำดับขั้น';
        } else if (spinData.source === 'quick_spin') {
          srcName = 'สุ่มแบบรวมทั้งหมด';
        }

        if (sourceEl) {
          sourceEl.innerHTML = `<span class="inline-flex items-center gap-1.5 text-emerald-700 font-semibold"><i class="fa-solid fa-circle-check text-emerald-500"></i> ซิงค์ตรงกับ: <b class="text-slate-800">${srcName}</b> ${timeStr ? `(${timeStr})` : ''} <span class="bg-emerald-100 text-emerald-800 text-[10px] px-1.5 py-0.2 rounded font-en">${count} คน</span></span>`;
        }
        document.getElementById('heroAssignedCount').textContent = count;
      } else {
        if (sourceEl) {
          sourceEl.innerHTML = `<span class="inline-flex items-center gap-1 text-amber-600 font-semibold"><i class="fa-solid fa-triangle-exclamation"></i> ยังไม่มีข้อมูลผลสุ่มล่าสุด</span>`;
        }
      }
    }

    function refreshData() {
      fetch('save_state.php?t=' + Date.now())
        .then(res => res.json())
        .then(data => {
          if (data && data.teams) {
            window.LATEST_SPIN_DATA = data;
            loadLatestSpin();
            renderAll();
          } else {
            alert('ยังไม่มีข้อมูลผลการสุ่มล่าสุดที่บันทึกไว้');
          }
        })
        .catch(err => {
          location.reload();
        });
    }

    function renderAll() {
      renderKpis();
      renderMatrix();
      renderViews();
    }

    // 1. KPI Cards 4 สี
    function renderKpis() {
      const container = document.getElementById('teamKpiCards');
      container.innerHTML = '';
      let totalAll = 0;
      let allAges = [];

      for (let i = 0; i < 4; i++) {
        const team = teamMembers[i] || [];
        totalAll += team.length;

        const males = team.filter(u => u.gender === 'ชาย').length;
        const females = team.filter(u => u.gender === 'หญิง').length;

        const validAges = team.map(u => parseInt(u.age)).filter(a => a && a > 0);
        allAges = allAges.concat(validAges);

        let avgAge = 0;
        let minAge = 0;
        let maxAge = 0;
        if (validAges.length > 0) {
          const sum = validAges.reduce((a, b) => a + b, 0);
          avgAge = (sum / validAges.length).toFixed(1);
          minAge = Math.min(...validAges);
          maxAge = Math.max(...validAges);
        }

        const tMeta = TEAMS[i];

        const card = document.createElement('div');
        card.className = `glass-card rounded-3xl p-5 border-2 ${tMeta.border} shadow-card ${tMeta.shadow} relative overflow-hidden transition-all duration-300 transform hover:-translate-y-1 flex flex-col justify-between`;
        
        card.innerHTML = `
          <!-- Gradient Top Bar Accent -->
          <div class="h-1.5 bg-gradient-to-r ${tMeta.gradient} absolute top-0 left-0 right-0"></div>

          <div>
            <!-- Header -->
            <div class="flex items-center justify-between mb-3 mt-1">
              <span class="inline-flex items-center gap-2 text-xs font-bold ${tMeta.text} uppercase tracking-wider">
                <span class="w-3 h-3 rounded-full ${tMeta.badge.split(' ')[0]} shadow-xs"></span>
                ${tMeta.name}
              </span>
              <span class="w-8 h-8 rounded-xl ${tMeta.bg} ${tMeta.text} flex items-center justify-center text-xs shadow-2xs">
                <i class="fa-solid ${tMeta.icon}"></i>
              </span>
            </div>

            <!-- Total Count & Average Age -->
            <div class="flex items-baseline justify-between mt-1 pt-1">
              <div>
                <span class="text-3xl sm:text-4xl font-extrabold text-slate-900 font-en tracking-tight">${team.length}</span>
                <span class="text-xs text-slate-400 font-medium ml-1">คน</span>
              </div>
              <div class="text-right">
                <div class="text-[10px] text-slate-400 font-medium">อายุเฉลี่ย</div>
                <div class="text-lg font-extrabold ${tMeta.text} font-en leading-tight">
                  ${avgAge > 0 ? avgAge : '-'} <span class="text-[10px] font-normal text-slate-400">ปี</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Bottom Breakdown stats -->
          <div class="mt-4 pt-3 border-t border-slate-100/90 text-xs text-slate-600 space-y-1.5">
            <div class="flex justify-between items-center text-[11px]">
              <span class="text-slate-500"><i class="fa-solid fa-venus-mars text-slate-400 mr-1"></i>สัดส่วนเพศ:</span>
              <span class="font-semibold text-slate-800">
                ชาย <b class="font-en text-blue-600">${males}</b> &bull; หญิง <b class="font-en text-rose-500">${females}</b>
              </span>
            </div>
            <div class="flex justify-between items-center text-[11px]">
              <span class="text-slate-500"><i class="fa-solid fa-hourglass-half text-slate-400 mr-1"></i>ช่วงอายุ:</span>
              <span class="font-bold text-slate-700 font-en">
                ${validAges.length > 0 ? `${minAge} - ${maxAge} ปี` : '-'}
              </span>
            </div>
          </div>
        `;
        container.appendChild(card);
      }

      if (allAges.length > 0) {
        const grandAvg = (allAges.reduce((a, b) => a + b, 0) / allAges.length).toFixed(1);
        document.getElementById('heroAvgAge').textContent = grandAvg;
      }
    }

    // 2. Matrix Table
    function renderMatrix() {
      const tbody = document.getElementById('matrixTableBody');
      tbody.innerHTML = '';

      const divMap = {};

      for (let t = 0; t < 4; t++) {
        teamMembers[t].forEach(u => {
          const div = u.division || 'ผู้บริหารระดับสูง';
          if (!divMap[div]) {
            divMap[div] = { total: 0, counts: [0, 0, 0, 0] };
          }
          divMap[div].total++;
          divMap[div].counts[t]++;
        });
      }

      const divNames = Object.keys(divMap).sort();
      document.getElementById('divCountBadge').textContent = divNames.length;
      document.getElementById('heroDivCount').textContent = divNames.length;

      if (divNames.length === 0) {
        tbody.innerHTML = `<tr><td colspan="7" class="text-center py-10 text-slate-400 italic">ยังไม่มีข้อมูลการสุ่ม</td></tr>`;
        return;
      }

      divNames.forEach((div, rowIdx) => {
        const item = divMap[div];
        const tr = document.createElement('tr');
        tr.className = 'hover:bg-slate-50/80 transition group';

        const max = Math.max(...item.counts);
        const min = Math.min(...item.counts);
        const diff = max - min;
        let badgeHtml = '';
        if (diff <= 1) {
          badgeHtml = '<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200/80 shadow-2xs"><i class="fa-solid fa-circle-check text-[9px] text-emerald-500"></i> สมดุลยอดเยี่ยม</span>';
        } else if (diff <= 2) {
          badgeHtml = '<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200/80 shadow-2xs"><i class="fa-solid fa-check text-[9px] text-blue-500"></i> สมดุลดี</span>';
        } else {
          badgeHtml = '<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200/80 shadow-2xs"><i class="fa-solid fa-minus text-[9px] text-amber-500"></i> กระจายตัว</span>';
        }

        tr.innerHTML = `
          <td class="py-3.5 px-5 font-bold text-slate-900 group-hover:text-brand-700 transition flex items-center gap-2">
            <span class="w-6 h-6 rounded-lg bg-slate-100 text-slate-500 flex items-center justify-center text-[10px] font-en font-bold">${rowIdx + 1}</span>
            <span>${div}</span>
          </td>
          <td class="py-3.5 px-4 text-center font-extrabold text-slate-900 font-en text-sm">${item.total}</td>
          <td class="py-3.5 px-4 text-center font-bold text-[#e51c23] bg-red-50/30 font-en">${item.counts[0]}</td>
          <td class="py-3.5 px-4 text-center font-bold text-[#e91e63] bg-pink-50/30 font-en">${item.counts[1]}</td>
          <td class="py-3.5 px-4 text-center font-bold text-[#259b24] bg-green-50/30 font-en">${item.counts[2]}</td>
          <td class="py-3.5 px-4 text-center font-bold text-amber-700 bg-yellow-50/30 font-en">${item.counts[3]}</td>
          <td class="py-3.5 px-4 text-center">${badgeHtml}</td>
        `;
        tbody.appendChild(tr);
      });
    }

    // 3. Views (Columns & Table)
    function switchView(view) {
      currentView = view;
      const btnCol = document.getElementById('btnViewColumns');
      const btnTab = document.getElementById('btnViewTable');
      const vCol = document.getElementById('viewColumns');
      const vTab = document.getElementById('viewTable');

      if (view === 'columns') {
        btnCol.className = 'px-4 py-2 rounded-lg bg-white text-brand-900 shadow-xs font-bold transition flex items-center gap-2 cursor-pointer';
        btnTab.className = 'px-4 py-2 rounded-lg text-slate-600 hover:text-slate-900 transition flex items-center gap-2 cursor-pointer';
        vCol.classList.remove('hidden');
        vTab.classList.add('hidden');
      } else {
        btnTab.className = 'px-4 py-2 rounded-lg bg-white text-brand-900 shadow-xs font-bold transition flex items-center gap-2 cursor-pointer';
        btnCol.className = 'px-4 py-2 rounded-lg text-slate-600 hover:text-slate-900 transition flex items-center gap-2 cursor-pointer';
        vTab.classList.remove('hidden');
        vCol.classList.add('hidden');
      }
      renderViews();
    }

    function renderViews() {
      const q = (document.getElementById('searchInput').value || '').trim().toLowerCase();
      const filterT = document.getElementById('teamFilter').value;

      if (currentView === 'columns') {
        renderColumnsView(q, filterT);
      } else {
        renderTableView(q, filterT);
      }
    }

    function renderColumnsView(q, filterT) {
      const container = document.getElementById('viewColumns');
      container.innerHTML = '';

      for (let t = 0; t < 4; t++) {
        if (filterT !== 'all' && parseInt(filterT) !== t) continue;

        const team = teamMembers[t] || [];
        const filtered = team.filter(u => {
          if (!q) return true;
          return (u.name && u.name.toLowerCase().includes(q)) ||
                 (u.position && u.position.toLowerCase().includes(q)) ||
                 (u.division && u.division.toLowerCase().includes(q)) ||
                 (u.section && u.section.toLowerCase().includes(q));
        });

        const tMeta = TEAMS[t];
        const col = document.createElement('div');
        col.className = `glass-card rounded-3xl shadow-card border-2 ${tMeta.border} p-4 flex flex-col h-[740px] relative overflow-hidden transition-all duration-200 hover:shadow-card-hover`;
        
        let membersListHtml = '';
        if (filtered.length === 0) {
          membersListHtml = `<div class="p-8 text-center text-slate-400 text-xs italic">ไม่พบรายชื่อตามคำค้นหา</div>`;
        } else {
          membersListHtml = filtered.map((u, idx) => {
            const avatarUrl = userAvatars[u.seq] || userAvatars[u.name] || '';
            return `
              <div class="p-2.5 rounded-2xl border border-slate-100 bg-white/80 hover:bg-white hover:border-slate-300 hover:shadow-xs transition duration-150 flex items-center gap-2.5 group">
                <div class="w-8 h-8 rounded-xl ring-1 ring-slate-200 overflow-hidden bg-slate-50 shrink-0 flex items-center justify-center text-slate-400 text-xs shadow-2xs group-hover:scale-105 transition">
                  ${avatarUrl ? `<img src="${avatarUrl}" class="w-full h-full object-cover">` : `<i class="fa-solid fa-user"></i>`}
                </div>
                <div class="min-w-0 flex-1">
                  <div class="font-bold text-xs text-slate-900 truncate flex items-center justify-between gap-1">
                    <span class="truncate group-hover:text-brand-700 transition">${u.name}</span>
                    ${u.age ? `<span class="text-[10px] font-bold text-slate-600 font-en shrink-0 bg-slate-100 px-1.5 py-0.2 rounded-md">${u.age} ปี</span>` : ''}
                  </div>
                  <div class="text-[10px] text-slate-500 truncate mt-0.5">${u.position || u.division || ''}</div>
                  <div class="text-[9px] text-slate-400 truncate">${u.division || ''} ${u.section ? `&bull; ${u.section}` : ''}</div>
                </div>
              </div>
            `;
          }).join('');
        }

        col.innerHTML = `
          <!-- Gradient Top Bar Accent -->
          <div class="h-1.5 bg-gradient-to-r ${tMeta.gradient} absolute top-0 left-0 right-0"></div>

          <!-- Top Team Header -->
          <div class="flex items-center justify-between pb-3 mb-3 border-b border-slate-100 mt-1">
            <div class="flex items-center gap-2">
              <span class="w-3.5 h-3.5 rounded-full ${tMeta.badge.split(' ')[0]} shadow-xs"></span>
              <h4 class="font-bold text-sm text-slate-900 font-heading">${tMeta.name}</h4>
            </div>
            <span class="text-xs font-bold ${tMeta.text} ${tMeta.bg} border ${tMeta.border} px-2.5 py-0.5 rounded-full font-en shadow-2xs">${filtered.length} คน</span>
          </div>
          <!-- Scrollable Member List -->
          <div class="flex-1 overflow-y-auto space-y-2 pr-1 custom-scrollbar">
            ${membersListHtml}
          </div>
        `;
        container.appendChild(col);
      }
    }

    function renderTableView(q, filterT) {
      const tbody = document.getElementById('memberTableBody');
      tbody.innerHTML = '';

      let list = [];
      for (let t = 0; t < 4; t++) {
        if (filterT !== 'all' && parseInt(filterT) !== t) continue;
        teamMembers[t].forEach(u => {
          list.push({ ...u, teamIndex: t });
        });
      }

      const filtered = list.filter(u => {
        if (!q) return true;
        return (u.name && u.name.toLowerCase().includes(q)) ||
               (u.position && u.position.toLowerCase().includes(q)) ||
               (u.division && u.division.toLowerCase().includes(q)) ||
               (u.section && u.section.toLowerCase().includes(q));
      });

      if (filtered.length === 0) {
        tbody.innerHTML = `<tr><td colspan="9" class="text-center py-10 text-slate-400 italic">ไม่พบรายชื่อตามเงื่อนไขที่กำหนด</td></tr>`;
        return;
      }

      filtered.forEach((u, idx) => {
        const tMeta = TEAMS[u.teamIndex];
        const avatarUrl = userAvatars[u.seq] || userAvatars[u.name] || '';
        const tr = document.createElement('tr');
        tr.className = 'hover:bg-slate-50/80 transition group';
        tr.innerHTML = `
          <td class="py-2.5 px-4 text-center font-bold text-slate-400 font-en text-xs">${idx + 1}</td>
          <td class="py-2.5 px-3 text-center">
            <div class="w-8 h-8 rounded-xl ring-1 ring-slate-200 overflow-hidden mx-auto bg-slate-50 flex items-center justify-center text-slate-400 text-xs shadow-2xs group-hover:scale-105 transition">
              ${avatarUrl ? `<img src="${avatarUrl}" class="w-full h-full object-cover">` : `<i class="fa-solid fa-user"></i>`}
            </div>
          </td>
          <td class="py-2.5 px-4">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold ${tMeta.bg} ${tMeta.text} border ${tMeta.border} shadow-2xs">
              <span class="w-2 h-2 rounded-full ${tMeta.badge.split(' ')[0]}"></span>
              ${tMeta.short}
            </span>
          </td>
          <td class="py-2.5 px-4 font-bold text-slate-900 group-hover:text-brand-700 transition">${u.name}</td>
          <td class="py-2.5 px-3 text-center text-slate-600 text-xs">${u.gender || '-'}</td>
          <td class="py-2.5 px-3 text-center font-bold text-slate-700 font-en text-xs">${u.age ? `${u.age} ปี` : '-'}</td>
          <td class="py-2.5 px-4 text-slate-600 text-xs">${u.position || '-'}</td>
          <td class="py-2.5 px-4 text-slate-700 font-medium text-xs">${u.division || '-'}</td>
          <td class="py-2.5 px-4 text-slate-500 text-xs">${u.section || '-'}</td>
        `;
        tbody.appendChild(tr);
      });
    }

    // Export to Excel (ตามรูปแบบไฟล์ต้นแบบ "ตัวอย่างรายชื่อสัมมนา ซ้อมสุ่มสี.xlsx")
    function exportToExcel() {
      let hasData = false;
      for (let t = 0; t < 4; t++) {
        if (teamMembers[t] && teamMembers[t].length > 0) hasData = true;
      }
      if (!hasData) {
        alert('ยังไม่มีข้อมูลผลการสุ่ม');
        return;
      }

      if (typeof exportSportResultExcel === 'function') {
        exportSportResultExcel(teamMembers, {
          fileName: `สรุปผลภาพรวมการแบ่งกลุ่ม4สี_${new Date().toISOString().slice(0, 10)}.xlsx`,
          teamNames: TEAMS.map(t => t.name)
        });
      }
    }

    window.addEventListener('DOMContentLoaded', () => {
      initData();
    });
  </script>
</body>
</html>
