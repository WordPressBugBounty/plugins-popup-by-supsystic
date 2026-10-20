jQuery(document).ready(function () {
  jQuery('.ppsgenDashboardOptionsSaveBtn').on('click', function () {
    jQuery('#ppsgenDashboardOptionsForm').trigger('submit');
  });
  jQuery('#ppsgenDashboardOptionsForm').on('submit', function (e) {
    e.preventDefault();
    var formData = jQuery('#ppsgenDashboardOptionsForm').serialize();
    formData += '&nonce_code=' + ppsgenAjax.nonce;
    jQuery('.ppsgenDashboardOptionsSaveBtn').attr('disabled', 'disabled').prop('disabled', true);
    jQuery('.ppsgenDashboardOptionsSaveBtn').find('.dashicons').toggleClass('dashicons-yes-alt').toggleClass('dashicons-clock');
    jQuery.post(ajaxurl, formData, function (response) {
      if (response) {
        jQuery('.ppsgenDashboardOptionsSaveBtn').removeAttr('disabled').prop('disabled', false);
        jQuery('.ppsgenDashboardOptionsSaveBtn').find('.dashicons').toggleClass('dashicons-yes-alt').toggleClass('dashicons-clock');
      }
    });
    return false;
  });
});
