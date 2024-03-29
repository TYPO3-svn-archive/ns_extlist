<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2009 Kasper Skaarhoj (kasperYYYY@typo3.com)
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
* Contains class with time tracking functions
*
* $Id: class.t3lib_timetrack.php 7592 2010-05-13 17:11:55Z baschny $
* Revised for TYPO3 3.6 July/2003 by Kasper Skaarhoj
* XHTML compliant
*
* @author  Kasper Skaarhoj <kasperYYYY@typo3.com>
*/
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   88: class t3lib_timeTrack
 *
 *              SECTION: Logging parsing times in the scripts
 *  144:     function start()
 *  164:     function push($tslabel, $value='')
 *  189:     function pull($content='')
 *  207:     function setTSlogMessage($content,$num=0)
 *  221:     function setTSselectQuery($query,$msg)
 *  234:     function incStackPointer()
 *  245:     function decStackPointer()
 *  255:     function mtime()
 *  265:     function convertMicrotime($microtime)
 *
 *              SECTION: Printing the parsing time information (for Admin Panel)
 *  298:     function printTSlog()
 *  447:     function fixContent(&$arr, $content, $depthData='', $first=0, $vKey='')
 *  511:     function fixCLen($c,$v)
 *  527:     function fw($str)
 *  541:     function createHierarchyArray(&$arr,$pointer,$uniqueId)
 *  561:     function debug_typo3PrintError($header,$text,$js,$baseUrl='')
 *
 * TOTAL FUNCTIONS: 15
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */











/**
 * Frontend Timetracking functions
 *
 * Is used to register how much time is used with operations in TypoScript
 * Used by index_ts
 *
 * @author  Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage t3lib
 * @see t3lib_tsfeBeUserAuth, tslib_fe, tslib_cObj, TSpagegen
 */
class t3lib_timeTrack {
	var $starttime = 0;             // Is loaded with the millisecond time when this object is created

	var $LR = 1;                    // Log Rendering flag. If set, ->push() and ->pull() is called from the cObj->cObjGetSingle(). This determines whether or not the TypoScript parsing activity is logged. But it also slows down the rendering
	var $printConf=array(
		'showParentKeys' => 1,
		'contentLength' => 10000,       // Determines max lenght of displayed content before it gets cropped.
		'contentLength_FILE' => 400,    // Determines max lenght of displayed content FROM FILE cObjects before it gets cropped. Reason is that most FILE cObjects are huge and often used as template-code.
		'flag_tree' => 1,
		'flag_messages' => 1,
		'flag_queries' => 0,
		'flag_content' => 0,
		'allTime' => 0,
		'keyLgd' => 40,
		'factor' => 10,
		'col' => '#D9D5C9',
		'highlight_col' => '#FF9933'
	);

	var $wrapError = array();
	var $wrapIcon = array();
	var $uniqueCounter = 0;
	var $tsStack = array(array());
	var $tsStackLevel = 0;
	var $tsStackLevelMax = array();
	var $tsStackLog = array();
	var $tsStackPointer = 0;
	var $currentHashPointer = array();

	var $highlightLongerThan = 0;	// Log entries that take than this number of milliseconds (own time) will be highlighted during log display. Set 0 to disable highlighting.







	/*******************************************
	*
	* Logging parsing times in the scripts
	*
	*******************************************/

	/**
	 * Constructor
	 * Sets the starting time
	 *
	 * @return	void
	 */
	public function start() {
		$this->wrapError = array(
			0 => array('',''),
			1 => array('<strong>','</strong>'),
			2 => array('<strong style="color:#ff6600;">','</strong>'),
			3 => array('<strong style="color:#ff0000;">','</strong>')
		);

		$this->wrapIcon = array(
			0 => '',
			1 => '<img src="'.TYPO3_mainDir.'gfx/icon_note.gif" width="18" height="16" align="absmiddle" alt="" />',
			2 => '<img src="'.TYPO3_mainDir.'gfx/icon_warning.gif" width="18" height="16" align="absmiddle" alt="" />',
			3 => '<img src="'.TYPO3_mainDir.'gfx/icon_fatalerror.gif" width="18" height="16" align="absmiddle" alt="" />'
		);

		$this->starttime = $this->getMilliseconds();
	}

