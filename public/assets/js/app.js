document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-flash]').forEach((el) => {
    const close = el.querySelector('.flash-close');
    if (close) {
      close.addEventListener('click', () => el.remove());
    }
    setTimeout(() => {
      el.style.opacity = '0';
      el.style.transition = 'opacity 0.4s';
      setTimeout(() => el.remove(), 400);
    }, 5000);
  });

  document.querySelectorAll('form[novalidate]').forEach((form) => {
    form.addEventListener('submit', (e) => {
      if (!form.checkValidity()) {
        e.preventDefault();
        form.reportValidity();
      }
    });
  });

  document.querySelectorAll('[data-copy]').forEach((btn) => {
    btn.addEventListener('click', async () => {
      try {
        await navigator.clipboard.writeText(btn.dataset.copy);
        btn.textContent = 'Lien copié';
      } catch (err) {
        window.prompt('Copiez le lien :', btn.dataset.copy);
      }
    });
  });

  document.querySelectorAll('[data-share]').forEach((btn) => {
    btn.addEventListener('click', async () => {
      const url = btn.dataset.share;
      if (navigator.share) {
        try { await navigator.share({ title: 'TWEET', url }); } catch (err) {}
        return;
      }
      try {
        await navigator.clipboard.writeText(url);
        btn.textContent = 'Lien copié';
      } catch (err) {
        window.prompt('Copiez le lien :', url);
      }
    });
  });

  document.querySelectorAll('[data-preview]').forEach((input) => {
    input.addEventListener('change', () => {
      const row = input.closest('form')?.querySelector('[data-preview-row]');
      if (!row) return;
      row.innerHTML = '';
      [...input.files].slice(0, 4).forEach((file) => {
        if (!file.type.startsWith('image/')) return;
        const img = document.createElement('img');
        img.src = URL.createObjectURL(file);
        row.appendChild(img);
      });
    });
  });

  const thread = document.getElementById('thread-messages');
  const threadForm = document.getElementById('thread-form');
  if (thread) {
    thread.scrollTop = thread.scrollHeight;
  }
  if (thread && threadForm) {
    const appendBubble = (message) => {
      if (!message || document.querySelector('[data-id="' + message.id + '"]')) return;
      const node = document.createElement('div');
      node.className = 'bubble ' + (message.mine ? 'mine' : 'theirs');
      node.dataset.id = message.id;
      const text = document.createElement('p');
      text.textContent = message.contenu;
      const time = document.createElement('time');
      time.textContent = message.time;
      node.append(text, time);
      thread.appendChild(node);
      thread.dataset.last = String(message.id);
      thread.scrollTop = thread.scrollHeight;
    };

    threadForm.addEventListener('submit', async (event) => {
      event.preventDefault();
      const input = threadForm.querySelector('input[name="contenu"]');
      const body = new FormData(threadForm);
      body.append('ajax', '1');
      const res = await fetch(threadForm.action, {
        method: 'POST',
        body,
        headers: { 'Accept': 'application/json' },
      });
      if (!res.ok) return;
      const data = await res.json();
      appendBubble(data.message);
      input.value = '';
    });

    const poll = async () => {
      try {
        const res = await fetch(thread.dataset.poll + '?after=' + (thread.dataset.last || '0'), {
          headers: { 'Accept': 'application/json' },
        });
        if (!res.ok) return;
        const data = await res.json();
        (data.messages || []).forEach(appendBubble);
      } catch (err) {}
    };
    setInterval(poll, 4000);
  }

  const more = document.getElementById('load-more');
  const list = document.getElementById('feed-list');
  if (more && list) {
    const load = async () => {
      if (more.dataset.loading === '1' || !more.dataset.next) return;
      more.dataset.loading = '1';
      more.textContent = 'Chargement…';
      try {
        const res = await fetch(more.dataset.next, { headers: { 'X-Requested-With': 'fetch' } });
        const data = await res.json();
        list.insertAdjacentHTML('beforeend', data.html || '');
        if (data.next) {
          const url = new URL(more.dataset.next, window.location.origin);
          url.searchParams.set('page', data.next);
          more.dataset.next = url.pathname + url.search;
          more.textContent = 'Voir plus';
          more.dataset.loading = '0';
        } else {
          more.remove();
        }
      } catch (err) {
        more.textContent = 'Réessayer';
        more.dataset.loading = '0';
      }
    };
    more.addEventListener('click', load);
    if ('IntersectionObserver' in window) {
      const observer = new IntersectionObserver((entries) => {
        if (entries.some((entry) => entry.isIntersecting)) load();
      }, { rootMargin: '200px' });
      observer.observe(more);
    }
  }

  document.querySelectorAll('[data-toggle-password]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const input = document.querySelector(btn.getAttribute('data-toggle-password'));
      if (!input) return;
      const show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      const on = btn.querySelector('.eye-on');
      const off = btn.querySelector('.eye-off');
      if (on && off) {
        on.hidden = !show;
        off.hidden = show;
      }
      btn.setAttribute('aria-label', show ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
    });
  });
});
