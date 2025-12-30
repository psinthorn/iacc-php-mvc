<?php

// Adapted for mPDF from TCPDF barcode. Original Details left below.

//============================================================+
// File name   : barcodes.php
// Begin       : 2008-06-09
// Last Update : 2009-04-15
// Version     : 1.0.008
// License     : GNU LGPL (http://www.gnu.org/copyleft/lesser.html)
// 	----------------------------------------------------------------------------
//  Copyright (C) 2008-2009 Nicola Asuni - Tecnick.com S.r.l.
// 	
// 	This program is free software: you can redistribute it and/or modify
// 	it under the terms of the GNU Lesser General Public License as published by
// 	the Free Software Foundation, either version 2.1 of the License, or
// 	(at your option) any later version.
// 	
// 	This program is distributed in the hope that it will be useful,
// 	but WITHOUT ANY WARRANTY; without even the implied warranty of
// 	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// 	GNU Lesser General Public License for more details.
// 	
// 	You should have received a copy of the GNU Lesser General Public License
// 	along with this program.  If not, see <http://www.gnu.org/licenses/>.
// 	
// 	See LICENSE.TXT file for more information.
//  ----------------------------------------------------------------------------
//
// Description : PHP class to creates array representations for 
//               common 1D barcodes to be used with TCPDF.
//
// Author: Nicola Asuni
//
// (c) Copyright:
//               Nicola Asuni
//               Tecnick.com S.r.l.
//               Via della Pace, 11
//               09044 Quartucciu (CA)
//               ITALY
//               www.tecnick.com
//               info@tecnick.com
//============================================================+

class PDFBarcode {
	
	protected $barcode_array;
	protected $gapwidth;
	protected $print_ratio;
	protected $daft;

	public function __construct() {

	}
	
	public function getBarcodeArray($code, $type, $pr='') {
		$this->setBarcode($code, $type, $pr);
		return $this->barcode_array;
	}
	public function getChecksum($code, $type) {
		$this->setBarcode($code, $type);
		if (!$this->barcode_array) { return ''; }
		else { return $this->barcode_array['checkdigit']; }
	}

