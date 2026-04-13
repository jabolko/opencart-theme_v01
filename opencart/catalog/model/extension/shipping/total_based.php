<?php
//==============================================================================
// Total-Based Shipping v2025-2-17
// 
// Author: Clear Thinking, LLC
// E-mail: johnathan@getclearthinking.com
// Website: http://www.getclearthinking.com
// 
// All code within this file is copyright Clear Thinking, LLC.
// You may not copy or reuse code within this file without written permission.
//==============================================================================

//namespace Opencart\Catalog\Model\Extension\TotalBased\Shipping;
//class TotalBased extends \Opencart\System\Engine\Model {

class ModelExtensionShippingTotalBased extends Model {
	
	private $type = 'shipping';
	private $name = 'total_based';
	private $testing_mode;
	private $charge;
	
	//==============================================================================
	// getQuote()
	//==============================================================================
	public function getQuote($address) {
		$settings = $this->getSettings();
		$this->testing_mode = $settings['testing_mode'];
		
		$this->logMessage("\n" . '------------------------------ Starting Test ' . date('Y-m-d G:i:s') . ' ------------------------------');
		
		if (empty($settings['status'])) {
			$this->logMessage('Extension is disabled');
			return;
		}
		
		// Set address info
		$addresses = array();
		$this->load->model('account/address');
		foreach (array('shipping', 'payment', 'geoiptools') as $address_type) {
			if ($address_type == 'geoiptools' && !empty($this->session->data['geoip_data']['location'])) {
				$address = $this->session->data['geoip_data']['location'];
			} elseif (($address_type == 'shipping' && empty($address)) || $address_type == 'payment') {
				if ($this->customer->isLogged()) {
					if (version_compare(VERSION, '4.0.2.0', '<')) {
						$address = $this->model_account_address->getAddress($this->customer->getAddressId());
					} else {
						$address = $this->model_account_address->getAddress($this->customer->getId(), $this->customer->getAddressId());
					}
				}
				
				if (empty($address)) {
					$address = array();
				}
				
				if (!empty($this->session->data['country_id']))							$address['country_id'] = $this->session->data['country_id'];
				if (!empty($this->session->data['zone_id']))							$address['zone_id'] = $this->session->data['zone_id'];
				if (!empty($this->session->data['postcode']))							$address['postcode'] = $this->session->data['postcode'];
				if (!empty($this->session->data['city']))								$address['city'] = $this->session->data['city'];
				
				if (!empty($this->session->data[$address_type . '_country_id']))		$address['country_id'] = $this->session->data[$address_type . '_country_id'];
				if (!empty($this->session->data[$address_type . '_zone_id']))			$address['zone_id'] = $this->session->data[$address_type . '_zone_id'];
				if (!empty($this->session->data[$address_type . '_postcode']))			$address['postcode'] = $this->session->data[$address_type . '_postcode'];
				if (!empty($this->session->data[$address_type . '_city']))				$address['city'] = $this->session->data[$address_type . '_city'];
				
				if (!empty($this->session->data['guest'][$address_type]))				$address = $this->session->data['guest'][$address_type];
				if (!empty($this->session->data[$address_type . '_address_id'])) {
					if (version_compare(VERSION, '4.0.2.0', '<')) {
						$address = $this->model_account_address->getAddress($this->session->data[$address_type . '_address_id']);
					} else {
						$address = $this->model_account_address->getAddress($this->customer->getId(), $this->session->data[$address_type . '_address_id']);
					}
				}
				if (!empty($this->session->data[$address_type . '_address']))			$address = $this->session->data[$address_type . '_address'];
			}
			
			if (empty($address))				$address = array();
			if (empty($address['company']))		$address['company'] = '';
			if (empty($address['address_1']))	$address['address_1'] = '';
			if (empty($address['address_2']))	$address['address_2'] = '';
			if (empty($address['city']))		$address['city'] = '';
			if (empty($address['postcode']))	$address['postcode'] = '';
			if (empty($address['country_id']))	$address['country_id'] = $this->config->get('config_country_id');
			if (empty($address['zone_id']))		$address['zone_id'] =  $this->config->get('config_zone_id');
			
			$country_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "country WHERE country_id = " . (int)$address['country_id']);
			$address['country'] = (isset($country_query->row['name'])) ? $country_query->row['name'] : '';
			$address['iso_code_2'] = (isset($country_query->row['iso_code_2'])) ? $country_query->row['iso_code_2'] : '';
			
			$zone_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone WHERE zone_id = " . (int)$address['zone_id']);
			$address['zone'] = (isset($zone_query->row['name'])) ? $zone_query->row['name'] : '';
			$address['zone_code'] = (isset($zone_query->row['code'])) ? $zone_query->row['code'] : '';
			
			$addresses[$address_type] = $address;
			
			$addresses[$address_type]['geo_zones'] = array();
			$geo_zones_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE country_id = " . (int)$address['country_id'] . " AND (zone_id = 0 OR zone_id = " . (int)$address['zone_id'] . ")");
			if ($geo_zones_query->num_rows) {
				foreach ($geo_zones_query->rows as $geo_zone) {
					$addresses[$address_type]['geo_zones'][] = $geo_zone['geo_zone_id'];
				}
			} else {
				$addresses[$address_type]['geo_zones'] = array(0);
			}
		}
		
