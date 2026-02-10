// ====== Globals / constants ==================================================
const BASE_URL = window.BASE_URL;
const REST_API_BASE_ROUTE = window.REST_API_BASE_ROUTE;

const maxUploadSize = parseInt(
  (document.querySelector("meta[property='site:max_upload_size']")?.getAttribute("content")) || "2097152",
  10
); // fallback: 2MB
window.maxUploadSize = maxUploadSize;

// ====== Utils base ===========================================================
let __autoIdSeq = 0;
function ensureId(el, prefix = 'file') {
  if (!el.id) el.id = `${prefix}-${Date.now().toString(36)}-${(++__autoIdSeq).toString(36)}`;
  return el.id;
}

function ensureChild(parent, selector, tag, className) {
  let el = parent.querySelector(selector);
  if (!el) {
    el = document.createElement(tag);
    if (className) el.className = className;
    parent.appendChild(el);
  }
  return el;
}

// ====== Helpers ==============================================================

/**
 * Returns true if file type is allowed by input's accept attr (mime, mime/* or .ext)
 */
function isValidFileType(file, inputEl) {
  const acceptAttr = (inputEl?.getAttribute('accept') || '')
    .split(',')
    .map(s => s.trim().toLowerCase())
    .filter(Boolean);

  if (acceptAttr.length === 0) return true;

  const fileType = (file.type || '').toLowerCase();  // e.g. image/png
  const fileName = (file.name || '').toLowerCase();  // e.g. photo.PNG

  return acceptAttr.some(acc => {
    if (!acc) return false;
    if (acc.startsWith('.')) return fileName.endsWith(acc);
    if (acc.endsWith('/*'))  return fileType.startsWith(acc.slice(0, -1));
    return fileType === acc;
  });
}

/**
 * Removes a file from input[type=file] by name
 */
function removeFileFromInput(inputEl, fileName) {
  if (!inputEl?.files) return;
  const dt = new DataTransfer();
  for (const f of inputEl.files) if (f.name !== fileName) dt.items.add(f);
  inputEl.files = dt.files;
}

function removeFromNativeInputIfPresent(inputEl, name) {
  if (!(inputEl instanceof HTMLInputElement) || !inputEl.files || !name) return;
  const exists = Array.from(inputEl.files).some(f => f.name === name);
  if (exists) removeFileFromInput(inputEl, name);
}

/**
 * Ensures the basic structure inside .files:
 * - .previewer
 * - hidden input[input-files]
 */
function ensureFilesScaffold(filesWrap, fileInputEl) {
  if (!filesWrap) return null;

  // previewer
  const previewer = ensureChild(filesWrap, '.previewer', 'div', 'previewer');

  // hidden input[input-files]
  let hidden = filesWrap.querySelector('input[input-files]');
  if (!hidden && fileInputEl) {
    hidden = document.createElement('input');
    hidden.type = 'hidden';
    hidden.setAttribute('input-files', '');
    hidden.name = fileInputEl.getAttribute('name') || '';
    hidden.value = '[]';
    if (fileInputEl.hasAttribute('field')) {
      hidden.setAttribute('field', fileInputEl.getAttribute('field'));
    }
    filesWrap.prepend(hidden);
  }

  return previewer;
}

/**
 * Returns or creates the input[input-files] used as JSON holder
 */
function ensureInputFilesHidden(fileInputEl) {
  const filesWrap = fileInputEl.closest('.files');
  if (!filesWrap) return null;
  ensureFilesScaffold(filesWrap, fileInputEl);
  return filesWrap.querySelector('input[input-files]');
}

/**
 * Appends a filename to the JSON stored in hidden input[input-files]
 */
function appendFileNameToJSON(fileInputEl, filename) {
  if (!filename) return;
  const hidden = ensureInputFilesHidden(fileInputEl);
  if (!hidden) return;
  let arr = [];
  try { arr = JSON.parse(hidden.value || '[]'); } catch {}
  arr.push(filename);
  hidden.value = JSON.stringify(arr);

  // Trigger revalidation for this upload field
  hidden.dispatchEvent(new Event('input', { bubbles: true }));
}

function updateInputFilesJSON(anyRefEl, fileName) {
  if (!fileName) return;
  const filesWrap = anyRefEl.closest('.files');
  if (!filesWrap) return;
  const hidden = filesWrap.querySelector('input[input-files]');
  if (!hidden) return;
  let arr = [];
  try { arr = JSON.parse(hidden.value || '[]'); } catch {}
  const idx = arr.indexOf(fileName);
  if (idx >= 0) {
    arr.splice(idx, 1);
    hidden.value = JSON.stringify(arr);
  }

  // Trigger revalidation after remove
  hidden.dispatchEvent(new Event('input', { bubbles: true }));
}


/**
 * Binds the real server filename to the remove button inside the container.
 * Works for both legacy attributes and the new unified structure.
 */
function bindRemoveToUrl(containerEl, fileKey) {
  if (!containerEl) return;
  const btn = containerEl.querySelector(
    'button[file-name], [delete-image-from-preview], [delete-video-from-preview], [delete-archive-from-preview]'
  );
  if (!btn) return;
  btn.setAttribute('file-name', fileKey);
}

/**
 * Detects input kind:
 *  - 'image'
 *  - 'video'
 *  - 'audio'
 *  - 'file' (generic archives)
 */
function getInputKind(inputEl) {
  const cls = inputEl.classList || [];
  const has = (a) =>
    inputEl.hasAttribute(a) ||
    cls.contains(a) ||
    cls.contains(a.replace('data-',''));

  if (has('data-upload-image') || has('upload-image')) return 'image';
  if (has('data-upload-video') || has('upload-video')) return 'video';
  if (has('data-upload-audio') || has('upload-audio')) return 'audio';
  if (has('data-upload-archive')  || has('upload-archive'))  return 'file';

  const accept = (inputEl.getAttribute('accept') || '').toLowerCase();
  if (accept.includes('image/')) return 'image';
  if (accept.includes('video/')) return 'video';
  if (accept.includes('audio/')) return 'audio';
  return 'file';
}

/**
 * Extracts a filename from ajax-upload-temp JSON response
 */
function extractUploadFilename(data) {
  let fname = null;
  if (data && data.file) {
    if (typeof data.file === 'string') fname = data.file;
    else if (typeof data.file === 'object' && data.file.file) fname = data.file.file;
    else if (Array.isArray(data.file)) {
      const s = data.file.find(v => typeof v === 'string');
      if (s) fname = s;
    }
  }
  if (!fname && data && data.url) {
    try {
      const path = new URL(data.url, window.location.origin).pathname;
      fname = path.split('/').pop();
    } catch {
      const p = (data.url || '').split('?')[0];
      fname = p.split('/').pop();
    }
  }
  return fname || '';
}

/**
 * Map raw extensions to presentation (icon, class, label).
 * Mirrors the PHP mapping for archives.
 */
