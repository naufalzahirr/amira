<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>AMIRA Web</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <style>
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: #0f172a;
            color: #e5e7eb;
            margin: 0;
            padding: 0;
            display: flex;
            height: 100vh;
            justify-content: center;
            align-items: center;
        }

        .chat-container {
            width: 100%;
            max-width: 1100px;
            height: 92vh;
            background: #020617;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.6);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            border: 1px solid #1f2937;
            position: relative;
            z-index: 10;
        }

        /* Overlay di dalam container */
        .screen-overlay {
            position: absolute;
            inset: 0;
            background: rgba(15, 23, 42, 0.6);
            z-index: 40;
            display: none;
        }

        .screen-overlay.show {
            display: block;
        }

        .chat-header {
            padding: 12px 20px;
            border-bottom: 1px solid #1f2937;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: radial-gradient(circle at top left, #0ea5e9, transparent 55%);
            gap: 12px;
            position: relative;
            z-index: 45; /* di atas overlay */
        }

        .chat-header h1 {
            font-size: 18px;
            margin: 0;
        }

        .chat-header small {
            color: #d1d5db;
        }

        .header-controls {
            display: flex;
            flex-direction: column;
            gap: 4px;
            align-items: flex-end;
        }

        .header-controls label {
            font-size: 11px;
            color: #e5e7eb;
        }

        .provider-select {
            border-radius: 9999px;
            border: 1px solid #374151;
            background: #020617;
            color: #e5e7eb;
            padding: 4px 10px;
            font-size: 13px;
            margin-left: 4px;
        }

        .header-line {
            display: flex;
            gap: 8px;
            align-items: center;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .clear-chat-btn {
            border-radius: 9999px;
            border: 1px solid #4b5563;
            background: transparent;
            color: #e5e7eb;
            padding: 4px 10px;
            font-size: 11px;
            cursor: pointer;
        }

        .clear-chat-btn:hover {
            background: #111827;
        }

        .chat-main {
            flex: 1;
            display: flex;
            min-height: 0;
            position: relative;
            z-index: 41;
        }

        .chat-body {
            flex: 1;
            padding: 16px 20px;
            overflow-y: auto;
        }

        .message {
            margin-bottom: 12px;
            max-width: 80%;
            line-height: 1.4;
            padding: 10px 14px;
            border-radius: 12px;
            font-size: 14px;
            white-space: pre-wrap;
        }

        .message.user {
            margin-left: auto;
            background: #1d4ed8;
        }

        .message.amira {
            margin-right: auto;
            background: #111827;
            border: 1px solid #1f2937;
        }

        .resource-panel {
            width: 290px;
            border-left: 1px solid #1f2937;
            background: #020617;
            padding: 12px;
            font-size: 13px;
            display: flex;
            flex-direction: column;
            z-index: 51; /* di atas overlay */
        }

        .resource-header {
            font-weight: 600;
            margin-bottom: 6px;
        }

        .resource-subtitle {
            font-size: 11px;
            color: #9ca3af;
            margin-bottom: 8px;
        }

        .resource-search {
            border-radius: 9999px;
            border: 1px solid #374151;
            background: #020617;
            color: #e5e7eb;
            padding: 4px 10px;
            font-size: 12px;
            margin-bottom: 8px;
        }

        .resource-list {
            flex: 1;
            overflow-y: auto;
            border-top: 1px solid #111827;
            margin-top: 6px;
            padding-top: 6px;
        }

        .resource-item {
            display: flex;
            gap: 6px;
            align-items: flex-start;
            padding: 6px 4px;
            border-radius: 8px;
            cursor: pointer;
        }

        .resource-item:hover {
            background: #020617;
            border: 1px solid #1f2937;
        }

        .resource-item input[type="checkbox"] {
            margin-top: 3px;
        }

        .resource-title {
            font-size: 13px;
            font-weight: 500;
        }

        .resource-meta {
            font-size: 11px;
            color: #9ca3af;
        }

        .resource-open {
            font-size: 11px;
            color: #60a5fa;
            text-decoration: none;
            display: inline-block;
            margin-top: 3px;
        }

        .resource-open:hover {
            text-decoration: underline;
        }

        .resource-empty {
            font-size: 12px;
            color: #9ca3af;
            margin-top: 10px;
        }

        .chat-footer {
            border-top: 1px solid #1f2937;
            padding: 10px 14px;
            display: flex;
            gap: 8px;
            align-items: flex-end;
            background: #020617;
            position: relative;
            z-index: 45; /* di atas overlay */
        }

        .footer-left {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .chat-footer textarea {
            flex: 1;
            resize: none;
            border-radius: 10px;
            border: 1px solid #374151;
            background: #020617;
            color: #e5e7eb;
            padding: 8px 10px;
            font-size: 14px;
            min-height: 40px;
            max-height: 90px;
        }

        .chat-footer textarea:focus {
            outline: none;
            border-color: #3b82f6;
        }

        .footer-tools {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 11px;
            color: #9ca3af;
            flex-wrap: wrap;
        }

        .file-label {
            border-radius: 9999px;
            border: 1px solid #374151;
            padding: 3px 10px;
            cursor: pointer;
        }

        .file-label input {
            display: none;
        }

        .file-info {
            font-size: 11px;
            color: #e5e7eb;
        }

        .chat-footer button {
            border: none;
            border-radius: 9999px;
            padding: 8px 16px;
            font-size: 14px;
            cursor: pointer;
            background: #22c55e;
            color: #020617;
            font-weight: 600;
        }

        .chat-footer button:disabled {
            opacity: 0.5;
            cursor: default;
        }

        .status {
            font-size: 11px;
            color: #9ca3af;
        }

        .desktop-only {
            display: inline-block;
        }

        .mobile-only {
            display: none;
        }

        .mobile-config-btn,
        .mobile-resource-btn {
            border-radius: 9999px;
            border: 1px solid #4b5563;
            background: transparent;
            color: #e5e7eb;
            padding: 4px 10px;
            font-size: 13px;
            cursor: pointer;
        }

        .mobile-config-btn:active,
        .mobile-resource-btn:active {
            background: #111827;
        }

        .header-close-btn,
        .resource-close-btn {
            border-radius: 9999px;
            border: 1px solid #4b5563;
            background: transparent;
            color: #e5e7eb;
            padding: 3px 10px;
            font-size: 11px;
            cursor: pointer;
        }

        /* Tablet / desktop kecil */
        @media (max-width: 900px) {
            .chat-main {
                flex-direction: column;
            }
            .resource-panel {
                width: 100%;
                border-left: none;
                border-top: 1px solid #1f2937;
                max-height: 220px;
            }
        }

        /* HP mode (<= 640px) */
        @media (max-width: 640px) {
            body {
                align-items: stretch;
                justify-content: stretch;
                height: 100vh;
            }

            .chat-container {
                height: 100vh;
                max-width: 100%;
                border-radius: 0;
                box-shadow: none;
                border-left: none;
                border-right: none;
            }

            .chat-header {
                padding: 10px 12px;
            }

            .chat-header h1 {
                font-size: 16px;
            }

            .chat-header small {
                font-size: 11px;
            }

            .chat-body {
                padding: 10px 12px;
            }

            .chat-footer {
                padding: 8px 10px;
            }

            .provider-select {
                font-size: 12px;
                padding: 3px 8px;
            }

            .clear-chat-btn {
                font-size: 10px;
                padding: 3px 8px;
            }

            .message {
                font-size: 13px;
                padding: 8px 10px;
            }

            .resource-panel {
                position: absolute;
                left: 0;
                right: 0;
                bottom: 0;
                max-height: 65vh;
                border-left: none;
                border-top: 1px solid #1f2937;
                border-radius: 16px 16px 0 0;
                box-shadow: 0 -10px 30px rgba(0,0,0,0.6);
                display: none;
            }

            .resource-panel.open {
                display: flex;
            }

            .resource-panel {
                padding: 10px 12px;
                font-size: 12px;
            }

            .resource-title {
                font-size: 12px;
            }

            .resource-meta,
            .resource-open {
                font-size: 10px;
            }

            .desktop-only {
                display: none !important;
            }

            .mobile-only {
                display: inline-flex;
            }

            /* header-controls jadi panel overlay di HP */
            .header-controls {
                display: none;
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                background: #020617;
                padding: 10px 12px 12px;
                border-bottom: 1px solid #1f2937;
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
                z-index: 50; /* di atas overlay */
            }

            .header-controls.open {
                display: flex;
            }

            .header-line {
                justify-content: flex-start;
            }
        }
    </style>
</head>
<body>
<div class="chat-container">
    {{-- overlay di dalam container --}}
    <div class="screen-overlay" id="screen-overlay"></div>

    <div class="chat-header">
        <div>
            <h1>AMIRA Web</h1>
            <small>Artificial Mentor for Intelligent Reasoning and Assistance</small>
        </div>

        {{-- Desktop: kontrol selalu terlihat, Mobile: jadi panel overlay --}}
        <div class="header-controls" id="header-controls">
            <div class="header-line">
                <label>Provider &amp; Model</label>
                <select id="provider" class="provider-select">
                    @foreach ($providersConfig as $key => $provider)
                        <option value="{{ $key }}" {{ $key === $defaultProvider ? 'selected' : '' }}>
                            {{ $provider['label'] }}
                        </option>
                    @endforeach
                </select>

                <select id="model" class="provider-select"></select>
            </div>

            <div class="header-line">
                <label>Mapel</label>
                <select id="subject" class="provider-select">
                    @foreach ($subjects as $subject)
                        <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                    @endforeach
                </select>

                <button type="button" id="clear-chat-btn" class="clear-chat-btn">
                    🧹 Hapus chat mapel ini
                </button>

                {{-- tombol tutup panel di HP --}}
                <button type="button" id="close-settings-btn" class="mobile-only header-close-btn">
                    Tutup
                </button>
            </div>
        </div>

        {{-- Tombol pengaturan khusus HP --}}
        <button type="button" id="open-settings-btn" class="mobile-only mobile-config-btn">
            ⚙️
        </button>
    </div>

    <div class="chat-main">
        {{-- Kolom pesan --}}
        <div id="chat-body" class="chat-body">
            <div class="message amira">
                Hai, saya AMIRA. Kamu bisa:
                <ul>
                    <li>Memilih <b>Mapel</b> (riwayat chat per mapel tersimpan)</li>
                    <li>Mengetik pertanyaan biasa</li>
                    <li>Mengunggah <b>dokumen</b> sebagai bahan ajar</li>
                    <li>Mengunggah <b>gambar</b> untuk dianalisis (soal / diagram)</li>
                    <li>Memilih <b>model</b> (Gemini / Groq, termasuk vision &amp; search)</li>
                </ul>
                Di HP, pengaturan model/mapel ada di tombol ⚙️ dan daftar PDF di tombol 📚 😊
            </div>
        </div>

        {{-- Panel resource (desktop: sidebar, HP: bottom sheet) --}}
        <aside class="resource-panel" id="resource-panel">
            <div class="resource-header">
                Resource / PDF dari Admin
            </div>
            <div class="resource-subtitle">
                Resource yang tampil mengikuti mapel yang dipilih.
            </div>

            <button type="button" id="close-resource-btn" class="mobile-only resource-close-btn" style="align-self:flex-end; margin-bottom:4px;">
                Tutup
            </button>

            <input id="resource-search" class="resource-search" placeholder="Cari judul / mapel...">

            <div id="resource-list" class="resource-list">
                @forelse ($resources as $resource)
                    @php
                        $searchText = strtolower(
                            $resource->title . ' ' .
                            $resource->subjects->pluck('name')->implode(' ')
                        );
                        $subjectIds = $resource->subjects->pluck('id')->implode(',');

                        $fileUrl = null;
                        if (!empty($resource->url)) {
                            $fileUrl = $resource->url;
                        } else {
                            $filePath = null;

                            if (!empty($resource->path)) {
                                $filePath = $resource->path;
                            } elseif (!empty($resource->file_path)) {
                                $filePath = $resource->file_path;
                            } elseif (!empty($resource->filepath)) {
                                $filePath = $resource->filepath;
                            } elseif (!empty($resource->file)) {
                                $filePath = $resource->file;
                            }

                            if (!empty($filePath)) {
                                $fileUrl = \Illuminate\Support\Facades\Storage::url($filePath);
                            }
                        }
                    @endphp

                    <label class="resource-item"
                           data-search="{{ $searchText }}"
                           data-subjects="{{ $subjectIds }}">
                        <input type="checkbox" class="resource-checkbox" value="{{ $resource->id }}">
                        <div>
                            <div class="resource-title">{{ $resource->title }}</div>
                            <div class="resource-meta">
                                @if ($resource->subjects->count())
                                    {{ $resource->subjects->pluck('name')->implode(', ') }}
                                @else
                                    (Belum ada mapel)
                                @endif
                            </div>
                            @if (!empty($fileUrl))
                                <a href="{{ $fileUrl }}" target="_blank"
                                   class="resource-open"
                                   onclick="event.stopPropagation();">
                                    🔗 Buka file
                                </a>
                            @endif
                        </div>
                    </label>
                @empty
                    <div class="resource-empty">
                        Belum ada resource dari admin. Tambahkan dari menu AMIRA Admin &gt; Resource File.
                    </div>
                @endforelse
            </div>
        </aside>
    </div>

    <form id="chat-form" class="chat-footer" enctype="multipart/form-data">
        <div class="footer-left">
            <textarea id="message" placeholder="Tulis pertanyaanmu di sini..."></textarea>

            <div class="footer-tools">
                <label class="file-label">
                    📄 Dokumen
                    <input type="file" id="document" name="document" accept=".pdf,.doc,.docx,.ppt,.pptx,.txt">
                </label>
                <label class="file-label">
                    🖼 Gambar
                    <input type="file" id="image" name="image" accept="image/*">
                </label>

                <span id="file-info" class="file-info"></span>
                <span id="status" class="status"></span>
            </div>
        </div>

        <div class="desktop-only">
            <button type="submit" id="send-btn">Kirim</button>
        </div>

        {{-- Tombol resource & kirim khusus HP --}}
        <div class="mobile-only" style="display:flex; align-items:center; gap:8px;">
            <button type="button" id="mobile-resource-btn" class="mobile-resource-btn">
                📚
            </button>
            <button type="submit" id="send-btn-mobile" style="border-radius:9999px; padding:8px 16px; font-size:14px; border:none; background:#22c55e; color:#020617; font-weight:600;">
                Kirim
            </button>
        </div>
    </form>
</div>

<script>
    const providersConfig   = @json($providersConfig);
    const defaultProvider   = '{{ $defaultProvider }}';
    const defaultModel      = '{{ $defaultModel }}';

    const form              = document.getElementById('chat-form');
    const messageInput      = document.getElementById('message');
    const chatBody          = document.getElementById('chat-body');
    const sendBtnDesktop    = document.getElementById('send-btn');
    const sendBtnMobile     = document.getElementById('send-btn-mobile');
    const statusEl          = document.getElementById('status');
    const providerSelect    = document.getElementById('provider');
    const modelSelect       = document.getElementById('model');
    const subjectSelect     = document.getElementById('subject');
    const clearChatBtn      = document.getElementById('clear-chat-btn');
    const documentInput     = document.getElementById('document');
    const imageInput        = document.getElementById('image');
    const resourceSearch    = document.getElementById('resource-search');
    const resourceList      = document.getElementById('resource-list');
    const fileInfo          = document.getElementById('file-info');
    const resourcePanel     = document.getElementById('resource-panel');
    const openSettingsBtn   = document.getElementById('open-settings-btn');
    const closeSettingsBtn  = document.getElementById('close-settings-btn');
    const openResourceBtn   = document.getElementById('mobile-resource-btn');
    const closeResourceBtn  = document.getElementById('close-resource-btn');
    const headerControls    = document.getElementById('header-controls');
    const screenOverlay     = document.getElementById('screen-overlay');

    const selectedResourceIds = new Set();

    const SUBJECT_STORAGE_KEY    = 'amira_selected_subject_id';
    const HISTORY_STORAGE_PREFIX = 'amira_chat_history_subject_';

    let chatHistory = [];

    // Maksimal jumlah pesan (user + AMIRA) yang dikirim ke model
    const MAX_MESSAGES_FOR_MODEL = 40;

    function isMobile() {
        return window.innerWidth <= 640;
    }

    function getCurrentSubjectId() {
        if (!subjectSelect) return 'none';
        return subjectSelect.value || 'none';
    }

    function saveHistory() {
        const subjectId = getCurrentSubjectId();
        const key = HISTORY_STORAGE_PREFIX + subjectId;
        try {
            localStorage.setItem(key, JSON.stringify(chatHistory));
        } catch (e) {
            console.warn('Tidak bisa menyimpan history ke localStorage', e);
        }
    }

    function loadHistoryForSubject(subjectId) {
        const key = HISTORY_STORAGE_PREFIX + subjectId;
        let stored = null;
        try {
            stored = localStorage.getItem(key);
        } catch (e) {
            console.warn('Tidak bisa membaca history dari localStorage', e);
        }

        chatHistory = stored ? JSON.parse(stored) : [];

        chatBody.innerHTML = '';
        if (chatHistory.length === 0) {
            const div = document.createElement('div');
            div.classList.add('message', 'amira');
            div.textContent = 'Belum ada percakapan untuk mapel ini. Silakan mulai bertanya kepada AMIRA 😊';
            chatBody.appendChild(div);
        } else {
            chatHistory.forEach(msg => appendMessage(msg.text, msg.role, false));
        }
    }

    function appendMessage(text, role, save = true) {
        const div = document.createElement('div');
        div.classList.add('message', role);
        div.textContent = text;
        chatBody.appendChild(div);
        chatBody.scrollTop = chatBody.scrollHeight;

        if (save) {
            chatHistory.push({ role, text });
            saveHistory();
        }
    }

    function refreshModelOptions() {
        if (!providerSelect || !modelSelect) return;
        const providerKey = providerSelect.value;
        const providerCfg = providersConfig[providerKey] || {models: {}};
        const models      = providerCfg.models || {};

        modelSelect.innerHTML = '';

        Object.entries(models).forEach(([value, label]) => {
            const opt = document.createElement('option');
            opt.value = value;
            opt.textContent = label;
            modelSelect.appendChild(opt);
        });

        if (providerKey === defaultProvider && defaultModel && models[defaultModel]) {
            modelSelect.value = defaultModel;
        }
    }

    function updateFileInfo() {
        if (!fileInfo) return;

        const parts = [];

        if (documentInput && documentInput.files[0]) {
            parts.push(`Dokumen: ${documentInput.files[0].name}`);
        }
        if (imageInput && imageInput.files[0]) {
            parts.push(`Gambar: ${imageInput.files[0].name}`);
        }

        fileInfo.textContent = parts.length ? `Lampiran: ${parts.join(' | ')}` : '';
    }

    function filterResources() {
        if (!resourceList) return;
        const subjectId = getCurrentSubjectId();
        const term = (resourceSearch?.value || '').toLowerCase();

        resourceList.querySelectorAll('.resource-item').forEach(item => {
            const haystack   = (item.dataset.search || '').toLowerCase();
            const subjects   = (item.dataset.subjects || '').split(',').filter(Boolean);
            const matchMapel  = subjects.includes(subjectId);
            const matchSearch = haystack.includes(term);

            if (matchMapel && matchSearch) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    }

    function clearCurrentSubjectHistory() {
        const subjectId = getCurrentSubjectId();
        const key = HISTORY_STORAGE_PREFIX + subjectId;

        try {
            localStorage.removeItem(key);
        } catch (e) {
            console.warn('Tidak bisa menghapus history dari localStorage', e);
        }

        chatHistory = [];
        chatBody.innerHTML = '';

        const div = document.createElement('div');
        div.classList.add('message', 'amira');
        div.textContent = 'Riwayat chat untuk mapel ini sudah dihapus. Silakan mulai percakapan baru dengan AMIRA 😊';
        chatBody.appendChild(div);
    }

    function openOverlay() {
        if (screenOverlay) {
            screenOverlay.classList.add('show');
        }
    }

    function closeOverlay() {
        if (screenOverlay) {
            screenOverlay.classList.remove('show');
        }
        if (headerControls) headerControls.classList.remove('open');
        if (resourcePanel)  resourcePanel.classList.remove('open');
    }

    // Susun teks percakapan beberapa pesan terakhir untuk dikirim ke backend
    function buildConversationTextFromHistory() {
        if (!Array.isArray(chatHistory) || chatHistory.length === 0) return '';

        const recent = chatHistory.slice(-MAX_MESSAGES_FOR_MODEL);

        const lines = recent.map((m, idx) => {
            const speaker = m.role === 'user' ? 'Siswa' : 'AMIRA';
            return `${idx + 1}. ${speaker}: ${m.text}`;
        });

        lines.push(
            '\nInstruksi untuk AMIRA:' +
            '\n- Percakapan di atas adalah sesi belajar untuk SATU mata pelajaran.' +
            '\n- Gunakan semua konteks tersebut, tetapi fokuskan jawaban pada pesan Siswa yang paling terakhir.' +
            '\n- Jawablah sebagai mentor belajar, bukan mesin pemberi kunci jawaban.'
        );

        return lines.join('\n');
    }

    if (providerSelect) {
        providerSelect.addEventListener('change', refreshModelOptions);
    }

    document.addEventListener('DOMContentLoaded', () => {
        refreshModelOptions();

        if (subjectSelect) {
            const savedSubjectId = localStorage.getItem(SUBJECT_STORAGE_KEY);
            if (savedSubjectId && subjectSelect.querySelector(`option[value="${savedSubjectId}"]`)) {
                subjectSelect.value = savedSubjectId;
            }
        }

        const initialSubjectId = getCurrentSubjectId();
        loadHistoryForSubject(initialSubjectId);
        filterResources();

        document.querySelectorAll('.resource-checkbox').forEach(cb => {
            cb.addEventListener('change', () => {
                if (cb.checked) {
                    selectedResourceIds.add(cb.value);
                } else {
                    selectedResourceIds.delete(cb.value);
                }
            });
        });

        if (documentInput) {
            documentInput.addEventListener('change', updateFileInfo);
        }
        if (imageInput) {
            imageInput.addEventListener('change', updateFileInfo);
        }

        if (subjectSelect) {
            subjectSelect.addEventListener('change', () => {
                const subjectId = getCurrentSubjectId();
                try {
                    localStorage.setItem(SUBJECT_STORAGE_KEY, subjectId);
                } catch (e) {
                    console.warn('Tidak bisa menyimpan subject ke localStorage', e);
                }

                selectedResourceIds.clear();
                document.querySelectorAll('.resource-checkbox').forEach(cb => {
                    cb.checked = false;
                });

                loadHistoryForSubject(subjectId);
                filterResources();
            });
        }

        if (clearChatBtn) {
            clearChatBtn.addEventListener('click', () => {
                const subjectName = subjectSelect
                    ? (subjectSelect.options[subjectSelect.selectedIndex]?.text || 'mapel ini')
                    : 'mapel ini';

                const ok = confirm(`Yakin ingin menghapus semua chat untuk ${subjectName}?`);
                if (ok) {
                    clearCurrentSubjectHistory();
                }
            });
        }

        if (messageInput) {
            messageInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    form.requestSubmit();
                }
            });
        }

        // Tombol pengaturan (HP)
        if (openSettingsBtn && headerControls && screenOverlay) {
            openSettingsBtn.addEventListener('click', () => {
                if (!isMobile()) return;
                headerControls.classList.add('open');
                openOverlay();
            });
        }

        if (closeSettingsBtn) {
            closeSettingsBtn.addEventListener('click', () => {
                headerControls.classList.remove('open');
                if (!resourcePanel || !resourcePanel.classList.contains('open')) {
                    closeOverlay();
                }
            });
        }

        // Tombol resource (HP)
        if (openResourceBtn && resourcePanel && screenOverlay) {
            openResourceBtn.addEventListener('click', () => {
                if (!isMobile()) return;
                resourcePanel.classList.add('open');
                openOverlay();
            });
        }

        if (closeResourceBtn && resourcePanel) {
            closeResourceBtn.addEventListener('click', () => {
                resourcePanel.classList.remove('open');
                if (!headerControls || !headerControls.classList.contains('open')) {
                    closeOverlay();
                }
            });
        }

        // klik overlay = tutup semua panel
        if (screenOverlay) {
            screenOverlay.addEventListener('click', closeOverlay);
        }
    });

    if (resourceSearch) {
        resourceSearch.addEventListener('input', () => {
            filterResources();
        });
    }

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const rawText = messageInput.value.trim();
        const docFile = documentInput?.files[0];
        const imgFile = imageInput?.files[0];

        if (!rawText && !docFile && !imgFile) {
            return;
        }

        // tampil di UI (hanya pesan terbaru)
        let displayText = rawText;
        if (!displayText) {
            if (imgFile) displayText = '[Gambar dikirim untuk dianalisis]';
            else if (docFile) displayText = '[Dokumen dikirim sebagai konteks]';
        }
        appendMessage(displayText, 'user');

        // susun riwayat terbatas MAX_MESSAGES_FOR_MODEL
        const conversationText = buildConversationTextFromHistory();

        if (sendBtnDesktop) sendBtnDesktop.disabled = true;
        if (sendBtnMobile)  sendBtnMobile.disabled  = true;
        if (statusEl) statusEl.textContent = 'AMIRA sedang berpikir...';

        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const formData  = new FormData();

        if (conversationText) {
            formData.append('message', conversationText);
        } else if (rawText) {
            formData.append('message', rawText);
        }

        if (providerSelect) formData.append('provider', providerSelect.value || '');
        if (modelSelect)    formData.append('model', modelSelect.value || '');

        if (docFile) {
            formData.append('document', docFile);
        }
        if (imgFile) {
            formData.append('image', imgFile);
        }

        selectedResourceIds.forEach(id => {
            formData.append('resources[]', id);
        });

        if (subjectSelect) {
            formData.append('subject_id', subjectSelect.value || '');
        }

        try {
            const res = await fetch('{{ route('amira.chat.send') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: formData,
            });

            let data = null;
            try {
                data = await res.json();
            } catch (parseErr) {
                console.error('JSON parse error', parseErr);
            }

            if (!res.ok) {
                if (res.status === 422 && data && data.errors) {
                    const firstError = Object.values(data.errors)[0][0] ?? 'Data tidak valid.';
                    appendMessage(firstError, 'amira');
                } else if (data && (data.error || data.message)) {
                    appendMessage(data.error || data.message, 'amira');
                } else {
                    appendMessage('Maaf, terjadi kesalahan di server.', 'amira');
                }
                return;
            }

            if (data && data.success) {
                appendMessage(data.reply, 'amira');
            } else if (data) {
                appendMessage(data.error || 'Maaf, terjadi kesalahan di server.', 'amira');
            } else {
                appendMessage('Maaf, server mengirim respons yang tidak dapat dibaca.', 'amira');
            }

        } catch (err) {
            console.error(err);
            appendMessage('Maaf, jaringan bermasalah atau server tidak merespon.', 'amira');
        } finally {
            if (sendBtnDesktop) sendBtnDesktop.disabled = false;
            if (sendBtnMobile)  sendBtnMobile.disabled  = false;
            if (statusEl) statusEl.textContent = '';
            messageInput.value = '';
            updateFileInfo();
        }
    });
</script>
</body>
</html>
