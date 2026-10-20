jQuery(document).ready(function () {
  const themeStorageKey = 'ppsgen_backend_theme';
  const darkThemeClass = 'ppsgen-theme-dark';
  const $html = jQuery(document.documentElement);
  const $body = jQuery('body');
  const $toggleBtn = jQuery('.ppsgenThemeToggleBtn');

  function applyTheme(theme) {
    const isDark = theme === 'dark';
    $html.toggleClass(darkThemeClass, isDark);
    $body.toggleClass(darkThemeClass, isDark);
    $toggleBtn.attr('aria-pressed', isDark ? 'true' : 'false');
    $toggleBtn.each(function () {
      const $btn = jQuery(this);
      const darkLabel = $btn.data('theme-label-dark') || 'Dark Theme';
      const lightLabel = $btn.data('theme-label-light') || 'Light Theme';
      $btn.find('.ppsgenThemeToggleBtnText').text(isDark ? lightLabel : darkLabel);
      $btn.find('.dashicons').removeClass('dashicons-moon').addClass('dashicons-lightbulb');
    });
  }

  function getStoredTheme() {
    const cookieMatch = document.cookie.match(/(?:^|; )ppsgen_backend_theme=([^;]+)/);
    const cookieTheme = cookieMatch ? decodeURIComponent(cookieMatch[1]) : '';
    if (cookieTheme) {
      return cookieTheme;
    }
    try {
      return window.localStorage.getItem(themeStorageKey) || 'light';
    } catch (e) {
      return 'light';
    }
  }

  function storeTheme(theme) {
    try {
      window.localStorage.setItem(themeStorageKey, theme);
    } catch (e) {}
    document.cookie = `ppsgen_backend_theme=${theme}; path=/; max-age=${60 * 60 * 24 * 365}`;
  }

  applyTheme(getStoredTheme());

  $toggleBtn.on('click', function (e) {
    e.preventDefault();
    const nextTheme = $body.hasClass(darkThemeClass) ? 'light' : 'dark';
    storeTheme(nextTheme);
    applyTheme(nextTheme);
  });

  jQuery('.ppsgenLegacyToggleBtn').on('click', function (e) {
    e.preventDefault();
    const $btn = jQuery(this);
    const nextState = $btn.data('next-state') == 1 ? 1 : 0;
    $btn.attr('disabled', 'disabled').prop('disabled', true);
    jQuery.post(
      ajaxurl,
      {
        action: 'toggleLegacyMode',
        mod: 'dashboard',
        plugin: 'ppsgen',
        legacy_active: nextState,
        nonce_code: ppsgenAjax.nonce,
      },
      function () {
        if (nextState === 1 && ppsgenAjax.legacy && ppsgenAjax.legacy.admin_url) {
          window.location.href = ppsgenAjax.legacy.admin_url;
          return;
        }
        window.location.reload();
      }
    ).fail(function () {
      $btn.removeAttr('disabled').prop('disabled', false);
    });
  });

  jQuery('.ppsgenLegacySwitchBannerClose').on('click', function (e) {
    e.preventDefault();
    const $btn = jQuery(this);
    const $banner = $btn.closest('.ppsgenLegacySwitchBanner');
    $btn.attr('disabled', 'disabled').prop('disabled', true);
    jQuery.post(
      ajaxurl,
      {
        action: 'dismissLegacySwitchBanner',
        mod: 'dashboard',
        plugin: 'ppsgen',
        nonce_code: ppsgenAjax.nonce,
      },
      function () {
        $banner.fadeOut(200, function () {
          $banner.remove();
        });
      }
    ).fail(function () {
      $btn.removeAttr('disabled').prop('disabled', false);
    });
  });

  const currentUrl = new URL(window.location.href);
  const currentTab = currentUrl.searchParams.get('tab') || 'dashboard';
  const $topLevelMenu = jQuery('#toplevel_page_ppsgen');
  const $submenuLinks = $topLevelMenu.find('.wp-submenu a');

  if ($submenuLinks.length) {
    $topLevelMenu.removeClass('wp-not-current-submenu').addClass('wp-has-current-submenu wp-menu-open');
    $submenuLinks.each(function () {
      const $link = jQuery(this);
      const href = $link.attr('href') || '';
      let linkTab = 'dashboard';
      try {
        const linkUrl = new URL(href, window.location.origin);
        linkTab = linkUrl.searchParams.get('tab') || 'dashboard';
      } catch (e) {
        linkTab = href.indexOf('tab=') !== -1 ? href.split('tab=')[1].split('&')[0] : 'dashboard';
      }
      const isCurrent = linkTab === currentTab;
      $link.parent('li').toggleClass('current', isCurrent);
      $link.toggleClass('current', isCurrent);
    });
  }

  jQuery('.ppsgenDashNavigationChildUl a[href*="tab=form_edit"]').each(function () {
    if (typeof ppsgenAjax !== 'undefined' && ppsgenAjax.links && ppsgenAjax.links.add_form_template) {
      jQuery(this).attr('href', ppsgenAjax.links.add_form_template);
    }
  });
  jQuery('.ppsgenDashNavigationChildUl a[href*="tab=popup_edit"]').each(function () {
    if (typeof ppsgenAjax !== 'undefined' && ppsgenAjax.links && ppsgenAjax.links.add_popup_template) {
      jQuery(this).attr('href', ppsgenAjax.links.add_popup_template);
    }
  });
});

