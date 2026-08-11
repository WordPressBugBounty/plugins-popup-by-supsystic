jQuery(document).ready(function () {
  jQuery('.ppsSmStyleColorSelect')
    .change(function () {
      if (jQuery(this).val() === 'custom') {
        jQuery('.ppsSmCustomStyleRow').show();
      } else {
        jQuery('.ppsSmCustomStyleRow').hide();
      }
    })
    .change();
});
