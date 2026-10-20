jQuery(document).ready(function () {
  function removeContactListRowGroup($row) {
    var $engagementRow = $row.next('.ppsgenContactEngagementRow');
    $row.remove();
    if ($engagementRow.length) {
      $engagementRow.remove();
    }
  }

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
        action: 'deleteContactList',
        mod: 'contact',
        plugin: 'ppsgen',
        objs: objs,
        nonce_code: ppsgenAjax.nonce,
      },
      function (response) {
        if (response && response.success) {
          jQuery('.ppsgenTableListSelectCheckbox:checked').each(function () {
            removeContactListRowGroup(jQuery(this).closest('tr'));
          });
        }
      }
    );
  });
  jQuery('.ppsgenTableListDeleteObjContact').on('click', function (e) {
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
        action: 'deleteContact',
        mod: 'contact',
        plugin: 'ppsgen',
        obj: obj,
        nonce_code: ppsgenAjax.nonce,
      },
      function (response) {
        if (response && response.success) {
          removeContactListRowGroup(button.closest('tr'));
        }
      }
    );
  });
  jQuery('.ppsgenTableListContactExport').on('click', function (e) {
    e.preventDefault();
    var button = jQuery(this);
    button.prop('disabled', true);

    var objs = [];
    jQuery('.ppsgenTableListSelectCheckbox:checked').each(function () {
      var obj = jQuery(this).data('obj');
      if (obj !== undefined) {
        objs.push(obj);
      }
    });
    jQuery.ajax({
      url: ajaxurl,
      type: 'POST',
      data: {
        action: 'exportContactList',
        mod: 'contact',
        plugin: 'ppsgen',
        objs: objs,
        nonce_code: ppsgenAjax.nonce,
      },
      xhrFields: {
        responseType: 'blob', // Expect binary data (file)
      },
      success: function (response, status, xhr) {
        if (xhr.status === 200) {
          var disposition = xhr.getResponseHeader('Content-Disposition');
          var filename = disposition.match(/filename="(.+)"/)[1] || 'ppsgen_contacts_export.csv';
          // Create a temporary link to download the file
          var blob = new Blob([response], { type: 'text/csv;charset=UTF-8' });
          var link = document.createElement('a');
          link.href = window.URL.createObjectURL(blob);
          link.download = filename;
          document.body.appendChild(link);
          link.click();
          document.body.removeChild(link);
          // Optional: Remove selected rows after export
          jQuery('.ppsgenTableListSelectCheckbox:checked').each(function () {
            jQuery(this).prop('checked', false); // Uncheck instead of removing rows
          });
        } else {
          alert('Error: ' + response.message || 'Failed to export contacts.');
        }
        button.prop('disabled', false);
      },
      error: function (xhr) {
        var response = xhr.responseJSON || {};
        alert('Error: ' + (response.message || 'Failed to export contacts.'));
        button.prop('disabled', false);
      },
    });
  });
});