jQuery(document).ready(function () {
  const storageKey = 'ppsgen_chain_edit_header_meta_collapsed';
  const $meta = jQuery('.ppsgenChainEditHeaderMeta');
  const $toggle = $meta.find('.ppsgenChainEditHeaderMetaToggle');

  if (!$meta.length || !$toggle.length) {
    return;
  }

  function applyCollapsedState(isCollapsed) {
    $meta.toggleClass('ppsgenChainEditHeaderMetaCollapsed', isCollapsed);
    $toggle.attr('aria-expanded', isCollapsed ? 'false' : 'true');
    $toggle.find('.dashicons')
      .toggleClass('dashicons-arrow-up-alt2', !isCollapsed)
      .toggleClass('dashicons-arrow-down-alt2', isCollapsed);
  }

  let isCollapsed = false;
  try {
    isCollapsed = window.localStorage.getItem(storageKey) === '1';
  } catch (e) {}

  applyCollapsedState(isCollapsed);

  $toggle.on('click', function (e) {
    e.preventDefault();
    isCollapsed = !$meta.hasClass('ppsgenChainEditHeaderMetaCollapsed');
    applyCollapsedState(isCollapsed);
    try {
      window.localStorage.setItem(storageKey, isCollapsed ? '1' : '0');
    } catch (e2) {}
  });
});

jQuery(document).ready(function () {
  jQuery(document).on('keydown.ppsgenPreviewEscape', function (e) {
    if (e.key !== 'Escape' && e.keyCode !== 27) {
      return;
    }
    const $previewPopup = jQuery('#ppsgenPreviewPopup');
    if (!$previewPopup.length || !$previewPopup.is(':visible')) {
      return;
    }
    $previewPopup.fadeOut(150);
  });
});

window.ppsgenBuildPayloadData = function (basePayload, encodedPayload) {
  const payload = Object.assign({}, basePayload || {});
  const packedData = typeof encodedPayload === 'string' ? encodedPayload : '';
  const localizedMaxLength = window.ppsgenAjax ? parseInt(ppsgenAjax.maxlength, 10) : 0;
  const maxLength = localizedMaxLength > 0 ? localizedMaxLength : 50000;
  if (packedData.length <= maxLength) {
    payload.payload_data = packedData;
    return payload;
  }
  payload.payload_data = [];
  for (let i = 0; i < packedData.length; i += maxLength) {
    payload.payload_data.push(packedData.slice(i, i + maxLength));
  }
  return payload;
};

jQuery(document).ready(function () {
  jQuery(function ($) {
    jQuery('.ppsgenSetTableOrder').on('click', function () {
      var form = jQuery(this).closest('form');
      var orderByInput = form.find('#order_by');
      var orderDirInput = form.find('#order_dir');
      var key = jQuery(this).data('key');
      var defaultDir = String(jQuery(this).data('default-dir') || 'ASC').toUpperCase() === 'DESC' ? 'DESC' : 'ASC';
      if (orderByInput.val() === key) {
        orderDirInput.val(orderDirInput.val() === 'ASC' ? 'DESC' : 'ASC');
      } else {
        orderByInput.val(key);
        orderDirInput.val(defaultDir);
      }
      form.submit();
    });
    jQuery('.ppsgenTableListSelectAllCheckbox').on('click', function () {
      var isChecked = jQuery(this).prop('checked');
      jQuery('.ppsgenTableListSelectCheckbox').prop('checked', isChecked);
    });
    jQuery('.ppsgenTableListSelectCheckbox').on('change', function () {
      var allChecked = jQuery('.ppsgenTableListSelectCheckbox').length === jQuery('.ppsgenTableListSelectCheckbox:checked').length;
      jQuery('.ppsgenTableListSelectAllCheckbox').prop('checked', allChecked);
    });
  });
});

jQuery(document).ready(function () {
  (function (jQuery) {
    const encodeMap = {
      '\\': '@@BL@@',
      "'": '@@SQ@@',
      '"': '@@DQ@@',
    };

    const decodeMap = {
      '@@BL@@': '\\',
      '@@SQ@@': "'",
      '@@DQ@@': '"',
    };

    jQuery.fn.backslashEncode = function () {
      return this.each(function () {
        let val = jQuery(this).val();
        if (typeof val === 'string') {
          for (let char in encodeMap) {
            val = val.split(char).join(encodeMap[char]);
          }
          jQuery(this).val(val);
        }
      });
    };

    jQuery.fn.backslashDecode = function () {
      return this.each(function () {
        let val = jQuery(this).val();
        if (typeof val === 'string') {
          for (let code in decodeMap) {
            val = val.split(code).join(decodeMap[code]);
          }
          jQuery(this).val(val);
        }
      });
    };
  })(jQuery);
});

jQuery(document).ready(function () {
  jQuery.fn.ppsgenShowPopupNotice = function ({ status = 'success', operation = '', msg = '', fadeOutTime = '' } = {}) {
    const presets = {
      success: {
        edit: ppsgenAjax.notice_statuses.success.edit,
        delete: ppsgenAjax.notice_statuses.success.delete,
        default: ppsgenAjax.notice_statuses.success.default,
      },
      error: {
        edit: ppsgenAjax.notice_statuses.error.edit,
        delete: ppsgenAjax.notice_statuses.error.delete,
        default: ppsgenAjax.notice_statuses.error.default,
      },
      warning: {
        edit: ppsgenAjax.notice_statuses.warning.edit,
        delete: ppsgenAjax.notice_statuses.warning.delete,
        default: ppsgenAjax.notice_statuses.warning.default,
      },
    };

    if (!fadeOutTime) {
      fadeOutTime = 2000;
    }

    if (!msg) {
      msg = presets[status] && presets[status][operation] ? presets[status][operation] : presets[status].default;
    }

    jQuery('.ppsgen-popup-notice').remove();

    const popup = jQuery(`<div class="ppsgen-popup-notice ppsgen-${status}">${msg}</div>`).css({
      position: 'fixed',
      top: '50%',
      left: '50%',
      transform: 'translate(-50%, -50%)',
      padding: '15px 25px',
      'border-radius': '8px',
      color: '#fff',
      'font-size': '16px',
      'z-index': 9999,
      display: 'none',
      'box-shadow': '0 2px 10px rgba(0,0,0,0.3)',
      'max-width': '80%',
      'text-align': 'center',
    });

    const bgColors = {
      success: '#4CAF50',
      error: '#F44336',
      warning: '#FF9800',
    };

    popup.css('background-color', bgColors[status] || bgColors.success);

    jQuery('body').append(popup);
    popup.fadeIn(300);

    setTimeout(() => {
      popup.fadeOut(300, () => popup.remove());
    }, fadeOutTime);

    return this;
  };
});

