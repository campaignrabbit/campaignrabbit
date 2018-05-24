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
class J2StoreModelAppZohoFieldMaps extends J2StoreAppModel
{
    public $_element = 'app_zohocrm';

    public function buildQuery($overrideLimits=false) {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $query->select('*')->from('#__j2store_zohofieldmaps');
        $this->_buildQueryWhere ( $query );
        return $query;
    }

    public function _buildQueryWhere(&$query){
        $db = JFactory::getDbo ();
        $state = $this->getFieldState();
        if(isset( $state->field_type ) && !empty( $state->field_type )){
            $query->where ( 'field_type ='.$db->q($state->field_type) );
        }

        if(isset( $state->search ) && !empty( $state->search )){
            $query->where('(j2store_field LIKE '.$db->q ( '%'.$state->search.'%' ).' OR zoho_field LIKE '.$db->q('%'.$state->search.'%').')');
        }
    }

    public function getFieldState(){
        $state = array(
            'field_type' => $this->getState ('field_type',''),
            'search' => $this->getState('search','')
        );
        return (Object)$state;
    }
}