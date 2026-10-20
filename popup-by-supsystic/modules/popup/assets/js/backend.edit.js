function utf8ToBase64(str) {
  return btoa(unescape(encodeURIComponent(str)));
}

function base64ToUtf8(str) {
  return decodeURIComponent(escape(atob(str)));
}

(function ($) {
  /**
   * Form builder class for managing popup fields with flat structure and parent_key.
   * @class
   */
  class ppsgenFormBuilder {
    /**
     * Initializes the form builder with DOM elements and state.
     * @constructor
     */
    constructor() {
      this.form = $('#ppsgenEditObjFormPopup');
      this.fieldsContainer = $('.ppsgenCfbFieldsWrapper');
      this.optionsContainer = $('.ppsgenPopupFieldsRight');
      this.currentFieldKey = null;
      this.fieldOptionsMap = typeof ppsgenPopupData !== 'undefined' && ppsgenPopupData.fields ? ppsgenPopupData.fields : {};
      this.predefinedOptions = {};
      this.screenActual = 'pc';
      this.screenRules = false;
      this.parentTypes = ['row', 'column'];
      this.init();
    }

    /**
     * Initializes event bindings and sortable functionality.
     */
    init() {
      this.bindEvents();
      this.initSortable();
      this.checkFieldListBtnToggle();
      if (window.ppsgenLivePreview) {
        window.ppsgenLivePreview.attach(this, { left: '.ppsgenPopupFieldsLeft', right: '.ppsgenPopupFieldsRight', builderTab: 'template', sideTabs: ['design', 'options', 'custom_css'], widths: { pc: 1280, tablet: 820, mobile: 390 }, fullButton: '.ppsgenLoadPreview' });
      }
    }

    /**
     * Binds events for form interactions.
     */
    bindEvents() {
      $('.ppsgenObjEditSaveBtn').on('click', () => this.handleSave());
      this.form.on('submit', (e) => this.handleSubmit(e));
      $('body').on('click', '.ppsgenLoadPreview', (e) => this.handlePreview(e));
      $('body').on('click', '.ppsgenCfbAddOtherFieldBtn', (e) => this.addField(e));
      $('body').on('click', '.ppsgen-popup-col', (e) => this.selectField(e));
      $('body').on('click', '.ppsgenCffDuplicateBtn', (e) => this.duplicateField(e));
      $('body').on('click', '.ppsgenCffDeleteBtn', (e) => this.deleteField(e));
      $('body').on('click', '.ppsgen-media-upload-btn', (e) => this.openMediaUploader(e));
      // $('body').on('click', '.ppsgen-add-social-link', (e) => this.addSocialLink(e));
      $('body').on('change', '[name^="col_"], [name^="show_"]', () => {
        if (this.currentFieldKey) {
          this.fieldOptionsMap[this.currentFieldKey] = this.getOptionsFromForm();
          this.recalcScreenFieldCols();
        }
      });
      $('body').on('click', '.ppsgenComponentListTabBtn', function () {
        let tab = $(this).data('tab');
        $('.ppsgenComponentListTabBtn').removeClass('ppsgenComponentListTabBtnActive');
        $(this).addClass('ppsgenComponentListTabBtnActive');
        $('.ppsgenComponentListTab').removeClass('ppsgenComponentListTabActive');
        $('.ppsgenComponentListTab[data-tab="' + tab + '"]').addClass('ppsgenComponentListTabActive');
      });
      $('.ppsgenCfbSizeChangerBtnPc').on('click', () => this.setScreen('pc'));
      $('.ppsgenCfbSizeChangerBtnTab').on('click', () => this.setScreen('tab'));
      $('.ppsgenCfbSizeChangerBtnMob').on('click', () => this.setScreen('mob'));
      $('.ppsgenCfbSizeChangerToggle').on('click', () => this.toggleScreenRules());

      $('body')
        .find('.ppsgenPopupFieldsRight .wp-color-picker')
        .each(function () {
          const picker = $(this);
          const relatedInput = picker.closest('.ppsgen-color-picker').find('.ppsgenColorInputText');
          relatedInput.on('input change', function () {
            const val = $(this).val().trim();
            picker.off('change.wpcolor');
            picker.val(val).wpColorPicker('color', val);
          });
        });
    }

    /**
     * Initializes sortable functionality for fields and nested elements.
     */
    initSortable() {
      // Top-level sortable
      this.initSortableForContainer(this.fieldsContainer);
      // Initialize sortable for existing child containers
      this.fieldsContainer.find('.ppsgen-popup-col-inner').each((_, inner) => {
        this.initSortableForContainer($(inner));
      });
    }

    /**
     * Initializes sortable for a specific container.
     * @param {jQuery} container - The container to make sortable.
     */
    initSortableForContainer(container) {
      new Sortable(container[0], {
        items: '.ppsgen-popup-col',
        animation: 150,
        ghostClass: 'ppsgen-popup-ghost',
        chosenClass: 'ppsgen-popup-chosen',
        dragClass: 'ppsgen-popup-drag',
        forceFallback: true,
        group: 'nested',
        fallbackTolerance: 3,
        swapThreshold: 0.65,
        onEnd: (evt) => {
          //console.log('Sortable onEnd: item=', evt.item, 'from=', evt.from, 'to=', evt.to);
          this.calculateFieldOrder();
        },
      });
    }

    /**
     * Triggers form submission if valid.
     */
    handleSave() {
      if (this.form[0].reportValidity()) {
        this.form.trigger('submit');
      }
    }

    beforePopupSubmit() {
      if (typeof window.ppsgenPopupBeforeSubmit === 'function') {
        return window.ppsgenPopupBeforeSubmit(this) !== false;
      }
      return true;
    }

    syncCustomCodeEditors() {
      if (typeof window.ppsgenSavePopupCodeEditors === 'function') {
        window.ppsgenSavePopupCodeEditors();
      }
      this.form.find('textarea.ppsgen-custom-code-editor, textarea[name="data[popup][settings][custom_css]"], textarea[name="data[popup][settings][custom_js]"]').each(function () {
        const codeMirror = $(this).siblings('.CodeMirror')[0]?.CodeMirror;
        if (codeMirror && typeof codeMirror.save === 'function') {
          codeMirror.save();
        }
      });
      this.form.find('.CodeMirror').each(function () {
        const codeMirror = this.CodeMirror;
        if (!codeMirror || typeof codeMirror.getTextArea !== 'function') {
          return;
        }
        const textarea = codeMirror.getTextArea();
        if (textarea && (textarea.classList.contains('ppsgen-custom-code-editor') || /^data\[popup\]\[settings\]\[custom_(css|js)\]$/.test(textarea.name))) {
          textarea.value = codeMirror.getValue();
        }
      });
    }

    appendCustomCodeToDataBlock(dataBlock) {
      this.syncCustomCodeEditors();
      this.form
        .find('textarea[name="data[popup][settings][custom_css]"], textarea[name="data[popup][settings][custom_js]"]')
        .each(function () {
          dataBlock[this.name] = $(this).val() || '';
        });
    }

    /**
     * Handles form submission, including data serialization and AJAX request.
     * @param {Event} e - The submit event.
     * @returns {boolean} - Returns false to prevent default form submission.
     */
    handleSubmit(e) {
      e.preventDefault();
      if (typeof tinyMCE !== 'undefined') {
        tinyMCE.triggerSave();
      }
      this.syncCustomCodeEditors();
      if (!this.beforePopupSubmit()) {
        return false;
      }

      if (this.currentFieldKey) {
        this.fieldOptionsMap[this.currentFieldKey] = this.getOptionsFromForm();
      }

      let formDataArray = this.form.serializeArray();
      const normalData = {};
      const dataBlock = {};

      formDataArray.forEach((item) => {
        if (item.name.startsWith('data[')) {
          const name = item.name;
          const value = item.value;
          if (name.endsWith('[]')) {
            const cleanName = name.slice(0, -2);
            if (!dataBlock[cleanName]) {
              dataBlock[cleanName] = [];
            }
            if (value !== '') {
              dataBlock[cleanName].push(value);
            }
          } else {
            dataBlock[name] = value;
          }
        } else {
          const allowedKeys = ['plugin', 'mod', 'obj_id', 'action', 'nonce_code'];
          if (allowedKeys.includes(item.name)) {
            normalData[item.name] = item.value;
          }
        }
      });

      normalData['mod'] = window.ppsgenPopupController ?? normalData['mod'] ?? 'popup';

      if (typeof this.fieldOptionsMap !== 'undefined') {
        Object.entries(this.fieldOptionsMap).forEach(([fieldKey, fieldData]) => {
          const serialized = this.serializeNested(fieldData, `data[popup][fields][${fieldKey}]`);
          const pairs = serialized.replace(/^&/, '').split('&');
          pairs.forEach((pair) => {
            if (!pair) return;
            let [name, value] = pair.split('=');
            value = decodeURIComponent(value || '');
            if (name.endsWith('[]')) {
              const cleanName = name.slice(0, -2);
              if (!dataBlock[cleanName]) dataBlock[cleanName] = [];
              if (value !== '') dataBlock[cleanName].push(value);
            } else {
              dataBlock[name] = value;
            }
          });
        });
      }

      if (typeof this.fieldOptionsMap !== 'undefined') {
        Object.entries(this.fieldOptionsMap).forEach(([fieldKey, fieldData]) => {
          const serialized = this.serializeNested(fieldData, `data[popup][fields][${fieldKey}]`);
          serialized
            .replace(/^&/, '')
            .split('&')
            .forEach((pair) => {
              const [name, value] = pair.split('=');
              if (!name.startsWith('data[')) return;
              dataBlock[name] = decodeURIComponent(value || '');
            });
        });
      }

      this.appendCustomCodeToDataBlock(dataBlock);
      const packedData = utf8ToBase64(JSON.stringify(dataBlock));
      const payload = window.ppsgenBuildPayloadData(
        {
          ...normalData,
          nonce_code: ppsgenAjax?.nonce || '',
        },
        packedData
      );

      $('.ppsgenObjEditSaveBtn').attr('disabled', 'disabled').prop('disabled', true).find('.dashicons').toggleClass('dashicons-yes-alt dashicons-clock');

      $.post(ajaxurl, payload, (response) => {
        $('.ppsgenObjEditSaveBtn').removeAttr('disabled').prop('disabled', false).find('.dashicons').toggleClass('dashicons-yes-alt dashicons-clock');
        if (response?.success) {
          const msg = response.data?.message || 'Popup is successfully saved';
          this.showPopupNotice('success', 'edit', msg);
          const url = response.data?.obj_id && ppsgenAjax?.admin_url ? `${ppsgenAjax.admin_url}?page=ppsgen&tab=popup_edit&id=${response.data.obj_id}` : '';
          setTimeout(() => {
            if (url) {
              location.href = url;
            } else {
              // location.reload();
            }
          }, 1000);
        } else {
          const msg = response.data?.message || 'Failed to save popup';
          this.showPopupNotice('error', 'edit', msg);
        }
      }).fail(() => {
        $('.ppsgenObjEditSaveBtn').removeAttr('disabled').prop('disabled', false).find('.dashicons').toggleClass('dashicons-yes-alt dashicons-clock');
        this.showPopupNotice('error', 'edit', 'There was an error saving the popup');
      });
      return false;
    }

    /**
     * Serializes array for form data.
     * @param {Array} arr - The array to serialize.
     * @param {string} prefix - The prefix for the serialized keys.
     * @returns {string} - The serialized string.
     */
    serializeArray(arr, prefix) {
      let str = '';
      arr.forEach((val, i) => {
        const fullPrefix = `${prefix}[${i}]`;
        if (typeof val === 'object' && val !== null) {
          str += this.serializeNested(val, fullPrefix);
        } else {
          str += `&${fullPrefix}=${encodeURIComponent(val ?? '')}`;
        }
      });
      return str;
    }

    /**
     * Serializes nested object for form data.
     * @param {Object} obj - The object to serialize.
     * @param {string} prefix - The prefix for the serialized keys.
     * @returns {string} - The serialized string.
     */
    serializeNested(obj, prefix) {
      let str = '';
      const build = (o, pre) => {
        if (Array.isArray(o)) {
          o.forEach((value, i) => {
            if (typeof value === 'object' && value !== null) {
              build(value, `${pre}[${i}]`);
            } else {
              str += `&${pre}[]=${encodeURIComponent(value ?? '')}`;
            }
          });
        } else if (typeof o === 'object' && o !== null) {
          Object.entries(o).forEach(([key, value]) => {
            build(value, `${pre}[${key}]`);
          });
        } else {
          str += `&${pre}=${encodeURIComponent(o ?? '')}`;
        }
      };
      build(obj, prefix);
      return str;
    }

    /**
     * Displays a popup notice or fallback alert with an escaped message.
     * @param {string} status - The status of the notice ('success' or 'error').
     * @param {string} operation - The operation type ('edit').
     * @param {string} msg - The message to display.
     */
    showPopupNotice(status, operation, msg) {
      const escapedMsg = $('<div>').text(msg).html();
      if (typeof jQuery().ppsgenShowPopupNotice === 'function') {
        jQuery().ppsgenShowPopupNotice({ status, operation, msg: escapedMsg });
      } else {
        console.warn('ppsgenShowPopupNotice is not defined. Displaying fallback alert.');
        alert(`${status.toUpperCase()}: ${escapedMsg}`);
      }
    }

    /**
     * Opens media uploader for image selection.
     * @param {Event} e - The click event.
     */
    openMediaUploader(e) {
      e.preventDefault();
      const $button = $(e.currentTarget);
      const $input = $button.prev('input.ppsgen-media-upload');
      const frame = wp.media({
        title: 'Select Image',
        button: { text: 'Use Image' },
        multiple: false,
      });
      frame.on('select', () => {
        const attachment = frame.state().get('selection').first().toJSON();
        $input.val(attachment.url);
      });
      frame.open();
    }

    /**
     * Adds a new field to the popup.
     * @param {Event} e - The click event.
     */
    addField(e) {
      const $btn = $(e.currentTarget);
      const $selected = $btn.closest('.ppsgenCfbAddFieldWrapper').find('.ppsgenCfbAddOtherFieldList option:selected');
      if ($selected.attr('disabled')) return;
      $btn.hide();
      const fieldOpt = $selected[0];
      const fType = $(fieldOpt).data('field-type') || '';
      // if (['submit', 'captcha', 'form'].includes(fType)) {
      //   $selected.prop('disabled', true);
      // }
      const key = this.generateUniqueFieldKey();
      const fieldHtmlAttr = `data-field-type="${this.sanitizeAttr(fType)}" data-field-key="${this.sanitizeAttr(key)}"`;
      const colSizes = this.getColSizes(fType);
      const fieldHtml = this.createFieldHtml(key, fType, fieldHtmlAttr);
      const predefinedOptions = this.createPredefinedOptions(key, fType, colSizes);
      // New titles and texts start with placeholder text, so it is clear they were added
      if (window.ppsgenApplyDefaultText) {
        window.ppsgenApplyDefaultText(predefinedOptions, fType);
      }

      if (this.currentFieldKey) {
        this.fieldOptionsMap[this.currentFieldKey] = this.getOptionsFromForm();
      }
      this.fieldOptionsMap[key] = predefinedOptions;
      this.addFieldToContainer(fieldHtml, key, predefinedOptions, fType);
      this.setOptionsToForm(this.fieldOptionsMap[key]);
      this.refreshOptionsState(fType);
      this.activateField(key);
      $btn.show();
      this.currentFieldKey = key;
      this.calculateFieldOrder();
      this.recalcScreenFieldCols();
      this.checkFieldListBtnToggle();
    }

    /**
     * Escapes attribute values to prevent XSS.
     * @param {string} value - The value to escape.
     * @returns {string} - The escaped value.
     */
    sanitizeAttr(value) {
      return encodeURIComponent(value);
    }

    /**
     * Defines column sizes and visibility for different field types.
     * @param {string} fType - The field type.
     * @returns {Object} - Object containing column sizes and visibility settings.
     */
    getColSizes(fType) {
      let colMob = 12,
        colTab = 6,
        colPc = 6;
      // if (['row', 'column', 'divider', 'desc', 'title', 'textarea'].includes(fType)) {
      colMob = colTab = colPc = 12;
      // } else if (fType === 'empty') {
      //   colMob = colTab = colPc = 1;
      // }
      return { colMob, colTab, colPc, showMob: true, showTab: true, showPc: true };
    }

    /**
     * Creates HTML for a new field with escaped content.
     * @param {string} key - The unique field key.
     * @param {string} fType - The field type.
     * @param {string} fieldHtmlAttr - The HTML attributes for the field.
     * @returns {string} - The generated HTML string.
     */
    createFieldHtml(key, fType, fieldHtmlAttr) {
      const escapedContent = $('<div>').text(fType).html();
      let fieldHtml = `<div ${fieldHtmlAttr} class="ppsgen-popup-col ppsgen-popup-type-${this.sanitizeAttr(fType)}">`;
      fieldHtml += `<div class="ppsgen-popup-label">${fType !== 'empty' ? escapedContent : '-'}</div>`;
      if (this.parentTypes.includes(fType)) {
        fieldHtml += '<div class="ppsgen-popup-col-inner"></div>';
      }
      fieldHtml += '</div>';
      return fieldHtml;
    }

    /**
     * Creates predefined options for a new field.
     * @param {string} key - The unique field key.
     * @param {string} fType - The field type.
     * @param {Object} colSizes - The column sizes and visibility settings.
     * @returns {Object} - The predefined options object.
     */
    createPredefinedOptions(key, fType, colSizes) {
      let defList = {
        field_key: key,
        field_type: fType,
        parent_key: '', // Initialize as empty, set in addFieldToContainer or calculateFieldOrder
        col_mob: colSizes.colMob,
        col_tab: colSizes.colTab,
        col_pc: colSizes.colPc,
        show_mob: colSizes.showMob ? '1' : '0',
        show_tab: colSizes.showTab ? '1' : '0',
        show_pc: colSizes.showPc ? '1' : '0',
        padding_top: '0',
        padding_top_unit: 'px',
        padding_right: '0',
        padding_right_unit: 'px',
        padding_bottom: '0',
        padding_bottom_unit: 'px',
        padding_left: '0',
        padding_left_unit: 'px',
        margin_top: '0',
        margin_top_unit: 'px',
        margin_right: '0',
        margin_right_unit: 'px',
        margin_bottom: '0',
        margin_bottom_unit: 'px',
        margin_left: '0',
        margin_left_unit: 'px',
        gap: '0',
        gap_unit: 'px',
        box_shadow_top: '0',
        box_shadow_top_unit: 'px',
        box_shadow_right: '0',
        box_shadow_right_unit: 'px',
        box_shadow_bottom: '0',
        box_shadow_bottom_unit: 'px',
        box_shadow_left: '0',
        box_shadow_left_unit: 'px',
        text_shadow_top: '0',
        text_shadow_top_unit: 'px',
        text_shadow_right: '0',
        text_shadow_right_unit: 'px',
        text_shadow_bottom: '0',
        text_shadow_bottom_unit: 'px',
        text_shadow_left: '0',
        text_shadow_left_unit: 'px',
        width_select: 'auto',
        width: '100',
        width_unit: 'percent',
        max_width_select: 'auto',
        max_width: '100',
        max_width_unit: 'percent',
        height_select: 'auto',
        height: '100',
        height_unit: 'percent',
        max_height_select: 'auto',
        max_height: '100',
        max_height_unit: 'percent',
        font_family: 'Default',
        font_size_unit: 'px',
        border: '0',
        border_unit: 'px',
        border_style: 'solid',
        border_radius: '0',
        border_radius_unit: 'px',
        grid_flex_wrap: '',
        grid_flex_direction: '',
        button_text: '',
        button_target: '0',
        button_url: '',
        form_id: '0',
        icon_class: '',
        icon_target: '0',
        icon_title: '',
        icon_rel: '0',
        icon_url: '',
        svg_icon: '',
        svg_icon_color: '',
      };
      switch (fType) {
        case 'iframe':
          defList['iframe_url'] = '';
          defList['iframe_responsive'] = '1';
          break;
        case 'row':
          defList['width_select'] = 'manual';
          defList['width'] = '100';
          defList['width_unit'] = 'percent';
          break;
        case 'svg_icon':
          defList['text_align'] = 'center';
          defList['svg_icon'] = 'undraw_balloons';
          defList['svg_icon_color'] = '#ff3a3a';
          defList['width_select'] = 'manual';
          defList['width'] = '100';
          defList['width_unit'] = 'percent';
          defList['max_width_select'] = 'manual';
          defList['max_width'] = '100';
          defList['max_width_unit'] = 'percent';
          break;
      }
      return defList;
    }

    /**
     * Adds a field to the container and updates options.
     * @param {string} fieldHtml - The HTML string for the field.
     * @param {string} key - The unique field key.
     * @param {Object} predefinedOptions - The predefined options for the field.
     * @param {string} fType - The field type.
     */
    addFieldToContainer(fieldHtml, key, predefinedOptions, fType) {
      let $targetContainer = this.fieldsContainer;
      if (this.currentFieldKey) {
        const $activeField = this.fieldsContainer.find(`.ppsgen-popup-col[data-field-key="${this.currentFieldKey}"]`);
        if ($activeField.length && this.parentTypes.includes($activeField.data('field-type'))) {
          $targetContainer = $activeField.find('.ppsgen-popup-col-inner').first();
          predefinedOptions.parent_key = this.currentFieldKey;
        }
      }

      $targetContainer.append(fieldHtml);
      if (this.parentTypes.includes(fType)) {
        const $inner = this.fieldsContainer.find(`.ppsgen-popup-col[data-field-key="${key}"] .ppsgen-popup-col-inner`).first();
        this.initSortableForContainer($inner);
      }

      if (predefinedOptions) {
        this.predefinedOptions[key] = predefinedOptions;
        this.fieldOptionsMap[key] = $.extend(true, {}, predefinedOptions);
      } else if (!this.fieldOptionsMap[key]) {
        this.fieldOptionsMap[key] = {};
      }
      this.recalcScreenFieldCols();
    }

    /**
     * Handles field selection.
     * @param {Event} e - The click event.
     */
    selectField(e) {
      e.stopPropagation();
      const $field = $(e.currentTarget);
      const key = $field.data('field-key');
      const fieldType = $field.data('field-type');
      //console.log('selectField: key=', key, 'fieldType=', fieldType, 'fieldOptionsMap[key]=', this.fieldOptionsMap[key]);
      if (!key || !fieldType) {
        this.showPopupNotice('error', 'edit', 'Invalid field key or type');
        return;
      }
      if (this.currentFieldKey) {
        this.fieldOptionsMap[this.currentFieldKey] = this.getOptionsFromForm();
        //console.log('Saved options for previous field:', this.currentFieldKey, this.fieldOptionsMap[this.currentFieldKey]);
      }
      this.activateField(key);
      if (this.fieldOptionsMap[key]) {
        this.setOptionsToForm(this.fieldOptionsMap[key]);
        this.refreshOptionsState(fieldType);
        this.currentFieldKey = key;
        //console.log('Options set for field:', key, this.fieldOptionsMap[key]);
      } else {
        this.showPopupNotice('error', 'edit', 'No options found for field key: ' + key);
      }
    }

    /**
     * Activates a field with animation.
     * @param {string} key - The unique field key.
     */
    activateField(key) {
      const $field = this.fieldsContainer.find(`.ppsgen-popup-col[data-field-key="${key}"]`);
      $('.ppsgen-popup-col-active').removeClass('ppsgen-popup-col-active');
      $field.addClass('click-animate ppsgen-popup-col-active');
      setTimeout(() => $field.removeClass('click-animate'), 300);
    }

    /**
     * Generates a unique key for a new field.
     * @returns {string} - The generated unique key.
     */
    generateUniqueFieldKey() {
      let key;
      do {
        key = Math.random().toString(36).substr(2, 12);
      } while (this.fieldOptionsMap.hasOwnProperty(key));
      return key;
    }

    /**
     * Shows or hides options based on field type.
     * @param {string} fieldType - The field type.
     */
    showOptionsBlockByType(fieldType) {
      //console.log('showOptionsBlockByType: fieldType=', fieldType, 'optionsContainer=', this.optionsContainer);
      this.optionsContainer.show();
      this.optionsContainer.find('.ppsgenComponentRow').each((_, row) => {
        const $row = $(row);
        let show = true;
        const showTypes = $row.data('field-type');
        const hideTypes = $row.data('field-type-hide');
        if (showTypes) {
          const showArr = showTypes.split(',').map((s) => s.trim());
          show = showArr.includes(fieldType);
        }
        if (hideTypes) {
          const hideArr = hideTypes.split(',').map((s) => s.trim());
          show = show && !hideArr.includes(fieldType);
        }
        $row.toggle(show);
        //console.log('Row toggle: row=', $row, 'show=', show, 'showTypes=', showTypes, 'hideTypes=', hideTypes);
      });
    }

    refreshOptionsState(fieldType) {
      this.showOptionsBlockByType(fieldType);
      this.optionsContainer.find('.data-option-toggle-trigger').each((_, el) => {
        $(el).trigger('change');
      });
      this.showOptionsBlockByType(fieldType);
    }

    /**
     * Recalculates column sizes and visibility for fields and their nested children.
     */
    recalcScreenFieldCols() {
      this.fieldsContainer.find('.ppsgen-popup-col').removeClass((_, className) => (className.match(/\bppsgen-col-[^ ]+-[^ ]+\b/g) || []).join(' '));
      if (this.screenRules) {
        this.fieldsContainer.find('.ppsgen-popup-col').addClass('ppsgen-col-hide');
      } else {
        this.fieldsContainer.find('.ppsgen-col-hide').removeClass('ppsgen-col-hide');
      }

      this.fieldsContainer.find('.ppsgen-popup-col').each((_, obj) => {
        const $field = $(obj);
        const key = $field.data('field-key');
        if (this.fieldOptionsMap[key]) {
          const colKey = `col_${this.screenActual}`;
          const showKey = `show_${this.screenActual}`;
          let colSize = parseInt(this.fieldOptionsMap[key][colKey]) || 12;
          const screenOption = this.fieldOptionsMap[key][showKey] || '1';

          if (isNaN(colSize) || colSize < 1 || colSize > 12) {
            colSize = 12;
            this.fieldOptionsMap[key][colKey] = colSize;
            this.showPopupNotice('error', 'edit', `Incorrect value for ${colKey}. Set value by default: 12.`);
          }

          if (this.screenRules && screenOption === '1') {
            $field.removeClass('ppsgen-col-hide');
          }
          const className = `ppsgen-col-${this.screenActual}-${colSize}`;
          $field.addClass(className);
          //console.log(`Updated field ${key}: ${colKey}=${colSize}, ${showKey}=${screenOption}, class=${className}`);
        }
      });
    }

    /**
     * Deletes a field from the form and its descendants.
     * @param {Event} e - The click event.
     */
    deleteField(e) {
      const $container = $(e.currentTarget).closest('.ppsgenCffOptionContainer');
      const key = $container.find('[name="field_key"]').val();
      //console.log('deleteField: key=', key);
      if (!key) {
        this.showPopupNotice('error', 'edit', 'No field key found for deletion');
        return;
      }
      if (!confirm(ppsgenAjax?.confirmDeleteText || 'Are you sure you want to delete this element?')) return;

      const $field = this.fieldsContainer.find(`.ppsgen-popup-col[data-field-key="${key}"]`);
      if (!$field.length) {
        this.showPopupNotice('error', 'edit', 'Field not found in DOM: ' + key);
        return;
      }

      const fType = $field.data('field-type') || '';
      //console.log('Deleting field: key=', key, 'type=', fType);

      // Collect all nested keys
      const nestedKeys = this.getAllNestedKeys(key);
      nestedKeys.forEach((nKey) => {
        const $nestedField = this.fieldsContainer.find(`.ppsgen-popup-col[data-field-key="${nKey}"]`);
        const nType = this.fieldOptionsMap[nKey]?.field_type || '';
        $('.ppsgenCfbAddOtherFieldList option').each((_, opt) => {
          const $opt = $(opt);
          // if (['submit', 'captcha', 'form'].includes(nType) && $opt.data('field-type') === nType) {
          //   $opt.prop('disabled', false).removeAttr('disabled');
          // }
        });
        $nestedField.remove();
        delete this.fieldOptionsMap[nKey];
      });

      // Re-enable option if it's a unique field
      $('.ppsgenCfbAddOtherFieldList option').each((_, opt) => {
        const $opt = $(opt);
        // if (['submit', 'captcha', 'form'].includes(fType) && $opt.data('field-type') === fType) {
        //   $opt.prop('disabled', false).removeAttr('disabled');
        // }
      });

      // Remove main field
      $field.remove();
      delete this.fieldOptionsMap[key];

      this.setOptionsToForm({});
      this.optionsContainer.hide();
      this.currentFieldKey = null;
      this.calculateFieldOrder();
      this.checkFieldListBtnToggle();
      //console.log('Field deleted: key=', key, 'nestedKeys=', nestedKeys, 'fieldOptionsMap=', this.fieldOptionsMap);
    }

    duplicateField(e) {
      e.preventDefault();
      const sourceKey = this.currentFieldKey || $(e.currentTarget).closest('.ppsgenCffOptionContainer').find('[name="field_key"]').val();
      if (!sourceKey || !this.fieldOptionsMap[sourceKey]) {
        this.showPopupNotice('error', 'edit', 'Select element for duplication first');
        return;
      }

      this.fieldOptionsMap[sourceKey] = this.getOptionsFromForm();

      const subtreeKeys = [sourceKey, ...this.getAllNestedKeys(sourceKey)];
      const keyMap = {};
      subtreeKeys.forEach((oldKey) => {
        keyMap[oldKey] = this.generateUniqueFieldKey();
      });

      subtreeKeys.forEach((oldKey) => {
        const clonedOptions = $.extend(true, {}, this.fieldOptionsMap[oldKey]);
        clonedOptions.field_key = keyMap[oldKey];
        clonedOptions.parent_key = clonedOptions.parent_key && keyMap[clonedOptions.parent_key] ? keyMap[clonedOptions.parent_key] : clonedOptions.parent_key || '';
        this.fieldOptionsMap[keyMap[oldKey]] = clonedOptions;
      });

      const $sourceField = this.fieldsContainer.find(`.ppsgen-popup-col[data-field-key="${sourceKey}"]`).first();
      if (!$sourceField.length) {
        this.showPopupNotice('error', 'edit', 'Selected element was not found');
        return;
      }

      const $cloneField = $sourceField.clone(false, false);
      $cloneField.find('.ppsgen-popup-col').addBack('.ppsgen-popup-col').each((_, el) => {
        const $el = $(el);
        const oldKey = $el.data('field-key');
        if (oldKey && keyMap[oldKey]) {
          $el.attr('data-field-key', keyMap[oldKey]);
          $el.data('field-key', keyMap[oldKey]);
          $el.removeClass('ppsgen-cf-col-active click-animate');
        }
      });

      $sourceField.after($cloneField);
      this.currentFieldKey = keyMap[sourceKey];
      this.activateField(this.currentFieldKey);
      this.setOptionsToForm(this.fieldOptionsMap[this.currentFieldKey]);
      this.refreshOptionsState(this.fieldOptionsMap[this.currentFieldKey].field_type || '');
      this.calculateFieldOrder();
      this.recalcScreenFieldCols();
      this.checkFieldListBtnToggle();
      jQuery(document).trigger('ppsgenFieldsChanged');
    }

    /**
     * Gets all nested keys recursively for a parent key.
     * @param {string} parentKey - The parent key.
     * @returns {Array} - Array of nested keys.
     */
    getAllNestedKeys(parentKey) {
      const nested = [];
      const collect = (key) => {
        Object.entries(this.fieldOptionsMap).forEach(([childKey, data]) => {
          if (data.parent_key === key) {
            nested.push(childKey);
            collect(childKey);
          }
        });
      };
      collect(parentKey);
      return nested;
    }

    /**
     * Calculates field order and parent_key for sorting.
     */
    calculateFieldOrder() {
      // Process top-level container
      this.calculateContainerOrder(this.fieldsContainer, '');

      // Process nested containers
      this.fieldsContainer.find('.ppsgen-popup-col-inner').each((_, inner) => {
        const $parent = $(inner).closest('.ppsgen-popup-col');
        const parentKey = $parent.data('field-key') || '';
        this.calculateContainerOrder($(inner), parentKey);
      });
    }

    /**
     * Calculates order for a specific container.
     * @param {jQuery} $container - The container to process.
     * @param {string} parentKey - The parent key of the container.
     */
    calculateContainerOrder($container, parentKey) {
      $container.find('.ppsgen-popup-col').each((index, obj) => {
        const $element = $(obj);
        const key = $element.data('field-key');
        if (!key || !this.fieldOptionsMap[key]) return;

        // Update order_index and parent_key
        this.fieldOptionsMap[key].order_index = index + 1;
        this.fieldOptionsMap[key].parent_key = parentKey;

        // Update form if this is the current field
        if (this.currentFieldKey === key) {
          $('[name="order_index"]').val(index + 1);
          $('[name="parent_key"]').val(parentKey);
        }

        //console.log('Updated field:', key, 'parent_key=', parentKey, 'order_index=', index + 1);
      });
    }

    /**
     * Retrieves options from the form.
     * @returns {Object} - The form options object.
     */
    getOptionsFromForm() {
      const data = {};
      this.optionsContainer.find(':input[name]').each((_, el) => {
        const $el = $(el);
        const name = $el.attr('name');
        if (typeof tinymce !== 'undefined' && tinymce.get(name)) {
          data[name] = tinymce.get(name).getContent();
        } else if ($el.is(':checkbox')) {
          data[name] = $el.is(':checked') ? '1' : '0';
        } else {
          data[name] = $el.val();
        }
      });
      if (data.field_key && this.currentFieldKey && data.field_key !== this.currentFieldKey) {
        console.warn(`Field key mismatch detected! Form field_key: ${data.field_key}, Expected: ${this.currentFieldKey}`);
        data.field_key = this.currentFieldKey;
      }
      data.options = this.getFieldOptionsFromForm();
      return data;
    }

    /**
     * Retrieves field options for select/checkbox/radio/social_links.
     * @returns {Object} - The field options object.
     */
    getFieldOptionsFromForm() {
      const options = {};
      $('.ppsgenComponentOptionsList .ppsgenComponentOptionInline, .ppsgenComponentSocialLinksList .ppsgenComponentOptionInline').each((_, opt) => {
        const $option = $(opt);
        let key = null;
        const optionData = {};
        $option.find(':input[name]').each((_, input) => {
          const $input = $(input);
          const name = $input.attr('name');
          const match = name.match(/options\[(.+?)\]\[(.+?)\]/);
          if (match) {
            key = match[1];
            const field = match[2];
            optionData[field] = $input.is(':checkbox') ? ($input.is(':checked') ? '1' : '0') : $input.val();
          }
        });
        if (key) options[key] = optionData;
      });
      return options;
    }

    /**
     * Sets options to the form.
     * @param {Object} data - The options data to set.
     */
    setOptionsToForm(data) {
      $('body')
        .find('.ppsgenPopupFieldsRight .wp-color-picker')
        .each(function () {
          const picker = $(this);
          picker.val('');
          picker.wpColorPicker('color', '');
          picker.trigger('change');
        });
      jQuery('.ppsgen-color-picker').val('').trigger('change');
      this.optionsContainer.find(':input[name]').each((_, el) => {
        const $el = $(el);
        const name = $el.attr('name');
        if (typeof data[name] !== 'undefined') {
          if (typeof tinymce !== 'undefined' && tinymce.get(name)) {
            tinymce.get(name).setContent('');
            setTimeout(() => tinymce.get(name).setContent(data[name]), 10);
          } else if ($el.is(':checkbox')) {
            $el.prop('checked', data[name] === '1' || data[name] === 1);
          } else {
            $el.val(data[name]);
            if ($el.hasClass('ppsgenColorInputText')) {
              const $picker = $el.closest('.ppsgen-color-picker').find('.wp-color-picker');
              if ($picker.length) {
                $picker.val(data[name]).wpColorPicker('color', data[name]);
              }
            }
          }
        } else {
          if (typeof tinymce !== 'undefined' && tinymce.get(name)) {
            tinymce.get(name).setContent('');
          } else if ($el.is(':checkbox')) {
            $el.prop('checked', false);
          } else {
            $el.val('');
          }
        }
      });
      if (data.options && typeof data.options === 'object') {
        // this.renderFieldOptionsToForm(data.options);
      } else {
      }
    }

    /**
     * Toggles visibility of the field list button.
     */
    checkFieldListBtnToggle() {
      let allOptionsDisabled = true;
      $('.ppsgenCfbAddOtherFieldList').each((_, list) => {
        if ($(list).find('option:not(:disabled)').length > 0) {
          allOptionsDisabled = false;
          return false;
        }
      });
      $('.ppsgenCfbAddOtherFieldBtn').toggle(!allOptionsDisabled);
    }

    /**
     * Sets the current screen size.
     * @param {string} screen - The screen size ('pc', 'tab', 'mob').
     */
    setScreen(screen) {
      $('.ppsgenCfbSizeChangerBtnActive').removeClass('ppsgenCfbSizeChangerBtnActive');
      $(`.ppsgenCfbSizeChangerBtn${screen.charAt(0).toUpperCase() + screen.slice(1)}`).addClass('ppsgenCfbSizeChangerBtnActive');
      this.screenActual = screen;
      this.recalcScreenFieldCols();
    }

    /**
     * Toggles visibility rules for fields.
     */
    toggleScreenRules() {
      $('.ppsgenCfbSizeChangerToggle').toggleClass('ppsgenCfbSizeChangerToggleActive').find('.dashicons').toggleClass('dashicons-hidden dashicons-visibility');
      this.screenRules = !this.screenRules;
      this.recalcScreenFieldCols();
    }
    /**
     * Handles form submission, including data serialization and AJAX request.
     * @param {Event} e - The submit event.
     * @returns {boolean} - Returns false to prevent default form submission.
     */
    handlePreview(e, live = false) {
      if (e) {
        e.preventDefault();
      }
      if (typeof tinyMCE !== 'undefined') {
        tinyMCE.triggerSave();
      }
      this.syncCustomCodeEditors();
      if (!this.beforePopupSubmit()) {
        return false;
      }

      if (!live) {
        jQuery('.ppsgenLoadPreview').attr('disabled', 'disabled').prop('disabled', true);

        jQuery(document).on('click', '#ppsgenPreviewClose', function () {
          jQuery('#ppsgenPreviewPopup').fadeOut(150);
        });
      }

      if (this.currentFieldKey) {
        this.fieldOptionsMap[this.currentFieldKey] = this.getOptionsFromForm();
      }

      let formDataArray = this.form.serializeArray();
      const normalData = {};
      const dataBlock = {};

      formDataArray.forEach((item) => {
        if (item.name.startsWith('data[')) {
          const name = item.name;
          const value = item.value;
          if (name.endsWith('[]')) {
            const cleanName = name.slice(0, -2);
            if (!dataBlock[cleanName]) {
              dataBlock[cleanName] = [];
            }
            if (value !== '') {
              dataBlock[cleanName].push(value);
            }
          } else {
            dataBlock[name] = value;
          }
        } else {
          const allowedKeys = ['plugin', 'mod', 'obj_id', 'action', 'nonce_code'];
          if (allowedKeys.includes(item.name)) {
            normalData[item.name] = item.value;
          }
        }
      });

      normalData['mod'] = window.ppsgenPopupController ?? 'popup';
      normalData['action'] = 'renderPreview';

      if (typeof this.fieldOptionsMap !== 'undefined') {
        Object.entries(this.fieldOptionsMap).forEach(([fieldKey, fieldData]) => {
          const serialized = this.serializeNested(fieldData, `data[popup][fields][${fieldKey}]`);
          const pairs = serialized.replace(/^&/, '').split('&');
          pairs.forEach((pair) => {
            if (!pair) return;
            let [name, value] = pair.split('=');
            value = decodeURIComponent(value || '');
            if (name.endsWith('[]')) {
              const cleanName = name.slice(0, -2);
              if (!dataBlock[cleanName]) dataBlock[cleanName] = [];
              if (value !== '') dataBlock[cleanName].push(value);
            } else {
              dataBlock[name] = value;
            }
          });
        });
      }

      if (typeof this.fieldOptionsMap !== 'undefined') {
        Object.entries(this.fieldOptionsMap).forEach(([fieldKey, fieldData]) => {
          const serialized = this.serializeNested(fieldData, `data[popup][fields][${fieldKey}]`);
          serialized
            .replace(/^&/, '')
            .split('&')
            .forEach((pair) => {
              const [name, value] = pair.split('=');
              if (!name.startsWith('data[')) return;
              dataBlock[name] = decodeURIComponent(value || '');
            });
        });
      }

      this.appendCustomCodeToDataBlock(dataBlock);
      const packedData = utf8ToBase64(JSON.stringify(dataBlock));
      const payload = window.ppsgenBuildPayloadData(
        {
          ...normalData,
          nonce_code: ppsgenAjax?.nonce || '',
        },
        packedData
      );

      const request = $.post(ajaxurl, payload, (response) => {
        if (response?.success) {
          const previewHtml = `
            <!doctype html>
            <html>
              <head>
                <meta charset="utf-8">
                ${response.data.styles.map((src) => `<link rel="stylesheet" href="${src}" defer>`).join('')}
                <style id="ppsgen-preview-style">
                  ${response.data.css || ''}
                </style>
              </head>
              <body>
                ${typeof window.ppsgenPreviewSiteMock === 'function' ? window.ppsgenPreviewSiteMock() : ''}
                ${response.data.preview}
                ${response.data.scripts.map((src) => `<script src="${src}" defer></script>`).join('')}
                ${response.data.inline_scripts}
              </body>
            </html>
          `;
          window.ppsgenShowPreview(previewHtml, live);
          jQuery('.ppsgenLoadPreview').removeAttr('disabled').prop('disabled', false);
        } else {
          jQuery('.ppsgenLoadPreview').removeAttr('disabled').prop('disabled', false);
        }
      }).fail(() => {
        jQuery('.ppsgenLoadPreview').removeAttr('disabled').prop('disabled', false);
      });
      return live ? request : false;
    }
  }

  $(document).ready(() => new ppsgenFormBuilder());
})(jQuery);

