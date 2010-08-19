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
 * Class that renders help message model windows using Ajax functionality 
 * @author Nuwan Sameera <nuwan28@gmail.com> 
 * @package TYPO3 
 */
class tx_nsdynamicc_helptips {
	
	/**
	 * Hook-function: inject t3editor JavaScript code before the page is compiled
	 * called in typo3/template.php:startPage
	 *
	 * @param array $parameters
	 * @param template $pObj
	 */
	public function preStartPageHook($parameters, $pObj) {
		//insert javascript code in document header
		$pObj->loadJavascriptLib(t3lib_extMgm::extRelPath("nsdynamicc").'scripts/qtips.js');
	}
	
	
	public function cshItemHook($table, $field, $BACK_PATH, $wrap = '', $onlyIconMode = FALSE, $styleAttrib = '', &$reference){
		global $TCA_DESCR, $LANG, $BE_USER;
		
		if ($BE_USER->uc['edit_showFieldHelp']) {
			$LANG->loadSingleTableDescription($table);      

			if (is_array($TCA_DESCR[$table])) {
				$fullText = $this->helpText($table, $field, $BACK_PATH, '');
				$icon = $this->helpTextIcon($table, $field, $BACK_PATH);				
				
				//debug($BE_USER->uc['edit_showFieldHelp']);
				if ($fullText && !$onlyIconMode && $BE_USER->uc['edit_showFieldHelp'] == 'text')	{         
					// Additional styles?
					$params = $styleAttrib ? ' style="'.$styleAttrib.'"' : '';

					// Compile table with CSH information:
					$fullText = '<table border="0" cellpadding="0" cellspacing="0" class="typo3-csh-inline"'.$params.'>
					<tr>
					<td valign="top" width="14">'.$icon.'</td>
					<td valign="top">'.$fullText.'</td>
					</tr>
					</table>';

					$output = $LANG->hscAndCharConv($fullText, false);
				} else {
							$output = $icon;
					if ($output && $wrap) {
							$wrParts = explode('|', $wrap);
							$output = $wrParts[0].$output.$wrParts[1];
					}
				}
			}
		}		
		return $output;		
	}
	
	/**
	 * --------------Here overwrite the helpText() in the t3lib_BEfunc Class----------------
	 * Returns CSH help text (description), if configured for.
	 * 	 
	 * @param	string		Table name
	 * @param	string		Field name
	 * @param	string		Back path
	 * @param	string		DEPRECATED: Additional style-attribute content for wrapping table (now: only in function cshItem needed)
	 * @return	string		HTML content for help text
	 */
	
	public function helpText($table, $field, $BACK_PATH, $styleAttrib = '') {
		global $TCA_DESCR, $BE_USER;
		$output = array();

		if (is_array($TCA_DESCR[$table]) && is_array($TCA_DESCR[$table]['columns'][$field])) {
			$data = $TCA_DESCR[$table]['columns'][$field];
			//debug($data);
				// add see also arrow
			if ($data['image_descr'] || $data['seeAlso'] || $data['details'] || $data['syntax']) {
				$output['arrow'] = t3lib_iconWorks::getSpriteIcon('actions-view-go-forward');
				//debug($output['arrow']);
			}
				// add description text
			if ($data['description'] || $arrow) {
				$output['paragraph'] = htmlspecialchars($data['description']);
			}
				// put header before the rest of the text
			if ($data['alttitle']) {
				$output['header'] = htmlspecialchars($data['alttitle']);
				//debug($output['header']);
			}
		}
		return $output;
	}
	
	/**
	 * ------------This method try to overwrite the function helpTextIcon method in t3lib_BEfunc Class -----------
	 * Returns help-text icon if configured for.
	 * TCA_DESCR must be loaded prior to this function and $BE_USER must have 'edit_showFieldHelp' set to 'icon', otherwise nothing is returned
	 * Usage: 6
	 *
	 * @param	string		Table name
	 * @param	string		Field name
	 * @param	string		Back path
	 * @param	boolean		Force display of icon nomatter BE_USER setting for help
	 * @return	string		HTML content for a help icon/text
	 */
	public function helpTextIcon($table, $field, $BACK_PATH, $force = 0) {
		global $TCA_DESCR, $BE_USER;
		$tips_content = array();
		//get the path of help resources 		
    $onClick = $BACK_PATH.'view_help.php?tfID='.($table.'.'.$field);
    
    if (is_array($TCA_DESCR[$table]) && is_array($TCA_DESCR[$table]['columns'][$field]) && (isset($BE_USER->uc['edit_showFieldHelp']) || $force)) {
			if ($BE_USER->uc['edit_showFieldHelp'] == 'icon') {        
				$tips_content = $this->helpText($table, $field, $BACK_PATH, '');
				//debug($tips_content);
				//define the data data- Attributes (HTML 5) to pass help tips				
				$text = '<span style="display:none;"><span class="typo3-csh-inline">'.$GLOBALS['LANG']->hscAndCharConv($text, false).'</span></span>';
			}
			return '<a id="typo3-csh-'.(($field)? $field : $table).'" class="typo3-csh-link" data-header="'.$tips_content['header'].'" data-paragraph="'.$tips_content['paragraph'].'" href="'.htmlspecialchars($onClick).'" >' . t3lib_iconWorks::getSpriteIcon('actions-system-help-open', array('class' => 'typo3-csh-icon')).'</a>';//.$text;
		}
	}
	
	
	public function viewBigHook($table, $rowId, &$reference){
		global $TCA, $LANG;
		//debug($table);
		//change Display information dialog box to Ext modle window
		$viewBig = '<a href="show_item.php?table='.$table.'&uid='.$rowId.'" class="typo3-disply-info" title="'.$LANG->getLL('showInfo', TRUE).'">'.
						t3lib_iconWorks::getSpriteIcon('actions-document-info') .
					'</a>';
		return $viewBig; 
	}
	
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/nsdynamicc/classes/class.tx_nsdynamicc_helptips.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/nsdynamicc/classes/class.tx_nsdynamicc_helptips.php']);
}

?>