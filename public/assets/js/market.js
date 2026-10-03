const side = document.getElementById('side');
const scrim = document.getElementById('scrim');
const menuBtn = document.getElementById('menu-btn');

function closeMenu() {
  side?.classList.remove('open');
  scrim?.classList.remove('show');
  menuBtn?.classList.remove('open');
}

menuBtn?.addEventListener('click', () => {
  const open = side?.classList.toggle('open');
  scrim?.classList.toggle('show', !!open);
  menuBtn.classList.toggle('open', !!open);
});

scrim?.addEventListener('click', closeMenu);
side?.querySelectorAll('a').forEach((link) => link.addEventListener('click', closeMenu));

function applyTheme(pref) {
  const resolved = pref === 'auto'
    ? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
    : pref;
  document.documentElement.setAttribute('data-theme', resolved);
  document.documentElement.dataset.themePref = pref;
  localStorage.setItem('tweet-theme', pref);
  document.querySelectorAll('.theme-pill').forEach((btn) => {
    btn.classList.toggle('is-on', btn.dataset.theme === pref);
  });
}

const savedTheme = localStorage.getItem('tweet-theme') || 'light';
applyTheme(savedTheme);

document.querySelectorAll('.theme-pill').forEach((btn) => {
  btn.addEventListener('click', () => applyTheme(btn.dataset.theme || 'light'));
});

window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
  if ((localStorage.getItem('tweet-theme') || 'light') === 'auto') applyTheme('auto');
});

const notifPref = document.getElementById('pref-notif');
if (notifPref) {
  notifPref.checked = localStorage.getItem('tweet-notif') !== '0';
  notifPref.addEventListener('change', () => {
    localStorage.setItem('tweet-notif', notifPref.checked ? '1' : '0');
  });
}

