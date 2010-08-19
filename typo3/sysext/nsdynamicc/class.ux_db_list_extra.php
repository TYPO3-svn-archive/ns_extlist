<?php

/**  
 * 
 * Dummy document - displays nothing but background color. *  
 ** @author    Nuwan Sameera <nuwan28@gmail.com> 
 * Revised for TYPO3 3.6 2/2003 by Kasper Skårhøj 
 * XHTML compliant content
 */

// Script Classes
// ***************************
class ux_localRecordList extends  localRecordList {
	
	var $setFields=array();				// Fields to display for the current table
	/**
	 * Initializes the list generation
	 *
	 * @param	integer		Page id for which the list is rendered. Must be >= 0
	 * @param	string		Tablename - if extended mode where only one table is listed at a time.
	 * @param	integer		Browsing pointer.
	 * @param	string		Search word, if any
	 * @param	integer		Number of levels to search down the page tree
	 * @param	integer		Limit of records to be listed.
	 * @return	void
	 */
		
	function start($id,$table,$pointer,$search="",$levels="",$showLimit=0)	{
				global $TCA;

			// Setting internal variables:
		$this->id=intval($id);					// sets the parent id
		if ($TCA[$table])	$this->table=$table;		// Setting single table mode, if table exists:
		$this->firstElementNumber=$pointer;
		$this->searchString=trim($search);
		$this->searchLevels=trim($levels);
		$this->showLimit=t3lib_div::intInRange($showLimit,0,10000);

			// Setting GPvars:
		$this->csvOutput = t3lib_div::_GP('csv') ? TRUE : FALSE;
		$this->sortField = t3lib_div::_GP('sortField');
		$this->sortRev = t3lib_div::_GP('sortRev');
		$this->displayFields = t3lib_div::_GP('displayFields');				
		$this->duplicateField = t3lib_div::_GP('duplicateField');

		if (t3lib_div::_GP('justLocalized'))	{
			$this->localizationRedirect(t3lib_div::_GP('justLocalized'));
		}

			// If thumbnails are disabled, set the "notfound" icon as default:
		if (!$GLOBALS['TYPO3_CONF_VARS']['GFX']['thumbnails'])	{
			$this->thumbScript='gfx/notfound_thumb.gif';
		}

			// Init dynamic vars:
		$this->counter=0;
		$this->JScode='';
		$this->HTMLcode='';

			// limits
		if(isset($this->modTSconfig['properties']['itemsLimitPerTable'])) {
			$this->itemsLimitPerTable = t3lib_div::intInRange(intval($this->modTSconfig['properties']['itemsLimitPerTable']), 1, 10000);
		}
		if(isset($this->modTSconfig['properties']['itemsLimitSingleTable'])) {
			$this->itemsLimitSingleTable = t3lib_div::intInRange(intval($this->modTSconfig['properties']['itemsLimitSingleTable']), 1, 10000);
		}

			// Set select levels:
		$sL=intval($this->searchLevels);
		$this->perms_clause = $GLOBALS['BE_USER']->getPagePermsClause(1);

			// this will hide records from display - it has nothing todo with user rights!!
		if ($pidList = $GLOBALS['BE_USER']->getTSConfigVal('options.hideRecords.pages')) {
			if ($pidList = $GLOBALS['TYPO3_DB']->cleanIntList($pidList)) {
				$this->perms_clause .= ' AND pages.uid NOT IN ('.$pidList.')';
			}
		}

		// Get configuration of collapsed tables from user uc and merge with sanitized GP vars
		$this->tablesCollapsed = is_array($GLOBALS['BE_USER']->uc['moduleData']['db_list.php']) ? $GLOBALS['BE_USER']->uc['moduleData']['db_list.php'] : array();		
		
		
		/**
		 *remove this collapse table configurations setup to ajax functionality.. class.tx_nsdynamicc_collapse.php
		 *
		  $collapseOverride = t3lib_div::_GP('collapse');
		if (is_array($collapseOverride)) {				
			foreach($collapseOverride as $collapseTable => $collapseValue) {
				if (is_array($GLOBALS['TCA'][$collapseTable]) && ($collapseValue == 0 || $collapseValue == 1)) {
					$this->tablesCollapsed[$collapseTable] = $collapseValue;
				}
			}
			// Save modified user uc
			$GLOBALS['BE_USER']->uc['moduleData']['db_list.php'] = $this->tablesCollapsed;
			$GLOBALS['BE_USER']->writeUC($GLOBALS['BE_USER']->uc);
			if (t3lib_div::_GP('returnUrl')) {
				$location = t3lib_div::_GP('returnUrl');
				t3lib_utility_Http::redirect($location);
			}
		} */

		if ($sL>0)	{
			$tree = $this->getTreeObject($id,$sL,$this->perms_clause);
			$this->pidSelect = 'pid IN ('.implode(',',$tree->ids).')';
		} else {
			$this->pidSelect = 'pid='.intval($id);
		}

			// Initialize languages:
		if ($this->localizationView){
			$this->initializeLanguages();
		}
	}
	
