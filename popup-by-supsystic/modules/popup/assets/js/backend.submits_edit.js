jQuery(document).ready(function () {
  jQuery('body').on('click', '.ppsgenPopupSubmitsRowTitle', function () {
    const row = jQuery(this).closest('.ppsgenPopupSubmitsRow');
    jQuery('.ppsgenPopupSubmitsRowData').not(row.find('.ppsgenPopupSubmitsRowData')).slideUp(200);
    row.find('.ppsgenPopupSubmitsRowData').slideToggle(200);
  });
  jQuery('body').on('click', '.ppsgenPopupSubmitsRowTitleDeleteBtn', function (e) {
    e.preventDefault();
    if (!confirm(ppsgenAjax.confirmDeleteText)) {
      return;
    }
    const button = jQuery(this);
    button.prop('disabled', true);
    const objId = button.data('obj');
    jQuery.post(
      ajaxurl,
      {
        action: 'deletePopupSubmit',
        mod: 'popup',
        plugin: 'ppsgen',
        obj: objId,
        nonce_code: ppsgenAjax.nonce,
      },
      function (response) {
        if (response && response.success) {
          button.closest('.ppsgenPopupSubmitsRow').slideUp(200, function () {
            jQuery(this).remove();
          });
        } else {
          button.prop('disabled', false);
          alert(response.data?.message || 'Error deleting entry.');
        }
      }
    );
  });

  jQuery('body').on('click', '.ppsgenPopupResetStatsBtn', function (e) {
    e.preventDefault();
    if (!confirm(ppsgenAjax.confirmResetText || ppsgenAjax.confirmDeleteText)) {
      return;
    }
    const button = jQuery(this);
    button.prop('disabled', true);
    const objId = button.data('obj');
    jQuery.post(
      ajaxurl,
      {
        action: 'resetPopupStats',
        mod: 'popup',
        plugin: 'ppsgen',
        obj: objId,
        nonce_code: ppsgenAjax.nonce,
      },
      function (response) {
        if (response && response.success) {
          window.location.reload();
        } else {
          button.prop('disabled', false);
        }
      }
    );
  });
});