document.querySelectorAll('.thumbs button').forEach((button) => {
  button.addEventListener('click', () => {
    const main = document.querySelector('.gallery-main');
    if (main && button.dataset.src) main.src = button.dataset.src;
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
      headers: { Accept: 'application/json' },
    });
    if (!res.ok) return;
    const data = await res.json();
    appendBubble(data.message);
    input.value = '';
  });

  const poll = async () => {
    try {
      const res = await fetch(thread.dataset.poll + '?after=' + (thread.dataset.last || '0'), {
        headers: { Accept: 'application/json' },
      });
      if (!res.ok) return;
      const data = await res.json();
      (data.messages || []).forEach(appendBubble);
    } catch (err) {}
  };
  setInterval(poll, 4000);
}

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
    const shareUrl = btn.dataset.share;
    if (navigator.share) {
      try { await navigator.share({ title: 'TWEET', url: shareUrl }); } catch (err) {}
      return;
    }
    try {
      await navigator.clipboard.writeText(shareUrl);
      btn.textContent = 'Lien copié';
    } catch (err) {
      window.prompt('Copiez le lien :', shareUrl);
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

const feedMore = document.getElementById('load-more');
const feedList = document.getElementById('feed-list');
if (feedMore && feedList) {
  const loadMore = async () => {
    if (feedMore.dataset.loading === '1' || !feedMore.dataset.next) return;
    feedMore.dataset.loading = '1';
    feedMore.textContent = 'Chargement…';
    try {
      const res = await fetch(feedMore.dataset.next, { headers: { 'X-Requested-With': 'fetch' } });
      const data = await res.json();
      feedList.querySelector('.feed-empty')?.remove();
      feedList.insertAdjacentHTML('beforeend', data.html || '');
      if (data.next) {
        const nextUrl = new URL(feedMore.dataset.next, window.location.origin);
        nextUrl.searchParams.set('page', data.next);
        feedMore.dataset.next = nextUrl.pathname + nextUrl.search;
        feedMore.textContent = 'Voir plus';
        feedMore.dataset.loading = '0';
      } else {
        feedMore.remove();
      }
    } catch (err) {
      feedMore.textContent = 'Réessayer';
      feedMore.dataset.loading = '0';
    }
  };
  feedMore.addEventListener('click', loadMore);
  if ('IntersectionObserver' in window) {
    const observer = new IntersectionObserver((entries) => {
      if (entries.some((entry) => entry.isIntersecting)) loadMore();
    }, { rootMargin: '220px' });
    observer.observe(feedMore);
  }
}

/* Premium search */
(() => {
  const page = document.getElementById('search-page');
  if (!page) return;
  const input = document.getElementById('search-input');
  const clearBtn = document.getElementById('search-clear');
  const suggestBox = document.getElementById('search-suggest');
  const liveBox = document.getElementById('search-live');
  const liveStatus = document.getElementById('search-live-status');
  const recentBox = document.getElementById('search-recent');
  const recentEmpty = document.getElementById('search-recent-empty');
  const recentClear = document.getElementById('search-recent-clear');
  const form = document.getElementById('search-form');
  const scopeInput = document.getElementById('search-scope');
  const suggestUrl = page.dataset.suggest;
  const exploreUrl = page.dataset.explore;
  const base = page.dataset.base || '/';
  const assetBase = page.dataset.asset || base + 'assets/';
  let scope = page.dataset.scope || 'all';
  const type = page.dataset.type || '';
  const KEY = 'tweet-search-recent';
  let timer = null;
  let liveTimer = null;
  let active = -1;
  let items = [];
  let reqId = 0;
  const homeHtml = !((input?.value || '').trim()) && liveBox ? liveBox.innerHTML : '';

  const mediaUrl = (path) => {
    if (!path) return assetBase + 'images/default-avatar.svg';
    if (/^https?:\/\//i.test(path)) return path;
    return base + 'uploads/' + String(path).replace(/^\/+/, '');
  };
  const money = (n) => {
    if (n == null || n === '') return '';
    try { return new Intl.NumberFormat('fr-FR').format(Number(n)) + ' DH'; }
    catch (e) { return n + ' DH'; }
  };
  const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => (
    { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]
  ));

  const loadRecent = () => {
    try { return JSON.parse(localStorage.getItem(KEY) || '[]'); } catch (e) { return []; }
  };
  const saveRecent = (q) => {
    const clean = (q || '').trim();
    if (clean.length < 1) return;
    localStorage.setItem(KEY, JSON.stringify([clean, ...loadRecent().filter((x) => x !== clean)].slice(0, 8)));
  };
  const renderRecent = () => {
    if (!recentBox) return;
    const list = loadRecent();
    recentBox.innerHTML = '';
    if (recentClear) recentClear.hidden = !list.length;
    if (!list.length) {
      if (recentEmpty) recentEmpty.hidden = false;
      return;
    }
    if (recentEmpty) recentEmpty.hidden = true;
    list.forEach((term) => {
      const wrap = document.createElement('div');
      wrap.className = 'sx-recent-item';
      const a = document.createElement('a');
      a.href = exploreUrl + '?q=' + encodeURIComponent(term);
      a.innerHTML = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="8"/><path d="M12 8v5l3 2"/></svg><span></span>';
      a.querySelector('span').textContent = term;
      const rm = document.createElement('button');
      rm.type = 'button';
      rm.className = 'sx-recent-rm';
      rm.setAttribute('aria-label', 'Retirer');
      rm.innerHTML = '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 6l12 12M18 6 6 18"/></svg>';
      rm.addEventListener('click', (e) => {
        e.preventDefault();
        localStorage.setItem(KEY, JSON.stringify(loadRecent().filter((x) => x !== term)));
        renderRecent();
      });
      wrap.appendChild(a);
      wrap.appendChild(rm);
      recentBox.appendChild(wrap);
    });
  };

  const row = (href, title, sub, mediaHtml) => {
    const a = document.createElement('a');
    a.className = 'sx-sg';
    a.href = href;
    a.innerHTML = (mediaHtml || '') + '<span class="sx-meta"><strong></strong><small></small></span>';
    a.querySelector('strong').textContent = title;
    a.querySelector('small').textContent = sub || '';
    return a;
  };

  const renderSuggest = (data) => {
    if (!suggestBox) return;
    suggestBox.innerHTML = '';
    items = [];
    active = -1;
    const addGroup = (title, rows) => {
      if (!rows.length) return;
      const g = document.createElement('div');
      g.className = 'sx-sg-group';
      const h = document.createElement('h3');
      h.textContent = title;
      g.appendChild(h);
      rows.forEach((el) => { items.push(el); g.appendChild(el); });
      suggestBox.appendChild(g);
    };

    addGroup('Utilisateurs', (data.users || []).map((u) => row(
      base + 'user/' + u.id,
      ((u.prenom || '') + ' ' + (u.nom || '')).trim(),
      '@' + (u.username || ('user' + u.id)),
      '<img class="sx-ava" src="' + mediaUrl(u.photo) + '" alt="">'
    )));
    addGroup('Publications', (data.posts || []).map((p) => row(
      base + 'posts/' + p.id,
      ((p.prenom || '') + ' ' + (p.nom || '')).trim(),
      p.extrait || '',
      '<img class="sx-ava" src="' + mediaUrl(p.photo) + '" alt="">'
    )));
    addGroup('Annonces', (data.listings || []).map((l) => row(
      base + 'annonces/' + l.id,
      l.title || 'Annonce',
      [l.city, money(l.price)].filter(Boolean).join(' · '),
      '<img class="sx-thumb" src="' + mediaUrl(l.photo) + '" alt="">'
    )));
    addGroup('Hashtags', (data.hashtags || []).map((h) => row(
      base + 'hashtag/' + encodeURIComponent(h.name),
      '#' + h.name,
      (h.total || 0) + ' publications',
      '<span class="sx-ico hash">#</span>'
    )));
    addGroup('Lieux', (data.places || []).map((p) => row(
      exploreUrl + '?q=' + encodeURIComponent(p.name) + '&scope=places',
      p.name,
      (p.total || 0) + ' publications',
      '<span class="sx-ico loc">⌖</span>'
    )));

    suggestBox.hidden = !suggestBox.children.length;
  };

  const section = (title, seeAllHref, rowsHtml, i) => (
    '<section class="sx-section" style="--i:' + i + '">'
    + '<div class="sx-section-head"><h2>' + esc(title) + '</h2>'
    + (seeAllHref ? '<a href="' + seeAllHref + '">Tout voir</a>' : '')
    + '</div><div class="sx-list">' + rowsHtml + '</div></section>'
  );

  const resultRow = (href, media, title, sub) => (
    '<a class="sx-row" href="' + href + '">' + media
    + '<span class="sx-meta"><strong>' + title + '</strong><small>' + sub + '</small></span></a>'
  );

  const renderLive = (data, q) => {
    if (!liveBox) return;
    const total = Number(data.total || 0);
    const sc = scope || 'all';
    if (!q) {
      liveBox.innerHTML = homeHtml;
      renderRecent();
      return;
    }
    if (!total) {
      liveBox.innerHTML =
        '<div class="sx-empty">'
        + '<div class="sx-empty-mark" aria-hidden="true"></div>'
        + '<h2>Aucun résultat</h2>'
        + '<p>Rien pour « ' + esc(q) + ' ». Essayez un @pseudo, un #hashtag ou un autre mot.</p>'
        + '<a href="' + exploreUrl + '">Effacer la recherche</a>'
        + '</div>';
      return;
    }

    const see = (key) => exploreUrl + '?q=' + encodeURIComponent(q) + '&scope=' + key + (type ? '&type=' + encodeURIComponent(type) : '');
    let html = '<div class="sx-results">';
    let i = 0;

    if ((sc === 'all' || sc === 'users') && (data.users || []).length) {
      html += section('Utilisateurs', sc === 'all' ? see('users') : '', (data.users || []).map((u) => resultRow(
        base + 'user/' + u.id,
        '<img class="sx-ava" src="' + mediaUrl(u.photo) + '" alt="">',
        esc(((u.prenom || '') + ' ' + (u.nom || '')).trim()),
        esc('@' + (u.username || ('user' + u.id)) + (u.bio ? ' · ' + String(u.bio).slice(0, 48) : ''))
      )).join(''), i++);
    }
    if ((sc === 'all' || sc === 'posts') && (data.posts || []).length) {
      html += section('Publications', sc === 'all' ? see('posts') : '', (data.posts || []).map((p) => resultRow(
        base + 'posts/' + p.id,
        '<img class="sx-ava" src="' + mediaUrl(p.photo) + '" alt="">',
        esc(((p.prenom || '') + ' ' + (p.nom || '')).trim())
          + (p.username ? ' <em>@' + esc(p.username) + '</em>' : ''),
        esc(p.extrait || '')
      )).join(''), i++);
    }
    if ((sc === 'all' || sc === 'listings') && (data.listings || []).length) {
      html += section('Annonces', sc === 'all' ? see('listings') : '', (data.listings || []).map((l) => resultRow(
        base + 'annonces/' + l.id,
        '<img class="sx-thumb" src="' + mediaUrl(l.photo) + '" alt="">',
        esc(l.title || 'Annonce'),
        esc([l.city, money(l.price)].filter(Boolean).join(' · '))
      )).join(''), i++);
    }
    if ((sc === 'all' || sc === 'hashtags') && (data.hashtags || []).length) {
      html += section('Hashtags', sc === 'all' ? see('hashtags') : '', (data.hashtags || []).map((h) => resultRow(
        base + 'hashtag/' + encodeURIComponent(h.name),
        '<span class="sx-ico hash">#</span>',
        esc('#' + h.name),
        esc((h.total || 0) + ' publications')
      )).join(''), i++);
    }
    if ((sc === 'all' || sc === 'places') && (data.places || []).length) {
      html += section('Lieux', sc === 'all' ? see('places') : '', (data.places || []).map((p) => resultRow(
        exploreUrl + '?q=' + encodeURIComponent(p.name) + '&scope=posts',
        '<span class="sx-ico loc">⌖</span>',
        esc(p.name),
        esc((p.total || 0) + ' publications')
      )).join(''), i++);
    }
    html += '</div>';
    liveBox.innerHTML = html;

    document.querySelectorAll('#search-scopes a').forEach((a) => {
      const key = a.dataset.scope;
      const n = key === 'all' ? total : ((data[key] || []).length);
      a.classList.toggle('is-active', key === sc);
      a.href = see(key === 'all' ? 'all' : key);
      let em = a.querySelector('em');
      if (n > 0) {
        if (!em) { em = document.createElement('em'); a.appendChild(em); }
        em.textContent = String(n);
      } else if (em) em.remove();
    });
  };

  const fetchSuggest = async (q) => {
    if (!q || q.trim().length < 1) {
      suggestBox.hidden = true;
      suggestBox.innerHTML = '';
      return;
    }
    try {
      const res = await fetch(suggestUrl + '?q=' + encodeURIComponent(q.trim()), {
        headers: { Accept: 'application/json', 'X-Requested-With': 'fetch' },
      });
      if (!res.ok) return;
      renderSuggest(await res.json());
    } catch (e) {}
  };

  const fetchLive = async (q) => {
    const id = ++reqId;
    if (!q) {
      if (liveStatus) liveStatus.hidden = true;
      if (homeHtml) {
        renderLive({ total: 0 }, '');
        history.replaceState(null, '', exploreUrl);
      } else {
        window.location.href = exploreUrl;
      }
      return;
    }
    if (liveStatus) liveStatus.hidden = false;
    try {
      const params = new URLSearchParams({ q, scope, ajax: '1' });
      if (type) params.set('type', type);
      const res = await fetch(exploreUrl + '?' + params.toString(), {
        headers: { Accept: 'application/json', 'X-Requested-With': 'fetch' },
      });
      if (!res.ok || id !== reqId) return;
      renderLive(await res.json(), q);
      history.replaceState(null, '', exploreUrl + '?q=' + encodeURIComponent(q) + '&scope=' + encodeURIComponent(scope)
        + (type ? '&type=' + encodeURIComponent(type) : ''));
    } catch (e) {
    } finally {
      if (id === reqId && liveStatus) liveStatus.hidden = true;
    }
  };

  const syncClear = () => { if (clearBtn) clearBtn.hidden = !(input?.value || '').trim(); };

  input?.addEventListener('input', () => {
    syncClear();
    clearTimeout(timer);
    clearTimeout(liveTimer);
    const q = input.value;
    timer = setTimeout(() => fetchSuggest(q), 180);
    liveTimer = setTimeout(() => fetchLive(q.trim()), 300);
  });
  input?.addEventListener('focus', () => { if (input.value.trim()) fetchSuggest(input.value); });
  clearBtn?.addEventListener('click', () => {
    if (!input) return;
    input.value = '';
    syncClear();
    suggestBox.hidden = true;
    suggestBox.innerHTML = '';
    fetchLive('');
    input.focus();
  });
  recentClear?.addEventListener('click', () => { localStorage.removeItem(KEY); renderRecent(); });
  document.addEventListener('click', (e) => { if (!page.contains(e.target)) suggestBox.hidden = true; });
  input?.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') { suggestBox.hidden = true; return; }
    if (suggestBox.hidden || !items.length) return;
    if (e.key === 'ArrowDown') { e.preventDefault(); active = (active + 1) % items.length; }
    else if (e.key === 'ArrowUp') { e.preventDefault(); active = (active - 1 + items.length) % items.length; }
    else if (e.key === 'Enter' && active >= 0) { e.preventDefault(); items[active].click(); return; }
    else return;
    items.forEach((el, i) => el.classList.toggle('is-active', i === active));
    items[active]?.scrollIntoView({ block: 'nearest' });
  });
  form?.addEventListener('submit', () => saveRecent(input?.value || ''));
  document.querySelectorAll('#search-scopes a').forEach((a) => {
    a.addEventListener('click', (e) => {
      const q = (input?.value || '').trim();
      if (!q) return;
      e.preventDefault();
      scope = a.dataset.scope || 'all';
      if (scopeInput) scopeInput.value = scope;
      page.dataset.scope = scope;
      saveRecent(q);
      fetchLive(q);
      suggestBox.hidden = true;
    });
  });
  renderRecent();
  syncClear();
  if ((input?.value || '').trim()) saveRecent(input.value);
})();

const socialFeed = document.getElementById('social-feed');
const feedBanner = document.getElementById('feed-new-banner');
if (socialFeed && feedBanner && socialFeed.dataset.poll) {
  const pollNew = async () => {
    try {
      const mode = socialFeed.dataset.mode || 'recent';
      const after = socialFeed.dataset.newest || '0';
      const res = await fetch(
        socialFeed.dataset.poll + '?mode=' + encodeURIComponent(mode) + '&after_id=' + encodeURIComponent(after),
        { headers: { Accept: 'application/json' } }
      );
      if (!res.ok) return;
      const data = await res.json();
      if ((data.count || 0) > 0) {
        feedBanner.hidden = false;
        feedBanner.innerHTML = data.count === 1
          ? '<span>1 nouvelle publication</span><em>Actualiser</em>'
          : '<span>' + data.count + ' nouvelles publications</span><em>Actualiser</em>';
      }
    } catch (err) {}
  };
  feedBanner.addEventListener('click', () => {
    window.location.reload();
  });
  setInterval(pollNew, 20000);
}
