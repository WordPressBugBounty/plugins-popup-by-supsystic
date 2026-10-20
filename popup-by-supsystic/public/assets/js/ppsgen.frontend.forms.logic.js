// Form conditional logic and the rating field (Free since the Free-to-PRO review)
jQuery(document).ready(function (jQuery) {
  window.ppsgenFormDataConditionalLogicUserLoggedIn = '';
  window.ppsgenFormDataConditionalLogicUserLang = '';

  jQuery('.ppsgenForm').each(function () {
    var jQueryform = jQuery(this);
    var formId = jQueryform.data('form-id');
    var clData = window['ppsgenFormDataConditionaLogic' + formId] || { logic_data: [], hidden_fields: [], user_logged_in: '', user_lang: '' };
    if (clData.user_logged_in) {
      window.ppsgenFormDataConditionalLogicUserLoggedIn = clData.user_logged_in ?? '';
    }
    if (clData.user_lang) {
      window.ppsgenFormDataConditionalLogicUserLang = clData.user_lang ?? '';
    }
    if (!clData.logic_data.length && !clData.hidden_fields.length) return;
    jQueryform.on('change keyup', 'input, select, textarea', function () {
      applyConditionalLogic(jQueryform, clData.logic_data, clData.hidden_fields);
    });
    applyConditionalLogic(jQueryform, clData.logic_data, clData.hidden_fields);
  });

  function applyConditionalLogic(jQueryform, logics, hidden_fields) {
    var afterSubmitRedirect = null;
    jQueryform.find('.ppsgen-form-field-container').show();
    jQuery.each(hidden_fields, function (i, fieldKey) {
      var jQuerycontainer = jQueryform.find('.ppsgen-form-field-container:has([data-fkey="' + fieldKey + '"])');
      jQuerycontainer.hide();
    });

    jQuery.each(logics, function (i, logic) {
      var conditionsMet = evaluateConditions(jQueryform, logic.conditions);

      if (conditionsMet) {
        jQuery.each(logic.actions, function (j, act) {
          if (act.type === 'field') {
            var el = jQueryform.find('[data-fkey="' + act.subType + '"]');
            var target = el.closest('.ppsgen-form-field-container');
            switch (act.operator) {
              case 'Show':
                target.show();
                break;
              case 'Hide':
                target.hide();
                break;
              case 'Set Value':
                setFieldValue(el, act.value);
                break;
              case 'Add Value':
                var val = getFieldValue(el);
                if (!isNaN(val) && !isNaN(act.value)) {
                  val = parseFloat(val) + parseFloat(act.value);
                } else {
                  val += ' ' + act.value;
                }
                setFieldValue(el, val);
                break;
              case 'Subtract Value':
                var val = getFieldValue(el);
                if (!isNaN(val) && !isNaN(act.value)) {
                  val = parseFloat(val) - parseFloat(act.value);
                } else {
                  val += ' ' + act.value;
                }
                setFieldValue(el, val);
                break;
            }
          } else if (act.type === 'redirect') {
            if (act.afterSubmit) {
              afterSubmitRedirect = act.value;
            } else {
              window.location.href = act.value;
            }
          }
        });
      }
    });
    if (afterSubmitRedirect) {
      jQueryform.data('after-submit-redirect', afterSubmitRedirect);
    } else {
      jQueryform.removeData('after-submit-redirect');
    }
  }
  function getFieldValue(el) {
    if (el.is(':radio')) {
      return el.filter(':checked').val() || '';
    } else if (el.is(':checkbox')) {
      return el
        .filter(':checked')
        .map(function () {
          return jQuery(this).val();
        })
        .get();
    } else {
      return el.val() || '';
    }
  }
  function setFieldValue(el, value) {
    if (el.is(':radio')) {
      el.filter('[value="' + value + '"]').prop('checked', true);
    } else if (el.is(':checkbox')) {
      if (Array.isArray(value)) {
        el.each(function () {
          jQuery(this).prop('checked', value.includes(jQuery(this).val()));
        });
      } else {
        el.prop('checked', !!value);
      }
    } else {
      el.val(value);
    }
  }
  function evaluateConditions(jQueryform, conditions) {
    if (!conditions.length) return true;
    var met = checkCondition(jQueryform, conditions[0]);
    for (var i = 1; i < conditions.length; i++) {
      var nextMet = checkCondition(jQueryform, conditions[i]);
      var op = conditions[i].operator_between || 'AND';
      if (op === 'AND') {
        met = met && nextMet;
      } else if (op === 'OR') {
        met = met || nextMet;
      }
    }
    return met;
  }
  function checkCondition(jQueryform, cond) {
    if (cond.type === 'field') {
      var el = jQueryform.find('[data-fkey="' + cond.subType + '"]');
      var val = getFieldValue(el);
      if (Array.isArray(val)) {
        switch (cond.operator) {
          case 'Equal':
            return val.includes(cond.value);
          case 'Not Equal':
            return !val.includes(cond.value);
          case 'Like':
            return val.some((v) => v.indexOf(cond.value) > -1);
          case 'More':
            return val.some((v) => parseFloat(v) > parseFloat(cond.value));
          case 'Less':
            return val.some((v) => parseFloat(v) < parseFloat(cond.value));
          default:
            return false;
        }
      } else {
        switch (cond.operator) {
          case 'Equal':
            return val == cond.value;
          case 'Not Equal':
            return val != cond.value;
          case 'Like':
            return val.indexOf(cond.value) > -1;
          case 'More':
            return parseFloat(val) > parseFloat(cond.value);
          case 'Less':
            return parseFloat(val) < parseFloat(cond.value);
          default:
            return false;
        }
      }
    } else if (cond.type === 'user') {
      switch (cond.subType) {
        case 'Url':
          var currentUrl = window.location.href;
          switch (cond.operator) {
            case 'Equal':
              return currentUrl === cond.value;
            case 'Not Equal':
              return currentUrl !== cond.value;
            case 'Like':
              return currentUrl.indexOf(cond.value) > -1;
            default:
              return false;
          }
        case 'Language':
          var currentLang = window.ppsgenFormDataConditionalLogicUserLang || '';
          switch (cond.operator) {
            case 'Equal':
              return currentLang === cond.value;
            case 'Not Equal':
              return currentLang !== cond.value;
            default:
              return false;
          }
        case 'Logged In':
          return !!window.ppsgenFormDataConditionalLogicUserLoggedIn;
        case 'Not Logged In':
          return !window.ppsgenFormDataConditionalLogicUserLoggedIn;
        default:
          return false;
      }
    }
    return false;
  }
});

(function (jQuery) {
  jQuery('.ppsgen-form-input-rating-wrapper').each(function () {
    var wrapper = jQuery(this);
    var labels = wrapper.find('label');
    var inputs = wrapper.find('input[type=radio]');
    var icons = wrapper.find('i');
    function setActiveClass(index) {
      icons.each(function (i) {
        jQuery(this).toggleClass('ppsgen-form-input-rating-icon-active', i <= index);
      });
    }
    labels.on('mouseenter', function () {
      var i = labels.index(this);
      setActiveClass(i);
    });
    labels.on('mouseleave', function () {
      var checkedIndex = inputs.index(inputs.filter(':checked'));
      setActiveClass(checkedIndex);
    });
    labels.on('click', function () {
      var i = labels.index(this);
      inputs.prop('checked', false);
      inputs.eq(i).prop('checked', true);
      setActiveClass(i);
      inputs.eq(i).trigger('change');
    });
    var checkedIndex = inputs.index(inputs.filter(':checked'));
    setActiveClass(checkedIndex);
  });
})(jQuery);
