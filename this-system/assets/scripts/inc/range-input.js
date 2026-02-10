/**
 * 
 * Custom range input that supports a single value or between two values.
 * 
 */
(function ()
{
  // Blocks tap/click right after dragging (mobile ghost click)
  let __rangeLastDragAt = 0;

  function blockGhostTap(e) {
    if (Date.now() - __rangeLastDragAt < 700) { // 500–900ms
      e.preventDefault();
      e.stopPropagation();
      if (e.stopImmediatePropagation) e.stopImmediatePropagation();
      return false;
    }
  }

  // Capture phase: cancels before step-form handlers
  document.addEventListener('click', blockGhostTap, true);

  // iOS/Safari sometimes triggers navigation via touchend -> click
  document.addEventListener('touchend', blockGhostTap, { capture: true, passive: false });

  function initRange(wrapper)
  {
    const inputMin   = wrapper.querySelector('.input-range');
    const inputMax   = wrapper.querySelector('.input-range-max');
    const range      = wrapper.querySelector('.range');
    const selection  = wrapper.querySelector('.range-selection');
    const handleMin  = wrapper.querySelector('.min-range-handle');
    const handleMax  = wrapper.querySelector('.max-range-handle');

    if (!inputMin || !range || !selection || !handleMin) return;

    const d       = inputMin.dataset;
    const min     = +d.rangeMin  || 0;
    const max     = +d.rangeMax  || 100;
    const step    = +d.rangeStep || 1;
    const isRange = !!inputMax && !!handleMax;

    let vMin = parseFloat(inputMin.value); if (isNaN(vMin)) vMin = min;
    let vMax = isRange ? parseFloat(inputMax.value) : null;
    if (isRange && isNaN(vMax)) vMax = max;

    vMin = Math.min(max, Math.max(min, vMin));
    if (isRange) {
      vMax = Math.min(max, Math.max(min, vMax));
      if (vMin > vMax) { const t = vMin; vMin = vMax; vMax = t; }
    }

    const numbers = wrapper.parentElement.querySelector('.numbers');
    const spans   = numbers ? numbers.querySelectorAll('span') : [];
    const spanMin = spans[0] || null;
    const spanMax = spans[1] || null;

    const valToPct = v => ((v - min) / (max - min || 1)) * 100;

    function render() {
      const pMin = valToPct(vMin);

      if (isRange) {
        const pMax = valToPct(vMax);
        handleMin.style.left = pMin + '%';
        handleMax.style.left = pMax + '%';
        selection.style.left = pMin + '%';
        selection.style.width = (pMax - pMin) + '%';
        inputMax.value = String(vMax);
        if (spanMax) spanMax.textContent = vMax;
      } else {
        handleMin.style.left = pMin + '%';
        selection.style.left = '0%';
        selection.style.width = pMin + '%';
      }

      inputMin.value = String(vMin);
      if (spanMin) spanMin.textContent = vMin;
    }

    function clientX(e) {
      return (e.touches && e.touches[0]) ? e.touches[0].clientX : e.clientX;
    }

    function startDrag(which, eDown)
    {
      eDown.preventDefault();
      eDown.stopPropagation();
      if (eDown.stopImmediatePropagation) eDown.stopImmediatePropagation();

      const startX = clientX(eDown);
      let moved = false;

      function move(e)
      {
        // IMPORTANT: passive:false to allow preventDefault on iOS
        e.preventDefault();
        e.stopPropagation();
        if (e.stopImmediatePropagation) e.stopImmediatePropagation();

        if (Math.abs(clientX(e) - startX) > 4) moved = true;

        const rect = range.getBoundingClientRect();
        let pct = (clientX(e) - rect.left) / rect.width;
        pct = Math.max(0, Math.min(1, pct));

        let v = min + pct * (max - min);
        v = Math.round(v / step) * step;

        if (isRange) {
          if (which === 'min') vMin = Math.min(v, vMax);
          else                vMax = Math.max(v, vMin);
        } else {
          vMin = v;
        }

        render();
      }

      function up(e)
      {
        if (e) {
          e.preventDefault();
          e.stopPropagation();
          if (e.stopImmediatePropagation) e.stopImmediatePropagation();
        }

        // If it moved, block any tap/click that may happen next
        if (moved) __rangeLastDragAt = Date.now();

        window.removeEventListener('touchmove', move, true);
        window.removeEventListener('touchend', up, true);
        window.removeEventListener('mousemove', move, true);
        window.removeEventListener('mouseup', up, true);
        window.removeEventListener('pointermove', move, true);
        window.removeEventListener('pointerup', up, true);
      }

      if (window.PointerEvent && eDown.pointerId != null) {
        window.addEventListener('pointermove', move, { capture: true, passive: false });
        window.addEventListener('pointerup', up, { capture: true, passive: false });
      } else {
        window.addEventListener('touchmove', move, { capture: true, passive: false });
        window.addEventListener('touchend', up, { capture: true, passive: false });
        window.addEventListener('mousemove', move, true);
        window.addEventListener('mouseup', up, true);
      }
    }

    const downEvt = (window.PointerEvent) ? 'pointerdown' : 'touchstart';

    handleMin.addEventListener(downEvt, e => startDrag('min', e), { passive: false });
    if (isRange && handleMax) {
      handleMax.addEventListener(downEvt, e => startDrag('max', e), { passive: false });
    }

    render();
  }

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.range-wrapper').forEach(initRange);
  });
})();
