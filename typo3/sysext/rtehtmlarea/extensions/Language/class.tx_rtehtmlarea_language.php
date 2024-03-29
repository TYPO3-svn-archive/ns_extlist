<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008-2010 Stanislas Rolland <typo3(arobas)sjbr.ca>
*  All rights reserved
*
*  This script is part of the Typo3 project. The Typo3 project is
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
/**
 * Language plugin for htmlArea RTE
 *
 * @author Stanislas Rolland <typo3(arobas)sjbr.ca>
 *
 * TYPO3 SVN ID: $Id: class.tx_rtehtmlarea_language.php 7271 2010-04-10 20:09:15Z stan $
 *
 */

require_once(t3lib_extMgm::extPath('rtehtmlarea').'class.tx_rtehtmlareaapi.php');

class tx_rtehtmlarea_language extends tx_rtehtmlareaapi {

	protected $extensionKey = 'rtehtmlarea';	// The key of the extension that is extending htmlArea RTE
	protected $pluginName = 'Language';		// The name of the plugin registered by the extension
	protected $relativePathToLocallangFile = 'extensions/Language/locallang.xml';	// Path to this main locallang file of the extension relative to the extension dir.
	protected $relativePathToSkin = 'extensions/Language/skin/htmlarea.css';		// Path to the skin (css) file relative to the extension dir.
	protected $htmlAreaRTE;				// Reference to the invoking object
	protected $thisConfig;				// Reference to RTE PageTSConfig
	protected $toolbar;				// Reference to RTE toolbar array
	protected $LOCAL_LANG; 				// Frontend language array

	protected $pluginButtons = 'lefttoright,righttoleft,language,showlanguagemarks';
	protected $convertToolbarForHtmlAreaArray = array (
		'lefttoright'			=> 'LeftToRight',
		'righttoleft'			=> 'RightToLeft',
		'language'			=> 'Language',
		'showlanguagemarks'		=> 'ShowLanguageMarks',
		);

	public function main($parentObject) {
		if (!t3lib_extMgm::isLoaded('static_info_tables')) {
			$this->pluginButtons = t3lib_div::rmFromList('language', $this->pluginButtons);
		} else {
			require_once(t3lib_extMgm::extPath('static_info_tables').'class.tx_staticinfotables_div.php');
		}
		return parent::main($parentObject);
	}

