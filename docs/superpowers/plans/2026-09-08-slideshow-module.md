# Slideshow Module Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build an admin-managed slideshow module (full/box width, height, per-slide background image + overlay color/opacity, per-slide content kicker/title/sub/button with alignment, autoplay/arrows/dots toggles) placed via the standard layout system, replacing the hardcoded homepage hero.

**Architecture:** Two-layer module mirroring the stock banner module: core admin `design/slideshow` controller + model (slideshow & slide CRUD in `oc_slideshow` / `oc_slideshow_slide` tables created by the module's `install()`), an extension module admin controller (instance settings: name/slideshow/status) that also installs/uninstalls the tables, and a catalog controller rendering a Bootstrap Carousel with fade. Homepage hero markup is removed; the slideshow is placed at home layout `content_top` via admin.

**Tech Stack:** PHP 8 / OpenCart 4.1.0.4 (admin + catalog MVC), Twig, Bootstrap 5.3 Carousel (bundled), SCSS via `catalog/view/stylesheet/stylesheet.scss`.

**Reference patterns to mirror (read before writing):** `admin/controller/design/banner.php`, `admin/model/design/banner.php`, `admin/view/template/design/banner_form.twig`, `extension/opencart/admin/controller/module/banner.php`, `extension/opencart/admin/controller/module/bestseller.php` (install/uninstall), `extension/opencart/catalog/controller/module/banner.php`, `extension/opencart/catalog/view/template/module/banner.twig`, `admin/controller/common/column_left.php` (Design group).

---

## File Structure

| File | Responsibility | Status |
|---|---|---|
| `extension/opencart/admin/model/module/slideshow.php` | install()/uninstall(): create/drop tables | Create (Task 1) |
| `extension/opencart/admin/controller/module/slideshow.php` | module instance index/save/install/uninstall | Create (Task 1) |
| `extension/opencart/admin/view/template/module/slideshow.twig` | module instance form | Create (Task 1) |
| `extension/opencart/admin/language/en-gb/module/slideshow.php` | module language | Create (Task 1) |
| `admin/model/design/slideshow.php` | slideshow + slide CRUD | Create (Task 2) |
| `admin/controller/design/slideshow.php` | design list/form/save/delete | Create (Task 2) |
| `admin/language/en-gb/design/slideshow.php` | design language | Create (Task 2) |
| `admin/view/template/design/slideshow.twig` | list view | Create (Task 3) |
| `admin/view/template/design/slideshow_form.twig` | slideshow + slide repeater | Create (Task 3) |
| `admin/controller/common/column_left.php` + `admin/language/en-gb/common/column_left.php` | Design menu entry | Modify (Task 3) |
| `extension/opencart/catalog/controller/module/slideshow.php` | catalog render | Create (Task 4) |
| `extension/opencart/catalog/view/template/module/slideshow.twig` | carousel markup | Create (Task 4) |
| `catalog/view/stylesheet/stylesheet.scss` + `.css` | slide/overlay/content/carousel styles | Modify (Task 4) |
| `catalog/view/template/common/home.twig` | remove `.va-hero` section | Modify (Task 5) |

---

## Task 1: Module Skeleton 鈥?Tables, Install, Module Admin

**Files:**
- Create: `extension/opencart/admin/model/module/slideshow.php`
- Create: `extension/opencart/admin/controller/module/slideshow.php`
- Create: `extension/opencart/admin/view/template/module/slideshow.twig`
- Create: `extension/opencart/admin/language/en-gb/module/slideshow.php`

- [ ] **Step 1: Create the install model** 鈥?`extension/opencart/admin/model/module/slideshow.php`

```php
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
```

- [ ] **Step 2: Create the module admin controller** 鈥?`extension/opencart/admin/controller/module/slideshow.php`

```php
<?php
namespace Opencart\Admin\Controller\Extension\Opencart\Module;
/**
 * Class Slideshow
 *
 * @package Opencart\Admin\Controller\Extension\Opencart\Module
 */
class Slideshow extends \Opencart\System\Engine\Controller {
	/**
	 * Index
	 *
	 * @return void
	 */
	public function index(): void {
		$this->load->language('extension/opencart/module/slideshow');

		$this->document->setTitle($this->language->get('heading_title'));

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module')
		];

		if (!isset($this->request->get['module_id'])) {
			$data['breadcrumbs'][] = [
				'text' => $this->language->get('heading_title'),
				'href' => $this->url->link('extension/opencart/module/slideshow', 'user_token=' . $this->session->data['user_token'])
			];
		} else {
			$data['breadcrumbs'][] = [
				'text' => $this->language->get('heading_title'),
				'href' => $this->url->link('extension/opencart/module/slideshow', 'user_token=' . $this->session->data['user_token'] . '&module_id=' . $this->request->get['module_id'])
			];
		}

		if (!isset($this->request->get['module_id'])) {
			$data['save'] = $this->url->link('extension/opencart/module/slideshow.save', 'user_token=' . $this->session->data['user_token']);
		} else {
			$data['save'] = $this->url->link('extension/opencart/module/slideshow.save', 'user_token=' . $this->session->data['user_token'] . '&module_id=' . $this->request->get['module_id']);
		}

		$data['back'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module');

		if (isset($this->request->get['module_id'])) {
			$this->load->model('setting/module');

			$module_info = $this->model_setting_module->getModule($this->request->get['module_id']);
		}

		if (isset($module_info['name'])) {
			$data['name'] = $module_info['name'];
		} else {
			$data['name'] = '';
		}

		// Slideshows
		$this->load->model('design/slideshow');

		$data['slideshows'] = $this->model_design_slideshow->getSlideshows();

		if (isset($module_info['slideshow_id'])) {
			$data['slideshow_id'] = $module_info['slideshow_id'];
		} else {
			$data['slideshow_id'] = '';
		}

		if (isset($module_info['status'])) {
			$data['status'] = $module_info['status'];
		} else {
			$data['status'] = true;
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/opencart/module/slideshow', $data));
	}

	/**
	 * Save
	 *
	 * @return void
	 */
	public function save(): void {
		$this->load->language('extension/opencart/module/slideshow');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/opencart/module/slideshow')) {
			$json['error']['warning'] = $this->language->get('error_permission');
		}

		$required = [
			'name'         => '',
			'slideshow_id' => 0,
			'status'       => 0
		];

		$post_info = $this->request->post + $required;

		if (!oc_validate_length($post_info['name'], 3, 64)) {
			$json['error']['name'] = $this->language->get('error_name');
		}

		if (!$json) {
			$this->load->model('setting/module');

			if (!isset($this->request->get['module_id'])) {
				$json['module_id'] = $this->model_setting_module->addModule('opencart.slideshow', $post_info);
			} else {
				$this->model_setting_module->editModule($this->request->get['module_id'], $post_info);
			}

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Install
	 *
	 * @return void
	 */
	public function install(): void {
		if ($this->user->hasPermission('modify', 'extension/opencart/module/slideshow')) {
			$this->load->model('extension/opencart/module/slideshow');

			$this->model_extension_opencart_module_slideshow->install();
		}
	}

	/**
	 * Uninstall
	 *
	 * @return void
	 */
	public function uninstall(): void {
		if ($this->user->hasPermission('modify', 'extension/opencart/module/slideshow')) {
			$this->load->model('extension/opencart/module/slideshow');

			$this->model_extension_opencart_module_slideshow->uninstall();
		}
	}
}
```

- [ ] **Step 3: Create the module admin view** 鈥?`extension/opencart/admin/view/template/module/slideshow.twig`

```twig
{{ header }}{{ column_left }}
<div id="content">
  <div class="page-header">
    <div class="container-fluid">
      <div class="float-end">
        <button type="submit" form="form-module" data-bs-toggle="tooltip" title="{{ button_save }}" class="btn btn-primary"><i class="fa-solid fa-save"></i></button>
        <a href="{{ back }}" data-bs-toggle="tooltip" title="{{ button_back }}" class="btn btn-light"><i class="fa-solid fa-reply"></i></a></div>
      <h1>{{ heading_title }}</h1>
      <ol class="breadcrumb">
        {% for breadcrumb in breadcrumbs %}
          <li class="breadcrumb-item"><a href="{{ breadcrumb.href }}">{{ breadcrumb.text }}</a></li>
        {% endfor %}
      </ol>
    </div>
  </div>
  <div class="container-fluid">
    <div class="card">
      <div class="card-header"><i class="fa-solid fa-pencil"></i> {{ text_edit }}</div>
      <div class="card-body">
        <form id="form-module" action="{{ save }}" method="post" data-oc-toggle="ajax">
          <div class="row mb-3 required">
            <label for="input-name" class="col-sm-2 col-form-label">{{ entry_name }}</label>
            <div class="col-sm-10">
              <input type="text" name="name" value="{{ name }}" placeholder="{{ entry_name }}" id="input-name" class="form-control"/>
              <div id="error-name" class="invalid-feedback"></div>
            </div>
          </div>
          <div class="row mb-3">
            <label for="input-slideshow" class="col-sm-2 col-form-label">{{ entry_slideshow }}</label>
            <div class="col-sm-10">
              <select name="slideshow_id" id="input-slideshow" class="form-select">
                <option value="0">{{ text_select }}</option>
                {% for slideshow in slideshows %}
                  <option value="{{ slideshow.slideshow_id }}"{% if slideshow.slideshow_id == slideshow_id %} selected{% endif %}>{{ slideshow.name }}</option>
                {% endfor %}
              </select>
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-2 col-form-label">{{ entry_status }}</label>
            <div class="col-sm-10">
              <div class="form-check form-switch form-switch-lg">
                <input type="hidden" name="status" value="0"/>
                <input type="checkbox" name="status" value="1" id="input-status" class="form-check-input"{% if status %} checked{% endif %}/>
              </div>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
{{ footer }}
```

- [ ] **Step 4: Create the module language file** 鈥?`extension/opencart/admin/language/en-gb/module/slideshow.php`

```php
<?php
// Heading
$_['heading_title']    = 'Slideshow';

// Text
$_['text_edit']        = 'Edit Slideshow Module';
$_['text_success']     = 'Success: You have modified slideshow module!';
$_['text_select']      = '--- Please Select ---';

// Entry
$_['entry_name']       = 'Module Name';
$_['entry_slideshow']  = 'Slideshow';
$_['entry_status']     = 'Status';

// Error
$_['error_permission'] = 'Warning: You do not have permission to modify slideshow module!';
$_['error_name']       = 'Module Name must be between 3 and 64 characters!';
```

- [ ] **Step 5: Verify & commit**

```powershell
git diff --check
git add extension/opencart/admin/model/module/slideshow.php extension/opencart/admin/controller/module/slideshow.php extension/opencart/admin/view/template/module/slideshow.twig extension/opencart/admin/language/en-gb/module/slideshow.php
git commit -m "feat: slideshow module skeleton with install tables"
```

Note: `admin/model/design/slideshow.php` is referenced by the module controller (Step 2) but created in Task 2 鈥?the files land in the same branch; the module controller only runs after Task 2 exists.

---

## Task 2: Design CRUD 鈥?Model, Controller, Language

**Files:**
- Create: `admin/model/design/slideshow.php`
- Create: `admin/controller/design/slideshow.php`
- Create: `admin/language/en-gb/design/slideshow.php`

- [ ] **Step 1: Create the design model** 鈥?`admin/model/design/slideshow.php`

```php
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
```

- [ ] **Step 2: Create the design controller** 鈥?`admin/controller/design/slideshow.php`

```php
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

		$results = $this->model_design_slideshow->getSlideshows($filter_data);

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
```

- [ ] **Step 3: Create the design language file** 鈥?`admin/language/en-gb/design/slideshow.php`

```php
<?php
// Heading
$_['heading_title'] = 'Slideshows';

// Text
$_['text_add']           = 'Add Slideshow';
$_['text_edit']          = 'Edit Slideshow';
$_['text_success']       = 'Success: You have modified slideshows!';
$_['text_list']          = 'Slideshow List';
$_['text_full']          = 'Full Width';
$_['text_box']           = 'Boxed';
$_['text_left']          = 'Left';
$_['text_center']        = 'Center';
$_['text_right']         = 'Right';
$_['text_enabled']       = 'Enabled';
$_['text_disabled']      = 'Disabled';
$_['text_confirm']       = 'Are you sure?';
$_['text_pagination']    = 'Showing %d to %d of %d (%d Pages)';

// Column
$_['column_name']        = 'Slideshow Name';
$_['column_status']      = 'Status';
$_['column_action']      = 'Action';

// Entry
$_['entry_name']         = 'Slideshow Name';
$_['entry_width_mode']   = 'Width';
$_['entry_height']       = 'Height (px)';
$_['entry_autoplay']     = 'Autoplay';
$_['entry_arrows']       = 'Arrows';
$_['entry_dots']         = 'Dots';
$_['entry_status']       = 'Status';
$_['entry_image']        = 'Background Image';
$_['entry_overlay_color'] = 'Overlay Color';
$_['entry_overlay_opacity'] = 'Overlay Opacity (%)';
$_['entry_align']        = 'Content Alignment';
$_['entry_kicker']       = 'Kicker';
$_['entry_title']        = 'Title';
$_['entry_sub']          = 'Subtitle';
$_['entry_button_text']  = 'Button Text';
$_['entry_button_link']  = 'Button Link';
$_['entry_sort_order']   = 'Sort Order';
$_['entry_slide']        = 'Slides';

// Button
$_['button_slide_add']   = 'Add Slide';
$_['button_remove']      = 'Remove';

// Error
$_['error_permission']   = 'Warning: You do not have permission to modify slideshows!';
$_['error_name']         = 'Slideshow Name must be between 3 and 64 characters!';
$_['error_width_mode']   = 'Invalid width mode!';
$_['error_height']       = 'Height must be between 50 and 2000!';
$_['error_overlay_color'] = 'Overlay color must be a hex color like #000000!';
$_['error_overlay_opacity'] = 'Overlay opacity must be between 0 and 100!';
$_['error_align']        = 'Invalid alignment!';
```

- [ ] **Step 4: Verify & commit**

```powershell
git diff --check
git add admin/model/design/slideshow.php admin/controller/design/slideshow.php admin/language/en-gb/design/slideshow.php
git commit -m "feat: slideshow design CRUD (model, controller, language)"
```

---

## Task 3: Admin Views + Menu Entry

**Files:**
- Create: `admin/view/template/design/slideshow_list.twig`
- Create: `admin/view/template/design/slideshow_form.twig`
- Modify: `admin/controller/common/column_left.php`
- Modify: `admin/language/en-gb/common/column_left.php`

- [ ] **Step 1: Create the list view** 鈥?`admin/view/template/design/slideshow_list.twig`

```twig
<form id="form-slideshow" method="post" data-oc-toggle="ajax" data-oc-load="{{ action }}" data-oc-target="#slideshow">
  <div class="table-responsive">
    <table class="table table-bordered table-hover">
      <thead>
        <tr>
          <td style="width: 1px;" class="text-center"><input type="checkbox" onclick="$('input[name*=\'selected\']').prop('checked', this.checked);" class="form-check-input"/></td>
          <th class="text-start"><a href="{{ sort_name }}">{{ column_name }}</a></th>
          <th class="text-start"><a href="{{ sort_status }}">{{ column_status }}</a></th>
          <th class="text-end">{{ column_action }}</th>
        </tr>
      </thead>
      <tbody>
        {% if slideshows %}
          {% for slideshow in slideshows %}
            <tr>
              <td class="text-center"><input type="checkbox" name="selected[]" value="{{ slideshow.slideshow_id }}" class="form-check-input"/></td>
              <td class="text-start">{{ slideshow.name }}</td>
              <td class="text-start">{{ slideshow.status ? text_enabled : text_disabled }}</td>
              <td class="text-end"><a href="{{ slideshow.edit }}" data-bs-toggle="tooltip" title="{{ button_edit }}" class="btn btn-primary"><i class="fa-solid fa-pencil"></i></a></td>
            </tr>
          {% endfor %}
        {% else %}
          <tr>
            <td class="text-center" colspan="4">{{ text_no_results }}</td>
          </tr>
        {% endif %}
      </tbody>
    </table>
  </div>
  <div class="row">
    <div class="col-sm-6 text-start">{{ pagination }}</div>
    <div class="col-sm-6 text-end">{{ results }}</div>
  </div>
</form>
```

- [ ] **Step 2: Create the main list wrapper view** 鈥?`admin/view/template/design/slideshow.twig`

```twig
{{ header }}{{ column_left }}
<div id="content">
  <div class="page-header">
    <div class="container-fluid">
      <div class="float-end">
        <button type="submit" form="form-slideshow" formaction="{{ delete }}" data-bs-toggle="tooltip" title="{{ button_delete }}" onclick="return confirm('{{ text_confirm|escape('js') }}');" class="btn btn-danger"><i class="fa-regular fa-trash-can"></i></button>
        <a href="{{ add }}" data-bs-toggle="tooltip" title="{{ button_add }}" class="btn btn-primary"><i class="fa-solid fa-plus"></i></a></div>
      <h1>{{ heading_title }}</h1>
      <ol class="breadcrumb">
        {% for breadcrumb in breadcrumbs %}
          <li class="breadcrumb-item"><a href="{{ breadcrumb.href }}">{{ breadcrumb.text }}</a></li>
        {% endfor %}
      </ol>
    </div>
  </div>
  <div class="container-fluid">
    <div class="card">
      <div class="card-header"><i class="fa-solid fa-list"></i> {{ text_list }}</div>
      <div id="slideshow" class="card-body">{{ list }}</div>
    </div>
  </div>
</div>
<script type="text/javascript"><!--
$('#slideshow').on('click', 'thead a, .pagination a', function(e) {
    e.preventDefault();

    $('#slideshow').load(this.href);
});
//--></script>
{{ footer }}
```

- [ ] **Step 3: Create the form view** 鈥?`admin/view/template/design/slideshow_form.twig`

```twig
{{ header }}{{ column_left }}
<div id="content">
  <div class="page-header">
    <div class="container-fluid">
      <div class="float-end">
        <button type="submit" form="form-slideshow" data-bs-toggle="tooltip" title="{{ button_save }}" class="btn btn-primary"><i class="fa-solid fa-save"></i></button>
        <a href="{{ back }}" data-bs-toggle="tooltip" title="{{ button_back }}" class="btn btn-light"><i class="fa-solid fa-reply"></i></a></div>
      <h1>{{ text_form }}</h1>
      <ol class="breadcrumb">
        {% for breadcrumb in breadcrumbs %}
          <li class="breadcrumb-item"><a href="{{ breadcrumb.href }}">{{ breadcrumb.text }}</a></li>
        {% endfor %}
      </ol>
    </div>
  </div>
  <div class="container-fluid">
    <div class="card">
      <div class="card-header"><i class="fa-solid fa-pencil"></i> {{ text_form }}</div>
      <div class="card-body">
        <form id="form-slideshow" action="{{ save }}" method="post" data-oc-toggle="ajax">
          <div class="row mb-3 required">
            <label for="input-name" class="col-sm-2 col-form-label">{{ entry_name }}</label>
            <div class="col-sm-10">
              <input type="text" name="name" value="{{ name }}" placeholder="{{ entry_name }}" id="input-name" class="form-control"/>
              <div id="error-name" class="invalid-feedback"></div>
            </div>
          </div>
          <div class="row mb-3">
            <label for="input-width-mode" class="col-sm-2 col-form-label">{{ entry_width_mode }}</label>
            <div class="col-sm-10">
              <select name="width_mode" id="input-width-mode" class="form-select">
                <option value="full"{% if width_mode == 'full' %} selected{% endif %}>{{ text_full }}</option>
                <option value="box"{% if width_mode == 'box' %} selected{% endif %}>{{ text_box }}</option>
              </select>
              <div id="error-width-mode" class="invalid-feedback"></div>
            </div>
          </div>
          <div class="row mb-3">
            <label for="input-height" class="col-sm-2 col-form-label">{{ entry_height }}</label>
            <div class="col-sm-10">
              <input type="text" name="height" value="{{ height }}" placeholder="{{ entry_height }}" id="input-height" class="form-control"/>
              <div id="error-height" class="invalid-feedback"></div>
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-2 col-form-label">{{ entry_autoplay }}</label>
            <div class="col-sm-10">
              <div class="form-check form-switch form-switch-lg">
                <input type="hidden" name="autoplay" value="0"/>
                <input type="checkbox" name="autoplay" value="1" id="input-autoplay" class="form-check-input"{% if autoplay %} checked{% endif %}/>
              </div>
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-2 col-form-label">{{ entry_arrows }}</label>
            <div class="col-sm-10">
              <div class="form-check form-switch form-switch-lg">
                <input type="hidden" name="arrows" value="0"/>
                <input type="checkbox" name="arrows" value="1" id="input-arrows" class="form-check-input"{% if arrows %} checked{% endif %}/>
              </div>
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-2 col-form-label">{{ entry_dots }}</label>
            <div class="col-sm-10">
              <div class="form-check form-switch form-switch-lg">
                <input type="hidden" name="dots" value="0"/>
                <input type="checkbox" name="dots" value="1" id="input-dots" class="form-check-input"{% if dots %} checked{% endif %}/>
              </div>
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-2 col-form-label">{{ entry_status }}</label>
            <div class="col-sm-10">
              <div class="form-check form-switch form-switch-lg">
                <input type="hidden" name="status" value="0"/>
                <input type="checkbox" name="status" value="1" id="input-status" class="form-check-input"{% if status %} checked{% endif %}/>
              </div>
            </div>
          </div>
          <br/>
          <div class="row mb-3">
            <label class="col-sm-2 col-form-label">{{ entry_slide }}</label>
            <div class="col-sm-10">
              <table id="slide" class="table table-bordered table-hover">
                <thead>
                  <tr>
                    <th class="text-center">{{ entry_image }}</th>
                    <th>{{ entry_kicker }}</th>
                    <th>{{ entry_title }}</th>
                    <th>{{ entry_sub }}</th>
                    <th>{{ entry_button_text }}</th>
                    <th>{{ entry_button_link }}</th>
                    <th>{{ entry_overlay_color }}</th>
                    <th>{{ entry_overlay_opacity }}</th>
                    <th>{{ entry_align }}</th>
                    <th class="text-end">{{ entry_sort_order }}</th>
                    <th class="text-center">{{ entry_status }}</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  {% set slide_row = 0 %}
                  {% for slide in slides %}
                    <tr id="slide-row-{{ slide_row }}">
                      <td class="text-center" style="width: 150px;">
                        <div class="border rounded d-block">
                          <img src="{{ slide.thumb }}" alt="" title="" id="thumb-slide-{{ slide_row }}" data-oc-placeholder="{{ placeholder }}" class="img-fluid"/>
                          <input type="hidden" name="slide[{{ slide_row }}][image]" value="{{ slide.image }}" id="input-slide-{{ slide_row }}-image"/>
                          <div class="d-grid">
                            <button type="button" data-oc-toggle="image" data-oc-target="#input-slide-{{ slide_row }}-image" data-oc-thumb="#thumb-slide-{{ slide_row }}" class="btn btn-primary rounded-0"><i class="fa-solid fa-pencil"></i> {{ button_edit }}</button>
                            <button type="button" data-oc-toggle="clear" data-oc-target="#input-slide-{{ slide_row }}-image" data-oc-thumb="#thumb-slide-{{ slide_row }}" class="btn btn-warning rounded-0"><i class="fa-regular fa-trash-can"></i> {{ button_clear }}</button>
                          </div>
                        </div>
                      </td>
                      <td><input type="text" name="slide[{{ slide_row }}][kicker]" value="{{ slide.kicker }}" id="input-slide-{{ slide_row }}-kicker" class="form-control"/></td>
                      <td><input type="text" name="slide[{{ slide_row }}][title]" value="{{ slide.title }}" id="input-slide-{{ slide_row }}-title" class="form-control"/></td>
                      <td><input type="text" name="slide[{{ slide_row }}][sub]" value="{{ slide.sub }}" id="input-slide-{{ slide_row }}-sub" class="form-control"/></td>
                      <td><input type="text" name="slide[{{ slide_row }}][button_text]" value="{{ slide.button_text }}" id="input-slide-{{ slide_row }}-button-text" class="form-control"/></td>
                      <td><input type="text" name="slide[{{ slide_row }}][button_link]" value="{{ slide.button_link }}" id="input-slide-{{ slide_row }}-button-link" class="form-control"/></td>
                      <td style="width: 80px;"><input type="color" name="slide[{{ slide_row }}][overlay_color]" value="{{ slide.overlay_color }}" id="input-slide-{{ slide_row }}-overlay-color" class="form-control form-control-color"/>
                        <div id="error-slide-{{ slide_row }}-overlay-color" class="invalid-feedback"></div></td>
                      <td style="width: 90px;"><input type="text" name="slide[{{ slide_row }}][overlay_opacity]" value="{{ slide.overlay_opacity }}" id="input-slide-{{ slide_row }}-overlay-opacity" class="form-control"/>
                        <div id="error-slide-{{ slide_row }}-overlay-opacity" class="invalid-feedback"></div></td>
                      <td style="width: 100px;"><select name="slide[{{ slide_row }}][align]" id="input-slide-{{ slide_row }}-align" class="form-select">
                          <option value="left"{% if slide.align == 'left' %} selected{% endif %}>{{ text_left }}</option>
                          <option value="center"{% if slide.align == 'center' %} selected{% endif %}>{{ text_center }}</option>
                          <option value="right"{% if slide.align == 'right' %} selected{% endif %}>{{ text_right }}</option>
                        </select>
                        <div id="error-slide-{{ slide_row }}-align" class="invalid-feedback"></div></td>
                      <td class="text-end" style="width: 80px;"><input type="text" name="slide[{{ slide_row }}][sort_order]" value="{{ slide.sort_order }}" id="input-slide-{{ slide_row }}-sort-order" class="form-control"/></td>
                      <td class="text-center" style="width: 60px;"><div class="form-check form-switch">
                        <input type="hidden" name="slide[{{ slide_row }}][status]" value="0"/>
                        <input type="checkbox" name="slide[{{ slide_row }}][status]" value="1" id="input-slide-{{ slide_row }}-status" class="form-check-input"{% if slide.status %} checked{% endif %}/></div></td>
                      <td class="text-end" style="width: 40px;"><button type="button" onclick="$('#slide-row-{{ slide_row }}').remove();" data-bs-toggle="tooltip" title="{{ button_remove }}" class="btn btn-danger"><i class="fa-solid fa-minus-circle"></i></button></td>
                    </tr>
                    {% set slide_row = slide_row + 1 %}
                  {% endfor %}
                </tbody>
                <tfoot>
                  <tr>
                    <td colspan="11"></td>
                    <td class="text-end"><button type="button" id="button-slide" data-bs-toggle="tooltip" title="{{ button_slide_add }}" class="btn btn-primary"><i class="fa-solid fa-plus-circle"></i></button></td>
                  </tr>
                </tfoot>
              </table>
            </div>
          </div>
          <input type="hidden" name="slideshow_id" value="{{ slideshow_id }}" id="input-slideshow-id"/>
        </form>
      </div>
    </div>
  </div>
</div>
<script type="text/javascript"><!--
var slide_row = {{ slide_row }};

$('#button-slide').on('click', function() {
    html = '<tr id="slide-row-' + slide_row + '">';
    html += '  <td class="text-center" style="width: 150px;">';
    html += '    <div class="border rounded d-block">';
    html += '      <img src="{{ placeholder|escape('js') }}" alt="" title="" id="thumb-slide-' + slide_row + '" data-oc-placeholder="{{ placeholder|escape('js') }}" class="img-fluid"/>';
    html += '      <input type="hidden" name="slide[' + slide_row + '][image]" value="" id="input-slide-' + slide_row + '-image"/>';
    html += '      <div class="d-grid">';
    html += '        <button type="button" data-oc-toggle="image" data-oc-target="#input-slide-' + slide_row + '-image" data-oc-thumb="#thumb-slide-' + slide_row + '" class="btn btn-primary rounded-0"><i class="fa-solid fa-pencil"></i> {{ button_edit|escape('js') }}</button>';
    html += '        <button type="button" data-oc-toggle="clear" data-oc-target="#input-slide-' + slide_row + '-image" data-oc-thumb="#thumb-slide-' + slide_row + '" class="btn btn-warning rounded-0"><i class="fa-regular fa-trash-can"></i> {{ button_clear|escape('js') }}</button>';
    html += '      </div>';
    html += '    </div>';
    html += '  </td>';
    html += '  <td><input type="text" name="slide[' + slide_row + '][kicker]" value="" id="input-slide-' + slide_row + '-kicker" class="form-control"/></td>';
    html += '  <td><input type="text" name="slide[' + slide_row + '][title]" value="" id="input-slide-' + slide_row + '-title" class="form-control"/></td>';
    html += '  <td><input type="text" name="slide[' + slide_row + '][sub]" value="" id="input-slide-' + slide_row + '-sub" class="form-control"/></td>';
    html += '  <td><input type="text" name="slide[' + slide_row + '][button_text]" value="" id="input-slide-' + slide_row + '-button-text" class="form-control"/></td>';
    html += '  <td><input type="text" name="slide[' + slide_row + '][button_link]" value="" id="input-slide-' + slide_row + '-button-link" class="form-control"/></td>';
    html += '  <td style="width: 80px;"><input type="color" name="slide[' + slide_row + '][overlay_color]" value="#000000" id="input-slide-' + slide_row + '-overlay-color" class="form-control form-control-color"/>';
    html += '    <div id="error-slide-' + slide_row + '-overlay-color" class="invalid-feedback"></div></td>';
    html += '  <td style="width: 90px;"><input type="text" name="slide[' + slide_row + '][overlay_opacity]" value="30" id="input-slide-' + slide_row + '-overlay-opacity" class="form-control"/>';
    html += '    <div id="error-slide-' + slide_row + '-overlay-opacity" class="invalid-feedback"></div></td>';
    html += '  <td style="width: 100px;"><select name="slide[' + slide_row + '][align]" id="input-slide-' + slide_row + '-align" class="form-select">';
    html += '    <option value="left">{{ text_left|escape('js') }}</option>';
    html += '    <option value="center">{{ text_center|escape('js') }}</option>';
    html += '    <option value="right">{{ text_right|escape('js') }}</option>';
    html += '  </select>';
    html += '    <div id="error-slide-' + slide_row + '-align" class="invalid-feedback"></div></td>';
    html += '  <td class="text-end" style="width: 80px;"><input type="text" name="slide[' + slide_row + '][sort_order]" value="0" id="input-slide-' + slide_row + '-sort-order" class="form-control"/></td>';
    html += '  <td class="text-center" style="width: 60px;"><div class="form-check form-switch">';
    html += '    <input type="hidden" name="slide[' + slide_row + '][status]" value="0"/>';
    html += '    <input type="checkbox" name="slide[' + slide_row + '][status]" value="1" id="input-slide-' + slide_row + '-status" class="form-check-input" checked/></div></td>';
    html += '  <td class="text-end" style="width: 40px;"><button type="button" onclick="$(\'#slide-row-' + slide_row + '\').remove();" data-bs-toggle="tooltip" title="{{ button_remove|escape('js') }}" class="btn btn-danger"><i class="fa-solid fa-minus-circle"></i></button></td>';
    html += '</tr>';

    $('#slide tbody').append(html);

    slide_row++;
});
//--></script>
{{ footer }}
```

- [ ] **Step 4: Add the Design menu entry** 鈥?in `admin/controller/common/column_left.php`

Find the Design group block (starts at the `// Design` comment around line 283). Inside the `$design = [];` array, add a new entry after the `design/theme` entry (or after `design/translation`), mirroring the existing entries:

```php
		if ($this->user->hasPermission('access', 'design/slideshow')) {
			$design[] = [
				'text' => $this->language->get('text_slideshow'),
				'href' => $this->url->link('design/slideshow', 'user_token=' . $this->session->data['user_token']),
				'children' => []
			];
		}
```

- [ ] **Step 5: Add the menu language key** 鈥?append to `admin/language/en-gb/common/column_left.php`

```php
$_['text_slideshow'] = 'Slideshows';
```

- [ ] **Step 6: Verify & commit**

```powershell
git diff --check
git add admin/view/template/design/slideshow.twig admin/view/template/design/slideshow_list.twig admin/view/template/design/slideshow_form.twig admin/controller/common/column_left.php admin/language/en-gb/common/column_left.php
git commit -m "feat: slideshow admin views and design menu entry"
```

---

## Task 4: Catalog Rendering + Styles

**Files:**
- Create: `extension/opencart/catalog/controller/module/slideshow.php`
- Create: `extension/opencart/catalog/view/template/module/slideshow.twig`
- Modify: `catalog/view/stylesheet/stylesheet.scss` + regenerate `.css`

- [ ] **Step 1: Create the catalog controller** 鈥?`extension/opencart/catalog/controller/module/slideshow.php`

```php
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

		$slideshow_info = $this->model_design_slideshow->getSlideshow((int)($setting['slideshow_id'] ?? 0));

		if (!$slideshow_info || !$slideshow_info['status']) {
			return '';
		}

		$data['slides'] = [];

		$slides = $this->model_design_slideshow->getSlides((int)($setting['slideshow_id'] ?? 0));

		$align_map = ['left' => 'text-start', 'center' => 'text-center', 'right' => 'text-end'];

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
				'align'           => $align_map[$slide['align']] ?? 'text-start',
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
```

- [ ] **Step 2: Create the catalog view** 鈥?`extension/opencart/catalog/view/template/module/slideshow.twig`

```twig
<div id="slideshow-{{ module }}" class="va-slideshow{% if width_mode == 'box' %} container{% endif %}">
  <div id="carousel-slideshow-{{ module }}" class="carousel slide carousel-fade" data-bs-ride="{% if autoplay %}carousel{% else %}false{% endif %}" style="height: {{ height }}px;">
    {% if dots and slides|length > 1 %}
      <div class="carousel-indicators">
        {% set slide_count = 0 %}
        {% for slide in slides %}
          <button type="button" data-bs-target="#carousel-slideshow-{{ module }}" data-bs-slide-to="{{ slide_count }}"{% if slide_count == 0 %} class="active"{% endif %}></button>
          {% set slide_count = slide_count + 1 %}
        {% endfor %}
      </div>
    {% endif %}
    <div class="carousel-inner">
      {% set slide_count = 0 %}
      {% for slide in slides %}
        <div class="carousel-item{% if slide_count == 0 %} active{% endif %}">
          <div class="va-slide" style="background-image: url('{{ slide.image }}');">
            <div class="va-slide-overlay" style="background: {{ slide.overlay }};"></div>
            <div class="va-slide-content {{ slide.align }}">
              {% if slide.kicker %}<p class="va-slide-kicker">{{ slide.kicker }}</p>{% endif %}
              {% if slide.title %}<h2 class="va-slide-title">{{ slide.title }}</h2>{% endif %}
              {% if slide.sub %}<p class="va-slide-sub">{{ slide.sub }}</p>{% endif %}
              {% if slide.button_text and slide.button_link %}<a href="{{ slide.button_link }}" class="btn btn-primary btn-lg">{{ slide.button_text }}</a>{% endif %}
            </div>
          </div>
        </div>
        {% set slide_count = slide_count + 1 %}
      {% endfor %}
    </div>
    {% if arrows and slides|length > 1 %}
      <button type="button" class="carousel-control-prev" data-bs-target="#carousel-slideshow-{{ module }}" data-bs-slide="prev"><span class="fa-solid fa-chevron-left"></span></button>
      <button type="button" class="carousel-control-next" data-bs-target="#carousel-slideshow-{{ module }}" data-bs-slide="next"><span class="fa-solid fa-chevron-right"></span></button>
    {% endif %}
  </div>
</div>
<script type="text/javascript"><!--
$(document).ready(function() {
    new bootstrap.Carousel(document.querySelector('#carousel-slideshow-{{ module }}'), {
        ride: {% if autoplay %}'carousel'{% else %}false{% endif %},
        interval: 5000,
        wrap: true
    });
});
//--></script>
```

- [ ] **Step 3: Add slideshow styles** 鈥?append to `catalog/view/stylesheet/stylesheet.scss`

```scss
/* ---------- slideshow module ---------- */
.va-slideshow {
  margin-bottom: 48px;
}

.va-slideshow .carousel {
  border: none;
  border-radius: 0;
  box-shadow: none;
  margin-bottom: 0;
}

.va-slideshow .carousel-inner,
.va-slideshow .carousel-item {
  height: 100%;
}

.va-slide {
  position: relative;
  height: 100%;
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
}

.va-slide-overlay {
  position: absolute;
  inset: 0;
}

.va-slide-content {
  position: relative;
  z-index: 2;
  display: flex;
  flex-direction: column;
  justify-content: center;
  height: 100%;
  max-width: 720px;
  padding: 0 8%;
  color: #FFFFFF;
  text-shadow: 0 1px 3px rgba(0, 0, 0, .4);
}

.va-slideshow .va-slide-content.text-center {
  margin: 0 auto;
  text-align: center;
}

.va-slideshow .va-slide-content.text-end {
  margin-left: auto;
  text-align: right;
}

.va-slide-kicker {
  font-size: 1.25rem;
  letter-spacing: .24em;
  text-transform: uppercase;
  color: var(--va-gold);
  margin-bottom: 10px;
}

.va-slide-title {
  font-size: 2rem;
  color: #FFFFFF;
  margin-bottom: 12px;
}

.va-slide-sub {
  font-size: 1.25rem;
  line-height: 1.6;
  margin-bottom: 24px;
  color: rgba(255, 255, 255, .92);
}

.va-slide-content .btn {
  align-self: flex-start;
}

.va-slideshow .va-slide-content.text-center .btn {
  align-self: center;
}

.va-slideshow .va-slide-content.text-end .btn {
  align-self: flex-end;
}

.va-slideshow .carousel-indicators {
  z-index: 3;
  bottom: 0;
}

.va-slideshow .carousel-indicators [data-bs-target] {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  border: 1px solid #FFFFFF;
  background-color: transparent;
  opacity: .6;
}

.va-slideshow .carousel-indicators .active {
  background-color: var(--va-gold);
  border-color: var(--va-gold);
  opacity: 1;
}

.va-slideshow .carousel-control-prev,
.va-slideshow .carousel-control-next {
  z-index: 3;
  width: 48px;
  height: 48px;
  top: 50%;
  transform: translateY(-50%);
  opacity: 0;
  transition: opacity .25s ease;
  color: #FFFFFF;
  font-size: 1.2rem;
}

.va-slideshow:hover .carousel-control-prev,
.va-slideshow:hover .carousel-control-next {
  opacity: .85;
}

.va-slideshow:hover .carousel-control-prev {
  left: 16px;
}

.va-slideshow:hover .carousel-control-next {
  right: 16px;
}

.va-slideshow .carousel-control-prev {
  left: 16px;
}

.va-slideshow .carousel-control-next {
  right: 16px;
}
```

- [ ] **Step 4: Recompile the stylesheet**

```powershell
npx --yes sass catalog/view/stylesheet/stylesheet.scss catalog/view/stylesheet/stylesheet.css --style=compressed
```

Expected: no errors. Delete `catalog/view/stylesheet/stylesheet.css.map` if produced.

- [ ] **Step 5: Verify & commit**

```powershell
git diff --check
git add extension/opencart/catalog/controller/module/slideshow.php extension/opencart/catalog/view/template/module/slideshow.twig catalog/view/stylesheet/stylesheet.scss catalog/view/stylesheet/stylesheet.css
git commit -m "feat: slideshow catalog rendering with bootstrap carousel"
```

---

## Task 5: Homepage Integration

**Files:**
- Modify: `catalog/view/template/common/home.twig`

- [ ] **Step 1: Remove the hardcoded hero section**

In `catalog/view/template/common/home.twig`, delete the entire `.va-hero` section (the `{% if hero %}`-less block from `<section class="va-hero">` through its closing `</section>`), so the page starts with the `.va-collections` section. Keep everything else. The `.va-hero*` CSS rules in `stylesheet.scss` stay (harmless, reusable).

The file should begin (after `{{ header }}` and `<div id="common-home">`) with:

```twig
  <section class="va-collections">
```

- [ ] **Step 2: Verify**

```powershell
git diff --check
Select-String -Path "catalog/view/template/common/home.twig" -Pattern "va-hero"
```

Expected: no hits for `va-hero` in the template.

- [ ] **Step 3: Commit**

```powershell
git add catalog/view/template/common/home.twig
git commit -m "refactor: remove hardcoded homepage hero, slideshow takes over content_top"
```

---

## Self-Review Notes

- **Spec coverage:** slideshow add/delete 鈫?Task 2 (design CRUD) + Task 1 (module instance); width full/box 鈫?Task 2 form + Task 4 view; height 鈫?same; background image 鈫?Task 2 slide repeater + Task 4 render (original image, CSS cover); overlay color/opacity 鈫?slide fields + hex鈫抮gba in Task 4 controller; content kicker/title/sub/button 鈫?slide fields + render; add/manage slides 鈫?Task 2 form repeater; per-slide alignment 鈫?slide `align` + `text-{align}` classes; autoplay/arrows/dots toggles 鈫?slideshow fields + Bootstrap carousel options. Homepage hero removal 鈫?Task 5.
- **Placeholder scan:** all code complete; no TBD.
- **Type consistency:** model method names (`addSlideshow`/`editSlideshow`/`deleteSlideshow`/`getSlideshow`/`getSlideshows`/`getTotalSlideshows`/`getSlides`/`addSlide`) used consistently across admin controller, module controller (`getSlideshows`), and catalog controller (`getSlideshow`/`getSlides`). `width_mode`/`overlay_color`/`overlay_opacity`/`align` field names consistent from form 鈫?model 鈫?catalog.
- **Deployment note:** module must be installed in admin (Extensions 鈫?Modules 鈫?Slideshow 鈫?Install) to create the tables; the design menu appears automatically after install. Catalog-side model load (`design/slideshow`) mirrors the stock banner module which loads the admin design model from catalog (verified working pattern).