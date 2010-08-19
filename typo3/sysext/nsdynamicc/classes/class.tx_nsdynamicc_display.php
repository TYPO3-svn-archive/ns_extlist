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
//unset($MCONF);
require_once (PATH_typo3.'class.db_list.inc');
require_once (PATH_typo3.'class.db_list_extra.inc');
//$BE_USER->modAccess($MCONF,1);
/**
 * Class that renders fields for the Extension Manager configuration.
 *
 * $Id: class.tx_dbal_tsparserext.php 28572 2010-01-08 17:13:29Z xperseguers $
 * @author Nuwan Sameera <nuwan28@gmail.com>
 *
 * @package TYPO3
 * @subpackage dbal
 */
class tx_nsdynamicc_display {
	
	/**
	 * Add dynamic_column.js file to the extenstion
	 * 	
	 */
	public function preStartPageHook($parameters, $pObj) {
		//insert javascript file and css in  to the document header
		$pObj->getPageRenderer()->loadExtJS();		
		$pObj->addStyleSheet('tx_nsdynamicc', t3lib_extMgm::extRelPath('nsdynamicc').'res/column_styles.css');
		$pObj->loadJavascriptLib(t3lib_extMgm::extRelPath("nsdynamicc").'scripts/dynamic_column.js');		
	}
	
	/**
	 * Ajax respons for Create the selector box for selecting fields to display from a table:
	 * 
   */
	function createAjaxSelector(array $params, TYPO3AJAX $ajaxObj){
		global $TCA, $LANG;		
    // get the params 		
		$id = t3lib_div::_GET('id');
		$table = t3lib_div::_GET('table');
		
		//create select box
		$selectorList = $this->fieldSelectBox($id,$table);
    /*if (empty($this->tree)) {
        $ajaxObj->setError('An error occurred');
    } else  {*/
			// the content is an array that can be set through $key / $value pairs as parameter
			$ajaxObj->addContent('tree', $selectorList);
			//$ajaxObj->setContentFormat('json');
    //}
	}

		/**
	 * Create the selector box for selecting fields to display from a table:
	 *
	 * @param	string		Table name
	 * @param	boolean		If true, form-fields will be wrapped around the table.
	 * @return	string		HTML table with the selector box (name: displayFields['.$table.'][])
	 */
	function fieldSelectBox($id,$table,$formFields=1)	{
		global $TCA, $LANG;
		
		//Initialize the dblist object:
		$dblist = t3lib_div::makeInstance('localRecordList');
		//Init
		//t3lib_div::loadTCA($table);
		$formElements=array('','');
		if ($formFields)	{
			$formElements=array('<form action="'.htmlspecialchars($dblist->listURL($id,$table)).'&db_list.php?id='.$id.'&table=" method="post">','</form>');
		}
		
		$dblist_test = t3lib_div::makeInstance('ux_localRecordList');
		$dblist_test->setDispFields();
				
		// Load already selected fields, if any:			
		$setFields=is_array($dblist_test->setFields[$table]) ? $dblist_test->setFields[$table] : array();
		
		// Request fields from table:
		$fields = $dblist->makeFieldList($table, false, true);
		
		// Add pseudo "control" fields
		$fields[]='_PATH_';
		$fields[]='_REF_';
		$fields[]='_LOCALIZATION_';
		$fields[]='_CONTROL_';
		$fields[]='_CLIPBOARD_';

			// Create an option for each field:
		$opt = array();
		//$opt[] = '<option value=""></option>';
		foreach($fields as $fN)	{
			$fL = is_array($TCA[$table]['columns'][$fN]) ? rtrim($LANG->sL($TCA[$table]['columns'][$fN]['label']),':') : '['.$fN.']';	// Field label
			
			$opt[] = '<div><input type="checkbox" id="dc_'.$fN.'" name="displayFields['.$table.'][]" value="'.$fN.'"'.(in_array($fN,$setFields)?' checked="yes"':'').' /><label for="dc_'.$fN.'">'.htmlspecialchars($fL).'</label></div>';
		}

			// Compile the options into a multiple selector box:
		//$lMenu = '<select size="'.t3lib_div::intInRange(count($fields)+1,3,20).'" multiple="multiple" name="displayFields['.$table.'][]">'.implode('',$opt).'</select>';
		//get the list of check boxes
		$lMenu = '<div class="clist">'.implode('',$opt).'</div>';

			// Table with the field selector::
		$content.= '
			'.$formElements[0].'
				<!--
					Field selector for extended table view:
				-->
				<div class="selector-container">'.$lMenu.'</div>
				<div class="selector-submit-button">					
					<input type="submit" name="search" value="'.$LANG->sL('LLL:EXT:lang/locallang_core.php:labels.setFields',1).'" />
				</div>
			'.$formElements[1];

		return $content;
	}
	
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/nsdynamicc/classes/class.tx_nsdynamicc_display.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/nsdynamicc/classes/class.tx_nsdynamicc_display.php']);
}

?>