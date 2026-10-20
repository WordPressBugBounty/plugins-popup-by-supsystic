jQuery(document).ready(function () {
  var formSelector = '#ppsgenContactOptionsForm';
  var saveButtonSelector = '.ppsgenContactOptionsSaveBtn';
  var manualScanButtonSelector = '.ppsgenContactManualSuspiciousScanBtn';

  function setButtonLoadingState(buttons, isLoading, iconClass) {
    if (isLoading) {
      buttons.attr('disabled', 'disabled').prop('disabled', true);
    } else {
      buttons.removeAttr('disabled').prop('disabled', false);
    }
    buttons.find('.dashicons').removeClass('dashicons-yes-alt dashicons-search dashicons-clock').addClass(isLoading ? 'dashicons-clock' : iconClass);
  }

  function setOptionsButtonsBusy(isBusy, activeButtonSelector) {
    setButtonLoadingState(jQuery(saveButtonSelector), isBusy, 'dashicons-yes-alt');
    setButtonLoadingState(jQuery(manualScanButtonSelector), isBusy, 'dashicons-search');
    if (isBusy && activeButtonSelector) {
      var activeButtons = jQuery(activeButtonSelector);
      activeButtons.find('.dashicons').removeClass('dashicons-yes-alt dashicons-search').addClass('dashicons-clock');
    }
  }

  function serializeOptionsForm(actionName) {
    var form = jQuery(formSelector);
    var actionField = form.find('input[name="action"]');
    var originalAction = actionField.val();
    var regExpField = form.find('input[name*="[phone_validator_reg_exp]"]');
    var regExpValueBefore = regExpField.val();
    actionField.val(actionName);
    regExpField.backslashEncode();
    var formData = form.serialize();
    formData += '&nonce_code=' + encodeURIComponent(ppsgenAjax.nonce);
    regExpField.val(regExpValueBefore);
    actionField.val(originalAction);
    return formData;
  }

  jQuery(saveButtonSelector).on('click', function () {
    jQuery(formSelector).trigger('submit');
  });

  jQuery(formSelector).on('submit', function (e) {
    e.preventDefault();
    var formData = serializeOptionsForm('editcontactOptions');
    setOptionsButtonsBusy(true, saveButtonSelector);
    jQuery.post(ajaxurl, formData, function (response) {
      setOptionsButtonsBusy(false);
      if (response && response.success && response.data && response.data.message) {
        jQuery().ppsgenShowPopupNotice({ status: 'success', operation: 'edit', msg: response.data.message });
      } else if (response && !response.success && response.data && response.data.message) {
        jQuery().ppsgenShowPopupNotice({ status: 'error', operation: 'edit', msg: response.data.message });
      }
    }).fail(function () {
      setOptionsButtonsBusy(false);
      jQuery().ppsgenShowPopupNotice({ status: 'error', operation: 'edit' });
    });
    return false;
  });

  jQuery(manualScanButtonSelector).on('click', function (e) {
    e.preventDefault();
    var formData = serializeOptionsForm('runManualSuspiciousActivityScan');
    setOptionsButtonsBusy(true, manualScanButtonSelector);
    jQuery.post(ajaxurl, formData, function (response) {
      setOptionsButtonsBusy(false);
      if (response && response.success) {
        var msg = response.data && response.data.message ? response.data.message : '';
        jQuery().ppsgenShowPopupNotice({ status: 'success', operation: 'edit', msg: msg });
      } else {
        var msg = response && response.data && response.data.message ? response.data.message : '';
        jQuery().ppsgenShowPopupNotice({ status: 'error', operation: 'edit', msg: msg });
      }
    }).fail(function () {
      setOptionsButtonsBusy(false);
      jQuery().ppsgenShowPopupNotice({ status: 'error', operation: 'edit' });
    });
    return false;
  });
});