	public function setBarcode($code, $type, $pr='') {
		$this->print_ratio = 1;
		switch (strtoupper($type)) {
			case 'ISBN':
			case 'ISSN':
			case 'EAN13': { // EAN 13
				$arrcode = $this->barcode_eanupc($code, 13);
				$arrcode['lightmL'] = 11;	// LEFT light margin =  x X-dim (http://www.gs1uk.org)
				$arrcode['lightmR'] = 7;	// RIGHT light margin =  x X-dim (http://www.gs1uk.org)
				$arrcode['nom-X'] = 0.33;	// Nominal value for X-dim in mm (http://www.gs1uk.org)
				$arrcode['nom-H'] = 25.93;	// Nominal bar height in mm incl. numerals (http://www.gs1uk.org)
				break;
			}
			case 'UPCA': { // UPC-A
				$arrcode = $this->barcode_eanupc($code, 12);
				$arrcode['lightmL'] = 9;	// LEFT light margin =  x X-dim (http://www.gs1uk.org)
				$arrcode['lightmR'] = 9;	// RIGHT light margin =  x X-dim (http://www.gs1uk.org)
				$arrcode['nom-X'] = 0.33;	// Nominal value for X-dim in mm (http://www.gs1uk.org)
				$arrcode['nom-H'] = 25.91;	// Nominal bar height in mm incl. numerals (http://www.gs1uk.org)
				break;
			}
			case 'UPCE': { // UPC-E
				$arrcode = $this->barcode_eanupc($code, 6);
				$arrcode['lightmL'] = 9;	// LEFT light margin =  x X-dim (http://www.gs1uk.org)
				$arrcode['lightmR'] = 7;	// RIGHT light margin =  x X-dim (http://www.gs1uk.org)
				$arrcode['nom-X'] = 0.33;	// Nominal value for X-dim in mm (http://www.gs1uk.org)
				$arrcode['nom-H'] = 25.93;	// Nominal bar height in mm incl. numerals (http://www.gs1uk.org)
				break;
			}
			case 'EAN8': { // EAN 8
				$arrcode = $this->barcode_eanupc($code, 8);
				$arrcode['lightmL'] = 7;	// LEFT light margin =  x X-dim (http://www.gs1uk.org)
				$arrcode['lightmR'] = 7;	// RIGHT light margin =  x X-dim (http://www.gs1uk.org)
				$arrcode['nom-X'] = 0.33;	// Nominal value for X-dim in mm (http://www.gs1uk.org)
				$arrcode['nom-H'] = 21.64;	// Nominal bar height in mm incl. numerals (http://www.gs1uk.org)
				break;
			}
			case 'EAN2': { // 2-Digits UPC-Based Extention
				$arrcode = $this->barcode_eanext($code, 2);
				$arrcode['lightmL'] = 7;	// LEFT light margin =  x X-dim (estimated)
				$arrcode['lightmR'] = 7;	// RIGHT light margin =  x X-dim (estimated)
				$arrcode['sepM'] = 9;		// SEPARATION margin =  x X-dim (http://web.archive.org/web/19990501035133/http://www.uc-council.org/d36-d.htm)
				$arrcode['nom-X'] = 0.33;	// Nominal value for X-dim in mm (http://www.gs1uk.org)
				$arrcode['nom-H'] = 20;	// Nominal bar height in mm incl. numerals (estimated) not used when combined
				break;
			}
			case 'EAN5': { // 5-Digits UPC-Based Extention
				$arrcode = $this->barcode_eanext($code, 5);
				$arrcode['lightmL'] = 7;	// LEFT light margin =  x X-dim (estimated)
				$arrcode['lightmR'] = 7;	// RIGHT light margin =  x X-dim (estimated)
				$arrcode['sepM'] = 9;		// SEPARATION margin =  x X-dim (http://web.archive.org/web/19990501035133/http://www.uc-council.org/d36-d.htm)
				$arrcode['nom-X'] = 0.33;	// Nominal value for X-dim in mm (http://www.gs1uk.org)
				$arrcode['nom-H'] = 20;	// Nominal bar height in mm incl. numerals (estimated) not used when combined
				break;
			}

			case 'IMB': { // IMB - Intelligent Mail Barcode - Onecode - USPS-B-3200
				$xdim = 0.508;			// Nominal value for X-dim (bar width) in mm (spec.)
				$bpi = 22;				// Bars per inch
				// Ratio of Nominal value for width of spaces in mm / Nominal value for X-dim (bar width) in mm based on bars per inch
				$this->gapwidth =  ((25.4/$bpi) - $xdim)/$xdim; 
				$this->daft = array('D'=>2, 'A'=>2, 'F'=>3, 'T'=>1);	// Descender; Ascender; Full; Tracker bar heights
				$arrcode = $this->barcode_imb($code);
				$arrcode['nom-X'] = $xdim ;
				$arrcode['nom-H'] = 3.68;	// Nominal value for Height of Full bar in mm (spec.) 
									// USPS-B-3200 Revision C = 4.623
									// USPS-B-3200 Revision E = 3.68
				$arrcode['quietL'] = 3.175;	// LEFT Quiet margin =  mm (spec.)
				$arrcode['quietR'] = 3.175;	// RIGHT Quiet margin =  mm (spec.)
				$arrcode['quietTB'] = 0.711;	// TOP/BOTTOM Quiet margin =  mm (spec.)
				break;
			}
			case 'RM4SCC': { // RM4SCC (Royal Mail 4-state Customer Code) - CBC (Customer Bar Code)
				$xdim = 0.508;			// Nominal value for X-dim (bar width) in mm (spec.)
				$bpi = 22;				// Bars per inch
				// Ratio of Nominal value for width of spaces in mm / Nominal value for X-dim (bar width) in mm based on bars per inch
				$this->gapwidth =  ((25.4/$bpi) - $xdim)/$xdim; 
				$this->daft = array('D'=>5, 'A'=>5, 'F'=>8, 'T'=>2);	// Descender; Ascender; Full; Tracker bar heights
				$arrcode = $this->barcode_rm4scc($code, false);
				$arrcode['nom-X'] = $xdim ;
				$arrcode['nom-H'] = 5.0;	// Nominal value for Height of Full bar in mm (spec.)
				$arrcode['quietL'] = 2;		// LEFT Quiet margin =  mm (spec.)
				$arrcode['quietR'] = 2;		// RIGHT Quiet margin =  mm (spec.)
				$arrcode['quietTB'] = 2;	// TOP/BOTTOM Quiet margin =  mm (spec?)
				break;
			}
			case 'KIX': { // KIX (Klant index - Customer index)
				$xdim = 0.508;			// Nominal value for X-dim (bar width) in mm (spec.)
				$bpi = 22;				// Bars per inch
				// Ratio of Nominal value for width of spaces in mm / Nominal value for X-dim (bar width) in mm based on bars per inch
				$this->gapwidth =  ((25.4/$bpi) - $xdim)/$xdim; 
				$this->daft = array('D'=>5, 'A'=>5, 'F'=>8, 'T'=>2);	// Descender; Ascender; Full; Tracker bar heights
				$arrcode = $this->barcode_rm4scc($code, true);
				$arrcode['nom-X'] = $xdim ;
				$arrcode['nom-H'] = 5.0;	// Nominal value for Height of Full bar in mm (? spec.)
				$arrcode['quietL'] = 2;		// LEFT Quiet margin =  mm (spec.)
				$arrcode['quietR'] = 2;		// RIGHT Quiet margin =  mm (spec.)
				$arrcode['quietTB'] = 2;	// TOP/BOTTOM Quiet margin =  mm (spec.)
				break;
			}
			case 'POSTNET': { // POSTNET
				$xdim = 0.508;			// Nominal value for X-dim (bar width) in mm (spec.)
				$bpi = 22;				// Bars per inch
				// Ratio of Nominal value for width of spaces in mm / Nominal value for X-dim (bar width) in mm based on bars per inch
				$this->gapwidth =  ((25.4/$bpi) - $xdim)/$xdim; 
				$arrcode = $this->barcode_postnet($code, false);
				$arrcode['nom-X'] = $xdim ;
				$arrcode['nom-H'] = 3.175;	// Nominal value for Height of Full bar in mm (spec.)
				$arrcode['quietL'] = 3.175;	// LEFT Quiet margin =  mm (?spec.)
				$arrcode['quietR'] = 3.175;	// RIGHT Quiet margin =  mm (?spec.)
				$arrcode['quietTB'] = 1.016;	// TOP/BOTTOM Quiet margin =  mm (?spec.)
				break;
			}
			case 'PLANET': { // PLANET
				$xdim = 0.508;			// Nominal value for X-dim (bar width) in mm (spec.)
				$bpi = 22;				// Bars per inch
				// Ratio of Nominal value for width of spaces in mm / Nominal value for X-dim (bar width) in mm based on bars per inch
				$this->gapwidth =  ((25.4/$bpi) - $xdim)/$xdim; 
				$arrcode = $this->barcode_postnet($code, true);
				$arrcode['nom-X'] = $xdim ;
				$arrcode['nom-H'] = 3.175;	// Nominal value for Height of Full bar in mm (spec.)
				$arrcode['quietL'] = 3.175;	// LEFT Quiet margin =  mm (?spec.)
				$arrcode['quietR'] = 3.175;	// RIGHT Quiet margin =  mm (?spec.)
				$arrcode['quietTB'] = 1.016;	// TOP/BOTTOM Quiet margin =  mm (?spec.)
				break;
			}

			case 'C93':	{	// CODE 93 - USS-93
				$arrcode = $this->barcode_code93($code);
				if ($arrcode == false) { break; }
				$arrcode['nom-X'] = 0.381;	// Nominal value for X-dim (bar width) in mm (2 X min. spec.)
				$arrcode['nom-H'] = 10;		// Nominal value for Height of Full bar in mm (non-spec.)
				$arrcode['lightmL'] = 10;	// LEFT light margin =  x X-dim (spec.)
				$arrcode['lightmR'] = 10;	// RIGHT light margin =  x X-dim (spec.)
				$arrcode['lightTB'] = 0;	// TOP/BOTTOM light margin =  x X-dim (non-spec.)
				break;
			}
			case 'CODE11': {	// CODE 11
				if ($pr > 0) { $this->print_ratio = $pr; }
				else { $this->print_ratio = 3; }		// spec: Pr= 1:2.24 - 1:3.5
				$arrcode = $this->barcode_code11($code);
				if ($arrcode == false) { break; }
				$arrcode['nom-X'] = 0.381;	// Nominal value for X-dim (bar width) in mm (2 X min. spec.)
				$arrcode['nom-H'] = 10;		// Nominal value for Height of Full bar in mm (non-spec.)
				$arrcode['lightmL'] = 10;	// LEFT light margin =  x X-dim (spec.)
				$arrcode['lightmR'] = 10;	// RIGHT light margin =  x X-dim (spec.)
				$arrcode['lightTB'] = 0;	// TOP/BOTTOM light margin =  x X-dim (non-spec.)
				break;
			}
			case 'MSI':		// MSI (Variation of Plessey code)
			case 'MSI+': {	// MSI + CHECKSUM (modulo 11)
				if (strtoupper($type)=='MSI') { $arrcode = $this->barcode_msi($code, false); }
				if (strtoupper($type)=='MSI+') { $arrcode = $this->barcode_msi($code, true); }
				if ($arrcode == false) { break; }
				$arrcode['nom-X'] = 0.381;	// Nominal value for X-dim (bar width) in mm (2 X min. spec.)
				$arrcode['nom-H'] = 10;		// Nominal value for Height of Full bar in mm (non-spec.)
				$arrcode['lightmL'] = 12;	// LEFT light margin =  x X-dim (spec.)
				$arrcode['lightmR'] = 12;	// RIGHT light margin =  x X-dim (spec.)
				$arrcode['lightTB'] = 0;	// TOP/BOTTOM light margin =  x X-dim (non-spec.)
				break;
			}
			case 'CODABAR': {	// CODABAR
				if ($pr > 0) { $this->print_ratio = $pr; }
				else { $this->print_ratio = 2.5; }		// spec: Pr= 1:2 - 1:3 (>2.2 if X<0.50)
				if (strtoupper($type)=='CODABAR') { $arrcode = $this->barcode_codabar($code); }
				if ($arrcode == false) { break; }
				$arrcode['nom-X'] = 0.381;	// Nominal value for X-dim (bar width) in mm (2 X min. spec.)
				$arrcode['nom-H'] = 10;		// Nominal value for Height of Full bar in mm (non-spec.)
				$arrcode['lightmL'] = 10;	// LEFT light margin =  x X-dim (spec.)
				$arrcode['lightmR'] = 10;	// RIGHT light margin =  x X-dim (spec.)
				$arrcode['lightTB'] = 0;	// TOP/BOTTOM light margin =  x X-dim (non-spec.)
				break;
			}
			case 'C128A':	// CODE 128 A
			case 'C128B':	// CODE 128 B
			case 'C128C': 	// CODE 128 C
			case 'EAN128A': 	// EAN 128 A
			case 'EAN128B': 	// EAN 128 B
			case 'EAN128C': {	// EAN 128 C
				if (strtoupper($type)=='C128A') { $arrcode = $this->barcode_c128($code, 'A'); }
				if (strtoupper($type)=='C128B') { $arrcode = $this->barcode_c128($code, 'B'); }
				if (strtoupper($type)=='C128C') { $arrcode = $this->barcode_c128($code, 'C'); }
				if (strtoupper($type)=='EAN128A') { $arrcode = $this->barcode_c128($code, 'A', true); }
				if (strtoupper($type)=='EAN128B') { $arrcode = $this->barcode_c128($code, 'B', true); }
				if (strtoupper($type)=='EAN128C') { $arrcode = $this->barcode_c128($code, 'C', true); }
				if ($arrcode == false) { break; }
				$arrcode['nom-X'] = 0.381;	// Nominal value for X-dim (bar width) in mm (2 X min. spec.)
				$arrcode['nom-H'] = 10;		// Nominal value for Height of Full bar in mm (non-spec.)
				$arrcode['lightmL'] = 10;	// LEFT light margin =  x X-dim (spec.)
				$arrcode['lightmR'] = 10;	// RIGHT light margin =  x X-dim (spec.)
				$arrcode['lightTB'] = 0;	// TOP/BOTTOM light margin =  x X-dim (non-spec.)
				break;
			}
			case 'C39':		// CODE 39 - ANSI MH10.8M-1983 - USD-3 - 3 of 9.
			case 'C39+':	// CODE 39 with checksum
			case 'C39E':	// CODE 39 EXTENDED
			case 'C39E+': {	// CODE 39 EXTENDED + CHECKSUM
				if ($pr > 0) { $this->print_ratio = $pr; }
				else { $this->print_ratio = 2.5; }	// spec: Pr= 1:2 - 1:3 (>2.2 if X<0.50)
				$code = str_replace(chr(194).chr(160), ' ', $code);	// mPDF 5.3.95  (for utf-8 encoded)
				$code = str_replace(chr(160), ' ', $code);	// mPDF 5.3.95	(for win-1252)
				if (strtoupper($type)=='C39') { $arrcode = $this->barcode_code39($code, false, false); }
				if (strtoupper($type)=='C39+') { $arrcode = $this->barcode_code39($code, false, true); }
				if (strtoupper($type)=='C39E') { $arrcode = $this->barcode_code39($code, true, false); }
				if (strtoupper($type)=='C39E+') { $arrcode = $this->barcode_code39($code, true, true); }
				if ($arrcode == false) { break; }
				$arrcode['nom-X'] = 0.381;	// Nominal value for X-dim (bar width) in mm (2 X min. spec.)
				$arrcode['nom-H'] = 10;		// Nominal value for Height of Full bar in mm (non-spec.)
				$arrcode['lightmL'] = 10;	// LEFT light margin =  x X-dim (spec.)
				$arrcode['lightmR'] = 10;	// RIGHT light margin =  x X-dim (spec.)
				$arrcode['lightTB'] = 0;	// TOP/BOTTOM light margin =  x X-dim (non-spec.)
				break;
			}
			case 'S25':		// Standard 2 of 5
			case 'S25+': {	// Standard 2 of 5 + CHECKSUM
				if ($pr > 0) { $this->print_ratio = $pr; }
				else { $this->print_ratio = 3; }		// spec: Pr=1:3/1:4.5
				if (strtoupper($type)=='S25') { $arrcode = $this->barcode_s25($code, false); }
				if (strtoupper($type)=='S25+') { $arrcode = $this->barcode_s25($code, true); }
				if ($arrcode == false) { break; }
				$arrcode['nom-X'] = 0.381;	// Nominal value for X-dim (bar width) in mm (2 X min. spec.)
				$arrcode['nom-H'] = 10;		// Nominal value for Height of Full bar in mm (non-spec.)
				$arrcode['lightmL'] = 10;	// LEFT light margin =  x X-dim (spec.)
				$arrcode['lightmR'] = 10;	// RIGHT light margin =  x X-dim (spec.)
				$arrcode['lightTB'] = 0;	// TOP/BOTTOM light margin =  x X-dim (non-spec.)
				break;
			}
			case 'I25':  // Interleaved 2 of 5
			case 'I25+': { // Interleaved 2 of 5 + CHECKSUM
				if ($pr > 0) { $this->print_ratio = $pr; }
				else { $this->print_ratio = 2.5; }	// spec: Pr= 1:2 - 1:3 (>2.2 if X<0.50)
				if (strtoupper($type)=='I25') { $arrcode = $this->barcode_i25($code, false); }
				if (strtoupper($type)=='I25+') { $arrcode = $this->barcode_i25($code, true); }
				if ($arrcode == false) { break; }
				$arrcode['nom-X'] = 0.381;	// Nominal value for X-dim (bar width) in mm (2 X min. spec.)
				$arrcode['nom-H'] = 10;		// Nominal value for Height of Full bar in mm (non-spec.)
				$arrcode['lightmL'] = 10;	// LEFT light margin =  x X-dim (spec.)
				$arrcode['lightmR'] = 10;	// RIGHT light margin =  x X-dim (spec.)
				$arrcode['lightTB'] = 0;	// TOP/BOTTOM light margin =  x X-dim (non-spec.)
				break;
			}
			case 'I25B':  // Interleaved 2 of 5 + Bearer bars
			case 'I25B+': { // Interleaved 2 of 5 + CHECKSUM + Bearer bars
				if ($pr > 0) { $this->print_ratio = $pr; }
				else { $this->print_ratio = 2.5; }	// spec: Pr= 1:2 - 1:3 (>2.2 if X<0.50)
				if (strtoupper($type)=='I25B') { $arrcode = $this->barcode_i25($code, false); }
				if (strtoupper($type)=='I25B+') { $arrcode = $this->barcode_i25($code, true); }
				if ($arrcode == false) { break; }
				$arrcode['nom-X'] = 0.381;	// Nominal value for X-dim (bar width) in mm (2 X min. spec.)
				$arrcode['nom-H'] = 10;		// Nominal value for Height of Full bar in mm (non-spec.)
				$arrcode['lightmL'] = 10;	// LEFT light margin =  x X-dim (spec.)
				$arrcode['lightmR'] = 10;	// RIGHT light margin =  x X-dim (spec.)
				$arrcode['lightTB'] = 2;	// TOP/BOTTOM light margin =  x X-dim (non-spec.) - used for bearer bars
				break;
			}
			default: {
				$this->barcode_array = false;
			}
		}
		$this->barcode_array = $arrcode;
	}
	
