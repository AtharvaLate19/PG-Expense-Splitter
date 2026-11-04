<?php
session_start();
if(isset($_SESSION['user_id'])){
    // redirect logic
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>PG Expense Splitter</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
  <style>
    :root {
      --blue: #2f5bd6;
      --blue-dark: #2549b3;
      --blue-soft: #eaf0ff;
      --ink: #172447;
      --text: #556080;
      --muted: #7a84a3;
      --line: #e3e8f4;
      --bg: #f5f7ff;
      --bg-alt: #f4f7fc;
      --white: #ffffff;
      --green: #1f9d6b;
      --radius: 20px;
    }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    html { scroll-behavior: smooth; }
    body {
      font-family: 'DM Sans', system-ui, sans-serif;
      color: var(--text);
      background: var(--white);
      line-height: 1.6;
      -webkit-font-smoothing: antialiased;
    }
    h1, h2, h3, .brand-name { font-family: 'Plus Jakarta Sans', 'DM Sans', sans-serif; color: var(--ink); letter-spacing: -0.03em; }
    a { text-decoration: none; color: inherit; }
    .wrap { max-width: 1240px; margin: 0 auto; padding: 0 24px; }
    svg { display: block; }

    /* ---------- Buttons ---------- */
    .btn {
      display: inline-flex; align-items: center; gap: 10px;
      padding: 15px 22px; border-radius: 12px; border: 1px solid transparent;
      font: 600 16px 'DM Sans', sans-serif; cursor: pointer;
      transition: transform .2s, box-shadow .2s, background .2s;
    }
    .btn:hover { transform: translateY(-2px); }
    .btn-primary { background: var(--blue); color: #fff; box-shadow: 0 10px 22px rgba(47, 91, 214, .28); }
    .btn-primary:hover { background: var(--blue-dark); }
    .btn-ghost { background: #fff; color: var(--ink); border-color: var(--line); }
    .btn-ghost:hover { box-shadow: 0 8px 18px rgba(23, 36, 71, .08); }
    .btn-white { background: #fff; color: var(--blue); box-shadow: 0 10px 22px rgba(0, 0, 0, .15); }
    .btn svg { width: 18px; height: 18px; }

    /* Styling for the Google Maps button (its markup is unchanged) */
    .map-btn-slot button {
      padding: 15px 22px; border: none; color: #fff; border-radius: 12px;
      cursor: pointer; font: 600 16px 'DM Sans', sans-serif; transition: .3s;
    }
    .map-btn-slot button:hover { opacity: .9; transform: translateY(-2px); }

    /* ---------- Header ---------- */
    header.site-header {
      position: sticky; top: 0; z-index: 20;
      background: rgba(245, 247, 255, .85); backdrop-filter: blur(12px);
    }
    .nav { display: flex; align-items: center; justify-content: space-between; height: 80px; }
    .brand { display: flex; align-items: center; gap: 14px; }
    .brand-mark {
      width: 48px; height: 48px; border-radius: 14px; display: grid; place-items: center;
      background: linear-gradient(135deg, #3b66e0, #2b4fc2); color: #fff;
      box-shadow: 0 10px 20px rgba(47, 91, 214, .3);
    }
    .brand-mark svg { width: 22px; height: 22px; }
    .brand-name { font-size: 20px; font-weight: 700; letter-spacing: -0.04em; }
    .nav-links { display: flex; align-items: center; gap: 32px; }
    .nav-links a { font-weight: 500; color: var(--text); transition: color .2s; }
    .nav-links a:hover { color: var(--blue); }
    .nav-links .btn { padding: 13px 18px; font-size: 15px; color: #fff; }
    .nav-links .btn:hover { color: #fff; }

    /* ---------- Hero ---------- */
    .hero {
      position: relative; overflow: hidden;
      background: radial-gradient(1000px 500px at 85% 20%, #e6eaff 0%, transparent 60%), var(--bg);
      padding: 80px 0 110px;
    }
    .hero-grid { display: grid; grid-template-columns: 1.05fr 1fr; gap: 40px; align-items: center; }
    .eyebrow { font-size: 13px; font-weight: 700; letter-spacing: .14em; text-transform: uppercase; color: var(--blue); }
    .hero h1 { font-size: clamp(44px, 6vw, 76px); line-height: 1.02; font-weight: 800; margin: 26px 0 28px; }
    .hero h1 span { color: var(--blue); display: block; }
    .hero p.lead { font-size: 20px; line-height: 1.9; max-width: 600px; color: var(--text); }
    .hero-actions { display: flex; flex-wrap: wrap; gap: 14px; margin-top: 38px; align-items: center; }
    .trust { display: flex; align-items: center; gap: 10px; margin-top: 30px; color: var(--muted); font-size: 15px; }
    .trust svg { width: 20px; height: 20px; color: var(--green); }

    /* Hero preview card */
    .visual { position: relative; min-height: 520px; display: grid; place-items: center; }
    .ring { position: absolute; border-radius: 50%; border: 1px solid rgba(47, 91, 214, .12); }
    .ring.r1 { width: 560px; height: 560px; right: -120px; top: -40px; background: rgba(47, 91, 214, .04); }
    .ring.r2 { width: 380px; height: 380px; right: 10px; top: 50px; }
    .overview {
      position: relative; width: 100%; max-width: 540px; background: #fff; border-radius: 28px;
      padding: 32px; box-shadow: 0 30px 60px rgba(40, 60, 130, .14);
    }
    .overview-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 22px; }
    .overview-head h3 { font-size: 20px; font-weight: 600; letter-spacing: -0.02em; }
    .status { display: flex; align-items: center; gap: 8px; color: var(--green); font-size: 14px; font-weight: 600; }
    .status i { width: 8px; height: 8px; border-radius: 50%; background: var(--green); }
    .total {
      background: linear-gradient(135deg, #2f55d0, #5b74e0); color: #fff;
      border-radius: 18px; padding: 24px; height: 120px;
      display: flex; flex-direction: column; justify-content: space-between;
    }
    .total small { font-size: 15px; opacity: .85; }
    .total .amt { display: flex; align-items: baseline; gap: 14px; font-family: 'Plus Jakarta Sans', sans-serif; font-size: 34px; font-weight: 800; }
    .total .amt small { font-family: 'DM Sans', sans-serif; font-size: 15px; font-weight: 500; }
    .row { display: flex; align-items: center; justify-content: space-between; padding: 18px 0; border-bottom: 1px solid var(--line); }
    .row:last-child { border-bottom: none; }
    .row .l { display: flex; align-items: center; gap: 14px; color: var(--text); }
    .row .r { font-weight: 700; color: var(--ink); font-size: 15px; }
    .ico { width: 40px; height: 40px; border-radius: 12px; background: var(--blue-soft); color: var(--blue); display: grid; place-items: center; flex-shrink: 0; }
    .ico svg { width: 20px; height: 20px; }
    .chip {
      position: absolute; background: #fff; border-radius: 16px; padding: 14px 18px;
      display: flex; align-items: center; gap: 12px; font-weight: 700; color: var(--ink); font-size: 15px;
      box-shadow: 0 16px 36px rgba(40, 60, 130, .16);
    }
    .chip .ico { width: 36px; height: 36px; }
    .chip.c1 { top: 24px; right: -20px; transform: rotate(4deg); }
    .chip.c1 .ico { background: #e4f6ee; color: var(--green); }
    .chip.c2 { bottom: 40px; left: -30px; transform: rotate(-4deg); }
    .chip.c2 .ico { background: #fdf0d9; color: #d98a14; }

    /* ---------- Sections ---------- */
    section { padding: 100px 0; }
    .sec-head { text-align: center; max-width: 760px; margin: 0 auto 56px; }
    .sec-head h2 { font-size: clamp(34px, 4.4vw, 52px); line-height: 1.08; font-weight: 800; margin: 18px 0 18px; }
    .sec-head p { font-size: 18px; color: var(--text); }
    .card {
      background: #fff; border: 1px solid var(--line); border-radius: var(--radius); padding: 32px;
      transition: transform .25s, box-shadow .25s;
    }
    .card:hover { transform: translateY(-4px); box-shadow: 0 18px 36px rgba(40, 60, 130, .09); }

    /* How it works */
    #how { background: var(--bg-alt); }
    .steps { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; }
    .num {
      width: 52px; height: 52px; border-radius: 14px; background: var(--blue-soft); color: var(--blue);
      display: grid; place-items: center; font: 700 18px 'Plus Jakarta Sans', sans-serif; margin-bottom: 28px;
    }
    .card h3 { font-size: 22px; font-weight: 700; margin-bottom: 8px; letter-spacing: -0.02em; }
    .card p { font-size: 16px; color: var(--text); }

    /* Facilities */
    #facilities { background: #f8faff; }
    .fac-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
    .fac { display: flex; gap: 18px; align-items: flex-start; padding: 26px; }
    .fac h3 { font-size: 18px; margin-bottom: 4px; }
    .fac p { font-size: 15px; }

    /* Branches */
    #branches { background: #fff; padding-bottom: 60px; }
    .branch-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; }
    .branch { position: relative; overflow: hidden; background: #fafbff; padding: 28px; }
    .branch::after {
      content: ""; position: absolute; top: -40px; right: -40px; width: 120px; height: 120px;
      border-radius: 50%; border: 1px solid rgba(47, 91, 214, .12); background: rgba(47, 91, 214, .04);
    }
    .branch-top { display: flex; align-items: center; gap: 16px; margin-bottom: 18px; }
    .branch-top h3 { font-size: 20px; margin: 0; letter-spacing: -0.02em; }
    .branch > p { padding-bottom: 20px; border-bottom: 1px solid var(--line); margin-bottom: 18px; }
    .contact { display: flex; align-items: center; gap: 12px; font-size: 14.5px; margin-bottom: 12px; color: var(--text); }
    .contact:last-child { margin-bottom: 0; }
    .contact svg { width: 18px; height: 18px; color: var(--blue); flex-shrink: 0; }

    /* CTA */
    .cta-sec { background: var(--bg-alt); padding: 70px 0 90px; }
    .cta {
      position: relative; overflow: hidden; border-radius: 28px; padding: 56px 60px;
      background: linear-gradient(110deg, #2a4fb8 0%, #4160d2 60%, #5570da 100%); color: #fff;
      display: flex; align-items: center; justify-content: space-between; gap: 30px; flex-wrap: wrap;
    }
    .cta::after {
      content: ""; position: absolute; right: -60px; top: -120px; width: 380px; height: 380px;
      border-radius: 50%; border: 50px solid rgba(255, 255, 255, .07);
    }
    .cta h2 { color: #fff; font-size: 40px; font-weight: 800; position: relative; z-index: 1; }
    .cta p { color: rgba(255, 255, 255, .8); font-size: 18px; margin-top: 10px; position: relative; z-index: 1; }
    .cta .btn { position: relative; z-index: 1; }

    /* Footer */
    footer { background: #fff; border-top: 1px solid var(--line); padding: 30px 0; }
    .foot { display: flex; align-items: center; justify-content: space-between; gap: 20px; flex-wrap: wrap; }
    .foot .brand-mark { width: 44px; height: 44px; }
    .foot p { font-size: 14px; color: var(--muted); }
    .foot nav { display: flex; gap: 22px; font-size: 14px; color: var(--muted); }
    .foot nav a:hover { color: var(--blue); }

    /* ---------- Responsive ---------- */
    @media (max-width: 1000px) {
      .hero-grid { grid-template-columns: 1fr; }
      .visual { min-height: 480px; margin-top: 30px; }
      .steps, .branch-grid, .fac-grid { grid-template-columns: 1fr 1fr; }
      .nav-links a:not(.btn) { display: none; }
    }
    @media (max-width: 680px) {
      .wrap { padding: 0 18px; }
      .hero { padding: 50px 0 70px; }
      section { padding: 70px 0; }
      .steps, .branch-grid, .fac-grid { grid-template-columns: 1fr; }
      .brand-name { font-size: 17px; }
      .chip.c1 { right: 0; }
      .chip.c2 { left: 0; }
      .overview { padding: 22px; }
      .cta { padding: 38px 28px; }
      .cta h2 { font-size: 30px; }
      .foot { flex-direction: column; text-align: center; }
    }
  </style>
</head>
<body>

<header class="site-header">
  <div class="wrap nav">
    <a href="index.php" class="brand">
      <span class="brand-mark">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h14V9.5"/><path d="M10 21v-6h4v6"/></svg>
      </span>
      <span class="brand-name">PG Expense Splitter</span>
    </a>
    <div class="nav-links">
      <a href="#how">How it works</a>
      <a href="#facilities">Facilities</a>
      <a href="#branches">Branches</a>
      <a href="login.php">Log in</a>
      <a href="signup.php" class="btn btn-primary">Join your PG
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
      </a>
    </div>
  </div>
</header>

<main>
  <!-- Hero -->
  <div class="hero">
    <div class="wrap hero-grid">
      <div>
        <div class="eyebrow">Shared living, a little simpler</div>
        <h1>Good roommates. <span>Clearer money.</span></h1>
        <p class="lead">Keep shared-home expenses easy to understand. See what was spent, how the split works, and what still needs settling — all in one calm place.</p>
        <div class="hero-actions">
          <a href="signup.php" class="btn btn-primary">Get started
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
          </a>
          <a href="login.php" class="btn btn-ghost">I already have an account</a>
          <span class="map-btn-slot">
            <button onclick="window.open('https://www.google.com/maps/embed?pb=!1m16!1m12!1m3!1d60273.561314185165!2d72.96331357458253!3d19.234578513450717!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!2m1!1spg%20near%20me%20in%20thane%20west%20and%20kasarvadavli!5e0!3m2!1sen!2sin!4v1760115744168!5m2!1sen!2sin', '_blank')"
      style="background-color:#6c63ff;">Find your nearest PG</button>
          </span>
        </div>
        <div class="trust">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 4 6v6c0 4.5 3.2 8 8 9 4.8-1 8-4.5 8-9V6l-8-3z"/><path d="m9 12 2 2 4-4"/></svg>
          Every share, visible. Every total, easy to check.
        </div>
      </div>

      <div class="visual" aria-hidden="true">
        <div class="ring r1"></div>
        <div class="ring r2"></div>
        <div class="overview">
          <div class="overview-head">
            <h3>Shared overview</h3>
            <span class="status"><i></i>Up to date</span>
          </div>
          <div class="total">
            <small>Household expenses</small>
            <div class="amt">₹ — <small>this month</small></div>
          </div>
          <div class="row">
            <div class="l"><span class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="m7 15 4-4 3 3 5-6"/></svg></span>Shared spending</div>
            <div class="r">In one place</div>
          </div>
          <div class="row">
            <div class="l"><span class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></span>Payment status</div>
            <div class="r">Easy to follow</div>
          </div>
          <div class="row">
            <div class="l"><span class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v18M17 7.5C16 6 14.2 5 12 5c-2.5 0-4.5 1.2-4.5 3.2 0 4.3 9 2.3 9 6.6C16.5 16.8 14.5 18 12 18c-2.3 0-4.2-1-5.2-2.5"/></svg></span>Fair split</div>
            <div class="r">Clear splits</div>
          </div>
        </div>
        <div class="chip c1"><span class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 5 5 9-10"/></svg></span>Everyone can follow along</div>
        <div class="chip c2"><span class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v18M17 7.5C16 6 14.2 5 12 5c-2.5 0-4.5 1.2-4.5 3.2 0 4.3 9 2.3 9 6.6C16.5 16.8 14.5 18 12 18c-2.3 0-4.2-1-5.2-2.5"/></svg></span>No more guesswork</div>
      </div>
    </div>
  </div>

  <!-- PG Map (replaces the How it works section only) -->
  <section id="how" style="background:var(--bg-alt);">
    <div class="wrap">
      <div class="sec-head">
        <div class="eyebrow">Find your home</div>
        <h2>Find your nearest PG.</h2>
        <p>Explore PG locations around Thane West and Kasarvadavli on the map below.</p>
      </div>
      <div style="width:100%; overflow:hidden; border-radius:20px; border:1px solid var(--line); box-shadow:0 18px 40px rgba(40,60,130,.12); background:#fff;">
        <iframe
          src="https://www.google.com/maps/embed?pb=!1m16!1m12!1m3!1d60273.561314185165!2d72.96331357458253!3d19.234578513450717!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!2m1!1spg%20near%20me%20in%20thane%20west%20and%20kasarvadavli!5e0!3m2!1sen!2sin!4v1760115744168!5m2!1sen!2sin"
          width="100%"
          height="500"
          style="border:0; display:block;"
          allowfullscreen
          loading="lazy"
          referrerpolicy="no-referrer-when-downgrade"
          title="PGs near Thane West and Kasarvadavli">
        </iframe>
      </div>
    </div>
  </section>

  <!-- Facilities -->
  <section id="facilities">
    <div class="wrap">
      <div class="sec-head">
        <div class="eyebrow">The comforts of home</div>
        <h2>Thoughtful essentials for everyday living.</h2>
        <p>Practical shared-home facilities that help residents settle in and feel at ease.</p>
      </div>
      <div class="fac-grid">
        <div class="card fac"><span class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h14V9.5"/><path d="M10 21v-6h4v6"/></svg></span><div><h3>Furnished rooms</h3><p>Comfortable rooms with Wi-Fi and air conditioning.</p></div></div>
        <div class="card fac"><span class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 4 6v6c0 4.5 3.2 8 8 9 4.8-1 8-4.5 8-9V6l-8-3z"/><path d="m9 12 2 2 4-4"/></svg></span><div><h3>24/7 security</h3><p>Round-the-clock security and CCTV monitoring.</p></div></div>
        <div class="card fac"><span class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 9h18M8 13h8M8 16h5"/></svg></span><div><h3>Mess &amp; meal plans</h3><p>Meal options to make the everyday routine easier.</p></div></div>
        <div class="card fac"><span class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h14V9.5"/><rect x="9" y="13" width="6" height="8"/></svg></span><div><h3>Shared lounge</h3><p>A common lounge and recreation area to unwind together.</p></div></div>
        <div class="card fac"><span class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 9h16l-1.5 11h-13L4 9z"/><path d="M8 9l2-5M16 9l-2-5M9 14h6"/></svg></span><div><h3>Laundry &amp; housekeeping</h3><p>Laundry and housekeeping services for a more comfortable stay.</p></div></div>
      </div>
    </div>
  </section>

  <!-- Branches -->
  <section id="branches">
    <div class="wrap">
      <div class="sec-head">
        <div class="eyebrow">Find your home</div>
        <h2>Made for shared living across Mumbai.</h2>
        <p>Keep the practical side of PG life in order, wherever your home base is.</p>
      </div>
      <div class="branch-grid">
        <div class="card branch">
          <div class="branch-top"><span class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 21V5l8-2v18"/><path d="M12 9l8 2v10"/><path d="M8 8v.01M8 12v.01M8 16v.01"/></svg></span><h3>PG Mumbai Central</h3></div>
          <p>A connected shared-home experience, with household costs kept clear for every resident.</p>
          <div class="contact"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s7-6.2 7-11.5A7 7 0 0 0 5 9.5C5 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/></svg>12, Mumbai Central Rd, Mumbai, Maharashtra</div>
          <div class="contact"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2z"/></svg>+91 9876543210</div>
          <div class="contact"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>info.mumbai1@pgsplit.com</div>
        </div>
        <div class="card branch">
          <div class="branch-top"><span class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 21V5l8-2v18"/><path d="M12 9l8 2v10"/><path d="M8 8v.01M8 12v.01M8 16v.01"/></svg></span><h3>PG Andheri East</h3></div>
          <p>Make everyday shared spending easier to review and simpler to settle together.</p>
          <div class="contact"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s7-6.2 7-11.5A7 7 0 0 0 5 9.5C5 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/></svg>45, Andheri East, Mumbai, Maharashtra</div>
          <div class="contact"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2z"/></svg>+91 9823456789</div>
          <div class="contact"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>contact.mumbai2@pgsplit.com</div>
        </div>
        <div class="card branch">
          <div class="branch-top"><span class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 21V5l8-2v18"/><path d="M12 9l8 2v10"/><path d="M8 8v.01M8 12v.01M8 16v.01"/></svg></span><h3>PG Bandra West</h3></div>
          <p>Stay on the same page about costs, contributions, and the home you share.</p>
          <div class="contact"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s7-6.2 7-11.5A7 7 0 0 0 5 9.5C5 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/></svg>78, Bandra West, Mumbai, Maharashtra</div>
          <div class="contact"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2z"/></svg>+91 9765432108</div>
          <div class="contact"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>stay.mumbai3@pgsplit.com</div>
        </div>
      </div>
    </div>
  </section>

  <!-- Call to action -->
  <div class="cta-sec">
    <div class="wrap">
      <div class="cta">
        <div>
          <h2>Less chasing. More clarity.</h2>
          <p>Bring your shared-home expenses into view.</p>
        </div>
        <a href="signup.php" class="btn btn-white">Create your account
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
        </a>
      </div>
    </div>
  </div>
</main>

<footer>
  <div class="wrap foot">
    <a href="index.php" class="brand">
      <span class="brand-mark">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h14V9.5"/><path d="M10 21v-6h4v6"/></svg>
      </span>
      <span class="brand-name" style="font-size:18px;">PG Expense Splitter</span>
    </a>
    <p>Clear shared expenses make a better shared home.</p>
    <nav>
      <a href="login.php">Log in</a>
      <a href="signup.php">Join your PG</a>
    </nav>
  </div>
</footer>

</body>
</html>