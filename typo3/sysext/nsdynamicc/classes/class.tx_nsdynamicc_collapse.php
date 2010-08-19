<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009-2010 Xavier Perseguers <typo3@perseguers.ch>
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
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

// TODO remove the include once the autoloader is in place
//require_once ($BACK_PATH.'class.db_list.inc');
//require_once ($BACK_PATH.'class.db_list_extra.inc');
//$BE_USER->modAccess($MCONF,1);
/**
 * Class that renders fields for the Extension Manager configuration.
 *
 * $Id: class.tx_nsdynamicc_collapse.php 28572 2010-01-08 17:13:29Z xperseguers $
 * @author Nuwan Sameera <nuwan28@gmail.com>
 *
 * @package TYPO3
 * @subpackage dbal
 */
class tx_nsdynamicc_collapse {
	var $collapseTitle; // the variable define to change collapse icon title to be changed by ajax
/**
 * Ajax respons for Create the selector box for selecting fields to display from a table:
 * 
 */
	function updateAjaxCollapse(array $params, TYPO3AJAX $ajaxObj){
		global $TCA, $LANG;
		// Get configuration of collapsed tables from user uc and merge with sanitized GP vars
		$this->tablesCollapsed = is_array($GLOBALS['BE_USER']->uc['moduleData']['db_list.php']) ? $GLOBALS['BE_USER']->uc['moduleData']['db_list.php'] : array();		
    // get the params 		
		$collapseOverride = t3lib_div::_GP('collapse');
		
		if (is_array($collapseOverride)) {				
			foreach($collapseOverride as $collapseTable => $collapseValue) {
				if (is_array($GLOBALS['TCA'][$collapseTable]) && ($collapseValue == 0 || $collapseValue == 1)) {
					$this->tablesCollapsed[$collapseTable] = $collapseValue;
								//set the collapse icon title description
			$this->collapseTitle = ($collapseValue == 1) ? $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.expandTable', TRUE) : $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.collapseTable', TRUE); 
				}
			}
			// Save modified user uc
			$GLOBALS['BE_USER']->uc['moduleData']['db_list.php'] = $this->tablesCollapsed;
			$GLOBALS['BE_USER']->writeUC($GLOBALS['BE_USER']->uc);
			
			//success message
			$ajaxObj->addContent('response',$this->collapseTitle);
			//$ajaxObj->setContentFormat('json');		
		}
	}

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/nsdynamicc/classes/class.tx_nsdynamicc_collapse.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/nsdynamicc/classes/class.tx_nsdynamicc_collapse.php']);
}

?>