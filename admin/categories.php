<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once '../config.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['category_name'])) {
    $name = trim($_POST['category_name']);
    
    if ($name !== '') {
        $tr = ['ş','Ş','ı','İ','ç','Ç','ü','Ü','ö','Ö','ğ','Ğ'];
        $eng = ['s','s','i','i','c','c','u','u','o','o','g','g'];
        $slug = strtolower(str_replace($tr, $eng, $name));
        $slug = preg_replace('/[^a-z0-9-]+/', '-', $slug);
        $slug = trim($slug, '-');

        $stmt = $db->prepare("INSERT INTO categories (category_name, category_slug) VALUES (?, ?)");
        try {
            $stmt->execute([$name, $slug]);
            header("Location: categories.php?success=add");
            exit;
        } catch(PDOException $e) {
            header("Location: categories.php?error=exists");
            exit;
        }
    }
}

if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    $db->prepare("DELETE FROM categories WHERE id = ?")->execute([$del_id]);
    header("Location: categories.php?success=delete");
    exit;
}

$kategoriler = $db->query("SELECT * FROM categories ORDER BY id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kategori Yönetimi - Kontrol Paneli</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root { --sidebar: #1e1e2d; --sidebar-hover: #1b1b29; --primary: #f97316; --bg: #f5f8fa; --white: #ffffff; --text-dark: #181c32; --text-muted: #a1a5b7; --border: #eff2f5; --danger: #f1416c; }
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
        
        .grid-layout { display: grid; grid-template-columns: 1fr 2fr; gap: 30px; }
        
        .card { background: var(--white); border-radius: 12px; padding: 30px; box-shadow: 0 0 20px rgba(0,0,0,0.02); border: 1px solid var(--border); }
        .card-title { font-size: 16px; font-weight: 700; margin-bottom: 25px; border-bottom: 1px dashed var(--border); padding-bottom: 15px; color: var(--text-dark); }
        
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-size: 13px; font-weight: 700; color: var(--text-dark); }
        .form-control { width: 100%; padding: 14px 16px; border: 1px solid var(--border); border-radius: 10px; font-size: 14px; outline: none; transition: 0.3s; background: var(--bg); color: var(--text-dark); font-weight: 500; }
        .form-control:focus { border-color: var(--primary); background: var(--white); box-shadow: 0 0 0 4px rgba(249, 115, 22, 0.1); }
        
        .btn-submit { background: var(--primary); color: var(--white); border: none; padding: 14px 24px; font-size: 14px; font-weight: 700; border-radius: 10px; cursor: pointer; transition: 0.3s; width: 100%; display: flex; justify-content: center; align-items: center; gap: 8px; }
        .btn-submit:hover { background: #ea580c; transform: translateY(-2px); }

        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { background: var(--bg); padding: 16px; font-weight: 700; color: var(--text-muted); font-size: 12px; text-transform: uppercase; border-bottom: 1px dashed var(--border); letter-spacing: 0.5px; white-space: nowrap; }
        td { padding: 16px; border-bottom: 1px dashed var(--border); font-size: 14px; font-weight: 500; color: var(--text-dark); }
        tr:hover td { background: var(--bg); }
        tr:last-child td { border-bottom: none; }

        .btn-delete { width: 34px; height: 34px; border-radius: 8px; display: inline-flex; justify-content: center; align-items: center; color: var(--white); transition: 0.3s; background: var(--danger); border: none; cursor: pointer; }
        .btn-delete:hover { opacity: 0.9; transform: translateY(-2px); box-shadow: 0 4px 10px rgba(241, 65, 108, 0.2); }

        @media (max-width: 1024px) { .grid-layout { grid-template-columns: 1fr; } }
        @media (max-width: 768px) {
            .sidebar { position: fixed; left: -280px; z-index: 1000; width: 260px; }
            .sidebar.active { left: 0; }
            .sidebar-overlay.active { display: block; }
            .mobile-menu-btn { display: block; }
            
            .topbar { padding: 0 20px; }
            .topbar-right { display: none; }
            .content { padding: 20px; }
            
            .card { overflow-x: auto; }
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
        <a href="categories.php" class="menu-item active">
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
            <h1>Kategori Yönetimi</h1>
        </div>
        <div class="topbar-right">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
            <?= date('d/m/Y') ?>
        </div>
    </div>

    <div class="content">
        <div class="grid-layout">
            <div class="card" style="height: max-content;">
                <div class="card-title">Yeni Kategori Oluştur</div>
                <form action="" method="POST">
                    <div class="form-group">
                        <label>Kategori Adı</label>
                        <input type="text" name="category_name" class="form-control" placeholder="Örn: Araçlar, Arsa, Bilgisayar" required autocomplete="off">
                    </div>
                    <button type="submit" class="btn-submit">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        Kategoriyi Kaydet
                    </button>
                </form>
            </div>

            <div class="card">
                <div class="card-title">Sistemde Kayıtlı Kategoriler</div>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Kategori Adı</th>
                            <th>SEO Bağlantısı (Slug)</th>
                            <th style="text-align: right;">İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($kategoriler) > 0): ?>
                            <?php foreach($kategoriler as $kat): ?>
                            <tr>
                                <td style="color:var(--text-muted);">#<?= $kat['id'] ?></td>
                                <td><strong style="color:var(--text-dark); white-space: nowrap;"><?= htmlspecialchars($kat['category_name']) ?></strong></td>
                                <td style="color:var(--primary); font-family: monospace; font-size: 13px;"><?= htmlspecialchars($kat['category_slug']) ?></td>
                                <td style="text-align: right;">
                                    <button type="button" onclick="deleteCategory(<?= $kat['id'] ?>)" class="btn-delete">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4" style="text-align:center; padding: 40px; color: var(--text-muted);">Sistemde kayıtlı kategori bulunmamaktadır.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
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

    function deleteCategory(id) {
        Swal.fire({
            title: 'Emin misiniz?',
            text: "Bu kategoriyi silmek istediğinize emin misiniz? Bu işlem geri alınamaz.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#f1416c',
            cancelButtonColor: '#a1a5b7',
            confirmButtonText: 'Evet, Sil',
            cancelButtonText: 'İptal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'categories.php?delete=' + id;
            }
        });
    }
</script>

<?php if(isset($_GET['success'])): ?>
<script>
    <?php if($_GET['success'] == 'add'): ?>
    Swal.fire({ title: 'Başarılı', text: 'Kategori başarıyla oluşturuldu.', icon: 'success', confirmButtonColor: '#f97316' });
    <?php elseif($_GET['success'] == 'delete'): ?>
    Swal.fire({ title: 'Silindi', text: 'Kategori sistemden başarıyla silindi.', icon: 'success', confirmButtonColor: '#f97316' });
    <?php endif; ?>
</script>
<?php endif; ?>

<?php if(isset($_GET['error']) && $_GET['error'] == 'exists'): ?>
<script>
    Swal.fire({ title: 'Hata', text: 'Bu kategori adı zaten kullanılmaktadır. Lütfen farklı bir isim belirleyin.', icon: 'error', confirmButtonColor: '#f97316' });
</script>
<?php endif; ?>

</body>
</html>