function getArchiveConfigByExtension(extension) {
  const ext = (extension || '').toLowerCase();

  let typeKey = 'generic';
  if (['csv', 'xls', 'xlsx'].includes(ext)) {
    typeKey = 'sheet';
  } else if (['ppt', 'pptx', 'pps', 'ppsx'].includes(ext)) {
    typeKey = 'slide';
  } else if (['zip', 'rar', '7z'].includes(ext)) {
    typeKey = 'compact';
  } else if (ext === 'pdf') {
    typeKey = 'pdf';
  } else if (['doc', 'docx', 'odt', 'rtf'].includes(ext)) {
    typeKey = 'doc';
  }

  const map = {
    sheet: {
      icon: 'fas fa-table',
      class: 'sheet',
      type: 'Planilha',
    },
    slide: {
      icon: 'fas fa-photo-film',
      class: 'slide',
      type: 'Apresentação',
    },
    compact: {
      icon: 'fas fa-file-zipper',
      class: 'compact',
      type: 'Compactado',
    },
    pdf: {
      icon: 'fas fa-file-pdf',
      class: 'pdf',
      type: 'PDF',
    },
    doc: {
      icon: 'fas fa-file-word',
      class: 'doc',
      type: 'Documento',
    },
    generic: {
      icon: 'fas fa-file',
      class: 'generic',
      type: 'Arquivo',
    },
  };

  return map[typeKey] || map.generic;
}

/**
 * Applies archive presentation (icon, label, ext) inside a .archive block
 * using a given filename (server-side or local).
 */
function applyArchiveMetadata(archiveBlock, filename) {
  if (!archiveBlock || !filename) return;

  const ext = (filename.split('.').pop() || '').toLowerCase();
  const cfg = getArchiveConfigByExtension(ext);

  const fileTypeEl = archiveBlock.querySelector('.file-type');
  if (fileTypeEl) {
    fileTypeEl.classList.remove('sheet', 'slide', 'compact', 'pdf', 'doc', 'generic');
    fileTypeEl.classList.add(cfg.class);

    const iconHolder = fileTypeEl.querySelector('p');
    if (iconHolder) {
      iconHolder.innerHTML = `<i class="icon ${cfg.icon}"></i>`;
    }
  }

  const ps = archiveBlock.querySelectorAll('p');
  if (ps.length >= 2) {
    // second <p> is filename according to PHP structure
    ps[1].textContent = filename;
  }

  const small = archiveBlock.querySelector('small');
  if (small) {
    small.textContent = `${cfg.type} (.${ext || 'file'})`;
  }
}

// ====== Progress bar helpers ================================================

/**
 * Enable/disable submit buttons for a given form,
 * but only toggle those that we disabled due to uploads.
 */
function toggleFormSubmitButtons(form, disabled) {
  if (!form) return;

  const submits = form.querySelectorAll('button[type="submit"], input[type="submit"]');

  submits.forEach((btn) => {
    if (disabled) {
      // Do not override buttons already disabled for other reasons
      if (!btn.disabled) {
        btn.disabled = true;
        btn.dataset.uploadDisabled = '1'; // mark that upload logic disabled this
      }
    } else {
      // Only re-enable those we disabled
      if (btn.dataset.uploadDisabled === '1') {
        btn.disabled = false;
        delete btn.dataset.uploadDisabled;
      }
    }
  });
}

/**
 * Starts a fake progress bar animation while the upload is pending.
 * We do not know real bytes, so we animate until ~90%.
 * Also disables submit buttons of the parent form while any upload is running.
 */
// function startProgress(container, file, loaded, total)
// {
//   if (!container) return;

//   const progress = container.querySelector('.progress');
//   const bar = progress && progress.querySelector('.progress-bar');
//   if (!progress || !bar) return;

//   // lock submit
//   const form = container.closest('form');
//   if (form) {
//     form._uploadPendingCount = (form._uploadPendingCount || 0) + 1;
//     toggleFormSubmitButtons(form, true);
//   }

//   // cria meta (% + ETA) uma vez
//   let meta = container.querySelector('[data-upload-meta]');
//   if (!meta) {
//     meta = document.createElement('small');
//     meta.setAttribute('data-upload-meta', '1');
//     meta.className = 'd-block mt-1';
//     container.appendChild(meta);
//   }

//   progress.classList.remove('d-none');

//   // ---- PROGRESSO REAL (quando vier loaded/total) ----
//   if (typeof loaded === 'number' && typeof total === 'number' && total > 0)
//   {
//     // inicia relógio no primeiro progresso real
//     if (!container._t0) container._t0 = Date.now();

//     const pct = Math.min(100, Math.max(0, Math.round((loaded / total) * 100)));
//     bar.style.width = pct + '%';
//     bar.setAttribute('aria-valuenow', String(pct));

//     // ETA simples: baseado em velocidade média
//     const dt = (Date.now() - container._t0) / 1000; // s
//     const speed = dt > 0 ? loaded / dt : 0;         // bytes/s
//     const remaining = total - loaded;
//     const etaSec = speed > 0 ? Math.ceil(remaining / speed) : null;

//     // formata eta
//     let etaTxt = '';
//     if (etaSec != null) {
//       const m = Math.floor(etaSec / 60);
//       const s = String(etaSec % 60).padStart(2, '0');
//       etaTxt = m ? ` · ~${m}m ${s}s` : ` · ~${etaSec}s`;
//     }

//     meta.textContent = pct + '%' + etaTxt;
//     return;
//   }

//   // ---- FALLBACK FAKE (sem progresso real) ----
//   if (!container._progressTimer) {
//     let value = 10;
//     bar.style.width = value + '%';
//     meta.textContent = value + '%';

//     container._progressTimer = setInterval(() => {
//       value = Math.min(value + 10, 90);
//       bar.style.width = value + '%';
//       meta.textContent = value + '%';
//     }, 400);
//   }
// }

// /**
//  * Stops the fake progress animation and optionally finalizes the bar.
//  * Also re-enables submit buttons if no more uploads are running.
//  */
// function stopProgress(container, success)
// {
//   if (!container) return;

//   const progress = container.querySelector('.progress');
//   const bar = progress && progress.querySelector('.progress-bar');

//   // release submit
//   const form = container.closest('form');
//   if (form && typeof form._uploadPendingCount === 'number') {
//     form._uploadPendingCount = Math.max(0, form._uploadPendingCount - 1);
//     if (form._uploadPendingCount === 0) toggleFormSubmitButtons(form, false);
//   }

//   if (container._progressTimer) {
//     clearInterval(container._progressTimer);
//     container._progressTimer = null;
//   }
//   container._t0 = null;

//   const meta = container.querySelector('[data-upload-meta]');
//   if (meta) meta.remove();

//   if (!progress || !bar) return;

//   bar.style.width = success ? '100%' : '0%';
//   setTimeout(() => progress.classList.add('d-none'), 400);
// }

// ====== Previews (images / videos / archives) ===============================

/**
 * Creates a preview container for a file based on the template
 * .file-container.template from the current .previewer.
 *
 * This is used for images, videos and archives (all non-audio).
 */
