<?php
/**
 * @package J2Store
 * @author  Alagesan, J2Store <support@j2store.org>
 * @copyright Copyright (c)2018 Ramesh Elamathi / J2Store.org
 * @license GNU GPL v3 or later
 */
/** ensure this file is being included by a parent file */
// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

jimport( 'joomla.plugin.plugin' );
jimport('joomla.html.parameter');

// Make sure FOF is loaded, otherwise do not run
if (!defined('F0F_INCLUDED'))
{
    include_once JPATH_LIBRARIES . '/f0f/include.php';
}

if (!defined('F0F_INCLUDED') || !class_exists('F0FLess', true))
{
    return;
}

// Do not run if Akeeba Subscriptions is not enabled
JLoader::import('joomla.application.component.helper');

if (!JComponentHelper::isEnabled('com_j2store', true))
{
    return;
}


class plgSystemCampaignrabbit extends JPlugin {
    function onAfterRoute() {
        //chk app campaign enabled
        if(JPluginHelper::isEnabled('j2store', 'app_campaignrabbit')){
            $plugin_data = JPluginHelper::getPlugin('j2store', 'app_campaignrabbit');
            $params = new \JRegistry;
            $params->loadString($plugin_data->params);
            $app_id = $params->get('app_id','');
            $document = JFactory::getDocument();
            $script_content = 'window.app_url = "https://app.campaignrabbit.com/";window.app_id = "'.$app_id.'";window.ancs_url = "https://hook.campaignrabbit.com/v1/pixel.gif";
                !function(e,t,n,p,o,a,i,s,c){e[o]||(i=e[o]=function(){i.process?i.process.apply(i,arguments):i.queue.push(arguments)},i.queue=[],i.t=1*new Date,s=t.createElement(n),s.async=1,s.src=p+"?t="+Math.ceil(new Date/a)*a,c=t.getElementsByTagName(n)[0],c.parentNode.insertBefore(s,c))}(window,document,"script","https://cdn.campaignrabbit.com/campaignrabbit.analytics.js","rabbit",1),rabbit("init",window.app_id),rabbit("event","pageload");';
            $document->addScriptDeclaration($script_content);
        }
    }
}