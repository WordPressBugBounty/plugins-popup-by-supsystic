jQuery(document).ready(function ($) {
  const proOfferPopup = $('#ppsgenTemplateProOfferPopup');
  const magnifierZoom = 2.35;
  const $magnifier = $('<div class="ppsgenTemplateMagnifier" aria-hidden="true"></div>').appendTo('body');

  // $card: the PRO template that was clicked — its preview and name are shown in the dialog
  function openProOfferPopup($card) {
    const $figure = proOfferPopup.find('.ppsgenTemplateProOfferPopupPreview');
    const $img = $card && $card.length ? $card.find('.ppsgenTemplatePreview img').first() : $();
    if ($img.length) {
      $figure.find('img').attr({ src: $img.attr('src'), alt: $img.attr('alt') || '' });
      $figure.find('figcaption').text($card.find('.ppsgenTemplateTitle').first().text());
      $figure.prop('hidden', false);
    } else {
      $figure.prop('hidden', true);
    }
    proOfferPopup.fadeIn(180);
  }

  function closeProOfferPopup() {
    proOfferPopup.fadeOut(180);
  }

  function hideTemplateMagnifier() {
    $magnifier.removeClass('ppsgenTemplateMagnifierActive');
  }

  function moveTemplateMagnifier(event, preview) {
    const img = preview.querySelector('img');
    if (!img || !img.currentSrc) {
      hideTemplateMagnifier();
      return;
    }

    const rect = img.getBoundingClientRect();
    if (!rect.width || !rect.height) {
      hideTemplateMagnifier();
      return;
    }

    const xRatio = Math.min(1, Math.max(0, (event.clientX - rect.left) / rect.width));
    const yRatio = Math.min(1, Math.max(0, (event.clientY - rect.top) / rect.height));
    const lensWidth = $magnifier.outerWidth();
    const lensHeight = $magnifier.outerHeight();
    const viewportGap = 16;
    let left = event.clientX + 22;
    let top = event.clientY + 22;

    if (left + lensWidth > window.innerWidth - viewportGap) {
      left = event.clientX - lensWidth - 22;
    }
    if (top + lensHeight > window.innerHeight - viewportGap) {
      top = event.clientY - lensHeight - 22;
    }

    left = Math.max(viewportGap, Math.min(left, window.innerWidth - lensWidth - viewportGap));
    top = Math.max(viewportGap, Math.min(top, window.innerHeight - lensHeight - viewportGap));

    const bgWidth = rect.width * magnifierZoom;
    const bgHeight = rect.height * magnifierZoom;
    const bgX = (lensWidth / 2) - (xRatio * bgWidth);
    const bgY = (lensHeight / 2) - (yRatio * bgHeight);

    $magnifier
      .addClass('ppsgenTemplateMagnifierActive')
      .css({
        left: Math.round(left),
        top: Math.round(top),
        backgroundImage: 'url("' + img.currentSrc + '")',
        backgroundSize: Math.round(bgWidth) + 'px ' + Math.round(bgHeight) + 'px',
        backgroundPosition: Math.round(bgX) + 'px ' + Math.round(bgY) + 'px',
      });
  }

  jQuery('.ppsgenFilterBtn').on('click', function () {
    var tag = jQuery(this).data('tag');
    jQuery('.ppsgenFilterBtn').removeClass('active');
    jQuery(this).addClass('active');
    jQuery('.ppsgenTemplateCard').each(function () {
      var tags = jQuery(this)
        .data('tags')
        .split(',')
        .map(function (t) {
          return t.trim();
        });
      if (tag === 'all' || tags.includes(tag)) {
        jQuery(this).show();
      } else {
        jQuery(this).hide();
      }
    });
    // Feature filters can be empty for now (e.g. email automations come later)
    jQuery('.ppsgenTemplateEmpty').toggle(!jQuery('.ppsgenTemplateCard:visible').length);
  });
  jQuery('.ppsgenTemplateCard').on('click', function () {
    if (jQuery(this).hasClass('ppsgenProInactive')) {
      openProOfferPopup(jQuery(this));
      return;
    }
    var code = jQuery(this).data('code');
    jQuery('.templateActive').removeClass('templateActive');
    jQuery(this).addClass('templateActive');
    jQuery('#template_code').val(code);
  });
  jQuery('#ppsgenTemplateAdd').on('submit', function (e) {
    e.preventDefault();
    const activeCard = jQuery('.ppsgenTemplateCard.templateActive');
    if (activeCard.length && activeCard.hasClass('ppsgenProInactive')) {
      openProOfferPopup(activeCard);
      return false;
    }
    jQuery('#template_submit').attr('disabled', 'disabled').prop('disabled', true);
    const formData = new FormData(jQuery('#ppsgenTemplateAdd')[0]);
    formData.append('mod', 'template');
    formData.append('action', 'templateAddSubmit');
    formData.append('nonce_code', ppsgenAjax.nonce);
    jQuery.ajax({
      url: ajaxurl,
      method: 'POST',
      data: formData,
      contentType: false,
      processData: false,
      success: function (response) {
        jQuery('#template_submit').removeAttr('disabled').prop('disabled', false);
        if (response) {
          if (response.success) {
            var msg = response.data && response.data.message ? response.data.message : '';
            jQuery().ppsgenShowPopupNotice({ status: 'success', operation: 'edit', msg: msg });
            var url = '';
            if (response.data && response.data.redirect) {
              url = response.data.redirect;
            }
            setTimeout(() => {
              if (url) {
                location.href = url;
              } else {
                location.reload();
              }
            }, 1000);
          } else {
            var msg = response.data && response.data.message ? response.data.message : '';
            if (response.data && response.data.show_pro_popup) {
              openProOfferPopup(jQuery('.ppsgenTemplateCard.templateActive'));
            }
            jQuery().ppsgenShowPopupNotice({ status: 'error', operation: 'edit', msg: msg });
          }
        }
      },
    });
    return false;
  });

  proOfferPopup.on('click', '.ppsgenTemplateProOfferPopupClose, .ppsgenTemplateProOfferPopupBackdrop, [data-action="dismiss"]', function (e) {
    e.preventDefault();
    closeProOfferPopup();
  });

  proOfferPopup.on('click', '[data-action="activate"]', function (e) {
    e.preventDefault();
    const url = proOfferPopup.data('offer-url') || 'https://supsystic.com/plugins/wordpress-popup-plugin/?utm_source=ces_plugin&utm_medium=feature&utm_campaign=templates_pro';
    window.open(url, '_blank', 'noopener,noreferrer');
  });

  jQuery(document)
    .on('mousemove', '.ppsgenTemplatePreview', function (e) {
      moveTemplateMagnifier(e, this);
    })
    .on('mouseleave', '.ppsgenTemplatePreview', hideTemplateMagnifier);

  jQuery(window).on('scroll resize blur', hideTemplateMagnifier);
});
