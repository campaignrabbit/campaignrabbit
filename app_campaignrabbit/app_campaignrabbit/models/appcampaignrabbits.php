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
require_once (JPATH_ADMINISTRATOR . '/components/com_j2store/library/appmodel.php');
require_once(JPATH_SITE.'/plugins/j2store/app_campaignrabbit/library/campaignrabbit/vendor/autoload.php');
class J2StoreModelAppCampaignRabbits extends J2StoreAppModel
{
    public $_element = 'app_campaignrabbit';

    public function getPluginParams(){
        $plugin = JPluginHelper::getPlugin('j2store', $this->_element);
        $params = new JRegistry($plugin->params);
        return $params;
    }

    public function getPlugin(){
        $db = JFactory::getDBo();
        $query = $db->getQuery(true);
        $query->select('*')->from('#__extensions')->where('type='.$db->q('plugin'))->where('element='.$db->q($this->_element))->where('folder='.$db->q('j2store'));
        $db->setQuery($query);
        return $db->loadObject();
    }

    public function saveParams($params){
        $json = $params->toString();
        $db = JFactory::getDbo ();
        $query = $db->getQuery ( true )->update ( $db->qn ( '#__extensions' ) )->set ( $db->qn ( 'params' ) . ' = ' . $db->q ( $json ) )->where ( $db->qn ( 'element' ) . ' = ' . $db->q ( $this->_element ) )->where ( $db->qn ( 'folder' ) . ' = ' . $db->q ( 'j2store' ) )->where ( $db->qn ( 'type' ) . ' = ' . $db->q ( 'plugin' ) );
        $db->setQuery ( $query );
        $db->execute ();
    }

    public function getCustomerList($limit=0,$start=0){
        $db = JFactory::getDbo ();
        $query = $db->getQuery ( true );
        $query->select('#__j2store_addresses.*')->from('#__j2store_addresses');
        $query->where('#__j2store_addresses.campaign_addr_id = ""');
        $query->group('#__j2store_addresses.j2store_address_id');
        $query->join('INNER', '#__j2store_orders AS o ON #__j2store_addresses.email = o.user_email');
        $db->setQuery($query,$start,$limit);
        return $db->loadObjectList();
    }

    public function getInvoiceList($limit=0,$start=0){
        $db = JFactory::getDbo ();
        $query = $db->getQuery ( true );
        $query->select('#__j2store_orders.*')->from('#__j2store_orders');
        $plugin_params = $this->getPluginParams();

        $query->where('#__j2store_orders.campaign_order_id = ""');


        $zero_order = $plugin_params->get('synch_zero_order',1);
        if(!$zero_order){
            $query->where('#__j2store_orders.order_total > 0');
        }
        $order_status = $plugin_params->get('orderstatus',array(1));
        if(!is_array($order_status)){
            $order_status = array($order_status);
        }
        if(!in_array('*',$order_status)){
            $query->where('#__j2store_orders.order_state_id IN ('.implode(',', $order_status ).')');
        }
        $query->group('#__j2store_orders.j2store_order_id');
        $db->setQuery($query,$start,$limit);
        return $db->loadObjectList();
    }

    public function buildQuery($overrideLimits=false) {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $query->select('*')->from('#__j2store_queues');
        $this->_buildQueryWhere ( $query );
        return $query;
    }

    protected function _buildQueryWhere(&$query)
    {
        $db = JFactory::getDbo ();
        $state = $this->getQueueState();

        if(isset( $state->queue_type ) && !empty( $state->queue_type )){
            $query->where ( 'queue_type ='.$db->q($state->queue_type) );
        }

        if(isset( $state->search ) && !empty( $state->search )){
            $query->where('(relation_id LIKE '.$db->q ( '%'.$state->search.'%' ).' OR status LIKE '.$db->q('%'.$state->search.'%').')');
        }

        $repeat_count = J2Store::config()->get('queue_repeat_count',10);
        if(!empty( $repeat_count ) && isset($state->is_expired) && $state->is_expired == 'no'){
            $query->where ( 'repeat_count <= '.$db->q($repeat_count) );
        }
        if(!empty( $repeat_count ) && isset($state->is_expired) && $state->is_expired == 'yes'){
            $query->where ( 'repeat_count > '.$db->q($repeat_count) );
        }
    }

