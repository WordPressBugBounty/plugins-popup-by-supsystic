jQuery(document).ready(function () {
  jQuery('#ppsgenCsvContactSubmitForm').on('submit', function (e) {
    e.preventDefault();
    const formData = new FormData(jQuery('#ppsgenCsvContactSubmitForm')[0]);
    formData.append('mod', 'contact');
    formData.append('nonce_code', ppsgenAjax.nonce);
    jQuery('.ppsgenCsvContactSubmitBtn').attr('disabled', 'disabled').prop('disabled', true);
    jQuery('.ppsgenCsvContactSubmitBtn').find('.dashicons').toggleClass('dashicons-yes-alt').toggleClass('dashicons-clock');
    jQuery.ajax({
      url: ajaxurl,
      method: 'POST',
      data: formData,
      contentType: false,
      processData: false,
      success: function (response) {
        jQuery('.ppsgenCsvContactSubmitBtn').removeAttr('disabled').prop('disabled', false);
        jQuery('.ppsgenCsvContactSubmitBtn').find('.dashicons').toggleClass('dashicons-yes-alt').toggleClass('dashicons-clock');
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
});
