// hCaptcha
function ppsHcaptchaApiLoaded() {
  ppsInitHcaptcha();
}
function ppsInitHcaptcha($elements) {
  if (typeof hcaptcha === 'undefined' || typeof hcaptcha.render === 'undefined') {
    // Api script did not load (yet) - will be initialized again on next explicit call
    return;
  }
  $elements = $elements ? $elements : jQuery(document).find('.h-captcha');
  if ($elements && $elements.length) {
    $elements.each(function () {
      var $this = jQuery(this);
      if (typeof $this.data('hcaptcha-widget-id') == 'undefined') {
        var elementId = $this.attr('id');
        if (!elementId) {
          elementId = 'ppsHcaptcha_' + Math.floor(Math.random() * 100000);
          $this.attr('id', elementId);
        }
        $this.data('hcaptcha-widget-id', hcaptcha.render(elementId, { sitekey: $this.data('sitekey') }));
      }
    });
  }
}

// Cloudflare Turnstile
function ppsTurnstileApiLoaded() {
  ppsInitTurnstile();
}
function ppsInitTurnstile($elements) {
  if (typeof turnstile === 'undefined' || typeof turnstile.render === 'undefined') {
    // Api script did not load (yet) - will be initialized again on next explicit call
    return;
  }
  $elements = $elements ? $elements : jQuery(document).find('.cf-turnstile');
  if ($elements && $elements.length) {
    $elements.each(function () {
      var $this = jQuery(this);
      if (typeof $this.data('turnstile-widget-id') == 'undefined') {
        var elementId = $this.attr('id');
        if (!elementId) {
          elementId = 'ppsTurnstile_' + Math.floor(Math.random() * 100000);
          $this.attr('id', elementId);
        }
        $this.data('turnstile-widget-id', turnstile.render(elementId, { sitekey: $this.data('sitekey') }));
      }
    });
  }
}

// Google reCaptcha v3 - invisible, no widget to render. Fetch a token eagerly and
// keep refreshing it in the background (tokens are single-use and last ~120s), so a
// fresh token already sits in the field's hidden input by the time the user submits.
// The popup subscribe form submits synchronously via sendFormPps() so token fetching
// cannot happen "just in time" on submit.
var g_ppsRecaptchaV3RefreshMs = 100000;
function ppsInitRecaptchaV3($elements) {
  $elements = $elements ? $elements : jQuery(document).find('.ppsRecaptchaV3');
  if (!$elements || !$elements.length) {
    return;
  }
  $elements.each(function () {
    var $this = jQuery(this);
    if ($this.data('recaptcha-v3-started')) {
      return;
    }
    $this.data('recaptcha-v3-started', true);
    var sitekey = $this.data('sitekey'),
      action = $this.data('action') || 'pps_form_submit',
      $hidden = $this.siblings('.ppsRecaptchaV3Value');
    var fetchToken = function () {
      if (typeof grecaptcha === 'undefined' || typeof grecaptcha.execute === 'undefined') {
        setTimeout(fetchToken, 500);
        return;
      }
      grecaptcha.ready(function () {
        grecaptcha.execute(sitekey, { action: action }).then(function (token) {
          $hidden.val(token);
        });
      });
    };
    fetchToken();
    setInterval(fetchToken, g_ppsRecaptchaV3RefreshMs);
  });
}

jQuery(document).ready(function () {
  // hCaptcha/Turnstile initialize themselves via their own API onload callbacks
  // (ppsHcaptchaApiLoaded/ppsTurnstileApiLoaded above). reCaptcha v3 has no such
  // widget callback - it just needs its token-fetch loop kicked off once.
  ppsInitRecaptchaV3();
});