	/**
	 * Pushes an element to the TypoScript tracking array
	 *
	 * @param	string		Label string for the entry, eg. TypoScript property name
	 * @param	string		Additional value(?)
	 * @return	void
	 * @see tslib_cObj::cObjGetSingle(), pull()
	 */
	public function push($tslabel, $value = '') {
		array_push($this->tsStack[$this->tsStackPointer], $tslabel);
		array_push($this->currentHashPointer, 'timetracker_'.$this->uniqueCounter++);

		$this->tsStackLevel++;
		$this->tsStackLevelMax[] = $this->tsStackLevel;

			// setTSlog
		$k = end($this->currentHashPointer);
		$this->tsStackLog[$k] = array(
			'level' => $this->tsStackLevel,
			'tsStack' => $this->tsStack,
			'value' => $value,
			'starttime' => microtime(true),
			'stackPointer' => $this->tsStackPointer
		);
	}

	/**
	 * Pulls an element from the TypoScript tracking array
	 *
	 * @param	string		The content string generated within the push/pull part.
	 * @return	void
	 * @see tslib_cObj::cObjGetSingle(), push()
	 */
	public function pull($content = '') {
		$k = end($this->currentHashPointer);
		$this->tsStackLog[$k]['endtime'] =  microtime(true);
		$this->tsStackLog[$k]['content'] = $content;

		$this->tsStackLevel--;
		array_pop($this->tsStack[$this->tsStackPointer]);
		array_pop($this->currentHashPointer);
	}

	/**
	 * Logs the TypoScript entry
	 *
	 * @param	string		The message string
	 * @param	integer		Message type: 0: information, 1: message, 2: warning, 3: error
	 * @return	void
	 * @see tslib_cObj::CONTENT()
	 */
	public function setTSlogMessage($content, $num = 0) {
		end($this->currentHashPointer);
		$k = current($this->currentHashPointer);

		if (strlen($content)>30) {	// Enlarge the "details" column by adding a wide clear.gif
			$placeholder = '<br /><img src="'.TYPO3_mainDir.'clear.gif" width="300" height="1" alt="" />';
		}
		$this->tsStackLog[$k]['message'][] = $this->wrapIcon[$num].$this->wrapError[$num][0].htmlspecialchars($content).$this->wrapError[$num][1].$placeholder;
	}

	/**
	 * Set TSselectQuery - for messages in TypoScript debugger.
	 *
	 * @param	array		Query array
	 * @param	string		Message/Label to attach
	 * @return	void
	 */
	public function setTSselectQuery(array $data, $msg = '') {
		end($this->currentHashPointer);
		$k = current($this->currentHashPointer);

		if (strlen($msg)) {
			$data['msg'] = $msg;
		}

		$this->tsStackLog[$k]['selectQuery'][] = $data;
	}

	/**
	 * Increases the stack pointer
	 *
	 * @return	void
	 * @see decStackPointer(), TSpagegen::renderContent(), tslib_cObj::cObjGetSingle()
	 */
	public function incStackPointer() {
		$this->tsStackPointer++;
		$this->tsStack[$this->tsStackPointer]=array();
	}

	/**
	 * Decreases the stack pointer
	 *
	 * @return	void
	 * @see incStackPointer(), TSpagegen::renderContent(), tslib_cObj::cObjGetSingle()
	 */
	public function decStackPointer() {
		unset($this->tsStack[$this->tsStackPointer]);
		$this->tsStackPointer--;
	}

