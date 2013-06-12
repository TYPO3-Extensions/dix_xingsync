<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Markus Kappe <markus.kappe@dix.at>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

// require_once(PATH_tslib . 'class.tslib_pibase.php');
require_once(t3lib_extMgm::extPath("dix_lib")."class.tx_dixlib.php");

/**
 * Plugin 'Xing Sync' for the 'dix_xingsync' extension.
 *
 * @author	Markus Kappe <markus.kappe@dix.at>
 * @package	TYPO3
 * @subpackage	tx_dixxingsync
 */
class tx_dixxingsync_pi1 extends tslib_pibase {
	public $prefixId      = 'tx_dixxingsync_pi1';		// Same as class name
	public $scriptRelPath = 'pi1/class.tx_dixxingsync_pi1.php';	// Path to this script relative to the extension dir.
	public $extKey        = 'dix_xingsync';	// The extension key.
	public $pi_checkCHash = TRUE;
	
	/**
	 * The main method of the Plugin.
	 *
	 * @param string $content The Plugin content
	 * @param array $conf The Plugin configuration
	 * @return string The content that is displayed on the website
	 */
	public function main($content, $conf) {
		$this->init($conf);
		$content = $this->doActions($this->piVars['action']);
		return $this->pi_wrapInBaseClass($content);
	}

	function doActions($action) {
		switch ($action) {
			case 'import':
				$content = $this->makeImport($this->piVars);
				break;
			default:
				$content = $this->makeIndex();
		}
		return $content;
	}

	/* Import selected data and show result state*/
	function makeImport($vars) {
		$data = t3lib_div::makeInstance('tx_dixxingsync_Data');
		$data->init($this->conf);
		$data->import($vars);
		$view = t3lib_div::makeInstance('tx_dixlib_View', $data);
		$content = $view->render('import.tmpl');
		return $content;
	}

	/* Zeige die potentiell zu importierten und die aktuellen Werte an*/
	function makeIndex() {
		
		$data = t3lib_div::makeInstance('tx_dixxingsync_Data');
		$data->init($this->conf);
		$view = t3lib_div::makeInstance('tx_dixlib_View', $data);
		$myRes = $data->getValue();
		
		// feature: Redirect auf Login-Seite, damit der User sich Clicks spart
		if ($myRes['error'] == 'nologin') {
			t3lib_utility_http::redirect($this->pi_getPageLink($this->conf['loginPid']));
		}
		//feature ende
		
		$content = $view->render('index.tmpl');
		
		return $content;
	}

	function init($conf) {
		$this->conf=$conf;
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();
		$this->pi_USER_INT_obj=0; // 0 = cHash (caching with USER obj) ; 1 = never set cHash (no caching with USER_INT)
		$GLOBALS['piObj'] = & $this;
		$GLOBALS['debug'] = true; // disable after development finished

		session_start();
		if ($GLOBALS['debug']) {
			$GLOBALS['TSFE']->set_no_cache();
			error_reporting(E_ALL &~ E_NOTICE);
			ini_set('display_errors', true);
			$GLOBALS['TYPO3_DB']->debugOutput = true;
			$GLOBALS['TYPO3_DB']->store_lastBuiltQuery = true;
		}
	}
}





class tx_dixxingsync_Data {
	private $mappedData = array();
	private $conf;
	private $error;
	private $memo;