	/**
	 * Setting the field names to display in extended list.
	 * Sets the internal variable $this->setFields
	 *
	 * @return	void
	 */
	function setDispFields()	{

		// Getting from session:
		$dispFields = $GLOBALS['BE_USER']->getModuleData('db_list.php/displayFields');
		
		// If fields has been inputted, then set those as the value and push it to session variable:
		if (is_array($this->displayFields))	{
			reset($this->displayFields);
			$tKey = key($this->displayFields);
			$dispFields[$tKey]=$this->displayFields[$tKey];
			$GLOBALS['BE_USER']->pushModuleData('db_list.php/displayFields',$dispFields);
		}		
			// Setting result:
		$this->setFields=$dispFields;		
	}
	
		/**
	 * Creates the listing of records from a single table
	 *
	 * @param	string		Table name
	 * @param	integer		Page id
	 * @param	string		List of fields to show in the listing. Pseudo fields will be added including the record header.
	 * @return	string		HTML table with the listing for the record.
	 */
	function getTable($table,$id,$rowlist)	{
		
		global $TCA, $TYPO3_CONF_VARS;
		
		// Loading all TCA details for this table:
		t3lib_div::loadTCA($table);

    	//Init
		$addWhere = '';
		$titleCol = $TCA[$table]['ctrl']['label'];
		$thumbsCol = $TCA[$table]['ctrl']['thumbnail'];
		$l10nEnabled = $TCA[$table]['ctrl']['languageField'] && $TCA[$table]['ctrl']['transOrigPointerField'] && !$TCA[$table]['ctrl']['transOrigPointerTable'];
		$tableCollapsed = (!$this->tablesCollapsed[$table]) ? false : true;

		// prepare space icon
		$this->spaceIcon = t3lib_iconWorks::getSpriteIcon('empty-empty', array('style' => 'background-position: 0 10px;'));

			// Cleaning rowlist for duplicates and place the $titleCol as the first column always!
		$this->fieldArray=array();
			// title Column
		$this->fieldArray[] = $titleCol;	// Add title column

			// Control-Panel
		if (!t3lib_div::inList($rowlist,'_CONTROL_'))	{
			$this->fieldArray[] = '_CONTROL_';
			$this->fieldArray[] = '_AFTERCONTROL_';
		}
			// Clipboard
		if ($this->showClipboard)	{
			$this->fieldArray[] = '_CLIPBOARD_';
		}
			// Ref
		if (!$this->dontShowClipControlPanels)	{
			$this->fieldArray[]='_REF_';
			$this->fieldArray[]='_AFTERREF_';
		}
			// Path
		if ($this->searchLevels)	{
			$this->fieldArray[]='_PATH_';
		}
			// Localization
		if ($this->localizationView && $l10nEnabled)	{
			$this->fieldArray[] = '_LOCALIZATION_';
			$this->fieldArray[] = '_LOCALIZATION_b';
			$addWhere.=' AND (
				'.$TCA[$table]['ctrl']['languageField'].'<=0
				OR
				'.$TCA[$table]['ctrl']['transOrigPointerField'].' = 0
			)';
		}		
						