function createPreviewContainerFromTemplate(input, file, previewer) {
  if (!input || !file || !previewer) return null;

  let kind = getInputKind(input); // 'image', 'video', 'audio', 'file'...
  let templateKind = null;

  // Se for "archives" (kind === 'file'), decide pelo MIME real
  if (kind === 'file') {
    templateKind = detectFileKindFromFile(file); // image|video|audio|archive
    if (templateKind === 'audio') templateKind = 'archive'; // áudio é outro fluxo
  } else {
    templateKind = kind; // image/video (inputs específicos)
  }

  // tenta template específico; senão cai no template padrão
  const template =
    previewer.querySelector(`.file-container.template[data-template-kind="${templateKind}"]`) ||
    previewer.querySelector('.file-container.template');

  const addBtn = previewer.querySelector('.add-file');
  if (!template) return null;

  const container = template.cloneNode(true);
  container.classList.remove('template');
  container.style.display = '';
  container.dataset.localName = file.name;

  // Remove button
  const removeBtn = container.querySelector('button.btn.btn-danger') || container.querySelector('button');
  if (removeBtn) {
    removeBtn.type = 'button';
    removeBtn.setAttribute('file-name', file.name);
    if (input.id) {
      removeBtn.setAttribute('input-id', input.id);
    }
    // Legacy attributes for backward-compat (not required anymore, but harmless)
    if (kind === 'image') {
      removeBtn.setAttribute('delete-image-from-preview', 'true');
    } else if (kind === 'video') {
      removeBtn.setAttribute('delete-video-from-preview', 'true');
    } else {
      removeBtn.setAttribute('delete-archive-from-preview', 'true');
    }
  }

  // Reset progress visual state
  const wrap = container.querySelector('.upload-progress');
  const progress = wrap?.querySelector('.progress');
  const bar = progress?.querySelector('.progress-bar');
  const meta = wrap?.querySelector('.upload-meta');

  if (wrap) wrap.classList.remove('d-none');
  if (progress) progress.classList.remove('d-none');
  if (bar) {
    bar.style.width = '1%';
    bar.setAttribute('aria-valuenow', '1');
  }
  if (meta) meta.innerHTML = 'Envio em 1% <br> Tempo: 0 s';

  const progressBar = progress?.querySelector('.progress-bar');
  if (progressBar) {
    progressBar.style.width = '1%';
  }

  // Type-specific visual wiring, based on template internals
  const archiveBlock = container.querySelector('.archive');
  const imgEl        = container.querySelector('img');
  const videoEl      = container.querySelector('video, source, [data-video], [data-src]');

  if (kind === 'image' && imgEl) {
    const reader = new FileReader();
    reader.onloadend = () => {
      imgEl.src = reader.result;
    };
    reader.readAsDataURL(file);
  }

  else if (kind === 'video' && videoEl) {
    const url = URL.createObjectURL(file);

    if (videoEl.tagName === 'VIDEO' || videoEl.tagName === 'SOURCE') {
      videoEl.src = url;
      videoEl.closest('video')?.load();
    } else {
      videoEl.setAttribute('data-video', url);
    }
  }

  else if (archiveBlock) {
    applyArchiveMetadata(archiveBlock, file.name);
  }


  if (archiveBlock) {
    // Archives: icon + filename + label based on extension (local name at first)
    applyArchiveMetadata(archiveBlock, file.name);
  }

  else if (imgEl)
  {
    // Images: use FileReader for preview
    const reader = new FileReader();
    reader.onloadend = () => {
      imgEl.src = reader.result;
    };
    reader.readAsDataURL(file);
  }

  else if (videoEl)
  {
    // Videos: use blob URL for preview
    const url = URL.createObjectURL(file);

    if (videoEl.tagName === 'VIDEO' || videoEl.tagName === 'SOURCE') {
      videoEl.src = url;
      if (videoEl.tagName === 'SOURCE') {
        const videoParent = videoEl.closest('video');
        if (videoParent) {
          videoParent.load();
        }
      }
    } else {
      if (videoEl.hasAttribute('data-video')) {
        videoEl.setAttribute('data-video', url);
      } else if (videoEl.hasAttribute('data-src')) {
        videoEl.setAttribute('data-src', url);
      }
    }
  }

  // Insert after add button (to keep "Add" always first), or at the end
  if (addBtn && addBtn.parentElement === previewer) {
  //   previewer.insertBefore(container, addBtn.nextSibling);
  // } else {
    previewer.appendChild(container);
  }

  // Hide add button if not multiple
  if (!input.hasAttribute('multiple') && addBtn) {
    addBtn.style.display = 'none';
  }

  return container;
}

function ensureUploadMeta(container) {
  let meta = container.querySelector('.upload-meta');
  if (!meta) {
    meta = document.createElement('small');
    meta.className = 'upload-meta d-block mt-1';
    meta.innerHTML = '<span class="upload-pct">0%</span> <span class="upload-eta"></span>';
    container.appendChild(meta);
  }
  return {
    bar: container.querySelector('.progress-bar'),
    pct: meta.querySelector('.upload-pct'),
    eta: meta.querySelector('.upload-eta'),
  };
}

function fmtTime(sec) {
  sec = Math.max(0, Math.round(sec || 0));
  const m = Math.floor(sec / 60), s = sec % 60;
  return m ? `${m}m ${String(s).padStart(2,'0')}s` : `${s}s`;
}

function setUploadUI(ui, pct, eta) {
  const p = Math.max(0, Math.min(100, Math.round(pct || 0)));
  if (ui.bar) {
    ui.bar.style.width = p + '%';
    ui.bar.setAttribute('aria-valuenow', String(p));
  }
  if (ui.pct) ui.pct.textContent = p + '%';
  if (ui.eta) ui.eta.textContent = (eta != null) ? `~${fmtTime(eta)}` : '';
}

/**
 * Handles the whole lifecycle on change for a non-audio input:
 *  - validations (type / size)
 *  - preview using template
 *  - upload to ajax-upload-temp
 *  - fill JSON in input[input-files]
 *  - update placeholders with server filename (archives)
 *  - progress bar animation
 */