jQuery(function ($) {
  const animationsIn = [
    'fadeIn',
    'fadeInDown',
    'fadeInDownBig',
    'fadeInLeft',
    'fadeInLeftBig',
    'fadeInRight',
    'fadeInRightBig',
    'fadeInUp',
    'fadeInUpBig',
    'bounceIn',
    'bounceInDown',
    'bounceInLeft',
    'bounceInRight',
    'bounceInUp',
    'zoomIn',
    'zoomInDown',
    'zoomInLeft',
    'zoomInRight',
    'zoomInUp',
    'slideInDown',
    'slideInLeft',
    'slideInRight',
    'slideInUp',
    'flipInX',
    'flipInY',
    'lightSpeedIn',
    'rollIn',
    'rotateIn',
    'rotateInDownLeft',
    'rotateInDownRight',
    'rotateInUpLeft',
    'rotateInUpRight',
    'jackInTheBox',
  ];
  const animationsOut = [
    'fadeOut',
    'fadeOutDown',
    'fadeOutDownBig',
    'fadeOutLeft',
    'fadeOutLeftBig',
    'fadeOutRight',
    'fadeOutRightBig',
    'fadeOutUp',
    'fadeOutUpBig',
    'bounceOut',
    'bounceOutDown',
    'bounceOutLeft',
    'bounceOutRight',
    'bounceOutUp',
    'zoomOut',
    'zoomOutDown',
    'zoomOutLeft',
    'zoomOutRight',
    'zoomOutUp',
    'slideOutDown',
    'slideOutLeft',
    'slideOutRight',
    'slideOutUp',
    'flipOutX',
    'flipOutY',
    'lightSpeedOut',
    'rollOut',
    'rotateOut',
    'rotateOutDownLeft',
    'rotateOutDownRight',
    'rotateOutUpLeft',
    'rotateOutUpRight',
    'hinge',
  ];
  let currentTargetInput = null;
  function openAnimOverlay(target, animType) {
    currentTargetInput = $(`.${target}`);
    const grid = $('#ppsgenAnimOverlay .ppsgenAnimGrid');
    grid.empty();
    const animations = animType === 'out' ? animationsOut : animationsIn;
    animations.forEach((anim) => {
      const square = $('<div class="ppsgenAnimSquare"></div>').text(anim).addClass('ppsgenAnimated').attr('data-anim', anim);
      square.on('click', function () {
        const el = $(this);
        const anim = el.data('anim');
        el.removeClass('ppsgenAnimated ' + anim);
        void el[0].offsetWidth;
        setTimeout(() => {
          el.addClass('ppsgenAnimated ' + anim);
          el.one('animationend', () => el.removeClass('ppsgenAnimated ' + anim));
        }, 300);
      });
      square.on('dblclick', function () {
        if (currentTargetInput) {
          const animClass = $(this).data('anim');
          const cleanClass = animClass;
          currentTargetInput.val(cleanClass);
        }
        closeAnimOverlay();
      });
      grid.append(square);
    });
    $('#ppsgenAnimOverlay').fadeIn(200);
  }
  function closeAnimOverlay() {
    $('#ppsgenAnimOverlay').fadeOut(200);
    currentTargetInput = null;
  }
  $('.ppsgenShowAnimationBtn').on('click', function () {
    const target = $(this).data('target');
    const animType = $(this).data('anim-type') || 'in';
    openAnimOverlay(target, animType);
  });
  $('#ppsgenAnimOverlay .ppsgenAnimClose, #ppsgenAnimOverlay .ppsgenAnimOverlayBg').on('click', function () {
    closeAnimOverlay();
  });
});

jQuery(document).ready(function () {
  jQuery('select[name^="data[popup][settings][pro_"]').each(function () {
    jQuery(this).prop('disabled', true).attr('disabled', 'disabled');
  });
  jQuery('select[name="data[popup][settings][close_on]"] option').each(function () {
    const val = jQuery(this).val();
    if (val && val.startsWith('pro_')) {
      jQuery(this).prop('disabled', true);
    }
  });
  jQuery('select[name="data[popup][settings][close_on]"]').trigger('change');
  jQuery('select[name="data[popup][settings][show_on]"] option').each(function () {
    const val = jQuery(this).val();
    if (val && val.startsWith('pro_')) {
      jQuery(this).prop('disabled', true);
    }
  });
  jQuery('select[name="data[popup][settings][show_on]"]').trigger('change');
});
