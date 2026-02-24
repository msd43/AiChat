<?php include __DIR__ . '/partials/header.php'; ?>
<div class="auth-container">
    <h1>⚡ MSD Kayıt</h1>
    <?php if (!empty($_SESSION['error'])): ?><div class="alert error"><?= e($_SESSION['error']); unset($_SESSION['error']); ?></div><?php endif; ?>
    <form method="post" action="/?route=register_post">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
        <label>E-posta</label>
        <input type="email" name="email" required>
        <label>Şifre</label>
        <input type="password" name="password" minlength="6" required>
        <button type="submit">Kayıt Ol</button>
    </form>
    <p>Zaten hesabın var mı? <a href="/?route=login">Giriş yap</a></p>
</div>
<?php include __DIR__ . '/partials/footer.php'; ?>
