jQuery(document).ready(function () {
  function setFieldValue(form, fieldName, value) {
    var field = form.find('[name="' + fieldName + '"]');
    if (field.length) {
      field.val(value);
    }
  }

  function getFieldValue(form, fieldName) {
    var field = form.find('[name="' + fieldName + '"]');
    if (!field.length) {
      return '';
    }
    return jQuery.trim(String(field.val() || ''));
  }

  function setFieldValueIfEmptyOrKnown(form, fieldName, value, knownValues) {
    var currentValue = getFieldValue(form, fieldName).toLowerCase();
    var normalizedKnownValues = jQuery.map(knownValues || [], function (knownValue) {
      return jQuery.trim(String(knownValue || '')).toLowerCase();
    });
    if (currentValue === '' || normalizedKnownValues.indexOf(currentValue) !== -1) {
      setFieldValue(form, fieldName, value);
    }
  }

  function applyZohoPreset() {
    var form = jQuery('#ppsgenDashboardMailConfigForm');
    var provider = form.find('[name="data[mail_config][settings][provider_type]"]').val();
    if (provider !== 'zoho_mail') {
      return;
    }
    var accountType = form.find('[name="data[mail_config][settings][zoho_account_type]"]').val() || 'business_domain';
    var smtpHost = accountType === 'personal_free' ? 'smtp.zoho.com' : 'smtppro.zoho.com';
    var imapHost = accountType === 'personal_free' ? 'imap.zoho.com' : 'imappro.zoho.com';
    setFieldValueIfEmptyOrKnown(form, 'data[mail_config][settings][zoho_smtp_server_name]', smtpHost, ['smtp.zoho.com', 'smtppro.zoho.com']);
    setFieldValueIfEmptyOrKnown(form, 'data[mail_config][settings][zoho_imap_server_name]', imapHost, ['imap.zoho.com', 'imappro.zoho.com']);
    setFieldValueIfEmptyOrKnown(form, 'data[mail_config][settings][zoho_smtp_port]', '465', ['465']);
    setFieldValueIfEmptyOrKnown(form, 'data[mail_config][settings][zoho_imap_port]', '993', ['993']);
    setFieldValueIfEmptyOrKnown(form, 'data[mail_config][settings][zoho_smtp_secure]', 'ssl', ['ssl']);
  }

  function applyFastmailPreset() {
    var form = jQuery('#ppsgenDashboardMailConfigForm');
    var provider = form.find('[name="data[mail_config][settings][provider_type]"]').val();
    if (provider !== 'fastmail') {
      return;
    }
    var profile = form.find('[name="data[mail_config][settings][fastmail_connection_type]"]').val() || 'standard';
    var smtpHost = profile === 'proxy' ? 'smtps-proxy.fastmail.com' : 'smtp.fastmail.com';
    var imapHost = profile === 'proxy' ? 'imaps-proxy.fastmail.com' : 'imap.fastmail.com';
    setFieldValueIfEmptyOrKnown(form, 'data[mail_config][settings][fastmail_smtp_server_name]', smtpHost, ['smtp.fastmail.com', 'smtps-proxy.fastmail.com']);
    setFieldValueIfEmptyOrKnown(form, 'data[mail_config][settings][fastmail_imap_server_name]', imapHost, ['imap.fastmail.com', 'imaps-proxy.fastmail.com']);
    setFieldValueIfEmptyOrKnown(form, 'data[mail_config][settings][fastmail_smtp_port]', profile === 'proxy' ? '443' : '465', ['443', '465']);
    setFieldValueIfEmptyOrKnown(form, 'data[mail_config][settings][fastmail_imap_port]', profile === 'proxy' ? '443' : '993', ['443', '993']);
    setFieldValueIfEmptyOrKnown(form, 'data[mail_config][settings][fastmail_smtp_secure]', 'ssl', ['ssl']);
  }

  function isProProviderSelected() {
    var provider = jQuery('#ppsgenDashboardMailConfigForm').find('[name="data[mail_config][settings][provider_type]"]').val();
    return provider === 'microsoft_365' || provider === 'google_workspace' || provider === 'zoho_mail' || provider === 'fastmail' || provider === 'sendgrid' || provider === 'mailgun' || provider === 'brevo' || provider === 'postmark' || provider === 'smtp2go' || provider === 'resend' || provider === 'amazon_ses';
  }

  function hasProviderApiFields() {
    var form = jQuery('#ppsgenDashboardMailConfigForm');
    var provider = form.find('[name="data[mail_config][settings][provider_type]"]').val();
    if (provider === 'google_workspace') {
      return form.find('[name="data[mail_config][settings][client_id]"]').length > 0;
    }
    if (provider === 'sendgrid') {
      return form.find('[name="data[mail_config][settings][api_key]"]').length > 0;
    }
    if (provider === 'mailgun') {
      return form.find('[name="data[mail_config][settings][mailgun_api_key]"]').length > 0;
    }
    if (provider === 'brevo') {
      return form.find('[name="data[mail_config][settings][brevo_api_key]"]').length > 0;
    }
    if (provider === 'postmark') {
      return form.find('[name="data[mail_config][settings][postmark_server_token]"]').length > 0;
    }
    if (provider === 'smtp2go') {
      return form.find('[name="data[mail_config][settings][smtp2go_api_key]"]').length > 0;
    }
    if (provider === 'resend') {
      return form.find('[name="data[mail_config][settings][resend_api_key]"]').length > 0;
    }
    if (provider === 'amazon_ses') {
      return form.find('[name="data[mail_config][settings][ses_access_key_id]"]').length > 0;
    }
    if (provider === 'zoho_mail') {
      return form.find('[name="data[mail_config][settings][zoho_account_type]"]').length > 0;
    }
    if (provider === 'fastmail') {
      return form.find('[name="data[mail_config][settings][fastmail_connection_type]"]').length > 0;
    }
    if (provider === 'microsoft_365') {
      return form.find('[name="data[mail_config][settings][m365_client_id]"]').length > 0;
    }
    return false;
  }

  function providerSupportsMailboxTracking(provider) {
    return provider === 'custom_smtp_imap' || provider === 'google_workspace' || provider === 'microsoft_365' || provider === 'zoho_mail' || provider === 'fastmail';
  }

  function renderProProviderNotice(provider) {
    var row = jQuery('.ppsgenDashboardMailConfigProNoticeRow');
    var boxes = row.find('.ppsgenDashboardMailConfigProNotice');
    var activeBox = boxes.filter('[data-provider="' + provider + '"]');
    if (!row.length || !boxes.length) {
      return;
    }
    boxes.hide();
    if (!provider || !activeBox.length) {
      row.hide();
      return;
    }
    activeBox.show();
    row.show();
  }

  function renderMailConfigTestResult(response, isSuccess) {
    var box = jQuery('.ppsgenDashboardMailConfigTestResult');
    if (!box.length) {
      return;
    }
    var data = response && response.data ? response.data : response;
    var html = '';
    var title = data && data.message ? data.message : '';
    if (title) {
      html += '<div><strong>' + title + '</strong></div>';
    }
    if (data && data.smtp) {
      html += '<div style="margin-top:8px;"><strong>SMTP:</strong> ' + (data.smtp.message || '') + '</div>';
      if (data.smtp.debug) {
        html += '<div style="margin-top:4px;font-size:11px;line-height:1.5;word-break:break-word;"><strong>SMTP Debug:</strong> ' + data.smtp.debug + '</div>';
      }
    }
    if (data && data.imap) {
      html += '<div style="margin-top:6px;"><strong>IMAP:</strong> ' + (data.imap.message || '') + '</div>';
      if (data.imap.debug) {
        html += '<div style="margin-top:4px;font-size:11px;line-height:1.5;word-break:break-word;"><strong>IMAP Debug:</strong> ' + data.imap.debug + '</div>';
      }
    }
    if (data && data.api) {
      html += '<div style="margin-top:6px;"><strong>API:</strong> ' + (data.api.message || '') + '</div>';
    }
    box
      .removeClass('ppsgenDashboardMailConfigTestResultSuccess ppsgenDashboardMailConfigTestResultError')
      .addClass(isSuccess ? 'ppsgenDashboardMailConfigTestResultSuccess' : 'ppsgenDashboardMailConfigTestResultError')
      .html(html)
      .show();
  }

  function syncHiddenToggleInputs(form) {
    form.find('.ppsgenComponentRow[data-toggle-name]').each(function () {
      var row = jQuery(this);
      var inputs = row.find(':input');
      if (!inputs.length) {
        return;
      }
      if (row.is(':visible')) {
        inputs.removeAttr('disabled').prop('disabled', false);
      } else {
        inputs.attr('disabled', 'disabled').prop('disabled', true);
      }
    });
  }

  function toggleValsInclude(row, value) {
    var values = row.data('toggle-val');
    var toggleVals = [];
    if (typeof values === 'string') {
      toggleVals = values.split(',').map(function (val) {
        return jQuery.trim(String(val));
      });
    } else if (typeof values === 'number') {
      toggleVals = [String(values)];
    }
    return toggleVals.indexOf(String(value)) !== -1;
  }

  function enableActiveProviderFields(form) {
    var providerFieldName = 'data[mail_config][settings][provider_type]';
    var provider = form.find('[name="' + providerFieldName + '"]').val();
    form.find('.ppsgenComponentRow[data-toggle-name="' + providerFieldName + '"]').each(function () {
      var row = jQuery(this);
      if (toggleValsInclude(row, provider) && row.is(':visible')) {
        row.find(':input').removeAttr('disabled').prop('disabled', false);
      }
    });
  }

  function syncMailConfigInputState(form) {
    syncAutoBounceRows();
    syncHiddenToggleInputs(form);
    enableActiveProviderFields(form);
  }

  function scheduleMailConfigInputStateSync(form) {
    window.setTimeout(function () {
      syncMailConfigInputState(form);
    }, 0);
  }

  function syncAutoBounceRows() {
    var form = jQuery('#ppsgenDashboardMailConfigForm');
    var provider = form.find('[name="data[mail_config][settings][provider_type]"]').val();
    var mailMethod = form.find('[name="data[mail_config][settings][mail_method]"]').val();
    var autoBounceEnabled = form.find('[name="data[mail_config][settings][auto_bounce_enabled]"]').is(':checked');
    var createUnknownContactsEnabled = form.find('[name="data[mail_config][settings][auto_bounce_create_unknown_contacts]"]').is(':checked');
    var baseRows = jQuery('.ppsgenDashboardMailConfigMailboxTrackingRow');
    var detailsRows = jQuery('.ppsgenDashboardMailConfigAutoBounceDetailRow');
    var unknownContactRows = jQuery('.ppsgenDashboardMailConfigAutoBounceUnknownContactDetailRow');
    var sectionTitle = jQuery('.ppsgenDashboardMailConfigMailboxTrackingTitle');
    var bounceTitle = jQuery('.ppsgenDashboardMailConfigAutoBounceSubTitle');
    var unknownContactTitle = jQuery('.ppsgenDashboardMailConfigAutoBounceUnknownContactSubTitle');
    var supportsMailboxTracking = providerSupportsMailboxTracking(provider) && (provider !== 'custom_smtp_imap' || mailMethod === 'smtp');
    var autoProcessingVisible = supportsMailboxTracking && autoBounceEnabled;

    sectionTitle.toggle(supportsMailboxTracking);
    baseRows.toggle(supportsMailboxTracking);
    detailsRows.toggle(autoProcessingVisible);
    bounceTitle.toggle(autoProcessingVisible);
    unknownContactTitle.toggle(autoProcessingVisible);
    unknownContactRows.toggle(autoProcessingVisible && createUnknownContactsEnabled);
  }

  jQuery('.ppsgenDashboardMailConfigSaveBtn').on('click', function () {
    jQuery('#ppsgenDashboardMailConfigForm').trigger('submit');
  });

  jQuery('.ppsgenDashboardMailConfigTestBtn').on('click', function (e) {
    e.preventDefault();
    var btn = jQuery(this);
    var form = jQuery('#ppsgenDashboardMailConfigForm');
    syncMailConfigInputState(form);
    var formData = form.serialize();
    formData += '&action=testMailConfigConnection&nonce_code=' + ppsgenAjax.nonce;
    btn.attr('disabled', 'disabled').prop('disabled', true);
    btn.find('.dashicons').removeClass('dashicons-update-alt').addClass('dashicons-clock');
    jQuery.post(ajaxurl, formData, function (response) {
      btn.removeAttr('disabled').prop('disabled', false);
      btn.find('.dashicons').removeClass('dashicons-clock').addClass('dashicons-update-alt');
      renderMailConfigTestResult(response, !!(response && response.success));
    }).fail(function () {
      btn.removeAttr('disabled').prop('disabled', false);
      btn.find('.dashicons').removeClass('dashicons-clock').addClass('dashicons-update-alt');
      renderMailConfigTestResult({ data: { message: 'Connection check request failed.' } }, false);
    });
    return false;
  });

  var form = jQuery('#ppsgenDashboardMailConfigForm');
  var providerField = form.find('[name="data[mail_config][settings][provider_type]"]');

  form.on('change', '[name="data[mail_config][settings][provider_type]"]', function () {
    var box = jQuery('.ppsgenDashboardMailConfigTestResult');
    var smtpRows = jQuery('.ppsgenDashboardMailConfigCustomSmtpRow');
    if (box.length) {
      box.hide().empty().removeClass('ppsgenDashboardMailConfigTestResultSuccess ppsgenDashboardMailConfigTestResultError');
    }
    if (isProProviderSelected()) {
      renderProProviderNotice(jQuery(this).val());
      smtpRows.hide();
      smtpRows.find(':input').prop('disabled', true);
      applyZohoPreset();
      applyFastmailPreset();
      syncMailConfigInputState(form);
      scheduleMailConfigInputStateSync(form);
      if (hasProviderApiFields()) {
        jQuery('.ppsgenDashboardMailConfigTestBtn').find('.dashicons').removeClass('dashicons-lock dashicons-clock').addClass('dashicons-update-alt');
      } else {
        jQuery('.ppsgenDashboardMailConfigTestBtn').find('.dashicons').removeClass('dashicons-update-alt dashicons-clock').addClass('dashicons-lock');
      }
    } else {
      renderProProviderNotice('');
      smtpRows.find(':input').removeAttr('disabled').prop('disabled', false);
      jQuery('#ppsgenDashboardMailConfigForm').find('[name="data[mail_config][settings][mail_method]"]').trigger('change');
      syncMailConfigInputState(form);
      scheduleMailConfigInputStateSync(form);
      jQuery('.ppsgenDashboardMailConfigTestBtn').find('.dashicons').removeClass('dashicons-lock dashicons-clock').addClass('dashicons-update-alt');
    }
  });

  form.on('change', '[name="data[mail_config][settings][mail_method]"]', function () {
    syncMailConfigInputState(form);
    // The SMTP rows are shown by the generic toggle after this handler: sync again once they are visible
    window.setTimeout(function () {
      syncMailConfigInputState(form);
    }, 50);
  });

  form.on('change', '[name="data[mail_config][settings][auto_bounce_enabled]"]', function () {
    syncMailConfigInputState(form);
  });

  form.on('change', '[name="data[mail_config][settings][auto_bounce_create_unknown_contacts]"]', function () {
    syncMailConfigInputState(form);
  });

  form.on('change', '[name="data[mail_config][settings][zoho_account_type]"]', function () {
    applyZohoPreset();
  });

  form.on('change', '[name="data[mail_config][settings][fastmail_connection_type]"]', function () {
    applyFastmailPreset();
  });

  if (providerField.length) {
    providerField.trigger('change');
  }
  syncMailConfigInputState(form);

  jQuery('#ppsgenDashboardMailConfigForm').on('submit', function (e) {
    e.preventDefault();
    var form = jQuery(this);
    syncMailConfigInputState(form);
    var formData = form.serialize();
    formData += '&nonce_code=' + ppsgenAjax.nonce;
    jQuery('.ppsgenDashboardMailConfigSaveBtn').attr('disabled', 'disabled').prop('disabled', true);
    jQuery('.ppsgenDashboardMailConfigSaveBtn').find('.dashicons').toggleClass('dashicons-yes-alt').toggleClass('dashicons-clock');
    jQuery.post(ajaxurl, formData, function (response) {
      jQuery('.ppsgenDashboardMailConfigSaveBtn').removeAttr('disabled').prop('disabled', false);
      jQuery('.ppsgenDashboardMailConfigSaveBtn').find('.dashicons').toggleClass('dashicons-yes-alt').toggleClass('dashicons-clock');
      if (response && response.success) {
        window.location.href = ppsgenAjax.admin_url + '?page=ppsgen&tab=dashboard_mail_configs';
        return;
      }
      jQuery().ppsgenShowPopupNotice({ status: 'error', msg: response && response.data && response.data.message ? response.data.message : '' });
    }).fail(function () {
      jQuery('.ppsgenDashboardMailConfigSaveBtn').removeAttr('disabled').prop('disabled', false);
      jQuery('.ppsgenDashboardMailConfigSaveBtn').find('.dashicons').toggleClass('dashicons-yes-alt').toggleClass('dashicons-clock');
      jQuery().ppsgenShowPopupNotice({ status: 'error' });
    });
    return false;
  });
});

// Guided SMTP set-up: the provider preset fills server, ports and encryption, and shows where to get an app password
jQuery(document).on('change', '.ppsgenSmtpPreset', function () {
  const $select = jQuery(this);
  const presets = $select.data('presets') || {};
  const preset = presets[$select.val()];
  const $form = $select.closest('form');
  const $help = $select.siblings('.ppsgenSmtpPresetHelp');
  if (!preset) {
    $help.hide();
    return;
  }
  const set = function (name, value) {
    $form.find('[name="data[mail_config][settings][' + name + ']"]').val(value).trigger('change');
  };
  set('smtp_server_name', preset.smtp);
  set('imap_server_name', preset.imap);
  set('smtp_port', preset.port);
  set('imap_port', 993);
  set('smtp_secure', preset.secure);
  $help.find('.ppsgenSmtpPresetHelpText').text(preset.help || '');
  $help.find('.ppsgenSmtpPresetHelpLink').attr('href', preset.url || '#').toggle(!!preset.url);
  $help.show();
});