	/**
	 * CODE 39 - ANSI MH10.8M-1983 - USD-3 - 3 of 9.
	 */
	protected function barcode_code39($code, $extended=false, $checksum=false) {
		$chr['0'] = '111221211';
		$chr['1'] = '211211112';
		$chr['2'] = '112211112';
		$chr['3'] = '212211111';
		$chr['4'] = '111221112';
		$chr['5'] = '211221111';
		$chr['6'] = '112221111';
		$chr['7'] = '111211212';
		$chr['8'] = '211211211';
		$chr['9'] = '112211211';
		$chr['A'] = '211112112';
		$chr['B'] = '112112112';
		$chr['C'] = '212112111';
		$chr['D'] = '111122112';
		$chr['E'] = '211122111';
		$chr['F'] = '112122111';
		$chr['G'] = '111112212';
		$chr['H'] = '211112211';
		$chr['I'] = '112112211';
		$chr['J'] = '111122211';
		$chr['K'] = '211111122';
		$chr['L'] = '112111122';
		$chr['M'] = '212111121';
		$chr['N'] = '111121122';
		$chr['O'] = '211121121';
		$chr['P'] = '112121121';
		$chr['Q'] = '111111222';
		$chr['R'] = '211111221';
		$chr['S'] = '112111221';
		$chr['T'] = '111121221';
		$chr['U'] = '221111112';
		$chr['V'] = '122111112';
		$chr['W'] = '222111111';
		$chr['X'] = '121121112';
		$chr['Y'] = '221121111';
		$chr['Z'] = '122121111';
		$chr['-'] = '121111212';
		$chr['.'] = '221111211';
		$chr[' '] = '122111211';
		$chr['$'] = '121212111';
		$chr['/'] = '121211121';
		$chr['+'] = '121112121';
		$chr['%'] = '111212121';
		$chr['*'] = '121121211';
		
		$code = strtoupper($code);
		$checkdigit = '';
		if ($extended) {
			// extended mode
			$code = $this->encode_code39_ext($code);
		}
		if ($code === false) {
			return false;
		}
		if ($checksum) {
			// checksum
			$checkdigit = $this->checksum_code39($code);
			$code .= $checkdigit ;
		}
		// add start and stop codes
		$code = '*'.$code.'*';
		
		$bararray = array('code' => $code, 'maxw' => 0, 'maxh' => 1, 'bcode' => array());
		$k = 0;
		$clen = strlen($code);
		for ($i = 0; $i < $clen; ++$i) {
			$char = $code[$i];
			if(!isset($chr[$char])) {
				// invalid character
				return false;
			}
			for ($j = 0; $j < 9; ++$j) {
				if (($j % 2) == 0) {
					$t = true; // bar
				} else {
					$t = false; // space
				}
				$x = $chr[$char][$j];
				if ($x == 2) { $w = $this->print_ratio; }
				else { $w = 1; }

				$bararray['bcode'][$k] = array('t' => $t, 'w' => $w, 'h' => 1, 'p' => 0);
				$bararray['maxw'] += $w;
				++$k;
			}
			$bararray['bcode'][$k] = array('t' => false, 'w' => 1, 'h' => 1, 'p' => 0);
			$bararray['maxw'] += 1;
			++$k;
		}
		$bararray['checkdigit'] = $checkdigit;
		return $bararray;
	}
	
	/**
	 * Encode a string to be used for CODE 39 Extended mode.
	 */
	protected function encode_code39_ext($code) {
		$encode = array(
			chr(0) => '%U', chr(1) => '$A', chr(2) => '$B', chr(3) => '$C',
			chr(4) => '$D', chr(5) => '$E', chr(6) => '$F', chr(7) => '$G',