    function getQueueState(){
        $state = array(
            'queue_type' => $this->getState ('queue_type',''),
            'search' => $this->getState('search',''),
            'is_expired' => $this->getState('is_expired','no')
        );
        return (Object)$state;
    }

    /**
     * Campaign Authentication
     * @return array - response from Campaign Rabbit
    */
    public function auth(){
        $params = $this->getPluginParams();
        try{
            $api_token = $params->get('api_token','');
            $app_id = $params->get('app_id','');
            $domain = trim(JUri::root());
            $campaign = new \CampaignRabbit\CampaignRabbit\Action\Request($api_token,$app_id,$domain);
            $response = $campaign->request('POST','user/store/auth','');
            $out_response = $campaign->parseResponse($response);
        }catch (Exception $e){
            $ex_body = $e->getBody()->getContents();
            $out_response = array(
                'message'=> $e->getReasonPhrase(),
                'code'=>$e->getStatusCode(),
                'body'=> isset($ex_body->data) ? $ex_body->data: $ex_body
            );
        }
        return $out_response;
    }

    /**
     * Get Customer from Campaign Rabbit
     * @param $email - customer email
     * @param array - Response
    */
    public function getCustomer($email){
        $params = $this->getPluginParams();
        try{
            $api_token = $params->get('api_token','');
            $app_id = $params->get('app_id','');
            $domain = trim(JUri::root());
            $customer = new \CampaignRabbit\CampaignRabbit\Action\Customer($api_token,$app_id,$domain);
            $out_response = $customer->getCustomer($email);
        }catch (Exception $e){
            $ex_body = $e->getBody()->getContents();
            $out_response = array(
                'message'=> $e->getReasonPhrase(),
                'code'=>$e->getStatusCode(),
                'body'=> isset($ex_body->data) ? $ex_body->data: $ex_body
            );
        }
        return $out_response;
    }

    /**
     * Update Customer to Campaign Rabbit
     * @param $email - customer email
     * @param $name - customer name
     * @param $metas - customer meta data
     * @param array - Response
     */
    public function updateCustomer($customer_params,$email){
        $params = $this->getPluginParams();
        try{
            $api_token = $params->get('api_token','');
            $app_id = $params->get('app_id','');
            $domain = trim(JUri::root());
            $customer = new \CampaignRabbit\CampaignRabbit\Action\Customer($api_token,$app_id,$domain);

            $out_response = $customer->updateCustomer($customer_params,$email);
        }catch (Exception $e){
            $ex_body = $e->getBody()->getContents();
            $out_response = array(
                'message'=> $e->getReasonPhrase(),
                'code'=>$e->getStatusCode(),
                'body'=> isset($ex_body->data) ? $ex_body->data: $ex_body
            );
        }
        return $out_response;
    }


    /**
     * Create Customer to Campaign Rabbit
     * @param $email - customer email
     * @param $name - customer name
     * @param $metas - customer meta data
     * @param array - Response
     */
    public function createCustomer($customer_params){
        $params = $this->getPluginParams();
        try{
            $api_token = $params->get('api_token','');
            $app_id = $params->get('app_id','');
            $domain = trim(JUri::root());
            $customer = new \CampaignRabbit\CampaignRabbit\Action\Customer($api_token,$app_id,$domain);
            //$campaign = new \CampaignRabbit\CampaignRabbit\Action\Request($api_token,$app_id,$domain);
            $out_response = $customer->createCustomer($customer_params);//$campaign->request('POST','customer',json_encode($where));
            //$out_response = $campaign->parseResponse($response);
        }catch (Exception $e){
            $ex_body = $e->getBody()->getContents();
            $out_response = array(
                'message'=> $e->getReasonPhrase(),
                'code'=>$e->getStatusCode(),
                'body'=> isset($ex_body->data) ? $ex_body->data: $ex_body
            );
        }
        return $out_response;
    }

    /**
     * Get Order from Campaign Rabbit
     * @param $order - Order Object
     * @param array - Response
     */
    public function getRabbitOrder($order){
        $params = $this->getPluginParams();
        try{
            $api_token = $params->get('api_token','');
            $app_id = $params->get('app_id','');
            $domain = trim(JUri::root());
            $rabbit_order = new \CampaignRabbit\CampaignRabbit\Action\Order($api_token,$app_id,$domain);
            $out_response = $rabbit_order->getOrderByRef($order->order_id);
        }catch (Exception $e){
            $ex_body = $e->getBody()->getContents();
            $out_response = array(
                'message'=> $e->getReasonPhrase(),
                'code'=>$e->getStatusCode(),
                'body'=> isset($ex_body->data) ? $ex_body->data: $ex_body
            );
        }
        return $out_response;
    }

