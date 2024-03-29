<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005-2010 Stanislas Rolland <typo3(arobas)sjbr.ca>
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
 * Front end RTE based on htmlArea
 *
 * @author Stanislas Rolland <typo3(arobas)sjbr.ca>
 *
 * $Id: class.tx_rtehtmlarea_pi2.php 7703 2010-05-27 21:23:59Z stan $  *
 */
require_once(t3lib_extMgm::extPath('rtehtmlarea').'class.tx_rtehtmlarea_base.php');
class tx_rtehtmlarea_pi2 extends tx_rtehtmlarea_base {

		// External:
	public $RTEWrapStyle = '';				// Alternative style for RTE wrapper <div> tag.
	public $RTEdivStyle = '';				// Alternative style for RTE <div> tag.

		// For the editor
	var $elementId;
	var $elementParts;
	var $tscPID;
	var $typeVal;
	var $thePid;
	var $RTEsetup = array();
	var $thisConfig = array();
	var $confValues;
	public $language;
	public $OutputCharset;
	var $specConf;
	var $LOCAL_LANG;

	/**
	 * Draws the RTE as an iframe
	 *
	 * @param	object		Reference to parent object, which is an instance of the TCEforms.
	 * @param	string		The table name
	 * @param	string		The field name
	 * @param	array		The current row from which field is being rendered
	 * @param	array		Array of standard content for rendering form fields from TCEforms. See TCEforms for details on this. Includes for instance the value and the form field name, java script actions and more.
	 * @param	array		"special" configuration - what is found at position 4 in the types configuration of a field from record, parsed into an array.
	 * @param	array		Configuration for RTEs; A mix between TSconfig and otherwise. Contains configuration for display, which buttons are enabled, additional transformation information etc.
	 * @param	string		Record "type" field value.
	 * @param	string		Relative path for images/links in RTE; this is used when the RTE edits content from static files where the path of such media has to be transformed forth and back!
	 * @param	integer		PID value of record (true parent page id)
	 * @return	string		HTML code for RTE!
	 */
	function drawRTE($parentObject,$table,$field,$row,$PA,$specConf,$thisConfig,$RTEtypeVal,$RTErelPath,$thePidValue) {
		global $TSFE, $TYPO3_CONF_VARS, $TYPO3_DB;

		$this->TCEform = $parentObject;
		$this->client = $this->clientInfo();
		$this->typoVersion = t3lib_div::int_from_ver(TYPO3_version);

		/* =======================================
		 * INIT THE EDITOR-SETTINGS
		 * =======================================
		 */
			// Get the path to this extension:
		$this->extHttpPath = t3lib_extMgm::siteRelPath($this->ID);
			// Get the site URL
		$this->siteURL = $GLOBALS['TSFE']->absRefPrefix ? $GLOBALS['TSFE']->absRefPrefix : '';
			// Get the host URL
		$this->hostURL = '';
			// Element ID + pid
		$this->elementId = $PA['itemFormElName'];
		$this->elementParts[0] = $table;
		$this->elementParts[1] = $row['uid'];
		$this->tscPID = $thePidValue;
		$this->thePid = $thePidValue;

			// Record "type" field value:
		$this->typeVal = $RTEtypeVal; // TCA "type" value for record

			// RTE configuration
		$pageTSConfig = $TSFE->getPagesTSconfig();
		if (is_array($pageTSConfig) && is_array($pageTSConfig['RTE.'])) {
			$this->RTEsetup = $pageTSConfig['RTE.'];
		}

		if (is_array($thisConfig) && !empty($thisConfig)) {
			$this->thisConfig = $thisConfig;
		} else if (is_array($this->RTEsetup['default.']) && is_array($this->RTEsetup['default.']['FE.'])) {
			$this->thisConfig = $this->RTEsetup['default.']['FE.'];
		}

			// Special configuration (line) and default extras:
		$this->specConf = $specConf;

		if ($this->thisConfig['forceHTTPS']) {
			$this->extHttpPath = preg_replace('/^(http|https)/', 'https', $this->extHttpPath);
			$this->siteURL = preg_replace('/^(http|https)/', 'https', $this->siteURL);
			$this->hostURL = preg_replace('/^(http|https)/', 'https', $this->hostURL);
		}
		/* =======================================
		 * LANGUAGES & CHARACTER SETS
		 * =======================================
		 */
			// Language
		$TSFE->initLLvars();
		$this->language = $TSFE->lang;
		$this->LOCAL_LANG = t3lib_div::readLLfile('EXT:' . $this->ID . '/locallang.xml', $this->language);
		if ($this->language == 'default' || !$this->language)	{
			$this->language = 'en';
		}
		$this->contentLanguageUid = ($row['sys_language_uid'] > 0) ? $row['sys_language_uid'] : 0;
		if (t3lib_extMgm::isLoaded('static_info_tables')) {
			if ($this->contentLanguageUid) {
				$tableA = 'sys_language';
				$tableB = 'static_languages';
				$languagesUidsList = $this->contentLanguageUid;
				$selectFields = $tableA . '.uid,' . $tableB . '.lg_iso_2,' . $tableB . '.lg_country_iso_2,' . $tableB . '.lg_typo3';
				$tableAB = $tableA . ' LEFT JOIN ' . $tableB . ' ON ' . $tableA . '.static_lang_isocode=' . $tableB . '.uid';
				$whereClause = $tableA . '.uid IN (' . $languagesUidsList . ') ';
				$whereClause .= t3lib_BEfunc::BEenableFields($tableA);
				$whereClause .= t3lib_BEfunc::deleteClause($tableA);
				$res = $TYPO3_DB->exec_SELECTquery($selectFields, $tableAB, $whereClause);
				while($languageRow = $TYPO3_DB->sql_fetch_assoc($res)) {
					$this->contentISOLanguage = strtolower(trim($languageRow['lg_iso_2']).(trim($languageRow['lg_country_iso_2'])?'_'.trim($languageRow['lg_country_iso_2']):''));
					$this->contentTypo3Language = strtolower(trim($languageRow['lg_typo3']));
				}
			} else {
				$this->contentISOLanguage = $GLOBALS['TSFE']->sys_language_isocode ? $GLOBALS['TSFE']->sys_language_isocode : 'en';
				$selectFields = 'lg_iso_2, lg_typo3';
				$tableAB = 'static_languages';
				$whereClause = 'lg_iso_2 = ' . $TYPO3_DB->fullQuoteStr(strtoupper($this->contentISOLanguage), $tableAB);
				$res = $TYPO3_DB->exec_SELECTquery($selectFields, $tableAB, $whereClause);
				while($languageRow = $TYPO3_DB->sql_fetch_assoc($res)) {
					$this->contentTypo3Language = strtolower(trim($languageRow['lg_typo3']));
				}
			}
		}
		$this->contentISOLanguage = $this->contentISOLanguage ? $this->contentISOLanguage : ($GLOBALS['TSFE']->sys_language_isocode ? $GLOBALS['TSFE']->sys_language_isocode : 'en');
		$this->contentTypo3Language = $this->contentTypo3Language ? $this->contentTypo3Language : $GLOBALS['TSFE']->lang;
		if ($this->contentTypo3Language == 'default') {
			$this->contentTypo3Language = 'en';
		}

			// Character set
		$this->charset = $TSFE->renderCharset;
		$this->OutputCharset  = $TSFE->metaCharset ? $TSFE->metaCharset : $TSFE->renderCharset;

			// Set the charset of the content
		$this->contentCharset = $TSFE->csConvObj->charSetArray[$this->contentTypo3Language];
		$this->contentCharset = $this->contentCharset ? $this->contentCharset : 'iso-8859-1';
		$this->contentCharset = trim($TSFE->config['config']['metaCharset']) ? trim($TSFE->config['config']['metaCharset']) : $this->contentCharset;

		/* =======================================
		 * TOOLBAR CONFIGURATION
		 * =======================================
		 */
		$this->initializeToolbarConfiguration();

		/* =======================================
		 * SET STYLES
		 * =======================================
		 */

		$width = 460+($this->TCEform->docLarge ? 150 : 0);
		if (isset($this->thisConfig['RTEWidthOverride'])) {
			if (strstr($this->thisConfig['RTEWidthOverride'], '%')) {
				if ($this->client['BROWSER'] != 'msie') {
					$width = (intval($this->thisConfig['RTEWidthOverride']) > 0) ? $this->thisConfig['RTEWidthOverride'] : '100%';
				}
			} else {
				$width = (intval($this->thisConfig['RTEWidthOverride']) > 0) ? intval($this->thisConfig['RTEWidthOverride']) : $width;
			}
		}
		$RTEWidth = strstr($width, '%') ? $width : $width . 'px';
		$editorWrapWidth = strstr($width, '%') ? $width :  ($width+2) . 'px';
		$height = 380;
		$RTEHeightOverride = intval($this->thisConfig['RTEHeightOverride']);
		$height = ($RTEHeightOverride > 0) ? $RTEHeightOverride : $height;
		$RTEHeight = $height . 'px';
		$editorWrapHeight = ($height+2) . 'px';
		$this->RTEWrapStyle = $this->RTEWrapStyle ? $this->RTEWrapStyle : ($this->RTEdivStyle ? $this->RTEdivStyle : ('height:' . $editorWrapHeight . '; width:'. $editorWrapWidth . ';'));
		$this->RTEdivStyle = $this->RTEdivStyle ? $this->RTEdivStyle : 'position:relative; left:0px; top:0px; height:' . $RTEHeight . '; width:'.$RTEWidth.'; border: 1px solid black;';

		/* =======================================
		 * LOAD JS, CSS and more
		 * =======================================
		 */
		$pageRenderer = $GLOBALS['TSFE']->getPageRenderer();
		$pageRenderer->setBackPath(TYPO3_mainDir);
			// Preloading the pageStyle and including RTE skin stylesheets
		$this->addPageStyle();
		$this->addSkin();
		$pageRenderer->addCssFile($this->siteURL . 't3lib/js/extjs/ux/resize.css');
			// Loading JavaScript files and code
		$pageRenderer->loadExtJs();
		$pageRenderer->enableExtJSQuickTips();
		if (!$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->ID]['enableCompressedScripts']) {
			$pageRenderer->enableExtJsDebug();
		}
		$pageRenderer->addJsFile($this->siteURL . 't3lib/js/extjs/ux/ext.resizable.js');
		if ($this->TCEform->RTEcounter == 1) {
			$this->TCEform->additionalJS_pre['rtehtmlarea-loadJScode'] = $this->loadJScode($this->TCEform->RTEcounter);
		}
		$this->TCEform->additionalJS_initial = $this->loadJSfiles($this->TCEform->RTEcounter);
		$resizableSettings = array(
			'textareaResize' => true,
			'textareaMaxHeight' => '600'
		);
		$pageRenderer->addInlineSettingArray('', $resizableSettings);

