<?php
namespace Opencart\Catalog\Model\Design;
/**
 * Class Slideshow
 *
 * @package Opencart\Catalog\Model\Design
 */
class Slideshow extends \Opencart\System\Engine\Model {
	/**
	 * Get Slideshow
	 *
	 * @param int $slideshow_id slideshow id
	 *
	 * @return array<string, mixed>
	 */
	public function getSlideshow(int $slideshow_id): array {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "slideshow` WHERE `slideshow_id` = '" . (int)$slideshow_id . "'");

		return $query->row;
	}

	/**
	 * Get Slides
	 *
	 * @param int $slideshow_id slideshow id
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function getSlides(int $slideshow_id): array {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "slideshow_slide` WHERE `slideshow_id` = '" . (int)$slideshow_id . "' ORDER BY `sort_order` ASC");

		return $query->rows;
	}
}