/* Forms › Move from Contact Form */
(function ($) {
  'use strict';

  var cfg = window.ppsgenCfImport || {};
  var t = cfg.i18n || {};
  var data = window.ppsgenCfImportData || { scan: null, preselect: [] };

  function fmt(str) {
    var args = Array.prototype.slice.call(arguments, 1);
    var i = 0;
    return String(str || '').replace(/%%|%(\d+\$)?[sd]/g, function (m, pos) {
      if (m === '%%') {
        return '%';
      }
      var idx = pos ? parseInt(pos, 10) - 1 : i++;
      return args[idx] !== undefined ? args[idx] : '';
    });
  }

  function esc(str) {
    return String(str === undefined || str === null ? '' : str).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  function plural(n, one, many) {
    return n === 1 ? one : fmt(many, n);
  }

  function post(action, payload) {
    return $.ajax({
      url: cfg.ajaxUrl,
      method: 'POST',
      dataType: 'json',
      data: $.extend({ action: action, plugin: 'ppsgen', mod: 'form/cf_import', nonce_code: cfg.nonce }, payload || {}),
    });
  }

  $(function () {
    var $root = $('[data-ppsgen-cfi]');
    if (!$root.length) {
      return;
    }
    var $list = $root.find('[data-ppsgen-cfi-list]');
    var $notes = $root.find('[data-ppsgen-cfi-notes]');
    var $status = $root.find('[data-ppsgen-cfi-status]');
    var $moveAll = $root.find('[data-ppsgen-cfi-move="all"]');
    var $moveSel = $root.find('[data-ppsgen-cfi-move="selected"]');
    var $selectAll = $root.find('[data-ppsgen-cfi-selectall]');
    var $results = $root.find('[data-ppsgen-cfi-results]');
    var $scList = $root.find('[data-ppsgen-cfi-sc-list]');
    var $scReplace = $root.find('[data-ppsgen-cfi-sc="replace"]');
    var $scUndo = $root.find('[data-ppsgen-cfi-sc="undo"]');
    var $scStatus = $root.find('[data-ppsgen-cfi-sc-status]');
    var scan = data.scan;
    var preselect = (data.preselect || []).map(Number);

    function option(name) {
      return $root.find('[data-ppsgen-cfi-opt="' + name + '"]').prop('checked') ? '1' : '0';
    }

    function syncOptions() {
      var subs = option('submissions') === '1';
      var $row = $root.find('[data-ppsgen-cfi-contacts-row]');
      $row.toggleClass('is-off', !subs).find('input').prop('disabled', !subs);
    }

    function selectedIds() {
      return $list
        .find('[data-ppsgen-cfi-pick]:checked')
        .map(function () {
          return parseInt(this.value, 10);
        })
        .get();
    }

    function updateButtons() {
      var forms = (scan && scan.forms) || [];
      var waiting = forms.filter(function (f) {
        return !f.cesId || (f.newSubmissions > 0 && option('submissions') === '1');
      }).length;
      var sel = selectedIds().length;
      $moveAll.find('[data-ppsgen-cfi-label]').text(forms.length > 1 ? fmt(t.moveAllN, forms.length) : t.moveAll);
      $moveAll.prop('disabled', !forms.length || !waiting);
      $moveSel.find('[data-ppsgen-cfi-label]').text(fmt(t.moveSelected, sel));
      $moveSel.prop('disabled', !sel);
      $selectAll.prop('checked', forms.length > 0 && sel === forms.length);
      $root.find('[data-ppsgen-cfi-count="forms"]').text(forms.length);
      $root.find('[data-ppsgen-cfi-count="moved"]').text(
        forms.filter(function (f) {
          return f.cesId;
        }).length
      );
    }

    function renderList() {
      if (!scan || !scan.sourceExists) {
        $list.html('<p class="ppsgen-cfi-empty">' + esc(t.noSource) + '</p>');
        updateButtons();
        return;
      }
      if (!scan.forms.length) {
        $list.html('<p class="ppsgen-cfi-empty">' + esc(t.empty) + '</p>');
        updateButtons();
        return;
      }
      var checkedBefore = {};
      var hadChecks = $list.find('[data-ppsgen-cfi-pick]').length > 0;
      $list.find('[data-ppsgen-cfi-pick]').each(function () {
        checkedBefore[this.value] = this.checked;
      });
      var html = scan.forms
        .map(function (f) {
          var checked = hadChecks ? !!checkedBefore[f.id] : preselect.length ? preselect.indexOf(f.id) !== -1 : true;
          var meta = [];
          if (f.template) {
            meta.push(esc(fmt(t.template, f.template)));
          }
          meta.push(esc(plural(f.fields, t.oneField, t.fields)));
          meta.push(esc(f.submissions ? plural(f.submissions, t.oneSubmission, t.submissions) : t.noSubmissions));
          if (f.pages && f.pages.length) {
            meta.push(
              '<span title="' +
                esc(
                  f.pages
                    .map(function (p) {
                      return p.title;
                    })
                    .join(', ')
                ) +
                '">' +
                esc(plural(f.pages.length, t.onePage, t.pages)) +
                '</span>'
            );
          } else {
            meta.push(esc(t.notOnPages));
          }
          if (f.pro && f.pro.length) {
            meta.push(esc(fmt(t.proUsed, f.pro.join(', '))));
          }
          var side = '';
          if (f.cesId) {
            side += '<span class="ppsgen-cfi-badge is-ok"><span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>' + esc(t.moved) + '</span>';
            if (f.newSubmissions > 0) {
              side += '<span class="ppsgen-cfi-badge is-warn">' + esc(fmt(t.newSubmissions, f.newSubmissions)) + '</span>';
            }
            side += '<a class="ppsgen-cfi-btn ppsgen-cfi-btn-sm" href="' + esc(f.editUrl) + '">' + esc(fmt(t.cesForm, f.cesId)) + '</a>';
          } else {
            side += '<span class="ppsgen-cfi-badge is-ready">' + esc(t.ready) + '</span>';
          }
          if (!f.active) {
            side += '<span class="ppsgen-cfi-badge">' + esc(t.inactive) + '</span>';
          }
          return (
            '<div class="ppsgen-cfi-row' +
            (f.cesId ? ' is-moved' : '') +
            '" role="listitem">' +
            '<label class="ppsgen-cfi-check"><input type="checkbox" data-ppsgen-cfi-pick value="' +
            f.id +
            '"' +
            (checked ? ' checked' : '') +
            ' aria-label="' +
            esc(f.name) +
            '"></label>' +
            '<div class="ppsgen-cfi-row-main"><div class="ppsgen-cfi-row-title">' +
            esc(f.name) +
            ' <span class="ppsgen-cfi-row-id">#' +
            f.id +
            '</span></div><div class="ppsgen-cfi-row-meta">' +
            meta
              .map(function (m) {
                return '<span>' + m + '</span>';
              })
              .join('') +
            '</div></div>' +
            '<div class="ppsgen-cfi-row-side">' +
            side +
            '</div></div>'
          );
        })
        .join('');
      $list.html(html);

      var notes = [];
      if (scan.abVariants) {
        notes.push(plural(scan.abVariants, t.abVariantsOne, t.abVariants));
      }
      if (scan.popups) {
        notes.push(plural(scan.popups, t.popupsOne, t.popups));
      }
      if (scan.widgets) {
        notes.push(plural(scan.widgets, t.widgetsOne, t.widgets));
      }
      $notes.html(
        notes
          .map(function (n) {
            return '<p class="ppsgen-cfi-note"><span class="dashicons dashicons-info-outline" aria-hidden="true"></span><span>' + esc(n) + '</span></p>';
          })
          .join('')
      );
      updateButtons();
    }

    function renderReport(report) {
      if (!report) {
        return '';
      }
      var moved = (report.moved || [])
        .map(function (m) {
          return '<li>' + esc(m) + '</li>';
        })
        .join('');
      var skipped = (report.skipped || [])
        .map(function (m) {
          return '<li>' + esc(m) + '</li>';
        })
        .join('');
      return (
        '<div class="ppsgen-cfi-cols">' +
        '<div class="ppsgen-cfi-col"><h4><span class="dashicons dashicons-yes" aria-hidden="true"></span>' +
        esc(t.movedList) +
        '</h4><ul>' +
        moved +
        '</ul></div>' +
        '<div class="ppsgen-cfi-col is-skipped"><h4><span class="dashicons dashicons-info-outline" aria-hidden="true"></span>' +
        esc(t.skippedList) +
        '</h4>' +
        (skipped ? '<ul>' + skipped + '</ul>' : '<p>' + esc(t.skippedNone) + '</p>') +
        '</div></div>'
      );
    }

    function renderResults(res) {
      var moved = 0;
      var existed = 0;
      var html = (res.results || [])
        .map(function (r) {
          if (r.status === 'error') {
            return '<div class="ppsgen-cfi-result"><div class="ppsgen-cfi-result-head"><h3>' + esc(r.name) + '</h3><span class="ppsgen-cfi-badge is-warn">' + esc(r.message || t.error) + '</span></div></div>';
          }
          if (r.status === 'moved') {
            moved++;
          } else {
            existed++;
          }
          var subs = '';
          if (r.submissions) {
            subs =
              '<p class="ppsgen-cfi-sub">' +
              esc(r.submissions.moved || r.submissions.contacts ? fmt(t.subsMoved, r.submissions.moved, r.submissions.contacts, r.submissions.linked) : t.subsNone) +
              '</p>';
          }
          return (
            '<div class="ppsgen-cfi-result"><div class="ppsgen-cfi-result-head"><h3>' +
            esc(r.name) +
            '</h3>' +
            (r.status === 'exists' ? '<span class="ppsgen-cfi-badge">' + esc(t.alreadyMoved) + '</span>' : '<span class="ppsgen-cfi-badge is-ok">' + esc(t.moved) + '</span>') +
            '<span class="ppsgen-cfi-result-actions"><span class="ppsgen-cfi-shortcode"><code class="ppsgen-cfi-code">' +
            esc(r.shortcode) +
            '</code><button type="button" class="ppsgen-cfi-btn ppsgen-cfi-btn-sm" data-ppsgen-cfi-copy="' +
            esc(r.shortcode) +
            '">' +
            esc(t.copy) +
            '</button></span><a class="ppsgen-cfi-btn ppsgen-cfi-btn-sm ppsgen-cfi-btn-accent" href="' +
            esc(r.designUrl || r.editUrl) +
            '"><span class="dashicons dashicons-art" aria-hidden="true"></span>' +
            esc(t.chooseTemplate) +
            '</a></span></div>' +
            (r.status === 'moved' ? renderReport(r.report) : '') +
            subs +
            '</div>'
          );
        })
        .join('');
      $results.find('[data-ppsgen-cfi-results-title]').text(fmt(t.resultTitle, moved, existed));
      var lead = t.resultLead;
      if (res.limitMessage) {
        lead += ' ' + res.limitMessage;
      }
      $results.find('[data-ppsgen-cfi-results-lead]').text(lead);
      var coupon = '';
      if (res.coupon && res.coupon.code) {
        coupon =
          '<div class="ppsgen-cfi-coupon"><span class="ppsgen-cfi-coupon-icon dashicons dashicons-tickets-alt" aria-hidden="true"></span>' +
          '<div class="ppsgen-cfi-coupon-text"><b>' +
          esc(fmt(t.couponTitle, res.coupon.code, res.coupon.discount)) +
          '</b><span>' +
          esc(res.coupon.needsPro ? t.couponTextPro : t.couponTextFree) +
          '</span></div><div class="ppsgen-cfi-coupon-actions"><code class="ppsgen-cfi-coupon-code">' +
          esc(res.coupon.code) +
          '</code><button type="button" class="ppsgen-cfi-btn ppsgen-cfi-btn-sm" data-ppsgen-cfi-copy="' +
          esc(res.coupon.code) +
          '">' +
          esc(t.copy) +
          '</button><a class="ppsgen-cfi-btn ppsgen-cfi-btn-sm ppsgen-cfi-btn-accent" href="' +
          esc(res.coupon.url) +
          '" target="_blank" rel="noopener">' +
          esc(t.couponBtn) +
          '</a></div></div>';
      }
      $results.find('[data-ppsgen-cfi-results-list]').html(coupon + html);
      $results.prop('hidden', false);
      $('html, body').animate({ scrollTop: $results.offset().top - 60 }, 300);
    }

    function renderShortcodes() {
      var plan = (scan && scan.shortcodes) || { pending: [], replaced: [] };
      var rows = [];
      var place = function (p, sub) {
        return (
          '<div class="ppsgen-cfi-sc-place">' +
          (p.url ? '<a href="' + esc(p.url) + '" target="_blank" rel="noopener">' + esc(p.title) + '</a>' : '<span>' + esc(p.title) + '</span>') +
          '<span class="ppsgen-cfi-sc-type">' +
          esc(sub) +
          '</span></div>'
        );
      };
      (plan.pending || []).forEach(function (p) {
        var changes = (p.changes || [])
          .map(function (c) {
            return (
              '<li class="ppsgen-cfi-sc-change"><code>' +
              esc(c.from) +
              '</code><span class="ppsgen-cfi-sc-arrow" aria-hidden="true">→</span><code class="is-new">' +
              esc(c.to) +
              '</code></li>'
            );
          })
          .join('');
        var waiting = (p.notMoved || []).length
          ? '<p class="ppsgen-cfi-sc-waiting"><span class="dashicons dashicons-clock" aria-hidden="true"></span>' + esc(t.scWaiting) + ' #' + esc(p.notMoved.join(', #')) + '</p>'
          : '';
        rows.push('<div class="ppsgen-cfi-sc-row" role="listitem">' + place(p, p.type) + '<div class="ppsgen-cfi-sc-changes">' + (changes ? '<ul>' + changes + '</ul>' : '') + waiting + '</div></div>');
      });
      (plan.replaced || []).forEach(function (p) {
        rows.push(
          '<div class="ppsgen-cfi-sc-row is-done" role="listitem">' +
            place(p, p.type + ' · ' + p.at) +
            '<div class="ppsgen-cfi-sc-changes"><span class="ppsgen-cfi-badge is-ok"><span class="dashicons dashicons-yes" aria-hidden="true"></span>' +
            esc(fmt(t.scDoneCount, p.count)) +
            '</span></div></div>'
        );
      });
      if (!rows.length) {
        $scList.html('<p class="ppsgen-cfi-empty">' + esc(t.scNone) + '</p>');
      } else {
        $scList.html(
          '<div class="ppsgen-cfi-sc-list" role="list"><div class="ppsgen-cfi-sc-row ppsgen-cfi-sc-head" aria-hidden="true"><span>' +
            esc(t.edit) +
            '</span><span>' +
            esc((plan.pending || []).length ? t.scColumn : t.scColumnDone) +
            '</span></div>' +
            rows.join('') +
            '</div>'
        );
      }
      var places = (plan.pending || []).filter(function (p) {
        return (p.changes || []).length;
      }).length;
      $scReplace.prop('hidden', !places).find('[data-ppsgen-cfi-label]').text(places === 1 ? t.scReplaceOne : fmt(t.scReplace, places));
      $scUndo.prop('hidden', !(plan.replaced || []).length);
    }

    function renderAll() {
      renderList();
      renderShortcodes();
    }

    function busy($btn, on) {
      $btn.toggleClass('is-busy', on).prop('disabled', on);
    }

    function runImport(ids) {
      busy($moveAll, true);
      $moveSel.prop('disabled', true);
      $status.removeClass('is-error is-ok').text(t.moving);
      post('cfImportRun', {
        ids: ids,
        submissions: option('submissions'),
        contacts: option('submissions') === '1' ? option('contacts') : '0',
      })
        .done(function (resp) {
          if (!resp || !resp.success) {
            $status.addClass('is-error').text((resp && resp.data && resp.data.message) || t.error);
            return;
          }
          scan = resp.data.scan;
          $status.addClass('is-ok').text('');
          renderAll();
          renderResults(resp.data);
        })
        .fail(function () {
          $status.addClass('is-error').text(t.error);
        })
        .always(function () {
          busy($moveAll, false);
          updateButtons();
        });
    }

    $moveAll.on('click', function () {
      runImport([]);
    });
    $moveSel.on('click', function () {
      var ids = selectedIds();
      if (ids.length) {
        runImport(ids);
      }
    });
    $selectAll.on('change', function () {
      $list.find('[data-ppsgen-cfi-pick]').prop('checked', this.checked);
      updateButtons();
    });
    $list.on('change', '[data-ppsgen-cfi-pick]', updateButtons);
    $root.on('change', '[data-ppsgen-cfi-opt]', function () {
      syncOptions();
      updateButtons();
    });

    $root.on('click', '[data-ppsgen-cfi-copy]', function () {
      var $btn = $(this);
      var text = $btn.attr('data-ppsgen-cfi-copy');
      var done = function () {
        $btn.text(t.copied);
        setTimeout(function () {
          $btn.text(t.copy);
        }, 1500);
      };
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(done, function () {});
      } else {
        var $tmp = $('<textarea>').val(text).appendTo('body').select();
        try {
          document.execCommand('copy');
          done();
        } catch (e) {}
        $tmp.remove();
      }
    });

    function shortcodeAction(action, $btn, confirmText, doneText) {
      if (confirmText && !window.confirm(confirmText)) {
        return;
      }
      busy($btn, true);
      $scStatus.removeClass('is-error is-ok').text('');
      post(action, {})
        .done(function (resp) {
          if (!resp || !resp.success) {
            $scStatus.addClass('is-error').text(t.scError);
            return;
          }
          scan = resp.data.scan;
          renderAll();
          $scStatus.addClass('is-ok').text(fmt(doneText, action === 'cfImportReplaceShortcodes' ? resp.data.shortcodes : resp.data.places));
        })
        .fail(function () {
          $scStatus.addClass('is-error').text(t.scError);
        })
        .always(function () {
          busy($btn, false);
        });
    }

    $scReplace.on('click', function () {
      shortcodeAction('cfImportReplaceShortcodes', $scReplace, t.scConfirm, t.scReplaced);
    });
    $scUndo.on('click', function () {
      shortcodeAction('cfImportUndoShortcodes', $scUndo, t.scUndoConfirm, t.scUndone);
    });

    syncOptions();
    if (scan) {
      renderAll();
    } else {
      post('cfImportScan').done(function (resp) {
        if (resp && resp.success) {
          scan = resp.data;
          renderAll();
        }
      });
    }
  });
})(jQuery);
