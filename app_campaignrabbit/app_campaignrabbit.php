<?php
/**
 * --------------------------------------------------------------------------------
 * APP - Campaign Rabbit
 * --------------------------------------------------------------------------------
 * @package     Joomla  3.x
 * @subpackage  J2 Store
 * @author      Alagesan, J2Store <support@j2store.org>
 * @copyright   Copyright (c) 2018 J2Store . All rights reserved.
 * @license     GNU/GPL license: v3 or later
 * @link        http://j2store.org
 * --------------------------------------------------------------------------------
 *
 * */
defined('_JEXEC') or die('Restricted access');
require_once(JPATH_ADMINISTRATOR.'/components/com_j2store/library/plugins/app.php');
class plgJ2StoreApp_campaignrabbit extends J2StoreAppPlugin
{
    /**
     * @var $_element  string  Should always correspond with the plugin's filename,
     *                         forcing it to be unique
     */
    var $_element = 'app_campaignrabbit';

    function __construct ( &$subject, $config )
    {
        parent::__construct ( $subject, $config );
        JFactory::getLanguage ()->load ( 'plg_j2store_' . $this->_element, JPATH_ADMINISTRATOR );
    }

    /**
     * Overriding
     *
     * @param $options
     * @return unknown_type
     */
    function onJ2StoreGetAppView ( $row )
    {

        if ( !$this->_isMe ( $row ) ) {
            return null;
        }

        $html = $this->viewList ();


        return $html;
    }

    /**
     * Validates the data submitted based on the suffix provided
     * A controller for this plugin, you could say
     *
     * @param $task
     * @return html
     */
    function viewList ()
    {

        $app = JFactory::getApplication ();
        $vars = new stdClass();
        $id = $app->input->getInt ( 'id', 0 );
        $vars->id = $id;
        JToolBarHelper::title ( JText::_ ( 'J2STORE_APP' ) . '-' . JText::_ ( 'PLG_J2STORE_' . strtoupper ( $this->_element ) ), 'j2store-logo' );
        JToolBarHelper::apply ( 'apply' );
        JToolBarHelper::save ();
        JToolBarHelper::back ( 'PLG_J2STORE_BACK_TO_APPS', 'index.php?option=com_j2store&view=apps' );
        JToolBarHelper::back ( 'J2STORE_BACK_TO_DASHBOARD', 'index.php?option=com_j2store' );
        $bar =JToolBar::getInstance('toolbar');
        $bar->appendButton( 'Link', 'list', 'J2STORE_APP_CAMPAIGNRABBIT_MANAGE_QUEUE', 'index.php?option=com_j2store&view=apps&id='.$id.'&task=view&appTask=manageQueue' );
        $this->includeCustomModel ( 'AppCampaignRabbits' );
        $model = F0FModel::getTmpInstance ( 'AppCampaignRabbits', 'J2StoreModel' );

        $data = $this->params->toArray ();

        $newdata = array();
        $newdata[ 'params' ] = $data;
        $form = $model->getForm ( $newdata );
        $vars->form = $form;
        $vars->action = "index.php?option=com_j2store&view=app&task=view&id={$id}";

        $html = $this->_getLayout ( 'default', $vars );
        return $html;
    }

    /**
     * Add Customer details to Queue
     *   */
    /*function onJ2StoreCheckoutShippingPayment($order){
        $session = JFactory::getSession();
        $address_id = $session->get('billing_address_id','','j2store');

        if($address_id) {
            $user = JFactory::getUser();
            $address = F0FTable::getInstance('Address', 'J2StoreTable')->getClone();
            $address->load($address_id);
            if(isset($address->campaign_addr_id) && !empty($address->campaign_addr_id)){
                $task = 'update_customer';
            }else{
                $task = 'create_customer';
            }

            $ship_address_id = $session->get('shipping_address_id','','j2store');
            $queue_data = array(
                'user_id' => $user->id,
                'email' => $address->email,
                'ship_address_id' => $ship_address_id,
                'billing_address_id' => $address_id,
                'task' => $task
            );

            $tz = JFactory::getConfig()->get('offset');
            $current_date = JFactory::getDate('now', $tz)->toSql(true);
            $date = JFactory::getDate('now +7 day', $tz)->toSql(true);

            $queue = array(
                'queue_type' => $this->_element,
                'relation_id' => 'user_'.$address_id,
                'queue_data' => json_encode($queue_data),
                'params' => '{}',
                'priority' => 0,
                'status' => 'new',
                'expired' => $date,
                'modified_on' => $current_date
            );
            try{
                $queue_table = F0FTable::getInstance('Queue', 'J2StoreTable')->getClone();
                $queue_table->load(array(
                    'relation_id' => $queue['relation_id']
                ));
                if(empty($queue_table->created_on)){
                    $queue_table->created_on = $current_date;
                }
                $queue_table->bind($queue);
                $queue_table->store();
            }catch (Exception $e){
                // do nothing
                $this->_log($e->getMessage(),'User Exception: ');
            }
        }
    }*/

