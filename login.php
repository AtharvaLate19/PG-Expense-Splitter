<?php
require_once 'db.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

$loginError = '';
$successMessage = $_SESSION['signup_success'] ?? '';
unset($_SESSION['signup_success']);

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $loginError = 'Enter a valid email address.';
    } elseif ($password === '') {
        $loginError = 'Please enter your password.';
    } else {
        $stmt = mysqli_prepare($conn, 'SELECT id, name, password, role, pg_code FROM users WHERE email = ? LIMIT 1');
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 's', $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $user = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);

            if ($user && password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['pg_code'] = $user['pg_code'];
                $_SESSION['name'] = $user['name'];
                header('Location: dashboard.php');
                exit;
            }
            $loginError = 'Invalid email or password.';
        } else {
            error_log('PG Split login: failed to prepare user lookup.');
            $loginError = 'Sign in is temporarily unavailable. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#f6f7ff">
<title>Sign in | PG Split</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<style>
:root{--ink:#202342;--muted:#777b96;--primary:#6757e8;--primary-dark:#5142cb;--line:#e5e7f2;--surface:#fff;--bg:#f5f6ff;--danger:#b42336;--success:#16794b}
*{box-sizing:border-box}html{min-height:100%;scroll-behavior:smooth}
body{margin:0;min-height:100vh;font-family:"DM Sans",sans-serif;color:var(--ink);background:radial-gradient(ellipse at 12% 12%,#e9e8ff 0,transparent 38%),radial-gradient(ellipse at 90% 88%,#e8f3ff 0,transparent 35%),var(--bg)}
a{color:var(--primary);text-decoration:none}a:hover{color:var(--primary-dark)}
.auth-shell{width:min(1080px,calc(100% - 40px));min-height:650px;margin:32px auto;display:grid;grid-template-columns:1fr 1fr;background:var(--surface);border:1px solid rgba(224,226,244,.9);border-radius:28px;overflow:hidden;box-shadow:0 24px 70px rgba(45,42,100,.12)}
.auth-brand-panel{position:relative;isolation:isolate;overflow:hidden;padding:42px;color:#fff;background:linear-gradient(145deg,#5143cb 0%,#7162ec 55%,#9285f7 100%);display:flex;flex-direction:column;justify-content:space-between}
.auth-brand-panel:before,.auth-brand-panel:after{content:"";position:absolute;z-index:-1;border:1px solid rgba(255,255,255,.17);border-radius:50%;pointer-events:none}
.auth-brand-panel:before{width:360px;height:360px;right:-170px;top:20px}.auth-brand-panel:after{width:260px;height:260px;right:-115px;top:70px}
.auth-brand{display:inline-flex;align-items:center;gap:11px;color:#fff;font:800 20px "Plus Jakarta Sans",sans-serif;width:max-content}
.brand-mark,.mobile-brand-mark{display:grid;place-items:center;width:42px;height:42px;border-radius:14px;background:rgba(255,255,255,.18);color:#fff}
.auth-brand-copy{max-width:420px;padding:45px 0}
.eyebrow{display:inline-flex;align-items:center;gap:8px;font-size:12px;font-weight:700;letter-spacing:1.1px;text-transform:uppercase}
.auth-brand-copy .eyebrow{padding:9px 12px;border:1px solid rgba(255,255,255,.24);background:rgba(255,255,255,.1);border-radius:999px}
.auth-brand-copy h1{font:800 clamp(31px,3.3vw,44px)/1.16 "Plus Jakarta Sans",sans-serif;letter-spacing:-1.5px;margin:24px 0 16px}
.auth-brand-copy>p{max-width:355px;line-height:1.8;color:rgba(255,255,255,.83);font-size:15px}
.auth-proof{display:flex;align-items:flex-start;gap:13px;margin-top:22px;color:#fff}
.auth-proof>i{display:grid;place-items:center;width:37px;height:37px;flex:0 0 37px;border-radius:12px;background:rgba(255,255,255,.15);padding:10px}
.auth-proof span{display:grid;gap:4px}.auth-proof strong{font-size:14px}.auth-proof small{font-size:12px;color:rgba(255,255,255,.78)}
.auth-form-panel{padding:32px clamp(25px,5vw,65px);display:flex;flex-direction:column;justify-content:center;min-width:0}
.auth-home{align-self:flex-end;display:inline-flex;align-items:center;gap:8px;color:var(--muted);font-size:13px;margin-bottom:24px}
.auth-card{width:100%;max-width:410px;margin:0 auto}
.auth-card-heading{margin-bottom:28px}
.mobile-brand-mark{display:none;background:#efedff;color:var(--primary);margin-bottom:18px}
.auth-card-heading .eyebrow{color:var(--primary);letter-spacing:1.3px}
.auth-card-heading h2{font:800 clamp(26px,3vw,33px)/1.2 "Plus Jakarta Sans",sans-serif;letter-spacing:-.8px;margin:10px 0}
.auth-card-heading p{margin:0;color:var(--muted);font-size:14px;line-height:1.65}
.form-alert{display:flex;gap:10px;align-items:flex-start;padding:12px 14px;margin:0 0 20px;border-radius:12px;font-size:13px;line-height:1.5;overflow-wrap:anywhere}
.error-alert{background:#fff0f1;color:var(--danger);border:1px solid #ffd9dd}.success-alert{background:#eaf8f0;color:var(--success);border:1px solid #ccebd9}
.form-alert i{margin-top:2px}
.input-box{margin-bottom:19px;min-width:0}.input-box label{display:block;margin-bottom:8px;font-size:13px;font-weight:700;color:#393b59}
.input-with-icon{position:relative;display:flex;align-items:center;min-width:0}
.input-with-icon>i{position:absolute;left:15px;color:#9295ad;font-size:14px;pointer-events:none}
.input-with-icon input{width:100%;min-width:0;height:51px;padding:0 45px 0 42px;border:1px solid var(--line);border-radius:12px;background:#fff;color:var(--ink);font:500 14px "DM Sans",sans-serif;outline:none;transition:border-color .18s,box-shadow .18s}
.input-with-icon input::placeholder{color:#a4a6ba}
.input-with-icon input:focus{border-color:var(--primary);box-shadow:0 0 0 3px rgba(103,87,232,.12)}
.input-with-icon input.invalid{border-color:#df5262;background:#fffafb}
.password-toggle{position:absolute;right:8px;top:50%;transform:translateY(-50%);width:34px;height:34px;border:0;border-radius:9px;background:transparent;color:#8588a1;cursor:pointer}
.password-toggle:hover{background:#f0efff;color:var(--primary)}
.error{min-height:0;margin:0;color:var(--danger);font-size:12px;line-height:1.4}.error:not(:empty){margin-top:6px}
.auth-submit{display:flex;align-items:center;justify-content:center;gap:10px;width:100%;min-height:51px;border:0;border-radius:12px;background:linear-gradient(110deg,#6757e8,#7d6bf0);color:#fff;font:700 14px "DM Sans",sans-serif;cursor:pointer;box-shadow:0 8px 18px rgba(103,87,232,.22);transition:transform .15s,box-shadow .15s,opacity .15s}
.auth-submit:hover{transform:translateY(-1px);box-shadow:0 11px 22px rgba(103,87,232,.28)}.auth-submit:disabled{opacity:.65;cursor:wait;transform:none}
.auth-switch{text-align:center;color:var(--muted);font-size:13px;margin:24px 0 0}.auth-switch a{font-weight:700}
@media(max-width:820px){.auth-shell{grid-template-columns:.9fr 1.1fr;width:min(760px,calc(100% - 28px));margin:14px auto;min-height:calc(100vh - 28px)}.auth-brand-panel{padding:30px 25px}.auth-form-panel{padding:26px 25px}.auth-brand-copy h1{font-size:32px}}
@media(max-width:620px){body{background:var(--bg)}.auth-shell{display:block;width:100%;min-height:100vh;margin:0;border:0;border-radius:0;box-shadow:none}.auth-brand-panel{display:none}.auth-form-panel{min-height:100vh;padding:24px 22px 32px;justify-content:center}.auth-home{align-self:flex-start;margin-bottom:30px}.auth-card{max-width:420px}.mobile-brand-mark{display:grid}.auth-card-heading{margin-bottom:24px}}
@media(max-width:360px){.auth-form-panel{padding-left:16px;padding-right:16px}.auth-card-heading h2{font-size:25px}}
@media(prefers-reduced-motion:reduce){*,*::before,*::after{scroll-behavior:auto!important;transition:none!important}}
</style>
</head>
<body>
<main class="auth-shell">
  <section class="auth-brand-panel" aria-label="PG Split overview">
    <a class="auth-brand" href="index.php"><span class="brand-mark"><i class="fa-solid fa-wallet"></i></span> PG Split</a>
    <div class="auth-brand-copy">
      <span class="eyebrow"><i class="fa-solid fa-wand-magic-sparkles"></i> Shared living, simplified</span>
      <h1>Make every shared expense feel effortless.</h1>
      <p>Track contributions, split bills fairly, and keep your PG group on the same page.</p>
      <div class="auth-proof"><i class="fa-solid fa-circle-check"></i><span><strong>Clear by design</strong><small>Everyone knows what they owe.</small></span></div>
      <div class="auth-proof"><i class="fa-solid fa-shield-halved"></i><span><strong>Built for trust</strong><small>One calm place for group finances.</small></span></div>
    </div>
  </section>
  <section class="auth-form-panel">
    <a class="auth-home" href="index.php"><i class="fa-solid fa-arrow-left"></i> Back to home</a>
    <div class="auth-card">
      <div class="auth-card-heading"><span class="mobile-brand-mark"><i class="fa-solid fa-wallet"></i></span><span class="eyebrow">Welcome back</span><h2>Sign in to PG Split</h2><p>Enter your details to continue to your group.</p></div>
      <?php if ($successMessage !== ''): ?><div class="form-alert success-alert" role="status"><i class="fa-solid fa-circle-check"></i><span><?= h($successMessage) ?></span></div><?php endif; ?>
      <?php if ($loginError !== ''): ?><div class="form-alert error-alert" role="alert"><i class="fa-solid fa-circle-exclamation"></i><span><?= h($loginError) ?></span></div><?php endif; ?>
      <form id="authForm" method="POST" action="login.php">
        <div class="input-box"><label for="email">Email address</label><div class="input-with-icon"><i class="fa-regular fa-envelope"></i><input type="email" name="email" id="email" placeholder="you@example.com" autocomplete="email" maxlength="254" required value="<?= h($_POST['email'] ?? '') ?>"></div><p id="emailError" class="error" aria-live="polite"></p></div>
        <div class="input-box"><label for="password">Password</label><div class="input-with-icon"><i class="fa-solid fa-lock"></i><input type="password" name="password" id="password" placeholder="Enter your password" autocomplete="current-password" required><button type="button" class="password-toggle" aria-label="Show password" aria-pressed="false"><i class="fa-regular fa-eye"></i></button></div><p id="passwordError" class="error" aria-live="polite"></p></div>
        <button type="submit" id="submitBtn" class="auth-submit"><span>Sign in</span><i class="fa-solid fa-arrow-right"></i></button>
      </form>
      <p class="auth-switch">New to PG Split? <a href="signup.php">Create an account</a></p>
    </div>
  </section>
</main>
<script>
(function(){
  const form=document.getElementById('authForm'),email=document.getElementById('email'),password=document.getElementById('password'),submit=document.getElementById('submitBtn'),toggle=document.querySelector('.password-toggle');
  const emailError=document.getElementById('emailError'),passError=document.getElementById('passwordError');
  toggle.addEventListener('click',function(){const show=password.type==='password';password.type=show?'text':'password';toggle.innerHTML=show?'<i class="fa-regular fa-eye-slash"></i>':'<i class="fa-regular fa-eye"></i>';toggle.setAttribute('aria-label',show?'Hide password':'Show password');toggle.setAttribute('aria-pressed',String(show));});
  form.addEventListener('submit',function(e){
    let ok=true;
    if(!/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(email.value.trim())){emailError.textContent='Enter a valid email address.';email.classList.add('invalid');ok=false;}else{emailError.textContent='';email.classList.remove('invalid');}
    if(!password.value){passError.textContent='Please enter your password.';password.classList.add('invalid');ok=false;}else{passError.textContent='';password.classList.remove('invalid');}
    if(!ok){e.preventDefault();form.querySelector('.invalid').focus();return;}
    submit.disabled=true;submit.querySelector('span').textContent='Signing in…';
  });
  email.addEventListener('input',()=>{emailError.textContent='';email.classList.remove('invalid');});
  password.addEventListener('input',()=>{passError.textContent='';password.classList.remove('invalid');});
  window.addEventListener('pageshow',()=>{submit.disabled=false;submit.querySelector('span').textContent='Sign in';});
})();
</script>
</body>
</html>