    /**
     * Update Order to Campaign Rabbit
     * @param $order - Order Object
     * @param $order_params - Campaign order params
     * @param array - Response
     */
    public function updateRabbitOrder($order,$order_params){
        $params = $this->getPluginParams();
        try{
            $api_token = $params->get('api_token','');
            $app_id = $params->get('app_id','');
            $domain = trim(JUri::root());
            $rabbit_order = new \CampaignRabbit\CampaignRabbit\Action\Order($api_token,$app_id,$domain);
            $old_rabbit_order = $rabbit_order->getOrderByRef($order->order_id);
            if(isset($old_rabbit_order['body']->id)){
                $out_response = $rabbit_order->updateOrder($order_params,$old_rabbit_order['body']->id);
            }else{
                $ex_body = $old_rabbit_order->getBody()->getContents();
                $out_response = array(
                    'message'=> $old_rabbit_order->getReasonPhrase(),
                    'code'=> $old_rabbit_order->getStatusCode(),
                    'body'=> isset($ex_body->data) ? $ex_body->data: $ex_body//$old_rabbit_order->getBody()->getContents()
                );
            }

        }catch (Exception $e){
            $ex_body = $e->getBody()->getContents();
            $out_response = array(
                'message'=> $e->getReasonPhrase(),
                'code'=>$e->getStatusCode(),
                'body'=> isset($ex_body->data) ? $ex_body->data: $ex_body
            );
        }
        return $out_response;
    }

    /**
     * Create order to Campaign Rabbit
     * @param $order - Order Object
     * @param $order_params - Campaign order params
     * @param array - Response
     */
    public function createRabbitOrder($order,$order_params){
        $params = $this->getPluginParams();
        try{
            $api_token = $params->get('api_token','');
            $app_id = $params->get('app_id','');
            $domain = trim(JUri::root());
            $rabbit_order = new \CampaignRabbit\CampaignRabbit\Action\Order($api_token,$app_id,$domain);
            $out_response = $rabbit_order->createOrder($order_params);
        }catch (Exception $e){
            $ex_body = $e->getBody()->getContents();
            $out_response = array(
                'message'=> $e->getReasonPhrase(),
                'code'=>$e->getStatusCode(),
                'body'=> isset($ex_body->data) ? $ex_body->data: $ex_body
            );
        }
        return $out_response;
    }

    /**
     * Add/Update Product to Campaign Rabbit
     * @param $item - Order Item Object
     * @param $product_params - Campaign product params
     * @param $order - order object
     * @param array - Response
     */
    public function addOrUpdateProducts($item,$product_params,$order){
        if(!isset($product_params['sku'])){
            return '';
        }
        $params = $this->getPluginParams();
        try{
            $api_token = $params->get('api_token','');
            $app_id = $params->get('app_id','');
            $domain = trim(JUri::root());
            $rabbit_order = new \CampaignRabbit\CampaignRabbit\Action\Product($api_token,$app_id,$domain);
            $out_response = $rabbit_order->getProduct($product_params['sku']);

            $is_need_update = false;
            if(isset($out_response['body']->sku)){
                $is_need_update = true;
            }
            if($is_need_update){
                $product_response =  $rabbit_order->updateProduct($product_params,$product_params['sku']);
            }else{
                $product_response =  $rabbit_order->createProduct($product_params);
            }

            if(isset($product_response['body']->sku)){
                $this->_log(json_encode($product_response),'Product Create/Update: ');
                $order->add_history('Campaign Rabbit Product sku : '.$product_params['sku']);
            }
        }catch (Exception $e){
            $ex_body = $e->getBody()->getContents();
            $out_response = array(
                'message'=> $e->getReasonPhrase(),
                'code'=>$e->getStatusCode(),
                'body'=> isset($ex_body->data) ? $ex_body->data: $ex_body
            );
            $this->_log(json_encode($out_response),'Product Error: ');
            $order->add_history($product_params['sku']. ' - Campaign Rabbit Product Error: '.json_encode($out_response));
        }

        return $out_response;
    }
}