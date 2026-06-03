<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/lamix.php';

startSess();
if (getUser()) { header('Location: /panel.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = trim($_POST['username'] ?? '');
    $pass = trim($_POST['password'] ?? '');

    if ($user && $pass) {
        // Login user to Lamix using their own credentials
        $result = Lamix::login($user, $pass, sys_get_temp_dir().'/ahssms_client_'.md5($user).'.txt');
        if ($result['success']) {
            // Get their client_id from agent panel
            $clientId = Lamix::getClientId($user);
            setUser($user, $clientId);
            header('Location: /panel.php'); exit;
        } else {
            $error = $result['error'] ?? 'Invalid username or password';
        }
    } else {
        $error = 'Please enter username and password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>AHS SMS Panel — Login</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg:#060810;--card:#0d1117;--border:#1c2333;
  --accent:#4f8eff;--accent2:#7c3aed;
  --text:#e6edf3;--muted:#7d8590;--red:#f85149;
}
body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;display:flex;align-items:center;justify-content:center;overflow:hidden;}
.bg{position:fixed;inset:0;z-index:0;}
.bg-mesh{position:absolute;inset:0;background:radial-gradient(ellipse 80% 60% at 20% 20%,rgba(79,142,255,.07) 0%,transparent 60%),radial-gradient(ellipse 60% 80% at 80% 80%,rgba(124,58,237,.07) 0%,transparent 60%);}
.bg-grid{position:absolute;inset:0;background-image:linear-gradient(rgba(255,255,255,.025) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.025) 1px,transparent 1px);background-size:32px 32px;}
.orb{position:absolute;border-radius:50%;filter:blur(80px);animation:drift 8s ease-in-out infinite;}
.orb1{width:400px;height:400px;background:rgba(79,142,255,.08);top:-100px;left:-100px;}
.orb2{width:300px;height:300px;background:rgba(124,58,237,.08);bottom:-80px;right:-80px;animation-delay:-4s;}
@keyframes drift{0%,100%{transform:translate(0,0)}50%{transform:translate(30px,20px)}}
.card{position:relative;z-index:1;background:var(--card);border:1px solid var(--border);border-radius:24px;padding:48px 44px 40px;width:100%;max-width:400px;box-shadow:0 32px 64px rgba(0,0,0,.6),0 0 0 1px rgba(255,255,255,.04) inset;animation:slideUp .5s cubic-bezier(.22,1,.36,1);}
@keyframes slideUp{from{opacity:0;transform:translateY(24px)}to{opacity:1;transform:translateY(0)}}
.logo{text-align:center;margin-bottom:40px;}
.logo-mark{width:60px;height:60px;margin:0 auto 18px;background:linear-gradient(135deg,var(--accent),var(--accent2));border-radius:18px;display:flex;align-items:center;justify-content:center;font-size:28px;box-shadow:0 12px 32px rgba(79,142,255,.25);position:relative;}
.logo-mark::after{content:'';position:absolute;inset:-1px;border-radius:18px;background:linear-gradient(135deg,rgba(255,255,255,.15),transparent);pointer-events:none;}
.logo h1{font-family:'Syne',sans-serif;font-size:22px;font-weight:800;letter-spacing:-.5px;}
.logo p{color:var(--muted);font-size:13px;margin-top:6px;}
.error-box{background:rgba(248,81,73,.08);border:1px solid rgba(248,81,73,.25);border-radius:10px;padding:11px 14px;font-size:13px;color:#ffa198;margin-bottom:20px;display:flex;align-items:center;gap:8px;}
.field{margin-bottom:16px;}
.field label{display:block;font-size:11px;font-weight:500;color:var(--muted);text-transform:uppercase;letter-spacing:.8px;margin-bottom:8px;}
.field input{width:100%;background:#0a0f1a;border:1px solid var(--border);border-radius:12px;padding:13px 16px;font-size:14px;font-family:inherit;color:var(--text);outline:none;transition:border-color .2s,box-shadow .2s;}
.field input:focus{border-color:rgba(79,142,255,.5);box-shadow:0 0 0 3px rgba(79,142,255,.1);}
.field input::placeholder{color:#3d4450;}
.btn{width:100%;margin-top:8px;background:linear-gradient(135deg,var(--accent),var(--accent2));color:#fff;border:none;border-radius:12px;padding:14px;font-size:14px;font-weight:600;font-family:'Syne',sans-serif;letter-spacing:.3px;cursor:pointer;transition:opacity .2s,transform .1s;position:relative;overflow:hidden;}
.btn::after{content:'';position:absolute;inset:0;background:linear-gradient(135deg,rgba(255,255,255,.1),transparent);}
.btn:hover{opacity:.9;}.btn:active{transform:scale(.99);}.btn:disabled{opacity:.5;cursor:not-allowed;}
.footer{text-align:center;color:var(--muted);font-size:12px;margin-top:24px;}
</style>
</head>
<body>
<div class="bg"><div class="bg-mesh"></div><div class="bg-grid"></div><div class="orb orb1"></div><div class="orb orb2"></div></div>
<div class="card">
  <div class="logo">
    <div class="logo-mark">🇧🇩</div>
    <h1>AHS SMS Panel</h1>
    <p>Sign in with your Lamix account</p>
  </div>
  <?php if ($error): ?>
  <div class="error-box">⚠️ <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <form method="POST" id="form">
    <div class="field">
      <label>Username</label>
      <input type="text" name="username" placeholder="Your Lamix username" required autofocus value="<?= htmlspecialchars($_POST['username']??'') ?>">
    </div>
    <div class="field">
      <label>Password</label>
      <input type="password" name="password" placeholder="••••••••" required>
    </div>
    <button type="submit" class="btn" id="btn">Sign In</button>
  </form>
  <p class="footer">Powered by AHS SHOVON Agent </p>
</div>
<script>
document.getElementById('form').addEventListener('submit',function(){
  const b=document.getElementById('btn');
  b.disabled=true; b.textContent='Signing in...';
});
</script>
</body>
</html>
