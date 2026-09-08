<?php
namespace Opencart\Catalog\Controller\Information;
/**
 * Class Custom
 *
 * @package Opencart\Catalog\Controller\Information
 */
class Custom extends \Opencart\System\Engine\Controller {
	/**
	 * Index
	 *
	 * @return void
	 */
	public function index(): void {
		$this->load->language('information/custom');

		$this->document->setTitle($this->language->get('heading_title'));

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home', 'language=' . $this->config->get('config_language'))
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('information/custom', 'language=' . $this->config->get('config_language'))
		];

		$data['send'] = $this->url->link('information/custom.send', 'language=' . $this->config->get('config_language'));

		// Prefill from product page
		$product_reference = '';

		if (isset($this->request->get['product_id'])) {
			$this->load->model('catalog/product');

			$product_info = $this->model_catalog_product->getProduct((int)$this->request->get['product_id']);

			if ($product_info) {
				$product_reference = $product_info['name'];
			}
		}

		$data['product_reference'] = $product_reference;
		$data['email'] = $this->customer->getEmail();

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('information/custom', $data));
	}

	/**
	 * Send
	 *
	 * @throws \Exception
	 *
	 * @return void
	 */
	public function send(): void {
		$this->load->language('information/custom');

		$json = [];

		$required = [
			'category'          => '',
			'product_reference' => '',
			'message'           => '',
			'budget'            => '',
			'email'             => ''
		];

		$post_info = $this->request->post + $required;

		$categories = ['jewelry', 'handbags', 'home-living'];

		if (!in_array($post_info['category'], $categories)) {
			$json['error']['category'] = $this->language->get('error_category');
		}

		if (!oc_validate_length($post_info['product_reference'], 0, 128)) {
			$json['error']['product_reference'] = $this->language->get('error_product_reference');
		}

		if (!oc_validate_length($post_info['message'], 10, 3000)) {
			$json['error']['message'] = $this->language->get('error_message');
		}

		$budgets = ['', 'under-200', '200-500', '500-1000', '1000-plus'];

		if (!in_array($post_info['budget'], $budgets)) {
			$json['error']['budget'] = $this->language->get('error_budget');
		}

		if (!oc_validate_email($post_info['email'])) {
			$json['error']['email'] = $this->language->get('error_email');
		}

		if (!$json) {
			if ($this->config->get('config_mail_engine')) {
				$mail_option = [
					'parameter'     => $this->config->get('config_mail_parameter'),
					'smtp_hostname' => $this->config->get('config_mail_smtp_hostname'),
					'smtp_username' => $this->config->get('config_mail_smtp_username'),
					'smtp_password' => html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8'),
					'smtp_port'     => $this->config->get('config_mail_smtp_port'),
					'smtp_timeout'  => $this->config->get('config_mail_smtp_timeout')
				];

				$mail = new \Opencart\System\Library\Mail($this->config->get('config_mail_engine'), $mail_option);
				$mail->setTo($this->config->get('config_email'));
				$mail->setFrom($this->config->get('config_email'));
				$mail->setReplyTo($post_info['email']);
				$mail->setSender(html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8'));
				$mail->setSubject(sprintf($this->language->get('email_subject'), $post_info['category']));
				$mail->setText(sprintf($this->language->get('email_body'), $post_info['category'], $post_info['product_reference'], $post_info['budget'], $post_info['message'], $post_info['email']));
				$mail->send();
			}

			$json['redirect'] = $this->url->link('information/custom.success', 'language=' . $this->config->get('config_language'), true);
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Success
	 *
	 * @return void
	 */
	public function success(): void {
		$this->load->language('information/custom');

		$this->document->setTitle($this->language->get('heading_title'));

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home', 'language=' . $this->config->get('config_language'))
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('information/custom', 'language=' . $this->config->get('config_language'))
		];

		$data['text_message'] = $this->language->get('text_message');

		$data['continue'] = $this->url->link('common/home', 'language=' . $this->config->get('config_language'));

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('common/success', $data));
	}
}
