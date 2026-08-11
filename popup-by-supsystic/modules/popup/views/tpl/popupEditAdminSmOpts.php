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
    <label><?php _e('Social Icons', PPS_LANG_CODE); ?></label>
    <?php
    $smLinkOptions = [];
    foreach ($this->smLinks as $smKey => $smData) {
      $smLinkOptions[$smKey] = $smData['label'];
    }
    $selectedSmIcons = isset($this->popup['params']['tpl']['sm_icons']) && is_array($this->popup['params']['tpl']['sm_icons']) ? $this->popup['params']['tpl']['sm_icons'] : [];
    ?>
    <?php echo viewPps::ksesString(
      htmlPps::selectlist('params[tpl][sm_icons]', [
        'options' => $smLinkOptions,
        'value' => $selectedSmIcons,
        'attrs' => 'class="chosen chosen-responsive" data-placeholder="' . __('Choose social networks', PPS_LANG_CODE) . '"',
      ]),
    ); ?>
  </div>
  <div class="ppsPopupOptRow">
    <table class="form-table ppsSubShellOptsTbl">
      <tr>
        <th scope="row"><?php _e('Box size', PPS_LANG_CODE); ?></th>
        <td>
          <?php echo viewPps::ksesString(
            htmlPps::input('params[tpl][sm_boxsize]', [
              'type' => 'number',
              'value' => isset($this->popup['params']['tpl']['sm_boxsize']) && $this->popup['params']['tpl']['sm_boxsize'] !== '' ? $this->popup['params']['tpl']['sm_boxsize'] : 30,
              'attrs' => 'min="1" style="width: 100px;"',
            ]),
          ); ?>
          <?php _e('pixels', PPS_LANG_CODE); ?>
        </td>
      </tr>
      <tr>
        <th scope="row"><?php _e('Font size', PPS_LANG_CODE); ?></th>
        <td>
          <?php echo viewPps::ksesString(
            htmlPps::input('params[tpl][sm_fontsize]', [
              'type' => 'number',
              'value' => isset($this->popup['params']['tpl']['sm_fontsize']) && $this->popup['params']['tpl']['sm_fontsize'] !== '' ? $this->popup['params']['tpl']['sm_fontsize'] : 18,
              'attrs' => 'min="1" style="width: 100px;"',
            ]),
          ); ?>
          <?php _e('pixels', PPS_LANG_CODE); ?>
        </td>
      </tr>
      <tr>
        <th scope="row"><?php _e('Buttons transparency', PPS_LANG_CODE); ?></th>
        <td>
          <?php
          $transparencyOptions = [];
          for ($i = 0; $i <= 10; $i++) {
            $transparencyOptions[(string) round(1 - $i / 10, 1)] = $i * 10 . '%';
          }
          ?>
          <?php echo viewPps::ksesString(
            htmlPps::selectbox('params[tpl][sm_transparency]', [
              'value' => isset($this->popup['params']['tpl']['sm_transparency']) && $this->popup['params']['tpl']['sm_transparency'] !== '' ? $this->popup['params']['tpl']['sm_transparency'] : 1,
              'options' => $transparencyOptions,
            ]),
          ); ?>
        </td>
      </tr>
      <tr>
        <th scope="row"><?php _e('Buttons border radius', PPS_LANG_CODE); ?></th>
        <td>
          <?php echo viewPps::ksesString(
            htmlPps::selectbox('params[tpl][sm_style_radius]', [
              'value' => isset($this->popup['params']['tpl']['sm_style_radius']) && !empty($this->popup['params']['tpl']['sm_style_radius']) ? $this->popup['params']['tpl']['sm_style_radius'] : 'rounded',
              'options' => $this->smStyleRadiusOptions,
            ]),
          ); ?>
        </td>
      </tr>
      <tr>
        <th scope="row"><?php _e('Buttons style', PPS_LANG_CODE); ?></th>
        <td>
          <?php echo viewPps::ksesString(
            htmlPps::selectbox('params[tpl][sm_style_color]', [
              'value' => isset($this->popup['params']['tpl']['sm_style_color']) && !empty($this->popup['params']['tpl']['sm_style_color']) ? $this->popup['params']['tpl']['sm_style_color'] : 'brand',
              'attrs' => 'class="ppsSmStyleColorSelect"',
              'options' => $this->smStyleColorOptions,
            ]),
          ); ?>
        </td>
      </tr>
      <tr class="ppsSmCustomStyleRow">
        <th scope="row"><?php _e('Background color (custom style)', PPS_LANG_CODE); ?></th>
        <td>
          <?php echo viewPps::ksesString(
            htmlPps::colorpicker('params[tpl][sm_background_color]', [
              'value' => isset($this->popup['params']['tpl']['sm_background_color']) && !empty($this->popup['params']['tpl']['sm_background_color']) ? $this->popup['params']['tpl']['sm_background_color'] : '#ffffff',
            ]),
          ); ?>
        </td>
      </tr>
      <tr class="ppsSmCustomStyleRow">
        <th scope="row"><?php _e('Icon color (custom style)', PPS_LANG_CODE); ?></th>
        <td>
          <?php echo viewPps::ksesString(
            htmlPps::colorpicker('params[tpl][sm_icon_color]', [
              'value' => isset($this->popup['params']['tpl']['sm_icon_color']) && !empty($this->popup['params']['tpl']['sm_icon_color']) ? $this->popup['params']['tpl']['sm_icon_color'] : '#000000',
            ]),
          ); ?>
        </td>
      </tr>
      <tr class="ppsSmCustomStyleRow">
        <th scope="row"><?php _e('Button border type (custom style)', PPS_LANG_CODE); ?></th>
        <td>
          <?php echo viewPps::ksesString(
            htmlPps::selectbox('params[tpl][sm_border_type]', [
              'value' => isset($this->popup['params']['tpl']['sm_border_type']) && !empty($this->popup['params']['tpl']['sm_border_type']) ? $this->popup['params']['tpl']['sm_border_type'] : 'none',
              'options' => $this->smBorderTypeOptions,
            ]),
          ); ?>
        </td>
      </tr>
      <tr class="ppsSmCustomStyleRow">
        <th scope="row"><?php _e('Button border color (custom style)', PPS_LANG_CODE); ?></th>
        <td>
          <?php echo viewPps::ksesString(
            htmlPps::colorpicker('params[tpl][sm_border_color]', [
              'value' => isset($this->popup['params']['tpl']['sm_border_color']) && !empty($this->popup['params']['tpl']['sm_border_color']) ? $this->popup['params']['tpl']['sm_border_color'] : '#ffffff',
            ]),
          ); ?>
        </td>
      </tr>
      <tr class="ppsSmCustomStyleRow">
        <th scope="row"><?php _e('Button border width (custom style)', PPS_LANG_CODE); ?></th>
        <td>
          <?php echo viewPps::ksesString(
            htmlPps::input('params[tpl][sm_border_width]', [
              'type' => 'number',
              'value' => isset($this->popup['params']['tpl']['sm_border_width']) && $this->popup['params']['tpl']['sm_border_width'] !== '' ? $this->popup['params']['tpl']['sm_border_width'] : 1,
              'attrs' => 'min="0" style="width: 100px;"',
            ]),
          ); ?>
          <?php _e('pixels', PPS_LANG_CODE); ?>
        </td>
      </tr>
    </table>
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
