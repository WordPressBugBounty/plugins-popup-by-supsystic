// Newsletter editor: send panel above the campaign tabs
jQuery(document).ready(function ($) {
  const $panel = $('.ppsgenNewsletterPanel');
  if (!$panel.length) {
    return;
  }
  let busy = false;

  $panel.on('click', '.ppsgenNewsletterOpenOptions', function (e) {
    e.preventDefault();
    $('.ppsgenObjEditTabButton[data-tab="options"]').trigger('click');
  });

  $panel.on('click', '.ppsgenNewsletterAction', function (e) {
    e.preventDefault();
    if (busy) {
      return;
    }
    const $btn = $(this);
    const action = $btn.data('action');
    const data = {
      action: action,
      mod: 'eca',
      plugin: 'ppsgen',
      chain_id: $panel.data('chain-id'),
      nonce_code: ppsgenAjax.nonce,
    };
    if (action === 'scheduleNewsletter') {
      data.send_at = $panel.find('.ppsgenNewsletterScheduleInput').val();
      if (!data.send_at) {
        $panel.find('.ppsgenNewsletterScheduleInput').trigger('focus');
        return;
      }
    }
    if ($btn.data('confirm') && !window.confirm($btn.data('confirm'))) {
      return;
    }
    busy = true;
    $btn.find('.dashicons').addClass('dashicons-clock');
    $.post(ajaxurl, data)
      .done(function (response) {
        const ok = !!(response && response.success);
        const msg = response && response.data && response.data.message ? response.data.message : '';
        jQuery().ppsgenShowPopupNotice({ status: ok ? 'success' : 'error', msg: msg, fadeOutTime: ok ? 4000 : 8000 });
        // The test send leaves the newsletter as it is; every other action changes its status
        if (ok && action !== 'sendNewsletterTest') {
          window.setTimeout(function () {
            window.location.reload();
          }, 1200);
          return;
        }
        busy = false;
        $btn.find('.dashicons').removeClass('dashicons-clock');
      })
      .fail(function () {
        jQuery().ppsgenShowPopupNotice({ status: 'error', fadeOutTime: 8000 });
        busy = false;
        $btn.find('.dashicons').removeClass('dashicons-clock');
      });
  });
});

// "Send to": a saved contact list or the tag rules
jQuery(document).ready(function ($) {
  $('.ppsgenNewsletterListSelect').on('change', function () {
    const $select = $(this);
    $select.prop('disabled', true);
    $.post(ajaxurl, {
      action: 'setNewsletterList',
      mod: 'eca',
      plugin: 'ppsgen',
      chain_id: $select.closest('.ppsgenNewsletterPanel').data('chain-id'),
      list_id: $select.val(),
      nonce_code: ppsgenAjax.nonce,
    })
      .done(function (response) {
        if (response && response.success) {
          window.location.reload();
          return;
        }
        jQuery().ppsgenShowPopupNotice({ status: 'error', msg: (response && response.data && response.data.message) || '', fadeOutTime: 6000 });
        $select.prop('disabled', false);
      })
      .fail(function () {
        jQuery().ppsgenShowPopupNotice({ status: 'error', fadeOutTime: 6000 });
        $select.prop('disabled', false);
      });
  });
});

// Newsletter settings save themselves: preview text, reply-to, UTM tracking and the click tag
jQuery(document).ready(function ($) {
  const $panel = $('.ppsgenNewsletterPanel');
  const $saved = $panel.find('.ppsgenNlSaved');
  const l10n = window.ppsgenNewsletterL10n || {};
  const show = (state, msg) => {
    $saved.attr('data-state', state).text(msg || (state === 'busy' ? l10n.saving || 'Saving…' : state === 'ok' ? l10n.saved || 'Saved' : ''));
  };
  const post = (data) => $.post(ajaxurl, Object.assign({ mod: 'eca', plugin: 'ppsgen', chain_id: $panel.data('chain-id'), nonce_code: ppsgenAjax.nonce }, data));
  const saveExtras = () =>
    post({
      action: 'setNewsletterExtras',
      preview_text: $panel.find('.ppsgenNewsletterPreviewText').val() || '',
      reply_to: $panel.find('.ppsgenNewsletterReplyTo').val() || '',
      utm: $panel.find('.ppsgenNewsletterUtm').is(':checked') ? 1 : 0,
    });
  const saveClickTag = () => post({ action: 'setNewsletterClickTag', tag: $panel.find('.ppsgenNewsletterClickTag').val() || '' });

  $panel.on('change', '.ppsgenNlAutosave', function () {
    const $field = $(this);
    if ($field.is('[type="email"]') && $field.val() && !this.checkValidity()) {
      show('error', l10n.badEmail || this.validationMessage);
      return;
    }
    show('busy');
    ($field.is('.ppsgenNewsletterClickTag') ? saveClickTag() : saveExtras())
      .done(function (response) {
        const ok = !!(response && response.success);
        show(ok ? 'ok' : 'error', ok ? '' : (response && response.data && response.data.message) || l10n.error || 'Not saved');
      })
      .fail(function () {
        show('error', l10n.error || 'Not saved');
      });
  });
  // Enter in a text field saves it too
  $panel.on('keydown', 'input.ppsgenNlAutosave', function (e) {
    if (e.key === 'Enter') {
      e.preventDefault();
      $(this).trigger('change');
    }
  });
});

// A newsletter has exactly one email: open it straight away instead of showing a list of steps
jQuery(document).ready(function ($) {
  const $form = $('#ppsgenEditObjFormChain.ppsgenNewsletterEditor');
  const $steps = $form.find('.ppsgenChainStep');
  if ($steps.length !== 1) {
    return;
  }
  $form.addClass('ppsgenNewsletterSingleEmail');
  const l10n = window.ppsgenNewsletterL10n || {};
  if (l10n.saveEmail) {
    $form.find('.ppsgenChainStepBtnSave').html('<span class="dashicons dashicons-yes-alt"></span> ' + $('<span>').text(l10n.saveEmail).html());
  }
  // Same as clicking the step, minus the "unsaved data" question: nothing is open yet
  // (after every other ready handler, so the step editor is listening)
  window.setTimeout(function () {
    const original = window.confirm;
    window.confirm = () => true;
    try {
      $steps.first().trigger('click');
    } finally {
      window.confirm = original;
    }
  }, 0);
});
