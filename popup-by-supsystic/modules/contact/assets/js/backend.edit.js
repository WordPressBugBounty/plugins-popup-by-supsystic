jQuery(document).ready(function () {
  jQuery('.ppsgenObjEditSaveBtn').on('click', function () {
    const form = document.getElementById('ppsgenEditObjForm');
    if (form.reportValidity()) {
      jQuery(form).trigger('submit');
    }
  });

  window.PpsgenContactController = 'contact';

  jQuery('#ppsgenEditObjForm').on('submit', function (e) {
    let valid = true;
    jQuery('.ppsgen-form-inputError').remove();
    const handled = {};
    jQuery('input[type="checkbox"][data-set-required="true"]', this).each(function () {
      const name = jQuery(this).attr('name');
      if (handled[name]) return;
      handled[name] = true;
      const $group = jQuery(`input[name="${name}"]`, e.target);
      const isChecked = $group.is(':checked');
      if (!isChecked) {
        valid = false;
        const $wrapper = $group.first().closest('.ppsgen-form-input-wrapper');
        if ($wrapper.length) {
          $wrapper.append('<div class="ppsgen-form-inputError">' + ppsgenAjax.cfvNotices.required + '</div>');
        }
      }
    });

    if (!valid) {
      e.preventDefault();
      return false;
    }

    e.preventDefault();
    const formData = new FormData(jQuery('#ppsgenEditObjForm')[0]);
    formData.append('mod', window.PpsgenContactController);
    formData.append('nonce_code', ppsgenAjax.nonce);

    jQuery('.ppsgenObjEditSaveBtn').attr('disabled', 'disabled').prop('disabled', true);
    jQuery('.ppsgenObjEditSaveBtn').find('.dashicons').toggleClass('dashicons-yes-alt').toggleClass('dashicons-clock');

    jQuery.ajax({
      url: ajaxurl,
      method: 'POST',
      data: formData,
      contentType: false,
      processData: false,
      success: function (response) {
        jQuery('.ppsgenObjEditSaveBtn').removeAttr('disabled').prop('disabled', false);
        jQuery('.ppsgenObjEditSaveBtn').find('.dashicons').toggleClass('dashicons-yes-alt').toggleClass('dashicons-clock');

        if (response) {
          if (response.success) {
            var msg = response.data && response.data.message ? response.data.message : '';
            jQuery().ppsgenShowPopupNotice({ status: 'success', operation: 'edit', msg: msg });
            var url = '';
            if (response.data && response.data.obj_id) {
              url = ppsgenAjax.admin_url + '?page=ppsgen&tab=contact_edit&id=' + response.data.obj_id;
            }
            setTimeout(() => {
              if (url) {
                location.href = url;
              } else {
                location.reload();
              }
            }, 1000);
          } else {
            var msg = response.data && response.data.message ? response.data.message : '';
            var errorFields = response.data && response.data.fields_error ? response.data.fields_error : {};
            jQuery('.ppsgen-form-inputError').remove();
            jQuery.each(errorFields, function (fieldName, errorText) {
              var $input = jQuery('[name*="[' + fieldName + ']"]');
              if ($input.length) {
                var $wrapper = $input.closest('.ppsgen-form-input-wrapper');
                if ($wrapper.length) {
                  $wrapper.append('<div class="ppsgen-form-inputError">' + errorText + '</div>');
                }
              }
            });
            jQuery().ppsgenShowPopupNotice({ status: 'error', operation: 'edit', msg: msg });
          }
        }
      },
    });

    return false;
  });

  jQuery(document).ready(function () {
    Inputmask.extendDefinitions(ppsgenAjax.cfvInputMaskDef);
    jQuery('input[data-mask]').each(function () {
      const mask = jQuery(this).data('mask');
      if (mask) {
        new Inputmask({
          mask: mask,
          regex: true,
        }).mask(this);
      }
    });
  });

  jQuery(document).on('click', '.ppsgenContactCampaignFlowAction', function (e) {
    e.preventDefault();
    const btn = jQuery(this);
    const action = btn.data('action');
    const flowId = parseInt(btn.data('flow-id'), 10) || 0;
    const confirmText = btn.data('confirm') || '';

    if (!action || !flowId) {
      return;
    }
    if (confirmText && !window.confirm(confirmText)) {
      return;
    }

    btn.prop('disabled', true);

    jQuery.post(
      ajaxurl,
      {
        action: action,
        mod: 'eca',
        plugin: 'ppsgen',
        flow_id: flowId,
        nonce_code: ppsgenAjax.nonce,
      },
      function (response) {
        btn.prop('disabled', false);
        if (response && response.success) {
          var msg = response.data && response.data.message ? response.data.message : '';
          jQuery().ppsgenShowPopupNotice({ status: 'success', msg: msg });
          setTimeout(function () {
            location.reload();
          }, 700);
          return;
        }
        var err = response && response.data && response.data.message ? response.data.message : '';
        jQuery().ppsgenShowPopupNotice({ status: 'error', msg: err });
      }
    ).fail(function () {
      btn.prop('disabled', false);
      jQuery().ppsgenShowPopupNotice({ status: 'error' });
    });
  });

  jQuery(document).on('click', '.ppsgenContactFieldHistoryBtn', function (e) {
    e.preventDefault();
    const btn = jQuery(this);
    const fieldId = parseInt(btn.data('field-id'), 10) || 0;
    if (!fieldId) {
      return;
    }
    const panel = jQuery('.ppsgenContactFieldHistoryPanel[data-field-id="' + fieldId + '"]');
    if (!panel.length) {
      return;
    }
    panel.stop(true, true).slideToggle(180);
    btn.toggleClass('ppsgenContactFieldHistoryBtnActive');
  });

  jQuery(document).on('click', '.ppsgenContactLogsClearBtn', function (e) {
    e.preventDefault();
    const btn = jQuery(this);
    const contactId = parseInt(btn.data('contact-id'), 10) || 0;
    const confirmText = btn.data('confirm') || '';
    if (!contactId) {
      return;
    }
    if (confirmText && !window.confirm(confirmText)) {
      return;
    }

    btn.prop('disabled', true);

    jQuery.post(
      ajaxurl,
      {
        action: 'clearContactFieldsAndTagsLogs',
        mod: 'contact',
        plugin: 'ppsgen',
        contact_id: contactId,
        nonce_code: ppsgenAjax.nonce,
      },
      function (response) {
        btn.prop('disabled', false);
        if (response && response.success) {
          const msg = response.data && response.data.message ? response.data.message : '';
          jQuery().ppsgenShowPopupNotice({ status: 'success', msg: msg });
          setTimeout(function () {
            location.reload();
          }, 500);
          return;
        }
        const err = response && response.data && response.data.message ? response.data.message : '';
        jQuery().ppsgenShowPopupNotice({ status: 'error', msg: err });
      }
    ).fail(function () {
      btn.prop('disabled', false);
      jQuery().ppsgenShowPopupNotice({ status: 'error' });
    });
  });

  function ppsgenGetContactEmailEditorContent(editorId) {
    if (window.tinyMCE && window.tinyMCE.get(editorId)) {
      return window.tinyMCE.get(editorId).getContent();
    }
    var textarea = document.getElementById(editorId);
    return textarea ? textarea.value : '';
  }

  function ppsgenSetContactEmailEditorContent(editorId, content) {
    if (window.tinyMCE && window.tinyMCE.get(editorId)) {
      window.tinyMCE.get(editorId).setContent(content);
    }
    var textarea = document.getElementById(editorId);
    if (textarea) {
      textarea.value = content;
    }
  }

  function ppsgenBuildContactReplySubject(subject) {
    var normalizedSubject = jQuery.trim(String(subject || ''));
    if (!normalizedSubject || normalizedSubject === '(No subject)') {
      return '';
    }
    return /^re\s*:/i.test(normalizedSubject) ? normalizedSubject : ('Re: ' + normalizedSubject);
  }

  function ppsgenSyncContactIncomingIndicators(summary) {
    var unhandledTotal = summary && parseInt(summary.all_unhandled_total, 10) > 0 ? parseInt(summary.all_unhandled_total, 10) : 0;
    var hasUnhandled = unhandledTotal > 0;
    jQuery('.ppsgenContactIncomingIndicator').toggleClass('is-active', !!hasUnhandled);
    jQuery('.ppsgenContactIncomingIndicatorBadge.ppsgenContactEditTabIncomingBadge').each(function () {
      var badge = jQuery(this);
      var singularTitle = badge.data('singular-title') || 'new incoming email needs attention';
      var pluralTitle = badge.data('plural-title') || 'new incoming emails need attention';
      var emptyTitle = badge.data('empty-title') || 'No new incoming emails need attention';
      badge.toggleClass('is-active', !!hasUnhandled);
      if (hasUnhandled) {
        var titleText = unhandledTotal === 1 ? singularTitle : pluralTitle;
        badge.attr('title', unhandledTotal + ' ' + titleText);
      } else {
        badge.attr('title', emptyTitle);
      }
    });
  }

  function ppsgenUpdateContactIncomingSummary(summaryHtml, summary) {
    var wrap = jQuery('.ppsgenContactEmailIncomingSummaryWrap');
    if (wrap.length) {
      wrap.html(summaryHtml || '');
    }
    ppsgenSyncContactIncomingIndicators(summary || {});
  }

  function ppsgenLoadContactEmailThread(forceReload) {
    var loadingText = (window.ppsgenAjax && ppsgenAjax.loadingText) ? ppsgenAjax.loadingText : 'Loading...';
    var errorText = (window.ppsgenAjax && ppsgenAjax.nonceNotice) ? ppsgenAjax.nonceNotice : 'Request failed.';
    var page = jQuery('.ppsgenContactEmailChatPage');
    if (!page.length) {
      return;
    }
    var contactId = parseInt(page.data('contact-id'), 10) || 0;
    var mailConfigId = jQuery('#ppsgenContactEmailMailConfig').length ? (parseInt(jQuery('#ppsgenContactEmailMailConfig').val(), 10) || 0) : 0;
    var thread = page.find('.ppsgenContactEmailThread');
    if (!contactId || !thread.length) {
      return;
    }
    if (!forceReload && String(thread.attr('data-loaded')) === '1') {
      return;
    }
    thread.attr('data-loaded', 'loading').html(
      '<div class="ppsgenContactEmailThreadLoader"><span class="dashicons dashicons-update-alt"></span>' +
      loadingText +
      '</div>'
    );
    jQuery.post(
      ajaxurl,
      {
        action: 'loadContactEmailThread',
        mod: 'contact',
        plugin: 'ppsgen',
        contact_id: contactId,
        mail_config_id: mailConfigId,
        nonce_code: ppsgenAjax.nonce,
      },
      function (response) {
        if (response && response.success) {
          thread.html(response.data && response.data.html ? response.data.html : '');
          thread.attr('data-loaded', '1');
          ppsgenUpdateContactIncomingSummary(
            response.data && response.data.incoming_summary_html ? response.data.incoming_summary_html : '',
            response.data && response.data.incoming_summary ? response.data.incoming_summary : {}
          );
          var subjectInput = jQuery('#ppsgenContactEmailSubject');
          if (subjectInput.length && !jQuery.trim(subjectInput.val()) && response.data && response.data.suggested_subject) {
            subjectInput.val(response.data.suggested_subject);
          }
          return;
        }
        thread.attr('data-loaded', '0').html(
          '<div class="ppsgenContactCampaignsEmpty ppsgenFormSubmitsWrapperWarning">' +
          (response && response.data && response.data.message ? response.data.message : '') +
          '</div>'
        );
      }
    ).fail(function () {
      thread.attr('data-loaded', '0').html(
        '<div class="ppsgenContactCampaignsEmpty ppsgenFormSubmitsWrapperWarning">' +
        errorText +
        '</div>'
      );
    });
  }

  function ppsgenOpenContactEmailModal(html) {
    var modal = jQuery('.ppsgenContactEmailModal');
    if (!modal.length) {
      return;
    }
    var target = modal.find('.ppsgenContactEmailModalFrameTarget');
    if (target.length) {
      target.empty();
      var frame = jQuery('<iframe>', {
        'class': 'ppsgenContactEmailModalFrame',
        sandbox: 'allow-same-origin allow-popups allow-top-navigation-by-user-activation',
      });
      target.append(frame);
      if (frame[0]) {
        // Written like the editors' previews (ppsgenFrameWrite): a srcdoc frame loads no files on WordPress Playground
        if (window.ppsgenFrameWrite) {
          window.ppsgenFrameWrite(frame[0], ppsgenPrepareContactEmailModalHtml(html || ''));
        } else {
          frame[0].srcdoc = ppsgenPrepareContactEmailModalHtml(html || '');
        }
      }
    }
    modal.addClass('is-active').attr('aria-hidden', 'false');
  }

  function ppsgenPrepareContactEmailModalHtml(html) {
    var content = String(html || '');
    var styleTag = '' +
      '<style>' +
      'html,body{background:#ffffff !important;color:#0f172a !important;}' +
      'body{margin:0;padding:0;font-family:Arial,sans-serif;box-sizing:border-box;-webkit-text-size-adjust:100%;}' +
      'body *{box-sizing:border-box;}' +
      'a{color:#8cb1ff !important;}' +
      '.ppsgenContactEmailModalBody{background:#ffffff !important;color:#0f172a !important;padding:0;min-height:100vh;}' +
      '.ppsgenContactEmailModalBody .body{background:#ffffff !important;color:#0f172a !important;}' +
      '.ppsgenContactEmailModalBody .body, .ppsgenContactEmailModalBody .body p, .ppsgenContactEmailModalBody .body div, .ppsgenContactEmailModalBody .body span, .ppsgenContactEmailModalBody .body td, .ppsgenContactEmailModalBody .body th, .ppsgenContactEmailModalBody .body li, .ppsgenContactEmailModalBody .body blockquote, .ppsgenContactEmailModalBody .body pre, .ppsgenContactEmailModalBody .body h1, .ppsgenContactEmailModalBody .body h2, .ppsgenContactEmailModalBody .body h3, .ppsgenContactEmailModalBody .body h4, .ppsgenContactEmailModalBody .body h5, .ppsgenContactEmailModalBody .body h6{color:#0f172a !important;}' +
      '.ppsgenContactEmailModalBody a{color:#1d4ed8 !important;}' +
      '</style>';

    if (!jQuery.trim(content)) {
      return '<!doctype html><html><head>' + styleTag + '</head><body><div class="ppsgenContactEmailModalBody"></div></body></html>';
    }

    if (!/<html[\s>]/i.test(content)) {
      return '<!doctype html><html><head>' + styleTag + '</head><body><div class="ppsgenContactEmailModalBody">' + content + '</div></body></html>';
    }

    if (/<head[\s>]/i.test(content)) {
      content = content.replace(/<head([^>]*)>/i, '<head$1>' + styleTag);
    } else {
      content = content.replace(/<html([^>]*)>/i, '<html$1><head>' + styleTag + '</head>');
    }

    if (/<body[\s>]/i.test(content) && !/ppsgenContactEmailModalBody/i.test(content)) {
      content = content.replace(/<body([^>]*)>/i, '<body$1><div class="ppsgenContactEmailModalBody">');
      if (/<\/body>/i.test(content)) {
        content = content.replace(/<\/body>/i, '</div></body>');
      } else {
        content += '</div>';
      }
    }

    return content;
  }

  function ppsgenCloseContactEmailModal() {
    var modal = jQuery('.ppsgenContactEmailModal');
    if (!modal.length) {
      return;
    }
    var target = modal.find('.ppsgenContactEmailModalFrameTarget');
    if (target.length) {
      target.empty();
    }
    modal.removeClass('is-active').attr('aria-hidden', 'true');
  }

  jQuery(document).on('click', '.ppsgenObjEditTabButton[data-tab="email_chat"]', function () {
    setTimeout(function () {
      ppsgenLoadContactEmailThread(false);
    }, 20);
  });

  if (jQuery('.ppsgenObjEditTabButtonActive[data-tab="email_chat"]').length) {
    ppsgenLoadContactEmailThread(false);
  }

  jQuery(document).on('click', '.ppsgenContactEmailRefreshBtn', function (e) {
    e.preventDefault();
    ppsgenLoadContactEmailThread(true);
  });

  jQuery(document).on('click', '.ppsgenContactEmailMarkHandledBtn', function (e) {
    e.preventDefault();
    var btn = jQuery(this);
    var page = btn.closest('.ppsgenContactEmailChatPage');
    var contactId = parseInt(page.data('contact-id'), 10) || 0;
    var mailConfigId = jQuery('#ppsgenContactEmailMailConfig').length ? (parseInt(jQuery('#ppsgenContactEmailMailConfig').val(), 10) || 0) : 0;
    if (!contactId) {
      return;
    }
    btn.prop('disabled', true);
    jQuery.post(
      ajaxurl,
      {
        action: 'markContactIncomingEmailsHandled',
        mod: 'contact',
        plugin: 'ppsgen',
        contact_id: contactId,
        mail_config_id: mailConfigId,
        nonce_code: ppsgenAjax.nonce,
      },
      function (response) {
        btn.prop('disabled', false);
        if (response && response.success) {
          ppsgenUpdateContactIncomingSummary(
            response.data && response.data.incoming_summary_html ? response.data.incoming_summary_html : '',
            response.data && response.data.incoming_summary ? response.data.incoming_summary : {}
          );
          jQuery().ppsgenShowPopupNotice({
            status: 'success',
            msg: response.data && response.data.message ? response.data.message : '',
          });
          return;
        }
        jQuery().ppsgenShowPopupNotice({
          status: 'error',
          msg: response && response.data && response.data.message ? response.data.message : '',
        });
      }
    ).fail(function () {
      btn.prop('disabled', false);
      jQuery().ppsgenShowPopupNotice({ status: 'error' });
    });
  });

  jQuery(document).on('click', '.ppsgenContactEmailOpenMessageBtn', function (e) {
    e.preventDefault();
    var btn = jQuery(this);
    var page = btn.closest('.ppsgenContactEmailChatPage');
    var contactId = parseInt(page.data('contact-id'), 10) || 0;
    var mailConfigId = jQuery('#ppsgenContactEmailMailConfig').length ? (parseInt(jQuery('#ppsgenContactEmailMailConfig').val(), 10) || 0) : 0;
    var mailboxKey = btn.data('mailbox-key') || '';
    var messageId = btn.data('message-id') || '';
    if (!contactId || !mailboxKey || !messageId) {
      return;
    }
    btn.prop('disabled', true);
    jQuery.post(
      ajaxurl,
      {
        action: 'getContactEmailMessageView',
        mod: 'contact',
        plugin: 'ppsgen',
        contact_id: contactId,
        mail_config_id: mailConfigId,
        mailbox_key: mailboxKey,
        message_id: messageId,
        nonce_code: ppsgenAjax.nonce,
      },
      function (response) {
        btn.prop('disabled', false);
        if (response && response.success) {
          ppsgenOpenContactEmailModal(response.data && response.data.html ? response.data.html : '');
          var replySubject = response.data && response.data.reply_subject
            ? response.data.reply_subject
            : ppsgenBuildContactReplySubject(btn.find('.ppsgenContactEmailThreadSubject').text());
          if (replySubject) {
            jQuery('#ppsgenContactEmailSubject').val(replySubject);
          }
          btn.removeClass('is-unseen').addClass('is-seen');
          btn.find('.ppsgenContactEmailThreadState').text('Read');
          return;
        }
        jQuery().ppsgenShowPopupNotice({
          status: 'error',
          msg: response && response.data && response.data.message ? response.data.message : '',
        });
      }
    ).fail(function () {
      btn.prop('disabled', false);
      jQuery().ppsgenShowPopupNotice({ status: 'error' });
    });
  });

  jQuery(document).on('click', '.ppsgenContactEmailModalClose, .ppsgenContactEmailModalBackdrop', function (e) {
    e.preventDefault();
    ppsgenCloseContactEmailModal();
  });

  jQuery(document).on('click', '.ppsgenContactEmailSendBtn', function (e) {
    e.preventDefault();
    var btn = jQuery(this);
    var contactId = parseInt(btn.data('contact-id'), 10) || 0;
    var editorId = btn.data('editor-id') || '';
    var subject = jQuery.trim(jQuery('#ppsgenContactEmailSubject').val());
    var templateId = jQuery('#ppsgenContactEmailTemplate').length ? jQuery('#ppsgenContactEmailTemplate').val() : '';
    var mailConfigId = jQuery('#ppsgenContactEmailMailConfig').length ? (parseInt(jQuery('#ppsgenContactEmailMailConfig').val(), 10) || 0) : 0;
    var message = ppsgenGetContactEmailEditorContent(editorId);
    if (!contactId) {
      return;
    }
    if (!jQuery.trim(jQuery('<div>').html(message).text())) {
      jQuery().ppsgenShowPopupNotice({
        status: 'error',
        msg: 'Please enter an email message before sending.',
      });
      return;
    }
    btn.prop('disabled', true);
    btn.find('.dashicons').removeClass('dashicons-email-alt').addClass('dashicons-update-alt');
    jQuery.post(
      ajaxurl,
      {
        action: 'sendContactEmailMessage',
        mod: 'contact',
        plugin: 'ppsgen',
        contact_id: contactId,
        nonce_code: ppsgenAjax.nonce,
        data: {
          contact_email_chat: {
            subject: subject,
            template_id: templateId,
            mail_config_id: mailConfigId,
            html_message: message,
          },
        },
      },
      function (response) {
        btn.prop('disabled', false);
        btn.find('.dashicons').removeClass('dashicons-update-alt').addClass('dashicons-email-alt');
        if (response && response.success) {
          ppsgenSetContactEmailEditorContent(editorId, '');
          jQuery().ppsgenShowPopupNotice({
            status: 'success',
            msg: response.data && response.data.message ? response.data.message : '',
          });
          ppsgenLoadContactEmailThread(true);
          return;
        }
        jQuery().ppsgenShowPopupNotice({
          status: 'error',
          msg: response && response.data && response.data.message ? response.data.message : '',
        });
      }
    ).fail(function () {
      btn.prop('disabled', false);
      btn.find('.dashicons').removeClass('dashicons-update-alt').addClass('dashicons-email-alt');
      jQuery().ppsgenShowPopupNotice({ status: 'error' });
    });
  });

  jQuery(document).on('change', '#ppsgenContactEmailMailConfig', function () {
    ppsgenLoadContactEmailThread(true);
  });
});
