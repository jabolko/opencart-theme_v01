<?php
//==============================================================================
// Payment-Based Fee/Discount v2025-2-19
// 
// Author: Clear Thinking, LLC
// E-mail: johnathan@getclearthinking.com
// Website: http://www.getclearthinking.com
// 
// All code within this file is copyright Clear Thinking, LLC.
// You may not copy or reuse code within this file without written permission.
//==============================================================================

//namespace Opencart\Admin\Controller\Extension\PaymentBasedFee\Total;
//class PaymentBasedFee extends \Opencart\System\Engine\Controller {

class ControllerExtensionTotalPaymentBasedFee extends Controller {
	
	private $type = 'total';
	private $name = 'payment_based_fee';
	
	//==============================================================================
	// index()
	//==============================================================================
	public function index() {
		$data = array(
			'type'			=> $this->type,
			'name'			=> $this->name,
			'autobackup'	=> true,
			'save_type'		=> 'keepediting',
			'permission'	=> $this->hasPermission('modify'),
		);
		
		$this->loadSettings($data);
		
		//------------------------------------------------------------------------------
		// Data Arrays
		//------------------------------------------------------------------------------
		$data['rule_options'] = array(
			'adjustments'				=> array('adjust', 'cumulative', 'max', 'min', 'round', 'tax_class', 'total_value'),
			'cart_criteria'				=> array('length', 'width', 'height', 'lwh', 'price', 'quantity', 'stock', 'total', 'volume', 'weight'),
			'datetime_criteria'			=> array('day', 'date', 'time'),
			'location_criteria'			=> array('city', 'country', 'distance', 'geo_zone', 'location_comparison', 'origin', 'postcode', 'zone'),
			'order_criteria'			=> array('currency', 'customer_group', 'language', 'payment_extension', 'store'),
			'rule_sets'					=> array('rule_set'),
		);
		
		$data['country_array'] = array($this->config->get('config_country_id') => '');
		$this->load->model('localisation/country');
		foreach ($this->model_localisation_country->getCountries() as $country) {
			$data['country_array'][$country['country_id']] = $country['name'];
		}
		
		$data['currency_array'] = array($this->config->get('config_currency') => '');
		$this->load->model('localisation/currency');
		foreach ($this->model_localisation_currency->getCurrencies() as $currency) {
			$data['currency_array'][$currency['code']] = $currency['code'];
		}
		
		$data['customer_group_array'] = array(0 => $data['text_guests']);
		$this->load->model((version_compare(VERSION, '2.1', '<') ? 'sale' : 'customer') . '/customer_group');
		foreach ($this->{'model_' . (version_compare(VERSION, '2.1', '<') ? 'sale' : 'customer') . '_customer_group'}->getCustomerGroups() as $customer_group) {
			$data['customer_group_array'][$customer_group['customer_group_id']] = $customer_group['name'];
		}
		
		$data['geo_zone_array'] = array(0 => $data['text_everywhere_else']);
		$this->load->model('localisation/geo_zone');
		foreach ($this->model_localisation_geo_zone->getGeoZones() as $geo_zone) {
			$data['geo_zone_array'][$geo_zone['geo_zone_id']] = $geo_zone['name'];
		}
		
		$config_language = (!empty($this->config->get('config_language_admin'))) ? $this->config->get('config_language_admin') : $this->config->get('config_language');
		$data['language_array'] = array($config_language => '');
		$data['language_flags'] = array();
		$this->load->model('localisation/language');
		foreach ($this->model_localisation_language->getLanguages() as $language) {
			$data['language_array'][$language['code']] = $language['name'];
			if (version_compare(VERSION, '2.2', '<')) {
				$data['language_flags'][$language['code']] = 'view/image/flags/' . $language['image'];
			} elseif (empty($language['extension'])) {
				$data['language_flags'][$language['code']] = 'language/' . $language['code'] . '/' . $language['code'] . '.png';
			} else {
				$data['language_flags'][$language['code']] = '../extension/' . $language['extension'] . '/admin/language/' . $language['code'] . '/' . $language['code'] . '.png';
			}
		}
		
		$data['store_array'] = array(0 => $this->config->get('config_name'));
		$store_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "store ORDER BY name");
		foreach ($store_query->rows as $store) {
			$data['store_array'][$store['store_id']] = $store['name'];
		}
		
		$data['tax_class_array'] = array(0 => $data['text_none']);
		$this->load->model('localisation/tax_class');
		foreach ($this->model_localisation_tax_class->getTaxClasses() as $tax_class) {
			$data['tax_class_array'][$tax_class['tax_class_id']] = $tax_class['title'];
		}
		
		$data['total_value_array'] = array();
		foreach (array('prediscounted', 'nondiscounted', 'taxed', 'total') as $total_value) {
			$data['total_value_array'][$total_value] = $data['text_' . $total_value . '_subtotal'];
		}
		
		$data['quantity_unit'] = $data['text_items'];
		$data['stock_unit'] = $data['text_items'];
		$left_symbol = $this->currency->getSymbolLeft($this->config->get('config_currency'));
		$data['price_unit'] = ($left_symbol) ? $left_symbol : $this->currency->getSymbolRight($this->config->get('config_currency'));
		$data['total_unit'] = ($left_symbol) ? $left_symbol : $this->currency->getSymbolRight($this->config->get('config_currency'));
		$length = $this->db->query("SELECT * FROM " . DB_PREFIX . "length_class_description WHERE length_class_id = " . (int)$this->config->get('config_length_class_id'));
		$data['length_unit'] = $length->row['unit'];
		$data['width_unit'] = $length->row['unit'];
		$data['height_unit'] = $length->row['unit'];
		$data['lwh_unit'] = $length->row['unit'];
		$data['volume_unit'] = $length->row['unit'] . '&sup3;';
		$weight = $this->db->query("SELECT * FROM " . DB_PREFIX . "weight_class_description WHERE weight_class_id = " . (int)$this->config->get('config_weight_class_id'));
		$data['weight_unit'] = $weight->row['unit'];
		
		
		$data['rule_sets'] = array();
		foreach ($data['saved'] as $key => $setting) {
			if (preg_match('/rule_set_(\d+)_name/', $key)) {
				$data['rule_sets'][str_replace(array('rule_set_', '_name'), '', $key)] = $setting;
			}
		}
		asort($data['rule_sets']);
		
