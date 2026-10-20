<?php

if (!defined('ABSPATH')) {
  exit();
}

class ContactTagSubmoduleViewPpsgen extends BaseViewPpsgen
{
  public function renderContactTagList($params)
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
    $data = $model->getContactsTags([
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
    $html = $this->renderTpl('list', 'contact', 'contact_tag');
    return $html;
  }

  public function renderContactTagEdit($params)
  {
    $objId = !empty($params['id']) ? $params['id'] : false;
    $options = [];
    if (!empty($objId)) {
      $options = $this->getModel()->getAllOptions($objId, 'contact_tag');
      $tagOptions = $this->getModel()->getContactTagById($objId);
      $options = array_merge((array) $options, (array) $tagOptions);
    }
    if (empty($options) && !empty($objId)) {
      return esc_html__('Cant find Contact Tag with specific ID for editing', 'ppsgen');
    }
    $options['obj_id'] = $objId;
    $this->assign('options', $options);
    $html = $this->renderTpl('edit', 'contact', 'contact_tag');
    return $html;
  }

  public function renderContactEditOptions($options)
  {
    $options['selected_contact_tags'] = $this->getModel()->getTagsByContactId($options['obj_id'], false, true);
    $allContactTags = $this->getModel()->getAllContactTags(true);
    $this->assign('options', $options);
    $this->assign('allContactTags', $allContactTags);
    $html = $this->renderTpl('edit_contact', 'contact', 'contact_tag');
    return $html;
  }
}
