<?php
namespace Opencart\Catalog\Controller\Extension\Opencart\Module;
/**
 * Class Slideshow
 *
 * @package Opencart\Catalog\Controller\Extension\Opencart\Module
 */
class Slideshow extends \Opencart\System\Engine\Controller {
	/**
	 * Index
	 *
	 * @param array<string, mixed> $setting array of settings
	 *
	 * @return string
	 */
	public function index(array $setting): string {
		static $module = 0;

		// Slideshow
		$this->load->model('design/slideshow');

		$slideshow_info = $this->model_design_slideshow->getSlideshow((int)$setting['slideshow_id']);

		if (!$slideshow_info || !$slideshow_info['status']) {
			return '';
		}

		$data['slides'] = [];

		$slides = $this->model_design_slideshow->getSlides((int)$setting['slideshow_id']);

		foreach ($slides as $slide) {
			if (!$slide['status']) {
				continue;
			}

			if ($slide['image'] && is_file(DIR_IMAGE . html_entity_decode($slide['image'], ENT_QUOTES, 'UTF-8'))) {
				$image = 'image/' . html_entity_decode($slide['image'], ENT_QUOTES, 'UTF-8');
			} else {
				continue;
			}

			// Hex color to rgba
			$hex = ltrim($slide['overlay_color'], '#');

			if (strlen($hex) == 6) {
				$r = hexdec(substr($hex, 0, 2));
				$g = hexdec(substr($hex, 2, 2));
				$b = hexdec(substr($hex, 4, 2));
			} else {
				$r = 0;
				$g = 0;
				$b = 0;
			}

			$alpha = max(0, min(100, (int)$slide['overlay_opacity'])) / 100;

			$data['slides'][] = [
				'image'           => $image,
				'overlay'         => sprintf('rgba(%d, %d, %d, %.2f)', $r, $g, $b, $alpha),
				'align'           => $slide['align'],
				'kicker'          => $slide['kicker'],
				'title'           => $slide['title'],
				'sub'             => $slide['sub'],
				'button_text'     => $slide['button_text'],
				'button_link'     => $slide['button_link']
			];
		}

		if (!$data['slides']) {
			return '';
		}

		$data['module'] = $module++;
		$data['width_mode'] = $slideshow_info['width_mode'];
		$data['height'] = (int)$slideshow_info['height'];
		$data['autoplay'] = (bool)$slideshow_info['autoplay'];
		$data['arrows'] = (bool)$slideshow_info['arrows'];
		$data['dots'] = (bool)$slideshow_info['dots'];

		return $this->load->view('extension/opencart/module/slideshow', $data);
	}
}
