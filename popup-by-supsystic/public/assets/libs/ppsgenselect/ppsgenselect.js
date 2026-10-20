(function (jQuery) {
  function ppsgenSelect(element, options) {
    this.element = element;
    this.options = options || {};
    this.init();
  }

  ppsgenSelect.prototype.init = function () {
    this.container = jQuery('<div class="ppsgenselect-container"></div>');
    this.drop = jQuery('<div class="ppsgenselect-drop"></div>');
    this.results = jQuery('<ul class="ppsgenselect-results"></ul>');
    this.search = jQuery('<div class="ppsgenselect-search"><span class="fappsgen9 fappsgen-search"></span><input class="ppsgenselect-search-input" type="text" autocomplete="off" placeholder="' + (jQuery(this.element).attr('data-placeholder') || 'Select options') + '" /></div>');
    this.single = jQuery('<div class="ppsgenselect-single"><span></span></div>');

    this.container.append(this.single);
    this.container.append(this.drop);
    this.drop.append(this.search);
    this.drop.append(this.results);

    jQuery(this.element).after(this.container).hide();

    this.bindEvents();
    this.updateResults();
    this.updateSingleText();

    this.observeSelectChanges();
  };

  ppsgenSelect.prototype.bindEvents = function () {
    var self = this;

    this.single.on('click', function () {
      self.toggleDropdown();
    });

    this.search.find('input').on('keyup', function () {
      self.filterResults(jQuery(this).val());
    });

    this.results.on('click', 'li', function () {
      self.selectResult(jQuery(this));
    });

    jQuery(document).on('click', function (event) {
      if (!jQuery(event.target).closest('.ppsgenselect-container').length) {
        self.closeDropdown();
      }
    });
  };

  ppsgenSelect.prototype.toggleDropdown = function () {
    this.container.toggleClass('ppsgenselect-with-drop');
  };

  ppsgenSelect.prototype.closeDropdown = function () {
    this.container.removeClass('ppsgenselect-with-drop');
  };

  ppsgenSelect.prototype.updateResults = function () {
    var self = this;
    this.results.empty();

    jQuery(this.element)
      .find('option')
      .each(function () {
        var option = jQuery(this);
        var thumb = option.data('thumb') || '';
        var priceCurrent = option.attr('data-price-current') || '';
        var priceOld = option.attr('data-price-old') || '';
        var li = jQuery('<li class="active-result"></li>').data('value', option.val());
        if (thumb) {
          li.append(jQuery('<span class="ppsgenselect-thumb"><img alt="" /></span>').find('img').attr('src', thumb).end());
        }
        li.append(jQuery('<span class="ppsgenselect-label"></span>').text(option.text()));
        if (priceCurrent || priceOld) {
          var priceWrap = jQuery('<span class="ppsgenselect-price"></span>');
          if (priceOld) {
            priceWrap.append(jQuery('<del></del>').text(priceOld));
          }
          if (priceCurrent) {
            priceWrap.append(jQuery('<ins></ins>').text(priceCurrent));
          }
          li.append(priceWrap);
        }
        if (option.is(':disabled')) {
          li.addClass('disabled').addClass('ppsgenselect-disabled');
        } else if (option.is(':selected')) {
          li.addClass('highlighted');
        }
        self.results.append(li);
      });
  };

  ppsgenSelect.prototype.filterResults = function (term) {
    var self = this;
    this.results.find('li').each(function () {
      var li = jQuery(this);
      if (li.text().toLowerCase().indexOf(term.toLowerCase()) >= 0) {
        li.show();
      } else {
        li.hide();
      }
    });
  };

  ppsgenSelect.prototype.selectResult = function (li) {
    if (li.hasClass('disabled') || li.hasClass('ppsgenselect-disabled')) {
      return;
    }
    var value = li.data('value');
    var option = jQuery(this.element).find('option[value="' + value + '"]');
    var isMultiple = jQuery(this.element).prop('multiple');
    var isSingleSelect = jQuery(this.element).attr('data-single') === '1';

    jQuery(this.element).find('option:disabled:selected').prop('selected', false);

    if (isMultiple && !isSingleSelect) {
      option.prop('selected', !option.prop('selected'));
      li.toggleClass('highlighted');
    } else {
      jQuery(this.element).find('option').prop('selected', false);
      option.prop('selected', true);
      this.results.find('li').removeClass('highlighted');
      li.addClass('highlighted');
      this.closeDropdown();
    }

    jQuery(this.element).trigger('change');
    this.updateResults();
    this.updateSingleText();
  };

  ppsgenSelect.prototype.updateSingleText = function () {
    var selectedOptions = jQuery(this.element).find('option:selected');
    var selectedItems = selectedOptions
      .map(function () {
        return {
          text: jQuery(this).text(),
          thumb: jQuery(this).data('thumb') || '',
          priceCurrent: jQuery(this).attr('data-price-current') || '',
          priceOld: jQuery(this).attr('data-price-old') || '',
        };
      })
      .get();

    var selectedTexts = selectedItems.map(function (item) {
      return item.text;
    });
    var displayText = selectedTexts.slice(0, 3).join(', ');
    if (selectedTexts.length > 3) {
      displayText += '...';
    }
    this.single.find('.ppsgenselect-single-thumb').remove();
    this.single.find('.ppsgenselect-single-price').remove();
    if (selectedItems.length === 1 && selectedItems[0].thumb) {
      this.single.prepend(jQuery('<span class="ppsgenselect-single-thumb"><img alt="" /></span>').find('img').attr('src', selectedItems[0].thumb).end());
    }
    this.single.find('span').text(displayText || jQuery(this.element).attr('data-placeholder') || 'Select options');
    if (selectedItems.length === 1 && (selectedItems[0].priceCurrent || selectedItems[0].priceOld)) {
      var singlePriceWrap = jQuery('<span class="ppsgenselect-single-price"></span>');
      if (selectedItems[0].priceOld) {
        singlePriceWrap.append(jQuery('<del></del>').text(selectedItems[0].priceOld));
      }
      if (selectedItems[0].priceCurrent) {
        singlePriceWrap.append(jQuery('<ins></ins>').text(selectedItems[0].priceCurrent));
      }
      this.single.append(singlePriceWrap);
    }
  };

  ppsgenSelect.prototype.observeSelectChanges = function () {
    var self = this;

    jQuery(this.element).on('change', function () {
      self.updateResults();
      self.updateSingleText();
    });
  };

  jQuery.fn.ppsgenselect = function (options) {
    return this.each(function () {
      if (!jQuery(this).data('ppsgenselect')) {
        jQuery(this).data('ppsgenselect', new ppsgenSelect(this, options));
      }
    });
  };
})(jQuery);

jQuery(document).ready(function () {
  jQuery('.ppsgenselect').ppsgenselect();
});
