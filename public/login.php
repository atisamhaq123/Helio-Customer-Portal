<?php
require dirname(__DIR__) . '/app/bootstrap.php';
if (user()) { redirect('index.php'); }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    if (getenv('HELIO_DISABLE_DEMO') === '1' && isset($_POST['demo'])) { $error = 'Demo access is disabled.'; }
    elseif (($_SESSION['attempts'] ?? 0) >= 10 && time() - ($_SESSION['attempt_time'] ?? 0) < 300) { $error = 'Too many attempts. Try again in five minutes.'; }
    else {
        $email = isset($_POST['demo']) ? 'alex@helio.demo' : trim((string)($_POST['email'] ?? ''));
        $password = isset($_POST['demo']) ? 'HelioDemo!2026' : (string)($_POST['password'] ?? '');
        $account = query('SELECT * FROM users WHERE email=?', [$email])[0] ?? null;
        if ($account && password_verify($password, $account['password'])) {
            session_regenerate_id(true); $_SESSION['user_id'] = $account['id']; unset($_SESSION['attempts'], $_SESSION['csrf']); redirect('index.php');
        }
        $_SESSION['attempts'] = ($_SESSION['attempts'] ?? 0) + 1; $_SESSION['attempt_time'] = time(); $error = 'Email or password is incorrect.';
    }
}
?><!doctype html>
<html lang="en" data-bs-theme="dark"><head><meta charset="utf-8"><link rel="icon" href="favicon.svg" type="image/svg+xml"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Welcome to Helio · Customer portal</title><link href="assets/vendor/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous"><link href="assets/fonts.css" rel="stylesheet"><link href="assets/style.css" rel="stylesheet"></head>
<body class="login-body"><main class="login-shell"><section class="login-story"><a class="brand" href="login.php"><span class="brand-mark"><?=icon('sun')?></span>helio<span class="brand-dot">.</span></a><div class="eyebrow mt-5"><span class="live-dot"></span> YOUR ENERGY. YOUR FUTURE.</div><h1>A brighter home.<br>A smarter <span>tomorrow.</span></h1><p>Your solar journey, connected. Meet your home’s new energy command center.</p><div class="login-orbit"><div class="orbit o1"></div><div class="orbit o2"></div><div class="orbit o3"></div><div class="orbit-core"><?=icon('sun')?></div><span class="orbit-label label-solar"><?=icon('bolt')?> Clean energy</span><span class="orbit-label label-home"><?=icon('home')?> Connected home</span></div><div class="login-foot"><?=icon('shield')?> Secure access to your energy world</div></section><section class="login-panel"><div class="eyebrow">WELCOME BACK</div><h2>Your energy starts here.</h2><p class="muted mb-4">Sign in to manage your home, effortlessly.</p><?php if ($error): ?><div class="alert alert-danger"><?=e($error)?></div><?php endif; ?><form method="post"><?=csrf_field()?><label class="form-label" for="email">Email address</label><input class="form-control mb-3" id="email" name="email" type="email" autocomplete="username" required placeholder="you@example.com"><label class="form-label" for="password">Password</label><input class="form-control mb-4" id="password" name="password" type="password" autocomplete="current-password" required placeholder="Enter your password"><button class="btn btn-accent w-100" type="submit">Sign in <?=icon('arrow')?></button></form><?php if (getenv('HELIO_DISABLE_DEMO') !== '1'): ?><div class="login-divider"><span>EXPLORE HELIO</span></div><form method="post"><?=csrf_field()?><button class="btn btn-outline-light w-100" name="demo" value="1">Enter customer demo <?=icon('up')?></button></form><p class="small muted mt-3">A working demo with sample energy data. Requests are saved locally.</p><details class="demo-details"><summary>Demo sign-in credentials</summary><p class="small mt-2">Customer: alex@helio.demo / HelioDemo!2026<br>Admin: admin@helio.demo / HelioAdmin!2026</p></details><?php endif; ?><div class="login-copyright">© <?=date('Y')?> Helio Energy. Built for a brighter future.</div></section></main></body></html>