	/**
	 * Returns the current time in milliseconds
	 *
	 * @return	integer
	 * @deprecated	since TYPO3 4.3, this function will be removed in TYPO3 4.5, use getDifferenceToStarttime() instead
	 */
	protected function mtime() {
		t3lib_div::logDeprecatedFunction();

		return $this->getDifferenceToStarttime();
	}

	/**
	 * Returns microtime input to milliseconds
	 *
	 * @param	string		PHP microtime string
	 * @return	integer
	 * @deprecated	since TYPO3 4.3, this function will be removed in TYPO3 4.5, use getMilliseconds() instead that expects microtime as float instead of a string
	 */
	public function convertMicrotime($microtime) {
		t3lib_div::logDeprecatedFunction();

		$parts = explode(' ',$microtime);
		return round(($parts[0]+$parts[1])*1000);
	}

	/**
	 * Gets a microtime value as milliseconds value.
	 *
	 * @param	float		$microtime: The microtime value - if not set the current time is used
	 * @return	integer		The microtime value as milliseconds value
	 */
	public function getMilliseconds($microtime = NULL) {
		if (!isset($microtime)) {
			$microtime = microtime(true);
		}
		return round($microtime * 1000);
	}

	/**
	 * Gets the difference between a given microtime value and the starting time as milliseconds.
	 *
	 * @param	float		$microtime: The microtime value - if not set the current time is used
	 * @return	integer		The difference between a given microtime value and starting time as milliseconds
	 */
	public function getDifferenceToStarttime($microtime = NULL) {
		return ($this->getMilliseconds($microtime) - $this->starttime);
	}

















	/*******************************************
	*
	* Printing the parsing time information (for Admin Panel)
	*
	*******************************************/