	/**
	 * Return JS configuration of the htmlArea plugins registered by the extension
	 *
	 * @param	integer		Relative id of the RTE editing area in the form
	 *
	 * @return string		JS configuration for registered plugins
	 *
	 * The returned string will be a set of JS instructions defining the configuration that will be provided to the plugin(s)
	 * Each of the instructions should be of the form:
	 * 	RTEarea['.$RTEcounter.'].buttons.button-id.property = "value";
	 */
	public function buildJavascriptConfiguration($RTEcounter) {
		$button = 'language';
		$registerRTEinJavascriptString = '';
		if (in_array($button, $this->toolbar)) {
			if (!is_array( $this->thisConfig['buttons.']) || !is_array($this->thisConfig['buttons.'][$button . '.'])) {
				$registerRTEinJavascriptString .= '
			RTEarea['.$RTEcounter.'].buttons.'. $button .' = new Object();';
			}
			if ($this->htmlAreaRTE->is_FE()) {
				$first = $GLOBALS['TSFE']->getLLL('No language mark',$this->LOCAL_LANG);
			} else {
				$first = $GLOBALS['LANG']->getLL('No language mark');
			}
			$languages = array('none' => $first);
			$languages = array_flip(array_merge($languages, $this->getLanguages()));
			$languagesJSArray = array();
			foreach ($languages as $key => $value) {
				$languagesJSArray[] = array('text' => $key, 'value' => $value);
			}
			$languagesJSArray = json_encode(array('options' => $languagesJSArray));
			$registerRTEinJavascriptString .= '
			RTEarea['.$RTEcounter.'].buttons.'. $button .'.dataUrl = "' . $this->htmlAreaRTE->writeTemporaryFile('', $button . '_' . $this->htmlAreaRTE->contentLanguageUid, 'js', $languagesJSArray) . '";';
		}
		return $registerRTEinJavascriptString;
	}
	/**
	 * Getting all languages into an array
	 * 	where the key is the ISO alpha-2 code of the language
	 * 	and where the value are the name of the language in the current language
	 * 	Note: we exclude sacred and constructed languages
	 *
	 * @return	array		An array of names of languages
	 */
	function getLanguages() {
		$where = '1=1';
		$table = 'static_languages';
		$lang = tx_staticinfotables_div::getCurrentLanguage();
		$nameArray = array();
		$titleFields = tx_staticinfotables_div::getTCAlabelField($table, TRUE, $lang);
		$prefixedTitleFields = array();
		foreach ($titleFields as $titleField) {
			$prefixedTitleFields[] = $table.'.'.$titleField;
		}
		$labelFields = implode(',', $prefixedTitleFields);
			// Restrict to certain languages
		if (is_array($this->thisConfig['buttons.']) && is_array($this->thisConfig['buttons.']['language.']) && isset($this->thisConfig['buttons.']['language.']['restrictToItems'])) {
			$languageList = implode("','", t3lib_div::trimExplode(',', $GLOBALS['TYPO3_DB']->fullQuoteStr(strtoupper($this->thisConfig['buttons.']['language.']['restrictToItems']), $table)));
			$where .= ' AND '. $table . '.lg_iso_2 IN (' . $languageList . ')';
		}
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			$table.'.lg_iso_2,'.$table.'.lg_country_iso_2,'.$labelFields,
			$table,
			$where.' AND lg_constructed = 0 '.
			($this->htmlAreaRTE->is_FE() ? $GLOBALS['TSFE']->sys_page->enableFields($table) : t3lib_BEfunc::BEenableFields($table) .  t3lib_BEfunc::deleteClause($table))
			);
		$prefixLabelWithCode = !$this->thisConfig['buttons.']['language.']['prefixLabelWithCode'] ? false : true;
		$postfixLabelWithCode = !$this->thisConfig['buttons.']['language.']['postfixLabelWithCode'] ? false : true;
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$code = strtolower($row['lg_iso_2']).($row['lg_country_iso_2']?'-'.strtoupper($row['lg_country_iso_2']):'');
			foreach ($titleFields as $titleField) {
				if ($row[$titleField]) {
					$nameArray[$code] = $this->htmlAreaRTE->is_FE() ? $GLOBALS['TSFE']->csConv($row[$titleField], $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['static_info_tables']['charset']) : ($this->htmlAreaRTE->TCEform->inline->isAjaxCall ? $GLOBALS['LANG']->csConvObj->utf8_encode($row[$titleField], $GLOBALS['LANG']->charSet) : $row[$titleField]);
					$nameArray[$code] = $prefixLabelWithCode ? ($code . ' - ' . $nameArray[$code]) : ($postfixLabelWithCode ? ($nameArray[$code] . ' - ' . $code) : $nameArray[$code]);
					break;
				}
			}
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($res);
		uasort($nameArray, 'strcoll');
		return $nameArray;
	}

	/**
	 * Return an updated array of toolbar enabled buttons
	 *
	 * @param	array		$show: array of toolbar elements that will be enabled, unless modified here
	 *
	 * @return 	array		toolbar button array, possibly updated
	 */
	public function applyToolbarConstraints($show) {
		if (!t3lib_extMgm::isLoaded('static_info_tables')) {
			return array_diff($show, array('language'));
		} else {
			return $show;
		}
	}
}
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/rtehtmlarea/extensions/Language/class.tx_rtehtmlarea_language.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/rtehtmlarea/extensions/Language/class.tx_rtehtmlarea_language.php']);
}
?>