<?php
class optionsViewPps extends viewPps {
	private $_news = array();
	public function getNewFeatures() {
		$res = array();
		$readmePath = PPS_DIR. 'readme.txt';
		if(file_exists($readmePath)) {
			$readmeContent = @file_get_contents($readmePath);
			if(!empty($readmeContent)) {
				$matchedData = '';
				if(preg_match('/= '. PPS_VERSION. ' =(.+)=.+=/isU', $readmeContent, $matches)) {
					$matchedData = $matches[1];
				} elseif(preg_match('/= '. PPS_VERSION. ' =(.+)/is', $readmeContent, $matches)) {
					$matchedData = $matches[1];
				}
				$matchedData = trim($matchedData);
				if(!empty($matchedData)) {
					$res = array_map('trim', explode("\n", $matchedData));
				}
			}
		}
		return $res;
	}
    public function getAdminPage() {
		$tabs = $this->getModule()->getTabs();
		$activeTab = $this->getModule()->getActiveTab();
		$content = 'No tab content found - ERROR';
		if(isset($tabs[ $activeTab ]) && isset($tabs[ $activeTab ]['callback'])) {
			//framePps::_()->getModule('supsystic_promo')->getModel()->saveUsageStat('tab.'. $activeTab);
			$content = call_user_func($tabs[ $activeTab ]['callback']);
		}
		$activeParentTabs = array();
		foreach($tabs as $tabKey => $tab) {
			if($tabKey == $activeTab && isset($tab['child_of'])) {
				$activeTab = $tab['child_of'];
				//$activeParentTabs[] = $tab['child_of'];
			}
		}
		framePps::_()->addJSVar('adminOptionsPps', 'ppsActiveTab', $activeTab);
		$this->assign('tabs', $tabs);
		$this->assign('activeTab', $activeTab);
		$this->assign('content', $content);
		$this->assign('mainUrl', $this->getModule()->getTabUrl());
		$this->assign('activeParentTabs', $activeParentTabs);
		$this->assign('breadcrumbs', framePps::_()->getModule('admin_nav')->getView()->getBreadcrumbs());
		$this->assign('mainLink', framePps::_()->getModule('supsystic_promo')->getMainLink());

        parent::display('optionsAdminPage');
    }
	public function sortOptsSet($a, $b) {
		if($a['weight'] > $b['weight'])
			return -1;
		if($a['weight'] < $b['weight'])
			return 1;
		return 0;
	}
	public function getTabContent() {
		framePps::_()->addScript('admin.mainoptions', $this->getModule()->getModPath(). 'js/admin.mainoptions.js');
		return parent::getContent('optionsAdminMain');
	}
	public function serverSettings() {
		global $wpdb;
		$this->assign('systemInfo', array(
            'Operating System' => array('value' => PHP_OS),
            'PHP Version' => array('value' => PHP_VERSION),
            'Server Software' => array('value' => reqPps::getVar('SERVER_SOFTWARE', 'server')),
			'MySQL' => array('value' =>  $wpdb->db_version()),
            'PHP Allow URL Fopen' => array('value' => ini_get('allow_url_fopen') ? 'Yes' : 'No'),
            'PHP Memory Limit' => array('value' => ini_get('memory_limit')),
            'PHP Max Post Size' => array('value' => ini_get('post_max_size')),
            'PHP Max Upload Filesize' => array('value' => ini_get('upload_max_filesize')),
            'PHP Max Script Execute Time' => array('value' => ini_get('max_execution_time')),
            'PHP EXIF Support' => array('value' => extension_loaded('exif') ? 'Yes' : 'No'),
            'PHP EXIF Version' => array('value' => phpversion('exif')),
            'PHP XML Support' => array('value' => extension_loaded('libxml') ? 'Yes' : 'No', 'error' => !extension_loaded('libxml')),
            'PHP CURL Support' => array('value' => extension_loaded('curl') ? 'Yes' : 'No', 'error' => !extension_loaded('curl')),
        ));
		return parent::display('_serverSettings');
	}
	public function getSettingsTabContent() {
		framePps::_()->addScript('admin.settings', $this->getModule()->getModPath(). 'js/admin.settings.js');
		framePps::_()->getModule('templates')->loadJqueryUi();

		$options = framePps::_()->getModule('options')->getAll();
		$this->assign('options', $options);
		$this->assign('exportAllSubscribersUrl', uriPps::mod('subscribe', 'getWpCsvList',  '_wpnonce='.wp_create_nonce('pps_nonce_export')));
		return parent::getContent('optionsSettingsTabContent');
	}
}
