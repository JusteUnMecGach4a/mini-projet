<?php
/**
 * CORE_SUPERVISOR v10.0 | FINAL AGENTIC EDITION
 * Systèmes : Mail Relais + Telegram Bot + SQL Analytics
 */

// 1. CONFIGURATION GÉNÉRALE
$host = 'localhost'; $port = '13306'; $db = 'bay_monitoring'; 
$user = 'user_PHP'; $pass = 'php_pwd_4';
$alert_email = 'baie_serveur-minipro.stowing699@passmail.net';

// CONFIGURATION TELEGRAM
$tg_token = "8740073248:AAHGxDnfjxHjXcZRyFgROOIdQQx2ZDLCZYU";
$tg_chat_id = "6422145292";

// FICHIERS DE STATUT
$status_file = '/tmp/alert_status.txt';
$time_file   = '/tmp/last_notification_sent.txt';

// Initialisation des variables de crash-protection
$init_logs = []; $labels = []; $temps = []; $hums = []; $relevés = [];
$alert_sent = false;

// Chargement du statut de surveillance (Bouton ON/OFF)
if (!file_exists($status_file)) file_put_contents($status_file, "0");
$is_alert_active = (trim(file_get_contents($status_file)) === "1");

// --- FONCTIONS DE COMMUNICATION ---
function envoyerTelegram($msg, $token, $chat_id) {
    $url = "https://api.telegram.org/bot$token/sendMessage?chat_id=$chat_id&parse_mode=Markdown&text=" . urlencode($msg);
    @file_get_contents($url);
}

