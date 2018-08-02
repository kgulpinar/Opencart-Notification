<?php
class ControllerExtensionModuleSmartNotifications extends Controller {
    private $moduleName;
    private $moduleNameSmall;
    private $moduleVersion;
    private $modulePath;
    private $extensionsLink;
    private $callModel;
    private $moduleModel;
    private $data = array();
    private $error = array();
    
    public function __construct($registry) {
        parent::__construct($registry);

        $this->config->load('isenselabs/smartnotifications');
        
        /* OC version-specific declarations - Begin */
        $this->moduleName = $this->config->get('smartnotifications_name');
        $this->moduleNameSmall = $this->config->get('smartnotifications_name_small');
        $this->moduleVersion = $this->config->get('smartnotifications_version');
        $this->modulePath = $this->config->get('smartnotifications_path');
        $this->extensionsLink = $this->url->link($this->config->get('smartnotifications_extensions_link'), 'user_token=' . $this->session->data['user_token'] . $this->config->get('smartnotifications_extensions_link_params'), 'SSL');
        /* OC version-specific declarations - End */
        
        /* Module-specific declarations - Begin */
        $this->load->language($this->modulePath);
        $this->load->model($this->modulePath);
        $this->callModel = $this->config->get('smartnotifications_model_call');
        $this->moduleModel = $this->{$this->callModel};
        /* Module-specific declarations - End */
        
        /* Module-specific loaders - Begin */
        $this->load->model('setting/store');
        $this->load->model('setting/setting');
        $this->load->model('localisation/language');
        $this->load->model('customer/customer_group');
        /* Module-specific loaders - End */        
        
        $this->data['module_name'] = $this->moduleNameSmall;
        $this->data['module_path'] = $this->modulePath;
    }
    