		if (version_compare(VERSION, '4.0', '>=')) {
			$billing_address_required = (version_compare(VERSION, '4.0.2.0', '<')) ? $this->config->get('config_checkout_address') : $this->config->get('config_checkout_payment_address');
			if (!$billing_address_required) {
				$addresses['payment'] = $addresses['shipping'];
			}
		}
		
		// Record testing mode info
		if ($this->customer->isLogged()) {
			$this->logMessage('CUSTOMER: ' . $this->customer->getFirstName() . ' ' . $this->customer->getLastName() . ' (customer_id: ' . $this->customer->getId() . ', ip: ' . $this->request->server['REMOTE_ADDR'] . ')');
		} else {
			$this->logMessage('CUSTOMER: Guest (' . $this->request->server['REMOTE_ADDR'] . ')');
		}
		
		if ($this->type != 'shipping') {
			$billing_address = array(
				$addresses['payment']['address_1'],
				$addresses['payment']['address_2'],
				$addresses['payment']['city'],
				$addresses['payment']['zone'],
				$addresses['payment']['postcode'],
				$addresses['payment']['country'],
			);
			$this->logMessage('BILLING ADDRESS: ' . implode(', ', array_filter($billing_address)));
		}
		
		$shipping_address = array(
			$addresses['shipping']['address_1'],
			$addresses['shipping']['address_2'],
			$addresses['shipping']['city'],
			$addresses['shipping']['zone'],
			$addresses['shipping']['postcode'],
			$addresses['shipping']['country'],
		);
		$this->logMessage('SHIPPING ADDRESS: ' . implode(', ', array_filter($shipping_address)));
		
		// Set order totals if necessary
		if ($this->type != 'total') {
			if ($this->type == 'shipping') {
				$stop_before = 'shipping';
			} elseif ($this->type == 'module') {
				$stop_before = 'total';
			} else {
				$stop_before = $this->name;
			}
			
			$order_totals = $this->getOrderTotals($stop_before);
			
			$total_data = $order_totals['totals'];
			$order_total = $order_totals['total'];
		}
		
		// Set cart data
		$this->load->model('catalog/product');
		
		$list_of_products = array();
		$cart_products = $this->cart->getProducts();
		
		foreach ($cart_products as &$cart_product) {
			$product_options = array();
			foreach ($cart_product['option'] as $option) {
				$product_options[] = $option['name'] . ': ' . $option['value'];
			}
			$list_of_products[] = $cart_product['name'] . ($product_options ? ' (' . implode(', ', $product_options) . ')' : '');
			
			if (version_compare(VERSION, '2.1', '>=')) {
				if (!empty($cart_product['recurring']['recurring_id'])) {
					$recurring_or_subscription_id = $cart_product['recurring']['recurring_id'];
				} elseif (!empty($cart_product['subscription']['subscription_plan_id'])) {
					$recurring_or_subscription_id = $cart_product['subscription']['subscription_plan_id'];
				} else {
					$recurring_or_subscription_id = 0;
				}
				$cart_product['recurring_or_subscription_id'] = $recurring_or_subscription_id;
				$cart_product['key'] = $cart_product['product_id'] . json_encode($cart_product['option']) . $recurring_or_subscription_id;
			}
		}
		
