(() => {
  const app = document.querySelector('.app');
  const toastContainer = document.getElementById('toastContainer');

  const showToast = (msg) => {
    if (!toastContainer) return;
    const toast = document.createElement('div');
    toast.className = 'toast';
    toast.textContent = msg;
    toastContainer.appendChild(toast);
    setTimeout(() => toast.remove(), 3200);
  };

  const themeToggle = document.getElementById('themeToggle');
  if (themeToggle) {
    const savedTheme = localStorage.getItem('msd_theme');
    if (savedTheme === 'light') document.body.classList.add('light');
    themeToggle.addEventListener('click', () => {
      document.body.classList.toggle('light');
      localStorage.setItem('msd_theme', document.body.classList.contains('light') ? 'light' : 'dark');
    });
  }

  const toggleApiKey = document.getElementById('toggleApiKey');
  if (toggleApiKey) {
    toggleApiKey.addEventListener('click', () => {
      const input = document.getElementById('apiKeyInput');
      input.type = input.type === 'password' ? 'text' : 'password';
    });
  }

  document.querySelectorAll('.markdown[data-markdown="1"]').forEach((el) => {
    el.innerHTML = marked.parse(el.textContent);
    el.querySelectorAll('pre code').forEach((block) => hljs.highlightElement(block));
  });

  document.querySelectorAll('.markdown pre').forEach((pre) => {
    const btn = document.createElement('button');
    btn.className = 'copy-btn';
    btn.textContent = 'Kopyala';
    btn.addEventListener('click', async () => {
      await navigator.clipboard.writeText(pre.innerText);
      showToast('Kod kopyalandı.');
    });
    pre.appendChild(btn);
  });

  if (!app) return;
  let mode = 'text';
  const input = document.getElementById('promptInput');
  const sendBtn = document.getElementById('sendBtn');
  const typing = document.getElementById('typing');
  const messages = document.getElementById('messages');
  const csrf = app.dataset.csrf;
  const chatId = Number(app.dataset.chatId || '0');

  document.querySelectorAll('.prompt-card').forEach((card) => {
    card.addEventListener('click', () => {
      input.value = card.dataset.prompt || '';
      input.dispatchEvent(new Event('input'));
      input.focus();
    });
  });

  document.querySelectorAll('.mode-btn').forEach((btn) => {
    btn.addEventListener('click', () => {
      mode = btn.dataset.mode;
      document.querySelectorAll('.mode-btn').forEach((b) => b.classList.remove('active'));
      btn.classList.add('active');
    });
  });

  input?.addEventListener('input', () => {
    input.style.height = 'auto';
    input.style.height = `${Math.min(input.scrollHeight, 220)}px`;
  });

  input?.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      sendBtn.click();
    }
  });

  sendBtn?.addEventListener('click', async () => {
    const prompt = input.value.trim();
    if (!prompt) return;
    if (!chatId) {
      showToast('Önce yeni sohbet oluşturun.');
      return;
    }

    typing.classList.remove('hidden');

    try {
      const res = await fetch('/api/gateway.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ chat_id: chatId, prompt, mode, csrf_token: csrf })
      });
      const data = await res.json();
      if (!res.ok || !data.ok) throw new Error(data.message || 'Hata oluştu');
      showToast('Yanıt alındı.');
      location.reload();
    } catch (err) {
      showToast(`Hata: ${err.message}`);
    } finally {
      typing.classList.add('hidden');
    }
  });
})();
