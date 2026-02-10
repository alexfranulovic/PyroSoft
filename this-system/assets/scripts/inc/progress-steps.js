(function ()
{
  function initProgress4(wrapper) {
    if (!wrapper || wrapper._p4) return;
    wrapper._p4 = true;

    const bar   = wrapper.querySelector('.progress');
    const steps = Array.from(wrapper.querySelectorAll('.progress-step'));

    function paint(index) {
      const maxIndex = Math.max(0, Math.min(index, steps.length - 1));
      steps.forEach((el, i) => el.classList.toggle('progress-step-active', i <= maxIndex));
      const width = (steps.length > 1) ? (maxIndex / (steps.length - 1)) * 100 : 0;
      if (bar) bar.style.width = width + '%';
      wrapper.dataset.active = String(maxIndex);
    }

    wrapper.setActiveByIndex = (i) => paint(i);

    // estado inicial
    const start = parseInt(wrapper.dataset.active || '0', 10) || 0;
    paint(start);
  }

  // init existentes
  document.querySelectorAll('.progress-content.progress_4').forEach(initProgress4);

  // (opcional) suporte a conteúdo dinâmico
  const mo = new MutationObserver(muts => {
    for (const m of muts) {
      for (const n of m.addedNodes) {
        if (!(n instanceof Element)) continue;
        if (n.matches?.('.progress-content.progress_4')) initProgress4(n);
        n.querySelectorAll?.('.progress-content.progress_4').forEach(initProgress4);
      }
    }
  });
  mo.observe(document.documentElement, { childList: true, subtree: true });

  // exemplo de navegação (escopo livre)
  document.addEventListener('click', (e) => {
    const next = e.target.closest('.btn-next');
    const prev = e.target.closest('.btn-prev');
    if (!next && !prev) return;

    const scope   = (next || prev).closest('[data-progress-scope]') || document;
    const wrapper = scope.querySelector('.progress-content.progress_4');
    if (!wrapper?.setActiveByIndex) return;

    const total  = parseInt(wrapper.dataset.total || '1', 10);
    const active = parseInt(wrapper.dataset.active || '0', 10);
    const to     = next ? Math.min(active + 1, total - 1) : Math.max(active - 1, 0);
    wrapper.setActiveByIndex(to);
  });
})();