    public function index() {
        $this->data['heading_title'] = $this->language->get('heading_title') . ' ' . $this->moduleVersion;
        $this->document->setTitle($this->data['heading_title']);

        $currentTemplate = $this->config->get('config_theme');

        /** Get SmartNotifications styles and scripts - begin **/
        $this->document->addStyle('view/stylesheet/' . $this->moduleNameSmall . '/fontawesome-iconpicker.min.css');
        $this->document->addStyle('view/stylesheet/' . $this->moduleNameSmall . '/bootstrap-slider.css');
        $this->document->addStyle('view/javascript/summernote/summernote.css');
        $this->document->addStyle('view/stylesheet/' . $this->moduleNameSmall . '/' . $this->moduleNameSmall . '.css');
        $this->document->addStyle('view/stylesheet/' . $this->moduleNameSmall . '/bootstrap-colorpicker.min.css');

        if (file_exists(dirname(DIR_APPLICATION) . '/catalog/' . $currentTemplate . '/stylesheet/' . $this->moduleNameSmall . '/animate.css')) {
            $this->document->addStyle('../catalog/view/theme/' . $currentTemplate . '/stylesheet/' . $this->moduleNameSmall . '/animate.css');
        } else {
            $this->document->addStyle('../catalog/view/theme/default/stylesheet/' . $this->moduleNameSmall . '/animate.css');
        }
        
        if (file_exists(dirname(DIR_APPLICATION) . '/catalog/' . $currentTemplate . '/stylesheet/' . $this->moduleNameSmall . '/' . $this->moduleNameSmall . '.css')) {
            $this->document->addStyle('../catalog/view/theme/' . $currentTemplate . '/stylesheet/' . $this->moduleNameSmall . '/' . $this->moduleNameSmall . '.css');
        } else {
            $this->document->addStyle('../catalog/view/theme/default/stylesheet/' . $this->moduleNameSmall . '/' . $this->moduleNameSmall . '.css');
        }

        $this->document->addScript('view/javascript/' . $this->moduleNameSmall . '/fontawesome-iconpicker.min.js');
        $this->document->addScript('view/javascript/' . $this->moduleNameSmall . '/bootstrap-slider.js');        
        $this->document->addScript('view/javascript/' . $this->moduleNameSmall . '/nprogress.js');
        $this->document->addScript('view/javascript/' . $this->moduleNameSmall . '/bootstrap-colorpicker.min.js');

        $this->document->addScript('../catalog/view/javascript/' . $this->moduleNameSmall . '/noty/packaged/jquery.noty.packaged.js');        
        $this->document->addScript('view/javascript/summernote/summernote.min.js');
        $this->document->addScript('../catalog/view/javascript/' . $this->moduleNameSmall . '/noty/themes/smart-notifications.js');
        /** Get SmartNotifications styles and scripts - end **/
        
        if (!isset($this->request->get['store_id'])) {
            $this->request->get['store_id'] = 0;
        }
        
        $store = $this->getCurrentStore($this->request->get['store_id']);

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            if (!empty($_POST['OaXRyb1BhY2sgLSBDb21'])) {
                $this->request->post[$this->moduleNameSmall]['licensed_on'] = $_POST['OaXRyb1BhY2sgLSBDb21'];
            }
            
            if (!empty($_POST['cHRpbWl6YXRpb24ef4fe'])) {
                $this->request->post[$this->moduleNameSmall]['license'] = json_decode(base64_decode($_POST['cHRpbWl6YXRpb24ef4fe']), true);
            }
            
            $store = $this->getCurrentStore($this->request->post['store_id']);
            
            if (!$this->user->hasPermission('modify', $this->modulePath)) {
                $this->redirect($this->url->link('extension/module', 'user_token=' . $this->session->data['user_token'], 'SSL'));
            }

            if (isset($this->request->post['smartnotifications']['popup'])) {
                $this->load->model('catalog/product');
                $this->load->model('catalog/category');
                
                foreach ($this->request->post['smartnotifications']['popup'] as $popup => $val) {
                    if (!empty($this->request->post['smartnotifications']['popup'][$popup]['product_category'])) {
                        foreach ($this->request->post['smartnotifications']['popup'][$popup]['product_category'] as $key => $value) {
                            $category_info = $this->model_catalog_category->getCategory($this->request->post['smartnotifications']['popup'][$popup]['product_category'][$key]);
                            
                            if ($category_info) {
                                $this->request->post['smartnotifications']['popup'][$popup]['product_category'][$key] = array(
                                    'category_id' => $category_info['category_id'],
                                    'name' => ($category_info['path']) ? $category_info['path'] . ' &gt; ' . $category_info['name'] : $category_info['name']
                                );
                            }
                        }
                    }

                    if (!empty($this->request->post['smartnotifications']['popup'][$popup]['category'])) {
                        foreach ($this->request->post['smartnotifications']['popup'][$popup]['category'] as $key => $value) {
                            $category_info = $this->model_catalog_category->getCategory($this->request->post['smartnotifications']['popup'][$popup]['category'][$key]);
                            
                            if ($category_info) {
                                $this->request->post['smartnotifications']['popup'][$popup]['category'][$key] = array(
                                    'category_id' => $category_info['category_id'],
                                    'name' => ($category_info['path']) ? $category_info['path'] . ' &gt; ' . $category_info['name'] : $category_info['name']
                                );
                            }
                        }
                    }

                    if (!empty($this->request->post['smartnotifications']['popup'][$popup]['products'])) {
                        foreach ($this->request->post['smartnotifications']['popup'][$popup]['products'] as $key => $value) {
                            $product_info = $this->model_catalog_product->getProduct($value);
                            
                            if ($product_info) {
                                $this->request->post['smartnotifications']['popup'][$popup]['products'][$key] = array(
                                    'product_id' => $product_info['product_id'],
                                    'name' => $product_info['name']
                                );
                            }
                        }
                    }
                }
            }

            $this->model_setting_setting->editSetting($this->moduleNameSmall, $this->request->post, $this->request->post['store_id']);
            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link($this->modulePath, 'store_id=' . $this->request->post['store_id'] . '&user_token=' . $this->session->data['user_token'], 'SSL'));
        }
        
        $languageVariables = array(
            // Main
            'error_permission',
            'text_success',
            'text_enabled',
            'text_disabled',
            'button_cancel',
            'save_changes',
            'text_default',
            'text_module',
            // Control panel
            'entry_code',
            'entry_code_help',
            'entry_popup_options',
            'entry_action_options',
            'button_add_module',
            'button_remove',
            'text_url',
            'entry_content',
            'entry_size',
            'text_show_on',
            'text_window_load',
            'text_page_load',
            'text_body_click'
        );
        
        foreach ($languageVariables as $languageVariable) {
            $this->data[$languageVariable] = $this->language->get($languageVariable);
        }

        $this->data['customer_groups'] = $this->model_customer_customer_group->getCustomerGroups();
        $this->data['days_of_week'] = $this->getWeekDays();
        $this->data['url'] = preg_replace('/https?\:/', '', $this->url->link($this->modulePath . "/livePreview", "", "SSL"));

        $this->data['stores'] = array_merge(array(
            0 => array(
                'store_id' => '0',
                'name' => $this->config->get('config_name') . ' (' . $this->data['text_default'] . ')',
                'url' => HTTP_SERVER,
                'ssl' => HTTPS_SERVER
            )
        ), $this->model_setting_store->getStores());

        /* Get language settings and country flags - begin */
        $languages = $this->model_localisation_language->getLanguages();
        $this->data['languages'] = $languages;
        foreach ($languages as $key => $value) {
            $this->data['languages'][$key]['flag_url'] = 'language/' . $languages[$key]['code'] . '/' . $languages[$key]['code'] . '.png"';
        }
        /* Get language settings and country flags - end */

        $this->data['store'] = $store;

        $this->data['user_token'] = $this->session->data['user_token'];
        $this->data['now'] = time(); // get the current time for license tab
        $this->data['action'] = $this->url->link($this->modulePath, 'user_token=' . $this->session->data['user_token'], 'SSL');
        $this->data['cancel'] = $this->extensionsLink;

        $moduleSettings = $this->model_setting_setting->getSetting($this->moduleNameSmall, $store['store_id']);
        $this->data['module_data'] = $moduleSettings;

        /* Set messages for success, eror and unlicensed version - begin */
        if (!empty($moduleSettings[$this->moduleNameSmall]['licensed_on'])) {
            $this->data['license_encoded'] = base64_encode(json_encode($moduleSettings[$this->moduleNameSmall]['license']));
            $this->data['expiration_date'] = date("F j, Y", strtotime($moduleSettings[$this->moduleNameSmall]['license']['licenseExpireDate'])); 
        }

        if (empty($moduleSettings['smartnotifications']['licensed_on'])) {
            $this->data['licensed_on'] = base64_decode('ICAgIDxkaXYgY2xhc3M9ImFsZXJ0IGFsZXJ0LWRhbmdlciBmYWRlIGluIj4NCiAgICAgICAgPGJ1dHRvbiB0eXBlPSJidXR0b24iIGNsYXNzPSJjbG9zZSIgZGF0YS1kaXNtaXNzPSJhbGVydCIgYXJpYS1oaWRkZW49InRydWUiPsOXPC9idXR0b24+DQogICAgICAgIDxoND5XYXJuaW5nISBVbmxpY2Vuc2VkIHZlcnNpb24gb2YgdGhlIG1vZHVsZSE8L2g0Pg0KICAgICAgICA8cD5Zb3UgYXJlIHJ1bm5pbmcgYW4gdW5saWNlbnNlZCB2ZXJzaW9uIG9mIHRoaXMgbW9kdWxlISBZb3UgbmVlZCB0byBlbnRlciB5b3VyIGxpY2Vuc2UgY29kZSB0byBlbnN1cmUgcHJvcGVyIGZ1bmN0aW9uaW5nLCBhY2Nlc3MgdG8gc3VwcG9ydCBhbmQgdXBkYXRlcy48L3A+PGRpdiBzdHlsZT0iaGVpZ2h0OjVweDsiPjwvZGl2Pg0KICAgICAgICA8YSBjbGFzcz0iYnRuIGJ0bi1kYW5nZXIiIGhyZWY9ImphdmFzY3JpcHQ6dm9pZCgwKSIgb25jbGljaz0iJCgnYVtocmVmPSNpc2Vuc2Utc3VwcG9ydF0nKS50cmlnZ2VyKCdjbGljaycpIj5FbnRlciB5b3VyIGxpY2Vuc2UgY29kZTwvYT4NCiAgICA8L2Rpdj4=');
        }

        if (isset($this->session->data['success'])) {
            $this->data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        } else {
            $this->data['success'] = '';
        }
        
        if (isset($this->error['warning'])) {
            $this->data['error_warning'] = $this->error['warning'];
        } else {
            $this->data['error_warning'] = '';
        }
        
        if (isset($this->error['code'])) {
            $data['error_code'] = $this->error['code'];
        } else {
            $data['error_code'] = '';
        }
        /* Set messages for success, eror and unlicensed version - end */

        $this->load->model('design/layout');
        $this->data['layouts'] = $this->model_design_layout->getLayouts();

        $this->data['catalog_url'] = $this->getCatalogURL();

        if (isset($this->data['module_data'][$this->moduleNameSmall])) {
            $this->data['module_data'] = $this->data['module_data'][$this->moduleNameSmall];
        } else {
            $this->data['module_data'] = array();
        }

        if (isset($this->data['module_data']['popup'])) {
            foreach ($this->data['module_data']['popup'] as $popup => $value) {
                if (!empty($this->data['module_data']['popup'][$popup]['icon_image'])) {
                    $this->load->model('tool/image');
                    $this->data['module_data']['popup'][$popup]['icon_image_thumb'] = $this->model_tool_image->resize($this->data['module_data']['popup'][$popup]['icon_image'], 50, 50);
                }
            }
        }
        
        $this->data['breadcrumbs'] = array();
        $this->data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true),
            'separator' => false
        );
        $this->data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_module'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true),
            'separator' => ' :: '
        );
        $this->data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link($this->modulePath, 'user_token=' . $this->session->data['user_token'], true),
            'separator' => ' :: '
        );

        $this->data['header'] = $this->load->controller('common/header');
        $this->data['column_left'] = $this->load->controller('common/column_left');
        $this->data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view($this->modulePath, $this->data));
    }
    
    public function getSmartNotificationsSettings() {
        $this->load->model('customer/customer_group');
        $this->data['customer_groups'] = $this->model_customer_customer_group->getCustomerGroups();

        $this->data['days_of_week'] = $this->getWeekDays();
        $this->data['currency']  = $this->config->get('config_currency');
        $this->data['languages'] = $this->model_localisation_language->getLanguages();

        foreach ($this->data['languages'] as $key => $value) {
            $this->data['languages'][$key]['flag_url'] = 'language/' . $this->data['languages'][$key]['code'] . '/' . $this->data['languages'][$key]['code'] . '.png"';
        }
        
        $store_id = $this->request->get['store_id'];
        
        $this->data['popup']['id'] = $this->request->get['popup_id'];
        $this->data['user_token'] = $this->session->data['user_token'];
        $this->data['data'] = $this->model_setting_setting->getSetting($this->moduleNameSmall, $store_id);
        
        $this->data['module_data']  = isset($this->data['module_data'][$this->moduleNameSmall]) ? $this->data['module_data'][$this->moduleNameSmall] : array();
        $this->data['new_addition'] = true;

        $this->response->setOutput($this->load->view($this->modulePath . '/tab_popuptab', $this->data));
    }
    
    private function getCatalogURL() {
        if (isset($_SERVER['HTTPS']) && (($_SERVER['HTTPS'] == 'on') || ($_SERVER['HTTPS'] == '1'))) {
            $storeURL = HTTPS_CATALOG;
        } else {
            $storeURL = HTTP_CATALOG;
        }
        
        return $storeURL;
    }
    
    private function getServerURL() {
        if (isset($_SERVER['HTTPS']) && (($_SERVER['HTTPS'] == 'on') || ($_SERVER['HTTPS'] == '1'))) {
            $storeURL = HTTPS_SERVER;
        } else {
            $storeURL = HTTP_SERVER;
        }
        
        return $storeURL;
    }
    
    private function getCurrentStore($store_id) {
        if ($store_id && $store_id != 0) {
            $store = $this->model_setting_store->getStore($store_id);
        } else {
            $store['store_id'] = 0;
            $store['name']     = $this->config->get('config_name');
            $store['url']      = $this->getCatalogURL();
        }
        
        return $store;
    }

    private function getWeekDays() { 
        return array(
            'Mon' => $this->language->get('text_monday'),
            'Tue' => $this->language->get('text_tuesday'),
            'Wed' => $this->language->get('text_wednesday'),
            'Thu' => $this->language->get('text_thursday'),
            'Fri' => $this->language->get('text_friday'),
            'Sat' => $this->language->get('text_saturday'),
            'Sun' => $this->language->get('text_sunday'),
        );
    }
    
    public function install() {
        $this->moduleModel->install($this->moduleNameSmall);
    }

    public function uninstall() {
        $this->model_setting_setting->deleteSetting($this->module_data_module, 0);
        $stores = $this->model_setting_store->getStores();
        
        foreach ($stores as $store) {
            $this->model_setting_setting->deleteSetting($this->module_data_module, $store['store_id']);
        }
        
        $this->moduleModel->uninstall($this->moduleNameSmall);
    }
    
    protected function validateForm() {
        if (!$this->user->hasPermission('modify', $this->modulePath)) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        
        return !$this->error;
    }
}
