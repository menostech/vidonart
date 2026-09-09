<?php
namespace Opencart\Admin\Model\Design;
/**
 * Class Slideshow
 *
 * @package Opencart\Admin\Model\Design
 */
class Slideshow extends \Opencart\System\Engine\Model {
	/**
	 * Add Slideshow
	 *
	 * @param array<string, mixed> $data array of data
	 *
	 * @return int
	 */
	public function addSlideshow(array $data): int {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "slideshow` SET `name` = '" . $this->db->escape((string)$data['name']) . "', `width_mode` = '" . $this->db->escape((string)$data['width_mode']) . "', `height` = '" . (int)$data['height'] . "', `autoplay` = '" . (bool)($data['autoplay'] ?? 0) . "', `arrows` = '" . (bool)($data['arrows'] ?? 0) . "', `dots` = '" . (bool)($data['dots'] ?? 0) . "', `status` = '" . (bool)($data['status'] ?? 0) . "'");

		$slideshow_id = $this->db->getLastId();

		if (isset($data['slide'])) {
			foreach ($data['slide'] as $slide) {
				$this->addSlide($slideshow_id, $slide);
			}
		}

		return $slideshow_id;
	}

	/**
	 * Edit Slideshow
	 *
	 * @param int                  $slideshow_id slideshow id
	 * @param array<string, mixed> $data         array of data
	 *
	 * @return void
	 */
	public function editSlideshow(int $slideshow_id, array $data): void {
		$this->db->query("UPDATE `" . DB_PREFIX . "slideshow` SET `name` = '" . $this->db->escape((string)$data['name']) . "', `width_mode` = '" . $this->db->escape((string)$data['width_mode']) . "', `height` = '" . (int)$data['height'] . "', `autoplay` = '" . (bool)($data['autoplay'] ?? 0) . "', `arrows` = '" . (bool)($data['arrows'] ?? 0) . "', `dots` = '" . (bool)($data['dots'] ?? 0) . "', `status` = '" . (bool)($data['status'] ?? 0) . "' WHERE `slideshow_id` = '" . (int)$slideshow_id . "'");

		$this->db->query("DELETE FROM `" . DB_PREFIX . "slideshow_slide` WHERE `slideshow_id` = '" . (int)$slideshow_id . "'");

		if (isset($data['slide'])) {
			foreach ($data['slide'] as $slide) {
				$this->addSlide($slideshow_id, $slide);
			}
		}
	}

	/**
	 * Delete Slideshow
	 *
	 * @param int $slideshow_id slideshow id
	 *
	 * @return void
	 */
	public function deleteSlideshow(int $slideshow_id): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "slideshow` WHERE `slideshow_id` = '" . (int)$slideshow_id . "'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "slideshow_slide` WHERE `slideshow_id` = '" . (int)$slideshow_id . "'");
	}

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
	 * Get Slideshows
	 *
	 * @param array<string, mixed> $data array of filters
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function getSlideshows(array $data = []): array {
		$sql = "SELECT * FROM `" . DB_PREFIX . "slideshow`";

		$sort_data = ['name', 'status'];

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY `" . $data['sort'] . "`";
		} else {
			$sql .= " ORDER BY `name`";
		}

		if (isset($data['order']) && ($data['order'] == 'DESC')) {
			$sql .= " DESC";
		} else {
			$sql .= " ASC";
		}

		if (isset($data['start']) || isset($data['limit'])) {
			if ($data['start'] < 0) {
				$data['start'] = 0;
			}

			if ($data['limit'] < 1) {
				$data['limit'] = 20;
			}

			$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
		}

		$query = $this->db->query($sql);

		return $query->rows;
	}

	/**
	 * Get Total Slideshows
	 *
	 * @return int
	 */
	public function getTotalSlideshows(): int {
		$query = $this->db->query("SELECT COUNT(*) AS `total` FROM `" . DB_PREFIX . "slideshow`");

		return (int)$query->row['total'];
	}

	/**
	 * Add Slide
	 *
	 * @param int                  $slideshow_id slideshow id
	 * @param array<string, mixed> $data         array of data
	 *
	 * @return void
	 */
	public function addSlide(int $slideshow_id, array $data): void {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "slideshow_slide` SET `slideshow_id` = '" . (int)$slideshow_id . "', `image` = '" . $this->db->escape((string)$data['image']) . "', `overlay_color` = '" . $this->db->escape((string)$data['overlay_color']) . "', `overlay_opacity` = '" . (int)$data['overlay_opacity'] . "', `align` = '" . $this->db->escape((string)$data['align']) . "', `kicker` = '" . $this->db->escape((string)$data['kicker']) . "', `title` = '" . $this->db->escape((string)$data['title']) . "', `sub` = '" . $this->db->escape((string)$data['sub']) . "', `button_text` = '" . $this->db->escape((string)$data['button_text']) . "', `button_link` = '" . $this->db->escape((string)$data['button_link']) . "', `sort_order` = '" . (int)$data['sort_order'] . "', `status` = '" . (bool)($data['status'] ?? 0) . "'");
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