    public function onJ2StoreAfterDisplayShippingPayment($order){
        return $this->displayOptIn('payment');
    }

    public function displayOptIn($opt_in_type){
        $html = '';
        $opt_in_postion = $this->params->get('opt_in_position','payment');
        $enable_opt_in = $this->params->get('enable_opt_in',0);
        if(($opt_in_postion == $opt_in_type) && $enable_opt_in){
            $vars = new JObject();
            $vars->params = $this->params;
            $html = $this->_getLayout('optin_check', $vars);
        }
        return $html;
    }

    public function onJ2StoreCheckoutValidateShippingPayment($values, $order){
        $session = JFactory::getSession();
        $address_id = $session->get('billing_address_id','','j2store');
        $session->set('app_campainrabbit_order',0,'j2store');
        $check_opt_in_status = false;
        $opt_in = $this->params->get('enable_opt_in',0);
        if(!$opt_in){
            $check_opt_in_status = true;
        }elseif($opt_in && isset($values['app_camp_rabbit_opt_in']) && $values['app_camp_rabbit_opt_in']){
            $check_opt_in_status = true;
        }
        if($address_id && $check_opt_in_status) {
            $session->set('app_campainrabbit_order',1,'j2store');
            $user = JFactory::getUser();
            $address = F0FTable::getInstance('Address', 'J2StoreTable')->getClone();
            $address->load($address_id);
            if(isset($address->campaign_addr_id) && !empty($address->campaign_addr_id)){
                $task = 'update_customer';
            }else{
                $task = 'create_customer';
            }

            $ship_address_id = $session->get('shipping_address_id','','j2store');
            $queue_data = array(
                'user_id' => $user->id,
                'email' => $address->email,
                'ship_address_id' => $ship_address_id,
                'billing_address_id' => $address_id,
                'task' => $task
            );

            $tz = JFactory::getConfig()->get('offset');
            $current_date = JFactory::getDate('now', $tz)->toSql(true);
            $date = JFactory::getDate('now +7 day', $tz)->toSql(true);

            $queue = array(
                'queue_type' => $this->_element,
                'relation_id' => 'user_'.$address_id,
                'queue_data' => json_encode($queue_data),
                'params' => '{}',
                'priority' => 0,
                'status' => 'new',
                'expired' => $date,
                'modified_on' => $current_date
            );
            try{
                $queue_table = F0FTable::getInstance('Queue', 'J2StoreTable')->getClone();
                $queue_table->load(array(
                    'relation_id' => $queue['relation_id']
                ));
                if(empty($queue_table->created_on)){
                    $queue_table->created_on = $current_date;
                }
                $queue_table->bind($queue);
                $queue_table->store();
            }catch (Exception $e){
                // do nothing
                $this->_log($e->getMessage(),'User Exception: ');
            }
        }
    }

    public function onJ2StorePrePayment($orderpayment_type, $data){
        if(isset($data['order_id']) && !empty($data['order_id'])){
            F0FTable::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_j2store/tables');
            $order = F0FTable::getInstance ( 'Order', 'J2StoreTable' )->getClone ();
            $order->load ( array (
                'order_id' => $data ['order_id']
            ) );
            $session = JFactory::getSession();
            $campaign_status = $session->get('app_campainrabbit_order',0,'j2store');
            $order_params = $this->getRegistryObject($order->order_params);
            if($campaign_status){
                $order_params->set('app_campainrabbit_order',1);
                $order->order_params = $order_params->toString();
                $order->store();
            }else{
                $order_params->set('app_campainrabbit_order',0);
                $order->order_params = $order_params->toString();
                $order->store();
            }

        }
    }


