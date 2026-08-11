<?php

class smPps extends modulePps //sm == socialmedia
{
  private $_availableLinks = [];

  public function init()
  {
    parent::init();
    dispatcherPps::addAction('beforePopupEditRender', [$this, 'addAdminAssets']);
  }
  public function addAdminAssets()
  {
    framePps::_()->addScript(PPS_CODE . '.admin.sm', $this->getModPath() . 'js/admin.sm.js', ['jquery']);
  }
  /**
   * Rendering ported 1:1 from gallery-by-supsystic's working implementation
   * (GridGallery_Galleries views/shortcode/gallery.twig macro getSocialIcons()):
   * same style options (box size, font size, transparency, border radius mode,
   * brand/black/white/custom color mode, custom border), just translated to PHP.
   */
  public function generateHtml($popup)
  {
    $socialSharingHtml = apply_filters('supsystic_popup_sm_html', '', $popup);
    if (!empty($socialSharingHtml)) {
      return $socialSharingHtml;
    }
    $this->getAvailableLinks();
    $enabledIcons = $this->_getEnabledIcons($popup);
    if (empty($enabledIcons)) {
      return '';
    }
    $tpl = $popup['params']['tpl'];
    $currFullUrl = uriPps::getFullUrl();
    $currTitle = wp_title('', false);

    $styleColor = !empty($tpl['sm_style_color']) ? $tpl['sm_style_color'] : 'brand';
    $styleRadius = !empty($tpl['sm_style_radius']) ? $tpl['sm_style_radius'] : 'rounded';
    $boxSize = isset($tpl['sm_boxsize']) && $tpl['sm_boxsize'] !== '' ? (int) $tpl['sm_boxsize'] : 30;
    $fontSize = isset($tpl['sm_fontsize']) && $tpl['sm_fontsize'] !== '' ? (int) $tpl['sm_fontsize'] : 18;
    $transparency = isset($tpl['sm_transparency']) && $tpl['sm_transparency'] !== '' ? $tpl['sm_transparency'] : 1;
    $backgroundColor = !empty($tpl['sm_background_color']) ? $tpl['sm_background_color'] : '#ffffff';
    $iconColor = !empty($tpl['sm_icon_color']) ? $tpl['sm_icon_color'] : '#000000';
    $borderType = !empty($tpl['sm_border_type']) ? $tpl['sm_border_type'] : 'none';
    $borderColor = !empty($tpl['sm_border_color']) ? $tpl['sm_border_color'] : '#ffffff';
    $borderWidth = isset($tpl['sm_border_width']) && $tpl['sm_border_width'] !== '' ? (int) $tpl['sm_border_width'] : 0;

    $res = '<div class="ppsSmLinksShell">';
    foreach ($enabledIcons as $lKey) {
      if (!isset($this->_availableLinks[$lKey])) {
        continue;
      }
      $lData = $this->_availableLinks[$lKey];
      $classes = ['ppsSmLink', 'ppsSmLink' . $lKey];
      if ($styleColor == 'black') {
        $classes[] = 'ppsSmLinkBlack';
      } elseif ($styleColor == 'white') {
        $classes[] = 'ppsSmLinkWhite';
      } elseif ($styleColor == 'brand') {
        $classes[] = 'ppsSmLinkBrand';
      }
      if ($styleRadius == 'square') {
        $classes[] = 'ppsSmLinkSquare';
      } elseif ($styleRadius == 'circle') {
        $classes[] = 'ppsSmLinkRound';
      } elseif ($styleRadius == 'rounded') {
        $classes[] = 'ppsSmLinkRounded';
      }
      $style = 'opacity:' . $transparency . ';font-size:' . $fontSize . 'px;width:' . $boxSize . 'px;height:' . $boxSize . 'px;';
      if ($styleColor == 'custom') {
        $style .= 'background:' . $backgroundColor . ';color:' . $iconColor . ';';
        $style .= 'border:' . $borderWidth . 'px ' . $borderType . ' ' . $borderColor . ';';
      } elseif ($styleColor == 'brand') {
        $style .= 'background:' . $lData['brand_primary'] . ';color:' . $lData['brand_secondary'] . ';';
      }
      $shareUrl = str_replace(['{url}', '{title}', '{description}'], [urlencode($currFullUrl), urlencode($currTitle), urlencode($currTitle)], $lData['share_link']);
      $res .=
        '<a target="_blank" href="' .
        esc_url($shareUrl, ['http', 'https', 'viber']) .
        '" class="' .
        esc_attr(implode(' ', $classes)) .
        '" style="' .
        esc_attr($style) .
        '" data-type="' .
        esc_attr($lKey) .
        '" onclick="window.open(this.href, \'ppsSmShare\', \'left=20,top=20,width=500,height=500,toolbar=1,resizable=0\'); return false;"></a>';
    }
    $res .= '<div style="clear: both;"></div>';
    $res .= '</div>';

    return $res;
  }
  /**
   * Multi-select (sm_icons) is the current format. Popups saved before that UI existed
   * only have individual enb_sm_$key checkboxes - migrate those on the fly, on read.
   */
  private function _getEnabledIcons($popup)
  {
    if (isset($popup['params']['tpl']['sm_icons']) && is_array($popup['params']['tpl']['sm_icons'])) {
      return $popup['params']['tpl']['sm_icons'];
    }
    $legacyEnabled = [];
    foreach (array_keys($this->_availableLinks) as $lKey) {
      if (!empty($popup['params']['tpl']['enb_sm_' . $lKey])) {
        $legacyEnabled[] = $lKey;
      }
    }
    return $legacyEnabled;
  }
  public function getAvailableLinks()
  {
    if (empty($this->_availableLinks)) {
      $this->_availableLinks = [
        'facebook' => ['label' => __('Facebook', PPS_LANG_CODE), 'share_link' => 'https://www.facebook.com/sharer.php?u={url}', 'id' => 1, 'brand_primary' => '#3b5998', 'brand_secondary' => '#ffffff'],
        // id 2 was "Google+", discontinued by Google in 2019 - retired for good, never reuse this id.
        'twitter' => ['label' => __('X', PPS_LANG_CODE), 'share_link' => 'https://twitter.com/share?url={url}&text={title}', 'id' => 3, 'brand_primary' => '#55acee', 'brand_secondary' => '#ffffff'],
        // Networks below and their brand colors are kept in sync with GridGalleryPro_Galleries_Model_Galleries::getSocialShareList().
        'pinterest' => ['label' => __('Pinterest', PPS_LANG_CODE), 'share_link' => 'https://pinterest.com/pin/create/link/?url={url}&description={title}', 'id' => 4, 'brand_primary' => '#cc2127', 'brand_secondary' => '#ffffff'],
        'linkedin' => ['label' => __('LinkedIn', PPS_LANG_CODE), 'share_link' => 'https://www.linkedin.com/shareArticle?mini=true&title={title}&url={url}', 'id' => 5, 'brand_primary' => '#3399ff', 'brand_secondary' => '#ffffff'],
        'reddit' => ['label' => __('Reddit', PPS_LANG_CODE), 'share_link' => 'https://reddit.com/submit?url={url}&title={title}', 'id' => 6, 'brand_primary' => '#cee3f8', 'brand_secondary' => '#ffffff'],
        'whatsapp' => ['label' => __('WhatsApp', PPS_LANG_CODE), 'share_link' => 'https://web.whatsapp.com/send?text={title}%20{url}', 'id' => 7, 'brand_primary' => '#43c353', 'brand_secondary' => '#ffffff'],
        'telegram' => ['label' => __('Telegram', PPS_LANG_CODE), 'share_link' => 'https://t.me/share/url?url={url}&text={title}', 'id' => 8, 'brand_primary' => '#229ed9', 'brand_secondary' => '#ffffff'],
        'tumblr' => ['label' => __('Tumblr', PPS_LANG_CODE), 'share_link' => 'https://www.tumblr.com/share/link?url={url}&name={title}', 'id' => 9, 'brand_primary' => '#36465d', 'brand_secondary' => '#ffffff'],
        'vkontakte' => ['label' => __('VK', PPS_LANG_CODE), 'share_link' => 'https://vk.com/share.php?url={url}', 'id' => 10, 'brand_primary' => '#45668e', 'brand_secondary' => '#ffffff'],
        'viber' => ['label' => __('Viber', PPS_LANG_CODE), 'share_link' => 'viber://forward?text={title}%20{url}', 'id' => 12, 'brand_primary' => '#7360f2', 'brand_secondary' => '#ffffff'],
        'livejournal' => ['label' => __('LiveJournal', PPS_LANG_CODE), 'share_link' => 'https://www.livejournal.com/update.bml?subject={title}&event={url}', 'id' => 13, 'brand_primary' => '#3399ff', 'brand_secondary' => '#ffffff'],
      ];
      $this->_availableLinks = apply_filters('supsystic_popup_sm_available_links', $this->_availableLinks);
    }
    return $this->_availableLinks;
  }
  public function getTypeIdByCode($code)
  {
    $this->getAvailableLinks();
    return isset($this->_availableLinks[$code]) ? $this->_availableLinks[$code]['id'] : 0;
  }
  public function getTypeById($id)
  {
    $this->getAvailableLinks();
    $res = [];
    foreach ($this->_availableLinks as $code => $type) {
      if ($type['id'] == $id) {
        $res = $type;
        $res['code'] = $code;
        return $res;
      }
    }
    return false;
  }
  public function getStyleColorOptions()
  {
    return [
      'brand' => __('Brand colors', PPS_LANG_CODE),
      'black' => __('Black', PPS_LANG_CODE),
      'white' => __('White', PPS_LANG_CODE),
      'custom' => __('Custom', PPS_LANG_CODE),
    ];
  }
  public function getStyleRadiusOptions()
  {
    return [
      'square' => __('Square', PPS_LANG_CODE),
      'rounded' => __('Rounded', PPS_LANG_CODE),
      'circle' => __('Round', PPS_LANG_CODE),
    ];
  }
  public function getBorderTypeOptions()
  {
    return [
      'none' => __('None', PPS_LANG_CODE),
      'solid' => __('Solid', PPS_LANG_CODE),
      'dotted' => __('Dotted', PPS_LANG_CODE),
      'dashed' => __('Dashed', PPS_LANG_CODE),
      'double' => __('Double', PPS_LANG_CODE),
      'groove' => __('Groove', PPS_LANG_CODE),
      'ridge' => __('Ridge', PPS_LANG_CODE),
      'inset' => __('Inset', PPS_LANG_CODE),
      'outset' => __('Outset', PPS_LANG_CODE),
    ];
  }
  public function generateCss($popup)
  {
    return str_replace('[PPS_MOD_PATH]', $this->getModPath(), file_get_contents($this->getModDir() . 'sm.css'));
  }
}