			// Cleaning up:
		$this->fieldArray=array_unique(array_merge($this->fieldArray,t3lib_div::trimExplode(',',$rowlist,1)));
		if ($this->noControlPanels)	{
			$tempArray = array_flip($this->fieldArray);
			unset($tempArray['_CONTROL_']);
			unset($tempArray['_CLIPBOARD_']);
			$this->fieldArray = array_keys($tempArray);
		}

			// Creating the list of fields to include in the SQL query:
		$selectFields = $this->fieldArray;
		
		$selectFields[] = 'uid';
		$selectFields[] = 'pid';
		if ($thumbsCol)	$selectFields[] = $thumbsCol;	// adding column for thumbnails
		if ($table=='pages')	{
			if (t3lib_extMgm::isLoaded('cms'))	{
				$selectFields[] = 'module';
				$selectFields[] = 'extendToSubpages';
				$selectFields[] = 'nav_hide';
			}
			$selectFields[] = 'doktype';
		}
		if (is_array($TCA[$table]['ctrl']['enablecolumns']))	{
			$selectFields = array_merge($selectFields,$TCA[$table]['ctrl']['enablecolumns']);
		}				
		
		if ($TCA[$table]['ctrl']['type'])	{
			$selectFields[] = $TCA[$table]['ctrl']['type'];
		}
		if ($TCA[$table]['ctrl']['typeicon_column'])	{
			$selectFields[] = $TCA[$table]['ctrl']['typeicon_column'];
		}
		if ($TCA[$table]['ctrl']['versioningWS'])	{
			$selectFields[] = 't3ver_id';
			$selectFields[] = 't3ver_state';
			$selectFields[] = 't3ver_wsid';
			$selectFields[] = 't3ver_swapmode';		// Filtered out when pages in makeFieldList()
		}
		if ($l10nEnabled)	{
			$selectFields[] = $TCA[$table]['ctrl']['languageField'];
			$selectFields[] = $TCA[$table]['ctrl']['transOrigPointerField'];
		}
		if ($TCA[$table]['ctrl']['label_alt'])	{
			$selectFields = array_merge($selectFields,t3lib_div::trimExplode(',',$TCA[$table]['ctrl']['label_alt'],1));
		}		
		$selectFields = array_unique($selectFields);		// Unique list!
		$selectFields = array_intersect($selectFields,$this->makeFieldList($table,1));		// Making sure that the fields in the field-list ARE in the field-list from TCA!
		$selFieldList = implode(',',$selectFields);		// implode it into a list of fields for the SQL-statement.
		$this->selFieldList = $selFieldList;
		
		/**
		 * @hook			DB-List getTable
		 * @date			2007-11-16
		 * @request		Malte Jansen  <mail@maltejansen.de>
		 */
		if(is_array($TYPO3_CONF_VARS['SC_OPTIONS']['typo3/class.db_list_extra.inc']['getTable'])) {
			foreach($TYPO3_CONF_VARS['SC_OPTIONS']['typo3/class.db_list_extra.inc']['getTable'] as $classData) {
				$hookObject = t3lib_div::getUserObj($classData);

				if(!($hookObject instanceof t3lib_localRecordListGetTableHook)) {
					throw new UnexpectedValueException('$hookObject must implement interface t3lib_localRecordListGetTableHook', 1195114460);
				}

				$hookObject->getDBlistQuery($table, $id, $addWhere, $selFieldList, $this);
			}
		}

