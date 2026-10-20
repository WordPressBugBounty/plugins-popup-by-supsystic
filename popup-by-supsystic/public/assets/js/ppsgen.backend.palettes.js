/**
 * Colour styles for popups, forms and email templates: a grid of palettes at the top of the Design tab.
 * Picking one restyles everything at once (the Design tab colours and the colours of the elements),
 * so titles, text, buttons, fields and backgrounds always fit together. Nothing is saved until Save,
 * and the last change can be undone. 8 palettes in Free, 16 more with PRO.
 */
(function ($) {
  'use strict';

  const l10n = window.ppsgenPalettesL10n || {};
  const t = (key, fallback) => l10n[key] || fallback;

  // Roles: bg (page / outer), surface (card), heading, text, muted, border, accent (buttons, links),
  // accentHover, accentText (text on buttons), input (field background), error, overlay, shadow
  const PALETTES = [
    { id: 'white', name: 'White', bg: '#f4f5f7', surface: '#ffffff', heading: '#111827', text: '#374151', muted: '#9ca3af', border: '#e5e7eb', accent: '#111827', accentHover: '#374151', accentText: '#ffffff', input: '#ffffff', error: '#dc2626', overlay: 'rgba(17,24,39,0.55)', shadow: 'rgba(17,24,39,0.12)' },
    { id: 'gray', name: 'Gray', bg: '#e5e7eb', surface: '#f3f4f6', heading: '#1f2937', text: '#4b5563', muted: '#9ca3af', border: '#d1d5db', accent: '#4b5563', accentHover: '#374151', accentText: '#ffffff', input: '#ffffff', error: '#dc2626', overlay: 'rgba(31,41,55,0.6)', shadow: 'rgba(31,41,55,0.14)' },
    { id: 'black', name: 'Black', bg: '#030712', surface: '#111827', heading: '#f9fafb', text: '#d1d5db', muted: '#6b7280', border: '#374151', accent: '#f9fafb', accentHover: '#e5e7eb', accentText: '#111827', input: '#1f2937', error: '#f87171', overlay: 'rgba(0,0,0,0.72)', shadow: 'rgba(0,0,0,0.45)' },
    { id: 'blue', name: 'Blue', bg: '#eff6ff', surface: '#ffffff', heading: '#1e3a8a', text: '#334155', muted: '#94a3b8', border: '#bfdbfe', accent: '#2563eb', accentHover: '#1d4ed8', accentText: '#ffffff', input: '#ffffff', error: '#dc2626', overlay: 'rgba(30,58,138,0.45)', shadow: 'rgba(37,99,235,0.15)' },
    { id: 'green', name: 'Green', bg: '#ecfdf5', surface: '#ffffff', heading: '#064e3b', text: '#374151', muted: '#9ca3af', border: '#a7f3d0', accent: '#059669', accentHover: '#047857', accentText: '#ffffff', input: '#ffffff', error: '#dc2626', overlay: 'rgba(6,78,59,0.45)', shadow: 'rgba(5,150,105,0.15)' },
    { id: 'red', name: 'Red', bg: '#fef2f2', surface: '#ffffff', heading: '#7f1d1d', text: '#374151', muted: '#9ca3af', border: '#fecaca', accent: '#dc2626', accentHover: '#b91c1c', accentText: '#ffffff', input: '#ffffff', error: '#b91c1c', overlay: 'rgba(127,29,29,0.45)', shadow: 'rgba(220,38,38,0.15)' },
    { id: 'orange', name: 'Orange', bg: '#fff7ed', surface: '#ffffff', heading: '#431407', text: '#44403c', muted: '#a8a29e', border: '#fed7aa', accent: '#f97316', accentHover: '#ea580c', accentText: '#ffffff', input: '#ffffff', error: '#dc2626', overlay: 'rgba(67,20,7,0.45)', shadow: 'rgba(249,115,22,0.15)' },
    { id: 'purple', name: 'Purple', bg: '#f5f3ff', surface: '#ffffff', heading: '#3b0764', text: '#374151', muted: '#9ca3af', border: '#ddd6fe', accent: '#7c3aed', accentHover: '#6d28d9', accentText: '#ffffff', input: '#ffffff', error: '#dc2626', overlay: 'rgba(59,7,100,0.45)', shadow: 'rgba(124,58,237,0.15)' },
    // PRO
    { id: 'sunset', name: 'Sunset', pro: true, bg: '#fff1e6', surface: '#fffaf5', heading: '#9a3412', text: '#57534e', muted: '#a8a29e', border: '#fdba74', accent: '#f43f5e', accentHover: '#e11d48', accentText: '#ffffff', input: '#ffffff', error: '#be123c', overlay: 'rgba(154,52,18,0.42)', shadow: 'rgba(244,63,94,0.18)' },
    { id: 'rose', name: 'Rose', pro: true, bg: '#fff1f2', surface: '#ffffff', heading: '#881337', text: '#57534e', muted: '#a8a29e', border: '#fecdd3', accent: '#e11d48', accentHover: '#be123c', accentText: '#ffffff', input: '#ffffff', error: '#b91c1c', overlay: 'rgba(136,19,55,0.42)', shadow: 'rgba(225,29,72,0.15)' },
    { id: 'lavender', name: 'Lavender', pro: true, bg: '#faf5ff', surface: '#ffffff', heading: '#581c87', text: '#52525b', muted: '#a1a1aa', border: '#e9d5ff', accent: '#c084fc', accentHover: '#a855f7', accentText: '#2e1065', input: '#ffffff', error: '#dc2626', overlay: 'rgba(88,28,135,0.35)', shadow: 'rgba(168,85,247,0.15)' },
    { id: 'ocean', name: 'Ocean', pro: true, bg: '#ecfeff', surface: '#ffffff', heading: '#134e4a', text: '#334155', muted: '#94a3b8', border: '#a5f3fc', accent: '#0d9488', accentHover: '#0f766e', accentText: '#ffffff', input: '#ffffff', error: '#dc2626', overlay: 'rgba(19,78,74,0.45)', shadow: 'rgba(13,148,136,0.15)' },
    { id: 'sand', name: 'Sand', pro: true, bg: '#faf6f0', surface: '#fffdf9', heading: '#3f2d20', text: '#57483a', muted: '#a8998a', border: '#e7dccd', accent: '#a16207', accentHover: '#854d0e', accentText: '#ffffff', input: '#ffffff', error: '#b91c1c', overlay: 'rgba(63,45,32,0.45)', shadow: 'rgba(63,45,32,0.12)' },
    { id: 'citrus', name: 'Citrus', pro: true, bg: '#fefce8', surface: '#ffffff', heading: '#1c1917', text: '#44403c', muted: '#a8a29e', border: '#fde68a', accent: '#facc15', accentHover: '#eab308', accentText: '#1c1917', input: '#ffffff', error: '#dc2626', overlay: 'rgba(28,25,23,0.5)', shadow: 'rgba(28,25,23,0.12)' },
    { id: 'midnight', name: 'Midnight', pro: true, bg: '#0f172a', surface: '#1e293b', heading: '#f8fafc', text: '#cbd5e1', muted: '#64748b', border: '#334155', accent: '#fbbf24', accentHover: '#f59e0b', accentText: '#111827', input: '#0f172a', error: '#f87171', overlay: 'rgba(2,6,23,0.72)', shadow: 'rgba(0,0,0,0.45)' },
    { id: 'forest', name: 'Forest', pro: true, bg: '#052e16', surface: '#14532d', heading: '#f0fdf4', text: '#bbf7d0', muted: '#4ade80', border: '#166534', accent: '#a3e635', accentHover: '#84cc16', accentText: '#052e16', input: '#052e16', error: '#fca5a5', overlay: 'rgba(5,46,22,0.72)', shadow: 'rgba(0,0,0,0.4)' },
    { id: 'neon', name: 'Neon night', pro: true, bg: '#09090b', surface: '#18181b', heading: '#fafafa', text: '#d4d4d8', muted: '#71717a', border: '#3f3f46', accent: '#ec4899', accentHover: '#db2777', accentText: '#ffffff', input: '#09090b', error: '#fb7185', overlay: 'rgba(9,9,11,0.75)', shadow: 'rgba(236,72,153,0.25)' },
    { id: 'mint', name: 'Mint', pro: true, bg: '#f0fdfa', surface: '#ffffff', heading: '#134e4a', text: '#3f3f46', muted: '#a1a1aa', border: '#99f6e4', accent: '#2dd4bf', accentHover: '#14b8a6', accentText: '#042f2e', input: '#ffffff', error: '#dc2626', overlay: 'rgba(4,47,46,0.4)', shadow: 'rgba(20,184,166,0.15)' },
    { id: 'peach', name: 'Peach', pro: true, bg: '#fff5f0', surface: '#ffffff', heading: '#4a1d3f', text: '#57534e', muted: '#a8a29e', border: '#fed7c3', accent: '#fb923c', accentHover: '#f97316', accentText: '#ffffff', input: '#ffffff', error: '#be123c', overlay: 'rgba(74,29,63,0.42)', shadow: 'rgba(251,146,60,0.18)' },
    { id: 'olive', name: 'Olive', pro: true, bg: '#f7f7ee', surface: '#fffff8', heading: '#2f3a1a', text: '#4b5320', muted: '#9a9f7a', border: '#dfe3c4', accent: '#65a30d', accentHover: '#4d7c0f', accentText: '#ffffff', input: '#ffffff', error: '#b91c1c', overlay: 'rgba(47,58,26,0.45)', shadow: 'rgba(101,163,13,0.15)' },
    { id: 'coffee', name: 'Coffee', pro: true, bg: '#1c1410', surface: '#2a1f19', heading: '#fdf6ec', text: '#e7d8c9', muted: '#a08b7a', border: '#4a3a30', accent: '#d4a373', accentHover: '#c08a52', accentText: '#1c1410', input: '#1c1410', error: '#fca5a5', overlay: 'rgba(28,20,16,0.75)', shadow: 'rgba(0,0,0,0.45)' },
    { id: 'slate', name: 'Slate', pro: true, bg: '#0b1220', surface: '#1e293b', heading: '#f1f5f9', text: '#cbd5e1', muted: '#64748b', border: '#334155', accent: '#22d3ee', accentHover: '#06b6d4', accentText: '#082f49', input: '#0f172a', error: '#fca5a5', overlay: 'rgba(2,6,23,0.72)', shadow: 'rgba(0,0,0,0.45)' },
    { id: 'berry', name: 'Berry', pro: true, bg: '#2a0a1f', surface: '#3b0f2c', heading: '#fdf2f8', text: '#f5d0e6', muted: '#c084a7', border: '#5b1a44', accent: '#f472b6', accentHover: '#ec4899', accentText: '#2a0a1f', input: '#2a0a1f', error: '#fda4af', overlay: 'rgba(42,10,31,0.75)', shadow: 'rgba(0,0,0,0.45)' },
    { id: 'royal', name: 'Royal', pro: true, bg: '#1e1b4b', surface: '#312e81', heading: '#eef2ff', text: '#c7d2fe', muted: '#818cf8', border: '#4338ca', accent: '#fbbf24', accentHover: '#f59e0b', accentText: '#1e1b4b', input: '#1e1b4b', error: '#fca5a5', overlay: 'rgba(30,27,75,0.72)', shadow: 'rgba(0,0,0,0.4)' },
  ];

  // Design tab fields per editor. "bgType" fields switch an image background back to a colour
  // (unless "Keep background image" is ticked); "match" fields tell which palette is in use.
  const EDITORS = {
    popup: {
      detect: () => $('#ppsgenEditObjFormPopup [name^="data[popup][design]"]').length > 0,
      prefix: 'data[popup][design]',
      bgType: ['popup_bg_type', 'overlay_bg_type'],
      match: ['popup_background', 'popup_font_color', 'popup_background_overlay', 'popup_border_color'],
      design: (p) => ({
        popup_background: p.surface,
        popup_border_color: p.border,
        popup_font_color: p.text,
        popup_background_overlay: p.overlay,
        popup_box_shadow_color: p.shadow,
        popup_scrollbar_track_color: p.bg,
        popup_scrollbar_thumb_color: p.muted,
        popup_scrollbar_thumb_hover_color: p.accent,
        popup_scrollbar_button_color: p.muted,
        popup_scrollbar_button_hover_color: p.accent,
        popup_scrollbar_corner_color: p.bg,
      }),
      elements: true,
    },
    form: {
      detect: () => $('[name^="data[form][design]"]').length > 0,
      prefix: 'data[form][design]',
      bgType: ['form_bg_type'],
      match: ['form_background', 'submit_background', 'finput_border_color', 'ftitle_font_color'],
      design: (p) => ({
        form_background: p.surface,
        form_border_color: p.border,
        form_box_shadow_color: p.shadow,
        form_font_color: p.text,
        form_select_icon_color: p.muted,
        ftitle_font_color: p.heading,
        finput_font_color: p.text,
        fplace_font_color: p.muted,
        finput_border_color: p.border,
        finput_background: p.input,
        finput_box_shadow_color: p.shadow,
        ferror_font_color: p.error,
        label_font_color: p.text,
        requiredicon_font_color: p.error,
        submit_font_color: p.accentText,
        submit_font_color_hover: p.accentText,
        submit_border_color: p.accent,
        submit_border_color_hover: p.accentHover,
        submit_background: p.accent,
        submit_background_hover: p.accentHover,
        submit_box_shadow_color: p.shadow,
        tooltiptext_font_color: p.surface,
        tooltiptext_border_color: p.heading,
        tooltiptext_background: p.heading,
        tooltipicon_color_icon: p.surface,
        tooltipicon_color_bg: p.muted,
        radcheck_font_color: p.text,
        radcheckcus_font_color: p.accent,
      }),
      elements: false,
    },
    template: {
      detect: () => $('#ppsgenEditObjFormTemplate [name^="data[template][design]"]').length > 0,
      prefix: 'data[template][design]',
      bgType: [],
      match: ['template_email_background', 'template_container_background', 'template_font_color', 'template_border_color'],
      design: (p) => ({
        template_email_background: p.bg,
        template_container_background: p.surface,
        template_border_color: p.border,
        template_font_color: p.text,
      }),
      elements: true,
    },
  };

  // Colours of the elements (popups and emails style each element on its own)
  const elementPatch = (type, options, p) => {
    const patch = {};
    const has = (name) => options[name] !== undefined && options[name] !== null && String(options[name]).trim() !== '';
    if (type === 'title') {
      patch.font_color = p.heading;
    } else if (type === 'textarea' || type === 'description' || type === 'desc' || type === 'unsubscribe') {
      patch.font_color = type === 'unsubscribe' ? p.muted : p.text;
      patch.link_color = p.accent;
      patch.link_hover_color = p.accentHover;
    } else if (type === 'button') {
      patch.background = p.accent;
      patch.background_hover = p.accentHover;
      patch.font_color = p.accentText;
      patch.border_color = p.accent;
    } else if (/icon|social/.test(type)) {
      patch.font_color = p.accent;
      patch.svg_icon_color = p.accent;
      patch.link_color = p.accent;
      patch.link_hover_color = p.accentHover;
    } else if (type === 'divider') {
      patch.border_color = p.border;
      if (has('background')) {
        patch.background = p.border;
      }
    } else if (type === 'row' || type === 'column') {
      // Only boxes that were given a colour get one: a soft tint of the palette
      if (has('background')) {
        patch.background = p.bg;
      }
      if (has('border_color')) {
        patch.border_color = p.border;
      }
    } else if (has('font_color')) {
      patch.font_color = p.text;
    }
    if (has('box_shadow_color')) {
      patch.box_shadow_color = p.shadow;
    }
    return patch;
  };

  const normalize = (value) => String(value || '').replace(/\s+/g, '').toLowerCase();

  class PpsgenPalettes {
    constructor() {
      this.kind = null;
      this.cfg = null;
      this.undo = null;
    }

    init() {
      this.kind = Object.keys(EDITORS).find((kind) => EDITORS[kind].detect()) || null;
      const $tab = $('.ppsgenObjEditTab[data-tab="design"]').first();
      if (!this.kind || !$tab.length || $tab.find('.ppsgenPalettes').length) {
        return;
      }
      this.cfg = EDITORS[this.kind];
      this.$panel = $(this.markup()).prependTo($tab);
      this.$panel.on('click', '.ppsgenPalette', (e) => this.pick($(e.currentTarget).data('palette')));
      this.$panel.on('click', '.ppsgenPalettesUndo', (e) => {
        e.preventDefault();
        this.revert();
      });
      this.refreshActive();
    }

    field(name) {
      return $(`[name="${this.cfg.prefix}[${name}]"]`).first();
    }

    hasImageBackground() {
      return this.cfg.bgType.some((name) => this.field(name).val() === 'image');
    }

    card(p) {
      const locked = p.pro && !l10n.pro;
      return `<button type="button" class="ppsgenPalette${locked ? ' ppsgenPaletteLocked' : ''}" data-palette="${p.id}" aria-pressed="false" title="${$('<div>').text(t(`name_${p.id}`, p.name)).html()}">
        <span class="ppsgenPaletteMock" style="background:${p.bg}">
          <span class="ppsgenPaletteCard" style="background:${p.surface};border-color:${p.border}">
            <i style="background:${p.heading};width:70%"></i>
            <i style="background:${p.text};width:90%;opacity:.55"></i>
            <i style="background:${p.text};width:60%;opacity:.55"></i>
            <b style="background:${p.accent}"></b>
          </span>
        </span>
        <span class="ppsgenPaletteDots">${[p.surface, p.heading, p.accent, p.bg].map((c) => `<em style="background:${c}"></em>`).join('')}</span>
        <span class="ppsgenPaletteName">${$('<div>').text(t(`name_${p.id}`, p.name)).html()}${locked ? ' <span class="ppsgenPaletteLock dashicons dashicons-lock"></span>' : ''}</span>
        ${p.pro ? '<span class="ppsgenPaletteBadge">PRO</span>' : ''}
      </button>`;
    }

    markup() {
      // One strip in two rows, read left to right: the top row first (Free palettes lead), then the bottom row.
      // The grid fills column by column, so the cards are interleaved top / bottom.
      const half = Math.ceil(PALETTES.length / 2);
      const ordered = [];
      for (let i = 0; i < half; i++) {
        ordered.push(PALETTES[i]);
        if (PALETTES[i + half]) {
          ordered.push(PALETTES[i + half]);
        }
      }
      const cards = ordered.map((p) => this.card(p)).join('');
      const keep = this.cfg.bgType.length
        ? `<label class="ppsgenPalettesKeep"${this.hasImageBackground() ? '' : ' hidden'}><input type="checkbox" class="ppsgenPalettesKeepImage" data-ppsgen-no-dirty> ${t('keepImage', 'Keep the background image')}</label>`
        : '';
      return `<section class="ppsgenPalettes" aria-label="${t('title', 'Color style')}">
        <div class="ppsgenPalettesHead">
          <h3>${t('title', 'Color style')}</h3>
          <span class="ppsgenPalettesHint">${t(`hint_${this.kind}`, t('hint', 'Pick a palette: all colors below and the colors of the elements change to match. Save to keep it.'))}</span>
        </div>
        <div class="ppsgenPalettesGrid" role="list">${cards}</div>
        <div class="ppsgenPalettesFoot">${keep}<span class="ppsgenPalettesStatus" aria-live="polite"></span></div>
      </section>`;
    }

    pick(id) {
      const palette = PALETTES.find((p) => p.id === id);
      if (!palette) {
        return;
      }
      if (palette.pro && !l10n.pro) {
        // The PRO offer dialog, showing the palette that was clicked; the old status line if it isn't loaded
        if (window.ppsgenProOffer) {
          const $mock = $(this.card(palette)).find('.ppsgenPaletteMock');
          window.ppsgenProOffer.open({
            eyebrow: t('offerEyebrow', 'This palette comes with PRO'),
            title: t('offerTitle', 'Every color style, one click away'),
            text: t('offerText', ''),
            previewHtml: $mock.prop('outerHTML'),
            caption: t(`name_${palette.id}`, palette.name),
            campaign: `palettes_pro_${this.kind}`,
          });
        } else {
          this.status(`${t('proOnly', 'This palette is part of PRO.')} <a href="${l10n.upgradeUrl || '#'}">${t('upgrade', 'Get PRO')}</a>`);
        }
        return;
      }
      this.apply(palette);
    }

    setValue(name, value, changes) {
      const $field = this.field(name);
      if (!$field.length || $field.val() === value) {
        return;
      }
      changes.push({ $field, value: $field.val() });
      $field.val(value);
      // Colour fields sync their picker on input; selects show / hide their related rows on change
      $field.trigger($field.is('select') ? 'change' : 'input');
    }

    apply(palette) {
      const changes = [];
      const elements = [];
      const keepImage = this.$panel.find('.ppsgenPalettesKeepImage').is(':checked');
      if (!keepImage) {
        this.cfg.bgType.forEach((name) => this.setValue(name, 'color', changes));
      }
      Object.entries(this.cfg.design(palette)).forEach(([name, value]) => this.setValue(name, value, changes));

      const editor = window.ppsgenBuilderOutline ? window.ppsgenBuilderOutline.editor : null;
      if (this.cfg.elements && editor && editor.fieldOptionsMap) {
        if (editor.currentFieldKey && typeof editor.getOptionsFromForm === 'function') {
          editor.fieldOptionsMap[editor.currentFieldKey] = editor.getOptionsFromForm();
        }
        Object.entries(editor.fieldOptionsMap).forEach(([key, options]) => {
          if (!options || typeof options !== 'object') {
            return;
          }
          const type = String(options.field_type || '');
          Object.entries(elementPatch(type, options, palette)).forEach(([name, value]) => {
            if (options[name] !== value) {
              elements.push({ key, name, value: options[name] });
              options[name] = value;
            }
          });
        });
        if (editor.currentFieldKey && typeof editor.setOptionsToForm === 'function') {
          editor.setOptionsToForm(editor.fieldOptionsMap[editor.currentFieldKey]);
        }
      }
      this.undo = { changes, elements, name: t(`name_${palette.id}`, palette.name) };
      this.afterChange();
      this.status(`${t('applied', 'Applied')}: <strong>${$('<div>').text(this.undo.name).html()}</strong> · <a href="#" class="ppsgenPalettesUndo">${t('undo', 'Undo')}</a>`);
    }

    revert() {
      if (!this.undo) {
        return;
      }
      this.undo.changes.reverse().forEach(({ $field, value }) => {
        $field.val(value);
        $field.trigger($field.is('select') ? 'change' : 'input');
      });
      const editor = window.ppsgenBuilderOutline ? window.ppsgenBuilderOutline.editor : null;
      if (editor && editor.fieldOptionsMap) {
        this.undo.elements.forEach(({ key, name, value }) => {
          if (editor.fieldOptionsMap[key]) {
            if (value === undefined) {
              delete editor.fieldOptionsMap[key][name];
            } else {
              editor.fieldOptionsMap[key][name] = value;
            }
          }
        });
        if (editor.currentFieldKey && typeof editor.setOptionsToForm === 'function') {
          editor.setOptionsToForm(editor.fieldOptionsMap[editor.currentFieldKey]);
        }
      }
      this.undo = null;
      this.afterChange();
      this.status(t('undone', 'Previous colors are back.'));
    }

    afterChange() {
      if (window.ppsgenUnsavedGuard) {
        window.ppsgenUnsavedGuard.mark(this.$panel[0]);
      }
      if (window.ppsgenLivePreview) {
        window.ppsgenLivePreview.signature = null;
        window.ppsgenLivePreview.tick(true);
      }
      this.refreshActive();
    }

    refreshActive() {
      const current = this.cfg.match.map((name) => normalize(this.field(name).val()));
      const design = (p) => this.cfg.design(p);
      this.$panel.find('.ppsgenPalette').each((_, el) => {
        const p = PALETTES.find((x) => x.id === el.getAttribute('data-palette'));
        const on = !!p && this.cfg.match.every((name, i) => normalize(design(p)[name]) === current[i]);
        $(el).toggleClass('ppsgenPaletteOn', on).attr('aria-pressed', on ? 'true' : 'false');
      });
      this.$panel.find('.ppsgenPalettesKeep').prop('hidden', !this.hasImageBackground() && !(this.undo && this.undo.changes.some((c) => this.cfg.bgType.some((n) => c.$field.is(`[name="${this.cfg.prefix}[${n}]"]`)))));
    }

    status(html) {
      this.$panel.find('.ppsgenPalettesStatus').html(html);
    }
  }

  window.ppsgenPalettes = new PpsgenPalettes();
  // After the editors have built their tabs
  $(function () {
    setTimeout(() => window.ppsgenPalettes.init(), 0);
  });
})(jQuery);
