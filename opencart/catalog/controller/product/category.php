<?php
class ControllerProductCategory extends Controller {
	public function index() {
		$this->load->language('product/category');

		$this->load->model('catalog/category');

		$this->load->model('catalog/product');

		$this->load->model('tool/image');

		if (isset($this->request->get['filter'])) {
			$filter = $this->request->get['filter'];
		} else {
			$filter = '';
		}

		if (isset($this->request->get['filter_manufacturer'])) {
			$filter_manufacturer = $this->request->get['filter_manufacturer'];
		} else {
			$filter_manufacturer = '';
		}

		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
		} else {
			$sort = 'p.date_added';
		}

		if (isset($this->request->get['order'])) {
			$order = $this->request->get['order'];
		} else {
			$order = 'DESC';
		}

		if (isset($this->request->get['page'])) {
			$page = (int)$this->request->get['page'];
		} else {
			$page = 1;
		}

		if (isset($this->request->get['limit']) && (int)$this->request->get['limit'] > 0) {
			$limit = (int)$this->request->get['limit'];
		} else {
			$limit = $this->config->get('theme_' . $this->config->get('config_theme') . '_product_limit');
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		if (isset($this->request->get['path'])) {
			$url = '';

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['limit'])) {
				$url .= '&limit=' . $this->request->get['limit'];
			}

			$path = '';

			$parts = explode('_', (string)$this->request->get['path']);

			$category_id = (int)array_pop($parts);

