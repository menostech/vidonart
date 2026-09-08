<?php
namespace Opencart\Catalog\Controller\Common;
/**
 * Class Newsletter
 *
 * @package Opencart\Catalog\Controller\Common
 */
class Newsletter extends \Opencart\System\Engine\Controller {
	/**
	 * Save
	 *
	 * @return void
	 */
	public function save(): void {
		$this->load->language('common/newsletter');

		$json = [];

		$email = isset($this->request->post['email']) ? $this->request->post['email'] : '';

		if (!oc_validate_email($email)) {
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
				$mail->setReplyTo($email);
				$mail->setSender(html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8'));
				$mail->setSubject($this->language->get('email_subject'));
				$mail->setText(sprintf($this->language->get('email_body'), $email));
				$mail->send();
			}

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
