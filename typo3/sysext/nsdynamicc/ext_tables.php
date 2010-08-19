<?php
if (!defined('TYPO3_MODE')) 	die('Access denied.');

if(TYPO3_MODE == 'BE') {
$opendocsPath = t3lib_extMgm::extPath('nsdynamicc');

/** Register your unique ajaxID for dynamic column Ajax function */
	$GLOBALS['TYPO3_CONF_VARS']['BE']['AJAX']['tx_nsdynamicc::createAjaxSelector'] = $opendocsPath.'classes/class.tx_nsdynamicc_display.php:tx_nsdynamicc_display->createAjaxSelector';

/** register your unique ajaxID for collapse record table Ajax functionality */
	$GLOBALS['TYPO3_CONF_VARS']['BE']['AJAX']['tx_nsdynamicc::updateAjaxCollapse'] = $opendocsPath.'classes/class.tx_nsdynamicc_collapse.php:tx_nsdynamicc_collapse->updateAjaxCollapse';

/** register new class to save movement changes (table row item sorting) in data talbes using Ajax functionality */
	$GLOBALS['TYPO3_CONF_VARS']['BE']['AJAX']['tx_nsdynamicc::movementSort'] = $opendocsPath.'classes/class.tx_nsdynamicc_sort.php:tx_nsdynamicc_sort->movementSort';

/** register AjaxID to genarate row item content quick editor Ajax functionality */
	$GLOBALS['TYPO3_CONF_VARS']['BE']['AJAX']['tx_nsdynamicc_inplaceediting::quickContent'] = $opendocsPath.'classes/class.tx_nsdynamicc_inplaceediting.php:tx_nsdynamicc_inplaceediting->quickContent';

/** register AjaxID to submit quick edit changes to the database */
	$GLOBALS['TYPO3_CONF_VARS']['BE']['AJAX']['tx_nsdynamicc_inplaceediting::quickEditSubmit'] = $opendocsPath.'classes/class.tx_nsdynamicc_inplaceediting.php:tx_nsdynamicc_inplaceediting->quickEditSubmit';


}

?>