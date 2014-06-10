<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "dix_xingsync".
 *
 * Auto generated 10-06-2014 11:11
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Synchronize fe_users with Xing',
	'description' => '',
	'category' => 'plugin',
	'author' => 'Markus Kappe',
	'author_email' => 'markus.kappe@dix.at',
	'shy' => '',
	'dependencies' => 'dix_easylogin,dix_lib,smarty',
	'conflicts' => '',
	'priority' => '',
	'module' => '',
	'state' => 'stable',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 1,
	'lockType' => '',
	'author_company' => '',
	'version' => '1.0.5',
	'constraints' => array(
		'depends' => array(
			'typo3' => '4.5.0-6.1.99',
			'dix_easylogin' => '0.3.2',
			'dix_lib' => '',
			'smarty' => '',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:14:{s:9:"ChangeLog";s:4:"6034";s:38:"class.tx_dixxingsync_registerLogin.php";s:4:"0993";s:35:"class.ux_tx_dixeasylogin_oauth1.php";s:4:"127f";s:12:"ext_icon.gif";s:4:"f9b3";s:17:"ext_localconf.php";s:4:"2968";s:14:"ext_tables.php";s:4:"a618";s:16:"locallang_db.xml";s:4:"06f5";s:14:"doc/manual.sxw";s:4:"48d5";s:32:"pi1/class.tx_dixxingsync_pi1.php";s:4:"7679";s:17:"pi1/locallang.xml";s:4:"9e64";s:16:"static/setup.txt";s:4:"f483";s:21:"templates/import.tmpl";s:4:"765b";s:20:"templates/index.tmpl";s:4:"354e";s:25:"templates/ll_template.xml";s:4:"1be2";}',
	'suggests' => array(
	),
);

?>