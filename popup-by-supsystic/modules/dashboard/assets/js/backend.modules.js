jQuery(document).ready(function () {
  let isActivating = false;
  let isDeactivating = false;
  let isShowingProOffer = false;

  function showProModulesOffer() {
    if (isShowingProOffer) {
      return;
    }
    const offerHtml = jQuery('#ppsgenModuleProOfferTemplate').html();
    if (!offerHtml) {
      return;
    }
    isShowingProOffer = true;
    const modal = jQuery('<div class="ppsgenModuleProOfferModal"></div>');
    const backdrop = jQuery('<div class="ppsgenModuleProOfferModalBackdrop"></div>');
    const dialog = jQuery('<div class="ppsgenModuleProOfferModalDialog"></div>');
    const close = jQuery('<a href="#" class="ppsgenModuleProOfferModalClose" aria-label="Close"><span class="dashicons dashicons-no-alt"></span></a>');

    modal.css({
      position: 'fixed',
      inset: 0,
      zIndex: 99998,
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'center',
      overflowX: 'hidden',
      overflowY: 'auto',
      padding: '32px 16px',
    });
    backdrop.css({
      position: 'fixed',
      inset: 0,
      background: 'rgba(15, 23, 42, 0.6)',
      backdropFilter: 'blur(4px)',
    });
    dialog.css({
      position: 'relative',
      width: 'min(1120px, calc(100vw - 32px))',
      maxHeight: 'calc(100vh - 32px)',
      zIndex: 2,
      overflowX: 'hidden',
      overflowY: 'auto',
      background: 'transparent',
    });
    close.css({
      position: 'absolute',
      top: '18px',
      right: '18px',
      zIndex: 3,
      display: 'inline-flex',
      alignItems: 'center',
      justifyContent: 'center',
      width: '40px',
      height: '40px',
      borderRadius: '999px',
      background: 'rgba(255,255,255,0.16)',
      color: '#fff',
      textDecoration: 'none',
      border: '1px solid rgba(255,255,255,0.18)',
    });

    dialog.append(close).append(offerHtml);
    modal.append(backdrop).append(dialog);
    jQuery('body').append(modal);

    function closeModal(e) {
      if (e) {
        e.preventDefault();
      }
      modal.remove();
      isShowingProOffer = false;
    }

    backdrop.on('click', closeModal);
    close.on('click', closeModal);
  }

  jQuery('.ppsgenModuleBtnActivate').on('click', function (e) {
    if (isActivating) return;
    isActivating = true;

    e.preventDefault();
    jQuery('.ppsgenModuleBtnActivate').attr('disabled', 'disabled').prop('disabled', true);

    var module = jQuery(this).data('module');
    jQuery.post(
      ajaxurl,
      {
        action: 'activateModule',
        mod: 'dashboard',
        plugin: 'ppsgen',
        module: module,
        nonce_code: ppsgenAjax.nonce,
      },
      function (response) {
        if (response && response.success) {
          location.reload();
        } else {
          isActivating = false;
          jQuery('.ppsgenModuleBtnActivate').prop('disabled', false);
        }
      }
    );
  });

  jQuery('.ppsgenModuleBtnDeactivate').on('click', function (e) {
    if (isDeactivating) return;
    isDeactivating = true;

    e.preventDefault();
    jQuery('.ppsgenModuleBtnDeactivate').attr('disabled', 'disabled').prop('disabled', true);

    var module = jQuery(this).data('module');
    jQuery.post(
      ajaxurl,
      {
        action: 'deactivateModule',
        mod: 'dashboard',
        plugin: 'ppsgen',
        module: module,
        nonce_code: ppsgenAjax.nonce,
      },
      function (response) {
        if (response && response.success) {
          location.reload();
        } else {
          isDeactivating = false;
          jQuery('.ppsgenModuleBtnDeactivate').prop('disabled', false);
        }
      }
    );
  });

  jQuery(document).on('click', '.ppsgenModuleBtnActivatePro', function (e) {
    e.preventDefault();
    showProModulesOffer();
  });
});