		foreach (array('payment') as $extension_type) {
			$data[$extension_type . '_extension_array'] = array();
			$extension_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "extension WHERE `type` = '" . $this->db->escape($extension_type) . "' ORDER BY `code` ASC");
			foreach ($extension_query->rows as $extension) {
				if ($extension['code'] == $this->name) continue;
				$extension_language = $this->loadLanguage($extension_type . '/' . $extension['code']);
				$data[$extension_type . '_extension_array'][$extension['code']] = (!empty($extension_language['heading_title'])) ? $extension_language['heading_title'] : $extension['code'];
			}
		}
		
		//------------------------------------------------------------------------------
		// Extensions Settings
		//------------------------------------------------------------------------------
		$data['settings'] = array();
		
		$data['settings'][] = array(
			'type'		=> 'tabs',
			'tabs'		=> array('extension_settings', 'charges', 'charge_combinations', 'rule_sets', 'testing_mode'),
		);
		$data['settings'][] = array(
			'key'		=> 'extension_settings',
			'type'		=> 'heading',
			'buttons'	=> 'backup_restore',
		);
		$data['settings'][] = array(
			'key'		=> 'status',
			'type'		=> 'select',
			'options'	=> array(1 => $data['text_enabled'], 0 => $data['text_disabled']),
			'default'	=> 1,
		);
		$data['settings'][] = array(
			'key'		=> 'check_for_updates',
			'type'		=> 'select',
			'options'	=> array(1 => $data['text_yes'], 0 => $data['text_no']),
			'default'	=> 0,
		);
		if ($this->type == 'shipping') {
			$data['settings'][] = array(
				'key'		=> 'heading',
				'type'		=> 'multilingual_text',
				'default'	=> $data['heading_title'],
			);
		}
		if ($this->type != 'module') {
			$data['settings'][] = array(
				'key'		=> 'sort_order',
				'type'		=> 'text',
				'default'	=> ($this->type == 'shipping' ? 1 : 3),
				'class'		=> 'short',
				'attributes'=> array('maxlength' => 2),
			);
			$data['settings'][] = array(
				'key'		=> 'tax_class_id',
				'type'		=> 'select',
				'options'	=> $data['tax_class_array'],
			);
			$data['settings'][] = array(
				'key'		=> 'charge_sorting',
				'type'		=> 'select',
				'options'	=> array('cost' => $data['text_sort_by_cost'], 'group' => $data['text_sort_by_group']),
				'default'	=> 'group',
			);
		}
		
		// Distance Settings
		if ((isset($data['rule_options']['location_criteria']) && in_array('distance', $data['rule_options']['location_criteria'])) || strpos($this->name, 'distance_based') === 0) {
			$data['settings'][] = array(
				'key'		=> 'distance_settings',
				'type'		=> 'heading',
			);
			$data['settings'][] = array(
				'key'		=> 'distance_calculation',
				'type'		=> 'select',
				'options'	=> array('driving' => $data['text_driving_distance'], 'straightline' => $data['text_straightline_distance']),
			);
			$data['settings'][] = array(
				'key'		=> 'distance_units',
				'type'		=> 'select',
				'options'	=> array('mi' => $data['text_miles'], 'km' => $data['text_kilometers']),
			);
			$data['settings'][] = array(
				'key'		=> 'google_apikey',
				'type'		=> 'text',
			);
		}
				
		// Admin Panel Settings
		$data['settings'][] = array(
			'key'		=> 'admin_panel_settings',
			'type'		=> 'heading',
		);
		$data['settings'][] = array(
			'key'		=> 'autosave',
			'type'		=> 'select',
			'options'	=> array(1 => $data['text_enabled'], 0 => $data['text_disabled']),
			'default'	=> 0,
		);
		if (!empty($data['typeaheads'])) {
			$data['settings'][] = array(
				'key'		=> 'autocomplete_preloading',
				'type'		=> 'select',
				'options'	=> array(1 => $data['text_enabled'], 0 => $data['text_disabled']),
			);
		}
		$data['settings'][] = array(
			'key'		=> 'display',
			'type'		=> 'select',
			'options'	=> array('expanded' => $data['text_expanded'], 'collapsed' => $data['text_collapsed']),
		);
		$data['settings'][] = array(
			'key'		=> 'tooltips',
			'type'		=> 'select',
			'options'	=> array(1 => $data['text_enabled'], 0 => $data['text_disabled']),
			'default'	=> 1,
		);
		
		//------------------------------------------------------------------------------
		// Charges
		//------------------------------------------------------------------------------
		$data['settings'][] = array(
			'key'		=> 'charges',
			'type'		=> 'tab',
		);
		$data['settings'][] = array(
			'type'		=> 'html',
			'content'	=> '<div class="text-info text-center pad-bottom">' . $data['help_charges'] . '</div>',
		);
		$data['settings'][] = array(
			'key'		=> 'charges',
			'type'		=> 'heading',
			'buttons'	=> 'expand_collapse',
		);
		
		$table = 'charge';
		$sortby = 'group';
		$data['settings'][] = array(
			'key'		=> $table,
			'type'		=> 'table_start',
			'columns'	=> array('action', 'group', 'title', 'charge', 'rules'),
		);
		foreach ($this->getTableRowNumbers($data, $table, $sortby) as $num => $rules) {
			$prefix = $table . '_' . $num . '_';
			$data['settings'][] = array(
				'type'		=> 'row_start',
			);
			$data['settings'][] = array(
				'key'		=> 'expand_collapse',
				'type'		=> 'button',
			);
			$data['settings'][] = array(
				'key'		=> 'copy',
				'type'		=> 'button',
			);
			$data['settings'][] = array(
				'key'		=> 'delete',
				'type'		=> 'button',
			);
			$data['settings'][] = array(
				'type'		=> 'column',
			);
			$data['settings'][] = array(
				'key'		=> $prefix . 'group',
				'type'		=> 'text',
				'class'		=> 'short',
				'attributes'=> array('maxlength' => 2),
			);
			$data['settings'][] = array(
				'type'		=> 'column',
			);
			$data['settings'][] = array(
				'key'		=> $prefix . 'title',
				'type'		=> 'multilingual_text',
				'admin_ref'	=> true,
			);
			$data['settings'][] = array(
				'type'		=> 'column',
			);
			
			$charge_options = array();
			$charge_options['text_simple_charges']			= '';
			$charge_options['flat'] 						= $data['text_flat_charge'];
			$charge_options['peritem']						= $data['text_per_item_charge'];
			$charge_options['text_bracket_charges']			= '';
			$charge_options['distance']						= $data['text_distance'];
			$charge_options['postcode']						= $data['text_postcode'];
			$charge_options['price']						= $data['text_price'];
			$charge_options['product_count']				= $data['text_product_count'];
			$charge_options['quantity']						= $data['text_quantity'];
			$charge_options['total']						= $data['text_total'];
			$charge_options['volume']						= $data['text_volume'];
			$charge_options['weight']						= $data['text_weight'];
			
			$data['settings'][] = array(
				'key'		=> $prefix . 'type',
				'type'		=> 'select',
				'options'	=> $charge_options,
			);
			$data['settings'][] = array(
				'key'		=> $prefix . 'charges',
				'type'		=> 'textarea',
			);
			$data['settings'][] = array(
				'type'		=> 'column',
			);
			$data['settings'][] = array(
				'key'		=> $prefix . 'rule',
				'type'		=> 'rule',
				'rules'		=> $rules,
			);
			$data['settings'][] = array(
				'type'		=> 'row_end',
			);
		}
		
		$data['settings'][] = array(
			'type'		=> 'table_end',
			'buttons'	=> 'add_row',
			'text'		=> 'button_add_charge',
		);
		
		//------------------------------------------------------------------------------
		// Charge Combinations
		//------------------------------------------------------------------------------
		$data['settings'][] = array(
			'key'		=> 'charge_combinations',
			'type'		=> 'tab',
		);
		$data['settings'][] = array(
			'type'		=> 'html',
			'content'	=> '<div class="text-info text-center padding-bottom">' . $data['help_charge_combinations'] . '</div>',
		);
		$data['settings'][] = array(
			'key'		=> 'charge_combinations',
			'type'		=> 'heading',
		);
		
		$table = 'combination';
		$sortby = 'sort_order';
		$data['settings'][] = array(
			'key'		=> $table,
			'type'		=> 'table_start',
			'columns'	=> array('action', 'sort_order', 'title_combination', 'groups_required', 'combination_formula'),
		);
		foreach ($this->getTableRowNumbers($data, $table, $sortby) as $num => $rules) {
			$prefix = $table . '_' . $num . '_';
			$data['settings'][] = array(
				'type'		=> 'row_start',
			);
			$data['settings'][] = array(
				'key'		=> 'delete',
				'type'		=> 'button',
			);
			$data['settings'][] = array(
				'type'		=> 'column',
			);
			$data['settings'][] = array(
				'key'		=> $prefix . 'sort_order',
				'type'		=> 'text',
				'class'		=> 'short',
				'attributes'=> array('maxlength' => 2),
			);
			$data['settings'][] = array(
				'type'		=> 'column',
			);
			$data['settings'][] = array(
				'key'		=> $prefix . 'title',
				'type'		=> 'select',
				'options'	=> array(
					'single'			=> $data['text_single_title'],
					'combined'			=> $data['text_combined_title_no_prices'],
					'combined_prices'	=> $data['text_combined_title_with_prices']
				),
			);
			$data['settings'][] = array(
				'type'		=> 'column',
			);
			$data['settings'][] = array(
				'key'		=> $prefix . 'groups_required',
				'type'		=> 'text',
			);
			$data['settings'][] = array(
				'type'		=> 'column',
			);
			$data['settings'][] = array(
				'key'		=> $prefix . 'formula',
				'type'		=> 'text',
				'attributes'=> array('style' => 'font-family: monospace; font-size: 13px; width: 300px !important', 'placeholder' => $data['placeholder_formula']),
			);
			$data['settings'][] = array(
				'type'		=> 'row_end',
			);
		}
		
		$data['settings'][] = array(
			'type'		=> 'table_end',
			'buttons'	=> 'add_row',
			'text'		=> 'button_add_combination',
		);
		
		//------------------------------------------------------------------------------
		// Rule Sets
		//------------------------------------------------------------------------------
		$data['settings'][] = array(
			'key'		=> 'rule_sets',
			'type'		=> 'tab',
		);
		$data['settings'][] = array(
			'type'		=> 'html',
			'content'	=> '<div class="text-info text-center pad-bottom">' . $data['help_rule_sets'] . '</div>',
		);
		$data['settings'][] = array(
			'key'		=> 'rule_sets',
			'type'		=> 'heading',
			'buttons'	=> 'expand_collapse',
		);
		
		$table = 'rule_set';
		$sortby = 'name';
		$data['settings'][] = array(
			'key'		=> $table,
			'type'		=> 'table_start',
			'columns'	=> array('action', 'name', 'rules'),
		);
		foreach ($this->getTableRowNumbers($data, $table, $sortby) as $num => $rules) {
			$prefix = $table . '_' . $num . '_';
			$data['settings'][] = array(
				'type'		=> 'row_start',
			);
			$data['settings'][] = array(
				'key'		=> 'expand_collapse',
				'type'		=> 'button',
			);
			$data['settings'][] = array(
				'key'		=> 'copy',
				'type'		=> 'button',
			);
			$data['settings'][] = array(
				'key'		=> 'delete',
				'type'		=> 'button',
			);
			$data['settings'][] = array(
				'type'		=> 'column',
			);
			$data['settings'][] = array(
				'key'		=> $prefix . 'name',
				'type'		=> 'text',
			);
			$data['settings'][] = array(
				'type'		=> 'column',
			);
			$data['settings'][] = array(
				'key'		=> $prefix . 'rule',
				'type'		=> 'rule',
				'rules'		=> $rules,
			);
			$data['settings'][] = array(
				'type'		=> 'row_end',
			);
		}
		
		$data['settings'][] = array(
			'type'		=> 'table_end',
			'buttons'	=> 'add_row',
			'text'		=> 'button_add_rule_set',
		);
		
		//------------------------------------------------------------------------------
		// Testing Mode
		//------------------------------------------------------------------------------
		$data['settings'][] = array(
			'key'		=> 'testing_mode',
			'type'		=> 'tab',
		);
		$data['settings'][] = array(
			'type'		=> 'html',
			'content'	=> '<div class="text-info text-center pad-bottom">' . $data['testing_mode_help'] . '</div>',
		);
		
		$filepath = DIR_LOGS . $this->name . '.messages';
		$testing_mode_log = '';
		$refresh_or_download_button = '<a class="btn btn-info" onclick="refreshLog()"><i class="fa fa-refresh fa-sync-alt pad-right-sm"></i> ' . $data['button_refresh_log'] . '</a>';
		
		if (file_exists($filepath)) {
			$filesize = filesize($filepath);
			if ($filesize > 999999) {
				$testing_mode_log = $data['standard_testing_mode'];
				$refresh_or_download_button = '<a class="btn btn-info" href="index.php?route=' . $data['extension_route'] . '/downloadLog&token=' . $data['token'] . '"><i class="fa fa-download pad-right-sm"></i> ' . $data['button_download_log'] . ' (' . round($filesize / 1000000, 1) . ' MB)</a>';
			} else {
				$testing_mode_log = html_entity_decode(file_get_contents($filepath), ENT_QUOTES, 'UTF-8');
			}
		}
		
		$data['settings'][] = array(
			'key'		=> 'testing_mode',
			'type'		=> 'heading',
			'buttons'	=> $refresh_or_download_button . ' <a class="btn btn-danger" onclick="clearLog()"><i class="fa fa-trash-o fa-trash-alt pad-right-sm"></i> ' . $data['button_clear_log'] . '</a>',
		);
		$data['settings'][] = array(
			'key'		=> 'testing_mode',
			'type'		=> 'select',
			'options'	=> array(1 => $data['text_enabled'], 0 => $data['text_disabled']),
			'default'	=> 0,
		);
		$data['settings'][] = array(
			'key'		=> 'testing_messages',
			'type'		=> 'textarea',
			'class'		=> 'nosave',
			'attributes'=> array('style' => 'width: 100% !important; height: 400px; font-size: 12px !important'),
			'default'	=> htmlentities($testing_mode_log),
		);
		
		//------------------------------------------------------------------------------
		// end settings
		//------------------------------------------------------------------------------
		
		$this->document->setTitle($data['heading_title']);
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		
		if (version_compare(VERSION, '4.0', '<')) {
			$template_file = DIR_TEMPLATE . 'extension/' . $this->type . '/' . $this->name . '.twig';
		} elseif (defined('DIR_EXTENSION')) {
			$template_file = DIR_EXTENSION . $this->name . '/admin/view/template/' . $this->type . '/' . $this->name . '.twig';
		}
		
		if (is_file($template_file)) {
			extract($data);
			
			ob_start();
			if (version_compare(VERSION, '4.0', '<')) {
				require(class_exists('VQMod') ? \VQMod::modCheck(modification($template_file)) : modification($template_file));
			} else {
				require(class_exists('VQMod') ? \VQMod::modCheck($template_file) : $template_file);
			}
			$output = ob_get_clean();
			
			if (version_compare(VERSION, '3.0', '>=')) {
				$output = str_replace(array('&token=', '&amp;token='), '&user_token=', $output);
			}
			
			if (version_compare(VERSION, '4.0', '>=')) {
				$separator = (version_compare(VERSION, '4.0.2.0', '<')) ? '|' : '.';
				$output = str_replace($data['extension_route'] . '/', $data['extension_route'] . $separator, $output);
			}
			
			echo $output;
		} else {
			echo 'Error loading template file: ' . $template_file;
		}
	}
	
	//==============================================================================
	// Helper functions
	//==============================================================================
	private function hasPermission($permission) {
		if (version_compare(VERSION, '2.3', '<')) {
			return $this->user->hasPermission($permission, $this->type . '/' . $this->name);
		} elseif (version_compare(VERSION, '4.0', '<')) {
			return $this->user->hasPermission($permission, 'extension/' . $this->type . '/' . $this->name);
		} else {
			return $this->user->hasPermission($permission, 'extension/' . $this->name . '/' . $this->type . '/' . $this->name);
		}
	}
	
	private function loadLanguage($path) {
		$_ = array();
		$language = array();
		if (version_compare(VERSION, '2.2', '<')) {
			$admin_language = $this->db->query("SELECT * FROM " . DB_PREFIX . "language WHERE `code` = '" . $this->db->escape($this->config->get('config_admin_language')) . "'")->row['directory'];
		} elseif (version_compare(VERSION, '4.0', '<')) {
			$admin_language = $this->config->get('config_admin_language');
		} else {
			$admin_language = $this->config->get('config_language_admin');
		}
		foreach (array('english', 'en-gb', $admin_language) as $directory) {
			$file = DIR_LANGUAGE . $directory . '/' . $directory . '.php';
			if (file_exists($file)) require(class_exists('VQMod') ? \VQMod::modCheck($file) : $file);
			$file = DIR_LANGUAGE . $directory . '/default.php';
			if (file_exists($file)) require(class_exists('VQMod') ? \VQMod::modCheck($file) : $file);
			$file = DIR_LANGUAGE . $directory . '/' . $path . '.php';
			if (file_exists($file)) require(class_exists('VQMod') ? \VQMod::modCheck($file) : $file);
			$file = DIR_LANGUAGE . $directory . '/extension/' . $path . '.php';
			if (file_exists($file)) require(class_exists('VQMod') ? \VQMod::modCheck($file) : $file);
			if (defined('DIR_EXTENSION')) {
				$file = DIR_EXTENSION . 'opencart/admin/language/' . $directory . '/' . $path . '.php';
				if (file_exists($file)) require(class_exists('VQMod') ? \VQMod::modCheck($file) : $file);
				$explode = explode('/', $path);
				$file = DIR_EXTENSION . $explode[1] . '/admin/language/' . $directory . '/' . $path . '.php';
				if (file_exists($file)) require(class_exists('VQMod') ? \VQMod::modCheck($file) : $file);
				$file = DIR_EXTENSION . $this->name . '/admin/language/' . $directory . '/' . $path . '.php';
				if (file_exists($file)) require(class_exists('VQMod') ? \VQMod::modCheck($file) : $file);
			}
			$language = array_merge($language, $_);
		}
		return $language;
	}
	
	private function getTableRowNumbers(&$data, $table, $sorting) {
		$groups = array();
		$rules = array();
		
		foreach ($data['saved'] as $key => $setting) {
			if (preg_match('/' . $table . '_(\d+)_' . $sorting . '/', $key, $matches)) {
				$groups[$setting][] = $matches[1];
			}
			if (preg_match('/' . $table . '_(\d+)_rule_(\d+)_type/', $key, $matches)) {
				$rules[$matches[1]][$setting . $matches[2]] = $matches[2];
			}
		}
		
		if (empty($groups)) $groups = array('' => array('1'));
		ksort($groups, defined('SORT_NATURAL') ? SORT_NATURAL : SORT_REGULAR);
		
		foreach ($rules as $key => $rule) {
			ksort($rules[$key], defined('SORT_NATURAL') ? SORT_NATURAL : SORT_REGULAR);
		}
		
		$data['used_rows'][$table] = array();
		$rows = array();
		foreach ($groups as $group) {
			foreach ($group as $num) {
				$data['used_rows'][preg_replace('/module_(\d+)_/', '', $table)][] = $num;
				$rows[$num] = (empty($rules[$num])) ? array() : $rules[$num];
			}
		}
		sort($data['used_rows'][$table]);
		
		return $rows;
	}
	
	//==============================================================================
	// loadSettings()
	//==============================================================================
	private $encryption_key = '';
	
	public function loadSettings(&$data) {
		$backup_type = (empty($data)) ? 'manual' : 'auto';
		if ($backup_type == 'manual' && !$this->hasPermission('modify')) {
			return;
		}
		
		$this->cache->delete($this->name);
		unset($this->session->data[$this->name]);
		$code = (version_compare(VERSION, '3.0', '<') ? '' : $this->type . '_') . $this->name;
		
		// Set URL data
		$data['token'] = $this->session->data[version_compare(VERSION, '3.0', '<') ? 'token' : 'user_token'];
		$data['exit'] = $this->url->link((version_compare(VERSION, '3.0', '<') ? 'extension' : 'marketplace') . '/' . (version_compare(VERSION, '2.3', '<') ? '' : 'extension&type=') . $this->type . '&token=' . $data['token'], '', 'SSL');
		$data['extension_route'] = 'extension/' . (version_compare(VERSION, '4.0', '<') ? '' : $this->name . '/') . $this->type . '/' . $this->name;
		
		// Load saved settings
		$data['saved'] = array();
		$settings_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "setting WHERE `code` = '" . $this->db->escape($code) . "' ORDER BY `key` ASC");
		
		foreach ($settings_query->rows as $setting) {
			$key = str_replace($code . '_', '', $setting['key']);
			$value = $setting['value'];
			if ($setting['serialized']) {
				$value = (version_compare(VERSION, '2.1', '<')) ? unserialize($setting['value']) : json_decode($setting['value'], true);
			}
			
			$data['saved'][$key] = $value;
			
			if (is_array($value)) {
				foreach ($value as $num => $value_array) {
					foreach ($value_array as $k => $v) {
						$data['saved'][$key . '_' . $num . '_' . $k] = $v;
					}
				}
			}
		}
		
		// Load language and run standard checks
		$data = array_merge($data, $this->loadLanguage($this->type . '/' . $this->name));
		
		if (ini_get('max_input_vars') && ((ini_get('max_input_vars') - count($data['saved'])) < 50)) {
			$data['warning'] = $data['standard_max_input_vars'];
		}
		
		// Modify files according to OpenCart version
		if ($this->type == 'total') {
			if (version_compare(VERSION, '2.2', '<')) {
				$filepath = DIR_CATALOG . 'model/' . $this->type . '/' . $this->name . '.php';
				file_put_contents($filepath, str_replace('public function getTotal($total) {', 'public function getTotal(&$total_data, &$order_total, &$taxes) {' . "\n\t\t" . '$total = array("totals" => &$total_data, "total" => &$order_total, "taxes" => &$taxes);', file_get_contents($filepath)));
			} elseif (defined('DIR_EXTENSION')) {
				$filepath = DIR_EXTENSION . $this->name . '/catalog/model/' . $this->type . '/' . $this->name . '.php';
				file_put_contents($filepath, str_replace('public function getTotal($total_input) {', 'public function getTotal(&$total_data, &$taxes, &$order_total) {', file_get_contents($filepath)));
			}
		}
		
		if (version_compare(VERSION, '2.3', '>=')) {
			$filepaths = array(
				DIR_APPLICATION . 'controller/' . $this->type . '/' . $this->name . '.php',
				DIR_CATALOG . 'controller/' . $this->type . '/' . $this->name . '.php',
				DIR_CATALOG . 'model/' . $this->type . '/' . $this->name . '.php',
			);
			foreach ($filepaths as $filepath) {
				if (file_exists($filepath)) {
					rename($filepath, str_replace('.php', '.php-OLD', $filepath));
				}
			}
		}
		
		if (version_compare(VERSION, '4.0', '>=')) {
			$extension_install_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "extension_install WHERE `code` = '" . $this->db->escape($this->name) . "'");
			if ($extension_install_query->row['version'] == 'unlicensed') {
				$this->db->query("UPDATE " . DB_PREFIX . "extension_install SET version = '" . $this->db->escape($data['version']) . "' WHERE `code` = '" . $this->db->escape($this->name) . "'");
			}
		}
		
		// Set save type and skip auto-backup if not needed
		if (!empty($data['saved']['autosave'])) {
			$data['save_type'] = 'auto';
		}
		
		if ($backup_type == 'auto' && empty($data['autobackup'])) {
			return;
		}
		
		// Create settings auto-backup file
		$manual_filepath = DIR_LOGS . $this->name . $this->encryption_key . '.backup';
		$auto_filepath = DIR_LOGS . $this->name . $this->encryption_key . '.autobackup';
		$filepath = ($backup_type == 'auto') ? $auto_filepath : $manual_filepath;
		if (file_exists($filepath)) unlink($filepath);
		
		file_put_contents($filepath, 'SETTING	NUMBER	SUB-SETTING	SUB-NUMBER	SUB-SUB-SETTING	VALUE' . "\n", FILE_APPEND|LOCK_EX);
		
		foreach ($data['saved'] as $key => $value) {
			if (is_array($value)) continue;
			
			$parts = explode('|', preg_replace(array('/_(\d+)_/', '/_(\d+)/'), array('|$1|', '|$1'), $key));
			
			$line = '';
			for ($i = 0; $i < 5; $i++) {
				$line .= (isset($parts[$i]) ? $parts[$i] : '') . "\t";
			}
			$line .= str_replace(array("\t", "\n"), array('    ', '\n'), $value) . "\n";
			
			file_put_contents($filepath, $line, FILE_APPEND|LOCK_EX);
		}
		
		$data['autobackup_time'] = date('Y-M-d @ g:i a');
		$data['backup_time'] = (file_exists($manual_filepath)) ? date('Y-M-d @ g:i a', filemtime($manual_filepath)) : '';
		
		if ($backup_type == 'manual') {
			echo $data['autobackup_time'];
		}
	}
	
	//==============================================================================
	// saveSettings()
	//==============================================================================
	public function saveSettings() {
		if (!$this->hasPermission('modify')) {
			echo 'PermissionError';
			return;
		}
		
		$this->cache->delete($this->name);
		unset($this->session->data[$this->name]);
		$code = (version_compare(VERSION, '3.0', '<') ? '' : $this->type . '_') . $this->name;
		
		if ($this->request->get['saving'] == 'manual') {
			$this->db->query("DELETE FROM " . DB_PREFIX . "setting WHERE `code` = '" . $this->db->escape($code) . "' AND `key` != '" . $this->db->escape($this->name . '_module') . "'");
		}
		
		$module_id = 0;
		$modules = array();
		$module_instance = false;
		
		foreach ($this->request->post as $key => $value) {
			if (strpos($key, 'module_') === 0) {
				$parts = explode('_', $key, 3);
				$module_id = $parts[1];
				$modules[$parts[1]][$parts[2]] = $value;
				if ($parts[2] == 'module_id') $module_instance = true;
			} else {
				$key = (version_compare(VERSION, '3.0', '<') ? '' : $this->type . '_') . $this->name . '_' . $key;
				
				if ($this->request->get['saving'] == 'auto') {
					$this->db->query("DELETE FROM " . DB_PREFIX . "setting WHERE `code` = '" . $this->db->escape($code) . "' AND `key` = '" . $this->db->escape($key) . "'");
				}
				
				$this->db->query("
					INSERT INTO " . DB_PREFIX . "setting SET
					`store_id` = 0,
					`code` = '" . $this->db->escape($code) . "',
					`key` = '" . $this->db->escape($key) . "',
					`value` = '" . $this->db->escape(stripslashes(is_array($value) ? implode(';', $value) : $value)) . "',
					`serialized` = 0
				");
			}
		}
		
		foreach ($modules as $module_id => $module) {
			$module_code = (version_compare(VERSION, '4.0', '<')) ? $this->name : $this->name . '.' . $this->name;
			if (!$module_id) {
				$this->db->query("
					INSERT INTO " . DB_PREFIX . "module SET
					`name` = '" . $this->db->escape($module['name']) . "',
					`code` = '" . $this->db->escape($module_code) . "',
					`setting` = ''
				");
				$module_id = $this->db->getLastId();
				$module['module_id'] = $module_id;
			}
			$module_settings = (version_compare(VERSION, '2.1', '<')) ? serialize($module) : json_encode($module);
			$this->db->query("
				UPDATE " . DB_PREFIX . "module SET
				`name` = '" . $this->db->escape($module['name']) . "',
				`code` = '" . $this->db->escape($module_code) . "',
				`setting` = '" . $this->db->escape($module_settings) . "'
				WHERE module_id = " . (int)$module_id . "
			");
		}
	}
	
	//==============================================================================
	// deleteSetting()
	//==============================================================================
	public function deleteSetting() {
		if (!$this->hasPermission('modify')) {
			echo 'PermissionError';
			return;
		}
		$prefix = (version_compare(VERSION, '3.0', '<')) ? '' : $this->type . '_';
		$this->db->query("DELETE FROM " . DB_PREFIX . "setting WHERE `code` = '" . $this->db->escape($prefix . $this->name) . "' AND `key` = '" . $this->db->escape($prefix . $this->name . '_' . str_replace('[]', '', $this->request->get['setting'])) . "'");
	}
	
	//==============================================================================
	// checkVersion()
	//==============================================================================
	public function checkVersion() {
		$data = $this->loadLanguage($this->type . '/' . $this->name);
		
		$curl = curl_init('https://www.getclearthinking.com/downloads/checkVersion?extension=' . urlencode($data['heading_title']));
		curl_setopt_array($curl, array(
			CURLOPT_CONNECTTIMEOUT	=> 10,
			CURLOPT_RETURNTRANSFER	=> true,
			CURLOPT_TIMEOUT			=> 10,
		));
		$response = curl_exec($curl);
		curl_close($curl);
		
		echo $response;
	}
	
	//==============================================================================
	// update()
	//==============================================================================
	public function update() {
		$data = $this->loadLanguage($this->type . '/' . $this->name);
		
		$curl = curl_init('https://www.getclearthinking.com/downloads/update?extension=' . urlencode($data['heading_title']) . '&domain=' . $this->request->server['HTTP_HOST'] . '&key=' . $this->request->post['license_key']);
		curl_setopt_array($curl, array(
			CURLOPT_CONNECTTIMEOUT	=> 10,
			CURLOPT_RETURNTRANSFER	=> true,
			CURLOPT_TIMEOUT			=> 10,
		));
		$response = curl_exec($curl);
		curl_close($curl);
		
		if (strpos($response, '<i') === 0) {
			echo $response;
			return;
		}
		
		$first_zip = DIR_DOWNLOAD . 'clearthinking.zip';
		$file = fopen($first_zip, 'w+');
		fwrite($file, $response);
		fclose($file);
		
		$temp_directory = DIR_DOWNLOAD . 'clearthinking/';
		$zip = new \ZipArchive();

		if ($zip->open($first_zip) === true) {
			$zip->extractTo($temp_directory);
			$zip->close();
		} else {
			echo 'Invalid zip archive';
			return;
		}
		
		@unlink($first_zip);
		
		if (version_compare(VERSION, '2.0', '<')) {
			$second_zip = $temp_directory . 'OpenCart 1.5 Versions.zip';
		} elseif (version_compare(VERSION, '2.3', '<')) {
			$second_zip = $temp_directory . 'OpenCart 2.0-2.2 Versions.ocmod.zip';
		} elseif (version_compare(VERSION, '4.0', '<')) {
			$second_zip = $temp_directory . 'OpenCart 2.3-3.0 Versions.ocmod.zip';
		} else {
			$second_zip = $temp_directory . 'OpenCart 4 Versions.zip';
		}
		
		$zip = new \ZipArchive();
		
		if (version_compare(VERSION, '4.0', '<')) {
			if ($zip->open($second_zip) === true) {
				$admin_directory = basename(DIR_APPLICATION);
				
				for ($i = 0; $i < $zip->numFiles; $i++) {
					$filepath = str_replace(array('upload/', 'admin/'), array('', $admin_directory . '/'), $zip->getNameIndex($i));
					
					if (strpos($filepath, '.txt')) {
						continue;
					}
					
					if ($filepath === 'install.xml') {
						$xml = $zip->getFromIndex($i);
						
						foreach (array('name', 'code', 'version', 'author', 'link') as $tag) {
							$first_explosion = explode('<' . $tag . '>', $xml);
							$second_explosion = explode('</' . $tag . '>', $first_explosion[1]);
							${'xml_'.$tag} = $second_explosion[0];
						}
						
						$this->db->query("DELETE FROM " . DB_PREFIX . "modification WHERE code = '" . $this->db->escape($xml_code) . "'");
						
						$this->db->query("INSERT INTO " . DB_PREFIX . "modification SET code = '" . $this->db->escape($xml_code) . "', name = '" . $this->db->escape($xml_name) . "', author = '" . $this->db->escape($xml_author) . "', version = '" . $this->db->escape($xml_version) . "', link = '" . $this->db->escape($xml_link) . "', xml = '" . $this->db->escape($xml) . "', status = 1, date_added = NOW()");
						
						continue;
					}
					
					$full_filepath = DIR_APPLICATION . '../' . $filepath;
					
					if (!strpos($filepath, '.')) {
						if (!is_dir($full_filepath)) {
							mkdir($full_filepath, 0777);
						}
						continue;
					}
					
					file_put_contents($full_filepath, $zip->getFromIndex($i));
				}
				
				$zip->close();
			} else {
				echo 'Invalid zip archive';
				return;
			}
		} else {
			if ($zip->open($second_zip) === true) {
				$zip->extractTo($temp_directory);
				$zip->close();
			} else {
				echo 'Invalid zip archive';
				return;
			}
			
			$third_zip = $temp_directory . $this->name . '.ocmod.zip';
			$zip = new \ZipArchive();
			
			if ($zip->open($third_zip) === true) {
				$zip->extractTo(DIR_EXTENSION . $this->name . '/');
				$zip->close();
			} else {
				echo 'Invalid zip archive';
				return;
			}
			
			@unlink($third_zip);
		}
		
		@array_map('unlink', array_filter((array)glob($temp_directory . '*')));
		@rmdir($temp_directory);
		
		echo 'success';
	}
	
	//==============================================================================
	// backupSettings()
	//==============================================================================
	public function backupSettings() {
		$data = array();
		$this->loadSettings($data);
	}
	
	//==============================================================================
	// viewBackup()
	//==============================================================================
	public function viewBackup() {
		if (!$this->hasPermission('access')) {
			echo 'You do not have permission to view this file.';
			return;
		}
		if (!file_exists(DIR_LOGS . $this->name . $this->encryption_key . '.backup')) {
			echo 'Backup file does not exist';
			return;
		}
		
		$contents = trim(file_get_contents(DIR_LOGS . $this->name . $this->encryption_key . '.backup'));
		$lines = explode("\n", $contents);
		
		$html = '<table border="1" style="font-family: monospace" cellspacing="0" cellpadding="5">';
		foreach ($lines as $line) {
			$html .= '<tr><td>' . implode('</td><td>', explode("\t", $line)) . '</td></tr>';
		}
		echo str_replace('<td></td>', '<td style="background: #DDD"></td>', $html) . '</table>';
	}
	
	//==============================================================================
	// downloadBackup()
	//==============================================================================
	public function downloadBackup() {
		$file = DIR_LOGS . $this->name . $this->encryption_key . '.backup';
		if (!file_exists($file) || !$this->hasPermission('access')) {
			return;
		}
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Content-Description: File Transfer');
		header('Content-Disposition: attachment; filename=' . $this->name . '.' . date('Y-n-d') . '.txt');
		header('Content-Length: ' . filesize($file));
		header('Content-Transfer-Encoding: binary');
		header('Content-Type: text/plain');
		header('Expires: 0');
		header('Pragma: public');
		readfile($file);
	}
	
	//==============================================================================
	// restoreSettings()
	//==============================================================================
	public function restoreSettings() {
		$data = $this->loadLanguage($this->type . '/' . $this->name);
		$token = (version_compare(VERSION, '3.0', '<')) ? 'token' : 'user_token';
		
		$extension_route = 'extension/' . (version_compare(VERSION, '4.0', '<') ? '' : $this->name . '/') . $this->type . '/' . $this->name;
		$redirect_url = str_replace(array('&amp;', "\n", "\r"), array('&', '', ''), $this->url->link($extension_route, $token . '=' . $this->session->data[$token], 'SSL'));
		
		if (!$this->hasPermission('modify')) {
			$this->session->data['error'] = $data['standard_error'];
			$this->response->redirect($redirect_url);
		}
		
		if ($this->request->post['from'] == 'auto') {
			$filepath = DIR_LOGS . $this->name . $this->encryption_key . '.autobackup';
		} elseif ($this->request->post['from'] == 'manual') {
			$filepath = DIR_LOGS . $this->name . $this->encryption_key . '.backup';
		} elseif ($this->request->post['from'] == 'file') {
			$filepath = $this->request->files['backup_file']['tmp_name'];
			if (empty($filepath)) {
				$this->response->redirect($redirect_url);
			}
		}
		
		$contents = str_replace("\r\n", "\n", trim(file_get_contents($filepath)));
		
		if (strpos($contents, 'SETTING') !== 0) {
			$this->session->data['error'] = $data['error_invalid_file_data'];
			$this->response->redirect($redirect_url);
		}
		
		$code = (version_compare(VERSION, '3.0', '<') ? '' : $this->type . '_') . $this->name;
		$this->db->query("DELETE FROM " . DB_PREFIX . "setting WHERE `code` = '" . $this->db->escape($code) . "'");
		
		foreach (explode("\n", $contents) as $row) {
			if (empty($row) || strpos($row, 'SETTING') === 0) continue;
			
			$col = explode("\t", $row);
			$value = str_replace('\n', "\n", array_pop($col));
			$key = implode('_', array_diff($col, array('')));
			
			$this->db->query("INSERT INTO " . DB_PREFIX . "setting SET `store_id` = 0, `code` = '" . $this->db->escape($code) . "', `key` = '" . $this->db->escape($code . '_' . $key) . "', `value` = '" . $this->db->escape($value) . "', `serialized` = 0");
		}
		
		$this->session->data['success'] = $data['text_settings_restored'];
		$this->response->redirect($redirect_url);
	}
	
	//==============================================================================
	// refreshLog()
	//==============================================================================
	public function refreshLog() {
		$data = $this->loadLanguage($this->type . '/' . $this->name);
		
		if (!$this->hasPermission('modify')) {
			echo $data['standard_error'];
			return;
		}
		
		$filepath = DIR_LOGS . $this->name . '.messages';
		
		if (file_exists($filepath)) {
			if (filesize($filepath) > 999999) {
				echo $data['standard_testing_mode'];
			} else {
				echo html_entity_decode(file_get_contents($filepath), ENT_QUOTES, 'UTF-8');
			}
		}
	}
	
	//==============================================================================
	// downloadLog()
	//==============================================================================
	public function downloadLog() {
		$file = DIR_LOGS . $this->name . '.messages';
		if (!file_exists($file) || !$this->hasPermission('access')) {
			return;
		}
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Content-Description: File Transfer');
		header('Content-Disposition: attachment; filename=' . $this->name . '.' . date('Y-n-d') . '.log');
		header('Content-Length: ' . filesize($file));
		header('Content-Transfer-Encoding: binary');
		header('Content-Type: text/plain');
		header('Expires: 0');
		header('Pragma: public');
		readfile($file);
	}
	
	//==============================================================================
	// clearLog()
	//==============================================================================
	public function clearLog() {
		$data = $this->loadLanguage($this->type . '/' . $this->name);
		
		if (!$this->hasPermission('modify')) {
			echo $data['standard_error'];
			return;
		}
		
		file_put_contents(DIR_LOGS . $this->name . '.messages', '');
	}
	
	//==============================================================================
	// loadDropdown()
	//==============================================================================
	public function loadDropdown() {
		$data = $this->loadLanguage($this->type . '/' . $this->name);
		$dropdown_type = ($this->request->get['type'] == 'quantity_of_group') ? 'product_group' : $this->request->get['type'];
		
		echo '<option value="">' . $data['standard_select'] . '</option>';
		
		$options = array();
		$code = (version_compare(VERSION, '3.0', '<') ? '' : $this->type . '_') . $this->name;
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "setting WHERE `code` = '" . $this->db->escape($code) . "' AND `key` LIKE '" . $this->db->escape($code . "_" . $dropdown_type) . "%'");
		
		foreach ($query->rows as $row) {
			if (strpos($row['key'], '_name')) {
				$num = str_replace(array($code . '_' . $dropdown_type . '_', '_name'), '', $row['key']);
				foreach ($query->rows as $subrow) {
					if (strpos($subrow['key'], '_' . $num . '_name') && $row['value']) {
						$options['<option value="' . $num . '">' . $row['value'] . '</option>'] = $subrow['value'];
						break;
					}
				}
			}
		}
		
		natcasesort($options);
		foreach ($options as $option => $sort_order) {
			echo $option;
		}
	}
	
	//==============================================================================
	// typeahead()
	//==============================================================================
	public function typeahead() {
		$search = (strpos($this->request->get['q'], '[')) ? substr($this->request->get['q'], 0, strpos($this->request->get['q'], ' [')) : $this->request->get['q'];
		
		if ($this->request->get['type'] == 'all') {
			if (strpos($this->name, 'ultimate') === 0) {
				$tables = array('attribute_group_description', 'attribute_description', 'category_description', 'filter_description', 'manufacturer', 'option_description', 'option_value_description', 'product_description');
			} else {
				$tables = array('category_description', 'manufacturer', 'product_description');
			}
		} elseif (in_array($this->request->get['type'], array('manufacturer', 'zone'))) {
			$tables = array($this->request->get['type']);
		} else {
			$tables = array($this->request->get['type'] . '_description');
		}
		
		$results = array();
		foreach ($tables as $table) {
			if ($table == 'product_description') {
				$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_description pd LEFT JOIN " . DB_PREFIX . "product p ON (pd.product_id = p.product_id) WHERE pd.name LIKE '%" . $this->db->escape($search) . "%' OR p.model LIKE '%" . $this->db->escape($search) . "%' ORDER BY pd.name ASC LIMIT 0,100");
			} else {
				$query = $this->db->query("SELECT * FROM " . DB_PREFIX . $table . " WHERE name LIKE '%" . $this->db->escape($search) . "%' ORDER BY name ASC LIMIT 0,100");
			}
			$results = array_merge($results, $query->rows);
		}
		
		if (empty($results)) {
			$variations = array();
			for ($i = 0; $i < strlen($search); $i++) {
				$variations[] = $this->db->escape(substr_replace($search, '_', $i, 1));
				$variations[] = $this->db->escape(substr_replace($search, '', $i, 1));
				if ($i != strlen($search)-1) {
					$transpose = $search;
					$transpose[$i] = $search[$i+1];
					$transpose[$i+1] = $search[$i];
					$variations[] = $this->db->escape($transpose);
				}
			}
			foreach ($tables as $table) {
				if ($table == 'product_description') {
					$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_description pd LEFT JOIN " . DB_PREFIX . "product p ON (pd.product_id = p.product_id) WHERE pd.name LIKE '%" . implode("%' OR pd.name LIKE '%", $variations) . "%' OR p.model LIKE '%" . implode("%' OR p.model LIKE '%", $variations) . "%' ORDER BY pd.name ASC LIMIT 0,100");
				} else {
					$query = $this->db->query("SELECT * FROM " . DB_PREFIX . $table . " WHERE name LIKE '%" . implode("%' OR name LIKE '%", $variations) . "%' ORDER BY name ASC LIMIT 0,100");
				}
				$results = array_merge($results, $query->rows);
			}
		}
		
		$items = array();
		foreach ($results as $result) {
			if (key($result) == 'category_id') {
				$category_id = reset($result);
				$parent_exists = true;
				while ($parent_exists) {
					$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "category_description WHERE category_id = (SELECT parent_id FROM " . DB_PREFIX . "category WHERE category_id = " . (int)$category_id . " AND parent_id != " . (int)$category_id . ")");
					if (!empty($query->row['name'])) {
						$category_id = $query->row['category_id'];
						$result['name'] = $query->row['name'] . ' > ' . $result['name'];
					} else {
						$parent_exists = false;
					}
				}
			}
			$items[] = html_entity_decode($result['name'], ENT_NOQUOTES, 'UTF-8') . ' [' . key($result) . ':' . reset($result) . ']';
		}
		
		natcasesort($items);
		echo '["' . implode('","', str_replace(array('"', '_id', "\t"), array('&quot;', '', ''), $items)) . '"]';
	}
}
?>