		$this->logMessage('PRODUCTS: ' . implode(', ', $list_of_products));
		
		// Set variables
		if ($this->customer->isLogged()) {
			$customer_group_id = (int)$this->customer->getGroupId();
		} elseif (!empty($this->session->data['customer']['customer_id']) && !empty($this->session->data['customer']['customer_group_id'])) {
			$customer_group_id = $this->session->data['customer']['customer_group_id'];
		} else {
			$customer_group_id = 0;
		}
		
		$cumulative_total_value = $order_total;
		$currency = $this->session->data['currency'];
		$customer_id = (int)$this->customer->getId();
		$distance = 0;
		$language = (!empty($this->session->data['language'])) ? $this->session->data['language'] : $this->config->get('config_language');
		$main_currency = $this->db->query("SELECT * FROM " . DB_PREFIX . "setting WHERE `key` = 'config_currency' AND store_id = 0 ORDER BY setting_id DESC LIMIT 1")->row['value'];
		$store_id = (isset($this->session->data['store_id'])) ? (int)$this->session->data['store_id'] : (int)$this->config->get('config_store_id');
		
		// Loop through charges
		$sort_order = array();
		foreach ($settings['charge'] as $key => $value) {
			$sort_order[$key] = (empty($value['group'])) ? 0 : $value['group'];
		}
		array_multisort($sort_order, SORT_ASC, $settings['charge']);
		
		$charges = array();
		
