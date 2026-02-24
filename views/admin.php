<?php include __DIR__ . '/partials/header.php'; ?>
<div class="admin-container">
    <h1>Admin Ayarları</h1>
    <?php if (!empty($_SESSION['error'])): ?><div class="alert error"><?= e($_SESSION['error']); unset($_SESSION['error']); ?></div><?php endif; ?>
    <?php if (!empty($_SESSION['success'])): ?><div class="alert success"><?= e($_SESSION['success']); unset($_SESSION['success']); ?></div><?php endif; ?>

    <form method="post" action="/?route=admin_save">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">

        <label>API Base URL</label>
        <input type="url" name="api_base_url" value="<?= e($settings['api_base_url'] ?? ''); ?>" required>

        <label>API Key (X-API-Key)</label>
        <div class="password-wrap">
            <input id="apiKeyInput" type="password" name="api_key" value="<?= e($settings['api_key'] ?? ''); ?>">
            <button id="toggleApiKey" type="button">Göster/Gizle</button>
        </div>

        <label>Sistem Promptu</label>
        <textarea name="system_prompt" rows="6"><?= e($settings['system_prompt'] ?? ''); ?></textarea>

        <button type="submit">Ayarları Kaydet</button>
    </form>
    <p><a href="/">← Sohbete dön</a></p>
</div>
<?php include __DIR__ . '/partials/footer.php'; ?>
