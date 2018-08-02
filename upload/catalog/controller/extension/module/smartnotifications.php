<?php
class ControllerExtensionModuleSmartNotifications extends Controller {
    private $moduleName;
    private $moduleNameSmall;
    private $modulePath;
    private $callModel;
    private $moduleModel;
    private $data = array();
    
    public function __construct($registry) {
        parent::__construct($registry);
        
        $this->config->load('isenselabs/smartnotifications');
        
        /* OC version-specific declarations - Begin */
        $this->moduleName      = $this->config->get('smartnotifications_name');
        $this->moduleNameSmall = $this->config->get('smartnotifications_name_small');
        $this->modulePath      = $this->config->get('smartnotifications_path');
        /* OC version-specific declarations - End */
        
        /* Module-specific declarations - Begin */
        $this->load->model($this->modulePath);
        $this->load->model('tool/image');
        $this->callModel   = $this->config->get('smartnotifications_model_call');
        $this->moduleModel = $this->{$this->callModel};
        /* Module-specific declarations - End */
    }
    
    public function index($setting)
    {
        $currentTemplate = $this->config->get('config_theme');

        $this->data['url'] = preg_replace('/https?\:/', '', $this->url->link($this->modulePath . "/getPopup", "", "SSL"));
        
        if (file_exists(DIR_TEMPLATE . $currentTemplate . '/stylesheet/' . $this->moduleNameSmall . '/animate.css')) {
            $this->document->addStyle('catalog/view/theme/' . $currentTemplate . '/stylesheet/' . $this->moduleNameSmall . '/animate.css');
        } else {
            $this->document->addStyle('catalog/view/theme/default/stylesheet/' . $this->moduleNameSmall . '/animate.css');
        }
        
        $this->document->addScript('catalog/view/javascript/' . $this->moduleNameSmall . '/noty/packaged/jquery.noty.packaged.js');
        $this->document->addScript('catalog/view/javascript/' . $this->moduleNameSmall . '/noty/themes/smart-notifications.js');
        
        if (file_exists(DIR_TEMPLATE . $currentTemplate . '/stylesheet/' . $this->moduleNameSmall . '/' . $this->moduleNameSmall . '.css')) {
            $this->document->addStyle('catalog/view/theme/' . $currentTemplate . '/stylesheet/' . $this->moduleNameSmall . '/' . $this->moduleNameSmall . '.css');
        } else {
            $this->document->addStyle('catalog/view/theme/default/stylesheet/' . $this->moduleNameSmall . '/' . $this->moduleNameSmall . '.css');
        }
        
        $direction = $this->language->get('direction');
        
        if ($direction == 'rtl') {
            if (file_exists(DIR_TEMPLATE . $currentTemplate . '/stylesheet/' . $this->moduleNameSmall . '/animate.css')) {
                $this->document->addStyle('catalog/view/theme/' . $currentTemplate . '/stylesheet/' . $this->moduleNameSmall . '/' . $this->moduleNameSmall . '_rtl.css');
            } else {
                $this->document->addStyle('catalog/view/theme/default/stylesheet/' . $this->moduleNameSmall . '/' . $this->moduleNameSmall . '_rtl.css');
            }
        }
        
        if (isset($this->request->get['product_id'])) {
            $this->data['product_id'] = $this->request->get['product_id'];
        } else {
            $this->data['product_id'] = 0;
        }

        if (isset($this->request->get['path'])) {
            $this->data['path'] = $this->request->get['path'];
        } else {
            $this->data['path'] = '';
        }

        if (isset($this->request->get['route'])) {
            $this->data['route'] = $this->request->get['route'];
        } else {
            $this->data['route'] = '';
        }

        return $this->load->view($this->modulePath, $this->data);
    }
    
    protected function showPopup($popup_id) {
        if (!isset($this->session->data['popups_repeat']) || !in_array($popup_id, $this->session->data['popups_repeat'])) {
            $this->session->data['popups_repeat'][] = $popup_id;
            return true;
        } else {
            return false;
        }
    }
    
    public function cookieCheck($days, $popup_id) {
        if (!isset($_COOKIE["smartnotifications" . $popup_id])) {
            setcookie("smartnotifications" . $popup_id, true, time() + 3600 * 24 * $days);
            return true;
        } else {
            return false;
        }
    }
    
    public function checkCustomerGroup($popup) {
        $popup_customer_group = array();

        if (!empty($popup['customer_groups'])) {
            $popup_customer_group = $popup['customer_groups'];
        }
        
        $customer_group_id = !is_null($this->customer->getGroupId()) ? $this->customer->getGroupId() : 0;
        return array_key_exists($customer_group_id, $popup_customer_group);
    }