    /**
     * Add Order details to Queue Table
    */
    function onJ2StoreAfterOrderstatusUpdate($order,$new_status){
        //check orderstatus for syncronize
        $order_status = $this->params->get('orderstatus',array(1));
        if(!is_array($order_status)){
            $order_status = array($order_status);
        }

        if(!in_array('*',$order_status)){
            if(!in_array($order->order_state_id, $order_status)){
                //remove from queue
                return '';
            }
        }
        $order_params = $this->getRegistryObject($order->order_params);
        $order_campaign_status = $order_params->get('app_campainrabbit_order',0);
        $opt_in = $this->params->get('enable_opt_in',0);
        $check_opt_in_status = false;
        if(!$opt_in){
            $check_opt_in_status = true;
        }elseif($opt_in && $order_campaign_status){
            $check_opt_in_status = true;
        }
        if(!$check_opt_in_status){
            return '';
        }

        if(!empty($order->campaign_order_id)){
            $task = 'update_order';
        }else{
            $task = 'create_order';
        }
        $queue_data = array(
            'order_id' =>$order->order_id,
            'task' => $task
        );

        $tz = JFactory::getConfig()->get('offset');
        $current_date = JFactory::getDate('now', $tz)->toSql(true);
        $date = JFactory::getDate('now +7 day', $tz)->toSql(true);

        $queue = array(
            'queue_type' => $this->_element,
            'relation_id' => 'order_'.$order->order_id,
            'queue_data' => json_encode($queue_data),
            'params' => '{}',
            'priority' => 0,
            'status' => 'new',
            'expired' => $date,
            'modified_on' => $current_date
        );
        try{
            $queue_table = F0FTable::getInstance('Queue', 'J2StoreTable')->getClone();
            $queue_table->load(array(
                'relation_id' => $queue['relation_id']
            ));
            if(empty($queue_table->created_on)){
                $queue_table->created_on = $current_date;
            }
            $queue_table->bind($queue);
            $queue_table->store();
        }catch (Exception $e){
            $this->_log($e->getMessage(),'Order task Exception: ');
        }
    }

    /**
     * Process Queue
    */
    public function onJ2StoreProcessQueue($list){
        if(isset($list->queue_type) && $list->queue_type == $this->_element){
            if(isset($list->queue_data) && !empty($list->queue_data)){
                $queue_helper = J2Store::queue();
                $queue_data = new JRegistry;
                $queue_data->loadString($list->queue_data);
                $task = $queue_data->get('task','');
                $queue_status = false;

                if(!empty($task)){
                    switch ($task){
                        case 'create_customer':
                            $queue_status = $this->addCustomer($queue_data);
                            break;
                        case 'update_customer':
                            $queue_status = $this->addCustomer($queue_data);
                            break;
                        case 'create_order':
                            $queue_status = $this->addSales($queue_data);
                            break;
                        case 'update_order':
                            $queue_status = $this->addSales($queue_data);
                            break;
                        default:
                            $queue_status = false;
                            break;
                    }
                }

                if($queue_status){
                    $queue_helper->deleteQueue($list);
                }else{
                    $queue_helper->resetQueue($list);
                }
            }
        }
    }

    /**
     * Syncronize to Campaign Rabbit
    */
    public function addCustomer($queue_data){

        $token = $this->params->get('api_token','');
        if(empty($token)){
            return false;
        }

        $app_id = $this->params->get('app_id','');
        if(empty($app_id)){
            return false;
        }

        $email = $queue_data->get('email', '');
        $email = trim($email);
        if(empty($email)) return true;

        //make sure they have an order
        /*$db = JFactory::getDbo();
        $sql = $db->getQuery(true)->select('user_email')->from('#__j2store_orders')->where('LOWER(user_email) = '.$db->q(strtolower($email)));
        $db->setQuery($sql);

        try {
            $result = $db->loadResult();
            if(!$result || empty($result)) {
                //no order found. Remove from the queue
                return true;
            }
        }catch(Exception $e) {
            return false;
        }*/
        
        $address_id = $queue_data->get('billing_address_id','');
        $user_id = $queue_data->get('user_id',0);

        F0FTable::addIncludePath ( JPATH_ADMINISTRATOR . '/components/com_j2store/tables' );
        $address = F0FTable::getInstance('Address', 'J2StoreTable')->getClone();

        if(!$address->load($address_id)){
            $address->load(array(
                'user_id' => $user_id
            ));
        }
        $country_name = $this->getCountryById($address->country_id)->country_name;
        $state = $this->getZoneById($address->zone_id)->zone_name;
        $contact_status = false;
        try{
            // check customer exit
            //query-customer
            $this->includeCustomModel ( 'AppCampaignRabbits' );
            $model = F0FModel::getTmpInstance ( 'AppCampaignRabbits', 'J2StoreModel' );
            $campaign_customer = $model->getCustomer($email);

            $is_need_update = false;
            if(isset($campaign_customer['body']->id)){
                $is_need_update = true;
            }

            // customer params
            $metas = array();
            foreach ($address as $key => $value){
                if($key == "country_id"){
                    $value = $country_name;
                }elseif($key == 'zone_id'){
                    $value = $state;
                }
                $meta = array();
                $meta['meta_key'] = $key;
                if(is_array($value)){
                    $value = json_encode($value);
                }
                $meta['meta_value'] = $value;
                $meta['meta_options'] = '';
                $metas[] = $meta;
            }
            $name = $address->first_name.' '. $address->last_name;

            if($is_need_update){
                // update customer
                $out_response = $model->updateCustomer($email,$name,$metas);
            }else{
                // create customer
                $out_response = $model->createCustomer($email,$name,$metas);

            }

            if($out_response['body']->id){
                $this->_log(json_encode($out_response),'Customer Create/Update: ');
                $contact_status = true;
            }
        }catch (Exception $e){
            $this->_log($e->getMessage(),'Customer Exception: ');
            $contact_status = false;
        }
        return $contact_status;
    }

