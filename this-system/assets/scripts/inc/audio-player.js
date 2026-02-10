// /js/audio-player.js
// Isolado para lidar apenas com gravação, preview, upload e remoção de áudios.
// Depende de: BASE_URL, REST_API_BASE_ROUTE, maxUploadSize, updateInputFilesJSON (já existentes no core).

(function ()
{
  const BASE_URL = window.BASE_URL || '';
  const REST_API_BASE_ROUTE = window.REST_API_BASE_ROUTE || '';
  const maxUploadSize = parseInt(
    document.querySelector("meta[property='site:max_upload_size']")?.content || "2097152"
  );

  async function uploadTempBlob(blob, { field, filename, mime })
  {
    const form = new FormData();
    const file = new File([blob], filename || `audio-${Date.now()}.webm`, { type: mime || blob.type || 'audio/webm' });
    form.append('file', file);
    form.append('field', field || '');

    const endpoint = `${BASE_URL}/${REST_API_BASE_ROUTE}/ajax-upload-temp`;
    const res = await fetch(endpoint, { method: 'POST', body: form });
    if (!res.ok) throw new Error('Falha na requisição');

    const data = await res.json();
    if (!data || data.code !== 'success' || !data.url) {
      throw new Error(data?.msg || 'Upload não aceito pelo servidor');
    }
    return data;
  }

  function extractUploadFilename(data)
  {
    let fname = null;
    if (data?.file) {
      if (typeof data.file === 'string') fname = data.file;
      else if (typeof data.file === 'object' && data.file.file) fname = data.file.file;
      else if (Array.isArray(data.file)) fname = data.file.find(v => typeof v === 'string');
    }
    if (!fname && data?.url) {
      try { fname = new URL(data.url, window.location.origin).pathname.split('/').pop(); }
      catch { fname = (data.url || '').split('?')[0].split('/').pop(); }
    }
    return fname || '';
  }

  function setupAudioPlayer(player, globalAudios) {
    const playBtn     = player.querySelector('.play-btn');
    const pauseBtn    = player.querySelector('.pause-btn');
    const progressBar = player.querySelector('.progress-bar');
    const progressFill= player.querySelector('.progress-fill');
    const timeDisplay = player.querySelector('.time');
    const deleteBtn   = player.querySelector('.btn-remove');

    const audio = new Audio(player.dataset.audio);
    globalAudios.push(audio);

    audio.addEventListener('loadedmetadata', () => {
      if (isFinite(audio.duration)) {
        player.dataset.duration = audio.duration;
        const minutes = String(Math.floor(audio.duration / 60)).padStart(2, '0');
        const seconds = String(Math.floor(audio.duration % 60)).padStart(2, '0');
        timeDisplay.textContent = `${minutes}:${seconds}`;
      }
    });

    playBtn?.addEventListener('click', () => {
      globalAudios.forEach(a => { if (a !== audio) { a.pause(); a.currentTime = 0; } });
      audio.play();
    });

    pauseBtn?.addEventListener('click', () => audio.pause());

    audio.addEventListener('play',  () => { playBtn.style.display = 'none'; pauseBtn.style.display = 'inline-block'; });
    audio.addEventListener('pause', () => { playBtn.style.display = 'inline-block'; pauseBtn.style.display = 'none'; });
    audio.addEventListener('ended', () => {
      playBtn.style.display = 'inline-block';
      pauseBtn.style.display = 'none';
      progressFill.style.width = '0%';
      timeDisplay.textContent = '00:00';
    });

    audio.addEventListener('timeupdate', () => {
      let duration = audio.duration;
      if (!isFinite(duration) || isNaN(duration)) duration = parseFloat(player.dataset.duration || 0);
      if (isFinite(duration) && duration > 0) {
        const percent = (audio.currentTime / duration) * 100;
        progressFill.style.width = `${percent}%`;
        const minutes = String(Math.floor(audio.currentTime / 60)).padStart(2, '0');
        const seconds = String(Math.floor(audio.currentTime % 60)).padStart(2, '0');
        timeDisplay.textContent = `${minutes}:${seconds}`;
      }
    });

    progressBar?.addEventListener('click', (e) => {
      const rect = progressBar.getBoundingClientRect();
      const percent = (e.clientX - rect.left) / rect.width;
      let duration = audio.duration;
      if (!isFinite(duration) || isNaN(duration)) duration = parseFloat(player.dataset.duration || 0);
      if (!isNaN(duration) && duration > 0) audio.currentTime = percent * duration;
    });

    deleteBtn?.addEventListener('click', () => {
      audio.pause();
      const filename = player.dataset.serverFilename || '';
      if (filename && typeof window.updateInputFilesJSON === 'function') {
        window.updateInputFilesJSON($(deleteBtn), filename);
      }
      const preview = player.closest('.audio-preview');
      const hiddenInput = preview.closest('.audio-recorder').querySelector('input[type=hidden]');
      if (hiddenInput) hiddenInput.value = '';
      player.remove();
    });
  }

  function initAudioRecorders() {
    const recorders = document.querySelectorAll('.audio-recorder');
    const globalAudios = [];

    recorders.forEach(rec => {
      let mediaRecorder;
      let chunks = [];
      let timer = null;
      let meterLoop = null;
      let time = 0;
      let analyser, dataArray;
      let isCancelled = false;

      const startBtn      = rec.querySelector('.start-recording');
      const stopBtn       = rec.querySelector('.stop-recording');
      const confirmBtn    = rec.querySelector('.confirm-recording');
      const preview       = rec.querySelector('.audio-preview');
      const hiddenInput   = rec.querySelector('input[type=hidden]');
      const timerDisplay  = rec.querySelector('.recording-timer');
      const panel         = rec.querySelector('.panel');
      const description   = rec.querySelector('.description');
      const template      = rec.querySelector('.template-player');
      const volumeCircle  = rec.querySelector('.volume-circle');

      const min = parseInt(rec.dataset.min || '1', 10);
      const max = parseInt(rec.dataset.max || '10', 10);

      function updateTimer() {
        const minutes = String(Math.floor(time / 60)).padStart(2, '0');
        const seconds = String(time % 60).padStart(2, '0');
        timerDisplay.textContent = `${minutes}:${seconds}`;
      }

      startBtn?.addEventListener('click', async () => {
        isCancelled = false;
        globalAudios.forEach(a => { a.pause(); a.currentTime = 0; });

        chunks = [];
        time = 0;
        preview.innerHTML = '';
        if (hiddenInput) hiddenInput.value = '';

        try {
          const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
          mediaRecorder = new MediaRecorder(stream);

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

          mediaRecorder.ondataavailable = e => { if (e.data.size > 0) chunks.push(e.data); };

          mediaRecorder.onstop = async () => {
            if (isCancelled) return;
            const blob = new Blob(chunks, { type: 'audio/webm' });
            if (blob.size > maxUploadSize) {
              alert(`Áudio muito grande: ${(blob.size / 1024 / 1024).toFixed(2)}MB.`);
              return;
            }

            const player = template.cloneNode(true);
            player.classList.remove('template-player');
            player.style.display = '';
            player.dataset.audio = URL.createObjectURL(blob);
            player.dataset.duration = time;
            preview.innerHTML = '';
            preview.append(player);
            setupAudioPlayer(player, globalAudios);

            try {
              const field =
                rec.getAttribute('field') ||
                (hiddenInput && hiddenInput.getAttribute('field')) ||
                '';
              const data = await uploadTempBlob(blob, {
                field,
                filename: `audio-${Date.now()}.webm`,
                mime: 'audio/webm'
              });
              const filename = extractUploadFilename(data);
              player.dataset.serverFilename = filename;
              if (data.url) player.dataset.audio = data.url;

              if (typeof window.updateInputFilesJSON === 'function') {
                window.updateInputFilesJSON($(rec), filename);
              }
            } catch (e) {
              console.error(e);
              alert('Falha ao enviar o áudio.');
              player.remove();
            }
          };

          mediaRecorder.start();
          updateTimer();

          startBtn.classList.add('d-none');
          description?.classList.add('d-none');
          panel?.classList.remove('d-none');
          confirmBtn?.classList.remove('d-none');
          if (volumeCircle) volumeCircle.style.display = 'block';

          timer = setInterval(() => {
            time++;
            updateTimer();
            if (time >= max) confirmBtn?.click();
          }, 1000);
        } catch {
          alert('Permissão de microfone negada.');
        }
      });

      stopBtn?.addEventListener('click', () => {
        isCancelled = true;
        if (mediaRecorder && mediaRecorder.state !== 'inactive') {
          mediaRecorder.stop();
          mediaRecorder.stream.getTracks().forEach(t => t.stop());
        }
        if (meterLoop) cancelAnimationFrame(meterLoop);
        if (volumeCircle) {
          volumeCircle.style.display = 'none';
          volumeCircle.style.transform = 'scale(1)';
        }
        clearInterval(timer);
        timerDisplay.textContent = '';
        startBtn?.classList.remove('d-none');
        description?.classList.remove('d-none');
        panel?.classList.add('d-none');
        confirmBtn?.classList.add('d-none');
        preview.innerHTML = '';
      });

      confirmBtn?.addEventListener('click', () => {
        if (time < min) {
          alert(`Gravação muito curta. Mínimo de ${min} segundos.`);
          return;
        }
        if (mediaRecorder && mediaRecorder.state !== 'inactive') {
          mediaRecorder.stop();
          mediaRecorder.stream.getTracks().forEach(t => t.stop());
        }
        if (meterLoop) cancelAnimationFrame(meterLoop);
        if (volumeCircle) {
          volumeCircle.style.display = 'none';
          volumeCircle.style.transform = 'scale(1)';
        }
        clearInterval(timer);
        timerDisplay.textContent = '';
        startBtn?.classList.remove('d-none');
        description?.classList.remove('d-none');
        panel?.classList.add('d-none');
        confirmBtn?.classList.add('d-none');
      });
    });
  }

  document.addEventListener('DOMContentLoaded', initAudioRecorders);
})();
