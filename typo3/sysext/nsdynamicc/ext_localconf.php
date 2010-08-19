<?php

if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

$TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/class.db_list_extra.inc'] = t3lib_extMgm::extPath('nsdynamicc') . 'class.ux_db_list_extra.php';

//$TYPO3_CONF_VARS['BE']['XCLASS']['t3lib/class.t3lib_befunc.php'] = t3lib_extMgm::extPath('nsdynamicc') . 'class.ux_t3lib_befunc.php';

if (TYPO3_MODE == 'BE') {
//register hook cshItemTempClass for helptip new functionality
$TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_befunc.php']['cshItemTempClass'][] = 'EXT:nsdynamicc/classes/class.tx_nsdynamicc_helptips.php:tx_nsdynamicc_helptips';

//template Hooks enabled to add qtips.js file
$TYPO3_CONF_VARS['SC_OPTIONS']['typo3/template.php']['preStartPageHook'][] = 'EXT:nsdynamicc/classes/class.tx_nsdynamicc_sort.php:&tx_nsdynamicc_sort->preStartPageHook';

//template Hooks enabled to add sortable.js file
$TYPO3_CONF_VARS['SC_OPTIONS']['typo3/template.php']['preStartPageHook'][] = 'EXT:nsdynamicc/classes/class.tx_nsdynamicc_helptips.php:&tx_nsdynamicc_helptips->preStartPageHook';

//template Hooks enabled to add dynamic_column.js file
$TYPO3_CONF_VARS['SC_OPTIONS']['typo3/template.php']['preStartPageHook'][] = 'EXT:nsdynamicc/classes/class.tx_nsdynamicc_display.php:&tx_nsdynamicc_display->preStartPageHook';

//add more content to bottom of the page
$TYPO3_CONF_VARS['SC_OPTIONS']['typo3/db_list.php']['extPageContent'][] = 'EXT:nsdynamicc/classes/class.tx_nsdynamicc_inplaceediting.php:&tx_nsdynamicc_inplaceediting';

//makeControl: Allows to change control icons of records in list-module
$TYPO3_CONF_VARS['SC_OPTIONS']['typo3/class.db_list_extra.inc']['actions'][] = 'EXT:nsdynamicc/classes/class.tx_nsdynamicc_sort.php:&tx_nsdynamicc_sort';

/** this is for Quick Edit content Hooks */
//template Hooks enabled to add Tabs js and css files 
$TYPO3_CONF_VARS['SC_OPTIONS']['typo3/template.php']['preStartPageHook'][] = 'EXT:nsdynamicc/classes/class.tx_nsdynamicc_inplaceediting.php:&tx_nsdynamicc_inplaceediting->preStartPageHook';

//create custom fields for quick edit panel
$TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_tceforms.php']['tceformsTempClass'][] = 'EXT:nsdynamicc/classes/class.tx_nsdynamicc_inplaceediting.php:&tx_nsdynamicc_inplaceediting';

//add custom field temple for quick edit panel
$TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_tceforms.php']['tceformsIntoTemplate'][] = 'EXT:nsdynamicc/classes/class.tx_nsdynamicc_inplaceediting.php:&tx_nsdynamicc_inplaceediting';

//add custom check box field for quick edit panel
$TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_tceforms.php']['tceformsTypeCheck'][] = 'EXT:nsdynamicc/classes/class.tx_nsdynamicc_inplaceediting.php:&tx_nsdynamicc_inplaceediting';

//add custom select box for quick edit panel
$TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_tceforms.php']['tceformsTypeSelect'][] = 'EXT:nsdynamicc/classes/class.tx_nsdynamicc_inplaceediting.php:&tx_nsdynamicc_inplaceediting';

//viewBigHook: is used to change display informtion dialog box to Ext model window
$TYPO3_CONF_VARS['SC_OPTIONS']['typo3/class.db_list_extra.inc']['viewBigClass'][] = 'EXT:nsdynamicc/classes/class.tx_nsdynamicc_helptips.php:&tx_nsdynamicc_helptips';

}

?>

