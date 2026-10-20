function utf8ToBase64(str) {
  return btoa(unescape(encodeURIComponent(str)));
}

function base64ToUtf8(str) {
  return decodeURIComponent(escape(atob(str)));
}

(function ($) {
  /**
   * Form builder class for managing fields and options in a WordPress admin panel.
   * @class
   */
  class ppsgenFormBuilder {
    /**
     * Initializes the form builder with DOM elements and state.
     * @constructor
     */
    constructor() {
      this.form = $('#ppsgenEditObjForm');
      this.fieldsContainer = $('.ppsgenCfbFieldsWrapper');
      this.optionsContainer = $('.ppsgenFormFieldsRight');
      this.currentFieldKey = null;
      this.isLoadingFieldOptions = false;
      this.fieldOptionsMap = typeof ppsgenFormData !== 'undefined' && ppsgenFormData.fields ? ppsgenFormData.fields : {};
      this.predefinedOptions = {};
      this.screenActual = 'pc';
      this.screenRules = false;
      this.init();
    }

    /**
     * Initializes event bindings and sortable functionality.
     */
    init() {
      this.bindEvents();
      this.initSortable();
      this.checkContactFieldListBtnToggle();
      if (window.ppsgenLivePreview) {
        window.ppsgenLivePreview.attach(this, { left: '.ppsgenFormFieldsLeft', right: '.ppsgenFormFieldsRight', builderTab: 'fields', sideTabs: ['design', 'options', 'custom_css', 'conditional_logic'], widths: { pc: 1000, tablet: 820, mobile: 390 }, fullButton: '.ppsgenLoadPreview' });
      }
    }

    /**
     * Binds events for form interactions.
     */
    bindEvents() {
      $('.ppsgenObjEditSaveBtn').on('click', () => this.handleSave());
      $('body').on('click', '.ppsgenLoadPreview', (e) => this.handlePreview(e));
      this.form.on('submit', (e) => this.handleSubmit(e));
      $('body').on('click', '.ppsgenCfbAddContactFieldBtn, .ppsgenCfbAddOtherFieldBtn', (e) => this.addField(e));
      $('body').on('click', '.ppsgen-cf-col', (e) => this.selectField(e));
      $('body').on('click', '.ppsgenCffDuplicateBtn', (e) => this.duplicateField(e));
      $('body').on('click', '.ppsgenCffDeleteBtn', (e) => this.deleteField(e));
      $('body').on('change', '[name^="col_"], [name^="show_"]', () => {
        if (this.isLoadingFieldOptions) {
          return;
        }
        if (this.currentFieldKey) {
          this.fieldOptionsMap[this.currentFieldKey] = this.getOptionsFromForm();
          //console.log('Updated fieldOptionsMap for key:', this.currentFieldKey, this.fieldOptionsMap[this.currentFieldKey]);
          this.recalcScreenFieldCols();
        }
      });
      $('.ppsgenCfbSizeChangerBtnPc').on('click', () => this.setScreen('pc'));
      $('.ppsgenCfbSizeChangerBtnTab').on('click', () => this.setScreen('tab'));
      $('.ppsgenCfbSizeChangerBtnMob').on('click', () => this.setScreen('mob'));
      $('.ppsgenCfbSizeChangerToggle').on('click', () => this.toggleScreenRules());
    }

    /**
     * Initializes sortable functionality for fields and options.
     */
    initSortable() {
      this.fieldsContainer.sortablejs({
        animation: 0,
        ghostClass: 'ppsgen-cf-ghost',
        chosenClass: 'ppsgen-cf-chosen',
        dragClass: 'ppsgen-cf-drag',
        forceFallback: true,
        onEnd: () => this.calculateFieldOrder(),
      });

      $('.ppsgenComponentOptionsList').sortable({
        items: '.ppsgenComponentOptionInline',
        handle: '.ppsgenComponentOptionsListBtnSort',
        placeholder: 'ppsgenComponentSortablePlaceholder',
        tolerance: 'pointer',
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

    beforeFormSubmit() {
      if (typeof window.ppsgenFormBeforeSubmit === 'function') {
        return window.ppsgenFormBeforeSubmit(this) !== false;
      }
      return true;
    }

    syncCustomCodeEditors() {
      if (typeof window.ppsgenSaveFormCodeEditors === 'function') {
        window.ppsgenSaveFormCodeEditors();
      }
      this.form.find('textarea.ppsgen-custom-code-editor, textarea[name="data[form][settings][custom_css]"], textarea[name="data[form][settings][custom_js]"]').each(function () {
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
        if (textarea && (textarea.classList.contains('ppsgen-custom-code-editor') || /^data\[form\]\[settings\]\[custom_(css|js)\]$/.test(textarea.name))) {
          textarea.value = codeMirror.getValue();
        }
      });
    }

    appendCustomCodeToDataBlock(dataBlock) {
      this.syncCustomCodeEditors();
      this.form
        .find('textarea[name="data[form][settings][custom_css]"], textarea[name="data[form][settings][custom_js]"]')
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
      if (!this.beforeFormSubmit()) {
        return false;
      }

      // Validate pattern field
      const patternInput = this.form.find('input[name*="[pattern]"]');
      if (patternInput.length) {
        const patternValue = patternInput.val();
        if (patternValue) {
          try {
            new RegExp(patternValue);
          } catch (err) {
            this.showPopupNotice('error', 'edit', 'Invalid regular expression');
            return false;
          }
        }
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

      normalData['mod'] = window.ppsgenFormController ?? normalData['mod'] ?? 'form';

      const regExpValueBefore = patternInput.val();
      this.form.find('input[name*="[pattern]"]').backslashEncode();
      if (this.currentFieldKey) {
        this.fieldOptionsMap[this.currentFieldKey] = this.getOptionsFromForm();
        //console.log('Saving fieldOptionsMap for key:', this.currentFieldKey, this.fieldOptionsMap[this.currentFieldKey]);
      }

      if (typeof this.fieldOptionsMap !== 'undefined') {
        Object.entries(this.fieldOptionsMap).forEach(([fieldKey, fieldData]) => {
          const serialized = this.serializeNested(fieldData, `data[form][fields][${fieldKey}]`);
          serialized
            .replace(/^&/, '')
            .split('&')
            .forEach((pair) => {
              const [name, value] = pair.split('=');
              if (!name.startsWith('data[')) {
                return;
              }
              dataBlock[decodeURIComponent(name)] = decodeURIComponent(value || '');
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

      this.form.find('input[name*="[pattern]"]').val(regExpValueBefore);
      $('.ppsgenObjEditSaveBtn').attr('disabled', 'disabled').prop('disabled', true).find('.dashicons').toggleClass('dashicons-yes-alt dashicons-clock');

      $.post(ajaxurl, payload, (response) => {
        $('.ppsgenObjEditSaveBtn').removeAttr('disabled').prop('disabled', false).find('.dashicons').toggleClass('dashicons-yes-alt dashicons-clock');
        if (response?.success) {
          const msg = response.data?.message || 'Form is successfully saved';
          this.showPopupNotice('success', 'edit', msg);
          const url = response.data?.obj_id && ppsgenAjax?.admin_url ? `${ppsgenAjax.admin_url}?page=ppsgen&tab=form_edit&id=${response.data.obj_id}` : '';
          setTimeout(() => {
            if (url) {
              location.href = url;
            } else {
              location.reload();
            }
          }, 1000);
        } else {
          const msg = response.data?.message || 'Failed to save form';
          this.showPopupNotice('error', 'edit', msg);
        }
      }).fail(() => {
        $('.ppsgenObjEditSaveBtn').removeAttr('disabled').prop('disabled', false).find('.dashicons').toggleClass('dashicons-yes-alt dashicons-clock');
        this.showPopupNotice('error', 'edit', 'There was an error saving the form');
      });
      return false;
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
     * Adds a new field to the form.
     * @param {Event} e - The click event.
     */
    addField(e) {
      const $btn = $(e.currentTarget);
      const $selected = $btn.closest('.ppsgenCfbAddFieldWrapper').find('.ppsgenCfbAddFieldList option:selected');
      if ($selected.attr('disabled')) return;
      $btn.hide();
      const fieldOpt = $selected[0];
      const fType = $(fieldOpt).data('field-type') || '';
      if (!['divider', 'description', 'title', 'empty'].includes(fType)) {
        $selected.prop('disabled', true);
      }
      const fId = $(fieldOpt).data('field-id') || 0;
      const fSlug = $(fieldOpt).data('field-slug') || '';
      const fLabel = $(fieldOpt).data('field-label') || '';
      // Use existing field_key if available, otherwise generate a new one
      let key = $(fieldOpt).data('field-key') || this.generateUniqueFieldKey();
      //console.log('Adding field with key:', key, 'Type:', fType, 'Slug:', fSlug);
      const fieldHtmlAttr = `data-field-type="${this.sanitizeAttr(fType)}" data-field-id="${this.sanitizeAttr(fId)}" data-field-slug="${this.sanitizeAttr(fSlug)}" data-field-label="${this.sanitizeAttr(fLabel)}"`;
      const colSizes = this.getColSizes(fType);
      const fieldHtml = this.createFieldHtml(key, fType, fSlug, fieldHtmlAttr);
      const predefinedOptions = this.createPredefinedOptions(key, fType, fId, fSlug, fLabel, colSizes);
      // New titles and texts start with placeholder text, so it is clear they were added
      if (window.ppsgenApplyDefaultText) {
        window.ppsgenApplyDefaultText(predefinedOptions, fType);
      }

      if (!['divider', 'description', 'title', 'submit', 'captcha', 'empty'].includes(fType)) {
        $.post(
          ajaxurl,
          {
            action: 'getContactFieldSettingJson',
            mod: 'contact/contact_field',
            plugin: 'ppsgen',
            field_slug: fSlug,
            nonce_code: ppsgenAjax?.nonce || '',
          },
          (response) => {
            if (response?.success && response.data) {
              if (this.currentFieldKey) {
                this.fieldOptionsMap[this.currentFieldKey] = this.getOptionsFromForm();
              }
              this.fieldOptionsMap[key] = $.extend({}, predefinedOptions, response.data);
              //console.log('Field settings loaded for key:', key, this.fieldOptionsMap[key]);
              this.addFieldToContainer(fieldHtml, key, this.fieldOptionsMap[key]);
              this.currentFieldKey = key;
              this.setOptionsToForm(this.fieldOptionsMap[key]);
              this.showOptionsBlockByType(fType);
              this.activateField(key);
              $btn.show();
              this.calculateFieldOrder();
              this.recalcScreenFieldCols();
              this.checkContactFieldListBtnToggle();
            } else {
              console.error('Failed to load field settings:', response);
              this.showPopupNotice('error', 'edit', 'Error loading field settings');
              $btn.show();
            }
          }
        ).fail((xhr, status, error) => {
          console.error('AJAX error in addField:', error);
          this.showPopupNotice('error', 'edit', 'Error loading field settings');
          $btn.show();
        });
      } else {
        if (this.currentFieldKey) {
          this.fieldOptionsMap[this.currentFieldKey] = this.getOptionsFromForm();
        }
        this.fieldOptionsMap[key] = predefinedOptions;
        this.addFieldToContainer(fieldHtml, key, predefinedOptions);
        this.currentFieldKey = key;
        this.setOptionsToForm(this.fieldOptionsMap[key]);
        this.showOptionsBlockByType(fType);
        this.activateField(key);
        $btn.show();
        this.calculateFieldOrder();
        this.recalcScreenFieldCols();
        this.checkContactFieldListBtnToggle();
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
     * Defines column sizes and visibility for different field types.
     * @param {string} fType - The field type.
     * @returns {Object} - Object containing column sizes and visibility settings.
     */
    getColSizes(fType) {
      let colMob = 12,
        colTab = 6,
        colPc = 6;
      if (['divider', 'description', 'title'].includes(fType)) {
        colMob = colTab = colPc = 12;
      } else if (fType === 'empty') {
        colMob = colTab = colPc = 1;
      }
      return { colMob, colTab, colPc, showMob: true, showTab: true, showPc: true };
    }

    /**
     * Creates HTML for a new field with escaped content.
     * @param {string} key - The unique field key.
     * @param {string} fType - The field type.
     * @param {string} fSlug - The field slug.
     * @param {string} fieldHtmlAttr - The HTML attributes for the field.
     * @returns {string} - The generated HTML string.
     */
    createFieldHtml(key, fType, fSlug, fieldHtmlAttr) {
      const escapedContent = $('<div>')
        .text(fType + (fSlug ? ' - ' + fSlug : ''))
        .html();
      let fieldHtml = `<div ${fieldHtmlAttr} data-field-key="${this.sanitizeAttr(key)}" class="ppsgen-cf-col ppsgen-cf-type-${this.sanitizeAttr(fType)}">`;
      fieldHtml += fType !== 'empty' ? escapedContent : '-';
      fieldHtml += '</div>';
      return fieldHtml;
    }

    /**
     * Creates predefined options for a new field.
     * @param {string} key - The unique field key.
     * @param {string} fType - The field type.
     * @param {number} fId - The field ID.
     * @param {string} fSlug - The field slug.
     * @param {string} fLabel - The field label.
     * @param {Object} colSizes - The column sizes and visibility settings.
     * @returns {Object} - The predefined options object.
     */
    createPredefinedOptions(key, fType, fId, fSlug, fLabel, colSizes) {
      return {
        field_key: key,
        field_type: fType,
        field_id: fId,
        field_slug: fSlug,
        field_label: fLabel,
        col_mob: colSizes.colMob,
        col_tab: colSizes.colTab,
        col_pc: colSizes.colPc,
        show_mob: colSizes.showMob ? '1' : '0',
        show_tab: colSizes.showTab ? '1' : '0',
        show_pc: colSizes.showPc ? '1' : '0',
      };
    }

    /**
     * Adds a field to the container and updates options.
     * @param {string} fieldHtml - The HTML string for the field.
     * @param {string} key - The unique field key.
     * @param {Object} predefinedOptions - The predefined options for the field.
     */
    addFieldToContainer(fieldHtml, key, predefinedOptions) {
      this.fieldsContainer.append(fieldHtml);
      if (predefinedOptions) {
        this.predefinedOptions[key] = predefinedOptions;
        this.fieldOptionsMap[key] = $.extend(true, {}, predefinedOptions);
        //console.log('Added field to container with key:', key, this.fieldOptionsMap[key]);
      } else if (!this.fieldOptionsMap[key]) {
        this.fieldOptionsMap[key] = {};
      }
      this.recalcScreenFieldCols();
      jQuery(document).trigger('ppsgenFieldsChanged');
    }

    /**
     * Handles field selection.
     * @param {Event} e - The click event.
     */
    selectField(e) {
      const $field = $(e.currentTarget);
      const key = $field.data('field-key');
      const fieldType = $field.data('field-type');
      if (!key) return;
      if (this.currentFieldKey) {
        this.fieldOptionsMap[this.currentFieldKey] = this.getOptionsFromForm();
      }
      this.activateField(key);
      if (this.fieldOptionsMap[key]) {
        //console.log('Selecting field with key:', key, this.fieldOptionsMap[key]);
        this.currentFieldKey = key;
        this.setOptionsToForm(this.fieldOptionsMap[key]);
        this.showOptionsBlockByType(fieldType);
      }
      jQuery(document).trigger('ppsgenFieldsChanged');
    }

    /**
     * Activates a field with animation.
     * @param {string} key - The unique field key.
     */
    activateField(key) {
      const $field = this.fieldsContainer.find(`.ppsgen-cf-col[data-field-key="${key}"]`);
      $('.ppsgen-cf-col-active').removeClass('ppsgen-cf-col-active');
      $field.addClass('click-animate ppsgen-cf-col-active');
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
      //console.log('Generated new field key:', key);
      return key;
    }

    serializeNested(obj, prefix) {
      let str = '';
      const build = (o, pre) => {
        if (typeof o === 'object' && o !== null && !Array.isArray(o)) {
          Object.entries(o).forEach(([key, value]) => {
            if (`${pre}[${key}]`.includes('options[@@OPTNUMBER@@]')) return;
            build(value, `${pre}[${key}]`);
          });
        } else if (Array.isArray(o)) {
          o.forEach((value, i) => {
            build(value, `${pre}[${i}]`);
          });
        } else {
          str += `&${pre}=${encodeURIComponent(o ?? '')}`;
        }
      };
      build(obj, prefix);
      return str;
    }

    /**
     * Shows or hides options based on field type.
     * @param {string} fieldType - The field type.
     */
    showOptionsBlockByType(fieldType) {
      this.optionsContainer.find('.ppsgenComponentRow').each((_, row) => {
        const $row = $(row);
        let show = true;
        const showTypes = $row.data('field-type');
        if (showTypes) {
          const showArr = showTypes
            .toString()
            .split(',')
            .map((s) => s.trim());
          show = showArr.includes(fieldType);
        }
        const hideTypes = $row.data('field-type-hide');
        if (hideTypes) {
          const hideArr = hideTypes
            .toString()
            .split(',')
            .map((s) => s.trim());
          show = !hideArr.includes(fieldType);
        }
        $row.toggle(show);
      });
      this.optionsContainer.show();
    }

    /**
     * Recalculates column sizes and visibility for fields.
     */
    recalcScreenFieldCols() {
      //console.log('Recalculating screen field cols. Screen:', this.screenActual, 'Rules:', this.screenRules);
      this.fieldsContainer.find('.ppsgen-cf-col').removeClass((_, className) => (className.match(/\bppsgen-col-[^ ]+-[^ ]+\b/g) || []).join(' '));
      if (this.screenRules) {
        $('.ppsgen-cf-col').addClass('ppsgen-col-hide');
      } else {
        $('.ppsgen-col-hide').removeClass('ppsgen-col-hide');
      }
      this.fieldsContainer.find('.ppsgen-cf-col').each((_, obj) => {
        const $field = $(obj);
        const key = $field.data('field-key');
        if (this.fieldOptionsMap[key]) {
          const colKey = `col_${this.screenActual}`;
          const showKey = `show_${this.screenActual}`;
          let colSize = parseInt(this.fieldOptionsMap[key][colKey]) || 12;
          const screenOption = this.fieldOptionsMap[key][showKey] || '1';

          // Validate col_* values
          if (isNaN(colSize) || colSize < 1 || colSize > 12) {
            colSize = 12;
            this.fieldOptionsMap[key][colKey] = colSize;
            this.showPopupNotice('error', 'edit', `Incorrect value for ${colKey}. Set value by default: 12.`);
          }

          //console.log(`Field ${key}: ${colKey}=${colSize}, ${showKey}=${screenOption}`);
          if (this.screenRules && screenOption === '1') {
            $field.removeClass('ppsgen-col-hide');
          }
          const className = `ppsgen-col-${this.screenActual}-${colSize}`;
          $field.addClass(className);
        }
      });
    }

    /**
     * Deletes a field from the form.
     * @param {Event} e - The click event.
     */
    deleteField(e) {
      const $container = $(e.currentTarget).closest('.ppsgenCffOptionContainer');
      const key = $container.find('[name="field_key"]').val();
      if (!confirm(ppsgenAjax?.confirmDeleteText || 'Are you sure you want to delete this field?')) return;
      const $field = $(`.ppsgen-cf-col[data-field-key="${key}"]`);
      const fType = $field.data('field-type') || '';
      const fId = $field.data('field-id') || 0;
      $('.ppsgenCfbAddFieldList option').each((_, opt) => {
        const $opt = $(opt);
        if (['submit', 'captcha'].includes(fType) && $opt.data('field-type') === fType) {
          $opt.prop('disabled', false).removeAttr('disabled');
        }
        if ($opt.data('field-id') === fId) {
          $opt.prop('disabled', false).removeAttr('disabled');
        }
      });
      $field.remove();
      this.setOptionsToForm({});
      setTimeout(() => {
        this.optionsContainer.hide();
        delete this.fieldOptionsMap[key];
        this.currentFieldKey = null;
        this.calculateFieldOrder();
        this.checkContactFieldListBtnToggle();
        jQuery(document).trigger('ppsgenFieldsChanged');
      }, 200);
    }

    duplicateField(e) {
      e.preventDefault();
      const sourceKey = this.currentFieldKey || $(e.currentTarget).closest('.ppsgenCffOptionContainer').find('[name="field_key"]').val();
      if (!sourceKey || !this.fieldOptionsMap[sourceKey]) {
        this.showPopupNotice('error', 'edit', 'Select field for duplication first');
        return;
      }

      this.fieldOptionsMap[sourceKey] = this.getOptionsFromForm();
      const sourceOptions = $.extend(true, {}, this.fieldOptionsMap[sourceKey]);
      if (parseInt(sourceOptions.field_id || 0, 10) > 0) {
        this.showPopupNotice('error', 'edit', 'Contact Fields cannot be duplicated here');
        return;
      }
      const newKey = this.generateUniqueFieldKey();
      sourceOptions.field_key = newKey;

      const $sourceField = this.fieldsContainer.find(`.ppsgen-cf-col[data-field-key="${sourceKey}"]`);
      if (!$sourceField.length) {
        this.showPopupNotice('error', 'edit', 'Selected field was not found');
        return;
      }

      const $cloneField = $sourceField.clone(false, false);
      $cloneField.attr('data-field-key', newKey);
      $cloneField.removeClass('ppsgen-cf-col-active click-animate');

      $sourceField.after($cloneField);
      this.fieldOptionsMap[newKey] = sourceOptions;
      this.currentFieldKey = newKey;
      this.activateField(newKey);
      this.setOptionsToForm(this.fieldOptionsMap[newKey]);
      this.showOptionsBlockByType(sourceOptions.field_type || '');
      this.calculateFieldOrder();
      this.recalcScreenFieldCols();
      this.checkContactFieldListBtnToggle();
      jQuery(document).trigger('ppsgenFieldsChanged');
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
      data.options = this.getFieldOptionsFromForm();
      return data;
    }

    /**
     * Retrieves field options for select/checkbox/radio.
     * @returns {Object} - The field options object.
     */
    getFieldOptionsFromForm() {
      const options = {};
      $('.ppsgenComponentOptionsList .ppsgenComponentOptionInline').each((_, opt) => {
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
      this.isLoadingFieldOptions = true;
      this.optionsContainer.find(':input[name]').each((_, el) => {
        const $el = $(el);
        const name = $el.attr('name');
        let mappedValue;
        if (typeof data[name] === 'undefined') {
          if (name.startsWith('include_ids_')) {
            mappedValue = data.include_ids;
          } else if (name.startsWith('exclude_ids_')) {
            mappedValue = data.exclude_ids;
          } else if (name.startsWith('source_parent_id_')) {
            mappedValue = data.source_parent_id;
          }
        }
        const fieldValue = typeof data[name] !== 'undefined' ? data[name] : mappedValue;
        if (typeof fieldValue !== 'undefined') {
          if (typeof tinymce !== 'undefined' && tinymce.get(name)) {
            tinymce.get(name).setContent('');
            setTimeout(() => tinymce.get(name).setContent(fieldValue), 10);
          } else if ($el.is(':checkbox')) {
            $el.prop('checked', fieldValue === '1' || fieldValue === 1);
            $el.trigger('change');
          } else {
            $el.val(fieldValue);
            $el.trigger('change');
          }
        } else {
          if (typeof tinymce !== 'undefined' && tinymce.get(name)) {
            tinymce.get(name).setContent('');
          } else if ($el.is(':checkbox')) {
            $el.prop('checked', false);
            $el.trigger('change');
          } else {
            $el.val('');
            $el.trigger('change');
          }
        }
      });
      this.isLoadingFieldOptions = false;
      if (data.options && typeof data.options === 'object') {
        this.renderFieldOptionsToForm(data.options);
      } else {
        $('.ppsgenComponentOptionsList').html('');
      }
    }

    /**
     * Renders field options for select/checkbox/radio.
     * @param {Object} optionsData - The options data to render.
     */
    renderFieldOptionsToForm(optionsData) {
      const $container = $('.ppsgenComponentOptionsList');
      const $template = $('.ppsgenComponentOptionTemplate');
      $container.empty();
      if (!optionsData || typeof optionsData !== 'object') return;
      Object.entries(optionsData).forEach(([key, option]) => {
        const $clone = $template.clone().removeClass('ppsgenComponentOptionTemplate');
        $clone.find('[name], [id], [for]').each((_, el) => {
          const $el = $(el);
          ['name', 'id', 'for'].forEach((attr) => {
            const val = $el.attr(attr);
            if (val && val.includes('@@OPTNUMBER@@')) {
              $el.attr(attr, val.replace(/@@OPTNUMBER@@/g, this.sanitizeAttr(key)));
            }
          });
        });
        $clone.find(':input[name]').each((_, input) => {
          const $input = $(input);
          const newName = $input.attr('name');
          if (newName.includes('[value]')) {
            $input.val(option.value || '');
          } else if (newName.includes('[label]')) {
            $input.val(option.label || '');
          } else if (newName.includes('[checked]')) {
            $input.prop('checked', option.checked == '1');
          } else if (newName.includes('[disabled]')) {
            $input.prop('checked', option.disabled == '1');
          } else if (newName.includes('[remove]')) {
            $input.prop('checked', option.remove == '1');
          }
        });
        $container.append($clone);
      });
    }

    /**
     * Calculates field order for sorting.
     */
    calculateFieldOrder() {
      this.fieldsContainer.find('.ppsgen-cf-col').each((index, obj) => {
        const key = $(obj).data('field-key');
        if (this.currentFieldKey === key) {
          $('[name="order_index"]').val(index + 1);
        }
        if (this.fieldOptionsMap[key]) {
          this.fieldOptionsMap[key]['order_index'] = index + 1;
          //console.log('Updated order_index for key:', key, 'to:', index + 1);
        }
      });
    }

    /**
     * Toggles visibility of the contact field list button.
     */
    checkContactFieldListBtnToggle() {
      let allOptionsDisabled = true;
      $('.ppsgenCfbAddContactFieldList').each((_, list) => {
        if ($(list).find('option:not(:disabled)').length > 0) {
          allOptionsDisabled = false;
          return false;
        }
      });
      $('.ppsgenCfbAddContactFieldBtn').toggle(!allOptionsDisabled);
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
      //console.log('Toggled screenRules to:', this.screenRules);
      this.recalcScreenFieldCols();
    }

    getCurrentFields() {
      this.fieldOptionsMap[this.currentFieldKey] = this.getOptionsFromForm();
      this.recalcScreenFieldCols();
      return this.fieldOptionsMap;
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
      if (!this.beforeFormSubmit()) {
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

      normalData['mod'] = window.ppsgenFormController ?? 'form';
      normalData['action'] = 'renderPreview';

      if (typeof this.fieldOptionsMap !== 'undefined') {
        Object.entries(this.fieldOptionsMap).forEach(([fieldKey, fieldData]) => {
          const serialized = this.serializeNested(fieldData, `data[form][fields][${fieldKey}]`);
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
          const serialized = this.serializeNested(fieldData, `data[form][fields][${fieldKey}]`);
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
                ${typeof window.ppsgenPreviewPageMock === 'function' ? window.ppsgenPreviewPageMock(response.data.preview) : response.data.preview}
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

  $(document).ready(() => (window.ppsgenFormBuilder = new ppsgenFormBuilder()));

  $(document).ready(function () {
    jQuery('body').on('click', '.ppsgenFormSubmitsRowTitle', function () {
      let title = $(this);
      jQuery('body').find('.ppsgenFormSubmitsRowData').hide();
      title.closest('.ppsgenFormSubmitsRow').find('.ppsgenFormSubmitsRowData').show();
    });

    jQuery('body').on('click', '.ppsgenFormSubmitsRowTitleDeleteBtn', function (e) {
      e.preventDefault();
      if (!confirm(ppsgenAjax.confirmDeleteText)) {
        return;
      }
      var button = jQuery(this);
      button.prop('disabled', true);
      var obj = button.data('obj');
      jQuery.post(
        ajaxurl,
        {
          action: 'deleteFormSubmit',
          mod: 'form',
          plugin: 'ppsgen',
          obj: obj,
          nonce_code: ppsgenAjax.nonce,
        },
        function (response) {
          if (response && response.success) {
            button.closest('.ppsgenFormSubmitsRow').remove();
          }
        }
      );
    });

    jQuery('body').on('click', '.ppsgenFormSubmitsRowTitleVerifyContactBtn', function (e) {
      e.preventDefault();
      if (!confirm(ppsgenAjax.confirmVerifyText)) {
        return;
      }
      var button = jQuery(this);
      button.prop('disabled', true);
      var obj = button.data('obj');
      jQuery.post(
        ajaxurl,
        {
          action: 'verifyContact',
          mod: 'contact',
          plugin: 'ppsgen',
          obj: obj,
          nonce_code: ppsgenAjax.nonce,
        },
        function (response) {
          if (response && response.success) {
            button.closest('.ppsgenFormSubmitsRowTitleBtns').find('.ppsgenFormSubmitsRowVerifyContactIcon').find('.dashicons.dashicons-dismiss').removeClass('dashicons-dismiss').addClass('dashicons-yes-alt');
            button.remove();
          }
        }
      );
    });

    jQuery('body').on('click', '.ppsgenFormSubmitsRowTitleVerifySubmitBtn', function (e) {
      e.preventDefault();
      if (!confirm(ppsgenAjax.confirmVerifySubmitText)) {
        return;
      }
      var button = jQuery(this);
      button.prop('disabled', true);
      var obj = button.data('obj');
      jQuery.post(
        ajaxurl,
        {
          action: 'verifySubmit',
          mod: 'contact',
          plugin: 'ppsgen',
          obj: obj,
          rewrite: 0,
          nonce_code: ppsgenAjax.nonce,
        },
        function (response) {
          if (response && response.success) {
            button.closest('.ppsgenFormSubmitsRowTitleBtns').find('.ppsgenFormSubmitsRowVerifySubmitIcon').find('.dashicons.dashicons-dismiss').removeClass('dashicons-dismiss').addClass('dashicons-yes-alt');
            button.remove();
          }
        }
      );
    });

    jQuery('body').on('click', '.ppsgenFormSubmitsRowTitleVerifySubmitContactBtn', function (e) {
      e.preventDefault();
      var rewrite = 0;
      if (!confirm(ppsgenAjax.confirmVerifySubmitText)) {
        return;
      }
      if (confirm(ppsgenAjax.confirmVerifySubmitRewrite)) {
        rewrite = 1;
      }
      var button = jQuery(this);
      button.prop('disabled', true);
      var obj = button.data('obj');
      jQuery.post(
        ajaxurl,
        {
          action: 'verifySubmit',
          mod: 'contact',
          plugin: 'ppsgen',
          obj: obj,
          rewrite: rewrite,
          nonce_code: ppsgenAjax.nonce,
        },
        function (response) {
          if (response && response.success) {
            button.closest('.ppsgenFormSubmitsRowTitleBtns').find('.ppsgenFormSubmitsRowVerifySubmitIcon').find('.dashicons.dashicons-dismiss').removeClass('dashicons-dismiss').addClass('dashicons-yes-alt');
            button.remove();
          }
        }
      );
    });

    jQuery('body').on('click', '.ppsgenFormResetStatsBtn', function (e) {
      e.preventDefault();
      if (!confirm(ppsgenAjax.confirmResetText || ppsgenAjax.confirmDeleteText)) {
        return;
      }
      var button = jQuery(this);
      button.prop('disabled', true);
      var obj = button.data('obj');
      jQuery.post(
        ajaxurl,
        {
          action: 'resetFormStats',
          mod: 'form',
          plugin: 'ppsgen',
          obj: obj,
          nonce_code: ppsgenAjax.nonce,
        },
        function (response) {
          if (response && response.success) {
            window.location.reload();
          } else {
            button.prop('disabled', false);
          }
        }
      );
    });
  });
})(jQuery);
