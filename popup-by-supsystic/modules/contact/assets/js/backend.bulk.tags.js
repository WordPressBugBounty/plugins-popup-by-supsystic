jQuery(document).ready(function () {
  function showNotice(status, msg) {
    var escapedMsg = jQuery('<div>').text(msg || '').html();
    if (typeof jQuery().ppsgenShowPopupNotice === 'function') {
      jQuery().ppsgenShowPopupNotice({ status: status, msg: escapedMsg, fadeOutTime: 3000 });
    } else if (msg) {
      window.alert(msg);
    }
  }

  function getScope(element) {
    return jQuery(element).closest('.ppsgenBulkAssignTagsScope');
  }

  function getModal($scope) {
    return $scope.find('.ppsgenBulkAssignTagsModal').first();
  }

  function getSelectedContactIds($scope) {
    var ids = [];
    $scope.find('.ppsgenTableListSelectCheckbox:checked').each(function () {
      var rawId = jQuery(this).data('contact-id');
      if (rawId === undefined || rawId === null || rawId === '') {
        rawId = jQuery(this).data('obj');
      }
      var contactId = parseInt(rawId, 10);
      if (!isNaN(contactId) && contactId > 0 && ids.indexOf(contactId) === -1) {
        ids.push(contactId);
      }
    });
    return ids;
  }

  function closeModal($modal) {
    if (!$modal.length) {
      return;
    }
    $modal.removeClass('is-active').attr('aria-hidden', 'true');
    $modal.find('.ppsgenBulkAssignTagsSelect').val([]).trigger('change');
    $modal.removeData('contactIds');
    $modal.removeData('mode');
    $modal.removeData('submitAction');
    $modal.removeData('successFallback');
    $modal.removeData('errorFallback');
  }

  jQuery(document).on('click', '.ppsgenBulkAssignTagsTrigger', function (e) {
    e.preventDefault();
    var $trigger = jQuery(this);
    var $scope = getScope(this);
    var $modal = getModal($scope);
    var contactIds = getSelectedContactIds($scope);

    if (!$modal.length) {
      showNotice('error', 'Bulk tag popup was not found.');
      return;
    }
    if (!contactIds.length) {
      showNotice('error', 'Select at least one contact first.');
      return;
    }

    var mode = ($trigger.data('mode') || 'add') === 'delete' ? 'delete' : 'add';
    var modalTitle = $trigger.data('modal-title') || 'Add Tag(s) to Selected Contacts';
    var modalDescription = $trigger.data('modal-description') || 'Choose one or more tags to assign to the selected contacts. Existing contact tags will be kept and not duplicated.';
    var submitLabel = $trigger.data('submit-label') || 'Add Tag(s)';
    var submitIcon = $trigger.data('submit-icon') || 'dashicons-tag';
    var submitAction = mode === 'delete' ? 'bulkDeleteTagsFromContacts' : 'bulkAddTagsToContacts';
    var successFallback = mode === 'delete' ? 'Tags were removed.' : 'Tags were added.';
    var errorFallback = mode === 'delete' ? 'Failed to remove tags.' : 'Failed to add tags.';

    $modal.data('contactIds', contactIds);
    $modal.data('mode', mode);
    $modal.data('submitAction', submitAction);
    $modal.data('successFallback', successFallback);
    $modal.data('errorFallback', errorFallback);
    $modal.attr('data-mode', mode);
    $modal.find('.ppsgenBulkAssignTagsSelectedCount').text(contactIds.length);
    $modal.find('.ppsgenBulkAssignTagsModalTitle').text(modalTitle);
    $modal.find('.ppsgenBulkAssignTagsModalDesc').text(modalDescription);
    $modal.find('.ppsgenBulkAssignTagsSubmitLabel').text(submitLabel);
    $modal.find('.ppsgenBulkAssignTagsSubmit .dashicons').attr('class', 'dashicons ' + submitIcon);
    $modal.find('.ppsgenBulkAssignTagsSubmit').toggleClass('is-delete-mode', mode === 'delete');
    $modal.addClass('is-active').attr('aria-hidden', 'false');
  });

  jQuery(document).on('click', '.ppsgenBulkAssignTagsCancel, .ppsgenBulkAssignTagsModalClose, .ppsgenBulkAssignTagsModalBackdrop', function (e) {
    e.preventDefault();
    closeModal(jQuery(this).closest('.ppsgenBulkAssignTagsModal'));
  });

  jQuery(document).on('click', '.ppsgenBulkAssignTagsSubmit', function (e) {
    e.preventDefault();
    var btn = jQuery(this);
    if (btn.hasClass('is-disabled') || btn.attr('aria-disabled') === 'true') {
      return;
    }
    if (btn.hasClass('is-loading')) {
      return;
    }

    var $modal = btn.closest('.ppsgenBulkAssignTagsModal');
    var contactIds = $modal.data('contactIds') || [];
    var tagIds = $modal.find('.ppsgenBulkAssignTagsSelect').val() || [];
    var submitAction = $modal.data('submitAction') || 'bulkAddTagsToContacts';
    var successFallback = $modal.data('successFallback') || 'Tags were added.';
    var errorFallback = $modal.data('errorFallback') || 'Failed to add tags.';

    if (!contactIds.length) {
      showNotice('error', 'Select at least one contact first.');
      return;
    }
    if (!tagIds.length) {
      showNotice('error', 'Select at least one tag.');
      return;
    }

    btn.addClass('is-loading').attr('aria-disabled', 'true');

    jQuery.post(
      ajaxurl,
      {
        action: submitAction,
        mod: 'contact',
        plugin: 'ppsgen',
        contact_ids: contactIds,
        tag_ids: tagIds,
        nonce_code: ppsgenAjax.nonce,
      },
      function (response) {
        if (response && response.success) {
          showNotice('success', response.data && response.data.message ? response.data.message : successFallback);
          closeModal($modal);
        } else {
          showNotice('error', response && response.data && response.data.message ? response.data.message : errorFallback);
        }
      }
    ).fail(function () {
      showNotice('error', errorFallback);
    }).always(function () {
      btn.removeClass('is-loading').attr('aria-disabled', 'false');
    });
  });

  jQuery(document).on('keydown', function (e) {
    if (e.key !== 'Escape' && e.keyCode !== 27) {
      return;
    }
    var $modal = jQuery('.ppsgenBulkAssignTagsModal.is-active').last();
    if ($modal.length) {
      closeModal($modal);
    }
  });
});
