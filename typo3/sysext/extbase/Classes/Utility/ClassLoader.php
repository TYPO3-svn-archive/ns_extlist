<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Jochen Rau <jochen.rau@typoplanet.de>
*  All rights reserved
*
*  This class is a backport of the corresponding class of FLOW3.
*  All credits go to the v5 team.
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
 * Autoloader of Extbase
 *
 * @package Extbase
 * @subpackage Utility
 * @version $Id: ClassLoader.php 1729 2009-11-25 21:37:20Z stucki $
 */
class Tx_Extbase_Utility_ClassLoader {
	
	/**
	 * Loads php files containing classes or interfaces found in the classes directory of
	 * an extension.
	 *
	 * @param string $className: Name of the class/interface to load
	 * @uses t3lib_extMgm::extPath()
	 * @return void
	 */
	public static function loadClass($className) {
		$classNameParts = explode('_', $className, 3);
		$extensionKey = Tx_Extbase_Utility_Extension::convertCamelCaseToLowerCaseUnderscored($classNameParts[1]);
		if (t3lib_extMgm::isLoaded($extensionKey)) {
			$classFilePathAndName = t3lib_extMgm::extPath($extensionKey) . 'Classes/' . strtr($classNameParts[2], '_', '/') . '.php';
			if (file_exists($classFilePathAndName)) {
				require($classFilePathAndName);
			}
		}
	}
	
}
?>