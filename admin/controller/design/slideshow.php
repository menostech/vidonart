<?php
namespace Opencart\Admin\Controller\Design;
/**
 * Class Slideshow
 *
 * @package Opencart\Admin\Controller\Design
 */
class Slideshow extends \Opencart\System\Engine\Controller {
	/**
	 * Index
	 *
	 * @return void
	 */
	public function index(): void {
		$this->load->language('design/slideshow');

		$this->document->setTitle($this->language->get('heading_title'));

		$url = '';

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('design/slideshow', 'user_token=' . $this->session->data['user_token'] . $url)
		];

		$data['add'] = $this->url->link('design/slideshow.form', 'user_token=' . $this->session->data['user_token'] . $url);
		$data['delete'] = $this->url->link('design/slideshow.delete', 'user_token=' . $this->session->data['user_token']);

		$data['list'] = $this->getList();

		$data['user_token'] = $this->session->data['user_token'];

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('design/slideshow', $data));
	}

	/**
	 * List
	 *
	 * @return void
	 */
	public function list(): void {
		$this->load->language('design/slideshow');

		$this->response->setOutput($this->getList());
	}

	/**
	 * Get List
	 *
	 * @return string
	 */
	public function getList(): string {
		if (isset($this->request->get['sort'])) {
			$sort = (string)$this->request->get['sort'];
		} else {
			$sort = 'name';
		}

		if (isset($this->request->get['order'])) {
			$order = (string)$this->request->get['order'];
		} else {
			$order = 'ASC';
		}

		if (isset($this->request->get['page'])) {
			$page = (int)$this->request->get['page'];
		} else {
			$page = 1;
		}

		$url = '';

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['action'] = $this->url->link('design/slideshow.list', 'user_token=' . $this->session->data['user_token'] . $url);

		$data['slideshows'] = [];

		$filter_data = [
			'sort'  => $sort,
			'order' => $order,
			'start' => ($page - 1) * $this->config->get('config_pagination_admin'),
			'limit' => $this->config->get('config_pagination_admin')
		];

		$this->load->model('design/slideshow');

		$results = [];

		$query = $this->db->query("SHOW TABLES LIKE '" . $this->db->escape(DB_PREFIX . 'slideshow') . "'");

		if ($query->num_rows) {
			$results = $this->model_design_slideshow->getSlideshows($filter_data);
		}

		foreach ($results as $result) {
			$data['slideshows'][] = ['edit' => $this->url->link('design/slideshow.form', 'user_token=' . $this->session->data['user_token'] . '&slideshow_id=' . $result['slideshow_id'] . $url)] + $result;
		}

		$url = '';

		if ($order == 'ASC') {
			$url .= '&order=DESC';
		} else {
			$url .= '&order=ASC';
		}

		$data['sort_name'] = $this->url->link('design/slideshow.list', 'user_token=' . $this->session->data['user_token'] . '&sort=name' . $url);
		$data['sort_status'] = $this->url->link('design/slideshow.list', 'user_token=' . $this->session->data['user_token'] . '&sort=status' . $url);

		$url = '';

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		$slideshow_total = $this->model_design_slideshow->getTotalSlideshows();

		$data['pagination'] = $this->load->controller('common/pagination', [
			'total' => $slideshow_total,
			'page'  => $page,
			'limit' => $this->config->get('config_pagination_admin'),
			'url'   => $this->url->link('design/slideshow.list', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}')
		]);

		$data['results'] = sprintf($this->language->get('text_pagination'), ($slideshow_total) ? (($page - 1) * $this->config->get('config_pagination_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_pagination_admin')) > ($slideshow_total - $this->config->get('config_pagination_admin'))) ? $slideshow_total : ((($page - 1) * $this->config->get('config_pagination_admin')) + $this->config->get('config_pagination_admin')), $slideshow_total, ceil($slideshow_total / $this->config->get('config_pagination_admin')));

		$data['sort'] = $sort;
		$data['order'] = $order;

		return $this->load->view('design/slideshow_list', $data);
	}

	/**
	 * Form
	 *
	 * @return void
	 */
	public function form(): void {
		$this->load->language('design/slideshow');

		$this->document->setTitle($this->language->get('heading_title'));

		$data['text_form'] = !isset($this->request->get['slideshow_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');

		$url = '';

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('design/slideshow', 'user_token=' . $this->session->data['user_token'] . $url)
		];

		$data['save'] = $this->url->link('design/slideshow.save', 'user_token=' . $this->session->data['user_token']);
		$data['back'] = $this->url->link('design/slideshow', 'user_token=' . $this->session->data['user_token'] . $url);

		if (isset($this->request->get['slideshow_id'])) {
			$this->load->model('design/slideshow');

			$slideshow_info = $this->model_design_slideshow->getSlideshow($this->request->get['slideshow_id']);
		}

		if (!empty($slideshow_info)) {
			$data['slideshow_id'] = $slideshow_info['slideshow_id'];
		} else {
			$data['slideshow_id'] = 0;
		}

		if (!empty($slideshow_info)) {
			$data['name'] = $slideshow_info['name'];
		} else {
			$data['name'] = '';
		}

		if (!empty($slideshow_info)) {
			$data['width_mode'] = $slideshow_info['width_mode'];
		} else {
			$data['width_mode'] = 'full';
		}

		if (!empty($slideshow_info)) {
			$data['height'] = $slideshow_info['height'];
		} else {
			$data['height'] = 600;
		}

		if (!empty($slideshow_info)) {
			$data['autoplay'] = $slideshow_info['autoplay'];
		} else {
			$data['autoplay'] = true;
		}

		if (!empty($slideshow_info)) {
			$data['arrows'] = $slideshow_info['arrows'];
		} else {
			$data['arrows'] = true;
		}

		if (!empty($slideshow_info)) {
			$data['dots'] = $slideshow_info['dots'];
		} else {
			$data['dots'] = true;
		}

		if (!empty($slideshow_info)) {
			$data['status'] = $slideshow_info['status'];
		} else {
			$data['status'] = true;
		}

		// Image
		$this->load->model('tool/image');

		if (!empty($slideshow_info)) {
			$slides = $this->model_design_slideshow->getSlides($this->request->get['slideshow_id']);
		} else {
			$slides = [];
		}

		$data['slides'] = [];

		foreach ($slides as $slide) {
			if ($slide['image'] && is_file(DIR_IMAGE . html_entity_decode($slide['image'], ENT_QUOTES, 'UTF-8'))) {
				$image = $slide['image'];
				$thumb = $slide['image'];
			} else {
				$image = '';
				$thumb = 'no_image.png';
			}

			$data['slides'][] = [
				'image'           => $image,
				'thumb'           => $this->model_tool_image->resize($thumb, $this->config->get('config_image_default_width'), $this->config->get('config_image_default_height')),
				'overlay_color'   => $slide['overlay_color'],
				'overlay_opacity' => $slide['overlay_opacity'],
				'align'           => $slide['align'],
				'kicker'          => $slide['kicker'],
				'title'           => $slide['title'],
				'sub'             => $slide['sub'],
				'button_text'     => $slide['button_text'],
				'button_link'     => $slide['button_link'],
				'sort_order'      => $slide['sort_order'],
				'status'          => $slide['status']
			];
		}

		$data['placeholder'] = $this->model_tool_image->resize('no_image.png', $this->config->get('config_image_default_width'), $this->config->get('config_image_default_height'));

		$data['user_token'] = $this->session->data['user_token'];

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('design/slideshow_form', $data));
	}

	/**
	 * Save
	 *
	 * @return void
	 */
	public function save(): void {
		$this->load->language('design/slideshow');

		$json = [];

		if (!$this->user->hasPermission('modify', 'design/slideshow')) {
			$json['error']['warning'] = $this->language->get('error_permission');
		}

		$required = [
			'slideshow_id' => 0,
			'slide'        => [],
			'name'         => '',
			'width_mode'   => 'full',
			'height'       => 600,
			'autoplay'     => 0,
			'arrows'       => 0,
			'dots'         => 0,
			'status'       => 0
		];

		$post_info = $this->request->post + $required;

		if (!oc_validate_length($post_info['name'], 3, 64)) {
			$json['error']['name'] = $this->language->get('error_name');
		}

		if (!in_array($post_info['width_mode'], ['full', 'box'])) {
			$json['error']['width_mode'] = $this->language->get('error_width_mode');
		}

		if ($post_info['height'] < 50 || $post_info['height'] > 2000) {
			$json['error']['height'] = $this->language->get('error_height');
		}

		if (isset($post_info['slide'])) {
			foreach ($post_info['slide'] as $key => $value) {
				if (!preg_match('/^#[0-9a-fA-F]{6}$/', (string)$value['overlay_color'])) {
					$json['error']['slide_' . $key . '_overlay_color'] = $this->language->get('error_overlay_color');
				}

				if ($value['overlay_opacity'] < 0 || $value['overlay_opacity'] > 100) {
					$json['error']['slide_' . $key . '_overlay_opacity'] = $this->language->get('error_overlay_opacity');
				}

				if (!in_array($value['align'], ['left', 'center', 'right'])) {
					$json['error']['slide_' . $key . '_align'] = $this->language->get('error_align');
				}
			}
		}

		if (!$json) {
			$this->load->model('design/slideshow');

			if (!$post_info['slideshow_id']) {
				$json['slideshow_id'] = $this->model_design_slideshow->addSlideshow($post_info);
			} else {
				$this->model_design_slideshow->editSlideshow($post_info['slideshow_id'], $post_info);
			}

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Delete
	 *
	 * @return void
	 */
	public function delete(): void {
		$this->load->language('design/slideshow');

		$json = [];

		if (isset($this->request->post['selected'])) {
			$selected = (array)$this->request->post['selected'];
		} else {
			$selected = [];
		}

		if (!$this->user->hasPermission('modify', 'design/slideshow')) {
			$json['error'] = $this->language->get('error_permission');
		}

		if (!$json) {
			$this->load->model('design/slideshow');

			foreach ($selected as $slideshow_id) {
				$this->model_design_slideshow->deleteSlideshow($slideshow_id);
			}

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
