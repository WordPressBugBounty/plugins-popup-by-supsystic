var g_ppsCurrTour = null;
var g_ppsTourOpenedWithTab = false;
var g_ppsAdminTourDismissed = false;

jQuery(function (jQuery) {
  if (typeof ppsAdminTourData === "undefined" || !ppsAdminTourData.tour) {
    return;
  }

  setTimeout(function () {
    jQuery("body").append(ppsAdminTourData.html);

    ppsAdminTourData._$ = jQuery("#supsystic-admin-tour");

    initTourTabs();
    openFirstPointer();
  }, 500);

  jQuery(document).on("click", ".supsystic-tour-btns .close", function (e) {
    e.preventDefault();

    if (!g_ppsCurrTour) return;

    sendTourAction("closeTour");

    g_ppsCurrTour.element.pointer("close");

    g_ppsAdminTourDismissed = true;
  });

  jQuery(document).on("click", ".supsystic-tour-finish-btn", function (e) {
    e.preventDefault();

    if (!g_ppsCurrTour) return;

    sendTourAction("addTourFinish");

    g_ppsCurrTour.element.pointer("close");
  });

  jQuery(document).on("click", ".supsystic-tour-next-btn", function (e) {
    var url = jQuery(this).attr("href");

    if (url && url !== "#") {
      e.preventDefault();

      if (!g_ppsCurrTour) return;

      jQuery.sendFormPps({
        msgElID: "noMessages",
        data: {
          mod: "supsystic_promo",
          _wpnonce: ppsAdminTourData.nonce,
          action: "addTourStep",
          tourId: g_ppsCurrTour._tourId,
          pointId: g_ppsCurrTour._pointId,
        },
        onSuccess: function () {
          window.location.href = url;
        },
      });
    }
  });
});

function initTourTabs() {
  jQuery.each(ppsAdminTourData.tour, function (tourId, tour) {
    if (!tour.points) return;

    jQuery.each(tour.points, function (pointId, point) {
      if (!point.sub_tab) return;

      var selector = 'a[href="' + point.sub_tab + '"]';

      jQuery(selector).data("tourId", tourId).data("pointId", pointId);

      var eventName = point.sub_tab.replace("#", "") + "_tabSwitch";

      jQuery(document).on(eventName, function (e, tab) {
        if (g_ppsTourOpenedWithTab || g_ppsAdminTourDismissed) return;

        var $tab = jQuery('a[href="' + tab + '"]');

        _ppsOpenPointer($tab.data("tourId"), $tab.data("pointId"));
      });
    });
  });
}

function openFirstPointer() {
  var firstTour = Object.keys(ppsAdminTourData.tour)[0];

  if (!firstTour) return;

  var firstPoint = Object.keys(ppsAdminTourData.tour[firstTour].points)[0];

  if (!firstPoint) return;

  _ppsOpenPointer(firstTour, firstPoint);
}

function _ppsOpenPointerAndPopupTab(tourId, pointId, tab) {
  g_ppsTourOpenedWithTab = true;

  jQuery("#ppsPopupEditTabs").wpTabs("activate", tab);

  _ppsOpenPointer(tourId, pointId);

  g_ppsTourOpenedWithTab = false;
}

function _ppsOpenPointer(tourId, pointId) {
  var point = ppsAdminTourData.tour[tourId].points[pointId];

  if (!point) return;

  if (!jQuery(point.target).length) return;

  if (g_ppsCurrTour) {
    sendTourStep(g_ppsCurrTour._tourId, g_ppsCurrTour._pointId);

    g_ppsCurrTour.element.pointer("close");

    g_ppsCurrTour = null;
  }

  if (
    point.sub_tab &&
    jQuery("#ppsPopupEditTabs").wpTabs("getActiveTab") != point.sub_tab
  ) {
    return;
  }

  var $content = ppsAdminTourData._$.find(
    "#supsystic-" + tourId + "-" + pointId
  );

  var options = jQuery.extend({}, point.options, {
    content: $content.find(".supsystic-tour-content").html(),

    pointerClass: "wp-pointer supsystic-pointer",

    buttons: function (event, t) {
      g_ppsCurrTour = t;

      g_ppsCurrTour._tourId = tourId;
      g_ppsCurrTour._pointId = pointId;

      return $content.find(".supsystic-tour-btns");
    },
  });

  jQuery(point.target).pointer(options).pointer("open");

  var pointerTop = parseInt(g_ppsCurrTour.pointer.css("top"));

  if (!isNaN(pointerTop) && pointerTop < 10) {
    g_ppsCurrTour.pointer.css("top", "10px");
  }
}

function sendTourStep(tourId, pointId) {
  jQuery.sendFormPps({
    msgElID: "noMessages",
    data: {
      mod: "supsystic_promo",
      _wpnonce: ppsAdminTourData.nonce,
      action: "addTourStep",
      tourId: tourId,
      pointId: pointId,
    },
  });
}

function sendTourAction(action) {
  if (!g_ppsCurrTour) return;

  jQuery.sendFormPps({
    msgElID: "noMessages",
    data: {
      mod: "supsystic_promo",
      _wpnonce: ppsAdminTourData.nonce,
      action: action,
      tourId: g_ppsCurrTour._tourId,
      pointId: g_ppsCurrTour._pointId,
    },
  });
}
