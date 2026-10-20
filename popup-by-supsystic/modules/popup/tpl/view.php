<?php
if (!defined('ABSPATH')) {
  exit();
}
$options = $this->options;
$fields = !empty($options['fields']) ? $options['fields'] : [];
$settings = !empty($options['settings']) ? $options['settings'] : [];
$design = !empty($options['design']) ? $options['design'] : [];
$obj_id = !empty($options['obj_id']) ? $options['obj_id'] : 0;
$designOverlay = isset($design['show_overlay']) ? (bool) $design['show_overlay'] : true;
$withOverlay = !empty($this->hideoverlay) ? false : $designOverlay;

$embed = !empty($this->embed) ? true : false;
$embedClass = '';
if (!empty($embed)) {
  $embedClass = 'ppsgenPopupEmbed';
}
$forceshow = !empty($this->forceshow) ? true : false;
$forceshowClass = '';
if (!empty($forceshow)) {
  $forceshowClass = 'ppsgenPopupForceshow';
}
?>
<?php
$popupClass = !empty($settings['popup_class']) ? $settings['popup_class'] : '';
$popupId = !empty($settings['popup_id']) ? $settings['popup_id'] : '';
// Same rule as the front-end script: the close button is hidden only when the option is explicitly on, so previews match the site
$hideCloseBtn = in_array($settings['hide_close_btn'] ?? '', ['1', 1, true], true);
?>
<?php
$class = '';
switch ($design['popup_contaner_position'] ?? '') {
  case 'top-left':
    $class = 'ppsgenPopupTopLeft';
    break;
  case 'top-center':
    $class = 'ppsgenPopupTopCenter';
    break;
  case 'top-right':
    $class = 'ppsgenPopupTopRight';
    break;
  case 'center-left':
    $class = 'ppsgenPopupCenterLeft';
    break;
  case 'center':
    $class = 'ppsgenPopupCenter';
    break;
  case 'center-right':
    $class = 'ppsgenPopupCenterRight';
    break;
  case 'bottom-left':
    $class = 'ppsgenPopupBottomLeft';
    break;
  case 'bottom-center':
    $class = 'ppsgenPopupBottomCenter';
    break;
  case 'bottom-right':
    $class = 'ppsgenPopupBottomRight';
    break;
  default:
    $class = 'ppsgenPopupCenter';
}
?>

<?php if ($withOverlay && !$embed) { ?>
  <div data-popup-id="<?php echo esc_attr($obj_id); ?>" class="ppsgenPopupOverlay ppsgenPopupOverlay-<?php echo esc_attr($obj_id); ?>">
<?php } ?>
  <div data-popup-id="<?php echo esc_attr($obj_id); ?>" class="ppsgenPopupWrapper  ppsgenPopupWrapper-<?php echo esc_attr($obj_id); ?> <?php echo esc_attr($embedClass); ?>" style="display:none;">
  <div data-popup-id="<?php echo esc_attr($obj_id); ?>" data-popup-var="ppsgenFrontendPopup<?php echo esc_attr($obj_id); ?>" class="ppsgenPopup ppsgenPopup-<?php echo esc_attr($obj_id); ?> <?php echo esc_attr($popupClass); ?> <?php echo esc_attr($class); ?> <?php echo esc_attr(
   $embedClass,
 ); ?> <?php echo esc_attr($forceshowClass); ?>" id="<?php echo esc_attr($popupId); ?> ">

  <div class="ppsgenPopupContent ppsgenPopupContent-<?php echo esc_attr($obj_id); ?>">
    <div class="ppsgenPopupClose ppsgenPopupClose-<?php echo esc_attr($obj_id); ?>"<?php echo $hideCloseBtn ? ' style="display:none;"' : ''; ?>><span class="fappsgen-icon-close"></span></div>
    <div class="ppsgenPopupScrollable ppsgenPopupScrollable-<?php echo esc_attr($obj_id); ?>">
    <?php if (!empty($fields)) { ?>
      <?php $tree = $this->popupEditBuildFieldsTree($fields); ?>
      <?php $this->defaultStepSlug = (string) ($settings['def_step_slug'] ?? ''); ?>
      <?php foreach ($tree as $field) { ?>
        <?php $html = $this->popupFrontendRenderField($field); ?>
        <?php $html = htmlspecialchars_decode(stripslashes($html)); ?>
        <?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo $this->frame->core('helper')->wpKses($html); ?>
      <?php } ?>
    <?php } ?>
    </div>
  </div>

  </div>
  
  </div>
<?php if ($withOverlay && !$embed) { ?>
  </div>
<?php } ?>
