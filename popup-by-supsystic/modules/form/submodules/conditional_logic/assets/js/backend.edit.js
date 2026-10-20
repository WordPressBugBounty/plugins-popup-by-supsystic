jQuery(document).ready(function (jQuery) {
  var clData = window.ppsgenFormDataConditionaLogic || { logic_data: [], hidden_fields: [], languages: {} };
  var fields = window.ppsgenFormBuilder.getCurrentFields();
  var valueFieldTypes = ['text', 'textarea', 'email', 'phone', 'number', 'hidden', 'password', 'select', 'radio', 'checkbox', 'file', 'datetime', 'date', 'month', 'week', 'time', 'url', 'range', 'color', 'rating', 'address', 'languages', 'countries'];
  var nonValueFieldTypes = ['title', 'description', 'empty', 'divider', 'submit', 'captcha'];
  var fieldOptionsConditions = {};
  var fieldOptionsActions = {};
  var fieldKeyToType = {};
  var fieldKeyToLabel = {};
  jQuery.each(fields, function (key, field) {
    if (!key || !field.field_type) return;
    fieldKeyToType[key] = field.field_type;
    var label;
    switch (field.field_type) {
      case 'title':
        label = 'title - (' + (field.title_text || '') + ')';
        break;
      case 'description':
        label = 'description - ' + key;
        break;
      case 'empty':
        label = 'empty - ' + key;
        break;
      case 'divider':
        label = 'divider - ' + key;
        break;
      case 'submit':
        label = 'submit - Submit Button';
        break;
      case 'captcha':
        label = 'captcha - Captcha';
        break;
      default:
        label = field.field_type + ' - ' + (field.label || field.field_slug || key);
    }
    fieldKeyToLabel[key] = label;
    if (valueFieldTypes.includes(field.field_type)) {
      fieldOptionsConditions[key] = label;
    }
    if (valueFieldTypes.includes(field.field_type) || nonValueFieldTypes.includes(field.field_type)) {
      fieldOptionsActions[key] = label;
    }
  });

  var jQueryhiddenSelect = jQuery('.ppsgenClHiddenFieldsSelect');
  jQuery.each(fieldOptionsActions, function (value, label) {
    jQueryhiddenSelect.append('<option value="' + value + '">' + label + '</option>');
  });
  var hiddenFields = clData.hidden_fields || [];
  jQueryhiddenSelect.val(hiddenFields);
  jQueryhiddenSelect.ppsgenselect();

  var conditionTemplate =
    '<div class="ppsgenClConditionRow">' +
    '<select class="ppsgenClTypeSelect"><option value="field">Field</option><option value="user">User</option></select>' +
    '<select class="ppsgenClSubTypeSelect"></select>' +
    '<select class="ppsgenClOperatorSelect"></select>' +
    '<input type="text" class="ppsgenClValueInput" style="display:none;">' +
    '<div class="ppsgenClDeleteRow dashicons dashicons-trash"></div>' +
    '</div>';

  var actionTemplate =
    '<div class="ppsgenClActionRow">' +
    '<select class="ppsgenClTypeSelect"><option value="field">Field</option><option value="redirect">Redirect</option></select>' +
    '<select class="ppsgenClSubTypeSelect"></select>' +
    '<select class="ppsgenClOperatorSelect"></select>' +
    '<input type="text" class="ppsgenClValueInput" style="display:none;">' +
    '<div class="ppsgenClDeleteRow dashicons dashicons-trash"></div>' +
    '</div>';

  var logicTemplate =
    '<div class="ppsgenClLogic">' +
    '<div class="ppsgenClConditions"><div class="ppsgenCffOptionContainerTitle">Conditions</div><div class="ppsgenClAddConditionBtn">+ Add condition</div><div class="ppsgenClConditionsWrapper"></div></div>' +
    '<div class="ppsgenClActions"><div class="ppsgenCffOptionContainerTitle">Actions</div><div class="ppsgenClAddActionBtn">+ Add action</div><div class="ppsgenClActionsWrapper"></div></div>' +
    '<div class="ppsgenClDeleteLogic dashicons dashicons-trash"></div>' +
    '</div>';

  var logicData = clData.logic_data || [];
  jQuery.each(logicData, function (i, logic) {
    addLogic(logic.conditions, logic.actions);
  });
  jQuery('.ppsgenClLogicDataInput').val(JSON.stringify(logicData));

  function addLogic(conditions = [], actions = []) {
    var jQuerylogic = jQuery(logicTemplate);
    var jQueryconditionsWrapper = jQuerylogic.find('.ppsgenClConditionsWrapper');
    var jQueryactionsWrapper = jQuerylogic.find('.ppsgenClActionsWrapper');

    jQuery.each(conditions, function (j, cond) {
      if (j > 0) {
        addOperatorBetween(jQueryconditionsWrapper, cond.operator_between || 'AND');
      }
      addCondition(jQueryconditionsWrapper, cond);
    });

    jQuery.each(actions, function (j, act) {
      addAction(jQueryactionsWrapper, act);
    });

    jQuery('.ppsgenClLogicsWrapper').append(jQuerylogic);
  }

  function addOperatorBetween(jQuerywrapper, value) {
    var jQueryoperator = jQuery('<select class="ppsgenClOperatorBetween"><option>AND</option><option>OR</option></select>');
    jQueryoperator.val(value);
    jQuerywrapper.append(jQueryoperator);
  }

  function addCondition(jQuerywrapper, cond = {}) {
    var jQueryrow = jQuery(conditionTemplate);
    var jQuerytypeSelect = jQueryrow.find('.ppsgenClTypeSelect');
    jQuerytypeSelect.val(cond.type || 'field');
    updateSubTypeAndOperator(jQueryrow, cond);
    jQuerywrapper.append(jQueryrow);
  }

  function addAction(jQuerywrapper, act = {}) {
    var jQueryrow = jQuery(actionTemplate);
    var jQuerytypeSelect = jQueryrow.find('.ppsgenClTypeSelect');
    jQuerytypeSelect.val(act.type || 'field');
    updateSubTypeAndOperator(jQueryrow, act);
    jQuerywrapper.append(jQueryrow);
  }

  function updateSubTypeAndOperator(jQueryrow, data = {}) {
    var type = jQueryrow.find('.ppsgenClTypeSelect').val();
    var jQuerysubType = jQueryrow.find('.ppsgenClSubTypeSelect');
    var jQueryoperator = jQueryrow.find('.ppsgenClOperatorSelect');
    var jQueryvalueInput = jQueryrow.find('.ppsgenClValueInput');

    jQuerysubType.empty().hide();
    jQueryoperator.empty().hide();
    jQueryvalueInput.hide();
    jQueryrow.find('.ppsgenClAfterSubmitWrapper').remove();
    jQueryrow.find('.ppsgenClLangSelect').remove();

    if (jQueryrow.hasClass('ppsgenClConditionRow')) {
      if (type === 'field') {
        jQuerysubType.show();
        jQuery.each(fieldOptionsConditions, function (value, label) {
          jQuerysubType.append('<option value="' + value + '">' + label + '</option>');
        });
        jQuerysubType.val(data.subType || '');
        jQueryoperator.show().append('<option>Equal</option><option>Not Equal</option><option>Like</option><option>More</option><option>Less</option>');
        jQueryoperator.val(data.operator || 'Equal');
        jQueryvalueInput.show().val(data.value || '');
      } else if (type === 'user') {
        jQuerysubType.show();
        jQuerysubType.append('<option value="Url">Url</option><option value="Language">Language</option><option value="Logged In">Logged In</option><option value="Not Logged In">Not Logged In</option>');
        jQuerysubType.val(data.subType || 'Url');
        updateUserSubType(jQueryrow, data);
      }
    } else if (jQueryrow.hasClass('ppsgenClActionRow')) {
      if (type === 'field') {
        jQuerysubType.show();
        jQuery.each(fieldOptionsActions, function (value, label) {
          jQuerysubType.append('<option value="' + value + '">' + label + '</option>');
        });
        jQuerysubType.val(data.subType || '');
        updateActionOperator(jQueryrow, data.operator);
      } else if (type === 'redirect') {
        jQuerysubType.hide();
        jQueryoperator.hide();
        jQueryvalueInput
          .show()
          .attr('placeholder', 'Enter URL')
          .val(data.value || '');
        var checkboxWrapper = '<div class="ppsgenClAfterSubmitWrapper"><input type="checkbox" class="ppsgenClAfterSubmit" ' + (data.afterSubmit ? 'checked' : '') + '> After Submit</div>';
        jQueryvalueInput.after(checkboxWrapper);
      }
    }
  }

  function updateActionOperator(jQueryrow, currentOp = 'Show') {
    var selectedKey = jQueryrow.find('.ppsgenClSubTypeSelect').val();
    var selectedType = fieldKeyToType[selectedKey] || '';
    var isNonValue = nonValueFieldTypes.includes(selectedType);
    var jQueryoperator = jQueryrow.find('.ppsgenClOperatorSelect');
    var jQueryvalueInput = jQueryrow.find('.ppsgenClValueInput');
    jQueryoperator.empty().show();
    if (isNonValue) {
      jQueryoperator.append('<option>Show</option><option>Hide</option>');
      jQueryoperator.val(['Show', 'Hide'].includes(currentOp) ? currentOp : 'Show');
      jQueryvalueInput.hide();
    } else {
      jQueryoperator.append('<option>Show</option><option>Hide</option><option>Set Value</option><option>Add Value</option><option>Subtract Value</option>');
      jQueryoperator.val(currentOp);
      if (['Set Value', 'Add Value', 'Subtract Value'].includes(jQueryoperator.val())) {
        jQueryvalueInput.show();
      } else {
        jQueryvalueInput.hide();
      }
    }
  }

  function updateUserSubType(jQueryrow, data = {}) {
    var subType = jQueryrow.find('.ppsgenClSubTypeSelect').val();
    var jQueryoperator = jQueryrow.find('.ppsgenClOperatorSelect');
    var jQueryvalueInput = jQueryrow.find('.ppsgenClValueInput');

    jQueryoperator.empty().hide();
    jQueryvalueInput.hide();
    jQueryrow.find('.ppsgenClLangSelect').remove();

    if (subType === 'Url') {
      jQueryoperator.show().append('<option>Equal</option><option>Not Equal</option><option>Like</option>');
      jQueryoperator.val(data.operator || 'Equal');
      jQueryvalueInput.show().val(data.value || '');
    } else if (subType === 'Language') {
      jQueryoperator.show().append('<option>Equal</option><option>Not Equal</option>');
      jQueryoperator.val(data.operator || 'Equal');
      var jQuerylangSelect = jQuery('<select class="ppsgenClLangSelect"></select>');
      jQuery.each(clData.languages || {}, function (code, name) {
        jQuerylangSelect.append('<option value="' + code + '">' + name + '</option>');
      });
      jQuerylangSelect.val(data.value || '');
      jQueryoperator.after(jQuerylangSelect);
    } else if (subType === 'Logged In' || subType === 'Not Logged In') {
    }
  }

  jQuery(document).on('change', '.ppsgenClTypeSelect', function () {
    var jQueryrow = jQuery(this).closest('.ppsgenClConditionRow, .ppsgenClActionRow');
    updateSubTypeAndOperator(jQueryrow);
    saveLogicData();
  });

  jQuery(document).on('change', '.ppsgenClLangSelect', function () {
    saveLogicData();
  });

  jQuery(document).on('change', '.ppsgenClSubTypeSelect', function () {
    var jQueryrow = jQuery(this).closest('.ppsgenClConditionRow, .ppsgenClActionRow');
    var type = jQueryrow.find('.ppsgenClTypeSelect').val();
    if (type === 'user' && jQueryrow.hasClass('ppsgenClConditionRow')) {
      updateUserSubType(jQueryrow);
    } else if (type === 'field' && jQueryrow.hasClass('ppsgenClActionRow')) {
      var currentOp = jQueryrow.find('.ppsgenClOperatorSelect').val() || 'Show';
      updateActionOperator(jQueryrow, currentOp);
    }
    saveLogicData();
  });

  jQuery(document).on('change', '.ppsgenClOperatorSelect', function () {
    var jQueryrow = jQuery(this).closest('.ppsgenClActionRow');
    var op = jQuery(this).val();
    var jQueryvalueInput = jQueryrow.find('.ppsgenClValueInput');
    if (op === 'Set Value' || op === 'Add Value' || op === 'Subtract Value') {
      jQueryvalueInput.show();
    } else {
      jQueryvalueInput.hide();
    }
    saveLogicData();
  });

  jQuery(document).on('click', '.ppsgenClAddLogicBtn', function () {
    addLogic();
    saveLogicData();
  });

  jQuery(document).on('click', '.ppsgenClAddConditionBtn', function () {
    var jQuerywrapper = jQuery(this).closest('.ppsgenClConditions').find('.ppsgenClConditionsWrapper');
    if (jQuerywrapper.children('.ppsgenClConditionRow').length > 0) {
      addOperatorBetween(jQuerywrapper, 'AND');
    }
    addCondition(jQuerywrapper);
    saveLogicData();
  });

  jQuery(document).on('click', '.ppsgenClAddActionBtn', function () {
    var jQuerywrapper = jQuery(this).closest('.ppsgenClActions').find('.ppsgenClActionsWrapper');
    addAction(jQuerywrapper);
    saveLogicData();
  });

  jQuery(document).on('click', '.ppsgenClDeleteRow', function () {
    var jQueryrow = jQuery(this).closest('.ppsgenClConditionRow, .ppsgenClActionRow');
    var jQuerywrapper = jQueryrow.parent();
    var jQueryprevOperator = jQueryrow.prev('.ppsgenClOperatorBetween');
    if (jQueryprevOperator.length) {
      jQueryprevOperator.remove();
    }
    jQueryrow.remove();
    while (jQuerywrapper.children().first().hasClass('ppsgenClOperatorBetween')) {
      jQuerywrapper.children().first().remove();
    }
    saveLogicData();
  });

  jQuery(document).on('click', '.ppsgenClDeleteLogic', function () {
    jQuery(this).closest('.ppsgenClLogic').remove();
    saveLogicData();
  });

  jQuery(document).on('change input', '.ppsgenClOperatorSelect, .ppsgenClValueInput, .ppsgenClAfterSubmit, .ppsgenClOperatorBetween', function () {
    saveLogicData();
  });

  function saveLogicData() {
    var logics = [];
    jQuery('.ppsgenClLogic').each(function () {
      var logic = { conditions: [], actions: [] };
      var jQueryconditionsWrapper = jQuery(this).find('.ppsgenClConditionsWrapper');
      var prevOperator = null;
      jQueryconditionsWrapper.children().each(function () {
        if (jQuery(this).hasClass('ppsgenClOperatorBetween')) {
          prevOperator = jQuery(this).val();
        } else if (jQuery(this).hasClass('ppsgenClConditionRow')) {
          var cond = {
            type: jQuery(this).find('.ppsgenClTypeSelect').val(),
            subType: jQuery(this).find('.ppsgenClSubTypeSelect').val(),
            operator: jQuery(this).find('.ppsgenClOperatorSelect').val(),
            value: jQuery(this).find('.ppsgenClLangSelect').val() || jQuery(this).find('.ppsgenClValueInput').val(),
            operator_between: prevOperator,
          };
          logic.conditions.push(cond);
          prevOperator = null;
        }
      });
      jQuery(this)
        .find('.ppsgenClActionsWrapper .ppsgenClActionRow')
        .each(function () {
          var act = {
            type: jQuery(this).find('.ppsgenClTypeSelect').val(),
            subType: jQuery(this).find('.ppsgenClSubTypeSelect').val(),
            operator: jQuery(this).find('.ppsgenClOperatorSelect').val(),
            value: jQuery(this).find('.ppsgenClValueInput').val(),
            afterSubmit: jQuery(this).find('.ppsgenClAfterSubmit').is(':checked'),
          };
          logic.actions.push(act);
        });
      if (logic.conditions.length > 0 || logic.actions.length > 0) {
        logics.push(logic);
      }
    });
    jQuery('.ppsgenClLogicDataInput').val(JSON.stringify(logics));
  }

  function rebuildFieldSelects() {
    var fields = window.ppsgenFormBuilder.getCurrentFields();
    fieldOptionsConditions = {};
    fieldOptionsActions = {};
    fieldKeyToType = {};
    fieldKeyToLabel = {};
    jQuery.each(fields, function (key, field) {
      if (!key || !field.field_type) return;
      fieldKeyToType[key] = field.field_type;
      var label;
      switch (field.field_type) {
        case 'title':
          label = 'title - (' + (field.title_text || '') + ')';
          break;
        case 'description':
          label = 'description - ' + key;
          break;
        case 'empty':
          label = 'empty - ' + key;
          break;
        case 'divider':
          label = 'divider - ' + key;
          break;
        case 'submit':
          label = 'submit - Submit Button';
          break;
        case 'captcha':
          label = 'captcha - Captcha';
          break;
        default:
          label = field.field_type + ' - ' + (field.label || field.field_slug || key);
      }
      fieldKeyToLabel[key] = label;
      if (valueFieldTypes.includes(field.field_type)) {
        fieldOptionsConditions[key] = label;
      }
      if (valueFieldTypes.includes(field.field_type) || nonValueFieldTypes.includes(field.field_type)) {
        fieldOptionsActions[key] = label;
      }
    });

    var jQueryhiddenSelect = jQuery('.ppsgenClHiddenFieldsSelect');
    var selected = jQueryhiddenSelect.val() || [];
    jQueryhiddenSelect.empty();
    jQuery.each(fieldOptionsActions, function (value, label) {
      jQueryhiddenSelect.append('<option value="' + value + '">' + label + '</option>');
    });
    var newSelected = selected.filter((val) => fieldOptionsActions[val]);
    jQueryhiddenSelect.val(newSelected);
    jQueryhiddenSelect.ppsgenselect();

    jQuery('.ppsgenClConditionRow').each(function () {
      var jQueryrow = jQuery(this);
      if (jQueryrow.find('.ppsgenClTypeSelect').val() === 'field') {
        var jQuerysubType = jQueryrow.find('.ppsgenClSubTypeSelect');
        var oldVal = jQuerysubType.val();
        jQuerysubType.empty();
        jQuery.each(fieldOptionsConditions, function (value, label) {
          jQuerysubType.append('<option value="' + value + '">' + label + '</option>');
        });
        if (fieldOptionsConditions[oldVal]) {
          jQuerysubType.val(oldVal);
        } else {
          var jQuerywrapper = jQueryrow.parent();
          var jQueryprevOperator = jQueryrow.prev('.ppsgenClOperatorBetween');
          if (jQueryprevOperator.length) {
            jQueryprevOperator.remove();
          }
          jQueryrow.remove();
          while (jQuerywrapper.children().first().hasClass('ppsgenClOperatorBetween')) {
            jQuerywrapper.children().first().remove();
          }
        }
      }
    });

    jQuery('.ppsgenClActionRow').each(function () {
      var jQueryrow = jQuery(this);
      if (jQueryrow.find('.ppsgenClTypeSelect').val() === 'field') {
        var jQuerysubType = jQueryrow.find('.ppsgenClSubTypeSelect');
        var oldVal = jQuerysubType.val();
        jQuerysubType.empty();
        jQuery.each(fieldOptionsActions, function (value, label) {
          jQuerysubType.append('<option value="' + value + '">' + label + '</option>');
        });
        if (fieldOptionsActions[oldVal]) {
          jQuerysubType.val(oldVal);
          var currentOp = jQueryrow.find('.ppsgenClOperatorSelect').val() || 'Show';
          updateActionOperator(jQueryrow, currentOp);
        } else {
          jQueryrow.remove();
        }
      }
    });

    saveLogicData();
  }

  jQuery(document).on('ppsgenFieldsChanged', rebuildFieldSelects);
});