			foreach ($parts as $path_id) {
				if (!$path) {
					$path = (int)$path_id;
				} else {
					$path .= '_' . (int)$path_id;
				}

				$category_info = $this->model_catalog_category->getCategory($path_id);

				if ($category_info) {
					$data['breadcrumbs'][] = array(
						'text' => $category_info['name'],
						'href' => $this->url->link('product/category', 'path=' . $path . $url)
					);
				}
			}
		} else {
			$category_id = 0;
		}

		$category_info = $this->model_catalog_category->getCategory($category_id);

		if ($category_info) {
			$this->document->setTitle($category_info['meta_title']);
			$this->document->setDescription($category_info['meta_description']);
			$this->document->setKeywords($category_info['meta_keyword']);

			$data['heading_title'] = $category_info['name'];

			$data['text_compare'] = sprintf($this->language->get('text_compare'), (isset($this->session->data['compare']) ? count($this->session->data['compare']) : 0));

			// Set the last category breadcrumb
			$data['breadcrumbs'][] = array(
				'text' => $category_info['name'],
				'href' => $this->url->link('product/category', 'path=' . $this->request->get['path'])
			);

			if ($category_info['image']) {
				$data['thumb'] = $this->model_tool_image->resize($category_info['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_category_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_category_height'));
			} else {
				$data['thumb'] = '';
			}

			$data['description'] = html_entity_decode($category_info['description'], ENT_QUOTES, 'UTF-8');
			$data['compare'] = $this->url->link('product/compare');

			$url = '';

			if (isset($this->request->get['filter'])) {
				$url .= '&filter=' . $this->request->get['filter'];
			}

			if (isset($this->request->get['filter_manufacturer'])) {
				$url .= '&filter_manufacturer=' . $this->request->get['filter_manufacturer'];
			}

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['limit'])) {
				$url .= '&limit=' . $this->request->get['limit'];
			}

			$data['categories'] = array();

			$results = $this->model_catalog_category->getCategories($category_id);

			foreach ($results as $result) {
				$filter_data = array(
					'filter_category_id'  => $result['category_id'],
					'filter_sub_category' => true
				);

				$data['categories'][] = array(
					'name' => $result['name'] . ($this->config->get('config_product_count') ? ' (' . $this->model_catalog_product->getTotalProducts($filter_data) . ')' : ''),
					'href' => $this->url->link('product/category', 'path=' . $this->request->get['path'] . '_' . $result['category_id'] . $url)
				);
			}

			$data['products'] = array();

			$filter_data = array(
				'filter_category_id' => $category_id,
				'filter_filter'      => $filter,
				'sort'               => $sort,
				'order'              => $order,
				'start'              => ($page - 1) * $limit,
				'limit'              => $limit
			);

			// Manufacturer filter — supports multiple IDs comma-separated
			if ($filter_manufacturer) {
				$manufacturer_ids = array_map('intval', explode(',', $filter_manufacturer));
				if (count($manufacturer_ids) == 1) {
					$filter_data['filter_manufacturer_id'] = $manufacturer_ids[0];
				} else {
					$filter_data['filter_manufacturer_ids'] = $manufacturer_ids;
				}
			}

			$product_total = $this->model_catalog_product->getTotalProducts($filter_data);

			$results = $this->model_catalog_product->getProducts($filter_data);

			foreach ($results as $result) {
				if ($result['image']) {
					$image = $this->model_tool_image->resize($result['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height'));
				} else {
					$image = $this->model_tool_image->resize('placeholder.png', $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height'));
				}

				if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
					$price = $this->currency->format($this->tax->calculate($result['price'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
				} else {
					$price = false;
				}

				if (!is_null($result['special']) && (float)$result['special'] >= 0) {
					$special = $this->currency->format($this->tax->calculate($result['special'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
					$tax_price = (float)$result['special'];
				} else {
					$special = false;
					$tax_price = (float)$result['price'];
				}
	
				if ($this->config->get('config_tax')) {
					$tax = $this->currency->format($tax_price, $this->session->data['currency']);
				} else {
					$tax = false;
				}

				if ($this->config->get('config_review_status')) {
					$rating = (int)$result['rating'];
				} else {
					$rating = false;
				}

				$data['products'][] = array(
					'product_id'   => $result['product_id'],
					'thumb'        => $image,
					'name'         => $result['name'],
					'manufacturer' => $result['manufacturer'],
					'description'  => utf8_substr(trim(strip_tags(html_entity_decode($result['description'], ENT_QUOTES, 'UTF-8'))), 0, $this->config->get('theme_' . $this->config->get('config_theme') . '_product_description_length')) . '..',
					'price'        => $price,
					'special'      => $special,
					'tax'          => $tax,
					'minimum'      => $result['minimum'] > 0 ? $result['minimum'] : 1,
					'rating'       => $result['rating'],
					'href'         => $this->url->link('product/product', 'path=' . $this->request->get['path'] . '&product_id=' . $result['product_id'] . $url)
				);
			}

			// Get manufacturers for products in this category
			$manufacturer_query = $this->db->query("SELECT DISTINCT m.manufacturer_id, m.name FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "manufacturer m ON (p.manufacturer_id = m.manufacturer_id) LEFT JOIN " . DB_PREFIX . "product_to_category p2c ON (p.product_id = p2c.product_id) WHERE p2c.category_id = '" . (int)$category_id . "' AND p.status = '1' AND p.manufacturer_id > 0 AND m.name != '' ORDER BY m.name ASC");

			$data['manufacturers'] = array();
			$data['filter_manufacturer'] = $filter_manufacturer;
			$filter_manufacturer_ids = $filter_manufacturer ? array_map('intval', explode(',', $filter_manufacturer)) : array();

			foreach ($manufacturer_query->rows as $mfr) {
				$mfr_count_data = array(
					'filter_category_id'  => $category_id,
					'filter_manufacturer_id' => (int)$mfr['manufacturer_id']
				);
				$data['manufacturers'][] = array(
					'manufacturer_id' => $mfr['manufacturer_id'],
					'name'            => html_entity_decode($mfr['name'], ENT_QUOTES, 'UTF-8'),
					'selected'        => in_array((int)$mfr['manufacturer_id'], $filter_manufacturer_ids),
					'count'           => $this->model_catalog_product->getTotalProducts($mfr_count_data)
				);
			}

			// Build active filter chips
			$data['active_filters'] = array();

			// Active OC filters (size etc.)
			if ($filter) {
				$filter_ids = explode(',', $filter);
				foreach ($filter_ids as $filter_id) {
					$filter_info = $this->db->query("SELECT fd.name FROM " . DB_PREFIX . "filter f LEFT JOIN " . DB_PREFIX . "filter_description fd ON (f.filter_id = fd.filter_id) WHERE f.filter_id = '" . (int)$filter_id . "' AND fd.language_id = '" . (int)$this->config->get('config_language_id') . "'");
					if ($filter_info->num_rows) {
						// Build URL without this filter
						$remaining = array_diff($filter_ids, array($filter_id));
						$remove_url = 'path=' . $this->request->get['path'];
						if ($remaining) {
							$remove_url .= '&filter=' . implode(',', $remaining);
						}
						if ($filter_manufacturer) {
							$remove_url .= '&filter_manufacturer=' . $filter_manufacturer;
						}

						$data['active_filters'][] = array(
							'name'       => $filter_info->row['name'],
							'type'       => 'filter',
							'remove_url' => html_entity_decode($this->url->link('product/category', $remove_url), ENT_QUOTES, 'UTF-8')
						);
					}
				}
			}

			// Active manufacturer filters
			if ($filter_manufacturer) {
				$mfr_ids = explode(',', $filter_manufacturer);
				foreach ($manufacturer_query->rows as $mfr) {
					if (in_array($mfr['manufacturer_id'], $mfr_ids)) {
						$remaining_mfrs = array_diff($mfr_ids, array($mfr['manufacturer_id']));
						$remove_url = 'path=' . $this->request->get['path'];
						if ($filter) {
							$remove_url .= '&filter=' . $filter;
						}
						if ($remaining_mfrs) {
							$remove_url .= '&filter_manufacturer=' . implode(',', $remaining_mfrs);
						}

						$data['active_filters'][] = array(
							'name'       => html_entity_decode($mfr['name'], ENT_QUOTES, 'UTF-8'),
							'type'       => 'manufacturer',
							'remove_url' => html_entity_decode($this->url->link('product/category', $remove_url), ENT_QUOTES, 'UTF-8')
						);
					}
				}
			}

			// Clear all URL (no filters)
			$data['clear_filters_url'] = html_entity_decode($this->url->link('product/category', 'path=' . $this->request->get['path']), ENT_QUOTES, 'UTF-8');

			// Filter groups for desktop sidebar (same data as filter module)
			$data['sidebar_filter_groups'] = array();
			$sidebar_fg = $this->model_catalog_category->getCategoryFilters($category_id);
			if ($sidebar_fg) {
				foreach ($sidebar_fg as $fg) {
					$fg_filters = array();
					foreach ($fg['filter'] as $f) {
						$filter_count_data = array(
							'filter_category_id' => $category_id,
							'filter_filter'      => $f['filter_id']
						);
						$fg_filters[] = array(
							'filter_id' => $f['filter_id'],
							'name'      => $f['name'],
							'count'     => $this->model_catalog_product->getTotalProducts($filter_count_data)
						);
					}
					$data['sidebar_filter_groups'][] = array(
						'name'   => $fg['name'],
						'filter' => $fg_filters
					);
				}
			}
			$data['sidebar_filter_category'] = $filter ? explode(',', $filter) : array();

			// Build 3-level category tree for the category sheet
			// Level 1: top-level parents (Deklice, Dečki, Nosečnice)
			// Level 2: direct children of active parent
			// Level 3: direct children of active level-2 category
			$current_path = isset($this->request->get['path']) ? $this->request->get['path'] : '';
			$path_parts = explode('_', $current_path);

			$top_parent_id = (int)$path_parts[0]; // e.g. 226 for Deklice
			$level2_id = isset($path_parts[1]) ? (int)$path_parts[1] : 0; // e.g. 228 for Jakne in Bunde
			$level3_id = isset($path_parts[2]) ? (int)$path_parts[2] : 0; // e.g. Brezrokavniki

			$data['category_tree'] = array();

			// Get top-level categories (parent_id = 0)
			$top_cats = $this->model_catalog_category->getCategories(0);

			foreach ($top_cats as $top) {
				$top_id = (int)$top['category_id'];
				$top_path = (string)$top_id;
				$top_node = array(
					'name'     => $top['name'],
					'href'     => html_entity_decode($this->url->link('product/category', 'path=' . $top_path), ENT_QUOTES, 'UTF-8'),
					'active'   => ($top_id == $category_id),
					'current_parent' => ($top_id == $top_parent_id),
					'children' => array()
				);

				// Always include level-2 children for all parents (JS handles expand/collapse)
				$level2_cats = $this->model_catalog_category->getCategories($top_id);
				foreach ($level2_cats as $l2) {
					$l2_id = (int)$l2['category_id'];
					$l2_path = $top_path . '_' . $l2_id;
					$l2_node = array(
						'name'     => $l2['name'],
						'href'     => html_entity_decode($this->url->link('product/category', 'path=' . $l2_path), ENT_QUOTES, 'UTF-8'),
						'active'   => ($l2_id == $category_id),
						'current_parent' => ($l2_id == $level2_id && $top_id == $top_parent_id),
						'children' => array()
					);

					// Show level-3 children only under the active level-2
					if ($l2_id == $level2_id && $top_id == $top_parent_id) {
						$level3_cats = $this->model_catalog_category->getCategories($l2_id);
						foreach ($level3_cats as $l3) {
							$l3_id = (int)$l3['category_id'];
							$l3_path = $l2_path . '_' . $l3_id;
							$l2_node['children'][] = array(
								'name'     => $l3['name'],
								'href'     => html_entity_decode($this->url->link('product/category', 'path=' . $l3_path), ENT_QUOTES, 'UTF-8'),
								'active'   => ($l3_id == $category_id),
								'children' => array()
							);
						}
					}

					$top_node['children'][] = $l2_node;
				}

				$data['category_tree'][] = $top_node;
			}

			$url = '';

			if (isset($this->request->get['filter'])) {
				$url .= '&filter=' . $this->request->get['filter'];
			}

			if (isset($this->request->get['filter_manufacturer'])) {
				$url .= '&filter_manufacturer=' . $this->request->get['filter_manufacturer'];
			}

			if (isset($this->request->get['limit'])) {
				$url .= '&limit=' . $this->request->get['limit'];
			}

			$data['sorts'] = array();

			$data['sorts'][] = array(
				'text'  => 'Zadnje dodano',
				'value' => 'p.date_added-DESC',
				'href'  => $this->url->link('product/category', 'path=' . $this->request->get['path'] . '&sort=p.date_added&order=DESC' . $url)
			);

			$data['sorts'][] = array(
				'text'  => 'Najnižji ceni',
				'value' => 'p.price-ASC',
				'href'  => $this->url->link('product/category', 'path=' . $this->request->get['path'] . '&sort=p.price&order=ASC' . $url)
			);

			$data['sorts'][] = array(
				'text'  => 'Najvišji ceni',
				'value' => 'p.price-DESC',
				'href'  => $this->url->link('product/category', 'path=' . $this->request->get['path'] . '&sort=p.price&order=DESC' . $url)
			);

			$url = '';

			if (isset($this->request->get['filter'])) {
				$url .= '&filter=' . $this->request->get['filter'];
			}

			if (isset($this->request->get['filter_manufacturer'])) {
				$url .= '&filter_manufacturer=' . $this->request->get['filter_manufacturer'];
			}

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			$data['limits'] = array();

			$limits = array_unique(array($this->config->get('theme_' . $this->config->get('config_theme') . '_product_limit'), 25, 50, 75, 100));

			sort($limits);

			foreach($limits as $value) {
				$data['limits'][] = array(
					'text'  => $value,
					'value' => $value,
					'href'  => $this->url->link('product/category', 'path=' . $this->request->get['path'] . $url . '&limit=' . $value)
				);
			}

			$url = '';

			if (isset($this->request->get['filter'])) {
				$url .= '&filter=' . $this->request->get['filter'];
			}

			if (isset($this->request->get['filter_manufacturer'])) {
				$url .= '&filter_manufacturer=' . $this->request->get['filter_manufacturer'];
			}

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['limit'])) {
				$url .= '&limit=' . $this->request->get['limit'];
			}

			$pagination = new Pagination();
			$pagination->total = $product_total;
			$pagination->page = $page;
			$pagination->limit = $limit;
			$pagination->url = $this->url->link('product/category', 'path=' . $this->request->get['path'] . $url . '&page={page}');

			$data['pagination'] = $pagination->render();

			$data['product_total'] = $product_total;

			$data['results'] = sprintf($this->language->get('text_pagination'), ($product_total) ? (($page - 1) * $limit) + 1 : 0, ((($page - 1) * $limit) > ($product_total - $limit)) ? $product_total : ((($page - 1) * $limit) + $limit), $product_total, ceil($product_total / $limit));

			// http://googlewebmastercentral.blogspot.com/2011/09/pagination-with-relnext-and-relprev.html
			if ($page == 1) {
			    $this->document->addLink($this->url->link('product/category', 'path=' . $category_info['category_id']), 'canonical');
			} else {
				$this->document->addLink($this->url->link('product/category', 'path=' . $category_info['category_id'] . '&page='. $page), 'canonical');
			}
			
			if ($page > 1) {
			    $this->document->addLink($this->url->link('product/category', 'path=' . $category_info['category_id'] . (($page - 2) ? '&page='. ($page - 1) : '')), 'prev');
			}

			if ($limit && ceil($product_total / $limit) > $page) {
			    $this->document->addLink($this->url->link('product/category', 'path=' . $category_info['category_id'] . '&page='. ($page + 1)), 'next');
			}

			$data['sort'] = $sort;
			$data['order'] = $order;
			$data['limit'] = $limit;

			$data['continue'] = $this->url->link('common/home');

			$data['column_left'] = $this->load->controller('common/column_left');
			$data['column_right'] = $this->load->controller('common/column_right');
			$data['content_top'] = $this->load->controller('common/content_top');
			$data['content_bottom'] = $this->load->controller('common/content_bottom');
			$data['footer'] = $this->load->controller('common/footer');
			$data['header'] = $this->load->controller('common/header');

			$this->response->setOutput($this->load->view('product/category', $data));
		} else {
			$url = '';

			if (isset($this->request->get['path'])) {
				$url .= '&path=' . $this->request->get['path'];
			}

			if (isset($this->request->get['filter'])) {
				$url .= '&filter=' . $this->request->get['filter'];
			}

			if (isset($this->request->get['filter_manufacturer'])) {
				$url .= '&filter_manufacturer=' . $this->request->get['filter_manufacturer'];
			}

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			if (isset($this->request->get['limit'])) {
				$url .= '&limit=' . $this->request->get['limit'];
			}

			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_error'),
				'href' => $this->url->link('product/category', $url)
			);

			$this->document->setTitle($this->language->get('text_error'));

			$data['continue'] = $this->url->link('common/home');

			$this->response->addHeader($this->request->server['SERVER_PROTOCOL'] . ' 404 Not Found');

			$data['column_left'] = $this->load->controller('common/column_left');
			$data['column_right'] = $this->load->controller('common/column_right');
			$data['content_top'] = $this->load->controller('common/content_top');
			$data['content_bottom'] = $this->load->controller('common/content_bottom');
			$data['footer'] = $this->load->controller('common/footer');
			$data['header'] = $this->load->controller('common/header');

			$this->response->setOutput($this->load->view('error/not_found', $data));
		}
	}
}
