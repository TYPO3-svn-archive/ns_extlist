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

/**
 * Class that save data items after data table sroting using Ajax functionality 
 * @author Nuwan Sameera <nuwan28@gmail.com> 
 * @package TYPO3 
 */

// Declare the interface interface
require_once(PATH_typo3.'interfaces/interface.localrecordlist_actionsHook.php');

 
class tx_nsdynamicc_sort implements localRecordList_actionsHook {
	
		var $cmd;			// Command array on the form [tablename][uid][command] = value. This array may get additional data set internally based on clipboard commands send in CB var!
		var $prErr;			// Boolean. If set, errors will be printed on screen instead of redirection. Should always be used, otherwise you will see no errors if they happen.
		var $vC;			// Verification code
		var $uPT;			// Boolean. Update Page Tree Trigger. If set and the manipulated records are pages then the update page tree signal will be set.
		
	/**
	 * Add sortable.js files to the extenstion
	 * 	
	 */
	public function preStartPageHook($parameters, $pObj) {
		//insert javascript code in document header
		$pObj->loadJavascriptLib(t3lib_extMgm::extRelPath("nsdynamicc").'scripts/sortable.js');
	}
	/**
	 * Ajax functionality for movement of data table row items
	 */
	function movementSort(array $params, TYPO3AJAX $ajaxObj){
		global $BE_USER, $TYPO3_CONF_VARS;;
		
		// get the Get params 		
		$this->cmd = t3lib_div::_GP('cmd');		
		$this->prErr = t3lib_div::_GP('prErr');
		$this->vC = t3lib_div::_GP('vC');
		$this->uPT = t3lib_div::_GP('uPT');

				
		// Creating TCEmain object
		$this->tce = t3lib_div::makeInstance('t3lib_TCEmain');
		$this->tce->stripslashes_values=0;
		// LOAD TCEmain with data and cmd arrays:
		$this->tce->start($this->data,$this->cmd);

		// Checking referer / executing
		$refInfo=parse_url(t3lib_div::getIndpEnv('HTTP_REFERER'));
		$httpHost = t3lib_div::getIndpEnv('TYPO3_HOST_ONLY');
		
		if ($httpHost!=$refInfo['host'] && $this->vC != $BE_USER->veriCode() && !$TYPO3_CONF_VARS['SYS']['doNotCheckReferer'])	{
			$this->tce->log('',0,0,0,1,'Referer host "%s" and server host "%s" did not match and veriCode was not valid either!',1,array($refInfo['host'],$httpHost));
			//out put message error
			$ajaxObj->addContent('response',false);
			$ajaxObj->setContentFormat('json');
			
		} else {
			// Execute actions:			
			$this->tce->process_cmdmap();

			// Clearing cache:
			$this->tce->clear_cacheCmd($this->cacheCmd);

			// Update page tree?
			if ($this->uPT && (isset($this->data['pages'])||isset($this->cmd['pages'])))	{
				t3lib_BEfunc::setUpdateSignal('updatePageTree');
			}
			
			//out put message
			$ajaxObj->addContent('response',true);
			$ajaxObj->setContentFormat('json');			
		}
	}
	
	/**
	 * modifies Web>List clip icons (copy, cut, paste, etc.) of a displayed row
	 */
	public function makeClip($table, $row, $cells, &$parentObject){
		return $cells;
	}
	
	/**
	 * modifies Web>List header row columns/cells
	 */ 
	public function renderListHeader($table, $currentIdList, $headerColumns, &$parentObject){
		return $headerColumns;
	}
	
	/**
	 * modifies Web>List header row clipboard/action icons
	 */
	public function renderListHeaderActions($table, $currentIdList, $cells, &$parentObject){
		return $cells;
	}


	/**
	 * use hooks to Creates the control panel for a single record in the listing.
	 * @usage to remove mov up/down This array contains values for the icons/actions generated for each record in Web>List.
	 */ 
	public function makeControl($table, $row, $cells, &$parentObject){
		global $TCA, $LANG, $SOBE, $TYPO3_CONF_VARS;
		//here remove the moveUp and moveDown form control icon		
		unset($cells['moveUp'],$cells['moveDown']);
		$cells['quick-edit'] = '<a class="quick-edit" href="#">Quick Edit</a>';
		return $cells;
	}
	
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/nsdynamicc/classes/class.tx_nsdynamicc_sort.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/nsdynamicc/classes/class.tx_nsdynamicc_sort.php']);
}

?>