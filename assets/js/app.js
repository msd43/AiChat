(() => {
  const shell = document.querySelector('.app-shell');
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

  const renderMarkdownBlocks = (root = document) => {
    root.querySelectorAll('.markdown[data-markdown="1"]').forEach((el) => {
      if (el.dataset.rendered === '1') return;
      el.innerHTML = marked.parse(el.textContent);
      el.querySelectorAll('pre code').forEach((block) => hljs.highlightElement(block));
      el.querySelectorAll('pre').forEach((pre) => {
        const btn = document.createElement('button');
        btn.className = 'copy-btn';
        btn.textContent = 'Kopyala';
        btn.addEventListener('click', async () => {
          await navigator.clipboard.writeText(pre.innerText);
          showToast('Kod kopyalandı.');
        });
        pre.appendChild(btn);
      });
      el.dataset.rendered = '1';
    });
  };

  renderMarkdownBlocks();

  if (!shell) return;

  let mode = 'text';
  const input = document.getElementById('promptInput');
  const sendBtn = document.getElementById('sendBtn');
  const typing = document.getElementById('typing');
  const messages = document.getElementById('messages');
  const csrf = shell.dataset.csrf;
  const chatId = Number(shell.dataset.chatId || '0');

  const scrollToBottom = () => {
    messages.scrollTop = messages.scrollHeight;
  };

  const appendMessage = (role, content, type = 'text', optimistic = false) => {
    const article = document.createElement('article');
    article.className = `msg ${role}`;
    if (optimistic) article.classList.add('optimistic');

    if (type === 'image') {
      const img = document.createElement('img');
      img.src = content;
      img.alt = 'Üretilen görsel';
      article.appendChild(img);
    } else {
      const box = document.createElement('div');
      box.className = 'markdown';
      box.dataset.markdown = '1';
      box.textContent = content;
      article.appendChild(box);
      renderMarkdownBlocks(article);
    }

    const welcome = messages.querySelector('.welcome-card');
    if (welcome) welcome.remove();

    messages.appendChild(article);
    scrollToBottom();
    return article;
  };

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

    appendMessage('user', prompt, mode, true);
    input.value = '';
    input.dispatchEvent(new Event('input'));

    typing.classList.remove('hidden');
    sendBtn.disabled = true;

    try {
      const res = await fetch('/api/gateway.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ chat_id: chatId, prompt, mode, csrf_token: csrf })
      });

      const data = await res.json();
      if (!res.ok || !data.ok) throw new Error(data.message || 'Hata oluştu');

      appendMessage('assistant', data.assistant.content, data.assistant.type);
      showToast('Yanıt alındı.');
    } catch (err) {
      appendMessage('assistant', `Hata: ${err.message}`, 'text');
      showToast(`Hata: ${err.message}`);
    } finally {
      typing.classList.add('hidden');
      sendBtn.disabled = false;
    }
  });
})();