    public function checkDaysOfWeek($popup) {
        if ($popup['days_of_week_status'] == '1' && !empty($popup['selected_days_of_week'])) {
           $today = date('D');
           return array_key_exists($today,$popup['selected_days_of_week']);
        }

        return !$popup['days_of_week_status'];
    }
    
    public function timeIsBetween($from, $to, $enabled) {
        $date = 'now';
        $date = is_int($date) ? $date : strtotime($date); // convert non timestamps
        $from = is_int($from) ? $from : strtotime($from);
        $to   = is_int($to) ? $to : strtotime($to);

        if ($enabled == "0"){
            return true;
        }
        
        return ($date > $from) && ($date < $to); // extra parens for clarity
    }
    
    private function isHome($uri) {
        $parsedURI = parse_url($uri);
        if ((strcmp(HTTP_SERVER, $uri) === 0) || (strcmp(HTTPS_SERVER, $uri) === 0) || (isset($parsedURI['query']) && $parsedURI['query'] == 'route=common/home') || (!isset($parsedURI['query']) && isset($parsedURI['path']) && $parsedURI['path'] == '/')) {
            return true;
        } else
            return false;
    }
    
    private function checkRepeatConditions($popup) {
        return ($popup['repeat'] == 0) || ($popup['repeat'] == 1 && $this->showPopup($popup['id'], $popup['repeat'])) || ($popup['repeat'] == 2 && $this->cookieCheck($popup['days'], $popup['id']));
    }
    
