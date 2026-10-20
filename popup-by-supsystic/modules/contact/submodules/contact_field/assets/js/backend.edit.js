jQuery(document).ready(function () {
  var $fieldType = jQuery('select[name="data[contact_field][field_type]"]');

  function isProFieldType(value) {
    return !!(value && value.toString().indexOf('pro_') === 0);
  }

  function updateProBannerVisibility(value) {
    var $banner = jQuery('.ppsgenContactFieldProBannerWrapper');
    if (!$banner.length) {
      return;
    }
    if (isProFieldType(value)) {
      $banner.stop(true, true).slideDown(180);
    } else {
      $banner.stop(true, true).slideUp(180);
    }
  }

  if ($fieldType.length) {
    updateProBannerVisibility($fieldType.val());
    $fieldType.on('change', function () {
      var currentValue = jQuery(this).val() || '';
      updateProBannerVisibility(currentValue);
    });
  }

  jQuery('.ppsgenObjEditSaveBtn').on('click', function () {
    const form = document.getElementById('ppsgenEditObjForm');
    if (form.reportValidity()) {
      jQuery(form).trigger('submit');
    }
  });

  jQuery('#ppsgenEditObjForm').on('submit', function (e) {
    e.preventDefault();
    var reg_exp_value_before = jQuery(this).find('input[name*="[pattern]"]').val();
    jQuery(this).find('input[name*="[pattern]"]').backslashEncode();
    var formData = jQuery('#ppsgenEditObjForm').serialize();
    formData += '&nonce_code=' + ppsgenAjax.nonce;
    jQuery(this).find('input[name*="[pattern]"]').val(reg_exp_value_before);
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
            url = ppsgenAjax.admin_url + '?page=ppsgen&tab=contact_field_edit&id=' + response.data.obj_id;
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

jQuery(function () {
  jQuery('.ppsgenComponentOptionsList').sortable({
    items: '.ppsgenComponentOptionInline',
    handle: '.ppsgenComponentOptionsListBtnSort',
    placeholder: 'ppsgenComponentSortablePlaceholder',
    tolerance: 'pointer',
  });
});

jQuery(function () {
  jQuery(document).on('click', '.ppsgenComponentOptionsListBtnAdd', function () {
    const $template = jQuery('.ppsgenComponentOptionTemplate').clone(true, true);
    const randomKey = 'key_' + Math.floor(Math.random() * 1000000000);
    $template.find('[name], [id], [for]').each(function () {
      const $el = jQuery(this);
      jQuery.each(['name', 'id', 'for'], function (_, attr) {
        const val = $el.attr(attr);
        if (val && val.includes('@@OPTNUMBER@@')) {
          $el.attr(attr, val.replace(/@@OPTNUMBER@@/g, randomKey));
        }
      });
    });
    $template.find('.ppsgenComponentOptionsListBtnAdd').remove();
    $template.removeClass('ppsgenComponentOptionTemplate');
    $template.find('.ppsgenComponentOptionsListBtnWrapper').eq(0).append('<div class="ppsgenComponentOptionsListBtn ppsgenComponentOptionsListBtnSort ui-sortable-handle"><span class="dashicons dashicons-sort"></span></div>');
    $template.find('.ppsgenComponentOptionsListBtnWrapper').eq(1).append('<div class="ppsgenComponentOptionsListBtn ppsgenComponentOptionsListBtnDelete"><span class="dashicons dashicons-trash"></span></div>');
    jQuery('body').find('.ppsgenComponentOptionsList').append($template);
  });
  jQuery('body').on('click', '.ppsgenComponentOptionsListBtnDelete', function () {
    jQuery(this).closest('.ppsgenComponentOptionInline').remove();
  });
});
