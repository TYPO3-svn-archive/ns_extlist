<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Ingo Renner <ingo@typo3.org>
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
 * A cache for any kinds of PHP variables
 *
 * This file is a backport from FLOW3
 *
 * @package TYPO3
 * @subpackage t3lib_cache
 * @version $Id: class.t3lib_cache_frontend_variablefrontend.php 5595 2009-06-15 21:40:01Z flyguide $
 */
class t3lib_cache_frontend_VariableFrontend extends t3lib_cache_frontend_AbstractFrontend {

	/**
	 * Saves the value of a PHP variable in the cache. Note that the variable
	 * will be serialized if necessary.
	 *
	 * @param string $entryIdentifier An identifier used for this cache entry
	 * @param mixed $variable The variable to cache
	 * @param array $tags Tags to associate with this cache entry
	 * @param integer $lifetime Lifetime of this cache entry in seconds. If NULL is specified, the default lifetime is used. "0" means unlimited liftime.
 	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function set($entryIdentifier, $variable, $tags = array(), $lifetime = NULL) {
		if (!$this->isValidEntryIdentifier($entryIdentifier)) {
			throw new InvalidArgumentException(
				'"' . $entryIdentifier . '" is not a valid cache entry identifier.',
				1233058264
			);
		}

		foreach ($tags as $tag) {
			if (!$this->isValidTag($tag)) {
				throw new InvalidArgumentException(
					'"' . $tag . '" is not a valid tag for a cache entry.',
					1233058269
				);
			}
		}

		$this->backend->set($entryIdentifier, serialize($variable), $tags, $lifetime);
	}

	/**
	 * Loads a variable value from the cache.
	 *
	 * @param string Identifier of the cache entry to fetch
	 * @return mixed The value
	 * @author Robert Lemke <robert@typo3.org>
	 * @throws t3lib_cache_exception_ClassAlreadyLoaded if the class already exists
	 */
	public function get($entryIdentifier) {
		if (!$this->isValidEntryIdentifier($entryIdentifier)) {
			throw new InvalidArgumentException(
				'"' . $entryIdentifier . '" is not a valid cache entry identifier.',
				1233058294
			);
		}

		return unserialize($this->backend->get($entryIdentifier));
	}

	/**
	 * Finds and returns all cache entries which are tagged by the specified tag.
	 *
	 * @param string $tag The tag to search for
	 * @return array An array with the content of all matching entries. An empty array if no entries matched
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getByTag($tag) {
		if (!$this->isValidTag($tag)) {
			throw new InvalidArgumentException(
				'"' . $tag . '" is not a valid tag for a cache entry.',
				1233058312
			);
		}

		$entries = array();
		$identifiers = $this->backend->findIdentifiersByTag($tag);

		foreach ($identifiers as $identifier) {
			$entries[] = unserialize($this->backend->get($identifier));
		}

		return $entries;
	}

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/cache/class.t3lib_cache_variablecache.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/cache/class.t3lib_cache_variablecache.php']);
}

?>