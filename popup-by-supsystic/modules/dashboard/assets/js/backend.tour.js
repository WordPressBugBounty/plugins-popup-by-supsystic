jQuery(function ($) {
  const data = window.ppsgenTourData || {};
  const legacySteps = Array.isArray(data.steps) ? data.steps : [];
  const tours = data.tours && typeof data.tours === 'object'
    ? data.tours
    : { dashboard: { steps: legacySteps, start_url: data.start_url || window.location.href } };
  const defaultTourId = data.default_tour_id || 'dashboard';
  const activeTourKey = data.active_tour_key || data.storage_key || 'ppsgen_tour_active_id';
  const activeStepKey = data.active_step_key || data.step_key || 'ppsgen_tour_active_step';
  const seenPrefix = data.seen_prefix || 'ppsgen_tour_seen_';

  if (!Object.keys(tours).length) {
    return;
  }

  const $body = $('body');
  const $overlay = $('<div class="ppsgenTourOverlay" aria-hidden="true"></div>').appendTo($body);
  const $focus = $('<div class="ppsgenTourFocus" aria-hidden="true"></div>').appendTo($body);
  const $popover = $(
    '<div class="ppsgenTourPopover" role="dialog" aria-live="polite">' +
      '<button type="button" class="ppsgenTourClose" aria-label=""></button>' +
      '<div class="ppsgenTourCounter"></div>' +
      '<h3 class="ppsgenTourTitle"></h3>' +
      '<p class="ppsgenTourText"></p>' +
      '<div class="ppsgenTourActions">' +
        '<button type="button" class="ppsgenTourBtn ppsgenTourPrevBtn"></button>' +
        '<button type="button" class="ppsgenTourBtn ppsgenTourBtnPrimary ppsgenTourNextBtn"></button>' +
      '</div>' +
    '</div>'
  ).appendTo($body);

  $popover.find('.ppsgenTourClose').html('<span class="dashicons dashicons-no-alt"></span>').attr('aria-label', data.close_text || 'Close tutorial');
  $popover.find('.ppsgenTourPrevBtn').text(data.prev_text || 'Prev');

  let repositionTimer = null;
  let currentStepIndex = 0;
  let currentTourId = defaultTourId;

  function storageGet(key, fallback) {
    try {
      const value = window.localStorage.getItem(key);
      return value === null ? fallback : value;
    } catch (e) {
      return fallback;
    }
  }

  function storageSet(key, value) {
    try {
      window.localStorage.setItem(key, value);
    } catch (e) {}
  }

  function storageRemove(key) {
    try {
      window.localStorage.removeItem(key);
    } catch (e) {}
  }

  function getCurrentTab() {
    const url = new URL(window.location.href);
    return url.searchParams.get('tab') || 'dashboard';
  }

  function getCurrentTourFromUrl() {
    const url = new URL(window.location.href);
    const id = url.searchParams.get('ppsgen_tour_id');
    return id && tours[id] ? id : '';
  }

  function getRequestedTourId() {
    const fromUrl = getCurrentTourFromUrl();
    if (fromUrl) {
      return fromUrl;
    }
    const fromStorage = storageGet(activeTourKey, '');
    return fromStorage && tours[fromStorage] ? fromStorage : defaultTourId;
  }

  function getRequestedStep() {
    const url = new URL(window.location.href);
    const fromUrl = parseInt(url.searchParams.get('ppsgen_tour_step') || '', 10);
    if (!Number.isNaN(fromUrl)) {
      return fromUrl;
    }
    return parseInt(storageGet(activeStepKey, '0') || '0', 10) || 0;
  }

  function getTour(tourId) {
    return tours[tourId] || tours[defaultTourId] || {};
  }

  function getTourSteps(tourId) {
    const tour = getTour(tourId);
    return Array.isArray(tour.steps) ? tour.steps : [];
  }

  // Only a tour link (ppsgen_tour=1) continues a tour. A tour left open in storage must not take over other pages.
  function isTourActive() {
    const url = new URL(window.location.href);
    return url.searchParams.get('ppsgen_tour') === '1';
  }

  function setTourActive(active, tourId, index) {
    if (active) {
      storageSet(activeTourKey, tourId);
      storageSet(activeStepKey, String(index || 0));
    } else {
      storageRemove(activeTourKey);
      storageRemove(activeStepKey);
    }
  }

  function markTourSeen(tourId) {
    storageSet(seenPrefix + tourId, '1');
  }

  function hasTourBeenSeen(tourId) {
    return storageGet(seenPrefix + tourId, '') === '1';
  }

  function buildStepUrl(step, index, tourId) {
    const tour = getTour(tourId);
    const fallbackUrl = step.tab && step.tab === getCurrentTab() ? window.location.href : data.start_url || window.location.href;
    const url = new URL(step.url || tour.start_url || fallbackUrl, window.location.origin);
    url.searchParams.set('ppsgen_tour', '1');
    url.searchParams.set('ppsgen_tour_id', tourId);
    url.searchParams.set('ppsgen_tour_step', String(index));
    return url.toString();
  }

  function activateStepContext(step) {
    if (step.inner_tab) {
      const selector = '.ppsgenObjEditTabButton[data-tab="' + String(step.inner_tab).replace(/"/g, '\\"') + '"]';
      const $tabButton = $(selector).filter(':visible').first();
      if ($tabButton.length && !$tabButton.hasClass('ppsgenObjEditTabButtonActive')) {
        $tabButton.trigger('click');
      }
    }

    if (step.click_selector) {
      const $clickTarget = $(step.click_selector).filter(':visible').first();
      if ($clickTarget.length) {
        $clickTarget.trigger('click');
      }
    }
  }

  function goToStep(index, tourId) {
    tourId = tourId || currentTourId || defaultTourId;
    const steps = getTourSteps(tourId);
    if (!steps.length) {
      finishTour();
      return;
    }
    if (index < 0) {
      index = 0;
    }
    if (index >= steps.length) {
      finishTour();
      return;
    }

    currentTourId = tourId;
    currentStepIndex = index;
    setTourActive(true, tourId, index);
    markTourSeen(tourId);

    const step = steps[index];
    if (step.tab && step.tab !== getCurrentTab()) {
      const here = new URL(window.location.href);
      // Already redirected for this very step and still on another page: stop instead of redirecting in a loop
      if (here.searchParams.get('ppsgen_tour') === '1' && here.searchParams.get('ppsgen_tour_id') === tourId && here.searchParams.get('ppsgen_tour_step') === String(index)) {
        finishTour();
        return;
      }
      window.location.href = buildStepUrl(step, index, tourId);
      return;
    }
    if (window.history && window.history.replaceState) {
      window.history.replaceState({}, '', buildStepUrl(step, index, tourId));
    }
    renderStep(index, true, tourId);
  }

  function finishTour() {
    currentStepIndex = 0;
    setTourActive(false, currentTourId, 0);
    if (window.history && window.history.replaceState) {
      const url = new URL(window.location.href);
      url.searchParams.delete('ppsgen_tour');
      url.searchParams.delete('ppsgen_tour_id');
      url.searchParams.delete('ppsgen_tour_step');
      window.history.replaceState({}, '', url.toString());
    }
    $overlay.removeClass('ppsgenTourOverlayActive');
    $focus.removeClass('ppsgenTourFocusActive');
    $popover.removeClass('ppsgenTourPopoverActive');
  }

  function findTarget(selector) {
    if (!selector) {
      return $();
    }
    const selectors = selector.split(',').map((part) => part.trim()).filter(Boolean);
    for (let i = 0; i < selectors.length; i++) {
      const $target = $(selectors[i]).filter(':visible').first();
      if ($target.length) {
        return $target;
      }
    }
    return $();
  }

  function getFallbackRect() {
    const node = $('.ppsgenDashContentWrapper:visible, .ppsgenObjEditWrapper:visible, .overviewContentStyle:visible').get(0) || document.body;
    const rect = node.getBoundingClientRect();
    return {
      top: Math.max(24, rect.top + 24),
      left: Math.max(24, rect.left + 24),
      width: Math.min(620, Math.max(260, rect.width - 48)),
      height: 120,
    };
  }

  function placePopover(rect, placement) {
    const gap = 14;
    const popWidth = $popover.outerWidth();
    const popHeight = $popover.outerHeight();
    const viewportWidth = window.innerWidth;
    const viewportHeight = window.innerHeight;
    let top;
    let left;
    let finalPlacement = placement || 'bottom';

    if (finalPlacement === 'right') {
      top = rect.top + Math.min(32, rect.height / 2);
      left = rect.left + rect.width + gap;
      if (left + popWidth > viewportWidth - 16) {
        finalPlacement = 'bottom';
      }
    }

    if (finalPlacement === 'left') {
      top = rect.top + Math.min(32, rect.height / 2);
      left = rect.left - popWidth - gap;
      if (left < 16) {
        finalPlacement = 'bottom';
      }
    }

    if (finalPlacement === 'top') {
      top = rect.top - popHeight - gap;
      left = rect.left;
      if (top < 16) {
        finalPlacement = 'bottom';
      }
    }

    if (finalPlacement === 'bottom') {
      top = rect.top + rect.height + gap;
      left = rect.left;
      if (top + popHeight > viewportHeight - 16) {
        top = Math.max(16, rect.top - popHeight - gap);
        finalPlacement = 'top';
      }
    }

    left = Math.min(Math.max(16, left), viewportWidth - popWidth - 16);
    top = Math.min(Math.max(16, top), viewportHeight - popHeight - 16);

    $popover
      .removeClass('ppsgenTourPlacementTop ppsgenTourPlacementBottom ppsgenTourPlacementLeft ppsgenTourPlacementRight')
      .addClass('ppsgenTourPlacement' + finalPlacement.charAt(0).toUpperCase() + finalPlacement.slice(1))
      .css({ top: Math.round(top), left: Math.round(left) });
  }

  function renderStep(index, shouldScroll, tourId) {
    tourId = tourId || currentTourId || defaultTourId;
    const steps = getTourSteps(tourId);
    const step = steps[index] || {};
    activateStepContext(step);

    let $target = findTarget(step.selector || '');
    let rect;

    if (shouldScroll && $target.length) {
      $target.get(0).scrollIntoView({ block: 'center', inline: 'nearest', behavior: 'smooth' });
    }

    window.setTimeout(function () {
      $target = findTarget(step.selector || '');
      if ($target.length) {
        rect = $target.get(0).getBoundingClientRect();
      } else {
        rect = getFallbackRect();
      }

      const pad = parseInt(step.pad || '8', 10);
      $overlay.addClass('ppsgenTourOverlayActive');
      $focus
        .addClass('ppsgenTourFocusActive')
        .css({
          top: Math.max(8, Math.round(rect.top - pad)),
          left: Math.max(8, Math.round(rect.left - pad)),
          width: Math.round(Math.min(rect.width + pad * 2, window.innerWidth - 16)),
          height: Math.round(Math.min(rect.height + pad * 2, window.innerHeight - 16)),
        });

      $popover.find('.ppsgenTourCounter').text((index + 1) + ' / ' + steps.length);
      $popover.find('.ppsgenTourTitle').text(step.title || '');
      $popover.find('.ppsgenTourText').text(step.text || '');
      $popover.find('.ppsgenTourPrevBtn').prop('disabled', index === 0);
      $popover.find('.ppsgenTourNextBtn').text(index >= steps.length - 1 ? (data.finish_text || 'Finish') : (data.next_text || 'Next'));
      $popover.addClass('ppsgenTourPopoverActive').css({ top: 16, left: 16 });
      placePopover(rect, step.placement || 'bottom');
    }, $target.length ? 280 : 40);
  }

  function startTour(tourId) {
    tourId = tourId && tours[tourId] ? tourId : defaultTourId;
    goToStep(0, tourId);
  }

  $('.ppsgenTourStartBtn').on('click', function (e) {
    e.preventDefault();
    startTour($(this).data('tourId') || $(this).data('tour') || $(this).attr('data-ppsgen-tour') || defaultTourId);
  });

  $popover.on('click', '.ppsgenTourClose', function (e) {
    e.preventDefault();
    finishTour();
  });

  $popover.on('click', '.ppsgenTourPrevBtn', function (e) {
    e.preventDefault();
    goToStep(currentStepIndex - 1, currentTourId);
  });

  $popover.on('click', '.ppsgenTourNextBtn', function (e) {
    e.preventDefault();
    goToStep(currentStepIndex + 1, currentTourId);
  });

  $(document).on('keydown.ppsgenTour', function (e) {
    if (!isTourActive()) {
      return;
    }
    if (e.key === 'Escape' || e.keyCode === 27) {
      finishTour();
    }
  });

  $(window).on('resize.ppsgenTour scroll.ppsgenTour', function () {
    if (isTourActive()) {
      window.clearTimeout(repositionTimer);
      repositionTimer = window.setTimeout(function () {
        renderStep(currentStepIndex, false, currentTourId);
      }, 80);
    }
  });

  if (isTourActive()) {
    currentTourId = getRequestedTourId();
    currentStepIndex = getRequestedStep();
    goToStep(currentStepIndex, currentTourId);
    return;
  }

  if (data.auto_start !== false && data.auto_tour_map && typeof data.auto_tour_map === 'object') {
    const autoTourId = data.auto_tour_map[getCurrentTab()] || '';
    if (autoTourId && tours[autoTourId] && !hasTourBeenSeen(autoTourId)) {
      window.setTimeout(function () {
        startTour(autoTourId);
      }, 500);
    }
  }
});
