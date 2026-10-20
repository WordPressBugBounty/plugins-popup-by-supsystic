jQuery(document).ready(function ($) {
  function debounce(fn, delay) {
    var timer = null;
    return function () {
      var context = this;
      var args = arguments;
      clearTimeout(timer);
      timer = setTimeout(function () {
        fn.apply(context, args);
      }, delay);
    };
  }

  function getI18n(key, fallback) {
    if (typeof ppsgenContactLookupI18n !== 'undefined' && ppsgenContactLookupI18n[key]) {
      return ppsgenContactLookupI18n[key];
    }
    return fallback;
  }

  function escapeHtml(value) {
    return $('<div>').text(value || '').html();
  }

  function escapeAttr(value) {
    return String(value || '')
      .replace(/&/g, '&amp;')
      .replace(/"/g, '&quot;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;');
  }

  function getDropdown($lookup) {
    return $lookup.find('.ppsgenContactLookupDropdown').first();
  }

  function getValueInput($lookup) {
    return $lookup.find('.ppsgenContactLookupValue').first();
  }

  function getSearchInput($lookup) {
    return $lookup.find('.ppsgenContactLookupSearch').first();
  }

  function hideDropdown($lookup) {
    getDropdown($lookup).attr('hidden', true).empty();
    $lookup.removeClass('is-open is-loading');
  }

  function renderDropdownMessage($lookup, message, cssClass) {
    var $dropdown = getDropdown($lookup);
    $dropdown
      .html('<div class="ppsgenContactLookupMessage ' + cssClass + '"></div>')
      .find('.ppsgenContactLookupMessage')
      .text(message);
    $dropdown.removeAttr('hidden');
    $lookup.addClass('is-open');
  }

  function selectLookupItem($lookup, item) {
    var id = parseInt(item && item.id ? item.id : 0, 10);
    var label = item && item.label ? String(item.label) : '';
    getValueInput($lookup).val(id > 0 ? id : '');
    getSearchInput($lookup).val(label).attr('data-selected-label', label);
    $lookup.data('selected-id', id > 0 ? id : 0);
    $lookup.data('selected-label', label);
    hideDropdown($lookup);
  }

  function clearLookupSelectionIfNeeded($lookup) {
    var $search = getSearchInput($lookup);
    var currentValue = $.trim($search.val() || '');
    var selectedLabel = String($search.attr('data-selected-label') || '');
    if (currentValue === selectedLabel) {
      return;
    }
    getValueInput($lookup).val('');
    $search.attr('data-selected-label', '');
    $lookup.data('selected-id', 0);
    $lookup.data('selected-label', '');
  }

  function renderLookupResults($lookup, items) {
    var $dropdown = getDropdown($lookup);
    if (!items || !items.length) {
      renderDropdownMessage($lookup, getI18n('no_results', 'No matching contacts found.'), 'is-empty');
      return;
    }
    var html = '<div class="ppsgenContactLookupResults">';
    items.forEach(function (item) {
      var id = parseInt(item && item.id ? item.id : 0, 10);
      if (!id) {
        return;
      }
      var label = item && item.label ? String(item.label) : String(id);
      html += '<button type="button" class="ppsgenContactLookupResult" data-contact-id="' + id + '" data-contact-label="' + escapeAttr(label) + '">' + escapeHtml(label) + '</button>';
    });
    html += '</div>';
    $dropdown.html(html).removeAttr('hidden');
    $lookup.addClass('is-open');
  }

  function performLookupSearch($lookup, query) {
    var minLength = parseInt($lookup.data('min-length'), 10) || 2;
    var limit = parseInt($lookup.data('limit'), 10) || 10;
    var searchAction = $lookup.data('search-action') || 'searchContactLookup';
    query = $.trim(String(query || ''));

    if (query.length < minLength) {
      hideDropdown($lookup);
      return;
    }

    var activeRequest = $lookup.data('lookup-xhr');
    if (activeRequest && typeof activeRequest.abort === 'function') {
      activeRequest.abort();
    }

    $lookup.addClass('is-loading');
    renderDropdownMessage($lookup, getI18n('searching', 'Searching contacts...'), 'is-loading');

    var xhr = $.post(ajaxurl, {
      action: searchAction,
      mod: 'contact',
      plugin: 'ppsgen',
      query: query,
      limit: limit,
      nonce_code: typeof ppsgenAjax !== 'undefined' ? ppsgenAjax.nonce : '',
    });

    $lookup.data('lookup-xhr', xhr);

    xhr.done(function (response) {
      if (!response || response.success !== true || !response.data) {
        renderDropdownMessage($lookup, getI18n('search_error', 'Unable to search contacts right now.'), 'is-error');
        return;
      }
      renderLookupResults($lookup, response.data.items || []);
    }).fail(function (jqXHR, textStatus) {
      if (textStatus === 'abort') {
        return;
      }
      renderDropdownMessage($lookup, getI18n('search_error', 'Unable to search contacts right now.'), 'is-error');
    }).always(function () {
      $lookup.removeClass('is-loading');
      $lookup.removeData('lookup-xhr');
    });
  }

  var debouncedSearch = debounce(function ($lookup, query) {
    performLookupSearch($lookup, query);
  }, 220);

  $(document).on('input', '.ppsgenContactLookupSearch', function () {
    var $lookup = $(this).closest('.ppsgenContactLookup');
    clearLookupSelectionIfNeeded($lookup);
    debouncedSearch($lookup, $(this).val());
  });

  $(document).on('focus', '.ppsgenContactLookupSearch', function () {
    var $lookup = $(this).closest('.ppsgenContactLookup');
    var value = $.trim($(this).val() || '');
    var minLength = parseInt($lookup.data('min-length'), 10) || 2;
    if (value.length >= minLength && getValueInput($lookup).val() === '') {
      debouncedSearch($lookup, value);
    }
  });

  $(document).on('keydown', '.ppsgenContactLookupSearch', function (e) {
    if (e.key !== 'Enter' && e.keyCode !== 13) {
      return;
    }
    var $lookup = $(this).closest('.ppsgenContactLookup');
    var $firstResult = getDropdown($lookup).find('.ppsgenContactLookupResult').first();
    if ($firstResult.length) {
      e.preventDefault();
      $firstResult.trigger('click');
    }
  });

  $(document).on('click', '.ppsgenContactLookupResult', function (e) {
    e.preventDefault();
    var $result = $(this);
    var $lookup = $result.closest('.ppsgenContactLookup');
    selectLookupItem($lookup, {
      id: $result.data('contact-id'),
      label: $.trim($result.text() || ''),
    });
  });

  $(document).on('click', function (e) {
    var $target = $(e.target);
    if ($target.closest('.ppsgenContactLookup').length) {
      return;
    }
    $('.ppsgenContactLookup').each(function () {
      hideDropdown($(this));
    });
  });
});