    public function getPopup() {
        header('Access-Control-Allow-Origin: *');
        
        if (isset($this->request->post['product_id'])) {
            $product_id = $this->request->post['product_id'];
        } else {
            $product_id = 0;
        }

        if (isset($this->request->post['path'])) {
            $path = $this->request->post['path'];
        } else {
            $path = 0;
        }

        if (isset($this->request->post['route'])) {
            $route = $this->request->post['route'];
        } else {
            $route = 0;
        }
        
        if (isset($this->request->post['uri'])) {
            $uri = $this->request->post['uri'];
        } else {
            $uri = "";
        }
        
        if (!isset($this->session->data['popups_repeat']))
            $this->session->data['popups_repeat'] = array();
        
        $date = date('H:i', time());
        $data = $this->config->get('smartnotifications');

        $uri = urldecode(htmlspecialchars_decode((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $this->request->post['uri']));

        $this->load->model('catalog/product');
        $categories = $this->model_catalog_product->getCategories($product_id);
        
        $json = array();

        if (!empty($data['popup'])) {
            foreach ($data['popup'] as $popup) {   
                if ($popup['enabled'] == "yes" && $this->checkCustomerGroup($popup) && $this->checkDaysOfWeek($popup)) {
                    $range = explode(",", $popup['random_range']);
                    if (!isset($range[0])) {
                    	$range[0] = 1;
                    }
                    if (!isset($range[1])) {
                    	$range[1] = 100;
                    }
                    if (!isset($popup['title'][$this->config->get('config_language_id')])) {
                    	$popup['title'][$this->config->get('config_language_id')] = '';
                    }
                    if (!isset($popup['description'][$this->config->get('config_language_id')])) {
                    	$popup['description'][$this->config->get('config_language_id')] = '';
                    }
                    $popup['title'][$this->config->get('config_language_id')] = str_replace('%random_value%', rand((int)$range[0], (int)$range[1]), $popup['title'][$this->config->get('config_language_id')]);
                    $popup['description'][$this->config->get('config_language_id')] = str_replace('%random_value%', rand((int)$range[0], (int)$range[1]), $popup['description'][$this->config->get('config_language_id')]);
                    
                    if ($popup['method'] == "0") { // On Homepage method
                        if ($this->timeIsBetween($popup['start_time'], $popup['end_time'], $popup['time_interval'])) {
                            $parsedURI = parse_url($uri);
                            if ($this->isHome($uri)) {
                                if ($this->checkRepeatConditions($popup)) {
                                    $json[] = $this->returnCurrentPopupInformation($popup);
                                }
                            }
                        }
                    }
                    
                    else if ($popup['method'] == "1") { // All pages method
                        if ($this->timeIsBetween($popup['start_time'], $popup['end_time'], $popup['time_interval'])) {
                            $excludedURLs = array();
                            $excludedURLs = array_map("urldecode", preg_split("/\\r\\n|\\r|\\n/", html_entity_decode($popup['excluded_urls'])));
                            if (($this->checkRepeatConditions($popup)) && !in_array($uri, $excludedURLs)) {
                                $json[] = $this->returnCurrentPopupInformation($popup);
                            }
                        }
                    }
                    
                    else if ($popup['method'] == "2") { // Specific URLs method
                        if ($this->timeIsBetween($popup['start_time'], $popup['end_time'], $popup['time_interval'])) {
                            $URLs = array();
                            $URLs = array_map("urldecode", preg_split("/\\r\\n|\\r|\\n/", html_entity_decode($popup['url'])));
                            $popup['url'] = htmlspecialchars_decode($popup['url']);
                            foreach ($URLs as $url) {
                                if (strpos($uri, $url) !== false) {
                                    if ($this->checkRepeatConditions($popup)) {
                                        $json[] = $this->returnCurrentPopupInformation($popup);
                                    }
                                }
                            }
                        }
                    }
                    
                    else if ($popup['method'] == "3" && strpos($route, 'product/product') !== false) { // Product Categories
                        $children = array();
                        $cat_match = false;
                        if ($this->timeIsBetween($popup['start_time'], $popup['end_time'], $popup['time_interval'])) {
                            foreach ($categories as $cat) {
                                foreach ($popup['product_category'] as $allowed_cat) {
                                    $this->moduleModel->getChildren($allowed_cat['category_id'], $children);

                                    array_push($children, $allowed_cat['category_id']);
                                    if (in_array($cat['category_id'], $children)) {
                                        $cat_match = true;
                                    }
                                }
                            }
                        }
                        
                        if ($cat_match && $this->checkRepeatConditions($popup)) {
                            $json[] = $this->returnCurrentPopupInformation($popup);
                        }
                    }

                    else if ($popup['method'] == "4" && !empty($path) && strpos($route, 'product/category') !== false) { // Categories         
                        $categories = explode('_', (string)$path);
                        $category_id = (int)array_pop($categories);
                        
                        $cat_match = false;
                        if ($this->timeIsBetween($popup['start_time'], $popup['end_time'], $popup['time_interval'])) {
                            foreach ($popup['category'] as $allowed_cat) {
                                if ($allowed_cat['category_id'] == $category_id) {
                                    $cat_match = true;
                                    break;
                                }
                            }
                        }
                        
                        if ($cat_match && $this->checkRepeatConditions($popup)) {
                            $json[] = $this->returnCurrentPopupInformation($popup);
                        }
                    }

                    else if ($popup['method'] == "5" && strpos($route, 'product/product') !== false) { // Specific Product
                        $product_match = false;
                        if ($this->timeIsBetween($popup['start_time'], $popup['end_time'], $popup['time_interval'])) {
                            foreach ($popup['products'] as $allowed_product) {
                                if ($allowed_product['product_id'] == $product_id) {
                                    $product_match = true;
                                    break;
                                }
                            }
                        }
                        
                        if ($product_match && $this->checkRepeatConditions($popup)) {
                            $json[] = $this->returnCurrentPopupInformation($popup);
                        }
                    }
                }
            }
        }

        $this->response->setOutput(json_encode($json));
    }

    private function returnCurrentPopupInformation($popup = array()) {
        $popupInfo = array(); // for the result

        // Main settings
        $popupInfo['match'] = true;
        $popupInfo['popup_id'] = $popup['id'];
        $popupInfo['title'] = html_entity_decode($popup['title'][$this->config->get('config_language_id')]);
        $popupInfo['description'] = html_entity_decode($popup['description'][$this->config->get('config_language_id')]);
        $popupInfo['event'] = $popup['event'];
        $popupInfo['random_range'] = $popup['random_range'];
        $popupInfo['position'] = $popup['position'];
        
        // Added devices
        $popupInfo['show_on_mobile'] = $popup['show_on_mobile'];
        $popupInfo['show_on_desktop'] = $popup['show_on_desktop'];
        $popupInfo['delay'] = $popup['delay'];
        $popupInfo['timeout'] = $popup['timeout'];

        // Animation
        $popupInfo['open_animation'] = $popup['open_animation'];
        $popupInfo['close_animation'] = $popup['close_animation'];

        // Template options
        $popupInfo['template'] = $popup['template'];
        $popupInfo['width'] = $popup['width'];
        $popupInfo['height'] = $popup['height'];
        $popupInfo['hex_code_background'] = $popup['hex_code_background'];
        $popupInfo['hex_code_border'] = $popup['hex_code_border'];
        $popupInfo['hex_code_title'] = $popup['hex_code_title'];

        // Icon settings
        $popupInfo['icon'] = $popup['icon'];
        $popupInfo['show_icon'] = $popup['show_icon'];
        $popupInfo['icon_type'] = $popup['icon_type'];
        $popupInfo['icon_image'] = !empty($popup['icon_image']) ? $this->model_tool_image->resize($popup['icon_image'], 50, 50) : '';

        return $popupInfo;
    }
}