// --- LOGIQUE DU BOUTON TOGGLE ---
if (isset($_POST['toggle_alert'])) {
    $is_alert_active = !$is_alert_active;
    file_put_contents($status_file, $is_alert_active ? "1" : "0");
    $state = $is_alert_active ? "✅ INITIALISÉ / SURVEILLANCE ACTIVE" : "🛑 SYSTÈME MIS EN VEILLE";
    
    // Notifications de changement d'état
    $msg = "*[SYSTEM_EVENT]*\n$state\nHeure : " . date('H:i:s');
    envoyerTelegram($msg, $tg_token, $tg_chat_id);
    @mail($alert_email, "[SYSTEM] Alert Engine Change", $msg, "From: supervisor@canova.local");
    
    header("Location: index.php?baie=" . ($_GET['baie'] ?? 0));
    exit;
}

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $init_logs[] = "[" . date('H:i:s') . "] DB_UPLINK: Connexion SQL OK (Port 13306).";

    // Liste des baies pour le filtre
    $stmt_baies = $pdo->query("SELECT id_baie, nom_baie FROM baies ORDER BY id_baie ASC");
    $liste_baies = $stmt_baies->fetchAll(PDO::FETCH_ASSOC);
    $filtre_id = isset($_GET['baie']) ? (int)$_GET['baie'] : 0;

    // --- REQUÊTE ANALYTIQUE (Baie seule ou Moyenne) ---
    if ($filtre_id > 0) {
        $sql = "SELECT m.temperature, m.humidite, m.date_mesure, b.nom_baie FROM mesures m JOIN baies b ON m.id_capteur = b.id_baie WHERE b.id_baie = :id ORDER BY m.date_mesure DESC LIMIT 15";
        $stmt = $pdo->prepare($sql); $stmt->execute(['id' => $filtre_id]);
    } else {
        $sql = "SELECT AVG(temperature) as temperature, AVG(humidite) as humidite, date_mesure, 'MOYENNE SALLE' as nom_baie FROM mesures GROUP BY date_mesure ORDER BY date_mesure DESC LIMIT 15";
        $stmt = $pdo->prepare($sql); $stmt->execute();
    }
    $relevés = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // --- LOGIQUE D'ALERTE CRITIQUE (Mail + Telegram) ---
    if ($is_alert_active && !empty($relevés)) {
        $last_sent = file_exists($time_file) ? (int)file_get_contents($time_file) : 0;
        
        if ((time() - $last_sent) >= 300) { // Rapport toutes les 5 min
            $stmt_scan = $pdo->query("SELECT b.nom_baie, m.temperature, m.humidite FROM baies b JOIN mesures m ON b.id_baie = m.id_capteur WHERE m.id IN (SELECT MAX(id) FROM mesures GROUP BY id_capteur)");
            $etats = $stmt_scan->fetchAll(PDO::FETCH_ASSOC);

            $critical = false;
            $report = "📊 *RAPPORT DE SURVEILLANCE CANOVA*\n----------------------------------\n";
            foreach($etats as $e) {
                $status = ($e['temperature'] > 30 || $e['humidite'] > 75) ? "‼️" : "✅";
                if ($status == "‼️") $critical = true;
                $report .= "$status Baie *".$e['nom_baie']."* : ".$e['temperature']."°C (".$e['humidite']."%)\n";
            }

            // On envoie l'alerte si une baie est critique OU pour le rapport périodique
            envoyerTelegram($report, $tg_token, $tg_chat_id);
            @mail($alert_email, "[REPORT] Status des Baies", $report, "From: supervisor@canova.local");
            file_put_contents($time_file, time());
            $alert_sent = true;
            $init_logs[] = "[" . date('H:i:s') . "] COMM_ENGINE: Alertes transmises (Mail+TG).";
        }
    }

    // Préparation Graphique
    $plt = array_reverse($relevés);
    foreach($plt as $d) { $labels[] = date('H:i:s', strtotime($d['date_mesure'])); $tmp[] = (float)$d['temperature']; $hum[] = (float)$d['humidite']; }

} catch (Exception $e) { $init_logs[] = "[" . date('H:i:s') . "] ERROR: " . $e->getMessage(); }
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="refresh" content="10">
    <title>SUPERVISOR_OS | Control Unit</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@600;900&family=JetBrains+Mono:wght@500;800&display=swap');
        :root { --neon-blue: #00f2ff; --neon-red: #ff0055; --bg-dark: #050810; }
        body { background-color: var(--bg-dark); color: #e2e8f0; font-family: 'JetBrains Mono', monospace; }
        .orbitron { font-family: 'Orbitron', sans-serif; }
        .panel { background: rgba(15, 23, 42, 0.95); border: 2px solid #1e293b; transition: 0.5s; }
        .is-active { border-color: var(--neon-red); box-shadow: 0 0 30px rgba(255, 0, 85, 0.2); }
        select { background: #0f172a !important; border: 2px solid var(--neon-blue) !important; color: white; padding: 0.6rem 2rem; border-radius: 15px; font-weight: 900; }
        .terminal { background: #000; border: 1px solid #1e293b; color: #10b981; font-size: 11px; padding: 15px; border-radius: 20px; }
    </style>
</head>
<body class="p-4 md:p-8">

    <div class="max-w-7xl mx-auto space-y-6">
        
        <!-- HEADER HUD -->
        <header class="flex flex-col lg:flex-row justify-between items-center bg-slate-900 p-8 rounded-[40px] border-2 border-slate-800 gap-8 shadow-2xl">
            <div class="flex items-center gap-8">
                <div class="w-20 h-20 bg-blue-600/10 rounded-3xl flex items-center justify-center border-2 <?= $is_alert_active ? 'border-red-500 shadow-[0_0_20px_red] animate-pulse' : 'border-slate-700' ?>">
                    <i class="fas fa-satellite-dish <?= $is_alert_active ? 'text-red-500' : 'text-[--neon-blue]' ?> text-3xl"></i>
                </div>
                <div>
                    <h1 class="text-4xl font-black orbitron text-white uppercase tracking-tighter">Supervisor <span class="text-cyan-500">.exe</span></h1>
                    <p class="text-sm font-bold <?= $is_alert_active ? 'text-red-500' : 'text-slate-500' ?> tracking-[0.4em] uppercase">
                        System: <?= $is_alert_active ? 'ARMED_FOR_ALERTS' : 'STANDBY_MODE' ?>
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <form method="POST">
                    <button type="submit" name="toggle_alert" class="px-8 py-4 rounded-2xl font-black orbitron text-xs transition-all <?= $is_alert_active ? 'bg-red-600 hover:bg-red-700 text-white' : 'bg-slate-700 hover:bg-blue-600 text-slate-300' ?>">
                        <?= $is_alert_active ? 'DESACTIVER AGENT' : 'ACTIVER AGENT' ?>
                    </button>
                </form>
                <form method="GET">
                    <select name="baie" onchange="this.form.submit()">
                        <option value="0">> MOYENNE_GLOBALE</option>
                        <?php foreach ($liste_baies as $b): ?>
                            <option value="<?= $b['id_baie'] ?>" <?= ($filtre_id == $b['id_baie']) ? 'selected' : '' ?>><?= strtoupper(htmlspecialchars($b['nom_baie'])) ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
        </header>

        <!-- DATA PANELS -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div class="panel rounded-[50px] p-10 border-l-8 <?= (!empty($relevés) && $relevés[0]['temperature'] > 30) ? 'border-red-500' : 'border-emerald-500' ?>">
                <p class="text-xs font-black orbitron mb-2 uppercase tracking-widest">Heat_Telemetry</p>
                <p class="text-7xl font-black"><?= !empty($relevés) ? number_format($relevés[0]['temperature'], 1) : '--' ?>°C</p>
                <div class="mt-8 h-48"><canvas id="tChart"></canvas></div>
            </div>

            <div class="panel rounded-[50px] p-10 border-l-8 border-cyan-500">
                <p class="text-xs font-black orbitron mb-2 uppercase tracking-widest">Hydro_Telemetry</p>
                <p class="text-7xl font-black"><?= !empty($relevés) ? number_format($relevés[0]['humidite'], 0) : '--' ?>%</p>
                <div class="mt-8 h-48"><canvas id="hChart"></canvas></div>
            </div>
        </div>

        <!-- TERMINAL LOGS -->
        <div class="terminal shadow-2xl">
            <p class="text-white border-b border-emerald-950 mb-2 font-black uppercase text-[10px] tracking-widest">Proof_of_Initialization_Logs</p>
            <?php foreach($init_logs as $l): ?><div><?= htmlspecialchars($l) ?></div><?php endforeach; ?>
            <div class="animate-pulse">_</div>
        </div>
    </div>

    <!-- TIMER -->
    <div style="position:fixed; bottom:20px; right:30px; font-size:12px; color:#475569; font-weight:bold; background:rgba(0,0,0,0.8); padding:8px 20px; border-radius:15px; border:1px solid #1e293b;">
        NEXT_REFRESH: <span id="timer" class="text-cyan-500">10</span>S
    </div>

    <script>
        const cfg = { 
            responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } },
            scales: { 
                y: { beginAtZero: false, grace: '15%', ticks: { color: '#475569', font: { size: 14, weight: 'bold' } }, grid: { color: 'rgba(255,255,255,0.05)' } },
                x: { ticks: { color: '#475569', font: { size: 10 } }, grid: { display: false } }
            }
        };
        new Chart(document.getElementById('tChart'), { type:'line', data:{ labels:<?= json_encode($labels) ?>, datasets:[{ data:<?= json_encode($tmp) ?>, borderColor:'#ff0055', borderWidth:6, tension:0.4, fill:true, backgroundColor:'rgba(255, 0, 85, 0.1)', pointRadius:4 }] }, options:cfg });
        new Chart(document.getElementById('hChart'), { type:'line', data:{ labels:<?= json_encode($labels) ?>, datasets:[{ data:<?= json_encode($hum) ?>, borderColor:'#00f2ff', borderWidth:6, tension:0.4, fill:true, backgroundColor:'rgba(0, 242, 255, 0.1)', pointRadius:4 }] }, options:cfg });

        let s = 10;
        setInterval(() => { s--; if(s < 0) s = 10; document.getElementById('timer').innerText = s; }, 1000);
    </script>
</body>
</html>
