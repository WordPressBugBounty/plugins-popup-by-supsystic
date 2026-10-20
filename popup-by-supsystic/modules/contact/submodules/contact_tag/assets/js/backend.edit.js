jQuery(document).ready(function () {
  jQuery('.ppsgenObjEditSaveBtn').on('click', function () {
    const form = document.getElementById('ppsgenEditObjForm');
    if (form.reportValidity()) {
      jQuery(form).trigger('submit');
    }
  });
  jQuery('#ppsgenEditObjForm').on('submit', function (e) {
    e.preventDefault();
    var formData = jQuery('#ppsgenEditObjForm').serialize();
    formData += '&nonce_code=' + ppsgenAjax.nonce;
    jQuery('.ppsgenObjEditSaveBtn').attr('disabled', 'disabled').prop('disabled', true);
    jQuery('.ppsgenObjEditSaveBtn').find('.dashicons').toggleClass('dashicons-yes-alt').toggleClass('dashicons-clock');
    jQuery.post(ajaxurl, formData, function (response) {
      if (response) {
        jQuery('.ppsgenObjEditSaveBtn').removeAttr('disabled').prop('disabled', false);
        jQuery('.ppsgenObjEditSaveBtn').find('.dashicons').toggleClass('dashicons-yes-alt').toggleClass('dashicons-clock');
        if (response.success) {
          var msg = response.data && response.data.message ? response.data.message : '';
          jQuery().ppsgenShowPopupNotice({ status: 'success', operation: 'edit', msg: msg });
          var url = '';
          if (response.data && response.data.obj_id) {
            url = ppsgenAjax.admin_url + '?page=ppsgen&tab=contact_tag_edit&id=' + response.data.obj_id;
          }
          setTimeout(() => {
            if (url) {
              location.href = url;
            } else {
              location.reload();
            }
          }, 1000);
        }
        if (!response.success) {
          var msg = response.data && response.data.message ? response.data.message : '';
          jQuery().ppsgenShowPopupNotice({ status: 'error', operation: 'edit', msg: msg });
        }
      }
    });
    return false;
  });
});