	public function init($conf) {
		$this->conf = $conf;
		
		$GLOBALS["TSFE"]->fe_user->fetchSessionData();

		$data = @unserialize($GLOBALS["TSFE"]->fe_user->getKey("ses", "xingsync"));
		if (!$data || $data['provider']['requestProfileUrl'] != $this->conf['requestProfileUrl'] || !$GLOBALS["TSFE"]->fe_user->user['uid']) {
			$this->error = 'nologin';
		} else {
			$table = 'tx_dixeasylogin_identifiers';
			$where = sprintf('identifier = %s %s', $GLOBALS['TYPO3_DB']->fullQuoteStr($data['id'], $table), $GLOBALS['piObj']->cObj->enableFields($table));
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $table, $where);
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
			
			if ($GLOBALS["TSFE"]->fe_user->user['uid'] != $row['user']) {
				$this->error = 'hijacking';
			}
			
			if (!$this->error) {
				$this->mapData($this->conf['map.'], $data['userData']);
				$this->convertData(); // um (array)datum in timestamp umzuwandeln etc.
			}
		}
	}
	
	/* Execute transformations on mappedData array */ 
	private function convertData() {
		foreach ($this->mappedData['foreign'] as $table => $values) {
			$this->mappedData['foreign'][$table] = array_values($values); // array-indizes resetten, damit im template das erste array-element durchlaufen werden kann
			
			foreach ($this->mappedData['foreign'][$table] as $j=>$data) {
				foreach ($data as $col=>$val) {
					$fn = $this->conf['tables.'][$table.'.']['convert.'][$col];
					
					if ($fn) { $fn = 'convert_'.$fn; }
					if ($fn && method_exists($this, $fn)) {
						
						$this->mappedData['foreign'][$table][$j][$col] = call_user_func(array($this, $fn), $val);
					}
				}
			}
		}		

		foreach ($this->mappedData['fe_users'] as $col=>$val) {
			$fn = $this->conf['tables.']['fe_users.']['convert.'][$col];
			if ($fn) { $fn = 'convert_'.$fn; }
			
			if ($fn && method_exists($this, $fn)) {
				$this->mappedData['fe_users'][$col] = call_user_func(array($this, $fn), $val, 'fe_users', $col);
			}
		}
	}
	
	/* Convert Xing Birthday fields into unix timestamp*/
	private function convert_xing_birthdate($val, $table, $col) {
		$ts = mktime(0,0,0,$val['month'], $val['day'], $val['year']);
		return $ts;
	}
	
	/* Den z. B. von Xing kommenden 2-stelligen ISO-Code in den 3-stelligen Iso-Code umwandenln*/
	private function convert_country_iso2_to_iso3 ($val, $table, $col) {
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('cn_iso_3', 'static_countries', 'cn_iso_2="'.$val.'"', '', 'uid');
		$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
		return $row['cn_iso_3'];
	}
	
	/* Convert the Xing Date given in format 2005-12 into a unix timestamp */
	private function convert_xing_date_year_month($val, $table=NULL, $col=NULL) {
		if (is_null($val)) {
			return null;
		} else {
			$ts = mktime(0,0,0,intval(substr($val,-2)), 1, intval(substr($val,0,4))); 
		}
		return $ts;
	}
	
	
	private function convert_file($url, $table, $col) {
		t3lib_div::loadTCA($table);
		$fieldConf = $GLOBALS['TCA'][$table]['columns'][$col]['config'];
		$path = parse_url($url, PHP_URL_PATH);
		$pi = pathinfo($path);
		if (!$pi['extension']) { return; }
		if ($fieldConf['allowed'] != '*' && !t3lib_div::inList(strtolower($fieldConf['allowed']), strtolower($pi['extension']))) {
			return; //  Falsches Dateiformat
		}
		if (substr($fieldConf['uploadfolder'], -1) != '/') { $fieldConf['uploadfolder'] .= '/'; }
		$filename = self::getUniqueFilename($fieldConf['uploadfolder'], $pi['basename']);
		t3lib_div::writeFile($fieldConf['uploadfolder'].$filename, t3lib_div::getURL($url));
		return $filename;
	}
	
	public static function getUniqueFilename($dir, $filename) {
		$filename = preg_replace('/[^a-z0-9_\.\-]+/i', '_', $filename);
		$pi = pathinfo($filename);
		$ext = strtolower($pi['extension']);
		$base = isset($pi['filename']) ? $pi['filename'] : substr($pi['basename'], 0, strrpos($pi['basename'], '.'));
		do {
			$newFn = $base.'_'.t3lib_div::shortMD5(microtime(), 4).'.'.$ext;
		} while (file_exists($dir.$newFn));
		return $newFn;
	}
	

	/* Write data from Xing into local mappedData-Array */
	private function mapData($map, $data, $table=null) { // rekursiv
		static $cnt = 99;
		$cnt++; // damit einträge in den arrays nicht überschrieben werden, wenn sie aus mehreren xing-teilstrukturen in eine typo3-tabelle zusammengeführt werden
		$keys = array();
		if (is_array($map)) {
			foreach ($map as $key => $value) {
				$k = trim($key, ".");
				if (in_array($k, $keys)) { continue; }
				$keys[] = $k;

				if ($map[$k.'.']) { // hat unterstrukturen in der mapping-konfiguration -> rekursiver aufruf
					$this->mapData($map[$k.'.'], $data[$k], $map[$k]);
				} else { // keine unterstrukturen. zuordnung 1:1 (fe_user) oder 1:n (fremdtabellen)
					if ($table) { // 1:n zu $table
						if (!$data[0]) { $data = array($data); } // 1:n aus einer 1:1 beziehung machen -> primary_company
						foreach ($data as $i=>$subdata) {
							$this->mappedData['foreign'][$table][$i+$cnt][$map[$k]] = $subdata[$k];
						}
					} else { // 1:1 fe_users
						$this->mappedData['fe_users'][$map[$k]] = $data[$k];
					}
				}
			}
		}
	}
	
	public function import($vars) {
		if (is_array($vars['import_feuser'])) {
			$this->importUser($vars['import_feuser']);
		}
		if (is_array($vars['delete_table'])) {
			foreach ($vars['delete_table'] as $table=>$uids) {
				$this->deleteForeign($table, $uids);
			}
		}
		if (is_array($vars['import_table'])) {
			foreach ($vars['import_table'] as $table=>$uids) {
				$this->importForeign($table, $uids);
			}
		}

		//feature: Schreibe in Network News, ist als Hook umgesetzt
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['dix_xingsync']['hook_synclog'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['dix_xingsync']['hook_synclog'] as $_classRef) {
				$_procObj = &t3lib_div::getUserObj($_classRef);
				$_procObj->process($vars, $this);
			}
		}
	}

	private function importUser($vars) {
		$values = array();
		foreach ($vars as $col) {
			$v = $this->mappedData['fe_users'][$col];

			$fn = $this->conf['tables.']['fe_users.']['convert_import.'][$col];
			if ($fn) { $fn = 'convert_'.$fn; }
			if ($fn && method_exists($this, $fn)) {
				$v = call_user_func(array($this, $fn), $v, 'fe_users', $col);
			}

			$values[$col] = $v;
		}
		if (!count($values)) { return; }
		$values['tstamp'] = time();
		$where = sprintf('uid = %d', $GLOBALS["TSFE"]->fe_user->user['uid']);
		$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery('fe_users', $where, $values);
	}

	private function deleteForeign($table, $uids) {
		if (!is_array($uids)) { return; }
		array_walk($uids, create_function('&$val', '$val = intval($val);'));
		$where = sprintf('uid in (%s)', join(',', $uids));
		$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery($table, $where, array('deleted'=>1, 'tstamp'=>time()));
	}

	private function importForeign($table, $uids) {
		if (!is_array($uids)) { return; }
		$lConf = $this->conf['tables.'][$table.'.'];
		foreach ($uids as $i) {
			$values = $this->mappedData['foreign'][$table][$i];

			foreach ($values as $col=>$v) {
				$fn = $this->conf['tables.'][$table.'.']['convert_import.'][$col];
				if ($fn) { $fn = 'convert_'.$fn; }
				if ($fn && method_exists($this, $fn)) {
					$values[$col] = call_user_func(array($this, $fn), $v, $table, $col);
				}
			}

			$values['pid'] = $lConf['pid'];
			$values['crdate'] = time();
			$values['tstamp'] = time();
			$values[$lConf['userIDfield']] = $GLOBALS["TSFE"]->fe_user->user['uid'];
			
			$res = $GLOBALS['TYPO3_DB']->exec_INSERTquery($table, $values);
		}
	}
	
	private function getUserTables() {
		$result = array();
		foreach ($this->conf['tables.'] as $tbl=>$tblConf) {
			$table = trim($tbl, '.');
			if ($table == 'fe_users') { continue; }
			$sorting = $tblConf['sorting'] ? $tblConf['sorting'] : 'uid';
			$where = sprintf('%s = %d %s', $GLOBALS['TYPO3_DB']->quoteStr($tblConf['userIDfield'],$table), $GLOBALS["TSFE"]->fe_user->user['uid'], $GLOBALS['piObj']->cObj->enableFields($table));
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $table, $where, '', $sorting);
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				$result[$table][] = $row;
			}
		}
		return $result;
	}
	
	public function getValue() {
		 return array(
			'error' => $this->error,
			'mappedData' => $this->mappedData,
			'usertables' => $this->getUserTables(),
		);
	}
}



if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/dix_xingsync/pi1/class.tx_dixxingsync_pi1.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/dix_xingsync/pi1/class.tx_dixxingsync_pi1.php']);
}

?>