    /**
     * Syncronize to Sales Order
    */
    public function addSales($queue_data){
        $token = $this->params->get('api_token','');
        if(empty($token)){
            return false;
        }

        $order_id = $queue_data->get('order_id','');
        if(empty($order_id)){
            return false;
        }

        $order = F0FTable::getInstance('Order', 'J2StoreTable')->getClone();
        $order->load(array(
            'order_id' => $order_id

        ));

        $zero_order = $this->params->get('synch_zero_order',1);
        if(!$zero_order && $order->order_total <=0){
            //remove from queue
            return true;
        }
        //check orderstatus for syncronize
        $order_status = $this->params->get('orderstatus',array(1));
        if(!is_array($order_status)){
            $order_status = array($order_status);
        }
        if(!in_array('*',$order_status)){
            if(!in_array($order->order_state_id, $order_status)){
                //remove from queue
                return true;
            }
        }

        $invoice_number = $order->getInvoiceNumber();
        $orderinfo = $order->getOrderInformation();
        $order_status = false;
        $this->includeCustomModel ( 'AppCampaignRabbits' );
        $model = F0FModel::getTmpInstance ( 'AppCampaignRabbits', 'J2StoreModel' );

        // customer params
        $metas = array();
        foreach ($order as $key => $value){
            $meta = array();
            $meta['meta_key'] = $key;
            if(is_array($value)){
                $value = json_encode($value);
            }
            $meta['meta_value'] = $value;
            $meta['meta_options'] = '';
            $metas[] = $meta;
        }
        
        $orderitems = $order->getItems();
        $items = array();
        foreach ($orderitems as $order_item){
            $item = array();
            $item['r_product_id'] = $order_item->variant_id;
            $item['sku'] = $order_item->orderitem_sku;
            $item['product_name'] = $order_item->orderitem_name;
            $item['product_price'] = $order_item->orderitem_finalprice;
            $item['item_qty'] = $order_item->orderitem_quantity;
            $item_meta = array();
            foreach ($order_item as $key => $value){
                $meta = array();
                $meta['meta_key'] = $key;
                if(is_array($value)){
                    $value = json_encode($value);
                }
                $meta['meta_value'] = $value;
                $meta['meta_options'] = '';
                $item_meta[] = $meta;
            }
            $item['meta'] = $item_meta;
            $model->addOrUpdateProducts($order_item,$item,$order);

            $items[] = $item;
        }
        $bill_country_name = $this->getCountryById($orderinfo->billing_country_id)->country_name;
        $bill_state = $this->getZoneById($orderinfo->billing_zone_id)->zone_name;
        $ship_country_name = $this->getCountryById($orderinfo->shipping_country_id)->country_name;
        $ship_state = $this->getZoneById($orderinfo->shipping_zone_id)->zone_name;

        $billing_address = array(
            "first_name" => $orderinfo->billing_first_name,
            "company_name" => $orderinfo->billing_company,
            "email" => $order->user_email,
            "mobile" => $orderinfo->billing_phone_2,
            "address_1" => $orderinfo->billing_address_1,
            "address_2" => $orderinfo->billing_address_2,
            "city" => $orderinfo->billing_city,
            "state" => $bill_state,
            "country" => $bill_country_name,
            "zipcode" => $orderinfo->billing_zip
        );

        $shipping_address = array(
            "first_name" => $orderinfo->shipping_first_name,
            "company_name" => $orderinfo->shipping_company,
            "email" => $order->user_email,
            "mobile" => $orderinfo->shipping_phone_2,
            "address_1" => $orderinfo->shipping_address_1,
            "address_2" => $orderinfo->shipping_address_2,
            "city" => $orderinfo->shipping_city,
            "state" => $ship_state,
            "country" => $ship_country_name,
            "zipcode" => $orderinfo->shipping_zip
        );
        $status = 'unpaid';
        if(in_array($order->order_state_id,array(1,2))){
            $status = 'paid';
        }elseif($order->order_state_id == 3){
            $status = 'failed';
        }elseif($order->order_state_id == 4){
            $status = 'pending';
        }elseif($order->order_state_id == 5){
            $status = 'unpaid';
        }elseif($order->order_state_id == 6){
            $status = 'cancelled';
        }
        //[‘unpaid’, ‘paid’, ‘pending’, ‘cancelled’, ‘failed’]
        $order_params = array(
            'r_order_id' => $order->order_id,
            'r_order_ref' => $order->j2store_order_id,
            'customer_email' => $order->user_email,
            'customer_name' => $orderinfo->billing_first_name.' '.$orderinfo->billing_last_name,
            'status' => $status,
            'order_total' => $order->order_total,
            'meta' => $metas,
            'order_items' => $items,
            'shipping' => $shipping_address,
            'billing' => $billing_address
        );
        $order_status = false;
        try{

            $campaign_order = $model->getRabbitOrder($order);

            $is_need_update = false;
            if(isset($campaign_order['body']->id)){
                $is_need_update = true;
            }
            if($is_need_update){
                // update customer
                $out_response = $model->updateRabbitOrder($order,$order_params);
            }else{
                // create customer
                $out_response = $model->createRabbitOrder($order,$order_params);

            }
            if(isset($out_response['body']->id)){
                $this->_log(json_encode($out_response),'Invoice Create/Update: ');
                $order_status = true;
                $order->add_history('Campaign Rabbit Order id: '.$out_response['body']->id);
            }

        }catch (Exception $e){
            $this->_log($e->getMessage(),'Order Exception: ');
            $order_status = false;
            $order->add_history('Order Exception:'.$e->getMessage());
        }
        return $order_status;

    }