async function handleFileInputChange(input)
{
  const kind = getInputKind(input);
  if (kind === 'audio') return;

  if (kind === 'image' && isProfileImageInput(input)) {
    await handleProfileImageChange(input);
    return;
  }

  const filesWrap = input.closest('.files');
  if (!filesWrap) return;

  const previewer = ensureFilesScaffold(filesWrap, input);
  if (!previewer) return;

  const addBtn = previewer.querySelector('.add-file');

  for (const file of input.files || [])
  {
    if (!isValidFileType(file, input)) { alert(`Tipo não permitido: ${file.name}`); if (!input.hasAttribute('multiple') && addBtn) addBtn.style.display = ''; continue; }
    if (file.size > maxUploadSize)      { alert(`Muito grande: ${file.name}`);     if (!input.hasAttribute('multiple') && addBtn) addBtn.style.display = ''; continue; }

    const container = createPreviewContainerFromTemplate(input, file, previewer);
    if (!container) continue;

    container.dataset.bound = '1';
    container.dataset.uploading = '1';
    container.style.opacity = '0.6';

    // UI progresso (REAL)
    const wrap = container.querySelector('.upload-progress');
    const progress = wrap?.querySelector('.progress');
    const bar = progress?.querySelector('.progress-bar');
    const meta = wrap?.querySelector('.upload-meta');

    if (progress && bar) {
      progress.classList.remove('d-none');
      bar.style.width = '1%';
      bar.setAttribute('aria-valuenow', '1');
    }

    if (wrap) wrap.classList.remove('d-none');
    if (progress) progress.classList.remove('d-none');
    if (bar) {
      bar.style.width = '1%';
      bar.setAttribute('aria-valuenow', '1');
    }
    if (meta) meta.innerHTML = 'Envio em 1% <br> Tempo: 0s';


    // trava submits enquanto envia
    const form = container.closest('form');
    if (form) {
      form._uploadPendingCount = (form._uploadPendingCount || 0) + 1;
      toggleFormSubmitButtons(form, true);
    }

    const field = input.getAttribute('field') || '';

    // cálculo de velocidade/ETA (REAL)
    let t0 = Date.now();
    let lastT = t0;
    let lastLoaded = 0;
    let speed = 0; // bytes/s (suavizado)

    try
    {
      const data = await uploadTempFile(file, {
        field,
        onXhr: (xhr) => {
          container._uploadXhr = xhr;
        },
        onProgress: (loaded, total) =>
        {
          const now = Date.now();
          const dt = Math.max(0.001, (now - lastT) / 1000);
          const dLoaded = Math.max(0, loaded - lastLoaded);

          const inst = dLoaded / dt;
          speed = speed ? (speed * 0.7 + inst * 0.3) : inst;

          lastT = now;
          lastLoaded = loaded;

          // upload terminou (bytes enviados), mas servidor ainda não respondeu → segura em 99%
          let pct = total ? Math.floor((loaded / total) * 100) : 0;

          // começa otimista
          if (loaded > 0 && pct < 1) pct = 1;

          pct = Math.max(0, Math.min(100, pct));

          // opcional: só sobe, nunca desce
          container._lastPct = container._lastPct || 1;
          pct = Math.max(pct, container._lastPct);
          container._lastPct = pct;

          if (bar) {
            bar.style.width = pct + '%';
            bar.setAttribute('aria-valuenow', String(pct));
          }

          if (pct >= 100) {
            container.dataset.processing = '1';
            if (meta) meta.textContent = 'Processando...';
            return;
          }

          if (speed > 0 && total > 0)
          {
            const remaining = Math.max(0, total - loaded);
            const eta = remaining / speed; // segundos
            if (meta)
            {
              let etaTxt = '0 s';
              if (speed > 0 && total > 0) {
                const etaSec = Math.ceil((total - loaded) / speed);
                etaTxt = (etaSec >= 60) ? `${Math.ceil(etaSec / 60)} min` : `${etaSec}s`;
              }
              if (meta) meta.innerHTML = `Envio em ${pct}% <br> Tempo: ${etaTxt}`;
            }
          } else {
            meta.textContent = `Envio em ${pct}%`;
          }
        }
      });

      const filename = extractUploadFilename(data);

      appendFileNameToJSON(input, filename);
      bindRemoveToUrl(container, filename);

      // === PROFILE IMAGE UI TOGGLE ===
      const box = input.closest('label.box.profile');
      if (box) {
        const tpl = box.querySelector('img.template');
        const img = box.querySelector('img:not(.template)');
        const del = box.querySelector('button.btn.btn-danger');

        if (tpl) tpl.classList.add('d-none');
        if (img) img.classList.remove('d-none');
        if (del) del.classList.remove('d-none');
      }

      const archiveBlock = container.querySelector('.archive');
      if (archiveBlock) applyArchiveMetadata(archiveBlock, filename);

      if (data.url) {
        const imgEl = container.querySelector('img');
        if (imgEl) imgEl.src = data.url;

        const source = container.querySelector('video source');
        const video = container.querySelector('video');
        if (source) { source.src = data.url; video?.load(); }
        else if (video) { video.src = data.url; video.load(); }
      }

      if (bar) {
        bar.style.width = '100%';
        bar.setAttribute('aria-valuenow', '100');
      }
      if (meta) meta.innerHTML = `Processando...`;

      setTimeout(() => {
        if (progress) progress.classList.add('d-none');
        if (wrap) wrap.classList.add('d-none');
      }, 300);

      container.style.opacity = '1';
    }
    catch (err)
    {
      const cancelled = container?._userCancelled || (err && String(err.message || err).includes('abort'));

      if (cancelled) {
        // cancelado pelo usuário: só limpa e sai sem alert
        removeFileFromInput(input, file.name);
        container?.remove();
        return;
      }

      console.error(err);
      if (meta) meta.textContent = 'Falha no upload ❌';

      container.remove();
      removeFileFromInput(input, file.name);
      alert(`Falha ao enviar: ${file.name}`);
    }
    finally
    {
      container.dataset.uploading = '0';
      container._uploadXhr = null;
      container._lastPct = null;

      if (form && typeof form._uploadPendingCount === 'number') {
        form._uploadPendingCount = Math.max(0, form._uploadPendingCount - 1);
        if (form._uploadPendingCount === 0) toggleFormSubmitButtons(form, false);
      }
    }
  }
}

function isProfileImageInput(input) {
  return !!input?.closest('label.box.profile');
}

function setProfileUI(box, { src, hasPhoto }) {
  const imgTpl = box.querySelector('img.template');
  const imgs = box.querySelectorAll('img:not(.template)');
  const imgReal = imgs[0] || null;
  const btn = box.querySelector('button.btn.btn-danger');

  if (imgReal && src) imgReal.src = src;

  if (imgTpl) imgTpl.classList.toggle('d-none', !!hasPhoto);
  if (imgReal) imgReal.classList.toggle('d-none', !hasPhoto);
  if (btn) btn.classList.toggle('d-none', !hasPhoto);
}

