<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008-2009 Jeff Segars <jeff@webempoweredchurch.org>
*  (c) 2008-2009 David Slayback <dave@webempoweredchurch.org>
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
 * View class for the admin panel in frontend editing.
 *
 * $Id: class.tslib_adminpanel.php 7307 2010-04-12 16:17:20Z benni $
 *
 * @author	Jeff Segars <jeff@webempoweredchurch.org>
 * @author	David Slayback <dave@webempoweredchurch.org>
 * @package TYPO3
 * @subpackage tslib
 */
class tslib_AdminPanel {

	/**
	 * Determines whether the update button should be shown.
	 *
	 * @var	boolean
	 */
	protected $extNeedUpdate = false;

	/**
	 * Force preview?
	 *
	 * @var boolean
	 */
	protected $ext_forcePreview = false;

	/**
	 * Comma separated list of page UIDs to be published.
	 *
	 * @var	string
	 */
	protected $extPublishList = '';

	public function __construct() {
		$this->initialize();
	}

	/*****************************************************
	 *
	 * Admin Panel Configuration/Initialization
	 *
	 ****************************************************/

	/**
	 * Initializes settings for the admin panel.
	 *
	 * @return	void
	 */
	public function initialize() {
		$this->saveConfigOptions();

				// Setting some values based on the admin panel
		$GLOBALS['TSFE']->forceTemplateParsing = $this->extGetFeAdminValue('tsdebug', 'forceTemplateParsing');
		$GLOBALS['TSFE']->displayEditIcons = $this->extGetFeAdminValue('edit', 'displayIcons');
		$GLOBALS['TSFE']->displayFieldEditIcons = $this->extGetFeAdminValue('edit', 'displayFieldIcons');

		if ($this->extGetFeAdminValue('tsdebug', 'displayQueries')) {
			if ($GLOBALS['TYPO3_DB']->explainOutput == 0) {		// do not override if the value is already set in t3lib_db
					// Enable execution of EXPLAIN SELECT queries
				$GLOBALS['TYPO3_DB']->explainOutput = 3;
			}
		}

		if (t3lib_div::_GP('ADMCMD_editIcons')) {
			$GLOBALS['TSFE']->displayFieldEditIcons=1;
			$GLOBALS['BE_USER']->uc['TSFE_adminConfig']['edit_editNoPopup']=1;
		}

		if (t3lib_div::_GP('ADMCMD_simUser')) {
			$GLOBALS['BE_USER']->uc['TSFE_adminConfig']['preview_simulateUserGroup']=intval(t3lib_div::_GP('ADMCMD_simUser'));
			$this->ext_forcePreview = true;
		}

		if (t3lib_div::_GP('ADMCMD_simTime')) {
			$GLOBALS['BE_USER']->uc['TSFE_adminConfig']['preview_simulateDate']=intval(t3lib_div::_GP('ADMCMD_simTime'));
			$this->ext_forcePreview = true;
		}

		if ($GLOBALS['TSFE']->forceTemplateParsing || $GLOBALS['TSFE']->displayEditIcons || $GLOBALS['TSFE']->displayFieldEditIcons) {
			$GLOBALS['TSFE']->set_no_cache();
		}
	}

	/**
	 * Checks if a Admin Panel section ("module") is available for the user. If so, true is returned.
	 *
	 * @param	string		The module key, eg. "edit", "preview", "info" etc.
	 * @return	boolean
	 */
	public function isAdminModuleEnabled($key) {
			// Returns true if the module checked is "preview" and the forcePreview flag is set.
		if ($key=='preview' && $this->ext_forcePreview) {
			return true;
		}

			// If key is not set, only "all" is checked
		if ($GLOBALS['BE_USER']->extAdminConfig['enable.']['all']) {
			return true;
		}

		if ($GLOBALS['BE_USER']->extAdminConfig['enable.'][$key]) {
			return true;
		}
	}

