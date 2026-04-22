<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once '../config.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    header("Location: manage-properties.php");
    exit;
}

if (isset($_GET['delete_img'])) {
    $img_id = (int)$_GET['delete_img'];
    $img_stmt = $db->prepare("SELECT image_path FROM property_images WHERE id = ? AND property_id = ?");
    $img_stmt->execute([$img_id, $id]);
    $resim = $img_stmt->fetch();
    
    if ($resim) {
        $dosya_yolu = '../' . $resim['image_path'];
        if (file_exists($dosya_yolu)) {
            unlink($dosya_yolu);
        }
        $db->prepare("DELETE FROM property_images WHERE id = ?")->execute([$img_id]);
    }
    header("Location: edit-property.php?id=$id&success=img_deleted");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    
    $tr = ['ş','Ş','ı','İ','ç','Ç','ü','Ü','ö','Ö','ğ','Ğ'];
    $eng = ['s','s','i','i','c','c','u','u','o','o','g','g'];
    $slug = strtolower(str_replace($tr, $eng, $title));
    $slug = preg_replace('/[^a-z0-9-]+/', '-', $slug);
    $slug = trim($slug, '-');

    $description = trim($_POST['description'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $status = $_POST['status'] ?? 'satilik';
    $property_type = $_POST['property_type'] ?? '';
    $room_count = $_POST['room_count'] ?? '';
    $square_meters = (int)($_POST['square_meters'] ?? 0);
    $location = trim($_POST['location'] ?? '');

    if ($title !== '' && $price > 0) {
        $stmt = $db->prepare("UPDATE properties SET title = ?, slug = ?, description = ?, price = ?, status = ?, property_type = ?, room_count = ?, square_meters = ?, location = ? WHERE id = ?");
        $stmt->execute([$title, $slug, $description, $price, $status, $property_type, $room_count, $square_meters, $location, $id]);

        if (!empty($_FILES['fotograflar']['name'][0])) {
            $upload_dir = '../uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_count = count($_FILES['fotograflar']['name']);
            $has_main = $db->query("SELECT COUNT(*) FROM property_images WHERE property_id = $id AND is_main = 1")->fetchColumn();

            for ($i = 0; $i < $file_count; $i++) {
                $tmp_name = $_FILES['fotograflar']['tmp_name'][$i];
                if ($tmp_name !== '') {
                    $name = basename($_FILES['fotograflar']['name'][$i]);
                    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                    $new_name = uniqid() . '-' . $i . '.' . $ext;
                    $destination = $upload_dir . $new_name;

                    if (move_uploaded_file($tmp_name, $destination)) {
                        $image_path = 'uploads/' . $new_name;
                        $is_main = ($has_main == 0 && $i === 0) ? 1 : 0; 
                        
                        $img_stmt = $db->prepare("INSERT INTO property_images (property_id, image_path, is_main) VALUES (?, ?, ?)");
                        $img_stmt->execute([$id, $image_path, $is_main]);
                    }
                }
            }
        }
        header("Location: edit-property.php?id=$id&success=updated");
        exit;
    }
}

$ilan_sorgu = $db->prepare("SELECT * FROM properties WHERE id = ?");
$ilan_sorgu->execute([$id]);
$ilan = $ilan_sorgu->fetch();

if (!$ilan) {
    header("Location: manage-properties.php");
    exit;
}

$kategoriler = $db->query("SELECT * FROM categories ORDER BY category_name ASC")->fetchAll();

$mevcut_resimler = $db->prepare("SELECT * FROM property_images WHERE property_id = ? ORDER BY is_main DESC, id ASC");
$mevcut_resimler->execute([$id]);
$resimler = $mevcut_resimler->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>İlanı Düzenle - Kontrol Paneli</title>
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
        
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; border-bottom: 1px dashed var(--border); padding-bottom: 15px; }
        .card-title { font-size: 18px; font-weight: 700; color: var(--text-dark); margin: 0; }
        .btn-back { background: var(--bg); color: var(--text-dark); border: 1px solid var(--border); padding: 10px 16px; border-radius: 8px; font-weight: 600; font-size: 13px; display: inline-flex; align-items: center; gap: 6px; }
        .btn-back:hover { background: #e2e8f0; }

        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px; }
        .form-group { display: flex; flex-direction: column; gap: 8px; }
        .form-group.full { grid-column: 1 / -1; }
        .form-group label { font-size: 13px; font-weight: 700; color: var(--text-dark); }
        .form-control { padding: 14px 16px; border: 1px solid var(--border); border-radius: 10px; font-size: 14px; outline: none; transition: 0.3s; background: var(--bg); color: var(--text-dark); font-weight: 500; }
        .form-control:focus { border-color: var(--primary); background: var(--white); box-shadow: 0 0 0 4px rgba(249, 115, 22, 0.1); }
        textarea.form-control { resize: vertical; min-height: 120px; }
        
        .existing-images { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 16px; margin-top: 16px; }
        .img-box { position: relative; border-radius: 10px; overflow: hidden; border: 1px solid var(--border); height: 120px; }
        .img-box img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .img-delete-btn { position: absolute; top: 6px; right: 6px; background: var(--danger); color: var(--white); width: 28px; height: 28px; border-radius: 6px; display: flex; align-items: center; justify-content: center; cursor: pointer; border: none; transition: 0.3s; }
        .img-delete-btn:hover { opacity: 0.9; transform: scale(1.05); }
        .img-main-badge { position: absolute; top: 6px; left: 6px; background: var(--primary); color: var(--white); font-size: 10px; padding: 4px 8px; border-radius: 4px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }

        .file-upload-box { border: 2px dashed var(--border); border-radius: 12px; padding: 30px 20px; text-align: center; background: var(--bg); cursor: pointer; transition: 0.3s; position: relative; overflow: hidden; }
        .file-upload-box:hover { border-color: var(--primary); background: rgba(249, 115, 22, 0.05); }
        .file-upload-box input[type="file"] { position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%; }
        
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
                <h2 class="card-title">İlanı Düzenle: #<?= $ilan['id'] ?></h2>
                <a href="manage-properties.php" class="btn-back">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                    Listeye Dön
                </a>
            </div>

            <form action="" method="POST" enctype="multipart/form-data">
                <div class="form-grid">
                    <div class="form-group full">
                        <label>İlan Başlığı</label>
                        <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($ilan['title']) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Fiyat (₺)</label>
                        <input type="number" name="price" class="form-control" value="<?= htmlspecialchars($ilan['price']) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>İşlem Durumu</label>
                        <select name="status" class="form-control" required>
                            <option value="satilik" <?= $ilan['status'] == 'satilik' ? 'selected' : '' ?>>Satılık</option>
                            <option value="kiralik" <?= $ilan['status'] == 'kiralik' ? 'selected' : '' ?>>Kiralık</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Kategori Seçimi</label>
                        <select name="property_type" class="form-control" required>
                            <option value="">-- Kategori Seçin --</option>
                            <?php foreach($kategoriler as $kat): ?>
                                <option value="<?= htmlspecialchars($kat['category_name']) ?>" <?= $ilan['property_type'] == $kat['category_name'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($kat['category_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Lokasyon</label>
                        <input type="text" name="location" class="form-control" value="<?= htmlspecialchars($ilan['location']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Oda Sayısı veya Özellik</label>
                        <input type="text" name="room_count" class="form-control" value="<?= htmlspecialchars($ilan['room_count']) ?>">
                    </div>

                    <div class="form-group">
                        <label>Büyüklük (m² vb.)</label>
                        <input type="number" name="square_meters" class="form-control" value="<?= htmlspecialchars($ilan['square_meters']) ?>">
                    </div>

                    <div class="form-group full">
                        <label>İlan Detaylı Açıklaması</label>
                        <textarea name="description" class="form-control" required><?= htmlspecialchars($ilan['description']) ?></textarea>
                    </div>
                    
                    <div class="form-group full">
                        <label style="border-bottom: 1px dashed var(--border); padding-bottom: 10px; margin-bottom: 10px;">Mevcut Fotoğraflar</label>
                        <?php if(count($resimler) > 0): ?>
                            <div class="existing-images">
                                <?php foreach($resimler as $resim): ?>
                                    <div class="img-box">
                                        <?php if($resim['is_main'] == 1): ?>
                                            <span class="img-main-badge">Kapak</span>
                                        <?php endif; ?>
                                        <img src="../<?= htmlspecialchars($resim['image_path']) ?>" alt="İlan Fotoğrafı">
                                        <button type="button" onclick="deleteImage(<?= $resim['id'] ?>, <?= $ilan['id'] ?>)" class="img-delete-btn" title="Fotoğrafı Sil">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                                        </button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p style="color: var(--text-muted); font-size: 13px; font-weight: 500;">Bu ilana ait fotoğraf bulunmamaktadır.</p>
                        <?php endif; ?>
                    </div>

                    <div class="form-group full">
                        <label>Yeni Fotoğraflar Ekle (İsteğe Bağlı)</label>
                        <div class="file-upload-box">
                            <input type="file" name="fotograflar[]" multiple accept="image/*">
                            <div style="font-weight: 700; color: var(--text-dark); margin-bottom: 4px; font-size: 14px;">Yeni fotoğraf seçmek için tıklayın veya sürükleyin</div>
                            <div style="font-size: 12px; color: var(--text-muted); font-weight: 500;">Aynı anda birden fazla dosya seçebilirsiniz. Sadece yeni eklemek istediğiniz fotoğrafları seçin.</div>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn-submit">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                    Değişiklikleri Kaydet
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

    function deleteImage(imgId, propId) {
        Swal.fire({
            title: 'Emin misiniz?',
            text: "Bu fotoğraf kalıcı olarak silinecektir. Bu işlem geri alınamaz.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#f1416c',
            cancelButtonColor: '#a1a5b7',
            confirmButtonText: 'Evet, Sil',
            cancelButtonText: 'İptal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `edit-property.php?id=${propId}&delete_img=${imgId}`;
            }
        });
    }
</script>

<?php if(isset($_GET['success'])): ?>
<script>
    <?php if($_GET['success'] == 'updated'): ?>
        Swal.fire({ 
            title: 'Başarılı', 
            text: 'İlan bilgileri başarıyla güncellendi. İlan listesine yönlendiriliyorsunuz...', 
            icon: 'success', 
            showConfirmButton: false,
            timer: 2000,
            timerProgressBar: true
        }).then(() => {
            window.location.href = 'manage-properties.php';
        });
    <?php elseif($_GET['success'] == 'img_deleted'): ?>
        Swal.fire({ 
            title: 'Silindi', 
            text: 'Fotoğraf sistemden başarıyla kaldırıldı.', 
            icon: 'success', 
            confirmButtonColor: '#f97316' 
        });
    <?php endif; ?>
</script>
<?php endif; ?>

</body>
</html>