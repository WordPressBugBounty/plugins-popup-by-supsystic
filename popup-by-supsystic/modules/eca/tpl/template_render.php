<?php
if (!defined('ABSPATH')) {
  exit();
}
$component = $this->component;
$helper = $this->helper;
$fields = $this->fields;
$design = $this->design;
?>

<?php
$cssInline = '';

$style = $component->value($design['template_email_background'] ?? null, '#ffffff');
if (!empty($style)) {
  $cssInline .= "
   background: {$style};
   background-color: {$style};
   ";
}

$fontFamilyKey = $component->value($design['template_font_family'] ?? null, 'Default');

$fontStyle = $component->getEmailFonts(false, $fontFamilyKey);

$cssInline .= $fontStyle;

$html = '';

$html .= '<body bgcolor="' . esc_attr($style) . '" style="margin:0; padding:0; ' . esc_attr($cssInline) . '">';

$html .= '<table width="100%" border="0" cellspacing="0" cellpadding="0" style="border-collapse:collapse; ' . esc_attr($cssInline) . '">';

$style = $component->value($design['template_container_position'] ?? null, 'center');

$marginT = $component->value($design['template_margin']['top'] ?? null, '0');
$marginTU = $component->cvalue($design['template_margin']['top_unit'] ?? null, 'px');
$marginB = $component->value($design['template_margin']['bottom'] ?? null, '0');
$marginBU = $component->cvalue($design['template_margin']['bottom_unit'] ?? null, 'px');
$marginL = $component->value($design['template_margin']['left'] ?? null, '0');
$marginLU = $component->cvalue($design['template_margin']['left_unit'] ?? null, 'px');
$marginR = $component->value($design['template_margin']['right'] ?? null, '0');
$marginRU = $component->cvalue($design['template_margin']['right_unit'] ?? null, 'px');
$cssInline = "
 padding-top: {$marginT}{$marginTU};
 padding-bottom: {$marginB}{$marginBU};
 padding-left: {$marginL}{$marginLU};
 padding-right: {$marginR}{$marginRU};
";

$html .= '<tr><td align="' . esc_attr($style) . '" style="' . esc_attr($cssInline) . '">';
$style = $component->value($design['template_container_background'] ?? null, '#ffffff');
$cssInline = '';
if (!empty($style)) {
  $cssInline .= "
    background: {$style};
    background-color: {$style};
    ";
}
$style = $component->value($design['template_text_position'] ?? null, 'center');
if (!empty($style)) {
  $cssInline .= "
    text-align: {$style};
    ";
}

$widthSelect = $component->value($design['template_width_select'] ?? null, 'auto');
$width = $component->value($design['template_width'] ?? null, '100');
$widthUnit = $component->cvalue($design['template_width_unit'] ?? null, '%');
if ($widthSelect == 'manual') {
  $cssInline .= "width: {$width}{$widthUnit};";
} elseif ($widthSelect == 'auto') {
  $cssInline .= 'width: auto;';
} else {
  $cssInline .= "width: {$widthSelect};";
}

$widthSelect = $component->value($design['template_max_width_select'] ?? null, 'auto');
$width = $component->value($design['template_max_width'] ?? null, '100');
$widthUnit = $component->cvalue($design['template_max_width_unit'] ?? null, '%');
if ($widthSelect == 'manual') {
  $cssInline .= "max-width: {$width}{$widthUnit};";
} elseif ($widthSelect == 'auto') {
  $cssInline .= 'max-width: auto;';
} else {
  $cssInline .= "max-width: {$widthSelect};";
}

$html .= '<table cellspacing="0" cellpadding="0" style="border-collapse:collapse; ' . esc_attr($cssInline) . '">';

$marginT = $component->value($design['template_padding']['top'] ?? null, '0');
$marginTU = $component->cvalue($design['template_padding']['top_unit'] ?? null, 'px');
$marginB = $component->value($design['template_padding']['bottom'] ?? null, '0');
$marginBU = $component->cvalue($design['template_padding']['bottom_unit'] ?? null, 'px');
$marginL = $component->value($design['template_padding']['left'] ?? null, '0');
$marginLU = $component->cvalue($design['template_padding']['left_unit'] ?? null, 'px');
$marginR = $component->value($design['template_padding']['right'] ?? null, '0');
$marginRU = $component->cvalue($design['template_padding']['right_unit'] ?? null, 'px');
$cssInline = "
   padding-top: {$marginT}{$marginTU};
   padding-bottom: {$marginB}{$marginBU};
   padding-left: {$marginL}{$marginLU};
   padding-right: {$marginR}{$marginRU};
  ";

$border = $component->value($design['template_border'] ?? null, '0');
$borderUnit = $component->cvalue($design['template_border_unit'] ?? null, 'px');
$borderStyle = $component->value($design['template_border_style'] ?? null, 'solid');
$borderColor = $component->value($design['template_border_color'] ?? null, '');
if (!empty($border)) {
  $cssInline .= "
       border: {$border}{$borderUnit} {$borderStyle} {$borderColor};
       ";
}

$html .= '<tr><td style="' . esc_attr($cssInline) . '">';

if (!empty($fields)) {
  foreach ($fields as $key => $field) {
    $fields[$key]['settings'] = $field;
  }
  $tree = $this->templateEditBuildFieldsTree($fields);
  foreach ($tree as $field) {
    $fieldHtml = $this->templateFrontendRenderField($field);
    $fieldHtml = htmlspecialchars_decode(stripslashes($fieldHtml));
    $html .= $helper->wpKses($fieldHtml);
  }
}

$html .= '</td></tr>';
$html .= '</table>';
$html .= '</td></tr>';
$html .= '</table>';
$html .= '</body>';

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo $helper->wpKses($html);

?>
