document.addEventListener("DOMContentLoaded", function () {

  const meta = document.querySelector('meta[name="pagbank-public-key"]');
  const publicKey = meta ? (meta.getAttribute("content") || "").trim() : "";

  if (!publicKey) {
    console.error("PagBank public key not found in meta[name='pagbank-public-key']");
    return;
  }

  if (typeof PagSeguro === "undefined") {
    console.error("PagBank SDK not loaded");
    return;
  }

  const form = document.querySelector("form") || document.querySelector("form");
  if (!form) return;

  const numberEl = form.querySelector('[name="payment_data[card_number]"]');
  const holderEl = form.querySelector('[name="payment_data[name]"]');
  const expEl    = form.querySelector('[name="payment_data[expiration]"]');
  const cvvEl    = form.querySelector('[name="payment_data[cvv]"]');

  const tokenInput = form.querySelector('[name="payment_data[token]"]');

  // If fields are missing, this form is not CC (maybe Pix)
  if (!numberEl || !holderEl || !expEl || !cvvEl || !tokenInput) return;

  let isSubmitting = false;

  function parseExpiration(expRaw) {
    const parts = String(expRaw || "").trim().split("/");
    if (parts.length < 2) return null;

    const m = parseInt(parts[0], 10);
    let y = String(parts[1]).trim();

    if (!m || m < 1 || m > 12) return null;

    if (y.length === 2) y = "20" + y;
    const year = parseInt(y, 10);

    if (!year || year < 1900 || year > 2099) return null;

    return {
      expMonth: String(m).padStart(2, "0"),
      expYear: String(year)
    };
  }

  const onClickTokenize = function (e) {

    // Only when clicking on submit-like buttons inside this form
    const btn = e.target.closest('button[type="submit"], input[type="submit"], [data-submit], [data-send-form]');
    if (!btn || !form.contains(btn)) return;

    // If token already exists, let the universal handler proceed
    if (tokenInput.value) return;

    // Prevent re-entry / double click
    if (isSubmitting) {
      e.preventDefault();
      e.stopImmediatePropagation();
      return;
    }

    // Block universal handler for THIS click
    e.preventDefault();
    e.stopImmediatePropagation();

    isSubmitting = true;
    btn.setAttribute("disabled", "true");

    try {
      // Collect
      const number = String(numberEl.value || "").replace(/\D+/g, "");
      const holder = String(holderEl.value || "").trim();
      const exp = parseExpiration(expEl.value);
      const cvv = String(cvvEl.value || "").trim();

      if (!number || number.length < 12) throw new Error("Invalid card number");
      if (!holder) throw new Error("Invalid holder");
      if (!exp) throw new Error("Invalid expiration");
      if (!cvv || cvv.length < 3) throw new Error("Invalid CVV");

      // Tokenize (PagBank style)
      const cardObj = PagSeguro.encryptCard({
        publicKey: publicKey,
        holder: holder,
        number: number,
        expMonth: exp.expMonth,
        expYear: exp.expYear,
        securityCode: cvv
      });

      if (!cardObj || cardObj.hasErrors || !cardObj.encryptedCard) {
        console.error(cardObj);
        throw new Error("Failed to encrypt card");
      }

      // Save token to hidden field
      tokenInput.value = cardObj.encryptedCard;

      // Remove sensitive data before sending to backend
      // numberEl.value = "";
      // cvvEl.value = "";

      // Temporarily disable this listener to avoid recursion
      form.removeEventListener("click", onClickTokenize, true);

      // Re-enable and re-trigger click so the universal handler runs
      btn.removeAttribute("disabled");
      btn.click();

      // Restore listener
      form.addEventListener("click", onClickTokenize, true);

    } catch (err) {
      console.log("Tokenize error:", err);
      alert("Não foi possível validar o cartão. Verifique os dados e tente novamente.");
    } finally {
      isSubmitting = false;
      btn.removeAttribute("disabled");
    }
  };

  // Capture phase so we can stop other click handlers reliably
  form.addEventListener("click", onClickTokenize, true);
});
