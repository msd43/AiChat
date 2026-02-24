<?php include __DIR__ . '/partials/header.php'; ?>
<div class="auth-container">
    <h1>⚡ MSD Giriş</h1>
    <?php if (!empty($_SESSION['error'])): ?><div class="alert error"><?= e($_SESSION['error']); unset($_SESSION['error']); ?></div><?php endif; ?>
    <?php if (!empty($_SESSION['success'])): ?><div class="alert success"><?= e($_SESSION['success']); unset($_SESSION['success']); ?></div><?php endif; ?>
    <form method="post" action="/?route=login_post">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
        <label>E-posta</label>
        <input type="email" name="email" required>
        <label>Şifre</label>
        <input type="password" name="password" required>
        <button type="submit">Giriş Yap</button>
    </form>
    <p>Hesabın yok mu? <a href="/?route=register">Kayıt ol</a></p>
</div>
<?php include __DIR__ . '/partials/footer.php'; ?>
