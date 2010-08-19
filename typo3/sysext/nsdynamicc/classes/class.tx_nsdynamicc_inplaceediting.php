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
 * Class that renders the tabs for in place content edit 
 * @author Nuwan Sameera <nuwan28@gmail.com> 
 * @package TYPO3 
 */
class tx_nsdynamicc_inplaceediting {

	var $editconf;			// GPvar "edit": Is an array looking approx like [tablename][list-of-ids]=command
	var $tceforms;
	var $R_URL; 
	/** Add quick edit html container to the main page */
	public function pageContent($table, &$reference){
		//global $TCA_DESCR, $LANG;
		//add empty <div> with id name for each record table		
		$output =	"<div id='quick-edit-container' class='x-hide-display'>".$contentEdit."</div>";
			
		return $output;		
	}

	/** genarate content html for each record table id */	
	public function quickContent(array $params, TYPO3AJAX $ajaxObj){
		global $TCA, $LANG;		

		$this->editconf = t3lib_div::_GP('edit');				
		
		//quick edit begning
		if (is_array($this->editconf))	{
			$this->tceforms = t3lib_div::makeInstance('t3lib_TCEforms');
			//print_r($TCA);
			//genarate quick edit form
			$quickEditForm = $this->makeQuickEditForm();
		}		
		
		$contentEdit = "		
			<div class='form-container'>				
					<fieldset class='styled'>
						<div>".$quickEditForm."</div>
					</fieldset>				
			</div>
		";
			
			//out put message
			$ajaxObj->addContent('response',$contentEdit);				
	}
	
	/**
	 * Create the quick edit form with $TCA array
	 * @return	string		HTML form elements wrapped in DIV
	 */
	public function makeQuickEditForm(){
		global $BE_USER,$LANG,$TCA;
		
		//assign varialbes 
		$this->elementsData=array();
		$editForm='';
		
		// Traverse the GPvar quick edit array
		foreach($this->editconf as $table => $conf)	{	// Tables:
					
				// Traverse the keys/comments of each table
				foreach($conf as $qKey => $qcmd)	{
					//get the record id
					$qid = $qKey;
								
								$calcPRec = t3lib_BEfunc::getRecord($table,$qid);
								
								t3lib_BEfunc::fixVersioningPid($table,$calcPRec);
								
								if (is_array($calcPRec))	{
									if ($table=='pages')	{	// If pages:										
										$CALC_PERMS = $BE_USER->calcPerms($calcPRec);
										$hasAccess = $CALC_PERMS&2 ? 1 : 0;
										$deleteAccess = $CALC_PERMS&4 ? 1 : 0;
										$this->viewId = $calcPRec['uid'];
									} else {
										$CALC_PERMS = $BE_USER->calcPerms(t3lib_BEfunc::getRecord('pages',$calcPRec['pid']));	// Fetching pid-record first.
										$hasAccess = $CALC_PERMS&16 ? 1 : 0;
										$deleteAccess = $CALC_PERMS&16 ? 1 : 0;
										$this->viewId = $calcPRec['pid'];
												
											// Adding "&L=xx" if the record being edited has a languageField with a value larger than zero!
										if ($TCA[$table]['ctrl']['languageField'] && $calcPRec[$TCA[$table]['ctrl']['languageField']]>0)	{
											$this->viewId_addParams = '&L='.$calcPRec[$TCA[$table]['ctrl']['languageField']];
										}
									}

										// Check internals regarding access:
									if ($hasAccess)	{
										$hasAccess = $BE_USER->recordEditAccessInternals($table, $calcPRec);
										$deniedAccessReason = $BE_USER->errorMsg;
									}
								} else $hasAccess = 0;
								
							if($hasAccess){								
								$trData = t3lib_div::makeInstance('t3lib_transferData');								
								$trData->addRawData = TRUE;								
								$trData->lockRecords=1;
								$trData->disableRTE = !$BE_USER->isRTE();
								$trData->fetchRecord($table,$qid,$cmd=='new'?'new':'');	// 'new'
								reset($trData->regTableItems_data);
								$rec = current($trData->regTableItems_data);
								
								//this is quick edit panel, So just add additional variable to the "$rec" array. This varialbe may used by hooks at Tceforms class
								$additionalValue = array('customedit' => 1);
								$rec = array_merge($rec, $additionalValue);
								//print_r($rec);
									// Now, render the quick edit form:
								if (is_array($rec))	{

									// Setting variables in TCEforms object:
									$this->tceforms->hiddenFieldList = '';
									$this->tceforms->globalShowHelp = $this->disHelp ? 0 : 1;
									if (is_array($this->overrideVals[$table]))	{
										$this->tceforms->hiddenFieldListArr = array_keys($this->overrideVals[$table]);
									}

										// Register default language labels, if any:
									$this->tceforms->registerDefaultLanguageData($table,$rec);

									// Create form for the record (either specific list of fields or the whole record):
									$panel = '';
									//define the array of columns for quick edit panel contain
									$quickEditColumns = array(
																						'row-1' => array('header', 'header_position', 'header_layout', 'hidden', 'linkToTop'),
																						'row-2' => array('starttime', 'endtime')
																						);
																		
										foreach($quickEditColumns as $rows ){
											$panel .= '<div class="control fields">';
												foreach($rows as $columns){
													if (array_key_exists($columns,$rec))	{										
															$panel.= $this->tceforms->getListedFields($table,$rec,$columns);
													}
												}
											$panel .= '</div>';
										}
									
								}
							}
				}
			}
			
			return $panel;
	}
	
