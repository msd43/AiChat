<?php include __DIR__ . '/partials/header.php'; ?>
<div class="app" data-csrf="<?= e(csrf_token()); ?>" data-chat-id="<?= (int)($activeChat['id'] ?? 0); ?>">
    <aside class="sidebar">
        <div class="logo">⚡ <strong>MSD</strong></div>
        <form method="post" action="/?route=create_chat">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
            <button class="btn" type="submit">+ Yeni Sohbet</button>
        </form>
        <div class="chat-list">
            <?php foreach ($chats as $chat): ?>
                <a class="chat-item <?= ((int)$chat['id'] === (int)($activeChat['id'] ?? 0)) ? 'active' : ''; ?>" href="/?chat_id=<?= (int)$chat['id']; ?>">
                    <span><?= e($chat['title']); ?></span>
                    <small><?= e(date('d.m.Y H:i', strtotime($chat['updated_at']))); ?></small>
                </a>
            <?php endforeach; ?>
        </div>
        <div class="sidebar-footer">
            <div><?= e($user['email'] ?? ''); ?></div>
            <?php if (($user['role'] ?? 'user') === 'admin'): ?><a href="/?route=admin">Ayarlar</a><?php endif; ?>
            <a href="/?route=logout">Çıkış</a>
            <button id="themeToggle" type="button">Tema Değiştir</button>
        </div>
    </aside>

    <main class="chat-main">
        <section id="messages" class="messages">
            <?php if (empty($messages)): ?>
                <div class="welcome">
                    <h2>MSD'ye hoş geldin 👋</h2>
                    <p>Başlamak için bir örnek seç veya kendi mesajını yaz.</p>
                    <div class="prompt-cards">
                        <button class="prompt-card" data-prompt="PHP MVC mimarisi için güvenlik checklisti hazırla.">PHP MVC güvenlik checklisti</button>
                        <button class="prompt-card" data-prompt="Tailwind benzeri bir tasarım sistemi planı çıkar.">Tasarım sistemi planı</button>
                        <button class="prompt-card" data-prompt="Modern bir logo için görsel prompt oluştur.">Logo görsel promptu</button>
                        <button class="prompt-card" data-prompt="Kullanıcı onboarding akışını adım adım yaz.">Onboarding akışı</button>
                    </div>
                </div>
            <?php endif; ?>

            <?php foreach ($messages as $msg): ?>
                <article class="msg <?= e($msg['role']); ?>">
                    <?php if ($msg['type'] === 'image'): ?>
                        <img src="<?= e($msg['content']); ?>" alt="Üretilen görsel">
                    <?php else: ?>
                        <div class="markdown" data-markdown="1"><?= e($msg['content']); ?></div>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </section>

        <section class="composer">
            <div class="mode-toggle">
                <button type="button" id="modeText" class="mode-btn active" data-mode="text">Sohbet</button>
                <button type="button" id="modeImage" class="mode-btn" data-mode="image">Resim Üret</button>
            </div>
            <textarea id="promptInput" placeholder="Mesajını yaz... (Enter: gönder, Shift+Enter: yeni satır)"></textarea>
            <div class="composer-actions">
                <button id="sendBtn" class="btn">Gönder</button>
                <div id="typing" class="typing hidden">Yazıyor...</div>
            </div>
        </section>
    </main>
</div>
<div id="toastContainer" class="toast-container"></div>
<?php include __DIR__ . '/partials/footer.php'; ?>
