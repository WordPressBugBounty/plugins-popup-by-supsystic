jQuery(document).ready(function ($) {
  function post(data) {
    return $.post(ajaxurl, $.extend({ mod: 'dashboard', plugin: 'ppsgen', nonce_code: ppsgenAjax.nonce }, data));
  }

  // Go live; on Free a running automation is switched off only after the owner agrees
  function setPlayLive(data) {
    return post($.extend({ action: 'playSetLive' }, data)).then(function (response) {
      const conflict = response && !response.success && response.data && response.data.conflict;
      if (conflict && !data.replace && window.confirm(response.data.message)) {
        return setPlayLive($.extend({}, data, { replace: 1 }));
      }
      if (conflict) {
        response.data.cancelled = true;
      }
      return response;
    });
  }

  function notifyError(response) {
    if (response && response.data && response.data.cancelled) {
      return;
    }
    jQuery().ppsgenShowPopupNotice({
      status: 'error',
      msg: (response && response.data && response.data.message) || '',
      fadeOutTime: 8000,
    });
  }

  // Setup: live preview and "What will happen" follow the answers
  const $form = $('.ppsgenPlayForm');
  if ($form.length) {
    const triggerNotes = {
      delay: 'Shows 8 seconds after a page loads.',
      scroll: 'Shows when a visitor scrolls halfway down a page.',
      exit: 'Shows when a visitor is about to leave.',
      none: 'No popup: place the form on a page with its shortcode.',
    };

    function currentAnswers() {
      const answers = {};
      $form.find('[name^="answers["]').each(function () {
        const $field = $(this);
        const key = $field.attr('name').replace(/^answers\[/, '').replace(/\]$/, '');
        if ($field.is(':checkbox')) {
          answers[key] = $field.is(':checked') ? 1 : 0;
        } else if ($field.is(':radio')) {
          if ($field.is(':checked')) {
            answers[key] = $field.val();
          }
        } else {
          answers[key] = $field.val();
        }
      });
      return answers;
    }

    // Previews show what the recipient sees: placeholders become a sample name
    function fill(template, answers) {
      return String(template || '')
        .replace(/\{([a-z0-9_]+)\}/g, function (match, key) {
          return Object.prototype.hasOwnProperty.call(answers, key) ? answers[key] : match;
        })
        .replace(/\{\{\s*contact\.first_name\|([^}]*)\}\}/g, '$1')
        .split('[first_name]')
        .join('Alex');
    }

    function refresh() {
      const answers = currentAnswers();
      $form.find('[data-template]').each(function () {
        $(this).text(fill($(this).data('template'), answers));
      });
      $form.find('[data-when]').each(function () {
        const value = answers[$(this).data('when')];
        $(this).toggle(!!value && value !== '0');
      });
      ['ask_name', 'ask_phone', 'ask_message'].forEach(function (name) {
        $form.find('.ppsgenPlayPreviewField[data-field="' + name + '"]').toggle(!!answers[name]);
      });
      const trigger = answers.trigger || 'delay';
      $form.find('.ppsgenPlayPreviewPopup').toggle(trigger !== 'none');
      $form.find('.ppsgenPlayPreviewNote').text(answers.trigger ? triggerNotes[trigger] || '' : '');
    }

    $form.on('input change', 'input, select, textarea', refresh);
    refresh();

    $form.on('click', '.ppsgenPlaySave', function (e) {
      e.preventDefault();
      const $btn = $(this);
      const build = $btn.data('build') ? 1 : 0;
      if (build && !$form[0].reportValidity()) {
        return;
      }
      const label = $btn.text();
      $form.find('.ppsgenPlaySave').prop('disabled', true);
      if ($btn.data('busy')) {
        $btn.text($btn.data('busy'));
      }
      const data = $form.serializeArray();
      data.push({ name: 'action', value: 'playSave' });
      data.push({ name: 'mod', value: 'dashboard' });
      data.push({ name: 'plugin', value: 'ppsgen' });
      data.push({ name: 'nonce_code', value: ppsgenAjax.nonce });
      data.push({ name: 'type', value: $form.data('type') });
      data.push({ name: 'play_id', value: $form.data('play-id') || '' });
      data.push({ name: 'build', value: build });
      $.post(ajaxurl, $.param(data))
        .done(function (response) {
          if (response && response.success && response.data && response.data.url) {
            window.location.href = response.data.url;
            return;
          }
          notifyError(response);
          $form.find('.ppsgenPlaySave').prop('disabled', false);
          $btn.text(label);
        })
        .fail(function () {
          notifyError();
          $form.find('.ppsgenPlaySave').prop('disabled', false);
          $btn.text(label);
        });
    });
  }

  // Review: send the owner a test enquiry (the real emails, marked [Test])
  $('.ppsgenPlayTestEnquiry').on('click', function () {
    const $btn = $(this);
    const label = $btn.text();
    $btn.prop('disabled', true).text($btn.data('busy') || label);
    post({ action: 'playSendTestEnquiry', play_id: $btn.data('play-id') })
      .done(function (response) {
        jQuery().ppsgenShowPopupNotice({
          status: response && response.success ? 'success' : 'error',
          msg: (response && response.data && response.data.message) || '',
          fadeOutTime: 8000,
        });
      })
      .fail(function () {
        notifyError();
      })
      .always(function () {
        $btn.prop('disabled', false).text(label);
      });
  });

  // Review: Go live
  $('.ppsgenPlaySetLive').on('click', function (e) {
    e.preventDefault();
    const $btn = $(this);
    $btn.prop('disabled', true).text($btn.data('busy') || $btn.text());
    setPlayLive({ play_id: $btn.closest('[data-play-id]').data('play-id'), live: 1 })
      .done(function (response) {
        if (response && response.success && response.data && response.data.url) {
          window.location.href = response.data.url;
          return;
        }
        notifyError(response);
        $btn.prop('disabled', false);
      })
      .fail(function () {
        notifyError();
        $btn.prop('disabled', false);
      });
  });

  // Summary: Live / Paused switch and "Mark replied"
  const $summary = $('.ppsgenPlaySummary');
  $summary.on('change', '.ppsgenPlayLiveSwitch .ppsgenEditorLiveInput', function () {
    const $input = $(this);
    const live = $input.is(':checked') ? 1 : 0;
    $input.prop('disabled', true);
    setPlayLive({ play_id: $summary.data('play-id'), live: live })
      .done(function (response) {
        if (response && response.success) {
          window.location.reload();
          return;
        }
        notifyError(response);
        $input.prop('checked', !live).prop('disabled', false);
      })
      .fail(function () {
        notifyError();
        $input.prop('checked', !live).prop('disabled', false);
      });
  });

  $summary.on('click', '.ppsgenPlayMarkStopped', function (e) {
    e.preventDefault();
    const $link = $(this);
    post({ action: 'playMarkStopped', play_id: $summary.data('play-id'), contact_id: $link.data('contact-id') })
      .done(function (response) {
        if (response && response.success) {
          $link.closest('td').html($('<span class="ppsgenChip"></span>').text(response.data.label || ''));
          return;
        }
        notifyError(response);
      })
      .fail(function () {
        notifyError();
      });
  });

  // Plays list: remove a play record (its parts stay)
  $('.ppsgenPlayDelete').on('click', function (e) {
    e.preventDefault();
    const $link = $(this);
    if (!window.confirm($link.data('confirm'))) {
      return;
    }
    const $row = $link.closest('tr');
    post({ action: 'playDelete', play_id: $row.data('play-id') }).done(function (response) {
      if (response && response.success) {
        $row.remove();
        jQuery().ppsgenShowPopupNotice({ status: 'success', msg: response.data.message, fadeOutTime: 5000 });
      }
    });
  });
});