		foreach ($settings['charge'] as $charge) {
			// Set up basic charge data
			if (empty($charge['group'])) {
				$group_text = '';
				$charge['group'] = 0;
			} else {
				$group_text = (!empty($charge['group'])) ? $charge['group'] . ' - ' : '';
				$charge['group'] = trim($charge['group']);
			}
			
			if (!empty($charge['title_admin'])) {
				$charge['title'] = $group_text . $charge['title_admin'];
			} elseif (!empty($charge['title_' . $language])) {
				$charge['title'] = $group_text . $charge['title_' . $language];
			} elseif (!empty($charge['group'])) {
				$charge['title'] = '(Charge ' . $charge['group'] . ')';
			} else {
				$charge['title'] = '';
			}
			
			$this->logMessage("\n" . 'CHECKING CHARGE ' . $charge['title']);
			
			if (substr($charge['group'], 0, 1) == '-') {
				$this->logMessage('Disabled due to a negative Group value');
				continue;
			}
			
			if (empty($charge['type'])) {
				$charge['type'] = str_replace(array('_based', '_fee'), '', $this->name);
			}
			
			$this->charge = $charge;
			
			// Compile rules and rule sets
			$rule_list = (!empty($charge['rule'])) ? $charge['rule'] : array();
			$rule_sets = array();
			
			foreach ($rule_list as $rule) {
				if (isset($rule['type']) && $rule['type'] == 'rule_set') {
					$rule_sets[] = $settings['rule_set'][$rule['value']]['rule'];
				}
			}
			
			foreach ($rule_sets as $rule_set) {
				$rule_list = array_merge($rule_list, $rule_set);
			}
			
			$rules = array();
			
			foreach ($rule_list as $rule) {
				if (empty($rule['type'])) continue;
				
				if (isset($rule['comparison'])) {
					if (in_array($rule['type'], array('attribute', 'custom_field', 'option', 'quantity_of_product'))) {
						$comparison = substr($rule['comparison'], strrpos($rule['comparison'], '[') + 1, -1);
					} else {
						$comparison = $rule['comparison'];
					}
				} else {
					$comparison = '';
				}
				
				if (isset($rule['value'])) {
					if (in_array($rule['type'], array('attribute_group', 'category', 'filter', 'manufacturer', 'product', 'zone'))) {
						$value = substr($rule['value'], strrpos($rule['value'], '[') + 1, -1);
					} else {
						$value = $rule['value'];
					}
				} else {
					$value = 1;
				}
				
				$rules[$rule['type']][$comparison][] = $value;
			}
			
			$this->charge['rules'] = $rules;
			
			// Check location criteria
			if (isset($rules['location_comparison'])) {
				$location_comparison = $rules['location_comparison'][''][0];
			} else {
				$location_comparison = ($this->type == 'shipping' || empty($addresses['payment']['city'])) ? 'shipping' : 'payment';
			}
			$address = $addresses[$location_comparison];
			$postcode = ($address['iso_code_2'] == 'US') ? substr($address['postcode'], 0, 5) : $address['postcode'];
			
			if ($this->ruleViolation('country', $address['country_id']) ||
				$this->ruleViolation('geo_zone', $address['geo_zones']) ||
				$this->ruleViolation('zone', $address['zone_id'])
			) {
				continue;
			}
			
			// Check order criteria
			if ($this->ruleViolation('currency', $currency) ||
				$this->ruleViolation('customer_group', $customer_group_id) ||
				$this->ruleViolation('language', $language) ||
				$this->ruleViolation('store', $store_id)
			) {
				continue;
			}
			
			// Generate comparison values
			$quantity = 0;
			$cart_criteria = array(
				'total',
			);
			
			foreach ($cart_criteria as $spec) {
				${$spec.'s'} = array();
				if (isset($rules[$spec])) {
					$this->commaMerge($rules[$spec]);
				}
			}
			
			$product_keys = array();
			$total_value = $cumulative_total_value;
			
			foreach ($cart_products as $product) {
				if ($this->type == 'shipping' && !$product['shipping']) {
					$total_value -= $product['total'];
					$this->logMessage($product['name'] . ' (product_id: ' . $product['product_id'] . ') was ignored because it does not require shipping');
					continue;
				}
				
				$product_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product WHERE product_id = " . (int)$product['product_id']);
				
				// total
				if (isset($rules['total_value'])) {
					$product_info = $this->model_catalog_product->getProduct($product['product_id']);
					$product_price = ($product_info['special']) ? $product_info['special'] : $product_info['price'];
					
					if (in_array('prediscounted', $rules['total_value'][''])) {
						$totals[$product['key']] = $product['total'] + ($product['quantity'] * ($product_query->row['price'] - $product_price));
					} elseif (in_array('nondiscounted', $rules['total_value'][''])) {
						$product_discount_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_discount WHERE product_id = " . (int)$product['product_id'] . " AND customer_group_id = " . (int)($customer_group_id ? $customer_group_id : $this->config->get('config_customer_group_id')) . " AND quantity <= " . (int)$product['quantity'] . " AND ((date_start = '0000-00-00' OR date_start < NOW()) AND (date_end = '0000-00-00' OR date_end > NOW())) ORDER BY quantity DESC, priority ASC, price ASC LIMIT 1");
						$totals[$product['key']] = ($product_info['special'] || $product_discount_query->num_rows) ? 0 : $product['total'];
					} elseif (in_array('taxed', $rules['total_value'][''])) {
						$totals[$product['key']] = $this->tax->calculate($product['total'], $product['tax_class_id']);
					}
				}
				if (!isset($totals[$product['key']])) {
					$totals[$product['key']] = $product['total'];
				}
				
				// product passed all rules and is eligible for charge
				$product_keys[] = $product['key'];
			}
			
			if (empty($product_keys)) {
				$disable_charge = true;
				
				if (!empty($this->session->data['vouchers'])) {
					$disable_charge = false;
					foreach ($rules as $type => $value) {
						if (in_array($type, array('attribute', 'attribute_group', 'category', 'manufacturer', 'option', 'product', 'product_group', 'other_product_data', 'quantity', 'volume', 'weight'))) {
							$disable_charge = true;
						}
					}
				}
				
				if ($disable_charge) {
					$this->logMessage('Disabled for having no eligible products');
					continue;
				}
			}
			
			// Check cart criteria and generate total comparison values
			$single_foreign_currency = (isset($rules['currency']['is']) && count($rules['currency']['is']) == 1 && $main_currency != $currency) ? $rules['currency']['is'][0] : '';
			
			foreach ($cart_criteria as $spec) {
				if ($spec == 'total' && isset($rules['total_value']) && in_array('total', $rules['total_value'][''])) {
					$total = $total_value;
					$cart_total = $total_value;
				} else {
					${$spec} = 0;
					foreach ($product_keys as $product_key) {
						if ($spec == 'length' || $spec == 'width' || $spec == 'height') {
							${$spec} += ${$spec.'s'}[$product_key] * $quantitys[$product_key];
						} else {
							${$spec} += ${$spec.'s'}[$product_key];
						}
					}
					
					if ($spec == 'length' || $spec == 'width' || $spec == 'height') {
						${'cart_'.$spec} = 0;
						foreach (${$spec.'s'} as $key => $value) {
							${'cart_'.$spec} += $value * $quantitys[$key];
						}
					} else {
						${'cart_'.$spec} = array_sum(${$spec.'s'});
					}
				}
				if ($spec == 'total' && $single_foreign_currency) {
					$total = $this->currency->convert($total, $main_currency, $single_foreign_currency);
				}
			}
			
			// Calculate the charge
			$brackets = (!empty($charge['charges'])) ? array_filter(explode(',', str_replace(array("\n", ',,'), ',', $charge['charges']))) : array(0);
			
			$cost = $this->calculateBrackets($brackets, $charge['type'], ${$charge['type']}, $quantity, $total);
			
			if ($cost === false) {
				$this->logMessage('Disabled because the value "' . (isset(${$charge['type']}) ? ${$charge['type']} : '') . '" does not match any of the brackets "' . implode(', ', $brackets) . '"');
				continue;
			}
			
			// Adjust charge
			if (isset($rules['adjust']['charge'])) {
				foreach ($rules['adjust']['charge'] as $adjustment) {
					$cost += (strpos($adjustment, '%')) ? $cost * (float)$adjustment / 100 : (float)$adjustment;
				}
			}
			if (isset($rules['round'])) {
				foreach ($rules['round'] as $comparison => $values) {
					$round = $values[0];
					if ($comparison == 'nearest') {
						$cost = round($cost / $round) * $round;
					} elseif ($comparison == 'up') {
						$cost = ceil($cost / $round) * $round;
					} elseif ($comparison == 'down') {
						$cost = floor($cost / $round) * $round;
					}
				}
			}
			if (isset($rules['min'])) {
				$cost = max($cost, $rules['min'][''][0]);
			}
			if (isset($rules['max'])) {
				$cost = min($cost, $rules['max'][''][0]);
			}
			if ($single_foreign_currency) {
				$cost = $this->currency->convert($cost, $single_foreign_currency, $main_currency);
			}
			
			// Add to charge array
			$this->logMessage('All rules passed, enabled with cost ' . (float)$cost);
			
			$charges[strtolower($charge['group'])][] = array(
				'title'			=> str_replace('[' . $charge['type'] . ']', number_format(${$charge['type']}, 2), html_entity_decode($charge['title_' . $language], ENT_QUOTES, 'UTF-8')),
				'charge'		=> (float)$cost,
				'tax_class_id'	=> isset($rules['tax_class']) ? $rules['tax_class'][''][0] : $settings['tax_class_id'],
			);
			
			if ($this->type != 'shipping') {
				$cumulative_total_value += (float)$cost;
			}
			
		} // end charge loop
		
