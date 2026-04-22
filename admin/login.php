<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once '../config.php';

if (isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit;
}

$hata = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $db->prepare("SELECT * FROM admins WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_id'] = $admin['id'];
        header("Location: index.php");
        exit;
    } else {
        if ($admin && $password === $admin['password']) {
            $_SESSION['admin_id'] = $admin['id'];
            header("Location: index.php");
            exit;
        } else {
            $hata = 'Girdiğiniz kullanıcı adı veya şifre hatalıdır.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yönetici Girişi - Kontrol Paneli</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #f97316; --dark: #111827; --light: #f9fafb; --border: #e5e7eb; --white: #ffffff; --gray: #6b7280; --info: #3b82f6; --info-light: #eff6ff; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: var(--dark); display: flex; justify-content: center; align-items: center; min-height: 100vh; position: relative; overflow: hidden; font-size: 14px; }
        body::before { content: ''; position: absolute; width: 600px; height: 600px; background: var(--primary); border-radius: 50%; filter: blur(150px); opacity: 0.15; top: -200px; left: -200px; }
        
        .login-box { background: var(--white); padding: 40px; border-radius: 20px; width: 100%; max-width: 420px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); position: relative; z-index: 10; border: 1px solid rgba(255,255,255,0.1); }
        
        .back-link { display: inline-flex; align-items: center; gap: 8px; color: var(--gray); text-decoration: none; font-size: 13px; font-weight: 600; margin-bottom: 30px; transition: all 0.3s ease; }
        .back-link:hover { color: var(--primary); transform: translateX(-5px); }
        
        .login-box h2 { text-align: center; color: var(--dark); margin-bottom: 25px; font-size: 26px; font-weight: 800; letter-spacing: -0.5px; }
        .login-box h2 span { color: var(--primary); }
        
        .demo-info { background: var(--info-light); border: 1px dashed rgba(59, 130, 246, 0.3); padding: 15px; border-radius: 12px; margin-bottom: 25px; text-align: center; }
        .demo-info span { display: block; font-size: 12px; color: var(--info); font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; }
        .demo-info p { color: var(--dark); font-size: 14px; font-weight: 600; }
        .demo-info p b { color: var(--primary); font-weight: 800; }

        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-size: 13px; font-weight: 700; color: var(--dark); }
        .form-control { width: 100%; padding: 14px 16px; border: 1px solid var(--border); border-radius: 10px; font-size: 14px; outline: none; transition: 0.3s; background: var(--light); color: var(--dark); font-weight: 500; }
        .form-control:focus { border-color: var(--primary); background: var(--white); box-shadow: 0 0 0 4px rgba(249, 115, 22, 0.1); }
        
        .btn-submit { width: 100%; background: var(--primary); color: var(--white); border: none; padding: 15px; border-radius: 10px; font-size: 15px; font-weight: 700; cursor: pointer; transition: 0.3s; margin-top: 10px; display: flex; justify-content: center; align-items: center; gap: 8px; }
        .btn-submit:hover { background: #ea580c; transform: translateY(-2px); box-shadow: 0 10px 20px rgba(249, 115, 22, 0.2); }
        
        .alert { background: #fef2f2; color: #991b1b; padding: 14px; border-radius: 10px; margin-bottom: 24px; font-size: 13px; font-weight: 600; text-align: center; border: 1px solid #fecaca; display: flex; align-items: center; justify-content: center; gap: 8px; }
    </style>
</head>
<body>

<div class="login-box">
    <a href="../index.php" class="back-link">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
        Siteye Geri Dön
    </a>
    
    <h2><span>Yönetici</span> Girişi</h2>
    
    <div class="demo-info">
        <span>Demo Erişim Bilgileri</span>
        <p>Kullanıcı Adı: <b>admin</b> &nbsp;|&nbsp; Şifre: <b>123456</b></p>
    </div>

    <?php if($hata): ?>
        <div class="alert">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
            <?= $hata ?>
        </div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="form-group">
            <label>Kullanıcı Adı</label>
            <input type="text" name="username" class="form-control" value="admin" required autocomplete="off">
        </div>
        <div class="form-group">
            <label>Şifre</label>
            <input type="password" name="password" class="form-control" value="123456" required>
        </div>
        <button type="submit" class="btn-submit">
            Güvenli Giriş Yap
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path><polyline points="10 17 15 12 10 7"></polyline><line x1="15" y1="12" x2="3" y2="12"></line></svg>
        </button>
    </form>
</div>

</body>
</html>