jQuery(document).ready(function () {
  jQuery('.ppsgenObjEditTabButton').on('click', function (e) {
    var btn = jQuery(this);
    var tab = btn.data('tab');
    jQuery('input[name="chain_edit_active_tab"]').val(tab);
    jQuery('input[name="obj_edit_active_tab"]').val(tab);
    jQuery('.ppsgenObjEditTabButton').removeClass('ppsgenObjEditTabButtonActive');
    btn.addClass('ppsgenObjEditTabButtonActive');
    jQuery('.ppsgenObjEditTab').removeClass('ppsgenObjEditTabActive');
    jQuery('.ppsgenObjEditTab[data-tab="' + tab + '"]').addClass('ppsgenObjEditTabActive');
  });
  // Deep links such as "Edit tags" or "Details" open the requested editor tab
  const requestedTab = new URL(window.location.href).searchParams.get('obj_edit_active_tab');
  if (requestedTab) {
    jQuery('.ppsgenObjEditTabButton')
      .filter(function () {
        return jQuery(this).data('tab') === requestedTab && !jQuery(this).hasClass('ppsgenObjEditTabButtonActive');
      })
      .first()
      .trigger('click');
  }
});

jQuery(document).ready(function () {
  jQuery(function ($) {
    const notice = $('.ppsgenFeedbackNotice');
    if (!notice.length) {
      return;
    }

    notice.on('click', '.ppsgenFeedbackNoticeClose', function (e) {
      e.preventDefault();
      notice.fadeOut(200);
    });

    notice.on('click', '.ppsgenFeedbackNoticeActionBtn', function (e) {
      e.preventDefault();
      const btn = $(this);
      const action = String(btn.data('action') || '');
      if (!action || btn.hasClass('ppsgenFeedbackNoticeBtnLoading')) {
        return;
      }

      let reviewTab = null;
      if (action === 'submit') {
        const reviewUrl =
          notice.data('review-url') || 'https://wordpress.org/support/plugin/popup-by-supsystic/reviews/#new-post';
        reviewTab = window.open(reviewUrl, '_blank', 'noopener,noreferrer');
      }

      btn.addClass('ppsgenFeedbackNoticeBtnLoading');
      notice.find('.ppsgenFeedbackNoticeActionBtn').addClass('ppsgenFeedbackNoticeBtnDisabled');

      $.ajax({
        url: typeof ajaxurl !== 'undefined' ? ajaxurl : ppsgenAjax.url,
        type: 'POST',
        dataType: 'json',
        data: {
          action: 'feedbackNoticeAction',
          mod: 'dashboard',
          plugin: 'ppsgen',
          nonce_code: ppsgenAjax.nonce,
          feedback_action: action,
        },
      })
        .done(function (response) {
          if (response && response.success) {
            if (action !== 'submit' && reviewTab) {
              reviewTab.close();
            }

            notice.fadeOut(200);
            $('body').ppsgenShowPopupNotice({
              status: 'success',
              msg: (response.data && response.data.message) || '',
            });
            return;
          }

          if (reviewTab) {
            reviewTab.close();
          }
          $('body').ppsgenShowPopupNotice({
            status: 'error',
            msg: (response && response.data && response.data.message) || '',
          });
        })
        .fail(function () {
          if (reviewTab) {
            reviewTab.close();
          }
          $('body').ppsgenShowPopupNotice({ status: 'error' });
        })
        .always(function () {
          btn.removeClass('ppsgenFeedbackNoticeBtnLoading');
          notice.find('.ppsgenFeedbackNoticeActionBtn').removeClass('ppsgenFeedbackNoticeBtnDisabled');
        });
    });
  });
});

jQuery(document).ready(function () {
  jQuery(function ($) {
    const notice = $('.ppsgenProOfferNotice');
    if (!notice.length) {
      return;
    }

    notice.on('click', '.ppsgenProOfferNoticeClose', function (e) {
      e.preventDefault();
      notice.fadeOut(200);
    });

    notice.on('click', '.ppsgenProOfferNoticeActionBtn', function (e) {
      e.preventDefault();
      const btn = $(this);
      const action = String(btn.data('action') || '');
      if (!action || btn.hasClass('ppsgenProOfferNoticeBtnLoading')) {
        return;
      }

      let offerTab = null;
      if (action === 'submit') {
        const offerUrl = notice.data('offer-url') || 'https://supsystic.com/plugins/wordpress-popup-plugin/?utm_source=ces_plugin&utm_medium=feature&utm_campaign=dashboard_pro';
        offerTab = window.open(offerUrl, '_blank', 'noopener,noreferrer');
      }

      btn.addClass('ppsgenProOfferNoticeBtnLoading');
      notice.find('.ppsgenProOfferNoticeActionBtn').addClass('ppsgenProOfferNoticeBtnDisabled');

      $.ajax({
        url: typeof ajaxurl !== 'undefined' ? ajaxurl : ppsgenAjax.url,
        type: 'POST',
        dataType: 'json',
        data: {
          action: 'proOfferNoticeAction',
          mod: 'dashboard',
          plugin: 'ppsgen',
          nonce_code: ppsgenAjax.nonce,
          promo_action: action,
        },
      })
        .done(function (response) {
          if (response && response.success) {
            if (action !== 'submit' && offerTab) {
              offerTab.close();
            }
            notice.fadeOut(200);
            $('body').ppsgenShowPopupNotice({
              status: 'success',
              msg: (response.data && response.data.message) || '',
            });
            return;
          }

          if (offerTab) {
            offerTab.close();
          }
          $('body').ppsgenShowPopupNotice({
            status: 'error',
            msg: (response && response.data && response.data.message) || '',
          });
        })
        .fail(function () {
          if (offerTab) {
            offerTab.close();
          }
          $('body').ppsgenShowPopupNotice({ status: 'error' });
        })
        .always(function () {
          btn.removeClass('ppsgenProOfferNoticeBtnLoading');
          notice.find('.ppsgenProOfferNoticeActionBtn').removeClass('ppsgenProOfferNoticeBtnDisabled');
        });
    });
  });
});

