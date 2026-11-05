<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#071b19">
    <meta name="description" content="ArenaSports membantu Anda menemukan lapangan olahraga, memesan jadwal, dan bergabung dalam pertandingan.">
    <title>ArenaSports | Temukan permainanmu</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root{--ink:#f4f7f2;--muted:#b4c2bc;--bg:#071b19;--panel:#102b26;--lime:#c8f169;--line:rgba(255,255,255,.14)}
        *{box-sizing:border-box}html{scroll-behavior:smooth}body{margin:0;background:var(--bg);color:var(--ink);font-family:"DM Sans",sans-serif}
        a{color:inherit;text-decoration:none}.wrap{width:min(1180px,calc(100% - 48px));margin:auto}
        .nav{height:86px;display:flex;align-items:center;justify-content:space-between;gap:24px}
        .brand{display:flex;align-items:center;gap:11px;font-family:Manrope,sans-serif;font-size:21px;font-weight:800;letter-spacing:-.8px}
        .brand-mark{width:39px;height:39px;border-radius:13px;background:var(--lime);display:grid;place-items:center;color:#09211b;font-size:20px;transform:rotate(-5deg)}
        .navlinks{display:flex;gap:30px;color:#d1ddd6;font-size:13px}.navlinks a:hover{color:var(--lime)}
        .nav-actions{display:flex;align-items:center;gap:12px}.btn{display:inline-flex;align-items:center;justify-content:center;gap:9px;padding:13px 19px;border-radius:12px;font-size:13px;font-weight:700;transition:transform .2s,background .2s;border:1px solid transparent;cursor:pointer}
        .btn:hover{transform:translateY(-2px)}.btn-ghost{border-color:var(--line);background:rgba(255,255,255,.035)}.btn-primary{background:var(--lime);color:#0a211b}.btn-primary:hover{background:#d8ff8a}
        .hero{position:relative;isolation:isolate;overflow:hidden;border:1px solid rgba(255,255,255,.12);border-radius:30px;min-height:590px;background:#102a25;display:flex;align-items:center}
        .hero:before{content:"";position:absolute;inset:0;z-index:-2;background:linear-gradient(90deg,rgba(5,23,20,.97) 0%,rgba(5,23,20,.88) 38%,rgba(5,23,20,.25) 100%),url("https://images.unsplash.com/photo-1526232761682-d26e03ac148e?auto=format&fit=crop&w=2000&q=85") center 45%/cover}
        .hero:after{content:"";position:absolute;inset:0;z-index:-1;background:radial-gradient(circle at 75% 15%,rgba(200,241,105,.13),transparent 27%),linear-gradient(0deg,rgba(4,20,17,.35),transparent 45%);pointer-events:none}
        .hero-content{max-width:660px;padding:75px 7.5% 82px}.eyebrow{display:inline-flex;align-items:center;gap:9px;color:var(--lime);font-size:11px;font-weight:800;letter-spacing:2px;text-transform:uppercase}
        .dot{width:7px;height:7px;background:var(--lime);border-radius:50%;box-shadow:0 0 0 5px rgba(200,241,105,.12)}
        h1{font-family:Manrope,sans-serif;font-size:clamp(43px,6.2vw,78px);line-height:1.04;letter-spacing:-4px;margin:24px 0 22px;font-weight:800;max-width:650px}
        h1 span{color:var(--lime)}.lead{font-size:16px;line-height:1.8;color:#d0dcd5;max-width:470px;margin:0 0 30px}
        .hero-buttons{display:flex;flex-wrap:wrap;gap:12px}.hero-buttons .btn{padding:16px 21px}
        .trust{display:flex;align-items:center;gap:13px;margin-top:38px;color:#c0cec7;font-size:12px}.avatars{display:flex;padding-left:4px}.avatar{width:29px;height:29px;border:2px solid #10251f;border-radius:50%;margin-left:-5px;display:grid;place-items:center;background:#d8a886;color:#173128;font-size:10px;font-weight:800}.avatar:nth-child(2){background:#a8c4a5}.avatar:nth-child(3){background:#e7d18c}.avatar:nth-child(4){background:#d6a3a0}
        .trust strong{color:#fff}.trust small{display:block;color:#91a59a;margin-top:3px}
        .floating{position:absolute;right:5%;bottom:10%;width:215px;border:1px solid rgba(255,255,255,.23);background:rgba(7,27,25,.78);backdrop-filter:blur(14px);padding:18px;border-radius:19px;box-shadow:0 20px 50px #00100d66}
        .floating-top{display:flex;align-items:center;justify-content:space-between;font-size:11px;color:#d5e1da}.live{color:var(--lime);font-size:10px}.floating h3{font-family:Manrope,sans-serif;font-size:18px;margin:15px 0 5px}.floating p{font-size:11px;color:#b7c7bf;margin:0}.mini-line{height:1px;background:var(--line);margin:15px 0}.mini-bottom{display:flex;justify-content:space-between;font-size:11px}.mini-bottom strong{color:var(--lime)}
        .stats{display:grid;grid-template-columns:repeat(3,1fr);gap:1px;background:var(--line);border:1px solid var(--line);border-radius:20px;overflow:hidden;margin:22px 0 75px}.stat{padding:25px 28px;background:#0b211d;display:flex;align-items:center;gap:15px}.stat-icon{width:43px;height:43px;border-radius:14px;display:grid;place-items:center;background:rgba(200,241,105,.11);color:var(--lime);font-size:20px}.stat strong{font-family:Manrope,sans-serif;font-size:19px;display:block}.stat span{display:block;color:#91a69b;font-size:11px;margin-top:4px}
        .section-head{display:flex;justify-content:space-between;align-items:end;gap:24px;margin-bottom:25px}.section-head h2{font-family:Manrope,sans-serif;font-size:clamp(27px,4vw,39px);letter-spacing:-1.5px;margin:12px 0 0}.section-head p{color:var(--muted);font-size:13px;line-height:1.7;max-width:360px;margin:0}
        .features{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:80px}.feature{padding:25px;border-radius:21px;border:1px solid var(--line);background:linear-gradient(145deg,rgba(255,255,255,.055),rgba(255,255,255,.015));min-height:210px}
        .feature-icon{width:45px;height:45px;border-radius:14px;display:grid;place-items:center;background:var(--lime);color:#0b241c;font-size:21px}.feature h3{font-family:Manrope,sans-serif;font-size:17px;margin:22px 0 9px}.feature p{font-size:12px;line-height:1.8;color:var(--muted);margin:0}
        .cta{display:flex;align-items:center;justify-content:space-between;gap:25px;padding:36px 40px;border-radius:24px;background:var(--lime);color:#0b241c;margin-bottom:65px}.cta h2{font-family:Manrope,sans-serif;font-size:clamp(23px,3vw,34px);letter-spacing:-1px;margin:0 0 8px}.cta p{font-size:13px;margin:0;color:#34503a}.cta .btn{background:#0b241c;color:white;white-space:nowrap}
        footer{border-top:1px solid var(--line);padding:25px 0 32px;color:#8fa59a;font-size:11px}.footer-inner{display:flex;align-items:center;justify-content:space-between;gap:18px}.footer-brand{color:#eaf2ec;font-weight:800}
        @media(max-width:800px){.navlinks{display:none}.hero{min-height:620px}.hero:before{background:linear-gradient(90deg,rgba(5,23,20,.95),rgba(5,23,20,.55)),url("https://images.unsplash.com/photo-1526232761682-d26e03ac148e?auto=format&fit=crop&w=1200&q=80") center/cover}.hero-content{padding:60px 7% 175px}h1{letter-spacing:-2.5px}.floating{right:7%;bottom:25px;width:205px}.stats{margin-bottom:60px}.stat{padding:20px 15px;gap:10px}.stat strong{font-size:16px}.features{grid-template-columns:1fr;margin-bottom:55px}.feature{min-height:unset}.section-head{align-items:start;flex-direction:column}.cta{padding:28px;align-items:start;flex-direction:column}.footer-inner{align-items:start;flex-direction:column}}
        @media(max-width:480px){.wrap{width:calc(100% - 28px)}.nav{height:72px}.brand{font-size:18px}.brand-mark{width:35px;height:35px}.nav-actions{gap:7px}.nav-actions .btn{padding:10px 12px;font-size:11px}.hero{border-radius:22px;min-height:650px}.hero-content{padding:48px 23px 185px}h1{font-size:46px}.lead{font-size:14px}.trust{align-items:start}.stats{grid-template-columns:1fr}.stat{padding:16px 20px}.floating{left:22px;right:auto;bottom:20px;width:calc(100% - 44px)}}
        @media(prefers-reduced-motion:reduce){html{scroll-behavior:auto}*,*:before,*:after{transition:none!important}}
    </style>
</head>
<body>
    <header class="wrap nav">
        <a href="{{ route('welcome') }}" class="brand" aria-label="ArenaSports beranda"><span class="brand-mark">✳</span><span>Arena<span style="color:var(--lime)">Sports</span></span></a>
        <nav class="navlinks" aria-label="Navigasi utama">
            <a href="{{ route('user.venues') }}">Cari Venue</a><a href="{{ route('user.courts') }}">Cari Lapangan</a><a href="{{ route('user.openmatches') }}">Open Match</a><a href="#fitur">Fitur</a>
        </nav>
        <div class="nav-actions">
            <a class="btn btn-ghost" href="{{ route('login') }}">Masuk</a>
            <a class="btn btn-primary" href="{{ route('register') }}">Daftar <span aria-hidden="true">↗</span></a>
        </div>
    </header>

    <main class="wrap">
        <section class="hero" aria-labelledby="hero-title">
            <div class="hero-content">
                <div class="eyebrow"><span class="dot"></span> Your game. Your community.</div>
                <h1 id="hero-title">Waktunya main.<br><span>Jadwalkan</span> aksimu.</h1>
                <p class="lead">Temukan lapangan favorit, amankan jadwal bermain, dan temukan teman satu permainan. Semua kebutuhan olahragamu, dalam satu tempat.</p>
                <div class="hero-buttons">
                    <a href="{{ route('user.venues') }}" class="btn btn-primary">Jelajahi Venue <span aria-hidden="true">↗</span></a>
                    <a href="{{ route('user.courts') }}" class="btn btn-ghost">Lihat Lapangan <span aria-hidden="true">→</span></a>
                </div>
                <div class="trust">
                    <div class="avatars" aria-hidden="true"><span class="avatar">A</span><span class="avatar">R</span><span class="avatar">D</span><span class="avatar">+</span></div>
                    <div><strong>Satu platform, lebih banyak permainan.</strong><small>Booking praktis · Komunitas aktif · Main lebih mudah</small></div>
                </div>
            </div>
            <aside class="floating" aria-label="Contoh informasi pertandingan">
                <div class="floating-top"><span>ARENASPORTS MATCH</span><span class="live">● OPEN MATCH</span></div>
                <h3>Find your team.</h3>
                <p>Temukan rekan bermain dan mulai pertandingan.</p>
                <div class="mini-line"></div>
                <div class="mini-bottom"><span>Komunitas olahraga</span><strong>Let's play ↗</strong></div>
            </aside>
        </section>

        <section class="stats" id="cara-kerja" aria-label="Keunggulan ArenaSports">
            <div class="stat"><span class="stat-icon">◷</span><div><strong>Booking praktis</strong><span>Pilih waktu yang sesuai</span></div></div>
            <div class="stat"><span class="stat-icon">⌖</span><div><strong>Pilihan lapangan</strong><span>Temukan venue untukmu</span></div></div>
            <div class="stat"><span class="stat-icon">↗</span><div><strong>Main bersama</strong><span>Terhubung lewat Open Match</span></div></div>
        </section>

        <section id="fitur">
            <div class="section-head">
                <div><div class="eyebrow"><span class="dot"></span> Built for every player</div><h2>Semua yang kamu butuhkan<br>untuk mulai bermain.</h2></div>
                <p>Dari merencanakan jadwal hingga mengajak pemain lain, ArenaSports membuat pengalaman olahraga terasa lebih mudah.</p>
            </div>
            <div class="features">
                <article class="feature"><div class="feature-icon">▦</div><h3>Booking lapangan</h3><p>Jelajahi lapangan, lihat informasi yang tersedia, lalu lanjutkan pemesanan melalui akunmu.</p></article>
                <article class="feature"><div class="feature-icon">◎</div><h3>Open Match</h3><p>Temukan pertandingan terbuka atau ajak pemain lain untuk mengisi tim dan bermain bersama.</p></article>
                <article class="feature"><div class="feature-icon">✦</div><h3>Poin dan reward</h3><p>Pantau poin akun dan jelajahi hadiah yang tersedia sesuai ketentuan platform.</p></article>
            </div>
        </section>

        <section class="cta" id="mulai">
            <div><h2>Permainan berikutnya dimulai di sini.</h2><p>Buat akun atau masuk untuk melanjutkan ke ArenaSports.</p></div>
            <a href="{{ route('register') }}" class="btn">Buat akun gratis <span aria-hidden="true">↗</span></a>
        </section>
    </main>

    <footer>
        <div class="wrap footer-inner">
            <div class="footer-brand">✳ ArenaSports <span style="font-weight:400;color:#8fa59a">· Sistem Penyewaan Lapangan</span></div>
            <div>Temukan permainanmu. Bangun komunitasmu.</div>
            <div>© {{ date('Y') }} ArenaSports</div>
        </div>
    </footer>
</body>
</html>
