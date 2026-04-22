<?php
require_once 'config.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if(!$id) { header('Location: index.php'); exit; }

$sorgu = $db->prepare("SELECT * FROM properties WHERE id = ?");
$sorgu->execute([$id]);
$ilan = $sorgu->fetch();

if(!$ilan) { header('Location: index.php'); exit; }

$resimler_sorgu = $db->prepare("SELECT * FROM property_images WHERE property_id = ? ORDER BY is_main DESC, id ASC");
$resimler_sorgu->execute([$id]);
$resimler = $resimler_sorgu->fetchAll();

$kapak_resmi = 'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?q=80&w=1200&auto=format&fit=crop';
$toplam_resim = count($resimler);
if($toplam_resim > 0) {
    $kapak_resmi = $resimler[0]['image_path'];
} else {
    $toplam_resim = 1;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($ilan['title']) ?> - <?= htmlspecialchars($ayar['site_title']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #ea580c; --primary-light: #f97316; --dark: #0f172a; --darker: #020617; --gray: #64748b; --light-bg: #f8fafc; --white: #ffffff; --border: #e2e8f0; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Outfit', sans-serif; background: var(--white); color: var(--dark); -webkit-font-smoothing: antialiased; }
        h1, h2, h3, h4, .logo { font-family: 'Plus Jakarta Sans', sans-serif; }
        a { text-decoration: none; color: inherit; transition: all 0.3s ease; }
        
        .topbar { background: var(--darker); color: #cbd5e1; font-size: 13px; padding: 12px 5%; display: flex; justify-content: space-between; align-items: center; letter-spacing: 0.5px; }
        .topbar-info { display: flex; gap: 24px; }
        .topbar-info span { display: flex; align-items: center; gap: 8px; }
        .topbar-social { display: flex; gap: 16px; }

        header { background: var(--white); display: flex; align-items: center; justify-content: space-between; padding: 20px 5%; border-bottom: 1px solid rgba(0,0,0,0.05); }
        .logo { font-size: 28px; font-weight: 800; color: var(--dark); display: flex; align-items: center; gap: 12px; letter-spacing: -1px; }
        .logo span { color: var(--primary); }
        .nav-links { display: flex; gap: 36px; font-weight: 600; font-size: 16px; color: var(--gray); }
        .nav-links a:hover { color: var(--dark); }
        .btn-primary { background: var(--primary); color: var(--white); padding: 12px 28px; border-radius: 12px; font-weight: 600; font-size: 15px; border: none; cursor: pointer; }

        .breadcrumb { padding: 20px 5%; color: var(--gray); font-size: 14px; font-weight: 500; border-bottom: 1px solid var(--border); }
        .breadcrumb span { color: var(--dark); font-weight: 600; }

        .detail-container { max-width: 1440px; margin: 0 auto; padding: 40px 5%; display: grid; grid-template-columns: 2fr 1fr; gap: 40px; }
        
        .left-content { display: flex; flex-direction: column; gap: 30px; }
        
        .image-gallery { position: relative; border-radius: 20px; overflow: hidden; height: 500px; background: var(--border); box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        .image-gallery img { width: 100%; height: 100%; object-fit: cover; }
        .gallery-counter { position: absolute; bottom: 20px; right: 20px; background: rgba(0,0,0,0.7); backdrop-filter: blur(4px); color: var(--white); padding: 8px 16px; border-radius: 8px; font-weight: 600; font-size: 14px; display: flex; align-items: center; gap: 8px; }
        .expand-btn { position: absolute; top: 20px; right: 20px; background: rgba(0,0,0,0.5); color: var(--white); width: 40px; height: 40px; border-radius: 50%; display: flex; justify-content: center; align-items: center; cursor: pointer; backdrop-filter: blur(4px); transition: 0.3s; }
        .expand-btn:hover { background: rgba(0,0,0,0.8); }

        .property-header { border-bottom: 1px solid var(--border); padding-bottom: 30px; }
        .ph-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px; }
        .ph-badge { display: inline-block; background: var(--light-bg); color: var(--primary); padding: 6px 14px; border-radius: 8px; font-weight: 700; font-size: 13px; letter-spacing: 0.5px; text-transform: uppercase; border: 1px solid rgba(234,88,12,0.2); }
        .ph-price { font-size: 36px; font-weight: 800; color: var(--primary); font-family: 'Plus Jakarta Sans', sans-serif; }
        .ph-title { font-size: 28px; font-weight: 700; color: var(--dark); line-height: 1.3; margin-bottom: 12px; }
        .ph-loc { color: var(--gray); font-size: 16px; display: flex; align-items: center; gap: 8px; font-weight: 500; margin-bottom: 24px; }
        
        .ph-meta { display: flex; justify-content: space-between; align-items: center; font-size: 14px; color: var(--gray); font-weight: 500; }
        .ph-meta-left { display: flex; gap: 20px; }
        .ph-meta-left span { display: flex; align-items: center; gap: 6px; }
        .ph-meta-right { display: flex; align-items: center; gap: 12px; }
        .share-icons { display: flex; gap: 8px; }
        .share-icons .s-icon { width: 32px; height: 32px; border-radius: 50%; display: flex; justify-content: center; align-items: center; color: var(--white); cursor: pointer; }

        .property-desc { padding-top: 20px; }
        .property-desc h3 { font-size: 22px; margin-bottom: 20px; color: var(--dark); }
        .property-desc p { font-size: 16px; line-height: 1.8; color: var(--gray); }

        .right-sidebar { position: sticky; top: 100px; height: max-content; display: flex; flex-direction: column; gap: 24px; }
        
        .contact-box { background: var(--primary-light); border-radius: 20px; padding: 30px; color: var(--white); box-shadow: 0 20px 40px rgba(234, 88, 12, 0.2); }
        .contact-box h3 { font-size: 24px; font-weight: 700; margin-bottom: 24px; }
        .phone-display { background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3); padding: 16px; border-radius: 12px; font-size: 18px; font-weight: 700; display: flex; align-items: center; gap: 12px; margin-bottom: 16px; }
        .wa-btn { background: #25D366; color: var(--white); padding: 16px; border-radius: 12px; font-size: 16px; font-weight: 600; display: flex; align-items: center; justify-content: center; gap: 10px; cursor: pointer; transition: 0.3s; }
        .wa-btn:hover { background: #20bd5a; }
        .ilan-no-bottom { margin-top: 30px; font-size: 13px; font-weight: 500; opacity: 0.9; border-top: 1px solid rgba(255,255,255,0.2); padding-top: 16px; }

        .share-box { background: var(--white); border: 1px solid var(--border); border-radius: 20px; padding: 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.02); }
        .share-box h4 { font-size: 18px; margin-bottom: 20px; color: var(--dark); }
        .share-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px; }
        .sh-btn { padding: 12px; border-radius: 10px; color: var(--white); font-weight: 600; font-size: 14px; display: flex; align-items: center; justify-content: center; gap: 8px; cursor: pointer; border: none; }
        .btn-fb { background: #1877F2; }
        .btn-wa { background: #25D366; }
        .btn-x { background: #0f1419; }
        .btn-ig { background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%); }
        .btn-copy { width: 100%; background: var(--white); color: var(--dark); border: 1px solid var(--border); padding: 12px; border-radius: 10px; font-weight: 600; display: flex; align-items: center; justify-content: center; gap: 8px; cursor: pointer; transition: 0.3s; }
        .btn-copy:hover { background: var(--light-bg); }

        @media (max-width: 1024px) { .detail-container { grid-template-columns: 1fr; } .right-sidebar { position: static; } }
        @media (max-width: 768px) { .ph-top { flex-direction: column; gap: 12px; } .ph-price { align-self: flex-start; } .ph-meta { flex-direction: column; align-items: flex-start; gap: 16px; } .image-gallery { height: 300px; } }
    </style>
</head>
<body>

<div class="topbar">
    <div class="topbar-info">
        <span><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg> <?= htmlspecialchars($ayar['contact_phone']) ?></span>
    </div>
    <div class="topbar-social">
        <a href="admin/" style="color:var(--white);"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg> Yönetici Girişi</a>
    </div>
</div>

<header>
    <a href="index.php" class="logo">
        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2.5"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
        <?= htmlspecialchars($ayar['site_title']) ?>
    </a>
    <nav class="nav-links">
        <a href="index.php">Ana Sayfa</a>
        <a href="index.php#ilanlar">Tüm İlanlar</a>
        <a href="index.php#iletisim">İletişim</a>
    </nav>
    <a href="index.php#iletisim" class="btn-primary">İlan Ara</a>
</header>

<div class="breadcrumb">
    <div style="max-width: 1440px; margin: 0 auto;">
        <a href="index.php">Ana Sayfa</a> &nbsp;/&nbsp; İlanlar &nbsp;/&nbsp; <?= htmlspecialchars(mb_strtoupper($ilan['property_type'])) ?> &nbsp;/&nbsp; <span><?= htmlspecialchars(mb_substr($ilan['title'], 0, 40)) ?>...</span>
    </div>
</div>

<div class="detail-container">
    <div class="left-content">
        <div class="image-gallery">
            <div class="expand-btn"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 3 21 3 21 9"></polyline><polyline points="9 21 3 21 3 15"></polyline><line x1="21" y1="3" x2="14" y2="10"></line><line x1="3" y1="21" x2="10" y2="14"></line></svg></div>
            <img src="<?= htmlspecialchars($kapak_resmi) ?>" alt="<?= htmlspecialchars($ilan['title']) ?>">
            <div class="gallery-counter">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                1 / <?= $toplam_resim ?>
            </div>
        </div>

        <div class="property-header">
            <div class="ph-top">
                <div class="ph-badge"><?= mb_strtoupper($ilan['property_type']) ?></div>
                <div class="ph-price"><?= number_format($ilan['price'], 0, ',', '.') ?> ₺</div>
            </div>
            <h1 class="ph-title"><?= htmlspecialchars($ilan['title']) ?></h1>
            <div class="ph-loc">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                <?= htmlspecialchars($ilan['location'] ?? 'Belirtilmedi') ?>
            </div>
            <div class="ph-meta">
                <div class="ph-meta-left">
                    <span style="color: var(--primary);">#</span> İlan No: <?= $ilan['id'] ?> &nbsp;&nbsp;&nbsp; 
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg> İlan Tarihi: <?= date('d.m.Y', strtotime($ilan['created_at'])) ?>
                </div>
                <div class="ph-meta-right">
                    Paylaş: 
                    <div class="share-icons">
                        <div class="s-icon" style="background: #25D366;"><svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg></div>
                        <div class="s-icon" style="background: #1877F2;"><svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path></svg></div>
                        <div class="s-icon" style="background: #0f1419;"><svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z"></path></svg></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="property-desc">
            <h3>İlan Açıklaması</h3>
            <p><?= nl2br(htmlspecialchars($ilan['description'] ?? 'Bu ilan için henüz bir açıklama girilmemiştir.')) ?></p>
        </div>
    </div>

    <div class="right-sidebar">
        <div class="contact-box">
            <h3>İletişime Geçin</h3>
            <div class="phone-display">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                <?= htmlspecialchars($ayar['contact_phone']) ?>
            </div>
            <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $ayar['contact_phone']) ?>?text=Merhaba,%20<?= $ilan['id'] ?>%20numaralı%20ilan%20için%20ulaşıyorum." target="_blank" class="wa-btn">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                WhatsApp ile Yaz
            </a>
            <div class="ilan-no-bottom">İlan No: <?= $ilan['id'] ?></div>
        </div>

        <div class="share-box">
            <h4>Bu İlanı Paylaş</h4>
            <div class="share-grid">
                <button class="sh-btn btn-fb"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path></svg> Facebook</button>
                <button class="sh-btn btn-wa"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg> WhatsApp</button>
                <button class="sh-btn btn-x">X</button>
                <button class="sh-btn btn-ig"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line></svg> Instagram</button>
            </div>
            <button class="btn-copy" onclick="navigator.clipboard.writeText(window.location.href); alert('Bağlantı kopyalandı!');">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg> 
                Linki Kopyala
            </button>
        </div>
    </div>
</div>

</body>
</html>