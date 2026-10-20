// Free default; the PRO add-on switches this to its own controller
window.ppsgenEcaController = window.ppsgenEcaController || 'eca';

jQuery(document).ready(function () {
  function showChainLimitBanner(bannerHtml) {
    if (!bannerHtml) {
      return;
    }
    jQuery('.ppsgenEcaPromoModal').remove();
    const modal = jQuery(`
      <div class="ppsgenEcaPromoModal">
        <div class="ppsgenEcaPromoModalBackdrop"></div>
        <div class="ppsgenEcaPromoModalDialog">
          <button type="button" class="ppsgenEcaPromoModalClose" aria-label="Close">
            <span class="dashicons dashicons-no-alt"></span>
          </button>
          ${bannerHtml}
        </div>
      </div>
    `);
    modal.css({
      position: 'fixed',
      inset: 0,
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'center',
      overflowX: 'hidden',
      overflowY: 'auto',
      zIndex: 99998,
      padding: '16px',
    });
    modal.find('.ppsgenProOfferNotice').css({ margin: 0, minHeight: 0 });
    modal.find('.ppsgenEcaPromoModalBackdrop').css({
      position: 'fixed',
      inset: 0,
      background: 'rgba(15, 23, 42, 0.55)',
      backdropFilter: 'blur(3px)',
      zIndex: 99998,
    });
    modal.find('.ppsgenEcaPromoModalDialog').css({
      position: 'relative',
      width: 'min(1100px, calc(100vw - 32px))',
      maxHeight: 'calc(100vh - 32px)',
      overflowX: 'hidden',
      overflowY: 'auto',
      zIndex: 99999,
      borderRadius: '24px',
      boxShadow: '0 25px 70px rgba(15, 23, 42, 0.35)',
      background: 'transparent',
    });
    modal.find('.ppsgenEcaPromoModalClose').css({
      position: 'absolute',
      top: '14px',
      right: '14px',
      zIndex: 2,
      width: '38px',
      height: '38px',
      borderRadius: '999px',
      border: '1px solid rgba(255,255,255,0.18)',
      background: 'rgba(255,255,255,0.12)',
      color: '#fff',
      cursor: 'pointer',
      display: 'inline-flex',
      alignItems: 'center',
      justifyContent: 'center',
    });
    jQuery('body').append(modal);
  }

  jQuery('body').on('click', '.ppsgenEcaPromoModalClose, .ppsgenEcaPromoModalBackdrop', function () {
    jQuery('.ppsgenEcaPromoModal').remove();
  });

  window.ppsgenEcaController = 'eca';
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
        action: 'deleteChainList',
        mod: window.ppsgenEcaController,
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
  jQuery('.ppsgenTableListDeleteObjChain').on('click', function (e) {
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
        action: 'deleteChain',
        mod: window.ppsgenEcaController,
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
  jQuery('.ppsgenTableListCopyObjChain').on('click', function (e) {
    e.preventDefault();
    var button = jQuery(this);
    button.prop('disabled', true);
    var obj = button.parent().data('obj');
    jQuery.post(
      ajaxurl,
      {
        action: 'duplicateChain',
        mod: window.ppsgenEcaController,
        plugin: 'ppsgen',
        obj: obj,
        nonce_code: ppsgenAjax.nonce,
      },
      function (response) {
        if (response && response.success) {
          var url = '';
          if (response.data && response.data.obj_id) {
            url = ppsgenAjax.admin_url + '?page=ppsgen&tab=eca_chain_edit&id=' + response.data.obj_id;
          }
          setTimeout(() => {
            if (url) {
              location.href = url;
            } else {
              location.reload();
            }
          }, 300);
        } else {
          button.prop('disabled', false);
        }
      }
    );
  });
  jQuery('.ppsgenTableListAddObj').on('click', function (e) {
    e.preventDefault();
    var button = jQuery(this);
    // The same list serves Automations and Newsletters; a newsletter Add button carries its own action and texts
    const createAction = button.data('create-action') || 'createChain';
    let chain_name = prompt(button.data('prompt') || 'Name your automation, for example "Welcome new subscribers":');
    if (chain_name === null && createAction !== 'createChain') return;
    if (!chain_name) chain_name = button.data('default-name') || 'New automation';
    button.prop('disabled', true);
    jQuery.post(
      ajaxurl,
      {
        action: createAction,
        mod: window.ppsgenEcaController,
        plugin: 'ppsgen',
        chain_name: chain_name,
        nonce_code: ppsgenAjax.nonce,
      },
      function (response) {
        if (response?.success) {
          const url = response.data?.obj_id && ppsgenAjax?.admin_url ? `${ppsgenAjax.admin_url}?page=ppsgen&tab=eca_chain_edit&id=${response.data.obj_id}` : '';
          setTimeout(() => {
            if (url) {
              location.href = url;
            }
          }, 300);
        } else {
          if (response?.data?.banner_html) {
            button.prop('disabled', false);
            showChainLimitBanner(response.data.banner_html);
            return;
          }
          jQuery().ppsgenShowPopupNotice({ status: 'error', operation: 'add', msg: response?.data?.message, fadeOutTime: 8000 });
        }
      }
    );
  });
});
