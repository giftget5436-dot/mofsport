<?php
// stage_random.php - หน้าสุ่มแบ่งกลุ่ม 4 สี แยกตามลำดับขั้น 5 รอบ
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require_once __DIR__ . '/parse_xlsx.php';

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
  <title>ระบบสุ่มแบ่งกลุ่ม 4 สี แยกตามลำดับขั้น 5 รอบ - สำนักงานปลัดกระทรวงการคลัง</title>
  
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
            royal: {
              50: '#eff6ff',
              100: '#dbeafe',
              200: '#bfdbfe',
              300: '#93c5fd',
              400: '#60a5fa',
              500: '#3b82f6',
              600: '#2563eb',
              700: '#1d4ed8',
              800: '#1e40af',
              900: '#0f2759',
              950: '#0b1f48',
            },
            sky: {
              50: '#f0f9ff',
              100: '#e0f2fe',
              200: '#bae6fd',
              300: '#7dd3fc',
              400: '#38bdf8',
              500: '#0ea5e9',
              600: '#0284c7',
              700: '#0369a1',
            }
          }
        }
      }
    }
  </script>

  <style>
    :root {
      --font-prompt: 'Prompt', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
      --font-sarabun: 'Sarabun', sans-serif;
    }
    body {
      font-family: var(--font-prompt);
      background-color: #f8fafc;
      color: #0f172a;
      -webkit-font-smoothing: antialiased;
    }
    
    .bg-mesh {
      background-color: #f8fafc;
      background-image: 
        radial-gradient(at 0% 0%, rgba(2, 132, 199, 0.07) 0px, transparent 50%),
        radial-gradient(at 100% 0%, rgba(139, 92, 246, 0.06) 0px, transparent 50%),
        radial-gradient(at 50% 100%, rgba(244, 63, 94, 0.04) 0px, transparent 50%);
    }

    .glass-nav {
      background: rgba(255, 255, 255, 0.85);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
    }

    .glass-card {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      border: 1px solid rgba(226, 232, 240, 0.9);
    }

    .custom-scroll::-webkit-scrollbar {
      width: 5px;
      height: 5px;
    }
    .custom-scroll::-webkit-scrollbar-track {
      background: #f1f5f9;
      border-radius: 9999px;
    }
    .custom-scroll::-webkit-scrollbar-thumb {
      background: #cbd5e1;
      border-radius: 9999px;
    }
    .custom-scroll::-webkit-scrollbar-thumb:hover {
      background: #94a3b8;
    }

    @keyframes glow-pulse {
      0%, 100% { box-shadow: 0 0 15px rgba(14, 165, 233, 0.35); }
      50% { box-shadow: 0 0 25px rgba(29, 78, 216, 0.55); }
    }
    .btn-glow {
      animation: glow-pulse 3s infinite;
    }

    @keyframes card-pop {
      0% { transform: scale(0.85); opacity: 0; }
      70% { transform: scale(1.03); opacity: 1; }
      100% { transform: scale(1); opacity: 1; }
    }
    .animate-pop {
      animation: card-pop 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
    }

    @keyframes step-pulse {
      0%, 100% { transform: scale(1); }
      50% { transform: scale(1.08); }
    }
    .pulse-active {
      animation: step-pulse 1.8s infinite ease-in-out;
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
<body class="bg-mesh min-h-screen text-slate-800 flex flex-col antialiased selection:bg-sky-500 selection:text-white">

  <!-- แถบ Header หลักแบบ Glassmorphism -->
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
            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200/80 shadow-2xs font-en">
              <span class="w-1.5 h-1.5 rounded-full bg-indigo-500 animate-pulse"></span>
              5 STAGES &bull; AGE BALANCED
            </span>
          </div>
          <p class="text-xs text-slate-500 font-sans flex items-center gap-2 mt-0.5 flex-wrap">
            <span class="text-slate-600 font-medium"><i class="fa-solid fa-building-columns text-slate-400 mr-1"></i>สำนักงานปลัดกระทรวงการคลัง</span>
            <span class="text-slate-300">•</span>
            <span>ทั้งหมด <b id="headerTotalCount" class="text-indigo-700 font-en">0</b> คน</span>
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
          <a href="division_random.php" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold text-purple-700 hover:bg-white hover:shadow-xs transition active:scale-95">
            <i class="fa-solid fa-sitemap text-purple-600"></i>
            <span>สุ่มแยกสำนัก &amp; ส่วน</span>
          </a>
          <a href="stage_random.php" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold text-indigo-800 bg-white shadow-xs transition">
            <i class="fa-solid fa-layer-group text-indigo-600"></i>
            <span>สุ่ม 5 ขั้น</span>
          </a>
        </div>

        <!-- ปุ่มจัดการรายชื่อ -->
        <button onclick="openGlobalManageModal()" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-slate-700 bg-white hover:bg-slate-50 border border-slate-200 rounded-xl shadow-xs transition active:scale-95 cursor-pointer" title="จัดการรายชื่อผู้เข้าร่วม">
          <i class="fa-solid fa-user-gear text-slate-500"></i>
          <span>จัดการรายชื่อ (<span class="user-manage-badge-count text-indigo-600 font-bold">0</span>)</span>
        </button>

        <!-- ปุ่มอัปโหลดไฟล์ใหม่ -->
        <button onclick="openGlobalExcelModal()" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 rounded-xl shadow-xs transition active:scale-95 cursor-pointer">
          <i class="fa-solid fa-cloud-arrow-up text-emerald-600"></i>
          <span>อัปโหลด Excel</span>
        </button>

        <!-- ปุ่ม Export Excel -->
        <button onclick="exportToExcel()" class="inline-flex items-center gap-2 px-4 py-2 text-xs font-bold text-white bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 rounded-xl shadow-md shadow-emerald-600/20 transition active:scale-95 cursor-pointer">
          <i class="fa-solid fa-file-excel text-sm"></i>
          <span>ส่งออก Excel</span>
        </button>

        <!-- ปุ่มรีเซ็ตการสุ่ม -->
        <button onclick="resetAllStages()" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200 rounded-xl shadow-xs transition active:scale-95 cursor-pointer">
          <i class="fa-solid fa-rotate-left text-rose-600"></i>
          <span>เริ่มสุ่มใหม่</span>
        </button>
      </div>

    </div>
  </header>

  <!-- เนื้อหาหลัก -->
  <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 w-full flex-1 flex flex-col gap-6">

    <!-- 1. แผง Stepper แสดง 5 ลำดับขั้น (Stages Progress) -->
    <div class="glass-card rounded-2xl p-5 shadow-sm no-print">
      <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 mb-4 border-b border-slate-100 pb-3">
        <div>
          <h2 class="font-bold text-slate-900 text-base font-heading flex items-center gap-2">
            <i class="fa-solid fa-stairs text-royal-600"></i>
            ขั้นตอนการสุ่มตามลำดับตำแหน่ง (5 รอบการสุ่ม)
          </h2>
          <p class="text-xs text-slate-500 mt-0.5">
            สุ่มกระจายคนลงทั้ง 4 สีอย่างสมดุลทีละรอบ เพื่อให้ได้สัดส่วนผู้บริหารและบุคลากรทุกระดับเท่าๆ กันทุกสี
          </p>
        </div>

        <div class="flex items-center gap-2">
          <button id="btnAutoAll" onclick="autoRunAllStages()" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs font-semibold text-white bg-gradient-to-r from-slate-700 to-slate-900 hover:from-slate-800 hover:to-black rounded-lg shadow-xs transition">
            <i class="fa-solid fa-forward-fast"></i>
            <span>สุ่มรวดเดียวครบ 5 รอบ</span>
          </button>
        </div>
      </div>

      <!-- สเต็ป 5 กล่อง -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3" id="stepperContainer">
        <!-- รอบ 1 -->
        <div id="stepCard-1" class="relative rounded-xl border-2 border-royal-600 bg-royal-50/70 p-3.5 transition-all">
          <div class="flex items-center justify-between mb-1.5">
            <span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-royal-600 text-white">รอบที่ 1</span>
            <span id="stepStatus-1" class="text-[11px] font-semibold text-royal-700 flex items-center gap-1">
              <span class="w-2 h-2 rounded-full bg-royal-600 animate-ping"></span> พร้อมสุ่ม
            </span>
          </div>
          <h3 class="font-bold text-slate-900 text-xs sm:text-sm">รองปลัด & หัวหน้าผู้ตรวจ</h3>
          <p class="text-[11px] text-slate-500 mt-0.5">รองปลัด (4) & หัวหน้าผู้ตรวจ (1)</p>
          <div class="mt-2.5 pt-2 border-t border-royal-200/60 flex items-center justify-between text-xs">
            <span class="text-slate-500">จำนวน:</span>
            <span id="stepCount-1" class="font-bold text-royal-700">5 คน</span>
          </div>
        </div>

        <!-- รอบ 2 -->
        <div id="stepCard-2" class="relative rounded-xl border border-slate-200 bg-white/70 p-3.5 transition-all opacity-75">
          <div class="flex items-center justify-between mb-1.5">
            <span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-slate-200 text-slate-700">รอบที่ 2</span>
            <span id="stepStatus-2" class="text-[11px] font-semibold text-slate-400">รอรอบ 1</span>
          </div>
          <h3 class="font-bold text-slate-900 text-xs sm:text-sm">ผู้ตรวจราชการ & ที่ปรึกษา</h3>
          <p class="text-[11px] text-slate-500 mt-0.5">ผู้ตรวจราชการ (5) & ที่ปรึกษา (3)</p>
          <div class="mt-2.5 pt-2 border-t border-slate-100 flex items-center justify-between text-xs">
            <span class="text-slate-500">จำนวน:</span>
            <span id="stepCount-2" class="font-bold text-slate-700">8 คน</span>
          </div>
        </div>

        <!-- รอบ 3 -->
        <div id="stepCard-3" class="relative rounded-xl border border-slate-200 bg-white/70 p-3.5 transition-all opacity-75">
          <div class="flex items-center justify-between mb-1.5">
            <span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-slate-200 text-slate-700">รอบที่ 3</span>
            <span id="stepStatus-3" class="text-[11px] font-semibold text-slate-400">รอรอบ 2</span>
          </div>
          <h3 class="font-bold text-slate-900 text-xs sm:text-sm">ผอ.สำนัก & ผู้เชี่ยวชาญ</h3>
          <p class="text-[11px] text-slate-500 mt-0.5">ผู้อำนวยการสำนัก/ศูนย์/กอง (6), เชี่ยวชาญ (6)</p>
          <div class="mt-2.5 pt-2 border-t border-slate-100 flex items-center justify-between text-xs">
            <span class="text-slate-500">จำนวน:</span>
            <span id="stepCount-3" class="font-bold text-slate-700">12 คน</span>
          </div>
        </div>

        <!-- รอบ 4 -->
        <div id="stepCard-4" class="relative rounded-xl border border-slate-200 bg-white/70 p-3.5 transition-all opacity-75">
          <div class="flex items-center justify-between mb-1.5">
            <span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-slate-200 text-slate-700">รอบที่ 4</span>
            <span id="stepStatus-4" class="text-[11px] font-semibold text-slate-400">รอรอบ 3</span>
          </div>
          <h3 class="font-bold text-slate-900 text-xs sm:text-sm">ผอ.ส่วน</h3>
          <p class="text-[11px] text-slate-500 mt-0.5">ผู้อำนวยการส่วน (18 ท่าน)</p>
          <div class="mt-2.5 pt-2 border-t border-slate-100 flex items-center justify-between text-xs">
            <span class="text-slate-500">จำนวน:</span>
            <span id="stepCount-4" class="font-bold text-slate-700">18 คน</span>
          </div>
        </div>

        <!-- รอบ 5 -->
        <div id="stepCard-5" class="relative rounded-xl border border-slate-200 bg-white/70 p-3.5 transition-all opacity-75">
          <div class="flex items-center justify-between mb-1.5">
            <span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-slate-200 text-slate-700">รอบที่ 5</span>
            <span id="stepStatus-5" class="text-[11px] font-semibold text-slate-400">รอรอบ 4</span>
          </div>
          <h3 class="font-bold text-slate-900 text-xs sm:text-sm">คนที่เหลือทั้งหมด</h3>
          <p class="text-[11px] text-slate-500 mt-0.5">เกลี่ยตามสำนัก/กอง และ ชาย-หญิง</p>
          <div class="mt-2.5 pt-2 border-t border-slate-100 flex items-center justify-between text-xs">
            <span class="text-slate-500">จำนวน:</span>
            <span id="stepCount-5" class="font-bold text-slate-700">426 คน</span>
          </div>
        </div>
      </div>

      <!-- ปุ่มกดสุ่มของ Stage ปัจจุบัน (Main Action Hero) -->
      <div class="mt-5 pt-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-4">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center font-bold text-sm">
            <i class="fa-solid fa-bullhorn"></i>
          </div>
          <div>
            <div class="text-xs text-slate-400 font-medium">ขั้นตอนต่อไปที่จะสุ่ม:</div>
            <div id="currentStagePrompt" class="text-sm font-bold text-slate-900">
              ครั้งที่ 1: สุ่มรองปลัดกระทรวง (4 ท่าน) & หัวหน้าผู้ตรวจ (1 ท่าน) รวม 5 ท่าน
            </div>
          </div>
        </div>

        <div class="flex items-center gap-3 w-full sm:w-auto">
          <!-- ปุ่มกดสุ่มสเต็ปปัจจุบัน -->
          <button id="btnSpinStage" onclick="spinCurrentStage()" class="btn-glow flex-1 sm:flex-none inline-flex items-center justify-center gap-2.5 px-6 py-3 rounded-xl bg-gradient-to-r from-royal-600 via-royal-700 to-sky-600 hover:from-royal-700 hover:to-sky-700 text-white font-bold text-sm shadow-lg shadow-royal-600/30 transition transform hover:-translate-y-0.5 active:translate-y-0">
            <i class="fa-solid fa-dice text-base animate-bounce"></i>
            <span id="btnSpinText">กดสุ่มรอบที่ 1 ทันที</span>
          </button>
        </div>
      </div>
    </div>

    <!-- 2. กล่องแสดงไฮไลท์การสุ่มรอบล่าสุด (Latest Reveal Showcase) -->
    <div id="latestRevealBox" class="hidden glass-card rounded-2xl p-5 shadow-sm border border-amber-200/80 bg-gradient-to-r from-amber-50/60 via-white to-sky-50/60 no-print animate-pop">
      <div class="flex items-center justify-between mb-3">
        <h3 class="font-bold text-slate-900 text-sm font-heading flex items-center gap-2">
          <i class="fa-solid fa-sparkles text-amber-500"></i>
          <span id="latestRevealTitle">ผลการสุ่มรอบล่าสุด</span>
        </h3>
        <span id="latestRevealBadge" class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-amber-100 text-amber-800">
          สุ่มสำเร็จ
        </span>
      </div>
      <div id="latestRevealList" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
        <!-- รายการคนที่เพิ่งสุ่มในรอบนี้จะแสดงที่นี่ -->
      </div>
    </div>

    <!-- 3. แผงสถิติรวม 4 สี (Team Balance Snapshot) -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
      <!-- สีแดง -->
      <div class="bg-white rounded-2xl p-4 border border-[#e51c23]/30 shadow-xs relative overflow-hidden">
        <div class="absolute top-0 right-0 w-20 h-20 bg-[#e51c23]/10 rounded-full blur-xl -mr-6 -mt-6"></div>
        <div class="flex items-center justify-between mb-2">
          <div class="flex items-center gap-2">
            <span class="w-3.5 h-3.5 rounded-full bg-[#e51c23] ring-4 ring-red-100"></span>
            <span class="font-bold text-[#e51c23] text-sm">สีแดง</span>
          </div>
          <span class="text-xs font-semibold text-[#e51c23] bg-red-50 px-2 py-0.5 rounded-full">Red</span>
        </div>
        <div class="flex items-baseline gap-2 mt-1">
          <span id="count-0" class="text-2xl sm:text-3xl font-extrabold text-slate-900 font-en">0</span>
          <span class="text-xs text-slate-400">คน</span>
        </div>
        <div class="mt-2 pt-2 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500">
          <span>ชาย: <strong id="male-0" class="text-slate-800">0</strong></span>
          <span>หญิง: <strong id="female-0" class="text-slate-800">0</strong></span>
          <span>ผู้บริหาร: <strong id="vip-0" class="text-amber-600">0</strong></span>
        </div>
      </div>

      <!-- สีชมพู -->
      <div class="bg-white rounded-2xl p-4 border border-[#e91e63]/30 shadow-xs relative overflow-hidden">
        <div class="absolute top-0 right-0 w-20 h-20 bg-[#e91e63]/10 rounded-full blur-xl -mr-6 -mt-6"></div>
        <div class="flex items-center justify-between mb-2">
          <div class="flex items-center gap-2">
            <span class="w-3.5 h-3.5 rounded-full bg-[#e91e63] ring-4 ring-pink-100"></span>
            <span class="font-bold text-[#e91e63] text-sm">สีชมพู</span>
          </div>
          <span class="text-xs font-semibold text-[#e91e63] bg-pink-50 px-2 py-0.5 rounded-full">Pink</span>
        </div>
        <div class="flex items-baseline gap-2 mt-1">
          <span id="count-1" class="text-2xl sm:text-3xl font-extrabold text-slate-900 font-en">0</span>
          <span class="text-xs text-slate-400">คน</span>
        </div>
        <div class="mt-2 pt-2 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500">
          <span>ชาย: <strong id="male-1" class="text-slate-800">0</strong></span>
          <span>หญิง: <strong id="female-1" class="text-slate-800">0</strong></span>
          <span>ผู้บริหาร: <strong id="vip-1" class="text-amber-600">0</strong></span>
        </div>
      </div>

      <!-- สีเขียว -->
      <div class="bg-white rounded-2xl p-4 border border-[#259b24]/30 shadow-xs relative overflow-hidden">
        <div class="absolute top-0 right-0 w-20 h-20 bg-[#259b24]/10 rounded-full blur-xl -mr-6 -mt-6"></div>
        <div class="flex items-center justify-between mb-2">
          <div class="flex items-center gap-2">
            <span class="w-3.5 h-3.5 rounded-full bg-[#259b24] ring-4 ring-green-100"></span>
            <span class="font-bold text-[#259b24] text-sm">สีเขียว</span>
          </div>
          <span class="text-xs font-semibold text-[#259b24] bg-green-50 px-2 py-0.5 rounded-full">Green</span>
        </div>
        <div class="flex items-baseline gap-2 mt-1">
          <span id="count-2" class="text-2xl sm:text-3xl font-extrabold text-slate-900 font-en">0</span>
          <span class="text-xs text-slate-400">คน</span>
        </div>
        <div class="mt-2 pt-2 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500">
          <span>ชาย: <strong id="male-2" class="text-slate-800">0</strong></span>
          <span>หญิง: <strong id="female-2" class="text-slate-800">0</strong></span>
          <span>ผู้บริหาร: <strong id="vip-2" class="text-amber-600">0</strong></span>
        </div>
      </div>

      <!-- สีเหลือง -->
      <div class="bg-white rounded-2xl p-4 border border-[#fbc02d]/40 shadow-xs relative overflow-hidden">
        <div class="absolute top-0 right-0 w-20 h-20 bg-[#ffeb3b]/20 rounded-full blur-xl -mr-6 -mt-6"></div>
        <div class="flex items-center justify-between mb-2">
          <div class="flex items-center gap-2">
            <span class="w-3.5 h-3.5 rounded-full bg-[#fbc02d] ring-4 ring-amber-100"></span>
            <span class="font-bold text-amber-800 text-sm">สีเหลือง</span>
          </div>
          <span class="text-xs font-semibold text-amber-800 bg-yellow-100 px-2 py-0.5 rounded-full">Yellow</span>
        </div>
        <div class="flex items-baseline gap-2 mt-1">
          <span id="count-3" class="text-2xl sm:text-3xl font-extrabold text-slate-900 font-en">0</span>
          <span class="text-xs text-slate-400">คน</span>
        </div>
        <div class="mt-2 pt-2 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500">
          <span>ชาย: <strong id="male-3" class="text-slate-800">0</strong></span>
          <span>หญิง: <strong id="female-3" class="text-slate-800">0</strong></span>
          <span>ผู้บริหาร: <strong id="vip-3" class="text-amber-600">0</strong></span>
        </div>
      </div>
    </div>

    <!-- 4. ส่วนแสดงผลหลัก (Columns 4 สี / ตารางสรุป) -->
    <div class="space-y-4">
      <!-- ตัวสลับมุมมองและช่องค้นหา -->
      <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 bg-white rounded-2xl p-3 border border-slate-200/90 shadow-xs no-print">
        <div class="inline-flex p-1 bg-slate-100/90 rounded-xl text-xs font-semibold">
          <button id="btnViewColumns" onclick="switchView('columns')" class="px-3.5 py-1.5 rounded-lg bg-white text-royal-700 shadow-xs font-bold transition">
            <i class="fa-solid fa-table-columns mr-1.5"></i> การ์ด 4 สี
          </button>
          <button id="btnViewTable" onclick="switchView('table')" class="px-3.5 py-1.5 rounded-lg text-slate-600 hover:text-slate-900 transition">
            <i class="fa-solid fa-list mr-1.5"></i> ตารางรายชื่อ
          </button>
          <button id="btnViewBreakdown" onclick="switchView('breakdown')" class="px-3.5 py-1.5 rounded-lg text-slate-600 hover:text-slate-900 transition">
            <i class="fa-solid fa-chart-pie mr-1.5"></i> สรุปตามรอบ
          </button>
        </div>

        <div class="flex items-center gap-2">
          <div class="relative flex-1 sm:w-64">
            <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
            <input type="text" id="searchInput" oninput="handleSearch(this.value)" placeholder="ค้นหาชื่อ, ตำแหน่ง, สำนัก..." class="w-full pl-9 pr-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-royal-500 focus:bg-white focus:outline-none transition">
          </div>
        </div>
      </div>

      <!-- 4.1 มุมมอง Columns 4 สี -->
      <div id="viewColumns" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Render ผ่าน JavaScript -->
      </div>

      <!-- 4.2 มุมมอง ตารางรายชื่อ (Table) -->
      <div id="viewTable" class="hidden bg-white rounded-2xl shadow-sm border border-slate-200/90 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs sm:text-sm text-slate-700">
            <thead class="bg-slate-50/90 text-slate-600 font-semibold text-[11px] uppercase tracking-wider border-b border-slate-200">
              <tr>
                <th class="py-3.5 px-4">ลำดับ</th>
                <th class="py-3.5 px-3 text-center">รูปถ่าย</th>
                <th class="py-3.5 px-4">รอบที่สุ่ม</th>
                <th class="py-3.5 px-4">สีที่สังกัด</th>
                <th class="py-3.5 px-4">ชื่อ - นามสกุล</th>
                <th class="py-3.5 px-4">เพศ</th>
                <th class="py-3.5 px-4">ตำแหน่งงาน</th>
                <th class="py-3.5 px-4">สำนัก / กอง (Division)</th>
                <th class="py-3.5 px-4">ส่วนงานย่อย (Subdivision)</th>
              </tr>
            </thead>
            <tbody id="tableBody" class="divide-y divide-slate-100">
              <!-- Render ผ่าน JavaScript -->
            </tbody>
          </table>
        </div>
      </div>

      <!-- 4.3 มุมมอง สรุปตามรอบ (Breakdown per Stage) -->
      <div id="viewBreakdown" class="hidden bg-white rounded-2xl shadow-sm border border-slate-200/90 p-5">
        <div class="mb-4">
          <h3 class="font-bold text-slate-900 text-base font-heading flex items-center gap-2">
            <i class="fa-solid fa-chart-pie text-royal-600"></i>
            ตารางตรวจสอบการกระจายตัวของแต่ละรอบ (Stage Breakdown)
          </h3>
          <p class="text-xs text-slate-500">
            แสดงจำนวนคนที่ถูกสุ่มกระจายลงแต่ละสีในทั้ง 5 รอบ
          </p>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-xs sm:text-sm text-slate-700">
            <thead class="bg-slate-50 text-slate-600 font-semibold border-b border-slate-200">
              <tr>
                <th class="py-3 px-3 text-left">รอบการสุ่ม (Stage)</th>
                <th class="py-3 px-3 text-center">จำนวนคนทั้งหมด</th>
                <th class="py-3 px-3 text-center text-[#e51c23]">สีแดง</th>
                <th class="py-3 px-3 text-center text-[#e91e63]">สีชมพู</th>
                <th class="py-3 px-3 text-center text-[#259b24]">สีเขียว</th>
                <th class="py-3 px-3 text-center text-amber-700">สีเหลือง</th>
                <th class="py-3 px-3 text-center">สถานะ</th>
              </tr>
            </thead>
            <tbody id="breakdownBody" class="divide-y divide-slate-100">
              <!-- Render ผ่าน JavaScript -->
            </tbody>
          </table>
        </div>
      </div>

    </div>

  </main>

  <!-- หน้าต่าง Modal อัปโหลดไฟล์ใหม่ -->
  <div id="uploadModal" class="fixed inset-0 z-50 bg-[#0b1f48]/60 backdrop-blur-xs hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-2xl overflow-hidden flex flex-col animate-pop">
      
      <!-- ส่วนหัวของ Modal อัปโหลด -->
      <div class="p-5 border-b border-slate-200 flex items-center justify-between bg-slate-50/70">
        <div class="flex items-center gap-3">
          <span class="w-10 h-10 rounded-xl bg-sky-100 text-sky-700 flex items-center justify-center text-lg shadow-xs">
            <i class="fa-solid fa-cloud-arrow-up"></i>
          </span>
          <div>
            <h3 class="font-bold text-lg text-slate-900 font-heading">นำเข้าไฟล์รายชื่อใหม่สำหรับการสุ่ม 5 รอบ</h3>
            <p class="text-xs text-slate-500">รองรับไฟล์ Excel (.xlsx, .xls), CSV (.csv) หรือ JSON (.json)</p>
          </div>
        </div>
        <button onclick="closeUploadModal()" class="text-slate-400 hover:text-slate-600 p-2 text-lg cursor-pointer">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>

      <!-- กล่อง Drag & Drop -->
      <div class="p-6">
        <div id="dropZone" 
             ondragover="handleDragOver(event)" 
             ondragleave="handleDragLeave(event)" 
             ondrop="handleDrop(event)"
             onclick="document.getElementById('universalFileInput').click()"
             class="border-2 border-dashed border-sky-300 hover:border-royal-600 bg-sky-50/50 hover:bg-sky-50 rounded-2xl p-8 text-center cursor-pointer transition-all duration-200 flex flex-col items-center justify-center gap-3 group">
          
          <input type="file" id="universalFileInput" accept=".xlsx, .xls, .csv, .json" onchange="handleUniversalFileUpload(event)" class="hidden">
          
          <div class="w-16 h-16 rounded-2xl bg-white shadow-sm border border-sky-100 flex items-center justify-center text-sky-600 group-hover:scale-110 group-hover:text-royal-600 transition-all duration-200">
            <i class="fa-solid fa-file-arrow-up text-3xl"></i>
          </div>

          <div>
            <p class="text-sm font-semibold text-slate-800">
              ลากไฟล์มาวางที่นี่ หรือ <span class="text-royal-600 underline">คลิกเพื่อเลือกไฟล์</span>
            </p>
            <p class="text-xs text-slate-500 mt-1">
              ไฟล์ Excel รายชื่อทางการกระทรวงการคลัง (สป.กค.) หรือไฟล์ข้อมูลบุคลากร
            </p>
          </div>

          <div class="flex items-center gap-2 mt-2">
            <span class="px-2.5 py-1 bg-white rounded-lg border border-slate-200 text-[11px] font-medium text-slate-600 flex items-center gap-1.5 shadow-2xs">
              <i class="fa-solid fa-file-excel text-emerald-600"></i> .xlsx / .xls
            </span>
            <span class="px-2.5 py-1 bg-white rounded-lg border border-slate-200 text-[11px] font-medium text-slate-600 flex items-center gap-1.5 shadow-2xs">
              <i class="fa-solid fa-file-code text-amber-600"></i> .json
            </span>
            <span class="px-2.5 py-1 bg-white rounded-lg border border-slate-200 text-[11px] font-medium text-slate-600 flex items-center gap-1.5 shadow-2xs">
              <i class="fa-solid fa-file-csv text-blue-600"></i> .csv
            </span>
          </div>
        </div>
      </div>

      <!-- ส่วนท้าย Modal -->
      <div class="p-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between text-xs text-slate-500">
        <span>* เมื่อนำเข้าไฟล์ใหม่ ระบบจะจำแนกบุคคลเข้า 5 ลำดับขั้นให้โดยอัตโนมัติทันที</span>
        <button onclick="closeUploadModal()" class="px-5 py-2 rounded-xl bg-slate-700 hover:bg-slate-800 text-white font-medium text-xs transition cursor-pointer">
          ปิดหน้าต่าง
        </button>
      </div>

    </div>
  </div>

  <!-- Footer -->
  <footer class="bg-white border-t border-slate-200/80 py-6 mt-12 w-full no-print">
    <div class="w-full px-4 text-center text-xs text-slate-500">
      <span>ระบบสุ่มแบ่งกลุ่ม 4 สี &bull; สำนักงานปลัดกระทรวงการคลัง (สป.กค.) กระทรวงการคลัง</span>
    </div>
  </footer>

  <!-- เสียง Audio สังเคราะห์ด้วย Web Audio API -->
  <script>
    let audioCtx = null;
    function playBeep(freq = 440, type = 'sine', duration = 0.08) {
      try {
        if (!audioCtx) audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        if (audioCtx.state === 'suspended') audioCtx.resume();
        const osc = audioCtx.createOscillator();
        const gain = audioCtx.createGain();
        osc.type = type;
        osc.frequency.setValueAtTime(freq, audioCtx.currentTime);
        gain.gain.setValueAtTime(0.08, audioCtx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + duration);
        osc.connect(gain);
        gain.connect(audioCtx.destination);
        osc.start();
        osc.stop(audioCtx.currentTime + duration);
      } catch (e) {}
    }

    function playFanfare() {
      const notes = [261.63, 329.63, 392.00, 523.25];
      notes.forEach((freq, i) => {
        setTimeout(() => playBeep(freq, 'triangle', 0.25), i * 120);
      });
    }

    // ==========================================
    // Core Logic: 5 Stages Definition & Management
    // ==========================================
    const teamNames = ['สีแดง', 'สีชมพู', 'สีเขียว', 'สีเหลือง'];
    const teamTheme = [
      { name: 'สีแดง', bg: 'bg-red-50', border: 'border-[#e51c23]/40', text: 'text-[#e51c23]', badge: 'bg-[#e51c23] text-white', bar: 'bg-[#e51c23]' },
      { name: 'สีชมพู', bg: 'bg-pink-50', border: 'border-[#e91e63]/40', text: 'text-[#e91e63]', badge: 'bg-[#e91e63] text-white', bar: 'bg-[#e91e63]' },
      { name: 'สีเขียว', bg: 'bg-green-50', border: 'border-[#259b24]/40', text: 'text-[#259b24]', badge: 'bg-[#259b24] text-white', bar: 'bg-[#259b24]' },
      { name: 'สีเหลือง', bg: 'bg-yellow-50', border: 'border-[#fbc02d]/50', text: 'text-amber-800', badge: 'bg-[#fbc02d] text-slate-900', bar: 'bg-[#fbc02d]' },
    ];

    // ข้อมูล Stage ทั้ง 5
    const stagesInfo = [
      {
        id: 1,
        title: 'รองปลัดกระทรวง (4 ท่าน) & หัวหน้าผู้ตรวจ (1 ท่าน)',
        sub: 'รองปลัดกระทรวงการคลัง (4 ท่าน) & หัวหน้าผู้ตรวจราชการกระทรวงการคลัง (1 ท่าน) รวม 5 ท่าน',
        tag: 'รอบที่ 1: รองปลัด/หัวหน้าผู้ตรวจ',
        badgeClass: 'bg-purple-100 text-purple-800 border-purple-200',
        icon: 'fa-crown',
        completed: false
      },
      {
        id: 2,
        title: 'ผู้ตรวจราชการ & ที่ปรึกษา',
        sub: 'ผู้ตรวจราชการกระทรวงการคลัง (5 ท่าน) & ที่ปรึกษา (3 ท่าน) รวม 8 ท่าน',
        tag: 'รอบที่ 2: ผู้ตรวจ/ที่ปรึกษา',
        badgeClass: 'bg-indigo-100 text-indigo-800 border-indigo-200',
        icon: 'fa-medal',
        completed: false
      },
      {
        id: 3,
        title: 'ผอ.สำนัก & ผู้เชี่ยวชาญ',
        sub: 'ผู้อำนวยการสำนัก/ศูนย์/กอง (6 ท่าน), ผู้เชี่ยวชาญ (6 ท่าน) รวม 12 ท่าน',
        tag: 'รอบที่ 3: ผอ.สำนัก/ผู้เชี่ยวชาญ',
        badgeClass: 'bg-blue-100 text-blue-800 border-blue-200',
        icon: 'fa-award',
        completed: false
      },
      {
        id: 4,
        title: 'ผอ.ส่วน',
        sub: 'ผู้อำนวยการส่วน (18 ท่าน)',
        tag: 'รอบที่ 4: ผอ.ส่วน',
        badgeClass: 'bg-sky-100 text-sky-800 border-sky-200',
        icon: 'fa-user-tie',
        completed: false
      },
      {
        id: 5,
        title: 'คนที่เหลือทั้งหมด',
        sub: 'เกลี่ยตามสำนัก/กอง (Division) และเพศ ชาย-หญิง เท่ากันทุกสี (426 ท่าน)',
        tag: 'รอบที่ 5: บุคลากรทั่วไป',
        badgeClass: 'bg-slate-100 text-slate-800 border-slate-200',
        icon: 'fa-users',
        completed: false
      }
    ];

    let allUsers = [];
    let stagePools = [[], [], [], [], []]; // Users in stages 1-5
    let currentTeams = [[], [], [], []];   // Teams 0, 1, 2, 3
    let currentStageIndex = 0;             // 0 = Stage 1, 1 = Stage 2, ...
    let currentView = 'columns';
    let currentSearch = '';

    // ==========================================
    // Avatar / Photo Management System
    // ==========================================
    let userAvatars = {};
    let currentAvatarTargetKey = null;
    let currentAvatarTargetName = '';

    function initAvatarSystem() {
      if (window.INITIAL_USER_AVATARS && typeof window.INITIAL_USER_AVATARS === 'object') {
        userAvatars = { ...window.INITIAL_USER_AVATARS };
      }
      try {
        const local = localStorage.getItem('sport_user_avatars');
        if (local) {
          const parsed = JSON.parse(local);
          userAvatars = { ...userAvatars, ...parsed };
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

    function renderAvatarHtml(u, sizeClass = 'w-11 h-11', iconSizeClass = 'text-base') {
      const key = getPersonKey(u);
      const url = getAvatarUrl(key);
      const isMale = u.gender === 'ชาย';
      const bgClass = isMale ? 'bg-blue-50 text-blue-500' : 'bg-rose-50 text-rose-500';
      const iconClass = isMale ? 'fa-user-tie' : 'fa-user';
      const safeName = (u.name || '').replace(/'/g, "\\'");

      return `
        <div class="relative shrink-0 cursor-pointer group/avatar select-none" onclick="event.stopPropagation(); triggerAvatarUpload('${key}', '${safeName}')" title="คลิกที่นี่เพื่ออัปโหลด/เปลี่ยนรูปถ่ายของ ${u.name}">
          <div class="${sizeClass} rounded-xl ring-2 ring-slate-100 group-hover/avatar:ring-royal-500 overflow-hidden ${bgClass} flex items-center justify-center transition duration-200 shadow-xs relative bg-white">
            <img id="avatar-img-${key}" src="${url || ''}" class="w-full h-full object-cover ${url ? '' : 'hidden'}" alt="${u.name}" onerror="this.classList.add('hidden'); const fb=document.getElementById('avatar-fallback-${key}'); if(fb) fb.classList.remove('hidden');">
            <div id="avatar-fallback-${key}" class="w-full h-full flex items-center justify-center ${url ? 'hidden' : ''}">
              <i class="fa-solid ${iconClass} ${iconSizeClass}"></i>
            </div>
            <!-- Hover Overlay -->
            <div class="absolute inset-0 bg-black/45 opacity-0 group-hover/avatar:opacity-100 transition-opacity flex flex-col items-center justify-center text-white text-[8px] font-bold backdrop-blur-[0.5px]">
              <i class="fa-solid fa-camera text-[11px] mb-0.5"></i>
              <span>${url ? 'เปลี่ยน' : 'เพิ่มรูป'}</span>
            </div>
          </div>
          <!-- Mini Camera Badge -->
          <span class="absolute -bottom-1 -right-1 w-4 h-4 rounded-full bg-white ring-1 ring-slate-200 text-royal-600 flex items-center justify-center text-[7px] shadow-xs group-hover/avatar:scale-110 transition">
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
        alert('กรุณาเลือกไฟล์รูปภาพ (JPG, PNG, WebP) เท่านั้นครับ');
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

        updateAvatarInDOM(key, dataUrl);
        showAvatarToast(`อัปโหลดรูปถ่ายของ ${currentAvatarTargetName} เรียบร้อยแล้ว!`);

        const formData = new FormData();
        formData.append('userId', key);
        formData.append('avatar', file);
        fetch('upload_avatar.php', {
          method: 'POST',
          body: formData
        })
        .then(res => res.json())
        .then(data => {
          if (data.success && data.avatarUrl) {
            userAvatars[key] = data.avatarUrl;
            try {
              localStorage.setItem('sport_user_avatars', JSON.stringify(userAvatars));
            } catch (e) {}
          }
        })
        .catch(err => console.warn('Server upload error:', err));
      };
      reader.readAsDataURL(file);
    }

    function updateAvatarInDOM(key, url) {
      document.querySelectorAll(`[id^="avatar-img-${key}"]`).forEach(img => {
        img.src = url;
        img.classList.remove('hidden');
      });
      document.querySelectorAll(`[id^="avatar-fallback-${key}"]`).forEach(fb => {
        fb.classList.add('hidden');
      });
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
    // State Synchronization (with index.php)
    // ==========================================
    function saveSyncState() {
      const payload = {
        timestamp: Date.now(),
        currentStageIndex: currentStageIndex,
        teams: currentTeams.map(team => team.map(u => ({
          ...u,
          seq: u.seq || u.id,
          name: u.name,
          gender: u.gender,
          age: u.age || (u.raw && u.raw.age) || 0,
          position: u.position,
          division: u.division,
          section: u.section || u.subdiv || u.subdiv1 || '',
          assignedTeam: u.assignedTeam,
          assignedStage: u.assignedStage,
          raw: u.raw || u
        }))),
        stagesCompleted: stagesInfo.map(s => s.completed),
        source: 'stage_random'
      };

      try {
        localStorage.setItem('sport_shared_stage_state', JSON.stringify(payload));
        localStorage.setItem('sport_latest_spin', JSON.stringify(payload));
        localStorage.setItem('sport_div_sections_state', JSON.stringify(payload));
      } catch (e) {}

      try {
        fetch('save_state.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        }).catch(() => {});
      } catch (e) {}
    }

    function restoreSavedState() {
      let state = null;
      try {
        const raw = localStorage.getItem('sport_shared_stage_state');
        if (raw) state = JSON.parse(raw);
      } catch (e) {}

      if (!state && window.LATEST_SPIN_DATA && window.LATEST_SPIN_DATA.teams) {
        state = window.LATEST_SPIN_DATA;
      }

      if (state && Array.isArray(state.teams) && state.teams.length === 4) {
        const hasPeople = state.teams.some(t => t.length > 0);
        if (hasPeople) {
          currentTeams = state.teams.map(team => team.map(u => ({
            ...u,
            gender: detectGender(u)
          })));
          currentStageIndex = typeof state.currentStageIndex === 'number' ? state.currentStageIndex : 5;
          if (Array.isArray(state.stagesCompleted)) {
            state.stagesCompleted.forEach((comp, idx) => {
              if (stagesInfo[idx]) stagesInfo[idx].completed = comp;
            });
          } else {
            for (let i = 0; i < currentStageIndex; i++) {
              if (stagesInfo[i]) stagesInfo[i].completed = true;
            }
          }
          return true;
        }
      }
      return false;
    }

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

    // จำแนกบุคคลลงใน 5 Stages
    function classifyUsersIntoStages(rawList) {
      stagePools = [[], [], [], [], []];

      rawList.forEach((u, idx) => {
        const pos = (u.POSITION || u.position || '').trim();
        const name = (u.name || '').trim();
        const div = (u.division || '').trim();
        const sub = (u.subdiv1 || '').trim();
        const gender = detectGender(u);

        const item = {
          ...u,
          id: idx,
          name: name,
          position: pos,
          division: div,
          subdiv: sub,
          gender: gender,
          assignedTeam: -1,
          assignedStage: -1
        };

        // Stage 1: รองปลัด (4 ท่าน) + หัวหน้าผู้ตรวจ (1 ท่าน) รวม 5 ท่าน
        if (/(รองปลัด|หัวหน้าผู้ตรวจ)/u.test(pos) || name.includes('ธีรลักษ์ แสงสนิท')) {
          item.stageId = 1;
          stagePools[0].push(item);
        }
        // Stage 2: ผู้ตรวจราชการ และ ที่ปรึกษา เท่านั้น (5 ท่าน - ไม่รวม อธิบดี, ปลัด, หรือหน้าห้อง)
        else if (/(ผู้ตรวจราชการ|ที่ปรึกษา)/u.test(pos) && 
                 !/(อธิบดี|ปลัด|เจ้าพนักงาน|พนักงาน|นักวิชาการ|นิติกร|เศรษฐกร|หน้าห้อง)/u.test(pos)) {
          item.stageId = 2;
          stagePools[1].push(item);
        }
        // Stage 3: ผอ.สำนัก, ผอ.ศูนย์, ผอ.กอง, ผู้เชี่ยวชาญ / เชี่ยวชาญ
        else if (/(ผู้อำนวยการสำนัก|ผู้อำนวยการศูนย์|ผู้อำนวยการกอง|ผู้อำนวยการสำนักงาน|ผอ\.สำนัก|เชี่ยวชาญ)/u.test(pos)) {
          item.stageId = 3;
          stagePools[2].push(item);
        }
        // Stage 4: ผอ.ส่วน
        else if (/(ผู้อำนวยการส่วน|ผอ\.ส่วน)/u.test(pos)) {
          item.stageId = 4;
          stagePools[3].push(item);
        }
        // Stage 5: บุคลากรที่เหลือทั้งหมด
        else {
          item.stageId = 5;
          stagePools[4].push(item);
        }
      });

      // อัปเดตตัวเลขแสดงใน Stepper
      for (let i = 0; i < 5; i++) {
        const el = document.getElementById(`stepCount-${i + 1}`);
        if (el) el.innerText = `${stagePools[i].length} คน`;
      }
    }

    // ฟังก์ชัน Fisher-Yates Shuffle
    function shuffleArray(arr) {
      for (let i = arr.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [arr[i], arr[j]] = [arr[j], arr[i]];
      }
      return arr;
    }

    // ==========================================
    // การสุ่มทีละ Stage (Stage Randomization)
    // ==========================================
    function spinCurrentStage() {
      if (currentStageIndex >= 5) {
        alert('สุ่มครบทั้ง 5 รอบเรียบร้อยแล้วครับ! หากต้องการสุ่มใหม่สามารถกดปุ่ม "เริ่มสุ่มใหม่" ได้เลยครับ');
        return;
      }

      const stageNum = currentStageIndex + 1;
      const pool = [...stagePools[currentStageIndex]];
      if (pool.length === 0) {
        advanceToNextStage();
        return;
      }

      playBeep(587.33, 'triangle', 0.15);

      // สุ่มคนใน Pool
      shuffleArray(pool);

      // ถ้าเป็น Stage 1-4 (ผู้บริหาร/ผอ.): เกลี่ยลงทั้ง 4 สีให้เท่าๆ กันพร้อมเกลี่ยอายุ (Age Balancing)
      const assignedThisRound = [];

      function assignStagePoolWithAgeBalancing(subPool, stageId) {
        const males = subPool.filter(u => u.gender === 'ชาย');
        const females = subPool.filter(u => u.gender === 'หญิง');
        const others = subPool.filter(u => u.gender !== 'ชาย' && u.gender !== 'หญิง');

        males.sort((a, b) => (b.age || 38) - (a.age || 38));
        females.sort((a, b) => (b.age || 38) - (a.age || 38));

        const queue = [];
        const maxLen = Math.max(males.length, females.length, others.length);
        for (let i = 0; i < maxLen; i++) {
          if (i < males.length) queue.push(males[i]);
          if (i < females.length) queue.push(females[i]);
          if (i < others.length) queue.push(others[i]);
        }

        queue.forEach(person => {
          const uAge = person.age || 38;
          const uGender = person.gender || '';

          const teamStats = [0, 1, 2, 3].map(t => {
            const countInStage = currentTeams[t].filter(u => u.assignedStage === stageId).length;
            const members = currentTeams[t];
            const genderCount = members.filter(u => u.gender === uGender).length;
            const totalInTeam = members.length;
            const totalAge = members.reduce((sum, m) => sum + (m.age || 38), 0);
            const avgAge = totalInTeam > 0 ? (totalAge / totalInTeam) : 38;
            return { teamId: t, countInStage, genderCount, totalInTeam, avgAge };
          });

          // กฎ 1: เกลี่ยเพศเดียวกันให้เท่ากันก่อน
          const minGenderCount = Math.min(...teamStats.map(s => s.genderCount));
          let candidateTeams = teamStats.filter(s => s.genderCount === minGenderCount);

          // กฎ 2: เกลี่ยจำนวนคนใน stage
          if (candidateTeams.length > 1) {
            const minStageCount = Math.min(...candidateTeams.map(s => s.countInStage));
            candidateTeams = candidateTeams.filter(s => s.countInStage === minStageCount);
          }

          // กฎ 3: เกลี่ยจำนวนคนรวมทั้งหมด
          if (candidateTeams.length > 1) {
            const minTotalCount = Math.min(...candidateTeams.map(s => s.totalInTeam));
            candidateTeams = candidateTeams.filter(s => s.totalInTeam === minTotalCount);
          }

          // กฎ 4: เกลี่ยอายุเฉลี่ยให้สมดุล
          let chosenTeamId = candidateTeams[0].teamId;
          if (candidateTeams.length > 1) {
            candidateTeams.sort((a, b) => {
              if (uAge >= 38) {
                return a.avgAge - b.avgAge; // ส่งไปทีมที่อายุเฉลี่ยต่ำสุด
              } else {
                return b.avgAge - a.avgAge; // ส่งไปทีมที่อายุเฉลี่ยสูงสุด
              }
            });
            chosenTeamId = candidateTeams[0].teamId;
          }

          person.assignedTeam = chosenTeamId;
          person.assignedStage = stageId;
          currentTeams[chosenTeamId].push(person);
          assignedThisRound.push({ person, team: chosenTeamId });
        });
      }

      if (stageNum <= 4) {
        assignStagePoolWithAgeBalancing(pool, stageNum);
      } else {
        // Stage 5: คนที่เหลือทั้งหมด เกลี่ยตามส่วนงานย่อย (Section) และเกลี่ยอายุให้สมดุล (Section diff <= 1, Division diff <= 1, Gender & Age balance)
        const secGroups = {};
        pool.forEach(p => {
          const div = (p.division || 'ส่วนกลาง').trim();
          const sec = (p.section || p.subdiv || p.subdiv1 || 'งานทั่วไป').trim();
          const key = div + ' /// ' + sec;
          if (!secGroups[key]) secGroups[key] = { div, sec, users: [] };
          secGroups[key].users.push(p);
        });

        const sortedSecKeys = Object.keys(secGroups).sort((a, b) => secGroups[b].users.length - secGroups[a].users.length);

        sortedSecKeys.forEach(secKey => {
          const groupObj = secGroups[secKey];
          const groupList = [...groupObj.users];
          shuffleArray(groupList);

          const males = groupList.filter(u => u.gender === 'ชาย');
          const females = groupList.filter(u => u.gender === 'หญิง');
          const others = groupList.filter(u => u.gender !== 'ชาย' && u.gender !== 'หญิง');

          males.sort((a, b) => (b.age || 38) - (a.age || 38));
          females.sort((a, b) => (b.age || 38) - (a.age || 38));

          const queue = [];
          const maxLen = Math.max(males.length, females.length, others.length);
          for (let i = 0; i < maxLen; i++) {
            if (i < males.length) queue.push(males[i]);
            if (i < females.length) queue.push(females[i]);
            if (i < others.length) queue.push(others[i]);
          }

          queue.forEach(person => {
            const uAge = person.age || 38;
            const uGender = person.gender || '';
            const uDiv = groupObj.div;
            const uSec = groupObj.sec;

            const teamStats = [0, 1, 2, 3].map(t => {
              const members = currentTeams[t];
              const count = members.length;
              const secCount = members.filter(m => {
                const mSec = (m.section || m.subdiv || m.subdiv1 || '').trim();
                const mDiv = (m.division || '').trim();
                return mSec === uSec && mDiv === uDiv;
              }).length;
              const divCount = members.filter(m => (m.division || '').trim() === uDiv).length;
              const genderCount = members.filter(m => m.gender === uGender).length;
              const totalAge = members.reduce((sum, m) => sum + (m.age || 38), 0);
              const avgAge = count > 0 ? (totalAge / count) : 38;
              return { teamId: t, count, secCount, divCount, genderCount, avgAge };
            });

            // 1. ส่วนงานย่อยกระจายสมดุลก่อน (diff <= 1 ไม่ซ้ำสี)
            const minSec = Math.min(...teamStats.map(s => s.secCount));
            let candidateTeams = teamStats.filter(s => s.secCount === minSec);

            // 2. สำนัก/กอง กระจายสมดุล
            if (candidateTeams.length > 1) {
              const minDiv = Math.min(...candidateTeams.map(s => s.divCount));
              candidateTeams = candidateTeams.filter(s => s.divCount === minDiv);
            }

            // 3. เกลี่ยเพศ
            if (candidateTeams.length > 1 && (uGender === 'ชาย' || uGender === 'หญิง')) {
              const minG = Math.min(...candidateTeams.map(s => s.genderCount));
              candidateTeams = candidateTeams.filter(s => s.genderCount === minG);
            }

            // 4. จำนวนรวมทีม
            if (candidateTeams.length > 1) {
              const minCount = Math.min(...candidateTeams.map(s => s.count));
              candidateTeams = candidateTeams.filter(s => s.count === minCount);
            }

            // 5. อายุเฉลี่ย
            let chosenTeamId = candidateTeams[0].teamId;
            if (candidateTeams.length > 1) {
              candidateTeams.sort((a, b) => {
                if (uAge >= 38) return a.avgAge - b.avgAge;
                else return b.avgAge - a.avgAge;
              });
              const bestAvg = candidateTeams[0].avgAge;
              const bestCandidates = candidateTeams.filter(c => Math.abs(c.avgAge - bestAvg) < 0.1);
              chosenTeamId = bestCandidates[Math.floor(Math.random() * bestCandidates.length)].teamId;
            }

            person.assignedTeam = chosenTeamId;
            person.assignedStage = stageNum;
            currentTeams[chosenTeamId].push(person);
            assignedThisRound.push({ person, team: chosenTeamId });
          });
        });
      }

      // ทำเอฟเฟกต์ Confetti
      confetti({
        particleCount: 70,
        spread: 60,
        origin: { y: 0.6 }
      });
      playFanfare();

      // บันทึกสถานะว่า Stage นี้เสร็จแล้ว
      stagesInfo[currentStageIndex].completed = true;

      // แสดงกล่องไฮไลท์ผลการสุ่มรอบนี้
      showLatestReveal(stageNum, assignedThisRound);

      // เลื่อนไป Stage ถัดไป และบันทึกสถานะซิงค์
      currentStageIndex++;
      saveSyncState();
      updateStepperUI();
      renderAllViews();
    }

    // แสดงการ์ดไฮไลท์คนที่เพิ่งได้สุ่มในรอบนี้
    function showLatestReveal(stageNum, assignedList) {
      const box = document.getElementById('latestRevealBox');
      const title = document.getElementById('latestRevealTitle');
      const badge = document.getElementById('latestRevealBadge');
      const list = document.getElementById('latestRevealList');

      box.classList.remove('hidden');
      title.innerText = `ผลการสุ่มรอบที่ ${stageNum}: ${stagesInfo[stageNum - 1].title} (${assignedList.length} ท่าน)`;
      badge.innerText = `รอบที่ ${stageNum} สำเร็จแล้ว`;

      // แสดงการ์ดไฮไลท์ (ถ้ามากกว่า 16 คน เช่นรอบ 5 ให้แสดงสรุปตัวอย่าง)
      const displayItems = assignedList.slice(0, 16);
      list.innerHTML = displayItems.map(({ person, team }) => {
        const theme = teamTheme[team];
        return `
          <div class="bg-white rounded-xl p-3 border ${theme.border} shadow-xs flex items-center gap-3 animate-pop">
            ${renderAvatarHtml(person, 'w-11 h-11', 'text-base')}
            <div class="overflow-hidden flex-1">
              <div class="font-bold text-slate-900 text-xs truncate" title="${person.name}">${person.name}</div>
              <div class="text-[11px] text-slate-500 truncate" title="${person.position}">${person.position}</div>
              <div class="text-[10px] ${theme.text} font-semibold mt-0.5">สังกัด: ${theme.name}</div>
            </div>
            <div class="w-7 h-7 rounded-full ${theme.bg} ${theme.text} flex items-center justify-center font-bold text-xs ring-2 ring-white shadow-xs shrink-0">
              ${teamNames[team].split(' ')[0].replace('สี', '')}
            </div>
          </div>
        `;
      }).join('');

      if (assignedList.length > 16) {
        list.innerHTML += `
          <div class="bg-slate-50 rounded-xl p-3 border border-slate-200 flex items-center justify-center text-xs text-slate-500 font-semibold col-span-full">
            และบุคลากรอีก ${assignedList.length - 16} คน ถูกกระจายครบทุกสีอย่างสมดุลเรียบร้อยแล้ว
          </div>
        `;
      }
    }

    // อัปเดต UI ของ Stepper 5 ขั้นตอน
    function updateStepperUI() {
      for (let i = 0; i < 5; i++) {
        const card = document.getElementById(`stepCard-${i + 1}`);
        const status = document.getElementById(`stepStatus-${i + 1}`);

        if (i < currentStageIndex) {
          // สำเร็จแล้ว
          card.className = "relative rounded-xl border border-emerald-300 bg-emerald-50/80 p-3.5 transition-all shadow-xs";
          status.className = "text-[11px] font-bold text-emerald-700 flex items-center gap-1";
          status.innerHTML = `<i class="fa-solid fa-check-circle text-emerald-600"></i> สุ่มแล้ว`;
        } else if (i === currentStageIndex) {
          // กำลังเป็นคิวปัจจุบัน
          card.className = "relative rounded-xl border-2 border-royal-600 bg-royal-50/80 p-3.5 transition-all shadow-sm pulse-active";
          status.className = "text-[11px] font-bold text-royal-700 flex items-center gap-1";
          status.innerHTML = `<span class="w-2 h-2 rounded-full bg-royal-600 animate-ping"></span> พร้อมสุ่ม`;
        } else {
          // ยังไม่ถึงคิว
          card.className = "relative rounded-xl border border-slate-200 bg-white/70 p-3.5 transition-all opacity-70";
          status.className = "text-[11px] font-semibold text-slate-400";
          status.innerText = `รอรอบที่ ${i}`;
        }
      }

      // อัปเดตข้อความบนปุ่มสุ่ม
      const btnSpin = document.getElementById('btnSpinStage');
      const btnSpinText = document.getElementById('btnSpinText');
      const promptEl = document.getElementById('currentStagePrompt');

      if (currentStageIndex < 5) {
        const nextStage = stagesInfo[currentStageIndex];
        const count = stagePools[currentStageIndex].length;
        btnSpin.disabled = false;
        btnSpin.className = "btn-glow flex-1 sm:flex-none inline-flex items-center justify-center gap-2.5 px-6 py-3 rounded-xl bg-gradient-to-r from-royal-600 via-royal-700 to-sky-600 hover:from-royal-700 hover:to-sky-700 text-white font-bold text-sm shadow-lg shadow-royal-600/30 transition transform hover:-translate-y-0.5 active:translate-y-0 cursor-pointer";
        btnSpinText.innerText = `กดสุ่มรอบที่ ${currentStageIndex + 1}: ${nextStage.title}`;
        promptEl.innerText = `ครั้งที่ ${currentStageIndex + 1}: สุ่ม${nextStage.title} (${count} ท่าน)`;
      } else {
        btnSpin.disabled = true;
        btnSpin.className = "flex-1 sm:flex-none inline-flex items-center justify-center gap-2.5 px-6 py-3 rounded-xl bg-emerald-600 text-white font-bold text-sm shadow-md transition cursor-not-allowed";
        btnSpinText.innerHTML = `<i class="fa-solid fa-circle-check"></i> สุ่มครบทั้ง 5 รอบเรียบร้อยแล้ว`;
        promptEl.innerHTML = `<span class="text-emerald-700 font-bold"><i class="fa-solid fa-check-double mr-1"></i> การสุ่มเสร็จสิ้นสมบูรณ์ครบทั้ง ${allUsers.length} คนแล้ว</span>`;
      }
    }

    // สุ่มทั้งหมดรวดเดียวครบ 5 รอบ
    function autoRunAllStages() {
      if (currentStageIndex >= 5) {
        if (!confirm('สุ่มครบ 5 รอบแล้ว ต้องการเริ่มสุ่มใหม่ตั้งแต่รอบที่ 1 หรือไม่?')) return;
        resetAllStages();
      }

      let interval = setInterval(() => {
        if (currentStageIndex < 5) {
          spinCurrentStage();
        } else {
          clearInterval(interval);
        }
      }, 700);
    }

    // รีเซ็ตการสุ่มใหม่ทั้งหมด
    function resetAllStages() {
      currentStageIndex = 0;
      currentTeams = [[], [], [], []];
      stagesInfo.forEach(s => s.completed = false);
      classifyUsersIntoStages(window.INITIAL_USER_DATA);
      document.getElementById('latestRevealBox').classList.add('hidden');
      saveSyncState();
      updateStepperUI();
      renderAllViews();
    }

    // ==========================================
    // Render Views (Columns, Table, Breakdown)
    // ==========================================
    function renderAllViews() {
      updateCounters();
      renderColumnsView();
      renderTableView();
      renderBreakdownView();
    }

    function updateCounters() {
      for (let t = 0; t < 4; t++) {
        const team = currentTeams[t];
        const total = team.length;
        const male = team.filter(u => u.gender === 'ชาย').length;
        const female = team.filter(u => u.gender === 'หญิง').length;
        const vip = team.filter(u => u.assignedStage <= 3).length; // ผู้บริหารรอบ 1-3

        const validAges = team.map(u => parseInt(u.age)).filter(a => a && a > 0);
        let avgAge = 0;
        if (validAges.length > 0) {
          avgAge = (validAges.reduce((sum, a) => sum + a, 0) / validAges.length).toFixed(1);
        }

        document.getElementById(`count-${t}`).innerText = total;
        document.getElementById(`male-${t}`).innerText = male;
        document.getElementById(`female-${t}`).innerText = female;
        
        const vipEl = document.getElementById(`vip-${t}`);
        if (vipEl) {
          vipEl.innerHTML = `${vip} ${avgAge > 0 ? `&bull; เฉลี่ย <b class="text-indigo-600 font-en font-bold">${avgAge}</b> ปี` : ''}`;
        }
      }
    }

    // 1. มุมมอง Columns 4 สี
    function renderColumnsView() {
      const container = document.getElementById('viewColumns');
      container.innerHTML = '';

      for (let t = 0; t < 4; t++) {
        const theme = teamTheme[t];
        let teamList = [...currentTeams[t]];

        // กรองตามการค้นหา
        if (currentSearch) {
          const q = currentSearch.toLowerCase();
          teamList = teamList.filter(u => 
            u.name.toLowerCase().includes(q) ||
            u.position.toLowerCase().includes(q) ||
            u.division.toLowerCase().includes(q) ||
            u.subdiv.toLowerCase().includes(q)
          );
        }

        // จัดเรียงตาม Stage 1 -> 5
        teamList.sort((a, b) => a.assignedStage - b.assignedStage);

        const col = document.createElement('div');
        col.className = `bg-white rounded-2xl border ${theme.border} shadow-sm overflow-hidden flex flex-col`;

        col.innerHTML = `
          <!-- Header ประจำสี -->
          <div class="${theme.bg} p-4 border-b ${theme.border}">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <span class="w-3.5 h-3.5 rounded-full ${theme.bar} ring-4 ring-white"></span>
                <h3 class="font-bold text-slate-900 text-base font-heading">${teamTheme[t].name}</h3>
              </div>
              <span class="text-xs font-bold ${theme.badge} px-2.5 py-1 rounded-full shadow-xs">
                ${teamList.length} คน
              </span>
            </div>
          </div>

          <!-- รายชื่อบุคคลในการ์ด -->
          <div class="p-3 space-y-2.5 overflow-y-auto max-h-[620px] custom-scroll flex-1">
            ${teamList.length === 0 ? `
              <div class="text-center py-12 text-slate-400 text-xs">
                <i class="fa-solid fa-user-clock text-2xl text-slate-300 mb-2 block"></i>
                ยังไม่มีรายชื่อในสีนี้<br>กดปุ่ม "สุ่มรอบที่ ${currentStageIndex + 1}" เพื่อเริ่มสุ่ม
              </div>
            ` : teamList.map((u, i) => {
              const stageData = stagesInfo[(u.assignedStage || 1) - 1];
              return `
                <div class="p-3 rounded-xl border border-slate-100 hover:border-slate-200 bg-white hover:bg-slate-50/80 transition shadow-xs group flex items-start gap-3">
                  <!-- Avatar Frame with Upload -->
                  ${renderAvatarHtml(u, 'w-12 h-12', 'text-lg')}

                  <!-- Person Details -->
                  <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between gap-1 mb-1">
                      <span class="text-[10px] font-bold px-2 py-0.5 rounded-md border ${stageData.badgeClass}">
                        <i class="fa-solid ${stageData.icon} text-[9px] mr-1"></i> ${stageData.tag.split(':')[0]}
                      </span>
                      <span class="text-[10px] font-medium px-1.5 py-0.5 rounded ${u.gender === 'ชาย' ? 'bg-blue-50 text-blue-700' : 'bg-rose-50 text-rose-700'}">
                        ${u.gender}
                      </span>
                    </div>

                    <div class="font-bold text-slate-900 text-xs sm:text-sm group-hover:text-royal-600 transition truncate" title="${u.name}">
                      ${u.name}
                    </div>

                    <div class="text-[11px] text-slate-600 truncate mt-0.5" title="${u.position}">
                      <i class="fa-solid fa-briefcase text-slate-400 text-[10px] mr-1"></i> ${u.position || '-'}
                    </div>

                    <div class="text-[10px] text-slate-400 truncate mt-1 pt-1 border-t border-slate-100" title="${u.division} / ${u.subdiv}">
                      <i class="fa-solid fa-building text-slate-300 mr-1"></i> ${u.division}
                    </div>
                  </div>
                </div>
              `;
            }).join('')}
          </div>
        `;

        container.appendChild(col);
      }
    }

    // 2. มุมมอง Table
    function renderTableView() {
      const tbody = document.getElementById('tableBody');
      tbody.innerHTML = '';

      let allAssigned = [];
      for (let t = 0; t < 4; t++) {
        currentTeams[t].forEach(u => allAssigned.push(u));
      }

      if (currentSearch) {
        const q = currentSearch.toLowerCase();
        allAssigned = allAssigned.filter(u => 
          u.name.toLowerCase().includes(q) ||
          u.position.toLowerCase().includes(q) ||
          u.division.toLowerCase().includes(q) ||
          u.subdiv.toLowerCase().includes(q)
        );
      }

      // เรียงตาม Stage แล้วตามชื่อ
      allAssigned.sort((a, b) => a.assignedStage - b.assignedStage || a.assignedTeam - b.assignedTeam);

      if (allAssigned.length === 0) {
        tbody.innerHTML = `<tr><td colspan="8" class="text-center py-10 text-slate-400">ยังไม่มีรายชื่อที่ถูกสุ่ม หรือไม่พบข้อมูลตรงกับคำค้นหา</td></tr>`;
        return;
      }

      allAssigned.forEach((u, idx) => {
        const theme = teamTheme[u.assignedTeam];
        const stageData = stagesInfo[(u.assignedStage || 1) - 1];
        const tr = document.createElement('tr');
        tr.className = "hover:bg-slate-50 transition border-b border-slate-100";
        tr.innerHTML = `
          <td class="py-3 px-4 text-slate-400 font-en text-xs">${idx + 1}</td>
          <td class="py-3 px-3 text-center">
            ${renderAvatarHtml(u, 'w-9 h-9', 'text-xs')}
          </td>
          <td class="py-3 px-4">
            <span class="inline-flex items-center gap-1 text-[11px] font-bold px-2 py-0.5 rounded border ${stageData.badgeClass}">
              <i class="fa-solid ${stageData.icon} text-[9px]"></i> รอบที่ ${u.assignedStage}
            </span>
          </td>
          <td class="py-3 px-4 font-bold">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold ${theme.badge}">
              <span class="w-2 h-2 rounded-full ${theme.bar}"></span>
              ${theme.name}
            </span>
          </td>
          <td class="py-3 px-4 font-semibold text-slate-900">${u.name}</td>
          <td class="py-3 px-4">
            <span class="px-2 py-0.5 rounded text-xs font-medium ${u.gender === 'ชาย' ? 'bg-blue-50 text-blue-700' : 'bg-rose-50 text-rose-700'}">
              ${u.gender}
            </span>
          </td>
          <td class="py-3 px-4 text-slate-600 text-xs max-w-xs truncate" title="${u.position}">${u.position || '-'}</td>
          <td class="py-3 px-4 text-slate-700 text-xs">${u.division}</td>
          <td class="py-3 px-4 text-slate-500 text-xs">${u.subdiv || '-'}</td>
        `;
        tbody.appendChild(tr);
      });
    }

    // 3. มุมมอง Breakdown per Stage
    function renderBreakdownView() {
      const tbody = document.getElementById('breakdownBody');
      tbody.innerHTML = '';

      for (let s = 1; s <= 5; s++) {
        const stage = stagesInfo[s - 1];
        const totalInPool = stagePools[s - 1].length;

        // นับจำนวนคนแต่ละสีในรอบนี้
        const c0 = currentTeams[0].filter(u => u.assignedStage === s).length;
        const c1 = currentTeams[1].filter(u => u.assignedStage === s).length;
        const c2 = currentTeams[2].filter(u => u.assignedStage === s).length;
        const c3 = currentTeams[3].filter(u => u.assignedStage === s).length;
        const assignedTotal = c0 + c1 + c2 + c3;

        const isDone = assignedTotal === totalInPool && totalInPool > 0;
        const tr = document.createElement('tr');
        tr.className = "hover:bg-slate-50 transition border-b border-slate-100";
        tr.innerHTML = `
          <td class="py-3.5 px-3">
            <div class="font-bold text-slate-900 text-xs sm:text-sm">
              รอบที่ ${s}: ${stage.title}
            </div>
            <div class="text-[11px] text-slate-400 mt-0.5">${stage.sub}</div>
          </td>
          <td class="py-3.5 px-3 text-center font-bold text-slate-900">${totalInPool} คน</td>
          <td class="py-3.5 px-3 text-center font-bold text-rose-600 font-en">${c0}</td>
          <td class="py-3.5 px-3 text-center font-bold text-blue-600 font-en">${c1}</td>
          <td class="py-3.5 px-3 text-center font-bold text-emerald-600 font-en">${c2}</td>
          <td class="py-3.5 px-3 text-center font-bold text-amber-600 font-en">${c3}</td>
          <td class="py-3.5 px-3 text-center">
            ${isDone ? `
              <span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2.5 py-0.5 rounded-full">
                <i class="fa-solid fa-check text-emerald-600"></i> สุ่มครบแล้ว
              </span>
            ` : s === currentStageIndex + 1 ? `
              <span class="inline-flex items-center gap-1 text-[11px] font-bold text-royal-700 bg-royal-50 border border-royal-200 px-2.5 py-0.5 rounded-full">
                <span class="w-1.5 h-1.5 rounded-full bg-royal-600 animate-ping"></span> คิวต่อไป
              </span>
            ` : `
              <span class="text-[11px] font-medium text-slate-400">ยังไม่ได้สุ่ม</span>
            `}
          </td>
        `;
        tbody.appendChild(tr);
      }
    }

    // สลับมุมมอง View
    function switchView(viewName) {
      currentView = viewName;
      document.getElementById('viewColumns').classList.toggle('hidden', viewName !== 'columns');
      document.getElementById('viewTable').classList.toggle('hidden', viewName !== 'table');
      document.getElementById('viewBreakdown').classList.toggle('hidden', viewName !== 'breakdown');

      // ปรับ Style ปุ่ม
      const bCols = document.getElementById('btnViewColumns');
      const bTable = document.getElementById('btnViewTable');
      const bBreak = document.getElementById('btnViewBreakdown');

      [bCols, bTable, bBreak].forEach(b => {
        b.className = "px-3.5 py-1.5 rounded-lg text-slate-600 hover:text-slate-900 transition";
      });

      if (viewName === 'columns') bCols.className = "px-3.5 py-1.5 rounded-lg bg-white text-royal-700 shadow-xs font-bold transition";
      else if (viewName === 'table') bTable.className = "px-3.5 py-1.5 rounded-lg bg-white text-royal-700 shadow-xs font-bold transition";
      else if (viewName === 'breakdown') bBreak.className = "px-3.5 py-1.5 rounded-lg bg-white text-royal-700 shadow-xs font-bold transition";
    }

    function handleSearch(val) {
      currentSearch = val.trim();
      renderColumnsView();
      renderTableView();
    }

    // ส่งออก Excel (ตามรูปแบบไฟล์ต้นแบบ "ตัวอย่างรายชื่อสัมมนา ซ้อมสุ่มสี.xlsx")
    function exportToExcel() {
      let hasData = false;
      for (let t = 0; t < 4; t++) {
        if (currentTeams[t] && currentTeams[t].length > 0) hasData = true;
      }
      if (!hasData) {
        alert('ยังไม่มีรายชื่อที่ถูกสุ่มครับ กรุณากดสุ่มอย่างน้อย 1 รอบก่อนส่งออกไฟล์');
        return;
      }

      if (typeof exportSportResultExcel === 'function') {
        exportSportResultExcel(currentTeams, {
          fileName: `ผลการสุ่มแบ่งกลุ่ม4สี_สุ่ม5รอบ_${new Date().toISOString().slice(0, 10)}.xlsx`,
          teamNames: teamNames
        });
      }
    }

    // ==========================================
    // ระบบอัปโหลดไฟล์ใหม่ (Excel, CSV, JSON)
    // ==========================================
    function openUploadModal() {
      document.getElementById('uploadModal').classList.remove('hidden');
    }

    function closeUploadModal() {
      document.getElementById('uploadModal').classList.add('hidden');
    }

    function handleDragOver(e) {
      e.preventDefault();
      e.stopPropagation();
      document.getElementById('dropZone').classList.add('border-royal-600', 'bg-sky-100/60');
    }

    function handleDragLeave(e) {
      e.preventDefault();
      e.stopPropagation();
      document.getElementById('dropZone').classList.remove('border-royal-600', 'bg-sky-100/60');
    }

    function handleDrop(e) {
      e.preventDefault();
      e.stopPropagation();
      document.getElementById('dropZone').classList.remove('border-royal-600', 'bg-sky-100/60');
      const dt = e.dataTransfer;
      if (dt && dt.files && dt.files.length > 0) {
        processUploadedFile(dt.files[0]);
      }
    }

    function handleUniversalFileUpload(e) {
      const file = e.target.files[0];
      if (file) {
        processUploadedFile(file);
      }
    }

    function processUploadedFile(file) {
      const fileName = file.name.toLowerCase();
      const reader = new FileReader();

      // 1. กรณีเป็นไฟล์ JSON
      if (fileName.endsWith('.json')) {
        reader.onload = function(evt) {
          try {
            const json = JSON.parse(evt.target.result);
            if (!Array.isArray(json)) {
              alert('รูปแบบไฟล์ JSON ไม่ถูกต้อง: ข้อมูลต้องเป็น Array ของรายชื่อ');
              return;
            }
            allUsers = json;
            document.getElementById('headerTotalCount').innerText = allUsers.length;
            resetAllStages();
            closeUploadModal();
            alert(`นำเข้าไฟล์ "${file.name}" สำเร็จเรียบร้อย! พบข้อมูลทั้งหมด ${json.length} คน พร้อมจัดกลุ่ม 5 ขั้นตอนแล้ว`);
          } catch (err) {
            alert('ไม่สามารถอ่านไฟล์ JSON ได้ กรุณาตรวจสอบความถูกต้องของไฟล์');
          }
        };
        reader.readAsText(file, 'UTF-8');
        return;
      }

      // 2. กรณีเป็นไฟล์ Excel (.xlsx, .xls) หรือ CSV (.csv)
      if (fileName.endsWith('.xlsx') || fileName.endsWith('.xls') || fileName.endsWith('.csv')) {
        reader.onload = function(evt) {
          try {
            const data = new Uint8Array(evt.target.result);
            const workbook = XLSX.read(data, { type: 'array' });
            const firstSheetName = workbook.SheetNames[0];
            const worksheet = workbook.Sheets[firstSheetName];
            const aoa = XLSX.utils.sheet_to_json(worksheet, { header: 1, defval: '' });

            if (!aoa || aoa.length === 0) {
              alert('ไฟล์ Excel/CSV ว่างเปล่า หรือไม่พบแถวข้อมูล');
              return;
            }

            const mainDivMap = {
              'สบก': 'สำนักบริหารกลาง',
              'ศทส': 'ศูนย์เทคโนโลยีสารสนเทศและการสื่อสาร',
              'สตป': 'สำนักตรวจสอบและประเมินผล',
              'สนย': 'สำนักนโยบายและยุทธศาสตร์',
              'สบค': 'สำนักบริหารทรัพยากรบุคคล',
              'ศปท': 'ศูนย์ปฏิบัติการต่อต้านการทุจริต',
              'กกม': 'กลุ่มกฎหมาย',
              'กตน': 'กลุ่มตรวจสอบภายใน',
              'กพร': 'กลุ่มพัฒนาระบบบริหาร',
              'สศค': 'สำนักงานเศรษฐกิจการคลัง'
            };

            const subdivToMainMap = {
              'ส่วนพัฒนาบุคคล': 'สำนักบริหารทรัพยากรบุคคล',
              'ส่วนแผนและประเมินผล': 'สำนักตรวจสอบและประเมินผล',
              'ส่วนพัฒนาระบบตรวจสอบ': 'สำนักตรวจสอบและประเมินผล',
              'ส่วนตรวจราชการ': 'สำนักตรวจสอบและประเมินผล',
              'ส่วนบริหารการคลัง': 'สำนักบริหารกลาง',
              'ส่วนอำนวยการ': 'สำนักบริหารกลาง',
              'ส่วนบริหารทั่วไป': 'สำนักบริหารกลาง',
              'ฝ่ายการพัสดุ': 'สำนักบริหารกลาง',
              'ฝ่ายสารบรรณ': 'สำนักบริหารกลาง'
            };

            let currentDept = 'สำนักงานปลัดกระทรวงการคลัง';
            let currentGroup = '';
            let currentUnit = '';
            let currentMainDivision = '';
            const mappedList = [];

            for (let r = 0; r < aoa.length; r++) {
              const row = aoa[r];
              if (!row || row.length === 0) continue;

              const colA = String(row[0] || '').trim();
              const colB = String(row[1] || '').trim();
              const colC = String(row[2] || '').trim();
              const colD = String(row[3] || '').trim();
              const colE = String(row[4] || '').trim();

              if (colA.includes('ชื่อหน่วยงาน')) {
                currentDept = colA.replace(/ชื่อหน่วยงาน\s*:/g, '').trim();
                continue;
              }
              if (colA.includes('กลุ่มบุคลากร')) {
                currentGroup = colA.replace(/กลุ่มบุคลากร\s*:/g, '').trim();
                currentUnit = '';
                currentMainDivision = currentGroup;
                continue;
              }
              if (colA.includes('หน่วยงาน') && !colA.includes('ชื่อหน่วยงาน')) {
                currentUnit = colA.replace(/หน่วยงาน\s*:/g, '').trim();
                currentGroup = '';
                currentMainDivision = '';

                const mBracket = currentUnit.match(/\(([ก-๙.]+)\)/u);
                if (mBracket) {
                  const abbr = mBracket[1].replace(/\./g, '');
                  if (mainDivMap[abbr]) currentMainDivision = mainDivMap[abbr];
                }
                if (!currentMainDivision) {
                  const mPrefix = currentUnit.match(/^([ก-๙]{2,4})\./u);
                  if (mPrefix && mainDivMap[mPrefix[1]]) currentMainDivision = mainDivMap[mPrefix[1]];
                }
                if (!currentMainDivision) {
                  const cleanU = currentUnit.replace(/\./g, '');
                  if (mainDivMap[cleanU]) currentMainDivision = mainDivMap[cleanU];
                }
                if (!currentMainDivision && subdivToMainMap[currentUnit]) {
                  currentMainDivision = subdivToMainMap[currentUnit];
                }
                if (!currentMainDivision) {
                  currentMainDivision = currentUnit;
                }
                continue;
              }

              // แถวรายชื่อที่มีตัวเลขลำดับใน Col A
              if (/^\d+$/.test(colA) && colB && colB !== 'ชื่อ-นามสกุล') {
                const seq = parseInt(colA);
                const name = colB;
                const pos = colC;
                const email = colD;
                const phone = colE;

                let gender = 'ไม่ระบุ';
                let prename = '';
                const mMale = name.match(/^(นาย|ว่าที่ร้อยตรี|ว่าที่ร้อยโท|ร้อยตรี|ร้อยโท|ร้อยตำรวจโท|พลฯ)/u);
                const mFemale = name.match(/^(นางสาว|นาง|ว่าที่ร้อยตำรวจโทหญิง|ว่าที่ร้อยตรีหญิง)/u);
                if (mMale) { gender = 'ชาย'; prename = mMale[1]; }
                else if (mFemale) { gender = 'หญิง'; prename = mFemale[1]; }

                const div = currentMainDivision || currentUnit || currentGroup || 'ส่วนกลาง';
                const subdiv = currentUnit || currentGroup || div;

                let level = 'ทั่วไป';
                const lm = pos.match(/(ทรงคุณวุฒิ|เชี่ยวชาญ|ชำนาญการพิเศษ|ชำนาญการ|ชำนาญงาน|ปฏิบัติการ|ปฏิบัติงาน)/u);
                if (lm) level = lm[1];
                else if (div === 'ผู้บริหารระดับสูง' || /ปลัด|ผู้ตรวจ|ที่ปรึกษา|อธิบดี|ผู้อำนวยการ/u.test(pos)) level = 'ผู้บริหารระดับสูง';
                else if (currentGroup) level = currentGroup;

                mappedList.push({
                  seq: seq,
                  name: name,
                  prename_th: prename,
                  gender: gender,
                  POSITION: pos,
                  position: pos,
                  department: currentDept,
                  division: div,
                  subdiv1: subdiv,
                  levelname_th: level,
                  email: email,
                  phone: phone
                });
              }
            }

            if (mappedList.length === 0) {
              alert('ไม่พบแถวข้อมูลรายชื่อในไฟล์ Excel กรุณาตรวจสอบโครงสร้างไฟล์');
              return;
            }

            allUsers = mappedList;
            document.getElementById('headerTotalCount').innerText = allUsers.length;
            resetAllStages();
            closeUploadModal();
            alert(`นำเข้าไฟล์ Excel "${file.name}" สำเร็จเรียบร้อย! พบข้อมูลทั้งหมด ${mappedList.length} คน พร้อมจำแนกเข้า 5 ลำดับขั้นทันที`);
          } catch (err) {
            console.error(err);
            alert('เกิดข้อผิดพลาดในการอ่านไฟล์ Excel: ' + err.message);
          }
        };
        reader.readAsArrayBuffer(file);
        return;
      }

      alert('กรุณาเลือกไฟล์สกุล .xlsx, .xls, .csv หรือ .json เท่านั้น');
    }

    // เริ่มต้นระบบเมื่อโหลดหน้า
    window.addEventListener('DOMContentLoaded', () => {
      initAvatarSystem();
      if (window.INITIAL_USER_DATA && window.INITIAL_USER_DATA.length > 0) {
        allUsers = window.INITIAL_USER_DATA;
        const activeUsersCount = allUsers.filter(u => u.active !== false).length;
        document.getElementById('headerTotalCount').innerText = activeUsersCount;
        if (typeof initGlobalUserManager === 'function') {
          initGlobalUserManager(allUsers, (updated) => {
            const activeList = updated.filter(u => u.active !== false);
            document.getElementById('headerTotalCount').innerText = activeList.length;
            classifyUsersIntoStages(activeList);

            // คัดกรองคนที่ถูกปิด active ออกจาก currentTeams
            if (Array.isArray(currentTeams)) {
              if (typeof filterTeamsByActiveUsers === 'function') {
                currentTeams = filterTeamsByActiveUsers(currentTeams);
              } else {
                currentTeams = currentTeams.map(t => (t || []).filter(u => u.active !== false));
              }
            }

            saveSyncState();
            updateStepperUI();
            renderAllViews();
          });
        }
        const activeList = allUsers.filter(u => u.active !== false);
        document.getElementById('headerTotalCount').innerText = activeList.length;
        classifyUsersIntoStages(activeList);
        const restored = restoreSavedState();
        if (restored && typeof filterTeamsByActiveUsers === 'function') {
          currentTeams = filterTeamsByActiveUsers(currentTeams);
        }
        updateStepperUI();
        renderAllViews();
      }
    });
  </script>

  <!-- Input สำหรับอัปโหลดรูปภาพประจำตัว -->
  <input type="file" id="globalAvatarInput" accept="image/*" class="hidden" onchange="handleAvatarFileSelected(event)">

  <!-- Toast แจ้งเตือนเมื่ออัปโหลดรูปภาพเสร็จสิ้น -->
  <div id="avatarToast" style="position:fixed;bottom:20px;right:20px;z-index:9999;display:flex;align-items:center;gap:10px;background:#1e293b;color:#fff;padding:12px 16px;border-radius:16px;font-size:13px;transform:translateY(80px);opacity:0;transition:all 0.3s;pointer-events:none;border:1px solid #334155;">
    <span style="width:24px;height:24px;border-radius:50%;background:rgba(16,185,129,0.2);color:#34d399;display:flex;align-items:center;justify-content:center;font-size:12px;">✓</span>
    <span id="avatarToastText">อัปโหลดรูปถ่ายเรียบร้อยแล้ว</span>
  </div>
</body>
</html>