	/**
	 * Saves any change in settings made in the Admin Panel.
	 * Called from index_ts.php right after access check for the Admin Panel
	 *
	 * @return	void
	 */
	public function saveConfigOptions() {
		$input = t3lib_div::_GP('TSFE_ADMIN_PANEL');
		if (is_array($input)) {
				// Setting
			$GLOBALS['BE_USER']->uc['TSFE_adminConfig'] = array_merge(!is_array($GLOBALS['BE_USER']->uc['TSFE_adminConfig']) ? array() : $GLOBALS['BE_USER']->uc['TSFE_adminConfig'], $input);			// Candidate for t3lib_div::array_merge() if integer-keys will some day make trouble...
			unset($GLOBALS['BE_USER']->uc['TSFE_adminConfig']['action']);

				// Actions:
			if ($input['action']['clearCache'] && $this->isAdminModuleEnabled('cache')) {
				$GLOBALS['BE_USER']->extPageInTreeInfo=array();
				$theStartId = intval($input['cache_clearCacheId']);
				$GLOBALS['TSFE']->clearPageCacheContent_pidList($GLOBALS['BE_USER']->extGetTreeList($theStartId, $this->extGetFeAdminValue('cache', 'clearCacheLevels'), 0, $GLOBALS['BE_USER']->getPagePermsClause(1)) . $theStartId);
			}
			if ($input['action']['publish'] && $this->isAdminModuleEnabled('publish')) {
				$theStartId = intval($input['publish_id']);
				$this->extPublishList = $GLOBALS['BE_USER']->extGetTreeList($theStartId, $this->extGetFeAdminValue('publish', 'levels'), 0, $GLOBALS['BE_USER']->getPagePermsClause(1)) . $theStartId;
			}

				// Saving
			$GLOBALS['BE_USER']->writeUC();
		}
		$GLOBALS['TT']->LR = $this->extGetFeAdminValue('tsdebug', 'LR');

		if ($this->extGetFeAdminValue('cache', 'noCache')) {
			$GLOBALS['TSFE']->set_no_cache();
		}

			// Hook for post processing the frontend admin configuration. Added with TYPO3 4.2, so naming is now incorrect but preserves compatibility.
			// @deprecated	since TYPO3 4.3
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tsfebeuserauth.php']['extSaveFeAdminConfig-postProc'])) {
			t3lib_div::deprecationLog('Frontend admin post processing hook extSaveFeAdminConfig-postProc is deprecated since TYPO3 4.3.');
			$_params = array('input' => &$input, 'pObj' => &$this);
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tsfebeuserauth.php']['extSaveFeAdminConfig-postProc'] as $_funcRef) {
				t3lib_div::callUserFunction($_funcRef, $_params, $this);
			}
		}
	}

	/**
	 * Returns the value for a Admin Panel setting. You must specify both the module-key and the internal setting key.
	 *
	 * @param	string		Module key
	 * @param	string		Setting key
	 * @return	string		The setting value
	 */
	public function extGetFeAdminValue($pre, $val='') {
			// Check if module is enabled.
		if ($this->isAdminModuleEnabled($pre)) {
				// Exceptions where the values can be overridden from backend:
				// deprecated
			if ($pre . '_' . $val == 'edit_displayIcons' && $GLOBALS['BE_USER']->extAdminConfig['module.']['edit.']['forceDisplayIcons']) {
				return true;
			}
			if ($pre . '_' . $val == 'edit_displayFieldIcons' && $GLOBALS['BE_USER']->extAdminConfig['module.']['edit.']['forceDisplayFieldIcons']) {
				return true;
			}

				// override all settings with user TSconfig
			if ($GLOBALS['BE_USER']->extAdminConfig['override.'][$pre . '.'][$val] && $val) {
				return $GLOBALS['BE_USER']->extAdminConfig['override.'][$pre . '.'][$val];
			}
			if ($GLOBALS['BE_USER']->extAdminConfig['override.'][$pre]) {
				return $GLOBALS['BE_USER']->extAdminConfig['override.'][$pre];
			}

			$retVal = $val ? $GLOBALS['BE_USER']->uc['TSFE_adminConfig'][$pre . '_' . $val] : 1;

			if ($pre=='preview' && $this->ext_forcePreview) {
				if (!$val) {
					return true;
				} else {
					return $retVal;
				}
			}
				// regular check:
			if ($this->isAdminModuleOpen($pre)) {	// See if the menu is expanded!
				return $retVal;
			}

				// Hook for post processing the frontend admin configuration. Added with TYPO3 4.2, so naming is now incorrect but preserves compatibility.
				// @deprecated	since TYPO3 4.3
			if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tsfebeuserauth.php']['extEditAction-postProc'])) {
				t3lib_div::deprecationLog('Frontend admin post processing hook extEditAction-postProc is deprecated since TYPO3 4.3.');
				$_params = array('cmd' => &$cmd, 'tce' => &$this->tce, 'pObj' => &$this);
				foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tsfebeuserauth.php']['extEditAction-postProc'] as $_funcRef) {
					t3lib_div::callUserFunction($_funcRef, $_params, $this);
				}
			}
		}
	}

	/**
	 * Enables the force preview option.
	 *
	 * @return	void
	 */
	public function forcePreview() {
		$this->ext_forcePreview = true;
	}

	/**
	 * Returns the comma-separated list of page UIDs to be published.
	 *
	 * @return	string
	 */
	public function getExtPublishList() {
		return $this->extPublishList;
	}

	/**
	 * Returns true if admin panel module is open
	 *
	 * @param	string		Module key
	 * @return	boolean		True, if the admin panel is open for the specified admin panel module key.
	 */
	public function isAdminModuleOpen($pre) {
		return $GLOBALS['BE_USER']->uc['TSFE_adminConfig']['display_top'] && $GLOBALS['BE_USER']->uc['TSFE_adminConfig']['display_' . $pre];
	}

	/*****************************************************
	 *
	 * Admin Panel Rendering
	 *
	 ****************************************************/

	/**
	 * Creates and returns the HTML code for the Admin Panel in the TSFE frontend.
	 *
	 * @return	string		HTML for the Admin Panel
	 */
	public function display() {
		$GLOBALS['LANG']->includeLLFile('EXT:lang/locallang_tsfe.php');

		$out = '<script type="text/javascript" src="t3lib/js/adminpanel.js"></script>';
		//CSS
		// @todo Check how this was handled before and if it's required here
		// $GLOBALS['TSFE']->additionalHeaderData['admPanelCSS'] = '<link rel="stylesheet" type="text/css" href="' . t3lib_extMgm::extRelPath('feedit') . 'admpanel.css' . '" />';
		if(!empty($GLOBALS['TBE_STYLES']['stylesheets']['admPanel'])) {
				$GLOBALS['TSFE']->additionalHeaderData['admPanelCSS-Skin'] = '
			<link rel="stylesheet" type="text/css" href="' . $GLOBALS['TBE_STYLES']['stylesheets']['admPanel'].'" />
				';
		}

		if ($GLOBALS['BE_USER']->uc['TSFE_adminConfig']['display_top']) {
			if ($this->isAdminModuleEnabled('preview')) {
				$out .= $this->getPreviewModule();
			}
			if ($this->isAdminModuleEnabled('cache')) {
				$out .= $this->getCacheModule();
			}
			if ($this->isAdminModuleEnabled('publish')) {
				$out .= $this->getPublishModule();
			}
			if ($this->isAdminModuleEnabled('edit')){
				$out .= $this->getEditModule();
			}
			if ($this->isAdminModuleEnabled('tsdebug')) {
				$out .= $this->getTSDebugModule();
			}
			if ($this->isAdminModuleEnabled('info')) {
				$out .= $this->getInfoModule();
			}
		}

		$row = '<img src="' . TYPO3_mainDir . 'gfx/ol/blank.gif" width="18" height="16" align="absmiddle" border="0" alt="" />';
		$row .= '<img src="' . TYPO3_mainDir . 'gfx/ol/' . ($GLOBALS['BE_USER']->uc['TSFE_adminConfig']['display_top']?'minus':'plus') . 'bullet.gif" width="18" height="16" align="absmiddle" border="0" alt="" />';
		$row .= '<strong>' . $this->extFw($this->extGetLL('adminOptions')) . '</strong>';
		$row .= $this->extFw(': ' . $GLOBALS['BE_USER']->user['username']);

		$header = '
			<tr class="typo3-adminPanel-hRow" style="background-color: #9ba1a8; cursor: move;">
				<td colspan="4" style="text-align:left; white-space:nowrap;">' .
					$this->extItemLink('top',$row) . '
					<img src="clear.gif" width="40" height="1" alt="" />
					<input type="hidden" name="TSFE_ADMIN_PANEL[display_top]" value="' . $GLOBALS['BE_USER']->uc['TSFE_adminConfig']['display_top'] . '" />' . ($this->extNeedUpdate ? '<input type="submit" value="' . $this->extGetLL('update') . '" />' : '') . '</td>
			</tr>';

		$query = !t3lib_div::_GET('id') ? ('<input type="hidden" name="id" value="' . $GLOBALS['TSFE']->id . '" />' . LF) : '';
			// the dummy field is needed for Firefox: to force a page reload on submit with must change the form value with JavaScript (see "onsubmit" attribute of the "form" element")
		$query .= '<input type="hidden" name="TSFE_ADMIN_PANEL[DUMMY]" value="">';
		foreach (t3lib_div::_GET() as $key => $value) {
			if ($key != 'TSFE_ADMIN_PANEL') {
				if (is_array($value)) {
					$query .= $this->getHiddenFields($key, $value);
				} else {
					$query .= '<input type="hidden" name="' . $key . '" value="' . htmlspecialchars($value) . '">' . LF;
				}
			}
		}

		$out = '
<!--
	ADMIN PANEL
-->
<a name="TSFE_ADMIN"></a>
<form name="TSFE_ADMIN_PANEL_FORM" action="' . htmlspecialchars(t3lib_div::getIndpEnv('TYPO3_REQUEST_SCRIPT')) . '#TSFE_ADMIN" method="get" style="margin:0;" onsubmit="document.forms.TSFE_ADMIN_PANEL_FORM[\'TSFE_ADMIN_PANEL[DUMMY]\'].value=Math.random().toString().substring(2,8)">' .
$query . '
	<table border="0" cellpadding="0" cellspacing="0" class="typo3-adminPanel" style="background-color:#f6f2e6; border: 1px solid black; z-index:0; position:absolute;" summary="">' .
		$header .
		$out . '
	</table>
</form>';

		if ($GLOBALS['BE_USER']->uc['TSFE_adminConfig']['display_top']) {
			$out .= '<script type="text/javascript" src="t3lib/jsfunc.evalfield.js"></script>';
			$out .= '
			<script type="text/javascript">
					/*<![CDATA[*/
				var evalFunc = new evalFunc();
					// TSFEtypo3FormFieldSet()
				function TSFEtypo3FormFieldSet(theField, evallist, is_in, checkbox, checkboxValue) {	//
					var theFObj = new evalFunc_dummy (evallist,is_in, checkbox, checkboxValue);
					var theValue = document.TSFE_ADMIN_PANEL_FORM[theField].value;
					if (checkbox && theValue==checkboxValue) {
						document.TSFE_ADMIN_PANEL_FORM[theField+"_hr"].value="";
						document.TSFE_ADMIN_PANEL_FORM[theField+"_cb"].checked = "";
					} else {
						document.TSFE_ADMIN_PANEL_FORM[theField+"_hr"].value = evalFunc.outputObjValue(theFObj, theValue);
						document.TSFE_ADMIN_PANEL_FORM[theField+"_cb"].checked = "on";
					}
				}
					// TSFEtypo3FormFieldGet()
				function TSFEtypo3FormFieldGet(theField, evallist, is_in, checkbox, checkboxValue, checkbox_off) {	//
					var theFObj = new evalFunc_dummy (evallist,is_in, checkbox, checkboxValue);
					if (checkbox_off) {
						document.TSFE_ADMIN_PANEL_FORM[theField].value=checkboxValue;
					}else{
						document.TSFE_ADMIN_PANEL_FORM[theField].value = evalFunc.evalObjValue(theFObj, document.TSFE_ADMIN_PANEL_FORM[theField+"_hr"].value);
					}
					TSFEtypo3FormFieldSet(theField, evallist, is_in, checkbox, checkboxValue);
				}
					/*]]>*/
			</script>
			<script language="javascript" type="text/javascript">' . $this->extJSCODE . '</script>';
		}

		$out = '
		<div onmousedown="TYPO3AdminPanel.dragStart(this)" onmouseup="TYPO3AdminPanel.savePosition(this)" id="admPanel" style="position:absolute; z-index: 10000;">
		' . $out . '
		<br /></div>
		<script type="text/javascript">
			TYPO3AdminPanel.dragInit();
			TYPO3AdminPanel.restorePosition();
		</script>';

		return $out;
	}

	/**
	 * Fetches recursively all GET parameters as hidden fields.
	 * Called from display()
	 *
	 * @param	string		current key
	 * @param	mixed		current value
	 * @return	string		hidden fields
	 * @see display()
	 */
	protected function getHiddenFields($key, &$val) {
		$out = '';
		foreach ($val as $k => $v) {
			if (is_array($v)) {
				$out .= $this->getHiddenFields($key . '[' . $k . ']', $v);
			} else {
				$out .= '<input type="hidden" name="' . $key . '[' . $k . ']" value="' . htmlspecialchars($v) . '">' . LF;
			}
		}
		return $out;
	}

	/*****************************************************
	 *
	 * Creating sections of the Admin Panel
	 *
	 ****************************************************/

	/**
	 * Creates the content for the "preview" section ("module") of the Admin Panel
	 *
	 * @param	string		Optional start-value; The generated content is added to this variable.
	 * @return	string		HTML content for the section. Consists of a string with table-rows with four columns.
	 * @see display()
	 */
	protected function getPreviewModule($out = '') {
		$out .= $this->extGetHead('preview');
		if ($GLOBALS['BE_USER']->uc['TSFE_adminConfig']['display_preview']) {
			$this->extNeedUpdate = true;
			$out .= $this->extGetItem('preview_showHiddenPages', '<input type="hidden" name="TSFE_ADMIN_PANEL[preview_showHiddenPages]" value="0" /><input type="checkbox" name="TSFE_ADMIN_PANEL[preview_showHiddenPages]" value="1"' . ($GLOBALS['BE_USER']->uc['TSFE_adminConfig']['preview_showHiddenPages'] ? ' checked="checked"' : '') . ' />');
			$out .= $this->extGetItem('preview_showHiddenRecords', '<input type="hidden" name="TSFE_ADMIN_PANEL[preview_showHiddenRecords]" value="0" /><input type="checkbox" name="TSFE_ADMIN_PANEL[preview_showHiddenRecords]" value="1"' . ($GLOBALS['BE_USER']->uc['TSFE_adminConfig']['preview_showHiddenRecords'] ? ' checked="checked"' : '') . ' />');

				// Simulate date
			$out .= $this->extGetItem('preview_simulateDate', '<input type="checkbox" name="TSFE_ADMIN_PANEL[preview_simulateDate]_cb" onclick="TSFEtypo3FormFieldGet(\'TSFE_ADMIN_PANEL[preview_simulateDate]\', \'datetime\', \'\',1,0,1);" /><input type="text" name="TSFE_ADMIN_PANEL[preview_simulateDate]_hr" onchange="TSFEtypo3FormFieldGet(\'TSFE_ADMIN_PANEL[preview_simulateDate]\', \'datetime\', \'\', 1,0);" /><input type="hidden" name="TSFE_ADMIN_PANEL[preview_simulateDate]" value="' . $GLOBALS['BE_USER']->uc['TSFE_adminConfig']['preview_simulateDate'] . '" />');
			$this->extJSCODE .= 'TSFEtypo3FormFieldSet("TSFE_ADMIN_PANEL[preview_simulateDate]", "datetime", "", 1,0);';

				// Simulate fe_user:
			$options = '<option value="0">&nbsp;</option>';
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
						'fe_groups.uid, fe_groups.title',
						'fe_groups,pages',
						'pages.uid=fe_groups.pid AND pages.deleted=0 ' . t3lib_BEfunc::deleteClause('fe_groups') . ' AND ' . $GLOBALS['BE_USER']->getPagePermsClause(1)
					);
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				$options .= '<option value="' . $row['uid'] . '"' . ($GLOBALS['BE_USER']->uc['TSFE_adminConfig']['preview_simulateUserGroup'] == $row['uid'] ? ' selected="selected"' : '') . '>' . htmlspecialchars('[' . $row['uid'] . '] ' . $row['title']) . '</option>';
			}
			$out .= $this->extGetItem('preview_simulateUserGroup', '<select name="TSFE_ADMIN_PANEL[preview_simulateUserGroup]">' . $options . '</select>');
		}

		return $out;
	}

	/**
	 * Creates the content for the "cache" section ("module") of the Admin Panel
	 *
	 * @param	string		Optional start-value; The generated content is added to this variable.
	 * @return	string		HTML content for the section. Consists of a string with table-rows with four columns.
	 * @see display()
	 */
	protected function getCacheModule($out = '') {
		$out.= $this->extGetHead('cache');
		if ($GLOBALS['BE_USER']->uc['TSFE_adminConfig']['display_cache']) {
			$this->extNeedUpdate = true;
			$out .= $this->extGetItem('cache_noCache', '<input type="hidden" name="TSFE_ADMIN_PANEL[cache_noCache]" value="0" /><input type="checkbox" name="TSFE_ADMIN_PANEL[cache_noCache]" value="1"' . ($GLOBALS['BE_USER']->uc['TSFE_adminConfig']['cache_noCache'] ? ' checked="checked"' : '') . ' />');
			$options = '';
			$options .= '<option value="0"' . ($GLOBALS['BE_USER']->uc['TSFE_adminConfig']['cache_clearCacheLevels'] == 0 ? ' selected="selected"' : '') . '>' . $this->extGetLL('div_Levels_0') . '</option>';
			$options .= '<option value="1"' . ($GLOBALS['BE_USER']->uc['TSFE_adminConfig']['cache_clearCacheLevels'] == 1 ? ' selected="selected"' : '') . '>' . $this->extGetLL('div_Levels_1') . '</option>';
			$options .= '<option value="2"' . ($GLOBALS['BE_USER']->uc['TSFE_adminConfig']['cache_clearCacheLevels'] == 2 ? ' selected="selected"' : '') . '>' . $this->extGetLL('div_Levels_2') . '</option>';
			$out .= $this->extGetItem('cache_clearLevels', '<select name="TSFE_ADMIN_PANEL[cache_clearCacheLevels]">' . $options . '</select>' .
					'<input type="hidden" name="TSFE_ADMIN_PANEL[cache_clearCacheId]" value="' . $GLOBALS['TSFE']->id . '" /><input type="submit" value="' . $this->extGetLL('update') . '" />');

				// Generating tree:
			$depth = $this->extGetFeAdminValue('cache', 'clearCacheLevels');
			$outTable = '';
			$GLOBALS['BE_USER']->extPageInTreeInfo = array();
			$GLOBALS['BE_USER']->extPageInTreeInfo[] = array($GLOBALS['TSFE']->page['uid'], htmlspecialchars($GLOBALS['TSFE']->page['title']), $depth+1);
			$GLOBALS['BE_USER']->extGetTreeList($GLOBALS['TSFE']->id, $depth, 0, $GLOBALS['BE_USER']->getPagePermsClause(1));
			foreach ($GLOBALS['BE_USER']->extPageInTreeInfo as $row) {
				$outTable .= '
					<tr>
						<td style="white-space:nowrap;"><img src="clear.gif" width="' . (($depth+1-$row[2])*18) . '" height="1" alt="" /><img src="' . TYPO3_mainDir . 'gfx/i/pages.gif" width="18" height="16" align="absmiddle" border="0" alt="" />' . $this->extFw($row[1]) . '</td>
						<td><img src="clear.gif" width="10" height="1" alt="" /></td>
						<td>' . $this->extFw($GLOBALS['BE_USER']->extGetNumberOfCachedPages($row[0])) . '</td>
					</tr>';
			}

			$outTable = '<br /><table border="0" cellpadding="0" cellspacing="0" summary="">' . $outTable . '</table>';
			$outTable .= '<input type="submit" name="TSFE_ADMIN_PANEL[action][clearCache]" value="' . $this->extGetLL('cache_doit') . '" />';

			$out .= $this->extGetItem('cache_cacheEntries', $outTable);
		}

		return $out;
	}

	/**
	 * Creates the content for the "publish" section ("module") of the Admin Panel
	 *
	 * @param	string		Optional start-value; The generated content is added to this variable.
	 * @return	string		HTML content for the section. Consists of a string with table-rows with four columns.
	 * @see display()
	 */
	protected function getPublishModule($out = '') {
		$out .= $this->extGetHead('publish');
		if ($GLOBALS['BE_USER']->uc['TSFE_adminConfig']['display_publish']) {
			$this->extNeedUpdate = true;
			$options = '';
			$options .= '<option value="0"' . ($GLOBALS['BE_USER']->uc['TSFE_adminConfig']['publish_levels'] == 0 ? ' selected="selected"' : '') . '>' . $this->extGetLL('div_Levels_0') . '</option>';
			$options .= '<option value="1"' . ($GLOBALS['BE_USER']->uc['TSFE_adminConfig']['publish_levels'] == 1 ? ' selected="selected"' : '') . '>' . $this->extGetLL('div_Levels_1') . '</option>';
			$options .= '<option value="2"' . ($GLOBALS['BE_USER']->uc['TSFE_adminConfig']['publish_levels'] == 2 ? ' selected="selected"' : '') . '>' . $this->extGetLL('div_Levels_2') . '</option>';
			$out .= $this->extGetItem('publish_levels', '<select name="TSFE_ADMIN_PANEL[publish_levels]">' . $options . '</select>' .
					'<input type="hidden" name="TSFE_ADMIN_PANEL[publish_id]" value="' . $GLOBALS['TSFE']->id . '" />&nbsp;<input type="submit" value="' . $this->extGetLL('update') . '" />');

				// Generating tree:
			$depth = $this->extGetFeAdminValue('publish', 'levels');
			$outTable = '';
			$GLOBALS['BE_USER']->extPageInTreeInfo = array();
			$GLOBALS['BE_USER']->extPageInTreeInfo[] = array($GLOBALS['TSFE']->page['uid'], htmlspecialchars($GLOBALS['TSFE']->page['title']), $depth+1);
			$GLOBALS['BE_USER']->extGetTreeList($GLOBALS['TSFE']->id, $depth, 0, $GLOBALS['BE_USER']->getPagePermsClause(1));
			foreach ($GLOBALS['BE_USER']->extPageInTreeInfo as $row) {
				$outTable.= '
					<tr>
						<td style="white-space:nowrap;"><img src="clear.gif" width="' . (($depth + 1 - $row[2]) * 18) . '" height="1" alt="" /><img src="' . TYPO3_mainDir . 'gfx/i/pages.gif" width="18" height="16" align="absmiddle" border="0" alt="" />' . $this->extFw($row[1]) . '</td>
						<td><img src="clear.gif" width="10" height="1" alt="" /></td>
						<td>' . $this->extFw('...') . '</td>
					</tr>';
			}
			$outTable = '<br /><table border="0" cellpadding="0" cellspacing="0" summary="">' . $outTable . '</table>';
			$outTable .= '<input type="submit" name="TSFE_ADMIN_PANEL[action][publish]" value="' . $this->extGetLL('publish_doit') . '" />';

			$out .= $this->extGetItem('publish_tree', $outTable);
		}

		return $out;
	}

	/**
	 * Creates the content for the "edit" section ("module") of the Admin Panel
	 *
	 * @param	string		Optional start-value; The generated content is added to this variable.
	 * @return	string		HTML content for the section. Consists of a string with table-rows with four columns.
	 * @see display()
	 */
	protected function getEditModule($out = '') {
		$out .= $this->extGetHead('edit');
		if ($GLOBALS['BE_USER']->uc['TSFE_adminConfig']['display_edit']) {

				// If another page module was specified, replace the default Page module with the new one
			$newPageModule = trim($GLOBALS['BE_USER']->getTSConfigVal('options.overridePageModule'));
			$pageModule = t3lib_BEfunc::isModuleSetInTBE_MODULES($newPageModule) ? $newPageModule : 'web_layout';

			$this->extNeedUpdate = true;
			$out .= $this->extGetItem('edit_displayFieldIcons', '<input type="hidden" name="TSFE_ADMIN_PANEL[edit_displayFieldIcons]" value="0" /><input type="checkbox" name="TSFE_ADMIN_PANEL[edit_displayFieldIcons]" value="1"' . ($GLOBALS['BE_USER']->uc['TSFE_adminConfig']['edit_displayFieldIcons'] ? ' checked="checked"' : '') . ' />');
			$out .= $this->extGetItem('edit_displayIcons', '<input type="hidden" name="TSFE_ADMIN_PANEL[edit_displayIcons]" value="0" /><input type="checkbox" name="TSFE_ADMIN_PANEL[edit_displayIcons]" value="1"' . ($GLOBALS['BE_USER']->uc['TSFE_adminConfig']['edit_displayIcons'] ? ' checked="checked"' : '') . ' />');
			$out .= $this->extGetItem('edit_editFormsOnPage', '<input type="hidden" name="TSFE_ADMIN_PANEL[edit_editFormsOnPage]" value="0" /><input type="checkbox" name="TSFE_ADMIN_PANEL[edit_editFormsOnPage]" value="1"' . ($GLOBALS['BE_USER']->uc['TSFE_adminConfig']['edit_editFormsOnPage'] ? ' checked="checked"':'') . ' />');
			$out .= $this->extGetItem('edit_editNoPopup', '<input type="hidden" name="TSFE_ADMIN_PANEL[edit_editNoPopup]" value="0" /><input type="checkbox" name="TSFE_ADMIN_PANEL[edit_editNoPopup]" value="1"' . ($GLOBALS['BE_USER']->uc['TSFE_adminConfig']['edit_editNoPopup'] ? ' checked="checked"' : '') . ' />');
			$out .= $this->extGetItem('', $this->ext_makeToolBar());

			if (!t3lib_div::_GP('ADMCMD_view')) {
				$out .= $this->extGetItem('', '<a href="#" onclick="' .
					htmlspecialchars('
						if (parent.opener && parent.opener.top && parent.opener.top.TS) {
							parent.opener.top.fsMod.recentIds["web"]=' . intval($GLOBALS['TSFE']->page['uid']) . ';
							if (parent.opener.top.content && parent.opener.top.content.nav_frame && parent.opener.top.content.nav_frame.refresh_nav) {
								parent.opener.top.content.nav_frame.refresh_nav();
							}
							parent.opener.top.goToModule("' . $pageModule . '");
							parent.opener.top.focus();
						} else {
							vHWin=window.open(\'' . TYPO3_mainDir.t3lib_BEfunc::getBackendScript() . '\',\'' . md5('Typo3Backend-' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename']) . '\',\'status=1,menubar=1,scrollbars=1,resizable=1\');
							vHWin.focus();
						}
						return false;
						').
					'">' . $this->extFw($this->extGetLL('edit_openAB')) . '</a>');
			}
		}

		return $out;
	}

	/**
	 * Creates the content for the "tsdebug" section ("module") of the Admin Panel
	 *
	 * @param	string		Optional start-value; The generated content is added to this variable.
	 * @return	string		HTML content for the section. Consists of a string with table-rows with four columns.
	 * @see display()
	 */
	protected function getTSDebugModule($out = '') {
		$out .= $this->extGetHead('tsdebug');
		if ($GLOBALS['BE_USER']->uc['TSFE_adminConfig']['display_tsdebug']) {
			$this->extNeedUpdate = true;

			$out .= $this->extGetItem('tsdebug_tree', '<input type="hidden" name="TSFE_ADMIN_PANEL[tsdebug_tree]" value="0" /><input type="checkbox" name="TSFE_ADMIN_PANEL[tsdebug_tree]" value="1"' . ($GLOBALS['BE_USER']->uc['TSFE_adminConfig']['tsdebug_tree'] ? ' checked="checked"' : '') . ' />');
			$out .= $this->extGetItem('tsdebug_displayTimes', '<input type="hidden" name="TSFE_ADMIN_PANEL[tsdebug_displayTimes]" value="0" /><input type="checkbox" name="TSFE_ADMIN_PANEL[tsdebug_displayTimes]" value="1"' . ($GLOBALS['BE_USER']->uc['TSFE_adminConfig']['tsdebug_displayTimes'] ? ' checked="checked"' : '') . ' />');
			$out .= $this->extGetItem('tsdebug_displayMessages', '<input type="hidden" name="TSFE_ADMIN_PANEL[tsdebug_displayMessages]" value="0" /><input type="checkbox" name="TSFE_ADMIN_PANEL[tsdebug_displayMessages]" value="1"' . ($GLOBALS['BE_USER']->uc['TSFE_adminConfig']['tsdebug_displayMessages'] ? ' checked="checked"':'') . ' />');
			$out .= $this->extGetItem('tsdebug_LR', '<input type="hidden" name="TSFE_ADMIN_PANEL[tsdebug_LR]" value="0" /><input type="checkbox" name="TSFE_ADMIN_PANEL[tsdebug_LR]" value="1"' . ($GLOBALS['BE_USER']->uc['TSFE_adminConfig']['tsdebug_LR'] ? ' checked="checked"' : '') . ' />');
			$out .= $this->extGetItem('tsdebug_displayContent', '<input type="hidden" name="TSFE_ADMIN_PANEL[tsdebug_displayContent]" value="0" /><input type="checkbox" name="TSFE_ADMIN_PANEL[tsdebug_displayContent]" value="1"' . ($GLOBALS['BE_USER']->uc['TSFE_adminConfig']['tsdebug_displayContent'] ? ' checked="checked"' : '') . ' />');
			$out .= $this->extGetItem('tsdebug_displayQueries', '<input type="hidden" name="TSFE_ADMIN_PANEL[tsdebug_displayQueries]" value="0" /><input type="checkbox" name="TSFE_ADMIN_PANEL[tsdebug_displayQueries]" value="1"' . ($GLOBALS['BE_USER']->uc['TSFE_adminConfig']['tsdebug_displayQueries'] ? ' checked="checked"' : '') . ' />');
			$out .= $this->extGetItem('tsdebug_forceTemplateParsing', '<input type="hidden" name="TSFE_ADMIN_PANEL[tsdebug_forceTemplateParsing]" value="0" /><input type="checkbox" name="TSFE_ADMIN_PANEL[tsdebug_forceTemplateParsing]" value="1"' . ($GLOBALS['BE_USER']->uc['TSFE_adminConfig']['tsdebug_forceTemplateParsing'] ? ' checked="checked"' : '') . ' />');

			$GLOBALS['TT']->printConf['flag_tree'] = $this->extGetFeAdminValue('tsdebug', 'tree');
			$GLOBALS['TT']->printConf['allTime'] = $this->extGetFeAdminValue('tsdebug', 'displayTimes');
			$GLOBALS['TT']->printConf['flag_messages'] = $this->extGetFeAdminValue('tsdebug', 'displayMessages');
			$GLOBALS['TT']->printConf['flag_content'] = $this->extGetFeAdminValue('tsdebug', 'displayContent');
			$GLOBALS['TT']->printConf['flag_queries'] = $this->extGetFeAdminValue('tsdebug', 'displayQueries');

			$out.= '
				<tr>
					<td><img src="clear.gif" width="50" height="1" alt="" /></td>
					<td colspan="3">' . $GLOBALS['TT']->printTSlog() . '</td>
				</tr>';
		}

		return $out;
	}

	/**
	 * Creates the content for the "info" section ("module") of the Admin Panel
	 *
	 * @param	string		Optional start-value; The generated content is added to this variable.
	 * @return	string		HTML content for the section. Consists of a string with table-rows with four columns.
	 * @see display()
	 */
	protected function getInfoModule($out = '') {
		$out .= $this->extGetHead('info');
		if ($GLOBALS['BE_USER']->uc['TSFE_adminConfig']['display_info']) {
			$tableArr = array();

			if ($this->extGetFeAdminValue('cache', 'noCache')) {
				$theBytes = 0;
				$count = 0;

				if (count($GLOBALS['TSFE']->imagesOnPage)) {
					$tableArr[] = array('*Images on this page:*', '');
					foreach ($GLOBALS['TSFE']->imagesOnPage as $file) {
						$fs = @filesize($file);
						$tableArr[] = array('&ndash; ' . $file, t3lib_div::formatSize($fs));
						$theBytes+= $fs;
						$count++;
					}
				}
				$tableArr[] = array('', '');	// Add an empty line

				$tableArr[] = array('*Total number of images:*', $count);
				$tableArr[] = array('*Total image file sizes:*', t3lib_div::formatSize($theBytes));
				$tableArr[] = array('*Document size:*', t3lib_div::formatSize(strlen($GLOBALS['TSFE']->content)));
				$tableArr[] = array('*Total page load:*', t3lib_div::formatSize(strlen($GLOBALS['TSFE']->content)+$theBytes));
				$tableArr[] = array('', '');
			}

			$tableArr[] = array('id:', $GLOBALS['TSFE']->id);
			$tableArr[] = array('type:', $GLOBALS['TSFE']->type);
			$tableArr[] = array('gr_list:', $GLOBALS['TSFE']->gr_list);
			$tableArr[] = array('no_cache:', $GLOBALS['TSFE']->no_cache ? 1 : 0);
			$tableArr[] = array('USER_INT objects:', count($GLOBALS['TSFE']->config['INTincScript']));
			$tableArr[] = array('fe_user, name:', $GLOBALS['TSFE']->fe_user->user['username']);
			$tableArr[] = array('fe_user, uid:', $GLOBALS['TSFE']->fe_user->user['uid']);
			$tableArr[] = array('', '');	// Add an empty line

				// parsetime:
			$tableArr[] = array('*Total parsetime:*', $GLOBALS['TSFE']->scriptParseTime . ' ms');

			$table = '';
			foreach ($tableArr as $arr) {
				if (strlen($arr[0])) {	// Put text wrapped by "*" between <strong> tags
					$value1 = preg_replace('/^\*(.*)\*$/', '$1', $arr[0], -1, $count);
					$value1 = ($count?'<strong>':'') . $this->extFw($value1) . ($count?'</strong>':'');
				} else {
					$value1 = $this->extFw('&nbsp;');
				}

				$value2 = strlen($arr[1]) ? $arr[1] : '&nbsp;';
				$value2 = $this->extFw($value2);

				$table .= '
					<tr>
						<td style="text-align:left">' . $value1 . '</td>
						<td style="text-align:right">' . $value2 . '</td>
					</tr>';
			}

			$table = '<table border="0" cellpadding="0" cellspacing="0" summary="">' . $table . '</table>';

			$out .= '
				<tr>
					<td><img src="clear.gif" width="50" height="1" alt="" /></td>
					<td colspan="3">' . $table . '</td>
				</tr>';
		}

		return $out;
	}

	/*****************************************************
	 *
	 * Admin Panel Layout Helper functions
	 *
	 ****************************************************/

	/**
	 * Returns a row (with colspan=4) which is a header for a section in the Admin Panel.
	 * It will have a plus/minus icon and a label which is linked so that it submits the form which surrounds the whole Admin Panel when clicked, alterting the TSFE_ADMIN_PANEL[display_' . $pre . '] value
	 * See the functions get*Module
	 *
	 * @param	string		The suffix to the display_ label. Also selects the label from the LOCAL_LANG array.
	 * @return	string		HTML table row.
	 * @access private
	 * @see extGetItem()
	 */
	protected function extGetHead($pre) {
		$out = '<img src="' . TYPO3_mainDir . 'gfx/ol/blank.gif" width="18" height="16" align="absmiddle" border="0" alt="" />';
		$out .= '<img src="' . TYPO3_mainDir . 'gfx/ol/' . ($GLOBALS['BE_USER']->uc['TSFE_adminConfig']['display_' . $pre] ? 'minus' : 'plus') . 'bullet.gif" width="18" height="16" align="absmiddle" border="0" alt="" />';
		$out .= $this->extFw($this->extGetLL($pre));

		$out = $this->extItemLink($pre,$out);
		return '
				<tr class="typo3-adminPanel-itemHRow" style="background-color:#abbbb4;">
					<td colspan="4" style="text-align:left; border-top:dashed 1px #007a8c; white-space:nowrap;">' . $out . '<input type="hidden" name="TSFE_ADMIN_PANEL[display_' . $pre . ']" value="' . $GLOBALS['BE_USER']->uc['TSFE_adminConfig']['display_' . $pre] . '" /></td>
				</tr>';
	}

	/**
	 * Wraps a string in a link which will open/close a certain part of the Admin Panel
	 *
	 * @param	string		The code for the display_ label/key
	 * @param	string		Input string
	 * @return	string		Linked input string
	 * @access private
	 * @see extGetHead()
	 */
	protected function extItemLink($pre, $str) {
		return '<a href="#" style="text-decoration:none;" onclick="' .
			htmlspecialchars('document.TSFE_ADMIN_PANEL_FORM[\'TSFE_ADMIN_PANEL[display_' . $pre . ']\'].value=' . ($GLOBALS['BE_USER']->uc['TSFE_adminConfig']['display_' . $pre] ? '0' : '1') . '; document.TSFE_ADMIN_PANEL_FORM.submit(); return false;') .
			'">' . $str . '</a>';
	}

	/**
	 * Returns a row (with 4 columns) for content in a section of the Admin Panel.
	 * It will take $pre as a key to a label to display and $element as the content to put into the forth cell.
	 *
	 * @param	string		Key to label
	 * @param	string		The HTML content for the forth table cell.
	 * @return	string		HTML table row.
	 * @access private
	 * @see extGetHead()
	 */
	protected function extGetItem($pre, $element) {
		$out = '
					<tr class="typo3-adminPanel-itemRow">
						<td><img src="clear.gif" width="50" height="1" alt="" /></td>
						<td style="text-align:left; white-space:nowrap;">' . ($pre ? $this->extFw($this->extGetLL($pre)) : '&nbsp;') . '</td>
						<td><img src="clear.gif" width="30" height="1" alt="" /></td>
						<td style="text-align:left; white-space:nowrap;">' . $element . '</td>
					</tr>';

		return $out;
	}

	/**
	 * Wraps a string in a span-tag with black verdana font
	 *
	 * @param	string		The string to wrap
	 * @return	string
	 */
	protected function extFw($str) {
		return '<span style="font-family:Verdana,Arial,Helvetica,sans-serif; font-size:10px; color:black;">' . $str . '</span>';
	}

	/**
	 * Creates the tool bar links for the "edit" section of the Admin Panel.
	 *
	 * @return	string		A string containing images wrapped in <a>-tags linking them to proper functions.
	 */
	public function ext_makeToolBar() {
			//  If mod.web_list.newContentWiz.overrideWithExtension is set, use that extension's create new content wizard instead:
		$tmpTSc = t3lib_BEfunc::getModTSconfig($this->pageinfo['uid'],'mod.web_list');
		$tmpTSc = $tmpTSc ['properties']['newContentWiz.']['overrideWithExtension'];
		$newContentWizScriptPath = t3lib_extMgm::isLoaded($tmpTSc) ? (t3lib_extMgm::extRelPath($tmpTSc) . 'mod1/db_new_content_el.php') : (TYPO3_mainDir . 'sysext/cms/layout/db_new_content_el.php');

		$perms = $GLOBALS['BE_USER']->calcPerms($GLOBALS['TSFE']->page);
		$langAllowed = $GLOBALS['BE_USER']->checkLanguageAccess($GLOBALS['TSFE']->sys_language_uid);

		$toolBar = '';
		$id = $GLOBALS['TSFE']->id;
		$toolBar .= '<a href="' . htmlspecialchars(TYPO3_mainDir . 'show_rechis.php?element=' . rawurlencode('pages:' . $id) . '&returnUrl=' . rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI'))) . '#latest">' .
					'<img src="' . TYPO3_mainDir . 'gfx/history2.gif" width="13" height="12" hspace="2" border="0" align="top" title="' . $this->extGetLL('edit_recordHistory') . '" alt="" /></a>';

		if ($perms&16 && $langAllowed) {
			$params = '';
			if ($GLOBALS['TSFE']->sys_language_uid) {
				$params = '&sys_language_uid=' . $GLOBALS['TSFE']->sys_language_uid;
			}
			$toolBar .= '<a href="' . htmlspecialchars($newContentWizScriptPath . '?id=' . $id . $params . '&returnUrl=' . rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI'))) . '">' .
						'<img src="' . TYPO3_mainDir . 'gfx/new_record.gif" width="16" height="12" hspace="1" border="0" align="top" title="' . $this->extGetLL('edit_newContentElement') . '" alt="" /></a>';
		}
		if ($perms&2) {
			$toolBar .= '<a href="' . htmlspecialchars(TYPO3_mainDir . 'move_el.php?table=pages&uid=' . $id . '&returnUrl=' . rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI'))) . '">' .
					'<img src="' . TYPO3_mainDir . 'gfx/move_page.gif" width="11" height="12" hspace="2" border="0" align="top" title="' . $this->extGetLL('edit_move_page') . '" alt="" /></a>';
		}
		if ($perms&8) {
			$toolBar .= '<a href="' . htmlspecialchars(TYPO3_mainDir . 'db_new.php?id=' . $id . '&pagesOnly=1&returnUrl=' . rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI'))) . '">' .
					'<img src="' . TYPO3_mainDir . 'gfx/new_page.gif" width="13" height="12" hspace="0" border="0" align="top" title="' . $this->extGetLL('edit_newPage') . '" alt="" /></a>';
		}
		if ($perms&2) {
			$params = '&edit[pages][' . $id . ']=edit';
			$toolBar .= '<a href="' . htmlspecialchars(TYPO3_mainDir . 'alt_doc.php?' . $params . '&noView=1&returnUrl=' . rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI'))) . '">' .
					'<img src="' . TYPO3_mainDir . 'gfx/edit2.gif" width="11" height="12" hspace="2" border="0" align="top" title="' . $this->extGetLL('edit_editPageProperties') . '" alt="" /></a>';

			if ($GLOBALS['TSFE']->sys_language_uid && $langAllowed) {
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'uid,pid,t3ver_state',	'pages_language_overlay',
					'pid=' . intval($id) . ' AND sys_language_uid=' . $GLOBALS['TSFE']->sys_language_uid . $GLOBALS['TSFE']->sys_page->enableFields('pages_language_overlay'),
					'', '', '1');
				$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
				$GLOBALS['TSFE']->sys_page->versionOL('pages_language_overlay',$row);
				if (is_array($row)) {
					$params = '&edit[pages_language_overlay][' . $row['uid'] . ']=edit';
					$toolBar .= '<a href="' . htmlspecialchars(TYPO3_mainDir . 'alt_doc.php?' . $params . '&noView=1&returnUrl=' . rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI'))) . '">' .
							'<img src="' . TYPO3_mainDir . 'gfx/edit3.gif" width="11" height="12" hspace="2" border="0" align="top" title="' . $this->extGetLL('edit_editPageOverlay') . '" alt="" /></a>';
				}
			}
		}
		if ($GLOBALS['BE_USER']->check('modules', 'web_list')) {
			$toolBar .= '<a href="' . htmlspecialchars(TYPO3_mainDir . 'db_list.php?id=' . $id . '&returnUrl=' . rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI'))) . '">' .
					'<img src="' . TYPO3_mainDir . 'gfx/list.gif" width="11" height="11" hspace="2" border="0" align="top" title="' . $this->extGetLL('edit_db_list') . '" alt="" /></a>';
		}

		return $toolBar;
	}

	/**
	 * Returns the label for key, $key. If a translation for the language set in $GLOBALS['BE_USER']->uc['lang'] is found that is returned, otherwise the default value.
	 * IF the global variable $LOCAL_LANG is NOT an array (yet) then this function loads the global $LOCAL_LANG array with the content of "sysext/lang/locallang_tsfe.php" so that the values therein can be used for labels in the Admin Panel
	 *
	 * @param	string		Key for a label in the $LOCAL_LANG array of "sysext/lang/locallang_tsfe.php"
	 * @return	string		The value for the $key
	 */
	protected function extGetLL($key) {
		$labelStr = htmlspecialchars($GLOBALS['LANG']->getLL($key));	// Label string in the default backend output charset.

			// Convert to utf-8, then to entities:
		if ($GLOBALS['LANG']->charSet!='utf-8') {
			$labelStr = $GLOBALS['LANG']->csConvObj->utf8_encode($labelStr,$GLOBALS['LANG']->charSet);
		}
		$labelStr = $GLOBALS['LANG']->csConvObj->utf8_to_entities($labelStr);

			// Return the result:
		return $labelStr;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/sysext/cms/tslib/class.tslib_adminpanel.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/sysext/cms/tslib/class.tslib_adminpanel.php']);
}

?>