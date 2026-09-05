document.addEventListener("DOMContentLoaded", function ()
{
  let cachedPublicKey = null;
  const publicKeyPromise = fetch(`${BASE_URL}/${REST_API_BASE_ROUTE}/pagbank-public-key`, {
    method: "GET",
    credentials: "include",
    headers: { "Accept": "application/json" }
  })
    .then(res => res.json())
    .then(json => {
      cachedPublicKey = (json && json.code === "success" && json.msg && json.msg.public_key) || "";
      if (!cachedPublicKey) console.error("PagBank public key endpoint returned no key:", json);
      return cachedPublicKey;
    })
    .catch(err => {
      console.error("Failed to fetch PagBank public key:", err);
      cachedPublicKey = "";
      return "";
    });

  if (typeof PagSeguro === "undefined") {
    console.error("PagBank SDK not loaded");
    return;
  }

  const form = document.querySelector("form");
  if (!form) return;

  const installmentsEl = form.querySelector('[name="payment_data[installments]"]');
  const savedCardIdEl  = form.querySelector('[name="payment_data[user_payment_method]"]');

  /**
   * credit_card and debit_card fields can both be rendered on the same
   * checkout at once, when both methods are active. Sharing identical
   * `name` attributes between the two blocks turned out to be fragile --
   * anything that binds by name alone (masking/formatting scripts, form
   * serialization, this file's own earlier version) always targets the
   * first match in the DOM, regardless of which method is actually
   * selected. So debit_card's new-card fields use distinct key names
   * (payment_data[debit_card_number], [debit_token], etc -- see
   * pagbank_debit_card_fields()); credit_card keeps its original,
   * unprefixed names.
   */
  function getFieldKey(name, method) {
    return String(method).startsWith("debit_card") ? `debit_${name}` : name;
  }

  function getMethodField(name, method) {
    if (!method) return null;
    return form.querySelector(`[name="payment_data[${getFieldKey(name, method)}]"]`);
  }

  const hasAnyTokenField = !!form.querySelector('[name="payment_data[token]"], [name="payment_data[debit_token]"]');
  if (!hasAnyTokenField) return;

  let isSubmitting = false;
  let installmentsAbortController = null;

  /**
   * Parse expiration field in MM/YY or MM/YYYY format.
   *
   * @param {string} expRaw Raw expiration value.
   * @returns {{expMonth: string, expYear: string}|null}
   */
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

  /**
   * Reset installments select with a placeholder option.
   *
   * @param {string} placeholder Placeholder text.
   * @returns {void}
   */
  function clearInstallments(placeholder = "Select installments") {
    if (!installmentsEl) return;

    installmentsEl.innerHTML = "";

    const option = document.createElement("option");
    option.value = "";
    option.textContent = placeholder;

    installmentsEl.appendChild(option);
    installmentsEl.value = "";
  }

  /**
   * Format a number in BRL currency.
   *
   * @param {number|string} value Monetary value.
   * @returns {string}
   */
  function formatMoneyBRL(value) {
    return new Intl.NumberFormat("pt-BR", {
      style: "currency",
      currency: "BRL"
    }).format(Number(value || 0));
  }

  /**
   * Normalize card brand names.
   *
   * @param {string} brand Raw brand.
   * @returns {string}
   */
  function normalizeBrand(brand) {
    brand = String(brand || "").trim().toLowerCase();

    const map = {
      visa: "visa",
      mastercard: "mastercard",
      master: "mastercard",
      elo: "elo",
      amex: "amex",
      americanexpress: "amex",
      hipercard: "hipercard",
      diners: "diners",
      discover: "discover",
      jcb: "jcb",
      aura: "aura"
    };

    return map[brand] || "";
  }

  /**
   * Try to detect brand by typed card number.
   *
   * @param {string} cardNumber Card number.
   * @returns {string}
   */
  function detectBrandByCardNumber(cardNumber) {
    const n = String(cardNumber || "").replace(/\D+/g, "");

    if (/^4/.test(n)) return "visa";
    if (/^(5[1-5]|2[2-7])/.test(n)) return "mastercard";
    if (/^3[47]/.test(n)) return "amex";
    if (/^(4011(78|79)|431274|438935|451416|457393|457631|457632|504175|627780|636297|636368)/.test(n)) return "elo";
    if (/^(606282|3841)/.test(n)) return "hipercard";
    if (/^(36|38|30[0-5])/.test(n)) return "diners";
    if (/^(6011|65|64[4-9])/.test(n)) return "discover";
    if (/^(35)/.test(n)) return "jcb";

    return "";
  }

  /**
   * Return the selected payment method input.
   *
   * @returns {HTMLInputElement|null}
   */
  function getSelectedPaymentMethodInput() {
    return document.querySelector('input[name="payment_method"]:checked');
  }

  /**
   * Return the selected payment method value.
   *
   * @returns {string}
   */
  function getSelectedMethod() {
    return getSelectedPaymentMethodInput()?.value || "";
  }

  /**
   * Return metadata for a selected saved card.
   *
   * Expected markup:
   * <input
   *   type="radio"
   *   name="payment_method"
   *   value="credit_card:26"
   *   data-provider-card-id="26"
   *   data-card-bin="516289"
   *   data-card-brand="mastercard"
   * >
   *
   * Also matches "debit_card:ID" -- both methods can be saved and reused
   * (see pagbank_credit_card_process_payment() / pagbank_debit_card_process_payment()).
   *
   * @returns {{input: HTMLInputElement, providerCardId: string, cardBin: string, brand: string}|null}
   */
  function getSelectedSavedCardMeta() {
    const input = getSelectedPaymentMethodInput();
    if (!input) return null;

    const value = String(input.value || "").trim();
    const match = value.match(/^(?:credit_card|debit_card):(.+)$/);

    if (!match || !match[1]) {
      return null;
    }

    return {
      input,
      providerCardId: input.dataset.providerCardId || match[1] || "",
      cardBin: input.dataset.cardBin || "",
      brand: normalizeBrand(input.dataset.cardBrand || "")
    };
  }

  /**
   * Sync hidden field for saved card id, when available.
   *
   * @returns {void}
   */
  function syncSavedCardField() {
    if (!savedCardIdEl) return;

    const savedCard = getSelectedSavedCardMeta();
    savedCardIdEl.value = savedCard?.providerCardId || "";
  }

  /**
   * Load installments for typed card or saved card.
   *
   * @returns {Promise<void>}
   */
  async function loadInstallments() {
    if (!installmentsEl) return;

    const method = getSelectedMethod();

    if (!method || !String(method).startsWith("credit_card")) {
      clearInstallments();
      return;
    }

    let amount = 0;

    try {
      amount = await fetchOrderAmount();
    } catch (err) {
      console.error("Order amount error:", err);
      clearInstallments("Could not load installments");
      return;
    }

    if (!amount || amount <= 0) {
      clearInstallments("No installments available");
      return;
    }

    let creditCardBin = "";
    const savedCard = getSelectedSavedCardMeta();

    if (savedCard && savedCard.cardBin) {
      creditCardBin = String(savedCard.cardBin).replace(/\D+/g, "").slice(0, 6);
    } else {
      const creditNumberEl = getMethodField("card_number", "credit_card");
      const number = String(creditNumberEl?.value || "").replace(/\D+/g, "");
      if (number.length >= 6) {
        creditCardBin = number.slice(0, 6);
      }
    }

    if (creditCardBin.length < 6) {
      clearInstallments(savedCard ? "Could not identify saved card BIN" : "Enter the card number");
      return;
    }

    if (installmentsAbortController) {
      installmentsAbortController.abort();
    }

    installmentsAbortController = new AbortController();

    try {
      clearInstallments("Loading installments...");

      const params = new URLSearchParams({
        amount: String(Math.round(amount * 100)),
        credit_card_bin: creditCardBin,
        max_installments: "18",
        max_installments_no_interest: String(maxNoInterestCache || 1)
      });

      const endpoint = `${BASE_URL}/${REST_API_BASE_ROUTE}/pagbank-installments?${params.toString()}`;

      const res = await fetch(endpoint, {
        method: "GET",
        credentials: "include",
        headers: { "Accept": "application/json" },
        signal: installmentsAbortController.signal
      });

      if (!res.ok) {
        throw new Error("Failed to load installments");
      }

      const json = await res.json();
      const installments = Array.isArray(json?.msg?.plans) ? json.msg.plans : [];

      installmentsEl.innerHTML = "";

      if (!installments.length) {
        clearInstallments("No installments available");
        return;
      }

      installments.forEach(item => {
        const quantity = parseInt(item.installments ?? 0, 10);
        const installmentAmount = Number(item.installment_value ?? 0) / 100;
        const totalAmount = Number(item.amount?.value ?? 0) / 100;
        const hasInterest = !item.interest_free;

        if (!quantity || !installmentAmount) return;

        const option = document.createElement("option");
        option.value = String(quantity);

        const totalLabel = totalAmount ? ` - ${formatMoneyBRL(totalAmount)}` : "";

        if (hasInterest) {
          option.textContent = `${quantity}x ${formatMoneyBRL(installmentAmount)}${totalLabel}`;
        } else {
          option.textContent = `${quantity}x ${formatMoneyBRL(installmentAmount)} - Sem juros`;
        }

        installmentsEl.appendChild(option);
      });

      if (!installmentsEl.value && installments.length) {
        installmentsEl.value = String(installments[0].installments || 1);
      }
    } catch (err) {
      if (err.name === "AbortError") return;
      console.error("Installments error:", err);
      clearInstallments("Could not load installments");
    }
  }

  /**
   * Refresh placeholder based on the selected flow.
   *
   * @returns {void}
   */
  function refreshInstallmentsPlaceholder() {
    const method = getSelectedMethod();

    if (!method || !String(method).startsWith("credit_card")) {
      clearInstallments();
      return;
    }

    const savedCard = getSelectedSavedCardMeta();

    if (savedCard && savedCard.cardBin) {
      clearInstallments("Loading installments...");
      loadInstallments();
      return;
    }

    clearInstallments("Enter the card number");
  }

  /**
   * Simple debounce helper.
   *
   * @param {Function} fn Function to debounce.
   * @param {number} wait Delay in milliseconds.
   * @returns {Function}
   */
  function debounce(fn, wait) {
    let t = null;

    return function (...args) {
      clearTimeout(t);
      t = setTimeout(() => fn.apply(this, args), wait);
    };
  }

  const debouncedLoadInstallments = debounce(loadInstallments, 350);

  if (installmentsEl) {
    refreshInstallmentsPlaceholder();

    const creditNumberElForInstallments = getMethodField("card_number", "credit_card");
    if (creditNumberElForInstallments) {
      creditNumberElForInstallments.addEventListener("input", function () {
        const creditTokenEl = getMethodField("token", "credit_card");
        if (creditTokenEl) creditTokenEl.value = "";

        const savedCard = getSelectedSavedCardMeta();
        if (savedCard) return;

        debouncedLoadInstallments();
      });
    }
  }

  // Always attached, regardless of whether installments exist for this
  // checkout (e.g. plan checkouts never render the installments select) --
  // switching methods should always clear any leftover encrypted token.
  document.querySelectorAll('input[name="payment_method"]').forEach(input => {
    input.addEventListener("change", function () {
      syncSavedCardField();

      form.querySelectorAll('[name="payment_data[token]"], [name="payment_data[debit_token]"]').forEach(el => {
        el.value = "";
      });

      setTimeout(() => {
        refreshInstallmentsPlaceholder();
      }, 0);
    });
  });

  /**
   * Payment methods that need PagSeguro.encryptCard() before submitting.
   * Installments stay credit_card-only below; that's unrelated to tokenization.
   */
  function isTokenizableMethod(method) {
    return String(method).startsWith("credit_card") || String(method).startsWith("debit_card");
  }

  /**
   * Reads the first non-empty value found among several candidate field
   * names.
   *
   * @returns {string}
   */
  function getFirstFormValue(names) {
    for (const name of names) {
      const el = document.querySelector(`[name="${name}"]`);
      if (el && String(el.value || "").trim() !== "") {
        return String(el.value).trim();
      }
    }
    return "";
  }

  /**
   * Splits a raw phone string into PagBank's {country, area, number, type}
   * shape (mirrors pagbank_normalize_phone() on the PHP side).
   *
   * @param {string} raw
   * @returns {{country: string, area: string, number: string, type: string}|null}
   */
  function splitPhone(raw) {
    let digits = String(raw || "").replace(/\D+/g, "");
    if (!digits) return null;

    if (digits.startsWith("55") && digits.length >= 12) digits = digits.slice(2);
    if (digits.length < 10) return null;

    return {
      country: "55",
      area: digits.slice(0, 2),
      number: digits.slice(2),
      type: "MOBILE"
    };
  }

  /**
   * Reads the billing address from the "payment_data[customer_address]"
   * address_form field (see pagbank_debit_card_fields(), only
   * rendered/shown for debit_card) and maps it into the {street, number,
   * complement, regionCode, country, city, postalCode} shape
   * authenticate3DS() expects.
   *
   * Field names confirmed from address_form's own input.php: it always
   * submits `{name}[zipcode]`, `[city]`, `[state]`, `[street]`,
   * `[number]`, `[complement]`, `[district]` -- `district` has no
   * counterpart in authenticate3DS's billingAddress schema, so it's
   * intentionally unused here.
   *
   * @returns {{street: string, number: string, complement: string, regionCode: string, country: string, city: string, postalCode: string}|null}
   */
  function getBillingAddress() {
    const street = getFirstFormValue(["payment_data[customer_address][street]"]);
    const number = getFirstFormValue(["payment_data[customer_address][number]"]);
    const complement = getFirstFormValue(["payment_data[customer_address][complement]"]);
    const regionCode = getFirstFormValue(["payment_data[customer_address][state]"]);
    const city = getFirstFormValue(["payment_data[customer_address][city]"]);
    const postalCode = getFirstFormValue(["payment_data[customer_address][zipcode]"]).replace(/\D+/g, "");

    if (street && number && city && regionCode && postalCode) {
      return { street, number, complement, regionCode, country: "BRA", city, postalCode };
    }

    return null;
  }

  /**
   * Creates an Error whose message is safe (and meant) to show the user
   * directly, distinguished from internal validation errors below (e.g.
   * "Invalid card number") which get a generic fallback message instead.
   */
  function userError(message) {
    const err = new Error(message);
    err.userFacing = true;
    return err;
  }

  /**
   * Runs PagBank's own 3DS authentication flow and returns the resulting
   * authentication id (or null when authentication genuinely doesn't apply
   * and the caller may proceed without one -- only relevant for
   * credit_card, since debit_card requires an id or the /orders call is
   * rejected).
   *
   * The SDK's authenticate3DS() promise only resolves once the ENTIRE flow
   * is done, including showing any challenge UI (SMS code, bank app, etc)
   * -- there's nothing else to wire up here for that part.
   *
   * https://developer.pagbank.com.br/reference/criar-pagar-pedido-com-3ds-validacao-pagbank
   *
   * @returns {Promise<string|null>}
   */
  async function run3DSAuthentication({ amount, cardNumber, expMonth, expYear, holderName, paymentType }) {
    const sessionRes = await fetch(`${BASE_URL}/${REST_API_BASE_ROUTE}/pagbank-3ds-session`, {
      method: "GET",
      credentials: "include",
      headers: { "Accept": "application/json" }
    });

    const sessionJson = await sessionRes.json();

    if (!sessionRes.ok || sessionJson?.code !== "success") {
      throw userError("Não foi possível iniciar a autenticação 3DS.");
    }

    const { session, env, customer } = sessionJson.msg;
    PagSeguro.setUp({ session, env });

    // Logged-in users never see customer[name]/[email]/[phone] -- the
    // checkout only renders that block for guests -- so prefer whatever
    // the server already knows (from tb_users) and only fall back to the
    // DOM for guests actually filling the form in right now.
    const customerName = customer?.name || getFirstFormValue(["customer[name]"]) || holderName;
    const customerEmail = customer?.email || getFirstFormValue(["customer[email]"]);
    const phone = splitPhone(customer?.phone || getFirstFormValue(["customer[phone]"]));
    const address = getBillingAddress();

    if (!customerName || !customerEmail || !phone || !address) {
      console.error("Missing customer/address data for 3DS authentication", {
        customerName, customerEmail, phone, address
      });
      throw userError("Preencha nome, e-mail, telefone e endereço antes de continuar.");
    }

    const request = {
      data: {
        customer: {
          name: customerName,
          email: customerEmail,
          phones: [phone]
        },
        paymentMethod: {
          type: paymentType, // "CREDIT_CARD" | "DEBIT_CARD"
          installments: 1,
          card: {
            number: cardNumber,
            expMonth,
            expYear,
            holder: { name: holderName }
          }
        },
        amount: {
          value: Math.round(amount * 100),
          currency: "BRL"
        },
        billingAddress: address,
        shippingAddress: address,
        dataOnly: false
      }
    };

    let result;
    try {
      result = await PagSeguro.authenticate3DS(request);
    } catch (err) {
      const detailMessage = err?.detail?.message;
      throw userError(detailMessage || "Não foi possível autenticar o cartão via 3DS.");
    }

    if (result.status === "AUTH_FLOW_COMPLETED") {
      return result.id || null;
    }

    if (result.status === "AUTH_NOT_SUPPORTED") {
      // Per PagBank docs: for DEBIT_CARD the transaction must stop here --
      // the card isn't eligible for 3DS, and debit requires it.
      if (paymentType === "DEBIT_CARD") {
        throw userError("Este cartão de débito não é compatível com autenticação 3DS.");
      }
      return null; // credit_card can still proceed without authentication_method
    }

    if (result.status === "CHANGE_PAYMENT_METHOD") {
      throw userError("Não foi possível autenticar esse cartão. Tente outro método de pagamento.");
    }

    return null;
  }

  /**
   * Intercept submit click to tokenize only new cards.
   *
   * Saved cards should not be tokenized again.
   */
  const onClickTokenize = async function (e) {
    const btn = e.target.closest('button[type="submit"], input[type="submit"], [data-submit], [data-send-form]');
    if (!btn || !form.contains(btn)) return;

    const method = getSelectedMethod();

    if (!isTokenizableMethod(method)) {
      return;
    }

    const savedCard = getSelectedSavedCardMeta();
    syncSavedCardField();

    /**
     * Saved card flow:
     * allow normal form submission without tokenization.
     */
    if (savedCard && savedCard.providerCardId) {
      return;
    }

    const tokenInputEl = getMethodField("token", method);
    const numberElActive = getMethodField("card_number", method);
    const holderElActive = getMethodField("name", method);
    const expElActive = getMethodField("expiration", method);
    const cvvElActive = getMethodField("cvv", method);
    const threeDsInputEl = getMethodField("threeds_id", method);

    if (!tokenInputEl) return;

    if (!numberElActive || !holderElActive || !expElActive || !cvvElActive) {
      console.error("Missing card fields for tokenization");
      return;
    }

    if (isSubmitting) {
      e.preventDefault();
      e.stopImmediatePropagation();
      return;
    }

    e.preventDefault();
    e.stopImmediatePropagation();

    // isSubmitting = true;
    // btn.setAttribute("disabled", "true");

    // Whether tokenization made it all the way to handing off to the real
    // submit handler (the synthetic btn.click() below) -- only then should
    // the button stay disabled once this function returns, since from that
    // point on the real handler owns disabling it for as long as its own
    // request is in flight (exactly like it already does, uninterrupted,
    // for the saved-card flow above). Anything short of that -- a
    // validation failure, encryptCard error, failed 3DS -- must re-enable
    // it in `finally` below so the user can fix the form and try again.
    let handedOffToRealSubmit = false;

    try {
      const number = String(numberElActive.value || "").replace(/\D+/g, "");
      const holder = String(holderElActive.value || "").trim();
      const exp = parseExpiration(expElActive.value);
      const cvv = String(cvvElActive.value || "").trim();

      if (!number || number.length < 12) throw new Error("Invalid card number");
      if (!holder) throw new Error("Invalid holder");
      if (!exp) throw new Error("Invalid expiration");
      if (!cvv || cvv.length < 3) throw new Error("Invalid CVV");

      // PagBank requires 3DS authentication for debit_card charges --
      // there's no way around it (see pagbank_debit_card_process_payment()).
      // It's optional for credit_card, so that flow is left untouched.
      if (String(method).startsWith("debit_card") && threeDsInputEl && !threeDsInputEl.value) {
        const amount = await fetchOrderAmount();

        const authId = await run3DSAuthentication({
          amount,
          cardNumber: number,
          expMonth: exp.expMonth,
          expYear: exp.expYear,
          holderName: holder,
          paymentType: "DEBIT_CARD"
        });

        if (authId) {
          threeDsInputEl.value = authId;
        }
      }

      const publicKey = cachedPublicKey ?? await publicKeyPromise;
      if (!publicKey) {
        throw userError("Não foi possível carregar a chave pública do PagBank. Recarregue a página e tente novamente.");
      }

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

      // Always overwrite the token field with the FRESH one just generated
      // -- if an earlier failed attempt (with a different card) left a
      // stale token behind, resubmitting without regenerating it here
      // would silently verify/charge the WRONG card.
      tokenInputEl.value = cardObj.encryptedCard;

      form.removeEventListener("click", onClickTokenize, true);

      // btn.removeAttribute("disabled");
      btn.click();

      form.addEventListener("click", onClickTokenize, true);

      // Hand-off complete: the real submit handler now owns the button's
      // disabled state for as long as its own request is in flight --
      // re-disable immediately (it most likely already did this itself,
      // synchronously, before its own first await; this just guarantees
      // it either way) so `finally` below doesn't undo it a moment later.
      // btn.setAttribute("disabled", "true");
      handedOffToRealSubmit = true;
    } catch (err) {
      console.log("Tokenize error:", err);
      // Errors thrown from run3DSAuthentication() (or the public-key check
      // above) already carry a user-facing pt-BR message; anything else
      // falls back to the generic message.
      const userFacing = err instanceof Error && err.userFacing;
      alert(userFacing ? err.message : "Não foi possível validar o cartão. Verifique os dados e tente novamente.");
    } finally {
      isSubmitting = false;
      if (!handedOffToRealSubmit) {
        // btn.removeAttribute("disabled");
      }
    }
  };

  syncSavedCardField();
  form.addEventListener("click", onClickTokenize, true);
});