			// Create the SQL query for selecting the elements in the listing:
		if ($this->csvOutput) {	// do not do paging when outputting as CSV
			$this->iLimit = 0;
		}

		if ($this->firstElementNumber > 2 && $this->iLimit > 0) {
				// Get the two previous rows for sorting if displaying page > 1
			$this->firstElementNumber = $this->firstElementNumber - 2;
			$this->iLimit = $this->iLimit + 2;
			$queryParts = $this->makeQueryArray($table, $id,$addWhere,$selFieldList);	// (API function from class.db_list.inc)
			$this->firstElementNumber = $this->firstElementNumber + 2;
			$this->iLimit = $this->iLimit - 2;
		} else {
			$queryParts = $this->makeQueryArray($table, $id,$addWhere,$selFieldList);	// (API function from class.db_list.inc)
		}
		
		
		$this->setTotalItems($queryParts);		// Finding the total amount of records on the page (API function from class.db_list.inc)

		// Init:
		$dbCount = 0;
		$out = '';
		$listOnlyInSingleTableMode = $this->listOnlyInSingleTableMode && !$this->table;
		
		// If the count query returned any number of records, we perform the real query, selecting records.
		if ($this->totalItems){
			// Fetch records only if not in single table mode or if in multi table mode and not collapsed
			//if ($listOnlyInSingleTableMode || (!$this->table && $tableCollapsed)) {
			if ($listOnlyInSingleTableMode && (!$this->table)) {
				$dbCount = $this->totalItems;				
			} else {				
					// set the showLimit to the number of records when outputting as CSV
				if ($this->csvOutput) {
					$this->showLimit = $this->totalItems;
					$this->iLimit = $this->totalItems;
				}				
				$result = $GLOBALS['TYPO3_DB']->exec_SELECT_queryArray($queryParts);
				$dbCount = $GLOBALS['TYPO3_DB']->sql_num_rows($result);
			}
		}

		// If any records was selected, render the list:
		if ($dbCount){
				// Half line is drawn between tables:
			if (!$listOnlyInSingleTableMode)	{
				$theData = Array();
				if (!$this->table && !$rowlist)	{
					$theData[$titleCol] = '<img src="clear.gif" width="'.($GLOBALS['SOBE']->MOD_SETTINGS['bigControlPanel']?'230':'350').'" height="1" alt="" />';
					if (in_array('_CONTROL_',$this->fieldArray))	$theData['_CONTROL_']='';
					if (in_array('_CLIPBOARD_',$this->fieldArray))	$theData['_CLIPBOARD_']='';
				}
				$out.=$this->addelement(0,'',$theData,'class="c-table-row-spacer"',$this->leftMargin);
			}

			// Header line is drawn
			$theData = Array();
			if ($this->disableSingleTableView)	{
				$theData[$titleCol] = '<span class="c-table">'.$GLOBALS['LANG']->sL($TCA[$table]['ctrl']['title'],1).'</span> ('.$this->totalItems.')';
			} else {				
				$theData[$titleCol] = $this->linkWrapTable($table, '<span class="c-table">' . $GLOBALS['LANG']->sL($TCA[$table]['ctrl']['title'], TRUE) . '</span> (' . $this->totalItems . ') ' . 	($this->table ? t3lib_iconWorks::getSpriteIcon('actions-view-table-collapse', array('title' => $GLOBALS['LANG']->getLL('contractView', TRUE))) : t3lib_iconWorks::getSpriteIcon('actions-view-table-expand', array('title' => $GLOBALS['LANG']->getLL('expandView', TRUE)))));			
			}

				// CSH:				
			$theData[$titleCol].= t3lib_BEfunc::cshItem($table,'',$this->backPath,'',FALSE,'margin-bottom:0px; white-space: normal;');

			if ($listOnlyInSingleTableMode)	{
				$out.='
					<tr>
						<td class="t3-row-header" style="width:95%;">' . $theData[$titleCol] . '</td>
					</tr>';

				if ($GLOBALS['BE_USER']->uc["edit_showFieldHelp"])	{
					$GLOBALS['LANG']->loadSingleTableDescription($table);
					if (isset($GLOBALS['TCA_DESCR'][$table]['columns']['']))	{
						$onClick = 'vHWin=window.open(\'view_help.php?tfID='.$table.'.\',\'viewFieldHelp\',\'height=400,width=600,status=0,menubar=0,scrollbars=1\');vHWin.focus();return false;';
						$out.='
					<tr>
						<td class="c-tableDescription">'.t3lib_BEfunc::helpTextIcon($table,'',$this->backPath,TRUE).$GLOBALS['TCA_DESCR'][$table]['columns']['']['description'].'</td>
					</tr>';
					}
				}
			} else {
				// Render collapse button if in multi table mode
				$collapseIcon = '';
				if (!$this->table) {
					$collapseIcon = '<a class="collapse_click_menu" href="#" data-collapse="' . $table . ',' . ($tableCollapsed ? '0' : '1') . '" title="' . ($tableCollapsed ? $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.expandTable', TRUE) : $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.collapseTable', TRUE)) . '">' .
							($tableCollapsed ? t3lib_iconWorks::getSpriteIcon('actions-view-list-expand', array('class' => 'collapseIcon')) : t3lib_iconWorks::getSpriteIcon('actions-view-list-collapse', array('class' => 'collapseIcon'))) .
						'</a>';
				}				
				//relative path for dynamic column icon
				$dIcon = $this->dIcon($id, $table); 				

				$out .=  $this->addElement(1, $collapseIcon, $theData, ' class="t3-row-header"', '', '', $dIcon);
				$out = '<thead>'. $out .'</thead>';

			}
			
