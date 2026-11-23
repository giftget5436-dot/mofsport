<?php
// division_random.php - ระบบสุ่มแบ่งกลุ่ม 4 สี แยกตามกรอบสำนัก/ศูนย์/กลุ่ม และส่วนงาน พร้อมเกลี่ยอายุเฉลี่ยเท่าเทียมกันทุกสี
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
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
  <meta http-equiv="Pragma" content="no-cache" />
  <meta http-equiv="Expires" content="0" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ระบบสุ่มแบ่งกลุ่ม 4 สี (เกลี่ยอายุเฉลี่ย & โครงสร้างสำนัก/ส่วนงาน) - สป.กค.</title>
  
  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=Prompt:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=Sarabun:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
  
  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>
  
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  
  <!-- Canvas Confetti -->
  <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>

  <!-- SheetJS -->
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
            en: ['Plus Jakarta Sans', 'sans-serif'],
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
            'glow-rose': '0 10px 30px -10px rgba(244, 63, 94, 0.3)',
            'glow-blue': '0 10px 30px -10px rgba(59, 130, 246, 0.3)',
            'glow-emerald': '0 10px 30px -10px rgba(16, 185, 129, 0.3)',
            'glow-amber': '0 10px 30px -10px rgba(245, 158, 11, 0.3)',
            'card': '0 1px 3px 0 rgba(0, 0, 0, 0.05), 0 10px 15px -5px rgba(0, 0, 0, 0.02)',
            'card-hover': '0 20px 25px -5px rgba(0, 0, 0, 0.06), 0 8px 10px -6px rgba(0, 0, 0, 0.04)'
          }
        }
      }
    }
  </script>

  <style>
    body { font-family: 'Prompt', 'Sarabun', sans-serif; }
    .font-en { font-family: 'Plus Jakarta Sans', sans-serif; }
    .bg-mesh {
      background-color: #f8fafc;
      background-image: 
        radial-gradient(at 0% 0%, rgba(2, 132, 199, 0.07) 0px, transparent 50%),
        radial-gradient(at 100% 0%, rgba(139, 92, 246, 0.06) 0px, transparent 50%),
        radial-gradient(at 50% 100%, rgba(244, 63, 94, 0.04) 0px, transparent 50%);
    }
    .custom-scrollbar::-webkit-scrollbar { width: 5px; height: 5px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 9999px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    
    .glass-nav {
      background: rgba(255, 255, 255, 0.85);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
    }
    .glass-card {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(8px);
    }

    @keyframes glow-pulse {
      0%, 100% { box-shadow: 0 0 15px rgba(99, 102, 241, 0.35); }
      50% { box-shadow: 0 0 28px rgba(99, 102, 241, 0.7); }
    }
    .btn-glow {
      animation: glow-pulse 2.5s infinite;
    }

    @keyframes bounce-gentle {
      0%, 100% { transform: translateY(0); }
      50% { transform: translateY(-4px); }
    }
    .animate-bounce-gentle {
      animation: bounce-gentle 1.8s infinite;
    }

    @media print {
      body { background: white !important; }
      .no-print { display: none !important; }
      .print-break { page-break-before: always; }
      .shadow-sm, .shadow-md, .shadow-lg, .shadow-xl { box-shadow: none !important; border: 1px solid #cbd5e1 !important; }
    }
  </style>

  <script>
    window.INITIAL_USER_DATA = <?php echo json_encode($userData, JSON_UNESCAPED_UNICODE); ?>;
    window.LATEST_SPIN_DATA = <?php echo json_encode($latestSpin, JSON_UNESCAPED_UNICODE); ?>;
    window.INITIAL_USER_AVATARS = <?php echo json_encode($userAvatars, JSON_UNESCAPED_UNICODE); ?>;
  </script>
</head>
<body class="bg-mesh min-h-screen text-slate-800 flex flex-col antialiased selection:bg-purple-500 selection:text-white">

  <!-- Header หลักแบบ Glassmorphism -->
  <header class="sticky top-0 z-40 glass-nav border-b border-slate-200/80 shadow-xs no-print">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 flex flex-wrap items-center justify-between gap-4">
      
      <!-- โลโก้และชื่อระบบ -->
      <div class="flex items-center gap-3.5">
        <div class="w-12 h-12 rounded-2xl bg-white p-1 shadow-lg shadow-slate-900/10 ring-2 ring-slate-200/80 flex items-center justify-center overflow-hidden transition transform hover:scale-105">
          <img src="img/Ministry.webp" alt="ตรากระทรวงการคลัง" class="w-full h-full object-contain">
        </div>
        <div>
          <div class="flex items-center gap-2 flex-wrap">
            <h1 class="font-extrabold text-slate-900 text-lg sm:text-xl tracking-tight font-heading leading-tight">
              MOF Smart Random
            </h1>
            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-purple-50 text-purple-700 border border-purple-200/80 shadow-2xs font-en">
              <span class="w-1.5 h-1.5 rounded-full bg-purple-500 animate-pulse"></span>
              AGE BALANCED
            </span>
          </div>
          <p class="text-xs text-slate-500 font-sans flex items-center gap-2 mt-0.5 flex-wrap">
            <span class="text-slate-600 font-medium"><i class="fa-solid fa-building-columns text-purple-600 mr-1"></i><b>สำนักงานปลัดกระทรวงการคลัง</b></span>
            <span class="text-slate-300">•</span>
            <span>ทั้งหมด <b id="headerTotalCount" class="text-purple-700 font-en">0</b> คน</span>
            <span class="text-slate-300">•</span>
            <span>สุ่มแล้ว <b id="headerAssignedCount" class="text-emerald-600 font-en">0</b> คน</span>
          </p>
        </div>
      </div>

      <!-- ปุ่ม Action ขวาบน -->
      <div class="flex items-center flex-wrap gap-2">
        <div class="inline-flex items-center p-1 bg-slate-100/90 rounded-2xl border border-slate-200/60 shadow-inner">
          <a href="index.php" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold text-slate-700 hover:bg-white hover:shadow-xs transition active:scale-95">
            <i class="fa-solid fa-chart-pie text-brand-600"></i>
            <span>หน้าสรุปผล</span>
          </a>
          <a href="all_random.php" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold text-sky-700 hover:bg-white hover:shadow-xs transition active:scale-95">
            <i class="fa-solid fa-shuffle text-sky-600"></i>
            <span>สุ่มแบบรวม</span>
          </a>
          <a href="division_random.php" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold text-purple-800 bg-white shadow-xs transition">
            <i class="fa-solid fa-sitemap text-purple-600"></i>
            <span>สุ่มแยกสำนัก &amp; ส่วน</span>
          </a>
          <a href="stage_random.php" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold text-indigo-700 hover:bg-white hover:shadow-xs transition active:scale-95">
            <i class="fa-solid fa-layer-group text-indigo-600"></i>
            <span>สุ่ม 5 ขั้น</span>
          </a>
        </div>

        <button onclick="openGlobalManageModal()" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 text-xs font-semibold shadow-xs transition active:scale-95 cursor-pointer" title="จัดการรายชื่อผู้เข้าร่วม">
          <i class="fa-solid fa-user-gear text-slate-500"></i>
          <span>จัดการรายชื่อ (<span class="user-manage-badge-count text-purple-600 font-bold">0</span>)</span>
        </button>

        <button onclick="openGlobalExcelModal()" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 text-xs font-semibold shadow-xs transition active:scale-95 cursor-pointer">
          <i class="fa-solid fa-cloud-arrow-up text-emerald-600"></i>
          <span>อัปโหลด Excel</span>
        </button>

        <button onclick="exportToExcel()" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white text-xs font-bold shadow-md shadow-emerald-600/20 transition active:scale-95 cursor-pointer">
          <i class="fa-solid fa-file-excel text-sm"></i>
          <span>ส่งออก Excel</span>
        </button>
      </div>

    </div>
  </header>

  <!-- เนื้อหาหลัก -->
  <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

    <!-- 1. แถบสรุปยอด 4 สี พร้อมแสดงอายุเฉลี่ย (Age Balance Dashboard) -->
    <section class="grid grid-cols-2 sm:grid-cols-4 gap-3.5">
      <!-- สีแดง -->
      <div class="bg-white rounded-2xl p-4 border-2 border-[#e51c23]/30 shadow-xs relative overflow-hidden transition hover:shadow-md">
        <div class="absolute top-0 right-0 w-16 h-16 bg-[#e51c23]/10 rounded-bl-full pointer-events-none"></div>
        <div class="flex items-center justify-between mb-2">
          <span class="inline-flex items-center gap-1.5 text-xs font-bold text-[#e51c23] uppercase tracking-wide">
            <span class="w-3.5 h-3.5 rounded-full bg-[#e51c23] inline-block shadow-xs"></span>
            สีแดง
          </span>
          <i class="fa-solid fa-fire text-[#e51c23]/70 text-sm"></i>
        </div>
        <div class="flex items-baseline justify-between">
          <div class="flex items-baseline gap-1.5">
            <span id="counter-team-0" class="text-3xl font-extrabold text-slate-900 font-en">0</span>
            <span class="text-xs text-slate-500 font-medium">คน</span>
          </div>
          <!-- อายุเฉลี่ย -->
          <div class="text-right">
            <div class="text-[10px] text-slate-400">อายุเฉลี่ย</div>
            <div class="text-base font-extrabold text-[#e51c23] font-en leading-tight">
              <span id="avg-age-0">0.0</span> <span class="text-[10px] font-normal text-slate-500">ปี</span>
            </div>
          </div>
        </div>
        <div class="mt-2 text-[11px] text-slate-500 flex justify-between border-t border-slate-100 pt-1.5">
          <span>ชาย: <b id="counter-team-0-m" class="text-slate-700 font-en">0</b> &bull; หญิง: <b id="counter-team-0-f" class="text-slate-700 font-en">0</b></span>
          <span>ช่วงอายุ: <b id="age-range-0" class="text-slate-700 font-en">-</b></span>
        </div>
      </div>

      <!-- สีชมพู -->
      <div class="bg-white rounded-2xl p-4 border-2 border-[#e91e63]/30 shadow-xs relative overflow-hidden transition hover:shadow-md">
        <div class="absolute top-0 right-0 w-16 h-16 bg-[#e91e63]/10 rounded-bl-full pointer-events-none"></div>
        <div class="flex items-center justify-between mb-2">
          <span class="inline-flex items-center gap-1.5 text-xs font-bold text-[#e91e63] uppercase tracking-wide">
            <span class="w-3.5 h-3.5 rounded-full bg-[#e91e63] inline-block shadow-xs"></span>
            สีชมพู
          </span>
          <i class="fa-solid fa-heart text-[#e91e63]/70 text-sm"></i>
        </div>
        <div class="flex items-baseline justify-between">
          <div class="flex items-baseline gap-1.5">
            <span id="counter-team-1" class="text-3xl font-extrabold text-slate-900 font-en">0</span>
            <span class="text-xs text-slate-500 font-medium">คน</span>
          </div>
          <div class="text-right">
            <div class="text-[10px] text-slate-400">อายุเฉลี่ย</div>
            <div class="text-base font-extrabold text-[#e91e63] font-en leading-tight">
              <span id="avg-age-1">0.0</span> <span class="text-[10px] font-normal text-slate-500">ปี</span>
            </div>
          </div>
        </div>
        <div class="mt-2 text-[11px] text-slate-500 flex justify-between border-t border-slate-100 pt-1.5">
          <span>ชาย: <b id="counter-team-1-m" class="text-slate-700 font-en">0</b> &bull; หญิง: <b id="counter-team-1-f" class="text-slate-700 font-en">0</b></span>
          <span>ช่วงอายุ: <b id="age-range-1" class="text-slate-700 font-en">-</b></span>
        </div>
      </div>

      <!-- สีเขียว -->
      <div class="bg-white rounded-2xl p-4 border-2 border-[#259b24]/30 shadow-xs relative overflow-hidden transition hover:shadow-md">
        <div class="absolute top-0 right-0 w-16 h-16 bg-[#259b24]/10 rounded-bl-full pointer-events-none"></div>
        <div class="flex items-center justify-between mb-2">
          <span class="inline-flex items-center gap-1.5 text-xs font-bold text-[#259b24] uppercase tracking-wide">
            <span class="w-3.5 h-3.5 rounded-full bg-[#259b24] inline-block shadow-xs"></span>
            สีเขียว
          </span>
          <i class="fa-solid fa-leaf text-[#259b24]/70 text-sm"></i>
        </div>
        <div class="flex items-baseline justify-between">
          <div class="flex items-baseline gap-1.5">
            <span id="counter-team-2" class="text-3xl font-extrabold text-slate-900 font-en">0</span>
            <span class="text-xs text-slate-500 font-medium">คน</span>
          </div>
          <div class="text-right">
            <div class="text-[10px] text-slate-400">อายุเฉลี่ย</div>
            <div class="text-base font-extrabold text-[#259b24] font-en leading-tight">
              <span id="avg-age-2">0.0</span> <span class="text-[10px] font-normal text-slate-500">ปี</span>
            </div>
          </div>
        </div>
        <div class="mt-2 text-[11px] text-slate-500 flex justify-between border-t border-slate-100 pt-1.5">
          <span>ชาย: <b id="counter-team-2-m" class="text-slate-700 font-en">0</b> &bull; หญิง: <b id="counter-team-2-f" class="text-slate-700 font-en">0</b></span>
          <span>ช่วงอายุ: <b id="age-range-2" class="text-slate-700 font-en">-</b></span>
        </div>
      </div>

      <!-- สีเหลือง -->
      <div class="bg-white rounded-2xl p-4 border-2 border-[#fbc02d]/40 shadow-xs relative overflow-hidden transition hover:shadow-md">
        <div class="absolute top-0 right-0 w-16 h-16 bg-[#ffeb3b]/20 rounded-bl-full pointer-events-none"></div>
        <div class="flex items-center justify-between mb-2">
          <span class="inline-flex items-center gap-1.5 text-xs font-bold text-amber-800 uppercase tracking-wide">
            <span class="w-3.5 h-3.5 rounded-full bg-[#fbc02d] inline-block shadow-xs"></span>
            สีเหลือง
          </span>
          <i class="fa-solid fa-sun text-amber-500 text-sm"></i>
        </div>
        <div class="flex items-baseline justify-between">
          <div class="flex items-baseline gap-1.5">
            <span id="counter-team-3" class="text-3xl font-extrabold text-slate-900 font-en">0</span>
            <span class="text-xs text-slate-500 font-medium">คน</span>
          </div>
          <div class="text-right">
            <div class="text-[10px] text-slate-400">อายุเฉลี่ย</div>
            <div class="text-base font-extrabold text-amber-600 font-en leading-tight">
              <span id="avg-age-3">0.0</span> <span class="text-[10px] font-normal text-slate-500">ปี</span>
            </div>
          </div>
        </div>
        <div class="mt-2 text-[11px] text-slate-500 flex justify-between border-t border-slate-100 pt-1.5">
          <span>ชาย: <b id="counter-team-3-m" class="text-slate-700 font-en">0</b> &bull; หญิง: <b id="counter-team-3-f" class="text-slate-700 font-en">0</b></span>
          <span>ช่วงอายุ: <b id="age-range-3" class="text-slate-700 font-en">-</b></span>
        </div>
      </div>
    </section>

    <!-- 2. แถบควบคุมสุ่มผู้บริหาร 3 กลุ่มแรก (Executive Rounds Section) -->
    <section class="bg-white rounded-2xl p-5 border border-slate-200/90 shadow-sm">
      <div class="flex items-center justify-between mb-3.5 pb-2.5 border-b border-slate-100">
        <div class="flex items-center gap-2.5">
          <span class="w-8 h-8 rounded-xl bg-purple-100 text-purple-700 flex items-center justify-center font-bold text-sm shadow-2xs">
            <i class="fa-solid fa-crown text-xs"></i>
          </span>
          <div>
            <h2 class="font-bold text-sm sm:text-base text-slate-900 font-heading">
              1. สุ่มกลุ่มผู้บริหารระดับสูง, ผอ.สำนัก/ผู้เชี่ยวชาญ และชำนาญการพิเศษ (4 กลุ่ม)
            </h2>
            <p class="text-xs text-slate-500">
              * สุ่มแยกออกมาก่อน โดยเกลี่ยอายุและเพศให้สมดุลเท่าเทียมกันทุกสี (ผอ.ส่วน ถูกย้ายไปอยู่ตามกรอบของแต่ละส่วนงานด้านล่างแล้ว)
            </p>
          </div>
        </div>

        <div class="flex items-center gap-2">
          <button onclick="spinAllExecutives()" class="px-3.5 py-1.5 rounded-xl bg-purple-50 hover:bg-purple-100 text-purple-700 border border-purple-200 text-xs font-bold transition active:scale-95 cursor-pointer flex items-center gap-1.5">
            <i class="fa-solid fa-bolt"></i>
            <span>สุ่มผู้บริหารทั้งหมด 4 กลุ่ม</span>
          </button>
          <button onclick="resetAllSpins()" class="px-3.5 py-1.5 rounded-xl bg-slate-100 hover:bg-rose-50 hover:text-rose-600 text-slate-600 font-bold text-xs border border-slate-200 shadow-2xs transition active:scale-95 cursor-pointer flex items-center gap-1.5" title="รีเซ็ตผลการสุ่มและเริ่มใหม่ทั้งหมด">
            <i class="fa-solid fa-rotate-left"></i>
            <span>เริ่มสุ่มใหม่ทั้งหมด</span>
          </button>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-4 gap-4" id="execCardsContainer">
        <!-- Exec 1: รองปลัด & หัวหน้าผู้ตรวจ -->
        <div id="execCard-1" class="rounded-2xl border border-slate-200 bg-slate-50/50 p-4 flex flex-col justify-between transition hover:shadow-xs">
          <div>
            <div class="flex items-center justify-between mb-2">
              <span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-purple-100 text-purple-800">ลำดับที่ 1</span>
              <span id="execStatus-1" class="text-[11px] font-semibold text-slate-400">ยังไม่ได้สุ่ม</span>
            </div>
            <h3 class="font-bold text-slate-900 text-sm">รองปลัด &amp; หัวหน้าผู้ตรวจ</h3>
            <p class="text-xs text-slate-500 mt-1">รองปลัดกระทรวงการคลัง (4) &amp; หัวหน้าผู้ตรวจ (1)</p>
            <div id="execMembersList-1" class="mt-2.5 space-y-1.5 max-h-36 overflow-y-auto custom-scrollbar">
              <!-- Rendered by JS -->
            </div>
          </div>
          <div class="mt-3 pt-2.5 border-t border-slate-200/60 flex items-center justify-between">
            <span class="text-xs text-slate-500">จำนวน: <b id="execCount-1" class="text-purple-700 font-en">0</b> คน</span>
            <button id="btnSpinExec-1" onclick="spinExecGroup(1)" class="px-3.5 py-1.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-white font-bold text-xs shadow-xs transition active:scale-95 cursor-pointer flex items-center gap-1.5">
              <i class="fa-solid fa-dice"></i>
              <span>สุ่มกลุ่มนี้</span>
            </button>
          </div>
        </div>

        <!-- Exec 2: ผู้ตรวจราชการ & ที่ปรึกษา -->
        <div id="execCard-2" class="rounded-2xl border border-slate-200 bg-slate-50/50 p-4 flex flex-col justify-between transition hover:shadow-xs">
          <div>
            <div class="flex items-center justify-between mb-2">
              <span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-800">ลำดับที่ 2</span>
              <span id="execStatus-2" class="text-[11px] font-semibold text-slate-400">ยังไม่ได้สุ่ม</span>
            </div>
            <h3 class="font-bold text-slate-900 text-sm">ผู้ตรวจราชการ &amp; ที่ปรึกษา</h3>
            <p class="text-xs text-slate-500 mt-1">ผู้ตรวจราชการกระทรวง (6) &amp; ที่ปรึกษา/ผู้ช่วย (4)</p>
            <div id="execMembersList-2" class="mt-2.5 space-y-1.5 max-h-36 overflow-y-auto custom-scrollbar">
              <!-- Rendered by JS -->
            </div>
          </div>
          <div class="mt-3 pt-2.5 border-t border-slate-200/60 flex items-center justify-between">
            <span class="text-xs text-slate-500">จำนวน: <b id="execCount-2" class="text-indigo-700 font-en">0</b> คน</span>
            <button id="btnSpinExec-2" onclick="spinExecGroup(2)" class="px-3.5 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-xs transition active:scale-95 cursor-pointer flex items-center gap-1.5">
              <i class="fa-solid fa-dice"></i>
              <span>สุ่มกลุ่มนี้</span>
            </button>
          </div>
        </div>

        <!-- Exec 3: ผอ.สำนัก & ผู้เชี่ยวชาญ -->
        <div id="execCard-3" class="rounded-2xl border border-slate-200 bg-slate-50/50 p-4 flex flex-col justify-between transition hover:shadow-xs">
          <div>
            <div class="flex items-center justify-between mb-2">
              <span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-blue-100 text-blue-800">ลำดับที่ 3</span>
              <span id="execStatus-3" class="text-[11px] font-semibold text-slate-400">ยังไม่ได้สุ่ม</span>
            </div>
            <h3 class="font-bold text-slate-900 text-sm">ผอ.สำนัก &amp; ผู้เชี่ยวชาญ</h3>
            <p class="text-xs text-slate-500 mt-1">ผู้อำนวยการสำนัก/ศูนย์/กอง (6) &amp; ผู้เชี่ยวชาญ (8)</p>
            <div id="execMembersList-3" class="mt-2.5 space-y-1.5 max-h-36 overflow-y-auto custom-scrollbar">
              <!-- Rendered by JS -->
            </div>
          </div>
          <div class="mt-3 pt-2.5 border-t border-slate-200/60 flex items-center justify-between">
            <span class="text-xs text-slate-500">จำนวน: <b id="execCount-3" class="text-blue-700 font-en">0</b> คน</span>
            <button id="btnSpinExec-3" onclick="spinExecGroup(3)" class="px-3.5 py-1.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-xs transition active:scale-95 cursor-pointer flex items-center gap-1.5">
              <i class="fa-solid fa-dice"></i>
              <span>สุ่มกลุ่มนี้</span>
            </button>
          </div>
        </div>

        <!-- Exec 4: ผู้บริหาร/ชำนาญการพิเศษ (ส่วนกลาง) -->
        <div id="execCard-4" class="rounded-2xl border border-slate-200 bg-slate-50/50 p-4 flex flex-col justify-between transition hover:shadow-xs">
          <div>
            <div class="flex items-center justify-between mb-2">
              <span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-teal-100 text-teal-800">ลำดับที่ 4</span>
              <span id="execStatus-4" class="text-[11px] font-semibold text-slate-400">ยังไม่ได้สุ่ม</span>
            </div>
            <h3 class="font-bold text-slate-900 text-sm">ผู้บริหาร &amp; ชำนาญการพิเศษ</h3>
            <p class="text-xs text-slate-500 mt-1">ผู้ช่วยปลัด, ผอ.กลุ่ม, หัวหน้า สนร. และชำนาญการพิเศษ (32)</p>
            <div id="execMembersList-4" class="mt-2.5 space-y-1.5 max-h-36 overflow-y-auto custom-scrollbar">
              <!-- Rendered by JS -->
            </div>
          </div>
          <div class="mt-3 pt-2.5 border-t border-slate-200/60 flex items-center justify-between">
            <span class="text-xs text-slate-500">จำนวน: <b id="execCount-4" class="text-teal-700 font-en">0</b> คน</span>
            <button id="btnSpinExec-4" onclick="spinExecGroup(4)" class="px-3.5 py-1.5 rounded-xl bg-teal-600 hover:bg-teal-700 text-white font-bold text-xs shadow-xs transition active:scale-95 cursor-pointer flex items-center gap-1.5">
              <i class="fa-solid fa-dice"></i>
              <span>สุ่มกลุ่มนี้</span>
            </button>
          </div>
        </div>
      </div>
    </section>

    <!-- 3. ส่วนหลัก: กรอบของแต่ละสำนัก/ศูนย์/กลุ่ม และกรอบของแต่ละส่วนงานย่อย (Divisions & Sections Frames) -->
    <section class="space-y-4">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-4 rounded-2xl border border-slate-200/90 shadow-xs">
        <div>
          <h2 class="font-bold text-base sm:text-lg text-slate-900 font-heading flex items-center gap-2">
            <i class="fa-solid fa-folder-tree text-purple-600"></i>
            <span>2. กรอบสำนัก/ศูนย์/กลุ่ม และส่วนงานย่อย (สุ่มได้สูงสุด 3 ครั้งตามความพอใจ)</span>
          </h2>
          <p class="text-xs text-slate-500 mt-0.5">
            แต่ละสำนัก/กอง และแต่ละส่วนงานย่อย สามารถกดสุ่มได้สูงสุด 3 ครั้งตามความพอใจ หากพอใจแล้วสามารถหยุดสุ่มได้ทันที
          </p>
        </div>

        <div class="flex items-center flex-wrap gap-2.5">
          <!-- ปุ่มสุ่มทุกส่วนงานทั้งหมดรวดเดียว -->
          <button onclick="spinAllSections()" class="btn-glow px-4 py-2 rounded-xl bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white font-bold text-xs shadow-xs transition active:scale-95 cursor-pointer flex items-center gap-1.5">
            <i class="fa-solid fa-forward-step"></i>
            <span>สุ่มทุกส่วนงานทั้งหมดรวดเดียว</span>
          </button>
        </div>
      </div>

      <!-- คอนเทนเนอร์แสดงกรอบสำนัก/ศูนย์/กลุ่ม (Rendered by JS) -->
      <div id="divisionsFramesList" class="space-y-6">
        <!-- Rendered by JS -->
      </div>
    </section>

  </main>

  <!-- Footer -->
  <footer class="bg-white border-t border-slate-200/80 py-6 mt-12 w-full no-print">
    <div class="w-full px-4 text-center text-xs text-slate-500">
      <span>ระบบสุ่มแบ่งกลุ่ม 4 สี &bull; สำนักงานปลัดกระทรวงการคลัง (สป.กค.) กระทรวงการคลัง</span>
    </div>
  </footer>

  <!-- Avatar File Input (hidden) -->
  <input type="file" id="globalAvatarInput" accept="image/*" class="hidden" style="display:none;" onchange="handleAvatarFileSelected(event)">

  <!-- Toast Notification -->
  <div id="avatarToast" style="position:fixed;bottom:20px;right:20px;z-index:9999;display:flex;align-items:center;gap:10px;background:#1e293b;color:#fff;padding:12px 16px;border-radius:16px;font-size:13px;transform:translateY(80px);opacity:0;transition:all 0.3s;pointer-events:none;border:1px solid #334155;">
    <span style="width:24px;height:24px;border-radius:50%;background:rgba(16,185,129,0.2);color:#34d399;display:flex;align-items:center;justify-content:center;font-size:12px;">✓</span>
    <span id="avatarToastText">อัปโหลดรูปถ่ายเรียบร้อยแล้ว</span>
  </div>

  <!-- JavaScript หลัก -->
  <script>
    // 4 สี
    const TEAMS = [
      { id: 0, name: 'สีแดง', subName: 'Red', color: '#e51c23', bgBadge: 'bg-red-100 text-red-700 border-red-200', dotBg: 'bg-[#e51c23]' },
      { id: 1, name: 'สีชมพู', subName: 'Pink', color: '#e91e63', bgBadge: 'bg-pink-100 text-pink-700 border-pink-200', dotBg: 'bg-[#e91e63]' },
      { id: 2, name: 'สีเขียว', subName: 'Green', color: '#259b24', bgBadge: 'bg-green-100 text-green-700 border-green-200', dotBg: 'bg-[#259b24]' },
      { id: 3, name: 'สีเหลือง', subName: 'Yellow', color: '#ffeb3b', bgBadge: 'bg-yellow-100 text-amber-800 border-yellow-300', dotBg: 'bg-[#fbc02d]' }
    ];

    // Palette ธีมสีเฉพาะของแต่ละสำนัก/ศูนย์/กลุ่ม (Modern Glass & Soft Gradient)
    const DIVISION_THEMES = [
      {
        border: 'border-indigo-200/90 hover:border-indigo-400',
        headerBg: 'bg-gradient-to-r from-indigo-50/90 via-sky-50/50 to-white',
        iconBg: 'bg-gradient-to-tr from-indigo-600 to-blue-600 text-white shadow-md shadow-indigo-500/20 ring-2 ring-white',
        titleColor: 'text-indigo-950',
        badge: 'bg-indigo-50 text-indigo-700 border border-indigo-200/70',
        secBg: 'bg-slate-50/50',
        secCardBg: 'bg-white',
        secIcon: 'bg-indigo-100/80 text-indigo-700',
        btnWhole: 'bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200/80',
        btnSec: 'bg-gradient-to-r from-indigo-600 to-blue-600 hover:from-indigo-700 hover:to-blue-700 shadow-sm shadow-indigo-600/20'
      },
      {
        border: 'border-purple-200/90 hover:border-purple-400',
        headerBg: 'bg-gradient-to-r from-purple-50/90 via-pink-50/50 to-white',
        iconBg: 'bg-gradient-to-tr from-purple-600 to-pink-600 text-white shadow-md shadow-purple-500/20 ring-2 ring-white',
        titleColor: 'text-purple-950',
        badge: 'bg-purple-50 text-purple-700 border border-purple-200/70',
        secBg: 'bg-slate-50/50',
        secCardBg: 'bg-white',
        secIcon: 'bg-purple-100/80 text-purple-700',
        btnWhole: 'bg-purple-50 hover:bg-purple-100 text-purple-700 border border-purple-200/80',
        btnSec: 'bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 shadow-sm shadow-purple-600/20'
      },
      {
        border: 'border-teal-200/90 hover:border-teal-400',
        headerBg: 'bg-gradient-to-r from-teal-50/90 via-emerald-50/50 to-white',
        iconBg: 'bg-gradient-to-tr from-teal-600 to-emerald-600 text-white shadow-md shadow-teal-500/20 ring-2 ring-white',
        titleColor: 'text-teal-950',
        badge: 'bg-teal-50 text-teal-700 border border-teal-200/70',
        secBg: 'bg-slate-50/50',
        secCardBg: 'bg-white',
        secIcon: 'bg-teal-100/80 text-teal-700',
        btnWhole: 'bg-teal-50 hover:bg-teal-100 text-teal-700 border border-teal-200/80',
        btnSec: 'bg-gradient-to-r from-teal-600 to-emerald-600 hover:from-teal-700 hover:to-emerald-700 shadow-sm shadow-teal-600/20'
      },
      {
        border: 'border-sky-200/90 hover:border-sky-400',
        headerBg: 'bg-gradient-to-r from-sky-50/90 via-blue-50/50 to-white',
        iconBg: 'bg-gradient-to-tr from-sky-600 to-cyan-600 text-white shadow-md shadow-sky-500/20 ring-2 ring-white',
        titleColor: 'text-sky-950',
        badge: 'bg-sky-50 text-sky-700 border border-sky-200/70',
        secBg: 'bg-slate-50/50',
        secCardBg: 'bg-white',
        secIcon: 'bg-sky-100/80 text-sky-700',
        btnWhole: 'bg-sky-50 hover:bg-sky-100 text-sky-700 border border-sky-200/80',
        btnSec: 'bg-gradient-to-r from-sky-600 to-cyan-600 hover:from-sky-700 hover:to-cyan-700 shadow-sm shadow-sky-600/20'
      },
      {
        border: 'border-amber-200/90 hover:border-amber-400',
        headerBg: 'bg-gradient-to-r from-amber-50/90 via-yellow-50/50 to-white',
        iconBg: 'bg-gradient-to-tr from-amber-500 to-orange-500 text-white shadow-md shadow-amber-500/20 ring-2 ring-white',
        titleColor: 'text-amber-950',
        badge: 'bg-amber-50 text-amber-700 border border-amber-200/70',
        secBg: 'bg-slate-50/50',
        secCardBg: 'bg-white',
        secIcon: 'bg-amber-100/80 text-amber-700',
        btnWhole: 'bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200/80',
        btnSec: 'bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 shadow-sm shadow-amber-500/20'
      },
      {
        border: 'border-rose-200/90 hover:border-rose-400',
        headerBg: 'bg-gradient-to-r from-rose-50/90 via-pink-50/50 to-white',
        iconBg: 'bg-gradient-to-tr from-rose-600 to-pink-600 text-white shadow-md shadow-rose-500/20 ring-2 ring-white',
        titleColor: 'text-rose-950',
        badge: 'bg-rose-50 text-rose-700 border border-rose-200/70',
        secBg: 'bg-slate-50/50',
        secCardBg: 'bg-white',
        secIcon: 'bg-rose-100/80 text-rose-700',
        btnWhole: 'bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200/80',
        btnSec: 'bg-gradient-to-r from-rose-600 to-pink-600 hover:from-rose-700 hover:to-pink-700 shadow-sm shadow-rose-600/20'
      },
      {
        border: 'border-emerald-200/90 hover:border-emerald-400',
        headerBg: 'bg-gradient-to-r from-emerald-50/90 via-teal-50/50 to-white',
        iconBg: 'bg-gradient-to-tr from-emerald-600 to-teal-600 text-white shadow-md shadow-emerald-500/20 ring-2 ring-white',
        titleColor: 'text-emerald-950',
        badge: 'bg-emerald-50 text-emerald-700 border border-emerald-200/70',
        secBg: 'bg-slate-50/50',
        secCardBg: 'bg-white',
        secIcon: 'bg-emerald-100/80 text-emerald-700',
        btnWhole: 'bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200/80',
        btnSec: 'bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 shadow-sm shadow-emerald-600/20'
      },
      {
        border: 'border-blue-200/90 hover:border-blue-400',
        headerBg: 'bg-gradient-to-r from-blue-50/90 via-sky-50/50 to-white',
        iconBg: 'bg-gradient-to-tr from-blue-600 to-indigo-600 text-white shadow-md shadow-blue-500/20 ring-2 ring-white',
        titleColor: 'text-blue-950',
        badge: 'bg-blue-50 text-blue-700 border border-blue-200/70',
        secBg: 'bg-slate-50/50',
        secCardBg: 'bg-white',
        secIcon: 'bg-blue-100/80 text-blue-700',
        btnWhole: 'bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200/80',
        btnSec: 'bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 shadow-sm shadow-blue-600/20'
      },
      {
        border: 'border-violet-200/90 hover:border-violet-400',
        headerBg: 'bg-gradient-to-r from-violet-50/90 via-purple-50/50 to-white',
        iconBg: 'bg-gradient-to-tr from-violet-600 to-purple-600 text-white shadow-md shadow-violet-500/20 ring-2 ring-white',
        titleColor: 'text-violet-950',
        badge: 'bg-violet-50 text-violet-700 border border-violet-200/70',
        secBg: 'bg-slate-50/50',
        secCardBg: 'bg-white',
        secIcon: 'bg-violet-100/80 text-violet-700',
        btnWhole: 'bg-violet-50 hover:bg-violet-100 text-violet-700 border border-violet-200/80',
        btnSec: 'bg-gradient-to-r from-violet-600 to-purple-600 hover:from-violet-700 hover:to-purple-700 shadow-sm shadow-violet-600/20'
      },
      {
        border: 'border-cyan-200/90 hover:border-cyan-400',
        headerBg: 'bg-gradient-to-r from-cyan-50/90 via-sky-50/50 to-white',
        iconBg: 'bg-gradient-to-tr from-cyan-600 to-teal-600 text-white shadow-md shadow-cyan-500/20 ring-2 ring-white',
        titleColor: 'text-cyan-950',
        badge: 'bg-cyan-50 text-cyan-700 border border-cyan-200/70',
        secBg: 'bg-slate-50/50',
        secCardBg: 'bg-white',
        secIcon: 'bg-cyan-100/80 text-cyan-700',
        btnWhole: 'bg-cyan-50 hover:bg-cyan-100 text-cyan-700 border border-cyan-200/80',
        btnSec: 'bg-gradient-to-r from-cyan-600 to-teal-600 hover:from-cyan-700 hover:to-teal-700 shadow-sm shadow-cyan-600/20'
      }
    ];

    function getDivisionTheme(divIndex) {
      return DIVISION_THEMES[divIndex % DIVISION_THEMES.length];
    }

    // State
    let allUsers = [];
    let execGroups = [[], [], []];
    let divisionsData = [];
    let teamMembers = [[], [], [], []];
    let userAvatars = {};
    let currentAvatarTargetKey = null;
    let currentAvatarTargetName = null;
    let spinHistory = {}; // บันทึกประวัติและจำนวนครั้งที่กดสุ่มต่อหน่วย: { key: { count: number, max: 3 } }

    function detectGender(u) {
      if (!u) return 'ไม่ระบุ';
      const cur = (u.gender || '').trim();
      if (cur === 'ชาย' || cur === 'หญิง') return cur;
      const pre = (u.prename_th || '').trim();
      const name = (u.name || '').trim();
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

    // ==========================================
    // 1. Data Preparation
    // ==========================================
    function initData() {
      allUsers = (window.INITIAL_USER_DATA || []).map((u, idx) => ({
        ...u,
        seq: u.seq || (idx + 1),
        name: (u.name || '').trim(),
        position: (u.POSITION || u.position || '').trim(),
        division: (u.division || 'ไม่ระบุสำนัก').trim(),
        section: (u.section || u.subdiv1 || 'งานบริหารทั่วไป/บุคลากรในสังกัด').trim(),
        gender: detectGender(u),
        age: (u.age !== undefined && u.age !== null && u.age !== '') ? parseInt(u.age) : null,
        assignedTeam: -1,
        assignedTag: ''
      }));

      document.getElementById('headerTotalCount').innerText = allUsers.length;

      organizeStructure();
      initAvatarSystem();
      restoreSavedState();

      if (typeof initGlobalUserManager === 'function') {
        initGlobalUserManager(allUsers, (updated) => {
          const activeMap = typeof getStoredUserActiveMap === 'function' ? getStoredUserActiveMap() : {};
          allUsers.forEach(u => {
            if (typeof isUserActiveInMap === 'function') {
              u.active = isUserActiveInMap(u, activeMap);
            }
          });

          const activeCount = allUsers.filter(u => u.active !== false).length;
          document.getElementById('headerTotalCount').innerText = activeCount;

          // ตัดผู้ใช้ที่ไม่ active ออกจาก teamMembers
          if (Array.isArray(teamMembers)) {
            if (typeof filterTeamsByActiveUsers === 'function') {
              teamMembers = filterTeamsByActiveUsers(teamMembers);
            } else {
              teamMembers = teamMembers.map(t => (t || []).filter(u => u.active !== false));
            }
          }

          organizeStructure();
          renderExecutiveCards();
          renderDivisionsFrames();
          updateTeamCounters();
          saveState();
        });
      }

      const activeInitCount = allUsers.filter(u => u.active !== false).length;
      document.getElementById('headerTotalCount').innerText = activeInitCount;

      renderExecutiveCards();
      renderDivisionsFrames();
      updateTeamCounters();
    }

    function organizeStructure() {
      execGroups = [[], [], [], []];
      const remainingUsers = [];

      allUsers.forEach(u => {
        if (u.active === false) return; // ข้ามผู้ที่ไม่ถูกเลือกสุ่ม
        const pos = u.position;
        const name = u.name;

        // ลำดับ 1: รองปลัด & หัวหน้าผู้ตรวจ
        if (/(รองปลัด|หัวหน้าผู้ตรวจ)/u.test(pos) || name.includes('ธีรลักษ์ แสงสนิท')) {
          u.isExec = 1;
          execGroups[0].push(u);
        }
        // ลำดับ 2: ผู้ตรวจราชการ & ที่ปรึกษา (ไม่รวมอธิบดี, ปลัด, เจ้าพนักงาน ฯลฯ)
        else if (/(ผู้ตรวจราชการ|ที่ปรึกษา)/u.test(pos) && 
                 !/(อธิบดี|ปลัด|เจ้าพนักงาน|พนักงาน|นักวิชาการ|นิติกร|เศรษฐกร|หน้าห้อง)/u.test(pos)) {
          u.isExec = 2;
          execGroups[1].push(u);
        }
        // ลำดับ 3: ผอ.สำนัก & ผู้เชี่ยวชาญ (ไม่รวม ผอ.ส่วน)
        else if (/(ผู้อำนวยการสำนัก|ผู้อำนวยการศูนย์|ผู้อำนวยการกอง|ผู้อำนวยการสำนักงาน|ผอ\.สำนัก|เชี่ยวชาญ)/u.test(pos) && 
                 !/(ผู้อำนวยการส่วน|ผอ\.ส่วน)/u.test(pos)) {
          u.isExec = 3;
          execGroups[2].push(u);
        }
        // ลำดับ 4: ผู้บริหารและชำนาญการพิเศษ (สังกัดผู้บริหารระดับสูง)
        else if (u.division === 'ผู้บริหารระดับสูง') {
          u.isExec = 4;
          execGroups[3].push(u);
        }
        // ส่วนงานย่อย (รวม ผอ.ส่วน)
        else {
          u.isExec = 0;
          u.isDirector = /(ผู้อำนวยการส่วน|ผอ\.ส่วน)/u.test(pos);
          remainingUsers.push(u);
        }
      });

      // จัดสร้างโครงสร้างสำนัก/ศูนย์/กลุ่ม
      const divMap = {};
      remainingUsers.forEach(u => {
        const d = u.division || 'ไม่ระบุสำนัก';
        // ข้ามกลุ่ม "ผู้บริหารระดับสูง" ไม่ให้นำมาสร้างเป็นกรอบสำนักซ้ำซ้อน เพราะจัดอยู่ในกลุ่มสุ่มผู้บริหารระดับสูงด้านบนแล้ว
        if (d === 'ผู้บริหารระดับสูง') return;

        let s = (u.section || u.subdiv1 || '').trim();
        if (s === '' || s === 'ฝ่าย/งานบริหารทั่วไป' || s === 'บุคลากรในสังกัด' || s === 'ทั่วไป' || s === 'ส่วนงานทั่วไป') {
          s = 'งานบริหารทั่วไป/บุคลากรในสังกัด';
        }

        if (!divMap[d]) {
          divMap[d] = {
            name: d,
            sections: {}
          };
        }
        if (!divMap[d].sections[s]) {
          divMap[d].sections[s] = {
            name: s,
            users: []
          };
        }
        divMap[d].sections[s].users.push(u);
      });

      // จัดเรียงบุคคลในแต่ละส่วน: ให้ ผอ.ส่วน ขึ้นเป็นคนแรก
      Object.values(divMap).forEach(divObj => {
        Object.values(divObj.sections).forEach(secObj => {
          secObj.users.sort((a, b) => {
            if (a.isDirector && !b.isDirector) return -1;
            if (!a.isDirector && b.isDirector) return 1;
            return (a.seq || 0) - (b.seq || 0);
          });
        });
      });

      // จัดเรียงสำนักใหญ่ขึ้นก่อน
      const divKeys = Object.keys(divMap);
      divKeys.sort((a, b) => {
        const countA = Object.values(divMap[a].sections).reduce((sum, s) => sum + s.users.length, 0);
        const countB = Object.values(divMap[b].sections).reduce((sum, s) => sum + s.users.length, 0);
        return countB - countA;
      });

      divisionsData = divKeys.map(k => divMap[k]);
    }

    // ==========================================
    // 2. Avatar System
    // ==========================================
    function initAvatarSystem() {
      if (window.INITIAL_USER_AVATARS && typeof window.INITIAL_USER_AVATARS === 'object') {
        userAvatars = { ...window.INITIAL_USER_AVATARS };
      }
      try {
        const local = localStorage.getItem('sport_user_avatars');
        if (local) {
          userAvatars = { ...userAvatars, ...JSON.parse(local) };
        }
      } catch (e) {}
    }

    function getPersonKey(u) {
      if (u.seq) return 'seq_' + u.seq;
      if (u.id !== undefined && u.id !== null) return 'id_' + u.id;
      return 'name_' + encodeURIComponent(u.name || '');
    }

    function getAvatarUrl(key) {
      return userAvatars[key] || null;
    }

    function renderAvatarHtml(u, sizeClass = 'w-12 h-12', iconSizeClass = 'text-base') {
      const key = getPersonKey(u);
      const url = getAvatarUrl(key);
      const isMale = u.gender === 'ชาย';
      const bgClass = isMale ? 'bg-blue-50 text-blue-500' : 'bg-rose-50 text-rose-500';
      const iconClass = isMale ? 'fa-user-tie' : 'fa-user';
      const safeName = (u.name || '').replace(/'/g, "\\'");

      return `
        <div class="relative shrink-0 cursor-pointer group/avatar select-none" onclick="event.stopPropagation(); triggerAvatarUpload('${key}', '${safeName}')" title="คลิกเพื่ออัปโหลด/เปลี่ยนรูปถ่ายของ ${u.name}">
          <div class="${sizeClass} rounded-2xl ring-2 ring-slate-100 group-hover/avatar:ring-purple-500 overflow-hidden ${bgClass} flex items-center justify-center transition duration-200 shadow-2xs relative bg-white">
            <img id="avatar-img-${key}" src="${url || ''}" class="w-full h-full object-cover ${url ? '' : 'hidden'}" alt="${u.name}" onerror="this.classList.add('hidden'); const fb=document.getElementById('avatar-fallback-${key}'); if(fb) fb.classList.remove('hidden');">
            <div id="avatar-fallback-${key}" class="w-full h-full flex items-center justify-center ${url ? 'hidden' : ''}">
              <i class="fa-solid ${iconClass} ${iconSizeClass}"></i>
            </div>
            <div class="absolute inset-0 bg-black/45 opacity-0 group-hover/avatar:opacity-100 transition-opacity flex flex-col items-center justify-center text-white text-[7px] font-bold backdrop-blur-[0.5px]">
              <i class="fa-solid fa-camera text-[10px] mb-0.5"></i>
              <span>${url ? 'เปลี่ยน' : 'เพิ่มรูป'}</span>
            </div>
          </div>
          <span class="absolute -bottom-1 -right-1 w-4 h-4 rounded-full bg-white ring-1 ring-slate-200 text-purple-600 flex items-center justify-center text-[7px] shadow-2xs group-hover/avatar:scale-110 transition">
            <i class="fa-solid ${url ? 'fa-pen' : 'fa-camera'}"></i>
          </span>
        </div>
      `;
    }

    function triggerAvatarUpload(key, name) {
      currentAvatarTargetKey = key;
      currentAvatarTargetName = name;
      const input = document.getElementById('globalAvatarInput');
      if (input) {
        input.value = '';
        input.click();
      }
    }

    function handleAvatarFileSelected(e) {
      const file = e.target.files && e.target.files[0];
      if (!file || !currentAvatarTargetKey) return;

      if (!file.type.startsWith('image/')) {
        alert('กรุณาเลือกไฟล์รูปภาพเท่านั้นครับ');
        return;
      }

      const reader = new FileReader();
      reader.onload = function(evt) {
        const dataUrl = evt.target.result;
        const key = currentAvatarTargetKey;
        userAvatars[key] = dataUrl;

        try {
          localStorage.setItem('sport_user_avatars', JSON.stringify(userAvatars));
        } catch (e) {}

        document.querySelectorAll(`[id^="avatar-img-${key}"]`).forEach(img => {
          img.src = dataUrl;
          img.classList.remove('hidden');
        });
        document.querySelectorAll(`[id^="avatar-fallback-${key}"]`).forEach(fb => {
          fb.classList.add('hidden');
        });

        showAvatarToast(`อัปโหลดรูปถ่ายของ ${currentAvatarTargetName} เรียบร้อยแล้ว!`);

        const formData = new FormData();
        formData.append('userId', key);
        formData.append('avatar', file);
        fetch('upload_avatar.php', { method: 'POST', body: formData })
          .then(res => res.json())
          .then(data => {
            if (data.success && data.avatarUrl) {
              userAvatars[key] = data.avatarUrl;
              localStorage.setItem('sport_user_avatars', JSON.stringify(userAvatars));
            }
          })
          .catch(err => console.warn('Upload error:', err));
      };
      reader.readAsDataURL(file);
    }

    function showAvatarToast(msg) {
      const toast = document.getElementById('avatarToast');
      const text = document.getElementById('avatarToastText');
      if (!toast || !text) return;
      text.innerText = msg;
      toast.style.transform = 'translateY(0)';
      toast.style.opacity = '1';
      toast.style.pointerEvents = 'auto';
      clearTimeout(window._avatarToastTimer);
      window._avatarToastTimer = setTimeout(() => {
        toast.style.transform = 'translateY(80px)';
        toast.style.opacity = '0';
        toast.style.pointerEvents = 'none';
      }, 3000);
    }

    // ==========================================
    // 3. Core Randomization (เกลี่ยอายุเฉลี่ยเท่ากัน & เกลี่ยเพศและจำนวน)
    // ==========================================
    /**
    /**
     * อัลกอริทึมการสุ่มและแจกจ่ายสมาชิกไปยัง 4 สี (Section-First & Perfect Balance):
     * 1. สมาชิกใน "ส่วนงานย่อยเดียวกัน" (Section) ต้องกระจายครบ 4 สี ไม่ซ้ำสีกัน (diff <= 1, หากส่วนงานมี <= 4 คน อยู่คนละสีกัน 100%, และส่วนงานขนาดกลางอยู่ด้วยกันไม่เกิน 3 คน)
     * 2. สมาชิกใน "สำนัก/กองเดียวกัน" (Division) ต้องเฉลี่ยลง 4 สี เท่าๆ กัน (diff <= 1)
     * 3. เพศชาย และ หญิง ในแต่ละสี ต้องเท่าเทียมกันที่สุด
     * 4. ยอดรวมแต่ละสี ต้องเท่าเทียมกัน (82 - 83 คน)
     * 5. อายุเฉลี่ยทุกสี ต้องใกล้เคียงกันที่สุด (38.6 - 38.9 ปี)
     */
    function distributeUsersToTeams(pool, tagLabel = '') {
      const unassigned = pool.filter(u => u.assignedTeam < 0);
      if (unassigned.length === 0) return;

      // จัดกลุ่มแยกเพศ เพื่อเกลี่ยเพศด้วย
      const males = unassigned.filter(u => u.gender === 'ชาย');
      const females = unassigned.filter(u => u.gender === 'หญิง');
      const others = unassigned.filter(u => u.gender !== 'ชาย' && u.gender !== 'หญิง');

      // ในแต่ละกลุ่มเพศ เรียงตามอายุจากมากไปน้อย เพื่อเกลี่ยทั้งอายุและเพศพร้อมกัน
      males.sort((a, b) => (b.age || 38) - (a.age || 38));
      females.sort((a, b) => (b.age || 38) - (a.age || 38));

      // รวมคิวโดยสลับเพศ
      const queue = [];
      const maxLen = Math.max(males.length, females.length, others.length);
      for (let i = 0; i < maxLen; i++) {
        if (i < males.length) queue.push(males[i]);
        if (i < females.length) queue.push(females[i]);
        if (i < others.length) queue.push(others[i]);
      }

      // แจกจ่ายสมาชิกทีละคนไปยังทีมที่เหมาะสมที่สุด
      queue.forEach(user => {
        const uAge = user.age || 38;
        const uGender = user.gender || '';
        const uDiv = (user.division || '').trim();
        const uSec = (user.section || user.subdiv1 || tagLabel || '').trim();

        // คำนวณสถานะของแต่ละทีมในปัจจุบัน
        const teamStats = [0, 1, 2, 3].map(t => {
          const members = teamMembers[t];
          const count = members.length;
          const secCount = members.filter(m => {
            const mSec = (m.section || m.subdiv1 || m.assignedTag || '').trim();
            const mDiv = (m.division || '').trim();
            return (mSec === uSec && mDiv === uDiv) || (tagLabel && m.assignedTag === tagLabel);
          }).length;
          const divCount = members.filter(m => (m.division || '').trim() === uDiv).length;
          const genderCount = members.filter(m => m.gender === uGender).length;
          const totalAge = members.reduce((sum, m) => sum + (m.age || 38), 0);
          const avgAge = count > 0 ? (totalAge / count) : 38;
          return { teamId: t, count, secCount, divCount, genderCount, avgAge, totalAge };
        });

        // 1. ความสมดุลของ "ส่วนงานย่อย" (Section) ต้องน้อยที่สุดก่อน (กระจายครบทุกสี ไม่ซ้ำสี diff <= 1)
        const minSecCount = Math.min(...teamStats.map(s => s.secCount));
        let candidateTeams = teamStats.filter(s => s.secCount === minSecCount);

        // 2. ความสมดุลของ "สำนัก/กอง" (Division)
        if (candidateTeams.length > 1 && uDiv) {
          const minDivCount = Math.min(...candidateTeams.map(s => s.divCount));
          candidateTeams = candidateTeams.filter(s => s.divCount === minDivCount);
        }

        // 3. เกลี่ยเพศเดียวกันให้เท่ากัน
        if (candidateTeams.length > 1 && (uGender === 'ชาย' || uGender === 'หญิง')) {
          const minGenderCount = Math.min(...candidateTeams.map(s => s.genderCount));
          candidateTeams = candidateTeams.filter(s => s.genderCount === minGenderCount);
        }

        // 4. ถ้าเพศเท่ากัน ดูจำนวนคนรวมของทีม
        if (candidateTeams.length > 1) {
          const minCount = Math.min(...candidateTeams.map(s => s.count));
          candidateTeams = candidateTeams.filter(s => s.count === minCount);
        }

        // 5. ถ้าจำนวนคนเท่ากัน ให้เลือกทีมที่จะทำให้อายุเฉลี่ยสมดุลที่สุด
        let chosenTeamId = candidateTeams[0].teamId;
        if (candidateTeams.length > 1) {
          candidateTeams.sort((a, b) => {
            if (uAge >= 38) {
              return a.avgAge - b.avgAge; // ทีม avg ต่ำสุดได้ก่อน
            } else {
              return b.avgAge - a.avgAge; // ทีม avg สูงสุดได้ก่อน
            }
          });

          // หากยังเท่ากันให้สุ่มจากทีมที่ดีที่สุด
          const bestAvg = candidateTeams[0].avgAge;
          const bestCandidates = candidateTeams.filter(c => Math.abs(c.avgAge - bestAvg) < 0.1);
          chosenTeamId = bestCandidates[Math.floor(Math.random() * bestCandidates.length)].teamId;
        }

        user.assignedTeam = chosenTeamId;
        user.assignedTag = tagLabel;
        teamMembers[chosenTeamId].push(user);
      });
    }

    // ฟังก์ชันเคลียร์ผลการสุ่มของสมาชิกกลุ่มหนึ่งๆ ออกจากทีมก่อน เพื่อให้สุ่มใหม่ได้
    function clearAssignedUsers(users) {
      users.forEach(u => {
        if (u.assignedTeam >= 0) {
          const t = u.assignedTeam;
          teamMembers[t] = teamMembers[t].filter(m => (m.seq ? m.seq !== u.seq : m.name !== u.name));
          u.assignedTeam = -1;
          u.assignedTag = '';
        }
      });
    }

    // จัดการประวัติการสุ่ม (จำกัดสูงสุด 3 ครั้งตามความพอใจ)
    function getSpinCount(key) {
      return (spinHistory[key] && spinHistory[key].count) || 0;
    }

    function canSpin(key) {
      return getSpinCount(key) < 3;
    }

    function recordSpin(key) {
      if (!spinHistory[key]) {
        spinHistory[key] = { count: 0, max: 3 };
      }
      spinHistory[key].count++;
      return spinHistory[key].count;
    }

    // 3.1 สุ่มเฉพาะส่วนงานย่อย (สุ่มได้สูงสุด 3 ครั้งตามความพอใจ)
    function spinSpecificSection(divName, secName) {
      const key = `sec:${divName}:${secName}`;
      const divObj = divisionsData.find(d => d.name === divName);
      if (!divObj || !divObj.sections[secName]) return;

      const secObj = divObj.sections[secName];
      const curCount = getSpinCount(key);
      if (curCount >= 3) {
        alert(`ส่วนงาน "${secName}" สุ่มครบโควตา 3 ครั้งแล้วครับ`);
        return;
      }

      // หากเคยสุ่มแล้ว ให้เคลียร์คนของส่วนงานนี้ออกจากทีมก่อน แล้วสุ่มใหม่
      clearAssignedUsers(secObj.users);
      recordSpin(key);

      distributeUsersToTeams(secObj.users, `${divName} - ${secName}`);

      launchConfetti();
      saveCurrentState();
      renderDivisionsFrames();
      updateTeamCounters();
    }

    // 3.2 สุ่มทั้งสำนัก (สุ่มได้สูงสุด 3 ครั้งตามความพอใจ)
    function spinWholeDivision(divName) {
      const key = `div:${divName}`;
      const divObj = divisionsData.find(d => d.name === divName);
      if (!divObj) return;

      const curCount = getSpinCount(key);
      if (curCount >= 3) {
        alert(`สำนัก "${divName}" สุ่มครบโควตา 3 ครั้งแล้วครับ`);
        return;
      }

      // เคลียร์คนทุกส่วนในสำนักนี้ออกจากทีมก่อน
      const allSecUsers = [];
      Object.values(divObj.sections).forEach(secObj => {
        allSecUsers.push(...secObj.users);
      });
      clearAssignedUsers(allSecUsers);
      recordSpin(key);

      // บันทึกการสุ่มให้ส่วนย่อยในสำนักนี้ด้วย
      Object.values(divObj.sections).forEach(secObj => {
        const secKey = `sec:${divName}:${secObj.name}`;
        if (!spinHistory[secKey]) spinHistory[secKey] = { count: 0, max: 3 };
        spinHistory[secKey].count = Math.min(3, (spinHistory[secKey].count || 0) + 1);
        distributeUsersToTeams(secObj.users, `${divName} - ${secObj.name}`);
      });

      launchConfetti();
      saveCurrentState();
      renderDivisionsFrames();
      updateTeamCounters();
    }

    // 3.3 สุ่มกลุ่มผู้บริหาร (สุ่มได้สูงสุด 3 ครั้งตามความพอใจ)
    function spinExecGroup(groupIdx) {
      const key = `exec:${groupIdx}`;
      const pool = execGroups[groupIdx - 1];
      if (!pool || pool.length === 0) return;

      const curCount = getSpinCount(key);
      if (curCount >= 3) {
        alert(`กลุ่มผู้บริหารลำดับที่ ${groupIdx} สุ่มครบโควตา 3 ครั้งแล้วครับ`);
        return;
      }

      clearAssignedUsers(pool);
      recordSpin(key);

      const titles = ['รองปลัด & หัวหน้าผู้ตรวจ', 'ผู้ตรวจราชการ & ที่ปรึกษา', 'ผอ.สำนัก & ผู้เชี่ยวชาญ', 'ผู้บริหาร & ชำนาญการพิเศษ'];
      distributeUsersToTeams(pool, titles[groupIdx - 1]);

      launchConfetti();
      saveCurrentState();
      renderExecutiveCards();
      updateTeamCounters();
    }

    function spinAllExecutives() {
      for (let i = 1; i <= 4; i++) {
        const key = `exec:${i}`;
        const pool = execGroups[i - 1];
        if (canSpin(key)) {
          clearAssignedUsers(pool);
          recordSpin(key);
          distributeUsersToTeams(pool, `ผู้บริหารกลุ่มที่ ${i}`);
        }
      }
      launchConfetti();
      saveCurrentState();
      renderExecutiveCards();
      updateTeamCounters();
    }

    function spinAllSections() {
      for (let i = 1; i <= 4; i++) {
        const key = `exec:${i}`;
        const pool = execGroups[i - 1];
        if (canSpin(key)) {
          clearAssignedUsers(pool);
          recordSpin(key);
          distributeUsersToTeams(pool, `ผู้บริหารกลุ่มที่ ${i}`);
        }
      }

      divisionsData.forEach(divObj => {
        const divKey = `div:${divObj.name}`;
        if (!spinHistory[divKey]) spinHistory[divKey] = { count: 0, max: 3 };
        spinHistory[divKey].count = Math.min(3, (spinHistory[divKey].count || 0) + 1);

        Object.values(divObj.sections).forEach(secObj => {
          const secKey = `sec:${divObj.name}:${secObj.name}`;
          if (canSpin(secKey)) {
            clearAssignedUsers(secObj.users);
            recordSpin(secKey);
            distributeUsersToTeams(secObj.users, `${divObj.name} - ${secObj.name}`);
          }
        });
      });

      launchBigConfetti();
      saveCurrentState();
      renderExecutiveCards();
      renderDivisionsFrames();
      updateTeamCounters();
    }

    function resetAllSpins() {
      if (!confirm('คุณต้องการรีเซ็ตผลการสุ่มทั้งหมด และเริ่มใหม่หรือไม่?')) return;

      teamMembers = [[], [], [], []];
      spinHistory = {};
      allUsers.forEach(u => {
        u.assignedTeam = -1;
        u.assignedTag = '';
      });

      try {
        localStorage.removeItem('sport_div_sections_state');
      } catch (e) {}

      renderExecutiveCards();
      renderDivisionsFrames();
      updateTeamCounters();
    }

    // ==========================================
    // 4. UI Renderers
    // ==========================================
    function renderExecutiveCards() {
      for (let i = 1; i <= 4; i++) {
        const pool = execGroups[i - 1];
        const countEl = document.getElementById(`execCount-${i}`);
        const statusEl = document.getElementById(`execStatus-${i}`);
        const listEl = document.getElementById(`execMembersList-${i}`);
        const btnSpin = document.getElementById(`btnSpinExec-${i}`);
        if (!countEl || !statusEl || !listEl || !btnSpin) continue;

        countEl.innerText = pool.length;

        const assignedCount = pool.filter(u => u.assignedTeam >= 0).length;
        const isDone = assignedCount === pool.length && pool.length > 0;
        const key = `exec:${i}`;
        const spinCount = getSpinCount(key);
        const remainingSpins = Math.max(0, 3 - spinCount);

        if (spinCount >= 3) {
          statusEl.innerHTML = '<span class="text-emerald-700 font-bold bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200"><i class="fa-solid fa-lock text-xs"></i> สุ่มครบ 3 ครั้งแล้ว</span>';
          btnSpin.disabled = true;
          btnSpin.className = 'px-3 py-1.5 rounded-xl bg-slate-200 text-slate-400 font-bold text-xs cursor-not-allowed flex items-center gap-1.5';
          btnSpin.innerHTML = '<i class="fa-solid fa-lock text-xs"></i><span>ครบ 3/3 ครั้ง</span>';
        } else if (isDone) {
          statusEl.innerHTML = `<span class="text-indigo-700 font-semibold bg-indigo-50 px-2 py-0.5 rounded-full border border-indigo-200"><i class="fa-solid fa-check"></i> สุ่มแล้ว (ครั้งที่ ${spinCount}/3)</span>`;
          btnSpin.disabled = false;
          btnSpin.className = 'px-3 py-1.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs shadow-xs transition active:scale-95 cursor-pointer flex items-center gap-1.5';
          btnSpin.innerHTML = `<i class="fa-solid fa-rotate text-xs"></i><span>สุ่มใหม่ (เหลือ ${remainingSpins})</span>`;
        } else {
          statusEl.innerHTML = '<span class="text-slate-400 font-semibold">ยังไม่ได้สุ่ม</span>';
          btnSpin.disabled = false;
          btnSpin.className = 'px-3.5 py-1.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-white font-bold text-xs shadow-xs transition active:scale-95 cursor-pointer flex items-center gap-1.5';
          btnSpin.innerHTML = '<i class="fa-solid fa-dice"></i><span>สุ่มกลุ่มนี้ (3 ครั้ง)</span>';
        }

        listEl.innerHTML = pool.map(u => {
          const t = u.assignedTeam;
          const team = t >= 0 ? TEAMS[t] : null;

          return `
            <div class="bg-white p-2.5 rounded-xl border ${team ? team.bgBadge.split(' ')[2] : 'border-slate-200'} flex items-center justify-between gap-2 shadow-2xs">
              <div class="flex items-center gap-2.5 min-w-0">
                ${renderAvatarHtml(u, 'w-8 h-8', 'text-xs')}
                <div class="min-w-0">
                  <div class="text-xs font-bold text-slate-900 truncate leading-tight">${u.name}</div>
                  <div class="text-[10px] text-slate-500 truncate leading-tight mt-0.5">${u.position} ${u.age ? `(${u.age} ปี)` : ''}</div>
                </div>
              </div>
              <div>
                ${team ? `
                  <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold ${team.bgBadge}">
                    <span class="w-1.5 h-1.5 rounded-full ${team.dotBg}"></span>
                    ${team.name}
                  </span>
                ` : `
                  <span class="text-[10px] text-slate-400 italic">รอสุ่ม</span>
                `}
              </div>
            </div>
          `;
        }).join('');
      }
    }

    function renderDivisionsFrames() {
      const container = document.getElementById('divisionsFramesList');
      if (!container) return;

      container.innerHTML = divisionsData.map((divObj, divIdx) => {
        const divName = divObj.name;
        const theme = getDivisionTheme(divIdx);
        const secList = Object.values(divObj.sections);
        const totalInDiv = secList.reduce((sum, s) => sum + s.users.length, 0);
        const totalAssignedInDiv = secList.reduce((sum, s) => sum + s.users.filter(u => u.assignedTeam >= 0).length, 0);
        const isDivAllDone = totalAssignedInDiv === totalInDiv && totalInDiv > 0;
        const divKey = `div:${divName}`;
        const divSpinCount = getSpinCount(divKey);
        const divRemaining = Math.max(0, 3 - divSpinCount);

        // กรณีสำนักที่มีส่วนเดียว (Single Section)
        const isSingleSection = secList.length === 1;
        const singleSecObj = isSingleSection ? secList[0] : null;
        const singleSecName = singleSecObj ? singleSecObj.name : '';
        const singleSecKey = singleSecObj ? `sec:${divName}:${singleSecName}` : '';
        const singleSecSpinCount = singleSecObj ? getSpinCount(singleSecKey) : 0;
        const singleSecRemaining = Math.max(0, 3 - singleSecSpinCount);

        return `
          <!-- กรอบใหญ่ของสำนัก/ศูนย์/กลุ่ม: ${divName} (ดีไซน์ Modern Floating Card พร้อมเส้นสายและเงา) -->
          <div class="bg-white rounded-3xl border ${isDivAllDone ? 'border-emerald-300 ring-2 ring-emerald-100 shadow-md' : theme.border + ' shadow-sm hover:shadow-md'} overflow-hidden transition-all duration-300">
            
            <!-- ส่วนหัวของกรอบสำนัก/ศูนย์/กลุ่ม -->
            <div class="p-4 sm:p-5 ${theme.headerBg} border-b ${theme.border} flex flex-col sm:flex-row sm:items-center justify-between gap-3">
              <div class="flex items-center gap-3.5">
                <span class="w-12 h-12 rounded-2xl ${isDivAllDone ? 'bg-gradient-to-tr from-emerald-600 to-teal-500 text-white shadow-md shadow-emerald-500/25 ring-2 ring-white' : theme.iconBg} flex items-center justify-center font-bold text-lg shrink-0">
                  <i class="fa-solid fa-sitemap"></i>
                </span>
                <div>
                  <div class="flex items-center gap-2.5 flex-wrap">
                    <h3 class="font-extrabold text-base sm:text-lg ${theme.titleColor} font-heading tracking-tight">
                      ${divName}
                    </h3>
                    <span class="text-xs font-semibold text-slate-500 bg-white/80 px-2 py-0.5 rounded-lg border border-slate-200/60 shadow-2xs">
                      ${totalInDiv} คน${!isSingleSection ? ` &bull; ${secList.length} ส่วนงาน` : ''}
                    </span>
                    ${isDivAllDone ? `
                      <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200 flex items-center gap-1 shadow-2xs">
                        <i class="fa-solid fa-circle-check text-xs text-emerald-600"></i> สุ่มครบแล้ว ${(isSingleSection ? singleSecSpinCount : divSpinCount) > 0 ? `(สุ่มไป ${isSingleSection ? singleSecSpinCount : divSpinCount}/3 ครั้ง)` : ''}
                      </span>
                    ` : `
                      <span class="px-2.5 py-0.5 rounded-full text-xs font-bold ${theme.badge} shadow-2xs">
                        สุ่มแล้ว ${totalAssignedInDiv}/${totalInDiv} คน
                      </span>
                    `}
                  </div>
                  <p class="text-xs text-slate-500 mt-1 flex items-center gap-1.5">
                    <i class="fa-solid fa-circle-info text-slate-400 text-[10px]"></i>
                    <span>${!isSingleSection 
                      ? `ภายในสำนักนี้แบ่งออกเป็น <b>${secList.length}</b> ส่วนงานย่อย สุ่มได้สูงสุด 3 ครั้งตามความพอใจ` 
                      : `บุคลากรในสังกัด <b>${divName}</b> สุ่มได้สูงสุด 3 ครั้งตามความพอใจ`}</span>
                  </p>
                </div>
              </div>

              <!-- ปุ่มควบคุมระดับหัวสำนัก -->
              <div class="flex items-center gap-2 self-end sm:self-auto">
                ${!isSingleSection ? `
                  ${divSpinCount >= 3 ? `
                    <span class="px-4 py-2 rounded-xl bg-slate-100 text-slate-500 font-bold text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs cursor-not-allowed">
                      <i class="fa-solid fa-lock text-xs"></i>
                      <span>ครบโควตา 3/3 ครั้ง</span>
                    </span>
                  ` : isDivAllDone ? `
                    <button onclick="spinWholeDivision('${divName.replace(/'/g, "\\'")}')" class="px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs transition active:scale-95 cursor-pointer flex items-center gap-1.5 shadow-md shadow-amber-500/20">
                      <i class="fa-solid fa-rotate text-xs"></i>
                      <span>สุ่มใหม่ทั้งสำนัก (เหลือ ${divRemaining} ครั้ง)</span>
                    </button>
                  ` : `
                    <button onclick="spinWholeDivision('${divName.replace(/'/g, "\\'")}')" class="px-4 py-2 rounded-xl ${theme.btnWhole} font-bold text-xs transition active:scale-95 cursor-pointer flex items-center gap-1.5 shadow-2xs hover:shadow-xs">
                      <i class="fa-solid fa-dice-d20"></i>
                      <span>สุ่มทุกส่วนในสำนักนี้ (3 ครั้ง)</span>
                    </button>
                  `}
                ` : `
                  <!-- กรณีสำนักที่มีเพียงส่วนเดียว ให้ปุ่มสุ่มมาอยู่ตรงหัวสำนักนี้แทน -->
                  ${singleSecSpinCount >= 3 ? `
                    <span class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs sm:text-sm font-bold bg-slate-100 text-slate-500 border border-slate-200 shadow-2xs cursor-not-allowed">
                      <i class="fa-solid fa-lock text-xs"></i> ครบ 3/3 ครั้ง
                    </span>
                  ` : isDivAllDone ? `
                    <button onclick="spinSpecificSection('${divName.replace(/'/g, "\\'")}', '${singleSecName.replace(/'/g, "\\'")}')" class="px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs sm:text-sm shadow-md shadow-amber-500/20 transition active:scale-95 cursor-pointer flex items-center gap-2" title="กดสุ่มใหม่ได้อีก ${singleSecRemaining} ครั้ง">
                      <i class="fa-solid fa-rotate text-xs"></i>
                      <span>สุ่มใหม่ (เหลือ ${singleSecRemaining} ครั้ง)</span>
                    </button>
                  ` : `
                    <button onclick="spinSpecificSection('${divName.replace(/'/g, "\\'")}', '${singleSecName.replace(/'/g, "\\'")}')" class="px-5 py-2.5 rounded-xl ${theme.btnSec} text-white font-bold text-xs sm:text-sm shadow-md transition active:scale-95 cursor-pointer flex items-center gap-2">
                      <i class="fa-solid fa-dice text-sm animate-bounce-gentle"></i>
                      <span>สุ่มสำนักนี้ (3 ครั้ง)</span>
                    </button>
                  `}
                `}
              </div>
            </div>

            <!-- เนื้อหากรอบสำนัก: กรอบของแต่ละส่วนงานย่อยเรียงต่อกันเป็นแนวนอน (Horizontal Flow) -->
            <div class="p-4 sm:p-5 space-y-4 ${theme.secBg}">
              ${secList.map(secObj => {
                const secName = secObj.name;
                const users = secObj.users;
                const assignedCount = users.filter(u => u.assignedTeam >= 0).length;
                const isSecDone = assignedCount === users.length && users.length > 0;
                const director = users.find(u => u.isDirector);
                const secKey = `sec:${divName}:${secName}`;
                const secSpinCount = getSpinCount(secKey);
                const secRemaining = Math.max(0, 3 - secSpinCount);

                return `
                  <!-- กรอบของแต่ละส่วนงานย่อย (แถวแนวนอน การ์ดสีขาวตัดขอบมนเงาสวยงาม) -->
                  <div class="bg-white rounded-2xl border ${isSecDone ? 'border-emerald-200/90 shadow-xs ring-1 ring-emerald-50' : 'border-slate-200/80 shadow-2xs hover:border-slate-300'} p-3.5 sm:p-4 transition hover:shadow-xs">
                    
                    <!-- ส่วนหัวแถวแนวนอน: ชื่อส่วนงาน, ผอ.ส่วน, จำนวนคน, สรุปเพศ, และปุ่มกดสุ่ม -->
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 pb-3 border-b border-slate-100/90">
                      <div class="flex items-center gap-2.5 sm:gap-3 flex-wrap">
                        <span class="w-8 h-8 rounded-xl ${theme.secIcon} flex items-center justify-center text-xs shrink-0 shadow-2xs">
                          <i class="fa-solid fa-folder-tree"></i>
                        </span>
                        <h4 class="font-bold text-sm sm:text-base text-slate-900 font-heading tracking-tight">
                          ${secName}
                        </h4>
                        <span class="text-xs font-bold text-slate-700 bg-slate-100/80 border border-slate-200/60 px-2.5 py-0.5 rounded-full">
                          ${users.length} คน
                        </span>
                        
                        ${director ? `
                          <span class="text-xs font-semibold text-purple-700 bg-purple-50/80 border border-purple-200/80 px-2.5 py-0.5 rounded-full flex items-center gap-1.5 shadow-2xs">
                            <i class="fa-solid fa-user-tie text-xs text-purple-600"></i>
                            <span>ผอ.ส่วน: <b class="text-slate-900">${director.name}</b> ${director.age ? `<span class="text-slate-500 font-normal">(${director.age} ปี)</span>` : ''}</span>
                          </span>
                        ` : ''}

                        <span class="text-xs text-slate-400 hidden sm:inline-flex items-center gap-1 bg-slate-50 px-2 py-0.5 rounded-md border border-slate-100">
                          <span>ชาย: <b class="text-blue-600 font-en">${users.filter(u => u.gender === 'ชาย').length}</b></span>
                          <span class="text-slate-300">|</span>
                          <span>หญิง: <b class="text-rose-500 font-en">${users.filter(u => u.gender === 'หญิง').length}</b></span>
                        </span>

                        ${secSpinCount > 0 ? `
                          <span class="text-[11px] font-bold px-2 py-0.5 rounded-full ${secSpinCount >= 3 ? 'bg-slate-100 text-slate-600 border border-slate-200' : 'bg-amber-50 text-amber-800 border border-amber-200/80'}">
                            <i class="fa-solid fa-dice mr-1 text-amber-500"></i>สุ่มแล้ว ${secSpinCount}/3 ครั้ง
                          </span>
                        ` : ''}
                      </div>

                      <!-- ปุ่มกดสุ่มเฉพาะส่วนนี้ (แสดงเฉพาะสำนักที่มีหลายส่วนย่อย เพื่อไม่ให้ปุ่มซ้ำซ้อนกับด้านบน) -->
                      ${!isSingleSection ? `
                        <div class="flex items-center gap-2 self-end md:self-auto shrink-0">
                          ${secSpinCount >= 3 ? `
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-slate-100 text-slate-500 border border-slate-200 shadow-2xs cursor-not-allowed">
                              <i class="fa-solid fa-lock text-xs"></i> ครบ 3/3 ครั้ง
                            </span>
                          ` : isSecDone ? `
                            <button onclick="spinSpecificSection('${divName.replace(/'/g, "\\'")}', '${secName.replace(/'/g, "\\'")}')" class="px-3.5 py-1.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs shadow-xs transition active:scale-95 cursor-pointer flex items-center gap-1.5" title="กดสุ่มใหม่ได้อีก ${secRemaining} ครั้ง">
                              <i class="fa-solid fa-rotate text-xs"></i>
                              <span>สุ่มใหม่ (เหลือ ${secRemaining})</span>
                            </button>
                          ` : `
                            <button onclick="spinSpecificSection('${divName.replace(/'/g, "\\'")}', '${secName.replace(/'/g, "\\'")}')" class="px-4 py-2 rounded-xl ${theme.btnSec} text-white font-bold text-xs shadow-xs transition active:scale-95 cursor-pointer flex items-center gap-1.5">
                              <i class="fa-solid fa-dice text-xs animate-bounce-gentle"></i>
                              <span>สุ่มส่วนนี้ (3 ครั้ง)</span>
                            </button>
                          `}
                        </div>
                      ` : ''}
                    </div>

                    <!-- รายชื่อสมาชิกในการ์ดแนวนอนขนาดใหญ่ (Large Horizontal Cards) -->
                    <div class="mt-3.5 flex items-center gap-3 overflow-x-auto pb-2.5 pt-1 px-1 custom-scrollbar">
                      ${users.map(u => {
                        const t = u.assignedTeam;
                        const team = t >= 0 ? TEAMS[t] : null;

                        return `
                          <div class="shrink-0 w-72 sm:w-80 p-3 rounded-2xl border ${team ? team.bgBadge.split(' ')[2] + ' bg-white shadow-2xs hover:shadow-xs' : 'border-slate-200/80 bg-white hover:border-slate-300'} ${u.isDirector ? 'ring-2 ring-purple-300/80 bg-purple-50/30' : ''} flex items-center gap-3 transition-all duration-150 select-none">
                            ${renderAvatarHtml(u, 'w-11 h-11', 'text-sm')}
                            <div class="min-w-0 flex-1">
                              <div class="font-bold text-xs sm:text-sm text-slate-900 truncate flex items-center gap-1.5">
                                <span class="truncate">${u.name}</span>
                                ${u.isDirector ? '<span class="text-[9px] font-bold px-1.5 py-0.2 rounded-md bg-purple-600 text-white shrink-0 shadow-2xs">ผอ.ส่วน</span>' : ''}
                              </div>
                              <div class="text-[11px] text-slate-500 truncate leading-snug mt-0.5">
                                ${u.position || '-'} 
                                ${u.age ? `<span class="font-bold text-indigo-700 bg-indigo-50/80 border border-indigo-100 px-1.5 py-0.2 rounded font-en">${u.age} ปี</span>` : ''}
                              </div>
                              <div class="mt-1.5">
                                ${team ? `
                                  <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg text-xs font-bold ${team.bgBadge}">
                                    <span class="w-2 h-2 rounded-full ${team.dotBg}"></span>
                                    ${team.name}
                                  </span>
                                ` : `
                                  <span class="inline-flex items-center gap-1 text-[11px] text-slate-400 font-medium bg-slate-50 border border-slate-100 px-2 py-0.5 rounded-md">
                                    <i class="fa-regular fa-clock text-[9px]"></i> รอสุ่ม
                                  </span>
                                `}
                              </div>
                            </div>
                          </div>
                        `;
                      }).join('')}
                    </div>

                  </div>
                `;
              }).join('')}
            </div>

          </div>
        `;
      }).join('');
    }

    function updateTeamCounters() {
      let totalAssigned = 0;
      for (let t = 0; t < 4; t++) {
        const members = teamMembers[t];
        const mCount = members.filter(u => u.gender === 'ชาย').length;
        const fCount = members.filter(u => u.gender === 'หญิง').length;
        totalAssigned += members.length;

        // คำนวณอายุเฉลี่ยและช่วงอายุ
        const validAges = members.map(u => u.age).filter(a => a !== null && a !== undefined && !isNaN(a));
        const avgAge = validAges.length > 0 ? (validAges.reduce((sum, a) => sum + a, 0) / validAges.length).toFixed(1) : '0.0';
        const minAge = validAges.length > 0 ? Math.min(...validAges) : '-';
        const maxAge = validAges.length > 0 ? Math.max(...validAges) : '-';

        document.getElementById(`counter-team-${t}`).innerText = members.length;
        document.getElementById(`counter-team-${t}-m`).innerText = mCount;
        document.getElementById(`counter-team-${t}-f`).innerText = fCount;
        document.getElementById(`avg-age-${t}`).innerText = avgAge;
        document.getElementById(`age-range-${t}`).innerText = validAges.length > 0 ? `${minAge}-${maxAge} ปี` : '-';
      }
      document.getElementById('headerAssignedCount').innerText = totalAssigned;
    }

    // ==========================================
    // 5. State Persistence
    // ==========================================
    function saveCurrentState() {
      const payload = {
        timestamp: Date.now(),
        source: 'division_random_seminar',
        spinHistory: spinHistory,
        teams: teamMembers.map((t, tIdx) => t.map(u => ({
          seq: u.seq,
          name: u.name,
          gender: u.gender,
          age: u.age || 0,
          position: u.position,
          division: u.division,
          section: u.section,
          assignedTeam: tIdx,
          assignedTag: u.assignedTag
        })))
      };

      try {
        localStorage.setItem('sport_div_sections_state', JSON.stringify(payload));
        localStorage.setItem('sport_latest_spin', JSON.stringify(payload));
        localStorage.setItem('sport_shared_stage_state', JSON.stringify(payload));
      } catch (e) {}

      fetch('save_state.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      }).catch(() => {});
    }

    function restoreSavedState() {
      let state = null;
      try {
        const raw = localStorage.getItem('sport_div_sections_state');
        if (raw) state = JSON.parse(raw);
      } catch (e) {}

      try {
        if (!state) {
          const rawShared = localStorage.getItem('sport_latest_spin') || localStorage.getItem('sport_shared_stage_state');
          if (rawShared) state = JSON.parse(rawShared);
        }
      } catch (e) {}

      // ถ้าไม่มีใน localStorage ให้ดูจาก window.LATEST_SPIN_DATA ที่โหลดจาก server
      if (!state && window.LATEST_SPIN_DATA && window.LATEST_SPIN_DATA.teams) {
        state = window.LATEST_SPIN_DATA;
      }

      if (!state || !Array.isArray(state.teams) || state.teams.length !== 4) return false;

      const hasPeople = state.teams.some(t => t.length > 0);
      if (!hasPeople) return false;

      if (state.spinHistory && typeof state.spinHistory === 'object') {
        spinHistory = { ...state.spinHistory };
      }

      teamMembers = [[], [], [], []];
      const assignedMap = {};
      state.teams.forEach((members, tIdx) => {
        members.forEach(u => {
          const keyName = (u.name || '').trim();
          const keySeq = String(u.seq || '');
          if (keySeq) assignedMap[keySeq] = { team: tIdx, tag: u.assignedTag || '' };
          if (keyName) assignedMap[keyName] = { team: tIdx, tag: u.assignedTag || '' };
        });
      });

      allUsers.forEach(u => {
        const uName = (u.name || '').trim();
        const uSeq = String(u.seq || '');
        const rec = assignedMap[uSeq] || assignedMap[uName];
        if (rec) {
          u.assignedTeam = rec.team;
          u.assignedTag = rec.tag;
          teamMembers[rec.team].push(u);
        }
      });

      return true;
    }

    // ==========================================
    // 6. Helpers & Export
    // ==========================================
    function shuffleArray(arr) {
      const array = [...arr];
      for (let i = array.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [array[i], array[j]] = [array[j], array[i]];
      }
      return array;
    }

    function launchConfetti() {
      if (typeof confetti === 'function') {
        confetti({ particleCount: 50, spread: 60, origin: { y: 0.7 } });
      }
    }

    function launchBigConfetti() {
      if (typeof confetti === 'function') {
        confetti({ particleCount: 150, spread: 100, origin: { y: 0.6 } });
        setTimeout(() => confetti({ particleCount: 100, spread: 120, origin: { y: 0.5 } }), 400);
      }
    }

    function exportToExcel() {
      if (!teamMembers || teamMembers[0].length === 0) {
        alert('กรุณากดสุ่มก่อนทำการส่งออกข้อมูล');
        return;
      }

      if (typeof exportSportResultExcel === 'function') {
        exportSportResultExcel(teamMembers, {
          fileName: `ผลการสุ่มแบ่งกลุ่ม4สี_สุ่มแยกสำนักส่วนงาน_${new Date().toISOString().slice(0, 10)}.xlsx`,
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
