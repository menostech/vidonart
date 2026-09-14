<?php
namespace Opencart\Catalog\Controller\Product;
/**
 * Class Thumb
 *
 * Can be loaded using $this->load->controller('product/thumb', $product_data);
 *
 * @example
 *
 * $product_data = [
 *     'description' => '',
 *     'thumb'       => '',
 *     'price'       => 1.00,
 *     'special'     => 0.00,
 *     'tax'         => 0.00,
 *     'minimum'     => 1,
 *     'href'        => ''
 * ];
 *
 * @package Opencart\Catalog\Controller\Product
 */
class Thumb extends \Opencart\System\Engine\Controller {
	/**
	 * Index
	 *
	 * @param array<string, mixed> $data array of data
	 *
	 * @return string
	 */
	public function index(array $data): string {
		$this->load->language('product/thumb');

		$data['cart'] = $this->url->link('common/cart.info', 'language=' . $this->config->get('config_language'));

		$data['cart_add'] = $this->url->link('checkout/cart.add', 'language=' . $this->config->get('config_language'));
		$data['wishlist_add'] = $this->url->link('account/wishlist.add', 'language=' . $this->config->get('config_language'));
		$data['compare_add'] = $this->url->link('product/compare.add', 'language=' . $this->config->get('config_language'));

		$data['review_status'] = (int)$this->config->get('config_review_status');

		// Swatches (first option that has colored values)
		$data['swatches'] = [];
		$data['swatch_option_id'] = 0;

		if (!empty($data['product_id'])) {
			$this->load->model('catalog/product');

			$this->load->model('tool/image');

			$options = $this->model_catalog_product->getOptions((int)$data['product_id']);

			foreach ($options as $option) {
				if (!in_array($option['type'], ['select', 'radio'])) {
					continue;
				}

				$colored = [];

				foreach ($option['product_option_value'] as $value) {
					if (!empty($value['color'])) {
						$image = '';

						if ($value['image'] && is_file(DIR_IMAGE . html_entity_decode($value['image'], ENT_QUOTES, 'UTF-8'))) {
							$image = $this->model_tool_image->resize(html_entity_decode($value['image'], ENT_QUOTES, 'UTF-8'), 500, 500);
						}

						$colored[] = [
							'product_option_value_id' => $value['product_option_value_id'],
							'color'                   => $value['color'],
							'image'                   => $image,
							'name'                    => $value['name']
						];
					}
				}

				if ($colored) {
					$data['swatches'] = $colored;
					$data['swatch_option_id'] = (int)$option['product_option_id'];
					break;
				}
			}
		}

		return $this->load->view('product/thumb', $data);
	}
}