jQuery(document).ready(function () {
  function ppsgenSyncColorPickerFromTextInput($textInput) {
    const $wrapper = $textInput.closest('.ppsgen-color-picker');
    const $picker = $wrapper.find('.wp-color-picker');
    if (!$picker.length || typeof $picker.wpColorPicker !== 'function') {
      return;
    }

    const rawValue = String($textInput.val() || '').trim();

    if (!rawValue) {
      $picker.val('');
      $picker.wpColorPicker('color', '');
      return;
    }

    // Let WordPress/iris validate and normalize the value.
    $picker.val(rawValue);
    $picker.wpColorPicker('color', rawValue);

    const normalizedValue = String($picker.val() || '').trim();
    if (normalizedValue && normalizedValue !== rawValue) {
      $textInput.val(normalizedValue);
    }
  }

  if (typeof jQuery.fn.wpColorPicker === 'function') {
    jQuery('body')
      .find('.wp-color-picker')
      .wpColorPicker({
        hide: true,
        defaultColor: false,
        width: 200,
        border: false,
        change: function (event, ui) {
          var color = ui.color.toString();
          jQuery(this).closest('.ppsgen-color-picker').find('.ppsgen-color-result-text').val(color);
        },
        clear: function () {
          jQuery(this).closest('.ppsgen-color-picker').find('.ppsgen-color-result-text').val('');
        },
      });
  }

  jQuery(document).off('input.ppsgenColorSync change.ppsgenColorSync paste.ppsgenColorSync', '.ppsgen-color-picker .ppsgenColorInputText');
  jQuery(document).on('input.ppsgenColorSync change.ppsgenColorSync', '.ppsgen-color-picker .ppsgenColorInputText', function () {
    ppsgenSyncColorPickerFromTextInput(jQuery(this));
  });
  jQuery(document).on('paste.ppsgenColorSync', '.ppsgen-color-picker .ppsgenColorInputText', function () {
    const $input = jQuery(this);
    window.setTimeout(function () {
      ppsgenSyncColorPickerFromTextInput($input);
    }, 0);
  });
});

jQuery(document).ready(function () {
  jQuery('.ppsgen-help-text-btn').on('click', function (e) {
    var btn = jQuery(this);
    var tab = btn.data('show');
    jQuery('.ppsgen-help-text[data-show="' + tab + '"]').fadeToggle();
  });
});

jQuery(document).ready(function () {
  function afterInit() {
    jQuery('body')
      .find('.data-option-toggle-trigger')
      .each(function () {
        let optval = jQuery(this).val();
        let optname = jQuery(this).attr('name');
        jQuery('body')
          .find('[data-toggle-name="' + optname + '"]')
          .hide();
        jQuery('body')
          .find('[data-toggle-name="' + optname + '"]')
          .each(function () {
            let values = jQuery(this).data('toggle-val');
            if (typeof values === 'string') {
              toggleVals = values.split(',').map(function (val) {
                return val.trim();
              });
            } else if (typeof values === 'number') {
              toggleVals = [String(values)];
            }
            if (toggleVals.includes(optval)) {
              jQuery(this).show();
            } else {
              jQuery(this).find('input[type="checkbox"]:checked').trigger('click').trigger('change');
            }
          });
      });
    jQuery('body')
      .find('input[type="checkbox"].data-option-toggle-trigger')
      .each(function () {
        let isChecked = jQuery(this).is(':checked');
        let optval = jQuery(this).val();
        let optname = jQuery(this).attr('name');
        jQuery('body')
          .find('[data-toggle-name="' + optname + '"]')
          .hide();
        if (isChecked) {
          jQuery('body')
            .find('[data-toggle-name="' + optname + '"]')
            .each(function () {
              let values = jQuery(this).data('toggle-val');
              if (typeof values === 'string') {
                toggleVals = values.split(',').map(function (val) {
                  return val.trim();
                });
              } else if (typeof values === 'number') {
                toggleVals = [String(values)];
              }
              if (toggleVals.includes(optval)) {
                jQuery(this).show();
              }
            });
        } else {
          jQuery('body')
            .find('[data-toggle-name="' + optname + '"]')
            .each(function () {
              let values = jQuery(this).data('toggle-val');
              if (typeof values === 'string') {
                toggleVals = values.split(',').map(function (val) {
                  return val.trim();
                });
              } else if (typeof values === 'number') {
                toggleVals = [String(values)];
              }
              if (toggleVals.includes(optval)) {
                jQuery(this).find('input[type="checkbox"]:checked').trigger('click').trigger('change');
              }
            });
        }
      });
  }
  afterInit();

  jQuery('body').on('change', '.data-option-toggle-trigger', function () {
    let optval = jQuery(this).val();
    let optname = jQuery(this).attr('name');
    jQuery('body')
      .find('[data-toggle-name="' + optname + '"]')
      .hide();
    jQuery('body')
      .find('[data-toggle-name="' + optname + '"]')
      .each(function () {
        let values = jQuery(this).data('toggle-val');
        if (typeof values === 'string') {
          toggleVals = values.split(',').map(function (val) {
            return val.trim();
          });
        } else if (typeof values === 'number') {
          toggleVals = [String(values)];
        }
        if (toggleVals.includes(optval)) {
          jQuery(this).show();
        } else {
          jQuery(this).find('input[type="checkbox"]:checked').trigger('click').trigger('change');
        }
      });
  });

  jQuery('body').on('change', 'input[type="checkbox"].data-option-toggle-trigger', function () {
    let isChecked = jQuery(this).is(':checked');
    let optval = jQuery(this).val();
    let optname = jQuery(this).attr('name');
    jQuery('body')
      .find('[data-toggle-name="' + optname + '"]')
      .hide();
    if (isChecked) {
      jQuery('body')
        .find('[data-toggle-name="' + optname + '"]')
        .each(function () {
          let values = jQuery(this).data('toggle-val');
          if (typeof values === 'string') {
            toggleVals = values.split(',').map(function (val) {
              return val.trim();
            });
          } else if (typeof values === 'number') {
            toggleVals = [String(values)];
          }
          if (toggleVals.includes(optval)) {
            jQuery(this).show();
          }
        });
    } else {
      jQuery('body')
        .find('[data-toggle-name="' + optname + '"]')
        .each(function () {
          let values = jQuery(this).data('toggle-val');
          if (typeof values === 'string') {
            toggleVals = values.split(',').map(function (val) {
              return val.trim();
            });
          } else if (typeof values === 'number') {
            toggleVals = [String(values)];
          }
          if (toggleVals.includes(optval)) {
            jQuery(this).find('input[type="checkbox"]:checked').trigger('click').trigger('change');
          }
        });
    }
  });

  jQuery('body')
    .find('.ppsgenComponentLabel')
    .each(function () {
      var $label = jQuery(this);
      var labelText = $label.data('notice-label');
      var labelDesc = $label.data('notice-label-desc');

      if (labelText || labelDesc) {
        $label.empty().append(document.createTextNode(labelText || ''));

        if (labelDesc) {
          var $desc = jQuery('<div>', { class: 'ppsgenComponentLabelDesc' }).append(jQuery('<span>', { class: 'dashicons dashicons-info ppsgenComponentLabelDescIcon' }), jQuery('<div>', { class: 'ppsgenComponentLabelContent', text: labelDesc }));

          $label.append($desc);
        }
      }
    });
});