async function handleProfileImageChange(input) {
  const filesWrap = input.closest('.files');
  if (!filesWrap) return;

  const hidden = ensureInputFilesHidden(input);
  const box = input.closest('label.box.profile');
  if (!box) return;

  const file = (input.files && input.files[0]) ? input.files[0] : null;
  if (!file) return;

  if (!isValidFileType(file, input)) { alert(`Tipo não permitido: ${file.name}`); return; }
  if (file.size > maxUploadSize)      { alert(`Muito grande: ${file.name}`); return; }

  // preview otimista local
  const reader = new FileReader();
  reader.onloadend = () => setProfileUI(box, { src: reader.result, hasPhoto: true });
  reader.readAsDataURL(file);

  // ==== PROGRESS UI (profile) ==========================================
  const wrap = box.querySelector('.upload-progress');
  const progress = wrap?.querySelector('.progress');
  const bar = progress?.querySelector('.progress-bar');
  const meta = wrap?.querySelector('.upload-meta');

  // mostra o progresso (corrige d-noe/d-none)
  if (wrap) {
    wrap.classList.remove('d-none');
    wrap.classList.remove('d-noe'); // no seu HTML tá com typo
  }
  if (progress) progress.classList.remove('d-none');
  if (bar) {
    bar.style.width = '1%';
    bar.setAttribute('aria-valuenow', '1');
  }
  if (meta) meta.innerHTML = 'Envio em 1% <br> Tempo: 0s';

  // trava submits
  const form = input.closest('form');
  if (form) {
    form._uploadPendingCount = (form._uploadPendingCount || 0) + 1;
    toggleFormSubmitButtons(form, true);
  }

  // ETA real (igual você fez no outro fluxo)
  let lastT = Date.now();
  let lastLoaded = 0;
  let speed = 0;

  try
  {
    const field = input.getAttribute('field') || '';

    const data = await uploadTempFile(file, {
      field,
      onProgress: (loaded, total) =>
      {
        const now = Date.now();
        const dt = Math.max(0.001, (now - lastT) / 1000);
        const dLoaded = Math.max(0, loaded - lastLoaded);

        const inst = dLoaded / dt;
        speed = speed ? (speed * 0.7 + inst * 0.3) : inst;

        lastT = now;
        lastLoaded = loaded;

        let pct = total ? Math.floor((loaded / total) * 100) : 0;
        if (loaded > 0 && pct < 1) pct = 1;
        pct = Math.max(0, Math.min(100, pct));

        // só sobe
        box._lastPct = box._lastPct || 1;
        pct = Math.max(pct, box._lastPct);
        box._lastPct = pct;

        if (bar) {
          bar.style.width = pct + '%';
          bar.setAttribute('aria-valuenow', String(pct));
        }

        if (pct >= 100) {
          if (meta) meta.textContent = 'Processando...';
          return;
        }

        if (meta) {
          let etaTxt = '0s';
          if (speed > 0 && total > 0) {
            const etaSec = Math.ceil((total - loaded) / speed);
            etaTxt = (etaSec >= 60) ? `${Math.ceil(etaSec / 60)} min` : `${etaSec}s`;
          }
          meta.innerHTML = `Envio em ${pct}% <br> Tempo: ${etaTxt}`;
        }
      }
    });

    const filename = extractUploadFilename(data);

    // profile nunca é multiple -> substitui
    if (hidden) {
      hidden.value = JSON.stringify(filename ? [filename] : []);
      hidden.dispatchEvent(new Event('input', { bubbles: true }));
    }

    // bind remove
    const btn = box.querySelector('button.btn.btn-danger');
    if (btn) btn.setAttribute('file-name', filename);

    // troca para url final
    if (data.url) setProfileUI(box, { src: data.url, hasPhoto: true });

    // finaliza barra
    if (bar) {
      bar.style.width = '100%';
      bar.setAttribute('aria-valuenow', '100');
    }
    if (meta) meta.textContent = 'Processando...';

    setTimeout(() => {
      if (progress) progress.classList.add('d-none');
      if (wrap) wrap.classList.add('d-none');
    }, 300);
  }

  catch (e)
  {
    console.error(e);
    alert(`Falha ao enviar: ${file.name}`);
    setProfileUI(box, { src: null, hasPhoto: false });
    if (hidden) hidden.value = '[]';

    if (meta) meta.textContent = 'Falha no upload ❌';
    setTimeout(() => {
      if (progress) progress.classList.add('d-none');
      if (wrap) wrap.classList.add('d-none');
    }, 800);
  }

  finally
  {
    box._lastPct = null;

    if (form && typeof form._uploadPendingCount === 'number') {
      form._uploadPendingCount = Math.max(0, form._uploadPendingCount - 1);
      if (form._uploadPendingCount === 0) toggleFormSubmitButtons(form, false);
    }
  }
}



function detectFileKindFromFile(file) {
  const t = (file?.type || '').toLowerCase();
  if (t.startsWith('image/')) return 'image';
  if (t.startsWith('video/')) return 'video';
  if (t.startsWith('audio/')) return 'audio';
  return 'archive';
}



// Legacy wrappers (kept for backward compatibility, now use unified handler)
function preview_images(id)  { const el = document.getElementById(id); if (el) handleFileInputChange(el); }
function preview_videos(id)  { const el = document.getElementById(id); if (el) handleFileInputChange(el); }
function preview_files(id)   { const el = document.getElementById(id); if (el) handleFileInputChange(el); }

// ====== Upload (fetch) =======================================================
function uploadTempFile(file, opts = {})
{
  const field = opts.field || '';
  const onProgress = typeof opts.onProgress === 'function' ? opts.onProgress : null;

  const onXhr = typeof opts.onXhr === 'function' ? opts.onXhr : null;

  return new Promise((resolve, reject) =>
  {
    const body = new FormData();
    body.append('file', file);
    if (field) body.append('field', field);

    const xhr = new XMLHttpRequest();
    xhr.open('POST', `${BASE_URL}/${REST_API_BASE_ROUTE}/ajax-upload-temp`);

    if (onXhr) onXhr(xhr);

    // xhr.timeout = 60000;
    // xhr.ontimeout = () => reject(new Error('timeout'));
    xhr.onabort   = () => reject(new Error('abort'));

    if (onProgress) {
      xhr.upload.onprogress = (e) => {
        if (e.lengthComputable) onProgress(e.loaded, e.total);
      };
    }

    xhr.onload = () => {
      try {
        const json = JSON.parse(xhr.responseText || '{}');
        if (xhr.status >= 200 && xhr.status < 300) resolve(json);
        else reject(json);
      } catch (e) {
        reject(e);
      }
    };

    xhr.onerror = reject;
    xhr.send(body);
  });
}



async function uploadTempBlob(blob, { field, filename, mime }) {
  const form = new FormData();
  const file = new File([blob], filename || `audio-${Date.now()}.webm`, { type: mime || blob.type || 'audio/webm' });
  form.append('file', file);
  form.append('field', field || '');
  const endpoint = `${BASE_URL}/${REST_API_BASE_ROUTE}/ajax-upload-temp`;
  const res = await fetch(endpoint, { method: 'POST', body: form });
  if (!res.ok) throw new Error('Falha na requisição');
  const data = await res.json();
  if (!data || data.code !== 'success' || !data.url) throw new Error(data?.msg || 'Upload não aceito pelo servidor');
  return data;
}

// ====== Delegated events (dinâmicos + estáticos) =============================

// Button .add-file (template trigger → open native input)
document.addEventListener('click', (e) => {
  const btn = e.target.closest('.add-file');
  if (!btn) return;

  const filesWrap = btn.closest('.files');
  const input = filesWrap?.querySelector('input[type="file"]');
  if (!input) return;

  // If <label for="..."> pointing to input, let browser handle it
  if (btn.tagName === 'LABEL') {
    const forId = btn.getAttribute('for');
    if (forId && input.id && forId === input.id) {
      return; // do not call input.click()
    }
  }

  // If input is inside the button/label, browser will trigger it
  if (btn.contains(input)) {
    return;
  }

  e.preventDefault();
  if (input._opening) return;           // avoid double opening
  input._opening = true;
  setTimeout(() => { input._opening = false; }, 0);
  input.click();
});

// Unified remove for images, videos and archives
document.addEventListener('click', (e) => {
  const btn = e.target.closest(
    '[delete-image-from-preview], [delete-video-from-preview], [delete-archive-from-preview], .file-container .btn.btn-danger[file-name]'
  );
  if (!btn) return;
  e.preventDefault();

  const filesWrap = btn.closest('.files');
  const container = btn.closest('.file-container') || btn.closest('label.box.profile');

  if (container?.dataset?.uploading === '1' && container._uploadXhr)
  {
    try {
      container._userCancelled = true;
      container._uploadXhr.abort();
    } catch {}
  }

  const input =
    document.getElementById(btn.getAttribute('input-id') || '') ||
    filesWrap?.querySelector('input[type="file"]');

  const previewer = filesWrap?.querySelector('.previewer');
  const fileName  = btn.getAttribute('file-name') || '';


  /**
   * Delete profile image.
   */
  const profileBox = btn.closest('label.box.profile');
  if (profileBox)
  {
    updateInputFilesJSON(btn, fileName);

    const inputEl =
      document.getElementById(btn.getAttribute('input-id') || '') ||
      profileBox.querySelector('input[type="file"]') ||
      filesWrap?.querySelector('input[type="file"]');

    if (inputEl) inputEl.value = '';

    const tpl = profileBox.querySelector('img.template');
    const img = profileBox.querySelector('img:not(.template)');

    if (tpl) tpl.classList.remove('d-none');
    if (img) img.classList.add('d-none');

    btn.classList.add('d-none');
    btn.setAttribute('file-name', '');

    return;
  }

  // Update JSON and native input
  updateInputFilesJSON(btn, fileName);
  if (input) removeFromNativeInputIfPresent(input, fileName);

  // Remove the preview container
  container?.remove();

  if (container && container.matches('label.box.profile')) {
    setProfileUI(container, { src: null, hasPhoto: false });
  }

  // If not multiple, show "Add" again if no other previews exist
  if (input && !input.hasAttribute('multiple') && previewer) {
    const addBtn = previewer.querySelector('.add-file');
    const hasPreview = !!previewer.querySelector('.file-container:not(.template):not(.add-file)');
    if (addBtn && !hasPreview) {
      addBtn.style.removeProperty('display');
    }
  }
});