	/**
	 * Hook-function: inject t3editor JavaScript code before the page is compiled
	 * called in typo3/template.php:startPage
	 *
	 * @param array $parameters
	 * @param template $pObj
	 */
	public function preStartPageHook($parameters, $pObj){
		//insert javascript code in document header
		$pObj->loadJavascriptLib(t3lib_extMgm::extRelPath("nsdynamicc").'scripts/tabs-content.js');
	}
	
	
	/**
	 * Hook-function: create custom input fields for quick edit panel Rendering wizards for form fields
	 * 
	 */
	public function customGetSingleField_typeInput($inputId,$cssClasses, $mLgd,&$PA,$itemName, $cssStyle, $column, &$reference){
		$columnData = (($column =='header')? ' data-column="col-title"': '');
		$item ='<input type="text" id="' . $inputId . '" class="' . implode(' ', $cssClasses) . ' quickedit-field"'. $columnData.' style="' . $cssStyle . '" name="'.$PA['itemFormElName'].'" value="'.htmlspecialchars($PA['itemFormElValue']).'" maxlength="'.$mLgd.'" />';	// This is the EDITABLE form field.		
		return $item; 
	}
	

	/**
	 * Hook - customGetSingleField_typeCheck function to create a custom check box to quick edit panel
	*/
	public function customGetSingleField_typeCheck($stylesCheck, $cBName, $cBID, $cBName, $disabled, $c, $thisValue, &$reference){		
		$boxselected = (($thisValue&pow(2,$c))?' checked="checked"':'');		
		$checkbox =	'<input type="checkbox" class="checkbox quickedit-field" value="'.$thisValue.'" name="'.$cBName.'" '.$disabled.$boxselected.' id="'.$cBID.'" class="quickedit-field" />';
		return $checkbox;
	}
	
	/**
	 * Hook - customGetSingleField_typeSelect: create custom select box for quick edit panel form 
	 */
	public function customGetSingleField_typeSelect($selectedStyle, $sID, $selectIconOption, $sName, $disabled, &$reference){
		$selectbox =  '<select' . $selectedStyle . ' id="' . $sID . '" class="quickedit-field" name="' . $sName . '"' . $selectIconOption . $disabled . '>';
		return $selectbox;
	}
	
	/**
	 * Hook - 	customIntoTemplate() function to add custom field template  
	*/
	public function customIntoTemplate(&$reference){
		$fieldTemplate = '<div><label>###FIELD_NAME###</label><span> ###FIELD_ITEM### </span></div>';	// Field template
		return $fieldTemplate;
	}
	
	/**
	 * save the quick edit panel data 
	 */
	public function quickEditSubmit(array $params, TYPO3AJAX $ajaxObj){
		global $TCA, $BE_USER, $LANG;		

		//GPvars specifically for processing:
		$this->data = t3lib_div::_GP('data');
		$this->cmd = t3lib_div::_GP('cmd');
		$this->vC = t3lib_div::_GP('vC');		
		
		// Only options related to $this->data submission are included here.
		$tce = t3lib_div::makeInstance('t3lib_TCEmain');
		$tce->stripslashes_values=0;
		
		// Setting default values specific for the user:
		$TCAdefaultOverride = $BE_USER->getTSConfigProp('TCAdefaults');
		if (is_array($TCAdefaultOverride))	{				
			$tce->setDefaultsFromUserTS($TCAdefaultOverride);
		}
		
				//Loading TCEmain with data:
		$tce->start($this->data,$this->cmd);
		if (is_array($this->mirror)){
			$tce->setMirror($this->mirror);
		}

			// If pages are being edited, we set an instruction about updating the page tree after this operation.
		if (isset($this->data['pages']))	{
			t3lib_BEfunc::setUpdateSignal('updatePageTree');
		}

		// Checking referer / executing
		$refInfo=parse_url(t3lib_div::getIndpEnv('HTTP_REFERER'));
		$httpHost = t3lib_div::getIndpEnv('TYPO3_HOST_ONLY');
		
		if($httpHost ==$refInfo['host'] && $this->vC ==$BE_USER->veriCode()){
			// Perform the saving operation with TCEmain:
			$tce->process_datamap();
			$tce->process_cmdmap();
			//confirm that data has been saved
			$respond = true;
		}
		else{
			$respond = false;
		}
		//return Ajax obj to tabs_content.sj
		$ajaxObj->addContent('response', $respond);				
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/nsdynamicc/classes/class.tx_nsdynamicc_inplaceediting.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/nsdynamicc/classes/class.tx_nsdynamicc_inplaceediting.php']);
}

?>