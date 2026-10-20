// silence is golden
jQuery(document).ready(function () {
  var formSelector = '#ppsgenEcaOptionsForm';
  var saveButtonSelector = '.ppsgenEcaOptionsSaveBtn';

  function setButtonsBusy(isBusy) {
    var buttons = jQuery(saveButtonSelector);
    if (isBusy) {
      buttons.attr('disabled', 'disabled').prop('disabled', true);
      buttons.find('.dashicons').removeClass('dashicons-yes-alt').addClass('dashicons-clock');
    } else {
      buttons.removeAttr('disabled').prop('disabled', false);
      buttons.find('.dashicons').removeClass('dashicons-clock').addClass('dashicons-yes-alt');
    }
  }

  jQuery(saveButtonSelector).on('click', function (e) {
    e.preventDefault();
    jQuery(formSelector).trigger('submit');
  });

  jQuery(formSelector).on('submit', function (e) {
    e.preventDefault();
    var formData = jQuery(formSelector).serialize();
    formData += '&nonce_code=' + encodeURIComponent(ppsgenAjax.nonce);
    setButtonsBusy(true);
    jQuery.post(ajaxurl, formData, function (response) {
      setButtonsBusy(false);
      if (response && response.success && response.data && response.data.message) {
        jQuery().ppsgenShowPopupNotice({ status: 'success', operation: 'edit', msg: response.data.message });
      } else {
        var msg = response && response.data && response.data.message ? response.data.message : '';
        jQuery().ppsgenShowPopupNotice({ status: 'error', operation: 'edit', msg: msg });
      }
    }).fail(function () {
      setButtonsBusy(false);
      jQuery().ppsgenShowPopupNotice({ status: 'error', operation: 'edit' });
    });
    return false;
  });
});
