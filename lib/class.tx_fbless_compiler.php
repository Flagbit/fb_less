<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Frederic Gaus <gaus@flagbit.de>, Flagbit GmbH & Co. KG
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
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
 * Fb Less Compiler
 *
 * @package fb_less
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 *
 */
class tx_fbless_compiler {
	var $prefixId = 'tx_fbless'; // Same as class name
	var $scriptRelPath = 'lib/class.tx_fbless_compiler.php'; // Path to this script relative to the extension dir.
	var $extKey = 'fb_less'; // The extension key.


	private $tempPath = 'typo3temp/fb_less/';
	private $extConf;
	private $compressor;


	/**
	 * Returns instance of t3lib_Compressor
	 *
	 * @return t3lib_Compressor Instance of t3lib_Compressor
	 */
	protected function getCompressor() {
		if ($this->compressor === NULL) {
			$this->compressor = t3lib_div::makeInstance('t3lib_Compressor');
		}
		return $this->compressor;
	}


	/**
	 * Checks weather compiled css files should be compressed
	 *
	 * @return bool
	 */
	protected function isCompressionEnabled() {
		return ! empty($this->extConf['compression']);
	}


	/**
	 * Checks weather file is a less file. Less-Files must end on .less
	 *
	 * @param String $filename
	 * @return bool true if file is a less file
	 */
	protected function isLessFile($filename) {
		return preg_match('#\.less$#i', $filename);
	}


	/**
	 * Generates the cached filename inside typo3temp-dir.
	 *
	 * @param String $lessFilename the filename of the less file
	 * @return String the filename for the compiled css file
	 */
	protected function getCssFilename($lessFilename) {
		$lessFileInfo = t3lib_div::split_fileref($lessFilename);
		return $this->tempPath.$lessFileInfo['filebody'].'.css';
	}


	/**
	 * Replaces a key inside an array
	 *
	 * @param array $array
	 * @param String $oldKey
	 * @param String $newKey
	 */
	public function replaceKey($array, $oldKey, $newKey) {
		$newArray = array();
		foreach ($array as $key => $value) {
			$newArray[$key === $oldKey ? $newKey : $key] = $value;
		}
		return $newArray;
	}


	/**
	 * Compile Function
	 *
	 * @param array $params
	 * @param object $pObj
	 */
	public function compile(&$params, &$pObj) {
		$this->extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['fb_less']);

		foreach ($params['cssFiles'] as $key => $includeFile) {

				//TYPO3 4.5 compability
			if (t3lib_div::compat_version('4.6')) {
				$filename = $includeFile['file'];
			} else {
				$filename = $key;
			}

			if ($this->isLessFile($filename)) {
				$cssFilename = $this->getCssFilename($filename);

					//check if we need to compile less file
				if (!file_exists($cssFilename) || $GLOBALS['TSFE']->no_cache) {
					$lessCompiler = t3lib_div::makeInstance('tx_fbless_lessc', $filename);
					$cssFileContents = $lessCompiler->parse();
					t3lib_div::writeFile($cssFilename, $cssFileContents);
				}

					//compression if compression is enabled
				if ($this->isCompressionEnabled()) {
					$cssFilename = $this->getCompressor()->compressCssFile($cssFilename);
				}

					//TYPO3 4.5 compability
				if (t3lib_div::compat_version('4.6')) {
					$params['cssFiles'][$key]['file'] = $cssFilename;
				} else {
					$params['cssFiles'] = $this->replaceKey($params['cssFiles'], $filename, $cssFilename);
				}
			}
		}
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/fb_less/lib/class.tx_fbless_compiler.php']) {
	include_once ($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/fb_less/lib/class.tx_fbless_compiler.php']);
}

?>
