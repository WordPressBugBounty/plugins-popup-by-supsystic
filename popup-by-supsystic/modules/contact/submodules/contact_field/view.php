<?php

if (!defined('ABSPATH')) {
  exit();
}

class ContactFieldSubmoduleViewPpsgen extends BaseViewPpsgen
{
  public function renderContactFieldList($params)
  {
    $page = max(1, intval($this->getFrame()->core('request')->getVar('page', 'post') ?? 1));
    $perPage = intval($this->getFrame()->core('request')->getVar('per_page', 'post') ?? 100);
    $orderBy = $this->getFrame()->core('request')->getVar('order_by', 'post') ?? 'id';
    $orderDir = $this->getFrame()->core('request')->getVar('order_dir', 'post') === 'DESC' ? 'DESC' : 'ASC';
    $filters = [];
    if (!empty($this->getFrame()->core('request')->getVar('filter_id', 'post'))) {
      $filters['id'] = intval($this->getFrame()->core('request')->getVar('filter_id', 'post'));
    }
    if (!empty($this->getFrame()->core('request')->getVar('filter_slug', 'post'))) {
      $filters['slug'] = $this->getFrame()->core('request')->getVar('filter_slug', 'post');
    }
    if (!empty($this->getFrame()->core('request')->getVar('filter_label', 'post'))) {
      $filters['label'] = $this->getFrame()->core('request')->getVar('filter_label', 'post');
    }
    if (!empty($this->getFrame()->core('request')->getVar('filter_field_type', 'post'))) {
      $filters['field_type'] = $this->getFrame()->core('request')->getVar('filter_field_type', 'post');
    }
    if (!empty($this->getFrame()->core('request')->getVar('filter_is_enabled', 'post'))) {
      $filters['is_enabled'] = $this->getFrame()->core('request')->getVar('filter_is_enabled', 'post');
    }
    if (!empty($this->getFrame()->core('request')->getVar('filter_is_default', 'post'))) {
      $filters['is_default'] = $this->getFrame()->core('request')->getVar('filter_is_default', 'post');
    }
    if (!empty($this->getFrame()->core('request')->getVar('filter_order_index', 'post'))) {
      $filters['order_index'] = $this->getFrame()->core('request')->getVar('filter_order_index', 'post');
    }
    if (!empty($this->getFrame()->core('request')->getVar('filter_created_at', 'post'))) {
      $filters['created_at'] = $this->getFrame()->core('request')->getVar('filter_created_at', 'post');
    }
    if (!empty($this->getFrame()->core('request')->getVar('filter_updated_at', 'post'))) {
      $filters['updated_at'] = $this->getFrame()->core('request')->getVar('filter_updated_at', 'post');
    }
    $model = $this->getModel();
    $data = $model->getContactFields([
      'page' => $page,
      'per_page' => $perPage,
      'order_by' => $orderBy,
      'order_dir' => $orderDir,
      'filters' => $filters,
    ]);
    $this->assign([
      'rows' => $data['rows'],
      'total' => $data['total'],
      'page' => $page,
      'per_page' => $perPage,
      'order_by' => $orderBy,
      'order_dir' => $orderDir,
      'filters' => $filters,
    ]);
    $html = $this->renderTpl('list', 'contact', 'contact_field');
    return $html;
  }

  public function renderContactFieldEdit($params)
  {
    $objId = !empty($params['id']) ? $params['id'] : false;
    $options = [];
    if (!empty($objId)) {
      $options = $this->getModel()->getAllOptions($objId, 'contact_field');
      $fieldOptions = $this->getModel()->getContactFieldById($objId);
      $options = array_merge((array) $options, (array) $fieldOptions);
      if (!empty($options['options'])) {
        $options['options'] = $this->getModel()->_prepareValueFromStore($options['options']);
      }
      if (!empty($options['settings'])) {
        $options['settings'] = $this->getModel()->_prepareValueFromStore($options['settings']);
      }
    }
    if (empty($options) && !empty($objId)) {
      return esc_html__('Cant find Contact Field with specific ID for editing', 'ppsgen');
    }
    $options['obj_id'] = $objId;
    $this->assign('options', $options);
    $contactFieldTypes = $this->getModel()->getContactFieldTypes();
    $this->assign('contactFieldTypes', $contactFieldTypes);
    $this->assign('contactFieldProBannerHtml', $this->getContactFieldProBannerHtml());
    $html = $this->renderTpl('edit', 'contact', 'contact_field');
    return $html;
  }

