<div class="ppsPopupOptRow">
  <label>
    <?php echo viewPps::ksesString(
      htmlPps::checkbox('params[tpl][enb_sm]', [
        'checked' => htmlPps::checkedOpt($this->popup['params']['tpl'], 'enb_sm'),
        'attrs' => 'data-switch-block="smShell"',
      ]),
    ); ?>
    <?php _e('Enable Social Buttons', PPS_LANG_CODE); ?>
  </label>
</div>
<span data-block-to-switch="smShell">
  <div class="ppsPopupOptRow">
    <?php foreach ($this->smLinks as $smKey => $smData) { ?>
    <label>
      <?php echo viewPps::ksesString(htmlPps::checkbox('params[tpl][enb_sm_' . $smKey . ']', ['checked' => htmlPps::checkedOpt($this->popup['params']['tpl'], 'enb_sm_' . $smKey)])); ?>
      <?php echo viewPps::ksesString($smData['label']); ?>
    </label>
    <?php } ?>
  </div>
  <div class="ppsPopupOptRow">
    <fieldset class="ppoPopupSubFields" style="padding: 10px;">
      <legend><?php _e('Social links design', PPS_LANG_CODE); ?></legend>
      <?php foreach ($this->smDesigns as $smKey => $smData) { ?>
      <label>
        <?php echo viewPps::ksesString(htmlPps::radiobutton('params[tpl][sm_design]', ['value' => $smKey, 'checked' => htmlPps::checkedOpt($this->popup['params']['tpl'], 'sm_design', $smKey)])); ?>
        <?php echo viewPps::ksesString($smData['label']); ?>
      </label>
      <?php } ?>
    </fieldset>
  </div>
  <?php if ($this->sssPlugAvailable && isset($this->sssProjectsForSelect) && !empty($this->sssProjectsForSelect)) { ?>
  <div class="ppsPopupOptRow">
    <table class="form-table" style="width: auto;">
      <tr>
        <th scope="row"><?php _e('Select Social Button Project', PPS_LANG_CODE); ?></th>
        <td>
          <?php echo viewPps::ksesString(
            htmlPps::selectbox('params[tpl][use_sss_prj_id]', [
              'value' => isset($this->popup['params']['tpl']['use_sss_prj_id']) ? $this->popup['params']['tpl']['use_sss_prj_id'] : '',
              'options' => $this->sssProjectsForSelect,
            ]),
          ); ?>
        </td>
      </tr>
    </table>
  </div>
  <?php } ?>
</span>