// Drag & drop support (previewer or .files) — delegated
['dragenter','dragover','dragleave','drop'].forEach(ev => {
  document.addEventListener(ev, (e) => {
    const targetWrap = e.target.closest('.previewer, .files');
    if (!targetWrap) return;
    const filesWrap  = targetWrap.classList.contains('files') ? targetWrap : targetWrap.closest('.files');
    if (!filesWrap) return;

    e.preventDefault();
    const previewer = ensureFilesScaffold(filesWrap, filesWrap.querySelector('input[type="file"]'));
    const addBtn = previewer?.querySelector('.add-file');

    if (ev === 'dragenter') {
      if (addBtn) addBtn.style.opacity = '0.5';
    }
    if (ev === 'dragleave' || ev === 'drop') {
      if (addBtn) addBtn.style.opacity = '1';
    }

    if (ev === 'drop') {
      const input = filesWrap.querySelector('input[type="file"]');
      if (!input) return;
      const files = e.dataTransfer?.files || [];
      if (!files.length) return;

      const dt = new DataTransfer();
      for (const f of files) {
        if (!isValidFileType(f, input)) {
          alert(`Extensão de arquivo não permitido: ${f.name}`);
        } else if (f.size > maxUploadSize) {
          alert(`Arquivo muito grande: ${f.name} (${(f.size/1024/1024).toFixed(2)}MB). Máx: ${(maxUploadSize/1024/1024).toFixed(2)}MB`);
        } else {
          dt.items.add(f);
        }
      }
      input.files = dt.files;
      input.dispatchEvent(new Event('change', { bubbles: true }));
    }
  });
});

// Change handler (unified for image / video / archives, skipping audio)
document.addEventListener('change', (e) => {
  const input = e.target;
  if (!(input instanceof HTMLInputElement) || input.type !== 'file') return;

  const inFiles = !!input.closest('.files');
  const hasDataAttr =
    input.hasAttribute('data-upload-image') ||
    input.hasAttribute('data-upload-archive') ||
    input.hasAttribute('data-upload-video') ||
    input.hasAttribute('data-upload-audio');

  if (!inFiles && !hasDataAttr) return; // not ours

  ensureId(input, 'dynfile');

  // Audio is handled by its own recorder logic; all other types are "file"
  const kind = getInputKind(input);
  if (kind === 'audio') {
    // let audio recorder JS handle
    return;
  }

  // All non-audio use the unified handler + template
  handleFileInputChange(input);
});

// ====== Áudio (recorder/player) com MutationObserver =========================
// (SECTION KEPT AS-IS, only context above was changed)

const __audioDurationCache = new Map();
let __sharedAudioCtx = null;

function secondsToMMSS(sec) {
  const s = Math.max(0, Math.floor(sec || 0));
  const m = String(Math.floor(s / 60)).padStart(2, '0');
  const r = String(s % 60).padStart(2, '0');
  return `${m}:${r}`;
}
async function decodeDurationWithWebAudio(url) {
  if (__audioDurationCache.has(url)) return __audioDurationCache.get(url);
  if (!__sharedAudioCtx) {
    try { __sharedAudioCtx = new (window.AudioContext || window.webkitAudioContext)(); }
    catch { /* ignore */ }
  }
  if (!__sharedAudioCtx) throw new Error('WebAudio indisponível');
  const u = new URL(url, window.location.href);
  const isCross = u.origin !== window.location.origin;

  // Se for cross-origin, fetch pode falhar por CORS → não tenta
  if (isCross) throw new Error('skip webaudio fetch (cross-origin)');

  const resp = await fetch(url, { cache: 'force-cache' });
  if (!resp.ok) throw new Error('fetch falhou');
  const buf = await resp.arrayBuffer();
  const audioBuf = await __sharedAudioCtx.decodeAudioData(buf.slice(0));
  const dur = audioBuf.duration || NaN;
  if (!isFinite(dur) || dur <= 0) throw new Error('duration inválida');
  __audioDurationCache.set(url, dur);
  return dur;
}
function primeMetadataMuted(audio) {
  return new Promise((resolve) => {
    let done = false;

    const prevMuted = audio.muted;
    const prevVol = audio.volume;

    const finish = () => {
      if (done) return;
      done = true;

      audio.removeEventListener('loadedmetadata', onMeta);
      audio.removeEventListener('error', onErr);

      audio.muted = prevMuted;
      audio.volume = prevVol;

      resolve();
    };

    const onMeta = () => finish();
    const onErr  = () => finish();

    audio.addEventListener('loadedmetadata', onMeta);
    audio.addEventListener('error', onErr);

    // mute temporário só pra “pingar” metadata
    audio.muted = true;
    audio.volume = 0;

    const p = audio.play();
    if (p && typeof p.then === 'function') {
      p.then(() => {
        audio.pause();
        setTimeout(finish, 0);
      }).catch(() => {
        audio.preload = 'metadata';
        audio.load();
        setTimeout(finish, 300);
      });
    } else {
      audio.preload = 'metadata';
      audio.load();
      setTimeout(finish, 300);
    }
  });
}


async function resolveDurationForPlayer(player, audio) {
  const src = player.dataset.audio || '';
  if (!src) return NaN;
  try {
    const d = await decodeDurationWithWebAudio(src);
    if (isFinite(d) && d > 0) return d;
  } catch {}
  try {
    await primeMetadataMuted(audio);
    if (isFinite(audio.duration) && audio.duration > 0) return audio.duration;
  } catch {}
  return NaN;
}

const __playersBound = new WeakSet();
const __globalAudios = new Set();