	/**
	 * Print TypoScript parsing log
	 *
	 * @return	string		HTML table with the information about parsing times.
	 * @see t3lib_tsfeBeUserAuth::extGetCategory_tsdebug()
	 */
	public function printTSlog() {
			// Calculate times and keys for the tsStackLog
		foreach ($this->tsStackLog as $uniqueId => &$data) {
			$data['endtime'] = $this->getDifferenceToStarttime($data['endtime']);
			$data['starttime'] = $this->getDifferenceToStarttime($data['starttime']);
			$data['deltatime'] = $data['endtime'] - $data['starttime'];
			$data['key'] = implode($data['stackPointer'] ? '.' : '/', end($data['tsStack']));
		}

			// Create hierarchical array of keys pointing to the stack
		$arr = array();
		foreach ($this->tsStackLog as $uniqueId => $data) {
			$this->createHierarchyArray($arr, $data['level'], $uniqueId);
		}
			// Parsing the registeret content and create icon-html for the tree
		$this->tsStackLog[$arr['0.'][0]]['content'] = $this->fixContent($arr['0.']['0.'], $this->tsStackLog[$arr['0.'][0]]['content'], '', 0, $arr['0.'][0]);

			// Displaying the tree:
		$outputArr = array();
		$outputArr[] = $this->fw('TypoScript Key');
		$outputArr[] = $this->fw('Value');

		if ($this->printConf['allTime']) {
			$outputArr[] = $this->fw('Time');
			$outputArr[] = $this->fw('Own');
			$outputArr[] = $this->fw('Sub');
			$outputArr[] = $this->fw('Total');
		} else {
			$outputArr[] = $this->fw('Own');
		}

		$outputArr[] = $this->fw('Details');

		$out = '';
		foreach ($outputArr as $row) {
			$out.= '
				<th style="text-align:center; background:#ABBBB4;"><strong>'.$row.'</strong></th>';
		}
		$out = '<tr>'.$out.'</tr>';

		$flag_tree = $this->printConf['flag_tree'];
		$flag_messages = $this->printConf['flag_messages'];
		$flag_content = $this->printConf['flag_content'];
		$flag_queries = $this->printConf['flag_queries'];
		$keyLgd = $this->printConf['keyLgd'];
		$factor = $this->printConf['factor'];
		$col = $this->printConf['col'];
		$highlight_col = $this->printConf['highlight_col'];

		$c=0;
		foreach ($this->tsStackLog as $uniqueId => $data) {
			$bgColor = ' background-color:'.($c%2 ? t3lib_div::modifyHTMLColor($col,$factor,$factor,$factor) : $col).';';
			if ($this->highlightLongerThan && intval($data['owntime']) > intval($this->highlightLongerThan)) {
				$bgColor = ' background-color:'.$highlight_col.';';
			}

			$item = '';
			if (!$c) {	// If first...
				$data['icons'] = '';
				$data['key'] = 'Script Start';
				$data['value'] = '';
			}


				// key label:
			$keyLabel = '';
			if (!$flag_tree && $data['stackPointer']) {
				$temp = array();
				foreach ($data['tsStack'] as $k => $v) {
					$temp[] = t3lib_div::fixed_lgd_cs(implode($v,$k?'.':'/'),-$keyLgd);
				}
				array_pop($temp);
				$temp = array_reverse($temp);
				array_pop($temp);
				if (count($temp)) {
					$keyLabel = '<br /><span style="color:#999999;">'.implode($temp,'<br />').'</span>';
				}
			}
			if ($flag_tree) {
				$tmp = t3lib_div::trimExplode('.',$data['key'],1);
				$theLabel = end($tmp);
			} else {
				$theLabel = $data['key'];
			}
			$theLabel = t3lib_div::fixed_lgd_cs($theLabel, -$keyLgd);
			$theLabel = $data['stackPointer'] ? '<span style="color:maroon;">'.$theLabel.'</span>' : $theLabel;
			$keyLabel = $theLabel.$keyLabel;
			$item.= '<td valign="top" style="text-align:left; white-space:nowrap; padding-left:2px;'.$bgColor.'">'.($flag_tree?$data['icons']:'').$this->fw($keyLabel).'</td>';

				// key value:
			$keyValue = $data['value'];
			$item.= '<td valign="top" style="text-align:left; white-space:nowrap;'.$bgColor.'">'.$this->fw(htmlspecialchars($keyValue)).'</td>';

			if ($this->printConf['allTime']) {
				$item.= '<td valign="top" style="text-align:right; white-space:nowrap;'.$bgColor.'"> '.$this->fw($data['starttime']).'</td>';
				$item.= '<td valign="top" style="text-align:right; white-space:nowrap;'.$bgColor.'"> '.$this->fw($data['owntime']).'</td>';
				$item.= '<td valign="top" style="text-align:left; white-space:nowrap;'.$bgColor.'"> '.$this->fw($data['subtime'] ? '+'.$data['subtime'] : '').'</td>';
				$item.= '<td valign="top" style="text-align:left; white-space:nowrap;'.$bgColor.'"> '.$this->fw($data['subtime'] ? '='.$data['deltatime'] : '').'</td>';
			} else {
				$item.= '<td valign="top" style="text-align:right; white-space:nowrap;'.$bgColor.'"> '.$this->fw($data['owntime']).'</td>';
			}


				// messages:
			$msgArr = array();
			$msg = '';
			if ($flag_messages && is_array($data['message'])) {
				foreach ($data['message'] as $v) {
					$msgArr[] = nl2br($v);
				}
			}
			if ($flag_queries && is_array($data['selectQuery'])) {
				$msgArr[] = t3lib_div::view_array($data['selectQuery']);
			}
			if ($flag_content && strcmp($data['content'],'')) {
				$maxlen = 120;
				if (preg_match_all('/(\S{'.$maxlen.',})/', $data['content'], $reg)) {	// Break lines which are too longer than $maxlen chars (can happen if content contains long paths...)
					foreach ($reg[1] as $key=>$match) {
						$match = preg_replace('/(.{'.$maxlen.'})/', '$1 ', $match);
						$data['content'] = str_replace($reg[0][$key], $match, $data['content']);
					}
				}
				$msgArr[] = '<span style="color:#000066;">'.nl2br($data['content']).'</span>';
			}
			if (count($msgArr)) {
				$msg = implode($msgArr,'<hr />');
			}
			$item.= '<td valign="top" style="text-align:left;'.$bgColor.'">'.$this->fw($msg).'</td>';
			$out.= '<tr>'.$item.'</tr>';
			$c++;
		}
		$out = '<table border="0" cellpadding="0" cellspacing="0" summary="">'.$out.'</table>';
		return $out;
	}

