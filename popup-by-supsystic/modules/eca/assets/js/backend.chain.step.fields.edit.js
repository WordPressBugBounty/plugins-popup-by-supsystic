function utf8ToBase64(str) {
  return btoa(unescape(encodeURIComponent(str)));
}

function base64ToUtf8(str) {
  return decodeURIComponent(escape(atob(str)));
}

window.ppsgenChainSelectedStepId = '';
window.ppsgenEcaController = 'eca';

(function ($) {
  /**
   * Form builder class for managing chain fields with flat structure and parent_key.
   * @class
   */
  class ppsgenChainStepFieldsBuilder {
    /**
     * Initializes the form builder with DOM elements and state.
     * @constructor
     */
    constructor(fieldOptionsMap = null, fieldContainerHtml = null) {
      this.form = $('#ppsgenEditObjFormChain');
      this.fieldsContainer = $('.ppsgenCfbFieldsWrapper');
      this.optionsContainer = $('.ppsgenChainFieldsRight');
      this.currentFieldKey = null;
      this.fieldOptionsMap = fieldOptionsMap;
      this.fieldContainerHtml = fieldContainerHtml;
      this.predefinedOptions = {};
      this.parentTypes = ['row', 'column'];
      this.init();
    }

    destroy() {
      if (this.form) {
        this.form.off('submit');
      }
      jQuery('body').off('click', '.ppsgenLoadChainPreview');
      jQuery('body').off('click', '.ppsgenCfbAddOtherFieldBtn');
      jQuery('body').off('click', '.ppsgen-chain-col');
      jQuery('body').off('click', '.ppsgenCffDuplicateBtn');
      jQuery('body').off('click', '.ppsgenCffDeleteBtn');
      jQuery('body').off('click', '.ppsgen-media-upload-btn');
      jQuery('body').off('input change', '.ppsgenEcaImageSrcInput');
      jQuery('body').off('click', '.ppsgenComponentListTabBtn');
      jQuery('body').off('change', '[name^="display_type"],[name^="width"],[name^="vertical_align"],[name^="horizontal_align"]');
      jQuery('.ppsgenObjEditSaveBtn').off('click');
      jQuery('.ppsgen-color-picker .ppsgenColorInputText').off('input change');
      if (this.fieldsContainer) {
        this.fieldsContainer.empty();
      }
      this.fieldOptionsMap = null;
      this.fieldContainerHtml = null;
      this.currentFieldKey = null;
      this.fieldOptionsMap = null;
      this.predefinedOptions = {};
      this.form = null;
      this.fieldsContainer = null;
      this.optionsContainer = null;
    }

    /**
     * Initializes event bindings and sortable functionality.
     */
    init() {
      this.setupContainerHtml();
      this.bindEvents();
      this.initSortable();
      this.checkFieldListBtnToggle();
      if (window.ppsgenLivePreview) {
        window.ppsgenLivePreview.attach(this, { left: '.ppsgenChainFieldsLeft', right: '.ppsgenChainFieldsRight', builderTab: null, widths: { pc: 760, tablet: 640, mobile: 390 }, fullButton: '.ppsgenLoadChainPreview' });
      }
    }

    /**
     * Binds events for form interactions.
     */
    bindEvents() {
      $('body').on('click', '.ppsgenLoadChainPreview', (e) => this.handlePreview(e));
      $('body').on('click', '.ppsgenCfbAddOtherFieldBtn', (e) => this.addField(e));
      $('body').on('click', '.ppsgen-chain-col', (e) => this.selectField(e));
      $('body').on('click', '.ppsgenCffDuplicateBtn', (e) => this.duplicateField(e));
      $('body').on('click', '.ppsgenCffDeleteBtn', (e) => this.deleteField(e));
      $('body').on('click', '.ppsgen-media-upload-btn', (e) => this.openMediaUploader(e));
      $('body').on('input change', '.ppsgenEcaImageSrcInput', (e) => this.updateEcaImageSrcPreview($(e.currentTarget).closest('.ppsgenEcaImageSrcPicker')));
      $('body').on('change', '[name^="display_type"],[name^="width"],[name^="vertical_align"],[name^="horizontal_align"]', () => {
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

      $('body')
        .find('.ppsgenChainFieldsRight .wp-color-picker')
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

    getSelectedStepId() {
      return window.ppsgenChainSelectedStepId;
    }

    setSelectedStepId(step_id) {
      window.ppsgenChainSelectedStepId = step_id;
    }

    setupContainerHtml() {
      jQuery('.ppsgenCfbFieldsWrapper').html(this.fieldContainerHtml);
    }

    initSortableForContainer(container) {
      var self = this;

      new Sortable(container[0], {
        animation: 150,
        ghostClass: 'ppsgen-chain-ghost',
        chosenClass: 'ppsgen-chain-chosen',
        dragClass: 'ppsgen-chain-drag',
        forceFallback: true,
        fallbackTolerance: 3,
        swapThreshold: 0.65,
        group: 'nested',
        onMove: function (evt) {
          var dragged = $(evt.dragged);
          var target = $(evt.to);
          var $parent = $(target).parent();
          if (dragged.hasClass('ppsgen-chain-type-row')) {
            if (!target.is(self.fieldsContainer[0]) && !$parent.hasClass('ppsgen-chain-type-column')) {
              return false;
            }
          } else if (dragged.hasClass('ppsgen-chain-type-column')) {
            if (!$parent.hasClass('ppsgen-chain-type-row')) {
              return false;
            }
          } else {
            if (!$parent.hasClass('ppsgen-chain-type-column')) {
              return false;
            }
          }
          return true;
        },
        onEnd: function (evt) {
          self.calculateFieldOrder();
        },
      });
    }

    initSortable() {
      var self = this;
      this.initSortableForContainer(this.fieldsContainer);
      this.fieldsContainer.find('.ppsgen-chain-col-inner').each(function (_, inner) {
        var $inner = $(inner);
        if ($inner.parent().hasClass('ppsgen-chain-type-row')) {
          self.initSortableForContainer($inner);
        } else if ($inner.parent().hasClass('ppsgen-chain-type-column')) {
          self.initSortableForContainer($inner);
        }
      });
    }

    /**
     * Handles form submission, including data serialization and AJAX request.
     * @param {Event} e - The submit event.
     * @returns {boolean} - Returns false to prevent default form submission.
     */
    handleSubmit(e) {
      // e.preventDefault();

      $('.ppsgenObjEditSaveBtn, .ppsgenChainStepBtnSave').attr('disabled', 'disabled').prop('disabled', true).find('.dashicons').toggleClass('dashicons-yes-alt dashicons-clock');

      if (typeof tinyMCE !== 'undefined') {
        tinyMCE.triggerSave();
      }

      if (this.currentFieldKey) {
        this.fieldOptionsMap[this.currentFieldKey] = this.getOptionsFromForm();
      }

      let formDataArray = this.form.serializeArray();
      const dataBlock = {};

      if (typeof this.fieldOptionsMap !== 'undefined') {
        Object.entries(this.fieldOptionsMap).forEach(([fieldKey, fieldData]) => {
          const serialized = this.serializeNested(fieldData, `data[chain][step_fields][${fieldKey}]`);
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
          const serialized = this.serializeNested(fieldData, `data[chain][step_fields][${fieldKey}]`);
          serialized
            .replace(/^&/, '')
            .split('&')
            .forEach((pair) => {
              const [name, value] = pair.split('=');
              if (!name.startsWith('data[chain][step_fields]')) return;
              dataBlock[name] = decodeURIComponent(value || '');
            });
        });
      }

      const packedData = utf8ToBase64(JSON.stringify(dataBlock));
      const payload = window.ppsgenBuildPayloadData(
        {
          plugin: 'ppsgen',
          mod: window.ppsgenEcaController,
          action: 'saveChainStepFields',
          step_id: this.getSelectedStepId(),
          nonce_code: ppsgenAjax?.nonce || '',
        },
        packedData
      );
      $.post(ajaxurl, payload, (response) => {
        if (response?.success) {
        const msg = response.data?.message || 'Campaign Step Template is successfully saved';
          this.showChainNotice('success', 'edit', msg);
          return true;
        } else {
        const msg = response.data?.message || 'Failed to save Campaign Step Template';
          this.showChainNotice('error', 'edit', msg);
          return true;
        }
      }).fail(() => {
      this.showChainNotice('error', 'edit', 'There was an error saving the Campaign Step Template');
        return true;
      });
      return true;
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

      if (!live) {
        jQuery('.ppsgenLoadChainPreview').attr('disabled', 'disabled').prop('disabled', true);

        jQuery(document).on('click', '#ppsgenPreviewClose', function () {
          jQuery('#ppsgenPreviewPopup').fadeOut(150);
        });
      }

      if (this.currentFieldKey) {
        this.fieldOptionsMap[this.currentFieldKey] = this.getOptionsFromForm();
      }

      let formDataArray = this.form.serializeArray();
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
        }
      });

      if (typeof this.fieldOptionsMap !== 'undefined') {
        Object.entries(this.fieldOptionsMap).forEach(([fieldKey, fieldData]) => {
          const serialized = this.serializeNested(fieldData, `data[fields][${fieldKey}]`);
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

      const packedData = utf8ToBase64(JSON.stringify(dataBlock));
      const payload = window.ppsgenBuildPayloadData(
        {
          plugin: 'ppsgen',
          mod: window.ppsgenEcaController,
          action: 'renderChainPreview',
          template_id: jQuery('[name="data[chain][options][template_id]"]').val(),
          nonce_code: ppsgenAjax?.nonce || '',
        },
        packedData
      );
      const request = $.post(ajaxurl, payload, (response) => {
        if (response?.success) {
          window.ppsgenShowPreview(response.data.preview, live);
          jQuery('.ppsgenLoadChainPreview').removeAttr('disabled').prop('disabled', false);
        } else {
          jQuery('.ppsgenLoadChainPreview').removeAttr('disabled').prop('disabled', false);
        }
      }).fail(() => {
        jQuery('.ppsgenLoadChainPreview').removeAttr('disabled').prop('disabled', false);
      });

      return live ? request : false;
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
     * Displays a chain notice or fallback alert with an escaped message.
     * @param {string} status - The status of the notice ('success' or 'error').
     * @param {string} operation - The operation type ('edit').
     * @param {string} msg - The message to display.
     */
    showChainNotice(status, operation, msg) {
      const escapedMsg = $('<div>').text(msg).html();
      if (typeof jQuery().ppsgenShowPopupNotice === 'function') {
        jQuery().ppsgenShowPopupNotice({ status, operation, msg: escapedMsg });
      } else {
        console.warn('ppsgenShowPopupNotice is not defined. Displaying fallback alert.');
        alert(`${status.toUpperCase()}: ${escapedMsg}`);
      }
    }

    normalizeMediaPath(url) {
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

    getPreviewUrl(path) {
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

    updateEcaImageSrcPreview($picker) {
      if (!$picker || !$picker.length) {
        return;
      }
      const value = ($picker.find('.ppsgenEcaImageSrcInput').val() || '').trim();
      const $preview = $picker.find('.ppsgenEcaImageSrcPreview');
      if (!value) {
        $preview.attr('src', '').hide();
        $picker.removeClass('ppsgenEcaImageSrcPickerHasPreview');
        return;
      }
      $preview.attr('src', this.getPreviewUrl(value)).show();
      $picker.addClass('ppsgenEcaImageSrcPickerHasPreview');
    }

    /**
     * Opens media uploader for image selection.
     * @param {Event} e - The click event.
     */
    openMediaUploader(e) {
      e.preventDefault();
      const $button = $(e.currentTarget);
      const $picker = $button.closest('.ppsgenEcaImageSrcPicker');
      const $input = $picker.length ? $picker.find('input.ppsgen-media-upload') : $button.prev('input.ppsgen-media-upload');
      const frame = wp.media({
        title: 'Select Image',
        button: { text: 'Use Image' },
        multiple: false,
      });
      frame.on('select', () => {
        const attachment = frame.state().get('selection').first().toJSON();
        $input.val(this.normalizeMediaPath(attachment.url || '')).trigger('input').trigger('change');
        this.updateEcaImageSrcPreview($input.closest('.ppsgenEcaImageSrcPicker'));
      });
      frame.open();
    }

    /**
     * Adds a new field to the chain.
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
      const fieldHtml = this.createFieldHtml(key, fType, fieldHtmlAttr);
      const predefinedOptions = this.createPredefinedOptions(key, fType);
      // New titles and texts start with placeholder text, so it is clear they were added
      if (window.ppsgenApplyDefaultText) {
        window.ppsgenApplyDefaultText(predefinedOptions, fType);
      }
      if (this.currentFieldKey) {
        this.fieldOptionsMap[this.currentFieldKey] = this.getOptionsFromForm();
      }
      if (this.addFieldToContainer(fieldHtml, key, predefinedOptions, fType)) {
        this.fieldOptionsMap[key] = predefinedOptions;
        this.setOptionsToForm(this.fieldOptionsMap[key]);
        this.showOptionsBlockByType(fType);
        this.activateField(key);
        $btn.show();
        this.currentFieldKey = key;
        this.calculateFieldOrder();
        this.recalcScreenFieldCols();
        this.checkFieldListBtnToggle();
        // this.disableUniqueFields();
      } else {
        this.checkFieldListBtnToggle();
      }
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
     * Creates HTML for a new field with escaped content.
     * @param {string} key - The unique field key.
     * @param {string} fType - The field type.
     * @param {string} fieldHtmlAttr - The HTML attributes for the field.
     * @returns {string} - The generated HTML string.
     */
    createFieldHtml(key, fType, fieldHtmlAttr) {
      const escapedContent = $('<div>').text(fType).html();
      let fieldHtml = `<div ${fieldHtmlAttr} class="ppsgen-chain-col ppsgen-chain-type-${this.sanitizeAttr(fType)}">`;
      fieldHtml += `<div class="ppsgen-chain-label">${fType !== 'empty' ? escapedContent : '-'}</div>`;
      if (this.parentTypes.includes(fType)) {
        fieldHtml += '<div class="ppsgen-chain-col-inner"></div>';
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
    createPredefinedOptions(key, fType) {
      let defList = {
        field_key: key,
        field_type: fType,
        parent_key: '', // Initialize as empty, set in addFieldToContainer or calculateFieldOrder
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
        font_size_unit: 'px',
        font_family: 'Default',
        font_lh_unit: 'px',
        border: '0',
        border_unit: 'px',
        border_style: 'solid',
        border_radius: '0',
        border_radius_unit: 'px',
        button_text: '',
        button_target: '',
        button_url: '',
        display_type: 'block',
        title_tag: 'h1',
        link_text_decoration: 'Default',
        vertical_align: '',
        horizontal_align: '',
        unsubscribe_text: 'Unsubscribe',
        image_target: '',
        image_title: '',
        image_url: '',
        image_src: '',
        svg_icon: '',
        svg_icon_color: '',
        posts_cats: '',
        posts_num: '3',
        posts_row_num: '3',
        posts_order: 'date',
        posts_order_by: 'desc',
        posts_show_title: '1',
        posts_show_thumbnail: '1',
        posts_show_desc: '1',
        posts_show_author: '0',
        posts_show_cat: '0',
        posts_show_date: '1',
        posts_show_more: '1',
        posts_show_more_text: 'Read More',
        posts_cats_child: 1,
        posts_exclude_pids: '',
        posts_exclude_cids: '',
        posts_show_tags: '',
        posts_show_desc_max: '',
        woo_categories_include_ids: '',
        woo_categories_exclude_ids: '',
        woo_categories_include_children: '0',
        woo_categories_num: '6',
        woo_categories_row_num: '3',
        woo_categories_order: 'name',
        woo_categories_order_by: 'asc',
        woo_categories_hide_empty: '0',
        woo_categories_show_thumbnail: '1',
        woo_categories_show_title: '1',
        woo_categories_show_title_limit: '60',
        woo_categories_show_hierarchy: '0',
        woo_categories_show_desc: '0',
        woo_categories_show_desc_limit: '140',
        woo_categories_show_count: '1',
        woo_categories_show_button: '1',
        woo_categories_button_text: 'View Category',
        woo_categories_button_target: '',
        woo_tags_include_ids: '',
        woo_tags_exclude_ids: '',
        woo_tags_hide_empty: '0',
        woo_tags_num: '6',
        woo_tags_row_num: '3',
        woo_tags_order: 'name',
        woo_tags_order_by: 'asc',
        woo_tags_show_title: '1',
        woo_tags_show_title_limit: '60',
        woo_tags_show_desc: '0',
        woo_tags_show_desc_limit: '140',
        woo_tags_show_count: '1',
        woo_tags_show_button: '1',
        woo_tags_button_text: 'View Tag',
        woo_tags_button_target: '',
        woo_attributes_include_ids: '',
        woo_attributes_exclude_ids: '',
        woo_attributes_attribute_parent_id: '',
        woo_attributes_include_children: '0',
        woo_attributes_num: '6',
        woo_attributes_row_num: '3',
        woo_attributes_order: 'name',
        woo_attributes_order_by: 'asc',
        woo_attributes_show_title: '1',
        woo_attributes_show_title_limit: '60',
        woo_attributes_show_hierarchy: '0',
        woo_attributes_show_desc: '0',
        woo_attributes_show_desc_limit: '140',
        woo_attributes_show_count: '1',
        woo_attributes_show_button: '1',
        woo_attributes_button_text: 'View Attribute',
        woo_attributes_button_target: '',
        woo_products_include_cats: '',
        woo_products_exclude_cats: '',
        woo_products_include_tags: '',
        woo_products_exclude_tags: '',
        woo_products_include_attributes: '',
        woo_products_exclude_attributes: '',
        woo_products_attribute_parent_id: '',
        woo_products_filter_by: 'categories',
        woo_products_include_children: '0',
        woo_products_exclude_pids: '',
        woo_products_num: '6',
        woo_products_row_num: '3',
        woo_products_order: 'date',
        woo_products_order_by: 'desc',
        woo_products_show_thumbnail: '1',
        woo_products_show_title: '1',
        woo_products_show_title_limit: '60',
        woo_products_show_desc: '0',
        woo_products_show_desc_limit: '140',
        woo_products_show_category: '1',
        woo_products_show_category_hierarchy: '0',
        woo_products_show_price: '1',
        woo_products_show_button: '1',
        woo_products_button_text: 'View Product',
        woo_products_button_target: '',
      };
      switch (fType) {
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
      var self = this;
      var $targetContainer = this.fieldsContainer;
      var $activeField = null;
      if (this.currentFieldKey) {
        $activeField = this.fieldsContainer.find('.ppsgen-chain-col[data-field-key="' + this.currentFieldKey + '"]');
      }
      if (fType === 'row') {
        if ($activeField && $activeField.data('field-type') === 'column') {
          $targetContainer = $activeField.find('.ppsgen-chain-col-inner').first();
          predefinedOptions.parent_key = this.currentFieldKey;
        } else {
          $targetContainer = this.fieldsContainer;
        }
      } else if (fType === 'column') {
        if ($activeField && $activeField.data('field-type') === 'row') {
          $targetContainer = $activeField.find('.ppsgen-chain-col-inner').first();
          predefinedOptions.parent_key = this.currentFieldKey;
        } else if ($activeField) {
          var $parentRow = $activeField.closest('.ppsgen-chain-type-row');
          if ($parentRow.length) {
            $targetContainer = $parentRow.find('.ppsgen-chain-col-inner').first();
            predefinedOptions.parent_key = $parentRow.data('field-key');
          } else {
            this.showChainNotice('error', 'edit', 'Unable to add column: no parent row.');
            return false;
          }
        } else {
          var $firstRow = this.fieldsContainer.find('.ppsgen-chain-type-row .ppsgen-chain-col-inner').first();
          if ($firstRow.length) {
            $targetContainer = $firstRow;
            predefinedOptions.parent_key = $firstRow.closest('.ppsgen-chain-col').data('field-key');
          } else {
            this.showChainNotice('error', 'edit', 'Unable to add column: no row to place.');
            return false;
          }
        }
      } else {
        if ($activeField && $activeField.data('field-type') === 'column') {
          $targetContainer = $activeField.find('.ppsgen-chain-col-inner').first();
          predefinedOptions.parent_key = this.currentFieldKey;
        } else if ($activeField) {
          var $parentColumn = $activeField.closest('.ppsgen-chain-type-column');
          if ($parentColumn.length) {
            $targetContainer = $parentColumn.find('.ppsgen-chain-col-inner').first();
            predefinedOptions.parent_key = $parentColumn.data('field-key');
          } else {
            this.showChainNotice('error', 'edit', 'Unable to add field: no parent column.');
            return false;
          }
        } else {
          var $firstColumn = this.fieldsContainer.find('.ppsgen-chain-type-column .ppsgen-chain-col-inner').first();
          if ($firstColumn.length) {
            $targetContainer = $firstColumn;
            predefinedOptions.parent_key = $firstColumn.closest('.ppsgen-chain-col').data('field-key');
          } else {
            this.showChainNotice('error', 'edit', 'Unable to add field: there is no column to place it in.');
            return false;
          }
        }
      }
      $targetContainer.append(fieldHtml);
      if (this.parentTypes.includes(fType)) {
        var $inner = this.fieldsContainer.find('.ppsgen-chain-col[data-field-key="' + key + '"] .ppsgen-chain-col-inner').first();
        this.initSortableForContainer($inner);
      }
      if (predefinedOptions) {
        this.predefinedOptions[key] = predefinedOptions;
        this.fieldOptionsMap[key] = $.extend(true, {}, predefinedOptions);
      } else if (!this.fieldOptionsMap[key]) {
        this.fieldOptionsMap[key] = {};
      }
      this.recalcScreenFieldCols();
      return true;
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
        this.showChainNotice('error', 'edit', 'Invalid field key or type');
        return;
      }
      if (this.currentFieldKey) {
        this.fieldOptionsMap[this.currentFieldKey] = this.getOptionsFromForm();
        //console.log('Saved options for previous field:', this.currentFieldKey, this.fieldOptionsMap[this.currentFieldKey]);
      }
      this.activateField(key);
      if (this.fieldOptionsMap[key]) {
        this.setOptionsToForm(this.fieldOptionsMap[key]);
        this.showOptionsBlockByType(fieldType);
        this.currentFieldKey = key;
        //console.log('Options set for field:', key, this.fieldOptionsMap[key]);
      } else {
        this.showChainNotice('error', 'edit', 'No options found for field key: ' + key);
      }
    }

    /**
     * Activates a field with animation.
     * @param {string} key - The unique field key.
     */
    activateField(key) {
      const $field = this.fieldsContainer.find(`.ppsgen-chain-col[data-field-key="${key}"]`);
      $('.ppsgen-chain-col-active').removeClass('ppsgen-chain-col-active');
      $field.addClass('click-animate ppsgen-chain-col-active');
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

    /**
     * Recalculates column sizes and visibility for fields and their nested children.
     */
    recalcScreenFieldCols() {
      var self = this;
      this.fieldsContainer.find('.ppsgen-chain-col').css('width', '').css('min-width', '');
      // console.log(self.fieldOptionsMap);
      this.fieldsContainer.find('.ppsgen-chain-col').each(function (_, obj) {
        var $field = $(obj);
        var key = $field.data('field-key');
        if (self.fieldOptionsMap[key]) {
          var fieldOpts = self.fieldOptionsMap[key];
          if (fieldOpts.width_select === 'auto') {
            if ($field.data('field-type') !== 'row' && $field.data('field-type') !== 'column') {
              if (fieldOpts.display_type == 'block') {
                $field.css('width', '100%');
              } else {
                $field.css('width', 'auto');
              }
            } else {
              $field.css('width', 'auto');
            }
          } else if (fieldOpts.width_select === 'manual' && fieldOpts.width_unit === 'percent') {
            var widthVal = parseFloat(fieldOpts.width);
            if (isNaN(widthVal) || widthVal < 0 || widthVal > 100) {
              widthVal = 100;
            }
            $field.css('width', widthVal + '%');
          }
          if ($field.data('field-type') !== 'row' && $field.data('field-type') !== 'column' && fieldOpts.display_type) {
            var display_type = fieldOpts.display_type.toLowerCase();
            $field.css('display', display_type);
          }
          if ($field.data('field-type') === 'column' && fieldOpts.vertical_align) {
            // console.log(fieldOpts.vertical_align);
            var va = fieldOpts.vertical_align.toLowerCase();
            var mapVA = {
              top: 'flex-start',
              middle: 'center',
              bottom: 'flex-end',
            };
            var flexVA = mapVA[va] || mapVA['top'];
            $field.find('.ppsgen-chain-col-inner').css('align-items', flexVA);
          }
          if ($field.data('field-type') === 'column' && fieldOpts.horizontal_align) {
            var ha = fieldOpts.horizontal_align.toLowerCase();
            var mapHA = {
              left: 'flex-start',
              center: 'center',
              right: 'flex-end',
            };
            var flexHA = mapHA[ha] || mapHA['left'];
            $field.find('.ppsgen-chain-col-inner').css('justify-content', flexHA);
          }
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
        this.showChainNotice('error', 'edit', 'No field key found for deletion');
        return;
      }
      if (!confirm(ppsgenAjax?.confirmDeleteText || 'Are you sure you want to delete this element?')) return;

      const $field = this.fieldsContainer.find(`.ppsgen-chain-col[data-field-key="${key}"]`);
      if (!$field.length) {
        this.showChainNotice('error', 'edit', 'Field not found in DOM: ' + key);
        return;
      }

      const fType = $field.data('field-type') || '';
      //console.log('Deleting field: key=', key, 'type=', fType);

      // Collect all nested keys
      const nestedKeys = this.getAllNestedKeys(key);
      nestedKeys.forEach((nKey) => {
        const $nestedField = this.fieldsContainer.find(`.ppsgen-chain-col[data-field-key="${nKey}"]`);
        const nType = this.fieldOptionsMap[nKey]?.field_type || '';
        // $('.ppsgenCfbAddOtherFieldList option').each((_, opt) => {
        //   const $opt = $(opt);
        //   // if (['submit', 'captcha', 'form'].includes(nType) && $opt.data('field-type') === nType) {
        //   //   $opt.prop('disabled', false).removeAttr('disabled');
        //   // }
        // });
        $nestedField.remove();
        delete this.fieldOptionsMap[nKey];
      });

      // Re-enable option if it's a unique field
      // $('.ppsgenCfbAddOtherFieldList option').each((_, opt) => {
      //   const $opt = $(opt);
      //   // if (['submit', 'captcha', 'form'].includes(fType) && $opt.data('field-type') === fType) {
      //   //   $opt.prop('disabled', false).removeAttr('disabled');
      //   // }
      // });

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

    /**
     * Duplicates the selected field together with all nested descendants.
     * @param {Event} e - The click event.
     */
    duplicateField(e) {
      const $container = $(e.currentTarget).closest('.ppsgenCffOptionContainer');
      const sourceKey = $container.find('[name="field_key"]').val();
      if (!sourceKey) {
        this.showChainNotice('error', 'edit', 'No field key found for duplication');
        return;
      }

      const $sourceField = this.fieldsContainer.find(`.ppsgen-chain-col[data-field-key="${sourceKey}"]`).first();
      if (!$sourceField.length) {
        this.showChainNotice('error', 'edit', 'Field not found in DOM: ' + sourceKey);
        return;
      }

      if (this.currentFieldKey && this.fieldOptionsMap[this.currentFieldKey]) {
        this.fieldOptionsMap[this.currentFieldKey] = this.getOptionsFromForm();
      }

      const subtreeKeys = [sourceKey].concat(this.getAllNestedKeys(sourceKey));
      const keyMap = {};

      subtreeKeys.forEach((oldKey) => {
        keyMap[oldKey] = this.generateUniqueFieldKey();
      });

      subtreeKeys.forEach((oldKey) => {
        const fieldData = $.extend(true, {}, this.fieldOptionsMap[oldKey] || {});
        const newKey = keyMap[oldKey];
        const oldParentKey = fieldData.parent_key || '';

        fieldData.field_key = newKey;
        fieldData.parent_key = keyMap[oldParentKey] || oldParentKey;

        this.fieldOptionsMap[newKey] = fieldData;
      });

      const $clonedField = $sourceField.clone(false, false);
      $clonedField
        .find('.ppsgen-chain-col')
        .addBack('.ppsgen-chain-col')
        .each((_, element) => {
          const $element = $(element);
          const oldKey = $element.attr('data-field-key');
          const newKey = keyMap[oldKey];

          if (!newKey) {
            return;
          }

          $element.attr('data-field-key', newKey);
          $element.data('field-key', newKey);
        });

      $sourceField.after($clonedField);

      this.calculateFieldOrder();
      this.activateField(keyMap[sourceKey]);
      this.currentFieldKey = keyMap[sourceKey];
      this.setOptionsToForm(this.fieldOptionsMap[keyMap[sourceKey]] || {});
      this.optionsContainer.show();
      this.recalcScreenFieldCols();
      this.checkFieldListBtnToggle();
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
      this.fieldsContainer.find('.ppsgen-chain-col-inner').each((_, inner) => {
        const $parent = $(inner).closest('.ppsgen-chain-col');
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
      $container.find('.ppsgen-chain-col').each((index, obj) => {
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
        .find('.ppsgenChainFieldsRight .wp-color-picker')
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
      this.updateEcaImageSrcPreview(this.optionsContainer.find('.ppsgenEcaImageSrcPicker'));
      setTimeout(() => {
        jQuery('.ppsgenCffOptionContainer select').trigger('change');
      }, 200);
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
  }

  window.ppsgenChainStepFieldsBuilder = ppsgenChainStepFieldsBuilder;
})(jQuery);
