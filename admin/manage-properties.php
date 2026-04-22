<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once '../config.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    
    $resimler = $db->prepare("SELECT image_path FROM property_images WHERE property_id = ?");
    $resimler->execute([$del_id]);
    while ($resim = $resimler->fetch()) {
        $dosya = '../' . $resim['image_path'];
        if (file_exists($dosya)) {
            unlink($dosya);
        }
    }
    
    $db->prepare("DELETE FROM properties WHERE id = ?")->execute([$del_id]);
    header("Location: manage-properties.php?success=delete");
    exit;
}

$ilanlar = $db->query("SELECT * FROM properties ORDER BY id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>İlanları Yönet - Kontrol Paneli</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root { --sidebar: #1e1e2d; --sidebar-hover: #1b1b29; --primary: #f97316; --bg: #f5f8fa; --white: #ffffff; --text-dark: #181c32; --text-muted: #a1a5b7; --border: #eff2f5; --success: #50cd89; --danger: #f1416c; --info: #3b82f6; --warning: #f59e0b; }
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
        
        .card { background: var(--white); border-radius: 12px; padding: 30px; box-shadow: 0 0 20px rgba(0,0,0,0.02); border: 1px solid var(--border); }
        
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; border-bottom: 1px dashed var(--border); padding-bottom: 15px; }
        .card-title { font-size: 16px; font-weight: 700; color: var(--text-dark); }
        
        .btn-primary { background: var(--primary); color: var(--white); padding: 12px 20px; border-radius: 8px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: 0.3s; font-size: 13px; }
        .btn-primary:hover { background: #ea580c; transform: translateY(-2px); box-shadow: 0 6px 15px rgba(249, 115, 22, 0.2); }

        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { background: var(--bg); padding: 16px; font-weight: 700; color: var(--text-muted); font-size: 12px; text-transform: uppercase; border-bottom: 1px dashed var(--border); letter-spacing: 0.5px; }
        td { padding: 16px; border-bottom: 1px dashed var(--border); font-size: 14px; font-weight: 500; color: var(--text-dark); }
        tr:hover td { background: var(--bg); }
        tr:last-child td { border-bottom: none; }

        .badge { padding: 6px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; display: inline-block; }
        .bg-satilik { background: rgba(249, 115, 22, 0.1); color: var(--primary); }
        .bg-kiralik { background: var(--success-light, #e8fff3); color: var(--success); }
        .bg-kategori { background: rgba(161, 165, 183, 0.1); color: var(--text-dark); }

        .action-btns { display: flex; gap: 8px; }
        .btn-action { width: 34px; height: 34px; border-radius: 8px; display: inline-flex; justify-content: center; align-items: center; color: var(--white); text-decoration: none; transition: 0.3s; border: none; cursor: pointer; }
        .btn-view { background: var(--info); }
        .btn-edit { background: var(--success); }
        .btn-delete { background: var(--danger); }
        
        .btn-action:hover { transform: translateY(-2px); opacity: 0.9; }
        .btn-view:hover { box-shadow: 0 4px 10px rgba(59, 130, 246, 0.2); }
        .btn-edit:hover { box-shadow: 0 4px 10px rgba(80, 205, 137, 0.2); }
        .btn-delete:hover { box-shadow: 0 4px 10px rgba(241, 65, 108, 0.2); }
        
        .table-location { font-size: 12px; color: var(--text-muted); display: flex; align-items: center; gap: 4px; margin-top: 4px; font-weight: 500; }
        .table-price { font-weight: 800; font-size: 15px; color: var(--primary); }

        @media (max-width: 768px) {
            .sidebar { position: fixed; left: -280px; z-index: 1000; width: 260px; }
            .sidebar.active { left: 0; }
            .sidebar-overlay.active { display: block; }
            .mobile-menu-btn { display: block; }
            
            .topbar { padding: 0 20px; }
            .topbar-right { display: none; }
            .content { padding: 20px; }
            
            .card { overflow-x: auto; }
            .card-header { flex-direction: column; align-items: flex-start; gap: 15px; }
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
        <a href="manage-properties.php" class="menu-item active">
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
            <h1>İlan Yönetimi</h1>
        </div>
        <div class="topbar-right">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
            <?= date('d/m/Y') ?>
        </div>
    </div>

    <div class="content">
        <div class="card">
            <div class="card-header">
                <div class="card-title">Portföy Listesi</div>
                <a href="add-property.php" class="btn-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                    Yeni İlan Ekle
                </a>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>İlan Başlığı</th>
                        <th>Fiyat</th>
                        <th>Kategori</th>
                        <th>Durum</th>
                        <th style="text-align: right;">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($ilanlar) > 0): ?>
                        <?php foreach($ilanlar as $ilan): ?>
                        <tr>
                            <td style="color:var(--text-muted); font-weight:700;">#<?= $ilan['id'] ?></td>
                            <td>
                                <strong style="display:block; margin-bottom:2px; min-width: 150px;"><?= htmlspecialchars($ilan['title']) ?></strong>
                                <span class="table-location">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg> 
                                    <?= htmlspecialchars($ilan['location']) ?>
                                </span>
                            </td>
                            <td class="table-price" style="min-width: 120px;"><?= number_format($ilan['price'], 0, ',', '.') ?> ₺</td>
                            <td><span class="badge bg-kategori"><?= htmlspecialchars($ilan['property_type']) ?></span></td>
                            <td><span class="badge <?= $ilan['status'] == 'satilik' ? 'bg-satilik' : 'bg-kiralik' ?>"><?= mb_strtoupper($ilan['status']) ?></span></td>
                            <td style="text-align: right;">
                                <div class="action-btns" style="justify-content: flex-end; min-width: 130px;">
                                    <a href="../detail.php?id=<?= $ilan['id'] ?>" target="_blank" class="btn-action btn-view" title="Sitede Görüntüle">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                    </a>
                                    <a href="edit-property.php?id=<?= $ilan['id'] ?>" class="btn-action btn-edit" title="İlanı Düzenle">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                    </a>
                                    <button type="button" onclick="deleteProperty(<?= $ilan['id'] ?>)" class="btn-action btn-delete" title="İlanı Sil">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align:center; padding: 60px; color: var(--text-muted);">Sistemde kayıtlı ilan bulunmamaktadır.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
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

    function deleteProperty(id) {
        Swal.fire({
            title: 'Emin misiniz?',
            text: "Bu ilanı ve bağlantılı tüm görselleri silmek üzeresiniz. Bu işlem geri alınamaz.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#f1416c',
            cancelButtonColor: '#a1a5b7',
            confirmButtonText: 'Evet, Sil',
            cancelButtonText: 'İptal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'manage-properties.php?delete=' + id;
            }
        });
    }
</script>

<?php if(isset($_GET['success']) && $_GET['success'] == 'delete'): ?>
<script>
    Swal.fire({
        title: 'Silindi',
        text: 'İlan ve bağlantılı görseller sistemden başarıyla kaldırıldı.',
        icon: 'success',
        confirmButtonColor: '#f97316'
    });
</script>
<?php endif; ?>

</body>
</html>