  protected function getContactFieldProBannerHtml(): string
  {
    $url = 'https://supsystic.com/plugins/wordpress-popup-plugin/?utm_source=ces_plugin&utm_medium=feature&utm_campaign=custom_fields_pro';
    return '<div class="ppsgenProOfferNotice ppsgenProOfferNoticeTab">'
      . '<div class="ppsgenProOfferNoticeHero">'
      . '<div class="ppsgenProOfferNoticeEyebrow">' . esc_html__('Advanced custom fields in PRO', 'ppsgen') . '</div>'
      . '<div class="ppsgenProOfferNoticeBadges">'
      . '<span class="ppsgenProOfferNoticeBadge">CRM</span>'
      . '<span class="ppsgenProOfferNoticeBadge">CUSTOM FIELDS</span>'
      . '<span class="ppsgenProOfferNoticeBadge">WP</span>'
      . '<span class="ppsgenProOfferNoticeBadge">WOO</span>'
      . '</div>'
      . '<h3 class="ppsgenProOfferNoticeTitle">' . esc_html__('Unlock advanced field types for your CRM', 'ppsgen') . '</h3>'
      . '<p class="ppsgenProOfferNoticeText">' . esc_html__('Build richer contact profiles with file uploads, time inputs, color pickers, address search, language and country lists, plus dynamic WordPress and WooCommerce object fields. Date, range and rating fields are already free.', 'ppsgen') . '</p>'
      . '<div class="ppsgenProOfferNoticeCouponLabel">' . esc_html__('Yearly licence · 1 site', 'ppsgen') . '</div>'
      . '<div class="ppsgenProOfferNoticeCoupon"><span><strong>$99</strong> / year</span></div>'
      . '<div class="ppsgenProOfferNoticeSubtext">' . esc_html__('$99 stays $99 when you renew · No contact-based pricing · 30-day money-back guarantee', 'ppsgen') . '</div>'
      . '<div class="ppsgenProOfferNoticeActions">'
      . '<a class="ppsgenProOfferNoticeBtn ppsgenProOfferNoticeBtnPrimary" href="' . esc_url($url) . '" target="_blank" rel="noopener noreferrer">' . esc_html__('Unlock PRO Custom Fields', 'ppsgen') . '</a>'
      . '</div>'
      . '</div>'
      . '<div class="ppsgenProOfferNoticeFeatures">'
      . '<div class="ppsgenProOfferNoticeFeaturesTitle">' . esc_html__('What you unlock in PRO', 'ppsgen') . '</div>'
      . '<ul class="ppsgenProOfferNoticeFeatureList">'
      . '<li>' . esc_html__('File uploads with validation rules, size limits, and multiple files', 'ppsgen') . '</li>'
      . '<li>' . esc_html__('Time, month, week, URL and color fields', 'ppsgen') . '</li>'
      . '<li>' . esc_html__('Address search with Google API plus language and country lists', 'ppsgen') . '</li>'
      . '<li>' . esc_html__('Dynamic lists from posts, pages, categories, and tags', 'ppsgen') . '</li>'
      . '<li>' . esc_html__('Woo categories, tags, attributes, and products as CRM fields', 'ppsgen') . '</li>'
      . '<li>' . esc_html__('Form-level overrides and richer frontend controls for advanced fields', 'ppsgen') . '</li>'
      . '</ul>'
      . '</div>'
      . '</div>';
  }

  public function renderContactEditOptions($options)
  {
    $contactFields = $this->getModel()->getAllContactFields(false);
    $contactFieldTypes = $this->getModel()->getContactFieldTypes();
    $contactId = !empty($options['obj_id']) ? (int) $options['obj_id'] : 0;
    $this->assign('contactFields', $contactFields);
    $this->assign('contactFieldTypes', $contactFieldTypes);
    $this->assign('contactFieldLogsGrouped', $contactId > 0 ? $this->getFrame()->getModule('contact')->getModel()->getContactFieldLogsGrouped($contactId) : []);
    $this->assign('options', $options);
    $html = $this->renderTpl('edit_contact_fields', 'contact', 'contact_field');
    return $html;
  }

  public function renderContactFieldEditSettings($options)
  {
    $this->assign('options', $options);
    $html = $this->renderTpl('edit_field_settings', 'contact', 'contact_field');
    return $html;
  }
}