			// Render table rows only if in multi table view and not collapsed or if in single table view			
			if (!$listOnlyInSingleTableMode || ($this->table)) {				
				// Fixing a order table for sortby tables
				$this->currentTable = array();
				$currentIdList = array();
				$doSort = ($TCA[$table]['ctrl']['sortby'] && !$this->sortField);
					
				$prevUid = 0;
				$prevPrevUid = 0;
					
				// Get first two rows and initialize prevPrevUid and prevUid if on page > 1
				if ($this->firstElementNumber > 2 && $this->iLimit > 0) {					
					$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);
					$prevPrevUid = -(int) $row['uid'];
					$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);
					$prevUid = $row['uid'];
				}
				
				$accRows = array();	// Accumulate rows here				
				while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result))	{
						// In offline workspace, look for alternative record:

					t3lib_BEfunc::workspaceOL($table, $row, $GLOBALS['BE_USER']->workspace, TRUE);
					
					if (is_array($row))	{
						$accRows[] = $row;
						$currentIdList[] = $row['uid'];
						if ($doSort)	{							
							if ($prevUid)	{								
								$this->currentTable['prev'][$row['uid']] = $prevPrevUid;								
								$this->currentTable['next'][$prevUid] = '-'.$row['uid'];
								$this->currentTable['prevUid'][$row['uid']] = $prevUid;
							}
							$prevPrevUid = isset($this->currentTable['prev'][$row['uid']]) ? -$prevUid : $row['pid'];
							$prevUid=$row['uid'];							
						}
					}
				}
				
				$GLOBALS['TYPO3_DB']->sql_free_result($result);

				$this->totalRowCount = count($accRows);					
					// CSV initiated
				if ($this->csvOutput) $this->initCSV();

					// Render items:
				$this->CBnames=array();
				$this->duplicateStack=array();
				$this->eCounter=$this->firstElementNumber;
					
				$iOut = '';
				$cc = 0;

				foreach($accRows as $row)	{					
					// Render item row if counter < limit									
					if ($cc < $this->iLimit) {
						$cc++;
						$this->translations = FALSE;						
						$iOut.= $this->renderListRow($table,$row,$cc,$titleCol,$thumbsCol);
						
							// If localization view is enabled it means that the selected records are either default or All language and here we will not select translations which point to the main record:
						if ($this->localizationView && $l10nEnabled)	{
								// For each available translation, render the record:
							if (is_array($this->translations)) {
								foreach ($this->translations as $lRow) {
										// $lRow isn't always what we want - if record was moved we've to work with the placeholder records otherwise the list is messed up a bit
									if ($row['_MOVE_PLH_uid'] && $row['_MOVE_PLH_pid']) {
										$tmpRow = t3lib_BEfunc::getRecordRaw($table, 't3ver_move_id="'.intval($lRow['uid']) . '" AND pid="' . $row['_MOVE_PLH_pid'] . '" AND t3ver_wsid=' . $row['t3ver_wsid'] . t3lib_beFunc::deleteClause($table), $selFieldList);
										$lRow = is_array($tmpRow)?$tmpRow:$lRow;
									}
										// In offline workspace, look for alternative record:
									t3lib_BEfunc::workspaceOL($table, $lRow, $GLOBALS['BE_USER']->workspace, true);
									if (is_array($lRow) && $GLOBALS['BE_USER']->checkLanguageAccess($lRow[$TCA[$table]['ctrl']['languageField']]))	{
										$currentIdList[] = $lRow['uid'];
										$iOut.=$this->renderListRow($table,$lRow,$cc,$titleCol,$thumbsCol,18);										
									}
								}
							}
						}
					}
						// Counter of total rows incremented:
					$this->eCounter++;
				}
					
					// Record navigation is added to the beginning and end of the table if in single table mode
				if ($this->table) {
					$pageNavigation = $this->renderListNavigation();
					$iOut = $pageNavigation . $iOut . $pageNavigation;
				} else {
						// show that there are more records than shown
					if ($this->totalItems > $this->itemsLimitPerTable) {
						$countOnFirstPage = $this->totalItems > $this->itemsLimitSingleTable ? $this->itemsLimitSingleTable : $this->totalItems;
						$hasMore = ($this->totalItems > $this->itemsLimitSingleTable);
						$iOut .= '<tr><td colspan="' . count($this->fieldArray) . '" style="padding:5px;">
								<a href="'.htmlspecialchars($this->listURL() . '&table=' . rawurlencode($table)) . '">' .
								'<img' . t3lib_iconWorks::skinImg($this->backPath,'gfx/pildown.gif', 'width="14" height="14"') .' alt="" />'.
								' <i>[1 - ' . $countOnFirstPage . ($hasMore ? '+' : '') . ']</i></a>
								</td></tr>';
						}

				}

					// The header row for the table is now created:
				$out_header = $this->renderListHeader($table,$currentIdList);
			}
			
			//get the prev item id for sort to easy
			list ($key1, $prevVal) = each($this->currentTable['prev']);
						
				//combind the list of records header and content to <tbody> dom element
				$iOut = '<tbody'.(($tableCollapsed && !$this->table)? " style='display: none'": "").' id="typo3_'.$table.'" data-tablename ="'.$table.'" data-vc="'.rawurlencode($GLOBALS['BE_USER']->veriCode()).'" data-prev="'.(isset($prevVal)? $prevVal : '').'" >'.$out_header. $iOut.'</tbody>';
				// The list of records is added after the header:
				$out .= $iOut;
					unset($iOut);
					unset($out_header);
				// ... and it is all wrapped in a table:
				$out='
			<!--
				DB listing of elements:	"'.htmlspecialchars($table).'"
			-->
				<table border="0" cellpadding="0" cellspacing="0" id="test'.$table.'" class="typo3-dblist'.($listOnlyInSingleTableMode?' typo3-dblist-overview':'').'">'.$out.'</table>';

			// Output csv if...
			if ($this->csvOutput)	$this->outputCSV($table);	// This ends the page with exit.
		}
			// Return content:			
		return $out;
	}
	


