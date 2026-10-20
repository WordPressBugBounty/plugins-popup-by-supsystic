function ppsgenOnloadCallback() {
  if (window.ppsgenRecaptchaCallbacks) {
    window.ppsgenRecaptchaCallbacks.forEach(function (cb) {
      cb();
    });
  }
}

window.ppsgenFormController = 'form';

window.ppsgenHandleFormSubmit = function (form) {
  const $form = jQuery(form);
  const submitBtn = $form.find('button[type="submit"]');
  const formVarName = $form.data('form-var');
  const formDataObj = window[formVarName];

  if (!formDataObj || !formDataObj.nonce_code || !formDataObj.form_id) return;

  let valid = true;
  jQuery('.ppsgen-form-inputError').remove();
  const handled = {};
  $form.find('input[type="checkbox"][data-set-required="true"]').each(function () {
    const name = jQuery(this).attr('name');
    if (handled[name]) return;
    handled[name] = true;
    const $group = $form.find('input[name="' + name + '"]');
    const isChecked = $group.is(':checked');
    if (!isChecked) {
      valid = false;
      const $wrapper = $group.first().closest('.ppsgen-form-input-wrapper');
      if ($wrapper.length) {
        $wrapper.append('<div class="ppsgen-form-inputError">' + ppsgenFrontendAjax.cfvNotices.required + '</div>');
      }
    }
  });
  $form.find('select[data-set-required="true"]').each(function () {
    const $select = jQuery(this);
    const name = $select.attr('name');
    if (handled[name]) return;
    handled[name] = true;
    const val = $select.val();
    if (!val || val === '' || val.length == 0) {
      valid = false;
      const $wrapper = $select.closest('.ppsgen-form-input-wrapper');
      if ($wrapper.length) {
        $wrapper.append('<div class="ppsgen-form-inputError">' + ppsgenFrontendAjax.cfvNotices.required + '</div>');
      }
    }
  });
  const recaptchaEl = $form.find('.g-recaptcha');
  if (recaptchaEl.length) {
    const captchaResponse = $form.find('[name="g-recaptcha-response"]').val();
    if (!captchaResponse) {
      let $captchaInput = $form.find('input[name="captcha"]');
      if (!$captchaInput.length) {
        $captchaInput = recaptchaEl.first();
      }
      const $wrapper = $captchaInput.closest('.ppsgen-form-input-wrapper');
      if ($wrapper.length) {
        $wrapper.append('<div class="ppsgen-form-inputError">' + ppsgenFrontendAjax.cfvNotices.captchaRequired + '</div>');
      }
      valid = false;
    }
  }
  if (!valid) return false;

  const formData = new FormData(form);
  formData.append('plugin', ppsgenFrontendAjax.prefix);
  formData.append('mod', window.ppsgenFormController);
  formData.append('action', 'frontendFormSubmit');
  formData.append('nonce_code', formDataObj.nonce_code);
  formData.append('form_id', formDataObj.form_id);

  submitBtn.prop('disabled', true);

  jQuery.ajax({
    url: ppsgenFrontendAjax.url,
    method: 'POST',
    data: formData,
    contentType: false,
    processData: false,
    success: function (response) {
      submitBtn.prop('disabled', false);
      if (response.success) {
        const popup = $form.closest('.ppsgenPopup');
        if (popup.length) {
          popup.trigger('ppsgenFormSuccess', formDataObj.form_id);
        }
        let msg = response.data?.message || '';
        let redirect = response.data?.redirect || '';
        $form.find('.ppsgen-form-field-container').hide();
        $form.find('.ppsgenFormSubmitWrapper').fadeIn();
        const formTop = $form.offset().top;
        const formHeight = $form.outerHeight();
        const windowHeight = jQuery(window).height();
        const scrollTo = formTop - windowHeight / 2 + formHeight / 2;
        jQuery('html, body').animate(
          {
            scrollTop: scrollTo,
          },
          600
        );
        if (redirect) {
          setTimeout(() => {
            window.location.href = redirect;
          }, 2000);
        }
      } else {
        const recaptchaEl = $form.find('.g-recaptcha, .ppsgen-invisible-recaptcha');
        if (recaptchaEl.length) {
          const widgetId = $form.data('ppsgenWidgetId');
          if (typeof grecaptcha !== 'undefined' && widgetId !== undefined) {
            grecaptcha.reset(widgetId);
          }
        }
        let msg = response.data?.message || '';
        let errorFields = response.data?.fields_error || {};
        jQuery.each(errorFields, function (fieldName, errorText) {
          let $input = $form.find('[name*="[' + fieldName + ']"]');
          if ($input.length) {
            let $wrapper = $input.closest('.ppsgen-form-input-wrapper');
            if ($wrapper.length) {
              $wrapper.append('<div class="ppsgen-form-inputError">' + errorText + '</div>');
            }
          }
        });
      }
    },
  });
};

jQuery(document).ready(function () {
  setTimeout(() => {
    jQuery('.ppsgenFormWrapper').fadeIn();
    ppsgenRecalcTooltips();
  }, 50);

  jQuery('.ppsgenForm').on('submit', function (e) {
    const $form = jQuery(this);
    const hasInvisibleRecaptcha = $form.find('.ppsgen-invisible-recaptcha').length > 0;
    const hasToken = $form.find('[name="g-recaptcha-response"]').val();

    if (hasInvisibleRecaptcha && !hasToken) {
      e.preventDefault();
      const widgetId = $form.data('ppsgenWidgetId');
      if (typeof grecaptcha !== 'undefined' && widgetId !== undefined) {
        grecaptcha.execute(widgetId);
      } else {
        console.error('reCAPTCHA widgetId cant found or Recaptcha not inited');
      }
      return;
    }

    e.preventDefault();
    window.ppsgenHandleFormSubmit(this);
    return false;
  });
});

function ppsgenRecalcTooltips() {
  jQuery('.ppsgen-form-inputTooltipText').each(function () {
    const $tooltip = jQuery(this);
    const $container = $tooltip.closest('.ppsgen-form-field-container');
    const containerWidth = $container.innerWidth();
    const tooltipOffsetLeft = $tooltip.position().left;
    const availableWidth = containerWidth - tooltipOffsetLeft - 30;
    $tooltip.css('max-width', availableWidth + 'px');
  });
}

jQuery(document).on('ppsgenPopupShowAction', function () {
  setTimeout(() => {
    ppsgenRecalcTooltips();
  }, 100);
});

jQuery(document).ready(function () {
  ppsgenRecalcTooltips();
});

jQuery(window).on('resize', function () {
  ppsgenRecalcTooltips();
});
