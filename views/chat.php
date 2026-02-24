<?php include __DIR__ . '/partials/header.php'; ?>
<div class="app-shell" data-csrf="<?= e(csrf_token()); ?>" data-chat-id="<?= (int)($activeChat['id'] ?? 0); ?>">
    <aside class="sidebar">
        <div class="brand">
            <div class="brand-icon">⚡</div>
            <div>
                <h1>MSD</h1>
                <p>AI Studio</p>
            </div>
        </div>

        <form method="post" action="/?route=create_chat">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
            <button class="btn btn-block" type="submit">+ Yeni Sohbet</button>
        </form>

        <div class="chat-list">
            <?php foreach ($chats as $chat): ?>
                <a class="chat-item <?= ((int)$chat['id'] === (int)($activeChat['id'] ?? 0)) ? 'active' : ''; ?>" href="/?chat_id=<?= (int)$chat['id']; ?>">
                    <strong><?= e($chat['title']); ?></strong>
                    <small><?= e(date('d.m.Y H:i', strtotime($chat['updated_at']))); ?></small>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="sidebar-footer">
            <div class="user-card">
                <span><?= e($user['email'] ?? ''); ?></span>
                <small><?= ($user['role'] ?? 'user') === 'admin' ? 'Admin' : 'Kullanıcı'; ?></small>
            </div>
            <div class="sidebar-actions">
                <?php if (($user['role'] ?? 'user') === 'admin'): ?><a href="/?route=admin">Ayarlar</a><?php endif; ?>
                <a href="/?route=logout">Çıkış</a>
                <button id="themeToggle" type="button">Tema</button>
            </div>
        </div>
    </aside>

    <main class="chat-main">
        <header class="chat-topbar">
            <h2>Akıllı Sohbet</h2>
            <p>Metin ve görsel üretimini tek panelde yönetin.</p>
        </header>

        <section id="messages" class="messages">
            <?php if (empty($messages)): ?>
                <div class="welcome-card">
                    <h3>Bugün ne üretelim?</h3>
                    <p>Bir öneri seçin, input otomatik dolsun.</p>
                    <div class="prompt-grid">
                        <button class="prompt-card" data-prompt="Ürün lansmanı için 30 saniyelik etkili reklam metni yaz.">Reklam metni</button>
                        <button class="prompt-card" data-prompt="SaaS girişimi için etkileyici landing sayfası başlık önerileri üret.">Landing başlıkları</button>
                        <button class="prompt-card" data-prompt="Minimal bir fintech dashboard görseli için ayrıntılı prompt yaz.">Görsel prompt</button>
                        <button class="prompt-card" data-prompt="Müşteri destek chatbotu için konuşma tonu rehberi hazırla.">Tone of voice rehberi</button>
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
                <button type="button" class="mode-btn active" data-mode="text">Sohbet</button>
                <button type="button" class="mode-btn" data-mode="image">Resim Üret</button>
            </div>

            <textarea id="promptInput" placeholder="Mesajınızı yazın... (Enter: gönder, Shift+Enter: satır)" rows="1"></textarea>

            <div class="composer-actions">
                <button id="sendBtn" class="btn">Gönder</button>
                <div id="typing" class="typing hidden"><span></span><span></span><span></span> Yanıt bekleniyor...</div>
            </div>
        </section>
    </main>
</div>
<div id="toastContainer" class="toast-container"></div>
<?php include __DIR__ . '/partials/footer.php'; ?>
