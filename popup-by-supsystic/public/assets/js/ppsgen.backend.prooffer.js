/**
 * The PRO offer dialog for any CES admin screen (same look as the PRO template offer in the catalogues).
 * window.ppsgenProOffer.open({ eyebrow, title, text, list: [], previewHtml, caption, campaign })
 * Every option is optional: missing texts fall back to the general PRO offer (ppsgenProOfferL10n).
 * previewHtml is shown in the glass frame on the right (e.g. a PRO colour palette), caption under it.
 */
(function ($) {
  const l10n = window.ppsgenProOfferL10n || {};
  let $dialog = null;
  let lastFocus = null;

  const esc = (text) => $('<div>').text(String(text ?? '')).html();

  function build() {
    $dialog = $(`<div class="ppsgenTemplateProOfferPopup ppsgenTemplateProOfferPopupLux" id="ppsgenProOfferShared" style="display:none;">
      <div class="ppsgenTemplateProOfferPopupBackdrop"></div>
      <div class="ppsgenTemplateProOfferPopupDialog" role="dialog" aria-modal="true" aria-labelledby="ppsgenProOfferSharedTitle">
        <a class="ppsgenTemplateProOfferPopupClose" href="#" aria-label="${esc(l10n.close || 'Close')}"><span class="dashicons dashicons-no-alt"></span></a>
        <div class="ppsgenTemplateProOfferPopupMain">
          <div class="ppsgenTemplateProOfferPopupEyebrow"><span class="dashicons dashicons-star-filled" aria-hidden="true"></span><span class="ppsgenProOfferEyebrowText"></span></div>
          <h3 class="ppsgenTemplateProOfferPopupTitle" id="ppsgenProOfferSharedTitle"></h3>
          <p class="ppsgenTemplateProOfferPopupText"></p>
          <ul class="ppsgenTemplateProOfferPopupList"></ul>
        </div>
        <div class="ppsgenTemplateProOfferPopupAside">
          <figure class="ppsgenTemplateProOfferPopupPreview ppsgenProOfferPreviewHtml" hidden>
            <div class="ppsgenProOfferPreviewBox"></div>
            <figcaption></figcaption>
          </figure>
          <div class="ppsgenTemplateProOfferPopupPrice">
            <span class="ppsgenTemplateProOfferPopupPriceLabel">${esc(l10n.priceLabel || 'Yearly licence · 1 site')}</span>
            <span class="ppsgenTemplateProOfferPopupPriceValue"><strong>$99</strong> / year</span>
            <span class="ppsgenTemplateProOfferPopupPriceMeta">${esc(l10n.priceMeta || 'renews at the same price · 30-day money-back guarantee')}</span>
          </div>
          <div class="ppsgenTemplateProOfferPopupActions">
            <a class="ppsgenTemplateProOfferPopupBtn ppsgenTemplateProOfferPopupBtnPrimary" href="#" data-action="activate" target="_blank" rel="noopener noreferrer">
              <span class="dashicons dashicons-superhero-alt"></span>${esc(l10n.cta || 'Get PRO for $99 a year')}
            </a>
            <a class="ppsgenTemplateProOfferPopupBtn ppsgenTemplateProOfferPopupBtnSecondary" href="#" data-action="dismiss">
              <span class="dashicons dashicons-dismiss"></span>${esc(l10n.later || 'Maybe Later')}
            </a>
          </div>
        </div>
      </div>
    </div>`).appendTo('body');

    $dialog.on('click', '.ppsgenTemplateProOfferPopupClose, .ppsgenTemplateProOfferPopupBackdrop, [data-action="dismiss"]', (e) => {
      e.preventDefault();
      close();
    });
    // The primary button is a real link (opens the offer in a new tab), then the dialog closes
    $dialog.on('click', '[data-action="activate"]', () => setTimeout(close, 0));
    $(document).on('keydown.ppsgenProOffer', (e) => {
      if (e.key === 'Escape' && $dialog.is(':visible')) {
        close();
      }
    });
  }

  function close() {
    $dialog.fadeOut(180);
    if (lastFocus && document.contains(lastFocus)) {
      lastFocus.focus();
    }
  }

  function open(opts = {}) {
    if (!$dialog) {
      build();
    }
    lastFocus = document.activeElement;
    const list = Array.isArray(opts.list) && opts.list.length ? opts.list : l10n.list || [];
    $dialog.find('.ppsgenProOfferEyebrowText').text(opts.eyebrow || l10n.eyebrow || 'PRO feature');
    $dialog.find('.ppsgenTemplateProOfferPopupTitle').text(opts.title || l10n.title || '');
    $dialog.find('.ppsgenTemplateProOfferPopupText').text(opts.text || l10n.text || '');
    $dialog.find('.ppsgenTemplateProOfferPopupList').html(list.map((item) => `<li>${esc(item)}</li>`).join(''));
    const $figure = $dialog.find('.ppsgenProOfferPreviewHtml');
    $figure.find('.ppsgenProOfferPreviewBox').html(opts.previewHtml || '');
    $figure.find('figcaption').text(opts.caption || '');
    $figure.prop('hidden', !opts.previewHtml);
    const url = new URL(l10n.offerUrl || 'https://supsystic.com/plugins/wordpress-popup-plugin/');
    url.searchParams.set('utm_source', 'ces_plugin');
    url.searchParams.set('utm_medium', 'feature');
    url.searchParams.set('utm_campaign', opts.campaign || 'pro_offer');
    $dialog.find('[data-action="activate"]').attr('href', url.toString());
    $dialog.fadeIn(180);
    $dialog.find('[data-action="activate"]').trigger('focus');
  }

  window.ppsgenProOffer = { open, close: () => $dialog && close() };
})(jQuery);
