<?php
class ControllerCommonMenu extends Controller {
	public function index() {
		$this->load->language('common/menu');

		// Menu
		$this->load->model('catalog/category');

		$this->load->model('catalog/product');

		$data['categories'] = array();

		$categories = $this->model_catalog_category->getCategories(0);

		foreach ($categories as $category) {
			if ($category['top']) {
				// Level 2
				$children_data = array();

				$children = $this->model_catalog_category->getCategories($category['category_id']);

				foreach ($children as $child) {
					$filter_data = array(
						'filter_category_id'  => $child['category_id'],
						'filter_sub_category' => true
					);

					$children_data[] = array(
						'name'  => $child['name'] . ($this->config->get('config_product_count') ? ' (' . $this->model_catalog_product->getTotalProducts($filter_data) . ')' : ''),
						'href'  => $this->url->link('product/category', 'path=' . $category['category_id'] . '_' . $child['category_id'])
					);
				}

				// Level 1
				$data['categories'][] = array(
					'name'     => $category['name'],
					'children' => $children_data,
					'column'   => $category['column'] ? $category['column'] : 1,
					'href'     => $this->url->link('product/category', 'path=' . $category['category_id'])
				);
			}
		}

		// "O nas" dropdown — load titles from OC information pages
		$this->load->model('catalog/information');
		$onas_ids = array(4, 11, 12);
		$data['onas_links'] = array();
		foreach ($onas_ids as $id) {
			$info = $this->model_catalog_information->getInformation($id);
			if ($info) {
				$href = ($id === 12)
					? $this->url->link('information/contact')
					: $this->url->link('information/information', 'information_id=' . $id);
				$data['onas_links'][] = array(
					'name' => $info['title'],
					'href' => $href
				);
			}
		}

		// Paket presenečenja — hardcoded sub-categories need counts passed separately
		if ($this->config->get('config_product_count')) {
			$data['paket_count_deklice'] = $this->model_catalog_product->getTotalProducts(array('filter_category_id' => 247, 'filter_sub_category' => true));
			$data['paket_count_decke']   = $this->model_catalog_product->getTotalProducts(array('filter_category_id' => 248, 'filter_sub_category' => true));
		} else {
			$data['paket_count_deklice'] = '';
			$data['paket_count_decke']   = '';
		}

		return $this->load->view('common/menu', $data);
	}
}
