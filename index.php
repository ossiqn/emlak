<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'config.php';

$ilan_sorgu = $db->query("
    SELECT p.*, 
    (SELECT image_path FROM property_images WHERE property_id = p.id AND is_main = 1 LIMIT 1) as main_image 
    FROM properties p 
    ORDER BY p.id DESC LIMIT 8
");
$ilanlar = $ilan_sorgu->fetchAll();

$kategoriler = $db->query("SELECT * FROM categories ORDER BY category_name ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($ayar['site_title']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #f97316; --primary-hover: #ea580c; --dark: #111827; --gray: #6b7280; --light-bg: #f9fafb; --white: #ffffff; --border: #e5e7eb; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: var(--white); color: var(--dark); -webkit-font-smoothing: antialiased; overflow-x: hidden; }
        a { text-decoration: none; color: inherit; transition: 0.3s; }
        
        .topbar { background: var(--dark); color: #d1d5db; font-size: 13px; padding: 12px 5%; display: flex; justify-content: space-between; align-items: center; }
        .topbar-info { display: flex; gap: 20px; align-items: center; }
        .topbar-info span { display: flex; align-items: center; gap: 8px; }
        .topbar-right a { display: flex; align-items: center; gap: 6px; color: var(--white); font-weight: 600; }
        .topbar-right a:hover { color: var(--primary); }

        header { background: var(--white); display: flex; align-items: center; justify-content: space-between; padding: 20px 5%; border-bottom: 1px solid var(--border); position: sticky; top: 0; z-index: 1000; }
        .logo { font-size: 24px; font-weight: 800; color: var(--dark); display: flex; align-items: center; gap: 10px; }
        .logo svg { color: var(--primary); }
        .logo-text { display: flex; flex-direction: column; }
        .logo-title { line-height: 1; margin-bottom: 4px; }
        .logo-sub { font-size: 11px; color: var(--gray); font-weight: 500; }
        
        .nav-links { display: flex; gap: 30px; font-weight: 600; font-size: 14px; color: var(--gray); }
        .nav-links a.active, .nav-links a:hover { color: var(--dark); }
        
        .header-actions { display: flex; align-items: center; gap: 15px; }
        .mobile-menu-btn { display: none; background: transparent; border: none; color: var(--dark); cursor: pointer; padding: 4px; }
        
        .btn-primary { background: var(--primary); color: var(--white); padding: 12px 24px; border-radius: 8px; font-weight: 600; font-size: 14px; display: inline-flex; align-items: center; gap: 8px; border: none; cursor: pointer; }
        .btn-primary:hover { background: var(--primary-hover); }

        .hero { position: relative; padding: 140px 5%; background: url('https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?q=80&w=2000&auto=format&fit=crop') center/cover no-repeat; display: flex; flex-direction: column; align-items: flex-start; justify-content: center; min-height: 550px; }
        .hero::before { content: ''; position: absolute; inset: 0; background: linear-gradient(to right, rgba(0,0,0,0.8) 0%, rgba(0,0,0,0.4) 100%); z-index: 1; }
        .hero-content { position: relative; z-index: 2; width: 100%; max-width: 800px; }
        .hero h1 { font-size: 56px; font-weight: 800; color: var(--white); line-height: 1.1; margin-bottom: 20px; }
        .hero p { font-size: 18px; color: #d1d5db; font-weight: 400; margin-bottom: 40px; line-height: 1.6; max-width: 600px; }
        
        .search-glass { background: var(--white); padding: 10px; border-radius: 12px; display: flex; gap: 10px; align-items: center; width: 100%; max-width: 900px; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2); }
        .search-glass .input-group { flex: 1; display: flex; align-items: center; border-right: 1px solid var(--border); padding: 0 15px; }
        .search-glass .input-group:nth-child(2) { border-right: none; }
        .search-glass .input-group svg { color: var(--gray); margin-right: 10px; width: 18px; height: 18px; }
        .search-glass input, .search-glass select { width: 100%; padding: 15px 0; border: none; font-size: 15px; outline: none; background: transparent; color: var(--dark); font-weight: 500; }
        .search-glass select { cursor: pointer; appearance: none; background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e"); background-repeat: no-repeat; background-position: right center; background-size: 16px; padding-right: 20px; }
        .search-glass .btn-primary { padding: 16px 32px; border-radius: 10px; }

        .section-wrapper { padding: 80px 5%; max-width: 1440px; margin: 0 auto; }
        .section-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 40px; border-bottom: 1px solid var(--border); padding-bottom: 15px; }
        .section-title { display: flex; flex-direction: column; gap: 5px; }
        .section-title span { color: var(--primary); font-size: 12px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; }
        .section-title h2 { font-size: 32px; font-weight: 800; color: var(--dark); }
        .view-all { font-size: 14px; font-weight: 700; color: var(--dark); display: flex; align-items: center; gap: 6px; }
        .view-all:hover { color: var(--primary); }

        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 30px; }
        .card { background: var(--white); border-radius: 16px; border: 1px solid var(--border); overflow: hidden; display: flex; flex-direction: column; transition: 0.3s; }
        .card:hover { box-shadow: 0 15px 30px rgba(0,0,0,0.06); transform: translateY(-5px); }
        .card-img-wrap { position: relative; height: 220px; width: 100%; background: var(--light-bg); }
        .card-img { width: 100%; height: 100%; object-fit: cover; }
        .card-badge { position: absolute; top: 15px; left: 15px; background: var(--dark); color: var(--white); font-size: 10px; font-weight: 800; padding: 6px 12px; border-radius: 6px; letter-spacing: 1px; text-transform: uppercase; }
        .card-body { padding: 20px; display: flex; flex-direction: column; flex: 1; }
        .card-meta { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
        .card-type { font-size: 11px; font-weight: 800; color: var(--primary); text-transform: uppercase; letter-spacing: 0.5px; }
        .card-price { font-size: 18px; font-weight: 800; color: var(--dark); }
        .card-title { font-size: 16px; font-weight: 700; color: var(--dark); margin-bottom: 16px; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .card-loc { display: flex; align-items: center; gap: 8px; color: var(--gray); font-size: 13px; font-weight: 500; margin-top: auto; }
        .card-loc svg { color: var(--primary); }

        .footer { background: var(--light-bg); padding: 60px 5% 30px; border-top: 1px solid var(--border); }
        .footer-content { max-width: 1440px; margin: 0 auto; display: flex; flex-direction: column; align-items: center; text-align: center; gap: 20px; }
        .footer-logo { font-size: 24px; font-weight: 800; color: var(--dark); display: flex; align-items: center; gap: 10px; }
        .footer-logo svg { color: var(--primary); }
        .footer-links { display: flex; gap: 24px; font-size: 14px; font-weight: 600; color: var(--gray); flex-wrap: wrap; justify-content: center; }
        .footer-links a:hover { color: var(--primary); }
        .footer-bottom { border-top: 1px solid var(--border); margin-top: 30px; padding-top: 30px; color: var(--gray); font-size: 13px; font-weight: 500; width: 100%; text-align: center; }

        .whatsapp-float { position: fixed; bottom: 30px; right: 30px; background: #25d366; color: white; width: 60px; height: 60px; border-radius: 50%; display: flex; justify-content: center; align-items: center; box-shadow: 0 10px 25px rgba(37,211,102,0.3); z-index: 100; transition: 0.3s; }
        .whatsapp-float svg { width: 32px; height: 32px; }
        .whatsapp-float:hover { transform: scale(1.1); }

        @media (max-width: 992px) {
            .nav-links { display: none; position: absolute; top: 100%; left: 0; width: 100%; background: var(--white); flex-direction: column; padding: 20px 5%; box-shadow: 0 10px 20px rgba(0,0,0,0.1); gap: 15px; border-top: 1px solid var(--border); }
            .nav-links.active { display: flex; }
            .mobile-menu-btn { display: block; }
            .header-actions .btn-primary { display: none; }
            .hero h1 { font-size: 42px; }
        }

        @media (max-width: 768px) {
            .topbar { flex-direction: column; gap: 12px; text-align: center; }
            .topbar-info { flex-direction: column; gap: 8px; }
            header { padding: 15px 5%; }
            .logo-sub { display: none; }
            
            .hero { padding: 100px 5%; min-height: auto; }
            .hero h1 { font-size: 32px; }
            .hero p { font-size: 15px; }
            
            .search-glass { flex-direction: column; padding: 15px; gap: 15px; }
            .search-glass .input-group { border-right: none; border-bottom: 1px solid var(--border); padding: 0 0 15px 0; width: 100%; }
            .search-glass .input-group:nth-child(2) { border-bottom: none; padding-bottom: 0; }
            .search-glass .btn-primary { width: 100%; justify-content: center; }
            
            .section-header { flex-direction: column; align-items: flex-start; gap: 15px; }
            .grid { grid-template-columns: 1fr; }
            
            .whatsapp-float { bottom: 20px; right: 20px; width: 50px; height: 50px; }
            .whatsapp-float svg { width: 26px; height: 26px; }
        }
    </style>
</head>
<body>

<div class="topbar">
    <div class="topbar-info">
        <span><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg> <?= htmlspecialchars($ayar['contact_phone']) ?></span>
        <span><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg> <?= htmlspecialchars($ayar['contact_email']) ?></span>
    </div>
    <div class="topbar-right">
        <a href="admin/login.php">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path><polyline points="10 17 15 12 10 7"></polyline><line x1="15" y1="12" x2="3" y2="12"></line></svg>
            Giriş Yap
        </a>
    </div>
</div>

<header>
    <a href="index.php" class="logo">
        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
        <div class="logo-text">
            <span class="logo-title"><?= htmlspecialchars(explode(' ', $ayar['site_title'])[0] ?? 'Demo') ?> Emlak</span>
            <span class="logo-sub">Hayalinizdeki Evi Bulun</span>
        </div>
    </a>
    <nav class="nav-links" id="navLinks">
        <a href="index.php" class="active">Ana Sayfa</a>
        <a href="#">Tüm İlanlar</a>
        <a href="#">Harita</a>
        <a href="#">Danışman Başvuru</a>
        <a href="#">Sat/Kirala</a>
        <a href="#">İletişim</a>
    </nav>
    <div class="header-actions">
        <a href="#" class="btn-primary">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            İlan Ara
        </a>
        <button class="mobile-menu-btn" id="mobileMenuBtn">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
        </button>
    </div>
</header>

<div class="hero">
    <div class="hero-content">
        <h1><?= htmlspecialchars($ayar['hero_title']) ?></h1>
        <p><?= htmlspecialchars($ayar['hero_desc']) ?></p>
        
        <form class="search-glass" action="search.php" method="GET">
            <div class="input-group">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input type="text" name="q" placeholder="İlan no, şehir veya anahtar kelime...">
            </div>
            <div class="input-group">
                <select name="type">
                    <option value="">Kategori Seçin</option>
                    <?php foreach($kategoriler as $kat): ?>
                        <option value="<?= htmlspecialchars($kat['category_slug']) ?>"><?= htmlspecialchars($kat['category_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn-primary">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                İlanları Ara
            </button>
        </form>
    </div>
</div>

<div class="section-wrapper">
    <div class="section-header">
        <div class="section-title">
            <span>EN YENİ PORTFÖYÜMÜZ</span>
            <h2>Sizin İçin Seçtiklerimiz</h2>
        </div>
        <a href="#" class="view-all">Tüm Koleksiyonu Gör &rarr;</a>
    </div>

    <div class="grid">
        <?php if(count($ilanlar) > 0): ?>
            <?php foreach($ilanlar as $ilan): ?>
                <a href="detail.php?id=<?= $ilan['id'] ?>" class="card">
                    <div class="card-img-wrap">
                        <div class="card-badge"><?= mb_strtoupper($ilan['status']) ?></div>
                        <img src="<?= htmlspecialchars($ilan['main_image'] ?? 'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?q=80&w=600&auto=format&fit=crop') ?>" alt="<?= htmlspecialchars($ilan['title']) ?>" class="card-img">
                    </div>
                    <div class="card-body">
                        <div class="card-meta">
                            <span class="card-type"><?= mb_strtoupper($ilan['property_type']) ?></span>
                            <span class="card-price"><?= number_format($ilan['price'], 0, ',', '.') ?> ₺</span>
                        </div>
                        <h3 class="card-title"><?= htmlspecialchars($ilan['title']) ?></h3>
                        <div class="card-loc">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                            <?= htmlspecialchars($ilan['location']) ?>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="grid-column: 1/-1; text-align:center; padding: 60px 20px; color: var(--gray);">
                Henüz sistemde aktif bir ilan bulunmamaktadır. Yönetici panelinden yeni ilan ekleyebilirsiniz.
            </div>
        <?php endif; ?>
    </div>
</div>

<footer class="footer">
    <div class="footer-content">
        <div class="footer-logo">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
            <?= htmlspecialchars($ayar['site_title']) ?>
        </div>
        <div class="footer-links">
            <a href="#">Ana Sayfa</a>
            <a href="#">Tüm İlanlar</a>
            <a href="#">Hakkımızda</a>
            <a href="#">İletişim</a>
            <a href="page.php?slug=kullanim-kosullari">Kullanım Koşulları</a>
            <a href="page.php?slug=gizlilik-politikasi">Gizlilik Politikası</a>
        </div>
        <div class="footer-bottom">
            <?= htmlspecialchars($ayar['footer_text']) ?>
        </div>
    </div>
</footer>

<a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $ayar['contact_phone']) ?>" class="whatsapp-float" target="_blank" title="WhatsApp Destek">
    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
    </a>

    <script>
        document.getElementById('mobileMenuBtn').addEventListener('click', function() {
            document.getElementById('navLinks').classList.toggle('active');
        });
    </script>
</body>
</html>