jQuery(function ($) {
  const icons = {
    solid: ppsgenFaIconsList.ppsgenFaIconsListSolid,
    brands: ppsgenFaIconsList.ppsgenFaIconsListBrand,
    regular: ppsgenFaIconsList.ppsgenFaIconsListRegular,
  };
  let currentTargetInput = null;
  function renderIcons() {
    const search = $('.ppsgenIconSearch').val().toLowerCase();
    $('.ppsgenIconTab').each(function () {
      const tab = $(this).data('tab');
      const grid = $(this).find('.ppsgenIconGrid');
      grid.empty();
      icons[tab].forEach((icon) => {
        if (search === '' || icon.includes(search)) {
          const iconFull = `ppsgen-fa-${tab} ${icon}`;
          const el = $('<div class="ppsgenIconItem"></div>');
          el.html(`<i class="${iconFull}"></i>`);
          el.attr('data-icon', icon);
          el.on('click', function () {
            if (currentTargetInput) {
              currentTargetInput.val(iconFull);
              closeIconOverlay();
            }
          });
          grid.append(el);
        }
      });
    });
    if (search !== '') {
      $('.ppsgenIconTabs div[data-tab]').removeClass('active');
      $('.ppsgenIconTab').show();
    } else {
      const activeTab = $('.ppsgenIconTabs div.active').data('tab');
      $('.ppsgenIconTab').each(function () {
        if ($(this).data('tab') === activeTab) {
          $(this).show();
        } else {
          $(this).hide();
        }
      });
    }
  }
  function openIconOverlay(target) {
    currentTargetInput = $(`.${target}`);
    renderIcons();
    $('#ppsgenIconOverlay').show();
  }
  function closeIconOverlay() {
    $('#ppsgenIconOverlay').hide();
    currentTargetInput = null;
  }

  $('.ppsgenShowIconBtn').on('click', function () {
    const target = $(this).data('target');
    openIconOverlay(target);
  });
  $('.ppsgenIconClose, .ppsgenIconOverlayBg').on('click', function () {
    closeIconOverlay();
  });
  $('.ppsgenIconSearch').on('input', function () {
    renderIcons();
  });
  $('.ppsgenIconTabs div[data-tab]').on('click', function () {
    const tab = $(this).data('tab');
    $('.ppsgenIconTabs div[data-tab]').removeClass('active');
    $(this).addClass('active');
    $('.ppsgenIconTab').hide();
    $(`.ppsgenIconTab[data-tab="${tab}"]`).show();
  });
  $('.ppsgenIconTabs div[data-tab]').first().click();
});

