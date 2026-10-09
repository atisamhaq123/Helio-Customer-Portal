<?php
declare(strict_types=1);

date_default_timezone_set('Asia/Karachi');
ini_set('session.use_strict_mode', '1');
session_name('helio_session');
session_set_cookie_params(['httponly' => true, 'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off', 'samesite' => 'Lax', 'path' => '/']);
session_start();
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');
header('Cache-Control: no-store');

function e(mixed $value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function redirect(string $path): never { header('Location: ' . $path); exit; }
function csrf(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(32)); }
function csrf_field(): string { return '<input type="hidden" name="csrf" value="'.e(csrf()).'">'; }
function check_csrf(): void {
    if (!hash_equals(csrf(), (string)($_POST['csrf'] ?? ''))) { http_response_code(419); exit('Your session expired. Reload the page and try again.'); }
}
function flash(string $message, string $type = 'success'): void { $_SESSION['flash'] = [$message, $type]; }
function money(float $amount): string { return '$' . number_format($amount, 2); }
function icon(string $name, string $class = ''): string {
    $paths = [
        'grid'=>'<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
        'sun'=>'<circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M2 12h2m16 0h2M5 5l1.5 1.5m11 11L19 19M5 19l1.5-1.5m11-11L19 5"/>',
        'bolt'=>'<path d="M13 2L4 14h7l-1 8 10-13h-7l1-7z"/>',
        'home'=>'<path d="M3 10l9-7 9 7v10H3zM9 20v-7h6v7"/>',
        'battery'=>'<rect x="2" y="6" width="18" height="12" rx="3"/><path d="M22 10v4M6 10v4m4-4v4m4-4v4"/>',
        'project'=>'<path d="M9 4h6v4H9zM6 6H4v15h16V6h-2M8 12h8m-8 4h5"/>',
        'wallet'=>'<path d="M20 8V5H4v15h16V8H4M16 11h5v6h-5z"/>',
        'file'=>'<path d="M14 2H5v20h14V7zM14 2v6h5M8 12h8m-8 4h6"/>',
        'calendar'=>'<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M7 2v6m10-6v6M3 11h18m-13 4h2m4 0h2"/>',
        'chat'=>'<path d="M21 11a8 8 0 01-8 8H7l-5 3 1-6a8 8 0 01-1-5 9.5 9.5 0 0119 0zM7 10h10m-10 4h6"/>',
        'settings'=>'<path d="M12 3l2 3 4-1 1 4 3 3-3 3-1 4-4-1-2 3-2-3-4 1-1-4-3-3 3-3 1-4 4 1z"/><circle cx="12" cy="12" r="3"/>',
        'arrow'=>'<path d="M5 12h14m-5-5l5 5-5 5"/>',
        'up'=>'<path d="M7 17L17 7M7 7h10v10"/>',
        'down'=>'<path d="M12 3v12m-5-5l5 5 5-5M4 17v4h16v-4"/>',
        'check'=>'<path d="M5 12l4 4L19 6"/>',
        'bell'=>'<path d="M18 8a6 6 0 00-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/>',
        'logout'=>'<path d="M9 4H3v16h6m4-13l5 5-5 5M8 12h13"/>',
        'menu'=>'<path d="M3 6h18M3 12h18M3 18h18"/>',
        'shield'=>'<path d="M12 2l9 4v6c0 5-9 10-9 10S3 17 3 12V6zM8 12l3 3 5-6"/>',
        'plus'=>'<path d="M12 5v14M5 12h14"/>',
        'search'=>'<circle cx="10" cy="10" r="6"/><path d="M15 15l6 6"/>',
        'plug'=>'<path d="M8 2v5m8-5v5M5 7h14v3a7 7 0 01-14 0zM12 17v5"/>',
    ];
    return '<svg class="icon '.e($class).'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.($paths[$name] ?? $paths['grid']).'</svg>';
}

