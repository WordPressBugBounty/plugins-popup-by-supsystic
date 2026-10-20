jQuery(document).ready(function () {
  jQuery('.ppsgenFormOptionsSaveBtn').on('click', function () {
    jQuery('#ppsgenFormOptionsForm').trigger('submit');
  });
  jQuery('#ppsgenFormOptionsForm').on('submit', function (e) {
    e.preventDefault();
    var formData = jQuery('#ppsgenFormOptionsForm').serialize();
    formData += '&nonce_code=' + ppsgenAjax.nonce;
    jQuery('.ppsgenFormOptionsSaveBtn').attr('disabled', 'disabled').prop('disabled', true);
    jQuery('.ppsgenFormOptionsSaveBtn').find('.dashicons').toggleClass('dashicons-yes-alt').toggleClass('dashicons-clock');
    jQuery.post(ajaxurl, formData, function (response) {
      if (response) {
        jQuery('.ppsgenFormOptionsSaveBtn').removeAttr('disabled').prop('disabled', false);
        jQuery('.ppsgenFormOptionsSaveBtn').find('.dashicons').toggleClass('dashicons-yes-alt').toggleClass('dashicons-clock');
      }
    });
    return false;
  });
});