jQuery(document).ready(function () {
  jQuery(function ($) {
    const icons = {
      undraw: ppsgenUndrList,
    };

    let basePath = ppsgenAjax.links.icon_list_url;

    let currentTargetInput = null;
    let currentPage = 1;
    const perPage = 40;
    const svgCache = {};

    function getActiveTab() {
      return $('.ppsgenIconSvgTabs div.active').data('tab');
    }

    function getIconColor() {
      return $('input[name="svg_icon_color"]').val() || '#000000';
    }

    function buildPath(tab, icon) {
      return basePath + '/' + tab + '/icons/' + icon + '.svg';
    }

    function filterIcons(tab) {
      const search = $('.ppsgenIconSvgSearch').val().toLowerCase();

      return icons[tab].filter(function (icon) {
        if (search === '') return true;

        const full = tab + '_' + icon;

        return icon.toLowerCase().includes(search) || full.toLowerCase().includes(search) || tab.toLowerCase().includes(search);
      });
    }

    function renderPagination(total) {
      const pages = Math.ceil(total / perPage);
      const container = $('.ppsgenIconSvgPagination');

      container.empty();

      if (pages <= 1) return;

      for (let i = 1; i <= pages; i++) {
        const btn = $('<div class="ppsgenIconSvgPage"></div>');
        btn.text(i);

        if (i === currentPage) {
          btn.addClass('active');
        }

        btn.on('click', function () {
          currentPage = i;
          renderIcons();
        });

        container.append(btn);
      }
    }

    function loadSvg(tab, icon, container) {
      const color = getIconColor();
      const cacheKey = tab + '_' + icon;

      if (svgCache[cacheKey]) {
        const svg = $(svgCache[cacheKey]).clone();
        svg.css('--primary-svg-color', color);
        container.html(svg);

        return;
      }

      const url = buildPath(tab, icon);

      jQuery.get(
        url,
        function (data) {
          const svg = $(data).find('svg');

          svgCache[cacheKey] = svg.clone();

          svg.css('--primary-svg-color', color);

          container.html(svg);
        },
        'xml'
      );
    }

    function renderIcons() {
      const tab = getActiveTab();

      const grid = $(`.ppsgenIconSvgTab[data-tab="${tab}"] .ppsgenIconSvgGrid`);

      const filtered = filterIcons(tab);

      const start = (currentPage - 1) * perPage;
      const end = start + perPage;

      const pageIcons = filtered.slice(start, end);

      grid.empty();

      pageIcons.forEach(function (icon) {
        const item = $('<div class="ppsgenIconSvgItem"></div>');

        item.attr('data-icon', icon);
        item.attr('data-tab', tab);

        loadSvg(tab, icon, item);

        item.on('click', function () {
          if (currentTargetInput) {
            currentTargetInput.val(tab + '_' + icon);

            closeOverlay();
          }
        });

        grid.append(item);
      });

      renderPagination(filtered.length);
    }

    function openOverlay(target) {
      currentTargetInput = $('.' + target);

      currentPage = 1;

      let value = currentTargetInput.val();

      if (value) {
        let parts = value.split('_');

        if (parts.length >= 2) {
          let tab = parts[0];
          let icon = parts.slice(1).join('_');

          if (icons[tab]) {
            $('.ppsgenIconSvgTabs div').removeClass('active');
            $(`.ppsgenIconSvgTabs div[data-tab="${tab}"]`).addClass('active');

            $('.ppsgenIconSvgTab').hide();
            $(`.ppsgenIconSvgTab[data-tab="${tab}"]`).show();

            $('.ppsgenIconSvgSearch').val(icon);
          }
        }
      }

      renderIcons();

      $('#ppsgenIconSvgOverlay').show();
    }

    function closeOverlay() {
      $('#ppsgenIconSvgOverlay').hide();
      currentTargetInput = null;
    }

    $('.ppsgenShowUndrIconBtn').on('click', function () {
      const target = $(this).data('target');

      openOverlay(target);
    });

    $('.ppsgenIconSvgClose, .ppsgenIconSvgOverlayBg').on('click', function () {
      closeOverlay();
    });

    $('.ppsgenIconSvgSearch').on('input', function () {
      currentPage = 1;

      renderIcons();
    });

    $('.ppsgenIconSvgTabs div[data-tab]').on('click', function () {
      const tab = $(this).data('tab');

      $('.ppsgenIconSvgTabs div').removeClass('active');
      $(this).addClass('active');

      $('.ppsgenIconSvgTab').hide();
      $(`.ppsgenIconSvgTab[data-tab="${tab}"]`).show();

      currentPage = 1;

      renderIcons();
    });

    $('input[name="svg_icon_color"]').on('input', function () {
      const color = $(this).val();

      $('.ppsgenIconSvgItem svg').each(function () {
        $(this).css('--primary-svg-color', color);
      });
    });

    $('.ppsgenIconSvgTabs div').first().click();
  });
});