// Details panel: click a contact row to see who they are, their tags and latest activity
jQuery(document).ready(function ($) {
  const $drawer = $('.ppsgenContactDrawer');
  if (!$drawer.length) {
    return;
  }
  let request = null;
  function closeDrawer() {
    if (request) {
      request.abort();
    }
    $drawer.attr('hidden', 'hidden').empty();
    $('.ppsgenContactRow').removeClass('ppsgenContactRowSelected');
  }
  $(document).on('click', '.ppsgenContactRow', function (e) {
    if ($(e.target).closest('a, button, input, select, label, textarea').length) {
      return;
    }
    const contactId = $(this).data('contact-id');
    if (!contactId) {
      return;
    }
    $('.ppsgenContactRow').removeClass('ppsgenContactRowSelected');
    $('.ppsgenContactRow[data-contact-id="' + contactId + '"]').addClass('ppsgenContactRowSelected');
    if (request) {
      request.abort();
    }
    $drawer.removeAttr('hidden').html('<p class="ppsgenEmpty">' + $('<span>').text($drawer.data('loading')).html() + '</p>');
    request = $.post(ajaxurl, {
      action: 'getContactPreview',
      mod: 'contact',
      plugin: 'ppsgen',
      contact_id: contactId,
      nonce_code: ppsgenAjax.nonce,
    })
      .done(function (response) {
        if (response && response.success && response.data && response.data.html) {
          $drawer.html(response.data.html);
        } else {
          $drawer.html('<p class="ppsgenEmpty">' + $('<span>').text((response && response.data && response.data.message) || ppsgenAjax.notice_statuses.error.default).html() + '</p>');
        }
      })
      .fail(function (xhr, status) {
        if (status !== 'abort') {
          $drawer.html('<p class="ppsgenEmpty">' + $('<span>').text(ppsgenAjax.notice_statuses.error.default).html() + '</p>');
        }
      });
  });
  $drawer.on('click', '.ppsgenContactPreviewClose', function (e) {
    e.preventDefault();
    closeDrawer();
  });
  // Details panel actions: "Mark replied" and "Add to automation" refresh the panel in place
  function drawerAction(data, $button) {
    $button.prop('disabled', true);
    $.post(ajaxurl, $.extend({ mod: 'contact', plugin: 'ppsgen', nonce_code: ppsgenAjax.nonce }, data))
      .done(function (response) {
        const msg = (response && response.data && response.data.message) || '';
        if (response && response.success && response.data.html) {
          $drawer.html(response.data.html);
        } else {
          $button.prop('disabled', false);
        }
        if (msg) {
          jQuery().ppsgenShowPopupNotice({ status: response && response.success ? 'success' : 'error', msg: msg, fadeOutTime: 5000 });
        }
      })
      .fail(function () {
        $button.prop('disabled', false);
      });
  }
  $drawer.on('click', '.ppsgenContactMarkReplied', function () {
    drawerAction({ action: 'markContactReplied', contact_id: $(this).data('contact-id') }, $(this));
  });
  $drawer.on('click', '.ppsgenContactAddToAutomation', function () {
    const chainId = $(this).siblings('.ppsgenContactAutomationSelect').val();
    drawerAction({ action: 'addContactToAutomation', contact_id: $(this).data('contact-id'), chain_id: chainId }, $(this));
  });
  $(document).on('keydown', function (e) {
    if (e.key === 'Escape' && !$drawer.is('[hidden]')) {
      closeDrawer();
    }
  });
});

// Lists: saved filters of the contacts table
jQuery(document).ready(function ($) {
  const $bar = $('.ppsgenContactListsBar');
  if (!$bar.length) {
    return;
  }
  const $form = $bar.closest('form');

  function currentFilters() {
    const filters = {};
    $form.find('[name^="filter_"], [name="columns[]"]').each(function () {
      const $field = $(this);
      const name = $field.attr('name');
      const value = $field.val();
      if (Array.isArray(value)) {
        if (value.length) {
          filters[name] = value;
        }
      } else if (value !== null && String(value).trim() !== '') {
        filters[name] = String(value).trim();
      }
    });
    return filters;
  }

  // Same POST as the Apply Filters button, plus the list id so the pill shows as active
  function applyFilters(filters, listId) {
    const $post = $('<form method="POST"></form>').attr('action', window.location.href);
    $.each(filters, function (name, value) {
      (Array.isArray(value) ? value : [value]).forEach(function (item) {
        $('<input type="hidden">').attr('name', name).val(item).appendTo($post);
      });
    });
    $('<input type="hidden" name="ppsgen_list_id">').val(listId || '').appendTo($post);
    $post.appendTo('body').trigger('submit');
  }

  function post(data) {
    return $.post(ajaxurl, $.extend({ mod: 'contact', plugin: 'ppsgen', nonce_code: ppsgenAjax.nonce }, data));
  }

  $bar.on('click', '.ppsgenContactListApply', function (e) {
    e.preventDefault();
    const $pill = $(this).closest('.ppsgenContactListPill');
    applyFilters($pill.data('filters') || {}, $pill.data('list-id'));
  });

  $bar.on('click', '.ppsgenContactListDelete', function (e) {
    e.preventDefault();
    const $link = $(this);
    if (!window.confirm($link.data('confirm'))) {
      return;
    }
    const $pill = $link.closest('.ppsgenContactListPill');
    post({ action: 'deleteSavedContactList', list_id: $pill.data('list-id') }).done(function () {
      $pill.remove();
    });
  });

  $bar.on('click', '.ppsgenContactListSave', function (e) {
    e.preventDefault();
    const filters = currentFilters();
    const name = window.prompt($(this).data('prompt'));
    if (!name) {
      return;
    }
    post({ action: 'saveSavedContactList', name: name, filters: JSON.stringify(filters) })
      .done(function (response) {
        if (response && response.success && response.data && response.data.id) {
          applyFilters(filters, response.data.id);
          return;
        }
        jQuery().ppsgenShowPopupNotice({ status: 'error', msg: (response && response.data && response.data.message) || '', fadeOutTime: 6000 });
      })
      .fail(function () {
        jQuery().ppsgenShowPopupNotice({ status: 'error', fadeOutTime: 6000 });
      });
  });
});
