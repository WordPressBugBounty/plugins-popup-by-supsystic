jQuery(document).ready(function () {
  jQuery('.ppsgenPopupOptionsSaveBtn').on('click', function () {
    jQuery('#ppsgenPopupOptionsPopup').trigger('submit');
  });
  jQuery('#ppsgenPopupOptionsPopup').on('submit', function (e) {
    e.preventDefault();
    var popupData = jQuery('#ppsgenPopupOptionsPopup').serialize();
    popupData += '&nonce_code=' + ppsgenAjax.nonce;
    jQuery('.ppsgenPopupOptionsSaveBtn').attr('disabled', 'disabled').prop('disabled', true);
    jQuery('.ppsgenPopupOptionsSaveBtn').find('.dashicons').toggleClass('dashicons-yes-alt').toggleClass('dashicons-clock');
    jQuery.post(ajaxurl, popupData, function (response) {
      if (response) {
        jQuery('.ppsgenPopupOptionsSaveBtn').removeAttr('disabled').prop('disabled', false);
        jQuery('.ppsgenPopupOptionsSaveBtn').find('.dashicons').toggleClass('dashicons-yes-alt').toggleClass('dashicons-clock');
      }
    });
    return false;
  });
});
