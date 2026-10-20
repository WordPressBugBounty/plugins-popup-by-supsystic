jQuery(document).ready(function () {
  const formSelector = '#ppsgenGenerateContactSubmitForm';
  const submitButtonSelector = '.ppsgenGenerateContactSubmitBtn';
  const autoImportButtonSelector = '.ppsgenContactAutoImportToggleBtn';
  const autoImportStatusSelector = '.ppsgenContactAutoImportStatusText';

  function setFormDataValue(formData, name, value) {
    if (typeof formData.delete === 'function') {
      formData.delete(name);
    }
    formData.append(name, value);
  }

  function setButtonLoadingState(button, isLoading, defaultIconClass) {
    const icon = button.find('.dashicons');
    if (isLoading) {
      button.attr('disabled', 'disabled').prop('disabled', true);
    } else {
      button.removeAttr('disabled').prop('disabled', false);
    }
    icon.removeClass('dashicons-yes-alt dashicons-no-alt dashicons-clock');
    icon.addClass(isLoading ? 'dashicons-clock' : defaultIconClass);
  }

  function updateAutoImportUi(button, enabled) {
    const status = jQuery(autoImportStatusSelector);
    const enableLabel = button.attr('data-enable-label') || '';
    const disableLabel = button.attr('data-disable-label') || '';
    const statusText = enabled ? status.attr('data-enabled-text') : status.attr('data-disabled-text');
    button.attr('data-enabled', enabled ? '1' : '0');
    button.find('.ppsgenAutoImportToggleLabel').text(enabled ? disableLabel : enableLabel);
    status.text(statusText || '');
    setButtonLoadingState(button, false, enabled ? 'dashicons-no-alt' : 'dashicons-yes-alt');
  }

  jQuery(formSelector).on('submit', function (e) {
    e.preventDefault();
    const formData = new FormData(jQuery(formSelector)[0]);
    formData.append('mod', 'contact');
    formData.append('nonce_code', ppsgenAjax.nonce);
    setButtonLoadingState(jQuery(submitButtonSelector), true, 'dashicons-yes-alt');
    jQuery.ajax({
      url: ajaxurl,
      method: 'POST',
      data: formData,
      contentType: false,
      processData: false,
      success: function (response) {
        setButtonLoadingState(jQuery(submitButtonSelector), false, 'dashicons-yes-alt');

        if (response) {
          if (response.success) {
            var msg = response.data && response.data.message ? response.data.message : '';
            jQuery().ppsgenShowPopupNotice({ status: 'success', operation: 'edit', msg: msg });
            setTimeout(() => {
              // location.reload();
            }, 1000);
          } else {
            var msg = response.data && response.data.message ? response.data.message : '';
            jQuery().ppsgenShowPopupNotice({ status: 'error', operation: 'edit', msg: msg });
          }
        }
      },
    });
    return false;
  });

  jQuery(autoImportButtonSelector).on('click', function () {
    const button = jQuery(this);
    const formData = new FormData(jQuery(formSelector)[0]);
    const nextEnabled = button.attr('data-enabled') !== '1';
    setFormDataValue(formData, 'action', 'saveContactAutoImportSettings');
    setFormDataValue(formData, 'mod', 'contact');
    setFormDataValue(formData, 'nonce_code', ppsgenAjax.nonce);
    setFormDataValue(formData, 'autoimport_source', 'wp_user');
    setFormDataValue(formData, 'autoimport_enabled', nextEnabled ? '1' : '0');
    setButtonLoadingState(button, true, nextEnabled ? 'dashicons-no-alt' : 'dashicons-yes-alt');
    jQuery.ajax({
      url: ajaxurl,
      method: 'POST',
      data: formData,
      contentType: false,
      processData: false,
      success: function (response) {
        if (response && response.success) {
          const enabled = !!(response.data && response.data.enabled);
          const msg = response.data && response.data.message ? response.data.message : '';
          updateAutoImportUi(button, enabled);
          jQuery().ppsgenShowPopupNotice({ status: 'success', operation: 'edit', msg: msg });
        } else {
          const msg = response && response.data && response.data.message ? response.data.message : '';
          setButtonLoadingState(button, false, nextEnabled ? 'dashicons-yes-alt' : 'dashicons-no-alt');
          jQuery().ppsgenShowPopupNotice({ status: 'error', operation: 'edit', msg: msg });
        }
      },
      error: function () {
        setButtonLoadingState(button, false, nextEnabled ? 'dashicons-yes-alt' : 'dashicons-no-alt');
        jQuery().ppsgenShowPopupNotice({ status: 'error', operation: 'edit' });
      },
    });
    return false;
  });
});