function setupAudioPlayer(player) {
  if (!player || __playersBound.has(player)) return;
  __playersBound.add(player);

  const playBtn     = player.querySelector('.play-btn');
  const pauseBtn    = player.querySelector('.pause-btn');
  const progressBar = player.querySelector('.progress-bar');
  const progressFill= player.querySelector('.progress-fill');
  const timeDisplay = player.querySelector('.time');
  const deleteBtn   = player.querySelector('.btn-remove');

  const audio = new Audio(player.dataset.audio || '');
  audio.preload = 'metadata';
  player._audio = audio;
  __globalAudios.add(audio);

  let resolvedDuration = NaN;
  if (timeDisplay) timeDisplay.textContent = '00:00';

  (async () => {
    resolvedDuration = await resolveDurationForPlayer(player, audio);
    if (isFinite(resolvedDuration) && resolvedDuration > 0) {
      player.dataset.duration = resolvedDuration;
      if (timeDisplay && audio.paused) timeDisplay.textContent = secondsToMMSS(resolvedDuration);
    }
  })();

  audio.addEventListener('loadedmetadata', () => {
    if (isFinite(audio.duration) && audio.duration > 0) {
      resolvedDuration = audio.duration;
      player.dataset.duration = resolvedDuration;
      if (timeDisplay && audio.paused) timeDisplay.textContent = secondsToMMSS(resolvedDuration);
    }
  });

  playBtn?.addEventListener('click', () => {
    __globalAudios.forEach(a => { if (a !== audio) a.pause(); });
    audio.play().catch(() => {});
  });
  pauseBtn?.addEventListener('click', () => audio.pause());

  audio.addEventListener('play',  () => { if (playBtn) playBtn.style.display = 'none'; if (pauseBtn) pauseBtn.style.display = 'inline-block'; });
  audio.addEventListener('pause', () => { if (playBtn) playBtn.style.display = 'inline-block'; if (pauseBtn) pauseBtn.style.display = 'none'; });
  audio.addEventListener('ended', () => {
    if (playBtn) playBtn.style.display = 'inline-block';
    if (pauseBtn) pauseBtn.style.display = 'none';
    if (progressFill) progressFill.style.width = '0%';
    if (isFinite(resolvedDuration) && resolvedDuration > 0) {
      if (timeDisplay) timeDisplay.textContent = secondsToMMSS(resolvedDuration);
    } else if (timeDisplay) timeDisplay.textContent = '00:00';
  });

  audio.addEventListener('timeupdate', () => {
    const d = (isFinite(audio.duration) && audio.duration > 0) ? audio.duration : (isFinite(resolvedDuration) ? resolvedDuration : NaN);
    if (progressFill && isFinite(d) && d > 0) progressFill.style.width = `${(audio.currentTime / d) * 100}%`;
    if (timeDisplay) timeDisplay.textContent = secondsToMMSS(audio.currentTime);
  });

  progressBar?.addEventListener('click', (e) => {
    const rect = progressBar.getBoundingClientRect();
    const percent = (e.clientX - rect.left) / rect.width;
    const d = (isFinite(audio.duration) && audio.duration > 0) ? audio.duration : (isFinite(resolvedDuration) ? resolvedDuration : NaN);
    if (isFinite(d) && d > 0) audio.currentTime = Math.max(0, Math.min(d, percent * d));
  });

  deleteBtn?.addEventListener('click', () => {
    audio.pause();
    let serverFilename = player.dataset.serverFilename || '';
    if (!serverFilename) {
      try {
        const u = new URL(player.dataset.audio || '', window.location.origin);
        serverFilename = (u.pathname.split('/').pop() || '').split('?')[0];
      } catch {
        const p = (player.dataset.audio || '').split('?')[0];
        serverFilename = p.split('/').pop() || '';
      }
    }
    if (serverFilename) updateInputFilesJSON(deleteBtn, serverFilename);

    const recorderEl = player.closest('.audio-recorder');
    const hidden = recorderEl?.closest('.files')?.querySelector('input[input-files]');
    const multi = recorderEl?.hasAttribute('multiple');

    if (!multi && hidden) {
      try { const arr = JSON.parse(hidden.value || '[]'); if (!arr.length) hidden.value = '[]'; }
      catch { hidden.value = '[]'; }
    }
    player.remove();
  });
}

// ==== Bluetooth + mic interno helpers =======================================

let __audioDevicesChecked = false;

async function buildAudioConstraintsWithInternalMic() {
  const baseConstraints = {
    audio: {
      echoCancellation: true,
      noiseSuppression: true,
      autoGainControl: true,
      channelCount: 1
    }
  };

  if (!navigator.mediaDevices?.enumerateDevices) {
    return baseConstraints;
  }

  try {
    const devices = await navigator.mediaDevices.enumerateDevices();
    const audioInputs = devices.filter(d => d.kind === 'audioinput');

    const hasBluetooth = audioInputs.some(d =>
      /bluetooth|bt/i.test(d.label || '')
    );

    if (hasBluetooth && !__audioDevicesChecked) {
      __audioDevicesChecked = true;
      alert(
        '⚠️ Detectamos um microfone Bluetooth conectado (relógio/fone/carro).\n' +
        'Isso pode fazer a gravação sair sem som. Se tiver problema, desconecte o Bluetooth e tente novamente.'
      );
    }

    // tentar identificar microfone interno pelo label
    const builtin = audioInputs.find(d =>
      /built[- ]?in|default|microfone (do dispositivo|integrado)|micrófono integrado/i.test(d.label || '')
    );

    if (builtin && builtin.deviceId) {
      baseConstraints.audio.deviceId = { exact: builtin.deviceId };
    }

    return baseConstraints;
  } catch (e) {
    console.warn('Falha ao inspecionar devices de áudio:', e);
    return baseConstraints;
  }
}

const __recordersBound = new WeakSet();

