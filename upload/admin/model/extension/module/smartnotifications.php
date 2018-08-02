<?php 
class ModelExtensionModuleSmartNotifications extends Model {
	public function install($moduleName) {
		/** Add SmartNotifications to the other frontend modules - begin **/
		$this->db->query("DELETE FROM `" . DB_PREFIX . "setting` WHERE store_id = '0' AND `code` = 'module_" . $this->db->escape($moduleName) . "'");

		$this->db->query("INSERT INTO " . DB_PREFIX . "setting SET store_id = '0', `code` = 'module_" . $this->db->escape($moduleName) . "', `key` = 'module_" . $this->db->escape($moduleName) . "_status', `value` = '1'");
		/** Add SmartNotifications to the other frontend modules - end **/

		/** Add the module to all layouts - begin **/
		$this->load->model('design/layout');
		$layouts = array();
		$layouts = $this->model_design_layout->getLayouts();
			
		foreach ($layouts as $layout) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "layout_module SET layout_id = '" . (int)$layout['layout_id'] . "', code = '" . $this->db->escape($moduleName) . "', position = '" . $this->db->escape('content_bottom') . "', sort_order = '0'");
			
			$this->event->trigger('post.admin.edit.layout', array($layout['layout_id']));
		}
		/** Add the module to all layouts - end **/
  	}
  
  	public function uninstall($moduleName) {
  		$this->db->query("DELETE FROM `" . DB_PREFIX . "setting` WHERE `code`= '" . $this->db->escape($moduleName) . "'");

		$this->load->model('design/layout');
		$layouts = array();
		$layouts = $this->model_design_layout->getLayouts();
			
		foreach ($layouts as $layout) {
			$this->db->query("DELETE FROM " . DB_PREFIX . "layout_module WHERE layout_id = '" . (int)$layout['layout_id'] . "' and code = '" . $this->db->escape($moduleName)."'");
		}
  	}
  }
?>