		$quote_data = array();
		
		foreach ($charges as $group_value => $group) {
			foreach ($group as $rate) {
				if (($this->type == 'shipping' && $rate['charge'] < 0) || ($this->type == 'total' && $rate['charge'] == 0)) continue;
				
				$taxed_charge = $this->tax->calculate($rate['charge'], $rate['tax_class_id'], $this->config->get('config_tax'));
				
				if ($settings['charge_sorting'] == 'group') {
					$sort_value = $group_value;
				} else {
					$sort_value = $rate['charge'];
				}
				
				$quote_data[$this->name . '_' . count($quote_data)] = array(
					'code'			=> $this->name . '.' . $this->name . '_' . count($quote_data),
					'extension'		=> $this->name,
					'sort_order'	=> $sort_value,
					'title'			=> $rate['title'],
					'name'			=> $rate['title'],
					'cost'			=> $rate['charge'],
					'value'			=> $rate['charge'],
					'tax_class_id'	=> $rate['tax_class_id'],
					'text'			=> $this->currency->format($this->type == 'total' ? $rate['charge'] : $taxed_charge, $currency),
				);
			}
		}
		
		$sort_order = array();
		foreach ($quote_data as $key => $value) $sort_order[$key] = $value['sort_order'];
		array_multisort($sort_order, SORT_ASC, $quote_data);
		
