(function (jQuery) {
  'use strict';

  // Extension point
  window.PpsgenPopupExtend = window.PpsgenPopupExtend || {};
  window.PpsgenPopupController = 'popup';

  class PpsgenPopup {
    constructor(element) {
      this.$el = jQuery(element);
      this.id = this.$el.data('popup-id');
      this.globalPopupSettings = window['ppsgenFrontendPopup' + this.id] || {};
      this.settings = window['ppsgenPopupSettings_' + this.id] || {};
      this.isVisible = false;
      this.timer = null;
      this.inactivityTimer = null;
      this.scrollShown = false;
      this.exitShown = false;
      this.adblockChecked = false;

      this.openedAt = null;
      this.closedAt = null;

      this.uniqueKey = 'ppsgen_seen_' + this.id;
      this.uniqueTTL = 30 * 24 * 60 * 60 * 1000;

      // Allow extension version to extend constructor
      this.extend('constructor', [element]);
      this.init();
    }

    // Method to call extension
    extend(methodName, args = []) {
      if (typeof window.PpsgenPopupExtend[methodName] === 'function') {
        return window.PpsgenPopupExtend[methodName].apply(this, args);
      }
      return null;
    }

    init() {
      if (this.extend('init')) return;
      this.bindEvents();
      if (this.$el.hasClass('ppsgenPopupForceshow')) {
        this.show();
      } else {
        this.checkShowConditions();
      }
    }

    bindEvents() {
      const self = this;
      this.$el.find('.ppsgenPopupClose').on('click', () => {
        this.hide();
      });

      if (this.settings.close_on === 'overlay_click') {
        if (this.$el.closest('.ppsgenPopupOverlay').length === 0) {
          jQuery(document).on('click.ppsgenCloseOnOutside', (e) => {
            const $popupWrapper = this.$el.closest('.ppsgenPopupWrapper');
            if (!$popupWrapper.length) return;
            if (!$popupWrapper.is(e.target) && $popupWrapper.has(e.target).length === 0) {
              this.hide();
              jQuery(document).off('click.ppsgenCloseOnOutside');
            }
          });
        }
        this.$el.closest('.ppsgenPopupOverlay').on('click', (e) => {
          if (jQuery(e.target).hasClass('ppsgenPopupOverlay')) {
            self.hide();
          }
        });
      }

      this.$el.on('click', '.ppsgenPopupActionTrigger', (e) => {
        const $target = jQuery(e.target).closest('.ppsgenPopupActionTrigger');
        if ($target.closest('.ppsgenFormWrapper').length > 0) return;
        const keyBase = 'ppsgen_popup_' + self.id;
        const keyTime = keyBase + '_action';
        const keyMetaList = keyBase + '_actions_meta';
        let actionMeta = null;
        const classes = $target.attr('class').split(/\s+/);
        for (let cls of classes) {
          if (cls.startsWith('ppsgenPopupActionTrigger_')) {
            actionMeta = cls.substring('ppsgenPopupActionTrigger_'.length);
            break;
          }
        }
        let sentMetaList = [];
        const stored = localStorage.getItem(keyMetaList);
        if (stored) {
          try {
            sentMetaList = JSON.parse(stored);
          } catch (e) {
            sentMetaList = [];
          }
        }
        if (actionMeta && sentMetaList.includes(actionMeta)) {
          return;
        }
        if (actionMeta) {
          sentMetaList.push(actionMeta);
          localStorage.setItem(keyMetaList, JSON.stringify(sentMetaList));
        }
        if (!localStorage.getItem(keyTime)) {
          localStorage.setItem(keyTime, Date.now());
        }
        const duration = Date.now() - self.openedAt;
        const isUnique = !!localStorage.getItem(self.uniqueKey);
        self.logEvent('action', {
          time_on_screen: duration,
          action_meta: actionMeta,
          is_unique: isUnique ? 1 : 0,
        });
      });

      this.$el.on('ppsgenFormSuccess', (e, form_id) => {
        if (form_id) {
          const key = 'ppsgen_popup_' + self.id;
          localStorage.setItem(key + '_form_submitted', Date.now());
          const duration = Date.now() - self.openedAt;
          self.logEvent('submit', { time_on_screen: duration, action_meta: form_id || '' });
        }
      });

      jQuery(window).on('resize', () => this.checkDevices());

      this.extend('bindEvents');
    }

    checkShowConditions() {
      const extResult = this.extend('checkShowConditions');
      if (extResult !== null) return;

      if (!this.checkDevices() || !this.checkShowTo()) return;

      const showOn = this.settings.show_on;
      const delay = this.getDelay(showOn);

      switch (showOn) {
        case 'page_load':
          if (this.settings.page_load_global_delay) {
            const key = 'ppsgen_popup_page_load_global_' + this.id;
            let firstVisit = parseInt(localStorage.getItem(key), 10);
            const now = Date.now();
            if (!firstVisit) {
              firstVisit = now;
              localStorage.setItem(key, firstVisit);
            }
            const waitMs = delay;
            const passed = now - firstVisit;
            if (passed >= waitMs) {
              this.show();
            } else {
              setTimeout(() => this.show(), waitMs - passed);
            }
          } else {
            setTimeout(() => this.show(), delay);
          }
          break;
        case 'click_on_page':
          jQuery('body').one('click', () => setTimeout(() => this.show(), delay));
          break;
        case 'click_on_element':
          // A link, menu item URL or title attribute with #supsystic-ces-popup-ID, or onclick="supsysticCesShowPopup(ID, this)"
          jQuery(document).on('click', '[href*="#supsystic-ces-popup-"], [title*="#supsystic-ces-popup-"]', (e) => {
            if (!cesLinksToPopup(e.currentTarget.getAttribute('href'), this.id) && !cesLinksToPopup(e.currentTarget.getAttribute('title'), this.id)) {
              return;
            }
            e.preventDefault();
            setTimeout(() => this.show(), delay);
          });
          jQuery(document).on('supsysticCesShowPopup_' + this.id, () => setTimeout(() => this.show(), delay));
          if (cesLinksToPopup(window.location.hash, this.id)) {
            setTimeout(() => this.show(), delay);
          }
          break;
        case 'scroll_window':
          jQuery(window).on('scroll', () => {
            if (this.scrollShown) return;
            const perc = this.settings.show_on_scroll_window_perc_scroll || 0;
            const scrolled = (jQuery(window).scrollTop() / (jQuery(document).height() - jQuery(window).height())) * 100;
            if (scrolled >= perc) {
              this.scrollShown = true;
              setTimeout(() => this.show(), delay);
            }
          });
          break;
        case 'on_exit':
          jQuery(document).on('mouseleave', () => {
            if (this.exitShown) return;
            this.exitShown = true;
            this.show();
          });
          break;
        case 'page_bottom':
          jQuery(window).on('scroll', () => {
            if (this.scrollShown) return;
            if (jQuery(window).scrollTop() + jQuery(window).height() >= jQuery(document).height() - 100) {
              this.scrollShown = true;
              this.show();
            }
          });
          break;
        case 'after_inactive': {
          const inactiveMs = (parseInt(this.settings.show_on_after_inactive_value, 10) || 10) * 1000;
          const resetInactivity = () => {
            if (this.inactiveShown) return;
            clearTimeout(this.inactivityTimer);
            this.inactivityTimer = setTimeout(() => {
              this.inactiveShown = true;
              this.show();
            }, inactiveMs);
          };
          jQuery(document).on('mousemove keydown scroll touchstart', resetInactivity);
          resetInactivity();
          break;
        }
        case 'after_comment': {
          // The server sets this cookie for a minute after a comment or review is posted
          const match = document.cookie.match(/(?:^|; )ppsgen_after_comment=([^;]*)/);
          let ids = [];
          try {
            ids = match ? JSON.parse(decodeURIComponent(match[1])) : [];
          } catch (e) {
            ids = [];
          }
          if (Array.isArray(ids) && ids.map(String).includes(String(this.id))) {
            this.show();
          }
          break;
        }
        case 'after_checkout':
        case 'build_in_page':
          // Only rendered on the order-received page, or where its shortcode is placed
          this.show();
          break;
        case 'link_follow':
          jQuery(document).on('click', '[href*="#supsystic-ces-popup-"]', (e) => {
            if (!cesLinksToPopup(e.currentTarget.getAttribute('href'), this.id)) {
              return;
            }
            e.preventDefault();
            setTimeout(() => this.show(), delay);
          });
          if (cesLinksToPopup(window.location.hash, this.id)) {
            setTimeout(() => this.show(), delay);
          }
          break;
        case 'adblock_detected':
          this.detectAdblock(() => this.show());
          break;
        case 'visit_several_pages': {
          const visitKey = 'ppsgen_visit_' + this.id;
          let visits = 1;
          try {
            visits = (parseInt(localStorage.getItem(visitKey), 10) || 0) + 1;
            localStorage.setItem(visitKey, visits);
          } catch (e) {}
          if (visits >= (parseInt(this.settings.visit_page_cnt, 10) || 1)) {
            this.show();
          }
          break;
        }
        default:
          break;
      }
    }

    // A hidden bait element and script: ad blockers hide or block them
    detectAdblock(callback) {
      if (this.adblockChecked) return;
      this.adblockChecked = true;
      const bait = document.createElement('div');
      bait.className = 'ad adsbox advert banner ad-banner ad-placement';
      bait.style.cssText = 'width:1px;height:1px;position:absolute;left:-9999px;';
      document.body.appendChild(bait);
      setTimeout(() => {
        const blocked = bait.offsetHeight === 0 || window.getComputedStyle(bait).display === 'none';
        document.body.removeChild(bait);
        if (blocked) {
          callback();
          return;
        }
        const self = document.querySelector('script[src*="ppsgen.frontend.popups.js"]');
        if (!self) return;
        const baitScript = document.createElement('script');
        baitScript.src = self.src.replace(/ppsgen\.frontend\.popups\.js.*$/, 'ads.js?r=' + Math.random().toString(36).substring(2, 10));
        baitScript.async = true;
        baitScript.onerror = () => callback();
        document.body.appendChild(baitScript);
      }, 100);
    }

    getDelay(showOn) {
      const proDelay = this.extend('getDelay', [showOn]);
      if (proDelay !== null) return proDelay;

      switch (showOn) {
        case 'page_load':
          return this.settings.show_on_page_load_delay ? this.settings.show_on_page_load_delay * 1000 : 0;
        case 'click_on_element':
          return this.settings.show_on_click_on_el_delay ? this.settings.show_on_click_on_el_delay * 1000 : 0;
        case 'link_follow':
          return this.settings.show_on_link_follow_delay ? this.settings.show_on_link_follow_delay * 1000 : 0;
        case 'scroll_window':
          return this.settings.show_on_scroll_window_delay ? this.settings.show_on_scroll_window_delay * 1000 : 0;
        default:
          return 0;
      }
    }

    checkDevices() {
      const extResult = this.extend('checkDevices');
      if (extResult !== null) return extResult;

      const action = this.settings.hide_for_devices_action;
      if (!action) return true;
      const devices = this.settings.hide_for_devices || [];
      const width = window.innerWidth;
      const isMobile = width <= 767.98;
      const isTablet = width > 767.98 && width <= 1023.98;
      const isDesktop = width > 1023.98;
      const match = (devices.includes('mobile') && isMobile) || (devices.includes('tablet') && isTablet) || (devices.includes('desktop') && isDesktop);
      const shouldShow = action === 'show' ? match : !match;
      if (!shouldShow) this.hide();
      return shouldShow;
    }

    checkShowTo() {
      const extResult = this.extend('checkShowTo');
      if (extResult !== null) return extResult;

      const key = 'ppsgen_popup_' + this.id;
      const now = Date.now();
      switch (this.settings.show_to) {
        case 'first_time_visit':
          var visitedDataRaw = localStorage.getItem(key + '_visited');
          var expireDays = parseInt(this.settings.show_to_first_time_visit_days || 0);
          var expireMs = expireDays > 0 ? expireDays * 86400000 : 0;
          if (visitedDataRaw) {
            const visitedTime = parseInt(visitedDataRaw, 10);
            if (!isNaN(visitedTime)) {
              if (expireMs === 0 || now - visitedTime < expireMs) {
                return false;
              }
            }
          }
          localStorage.setItem(key + '_visited', now);
          return true;
        case 'count_times': {
          const period = this.settings.count_times_mes || 'day';
          const maxCount = parseInt(this.settings.count_times_num || 0);
          const periodMs =
            {
              hour: 3600000,
              day: 86400000,
              week: 604800000,
              month: 2592000000,
            }[period] || 86400000;
          let data;
          try {
            data = JSON.parse(localStorage.getItem(key + '_count') || '{}');
          } catch (e) {
            data = {};
          }
          const last = parseInt(data.last || 0);
          const count = parseInt(data.count || 0);
          if (now - last >= periodMs) {
            data = { count: 1, last: now };
          } else {
            if (count >= maxCount) return false;
            data.count = count + 1;
            data.last = last || now;
          }
          localStorage.setItem(key + '_count', JSON.stringify(data));
          return true;
        }
        default:
          return true;
      }
    }

    show() {
      jQuery(document).trigger('ppsgenPopupShowAction');
      const extResult = this.extend('show');
      if (extResult !== null) return;

      if (this.isVisible) return;
      this.isVisible = true;
      this.openedAt = Date.now();
      const anim = this.settings.popup_anim_show || '';

      if (this.settings.hide_close_btn === '1' || this.settings.hide_close_btn === 1 || this.settings.hide_close_btn === true) {
        this.$el.find('.ppsgenPopupClose').hide();
      } else {
        this.$el.find('.ppsgenPopupClose').show();
      }

      this.$el.closest('.ppsgenPopupOverlay').show();
      this.$el.closest('.ppsgenPopupOverlay').addClass('ppsgenAnimated fadeIn');

      this.$el.closest('.ppsgenPopupWrapper').show();
      this.$el.closest('.ppsgenPopupWrapper').addClass('ppsgenAnimated ' + anim);

      this.$el.closest('.ppsgenPopupOverlay').one('animationend', () => this.$el.closest('.ppsgenPopupOverlay').removeClass('ppsgenAnimated fadeIn'));
      this.$el.closest('.ppsgenPopupWrapper').one('animationend', () => this.$el.closest('.ppsgenPopupWrapper').removeClass('ppsgenAnimated ' + anim));

      if (this.settings.hide_other) {
        PpsgenPopupManager.hideAllExcept(this.id);
      }

      let isUnique = 0;
      let seen = localStorage.getItem(this.uniqueKey);
      if (!seen) {
        isUnique = 1;
      } else {
        const seenTime = parseInt(seen, 10);
        if (isNaN(seenTime) || Date.now() - seenTime > this.uniqueTTL) {
          isUnique = 1;
        }
      }
      if (isUnique) {
        localStorage.setItem(this.uniqueKey, Date.now());
      }

      this.logEvent('view', { is_unique: isUnique });

      this.initFacebookPage();
    }

    initFacebookPage() {
      const $wrapper = this.$el.find('.ppsgenPopupFbWrapper');
      if (!$wrapper.length) return;
      const $scrollable = this.$el.find('.ppsgenPopupScrollable');
      $wrapper.empty();
      $wrapper.parent().addClass('ppsgen-popup-fb-frame-field');
      const $popup = this.$el.closest('.ppsgenPopup-' + this.id);
      if (!$popup.length) return;
      const buildIframe = () => {
        $wrapper.empty();
        let totalInnerHeight = Math.floor($scrollable.outerHeight());
        let popupHeight = Math.floor($popup.outerHeight());
        let fbHeight = Math.floor(this.$el.find('.ppsgen-popup-fb-frame-field').outerHeight());
        let height = popupHeight - (totalInnerHeight - fbHeight) - 20;
        const width = Math.floor($wrapper.outerWidth());
        // const height = Math.floor($popup.outerHeight() - 20);
        this.$el.find('.ppsgen-popup-fb-frame-field').css('height', height + 'px');
        const href = $wrapper.data('href');
        if (!href) return;
        const fbUrl = `https://www.facebook.com/plugins/page.php?` + `href=${href}` + `&tabs=timeline` + `&width=${width}` + `&height=${height}` + `&small_header=true` + `&adapt_container_width=true` + `&hide_cover=false` + `&show_facepile=true&appId`;
        $wrapper.html(`<div class="fb-widget-container" style="width:100%;height:100%;">
                          <iframe src="${fbUrl}" style="border:none;overflow:hidden" scrolling="no" frameborder="0" allow="autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share"></iframe>
                      </div>`);
      };
      buildIframe();
      buildIframe();
      let resizeTimer;
      jQuery(window).on('resize.fbWidget' + this.id, () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => {
          buildIframe();
        }, 150);
      });
      $popup.on('ppsgen-popup-close', () => {
        jQuery(window).off('resize.fbWidget' + this.id);
        $wrapper.empty();
      });
    }

    hide() {
      const extResult = this.extend('hide');
      if (extResult !== null) return;

      if (!this.isVisible) return;
      this.isVisible = false;
      this.closedAt = Date.now();
      clearTimeout(this.timer);
      clearTimeout(this.inactivityTimer);
      const anim = this.settings.popup_anim_hide || '';

      this.$el.closest('.ppsgenPopupOverlay').addClass('ppsgenAnimated fadeOut');
      this.$el.closest('.ppsgenPopupWrapper').addClass('ppsgenAnimated ' + anim);

      this.pauseIframeVideos();

      this.$el.closest('.ppsgenPopupOverlay').one('animationend', () => this.$el.closest('.ppsgenPopupOverlay').removeClass('ppsgenAnimated fadeOut').hide());
      this.$el.closest('.ppsgenPopupWrapper').one('animationend', () =>
        this.$el
          .closest('.ppsgenPopupWrapper')
          .removeClass('ppsgenAnimated ' + anim)
          .hide()
      );

      const duration = this.closedAt - this.openedAt;
      this.logEvent('close', {
        is_bounce: duration < 4000 ? 1 : 0,
        time_on_screen: duration,
      });
    }

    pauseIframeVideos() {
      const iframes = this.$el.find('iframe');
      if (!iframes.length) return;
      iframes.each(function () {
        const iframe = this;
        try {
          const doc = iframe.contentDocument || iframe.contentWindow.document;
          if (!doc) return;
          const videos = doc.querySelectorAll('video');
          videos.forEach((video) => {
            if (!video.paused) {
              video.pause();
            }
          });
        } catch (e) {}
        iframe.contentWindow?.postMessage(
          JSON.stringify({
            event: 'command',
            func: 'pauseVideo',
            args: [],
          }),
          '*'
        );
        iframe.contentWindow?.postMessage(
          {
            method: 'pause',
          },
          '*'
        );
      });
    }

    getDeviceType() {
      const extResult = this.extend('getDeviceType');
      if (extResult !== null) return extResult;

      const ua = navigator.userAgent.toLowerCase();
      const isMobile = /(android|webos|iphone|ipod|blackberry|iemobile|opera mini|mobile)/i.test(ua);
      const isTablet = /(tablet|ipad|playbook|silk|kindle|android(?!.*mobile))/i.test(ua);
      const isTouchDevice = 'ontouchstart' in window || navigator.maxTouchPoints > 0;
      const isSmallScreen = window.innerWidth <= 768;
      const isMediumScreen = window.innerWidth > 768 && window.innerWidth <= 1024;
      if (isMobile || (isTouchDevice && isSmallScreen)) {
        return 'mobile';
      } else if (isTablet || (isTouchDevice && isMediumScreen)) {
        return 'tablet';
      } else {
        return 'pc';
      }
    }

    initTimers() {
      const extResult = this.extend('initTimers');
      if (extResult !== null) return extResult;
    }

    logEvent(actionType, extraData = {}) {
      const extResult = this.extend('logEvent', [actionType, extraData]);
      if (extResult !== null) return;

      const urlParams = new URLSearchParams(window.location.search);
      const utm = {
        utm_source: urlParams.get('utm_source') || '',
        utm_medium: urlParams.get('utm_medium') || '',
        utm_campaign: urlParams.get('utm_campaign') || '',
        utm_term: urlParams.get('utm_term') || '',
        utm_content: urlParams.get('utm_content') || '',
      };
      jQuery.post(ppsgenFrontendAjax.url, {
        plugin: ppsgenFrontendAjax.prefix,
        mod: window.PpsgenPopupController,
        action: 'frontendPopupStatsSubmit',
        popup_id: this.id,
        nonce_code: this.globalPopupSettings.nonce_code,
        action_type: actionType,
        action_meta: extraData.action_meta || '',
        time_on_screen: extraData.time_on_screen || 0,
        is_bounce: extraData.is_bounce || 0,
        is_unique: extraData.is_unique || 0,
        user_agent: this.getDeviceType(),
        extra: {
          utm,
          url: window.location.href,
          referer: document.referrer,
        },
      });
    }
  }

  class PpsgenPopupManager {
    static instances = [];

    static init() {
      jQuery('.ppsgenPopup').each(function () {
        const popup = new PpsgenPopup(this);
        if (jQuery(this).data('popup-id') == '999') {
          popup.initFacebookPage();
        }
        PpsgenPopupManager.instances.push(popup);
      });
    }

    static getCount() {
      return this.instances.length;
    }

    static getById(id) {
      return this.instances.find((p) => p.id === id) || null;
    }

    static showAll() {
      this.instances.forEach((p) => p.show());
    }

    static hideAllExcept(exceptId) {
      this.instances.forEach((p) => {
        if (p.id !== exceptId) p.hide();
      });
    }
  }

  jQuery(document).ready(() => PpsgenPopupManager.init());
})(jQuery);

