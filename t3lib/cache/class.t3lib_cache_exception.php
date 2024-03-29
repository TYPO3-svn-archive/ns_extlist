<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008-2009 Ingo Renner <ingo@typo3.org>
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
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/


/**
 * A generic Cache exception
 *
 * This file is a backport from FLOW3
 *
 * @package TYPO3
 * @subpackage t3lib_cache
 * @version $Id: class.t3lib_cache_exception.php 5165 2009-03-09 18:28:59Z ohader $
 */
class t3lib_cache_Exception extends Exception {

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/cache/class.t3lib_cache_exception.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/cache/class.t3lib_cache_exception.php']);
}

?>