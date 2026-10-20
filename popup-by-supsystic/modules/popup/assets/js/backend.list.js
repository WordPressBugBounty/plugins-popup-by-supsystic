jQuery(document).ready(function () {
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
        action: 'deletePopupList',
        mod: 'popup',
        plugin: 'ppsgen',
        objs: objs,
        nonce_code: ppsgenAjax.nonce,
      },
      function (response) {
        if (response && response.success) {
          jQuery('.ppsgenTableListSelectCheckbox:checked').each(function () {
            jQuery(this).closest('tr').remove();
          });
        }
      }
    );
  });
  jQuery('.ppsgenTableListDeleteObjPopup').on('click', function (e) {
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
        action: 'deletePopup',
        mod: 'popup',
        plugin: 'ppsgen',
        obj: obj,
        nonce_code: ppsgenAjax.nonce,
      },
      function (response) {
        if (response && response.success) {
          button.closest('tr').remove();
        }
      }
    );
  });
  jQuery('.ppsgenTableListCopyObj').on('click', function (e) {
    e.preventDefault();
    var button = jQuery(this);
    button.prop('disabled', true);
    var obj = button.parent().data('obj');
    jQuery.post(
      ajaxurl,
      {
        action: 'duplicatePopup',
        mod: 'popup',
        plugin: 'ppsgen',
        obj: obj,
        nonce_code: ppsgenAjax.nonce,
      },
      function (response) {
        if (response && response.success) {
          var url = '';
          if (response.data && response.data.obj_id) {
            url = ppsgenAjax.admin_url + '?page=ppsgen&tab=popup_edit&id=' + response.data.obj_id;
          }
          setTimeout(() => {
            if (url) {
              location.href = url;
            } else {
              location.reload();
            }
          }, 300);
        }
      }
    );
  });
});
