jQuery(document).ready(function () {
  jQuery('body').on('click', '.ppsgenFormSubmitsRowTitle', function () {
    let title = jQuery(this);
    jQuery('body').find('.ppsgenFormSubmitsRowData').hide();
    title.closest('.ppsgenFormSubmitsRow').find('.ppsgenFormSubmitsRowData').show();
  });
  jQuery('body').on('click', '.ppsgenFormSubmitsRowTitleDeleteBtn', function (e) {
    e.preventDefault();
    if (!confirm(ppsgenAjax.confirmDeleteText)) {
      return;
    }
    var button = jQuery(this);
    button.prop('disabled', true);
    var obj = button.data('obj');
    jQuery.post(
      ajaxurl,
      {
        action: 'deleteFormSubmit',
        mod: 'form',
        plugin: 'ppsgen',
        obj: obj,
        nonce_code: ppsgenAjax.nonce,
      },
      function (response) {
        if (response && response.success) {
          button.closest('.ppsgenFormSubmitsRow').remove();
        }
      }
    );
  });
  jQuery('body').on('click', '.ppsgenFormSubmitsRowTitleVerifySubmitContactBtn', function (e) {
    e.preventDefault();
    var rewrite = 0;
    if (!confirm(ppsgenAjax.confirmVerifySubmitText)) {
      return;
    }
    if (confirm(ppsgenAjax.confirmVerifySubmitRewrite)) {
      rewrite = 1;
    }
    var button = jQuery(this);
    button.prop('disabled', true);
    var obj = button.data('obj');
    jQuery.post(
      ajaxurl,
      {
        action: 'verifySubmit',
        mod: 'contact',
        plugin: 'ppsgen',
        obj: obj,
        rewrite: rewrite,
        nonce_code: ppsgenAjax.nonce,
      },
      function (response) {
        if (response && response.success) {
          button.closest('.ppsgenFormSubmitsRowTitleBtns').find('.ppsgenFormSubmitsRowVerifySubmitIcon').find('.dashicons.dashicons-dismiss').removeClass('dashicons-dismiss').addClass('dashicons-yes-alt');
          button.remove();
        }
      }
    );
  });
});
