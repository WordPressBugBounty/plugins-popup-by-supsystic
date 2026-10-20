jQuery(document).on('click', '.ppsgenEcaSubmitUnsubscribe', function (e) {
  e.preventDefault();
  const button = jQuery(this);
  const form = button.closest('form');
  if (!form.length) {
    return;
  }
  button.prop('disabled', true).addClass('is-loading');
  const formData = new FormData(form[0]);
  jQuery.ajax({
    url: ppsgenFrontendAjax.url,
    type: 'POST',
    data: formData,
    processData: false,
    contentType: false,
    success: function (response) {
      if (response && response.success) {
        form.replaceWith(response?.data?.message);
      } else {
        form.replaceWith(response?.data?.message);
      }
    },
    error: function () {
      alert('Network error. Please try again later.');
    },
  });
});
