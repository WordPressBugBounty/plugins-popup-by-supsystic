jQuery(document).ready(function () {
  jQuery(document).on('click', '.ppsgenDashboardMailConfigDeleteBtn', function (e) {
    e.preventDefault();
    var btn = jQuery(this);
    var configId = parseInt(btn.data('config-id'), 10) || 0;
    var confirmText = btn.data('confirm') || '';
    if (!configId) {
      return;
    }
    if (confirmText && !window.confirm(confirmText)) {
      return;
    }
    btn.prop('disabled', true);
    jQuery.post(ajaxurl, {
      action: 'deleteMailConfig',
      mod: 'dashboard',
      plugin: 'ppsgen',
      config_id: configId,
      nonce_code: ppsgenAjax.nonce,
    }, function (response) {
      btn.prop('disabled', false);
      if (response && response.success) {
        location.reload();
        return;
      }
      jQuery().ppsgenShowPopupNotice({ status: 'error', msg: response && response.data && response.data.message ? response.data.message : '' });
    }).fail(function () {
      btn.prop('disabled', false);
      jQuery().ppsgenShowPopupNotice({ status: 'error' });
    });
  });
});