function initAudioRecorder(rec) {
  if (!rec || __recordersBound.has(rec)) return;
  __recordersBound.add(rec);

  let mediaRecorder, chunks = [];
  let timer = null, meterLoop = null, time = 0;
  let analyser, dataArray;
  let isCancelled = false;

  const isMulti      = rec.hasAttribute('multiple');
  const startBtn     = rec.querySelector('.start-recording');
  const stopBtn      = rec.querySelector('.stop-recording');
  const confirmBtn   = rec.querySelector('.confirm-recording');
  const preview      = rec.querySelector('.audio-preview');
  const hiddenInput  = rec.closest('.files')?.querySelector('input[input-files]');
  const timerDisplay = rec.querySelector('.recording-timer');
  const panel        = rec.querySelector('.panel');
  const description  = rec.querySelector('.description');
  const template     = rec.querySelector('.template-player');
  const volumeCircle = rec.querySelector('.volume-circle');

  const min = parseInt(rec.dataset.min || '0', 10);
  const max = parseInt(rec.dataset.max || '3600', 10);

  startBtn?.addEventListener('click', async () => {
    isCancelled = false;
    __globalAudios.forEach(a => { a.pause(); });

    chunks = [];
    time = 0;
    if (!isMulti) { if (preview) preview.innerHTML = ''; if (hiddenInput) hiddenInput.value = '[]'; }

    try {
      // 🔹 Força uso do mic interno quando possível + avisa se houver Bluetooth
      const constraints = await buildAudioConstraintsWithInternalMic();
      const stream = await navigator.mediaDevices.getUserMedia(constraints);
      mediaRecorder = new MediaRecorder(stream);

      // meter
      const audioCtx = new AudioContext();
      const source = audioCtx.createMediaStreamSource(stream);
      analyser = audioCtx.createAnalyser();
      analyser.fftSize = 64;
      const bufferLength = analyser.frequencyBinCount;
      dataArray = new Uint8Array(bufferLength);
      source.connect(analyser);

      (function drawMeter() {
        meterLoop = requestAnimationFrame(drawMeter);
        analyser.getByteFrequencyData(dataArray);
        const volume = dataArray.reduce((a, b) => a + b, 0) / dataArray.length;
        const scale = Math.min(Math.max(volume / 50, 0.2), 1);
        if (volumeCircle) volumeCircle.style.transform = `scale(${scale})`;
      })();

      mediaRecorder.ondataavailable = (e) => { if (e.data.size > 0) chunks.push(e.data); };
      mediaRecorder.onstop = async () => {
        if (isCancelled) return;

        const blob = new Blob(chunks, { type: 'audio/webm' });
        if (blob.size > maxUploadSize) {
          alert(`Áudio muito grande: ${(blob.size / 1024 / 1024).toFixed(2)}MB. Máx: ${(maxUploadSize / 1024 / 1024).toFixed(2)}MB`);
          return;
        }

        const url = URL.createObjectURL(blob);
        const player = template.cloneNode(true);
        player.classList.remove('template-player');
        player.style.display = '';
        player.dataset.audio = url;
        player.dataset.duration = time;

        if (!isMulti && preview) preview.innerHTML = '';
        preview?.appendChild(player);

        setupAudioPlayer(player);

        const durationSec = Math.max(0, time);
        const minutes = String(Math.floor(durationSec / 60)).padStart(2, '0');
        const seconds = String(durationSec % 60).padStart(2, '0');
        player.querySelector('.time')?.replaceChildren(document.createTextNode(`${minutes}:${seconds}`));
        player.dataset.duration = durationSec;

        player.setAttribute('data-uploading', '1');
        player.style.opacity = '0.6';

        try {
          const field =
            rec.getAttribute('field') ||
            hiddenInput?.getAttribute('field') ||
            '';

          const data = await uploadTempBlob(blob, {
            field,
            filename: `audio-${Date.now()}.webm`,
            mime: 'audio/webm'
          });

          const filename = extractUploadFilename(data);

          const filesWrap = rec.closest('.files');
          const fakeInput = filesWrap?.querySelector('input[type="file"]');
          const targetEl  = isMulti ? (fakeInput || hiddenInput) : hiddenInput;

          if (isMulti && targetEl) {
            appendFileNameToJSON(targetEl, filename);
          } else if (!isMulti && hiddenInput) {
            hiddenInput.value = JSON.stringify([filename]);
          }

          player.dataset.serverFilename = filename;
          if (data.url) player.dataset.audio = data.url;

        } catch (e) {
          console.error(e);
          alert('Falha ao enviar o áudio.');
          if (hiddenInput) hiddenInput.value = '[]';
          player.remove();
        } finally {
          player.removeAttribute('data-uploading');
          player.style.opacity = '';
        }
      };

      mediaRecorder.start();

      // clock
      const t0 = Date.now();
      time = 0;
      if (timerDisplay) timerDisplay.textContent = '00:00';

      // update 4x/s using real time
      timer = setInterval(() => {
        const elapsed = Math.floor((Date.now() - t0) / 1000);
        if (elapsed !== time) {
          time = elapsed;
          if (timerDisplay) timerDisplay.textContent = secondsToMMSS(time);
        }
        if (elapsed >= max) {
          clearInterval(timer);
          confirmBtn?.click(); // stop exactly at max
        }
      }, 250);

      startBtn.classList.add('d-none');
      description?.classList.add('d-none');
      panel?.classList.remove('d-none');
      confirmBtn?.classList.remove('d-none');
      if (volumeCircle) volumeCircle.style.display = 'block';

    } catch (err) {
      alert(
        `Permissão de microfone foi negada ou não foi possível acessar o dispositivo.\n\n` +
        `⚠️ Dicas:\n` +
        `1. Verifique se o site tem permissão para usar o microfone.\n` +
        `2. Desconecte fones/relogios Bluetooth se o áudio sair mudo.\n` +
        `3. Recarregue a página.\n`
      );
      if (volumeCircle) volumeCircle.style.display = 'none';
      panel?.classList.add('d-none');
    }
  });

  stopBtn?.addEventListener('click', () => {
    isCancelled = true;
    if (timer) { clearInterval(timer); timer = null; }
    if (mediaRecorder && mediaRecorder.state !== 'inactive') {
      mediaRecorder.stop();
      mediaRecorder.stream.getTracks().forEach(track => track.stop());
    }
    if (meterLoop) cancelAnimationFrame(meterLoop);
    if (volumeCircle) { volumeCircle.style.display = 'none'; volumeCircle.style.transform = 'scale(1)'; }
    panel?.classList.add('d-none');
    if (timerDisplay) timerDisplay.textContent = '';
    startBtn?.classList.remove('d-none');
    description?.classList.remove('d-none');
    confirmBtn?.classList.add('d-none');
    if (!isMulti) { if (preview) preview.innerHTML = ''; if (hiddenInput) hiddenInput.value = '[]'; }
  });

  confirmBtn?.addEventListener('click', () => {
    if (time < min) { alert(`Gravação muito curta. Mínimo de ${min} segundos.`); return; }
    if (timer) { clearInterval(timer); timer = null; }
    if (mediaRecorder && mediaRecorder.state !== 'inactive') {
      mediaRecorder.stop();
      mediaRecorder.stream.getTracks().forEach(track => track.stop());
    }
    if (meterLoop) cancelAnimationFrame(meterLoop);
    if (volumeCircle) { volumeCircle.style.display = 'none'; volumeCircle.style.transform = 'scale(1)'; }
    panel?.classList.add('d-none');
    if (timerDisplay) timerDisplay.textContent = '';
    startBtn?.classList.remove('d-none');
    description?.classList.remove('d-none');
    confirmBtn?.classList.add('d-none');
  });

  // Bind pre-rendered players inside recorder
  rec.querySelectorAll('.audio-player:not(.template-player)').forEach(setupAudioPlayer);
}

// Initial scan for audio recorders and players
function scanAudio(root = document) {
  root.querySelectorAll('.audio-recorder').forEach(initAudioRecorder);
  root.querySelectorAll('.audio-player:not(.template-player)').forEach(setupAudioPlayer);
}
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => scanAudio());
} else {
  scanAudio();
}

// ====== MutationObserver: inputs & áudio =====================================
const __observer = new MutationObserver((mutations) => {
  for (const m of mutations) {
    for (const node of m.addedNodes) {
      if (!(node instanceof Element)) continue;

      // Files: prepare inputs and structure
      const candidateInputs = [];
      if (node.matches?.('input[type="file"]')) candidateInputs.push(node);
      node.querySelectorAll?.('input[type="file"]').forEach(i => candidateInputs.push(i));

      for (const input of candidateInputs) {
        if (!input.closest('.files') && !(
          input.hasAttribute('data-upload-image') ||
          input.hasAttribute('data-upload-archive') ||
          input.hasAttribute('data-upload-video') ||
          input.hasAttribute('data-upload-audio')
        )) continue; // not ours

        ensureId(input, 'dynfile');
        const filesWrap = input.closest('.files');
        if (filesWrap) ensureFilesScaffold(filesWrap, input);
      }

      // Audio: recorders/players
      if (node.matches?.('.audio-recorder')) initAudioRecorder(node);
      if (node.matches?.('.audio-player:not(.template-player)')) setupAudioPlayer(node);
      node.querySelectorAll?.('.audio-recorder').forEach(initAudioRecorder);
      node.querySelectorAll?.('.audio-player:not(.template-player)').forEach(setupAudioPlayer);
    }
  }
});
__observer.observe(document.documentElement, { childList: true, subtree: true });
