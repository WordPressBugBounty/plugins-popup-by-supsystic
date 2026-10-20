(function (window, document) {
  'use strict';

  function getCookie(name) {
    var match = document.cookie.match(new RegExp('(?:^|; )' + name.replace(/[.$?*|{}()[\]\\/+^]/g, '\\$&') + '=([^;]*)'));
    return match ? decodeURIComponent(match[1]) : '';
  }

  window.ppsgenGetEcaAttributionToken = function () {
    try {
      return window.localStorage.getItem('ppsgen_eca_attribution') || getCookie('ppsgen_eca_attribution') || '';
    } catch (e) {
      return getCookie('ppsgen_eca_attribution') || '';
    }
  };

  // "Who sees it": the signed visitor token lives in a cookie (read by the server) and in localStorage,
  // so a returning contact is still recognised after the cookie was cleared or expired
  (function syncVisitorToken() {
    var name = 'ppsgen_visitor';
    try {
      var fromCookie = getCookie(name);
      var stored = window.localStorage.getItem(name) || '';
      if (fromCookie && fromCookie !== stored) {
        window.localStorage.setItem(name, fromCookie);
      } else if (!fromCookie && stored) {
        var path = (window.ppsgenFrontendAjax && window.ppsgenFrontendAjax.cookiePath) || '/';
        document.cookie = name + '=' + encodeURIComponent(stored) + '; path=' + path + '; max-age=' + 365 * 86400 + '; SameSite=Lax' + (window.location.protocol === 'https:' ? '; Secure' : '');
      }
    } catch (e) {}
  })();

  window.ppsgenAttachEcaAttributionToForm = function (form) {
    var token = window.ppsgenGetEcaAttributionToken();
    if (!token || !form) {
      return false;
    }
    var field = form.querySelector('input[name="ppsgen_eca_attribution"]');
    if (!field) {
      field = document.createElement('input');
      field.type = 'hidden';
      field.name = 'ppsgen_eca_attribution';
      form.appendChild(field);
    }
    field.value = token;
    return true;
  };
})(window, document);
