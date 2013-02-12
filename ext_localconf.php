<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}
#$TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dix_easylogin/pi1/class.tx_dixeasylogin_oauth1.php'] = t3lib_extMgm::extPath($_EXTKEY)."class.ux_tx_dixeasylogin_oauth1.php";

$TYPO3_CONF_VARS['EXTCONF']['dix_easylogin']['hook_userInfo'][] = 'EXT:dix_xingsync/class.tx_dixxingsync_registerLogin.php:&tx_dixxingsync_registerLogin';

t3lib_extMgm::addPItoST43($_EXTKEY, 'pi1/class.tx_dixxingsync_pi1.php', '_pi1', 'list_type', 1);
?>