	/**
	 * Recursively generates the content to display
	 *
	 * @param	array		Array which is modified with content. Reference
	 * @param	string		Current content string for the level
	 * @param	string		Prefixed icons for new PM icons
	 * @param	boolean		Set this for the first call from outside.
	 * @param	string		Seems to be the previous tsStackLog key
	 * @return	string		Returns the $content string generated/modified. Also the $arr array is modified!
	 */
	protected function fixContent(&$arr, $content, $depthData = '', $first = 0, $vKey = '') {
		$ac=0;
		$c=0;
			// First, find number of entries
		foreach ($arr as $k => $v) {
			if (t3lib_div::testInt($k)) {
				$ac++;
			}
		}
			// Traverse through entries
		$subtime=0;
		foreach ($arr as $k => $v) {
			if (t3lib_div::testInt($k)) {
				$c++;

				$deeper = is_array($arr[$k.'.']) ? 1 : 0;
				$PM = 'join';
				$LN = ($ac==$c)?'blank':'line';
				$BTM = ($ac==$c)?'bottom':'';
				$PM = is_array($arr[$k.'.']) ? ($deeper ? 'minus':'plus') : 'join';
				$this->tsStackLog[$v]['icons'] = $depthData.($first?'':'<img src="'.TYPO3_mainDir.'gfx/ol/'.$PM.$BTM.'.gif" width="18" height="16" align="top" border="0" alt="" />');

				if (strlen($this->tsStackLog[$v]['content'])) {
					$content = str_replace($this->tsStackLog[$v]['content'],$v, $content);
				}
				if (is_array($arr[$k.'.'])) {
					$this->tsStackLog[$v]['content'] = $this->fixContent($arr[$k.'.'], $this->tsStackLog[$v]['content'], $depthData.($first?'':'<img src="'.TYPO3_mainDir.'gfx/ol/'.$LN.'.gif" width="18" height="16" align="top" border="0" alt="" />'), 0, $v);
				} else {
					$this->tsStackLog[$v]['content'] = $this->fixCLen($this->tsStackLog[$v]['content'], $this->tsStackLog[$v]['value']);
					$this->tsStackLog[$v]['subtime'] = '';
					$this->tsStackLog[$v]['owntime'] = $this->tsStackLog[$v]['deltatime'];
				}
				$subtime+= $this->tsStackLog[$v]['deltatime'];
			}
		}
			// Set content with special chars
		if (isset($this->tsStackLog[$vKey])) {
			$this->tsStackLog[$vKey]['subtime'] = $subtime;
			$this->tsStackLog[$vKey]['owntime'] = $this->tsStackLog[$vKey]['deltatime']-$subtime;
		}
		$content=$this->fixCLen($content, $this->tsStackLog[$vKey]['value']);

			// Traverse array again, this time substitute the unique hash with the red key
		foreach ($arr as $k => $v) {
			if (t3lib_div::testInt($k)) {
				if (strlen($this->tsStackLog[$v]['content'])) {
					$content = str_replace($v, '<strong style="color:red;">['.$this->tsStackLog[$v]['key'].']</strong>', $content);
				}
			}
		}
			// return the content
		return $content;
	}

