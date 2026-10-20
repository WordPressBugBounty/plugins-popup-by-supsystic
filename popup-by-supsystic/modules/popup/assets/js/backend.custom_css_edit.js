jQuery(function ($) {
  if (!window.wp || !wp.codeEditor || !window.ppsgenPopupCustomCssEditor) {
    return;
  }

  var textarea = document.querySelector('textarea[name="data[popup][settings][custom_css]"]');
  if (!textarea || textarea.dataset.ppsgenEditorInit) {
    return;
  }

  var editor = wp.codeEditor.initialize(textarea, window.ppsgenPopupCustomCssEditor.css || {});
  if (editor.codemirror) {
    editor.codemirror.on('change', function (codeMirror) {
      textarea.value = codeMirror.getValue();
    });
  }
  textarea.dataset.ppsgenEditorInit = '1';

  $(document).on('click', '.ppsgenObjEditTabButton[data-tab="custom_css"]', function () {
    setTimeout(function () {
      if (editor.codemirror) {
        editor.codemirror.refresh();
      }
    }, 0);
  });
});
