function utf8ToBase64(str) {
  return btoa(unescape(encodeURIComponent(str)));
}

function base64ToUtf8(str) {
  return decodeURIComponent(escape(atob(str)));
}

window.ppsgenChainSelectedStepId = '';
window.ppsgenEcaController = 'eca';

(function ($) {
  /**
   * Form builder class for managing chain fields with flat structure and parent_key.
   * @class
   */
  class ppsgenChainStepBuilder {
    /**
     * Initializes the form builder with DOM elements and state.
     * @constructor
     */
    constructor() {
      this.form = $('#ppsgenEditObjFormChain');
      this.ppsgenChainStepFieldsBuilder = null;
      this.stepTrendGroup = 'day';
      this.stepTrendRange = 30;
      this.init();
    }

    /**
     * Initializes event bindings and sortable functionality.
     */
    init() {
      this.initMailConfigStats();
      this.bindEvents();
      this.initSortable();
    }

    initMailConfigStats() {
      const dataNode = document.getElementById('ppsgenChainMailConfigStatsData');
      if (!dataNode) {
        return;
      }
      try {
        window.ppsgenChainMailConfigStats = JSON.parse(dataNode.dataset.stats || '{}');
      } catch (e) {
        window.ppsgenChainMailConfigStats = {};
      }
      window.ppsgenChainMailConfigDefaultLabel = dataNode.dataset.defaultLabel || 'Default';
    }

    /**
     * Binds events for form interactions.
     */
    bindEvents() {
      $('body').on('click', '.ppsgenChainStep', (e) => this.selectChainStep(e));
      $('body').on('click', '.ppsgenChainStepBtnSave', (e) => this.saveCurrentStep(e));
      $('body').on('click', '.ppsgenChainStepsBtnDelete', (e) => this.deleteSelectedStep(e));
      $('body').on('click', '.ppsgenChainStepsBtnAdd', (e) => this.addNewStep(e));
      $('body').on('click', '.ppsgenChainOptionsBtnSave', (e) => this.saveChainOptions(e));
      $('body').on('click', '.ppsgenChainOptionsBtnApplyChain', (e) => this.applyChainToContacts(e));
      this.form.on('change', '[name="data[chain][step_options][delay_unit]"]', () => this.updateDelayUnitControls());
      $('body').on('click', '.ppsgenChainOptionFieldsEntryBtnAdd', (e) => this.optionFieldsEntryAdd(e));
      $('body').on('click', '.ppsgenChainOptionFieldsEntryBtnDelete', (e) => this.optionFieldsEntryDelete(e));
      $('body').on('click', '.ppsgenChainOptionFieldsExitBtnAdd', (e) => this.optionFieldsExitAdd(e));
      $('body').on('click', '.ppsgenChainOptionFieldsExitBtnDelete', (e) => this.optionFieldsExitDelete(e));
      $('body').on('click', '.ppsgenChainStepStatsResetBtn', (e) => this.resetStepStats(e));
      $('body').on('click', '.ppsgenChainStatsResetBtn', (e) => this.resetChainStats(e));
      $('body').on('click', '.ppsgenChainContactFlowAction', (e) => this.handleChainContactFlowAction(e));
      $('body').on('click', '.ppsgenChainStepTrendBtn', (e) => this.changeStepTrend(e));
      this.form.on('change', '[name="data[chain][options][mail_config_id]"]', (e) => this.updateMailConfigMeta(e));
    }

    updateMailConfigMeta(e) {
      const select = jQuery(e.currentTarget);
      const configId = parseInt(select.val() || 0, 10) || 0;
      const statsMap = window.ppsgenChainMailConfigStats || {};
      const config = statsMap[configId] || statsMap[0] || null;
      if (!config) {
        return;
      }
      const fallbackLabel = window.ppsgenChainMailConfigDefaultLabel || 'Default';
      jQuery('[data-mail-config-name]').text(config.name || fallbackLabel);
      jQuery('[data-mail-config-limit]').text(`${parseInt(config.remaining || 0, 10)} / ${parseInt(config.limit || 0, 10)}`);
    }

    getSelectedStepId() {
      return window.ppsgenChainSelectedStepId;
    }

    setSelectedStepId(step_id) {
      window.ppsgenChainSelectedStepId = step_id;
    }

    selectChainStep(e) {
      if (!window.confirm('All unsaved Step data will be lost. Do you want to continue?')) {
        return;
      }
      const $button = jQuery(e.currentTarget);
      let stepId = $button.data('step-id');
      this.setSelectedStepId(stepId);
      jQuery('body').find('.ppsgenChainStep').removeClass('ppsgenChainStepActive');
      $button.addClass('ppsgenChainStepActive');
      this.loadChainStep(stepId);
    }

    loadChainStep(stepId) {
      jQuery('.ppsgenChainStepWrapper').hide();
      jQuery
        .post(
          ajaxurl,
          {
            plugin: 'ppsgen',
            mod: window.ppsgenEcaController,
            action: 'getChainStepData',
            step_id: stepId,
            step_stats_group: this.stepTrendGroup,
            step_stats_range: this.stepTrendRange,
            nonce_code: ppsgenAjax?.nonce || '',
          },
          (response) => {
            if (response?.success) {
              this.stepTrendGroup = response.data.data.stepTrendGroup || this.stepTrendGroup;
              this.stepTrendRange = parseInt(response.data.data.stepTrendRange || this.stepTrendRange, 10) || 30;
              jQuery('.ppsgenChainStepWrapper').show();
              jQuery('.ppsgenChainStepBtn[data-tab="stepFields"]').trigger('click');
              if (this.ppsgenChainStepFieldsBuilder instanceof window.ppsgenChainStepFieldsBuilder) {
                this.ppsgenChainStepFieldsBuilder.destroy();
              }
              this.ppsgenChainStepFieldsBuilder = new window.ppsgenChainStepFieldsBuilder(response.data.data.stepFields, response.data.data.stepFieldsContainerHtml);
              this.setupChainStepOptions(response.data.data.stepData.settings);
              this.renderChainStepStats(response.data.data.stepStats || {}, response.data.data.stepTrend || []);
            } else {
              this.showChainNotice('error', 'edit', 'There was an error with loading Step Data');
            }
          }
        )
        .fail(() => {
          this.showChainNotice('error', 'edit', 'There was an error with loading Step Data');
        });
    }

    setupChainStepOptions(data) {
      const defaultStepOptions = {
        active: '0',
        name: '',
        email_subject: '',
        send_without_open_tracking: '0',
        send_without_link_tracking: '0',
        send_without_unsubscribe: '0',
        send_gmail_without_tracking: '0',
        delay_time: '1',
        delay_unit: 'days',
        delay_day_of_month: '1',
        delay_specific_date: '',
        tag_yes_condition: 'or',
        tag_not_condition: 'or',
        on_false_condition: 'try_after_day',
        use_as_exit: '0',
        created_at: '',
        updated_at: '',
        add_tag_view: [],
        delete_tag_view: [],
        add_tag_click: [],
        delete_tag_click: [],
        add_tag_unsubscribe: [],
      };
      data = Object.assign({}, defaultStepOptions, data || {});
      let stepOptionsContainer = jQuery('.ppsgenChainStepTabContent[data-tab="stepOptions"]');
      stepOptionsContainer.find(':input[name]').each((_, el) => {
        const $el = jQuery(el);
        const fullName = $el.attr('name');
        const key = fullName.replace(/^.*\[step_options]\[([^\]]+)].*$/, '$1');
        if ($el.attr('type') === 'hidden') {
          return;
        }
        if (typeof data[key] !== 'undefined') {
          // TinyMCE
          if (typeof tinymce !== 'undefined' && tinymce.get(fullName)) {
            tinymce.get(fullName).setContent('');
            setTimeout(() => tinymce.get(fullName).setContent(data[key]), 10);
            return;
          }
          // Checkbox
          if ($el.is(':checkbox')) {
            $el.prop('checked', data[key] === '1' || data[key] === 1);
            return;
          }
          // Default
          $el.val(data[key]);
          $el.trigger('change');
        } else {
          // No Data - clear
          if (typeof tinymce !== 'undefined' && tinymce.get(fullName)) {
            tinymce.get(fullName).setContent('');
          } else if ($el.is(':checkbox')) {
            $el.prop('checked', false);
          } else {
            $el.val('');
            $el.trigger('change');
          }
        }
      });
      this.updateDelayUnitControls();
    }

    updateDelayUnitControls() {
      const stepOptionsContainer = jQuery('.ppsgenChainStepTabContent[data-tab="stepOptions"]');
      const unit = stepOptionsContainer.find('[name="data[chain][step_options][delay_unit]"]').val();
      const isSpecificDay = unit === 'specific_day_of_month';
      const isSpecificDate = unit === 'specific_date';
      stepOptionsContainer.find('.ppsgenEcaDelayRelative').prop('hidden', isSpecificDay || isSpecificDate);
      stepOptionsContainer.find('.ppsgenEcaDelayDayOfMonth').prop('hidden', !isSpecificDay);
      stepOptionsContainer.find('.ppsgenEcaDelaySpecificDate').prop('hidden', !isSpecificDate);
    }

    getStepTrendMax(trend) {
      let maxValue = 0;
      (trend || []).forEach((point) => {
        maxValue = Math.max(maxValue, parseInt(point?.emails_sent || 0, 10), parseInt(point?.opens || 0, 10), parseInt(point?.clicks || 0, 10), parseInt(point?.unsubscribed || 0, 10));
      });
      return maxValue;
    }

    buildStepTrendChart(trend) {
      const safeTrend = Array.isArray(trend) ? trend : [];
      const trendMax = this.getStepTrendMax(safeTrend);
      let html = `
        <div class="ppsgenStatsTrendPanel">
          <div class="ppsgenStatsTrendToolbar">
            <div class="ppsgenStatsTrendGroup">
              <strong>Group:</strong>
              ${this.buildStepTrendButton('day', 'Day', 'group')}
              ${this.buildStepTrendButton('week', 'Week', 'group')}
                ${this.buildStepTrendButton('month', 'Month', 'group')}
                ${this.buildStepTrendButton('year', 'Year', 'group')}
            </div>
            <div class="ppsgenStatsTrendGroup">
              <strong>Range:</strong>
              ${this.buildStepTrendButton(7, '7', 'range')}
              ${this.buildStepTrendButton(30, '30', 'range')}
              ${this.buildStepTrendButton(365, '365', 'range')}
            </div>
          </div>
          <div class="ppsgenStatsTrendLegend">
            <span class="ppsgenStatsTrendLegendItem"><i class="ppsgenStatsTrendColor ppsgenStatsTrendColorPrimary"></i>Emails Sent</span>
            <span class="ppsgenStatsTrendLegendItem"><i class="ppsgenStatsTrendColor ppsgenStatsTrendColorSecondary"></i>Opens</span>
            <span class="ppsgenStatsTrendLegendItem"><i class="ppsgenStatsTrendColor ppsgenStatsTrendColorTertiary"></i>Clicks</span>
            <span class="ppsgenStatsTrendLegendItem"><i class="ppsgenStatsTrendColor ppsgenStatsTrendColorDanger"></i>Unsubscribed</span>
          </div>
          <div class="ppsgenStatsTrendChart">
      `;

      if (safeTrend.length && trendMax > 0) {
        safeTrend.forEach((point) => {
          const sent = parseInt(point?.emails_sent || 0, 10);
          const opens = parseInt(point?.opens || 0, 10);
          const clicks = parseInt(point?.clicks || 0, 10);
          const unsubscribed = parseInt(point?.unsubscribed || 0, 10);
          html += `
            <div class="ppsgenStatsTrendCol">
              <div class="ppsgenStatsTrendBars">
                ${this.buildStepTrendBar(sent, trendMax, 'ppsgenStatsTrendBarPrimary', `Emails Sent: ${sent}`)}
                ${this.buildStepTrendBar(opens, trendMax, 'ppsgenStatsTrendBarSecondary', `Opens: ${opens}`)}
                ${this.buildStepTrendBar(clicks, trendMax, 'ppsgenStatsTrendBarTertiary', `Clicks: ${clicks}`)}
                ${this.buildStepTrendBar(unsubscribed, trendMax, 'ppsgenStatsTrendBarDanger', `Unsubscribed: ${unsubscribed}`)}
              </div>
              <div class="ppsgenStatsTrendLabel">${this.escapeHtml(point?.label || '')}</div>
            </div>
          `;
        });
      } else {
        html += `<div class="ppsgenStatsTrendEmpty">Not enough step activity yet to build a trend chart.</div>`;
      }

      html += `
          </div>
        </div>
      `;

      return html;
    }

    buildStepTrendButton(value, label, type) {
      const isActive = type === 'group' ? this.stepTrendGroup === value : parseInt(this.stepTrendRange, 10) === parseInt(value, 10);
      const attrName = type === 'group' ? 'data-group' : 'data-range';
      return `<button type="button" class="ppsgenStatsTrendBtn ppsgenChainStepTrendBtn${isActive ? ' ppsgenStatsTrendBtnActive' : ''}" ${attrName}="${this.escapeHtml(String(value))}">${this.escapeHtml(String(label))}</button>`;
    }

    buildStepTrendBar(value, maxValue, className, title) {
      const height = maxValue > 0 ? Math.max(4, Math.round((value / maxValue) * 140)) : 4;
      return `<span class="ppsgenStatsTrendBar ${className}" style="height: ${height}px;" title="${this.escapeHtml(title)}"></span>`;
    }

    escapeHtml(str) {
      return jQuery('<div>').text(str ?? '').html();
    }

    renderChainStepStats(stats, trend) {
      const $container = jQuery('.ppsgenChainStepStatsDynamic');
      const i18n = Object.assign(
        {
          reset_step_logs: 'Reset Step Logs',
          reset_step_events: 'Reset Step Events',
          current_state: 'Current State',
          users_on_this_step: 'Users on This Step',
          waiting_right_now: 'Waiting Right Now',
          processing_right_now: 'Processing Right Now',
          step_progress: 'Step Progress',
          passed_this_step: 'Passed This Step',
          emails_sent: 'Emails Sent',
          finished_on_this_step: 'Finished on This Step',
          skipped_this_step: 'Skipped This Step',
          failures_and_dropoff: 'Failures & Drop-Off',
          condition_false_users: 'Condition False Users',
          condition_false_checks: 'Condition False Checks',
          exited_on_this_step: 'Exited on This Step',
          unsubscribed_on_this_step: 'Unsubscribed on This Step',
          errors: 'Errors',
          engagement_overview: 'Engagement Overview',
          total_events: 'Total Events',
          engaged_contacts: 'Engaged Contacts',
          opens: 'Opens',
          total_opens: 'Total Opens',
          suspicious_opens: 'Suspicious Opens',
          live_opens: 'Live Opens',
          clicks: 'Clicks',
          total_clicks: 'Total Clicks',
          suspicious_clicks: 'Suspicious Clicks',
          live_clicks: 'Live Clicks',
          conversions: 'Conversions',
          paid_conversions: 'Paid Conversions',
          unique_contacts: 'Unique Contacts',
          unique_emails: 'Unique Emails',
          total_value: 'Total Value',
        },
        window.ppsgenEcaChainStepsData?.i18n || {}
      );
      const safeStats = Object.assign(
        {
          current_total: 0,
          current_waiting: 0,
          current_processing: 0,
          completed_contacts: 0,
          sent_total: 0,
          finished_on_step: 0,
          skipped_contacts: 0,
          condition_false_contacts: 0,
          condition_false_total: 0,
          exited_on_step: 0,
          unsubscribed_on_step: 0,
          errors_total: 0,
          events_total: 0,
          open_total: 0,
          open_suspicious_total: 0,
          open_live_total: 0,
          open_unique: 0,
          click_total: 0,
          click_suspicious_total: 0,
          click_live_total: 0,
          click_unique: 0,
          engaged_contacts: 0,
          conversion_paid_total: 0,
          conversion_paid_contacts: 0,
          conversion_paid_emails: 0,
          conversion_paid_value_label: '0',
        },
        stats || {}
      );

      const groups = [
        {
          title: i18n.current_state,
          icon: 'dashicons-randomize',
          className: 'is-flow',
          items: [
            [i18n.users_on_this_step, safeStats.current_total, 'is-neutral'],
            [i18n.waiting_right_now, safeStats.current_waiting, 'is-primary'],
            [i18n.processing_right_now, safeStats.current_processing, 'is-warning'],
          ],
        },
        {
          title: i18n.step_progress,
          icon: 'dashicons-yes-alt',
          className: 'is-delivery',
          items: [
            [i18n.passed_this_step, safeStats.completed_contacts, 'is-primary'],
            [i18n.emails_sent, safeStats.sent_total, 'is-primary'],
            [i18n.finished_on_this_step, safeStats.finished_on_step, 'is-success'],
            [i18n.skipped_this_step, safeStats.skipped_contacts, 'is-muted'],
          ],
        },
        {
          title: i18n.failures_and_dropoff,
          icon: 'dashicons-dismiss',
          className: 'is-danger',
          items: [
            [i18n.condition_false_users, safeStats.condition_false_contacts, 'is-warning'],
            [i18n.condition_false_checks, safeStats.condition_false_total, 'is-warning'],
            [i18n.exited_on_this_step, safeStats.exited_on_step, 'is-danger'],
            [i18n.unsubscribed_on_this_step, safeStats.unsubscribed_on_step, 'is-muted'],
            [i18n.errors, safeStats.errors_total, 'is-danger'],
          ],
        },
        {
          title: i18n.engagement_overview,
          icon: 'dashicons-chart-bar',
          className: 'is-flow',
          items: [
            [i18n.total_events, safeStats.events_total, 'is-neutral'],
            [i18n.engaged_contacts, safeStats.engaged_contacts, 'is-primary'],
          ],
        },
        {
          title: i18n.opens,
          icon: 'dashicons-visibility',
          className: 'is-open',
          items: [
            [i18n.total_opens, safeStats.open_total, 'is-primary'],
            [i18n.suspicious_opens, safeStats.open_suspicious_total, 'is-warning'],
            [i18n.live_opens, safeStats.open_live_total, 'is-success'],
          ],
        },
        {
          title: i18n.clicks,
          icon: 'dashicons-external',
          className: 'is-click',
          items: [
            [i18n.total_clicks, safeStats.click_total, 'is-primary'],
            [i18n.suspicious_clicks, safeStats.click_suspicious_total, 'is-warning'],
            [i18n.live_clicks, safeStats.click_live_total, 'is-success'],
          ],
        },
        {
          title: i18n.conversions,
          icon: 'dashicons-chart-line',
          className: 'is-conversion',
          items: [
            [i18n.paid_conversions, safeStats.conversion_paid_total, 'is-success'],
            [i18n.unique_contacts, safeStats.conversion_paid_contacts, 'is-primary'],
            [i18n.unique_emails, safeStats.conversion_paid_emails, 'is-primary'],
            [i18n.total_value, safeStats.conversion_paid_value_label, 'is-success'],
          ],
        },
      ];

      let html = '';
      const stepId = this.getSelectedStepId();
      html += `
        <div class="ppsgenChainStepStatsActions">
          <button type="button" class="ppsgenTableListBtn ppsgenChainStepStatsResetBtn" data-action="resetChainStepLogs" data-step-id="${stepId}">
            <span class="dashicons dashicons-update-alt"></span> ${this.escapeHtml(i18n.reset_step_logs)}
          </button>
          <button type="button" class="ppsgenTableListBtn ppsgenChainStepStatsResetBtn" data-action="resetChainStepEvents" data-step-id="${stepId}">
            <span class="dashicons dashicons-update-alt"></span> ${this.escapeHtml(i18n.reset_step_events)}
          </button>
        </div>
      `;
      html += `<div class="ppsgenChainStatsGrid">`;
      groups.forEach((group) => {
        html += `<div class="ppsgenChainStatsCard ${this.escapeHtml(group.className || '')}">`;
        html += `
          <div class="ppsgenChainStatsCardHeader">
            <div class="ppsgenChainStatsCardTitleWrap">
              <span class="ppsgenChainStatsCardIcon dashicons ${this.escapeHtml(group.icon || 'dashicons-chart-bar')}"></span>
              <div class="ppsgenChainStatsCardTitle">${this.escapeHtml(group.title)}</div>
            </div>
          </div>
        `;
        html += `<div class="ppsgenChainStatsCardBody">`;
        group.items.forEach((item) => {
          html += `
            <div class="ppsgenChainStatsMetric">
              <div class="ppsgenChainStatsMetricLabel">${this.escapeHtml(item[0])}</div>
              <div class="ppsgenChainStatsMetricValue ${this.escapeHtml(item[2] || 'is-neutral')}">${this.escapeHtml(String(item[1] ?? 0))}</div>
            </div>
          `;
        });
        html += `</div>`;
        html += `</div>`;
      });
      html += `</div>`;

      html += this.buildStepTrendChart(trend);

      $container.html(html);
    }

    changeStepTrend(e) {
      e.preventDefault();
      const $button = jQuery(e.currentTarget);
      const group = $button.data('group');
      const range = parseInt($button.data('range'), 10);
      if (group) {
        this.stepTrendGroup = ['day', 'week', 'month', 'year'].includes(group) ? group : 'day';
      }
      if (range) {
        this.stepTrendRange = [7, 30, 365].includes(range) ? range : 30;
      }
      const stepId = this.getSelectedStepId();
      if (stepId) {
        this.loadChainStep(stepId);
      }
    }

    resetStepStats(e) {
      e.preventDefault();
      const $button = jQuery(e.currentTarget);
      const action = $button.data('action');
      const stepId = parseInt($button.data('step-id'), 10) || this.getSelectedStepId();
      if (!action || !stepId) {
        return;
      }
      if (!confirm(ppsgenAjax.confirmResetText || ppsgenAjax.confirmDeleteText)) {
        return;
      }
      $button.prop('disabled', true);
      jQuery.post(
        ajaxurl,
        {
          plugin: 'ppsgen',
          mod: window.ppsgenEcaController,
          action: action,
          step_id: stepId,
          nonce_code: ppsgenAjax?.nonce || '',
        },
        (response) => {
          $button.prop('disabled', false);
          if (response?.success) {
            this.showChainNotice('success', 'edit', response.data?.message || 'Step statistics reset successfully');
            this.loadChainStep(stepId);
          } else {
            this.showChainNotice('error', 'edit', response?.data?.message || 'Failed to reset step statistics');
          }
        }
      ).fail(() => {
        $button.prop('disabled', false);
        this.showChainNotice('error', 'edit', 'Failed to reset step statistics');
      });
    }

    resetChainStats(e) {
      e.preventDefault();
      const $button = jQuery(e.currentTarget);
      const action = $button.data('action');
      const chainId = parseInt($button.data('chain-id'), 10) || parseInt(jQuery('[name="obj_id"]').val(), 10);
      if (!action || !chainId) {
        return;
      }
      if (!confirm(ppsgenAjax.confirmResetText || ppsgenAjax.confirmDeleteText)) {
        return;
      }
      $button.prop('disabled', true);
      jQuery.post(
        ajaxurl,
        {
          plugin: 'ppsgen',
          mod: window.ppsgenEcaController,
          action: action,
          chain_id: chainId,
          nonce_code: ppsgenAjax?.nonce || '',
        },
        (response) => {
          $button.prop('disabled', false);
          if (response?.success) {
      this.showChainNotice('success', 'edit', response.data?.message || 'Campaign statistics reset successfully');
            window.location.reload();
          } else {
      this.showChainNotice('error', 'edit', response?.data?.message || 'Failed to reset campaign statistics');
          }
        }
      ).fail(() => {
        $button.prop('disabled', false);
      this.showChainNotice('error', 'edit', 'Failed to reset campaign statistics');
      });
    }

    handleChainContactFlowAction(e) {
      e.preventDefault();
      const btn = jQuery(e.currentTarget);
      const action = btn.data('action') || '';
      const flowId = parseInt(btn.data('flow-id') || 0, 10);
      const confirmText = btn.data('confirm') || ppsgenAjax?.confirmDeleteText || 'Are you sure?';

      if (!action || !flowId) {
        this.showChainNotice('error', 'edit', 'Invalid flow action');
        return;
      }

      if (!confirm(confirmText)) {
        return;
      }

      btn.prop('disabled', true).addClass('ppsgenLoading');

      jQuery.post(
        ajaxurl,
        {
          action,
          mod: 'eca',
          plugin: 'ppsgen',
          nonce_code: ppsgenAjax?.nonce || '',
          flow_id: flowId,
        },
        (response) => {
          btn.prop('disabled', false).removeClass('ppsgenLoading');
          if (response?.success) {
            this.showChainNotice('success', 'edit', response?.data?.message || 'Flow updated successfully');
            window.location.reload();
            return;
          }
          this.showChainNotice('error', 'edit', response?.data?.message || 'Failed to update flow');
        },
      ).fail(() => {
        btn.prop('disabled', false).removeClass('ppsgenLoading');
        this.showChainNotice('error', 'edit', 'Failed to update flow');
      });
    }

    initSortable() {
      const self = this;
      const container = jQuery('.ppsgenChainStepsContainer');
      if (!container.length) return;
      new Sortable(container[0], {
        animation: 150,
        handle: '.ppsgenChainStep',
        draggable: '.ppsgenChainStep',
        direction: 'vertical',
        onEnd(evt) {
          let steps = [];
          jQuery('.ppsgenChainStepsContainer .ppsgenChainStep').each(function (index) {
            const stepId = jQuery(this).data('step-id');
            const order = index + 1;
            jQuery(this).attr('data-step-order', order);
            steps.push({
              id: stepId,
              order: order,
            });
          });
          jQuery.post(ajaxurl, {
            plugin: 'ppsgen',
            mod: window.ppsgenEcaController,
            action: 'updateChainStepsOrder',
            steps: steps,
            nonce_code: ppsgenAjax?.nonce || '',
          });
        },
      });
    }

    getNextConditionIndex(containerSelector, fieldKey, etalon) {
      let maxIndex = parseInt(jQuery(etalon).data('count'), 10) || 0;
      const pattern = new RegExp('\\[' + fieldKey + '\\]\\[(\\d+)\\]');
      jQuery(containerSelector)
        .find('[name]')
        .each(function () {
          const name = jQuery(this).attr('name') || '';
          const match = name.match(pattern);
          if (match) {
            maxIndex = Math.max(maxIndex, parseInt(match[1], 10) || 0);
          }
        });
      return maxIndex + 1;
    }

    optionFieldsEntryAdd() {
      let container = jQuery('.ppsgenChainOptionFieldsEntryEtalon');
      let count = this.getNextConditionIndex('.ppsgenChainOptionFieldsEntryContainer', 'entry_field', container);
      container.data('count', count).attr('data-count', count);
      let clone = container.clone(false);
      clone.find('[name]').each(function () {
        let name = jQuery(this).attr('name');
        if (!name) return;
        jQuery(this).attr('name', name.replace('[x]', '[entry_field][' + count + ']'));
      });
      container.find('input, select, textarea').each(function () {
        let name = jQuery(this).attr('name');
        if (!name) return;
        let value = jQuery(this).val();
        clone.find('[name="' + name.replace('[x]', '[entry_field][' + count + ']') + '"]').val(value);
      });
      clone.removeClass('ppsgenChainOptionFieldsEntryEtalon').addClass('ppsgenChainOptionFieldsEntryObj');
      clone.find('.ppsgenChainOptionFieldsEntryBtnDelete').show();
      jQuery('.ppsgenChainOptionFieldsEntryContainer').append(clone);
    }

    optionFieldsEntryDelete(e) {
      $(e.currentTarget).closest('.ppsgenChainOptionFieldsEntryObj').remove();
    }

    optionFieldsExitAdd() {
      let container = jQuery('.ppsgenChainOptionFieldsExitEtalon');
      let count = this.getNextConditionIndex('.ppsgenChainOptionFieldsExitContainer', 'exit_field', container);
      container.data('count', count).attr('data-count', count);
      let clone = container.clone(false);
      clone.find('[name]').each(function () {
        let name = jQuery(this).attr('name');
        if (!name) return;
        jQuery(this).attr('name', name.replace('[x]', '[exit_field][' + count + ']'));
      });
      container.find('input, select, textarea').each(function () {
        let name = jQuery(this).attr('name');
        if (!name) return;
        let value = jQuery(this).val();
        clone.find('[name="' + name.replace('[x]', '[exit_field][' + count + ']') + '"]').val(value);
      });
      clone.removeClass('ppsgenChainOptionFieldsExitEtalon').addClass('ppsgenChainOptionFieldsExitObj');
      clone.find('.ppsgenChainOptionFieldsExitBtnDelete').show();
      jQuery('.ppsgenChainOptionFieldsExitContainer').append(clone);
    }

    optionFieldsExitDelete(e) {
      $(e.currentTarget).closest('.ppsgenChainOptionFieldsExitObj').remove();
    }

    addNewStep() {
      let chain_id = jQuery('[name="obj_id"]').val();
      let step_name = prompt('Enter step name:');
      if (!step_name) step_name = 'New Step';
      if (chain_id) {
        jQuery('.ppsgenChainStepsBtnAdd').attr('disabled', 'disabled').prop('disabled', true).find('.dashicons').toggleClass('dashicons-plus-alt dashicons-clock');
        $.post(
          ajaxurl,
          {
            plugin: 'ppsgen',
            mod: window.ppsgenEcaController,
            action: 'addNewChainStep',
            chain_id: chain_id,
            step_name: step_name,
            nonce_code: ppsgenAjax?.nonce || '',
          },
          (response) => {
            if (response?.success) {
              let step_id = response.data.step_id;
              let count = jQuery('.ppsgenChainStepsContainer .ppsgenChainStep').length;
              let order = count + 1;
              let $step = jQuery(`
                  <div class="ppsgenChainStep"
                      data-step-active="1"
                      data-step-order="${order}"
                      data-step-id="${step_id}">
                      <span>${step_name}</span>
                  </div>
              `);
              jQuery('.ppsgenChainStepsContainer').append($step);
        const msg = response.data?.message || 'Campaign Step is successfully created';
              jQuery('.ppsgenChainStepsBtnAdd').removeAttr('disabled').prop('disabled', false).find('.dashicons').toggleClass('dashicons-plus-alt dashicons-clock');
              this.showChainNotice('success', 'edit', msg);
              return true;
            } else {
        const msg = response.data?.message || 'There was an error creating the Campaign Step';
              jQuery('.ppsgenChainStepsBtnAdd').removeAttr('disabled').prop('disabled', false).find('.dashicons').toggleClass('dashicons-plus-alt dashicons-clock');
              jQuery().ppsgenShowPopupNotice({ status: 'error', operation: 'add', msg: msg, fadeOutTime: 8000 });
              return false;
            }
          }
        ).fail(() => {
      this.showChainNotice('error', 'edit', 'There was an error creating the Campaign Step');
          jQuery('.ppsgenChainStepsBtnAdd').removeAttr('disabled').prop('disabled', false).find('.dashicons').toggleClass('dashicons-plus-alt dashicons-clock');
          return false;
        });
      }
    }

    deleteSelectedStep() {
      if (!window.confirm('Are you sure you want to delete this step? This action cannot be reversed.')) {
        return;
      }
      let step_id = this.getSelectedStepId();
      if (step_id) {
        jQuery('.ppsgenChainStepsBtnDelete').attr('disabled', 'disabled').prop('disabled', true).find('.dashicons').toggleClass('dashicons-trash dashicons-clock');
        $.post(
          ajaxurl,
          {
            plugin: 'ppsgen',
            mod: window.ppsgenEcaController,
            action: 'deleteChainStep',
            step_id: step_id,
            nonce_code: ppsgenAjax?.nonce || '',
          },
          (response) => {
            if (response?.success) {
              jQuery('.ppsgenChainStepWrapper').hide();
              jQuery('.ppsgenChainStep[data-step-id="' + step_id + '"]').remove();
        const msg = response.data?.message || 'Campaign Step is successfully deleted';
              jQuery('.ppsgenChainStepsBtnDelete').removeAttr('disabled').prop('disabled', false).find('.dashicons').toggleClass('dashicons-trash dashicons-clock');
              this.setSelectedStepId(false);
              this.showChainNotice('success', 'edit', msg);
              return true;
            } else {
        const msg = response.data?.message || 'There was an error deleting the Campaign Step';
              jQuery('.ppsgenChainStepsBtnDelete').removeAttr('disabled').prop('disabled', false).find('.dashicons').toggleClass('dashicons-trash dashicons-clock');
              this.showChainNotice('error', 'edit', msg);
              return false;
            }
          }
        ).fail(() => {
      this.showChainNotice('error', 'edit', 'There was an error deleting the Campaign Step');
          jQuery('.ppsgenChainStepsBtnDelete').removeAttr('disabled').prop('disabled', false).find('.dashicons').toggleClass('dashicons-trash dashicons-clock');
          return false;
        });
      }
    }

    saveCurrentStep() {
      if (this.ppsgenChainStepFieldsBuilder instanceof window.ppsgenChainStepFieldsBuilder) {
        let stepFieldsSavingRes = this.ppsgenChainStepFieldsBuilder.handleSubmit();
        let stepOptionSavingRes = this.saveStepOptions();
      }
    }

    saveStepOptions() {
      const stepOptionsContainer = jQuery('.ppsgenChainStepTabContent[data-tab="stepOptions"]');
      const delayUnit = stepOptionsContainer.find('[name="data[chain][step_options][delay_unit]"]').val();
      const specificDate = stepOptionsContainer.find('[name="data[chain][step_options][delay_specific_date]"]').val();
      if (delayUnit === 'specific_date' && !specificDate) {
        this.showChainNotice('error', 'edit', 'Choose a specific date before saving this Step.');
        return false;
      }
      let formDataArray = this.form.serializeArray();
      const dataBlock = {};
      formDataArray.forEach((item) => {
        if (item.name.startsWith('data[chain][step_options]')) {
          const name = item.name;
          const value = item.value;
          if (name.endsWith('[]')) {
            const cleanName = name.slice(0, -2);
            if (!dataBlock[cleanName]) {
              dataBlock[cleanName] = [];
            }
            if (value !== '') {
              dataBlock[cleanName].push(value);
            }
          } else {
            dataBlock[name] = value;
          }
        }
      });
      const packedData = utf8ToBase64(JSON.stringify(dataBlock));
      const payload = window.ppsgenBuildPayloadData(
        {
          plugin: 'ppsgen',
          mod: window.ppsgenEcaController,
          action: 'saveChainStepOptions',
          step_id: this.getSelectedStepId(),
          nonce_code: ppsgenAjax?.nonce || '',
        },
        packedData
      );
      $.post(ajaxurl, payload, (response) => {
        if (response?.success) {
        const msg = response.data?.message || 'Campaign Step Options are successfully saved';
          this.showChainNotice('success', 'edit', msg);
          $('.ppsgenObjEditSaveBtn, .ppsgenChainStepBtnSave').removeAttr('disabled').prop('disabled', false).find('.dashicons').toggleClass('dashicons-yes-alt dashicons-clock');
          return true;
        } else {
        const msg = response.data?.message || 'Failed to save Campaign Step Options';
          this.showChainNotice('error', 'edit', msg);
          $('.ppsgenObjEditSaveBtn, .ppsgenChainStepBtnSave').removeAttr('disabled').prop('disabled', false).find('.dashicons').toggleClass('dashicons-yes-alt dashicons-clock');
          return true;
        }
      }).fail(() => {
        $('.ppsgenObjEditSaveBtn, .ppsgenChainStepBtnSave').removeAttr('disabled').prop('disabled', false).find('.dashicons').toggleClass('dashicons-yes-alt dashicons-clock');
      this.showChainNotice('error', 'edit', 'There was an error saving the Campaign Step Options');
        return true;
      });
      return true;
    }

    saveChainOptions() {
      jQuery('.ppsgenChainOptionsBtnSave').attr('disabled', 'disabled').prop('disabled', true).find('.dashicons').toggleClass('dashicons-yes-alt dashicons-clock');
      let formDataArray = this.form.serializeArray();
      const dataBlock = {};
      formDataArray.forEach((item) => {
        if (item.name.startsWith('data[chain][options]')) {
          const name = item.name;
          if (name.indexOf('[x]') !== -1) {
            return;
          }
          const value = item.value;
          if (name.endsWith('[]')) {
            const cleanName = name.slice(0, -2);
            if (!dataBlock[cleanName]) {
              dataBlock[cleanName] = [];
            }
            if (value !== '') {
              dataBlock[cleanName].push(value);
            }
          } else {
            dataBlock[name] = value;
          }
        } else {
          // const allowedKeys = ['plugin', 'mod', 'obj_id', 'action', 'nonce_code'];
          // if (allowedKeys.includes(item.name)) {
          //   // normalData[item.name] = item.value;
          // }
        }
      });
      let chain_id = jQuery('[name="obj_id"]').val();
      const packedData = utf8ToBase64(JSON.stringify(dataBlock));
      const payload = window.ppsgenBuildPayloadData(
        {
          plugin: 'ppsgen',
          mod: window.ppsgenEcaController,
          action: 'saveChainOptions',
          chain_id: chain_id,
          nonce_code: ppsgenAjax?.nonce || '',
        },
        packedData
      );
      $.post(ajaxurl, payload, (response) => {
        if (response?.success) {
        const msg = response.data?.message || 'Campaign Options are successfully saved';
          this.showChainNotice('success', 'edit', msg);
          $('.ppsgenChainOptionsBtnSave').removeAttr('disabled').prop('disabled', false).find('.dashicons').toggleClass('dashicons-yes-alt dashicons-clock');
          return true;
        } else {
        const msg = response.data?.message || 'Failed to save Campaign Options';
          this.showChainNotice('error', 'edit', msg);
          $('.ppsgenChainOptionsBtnSave').removeAttr('disabled').prop('disabled', false).find('.dashicons').toggleClass('dashicons-yes-alt dashicons-clock');
          return true;
        }
      }).fail(() => {
        $('.ppsgenChainOptionsBtnSave').removeAttr('disabled').prop('disabled', false).find('.dashicons').toggleClass('dashicons-yes-alt dashicons-clock');
      this.showChainNotice('error', 'edit', 'There was an error saving the Campaign Options');
        return true;
      });
      return true;
    }

    applyChainToContacts() {
    let msg = 'Apply this email automation campaign to all contacts in your database. This process will check each contact against the campaign’s entry and exit conditions and create or update flows accordingly. Depending on the number of contacts, this operation may take some time to complete.';
      this.showChainNotice('success', 'edit', msg, 12000);
      const chainId = jQuery('[name="obj_id"]').val();
      const payload = {
        plugin: 'ppsgen',
        mod: window.ppsgenEcaController,
        action: 'applyChainToContacts',
        chain_id: chainId,
        nonce_code: ppsgenAjax?.nonce || '',
      };
      jQuery('.ppsgenChainOptionsBtnApplyChain').prop('disabled', true).find('.dashicons').removeClass('dashicons-email-alt').addClass('dashicons-clock');
      jQuery.post(ajaxurl, payload, (response) => {
        if (!response || !response.success) {
          if (response.data?.message == 'already_running') {
            this.applyChainPollProgress(chainId);
          }
          return;
        }
        this.applyChainPollProgress(chainId);
      });
    }

    applyChainPollProgress(chainId) {
      jQuery('.ppsgenChainOptionsBarApplyChain').fadeIn();
      jQuery.post(
        ajaxurl,
        {
          plugin: 'ppsgen',
          mod: window.ppsgenEcaController,
          action: 'applyChainToContactsProgress',
          chain_id: chainId,
          nonce_code: ppsgenAjax?.nonce || '',
        },
        (response) => {
          if (!response || !response.success) {
            return;
          }
          const p = response.data;
          const percent = Math.round((p.processed / p.total) * 100);
          const percentTrue = Math.floor((p.processed / p.total) * 100);
          jQuery('.ppsgenChainOptionsBarApplyChain')
            .css('width', percent + '%')
            .text(percentTrue + '%');
          if (p.status === 'processing') {
            setTimeout(() => this.applyChainPollProgress(chainId), 1500);
          } else {
            jQuery('.ppsgenChainOptionsBtnApplyChain').prop('disabled', false).find('.dashicons').removeClass('dashicons-clock').addClass('dashicons-email-alt');
            jQuery('.ppsgenChainOptionsBarApplyChain').text('Completed');
            setTimeout(() => {
              jQuery('.ppsgenChainOptionsBarApplyChain').fadeOut();
            }, 4000);
          }
        }
      );
    }

    /**
     * Serializes array for form data.
     * @param {Array} arr - The array to serialize.
     * @param {string} prefix - The prefix for the serialized keys.
     * @returns {string} - The serialized string.
     */
    serializeArray(arr, prefix) {
      let str = '';
      arr.forEach((val, i) => {
        const fullPrefix = `${prefix}[${i}]`;
        if (typeof val === 'object' && val !== null) {
          str += this.serializeNested(val, fullPrefix);
        } else {
          str += `&${fullPrefix}=${encodeURIComponent(val ?? '')}`;
        }
      });
      return str;
    }

    /**
     * Serializes nested object for form data.
     * @param {Object} obj - The object to serialize.
     * @param {string} prefix - The prefix for the serialized keys.
     * @returns {string} - The serialized string.
     */
    serializeNested(obj, prefix) {
      let str = '';
      const build = (o, pre) => {
        if (Array.isArray(o)) {
          o.forEach((value, i) => {
            if (typeof value === 'object' && value !== null) {
              build(value, `${pre}[${i}]`);
            } else {
              str += `&${pre}[]=${encodeURIComponent(value ?? '')}`;
            }
          });
        } else if (typeof o === 'object' && o !== null) {
          Object.entries(o).forEach(([key, value]) => {
            build(value, `${pre}[${key}]`);
          });
        } else {
          str += `&${pre}=${encodeURIComponent(o ?? '')}`;
        }
      };
      build(obj, prefix);
      return str;
    }

    /**
     * Displays a chain notice or fallback alert with an escaped message.
     * @param {string} status - The status of the notice ('success' or 'error').
     * @param {string} operation - The operation type ('edit').
     * @param {string} msg - The message to display.
     */
    showChainNotice(status, operation, msg, fadeOutTime = 2000) {
      const escapedMsg = $('<div>').text(msg).html();
      if (typeof jQuery().ppsgenShowPopupNotice === 'function') {
        jQuery().ppsgenShowPopupNotice({ status, operation, msg: escapedMsg, fadeOutTime });
      } else {
        console.warn('ppsgenShowPopupNotice is not defined. Displaying fallback alert.');
        alert(`${status.toUpperCase()}: ${escapedMsg}`);
      }
    }
  }

  $(document).ready(() => new ppsgenChainStepBuilder());
})(jQuery);

jQuery(document).ready(function () {
  jQuery('.ppsgenChainStepBtn').on('click', function (e) {
    var btn = jQuery(this);
    var tab = btn.data('tab');
    jQuery('.ppsgenChainStepBtn').removeClass('ppsgenChainStepBtnActive');
    btn.addClass('ppsgenChainStepBtnActive');
    jQuery('.ppsgenChainStepTabContent').removeClass('ppsgenChainStepTabContentActive');
    jQuery('.ppsgenChainStepTabContent[data-tab="' + tab + '"]').addClass('ppsgenChainStepTabContentActive');
  });
});
