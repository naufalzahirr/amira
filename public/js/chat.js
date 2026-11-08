(() => {
  const cfg = JSON.parse(document.getElementById('chat-config').textContent);
  const $ = (id) => document.getElementById(id);
  const log = $('chat-log');
  const items = $('chat-items');
  const form = $('composer');
  const input = $('composer-input');
  const sendBtn = $('send-btn');
  const waitBanner = $('wait-banner');
  const quotaUsed = $('quota-used');
  const nextHintBtn = $('next-hint');
  const attachInput = $('attach-input');
  const attachPreview = $('attach-preview');

  let conversationId = cfg.conversationId;
  let hintLevel = cfg.hintLevel;
  let busy = false;
  let retryTimer = null;
  let attachment = null; // File

  const MAX_IMAGE = 8 * 1024 * 1024;

  // Ikuti jawaban yang mengalir selama murid tidak sedang menggulir ke atas membaca pesan lama.
  let stick = true;
  log.addEventListener('scroll', () => { stick = log.scrollHeight - log.scrollTop - log.clientHeight < 140; });
  const scrollDown = (force = false) => {
    if (force) stick = true;
    if (stick) log.scrollTop = log.scrollHeight;
  };
  scrollDown(true);

  const toast = (text) => {
    const el = document.createElement('div');
    el.className = 'toast';
    el.textContent = text;
    document.body.appendChild(el);
    setTimeout(() => el.remove(), 2600);
  };

  const autoGrow = () => {
    input.style.height = 'auto';
    input.style.height = Math.min(input.scrollHeight, 220) + 'px';
  };
  input.addEventListener('input', autoGrow);

  const focusEnd = () => {
    input.focus();
    input.setSelectionRange(input.value.length, input.value.length);
    autoGrow();
  };

  /* ---------- Pemberitahuan privasi (sekali per murid) ---------- */
  let locked = !!cfg.needsAck;
  const lockComposer = () => {
    input.disabled = locked;
    attachInput.disabled = locked;
    document.querySelectorAll('[data-preset]').forEach((b) => { b.disabled = locked; });
    if (locked) input.placeholder = 'Baca pemberitahuan di atas dulu, ya.';
    else input.placeholder = 'Tulis pertanyaan atau usahamu...';
  };
  $('privacy-ok')?.addEventListener('click', async (e) => {
    e.target.disabled = true;
    try {
      const res = await fetch(e.target.dataset.url, {
        method: 'POST',
        headers: { Accept: 'application/json', 'X-CSRF-TOKEN': cfg.csrf },
      });
      if (!res.ok) throw new Error();
      $('privacy-card').remove();
      $('chat-welcome')?.classList.remove('hidden');
      locked = false;
      lockComposer();
      setBusy(false);
      input.focus();
    } catch {
      e.target.disabled = false;
      toast('Gagal menyimpan. Coba lagi.');
    }
  });

  /* ---------- Tangga petunjuk ---------- */
  const renderHint = () => {
    const bar = $('hint-bar');
    if (!bar || !hintLevel) return;
    bar.dataset.level = hintLevel;
    bar.querySelectorAll('.hint-step').forEach((s) => {
      const n = Number(s.dataset.step);
      s.classList.toggle('done', n < hintLevel);
      s.classList.toggle('on', n === hintLevel);
    });
    $('hint-label').textContent = `Petunjuk ${hintLevel} · ${cfg.hintLabels[hintLevel]}`;
    nextHintBtn.disabled = locked || busy || !conversationId || hintLevel >= 3;
    nextHintBtn.textContent = hintLevel >= 3 ? 'Petunjuk terakhir' : 'Petunjuk berikutnya';
    nextHintBtn.title = hintLevel >= 3
      ? 'Ini tingkat bantuan tertinggi. Kalau masih buntu, diskusikan dengan guru.'
      : `Naik ke Petunjuk ${hintLevel + 1}: ${cfg.hintLabels[hintLevel + 1]}`;
  };
  renderHint();

  nextHintBtn?.addEventListener('click', () => {
    if (busy || hintLevel >= 3) return;
    const text = input.value.trim();
    input.value = '';
    autoGrow();
    send({ text, nextHint: true });
  });

  /* ---------- Lampiran gambar ---------- */
  const setAttachment = (file) => {
    if (!file) return;
    if (!/^image\/(png|jpeg|webp)$/.test(file.type)) { toast('Gambar harus PNG, JPG, atau WebP.'); return; }
    if (file.size > MAX_IMAGE) { toast('Ukuran gambar maksimal 8 MB.'); return; }
    attachment = file;
    const img = attachPreview.querySelector('img');
    if (img.src) URL.revokeObjectURL(img.src);
    img.src = URL.createObjectURL(file);
    $('attach-name').textContent = file.name || 'Screenshot';
    attachPreview.classList.remove('hidden');
    input.focus();
  };
  const clearAttachment = () => {
    attachment = null;
    attachInput.value = '';
    attachPreview.classList.add('hidden');
  };
  attachInput.addEventListener('change', () => setAttachment(attachInput.files[0]));
  $('attach-remove').addEventListener('click', clearAttachment);
  input.addEventListener('paste', (e) => {
    const file = [...(e.clipboardData?.files || [])].find((f) => f.type.startsWith('image/'));
    if (file) { e.preventDefault(); setAttachment(file); }
  });
  log.addEventListener('dragover', (e) => e.preventDefault());
  log.addEventListener('drop', (e) => {
    e.preventDefault();
    setAttachment([...(e.dataTransfer?.files || [])].find((f) => f.type.startsWith('image/')));
  });

  /* ---------- Preset & panel samping ---------- */
  document.querySelectorAll('[data-preset]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const text = btn.dataset.preset;
      input.value = input.value.trim() ? `${input.value.trim()}\n${text} ` : `${text} `;
      focusEnd();
    });
  });

  document.querySelector('[data-toggle-side]')?.addEventListener('click', () => {
    $('chat-side').classList.toggle('open');
  });

  input.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' && !e.shiftKey && !e.isComposing) {
      e.preventDefault();
      form.requestSubmit();
    }
  });

  /* ---------- Gelembung pesan ---------- */
  const removeWelcome = () => $('chat-welcome')?.remove();

  const addUser = (text, file) => {
    removeWelcome();
    const wrap = document.createElement('div');
    wrap.className = 'msg msg-user';
    const bubble = document.createElement('div');
    bubble.className = 'bubble';
    if (file) {
      const a = document.createElement('a');
      a.className = 'bubble-img';
      const img = document.createElement('img');
      img.src = URL.createObjectURL(file);
      img.alt = 'Gambar lampiran';
      a.appendChild(img);
      bubble.appendChild(a);
    }
    const t = document.createElement('div');
    t.className = 'bubble-text';
    t.textContent = text;
    bubble.appendChild(t);
    wrap.appendChild(bubble);
    items.appendChild(wrap);
    scrollDown(true);
    return wrap;
  };

  const avatar = () => {
    const t = document.createElement('template');
    t.innerHTML = cfg.avatarHtml.trim(); // dirender server dari data mentor milik guru
    return t.content.firstElementChild;
  };

  const addModelShell = () => {
    const wrap = document.createElement('div');
    wrap.className = 'msg msg-model';
    wrap.appendChild(avatar());
    const col = document.createElement('div');
    col.style.minWidth = '0';
    const bubble = document.createElement('div');
    bubble.className = 'bubble typing';
    bubble.innerHTML = '<span></span><span></span><span></span>';
    bubble.setAttribute('aria-label', `${cfg.mentorName} sedang menulis`);
    col.appendChild(bubble);
    wrap.appendChild(col);
    items.appendChild(wrap);
    scrollDown(true);
    return { wrap, col, bubble };
  };

  const finishModel = (shell, data) => {
    const { wrap, col, bubble } = shell;
    wrap.dataset.topic = data.topic || '';
    bubble.className = 'bubble';
    bubble.innerHTML = data.reply_html; // di-escape server (Str::markdown html_input=escape)

    if (data.hint_used || data.solved) {
      const meta = document.createElement('div');
      meta.className = 'msg-meta';
      if (data.hint_used) {
        const tag = document.createElement('span');
        tag.className = `hint-tag hint-${data.hint_used}`;
        tag.textContent = `Petunjuk ${data.hint_used} · ${cfg.hintLabels[data.hint_used]}`;
        meta.appendChild(tag);
      }
      if (data.solved) {
        const tag = document.createElement('span');
        tag.className = 'solved-tag';
        tag.textContent = 'Masalah selesai';
        meta.appendChild(tag);
      }
      col.insertBefore(meta, bubble);
    }

    const tools = document.createElement('div');
    tools.className = 'msg-tools';
    tools.innerHTML = '<button type="button" class="tool" data-copy-answer>Salin</button>'
      + '<button type="button" class="tool" data-copy-cite>Salin sitasi</button>'
      + '<button type="button" class="tool" data-explain-back>Jelaskan balik</button>';
    if (data.via) {
      const via = document.createElement('span');
      via.className = 'via';
      via.textContent = `via ${data.via}`;
      tools.appendChild(via);
    }
    const raw = document.createElement('template');
    raw.setAttribute('data-raw', '');
    raw.content.textContent = data.reply_text;
    col.append(tools, raw);
    enhanceCode(bubble);
    scrollDown();
  };

  const addError = (text) => {
    const wrap = document.createElement('div');
    wrap.className = 'flash flash-error';
    wrap.style.margin = '0';
    wrap.textContent = text;
    items.appendChild(wrap);
    scrollDown(true);
  };

  /* Tombol salin di setiap blok kode */
  const enhanceCode = (root) => {
    root.querySelectorAll('pre').forEach((pre) => {
      if (pre.querySelector('.code-copy')) return;
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'code-copy';
      btn.textContent = 'Salin kode';
      btn.addEventListener('click', () => copy(pre.querySelector('code')?.textContent ?? pre.textContent, 'Kode disalin. Jangan lupa beri sitasi.'));
      pre.appendChild(btn);
    });
  };
  enhanceCode(items);

  const setBusy = (state) => {
    busy = state;
    sendBtn.disabled = state || locked;
    renderHint();
  };
  lockComposer();
  setBusy(false);

  const waitAndRetry = (seconds, payload, message) => {
    let left = seconds;
    waitBanner.classList.remove('hidden');
    const tick = () => {
      waitBanner.textContent = `${message} Pesanmu dikirim ulang otomatis dalam ${left} detik.`;
      if (left <= 0) {
        clearInterval(retryTimer);
        waitBanner.classList.add('hidden');
        send(payload, true);
      }
      left -= 1;
    };
    tick();
    retryTimer = setInterval(tick, 1000);
  };

  /* Membaca Server-Sent Events dari fetch */
  async function* readEvents(response) {
    const reader = response.body.getReader();
    const decoder = new TextDecoder();
    let buffer = '';
    for (;;) {
      const { value, done } = await reader.read();
      if (done) break;
      buffer += decoder.decode(value, { stream: true });
      let idx;
      while ((idx = buffer.indexOf('\n\n')) !== -1) {
        const chunk = buffer.slice(0, idx);
        buffer = buffer.slice(idx + 2);
        const line = chunk.split('\n').find((l) => l.startsWith('data:'));
        if (line) {
          try { yield JSON.parse(line.slice(5)); } catch { /* abaikan */ }
        }
      }
    }
  }

  async function send(payload, isRetry = false) {
    const { text, file, nextHint } = payload;
    setBusy(true);
    if (!isRetry) {
      addUser(text || (nextHint ? 'Aku masih buntu. Boleh minta petunjuk berikutnya?' : ''), file);
    }
    const shell = addModelShell();

    const body = new FormData();
    if (text) body.append('message', text);
    if (conversationId) body.append('conversation_id', conversationId);
    if (nextHint) body.append('next_hint', '1');
    if (file) body.append('image', file);

    try {
      const res = await fetch(cfg.sendUrl, {
        method: 'POST',
        headers: { Accept: 'text/event-stream, application/json', 'X-CSRF-TOKEN': cfg.csrf },
        body,
      });

      if ((res.headers.get('content-type') || '').includes('text/event-stream')) {
        let finished = false;
        for await (const event of readEvents(res)) {
          if (event.type === 'delta') {
            shell.bubble.className = 'bubble streaming';
            shell.bubble.innerHTML = event.html;
            scrollDown();
          } else if (event.type === 'done') {
            finished = true;
            conversationId = event.conversation_id;
            if (event.conversation_url && location.href !== event.conversation_url) {
              history.replaceState(null, '', event.conversation_url);
            }
            if (quotaUsed && event.used !== null && event.used !== undefined) quotaUsed.textContent = event.used;
            finishModel(shell, event);
            if (hintLevel && event.hint_level) {
              const solvedNow = event.solved && hintLevel > 1;
              hintLevel = event.hint_level;
              if (solvedNow) toast('Masalah selesai. Tangga petunjuk kembali ke awal.');
            }
          } else if (event.type === 'error') {
            finished = true;
            shell.wrap.remove();
            addError(event.message);
          }
        }
        if (!finished) {
          shell.wrap.remove();
          addError('Jawaban terputus. Kirim ulang pesanmu.');
        }
        setBusy(false);
        return;
      }

      const data = await res.json().catch(() => ({}));
      shell.wrap.remove();

      if (res.status === 429 && data.busy) {
        waitAndRetry(data.retry_after || 15, payload, data.message || 'AMIRA sedang ramai.');
        return; // tetap sibuk sampai kirim ulang
      }

      if (res.status === 419) {
        addError('Sesi kedaluwarsa. Muat ulang halaman, lalu kirim lagi.');
      } else {
        const firstError = data.errors ? Object.values(data.errors)[0]?.[0] : null;
        addError(data.error || firstError || data.message || 'AMIRA belum bisa menjawab. Coba lagi sebentar.');
      }
    } catch (e) {
      shell.wrap.remove();
      addError('Koneksi terputus. Periksa internet, lalu kirim lagi.');
    }
    setBusy(false);
  }

  form.addEventListener('submit', (e) => {
    e.preventDefault();
    const text = input.value.trim();
    if ((!text && !attachment) || busy || locked) return;
    const file = attachment;
    input.value = '';
    autoGrow();
    clearAttachment();
    send({ text, file });
  });

  /* ---------- Salin, sitasi, jelaskan balik ---------- */
  async function copy(text, label) {
    try {
      await navigator.clipboard.writeText(text);
      toast(label);
    } catch {
      toast('Gagal menyalin. Blok teksnya lalu salin manual.');
    }
  }

  items.addEventListener('click', (e) => {
    const msg = e.target.closest('.msg-model');
    if (!msg) return;
    const raw = msg.querySelector('template[data-raw]')?.content.textContent?.trim() || '';

    if (e.target.matches('[data-copy-answer]')) {
      copy(raw, 'Jawaban disalin.');
    } else if (e.target.matches('[data-copy-cite]')) {
      const date = new Date().toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
      const topic = msg.dataset.topic ? `: ${msg.dataset.topic}` : '';
      copy(`// Dibantu AMIRA (${cfg.mentorName}), ${date}${topic}`, 'Sitasi disalin. Tempel di atas kode yang dibantu.');
    } else if (e.target.matches('[data-explain-back]')) {
      input.value = 'Aku coba jelaskan balik dengan kata-kataku sendiri: ';
      focusEnd();
    }
  });
})();
