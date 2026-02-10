(() => {
  /**
   * Shorthand for querySelector on a given root (or document).
   *
   * @param {ParentNode|null|undefined} root Root element for the search (defaults to document).
   * @param {string} sel CSS selector.
   * @returns {Element|null} First matched element or null.
   */
  const $$ = (root, sel) => (root || document).querySelector(sel);

  /**
   * Given a button inside an `.input-group`, finds the related password/text input.
   *
   * @param {HTMLElement} btn Button element (ex: [show-hide-password] or [generate-password]).
   * @returns {HTMLInputElement|null} Input element if found.
   */
  function getPwInput(btn) {
    return btn
      .closest('.input-group')
      ?.querySelector('input[type="password"],input[type="text"]');
  }

  /**
   * Toggles the password visibility and updates the eye icon + title accordingly.
   *
   * Assumes the button contains an element `.icon` using FontAwesome classes.
   *
   * @param {HTMLElement} btn The show/hide button.
   * @param {HTMLInputElement} input The target password input.
   * @returns {void}
   */
  function toggleEye(btn, input) {
    const icon = btn.querySelector('.icon');
    if (!icon) return;

    const shouldShow = input.type === 'password';

    input.type = shouldShow ? 'text' : 'password';

    icon.classList.toggle('fa-eye', !shouldShow);
    icon.classList.toggle('fa-eye-slash', shouldShow);

    btn.title = shouldShow ? 'Ocultar senha' : 'Mostrar senha';
  }

  /**
   * Generates a random password ensuring:
   * - At least 1 uppercase
   * - At least 1 lowercase
   * - At least 1 number
   * - At least 1 special character
   *
   * Password length is clamped between 8 and 64.
   *
   * @param {number} len Desired length (default 16).
   * @returns {string} Generated password.
   */
  function genPassword(len = 16) {
    len = Math.max(8, Math.min(64, len | 0));

    const U = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    const L = 'abcdefghijklmnopqrstuvwxyz';
    const N = '0123456789';
    const S = '!@#$%^&*()-_=+[]{}<>?';
    const A = U + L + N + S;

    /**
     * Picks a single random character from a string.
     *
     * @param {string} str Source string.
     * @returns {string} Single character.
     */
    const pick = (str) => str[(Math.random() * str.length) | 0];

    // Guarantee minimum diversity: 1 from each group.
    const chars = [pick(U), pick(L), pick(N), pick(S)];

    // Fill remaining length with any allowed character.
    for (let i = chars.length; i < len; i++) chars.push(pick(A));

    // Shuffle (Fisher–Yates).
    for (let i = chars.length - 1; i > 0; i--) {
      const j = (Math.random() * (i + 1)) | 0;
      [chars[i], chars[j]] = [chars[j], chars[i]];
    }

    return chars.join('');
  }

  /**
   * Updates UI rules feedback (within the same fieldset) based on the current password value.
   *
   * Expects this structure:
   * - input is inside a <fieldset>
   * - inside fieldset there is a <small> containing rule spans like:
   *   - .has-number
   *   - .has-upper
   *   - .has-special-characters
   *   - .length
   *
   * It toggles `text-success` / `text-danger` on each rule span.
   *
   * @param {HTMLInputElement} input The main password input ([data-password-must]).
   * @returns {void}
   */
  function updateStrength(input) {
    const fs = input.closest('fieldset');
    const small = fs ? $$(fs, 'small') : null;
    if (!small) return;

    const pw = input.value || '';
    const min = parseInt(input.getAttribute('minlength'), 10) || 0;
    const max = parseInt(input.getAttribute('maxlength'), 10) || Infinity;

    const checks = [
      [ $$(small, '.has-number'), /\d/ ],
      [ $$(small, '.has-upper'), /[A-Z]/ ],
      [ $$(small, '.has-special-characters'), /[!@#$%^&*(),.?":{}|<>]/ ],
    ];

    checks.forEach(([el, rx]) => {
      if (!el) return;
      const ok = rx.test(pw);
      el.classList.toggle('text-success', ok);
      el.classList.toggle('text-danger', !ok);
    });

    const lenEl = $$(small, '.length');
    if (lenEl) {
      const ok = pw.length >= min && pw.length <= max;
      lenEl.classList.toggle('text-success', ok);
      lenEl.classList.toggle('text-danger', !ok);
    }
  }

  /**
   * Ensures a repeat-password feedback element exists as a direct child of the fieldset.
   * (This helper is currently not used directly by updateRepeatState, but kept as utility.)
   *
   * @param {HTMLElement} fieldset The fieldset wrapping the repeat input.
   * @param {string} msg Message to show.
   * @returns {HTMLElement} The feedback element.
   */
  function ensureRepeatFeedback(fieldset, msg) {
    let fb = fieldset.querySelector('.invalid-feedback[data-repeat-password-feedback]');
    if (!fb) {
      fb = document.createElement('small');
      fb.className = 'invalid-feedback d-block';
      fb.setAttribute('data-repeat-password-feedback', '1');
      fieldset.appendChild(fb);
    }
    fb.textContent = msg;
    return fb;
  }

  /**
   * Updates the "repeat password" validation state and controls the submit button enabled/disabled.
   *
   * Rules:
   * 1) Strength gate: if inside the password fieldset `<small>` there is any `.text-danger`,
   *    the submit button is blocked.
   * 2) Repeat gate:
   *    - if [data-repeat-password] exists and it has a value, it must match the main password.
   *    - when mismatch: add `.is-invalid` and show a `.invalid-feedback` (direct fieldset child)
   *    - when match: remove invalid class and remove feedback element
   *
   * Finally:
   * - If repeat exists: submit enabled only if both fields are non-empty and equal, and strength ok.
   * - If repeat does not exist: submit enabled only if main password is non-empty and strength ok.
   *
   * @param {HTMLFormElement} form The form containing the fields.
   * @returns {void}
   */
  function updateRepeatState(form) {
    const main = $$(form, '[data-password-must]');
    const rep  = $$(form, '[data-repeat-password]');
    const btn  = $$(form, 'button[type="submit"]');
    if (!main || !btn) return;

    // Strength gate: any ".text-danger" in rules list blocks submit.
    const fsMain = main.closest('fieldset');
    const small = fsMain ? $$(fsMain, 'small') : null;
    const hasWeakRule = !!(small && small.querySelector('.text-danger'));

    // Repeat gate.
    let mismatch = false;

    if (rep) {
      const fieldset = rep.closest('fieldset');
      if (fieldset) {
        const msg = rep.getAttribute('data-message') || 'As senhas não coincidem.';
        const a = main.value || '';
        const b = rep.value || '';

        mismatch = (b !== '' && a !== b);

        let fb = fieldset.querySelector('.invalid-feedback[data-repeat-password-feedback]');
        if (mismatch) {
          if (!fb) {
            fb = document.createElement('small');
            fb.className = 'invalid-feedback d-block';
            fb.setAttribute('data-repeat-password-feedback', '1');
            fieldset.appendChild(fb);
          }
          fb.textContent = msg;
          fb.style.display = 'block';
        } else if (fb) {
          fb.remove();
        }

        rep.classList.toggle('is-invalid', mismatch);
      }
    }

    const a = (main.value || '');
    const b = rep ? (rep.value || '') : null;

    const repeatOk = rep ? (a && b && a === b) : !!a;
    btn.disabled = !(repeatOk && !hasWeakRule);
  }

  /**
   * Global click handler:
   * - [show-hide-password] toggles input type and updates icon/title.
   * - [generate-password] generates a password, puts it into the input, forces input/change events,
   *   then updates strength + submit enable/disable (if inside a form).
   */
  document.addEventListener('click', (e) => {
    const btnEye = e.target.closest('[show-hide-password]');
    if (btnEye) {
      const input = getPwInput(btnEye);
      if (input) toggleEye(btnEye, input);
      return;
    }

    const btnGen = e.target.closest('[generate-password]');
    if (btnGen) {
      const input = getPwInput(btnGen);
      if (!input) return;

      const len = parseInt(btnGen.getAttribute('data-length'), 10) || 16;

      input.value = genPassword(len);
      input.type = 'text';

      // Trigger validation and UI updates downstream.
      input.dispatchEvent(new Event('input', { bubbles: true }));
      input.dispatchEvent(new Event('change', { bubbles: true }));

      const form = input.closest('form');
      if (form) {
        updateStrength(input);
        updateRepeatState(form);
      }
    }
  });

  /**
   * Global input handler:
   * - When typing in main password field: update strength + submit gating.
   * - When typing in repeat field: update submit gating + mismatch feedback.
   */
  document.addEventListener('input', (e) => {
    const t = e.target;

    if (t.matches('[data-password-must]')) {
      updateStrength(t);
      const form = t.closest('form');
      if (form) updateRepeatState(form);
      return;
    }

    if (t.matches('[data-repeat-password]')) {
      const form = t.closest('form');
      if (form) updateRepeatState(form);
    }
  });

  /**
   * Global blur handler:
   * Forces repeat-password mismatch feedback to appear as soon as the user leaves the field.
   */
  document.addEventListener('blur', (e) => {
    const rep = e.target.closest('[data-repeat-password]');
    if (!rep) return;

    const form = rep.closest('form');
    if (form) updateRepeatState(form);
  }, true);
})();