//addElement method overwrite here and 	
function addElement($h, $icon, $data, $trParams = '', $lMargin = '', $altLine = '', $dcolumn ='', $moveId=''){
		
	$noWrap = ($this->no_noWrap) ? '' : ' nowrap="nowrap"';
	// Start up:
	$out='
	<!-- Element, begin: -->
	<tr '.$trParams.'>';

	//if move icon is true
	if($moveId !='' && !$this->sortField && !$this->searchLevels) {
		$move_icon_dpath = t3lib_extMgm::extRelPath("nsdynamicc").'icon/move_row_new.png';		
		$move_icon = '<span><img class="handle" id="handle_sort_'.$moveId.'" src="'.$move_icon_dpath.'" alt="Move" /></span>';
		$icon = $move_icon.$icon;
	}
		// Show icon and lines
	if ($this->showIcon)	{
		$out.='
		<td nowrap="nowrap" class="col-icon move-icon">';

		if (!$h)	{			
			$out.='<img src="clear.gif" width="1" height="8" alt="" />';
		} else {
			for ($a=0;$a<$h;$a++)	{
				if (!$a)	{
					if ($icon)	$out.= $icon;
				} else {
				}
			}
		}
		$out.='</td>
		';
	}

	// Init rendering.
	$colsp='';
	$lastKey='';
	$c=0;
	$ccount=0;
 				 	
	// Traverse field array which contains the data to present:			
	foreach ($this->fieldArray as $vKey) {
		if (isset($data[$vKey]))	{
			if ($lastKey)	{
				$cssClass = $this->addElement_tdCssClass[$lastKey];
				if($this->oddColumnsCssClass && $ccount % 2 == 0) {
					$cssClass = implode(' ', array($this->addElement_tdCssClass[$lastKey], $this->oddColumnsCssClass));
				}
	
				$out.='
					<td'.
					$noWrap.
					' class="' . $cssClass . '"'.
					$colsp.
					$this->addElement_tdParams[$lastKey].
					'>'.$data[$lastKey].'</td>';
			}
			$lastKey=$vKey;
			$c=1;
			$ccount++;
		} else {
			if (!$lastKey) {$lastKey=$vKey;}
			$c++;
		}
		if ($c>1)	{$colsp=' colspan="'.$c.'"';} else {$colsp='';}
	}
	
	if ($lastKey) {
		$cssClass = $this->addElement_tdCssClass[$lastKey];
		if($this->oddColumnsCssClass) {
			$cssClass = implode(' ', array($this->addElement_tdCssClass[$lastKey], $this->oddColumnsCssClass));
		}
		
		$out.='
			<td'.$noWrap.' class="' . $cssClass . '"' . $colsp.$this->addElement_tdParams[$lastKey].'>'.$data[$lastKey].'</td>';	
			}
	//add dynamic column a small icon at the top-right corner of the record table
	if($dcolumn){				
		$out .= '<td style="padding-right: 3px; float: right;">'.$dcolumn.'</td>';
	}
	else{
		$out .= '<td></td>';
	}	
	// End row
	$out.='
	</tr>';
	// Return row.
	return $out;
  }
	
		/**
	 * Genarate the dynamic column icon with its attributes
	 *
	 * @param	 string		Table name
	 * @param	 integer		Page id
	 * @return string		dynamic column icon HTML
	 */
	function dIcon($id,$table){
		//get the path of dynamic column icon
		$dpath = t3lib_extMgm::extRelPath("nsdynamicc").'icon/icon-column.png';
		$dHTML = '<a class="dcolumn" data-dinfo="'.$id.','.$table.'" href="#"><img  src="'.$dpath.'" alt="dcolumn" /></a>';
		return $dHTML; 
	}

	
}
// Include extension?
	
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/nsdynamicc/class.ux_db_list_extra.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/nsdynamicc/class.ux_db_list_extra.php']);
}

?>