    /**
     * Syncronize Product to Campaign rabbit
    */
    public function addProducts($order,&$insert_data){

    }

    public function onJ2StoreAdminOrderAfterGeneralInformation($order_view){
        $is_enable_manuval = $this->params->get('syn_manual',0);
        $order = $order_view->order;
        $html = '';
        //check orderstatus for syncronize
        $order_status = $this->params->get('orderstatus',array(1));
        if(!is_array($order_status)){
            $order_status = array($order_status);
        }
        if(!in_array('*',$order_status)){
            if(!in_array($order->order_state_id, $order_status)){
                //remove from queue
                return $html;
            }
        }
        if($is_enable_manuval){
            $vars = new stdClass();
            //model should always be a plural
            $this->includeCustomModel ( 'AppCampaignRabbits' );
            $model = F0FModel::getTmpInstance('AppCampaignRabbits', 'J2StoreModel');
            $id = $model->getPlugin()->extension_id;
            $vars->id = $id;
            $vars->action = "index.php?option=com_j2store&view=app&task=view&id={$id}";
            $vars->button_text = JText::_('J2STORE_CAMPAIGN_SYNCRONIZE');
            $vars->order_id = $order->order_id;
            $html .= $this->_getLayout('order_queue', $vars);
        }
        return $html;
    }


    /**
     * Simple logger
     *
     * @param string $text
     * @param string $type
     * @return void
     */
    function _log($text, $type = 'message')
    {
        $isLog = $this->params->get('debug',0);
        if ($isLog) {
            $file = JPATH_ROOT . "/cache/{$this->_element}.log";
            $date = JFactory::getDate();

            $f = fopen($file, 'a');
            fwrite($f, "\n\n" . $date->format('Y-m-d H:i:s'));
            fwrite($f, "\n" . $type . ': ' . $text);
            fclose($f);
        }
    }

    public function getRegistryObject($json){
        if(!$json instanceof JRegistry) {
            $params = new JRegistry();
            try {
                $params->loadString($json);

            }catch(Exception $e) {
                $params = new JRegistry('{}');
            }
        }else{
            $params = $json;
        }
        return $params;
    }
}