// A popup scrolls only when its content is really taller than the box. Hover effects that grow or shift a
// button (scale, translate) pushed the content a few pixels past the edge and flashed a scrollbar; the
// content height here comes from layout boxes (offsetTop / offsetHeight), which transforms don't change.
(function () {
  function fits(box) {
    var style = window.getComputedStyle(box);
    var top = box.offsetTop + box.clientTop;
    var bottom = 0;
    for (var i = 0; i < box.children.length; i++) {
      var child = box.children[i];
      var cs = window.getComputedStyle(child);
      if (cs.display === 'none' || cs.position === 'absolute' || cs.position === 'fixed') {
        continue;
      }
      var base = child.offsetParent === box ? 0 : top;
      bottom = Math.max(bottom, child.offsetTop - base + child.offsetHeight + (parseFloat(cs.marginBottom) || 0));
    }
    if (bottom + box.scrollTop + (parseFloat(style.paddingBottom) || 0) > box.clientHeight + 2) {
      return false;
    }
    // Content can also spill out of a child that has a fixed or 100% height (full-screen layouts); that shows
    // only in scrollHeight. Hover effects are not active while this runs (it reacts to size and DOM changes),
    // and the small allowance covers looping decorative motion.
    return box.scrollHeight <= box.clientHeight + 12;
  }
  var queued = false;
  function updateAll() {
    queued = false;
    document.querySelectorAll('.ppsgenPopupScrollable').forEach(function (box) {
      if (box.getClientRects().length) {
        box.classList.toggle('ppsgenPopupFits', fits(box));
      }
    });
  }
  function schedule() {
    if (!queued) {
      queued = true;
      // not requestAnimationFrame: it never runs in a background tab, and a popup can open in one
      window.setTimeout(updateAll, 30);
    }
  }
  function start() {
    updateAll();
    var boxes = document.querySelectorAll('.ppsgenPopupScrollable');
    if (window.ResizeObserver) {
      var sizes = new ResizeObserver(schedule);
      boxes.forEach(function (box) {
        sizes.observe(box);
        for (var i = 0; i < box.children.length; i++) {
          sizes.observe(box.children[i]);
        }
      });
    }
    // steps switching, a thank-you message, a popup opening: re-check
    if (window.MutationObserver) {
      var changes = new MutationObserver(schedule);
      document.querySelectorAll('.ppsgenPopup').forEach(function (popup) {
        changes.observe(popup, { attributes: true, attributeFilter: ['class', 'style'], childList: true, subtree: true });
      });
    }
    window.addEventListener('resize', schedule);
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start);
  } else {
    start();
  }
})();

// True when a link, title or address points at exactly this popup: #supsystic-ces-popup-1 must not open popup 12
function cesLinksToPopup(value, id) {
  return new RegExp('#supsystic-ces-popup-' + id + '(?![0-9])', 'i').test(String(value || ''));
}

// onclick="supsysticCesShowPopup(12, this)" on any element opens popup 12 (popups set to open on click)
function supsysticCesShowPopup(id, el) {
  jQuery(document).trigger('supsysticCesShowPopup_' + id);
}
window.supsysticCesShowPopup = supsysticCesShowPopup;
