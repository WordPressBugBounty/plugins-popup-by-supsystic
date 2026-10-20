<?php
if (!defined('ABSPATH')) {
  exit();
}
$objId = $this->objId;
$formSubmits = !empty($this->formSubmits) ? $this->formSubmits : [];
?>
<?php if (!empty($formSubmits) && is_array($formSubmits)) { ?>
  <div class="ppsgenFormSubmitsWrapper">
    <?php foreach ($formSubmits as $submit) { ?>
      <?php
      $contactId = !empty($submit['contact_id']) ? (int) $submit['contact_id'] : 0;
      $contactIsExist = !empty($contactId) ? $this->frame->getModule('contact')->getModel()->contactExist($contactId) : false;
      $contactIsVerified = !empty($contactIsExist) ? $this->frame->getModule('contact')->getModel()->contactIsverified($contactId) : false;
      $submitIsVerified = strpos($submit['fields_values'] ?? '', '"unverified_submit":"1"') === false ? true : false;
      ?>
      <div class="ppsgenFormSubmitsRow">
        <div class="ppsgenFormSubmitsRowTitle">
          <div class="ppsgenFormSubmitsRowTitleEl"><?php esc_html_e('Submit ID:', 'ppsgen'); ?> <span><?php echo esc_html($submit['id']); ?></span></div>
          <?php
          $userId = isset($submit['wp_user_id']) ? (int) $submit['wp_user_id'] : 0;
          $editUserUrl = $userId ? get_edit_user_link($userId) : '';
          ?>
          <div class="ppsgenFormSubmitsRowTitleEl">
              <?php esc_html_e('User ID:', 'ppsgen'); ?>
              <?php if ($editUserUrl) { ?>
                  <a target="_blank" href="<?php echo esc_url($editUserUrl); ?>">
                      <?php echo esc_html($userId); ?>
                  </a>
              <?php } else { ?>
                  <span><?php echo esc_html($userId); ?></span>
              <?php } ?>
          </div>
          <div class="ppsgenFormSubmitsRowTitleEl"><?php esc_html_e('Form:', 'ppsgen'); ?> <span><a href="?page=ppsgen&tab=form_edit&id=<?php echo esc_attr($submit['form_id']); ?>"><?php echo esc_html($submit['form_id']); ?></a></span></div>
          <?php $submitGeo = $this->getFrame()->core('helper')->getGeoLiteLocationLabelByIp($submit['ip'] ?? ''); ?>
          <div class="ppsgenFormSubmitsRowTitleEl"><?php esc_html_e('IP:', 'ppsgen'); ?> <span><?php echo esc_html($submit['ip']); ?></span><?php if (!empty($submitGeo)) { ?> <span>(<?php echo esc_html($submitGeo); ?>)</span><?php } ?></div>
          <div class="ppsgenFormSubmitsRowTitleEl"><span><?php echo esc_html($submit['created_date']); ?></span></div>
          <div class="ppsgenFormSubmitsRowTitleBtns">

            <?php if (!$submitIsVerified) { ?>
              <a class="ppsgenTableListBtn ppsgenFormSubmitsRowTitleVerifySubmitContactBtn" data-obj="<?php echo esc_attr($submit['id']); ?>" title="<?php echo esc_attr__(
  'Verify Submit',
  'ppsgen',
); ?>"><span class="dashicons dashicons-feedback"></span><span class="dashicons dashicons-yes-alt"></span></a>
            <?php } ?>

            <?php if (!$submitIsVerified) { ?>
              <span title="<?php echo esc_attr__('Submit Is Not Verified', 'ppsgen'); ?>" class="ppsgenVerifiedIcon ppsgenFormSubmitsRowVerifySubmitIcon"><span class="dashicons dashicons-feedback"></span><span class="dashicons dashicons-dismiss"></span></span>
            <?php } else { ?>
              <span title="<?php echo esc_attr__('Submit Is Verified', 'ppsgen'); ?>" class="ppsgenVerifiedIcon ppsgenFormSubmitsRowVerifySubmitIcon"><span class="dashicons dashicons-feedback"></span><span class="dashicons dashicons-yes-alt"></span></span>
            <?php } ?>

            <a class="ppsgenTableListBtn ppsgenFormSubmitsRowTitleDeleteBtn" data-obj="<?php echo esc_attr($submit['id']); ?>" title="<?php echo esc_attr__('Delete Submit', 'ppsgen'); ?>"><span class="dashicons dashicons-trash"></span></a>
          </div>
        </div>
        <div class="ppsgenFormSubmitsRowData">
          <?php if (!empty($submit['fields_values'])) { ?>
            <?php $fields = $this->getModel()->_prepareValueFromStore($submit['fields_values']); ?>
            <?php if (!empty($fields) && is_array($fields)) { ?>
              <?php foreach ($fields as $key => $field) { ?>
                <?php if (empty($key)) {
                  continue;
                } ?>
                <div class="ppsgenFormSubmitsRowDataField">
                  <span class="ppsgenFormSubmitsRowDataFieldKey"><?php echo esc_html($key); ?>:</span>                  
                  <span class="ppsgenFormSubmitsRowDataFieldValue">
                    <?php if (!empty($field) && is_array($field)) { ?>
                      <?php if (isset($field[0]) && empty($field[0])) { ?>
                        <?php unset($field[0]); ?>
                      <?php } ?>
                      <?php $field = $this->getModel()->_prepareValueToStore($field, 'json'); ?>
                      <?php echo esc_html($field); ?>
                    <?php } else { ?>
                      <?php echo esc_html($field); ?>
                    <?php } ?>
                  </span>
                </div>
              <?php } ?>
            <?php } ?>
          <?php } else { ?>
            <?php esc_html_e('There are no available field data in this submission.', 'ppsgen'); ?>
          <?php } ?>    
        </div>
      </div>
    <?php } ?>
  </div>
<?php } else { ?>
  <div class="ppsgenFormSubmitsWrapperWarning">
    <?php esc_html_e('This Contact does not have any Form Submits yet.', 'ppsgen'); ?>
  </div>
<?php } ?>