	/**
	 * Wraps the input content string in green colored span-tags IF the length o fthe input string exceeds $this->printConf['contentLength'] (or $this->printConf['contentLength_FILE'] if $v == "FILE"
	 *
	 * @param	string		The content string
	 * @param	string		Command: If "FILE" then $this->printConf['contentLength_FILE'] is used for content length comparison, otherwise $this->printConf['contentLength']
	 * @return	string
	 */
	protected function fixCLen($c, $v) {
		$len = $v=='FILE'?$this->printConf['contentLength_FILE']:$this->printConf['contentLength'];
		if (strlen($c)>$len) {
			$c = '<span style="color:green;">'.htmlspecialchars(t3lib_div::fixed_lgd_cs($c,$len)).'</span>';
		} else {
			$c = htmlspecialchars($c);
		}
		return $c;
	}

	/**
	 * Wraps input string in a <span> tag with black verdana font
	 *
	 * @param	string		The string to be wrapped
	 * @return	string
	 */
	protected function fw($str) {
		return '<span style="font-family:Verdana,Arial,Helvetica,sans-serif; font-size:10px; color:black; vertical-align:top;">'.$str.'&nbsp;</span>';
	}

	/**
	 * Helper function for internal data manipulation
	 *
	 * @param	array		Array (passed by reference) and modified
	 * @param	integer		Pointer value
	 * @param	string		Unique ID string
	 * @return	void
	 * @access private
	 * @see printTSlog()
	 */
	protected function createHierarchyArray(&$arr, $pointer, $uniqueId) {
		if (!is_array($arr)) {
			$arr = array();
		}
		if ($pointer>0) {
			end($arr);
			$k = key($arr);
			$this->createHierarchyArray($arr[intval($k).'.'],$pointer-1,$uniqueId);
		} else {
			$arr[] = $uniqueId;
		}
	}

	/**
	 * This prints out a TYPO3 error message.
	 *
	 * @param	string		Header string
	 * @param	string		Message string
	 * @param	boolean		If set, then this will produce a alert() line for inclusion in JavaScript.
	 * @param	string		URL for the <base> tag (if you want it)
	 * @return	string
	 */
	public function debug_typo3PrintError($header, $text, $js, $baseUrl = '') {
		if ($js) {
			$errorMessage = 'alert(\'' . t3lib_div::slashJS($header . '\n' . $text) . '\');';
		} else {
			$errorMessage = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
					"http://www.w3.org/TR/xhtml1/DTD/xhtml11.dtd">
				<?xml version="1.0" encoding="utf-8"?>
				<html>
					<head>
						'.($baseUrl ? '<base href="'.htmlspecialchars($baseUrl).'" />' : '').'
						<title>Error!</title>
						<style type="text/css"><!--/*--><![CDATA[/*><!--*/
							body { font-family:Verdana,Arial,Helvetica,sans-serif; font-size: 90%; text-align: center; background-color: #ffffff; }
							h1 { font-size: 1.2em; margin: 0 0 1em 0; }
							p { margin: 0; text-align: left; }
							img { border: 0; margin: 10px 0; }
							div.center div { margin: 0 auto; }
							.errorBox { width: 400px; padding: 0.5em; border: 1px solid black; background-color: #F4F0E8; }
						/*]]>*/--></style>
					</head>
					<body>
						<div class="center">
							<img src="'.TYPO3_mainDir.'gfx/typo3logo.gif" width="123" height="34" alt="" />
							<div class="errorBox">
								<h1>'.$header.'</h1>
								<p>'.$text.'</p>
							</div>
						</div>
					</body>
				</html>';
		}

			// Hook to modify error message
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_timetrack.php']['debug_typo3PrintError'])) {
			$params = array(
				'header' => $header,
				'text' => $text,
				'js' => $js,
				'baseUrl' => $baseUrl,
				'errorMessage' => &$errorMessage
			);
			$null = null;
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_timetrack.php']['debug_typo3PrintError'] as $hookMethod) {
				t3lib_div::callUserFunction($hookMethod, $params, $null);
			}
		}

		echo $errorMessage;
	}
}

// XCLASSing is not possible for this class

?>