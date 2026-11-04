<?php
require_once 'db.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

$error_message = '';
$success_message = '';

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
function old($key) {
    return h($_POST[$key] ?? '');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $plain_password = $_POST['password'] ?? '';
    $pg_code = trim($_POST['pg_code'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if ($name === '' || mb_strlen($name) < 2 || mb_strlen($name) > 80) {
        $error_message = 'Enter a name between 2 and 80 characters.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
        $error_message = 'Enter a valid email address.';
    } elseif (strlen($plain_password) < 8) {
        $error_message = 'Password must be at least 8 characters long.';
    } elseif (!preg_match('/^\d{6}$/', $pg_code)) {
        $error_message = 'PG code must contain exactly 6 digits.';
    } elseif ($phone !== '' && !preg_match('/^\d{10}$/', $phone)) {
        $error_message = 'Phone number must contain exactly 10 digits.';
    } else {
        $password = password_hash($plain_password, PASSWORD_DEFAULT);
        try {
            mysqli_begin_transaction($conn);

            $stmt = mysqli_prepare($conn, 'SELECT id FROM users WHERE email = ? LIMIT 1');
            mysqli_stmt_bind_param($stmt, 's', $email);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            $email_exists = mysqli_stmt_num_rows($stmt) > 0;
            mysqli_stmt_close($stmt);

            if ($email_exists) {
                mysqli_rollback($conn);
                $error_message = 'An account already exists with this email. Please sign in.';
            } else {
                $stmt = mysqli_prepare($conn, 'SELECT pg_code FROM groupp WHERE pg_code = ? LIMIT 1');
                mysqli_stmt_bind_param($stmt, 's', $pg_code);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_store_result($stmt);
                $group_exists = mysqli_stmt_num_rows($stmt) > 0;
                mysqli_stmt_close($stmt);

                if (!$group_exists) {
                    $group_name = $name . "'s PG";
                    $description = 'Auto-created group for PG code ' . $pg_code;
                    $stmt = mysqli_prepare($conn, 'INSERT INTO groupp (pg_code, name, description) VALUES (?, ?, ?)');
                    mysqli_stmt_bind_param($stmt, 'sss', $pg_code, $group_name, $description);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                }

                $stmt = mysqli_prepare($conn, 'SELECT id FROM users WHERE pg_code = ? LIMIT 1');
                mysqli_stmt_bind_param($stmt, 's', $pg_code);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_store_result($stmt);
                $role = mysqli_stmt_num_rows($stmt) === 0 ? 'Admin' : 'User';
                mysqli_stmt_close($stmt);

                $stmt = mysqli_prepare($conn, 'INSERT INTO users (name, email, password, pg_code, role, phone) VALUES (?, ?, ?, ?, ?, ?)');
                mysqli_stmt_bind_param($stmt, 'ssssss', $name, $email, $password, $pg_code, $role, $phone);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);

                mysqli_commit($conn);
                $_SESSION['signup_success'] = 'Account created successfully. Please sign in.';
                header('Location: login.php');
                exit;
            }
        } catch (Throwable $e) {
            mysqli_rollback($conn);
            // Log the technical details server-side; show a safe message to the user.
            error_log('PG Split signup error: ' . $e->getMessage());
            $error_message = 'We could not create your account. Please check your details and try again.';
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
<title>Create account | PG Split</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<style>
:root{--ink:#202342;--muted:#777b96;--primary:#6757e8;--primary-dark:#5142cb;--line:#e5e7f2;--surface:#fff;--bg:#f5f6ff;--danger:#b42336;--success:#16794b}
*{box-sizing:border-box}
html{min-height:100%;scroll-behavior:smooth}
body{margin:0;min-height:100vh;font-family:"DM Sans",sans-serif;color:var(--ink);background:radial-gradient(ellipse at 12% 12%,#e9e8ff 0,transparent 38%),radial-gradient(ellipse at 90% 88%,#e8f3ff 0,transparent 35%),var(--bg)}
a{color:var(--primary);text-decoration:none}a:hover{color:var(--primary-dark)}
.auth-shell{width:min(1120px,calc(100% - 40px));min-height:690px;margin:32px auto;display:grid;grid-template-columns:.92fr 1.08fr;background:var(--surface);border:1px solid rgba(224,226,244,.9);border-radius:28px;overflow:hidden;box-shadow:0 24px 70px rgba(45,42,100,.12)}
.auth-brand-panel{position:relative;isolation:isolate;overflow:hidden;padding:38px 42px;color:#fff;background:linear-gradient(145deg,#5143cb 0%,#7162ec 55%,#9285f7 100%);display:flex;flex-direction:column;justify-content:space-between}
.auth-brand-panel:before,.auth-brand-panel:after{content:"";position:absolute;z-index:-1;border:1px solid rgba(255,255,255,.17);border-radius:50%;pointer-events:none}
.auth-brand-panel:before{width:360px;height:360px;right:-170px;top:20px}.auth-brand-panel:after{width:260px;height:260px;right:-115px;top:70px}
.auth-brand{display:inline-flex;align-items:center;gap:11px;color:#fff;font:800 20px "Plus Jakarta Sans",sans-serif;width:max-content}
.brand-mark,.mobile-brand-mark{display:grid;place-items:center;width:42px;height:42px;border-radius:14px;background:rgba(255,255,255,.18);color:#fff}
.auth-brand-copy{max-width:420px;padding:48px 0}
.eyebrow{display:inline-flex;align-items:center;gap:8px;font-size:12px;font-weight:700;letter-spacing:1.1px;text-transform:uppercase}
.auth-brand-copy .eyebrow{padding:9px 12px;border:1px solid rgba(255,255,255,.24);background:rgba(255,255,255,.1);border-radius:999px}
.auth-brand-copy h1{font:800 clamp(30px,3.2vw,43px)/1.16 "Plus Jakarta Sans",sans-serif;letter-spacing:-1.5px;margin:24px 0 16px}
.auth-brand-copy>p{max-width:350px;line-height:1.8;color:rgba(255,255,255,.83);font-size:15px}
.auth-stat-row{display:grid;gap:16px;margin-top:36px}
.auth-stat-row>div{display:flex;align-items:center;gap:14px;font-size:14px;color:rgba(255,255,255,.92)}
.auth-stat-row strong{display:grid;place-items:center;width:38px;height:38px;border:1px solid rgba(255,255,255,.25);border-radius:12px;font-size:12px}
.auth-form-panel{padding:30px clamp(24px,5vw,66px);display:flex;flex-direction:column;justify-content:center;min-width:0}
.auth-home{align-self:flex-end;display:inline-flex;align-items:center;gap:8px;color:var(--muted);font-size:13px;margin-bottom:18px}
.auth-card{width:100%;max-width:430px;margin:0 auto}
.auth-card-heading{margin-bottom:25px}
.mobile-brand-mark{display:none;background:#efedff;color:var(--primary);margin-bottom:18px}
.auth-card-heading .eyebrow{color:var(--primary);letter-spacing:1.3px}
.auth-card-heading h2{font:800 clamp(25px,3vw,32px)/1.2 "Plus Jakarta Sans",sans-serif;letter-spacing:-.8px;margin:10px 0}
.auth-card-heading p{margin:0;color:var(--muted);font-size:14px;line-height:1.65}
.form-alert{display:flex;gap:10px;align-items:flex-start;padding:12px 14px;margin:0 0 18px;border-radius:12px;font-size:13px;line-height:1.5;overflow-wrap:anywhere}
.error-alert{background:#fff0f1;color:var(--danger);border:1px solid #ffd9dd}.success-alert{background:#eaf8f0;color:var(--success);border:1px solid #ccebd9}
.form-alert i{margin-top:2px}
.input-box{margin-bottom:16px;min-width:0}
.input-box label{display:block;margin-bottom:8px;font-size:13px;font-weight:700;color:#393b59}
.input-box label span{font-weight:500;color:var(--muted)}
.input-with-icon{position:relative;display:flex;align-items:center;min-width:0}
.input-with-icon>i{position:absolute;left:15px;color:#9295ad;font-size:14px;pointer-events:none}
.input-with-icon input{width:100%;min-width:0;height:49px;padding:0 45px 0 42px;border:1px solid var(--line);border-radius:12px;background:#fff;color:var(--ink);font:500 14px "DM Sans",sans-serif;outline:none;transition:border-color .18s,box-shadow .18s}
.input-with-icon input::placeholder,.phone-container input::placeholder{color:#a4a6ba}
.input-with-icon input:focus,.phone-container input:focus{border-color:var(--primary);box-shadow:0 0 0 3px rgba(103,87,232,.12)}
.input-with-icon input.invalid,.phone-container input.invalid{border-color:#df5262;background:#fffafb}
.password-toggle{position:absolute;right:8px;top:50%;transform:translateY(-50%);width:34px;height:34px;border:0;border-radius:9px;background:transparent;color:#8588a1;cursor:pointer}
.password-toggle:hover{background:#f0efff;color:var(--primary)}
.auth-form-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.phone-container{display:flex;gap:7px;min-width:0}
.phone-container input{height:49px;border:1px solid var(--line);border-radius:12px;background:#fff;color:var(--ink);font:500 13px "DM Sans",sans-serif;outline:none;min-width:0}
.phone-container input:first-child{width:54px;text-align:center;padding:0;background:#f7f7fc;color:#777b96;flex:0 0 54px}
.phone-container input:last-child{width:100%;padding:0 10px}
.error{min-height:0;margin:0;color:var(--danger);font-size:12px;line-height:1.4}
.error:not(:empty){margin-top:6px}
.field-hint{display:flex;gap:8px;align-items:flex-start;color:var(--muted);font-size:12px;line-height:1.55;margin:0 0 18px}
.field-hint i{color:var(--primary);margin-top:2px}
.auth-submit{display:flex;align-items:center;justify-content:center;gap:10px;width:100%;min-height:50px;border:0;border-radius:12px;background:linear-gradient(110deg,#6757e8,#7d6bf0);color:#fff;font:700 14px "DM Sans",sans-serif;cursor:pointer;box-shadow:0 8px 18px rgba(103,87,232,.22);transition:transform .15s,box-shadow .15s,opacity .15s}
.auth-submit:hover{transform:translateY(-1px);box-shadow:0 11px 22px rgba(103,87,232,.28)}.auth-submit:disabled{opacity:.65;cursor:wait;transform:none}
.auth-switch{text-align:center;color:var(--muted);font-size:13px;margin:22px 0 0}.auth-switch a{font-weight:700}
@media(max-width:820px){.auth-shell{grid-template-columns:.8fr 1.2fr;width:min(700px,calc(100% - 28px));margin:14px auto;min-height:calc(100vh - 28px)}.auth-brand-panel{padding:30px 25px}.auth-form-panel{padding:26px 25px}.auth-brand-copy h1{font-size:32px}.auth-stat-row{gap:12px}}
@media(max-width:620px){body{background:var(--bg)}.auth-shell{display:block;width:100%;min-height:100vh;margin:0;border:0;border-radius:0;box-shadow:none}.auth-brand-panel{display:none}.auth-form-panel{min-height:100vh;padding:24px 22px 32px;justify-content:center}.auth-home{align-self:flex-start;margin-bottom:26px}.auth-card{max-width:420px}.mobile-brand-mark{display:grid}.auth-card-heading{margin-bottom:23px}.auth-form-grid{grid-template-columns:1fr;gap:0}.input-box{margin-bottom:16px}}
@media(max-width:360px){.auth-form-panel{padding-left:16px;padding-right:16px}.auth-card-heading h2{font-size:25px}}
@media(prefers-reduced-motion:reduce){*,*::before,*::after{scroll-behavior:auto!important;transition:none!important}}
</style>
</head>
<body>
<main class="auth-shell">
  <section class="auth-brand-panel" aria-label="PG Split benefits">
    <a class="auth-brand" href="index.php"><span class="brand-mark"><i class="fa-solid fa-wallet"></i></span> PG Split</a>
    <div class="auth-brand-copy">
      <span class="eyebrow"><i class="fa-solid fa-wand-magic-sparkles"></i> Your group, in sync</span>
      <h1>Start splitting smarter today.</h1>
      <p>Create your account and bring clarity to every shared bill in your PG.</p>
      <div class="auth-stat-row"><div><strong>01</strong><span>Create your account</span></div><div><strong>02</strong><span>Join your PG group</span></div><div><strong>03</strong><span>Split fairly</span></div></div>
    </div>
  </section>
  <section class="auth-form-panel">
    <a class="auth-home" href="index.php"><i class="fa-solid fa-arrow-left"></i> Back to home</a>
    <div class="auth-card">
      <div class="auth-card-heading">
        <span class="mobile-brand-mark"><i class="fa-solid fa-wallet"></i></span>
        <span class="eyebrow">Get started</span><h2>Create your account</h2>
        <p>Set up your profile to join or create a PG group.</p>
      </div>
      <?php if ($error_message !== ''): ?><div class="form-alert error-alert" role="alert"><i class="fa-solid fa-circle-exclamation"></i><span><?= h($error_message) ?></span></div><?php endif; ?>
      <form id="authForm" method="POST" action="signup.php">
        <div class="input-box"><label for="name">Full name</label><div class="input-with-icon"><i class="fa-regular fa-user"></i><input type="text" id="name" name="name" placeholder="Your full name" autocomplete="name" minlength="2" maxlength="80" required value="<?= old('name') ?>"></div><p id="nameError" class="error" aria-live="polite"></p></div>
        <div class="input-box"><label for="email">Email address</label><div class="input-with-icon"><i class="fa-regular fa-envelope"></i><input type="email" id="email" name="email" placeholder="you@example.com" autocomplete="email" maxlength="254" required value="<?= old('email') ?>"></div><p id="emailError" class="error" aria-live="polite"></p></div>
        <div class="input-box"><label for="password">Password</label><div class="input-with-icon"><i class="fa-solid fa-lock"></i><input type="password" id="password" name="password" placeholder="At least 8 characters" autocomplete="new-password" minlength="8" required><button type="button" class="password-toggle" aria-label="Show password" aria-pressed="false"><i class="fa-regular fa-eye"></i></button></div><p id="passwordError" class="error" aria-live="polite"></p></div>
        <div class="auth-form-grid">
          <div class="input-box"><label for="pgCode">6-digit PG code</label><div class="input-with-icon"><i class="fa-solid fa-hashtag"></i><input type="text" id="pgCode" name="pg_code" placeholder="123456" maxlength="6" minlength="6" inputmode="numeric" pattern="[0-9]{6}" autocomplete="off" required value="<?= old('pg_code') ?>"></div><p id="pgCodeError" class="error" aria-live="polite"></p></div>
          <div class="input-box"><label for="phone">Phone <span>(optional)</span></label><div class="phone-container"><input type="text" value="+91" readonly tabindex="-1" aria-label="Country code"><input type="tel" id="phone" name="phone" placeholder="10-digit number" maxlength="10" inputmode="numeric" autocomplete="tel-national" pattern="[0-9]{10}" value="<?= old('phone') ?>"></div><p id="phoneError" class="error" aria-live="polite"></p></div>
        </div>
        <p class="field-hint"><i class="fa-solid fa-circle-info"></i><span>The first person using a new PG code becomes its admin.</span></p>
        <button type="submit" id="submitBtn" class="auth-submit"><span>Create account</span><i class="fa-solid fa-arrow-right"></i></button>
      </form>
      <p class="auth-switch">Already have an account? <a href="login.php">Sign in</a></p>
    </div>
  </section>
</main>
<script>
(function(){
  const form=document.getElementById('authForm'), submit=document.getElementById('submitBtn');
  const fields={name:document.getElementById('name'),email:document.getElementById('email'),password:document.getElementById('password'),pgCode:document.getElementById('pgCode'),phone:document.getElementById('phone')};
  const messages={name:'nameError',email:'emailError',password:'passwordError',pgCode:'pgCodeError',phone:'phoneError'};
  const toggle=document.querySelector('.password-toggle');
  toggle.addEventListener('click',function(){const show=fields.password.type==='password';fields.password.type=show?'text':'password';toggle.innerHTML=show?'<i class="fa-regular fa-eye-slash"></i>':'<i class="fa-regular fa-eye"></i>';toggle.setAttribute('aria-label',show?'Hide password':'Show password');toggle.setAttribute('aria-pressed',String(show));});
  ['pgCode','phone'].forEach(id=>fields[id].addEventListener('input',function(){this.value=this.value.replace(/\D/g,'').slice(0,id==='pgCode'?6:10);}));
  function check(id,message){const field=fields[id];document.getElementById(messages[id]).textContent=message;field.classList.toggle('invalid',!!message);return !message;}
  form.addEventListener('submit',function(e){
    let ok=true;
    const name=fields.name.value.trim(), email=fields.email.value.trim();
    ok=check('name',name.length>=2&&name.length<=80?'':'Enter a name between 2 and 80 characters.')&&ok;
    ok=check('email',/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(email)?'':'Enter a valid email address.')&&ok;
    ok=check('password',fields.password.value.length>=8?'':'Password must be at least 8 characters.')&&ok;
    ok=check('pgCode',/^\d{6}$/.test(fields.pgCode.value)?'':'PG code must be exactly 6 digits.')&&ok;
    ok=check('phone',!fields.phone.value||/^\d{10}$/.test(fields.phone.value)?'':'Phone number must be exactly 10 digits.')&&ok;
    if(!ok){e.preventDefault();const first=form.querySelector('.invalid');if(first)first.focus();return;}
    submit.disabled=true;submit.querySelector('span').textContent='Creating account…';
  });
  Object.keys(fields).forEach(id=>fields[id].addEventListener('input',function(){if(this.classList.contains('invalid'))form.dispatchEvent(new Event('validatefield'));}));
  window.addEventListener('pageshow',()=>{submit.disabled=false;submit.querySelector('span').textContent='Create account';});
})();
</script>
</body>
</html>