		foreach ($quote_data as $quote) {
			$quote['code'] = $this->name;
			$quote['sort_order'] = $settings['sort_order'] . '.' . $quote['sort_order'];
			
			$total_data[] = $quote;
			
			if ($quote['tax_class_id']) {
				foreach ($this->tax->getRates($quote['cost'], $quote['tax_class_id']) as $tax_rate) {
					$taxes[$tax_rate['tax_rate_id']] = (isset($taxes[$tax_rate['tax_rate_id']])) ? $taxes[$tax_rate['tax_rate_id']] + $tax_rate['amount'] : $tax_rate['amount'];
				}
			}
			
			$order_total += $quote['cost'];
		}
		
		if ($this->type == 'shipping' && $quote_data) {
			return array(
				'code'			=> $this->name,
				'title'			=> str_replace('[' . $charge['type'] . ']', number_format(${$charge['type']}, 2), html_entity_decode($settings['heading_' . $language], ENT_QUOTES, 'UTF-8')),
				'name'			=> str_replace('[' . $charge['type'] . ']', number_format(${$charge['type']}, 2), html_entity_decode($settings['heading_' . $language], ENT_QUOTES, 'UTF-8')),
				'quote'			=> $quote_data,
				'sort_order'	=> $settings['sort_order'],
				'error'			=> false
			);
		} else {
			return array();
		}
	}
	
	//==============================================================================
	// Private functions
	//==============================================================================
	private function getSettings() {
		$code = (version_compare(VERSION, '3.0', '<') ? '' : $this->type . '_') . $this->name;
		
		$settings = array();
		$settings_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "setting WHERE `code` = '" . $this->db->escape($code) . "' ORDER BY `key` ASC");
		
		foreach ($settings_query->rows as $setting) {
			$value = $setting['value'];
			if ($setting['serialized']) {
				$value = (version_compare(VERSION, '2.1', '<')) ? unserialize($setting['value']) : json_decode($setting['value'], true);
			}
			$split_key = preg_split('/_(\d+)_?/', str_replace($code . '_', '', $setting['key']), -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
			
				if (count($split_key) == 1)	$settings[$split_key[0]] = $value;
			elseif (count($split_key) == 2)	$settings[$split_key[0]][$split_key[1]] = $value;
			elseif (count($split_key) == 3)	$settings[$split_key[0]][$split_key[1]][$split_key[2]] = $value;
			elseif (count($split_key) == 4)	$settings[$split_key[0]][$split_key[1]][$split_key[2]][$split_key[3]] = $value;
			else 							$settings[$split_key[0]][$split_key[1]][$split_key[2]][$split_key[3]][$split_key[4]] = $value;
		}
		
		if (version_compare(VERSION, '4.0', '<')) {
			$settings['extension_route'] = 'extension/' . $this->type . '/' . $this->name;
		} else {
			$settings['extension_route'] = 'extension/' . $this->name . '/' . $this->type . '/' . $this->name;
		}
		
		return $settings;
	}
	
	private function logMessage($message) {
		if ($this->testing_mode) {
			$filepath = DIR_LOGS . $this->name . '.messages';
			if (is_file($filepath) && filesize($filepath) > 50000000) {
				file_put_contents($filepath, '');
			}
			file_put_contents($filepath, print_r($message, true) . "\n", FILE_APPEND|LOCK_EX);
		}
	}
	
	private function getOrderTotals($stop_before = '') {
		$prefix = (version_compare(VERSION, '3.0', '<')) ? '' : 'total_';
		$order_total_extensions = $this->db->query("SELECT * FROM " . DB_PREFIX . "extension WHERE `type` = 'total' ORDER BY `code` ASC")->rows;
		
		$sort_order = array();
		foreach ($order_total_extensions as $key => $value) {
			$sort_order[$key] = $this->config->get($prefix . $value['code'] . '_sort_order');
		}
		array_multisort($sort_order, SORT_ASC, $order_total_extensions);
		
		$order_totals = array();
		$total = 0;
		$taxes = $this->cart->getTaxes();
		$reference_array = array('totals' => &$order_totals, 'total' => &$total, 'taxes' => &$taxes);
		
		foreach ($order_total_extensions as $ot) {
			if ($ot['code'] == $this->name || $ot['code'] == $stop_before) {
				break;
			}
			if (!$this->config->get($prefix . $ot['code'] . '_status') || $ot['code'] == 'intermediate_order_total') {
				continue;
			}
			
			if (version_compare(VERSION, '2.2', '<')) {
				$this->load->model('total/' . $ot['code']);
				$this->{'model_total_' . $ot['code']}->getTotal($order_totals, $total, $taxes);
			} elseif (version_compare(VERSION, '2.3', '<')) {
				$this->load->model('total/' . $ot['code']);
				$this->{'model_total_' . $ot['code']}->getTotal($reference_array);
			} elseif (version_compare(VERSION, '4.0', '<')) {
				$this->load->model('extension/total/' . $ot['code']);
				$this->{'model_extension_total_' . $ot['code']}->getTotal($reference_array);
			} else {
				$this->load->model('extension/' . $ot['extension'] . '/total/' . $ot['code']);
				$getTotalFunction = $this->{'model_extension_' . $ot['extension'] . '_total_' . $ot['code']}->getTotal;
				$getTotalFunction($order_totals, $taxes, $total);
			}
		}
		
		return $reference_array;
	}
	
	private function curlRequest($url) {
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 3);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_TIMEOUT, 3);
		$response = json_decode(curl_exec($curl), true);
		curl_close($curl);
		return $response;
	}
	
	private function commaMerge(&$rule) {
		$merged_rule = array();
		foreach ($rule as $comparison => $values) {
			$merged_rule[$comparison] = array();
			foreach ($values as $value) {
				$merged_rule[$comparison] = array_merge($merged_rule[$comparison], array_map('trim', explode(',', strtolower($value))));
			}
		}
		$rule = $merged_rule;
	}
	
	private function ruleViolation($rule, $value, $product_name = '') {
		$violation = false;
		$rules = $this->charge['rules'];
		$function = (is_array($value)) ? 'array_intersect' : 'in_array';
		
		if (isset($rules[$rule]['after']) && strtotime($value) < min(array_map('strtotime', $rules[$rule]['after']))) {
			$violation = true;
			$comparison = 'after';
		}
		if (isset($rules[$rule]['before']) && strtotime($value) > max(array_map('strtotime', $rules[$rule]['before']))) {
			$violation = true;
			$comparison = 'before';
		}
		if (isset($rules[$rule]['is']) && !$function($value, $rules[$rule]['is'])) {
			$violation = true;
			$comparison = 'is';
		}
		if (isset($rules[$rule]['not']) && $function($value, $rules[$rule]['not'])) {
			$violation = true;
			$comparison = 'not';
		}
		
		if ($violation) {
			if ($product_name) {
				$this->logMessage($product_name . ' was ignored for violating rule "' . $rule . ' ' . $comparison . ' ' . implode(', ', $rules[$rule][$comparison]) . '" with value "' . (is_array($value) ? implode(',', $value) : $value) . '"');
			} else {
				$this->logMessage('Disabled for violating rule "' . $rule . ' ' . $comparison . ' ' . implode(', ', $rules[$rule][$comparison]) . '" with value "' . (is_array($value) ? implode(',', $value) : $value) . '"');
			}
		}
		
		return $violation;
	}
	
	private function inRange($value, $range_list, $charge_type = '', $skip_testing = false) {
		$in_range = false;
		
		foreach ($range_list as $range) {
			if ($range == '') continue;
			
			$range = (strpos($range, '::')) ? explode('::', $range) : explode('-', $range);
			
			if (strpos($charge_type, 'distance') === 0) {
				if (empty($range[1])) {
					array_unshift($range, 0);
				}
				if ($value >= (float)$range[0] && $value <= (float)$range[1]) {
					$in_range = true;
				}
			} elseif (strpos($charge_type, 'postcode') === 0) {
				$postcode = preg_replace('/[^A-Z0-9]/', '', strtoupper($value));
				$from = preg_replace('/[^A-Z0-9]/', '', strtoupper($range[0]));
				$to = (isset($range[1])) ? preg_replace('/[^A-Z0-9]/', '', strtoupper($range[1])) : $from;
				
				if (strlen($from) < 3 && !preg_match('/[0-9]/', $from)) $from .= '1';
				if (strlen($to) < 3 && !preg_match('/[0-9]/', $to)) $to .= '99';
				
				if (strlen($from) < strlen($postcode)) $from = str_pad($from, max(strlen($postcode), strlen($from) + 3), ' ');
				if (strlen($to) < strlen($postcode)) $to = str_pad($to, max(strlen($postcode), strlen($to) + 3), preg_match('/[A-Z]/', $postcode) ? 'Z' : '9');
				
				$postcode = substr_replace(substr_replace($postcode, ' ', -3, 0), ' ', -2, 0);
				$from = substr_replace(substr_replace($from, ' ', -3, 0), ' ', -2, 0);
				$to = substr_replace(substr_replace($to, ' ', -3, 0), ' ', -2, 0);
				
				if (strnatcasecmp($postcode, $from) >= 0 && strnatcasecmp($postcode, $to) <= 0) {
					$in_range = true;
				}
			} else {
				if (!isset($range[1]) && $charge_type != 'attribute' && $charge_type != 'custom_field' && strpos($charge_type, 'customer_data') !== 0 && $charge_type != 'option' && $charge_type != 'other product data') {
					$range[1] = 999999999;
				}
				
				if ((count($range) > 1 && $value >= $range[0] && $value <= $range[1]) || (count($range) == 1 && $value == $range[0])) {
					$in_range = true;
				}
			}
		}
		
		if (empty($value) && empty($range_list[0])) {
			$in_range = true;
		}
		
		if (!$skip_testing) {
			if (strpos($charge_type, ' not') ? $in_range : !$in_range) {
				$this->logMessage('Disabled for violating rule "' . $charge_type . (strpos($charge_type, ' not') ? ' ' : ' is ') . implode(', ', $range_list) . '" with value "' . $value . '"');
			}
		}
		
		return $in_range;
	}
	
	private function calculateBrackets($brackets, $charge_type, $comparison_value, $quantity, $total) {
		$to = 0;
		
		foreach ($brackets as $bracket) {
			$bracket = str_replace(array('::', ':'), array('~', '='), $bracket);
			
			$bracket_pieces = explode('=', $bracket);
			if (count($bracket_pieces) == 1) {
				array_unshift($bracket_pieces, ($charge_type == 'postcode') ? '0-ZZZZ' : '0-999999');
			}
			
			$from_and_to = (strpos($bracket_pieces[0], '~')) ? explode('~', $bracket_pieces[0]) : explode('-', $bracket_pieces[0]);
			if (count($from_and_to) == 1) {
				array_unshift($from_and_to, ($charge_type == 'postcode') ? $from_and_to[0] : $to);
			}
			$from = trim($from_and_to[0]);
			$to = trim($from_and_to[1]);
			
			$cost_and_per = explode('/', $bracket_pieces[1]);
			$per = (isset($cost_and_per[1])) ? (float)$cost_and_per[1] : 0;
			
			$top = min($to, $comparison_value);
			$bottom = (isset($this->charge['rules']['cumulative'])) ? $from : 0;
			$difference = ($charge_type == 'postcode' || $charge_type == 'price') ? $quantity : $top - $bottom;
			$multiplier = ($per) ? ceil($difference / $per) : 1;
			
			if (!isset($cost) || !isset($this->charge['rules']['cumulative'])) {
				$cost = 0;
			}
			$cost += (strpos($cost_and_per[0], '%')) ? (float)$cost_and_per[0] * $multiplier * $total / 100 : (float)$cost_and_per[0] * $multiplier;
			
			$in_range = $this->inRange($comparison_value, array($from . '::' . $to), $charge_type, true);
			if ($in_range) {
				return $cost;
			}
		}
		
		return false;
	}
}
?>