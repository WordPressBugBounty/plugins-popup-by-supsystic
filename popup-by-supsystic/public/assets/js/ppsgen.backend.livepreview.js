/**
 * Live preview for the drag-and-drop editors (popup, form, email template, automation and newsletter emails).
 *
 * The preview sits between the element builder and the element options and re-renders by itself shortly after
 * anything changes. It reuses each editor's own handlePreview(e, live) request, so it shows exactly what the
 * "Full preview" button shows. Other editor tabs (Design, Options…) get the preview on the right.
 */
(function ($) {
  'use strict';

  const l10n = window.ppsgenLivePreviewL10n || {};
  const text = (key, fallback) => l10n[key] || fallback;

  /**
   * Writes a whole HTML document into a preview iframe (live preview, Full preview, email views).
   * A frame written while it is still the initial about:blank document is not controlled by a service worker,
   * so where WordPress itself is served by one (WordPress Playground) none of its CSS, JS, fonts or images
   * load there: the popup lost its layout and its icons. So a frame first opens the plugin's blank page
   * (public/preview-blank.html, same origin) and is written once that has loaded; later writes go straight in.
   * A frame moved in the page reloads (back to about:blank, then the blank page): it waits again.
   * done(doc) runs after the write.
   */
  const BLANK_URL = l10n.blankUrl || '';
  const isInitialBlank = (frame) => {
    try {
      return frame.contentWindow.location.href === 'about:blank';
    } catch (e) {
      return true;
    }
  };
  const writeNow = (frame, html, done) => {
    const doc = frame.contentDocument || frame.contentWindow.document;
    doc.open();
    doc.write(html);
    doc.close();
    if (done) {
      done(doc);
    }
  };
  // Opens the blank page in a frame that is still about:blank (ahead of the first write, to save time later)
  const primeFrame = (frame) => {
    if (BLANK_URL && frame && isInitialBlank(frame) && frame.getAttribute('src') !== BLANK_URL) {
      frame.setAttribute('src', BLANK_URL);
    }
  };
  window.ppsgenFrameWrite = function (frame, html, done) {
    if (!frame) {
      return;
    }
    if (!BLANK_URL || !isInitialBlank(frame)) {
      writeNow(frame, html, done);
      return;
    }
    // The latest write wins while waiting
    frame.ppsgenQueuedWrite = () => writeNow(frame, html, done);
    if (frame.ppsgenWaiting) {
      return;
    }
    frame.ppsgenWaiting = true;
    const flush = () => {
      frame.removeEventListener('load', onLoad);
      clearTimeout(timer);
      frame.ppsgenWaiting = false;
      const run = frame.ppsgenQueuedWrite;
      frame.ppsgenQueuedWrite = null;
      if (run) {
        try {
          run();
        } catch (e) {
          // The blank page couldn't be opened in a frame (e.g. a server forbidding frames): plain about:blank
          frame.removeAttribute('src');
          frame.addEventListener('load', () => writeNow(frame, html, done), { once: true });
          frame.setAttribute('src', 'about:blank');
        }
      }
    };
    const onLoad = () => {
      if (!isInitialBlank(frame)) {
        flush();
      }
    };
    // Never wait forever (a navigation that never finishes): write the old way after a few seconds
    const timer = setTimeout(flush, 4000);
    frame.addEventListener('load', onLoad);
    if (frame.getAttribute('src') !== BLANK_URL) {
      frame.setAttribute('src', BLANK_URL);
    }
  };
  window.ppsgenPrimeFrame = primeFrame;
  const STORAGE_KEY = 'ppsgenLivePreview';
  const DEVICES = ['pc', 'tablet', 'mobile'];
  const DEVICE_ICONS = { pc: 'desktop', tablet: 'tablet', mobile: 'smartphone' };
  const BUILDER_DEVICE_BUTTONS = { pc: '.ppsgenCfbSizeChangerBtnPc', tablet: '.ppsgenCfbSizeChangerBtnTab', mobile: '.ppsgenCfbSizeChangerBtnMob' };
  // Keeps the preview calm while typing: the popup entrance animation (.ppsgenAnimated) and transitions
  // finish at once, links and forms do nothing. Decorative animations of the design itself (shimmer,
  // swinging, pulsing…) keep their real timing: shortening every animation made infinite loops flicker.
  const LIVE_HEAD =
    '<style id="ppsgen-live-preview-calm">.ppsgenAnimated,.ppsgenAnimated::before,.ppsgenAnimated::after{animation-duration:1ms!important;animation-delay:0s!important;}' +
    '*,*::before,*::after{transition-duration:1ms!important;transition-delay:0s!important;}</style>' +
    '<script>document.addEventListener("click",function(e){var a=e.target&&e.target.closest?e.target.closest("a[href]"):null;if(a){e.preventDefault();}},true);' +
    'document.addEventListener("submit",function(e){e.preventDefault();},true);<\/script>' +
    '<style id="ppsgen-live-preview-pick">[data-ppsgen-hover]{outline:2px dashed #4f6bed!important;outline-offset:2px;cursor:pointer!important;}' +
    '[data-ppsgen-selected]{outline:2px solid #4f6bed!important;outline-offset:2px;}' +
    '[data-ppsgen-editing]{outline:2px solid #f59e0b!important;outline-offset:3px;cursor:text!important;caret-color:#f59e0b;}' +
    '.ppsgen-form-inputTooltipText[data-ppsgen-editing]{display:block!important;visibility:visible!important;opacity:1!important;}' +
    '#ppsgen-live-badge{position:absolute;z-index:2147483647;display:none;padding:0 .5em;border-radius:3px;background:#4f6bed;color:#fff;font:600 11px/1.7 -apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;white-space:nowrap;pointer-events:none;}' +
    '#ppsgen-live-tools{position:absolute;z-index:2147483647;display:none;gap:2px;padding:2px;border-radius:4px;background:#4f6bed;box-shadow:0 1px 4px rgba(0,0,0,.3);font:600 13px/1 -apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;}' +
    '#ppsgen-live-tools button{all:unset;box-sizing:border-box;min-width:1.9em;padding:.35em .5em;border-radius:3px;color:#fff;text-align:center;cursor:pointer;}' +
    '#ppsgen-live-tools button:hover{background:rgba(255,255,255,.22);}#ppsgen-live-tools button[disabled]{opacity:.35;cursor:default;background:none;}</style>';

  const readState = () => {
    try {
      const state = JSON.parse(window.localStorage.getItem(STORAGE_KEY) || '{}');
      return state && typeof state === 'object' ? state : {};
    } catch (e) {
      return {};
    }
  };
  const writeState = (patch) => {
    try {
      window.localStorage.setItem(STORAGE_KEY, JSON.stringify(Object.assign(readState(), patch)));
    } catch (e) {}
  };

  class PpsgenLivePreview {
    constructor() {
      const state = readState();
      this.editor = null;
      this.cfg = null;
      this.$dock = null;
      this.frames = [];
      this.visibleFrame = 0;
      this.signature = null;
      this.changedAt = 0;
      this.request = null;
      this.again = false;
      this.shown = false;
      this.lastHtml = '';
      this.swapToken = 0;
      this.device = DEVICES.includes(state.device) ? state.device : 'pc';
      // The preview is always shown (the hide button was removed), so an older stored "collapsed" state is ignored
      this.collapsed = false;
      this.overflowFixed = [];
      this.bound = false;
      // Step picked with the step bar (PRO "Go to step" popups); null = the popup's own default step
      this.step = null;
      // Field key whose step was last followed on selection (see markSelected)
      this.stepFollowedKey = '';
    }

    /**
     * @param {object} editor Editor instance with handlePreview(e, live) returning the jqXHR when live.
     * @param {object} cfg    left/right selectors, builderTab, sideTabs, widths, fullButton.
     */
    attach(editor, cfg) {
      this.cfg = Object.assign({ builderTab: null, sideTabs: [], widths: { pc: 1280, tablet: 820, mobile: 390 }, fullButton: '' }, cfg || {});
      if (window.ppsgenBuilderOutline) {
        window.ppsgenBuilderOutline.attach(editor);
      }
      if (window.ppsgenUnsavedGuard) {
        window.ppsgenUnsavedGuard.attach(editor);
      }
      if (!$(this.cfg.left).length || typeof editor.handlePreview !== 'function') {
        return;
      }
      this.editor = editor;
      this.signature = null;
      if (!this.$dock || !document.body.contains(this.$dock[0])) {
        this.build();
      }
      this.bind();
      this.place();
      this.tick(true);
    }

    isOn() {
      return !!(this.$dock && this.editor && !this.collapsed && this.$dock.is(':visible'));
    }

    build() {
      const $source = $('#ppsgenPreviewFrame').first();
      const frameClass = ($source.attr('class') || '').trim();
      const frameStyle = $source.attr('style') || '';
      const deviceButtons = DEVICES.map(
        (device) =>
          `<button type="button" class="ppsgenLivePreviewDevice" data-device="${device}" title="${text(device, device)}" aria-label="${text(device, device)}"><span class="dashicons dashicons-${DEVICE_ICONS[device]}"></span></button>`
      ).join('');
      this.$dock = $(`
        <section class="ppsgenLivePreview" aria-label="${text('title', 'Live preview')}">
          <div class="ppsgenLivePreviewBar">
            <span class="ppsgenLivePreviewTitle"><span class="ppsgenLivePreviewDot"></span><span class="ppsgenLivePreviewStatus">${text('title', 'Live preview')}</span></span>
            <span class="ppsgenLivePreviewDevices">${deviceButtons}</span>
            <span class="ppsgenLivePreviewScale"></span>
            <span class="ppsgenLivePreviewActions">
              <button type="button" class="ppsgenLivePreviewRefresh" title="${text('refresh', 'Refresh')}" aria-label="${text('refresh', 'Refresh')}"><span class="dashicons dashicons-update"></span></button>
              <button type="button" class="ppsgenLivePreviewFull" title="${text('full', 'Full preview')}" aria-label="${text('full', 'Full preview')}"><span class="dashicons dashicons-editor-expand"></span></button>
            </span>
          </div>
          <div class="ppsgenLivePreviewStage">
            <div class="ppsgenLivePreviewViewport">
              <iframe class="ppsgenLivePreviewFrame ${frameClass}" style="${frameStyle}" title="${text('title', 'Live preview')}"></iframe>
              <iframe class="ppsgenLivePreviewFrame ${frameClass}" style="${frameStyle}" title="${text('title', 'Live preview')}" aria-hidden="true"></iframe>
            </div>
          </div>
          <button type="button" class="ppsgenLivePreviewRail" title="${text('show', 'Show live preview')}"><span class="dashicons dashicons-visibility"></span><span>${text('title', 'Live preview')}</span></button>
        </section>`);
      this.frames = this.$dock.find('iframe').toArray();
      // Open the blank page now, so the frames are ready by the first render (see ppsgenFrameWrite)
      this.frames.forEach(primeFrame);
      this.visibleFrame = 0;
      $(this.frames[0]).addClass('ppsgenLivePreviewFrameOn');
      this.$dock.toggleClass('ppsgenLivePreviewCollapsed', this.collapsed);
      this.setDevice(this.device, false);

      this.$dock.on('click', '.ppsgenLivePreviewDevice', (e) => this.setDevice($(e.currentTarget).data('device'), true));
      this.$dock.on('click', '.ppsgenLivePreviewRefresh', () => this.render());
      this.$dock.on('click', '.ppsgenLivePreviewFull', () => this.cfg.fullButton && $(this.cfg.fullButton).first().trigger('click'));
      this.$dock.on('click', '.ppsgenLivePreviewRail', () => this.setCollapsed(false));

      if (window.ResizeObserver) {
        new ResizeObserver(() => this.fit()).observe(this.$dock.find('.ppsgenLivePreviewStage')[0]);
      }
    }

    bind() {
      if (this.bound) {
        return;
      }
      this.bound = true;
      // Tab switches move the preview next to whatever is being edited
      $(document).on('click.ppsgenLive', '.ppsgenObjEditTabButton, .ppsgenChainStepBtn', () => setTimeout(() => this.place(), 0));
      // The builder's own screen-size buttons switch the preview device too
      $(document).on('click.ppsgenLive', '.ppsgenCfbSizeChangerBtn', (e) => {
        const $btn = $(e.currentTarget);
        const device = $btn.is('.ppsgenCfbSizeChangerBtnMob') ? 'mobile' : $btn.is('.ppsgenCfbSizeChangerBtnTab') ? 'tablet' : 'pc';
        this.setDevice(device, false);
      });
      // Typing and picking react quickly; the interval catches changes made by scripts (color pickers, drag and drop)
      $(document).on('input.ppsgenLive change.ppsgenLive', () => setTimeout(() => this.tick(false), 250));
      $(document).on('mouseup.ppsgenLive', '.ppsgenCfbFieldsWrapper', () => setTimeout(() => this.tick(false), 250));
      setInterval(() => this.tick(false), 700);
      $(window).on('resize.ppsgenLive', () => this.layout());
      $(document).on('click.ppsgenLive', '[data-field-key]', () => setTimeout(() => this.markSelected(true), 60));
      // Clicking anywhere on the page outside the preview ends typing in the preview
      $(document).on('mousedown.ppsgenLive', () => {
        if (this.editing && this.editing.finish) {
          this.editing.finish();
        }
      });
    }

    activeTab() {
      return $('.ppsgenObjEditTabButtonActive').first().data('tab') || null;
    }

    place() {
      if (!this.$dock || !this.cfg) {
        return;
      }
      const tab = this.activeTab();
      const $left = $(this.cfg.left).first();
      const $grid = $left.parent();
      const $wrapper = $('.ppsgenObjEditWrapper').first();
      let mode = 'off';
      if (!this.cfg.builderTab || tab === this.cfg.builderTab) {
        mode = 'between';
      } else if (this.cfg.sideTabs.includes(tab)) {
        mode = 'side';
      }
      const before = this.$dock.parent()[0];
      if (mode === 'between') {
        if (!this.$dock.prev().is($left)) {
          this.$dock.insertAfter($left);
        }
        $grid.addClass('ppsgenLiveGrid');
        $left.addClass('ppsgenLiveLeft');
        $grid.children(this.cfg.right).addClass('ppsgenLiveRight');
      } else if (mode === 'side') {
        if (this.$dock.parent()[0] !== $wrapper[0]) {
          $wrapper.append(this.$dock);
        }
      }
      $wrapper.toggleClass('ppsgenLiveSide', mode === 'side');
      this.$dock.toggleClass('ppsgenLivePreviewOff', mode === 'off');
      this.mode = mode;
      this.fixOverflow(mode === 'between' ? this.$dock : null);
      if (before !== this.$dock.parent()[0] && this.lastHtml) {
        // Moving an iframe reloads it, so put the last render back
        this.write(this.frames[this.visibleFrame], this.lastHtml);
      }
      this.layout();
    }

    // Sticky positioning stops working inside scrolling boxes, so open them up while the preview is between columns
    fixOverflow($from) {
      this.overflowFixed.forEach((el) => el.classList.remove('ppsgenLiveOverflowFix'));
      this.overflowFixed = [];
      if (!$from) {
        return;
      }
      let el = $from[0].parentElement;
      while (el && el !== document.body) {
        const style = window.getComputedStyle(el);
        if (/(auto|scroll|hidden)/.test(style.overflowX + style.overflowY)) {
          el.classList.add('ppsgenLiveOverflowFix');
          this.overflowFixed.push(el);
        }
        el = el.parentElement;
      }
    }

    layout() {
      if (!this.$dock) {
        return;
      }
      const bar = document.querySelector('.ppsgenTopBarWrapper');
      let top = 32;
      if (bar && /sticky|fixed/.test(window.getComputedStyle(bar).position)) {
        top = (parseFloat(window.getComputedStyle(bar).top) || 0) + bar.offsetHeight;
      }
      document.documentElement.style.setProperty('--ppsgen-live-top', `${Math.round(top + 12)}px`);
      const $grid = this.$dock.parent('.ppsgenLiveGrid');
      if ($grid.length) {
        const width = $grid.width();
        $grid.toggleClass('ppsgenLiveGridNarrow', width < 1180 && width >= 760);
        $grid.toggleClass('ppsgenLiveGridStack', width < 760);
      }
      this.fit();
    }

    fit() {
      if (!this.$dock || !this.cfg) {
        return;
      }
      const $stage = this.$dock.find('.ppsgenLivePreviewStage');
      const stageWidth = $stage.width();
      const stageHeight = $stage.height();
      if (!stageWidth || !stageHeight) {
        return;
      }
      const deviceWidth = this.cfg.widths[this.device] || this.cfg.widths.pc;
      const scale = Math.min(1, stageWidth / deviceWidth);
      this.$dock.find('.ppsgenLivePreviewViewport').css({ width: Math.floor(deviceWidth * scale), height: stageHeight });
      $(this.frames).css({ width: deviceWidth, height: Math.ceil(stageHeight / scale), transform: `scale(${scale})` });
      this.$dock.find('.ppsgenLivePreviewScale').text(`${Math.round(scale * 100)}%`);
      this.scale = scale;
    }

    setDevice(device, syncBuilder) {
      if (!DEVICES.includes(device)) {
        return;
      }
      this.device = device;
      writeState({ device });
      if (this.$dock) {
        this.$dock.find('.ppsgenLivePreviewDevice').each((_, btn) => $(btn).toggleClass('ppsgenLivePreviewDeviceOn', $(btn).data('device') === device).attr('aria-pressed', $(btn).data('device') === device ? 'true' : 'false'));
        this.$dock.attr('data-device', device);
      }
      if (syncBuilder) {
        const $builderBtn = $(BUILDER_DEVICE_BUTTONS[device]).filter(':visible').first();
        if ($builderBtn.length && !$builderBtn.hasClass('ppsgenCfbSizeChangerBtnActive')) {
          $builderBtn.trigger('click');
        }
      }
      this.fit();
    }

    setCollapsed(collapsed) {
      this.collapsed = collapsed;
      writeState({ collapsed });
      this.$dock.toggleClass('ppsgenLivePreviewCollapsed', collapsed);
      if (!collapsed) {
        this.signature = null;
        setTimeout(() => {
          this.layout();
          this.tick(true);
        }, 0);
      }
    }

    // Fingerprint of everything that can change the preview, in two parts: the element options panel
    // ("options") and everything else ("page"). Selecting another element only refills the options panel,
    // so it must not count as a change (otherwise every click in the preview would redraw it).
    fingerprint() {
      const editor = this.editor;
      const $options = editor.optionsContainer && editor.optionsContainer.length ? editor.optionsContainer : $();
      const page = [];
      const options = [];
      const roots = [editor.form && editor.form[0], $options[0]].filter(Boolean);
      $(roots)
        .find('input, select, textarea')
        .each((_, el) => {
          if (el.type === 'button' || el.type === 'submit' || el.type === 'file') {
            return;
          }
          const value = el.type === 'checkbox' || el.type === 'radio' ? (el.checked ? '1' : '0') : el.value;
          ($options.length && $.contains($options[0], el) ? options : page).push(`${el.name || el.className}=${value}`);
        });
      if (editor.fieldsContainer && editor.fieldsContainer.length) {
        editor.fieldsContainer.find('[data-field-key]').each((_, el) => {
          const parent = $(el).parent().closest('[data-field-key]').attr('data-field-key') || '';
          page.push(`#${el.getAttribute('data-field-key')}<${parent}`);
        });
      }
      if (window.tinyMCE && Array.isArray(window.tinyMCE.editors)) {
        window.tinyMCE.editors.forEach((ed) => {
          try {
            if (ed && !ed.removed && ed.initialized) {
              const element = ed.getElement ? ed.getElement() : null;
              (element && $options.length && $.contains($options[0], element) ? options : page).push(`@${ed.id}=${ed.getContent({ format: 'raw' })}`);
            }
          } catch (e) {}
        });
      }
      page.push(`template=${$('[name="data[chain][options][template_id]"]').val() || ''}`);
      return { page: page.join('\u0001'), options: options.join('\u0001'), key: String(editor.currentFieldKey || '') };
    }

    tick(force) {
      if (!this.editor || !this.$dock || document.hidden || !this.isOn() || this.mode === 'off' || this.editing) {
        return;
      }
      let current;
      try {
        current = this.fingerprint();
      } catch (e) {
        return;
      }
      const previous = this.signature;
      this.signature = current;
      // Another element was selected: outline it (and show its step). The builder can stop the click from
      // reaching the document, so the click handler alone isn't enough.
      if (previous && current.key !== previous.key) {
        this.markSelected(true);
      }
      let changed = force || !previous || current.page !== previous.page;
      if (!changed && current.options !== previous.options) {
        // Same element: its options were edited. Another element: it was only selected
        changed = current.key === previous.key;
      }
      if (changed) {
        if (window.ppsgenBuilderOutline) {
          window.ppsgenBuilderOutline.refresh();
        }
        this.changedAt = Date.now();
        clearTimeout(this.renderTimer);
        this.renderTimer = setTimeout(() => this.render(), force ? 0 : 350);
      }
    }

    render() {
      if (!this.editor || !this.isOn() || this.editing) {
        return;
      }
      if (this.request) {
        this.again = true;
        return;
      }
      this.shown = false;
      this.status('busy');
      let request;
      try {
        request = this.editor.handlePreview(null, true);
      } catch (e) {
        request = null;
      }
      if (!request || typeof request.always !== 'function') {
        this.status(this.shown ? 'ok' : 'error');
        return;
      }
      this.request = request;
      request.always(() => {
        this.request = null;
        if (!this.shown) {
          this.status('error');
        }
        if (this.again) {
          this.again = false;
          this.render();
        }
      });
    }

    status(state) {
      if (!this.$dock) {
        return;
      }
      const labels = { busy: text('updating', 'Updating…'), ok: text('title', 'Live preview'), error: text('error', 'Couldn’t update the preview') };
      this.$dock.attr('data-status', state);
      this.$dock.find('.ppsgenLivePreviewStatus').text(labels[state] || labels.ok);
    }

    write(frame, html) {
      const live = /<\/head>/i.test(html) ? html.replace(/<\/head>/i, `${LIVE_HEAD}</head>`) : LIVE_HEAD + html;
      window.ppsgenFrameWrite(frame, live, () => this.bindPicking(frame));
    }

    // Builder element for a field key (only elements the builder knows can be picked)
    builderField(key) {
      if (!key || !this.editor || !this.editor.fieldsContainer) {
        return $();
      }
      return this.editor.fieldsContainer
        .find('[data-field-key]')
        .filter((_, el) => el.getAttribute('data-field-key') === key || decodeURIComponent(el.getAttribute('data-field-key')) === key)
        .first();
    }

    // Field key of a preview node: data-fkey (popups, forms, emails) or the form's f_{key} classes
    pickKey(node) {
      for (let el = node; el && el.nodeType === 1; el = el.parentElement) {
        let key = el.getAttribute('data-fkey');
        if (!key && el.classList && el.classList.contains('ppsgen-form-input-wrapper')) {
          const input = el.querySelector('[data-fkey]');
          key = input ? input.getAttribute('data-fkey') : '';
        }
        if (!key) {
          const match = String(el.className || '').match(/(?:^|\s)f_([a-z0-9]+)(?:_wrapper)?(?:\s|$)/i);
          key = match ? match[1] : '';
        }
        if (key && this.builderField(key).length) {
          return { el, key };
        }
      }
      return null;
    }

    // Hover shows what a click will select; a click selects it in the builder and opens its options.
    // Clicking the selected element again selects the row / column around it.
    bindPicking(frame) {
      const doc = frame.contentDocument;
      if (!doc || !this.editor) {
        return;
      }
      const badge = () => {
        let el = doc.getElementById('ppsgen-live-badge');
        if (!el && doc.body) {
          el = doc.createElement('div');
          el.id = 'ppsgen-live-badge';
          doc.body.appendChild(el);
        }
        return el;
      };
      const clearHover = () => {
        doc.querySelectorAll('[data-ppsgen-hover]').forEach((n) => n.removeAttribute('data-ppsgen-hover'));
        const b = doc.getElementById('ppsgen-live-badge');
        if (b) {
          b.style.display = 'none';
        }
      };
      doc.addEventListener(
        'mouseover',
        (e) => {
          const hit = this.pickKey(e.target);
          clearHover();
          if (!hit) {
            return;
          }
          hit.el.setAttribute('data-ppsgen-hover', '1');
          const b = badge();
          if (!b) {
            return;
          }
          const type = decodeURIComponent(this.builderField(hit.key).attr('data-field-type') || '');
          const typeName = window.ppsgenBuilderOutline ? window.ppsgenBuilderOutline.typeLabel(type) : type;
          b.textContent = this.editableTarget(e.target, hit) ? `${typeName} · ${text('dblEdit', 'double-click to edit')}` : typeName;
          const rect = hit.el.getBoundingClientRect();
          const win = doc.defaultView;
          b.style.fontSize = `${Math.round(11 / (this.scale || 1))}px`;
          b.style.display = 'block';
          b.style.left = `${Math.max(0, rect.left + win.scrollX)}px`;
          b.style.top = `${Math.max(0, rect.top + win.scrollY - b.offsetHeight - 3)}px`;
        },
        true
      );
      doc.documentElement.addEventListener('mouseleave', clearHover);
      // The preview is for looking and picking only: nothing inside it reacts to clicks
      doc.addEventListener(
        'click',
        (e) => {
          e.preventDefault();
          e.stopPropagation();
          if (this.editing && this.editing.target.contains(e.target)) {
            return;
          }
          const tool = e.target && e.target.closest ? e.target.closest('#ppsgen-live-tools button') : null;
          if (tool) {
            if (!tool.disabled) {
              this.moveSelected(tool.getAttribute('data-move') === 'up' ? -1 : 1);
            }
            return;
          }
          const hit = this.pickKey(e.target);
          if (!hit) {
            return;
          }
          let key = hit.key;
          if (key === String(this.editor.currentFieldKey) && hit.el.parentElement) {
            const parent = this.pickKey(hit.el.parentElement);
            if (parent) {
              key = parent.key;
            }
          }
          this.selectInBuilder(key);
        },
        true
      );
      ['mousedown', 'mouseup'].forEach((type) => doc.addEventListener(type, (e) => e.stopPropagation(), true));
      // Double click on a title, text, button, field label, tooltip or field: type straight into the preview
      doc.addEventListener(
        'dblclick',
        (e) => {
          e.stopPropagation();
          if (this.editing) {
            return;
          }
          const hit = this.pickKey(e.target);
          if (hit && this.editableTarget(e.target, hit)) {
            e.preventDefault();
            this.startInlineEdit(doc, hit, e.target);
          }
        },
        true
      );
    }

    // Which option holds the text of an element type, and whether it is HTML (editor) or plain text
    inlineSpec(type) {
      return (
        {
          title: { name: 'title_text', html: false },
          button: { name: 'button_text', html: false },
          submit: { name: 'submit_text', html: false },
          textarea: { name: 'html_textarea_content', html: true },
          description: { name: 'html_description_text', html: true },
        }[type] || null
      );
    }

    /**
     * What a double click at `origin` would edit: { node, name, mode } with mode 'text', 'html' or 'placeholder'.
     * Form contact fields: the label edits the label, the tooltip edits the tooltip text, the field edits its placeholder.
     */
    editableTarget(origin, hit) {
      const wrapper = origin && origin.closest ? origin.closest('.ppsgen-form-input-wrapper') : null;
      if (wrapper) {
        const tooltip = origin.closest('.ppsgen-form-inputTooltip');
        if (tooltip) {
          const node = tooltip.querySelector('.ppsgen-form-inputTooltipText');
          return node ? { node, name: 'title', mode: 'text' } : null;
        }
        if (origin.closest('.ppsgen-form-inputLabelWrap')) {
          const node = wrapper.querySelector('.ppsgen-form-inputLabel');
          return node ? { node, name: 'label', mode: 'text' } : null;
        }
        const field = origin.closest('input, textarea') || wrapper.querySelector('.ppsgen-form-inputFieldWrap input, .ppsgen-form-inputFieldWrap textarea');
        if (field && (field.tagName === 'TEXTAREA' || /^(text|email|tel|number|url|password|search|date)$/i.test(field.type || 'text'))) {
          return { node: field, name: 'placeholder', mode: 'placeholder' };
        }
        // Submit buttons and other special fields fall through to their element type
      }
      const $col = this.builderField(hit.key);
      const spec = this.inlineSpec(decodeURIComponent($col.attr('data-field-type') || ''));
      if (!spec) {
        return null;
      }
      // The element that really holds the text (titles and buttons are wrapped a few times)
      let node = hit.el;
      while (node.children.length === 1 && node.children[0].textContent.trim() === node.textContent.trim() && !(spec.html && /^(P|UL|OL|H[1-6]|BLOCKQUOTE)$/.test(node.children[0].tagName))) {
        node = node.children[0];
      }
      if (/^(INPUT|TEXTAREA|SELECT|IMG)$/.test(node.tagName)) {
        return null;
      }
      return { node, name: spec.name, mode: spec.html ? 'html' : 'text' };
    }

    startInlineEdit(doc, hit, origin) {
      const spot = this.editableTarget(origin, hit);
      if (!spot) {
        return;
      }
      if (String(this.editor.currentFieldKey) !== hit.key) {
        this.selectInBuilder(hit.key);
      }
      const target = spot.node;
      const placeholder = spot.mode === 'placeholder';
      const read = () => (placeholder ? target.value : spot.mode === 'html' ? target.innerHTML : target.textContent);
      const original = placeholder ? target.getAttribute('placeholder') || '' : read();
      this.editing = { doc, target, key: hit.key, spot, read };
      clearTimeout(this.renderTimer);
      ['ppsgen-live-tools', 'ppsgen-live-badge'].forEach((id) => {
        const el = doc.getElementById(id);
        if (el) {
          el.style.display = 'none';
        }
      });
      target.setAttribute('data-ppsgen-editing', '1');
      if (placeholder) {
        // The field shows its placeholder as text while it is being edited
        target.value = original;
        target.focus();
        target.select();
      } else {
        target.setAttribute('contenteditable', spot.mode === 'html' ? 'true' : 'plaintext-only');
        target.focus();
        const range = doc.createRange();
        range.selectNodeContents(target);
        range.collapse(false);
        const selection = doc.getSelection();
        selection.removeAllRanges();
        selection.addRange(range);
      }
      const onInput = () => this.pushInline();
      const onKey = (e) => {
        if (e.key === 'Escape') {
          e.preventDefault();
          if (placeholder) {
            target.value = original;
          } else if (spot.mode === 'html') {
            target.innerHTML = original;
          } else {
            target.textContent = original;
          }
          this.pushInline();
          finish();
        } else if (e.key === 'Enter' && (spot.mode !== 'html' || placeholder) && target.tagName !== 'TEXTAREA') {
          e.preventDefault();
          finish();
        }
      };
      const finish = () => {
        if (!this.editing || this.editing.target !== target) {
          return;
        }
        target.removeEventListener('input', onInput);
        target.removeEventListener('keydown', onKey);
        target.removeEventListener('blur', finish);
        target.removeAttribute('contenteditable');
        target.removeAttribute('data-ppsgen-editing');
        if (placeholder) {
          target.setAttribute('placeholder', target.value);
          target.value = '';
        }
        this.editing = null;
        this.signature = null;
        this.tick(true);
      };
      this.editing.finish = finish;
      target.addEventListener('input', onInput);
      target.addEventListener('keydown', onKey);
      target.addEventListener('blur', finish);
    }

    // Copies the text being typed in the preview into the element's options (and the builder's data)
    pushInline() {
      const edit = this.editing;
      if (!edit) {
        return;
      }
      const value = edit.read();
      const name = edit.spot.name;
      const map = this.editor.fieldOptionsMap || {};
      if (map[edit.key]) {
        map[edit.key][name] = value;
      }
      if (String(this.editor.currentFieldKey) === edit.key) {
        const $input = this.editor.optionsContainer ? this.editor.optionsContainer.find(`[name="${name}"]`) : $();
        $input.val(value);
        if (window.tinymce && window.tinymce.get(name)) {
          window.tinymce.get(name).setContent(value);
        }
      }
      if (window.ppsgenBuilderOutline) {
        window.ppsgenBuilderOutline.refresh();
      }
      if (window.ppsgenUnsavedGuard) {
        window.ppsgenUnsavedGuard.mark(this.builderField(edit.key)[0]);
      }
    }

    selectInBuilder(key) {
      const $col = this.builderField(key);
      if (!$col.length) {
        return;
      }
      $col.parents('.ppsgenOutlineCollapsed').each((_, el) => {
        $(el).removeClass('ppsgenOutlineCollapsed');
        if (window.ppsgenBuilderOutline) {
          window.ppsgenBuilderOutline.collapsed.delete(el.getAttribute('data-field-key'));
        }
      });
      const $row = $col.children('.ppsgenOutlineRow').first();
      const $target = $row.length ? $row : $col;
      $target.trigger('click');
      if ($target[0].scrollIntoView) {
        $target[0].scrollIntoView({ block: 'nearest' });
      }
      setTimeout(() => this.markSelected(true), 0);
    }

    // Outlines the element selected in the builder inside the preview
    markSelected(scrollIntoView) {
      const frame = this.frames[this.visibleFrame];
      const doc = frame && frame.contentDocument;
      if (!doc || !doc.body) {
        return;
      }
      doc.querySelectorAll('[data-ppsgen-selected]').forEach((n) => n.removeAttribute('data-ppsgen-selected'));
      const tools = doc.getElementById('ppsgen-live-tools');
      if (tools) {
        tools.style.display = 'none';
      }
      const key = this.editor && this.editor.currentFieldKey ? String(this.editor.currentFieldKey) : '';
      if (!key) {
        return;
      }
      const node = [...doc.querySelectorAll('[data-fkey]')].find((n) => n.getAttribute('data-fkey') === key) || doc.querySelector(`.f_${key.replace(/[^a-z0-9]/gi, '')}`);
      if (!node) {
        return;
      }
      // A newly selected element of a step that isn't shown: show that step, so what is being edited is on
      // screen. Only once per selection — the step bar's arrows must still be able to move away from it.
      const hiddenStep = node.closest('.ppsgen-step-hidden[data-step-slug]');
      if (hiddenStep && key !== this.stepFollowedKey) {
        this.step = hiddenStep.getAttribute('data-step-slug');
        this.applyStep(frame);
      }
      this.stepFollowedKey = key;
      node.setAttribute('data-ppsgen-selected', '1');
      if (scrollIntoView) {
        const rect = node.getBoundingClientRect();
        if (rect.top < 0 || rect.bottom > doc.defaultView.innerHeight) {
          node.scrollIntoView({ block: 'center' });
        }
      }
      this.placeTools(doc, node, key);
    }

    // ↑ ↓ next to the selected element: moves it among its neighbours in the same row / column,
    // so each editor's nesting rules always hold
    placeTools(doc, node, key) {
      const $col = this.builderField(key);
      if (!$col.length || !doc.body) {
        return;
      }
      let tools = doc.getElementById('ppsgen-live-tools');
      if (!tools) {
        tools = doc.createElement('div');
        tools.id = 'ppsgen-live-tools';
        tools.innerHTML = '<button type="button" data-move="up"></button><button type="button" data-move="down"></button>';
        doc.body.appendChild(tools);
      }
      const [up, down] = tools.querySelectorAll('button');
      up.textContent = '↑';
      down.textContent = '↓';
      up.title = text('up', 'Move up');
      down.title = text('down', 'Move down');
      up.setAttribute('aria-label', up.title);
      down.setAttribute('aria-label', down.title);
      up.disabled = !$col.prevAll('[data-field-key]').length;
      down.disabled = !$col.nextAll('[data-field-key]').length;
      if (up.disabled && down.disabled) {
        tools.style.display = 'none';
        return;
      }
      const rect = node.getBoundingClientRect();
      if (!rect.width && !rect.height) {
        tools.style.display = 'none';
        return;
      }
      const win = doc.defaultView;
      tools.style.fontSize = `${Math.round(16 / (this.scale || 1))}px`;
      tools.style.display = 'flex';
      const width = tools.offsetWidth;
      const height = tools.offsetHeight;
      const top = rect.top - height - 4 >= 0 ? rect.top - height - 4 : rect.bottom + 4;
      tools.style.left = `${Math.max(0, rect.right - width) + win.scrollX}px`;
      tools.style.top = `${top + win.scrollY}px`;
    }

    moveSelected(direction) {
      const key = this.editor && this.editor.currentFieldKey ? String(this.editor.currentFieldKey) : '';
      const $col = this.builderField(key);
      if (!$col.length) {
        return;
      }
      if (direction < 0) {
        const $prev = $col.prevAll('[data-field-key]').first();
        if (!$prev.length) {
          return;
        }
        $col.insertBefore($prev);
      } else {
        const $next = $col.nextAll('[data-field-key]').first();
        if (!$next.length) {
          return;
        }
        $col.insertAfter($next);
      }
      if (typeof this.editor.calculateFieldOrder === 'function') {
        this.editor.calculateFieldOrder();
      }
      const $row = $col.children('.ppsgenOutlineRow').first();
      if ($row.length && $row[0].scrollIntoView) {
        $row[0].scrollIntoView({ block: 'nearest' });
      }
      this.tick(false);
    }

    // Draws into the hidden frame and swaps when it has loaded, so the preview never flashes white
    show(html) {
      this.shown = true;
      this.lastHtml = html;
      const current = this.frames[this.visibleFrame];
      const nextIndex = this.visibleFrame === 0 ? 1 : 0;
      const next = this.frames[nextIndex];
      let scrollY = 0;
      try {
        scrollY = current.contentWindow.scrollY || 0;
      } catch (e) {}
      const token = ++this.swapToken;
      const swap = () => {
        if (token !== this.swapToken) {
          return;
        }
        this.swapToken++;
        // Keep the step being edited on screen across redraws
        this.applyStep(next);
        try {
          next.contentWindow.scrollTo(0, scrollY);
        } catch (e) {}
        $(next).addClass('ppsgenLivePreviewFrameOn').removeAttr('aria-hidden');
        $(current).removeClass('ppsgenLivePreviewFrameOn').attr('aria-hidden', 'true');
        this.visibleFrame = nextIndex;
        this.status('ok');
        this.markSelected(false);
      };
      $(next).one('load', () => setTimeout(swap, 60));
      setTimeout(swap, 900);
      this.write(next, html);
    }

    // ---- Steps. A popup with PRO "Go to step" buttons shows one step at a time; the preview shows the
    // default one. A bar above the builder's screen-size buttons flips through the steps. It appears only
    // when the popup has steps and at least one button that opens a step, so it shows up as soon as both
    // are added and goes away when they are removed.

    // Step slugs in page order
    stepsIn(doc) {
      const slugs = [];
      doc.querySelectorAll('[data-step-slug]').forEach((el) => {
        const slug = el.getAttribute('data-step-slug');
        if (slug && !slugs.includes(slug)) {
          slugs.push(slug);
        }
      });
      return slugs;
    }

    // The step on screen: the one picked in the bar, else the one the popup shows first
    currentStep(doc, slugs) {
      if (this.step && slugs.includes(this.step)) {
        return this.step;
      }
      const shown = [...doc.querySelectorAll('[data-step-slug]')].find((el) => !el.classList.contains('ppsgen-step-hidden'));
      return shown ? shown.getAttribute('data-step-slug') : slugs[0];
    }

    applyStep(frame) {
      const doc = frame && frame.contentDocument;
      if (!doc || !doc.body) {
        return;
      }
      const slugs = this.stepsIn(doc);
      if (this.step && !slugs.includes(this.step)) {
        this.step = null;
      }
      if (this.step) {
        doc.querySelectorAll('[data-step-slug]').forEach((el) => el.classList.toggle('ppsgen-step-hidden', el.getAttribute('data-step-slug') !== this.step));
      }
      this.renderStepBar(doc, slugs);
      this.renderResetBar();
    }

    // ---- Start over. Game templates (spin, scratch, pick a card, quizzes, age gate…) remember in the browser what
    // the visitor did, under keys starting with "ces" — so the admin who played once in the preview would only
    // see the result from then on. A bar under the "Choose element" list forgets it and draws the preview again.
    // It appears only when the template's script keeps something in localStorage / sessionStorage.

    // Key prefixes the template's script remembers things under (the popup id is already in them)
    memoryKeys(html) {
      if (!html || !/\b(localStorage|sessionStorage)\b/.test(html)) {
        return [];
      }
      const keys = [];
      const re = /['"](ces[A-Z][A-Za-z0-9_]*)/g;
      let m;
      while ((m = re.exec(html))) {
        if (!keys.includes(m[1])) {
          keys.push(m[1]);
        }
      }
      return keys;
    }

    renderResetBar() {
      const $anchor = this.cfg ? $(this.cfg.left).find('.ppsgenCfbAddOtherFieldList').first().closest('.ppsgenCfbAddFieldWrapper') : $();
      if (!$anchor.length) {
        return;
      }
      let $bar = $anchor.next('.ppsgenPreviewReset');
      this.resetKeys = this.memoryKeys(this.lastHtml);
      if (!this.resetKeys.length) {
        $bar.remove();
        return;
      }
      if (!$bar.length) {
        $bar = $(`
          <div class="ppsgenPreviewReset">
            <span class="ppsgenPreviewResetText">${text('resetText', 'The preview remembers what you picked or won')}</span>
            <button type="button" class="ppsgenPreviewResetBtn" title="${text('resetHint', 'Forget it and play again as a new visitor')}"><span class="dashicons dashicons-image-rotate"></span>${text('reset', 'Start over')}</button>
          </div>`);
        $bar.on('click', '.ppsgenPreviewResetBtn', () => this.resetMemory());
        $anchor.after($bar);
      }
    }

    // Forgets what the template remembered (in this admin page and in the preview frames) and draws it again
    resetMemory() {
      const prefixes = this.resetKeys || [];
      if (!prefixes.length) {
        return;
      }
      const stores = [];
      [window].concat(this.frames.map((frame) => frame.contentWindow)).forEach((win) => {
        try {
          stores.push(win.localStorage, win.sessionStorage);
        } catch (e) {}
      });
      stores.filter((store, i) => store && stores.indexOf(store) === i).forEach((store) => {
        try {
          for (let i = store.length - 1; i >= 0; i--) {
            const key = store.key(i);
            if (key && prefixes.some((prefix) => key.indexOf(prefix) === 0)) {
              store.removeItem(key);
            }
          }
        } catch (e) {}
      });
      if (this.lastHtml) {
        this.show(this.lastHtml);
      }
    }

    renderStepBar(doc, slugs) {
      const $anchor = this.cfg ? $(this.cfg.left).find('.ppsgenCfbFieldsWrapperSizeChanger').first() : $();
      if (!$anchor.length) {
        return;
      }
      let $bar = $anchor.prev('.ppsgenStepSwitcher');
      if (!slugs.length || !doc.querySelector('[data-type="to_step"]')) {
        $bar.remove();
        return;
      }
      if (!$bar.length) {
        $bar = $(`
          <div class="ppsgenStepSwitcher" role="group" aria-label="${text('steps', 'Steps')}">
            <button type="button" class="ppsgenStepSwitcherPrev" data-dir="-1" title="${text('stepPrev', 'Previous step')}" aria-label="${text('stepPrev', 'Previous step')}"><span class="dashicons dashicons-arrow-left-alt2"></span></button>
            <span class="ppsgenStepSwitcherLabel"></span>
            <button type="button" class="ppsgenStepSwitcherNext" data-dir="1" title="${text('stepNext', 'Next step')}" aria-label="${text('stepNext', 'Next step')}"><span class="dashicons dashicons-arrow-right-alt2"></span></button>
          </div>`);
        $bar.on('click', 'button', (e) => this.moveStep(parseInt($(e.currentTarget).attr('data-dir'), 10)));
        $anchor.before($bar);
      }
      const current = this.currentStep(doc, slugs);
      const index = Math.max(0, slugs.indexOf(current));
      const $label = $bar.find('.ppsgenStepSwitcherLabel').empty();
      $label.append(document.createTextNode(`${text('step', 'Step')} ${index + 1} / ${slugs.length} · `), $('<b>').text(current));
      $bar.find('.ppsgenStepSwitcherPrev').prop('disabled', index <= 0);
      $bar.find('.ppsgenStepSwitcherNext').prop('disabled', index >= slugs.length - 1);
    }

    moveStep(dir) {
      const frame = this.frames[this.visibleFrame];
      const doc = frame && frame.contentDocument;
      if (!doc) {
        return;
      }
      const slugs = this.stepsIn(doc);
      const index = slugs.indexOf(this.currentStep(doc, slugs));
      const target = slugs[Math.min(slugs.length - 1, Math.max(0, index + dir))];
      if (!target) {
        return;
      }
      this.step = target;
      this.applyStep(frame);
      this.markSelected(false);
    }
  }

  /**
   * Shows the element builder as an outline: one line per element, children indented under their row / column
   * (a "ladder"). The builder's own markup and drag-and-drop stay as they are, so ordering, saving and each
   * editor's nesting rules (Row → Column → content in emails) keep working unchanged.
   */
  const OUTLINE_ICONS = {
    row: 'align-wide',
    column: 'columns',
    title: 'heading',
    textarea: 'editor-paragraph',
    description: 'editor-paragraph',
    desc: 'editor-paragraph',
    image: 'format-image',
    svg_icon: 'star-filled',
    icon: 'star-filled',
    button: 'button',
    submit: 'button',
    form: 'feedback',
    socials: 'share',
    social: 'share',
    divider: 'minus',
    iframe: 'video-alt3',
    video: 'video-alt3',
    email_content: 'email-alt',
    unsubscribe: 'dismiss',
    captcha: 'shield',
    pro: 'admin-post',
    empty: 'editor-insertmore',
    email: 'email',
    phone: 'phone',
    select: 'menu-alt',
    checkbox: 'yes-alt',
    radio: 'marker',
    date: 'calendar-alt',
    range: 'leftright',
    rating: 'star-half',
  };
  const OUTLINE_SUMMARY_KEYS = ['title_text', 'button_text', 'submit_text', 'label', 'icon_title', 'image_title', 'text', 'textarea', 'html_textarea_content', 'content', 'description', 'placeholder', 'unsubscribe_text', 'iframe_url', 'image_url', 'image_src', 'field_slug'];

  class PpsgenBuilderOutline {
    constructor() {
      this.editor = null;
      this.observer = null;
      this.collapsed = new Set();
      this.busy = false;
    }

    attach(editor) {
      const $wrapper = editor.fieldsContainer && editor.fieldsContainer.length ? editor.fieldsContainer.first() : $();
      if (!$wrapper.length) {
        return;
      }
      this.editor = editor;
      this.$wrapper = $wrapper.addClass('ppsgenOutline');
      // The add buttons show only "+", so their text ("Add to Form"…) becomes the tooltip and the accessible name
      $('.ppsgenCfbAddFieldBtn').each((_, btn) => {
        if (!btn.getAttribute('aria-label')) {
          const label = btn.textContent.trim();
          btn.setAttribute('title', label);
          btn.setAttribute('aria-label', label);
          btn.setAttribute('role', 'button');
          btn.setAttribute('tabindex', '0');
        }
      });
      if (this.observer) {
        this.observer.disconnect();
      }
      if (window.MutationObserver) {
        this.observer = new MutationObserver(() => {
          if (!this.busy) {
            clearTimeout(this.timer);
            this.timer = setTimeout(() => this.refresh(), 30);
          }
        });
        this.observer.observe($wrapper[0], { childList: true, subtree: true });
      }
      if (!this.bound) {
        this.bound = true;
        $(document).on('keydown.ppsgenOutline', '.ppsgenCfbAddFieldBtn', (e) => {
          if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            $(e.currentTarget).trigger('click');
          }
        });
        // Typing in the element options updates that element's line
        $(document).on('input.ppsgenOutline change.ppsgenOutline', () => {
          clearTimeout(this.timer);
          this.timer = setTimeout(() => this.refresh(), 300);
        });
        $(document).on('click.ppsgenOutline', '.ppsgenOutlineCaret', (e) => {
          e.preventDefault();
          e.stopPropagation();
          const $col = $(e.currentTarget).closest('[data-field-key]');
          const key = $col.attr('data-field-key');
          const collapsed = !$col.hasClass('ppsgenOutlineCollapsed');
          $col.toggleClass('ppsgenOutlineCollapsed', collapsed);
          $(e.currentTarget).attr('aria-expanded', collapsed ? 'false' : 'true');
          if (collapsed) {
            this.collapsed.add(key);
          } else {
            this.collapsed.delete(key);
          }
        });
      }
      this.refresh();
    }

    typeLabel(type) {
      const names = l10n.types || {};
      if (names[type]) {
        return names[type];
      }
      const clean = String(type || '').replace(/[_-]+/g, ' ').trim();
      return clean ? clean.charAt(0).toUpperCase() + clean.slice(1) : '—';
    }

    summary(options, type) {
      if (!options || typeof options !== 'object') {
        return '';
      }
      const pick = (value) => {
        if (typeof value !== 'string') {
          return '';
        }
        const div = document.createElement('div');
        div.innerHTML = value;
        return (div.textContent || '').replace(/\s+/g, ' ').trim();
      };
      if (/icon|social/.test(type) && typeof options.icon_class === 'string' && options.icon_class) {
        const names = options.icon_class.match(/fa-([a-z0-9-]+)/g);
        if (names) {
          return names[names.length - 1].replace(/^fa-/, '');
        }
      }
      for (const key of OUTLINE_SUMMARY_KEYS) {
        const value = pick(options[key]);
        if (value && value.toLowerCase() !== String(type).toLowerCase()) {
          return value.length > 48 ? `${value.slice(0, 47)}…` : value;
        }
      }
      return '';
    }

    refresh() {
      if (!this.editor || !this.$wrapper || !document.body.contains(this.$wrapper[0])) {
        return;
      }
      const map = this.editor.fieldOptionsMap || {};
      this.busy = true;
      this.$wrapper.find('[data-field-key]').each((_, el) => {
        const $col = $(el);
        const key = $col.attr('data-field-key');
        const type = decodeURIComponent($col.attr('data-field-type') || '');
        const $inner = $col.children().filter((__, child) => /-col-inner/.test(child.className || ''));
        const isContainer = $inner.length > 0;
        let options = map[key] || map[decodeURIComponent(key)] || null;
        if (key === this.editor.currentFieldKey && typeof this.editor.getOptionsFromForm === 'function') {
          try {
            options = Object.assign({}, options || {}, this.editor.getOptionsFromForm());
          } catch (e) {}
        }
        const summary = this.summary(options, type);
        const signature = `${type}|${summary}|${isContainer ? $inner.children('[data-field-key]').length : '-'}`;
        let $row = $col.children('.ppsgenOutlineRow');
        if ($row.length && $row.attr('data-sig') === signature) {
          return;
        }
        if (!$row.length) {
          $row = $('<div class="ppsgenOutlineRow"></div>').prependTo($col);
        }
        const icon = OUTLINE_ICONS[type] || (type.indexOf('woo') === 0 ? 'cart' : 'marker');
        const count = isContainer ? $inner.children('[data-field-key]').length : 0;
        $row
          .attr('data-sig', signature)
          .empty()
          .append(
            isContainer
              ? $('<button type="button" class="ppsgenOutlineCaret"></button>')
                  .attr({ 'aria-expanded': this.collapsed.has(key) ? 'false' : 'true', 'aria-label': text('toggle', 'Show or hide what is inside') })
                  .append('<span class="dashicons dashicons-arrow-down-alt2"></span>')
              : $('<span class="ppsgenOutlineCaretSpace"></span>'),
            $(`<span class="ppsgenOutlineIcon dashicons dashicons-${icon}"></span>`),
            $('<span class="ppsgenOutlineType"></span>').text(this.typeLabel(type)),
            $('<span class="ppsgenOutlineSummary"></span>').text(summary),
            isContainer && count === 0 ? $('<span class="ppsgenOutlineEmpty"></span>').text(text('empty', 'empty')) : $()
          );
        $col.toggleClass('ppsgenOutlineContainer', isContainer).toggleClass('ppsgenOutlineCollapsed', isContainer && this.collapsed.has(key));
      });
      if (this.observer) {
        this.observer.takeRecords();
      }
      this.busy = false;
      if (window.ppsgenUnsavedGuard) {
        window.ppsgenUnsavedGuard.checkStructure(this.editor, this.$wrapper);
      }
    }
  }

  /**
   * Starting content and look for newly added elements in every builder (popup, form, email template,
   * automation and newsletter emails), so a new button looks like a button, an icon shows an icon and a
   * timer counts down straight away. Only called for an element that was just added, so it may set values.
   * Popups and emails name their options differently: popup elements carry the col_* screen options.
   */
  window.ppsgenApplyDefaultText = function (options, type) {
    if (!options || typeof options !== 'object') {
      return options;
    }
    const isPopup = Object.prototype.hasOwnProperty.call(options, 'col_mob');
    const accent = '#2563eb';
    const accentHover = '#1d4ed8';
    const buttonText = text('defaultButton', 'Get started');
    const presets = {
      title: { title_text: text('defaultTitle', 'Title') },
      textarea: { html_textarea_content: `<p>${text('defaultText', 'Text')}</p>` },
      description: { html_description_text: `<p>${text('defaultText', 'Text')}</p>` },
      button: isPopup
        ? {
            button_text: buttonText,
            button_url: '#',
            background: accent,
            background_hover: accentHover,
            font_color: '#ffffff',
            font_size: '16',
            font_size_unit: 'px',
            font_weight: '600',
            border: '0',
            border_radius: '8',
            border_radius_unit: 'px',
            padding_top: '12',
            padding_right: '28',
            padding_bottom: '12',
            padding_left: '28',
            margin_top: '8',
            margin_bottom: '8',
            title_text_align: 'center',
          }
        : {
            button_text: buttonText,
            button_url: '#',
            display_type: 'inline-block',
            width_select: 'auto',
            text_align: 'center',
            background: accent,
            font_color: '#ffffff',
            font_size: '16',
            font_size_unit: 'px',
            font_weight: 'bold',
            link_text_decoration: 'none',
            border: '0',
            border_radius: '6',
            border_radius_unit: 'px',
            padding_top: '12',
            padding_right: '28',
            padding_bottom: '12',
            padding_left: '28',
            margin_top: '8',
            margin_bottom: '8',
          },
      icon: { icon_class: 'ppsgen-fa-solid fa-gift', icon_title: text('defaultIcon', 'Gift'), font_size: '36', font_size_unit: 'px', font_color: accent, link_color: accent, link_hover_color: accentHover },
      svg_icon: { svg_icon_color: accent },
      // Countdown: 15 minutes from the moment the popup opens, minutes and seconds with labels under them
      timer: {
        timer_type: 'session',
        timer_duration: '15',
        timer_on_expire: 'none',
        timer_show_year: '0',
        timer_show_month: '0',
        timer_show_days: '0',
        timer_show_hours: '0',
        timer_show_minutes: '1',
        timer_show_seconds: '1',
        timer_show_label: 'under',
        timer_size: 'm',
        timer_style: 'black',
        timer_shape: 'rounded',
        timer_gap: '8',
      },
    };
    Object.assign(options, presets[type] || {});
    return options;
  };

  /**
   * A simple website behind the popup preview (header, hero, cards, footer), so people see how the popup
   * sits on top of a real page. Muted on purpose, responsive with the preview device, and drawn below
   * the page content (z-index -1), so inline / embedded popups still show above it.
   */
  window.ppsgenPreviewSiteMock = function () {
    const esc = (value) => $('<div>').text(value).html();
    const nav = [text('mockNavHome', 'Home'), text('mockNavShop', 'Shop'), text('mockNavAbout', 'About'), text('mockNavContact', 'Contact')]
      .map((item) => `<span>${esc(item)}</span>`)
      .join('');
    const card = (n) =>
      `<div class="ppsgenMockCard"><div class="ppsgenMockImg ppsgenMockImg${n}"></div><div class="ppsgenMockLine ppsgenMockLineStrong" style="width:70%"></div><div class="ppsgenMockLine"></div><div class="ppsgenMockLine" style="width:55%"></div></div>`;
    return `<style id="ppsgen-preview-site-mock">
      html{background:#eef0f4;}
      #ppsgenMockSite{position:fixed;inset:0;z-index:-1;overflow:hidden;pointer-events:none;background:#eef0f4;color:#8a93a3;font:15px/1.5 -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;}
      #ppsgenMockSite *{box-sizing:border-box;}
      .ppsgenMockHeader{display:flex;align-items:center;gap:28px;height:64px;padding:0 6%;background:#fff;border-bottom:1px solid #e3e6ec;}
      .ppsgenMockLogo{display:flex;align-items:center;gap:10px;font-weight:700;color:#6b7383;white-space:nowrap;}
      .ppsgenMockLogo i{width:28px;height:28px;border-radius:8px;background:#c9d2e3;}
      .ppsgenMockNav{display:flex;gap:22px;margin-left:auto;font-size:14px;}
      .ppsgenMockBurger{display:none;margin-left:auto;width:24px;height:16px;border-top:3px solid #c3c9d4;border-bottom:3px solid #c3c9d4;position:relative;}
      .ppsgenMockBurger::after{content:"";position:absolute;left:0;right:0;top:4px;border-top:3px solid #c3c9d4;}
      .ppsgenMockBtn{display:inline-block;padding:9px 18px;border-radius:6px;background:#c9d2e3;color:#fff;font-size:14px;font-weight:600;}
      .ppsgenMockHero{display:grid;grid-template-columns:1.1fr 1fr;gap:6%;align-items:center;padding:56px 6% 48px;}
      .ppsgenMockHero h1{margin:0 0 14px;color:#6b7383;font-size:40px;line-height:1.15;font-weight:800;}
      .ppsgenMockHero p{margin:0 0 22px;max-width:460px;}
      .ppsgenMockHeroImg{height:280px;border-radius:14px;background:linear-gradient(135deg,#d6dcea,#c4cde0);}
      .ppsgenMockCards{display:grid;grid-template-columns:repeat(3,1fr);gap:24px;padding:0 6% 48px;}
      .ppsgenMockCard{padding:14px;border-radius:12px;background:#fff;}
      .ppsgenMockImg{height:120px;margin-bottom:14px;border-radius:8px;background:#dde2ec;}
      .ppsgenMockImg2{background:#d9e0ea;}.ppsgenMockImg3{background:#e1e4ec;}
      .ppsgenMockLine{height:10px;margin:8px 0;border-radius:5px;background:#e3e6ec;}
      .ppsgenMockLineStrong{height:13px;background:#d3d8e2;}
      .ppsgenMockFooter{position:absolute;left:0;right:0;bottom:0;height:56px;background:#e3e6ec;}
      @media (max-width:900px){.ppsgenMockHero{grid-template-columns:1fr;padding-top:36px}.ppsgenMockHeroImg{height:200px}.ppsgenMockCards{grid-template-columns:repeat(2,1fr)}.ppsgenMockCard:nth-child(3){display:none}}
      @media (max-width:600px){.ppsgenMockNav,.ppsgenMockHeader .ppsgenMockBtn{display:none}.ppsgenMockBurger{display:block}.ppsgenMockHero h1{font-size:28px}.ppsgenMockCards{grid-template-columns:1fr}.ppsgenMockCard:nth-child(2){display:none}}
    </style>
    <div id="ppsgenMockSite" aria-hidden="true">
      <div class="ppsgenMockHeader"><div class="ppsgenMockLogo"><i></i>${esc(text('mockSite', 'Your website'))}</div><nav class="ppsgenMockNav">${nav}</nav><span class="ppsgenMockBtn">${esc(text('mockButton', 'Get started'))}</span><span class="ppsgenMockBurger"></span></div>
      <div class="ppsgenMockHero"><div><h1>${esc(text('mockTitle', 'Your page content'))}</h1><p>${esc(text('mockText', 'Visitors read your page here. The popup appears on top of it, just like in this preview.'))}</p><span class="ppsgenMockBtn">${esc(text('mockMore', 'Learn more'))}</span></div><div class="ppsgenMockHeroImg"></div></div>
      <div class="ppsgenMockCards">${card(1)}${card(2)}${card(3)}</div>
      <div class="ppsgenMockFooter"></div>
    </div>`;
  };

  /**
   * Unsaved changes: only what a person does counts (typing, picking, dragging, adding, deleting, colours,
   * typing in the preview), never values scripts put into the fields when an element is selected.
   * Marks the Save button and asks before leaving the page. Automation steps are tracked separately.
   */
  const SAVE_ACTIONS = { editPopup: 'main', editForm: 'main', editTemplate: 'main', saveChainOptions: 'main', saveChainStepFields: 'step', saveChainStepOptions: 'step' };
  const EDITOR_FORMS = '#ppsgenEditObjFormPopup, #ppsgenEditObjForm, #ppsgenEditObjFormTemplate, #ppsgenEditObjFormChain';
  const NOT_EDITS = '.ppsgenCfbAddFieldList, [data-ppsgen-no-dirty], .ppsgenChainStepStatsDynamic *, [data-tab="stats"] *, [data-tab="stepStats"] *, [data-tab="submits"] *, [data-tab="contacts"] *';

  class PpsgenUnsavedGuard {
    constructor() {
      this.dirty = { main: false, step: false };
      this.structure = null;
      this.bound = false;
    }

    scopeOf(el) {
      return el && $(el).closest('.ppsgenChainStepWrapper').length ? 'step' : 'main';
    }

    attach(editor) {
      if (!$(EDITOR_FORMS).length) {
        return;
      }
      // A newly opened automation step starts clean; the page itself starts clean once
      const scope = editor && editor.fieldsContainer ? this.scopeOf(editor.fieldsContainer[0]) : 'main';
      this.dirty[scope] = false;
      this.structure = null;
      this.paint();
      this.bind();
    }

    mark(el) {
      const scope = this.scopeOf(el);
      if (!this.dirty[scope]) {
        this.dirty[scope] = true;
        this.paint();
      }
    }

    any() {
      return this.dirty.main || this.dirty.step;
    }

    // Adding, deleting, dragging and ↑ ↓ all change the builder's structure
    checkStructure(editor, $wrapper) {
      if (!$wrapper || !$wrapper.length) {
        return;
      }
      const signature = $wrapper
        .find('[data-field-key]')
        .map((_, el) => `${el.getAttribute('data-field-key')}<${$(el).parent().closest('[data-field-key]').attr('data-field-key') || ''}`)
        .get()
        .join('|');
      if (this.structure !== null && signature !== this.structure) {
        this.mark($wrapper[0]);
      }
      this.structure = signature;
    }

    paint() {
      const title = text('unsaved', 'Unsaved changes');
      $('.ppsgenObjEditSaveBtn, .ppsgenChainOptionsBtnSave').toggleClass('ppsgenHasUnsaved', this.dirty.main).attr('data-unsaved', this.dirty.main ? title : null);
      $('.ppsgenChainStepBtnSave').toggleClass('ppsgenHasUnsaved', this.dirty.step).attr('data-unsaved', this.dirty.step ? title : null);
    }

    bind() {
      if (this.bound) {
        return;
      }
      this.bound = true;
      const trusted = (e) => !!(e.originalEvent && e.originalEvent.isTrusted);
      $(document).on('input.ppsgenDirty change.ppsgenDirty', `${EDITOR_FORMS.split(', ').map((f) => `${f} :input`).join(', ')}`, (e) => {
        if (trusted(e) && !$(e.target).is(NOT_EDITS)) {
          this.mark(e.target);
        }
      });
      // Colour pickers set their fields by script; the person's drag or click is what counts
      $(document).on('mouseup.ppsgenDirty', '.iris-picker, .iris-palette', (e) => this.mark(e.target));
      $(document).on('click.ppsgenDirty', '.wp-picker-clear, .ppsgenCffDuplicateBtn, .ppsgenCffDeleteBtn', (e) => {
        if (trusted(e)) {
          this.mark(e.target);
        }
      });
      const watchEditor = (ed) => {
        if (ed && !ed.ppsgenDirtyBound) {
          ed.ppsgenDirtyBound = true;
          ed.on('input ExecCommand', () => this.mark(ed.getElement ? ed.getElement() : null));
        }
      };
      if (window.tinymce) {
        (window.tinymce.editors || []).forEach(watchEditor);
        window.tinymce.on('AddEditor', (e) => watchEditor(e.editor));
      }
      $(document).ajaxSuccess((event, xhr, settings, data) => {
        let action = '';
        try {
          action = typeof settings.data === 'string' ? new URLSearchParams(settings.data).get('action') || '' : (settings.data && settings.data.action) || '';
        } catch (e) {}
        const scope = SAVE_ACTIONS[action];
        const json = data && typeof data === 'object' ? data : xhr.responseJSON;
        if (scope && json && json.success) {
          this.dirty[scope] = false;
          this.paint();
        }
      });
      window.addEventListener('beforeunload', (e) => {
        if (this.any()) {
          e.preventDefault();
          e.returnValue = '';
        }
      });
      // Switching automation steps only needs the "unsaved data" question when the open step has changes
      document.addEventListener(
        'click',
        (e) => {
          if (!this.dirty.step && e.target && e.target.closest && e.target.closest('.ppsgenChainStep')) {
            const original = window.confirm;
            window.confirm = () => true;
            setTimeout(() => {
              window.confirm = original;
            }, 0);
          }
        },
        true
      );
    }
  }

  window.ppsgenUnsavedGuard = new PpsgenUnsavedGuard();

  /**
   * A simple page around the form preview: forms sit inside page content, so the preview shows one
   * in a content column between a heading, some text lines, a sidebar and a footer.
   */
  window.ppsgenPreviewPageMock = function (formHtml) {
    const esc = (value) => $('<div>').text(value).html();
    const nav = [text('mockNavHome', 'Home'), text('mockNavShop', 'Shop'), text('mockNavAbout', 'About'), text('mockNavContact', 'Contact')]
      .map((item) => `<span>${esc(item)}</span>`)
      .join('');
    const lines = (widths) => widths.map((w) => `<div class="ppsgenPmLine" style="width:${w}%"></div>`).join('');
    return `<style id="ppsgen-preview-page-mock">
      html,body{margin:0;background:#eef0f4;}
      .ppsgenPm{min-height:100vh;color:#8a93a3;font:15px/1.5 -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;}
      .ppsgenPm *{box-sizing:border-box;}
      .ppsgenPmHeader{display:flex;align-items:center;gap:28px;height:64px;padding:0 6%;background:#fff;border-bottom:1px solid #e3e6ec;}
      .ppsgenPmLogo{display:flex;align-items:center;gap:10px;font-weight:700;color:#6b7383;white-space:nowrap;}
      .ppsgenPmLogo i{width:28px;height:28px;border-radius:8px;background:#c9d2e3;}
      .ppsgenPmNav{display:flex;gap:22px;margin-left:auto;font-size:14px;}
      .ppsgenPmBurger{display:none;margin-left:auto;width:24px;height:16px;border-top:3px solid #c3c9d4;border-bottom:3px solid #c3c9d4;position:relative;}
      .ppsgenPmBurger::after{content:"";position:absolute;left:0;right:0;top:4px;border-top:3px solid #c3c9d4;}
      .ppsgenPmBody{display:grid;grid-template-columns:minmax(0,1fr) 260px;gap:32px;max-width:1100px;margin:0 auto;padding:40px 6% 56px;}
      .ppsgenPmMain{padding:32px;border-radius:12px;background:#fff;}
      .ppsgenPmMain h1{margin:0 0 12px;color:#6b7383;font-size:30px;line-height:1.2;font-weight:800;}
      .ppsgenPmLine{height:10px;margin:9px 0;border-radius:5px;background:#e3e6ec;}
      .ppsgenPmForm{margin:26px 0;}
      .ppsgenPmSide{display:flex;flex-direction:column;gap:16px;}
      .ppsgenPmCard{padding:16px;border-radius:12px;background:#fff;}
      .ppsgenPmImg{height:110px;margin-bottom:12px;border-radius:8px;background:#dde2ec;}
      .ppsgenPmFooter{height:56px;background:#e3e6ec;}
      @media (max-width:900px){.ppsgenPmBody{grid-template-columns:1fr}.ppsgenPmSide{display:none}}
      @media (max-width:600px){.ppsgenPmNav{display:none}.ppsgenPmBurger{display:block}.ppsgenPmBody{padding:20px 4% 36px}.ppsgenPmMain{padding:20px}.ppsgenPmMain h1{font-size:24px}}
    </style>
    <div class="ppsgenPm">
      <div class="ppsgenPmHeader" aria-hidden="true"><div class="ppsgenPmLogo"><i></i>${esc(text('mockSite', 'Your website'))}</div><nav class="ppsgenPmNav">${nav}</nav><span class="ppsgenPmBurger"></span></div>
      <div class="ppsgenPmBody">
        <main class="ppsgenPmMain">
          <h1 aria-hidden="true">${esc(text('mockFormTitle', 'Your page content'))}</h1>
          <div aria-hidden="true">${lines([92, 86, 64])}</div>
          <div class="ppsgenPmForm">${formHtml}</div>
          <div aria-hidden="true">${lines([88, 72])}</div>
        </main>
        <aside class="ppsgenPmSide" aria-hidden="true"><div class="ppsgenPmCard"><div class="ppsgenPmImg"></div>${lines([80, 60])}</div><div class="ppsgenPmCard">${lines([90, 70, 50])}</div></aside>
      </div>
      <div class="ppsgenPmFooter" aria-hidden="true"></div>
    </div>`;
  };

  window.ppsgenBuilderOutline = new PpsgenBuilderOutline();
  window.ppsgenLivePreview = new PpsgenLivePreview();

  /**
   * Shared by the editors' preview handlers: live renders go to the docked preview,
   * the "Full preview" button keeps opening the large window.
   */
  window.ppsgenShowPreview = function (html, live) {
    if (live) {
      if (window.ppsgenLivePreview.isOn()) {
        window.ppsgenLivePreview.show(html);
      }
      return;
    }
    const iframe = document.getElementById('ppsgenPreviewFrame');
    if (!iframe) {
      return;
    }
    $('#ppsgenPreviewPopup').fadeIn(150);
    window.ppsgenFrameWrite(iframe, html, (doc) => {
      // Full preview opens on the step picked in the live preview's step bar
      const step = window.ppsgenLivePreview && window.ppsgenLivePreview.step;
      if (step) {
        const showStep = () => doc.querySelectorAll('[data-step-slug]').forEach((el) => el.classList.toggle('ppsgen-step-hidden', el.getAttribute('data-step-slug') !== step));
        showStep();
        setTimeout(showStep, 400);
      }
    });
  };
})(jQuery);