$storage = getenv('HELIO_DB_PATH') ?: dirname(__DIR__) . '/storage/helio.sqlite';
if (!is_dir(dirname($storage))) { mkdir(dirname($storage), 0700, true); }
$db = new PDO('sqlite:' . $storage, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
$db->exec('PRAGMA foreign_keys = ON; PRAGMA busy_timeout = 5000;');
$db->exec('CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY, name TEXT NOT NULL, email TEXT UNIQUE NOT NULL, password TEXT NOT NULL, role TEXT NOT NULL DEFAULT "customer");
CREATE TABLE IF NOT EXISTS properties (id INTEGER PRIMARY KEY, user_id INTEGER NOT NULL REFERENCES users(id), name TEXT NOT NULL, address TEXT NOT NULL, capacity REAL NOT NULL, stage INTEGER NOT NULL DEFAULT 4);
CREATE TABLE IF NOT EXISTS tickets (id INTEGER PRIMARY KEY, user_id INTEGER NOT NULL REFERENCES users(id), subject TEXT NOT NULL, message TEXT NOT NULL, category TEXT NOT NULL, status TEXT NOT NULL DEFAULT "Open", reply TEXT NOT NULL DEFAULT "", created_at TEXT NOT NULL);
CREATE TABLE IF NOT EXISTS appointments (id INTEGER PRIMARY KEY, user_id INTEGER NOT NULL REFERENCES users(id), type TEXT NOT NULL, date TEXT NOT NULL, time TEXT NOT NULL, notes TEXT NOT NULL DEFAULT "", status TEXT NOT NULL DEFAULT "Requested");
CREATE TABLE IF NOT EXISTS invoices (id INTEGER PRIMARY KEY, user_id INTEGER NOT NULL REFERENCES users(id), title TEXT NOT NULL, amount REAL NOT NULL, due_date TEXT NOT NULL, status TEXT NOT NULL);
CREATE TABLE IF NOT EXISTS documents (id INTEGER PRIMARY KEY, user_id INTEGER NOT NULL REFERENCES users(id), title TEXT NOT NULL, category TEXT NOT NULL, content TEXT NOT NULL, created_at TEXT NOT NULL);
CREATE TABLE IF NOT EXISTS audit (id INTEGER PRIMARY KEY, user_id INTEGER NOT NULL REFERENCES users(id), action TEXT NOT NULL, created_at TEXT NOT NULL);');
if ((int)$db->query('SELECT COUNT(*) FROM users')->fetchColumn() === 0) {
    $db->beginTransaction();
    $stmt = $db->prepare('INSERT INTO users (id,name,email,password,role) VALUES (?,?,?,?,?)');
    $stmt->execute([1,'Alex Morgan','alex@helio.demo',password_hash('HelioDemo!2026', PASSWORD_DEFAULT),'customer']);
    $stmt->execute([2,'Jamie Chen','admin@helio.demo',password_hash('HelioAdmin!2026', PASSWORD_DEFAULT),'admin']);
    $db->exec("INSERT INTO properties VALUES (1,1,'The Morgan Residence','1248 Oakridge Drive, Austin, TX',8.4,4),(2,1,'Lakeside Retreat','86 Lakeview Lane, Austin, TX',5.6,2)");
    $stmt = $db->prepare('INSERT INTO invoices VALUES (?,?,?,?,?,?)');
    $stmt->execute([1,1,'Annual care plan',249,date('Y-m-d',strtotime('+14 days')),'Due']);
    $stmt->execute([2,1,'System installation · final milestone',4200,date('Y-m-d',strtotime('-20 days')),'Paid']);
    $stmt->execute([3,1,'System installation · deposit',2800,date('Y-m-d',strtotime('-50 days')),'Paid']);
    $stmt = $db->prepare('INSERT INTO documents VALUES (?,?,?,?,?,?)');
    foreach ([['System overview','Technical','System: 8.4 kWp rooftop solar array. 21 panels rated at 400 W. Hybrid inverter and 13.5 kWh home battery. This is a sample system specification.'],['Equipment warranty','Warranty','Sample warranty summary: panels 25 years; inverter 10 years; battery 10 years. Actual terms must be supplied by the equipment manufacturer.'],['Installation handover','Project','Sample handover: installation inspected, monitoring configured, customer orientation completed. This document is for demonstration only.']] as $i => $doc) { $stmt->execute([$i+1,1,$doc[0],$doc[1],$doc[2],date('Y-m-d',strtotime('-25 days'))]); }
    $stmt = $db->prepare('INSERT INTO tickets (user_id,subject,message,category,status,reply,created_at) VALUES (?,?,?,?,?,?,?)');
    $stmt->execute([1,'Monitoring app setup','Please help me connect the monitoring app to my system.','Monitoring','Resolved','Your monitoring gateway is configured. You can now sign in with the invitation sent by your installer.',date('Y-m-d H:i:s',strtotime('-3 days'))]);
    $stmt = $db->prepare('INSERT INTO appointments (user_id,type,date,time,notes,status) VALUES (?,?,?,?,?,?)');
    $stmt->execute([1,'System health check',date('Y-m-d',strtotime('+7 days')),'10:00','Annual equipment inspection.','Confirmed']);
    $db->commit();
}

function query(string $sql, array $params = []): array { global $db; $stmt = $db->prepare($sql); $stmt->execute($params); return $stmt->fetchAll(); }
function run(string $sql, array $params = []): void { global $db; $stmt = $db->prepare($sql); $stmt->execute($params); }
function user(): ?array { return isset($_SESSION['user_id']) ? (query('SELECT id,name,email,role FROM users WHERE id=?', [$_SESSION['user_id']])[0] ?? null) : null; }
function audit(string $action): void { run('INSERT INTO audit (user_id,action,created_at) VALUES (?,?,?)', [$_SESSION['user_id'],$action,date('Y-m-d H:i:s')]); }
