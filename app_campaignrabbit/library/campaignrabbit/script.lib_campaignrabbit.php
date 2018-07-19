<?php
defined('_JEXEC') or die('Restricted access');
jimport('joomla.filesystem.file');
include_once(JPATH_ADMINISTRATOR.'/components/com_j2store/version.php');
class plgLib_phpexcelInstallerScript{
	function preflight( $type, $parent ) {
		if (version_compare(phpversion(), '5.4', '<')) {
			Jerror::raiseWarning ( null, 'You need PHP Version 5.4 to install this package' );
			return false;
		}
	}
}
