<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once '../config.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

$toplam_ilan = $db->query("SELECT COUNT(*) FROM properties")->fetchColumn();
$satilik_ilan = $db->query("SELECT COUNT(*) FROM properties WHERE status = 'satilik'")->fetchColumn();
$kiralik_ilan = $db->query("SELECT COUNT(*) FROM properties WHERE status = 'kiralik'")->fetchColumn();

$kategori_sorgu = $db->query("SELECT category_name FROM categories LIMIT 5");
$kategori_istatistik = [];
while ($kat = $kategori_sorgu->fetch()) {
    $ad = $kat['category_name'];
    $sayi_stmt = $db->prepare("SELECT COUNT(*) FROM properties WHERE property_type = ?");
    $sayi_stmt->execute([$ad]);
    $kategori_istatistik[$ad] = $sayi_stmt->fetchColumn();
}

$son_ilanlar = $db->query("
    SELECT p.*, 
    (SELECT image_path FROM property_images WHERE property_id = p.id AND is_main = 1 LIMIT 1) as main_image 
    FROM properties p 
    ORDER BY p.id DESC LIMIT 4
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kontrol Paneli</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --sidebar: #1e1e2d; --sidebar-hover: #1b1b29; --primary: #f97316; --bg: #f5f8fa; --white: #ffffff; --text-dark: #181c32; --text-muted: #a1a5b7; --border: #eff2f5; --success: #50cd89; --success-light: #e8fff3; --warning: #ffc700; --warning-light: #fff8dd; --purple: #7239ea; --purple-light: #f8f5ff; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: var(--bg); color: var(--text-dark); display: flex; min-height: 100vh; font-size: 14px; overflow-x: hidden; }
        a { text-decoration: none; transition: 0.3s; }
        
        .sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 99; backdrop-filter: blur(2px); }
        .sidebar { width: 260px; background: var(--sidebar); color: var(--text-muted); display: flex; flex-direction: column; flex-shrink: 0; height: 100vh; position: sticky; top: 0; overflow-y: auto; z-index: 100; transition: left 0.3s ease; }
        
        .sidebar-logo { padding: 30px 20px; display: flex; justify-content: center; align-items: center; border-bottom: 1px dashed rgba(255,255,255,0.1); margin-bottom: 20px; }
        .sidebar-logo svg { color: var(--primary); width: 40px; height: 40px; }
        .menu-section { padding: 15px 25px 5px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #4c4e6f; }
        .menu-list { list-style: none; padding: 0 15px; margin-bottom: 15px; }
        .menu-item { display: flex; align-items: center; gap: 12px; padding: 12px 15px; color: var(--text-muted); border-radius: 8px; font-weight: 500; margin-bottom: 4px; }
        .menu-item:hover { color: var(--white); background: var(--sidebar-hover); }
        .menu-item.active { background: var(--primary); color: var(--white); box-shadow: 0 4px 10px rgba(249, 115, 22, 0.3); }
        .menu-item svg { width: 18px; height: 18px; }

        .wrapper { flex: 1; display: flex; flex-direction: column; overflow: hidden; width: 100%; }
        .topbar { background: var(--white); height: 70px; display: flex; align-items: center; justify-content: space-between; padding: 0 40px; box-shadow: 0 2px 10px rgba(0,0,0,0.02); }
        .topbar-left { display: flex; align-items: center; gap: 15px; }
        .mobile-menu-btn { display: none; background: transparent; border: none; color: var(--text-dark); cursor: pointer; padding: 4px; }
        .topbar h1 { font-size: 18px; font-weight: 700; }
        .topbar-right { display: flex; align-items: center; gap: 20px; color: var(--text-muted); font-weight: 600; }

        .content { padding: 40px; overflow-y: auto; height: calc(100vh - 70px); }
        
        .alert-box { background: var(--warning-light); border: 1px dashed var(--warning); color: #b78e00; padding: 16px 20px; border-radius: 8px; font-weight: 500; margin-bottom: 30px; display: flex; align-items: center; gap: 10px; }
        
        .card { background: var(--white); border-radius: 12px; padding: 25px; box-shadow: 0 0 20px rgba(0,0,0,0.02); margin-bottom: 30px; border: 1px solid var(--border); }
        
        .system-status { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 20px; }
        .status-item { display: flex; flex-direction: column; gap: 4px; }
        .status-title { font-size: 12px; color: var(--text-muted); font-weight: 500; }
        .status-val { display: flex; align-items: center; gap: 6px; font-weight: 600; color: var(--success); font-size: 13px; }

        .profile-card { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px; }
        .profile-left { display: flex; align-items: center; gap: 20px; flex-wrap: wrap; }
        .profile-img { width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 3px solid var(--white); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .profile-info h2 { font-size: 20px; font-weight: 800; margin-bottom: 4px; color: var(--text-dark); }
        .profile-info p { color: var(--text-muted); font-size: 13px; font-weight: 500; margin-bottom: 12px; }
        .tags { display: flex; gap: 8px; flex-wrap: wrap; }
        .tag { padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; }
        .tag-o { background: rgba(249, 115, 22, 0.1); color: var(--primary); }
        .tag-b { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
        .tag-g { background: var(--success-light); color: var(--success); }

        .profile-actions { display: flex; align-items: center; gap: 15px; }
        .profile-actions a { display: flex; align-items: center; justify-content: center; color: var(--text-dark); width: 40px; height: 40px; border-radius: 10px; background: var(--bg); transition: 0.3s; }
        .profile-actions a:hover { background: var(--primary); color: var(--white); transform: translateY(-2px); }

        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat-box { background: var(--white); border-radius: 12px; padding: 25px; display: flex; justify-content: space-between; border: 1px solid var(--border); box-shadow: 0 0 20px rgba(0,0,0,0.02); }
        .stat-info { display: flex; flex-direction: column; justify-content: space-between; }
        .stat-title { color: var(--text-muted); font-size: 13px; font-weight: 600; }
        .stat-num { font-size: 32px; font-weight: 800; color: var(--text-dark); margin-top: 10px; }
        .stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; justify-content: center; align-items: center; flex-shrink: 0; }
        .icon-o { background: rgba(249, 115, 22, 0.1); color: var(--primary); }
        .icon-g { background: var(--success-light); color: var(--success); }
        .icon-y { background: var(--warning-light); color: var(--warning); }
        .icon-p { background: var(--purple-light); color: var(--purple); }

        .bottom-grid { display: grid; grid-template-columns: 1fr 2fr; gap: 30px; }
        .card-title { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px dashed var(--border); padding-bottom: 15px; margin-bottom: 15px; font-size: 16px; font-weight: 700; }
        .card-link { font-size: 12px; color: var(--text-muted); font-weight: 600; }
        .card-link:hover { color: var(--primary); }
        
        .cat-list { display: flex; flex-direction: column; gap: 15px; }
        .cat-item { display: flex; justify-content: space-between; align-items: center; font-weight: 600; color: var(--text-dark); }
        .cat-count { background: rgba(249, 115, 22, 0.1); color: var(--primary); padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 700; }

        .prop-list { display: flex; flex-direction: column; gap: 15px; }
        .prop-item { display: flex; align-items: center; justify-content: space-between; padding-bottom: 15px; border-bottom: 1px dashed var(--border); flex-wrap: wrap; gap: 15px; }
        .prop-item:last-child { border-bottom: none; padding-bottom: 0; }
        .prop-left { display: flex; align-items: center; gap: 15px; flex: 1; min-width: 250px; }
        .prop-img { width: 60px; height: 50px; border-radius: 8px; object-fit: cover; }
        .prop-details h4 { font-size: 14px; font-weight: 700; color: var(--text-dark); margin-bottom: 4px; }
        .prop-details span { font-size: 12px; color: var(--text-muted); font-weight: 500; }
        .prop-right { text-align: right; display: flex; flex-direction: column; gap: 4px; }
        .prop-price { font-size: 14px; font-weight: 800; color: var(--primary); }
        .prop-status { font-size: 11px; font-weight: 700; color: var(--success); }

        @media (max-width: 1024px) {
            .bottom-grid { grid-template-columns: 1fr; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
        }

        @media (max-width: 768px) {
            .sidebar { position: fixed; left: -280px; z-index: 1000; width: 260px; }
            .sidebar.active { left: 0; }
            .sidebar-overlay.active { display: block; }
            .mobile-menu-btn { display: block; }
            
            .topbar { padding: 0 20px; }
            .topbar-right { display: none; }
            .content { padding: 20px; }
            
            .stats-grid { grid-template-columns: 1fr; }
            .system-status { grid-template-columns: 1fr 1fr; }
            .profile-card { flex-direction: column; text-align: center; }
            .profile-left { flex-direction: column; justify-content: center; }
            .tags { justify-content: center; }
            .prop-right { text-align: left; width: 100%; }
        }
    </style>
</head>
<body>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="sidebar" id="sidebar">
    <div class="sidebar-logo">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
    </div>
    
    <div class="menu-section">Genel</div>
    <div class="menu-list">
        <a href="index.php" class="menu-item active">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
            Kontrol Paneli
        </a>
        <a href="../index.php" target="_blank" class="menu-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
            Siteyi Görüntüle
        </a>
    </div>

    <div class="menu-section">İlanlar</div>
    <div class="menu-list">
        <a href="manage-properties.php" class="menu-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>
            İlan Listesi
        </a>
        <a href="add-property.php" class="menu-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            Yeni İlan Ekle
        </a>
    </div>

    <div class="menu-section">Sistem Yönetimi</div>
    <div class="menu-list">
        <a href="categories.php" class="menu-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
            Kategoriler
        </a>
        <a href="settings.php" class="menu-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
            Site Ayarları
        </a>
        <a href="logout.php" class="menu-item" style="color: #ef4444; margin-top: 20px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
            Güvenli Çıkış
        </a>
    </div>
</div>

<div class="wrapper">
    <div class="topbar">
        <div class="topbar-left">
            <button class="mobile-menu-btn" id="mobileMenuBtn">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
            </button>
            <h1>Kontrol Paneli</h1>
        </div>
        <div class="topbar-right">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
            <?= date('d/m/Y') ?>
        </div>
    </div>

    <div class="content">
        <div class="alert-box">
            Sisteme yönetici olarak başarıyla giriş yapıldı. Tüm veriler günceldir.
        </div>

        <div class="card system-status">
            <div class="status-item">
                <span class="status-title">Veritabanı Bağlantısı</span>
                <span class="status-val"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg> Tamam</span>
            </div>
            <div class="status-item">
                <span class="status-title">Önbellek (Cache)</span>
                <span class="status-val"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg> Tamam</span>
            </div>
            <div class="status-item">
                <span class="status-title">Depolama Alanı</span>
                <span class="status-val"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg> Yazılabilir</span>
            </div>
            <div class="status-item">
                <span class="status-title">Sistem Sürümü</span>
                <span class="status-val"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg> v1.0.0</span>
            </div>
        </div>

        <div class="card profile-card">
            <div class="profile-left">
                <img src="https://ui-avatars.com/api/?name=ossiqn&background=1e1e2d&color=f97316&size=128" alt="Profile" class="profile-img">
                <div class="profile-info">
                    <h2>ossiqn</h2>
                    <p>Tam Yetkili Yönetici Hesabı</p>
                    <div class="tags">
                        <span class="tag tag-o">Tüm Yetkiler</span>
                        <span class="tag tag-b">Emlak Yönetimi</span>
                        <span class="tag tag-g">Aktif</span>
                    </div>
                </div>
            </div>
            <div class="profile-actions">
                <a href="https://github.com/ossiqn" target="_blank" title="GitHub Profiline Git">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 19c-5 1.5-5-2.5-7-3m14 6v-3.87a3.37 3.37 0 0 0-.94-2.61c3.14-.35 6.44-1.54 6.44-7A5.44 5.44 0 0 0 20 4.77 5.07 5.07 0 0 0 19.91 1S18.73.65 16 2.48a13.38 13.38 0 0 0-7 0C6.27.65 5.09 1 5.09 1A5.07 5.07 0 0 0 5 4.77a5.44 5.44 0 0 0-1.5 3.78c0 5.42 3.3 6.61 6.44 7A3.37 3.37 0 0 0 9 18.13V22"></path></svg>
                </a>
                <a href="settings.php" title="Ayarlara Git">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                </a>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-box">
                <div class="stat-info">
                    <span class="stat-title">Toplam İlan</span>
                    <span class="stat-num"><?= $toplam_ilan ?></span>
                </div>
                <div class="stat-icon icon-o">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path></svg>
                </div>
            </div>
            <div class="stat-box">
                <div class="stat-info">
                    <span class="stat-title">Satılık İlanlar</span>
                    <span class="stat-num"><?= $satilik_ilan ?></span>
                </div>
                <div class="stat-icon icon-g">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                </div>
            </div>
            <div class="stat-box">
                <div class="stat-info">
                    <span class="stat-title">Kiralık İlanlar</span>
                    <span class="stat-num"><?= $kiralik_ilan ?></span>
                </div>
                <div class="stat-icon icon-y">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                </div>
            </div>
            <div class="stat-box">
                <div class="stat-info">
                    <span class="stat-title">İşlemler</span>
                    <span class="stat-num">-</span>
                </div>
                <div class="stat-icon icon-p">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                </div>
            </div>
        </div>

        <div class="bottom-grid">
            <div class="card">
                <div class="card-title">
                    Kategorilere Göre
                </div>
                <div class="cat-list">
                    <?php foreach($kategori_istatistik as $ad => $sayi): ?>
                    <div class="cat-item">
                        <span><?= htmlspecialchars($ad) ?></span>
                        <span class="cat-count"><?= $sayi ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="card">
                <div class="card-title">
                    Son Eklenen İlanlar
                    <a href="manage-properties.php" class="card-link">Tümünü Gör &rarr;</a>
                </div>
                <div class="prop-list">
                    <?php foreach($son_ilanlar as $ilan): ?>
                    <div class="prop-item">
                        <div class="prop-left">
                            <img src="../<?= htmlspecialchars($ilan['main_image'] ?? 'placeholder.jpg') ?>" class="prop-img">
                            <div class="prop-details">
                                <h4><?= htmlspecialchars($ilan['title']) ?></h4>
                                <span><?= htmlspecialchars($ilan['property_type']) ?></span>
                            </div>
                        </div>
                        <div class="prop-right">
                            <div class="prop-price"><?= number_format($ilan['price'], 0, ',', '.') ?> ₺</div>
                            <div class="prop-status">Aktif</div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');

    function toggleMenu() {
        sidebar.classList.toggle('active');
        overlay.classList.toggle('active');
        document.body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : '';
    }

    mobileMenuBtn.addEventListener('click', toggleMenu);
    overlay.addEventListener('click', toggleMenu);
</script>

</body>
</html>