		/* =======================================
		 * DRAW THE EDITOR
		 * =======================================
		 */
			// Transform value:
		$value = $this->transformContent('rte',$PA['itemFormElValue'],$table,$field,$row,$specConf,$thisConfig,$RTErelPath,$thePidValue);

			// Further content transformation by registered plugins
		foreach ($this->registeredPlugins as $pluginId => $plugin) {
			if ($this->isPluginEnabled($pluginId) && method_exists($plugin, "transformContent")) {
				$value = $plugin->transformContent($value);
			}
		}

			// Register RTE windows:
		$this->TCEform->RTEwindows[] = $PA['itemFormElName'];
		$textAreaId = htmlspecialchars($PA['itemFormElName']);

			// Register RTE in JS:
		$this->TCEform->additionalJS_post[] = $this->registerRTEinJS($this->TCEform->RTEcounter, '', '', '',$textAreaId);

			// Set the save option for the RTE:
		$this->TCEform->additionalJS_submit[] = $this->setSaveRTE($this->TCEform->RTEcounter, $this->TCEform->formName, $textAreaId);

			// draw the textarea
		$item = $this->triggerField($PA['itemFormElName']).'
			<div id="pleasewait' . $textAreaId . '" class="pleasewait" style="display: block;" >' . $TSFE->csConvObj->conv($TSFE->getLLL('Please wait',$this->LOCAL_LANG), $this->charset, $TSFE->renderCharset) . '</div>
			<div id="editorWrap' . $textAreaId . '" class="editorWrap" style="visibility: hidden; '. htmlspecialchars($this->RTEWrapStyle). '">
			<textarea id="RTEarea' . $textAreaId . '" name="'.htmlspecialchars($PA['itemFormElName']).'" style="'.htmlspecialchars($this->RTEdivStyle).'">'.t3lib_div::formatForTextarea($value).'</textarea>
			</div>' . ($TYPO3_CONF_VARS['EXTCONF'][$this->ID]['enableDebugMode'] ? '<div id="HTMLAreaLog"></div>' : '') . '
			';
		return $item;
	}

	/**
	 * Add style sheet file to document header
	 *
	 * @param	string		$key: some key identifying the style sheet
	 * @param	string		$href: uri to the style sheet file
	 * @param	string		$title: value for the title attribute of the link element
	 * @return	string		$relation: value for the rel attribute of the link element
	 * @return	void
	 */
	protected function addStyleSheet($key, $href, $title='', $relation='stylesheet') {
		$pageRenderer = $GLOBALS['TSFE']->getPageRenderer();
		$pageRenderer->addCssFile($href, $relation, 'screen', $title);
	}
	/**
	 * Return true if we are in the FE, but not in the FE editing feature of BE.
	 *
	 * @return boolean
	 */
	function is_FE() {
		return true;
	}
	/**
	 * Return the JS-Code for copy the HTML-Code from the editor in the hidden input field.
	 * This is for submit function from the form.
	 *
	 * @param	integer		$RTEcounter: The index number of the RTE editing area.
	 * @param	string		$form: the name of the form
	 * @param	string		$textareaId: the id of the textarea
	 *
	 * @return	string		the JS-Code
	 */
	function setSaveRTE($RTEcounter, $form, $textareaId) {
		return '
		if (RTEarea[\'' . $textareaId . '\'] && !RTEarea[\'' . $textareaId . '\'].deleted) {
			fields = document.getElementsByName(\'' . $textareaId . '\');
			field = fields.item(0);
			if (field && field.nodeName.toLowerCase() == \'textarea\') {
				field.value = RTEarea[\'' . $textareaId . '\'][\'editor\'].getHTML();
			}
		} else {
			OK = 0;
		}';
	}
}
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/rtehtmlarea/pi2/class.tx_rtehtmlarea_pi2.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/rtehtmlarea/pi2/class.tx_rtehtmlarea_pi2.php']);
}
?>