jQuery(document).ready(function ($) {
  let bgMediaFrame = null;
  let bgAssetTarget = null;

  function getBgAssets() {
    if (typeof ppsgenAjax === 'undefined' || !Array.isArray(ppsgenAjax.background_assets)) {
      return [];
    }
    return ppsgenAjax.background_assets;
  }

  function normalizeMediaPath(url) {
    if (!url) {
      return '';
    }
    try {
      const parsed = new URL(url, window.location.origin);
      if (parsed.origin === window.location.origin) {
        return parsed.pathname + parsed.search;
      }
    } catch (e) {}
    return url;
  }

  function getPreviewUrl(path) {
    if (!path) {
      return '';
    }
    if (/^https?:\/\//i.test(path) || path.indexOf('//') === 0) {
      return path;
    }
    if (path.charAt(0) === '/') {
      return window.location.origin + path;
    }
    return window.location.origin + '/' + path.replace(/^\/+/, '');
  }

  function updateBgPreview($picker) {
    const value = ($picker.find('.ppsgenBgImageInput').val() || '').trim();
    const $preview = $picker.find('.ppsgenBgImagePreview');
    if (!value) {
      $preview.attr('src', '').hide();
      $picker.removeClass('ppsgenBgImagePickerHasPreview');
      return;
    }
    $preview.attr('src', getPreviewUrl(value)).show();
    $picker.addClass('ppsgenBgImagePickerHasPreview');
  }

  function setBgPickerValue($picker, value) {
    $picker.find('.ppsgenBgImageInput').val(value).trigger('input').trigger('change');
    updateBgPreview($picker);
  }

  function ensureBgAssetOverlay() {
    if ($('#ppsgenBgAssetOverlay').length) {
      return;
    }

    $('body').append(
      '<div id="ppsgenBgAssetOverlay" class="ppsgenBgAssetOverlay" style="display:none;">' +
        '<div class="ppsgenBgAssetOverlayBg"></div>' +
        '<div class="ppsgenBgAssetBox">' +
          '<div class="ppsgenBgAssetHeader">' +
            '<input type="search" class="ppsgenBgAssetSearch" placeholder="Search images" />' +
            '<button type="button" class="ppsgenBgAssetClose" aria-label="Close">&times;</button>' +
          '</div>' +
          '<div class="ppsgenBgAssetGrid"></div>' +
        '</div>' +
      '</div>'
    );
  }

  function renderBgAssets() {
    ensureBgAssetOverlay();
    const assets = getBgAssets();
    const query = ($('#ppsgenBgAssetOverlay .ppsgenBgAssetSearch').val() || '').toLowerCase();
    const $grid = $('#ppsgenBgAssetOverlay .ppsgenBgAssetGrid');
    $grid.empty();

    const filtered = assets.filter(function (asset) {
      const haystack = ((asset.label || '') + ' ' + (asset.source || '') + ' ' + (asset.path || '')).toLowerCase();
      return query === '' || haystack.indexOf(query) !== -1;
    });

    if (!filtered.length) {
      $grid.append('<div class="ppsgenBgAssetEmpty">No images found.</div>');
      return;
    }

    filtered.forEach(function (asset) {
      const $item = $('<button type="button" class="ppsgenBgAssetItem"></button>');
      $('<span class="ppsgenBgAssetThumb"></span>').append($('<img alt="" />').attr('src', asset.preview || asset.path)).appendTo($item);
      $('<span class="ppsgenBgAssetName"></span>').text(asset.label || asset.path).appendTo($item);
      $('<span class="ppsgenBgAssetSource"></span>').text(asset.source || '').appendTo($item);
      $item.on('click', function () {
        if (bgAssetTarget) {
          setBgPickerValue(bgAssetTarget, asset.path || '');
        }
        closeBgAssetOverlay();
      });
      $grid.append($item);
    });
  }

  function openBgAssetOverlay($picker) {
    const assets = getBgAssets();
    if (!assets.length) {
      window.alert('No background images are available.');
      return;
    }
    bgAssetTarget = $picker;
    ensureBgAssetOverlay();
    $('#ppsgenBgAssetOverlay .ppsgenBgAssetSearch').val('');
    renderBgAssets();
    $('#ppsgenBgAssetOverlay').show();
  }

  function closeBgAssetOverlay() {
    $('#ppsgenBgAssetOverlay').hide();
    bgAssetTarget = null;
  }

  $('.ppsgenBgImagePicker').each(function () {
    updateBgPreview($(this));
  });

  $(document).on('input change', '.ppsgenBgImageInput', function () {
    updateBgPreview($(this).closest('.ppsgenBgImagePicker'));
  });

  $(document).on('click', '.ppsgenBgMediaBtn', function () {
    const $picker = $(this).closest('.ppsgenBgImagePicker');
    if (typeof wp === 'undefined' || !wp.media) {
      window.alert('WordPress Media Library is not available on this page.');
      return;
    }

    bgMediaFrame = wp.media({
      title: 'Select background image',
      button: {
        text: 'Use image',
      },
      multiple: false,
      library: {
        type: 'image',
      },
    });

    bgMediaFrame.on('select', function () {
      const attachment = bgMediaFrame.state().get('selection').first().toJSON();
      setBgPickerValue($picker, normalizeMediaPath(attachment.url || ''));
    });

    bgMediaFrame.open();
  });

  $(document).on('click', '.ppsgenBgAssetsBtn', function () {
    openBgAssetOverlay($(this).closest('.ppsgenBgImagePicker'));
  });

  $(document).on('click', '.ppsgenBgAssetClose, .ppsgenBgAssetOverlayBg', function () {
    closeBgAssetOverlay();
  });

  $(document).on('input', '.ppsgenBgAssetSearch', function () {
    renderBgAssets();
  });
});

