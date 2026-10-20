jQuery(document).ready(function () {
  jQuery('.ppsgenTableListDeleteAll').on('click', function (e) {
    e.preventDefault();
    if (!confirm(ppsgenAjax.confirmDeleteText)) {
      return;
    }
    var button = jQuery(this);
    button.prop('disabled', true);
    jQuery.post(
      ajaxurl,
      {
        action: 'deleteFlowLogAll',
        mod: 'eca',
        plugin: 'ppsgen',
        nonce_code: ppsgenAjax.nonce,
      },
      function (response) {
        if (response && response.success) {
          jQuery('.ppsgenTableListSelectCheckbox').each(function () {
            jQuery(this).closest('tr').remove();
          });
        }
      }
    );
  });
  jQuery('.ppsgenTableListDeleteSelectedObj').on('click', function (e) {
    e.preventDefault();
    if (!confirm(ppsgenAjax.confirmDeleteText)) {
      return;
    }
    var button = jQuery(this);
    button.prop('disabled', true);
    var objs = [];
    jQuery('.ppsgenTableListSelectCheckbox:checked').each(function () {
      var obj = jQuery(this).data('obj');
      if (obj !== undefined) {
        objs.push(obj);
      }
    });
    if (objs.length === 0) {
      button.prop('disabled', false);
      return;
    }
    jQuery.post(
      ajaxurl,
      {
        action: 'deleteFlowsLogsList',
        mod: 'eca',
        plugin: 'ppsgen',
        objs: objs,
        nonce_code: ppsgenAjax.nonce,
      },
      function (response) {
        if (response && response.success) {
          jQuery('.ppsgenTableListSelectCheckbox:checked').each(function () {
            jQuery(this).closest('tr').remove();
          });
        }
      }
    );
  });
  jQuery('.ppsgenTableListDeleteObjChain').on('click', function (e) {
    e.preventDefault();
    if (!confirm(ppsgenAjax.confirmDeleteText)) {
      return;
    }
    var button = jQuery(this);
    button.prop('disabled', true);
    var obj = button.parent().data('obj');
    jQuery.post(
      ajaxurl,
      {
        action: 'deleteFlowsLogs',
        mod: 'eca',
        plugin: 'ppsgen',
        obj: obj,
        nonce_code: ppsgenAjax.nonce,
      },
      function (response) {
        if (response && response.success) {
          button.closest('tr').remove();
        }
      }
    );
  });

  jQuery(document).on('click', '.ppsgenDeleteUndeliveredBtn', function (e) {
    e.preventDefault();
    var button = jQuery(this);
    if (button.prop('disabled')) {
      return;
    }
    var confirmText = button.data('confirm') || '';
    if (confirmText && !confirm(confirmText)) {
      return;
    }
    button.prop('disabled', true);
    jQuery
      .post(ajaxurl, {
        action: 'deleteUndeliveredEmails',
        mod: 'eca',
        plugin: 'ppsgen',
        nonce_code: ppsgenAjax.nonce,
      })
      .done(function (response) {
        if (!response || response.success !== true) {
          var errorMessage = response && response.data && response.data.message ? response.data.message : 'Unknown error';
          showChainNotice('error', 'edit', errorMessage);
          return;
        }
        if (!response.data || !response.data.data) {
          showChainNotice('error', 'edit', 'Invalid response');
          return;
        }
        var stats = response.data.data;
        var message = 'Total found: ' + (stats.total_found || 0) + '\n' + 'Total processed: ' + (stats.processed || 0) + '\n' + 'Total bounced: ' + (stats.bounced || 0) + '\n' + 'Total tagged: ' + (stats.tagged || 0);
        showChainNotice('success', 'edit', message);
        button.prop('disabled', false);
      })
      .fail(function () {
        showChainNotice('success', 'edit', 'Request Failed');
        button.prop('disabled', false);
      })
      .always(function () {
        button.prop('disabled', false);
      });
  });

  function showChainNotice(status, operation, msg, fadeOutTime = 2000) {
    const escapedMsg = jQuery('<div>').text(msg).html();
    if (typeof jQuery().ppsgenShowPopupNotice === 'function') {
      jQuery().ppsgenShowPopupNotice({ status, operation, msg: escapedMsg, fadeOutTime });
    } else {
      console.warn('ppsgenShowPopupNotice is not defined. Displaying fallback alert.');
      alert(`jQuery{status.toUpperCase()}: jQuery{escapedMsg}`);
    }
  }
});
