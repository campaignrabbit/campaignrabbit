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

class plgJ2StoreApp_campaignrabbitInstallerScript {

    function preflight( $type, $parent ) {

        if(!JComponentHelper::isEnabled('com_j2store')) {
            JError::raiseWarning(null, 'J2Store not found. Please install J2Store before installing this plugin');
            return false;
        }

        jimport('joomla.filesystem.file');
        $version_file = JPATH_ADMINISTRATOR.'/components/com_j2store/version.php';
        if (JFile::exists ( $version_file )) {
            require_once($version_file);
            // abort if the current J2Store release is older
            if (version_compare ( J2STORE_VERSION, '3.2.23', 'lt' )) {
                JError::raiseWarning ( null, 'You need at least J2Store for this app to work' );
                return false;
            }
        } else {
            JError::raiseWarning ( null, 'J2Store not found or the version file is not found. Make sure that you have installed J2Store before installing this plugin' );
            return false;
        }

        $db = JFactory::getDbo ();
        // get the table list
        $tables = $db->getTableList ();
        // get prefix
        $prefix = $db->getPrefix ();
        //zoho field map
        /*if(!in_array($prefix.'j2store_campaignfieldmaps',$tables)){
            $query = "CREATE TABLE IF NOT EXISTS `#__j2store_campaignfieldmaps` (
              `j2store_campaignfieldmap_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
              `field_type` varchar(255) NOT NULL,
              `campaign_field` varchar(255) NOT NULL,
              `j2store_field` varchar(255) NOT NULL,
              `default_value` longtext NOT NULL,
              `required` int(11) unsigned NOT NULL,
              `enabled` int(11) unsigned NOT NULL,
              PRIMARY KEY (`j2store_campaignfieldmap_id`)
            ) ENGINE=InnoDB";
            $this->_executeQuery ( $query );
        }*/
        //campaign_addr_id
        if (in_array ( $prefix . 'j2store_addresses', $tables )) {
            $fields = $db->getTableColumns ( '#__j2store_addresses' );
            if (! array_key_exists ( 'campaign_addr_id', $fields )) {
                $query = "ALTER TABLE #__j2store_addresses ADD `campaign_addr_id` varchar(255) NOT NULL;";
                $this->_executeQuery ( $query );
            }
        }

        //campaign_variant_id
        if (in_array ( $prefix . 'j2store_variants', $tables )) {
            $fields = $db->getTableColumns ( '#__j2store_variants' );
            if (! array_key_exists ( 'campaign_variant_id', $fields )) {
                $query = "ALTER TABLE #__j2store_variants ADD `campaign_variant_id` varchar(255) NOT NULL;";
                $this->_executeQuery ( $query );
            }
        }

        //campaign_order_id
        if (in_array ( $prefix . 'j2store_orders', $tables )) {
            $fields = $db->getTableColumns ( '#__j2store_orders' );
            if (! array_key_exists ( 'campaign_order_id', $fields )) {
                $query = "ALTER TABLE #__j2store_orders ADD `campaign_order_id` varchar(255) NOT NULL;";
                $this->_executeQuery ( $query );
            }
        }

        return true;
    }

    private function _executeQuery($query) {
        $db = JFactory::getDbo ();
        $db->setQuery ( $query );
        try {
            $db->execute ();
        } catch ( Exception $e ) {
            // do nothing. we dont want to fail the install process.
        }
    }
}