jQuery(document).ready(function ($) {
  function getGoogleFontName(value) {
    if (!value || value.indexOf('google:') !== 0) {
      return '';
    }
    return value.substring(7).trim();
  }

  function loadGoogleFont(font) {
    if (!font) {
      return;
    }
    const id = 'ppsgen-google-font-' + font.toLowerCase().replace(/[^a-z0-9]+/g, '-');
    if (document.getElementById(id)) {
      return;
    }
    const link = document.createElement('link');
    link.id = id;
    link.rel = 'stylesheet';
    link.href = 'https://fonts.googleapis.com/css?family=' + encodeURIComponent(font).replace(/%20/g, '+') + '&display=swap';
    document.head.appendChild(link);
  }

  function updateFontFamilyPreview($select) {
    const value = $select.val() || '';
    const googleFont = getGoogleFontName(value);
    const $selected = $select.find('option:selected');
    const label = googleFont || $selected.text() || 'Aa';
    const $preview = $select.closest('.ppsgenFontFamilyPicker').find('.ppsgenFontFamilyPreview');
    const $previewText = $preview.find('.ppsgenFontFamilyPreviewText');

    if (googleFont) {
      loadGoogleFont(googleFont);
      $preview.css('font-family', '"' + googleFont.replace(/"/g, '') + '", sans-serif');
    } else {
      const selectedStyle = $selected.attr('style') || '';
      const match = selectedStyle.match(/font-family\\s*:\\s*([^;]+)/i);
      $preview.css('font-family', match ? match[1] : '');
    }

    $previewText.text(label === 'Default' || value === '' ? 'Aa' : label);
  }

  $('.ppsgenFontFamilySelect').each(function () {
    updateFontFamilyPreview($(this));
  });

  $(document).on('change', '.ppsgenFontFamilySelect', function () {
    updateFontFamilyPreview($(this));
  });
});

// Editor header Live switch mirrors the item's "Enabled" toggle inside the form, so Save stores it as before
jQuery(document).ready(function ($) {
  $('.ppsgenEditorLive[data-live-target]').each(function () {
    const $wrap = $(this);
    const $header = $wrap.find('.ppsgenEditorLiveInput');
    const $text = $wrap.find('.ppsgenEditorLiveText');
    const $inner = $('input[type="checkbox"][name="' + $wrap.data('live-target') + '"]').first();
    if (!$inner.length) {
      $wrap.hide();
      return;
    }
    function paint(isOn) {
      $header.prop('checked', isOn);
      $text.text(isOn ? $text.data('on') : $text.data('off'));
      $wrap.toggleClass('ppsgenEditorLiveOn', isOn);
    }
    paint($inner.is(':checked'));
    $header.on('change', function () {
      const isOn = $header.is(':checked');
      $inner.prop('checked', isOn).trigger('change');
      paint(isOn);
      $wrap.addClass('ppsgenEditorLiveDirty');
    });
    $inner.on('change', function () {
      paint($inner.is(':checked'));
    });
  });
});

// Settings › Email sending: real test email through the default sending method
jQuery(document).ready(function ($) {
  $('.ppsgenSendTestEmailBtn').on('click', function (e) {
    e.preventDefault();
    const $btn = $(this);
    const $card = $btn.closest('.ppsgenEmailStatusCard');
    const $result = $card.find('.ppsgenEmailTestResult');
    const label = $btn.text();
    $btn.prop('disabled', true).text($btn.data('sending'));
    $.post(ajaxurl, {
      action: 'sendTestEmail',
      mod: 'dashboard',
      plugin: 'ppsgen',
      nonce_code: ppsgenAjax.nonce,
    })
      .done(function (response) {
        const data = response && response.data ? response.data : {};
        $result.text(data.message || '');
        $card.toggleClass('ppsgenEmailTestFailed', !(response && response.success));
      })
      .fail(function () {
        $result.text(ppsgenAjax.notice_statuses.error.default);
        $card.addClass('ppsgenEmailTestFailed');
      })
      .always(function () {
        $btn.prop('disabled', false).text(label);
      });
  });
});

// License ends soon: "Remind me later" hides the notice until the next milestone (14 or 7 days)
jQuery(document).on('click', '.ppsgenProLicenseExpiryDismiss', function (e) {
  e.preventDefault();
  const $link = jQuery(this);
  $link.closest('.ppsgenProLicenseStateNotice').slideUp(150);
  jQuery.post(ajaxurl, {
    plugin: 'ppsgen',
    mod: 'dashboard',
    action: 'dismissLicenseExpiryNotice',
    milestone: $link.data('milestone'),
    nonce_code: (window.ppsgenAjax && window.ppsgenAjax.nonce) || '',
  });
});

// Optional anonymous usage data: the one-time question and the Settings › Usage data switch
function ppsgenSetUsageTracking(value) {
  return jQuery.post(ajaxurl, {
    plugin: 'ppsgen',
    mod: 'dashboard',
    action: 'setUsageTracking',
    value: value,
    nonce_code: (window.ppsgenAjax && window.ppsgenAjax.nonce) || '',
  });
}
jQuery(document).on('click', '.ppsgenUsageChoice', function () {
  const $card = jQuery(this).closest('.ppsgenUsageAsk');
  $card.find('.ppsgenUsageChoice').prop('disabled', true);
  ppsgenSetUsageTracking(jQuery(this).data('value')).always(function (response) {
    const msg = response && response.data && response.data.message;
    $card.html(jQuery('<p class="ppsgenMuted"></p>').text(msg || ''));
    setTimeout(function () {
      $card.slideUp(200);
    }, 2500);
  });
});
jQuery(document).on('change', '.ppsgenUsageToggle', function () {
  const $input = jQuery(this);
  const enabled = $input.is(':checked');
  const $label = $input.closest('.ppsgenUsageSwitch');
  $input.prop('disabled', true);
  ppsgenSetUsageTracking(enabled ? 'yes' : 'no')
    .done(function (response) {
      if (response && response.success) {
        $label.toggleClass('ppsgenEditorLiveOn', enabled);
        $label.find('.ppsgenEditorLiveText').text(enabled ? 'Sharing anonymous counts' : 'Not sharing');
      } else {
        $input.prop('checked', !enabled);
      }
    })
    .fail(function () {
      $input.prop('checked', !enabled);
    })
    .always(function () {
      $input.prop('disabled', false);
    });
});

// Settings › Email sending: look up the sending domain's SPF / DMARC records again
jQuery(document).on('click', '.ppsgenRecheckDomain', function () {
  const $btn = jQuery(this);
  $btn.prop('disabled', true).text($btn.data('busy') || $btn.text());
  jQuery
    .post(ajaxurl, { plugin: 'ppsgen', mod: 'dashboard', action: 'recheckEmailDomain', nonce_code: (window.ppsgenAjax && window.ppsgenAjax.nonce) || '' })
    .always(function () {
      window.location.reload();
    });
});

// Home › This month: monthly summary email
function ppsgenMonthlyReport(data) {
  return jQuery
    .post(ajaxurl, jQuery.extend({ plugin: 'ppsgen', mod: 'dashboard', action: 'setMonthlyReport', nonce_code: (window.ppsgenAjax && window.ppsgenAjax.nonce) || '' }, data))
    .done(function (response) {
      jQuery().ppsgenShowPopupNotice({ status: response && response.success ? 'success' : 'error', msg: (response && response.data && response.data.message) || '', fadeOutTime: 5000 });
    });
}
jQuery(document).on('change', '.ppsgenMonthlyReportToggle', function () {
  ppsgenMonthlyReport({ enabled: jQuery(this).is(':checked') ? 1 : 0 });
});
jQuery(document).on('click', '.ppsgenMonthlyReportSample', function (e) {
  e.preventDefault();
  ppsgenMonthlyReport({ sample: 1 });
});
