<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once '../config.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST as $key => $value) {
        $stmt = $db->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
        $stmt->execute([trim($value), $key]);
    }
    header("Location: settings.php?success=1");
    exit;
}

$ayarlar_sorgu = $db->query("SELECT setting_key, setting_value FROM settings");
$mevcut_ayarlar = [];
while ($row = $ayarlar_sorgu->fetch()) {
    $mevcut_ayarlar[$row['setting_key']] = $row['setting_value'];
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site Ayarları - Kontrol Paneli</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root { --sidebar: #1e1e2d; --sidebar-hover: #1b1b29; --primary: #f97316; --bg: #f5f8fa; --white: #ffffff; --text-dark: #181c32; --text-muted: #a1a5b7; --border: #eff2f5; --success: #50cd89; --danger: #f1416c; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: var(--bg); color: var(--text-dark); display: flex; min-height: 100vh; font-size: 14px; overflow-x: hidden; }
        a { text-decoration: none; transition: 0.3s; }
        
        .sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 99; backdrop-filter: blur(2px); }
        .sidebar { width: 260px; background: var(--sidebar); color: var(--text-muted); display: flex; flex-direction: column; flex-shrink: 0; height: 100vh; position: sticky; top: 0; overflow-y: auto; z-index: 100; transition: left 0.3s ease; }
        
        .sidebar-logo { padding: 30px 20px; display: flex; justify-content: center; align-items: center; border-bottom: 1px dashed rgba(255,255,255,0.1); margin-bottom: 20px; }
        .sidebar-logo svg { color: var(--primary); width: 40px; height: 40px; }
        .menu-section { padding: 15px 25px 5px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #4c4e6f; }
        .menu-list { list-style: none; padding: 0 15px; margin-bottom: 15px; }
        .menu-item { display: flex; align-items: center; gap: 12px; padding: 12px 15px; color: var(--text-muted); border-radius: 8px; font-weight: 500; transition: 0.3s; margin-bottom: 4px; }
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
        
        .card { background: var(--white); border-radius: 12px; padding: 35px; box-shadow: 0 0 20px rgba(0,0,0,0.02); border: 1px solid var(--border); max-width: 1000px; margin: 0 auto; }
        .card-title { font-size: 18px; font-weight: 700; margin-bottom: 25px; border-bottom: 1px dashed var(--border); padding-bottom: 15px; color: var(--text-dark); }

        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px; }
        .form-group { display: flex; flex-direction: column; gap: 8px; }
        .form-group.full { grid-column: 1 / -1; }
        .form-group label { font-size: 13px; font-weight: 700; color: var(--text-dark); }
        .form-control { padding: 14px 16px; border: 1px solid var(--border); border-radius: 10px; font-size: 14px; outline: none; transition: 0.3s; background: var(--bg); color: var(--text-dark); font-weight: 500; }
        .form-control:focus { border-color: var(--primary); background: var(--white); box-shadow: 0 0 0 4px rgba(249, 115, 22, 0.1); }
        textarea.form-control { resize: vertical; min-height: 120px; }

        .btn-submit { background: var(--primary); color: var(--white); border: none; padding: 16px 32px; font-size: 15px; font-weight: 700; border-radius: 10px; cursor: pointer; transition: 0.3s; width: 100%; margin-top: 10px; display: flex; justify-content: center; align-items: center; gap: 8px; }
        .btn-submit:hover { background: #ea580c; transform: translateY(-2px); box-shadow: 0 8px 15px rgba(249, 115, 22, 0.2); }

        @media (max-width: 768px) { 
            .form-grid { grid-template-columns: 1fr; }
            .sidebar { position: fixed; left: -280px; z-index: 1000; width: 260px; }
            .sidebar.active { left: 0; }
            .sidebar-overlay.active { display: block; }
            .mobile-menu-btn { display: block; }
            
            .topbar { padding: 0 20px; }
            .topbar-right { display: none; }
            .content { padding: 20px; }
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
        <a href="index.php" class="menu-item">
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
        <a href="settings.php" class="menu-item active">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
            Site Ayarları
        </a>
        <a href="logout.php" class="menu-item" style="color: var(--danger); margin-top: 20px;">
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
            <h1>Site Ayarları</h1>
        </div>
        <div class="topbar-right">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
            <?= date('d/m/Y') ?>
        </div>
    </div>

    <div class="content">
        <div class="card">
            <div class="card-title">Genel Yapılandırma</div>
            
            <form id="settingsForm" action="" method="POST">
                <div class="form-grid">
                    <div class="form-group full">
                        <label>Site Başlığı (Logo Metni & Title)</label>
                        <input type="text" name="site_title" class="form-control" value="<?= htmlspecialchars($mevcut_ayarlar['site_title'] ?? '') ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>İletişim Numarası</label>
                        <input type="text" name="contact_phone" class="form-control" value="<?= htmlspecialchars($mevcut_ayarlar['contact_phone'] ?? '') ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>İletişim E-Posta</label>
                        <input type="email" name="contact_email" class="form-control" value="<?= htmlspecialchars($mevcut_ayarlar['contact_email'] ?? '') ?>" required>
                    </div>

                    <div class="form-group full">
                        <label>Ana Sayfa Karşılama Başlığı (Hero Title)</label>
                        <input type="text" name="hero_title" class="form-control" value="<?= htmlspecialchars($mevcut_ayarlar['hero_title'] ?? '') ?>" required>
                    </div>

                    <div class="form-group full">
                        <label>Ana Sayfa Karşılama Alt Metni (Hero Description)</label>
                        <textarea name="hero_desc" class="form-control" required><?= htmlspecialchars($mevcut_ayarlar['hero_desc'] ?? '') ?></textarea>
                    </div>

                    <div class="form-group full">
                        <label>Footer (Alt Bilgi) Telif Hakkı Yazısı</label>
                        <input type="text" name="footer_text" class="form-control" value="<?= htmlspecialchars($mevcut_ayarlar['footer_text'] ?? '') ?>" required>
                    </div>
                </div>

                <button type="button" class="btn-submit" onclick="confirmSave()">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                    Ayarları Kaydet
                </button>
            </form>
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

    function confirmSave() {
        Swal.fire({
            title: 'Emin misiniz?',
            text: "Sistem genel ayarlarını güncellemek üzeresiniz.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#f97316',
            cancelButtonColor: '#a1a5b7',
            confirmButtonText: 'Evet, Kaydet',
            cancelButtonText: 'İptal'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('settingsForm').submit();
            }
        });
    }
</script>

<?php if(isset($_GET['success'])): ?>
<script>
    Swal.fire({
        title: 'Başarılı',
        text: 'Site ayarları başarıyla güncellendi.',
        icon: 'success',
        confirmButtonColor: '#f97316'
    });
</script>
<?php endif; ?>

</body>
</html>