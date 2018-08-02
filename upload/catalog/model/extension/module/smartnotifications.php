<?php
class ModelExtensionModuleSmartNotifications extends Model {
  	public function getChildren($category_id, $data = array ()){
		$query = $this->db->query("SELECT category_id, parent_id FROM " . DB_PREFIX . "category WHERE parent_id = " . (int)$category_id);
		
		if ($query->num_rows == 0){
			return ;
		} else {
			foreach($query->rows as $row){
				array_push($data,$row['category_id']);
				$this->getChildren($row['category_id'],$data); 
			}
		}
	}
}
?>