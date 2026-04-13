<?php
class ControllerExtensionModulePersistentCart extends Controller {
	// Module Unifier
	private $moduleName;
	private $moduleNameSmall;
    private $modulePath;
	private $moduleData_module = 'persistentcart_module';
	private $moduleModel;
    private $version;
    private $extensionLink;
	// Module Unifier


    public function __construct($registry) {
        parent::__construct($registry);
        $this->config->load('isenselabs/persistentcart');
        $this->moduleName       = $this->config->get('persistentcart_name');
        $this->moduleNameSmall  = strtolower($this->config->get('persistentcart_name'));
        $this->modulePath       = $this->config->get('persistentcart_path');
        $this->moduleModel      = $this->config->get('persistentcart_model');
        $this->version          = $this->config->get('persistentcart_version');

        $this->extensionLink    = $this->url->link($this->config->get('persistentcart_extensionLink'), 'user_token=' . $this->session->data['user_token'].$this->config->get('persistentcart_extensionLink_type'), 'SSL');

    }

    public function index() {
				$this->document->addScript('view/javascript/summernote/summernote.min.js');
				$this->document->addStyle('view/javascript/summernote/summernote.css');

		// Module Unifier
				$data['moduleName'] = $this->moduleName;
        $data['modulePath'] = $this->modulePath;
				$data['moduleNameSmall'] = $this->moduleNameSmall;
				$data['moduleData_module'] = $this->moduleData_module;
				$data['moduleModel'] = $this->moduleModel;
		// Module Unifier

        $this->load->language($this->modulePath);
        $this->load->model($this->modulePath);
        $this->load->model('setting/store');
				$this->load->model('setting/setting');
        $this->load->model('localisation/language');
        $this->load->model('design/layout');

        $catalogURL = $this->getCatalogURL();

        $this->document->addStyle('view/stylesheet/'.$this->moduleNameSmall.'/'.$this->moduleNameSmall.'.css');

        $this->document->setTitle($this->language->get('heading_title'));

        if(!isset($this->request->get['store_id'])) {
           $this->request->get['store_id'] = 0;
        }

        $store = $this->getCurrentStore($this->request->get['store_id']);

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            if (!empty($_POST['OaXRyb1BhY2sgLSBDb21'])) {
                $this->request->post[$this->moduleName]['LicensedOn'] = $_POST['OaXRyb1BhY2sgLSBDb21'];
            }

            if (!empty($_POST['cHRpbWl6YXRpb24ef4fe'])) {
                $this->request->post[$this->moduleName]['License'] = json_decode(base64_decode($_POST['cHRpbWl6YXRpb24ef4fe']), true);
            }

            $this->model_setting_setting->editSetting($this->moduleName, $this->request->post, $this->request->post['store_id']);
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link($this->modulePath, 'store_id='.$this->request->post['store_id'] . '&user_token=' . $this->session->data['user_token'], 'SSL'));
        }

			if (isset($this->session->data['success'])) {
				$data['success'] = $this->session->data['success'];
				unset($this->session->data['success']);
			} else {
				$data['success'] = '';
			}

			if (isset($this->error['warning'])) {
				$data['error_warning'] = $this->error['warning'];
			} else {
				$data['error_warning'] = '';
			}

        $data['breadcrumbs']   = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], 'SSL'),
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_module'),
            'href' => $this->extensionLink,
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link($this->modulePath, 'user_token=' . $this->session->data['user_token'], 'SSL'),
        );

        $languageVariables = array(
		    // Main
			'heading_title',
			'error_permission',
			'text_success',
			'text_enabled',
			'text_disabled',
			'button_cancel',
			'save_changes',
			'text_default',
			'text_module',
			// Control panel
            'entry_code',
			'entry_code_help',
            'text_content_top',
            'text_content_bottom',
            'text_column_left',
            'text_column_right',
            'entry_layout',
            'entry_position',
            'entry_status',
            'entry_sort_order',
            'entry_layout_options',
            'entry_position_options',
			'entry_action_options',
            'button_add_module',
            'button_remove',
			// Custom CSS
			'custom_css',
            'custom_css_help',
            'custom_css_placeholder',
			// Module depending
			'wrap_widget',
			'wrap_widget_help',
			'text_products',
			'text_products_help',
			'text_image_dimensions',
			'text_image_dimensions_help',
			'text_pixels',
			'text_panel_name',
			'text_panel_name_help',
			'text_products_small',
			'show_add_to_cart',
			'show_add_to_cart_help'
        );

        foreach ($languageVariables as $languageVariable) {
            $data[$languageVariable] = $this->language->get($languageVariable);
        }
        $data['heading_title'] = $data['heading_title'].' '.$this->version;
        $data['stores']					= array_merge(array(0 => array('store_id' => '0', 'name' => $this->config->get('config_name') . ' (' . $data['text_default'].')', 'url' => HTTP_SERVER, 'ssl' => HTTPS_SERVER)), $this->model_setting_store->getStores());
        $data['languages']				= $this->model_localisation_language->getLanguages();
        $data['store']					= $store;
        $data['user_token']                  = $this->session->data['user_token'];
        $data['action']                 = $this->url->link($this->modulePath, 'user_token=' . $this->session->data['user_token'], 'SSL');
        $data['cancel']                 = $this->extensionLink;
        $data['moduleSettings']			= $this->model_setting_setting->getSetting($this->moduleName, $store['store_id']);
        $data['layouts']                = $this->model_design_layout->getLayouts();
        $data['catalog_url']			= $catalogURL;
				$data['user_token']					= $this->session->data['user_token'];
				$data['moduleData'] 			= isset($data['moduleSettings'][$this->moduleName]) ? $data['moduleSettings'][$this->moduleName] : array();
				if (isset($data['moduleData']['Enabled']) && $data['moduleData']['Enabled'] == 'yes'){
					$this->model_setting_setting->editSetting('module_persistentcart', array('module_persistentcart_status' => 1));
				} else{
					$this->model_setting_setting->editSetting('module_persistentcart', array('module_persistentcart_status' => 0));
				}

				$hostname = (!empty($_SERVER['HTTP_HOST'])) ? $_SERVER['HTTP_HOST'] : '' ;
				$data['header']					= $this->load->controller('common/header');
				$data['column_left']			= $this->load->controller('common/column_left');
				$data['footer']					= $this->load->controller('common/footer');
				$data['hostname']= (strstr($hostname,'http://') === false) ? 'http://'.$hostname : $hostname;
				$data['domHostname'] 	= base64_encode($data['hostname']);
				$data['time_now']			= time();
				$data['unlicensedHtml'] = (empty($data['moduleData']['LicensedOn'])) ? base64_decode('ICAgIDxkaXYgY2xhc3M9ImFsZXJ0IGFsZXJ0LWRhbmdlciBmYWRlIGluIj4NCiAgICAgICAgPGJ1dHRvbiB0eXBlPSJidXR0b24iIGNsYXNzPSJjbG9zZSIgZGF0YS1kaXNtaXNzPSJhbGVydCIgYXJpYS1oaWRkZW49InRydWUiPsOXPC9idXR0b24+DQogICAgICAgIDxoND5XYXJuaW5nISBVbmxpY2Vuc2VkIHZlcnNpb24gb2YgdGhlIG1vZHVsZSE8L2g0Pg0KICAgICAgICA8cD5Zb3UgYXJlIHJ1bm5pbmcgYW4gdW5saWNlbnNlZCB2ZXJzaW9uIG9mIHRoaXMgbW9kdWxlISBZb3UgbmVlZCB0byBlbnRlciB5b3VyIGxpY2Vuc2UgY29kZSB0byBlbnN1cmUgcHJvcGVyIGZ1bmN0aW9uaW5nLCBhY2Nlc3MgdG8gc3VwcG9ydCBhbmQgdXBkYXRlcy48L3A+PGRpdiBzdHlsZT0iaGVpZ2h0OjVweDsiPjwvZGl2Pg0KICAgICAgICA8YSBjbGFzcz0iYnRuIGJ0bi1kYW5nZXIiIGhyZWY9ImphdmFzY3JpcHQ6dm9pZCgwKSIgb25jbGljaz0iJCgnYVtocmVmPSNpc2Vuc2Vfc3VwcG9ydF0nKS50cmlnZ2VyKCdjbGljaycpIj5FbnRlciB5b3VyIGxpY2Vuc2UgY29kZTwvYT4NCiAgICA8L2Rpdj4=') : '';
				$data['licenseDataBase64'] =  !empty($this->data['data']['License']) ? base64_encode(json_encode($this->data['data']['License'])) : '';

				$data['licenseExpireDate'] = !empty($this->data['data']['LicensedOn']) ? date("F j, Y", strtotime($this->data['data']['License']['licenseExpireDate'])) : "";
				$data['supportTicketLink'] = 'http://isenselabs.com/tickets/open/' . base64_encode('Support Request').'/'.base64_encode('131').'/'. base64_encode($_SERVER['SERVER_NAME']);
				$data['tab_support_panel'] = $this->load->view($this->modulePath.'/tab_support', $data);
				$data['tab_control_panel'] = $this->load->view($this->modulePath.'/tab_controlpanel', $data);
				$this->response->setOutput($this->load->view($this->modulePath, $data));
    }

	protected function validateForm() {
		if (!$this->user->hasPermission('modify', $this->modulePath)) {
			$this->error['warning'] = $this->language->get('error_permission');
		}
		return !$this->error;
	}

    private function getCatalogURL() {
        if (isset($_SERVER['HTTPS']) && (($_SERVER['HTTPS'] == 'on') || ($_SERVER['HTTPS'] == '1'))) {
            $storeURL = HTTPS_CATALOG;
        } else {
            $storeURL = HTTP_CATALOG;
        }
        return $storeURL;
    }

    private function getServerURL() {
        if (isset($_SERVER['HTTPS']) && (($_SERVER['HTTPS'] == 'on') || ($_SERVER['HTTPS'] == '1'))) {
            $storeURL = HTTPS_SERVER;
        } else {
            $storeURL = HTTP_SERVER;
        }
        return $storeURL;
    }

    private function getCurrentStore($store_id) {
        if($store_id && $store_id != 0) {
            $store = $this->model_setting_store->getStore($store_id);
        } else {
            $store['store_id'] = 0;
            $store['name'] = $this->config->get('config_name');
            $store['url'] = $this->getCatalogURL();
        }
        return $store;
    }

    public function install() {
	    $this->load->model($this->modulePath);
			$this->load->model('setting/setting');
			$this->load->model('setting/event');
	    $this->{$this->moduleModel}->install();
			//$this->model_setting_setting->editSetting('module_persistentcart', array('module_persistentcart_status' => 1));
    }

    public function uninstall() {
    	$this->load->model('setting/setting');
		$this->load->model('setting/store');
		$this->model_setting_setting->deleteSetting($this->moduleData_module,0);
		$stores=$this->model_setting_store->getStores();
		foreach ($stores as $store) {
			$this->model_setting_setting->deleteSetting($this->moduleData_module, $store['store_id']);
		}
		$this->load->model("setting/event");
		$this->model_setting_event->deleteEvent('persistentcart');
        $this->load->model($this->modulePath);
        $this->{$this->moduleModel}->uninstall();
    }
}

?>
