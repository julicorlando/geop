(() => {
  'use strict';
  const cfg = window.GEOP_NOTIFICATION_CONFIG;
  if (!cfg || !cfg.api || !cfg.userId) return;

  const cursorKey = `geop:notificacoes:cursor:${cfg.userId}`;
  const enabledKey = `geop:notificacoes:alertas:${cfg.userId}`;
  const alertTypes = new Set(['ACESSO_PENDENTE', 'ACESSO_APROVADO']);
  let cursor = Number(localStorage.getItem(cursorKey) || 0);
  const serverCursor = Number(cfg.cursor || 0);
  if (!cursor) {
    cursor = serverCursor;
    localStorage.setItem(cursorKey, String(cursor));
  }
  let polling = false;
  let audioContext = null;
  let timer = null;

  const button = document.getElementById('geop-enable-alerts');
  const badge = document.getElementById('geop-notification-badge');

  function alertsEnabled() {
    return localStorage.getItem(enabledKey) === '1';
  }

  function updateButton() {
    if (!button) return;
    if (!('Notification' in window)) {
      button.textContent = 'Notificações não suportadas neste navegador';
      button.disabled = true;
      return;
    }
    if (Notification.permission === 'denied') {
      button.textContent = 'Notificações bloqueadas no navegador';
      button.className = 'btn btn-sm btn-outline-danger w-100';
      return;
    }
    if (alertsEnabled() && Notification.permission === 'granted') {
      button.textContent = 'Alertas no celular ativados';
      button.className = 'btn btn-sm btn-outline-success w-100';
      return;
    }
    button.textContent = 'Ativar som, vibração e notificações';
    button.className = 'btn btn-sm btn-primary w-100';
  }

  function unlockAudio() {
    try {
      const AudioCtx = window.AudioContext || window.webkitAudioContext;
      if (!AudioCtx) return;
      if (!audioContext) audioContext = new AudioCtx();
      if (audioContext.state === 'suspended') audioContext.resume().catch(() => {});
    } catch (_) {}
  }

  function playTone(type) {
    if (!alertsEnabled()) return;
    unlockAudio();
    if (!audioContext || audioContext.state !== 'running') return;
    const approved = type === 'ACESSO_APROVADO';
    const notes = approved ? [740, 988] : [880, 880, 1175];
    const start = audioContext.currentTime + 0.02;
    notes.forEach((frequency, index) => {
      const oscillator = audioContext.createOscillator();
      const gain = audioContext.createGain();
      const when = start + index * 0.19;
      oscillator.type = 'sine';
      oscillator.frequency.setValueAtTime(frequency, when);
      gain.gain.setValueAtTime(0.0001, when);
      gain.gain.exponentialRampToValueAtTime(0.16, when + 0.015);
      gain.gain.exponentialRampToValueAtTime(0.0001, when + 0.14);
      oscillator.connect(gain);
      gain.connect(audioContext.destination);
      oscillator.start(when);
      oscillator.stop(when + 0.16);
    });
  }

  function vibrate(type) {
    if (!alertsEnabled() || !navigator.vibrate) return;
    const pattern = type === 'ACESSO_APROVADO'
      ? [180, 80, 180]
      : [220, 100, 220, 100, 350];
    navigator.vibrate(pattern);
  }

  function absoluteUrl(url) {
    try { return new URL(url || cfg.notificationsUrl || location.href, location.href).href; }
    catch (_) { return location.href; }
  }

  function showToast(item) {
    let area = document.getElementById('geop-live-notification-area');
    if (!area) {
      area = document.createElement('div');
      area.id = 'geop-live-notification-area';
      area.setAttribute('aria-live', 'assertive');
      area.style.cssText = 'position:fixed;right:16px;bottom:16px;z-index:1085;width:min(390px,calc(100vw - 32px));display:grid;gap:10px';
      document.body.appendChild(area);
    }
    const card = document.createElement('a');
    card.href = absoluteUrl(item.url);
    card.className = 'alert alert-warning shadow text-decoration-none text-dark mb-0';
    card.style.cssText = 'border-left:5px solid #b58105;animation:geop-notification-in .22s ease-out';
    const title = document.createElement('strong');
    title.className = 'd-block';
    title.textContent = item.titulo || 'Nova notificação';
    const body = document.createElement('span');
    body.className = 'd-block small mt-1';
    body.textContent = item.mensagem || '';
    card.append(title, body);
    area.prepend(card);
    window.setTimeout(() => card.remove(), 12000);
  }

  async function showSystemNotification(item) {
    if (!alertsEnabled() || !('Notification' in window) || Notification.permission !== 'granted') return;
    const url = absoluteUrl(item.url);
    const options = {
      body: item.mensagem || '',
      icon: cfg.icon || undefined,
      badge: cfg.badge || cfg.icon || undefined,
      tag: `geop-${item.tipo || 'notificacao'}-${item.id}`,
      renotify: true,
      requireInteraction: alertTypes.has(item.tipo),
      vibrate: item.tipo === 'ACESSO_APROVADO' ? [180, 80, 180] : [220, 100, 220, 100, 350],
      data: { url }
    };
    try {
      if ('serviceWorker' in navigator) {
        const registration = await navigator.serviceWorker.ready;
        await registration.showNotification(item.titulo || 'GEOP', options);
      } else {
        const notice = new Notification(item.titulo || 'GEOP', options);
        notice.onclick = () => { window.focus(); location.href = url; };
      }
    } catch (_) {}
  }

  function updateBadge(unread) {
    const total = Number(unread || 0);
    if (!badge) return;
    badge.textContent = String(Math.min(99, total));
    badge.classList.toggle('d-none', total <= 0);
  }

  function announce(item) {
    showToast(item);
    if (alertTypes.has(item.tipo)) {
      playTone(item.tipo);
      vibrate(item.tipo);
      showSystemNotification(item);
      document.title = `● ${item.titulo || 'Nova liberação'} | ${document.title.replace(/^● .*? \| /, '')}`;
    }
  }

  async function poll() {
    if (polling) return;
    polling = true;
    try {
      const url = new URL(cfg.api, location.href);
      url.searchParams.set('after', String(cursor));
      url.searchParams.set('limit', '30');
      const response = await fetch(url, { credentials: 'same-origin', cache: 'no-store', headers: { Accept: 'application/json' } });
      if (response.status === 401 || response.status === 403) return;
      const data = await response.json();
      if (!data || !data.ok) return;
      (data.items || []).forEach(item => {
        cursor = Math.max(cursor, Number(item.id || 0));
        announce(item);
      });
      cursor = Math.max(cursor, Number(data.max_id || 0));
      localStorage.setItem(cursorKey, String(cursor));
      updateBadge(data.unread);
    } catch (_) {
      // Falhas de rede são ignoradas; a próxima consulta tenta novamente.
    } finally {
      polling = false;
    }
  }

  async function activateAlerts() {
    unlockAudio();
    if (!('Notification' in window)) return;
    let permission = Notification.permission;
    if (permission === 'default') permission = await Notification.requestPermission();
    if (permission === 'granted') {
      localStorage.setItem(enabledKey, '1');
      try { navigator.vibrate?.([80, 50, 80]); } catch (_) {}
      playTone('ACESSO_APROVADO');
    }
    updateButton();
  }

  if (button) button.addEventListener('click', activateAlerts);
  document.addEventListener('pointerdown', unlockAudio, { once: true, passive: true });
  document.addEventListener('keydown', unlockAudio, { once: true });
  document.addEventListener('visibilitychange', () => { if (!document.hidden) poll(); });
  window.addEventListener('online', poll);
  updateButton();

  window.setTimeout(poll, 1200);
  timer = window.setInterval(poll, Math.max(5000, Number(cfg.interval || 7000)));
  window.addEventListener('beforeunload', () => { if (timer) window.clearInterval(timer); });
})();
