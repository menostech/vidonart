<?php
namespace Opencart\Admin\Model\Extension\Opencart\Module;
/**
 * Class Slideshow
 *
 * @package Opencart\Admin\Model\Extension\Opencart\Module
 */
class Slideshow extends \Opencart\System\Engine\Model {
	/**
	 * Install
	 *
	 * @return void
	 */
	public function install(): void {
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "slideshow` (
			`slideshow_id` INT(11) NOT NULL AUTO_INCREMENT,
			`name` VARCHAR(128) NOT NULL,
			`width_mode` CHAR(4) NOT NULL DEFAULT 'full',
			`height` INT(5) NOT NULL DEFAULT 600,
			`autoplay` TINYINT(1) NOT NULL DEFAULT 1,
			`arrows` TINYINT(1) NOT NULL DEFAULT 1,
			`dots` TINYINT(1) NOT NULL DEFAULT 1,
			`status` TINYINT(1) NOT NULL DEFAULT 1,
			PRIMARY KEY (`slideshow_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "slideshow_slide` (
			`slide_id` INT(11) NOT NULL AUTO_INCREMENT,
			`slideshow_id` INT(11) NOT NULL,
			`image` VARCHAR(255) NOT NULL,
			`overlay_color` VARCHAR(7) NOT NULL DEFAULT '#000000',
			`overlay_opacity` INT(3) NOT NULL DEFAULT 30,
			`align` VARCHAR(6) NOT NULL DEFAULT 'left',
			`kicker` VARCHAR(255) NOT NULL,
			`title` VARCHAR(255) NOT NULL,
			`sub` VARCHAR(1024) NOT NULL,
			`button_text` VARCHAR(128) NOT NULL,
			`button_link` VARCHAR(512) NOT NULL,
			`sort_order` INT(3) NOT NULL DEFAULT 0,
			`status` TINYINT(1) NOT NULL DEFAULT 1,
			PRIMARY KEY (`slide_id`),
			KEY `slideshow_id` (`slideshow_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
	}

	/**
	 * Uninstall
	 *
	 * @return void
	 */
	public function uninstall(): void {
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "slideshow_slide`");
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "slideshow`");
	}
}
