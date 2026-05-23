(function(){
  if (typeof lvLoginizerModal === 'undefined') {
    return;
  }

  var REGISTER_HTML = lvLoginizerModal.registerHtml || '';
  var LABEL_LOGIN   = lvLoginizerModal.labelLogin || 'LOGIN WITH GOOGLE';
  var LABEL_REG     = lvLoginizerModal.labelRegister || 'SIGN UP WITH GOOGLE';

  function qs(sel, root){ return (root||document).querySelector(sel); }
  function qsa(sel, root){ return Array.prototype.slice.call((root||document).querySelectorAll(sel)); }

  function getPanel(){
    return qs('#account-modal.ct-panel.active')
      || qs('#account-modal.ct-panel')
      || qs('.ct-panel[data-behaviour="modal"]');
  }

  function placeAfterSubmit(social, submitRow){
    if (!social || !submitRow) return;
    if (submitRow.nextElementSibling === social) return;
    submitRow.insertAdjacentElement('afterend', social);
  }

  function setLabelHard(btn, labelText){
    if (!btn) return;

    var logo = btn.querySelector('.loginizer-social-btn-logo');
    if (logo) logo = logo.cloneNode(true);

    btn.innerHTML = '';

    if (logo) btn.appendChild(logo);

    var span = document.createElement('span');
    span.className = 'lv-google-label';
    span.textContent = labelText;
    btn.appendChild(span);
  }

  function ensureLabels(modal){
    if (!modal) return;

    qsa('.ct-login-form .loginizer-social-button', modal).forEach(function(btn){
      setLabelHard(btn, LABEL_LOGIN);
    });

    qsa('.ct-register-form .loginizer-social-button', modal).forEach(function(btn){
      setLabelHard(btn, LABEL_REG);
    });
  }

  function ensureLoginPlacement(modal){
    var loginForm = qs('.ct-login-form form#loginform.login, .ct-login-form form.login, form#loginform.login', modal);
    if (!loginForm) return;

    var submitRow = qs('.login-submit', loginForm);
    if (!submitRow) return;

    var social = qs('#lz-social-login-btns', loginForm);
    if (!social) return;

    placeAfterSubmit(social, submitRow);
  }

  function ensureRegisterPlacement(modal){
    var regForm = qs('.ct-register-form form#registerform.register, .ct-register-form form.register, form#registerform.register', modal);
    if (!regForm) return;

    var submitBtn = qs(
      'button.ct-account-register-submit, button[name="wp-submit"], button[type="submit"], input[type="submit"]',
      regForm
    );

    var submitRow = submitBtn ? (submitBtn.closest('p') || submitBtn.parentElement) : null;
    if (!submitRow) return;

    var existing = qs('#lz-social-login-btns-register', regForm) || qs('#lz-social-login-btns', regForm);

    if (!existing) {
      if (!REGISTER_HTML || !REGISTER_HTML.trim()) return;

      if (!qs('.lv-social-insert', regForm)) {
        var wrap = document.createElement('div');
        wrap.className = 'lv-social-insert';
        wrap.innerHTML = REGISTER_HTML;

        var social = qs('#lz-social-login-btns-register', wrap) || qs('#lz-social-login-btns', wrap);

        if (social) {
          submitRow.insertAdjacentElement('afterend', social);
        } else {
          submitRow.insertAdjacentElement('afterend', wrap);
        }
      }

      existing = qs('#lz-social-login-btns-register', regForm) || qs('#lz-social-login-btns', regForm);
    } else {
      placeAfterSubmit(existing, submitRow);
    }
  }

  function run(){
    var panel = getPanel();
    if (!panel) return;

    var modal = qs('.ct-account-modal', panel);
    if (!modal) return;

    ensureLoginPlacement(modal);
    ensureRegisterPlacement(modal);
    ensureLabels(modal);
  }

  var t = null;

  function schedule(){
    if (t) clearTimeout(t);

    t = setTimeout(function(){
      t = null;
      run();
    }, 80);
  }

  if (document.readyState === 'loading'){
    document.addEventListener('DOMContentLoaded', schedule);
  } else {
    schedule();
  }

  document.addEventListener('click', function(e){
    if (
      e.target.closest(
        '#account-modal, .ct-account-modal, .ct-header-account, [data-account-trigger], .ct-login, .ct-register'
      )
    ) {
      schedule();
    }
  });

  var tries = 0;

  var iv = setInterval(function(){
    tries++;
    run();

    if (tries >= 30) {
      clearInterval(iv);
    }
  }, 100);

})();