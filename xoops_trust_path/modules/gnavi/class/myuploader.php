<?php
// $Id: uploader.php,v 1.12 2004/01/05 12:49:12 okazu Exp $
//  ------------------------------------------------------------------------ //
//                XOOPS - PHP Content Management System                      //
//                    Copyright (c) 2000 XOOPS.org                           //
//                       <http://www.xoops.org/>                             //
//  ------------------------------------------------------------------------ //
//  This program is free software; you can redistribute it and/or modify     //
//  it under the terms of the GNU General Public License as published by     //
//  the Free Software Foundation; either version 2 of the License, or        //
//  (at your option) any later version.                                      //
//                                                                           //
//  You may not change or alter any portion of this comment or credits       //
//  of supporting developers from this source code or any supporting         //
//  source code which is considered copyrighted (c) material of the          //
//  original comment or credit authors.                                      //
//                                                                           //
//  This program is distributed in the hope that it will be useful,          //
//  but WITHOUT ANY WARRANTY; without even the implied warranty of           //
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            //
//  GNU General Public License for more details.                             //
//                                                                           //
//  You should have received a copy of the GNU General Public License        //
//  along with this program; if not, write to the Free Software              //
//  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307 USA //
//  ------------------------------------------------------------------------ //
// Author: Kazumi Ono (AKA onokazu)                                          //
// URL: http://www.myweb.ne.jp/, http://www.xoops.org/, http://jp.xoops.org/ //
// Project: The XOOPS Project                                                //
// ------------------------------------------------------------------------- //

// myuploader.php,v 1.12+
// Security & Bug fixed version of class XoopsMediaUploader by GIJOE

/*!
Example

  include_once 'myuploader.php';
  $allowed_mimetypes = array('image/gif', 'image/jpeg', 'image/pjpeg', 'image/x-png');
  $maxfilesize = 50000;
  $maxfilewidth = 120;
  $maxfileheight = 120;
  $uploader = new XoopsMediaUploader('/home/xoops/uploads', $allowed_mimetypes, $maxfilesize, $maxfilewidth, $maxfileheight, $allowed_exts);
  if ($uploader->fetchMedia($_POST['uploade_file_name'])) {
    if (!$uploader->upload()) {
       echo $uploader->getErrors();
    } else {
       echo '<h4>File uploaded successfully!</h4>'
       echo 'Saved as: ' . $uploader->getSavedFileName() . '<br />';
       echo 'Full path: ' . $uploader->getSavedDestination();
    }
  } else {
    echo $uploader->getErrors();
  }

*/

class MyXoopsMediaUploader
{
	var $mediaName;
	var $mediaType;
	var $mediaSize;
	var $mediaTmpName;
	var $mediaError;

	var $uploadDir = '';

	var $allowedMimeTypes = array();
	var $allowedExtensions = array();

	var $maxFileSize = 0;
	var $maxWidth;
	var $maxHeight;

	var $targetFileName;

	var $prefix;

	var $errors = array();

	var $savedDestination;

	var $savedFileName;

	var $exifData = array();
	var $exifGeo = array();

	/**
	 * Constructor
	 * 
	 * @param   string  $uploadDir
	 * @param   array   $allowedMimeTypes
	 * @param   int	 $maxFileSize
	 * @param   int	 $maxWidth
	 * @param   int	 $maxHeight
	 * @param   int	 $cmodvalue
	 * @param   array   $allowedExtensions
	 **/
	function MyXoopsMediaUploader($uploadDir, $allowedMimeTypes, $maxFileSize, $maxWidth=null, $maxHeight=null, $allowedExtensions=null )
	{
		if (is_array($allowedMimeTypes)) {
			$this->allowedMimeTypes =& $allowedMimeTypes;
		}
		$this->uploadDir = $uploadDir;
		$this->maxFileSize = intval($maxFileSize);
		if(isset($maxWidth)) {
			$this->maxWidth = intval($maxWidth);
		}
		if(isset($maxHeight)) {
			$this->maxHeight = intval($maxHeight);
		}
		if( isset( $allowedExtensions ) && is_array( $allowedExtensions ) ) {
			$this->allowedExtensions =& $allowedExtensions ;
		}
	}

	/**
	 * Fetch the uploaded file
	 * 
	 * @param   string  $media_name Name of the file field
	 * @param   int	 $index	  Index of the file (if more than one uploaded under that name)
	 * @return  bool
	 **/
	function fetchMedia($media_name, $index = null)
	{
	if (!isset($_FILES[$media_name])) {
		$this->setErrors('File not found');
		return false;
	} elseif (is_array($_FILES[$media_name]['name']) && isset($index)) {
			$index = intval($index);
			$this->mediaName = $_FILES[$media_name]['name'][$index];
			$this->mediaType = $_FILES[$media_name]['type'][$index];
			$this->mediaSize = $_FILES[$media_name]['size'][$index];
			$this->mediaTmpName = $_FILES[$media_name]['tmp_name'][$index];
			$this->mediaError = !empty($_FILES[$media_name]['error'][$index]) ? $_FILES[$media_name]['errir'][$index] : 0;
	} else {
			$media_name =& $_FILES[$media_name];
			$this->mediaName = $media_name['name'];
			$this->mediaType = $media_name['type'];
			$this->mediaSize = $media_name['size'];
			$this->mediaTmpName = $media_name['tmp_name'];
			$this->mediaError = !empty($media_name['error']) ? $media_name['error'] : 0;
		}
		$this->errors = array();
		if (intval($this->mediaSize) < 0) {
			$this->setErrors('Invalid File Size');
			return false;
		}
		if ($this->mediaName == '') {
			$this->setErrors('Filename Is Empty');
			return false;
		}
		if ($this->mediaTmpName == 'none' || !is_uploaded_file($this->mediaTmpName) || $this->mediaSize == 0 ) {
			$this->setErrors('No file uploaded');
			return false;
		}
		if ($this->mediaError > 0) {
			$this->setErrors('Error occurred: Error #'.$this->mediaError);
			return false;
		}
		return true;
	}

	/**
	 * Set the target filename
	 * 
	 * @param   string  $value
	 **/
	function setTargetFileName($value){
		$this->targetFileName = strval(trim($value));
	}

	/**
	 * Set the prefix
	 * 
	 * @param   string  $value
	 **/
	function setPrefix($value){
		$this->prefix = strval(trim($value));
	}

	/**
	 * Get the uploaded filename
	 * 
	 * @return  string 
	 **/
	function getMediaName()
	{
		return $this->mediaName;
	}

	/**
	 * Get the type of the uploaded file
	 * 
	 * @return  string 
	 **/
	function getMediaType()
	{
		return $this->mediaType;
	}

	/**
	 * Get the size of the uploaded file
	 * 
	 * @return  int 
	 **/
	function getMediaSize()
	{
		return $this->mediaSize;
	}

	/**
	 * Get the temporary name that the uploaded file was stored under
	 * 
	 * @return  string 
	 **/
	function getMediaTmpName()
	{
		return $this->mediaTmpName;
	}

	/**
	 * Get the saved filename
	 * 
	 * @return  string 
	 **/
	function getSavedFileName(){
		return $this->savedFileName;
	}

	/**
	 * Get the destination the file is saved to
	 * 
	 * @return  string
	 **/
	function getSavedDestination(){
		return $this->savedDestination;
	}
	
	function getExif(){
		return $this->exifData;
	}
	
	function getExifGeo(){
		return (isset($this->exifData['GPS']))? $this->exifData['GPS'] : array();
	}
	
	/**
	 * Check the file and copy it to the destination
	 * 
	 * @return  bool
	 **/
	function upload($chmod = 0644)
	{
		if ($this->uploadDir == '') {
			$this->setErrors('Upload directory not set');
			return false;
		}
		if (!is_dir($this->uploadDir)) {
			$this->setErrors('Failed opening directory: '.$this->uploadDir);
			return false;
		}
		if (!is_writeable($this->uploadDir)) {
			$this->setErrors('Failed opening directory with write permission: '.$this->uploadDir);
			return false;
		}
		if (!$this->checkMimeType()) {
			$this->setErrors('MIME type not allowed: '.$this->mediaType);
			return false;
		}
		if (!$this->checkExtension()) {
			$this->setErrors('Extension not allowed');
			return false;
		}
		if (!$this->checkMaxFileSize()) {
			$this->setErrors('File size too large: '.$this->mediaSize);
		}
		if (!$this->checkMaxWidth()) {
			$this->setErrors(sprintf('File width must be smaller than %u', $this->maxWidth));
		}
		if (!$this->checkMaxHeight()) {
			$this->setErrors(sprintf('File height must be smaller than %u', $this->maxHeight));
		}
		if (count($this->errors) > 0) {
			return false;
		}
		
		if (!$this->_copyFile($chmod)) {
			$this->setErrors('Failed uploading file: '.$this->mediaName);
			return false;
		}
		
		$this->setExif();
		
		return true;
	}

	/**
	 * Copy the file to its destination
	 * 
	 * @return  bool 
	 **/
	function _copyFile($chmod)
	{
		$matched = array();
		if (!preg_match("/\.([a-zA-Z0-9]+)$/", $this->mediaName, $matched)) {
			return false;
		}
		if (isset($this->targetFileName)) {
			$this->savedFileName = $this->targetFileName;
		} elseif (isset($this->prefix)) {
			$this->savedFileName = uniqid($this->prefix).'.'.strtolower($matched[1]);
		} else {
			$this->savedFileName = strtolower($this->mediaName);
		}
		$this->savedDestination = $this->uploadDir.'/'.$this->savedFileName;
		if (!move_uploaded_file($this->mediaTmpName, $this->savedDestination)) {
			return false;
		}
		@chmod($this->savedDestination, $chmod);
		return true;
	}

	/**
	 * Is the file the right size?
	 * 
	 * @return  bool 
	 **/
	function checkMaxFileSize()
	{
		if ($this->mediaSize > $this->maxFileSize) {
			return false;
		}
		return true;
	}

	/**
	 * Is the picture the right width?
	 * 
	 * @return  bool 
	 **/
	function checkMaxWidth()
	{
		if (!isset($this->maxWidth)) {
			return true;
		}
		if (false !== $dimension = getimagesize($this->mediaTmpName)) {
			if ($dimension[0] > $this->maxWidth) {
				return false;
			}
		} else {
			trigger_error(sprintf('Failed fetching image size of %s, skipping max width check..', $this->mediaTmpName), E_USER_WARNING);
		}
		return true;
	}

	/**
	 * Is the picture the right height?
	 * 
	 * @return  bool 
	 **/
	function checkMaxHeight()
	{
		if (!isset($this->maxHeight)) {
			return true;
		}
		if (false !== $dimension = getimagesize($this->mediaTmpName)) {
			if ($dimension[1] > $this->maxHeight) {
				return false;
			}
		} else {
			trigger_error(sprintf('Failed fetching image size of %s, skipping max height check..', $this->mediaTmpName), E_USER_WARNING);
		}
		return true;
	}

	/**
	 * Is the file the right Mime type 
	 * 
	 * (is there a right type of mime? ;-)
	 * 
	 * @return  bool
	 **/
	function checkMimeType()
	{
		if (count($this->allowedMimeTypes) > 0 && !in_array($this->mediaType, $this->allowedMimeTypes)) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * Is the file the right extension
	 * 
	 * @return  bool
	 **/
	function checkExtension()
	{
		$ext = substr( strrchr( $this->mediaName , '.' ) , 1 ) ;
		if( ! empty( $this->allowedExtensions ) && ! in_array( strtolower( $ext ) , $this->allowedExtensions ) ) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * Add an error
	 * 
	 * @param   string  $error
	 **/
	function setErrors($error)
	{
		$this->errors[] = trim($error);
	}

	/**
	 * Get generated errors
	 * 
	 * @param	bool	$ashtml Format using HTML?
	 * 
	 * @return	array|string	Array of array messages OR HTML string
	 */
	function &getErrors($ashtml = true)
	{
		if (!$ashtml) {
			return $this->errors;
		} else {
			$ret = '';
			if (count($this->errors) > 0) {
				$ret = '<h4>Errors Returned While Uploading</h4>';
				foreach ($this->errors as $error) {
					$ret .= $error.'<br />';
				}
			}
			return $ret;
		}
	}
	
	function setExif() {
		if (! extension_loaded('exif')) return false;
		
		$file = $this->savedDestination;

		if (! $is = @getimagesize($file) or ($is[2] !== IMAGETYPE_JPEG and $is[2] !== IMAGETYPE_JPEG2000)) return false;
		
		$ret =& $this->exifData;
		if ($ret['RAW'] = @ exif_read_data($file)) {
			// set GPS
			if ($gps = $this->calExifGeo()) {
				$ret['GPS'] = $gps;
			} else {
				$ret['GPS'] = array();
			}

			if (isset($exif_data['Model']))
				$ret['Camera'] = $exif_data['Model'];
			
			if (isset($exif_data['Make'])) {
				if (strpos($ret['Camera'], $exif_data['Make']) !== 0){
					$ret['Camera'] = $exif_data['Make'] . ' ' . $ret['Camera'];
				}
			}
			
			
			if (isset($exif_data['DateTimeOriginal']))
				$ret['Date'] = $exif_data['DateTimeOriginal'];
			
			if (isset($exif_data['ExposureTime']))
				$ret['Exposure'] = $this->get_exif_numbar($exif_data['ExposureTime'], FALSE, 'fraction').' sec';
			
			if (isset($exif_data['ExposureBiasValue'])) {
				$ret['Exposure'] .= ' (' . $this->get_exif_numbar($exif_data['ExposureBiasValue'], FALSE).' EV)';
			}
			
			if (isset($exif_data['ApertureValue'])) {
				$ret['Aperture'] = 'F '.$this->get_exif_numbar($exif_data['ApertureValue'], 'A');
			} else if (isset($exif_data['FNumber'])) {
				$ret['Aperture'] = 'F '.$this->get_exif_numbar($exif_data['FNumber']);
			}
			
			if (isset($exif_data['FocalLength']))
				$ret['Lens'] = $this->get_exif_numbar($exif_data['FocalLength']).' mm';
			
			if (isset($exif_data['FocalLengthIn35mmFilm']))
				@$ret['Lens'] .= '(35mm:' . $this->get_exif_numbar($exif_data['FocalLengthIn35mmFilm']).')';
			
			if (isset($exif_data['MaxApertureValue']))
				@$ret['Lens'] .= '/F '.$this->get_exif_numbar($exif_data['MaxApertureValue'], 'A');
			
			if (isset($exif_data['Flash'])){
				if ($exif_data['Flash'] == 0) {$ret['Flash'] = "OFF";}
				else if ($exif_data['Flash'] == 1) {$ret['Flash'] = "ON";}
				else if ($exif_data['Flash'] == 5) {$ret['Flash'] = "Light(No Reflection)";}
				else if ($exif_data['Flash'] == 7) {$ret['Flash'] = "Light(Reflection)";}
				else if ($exif_data['Flash'] == 9) {$ret['Flash'] = "Always ON";}
				else if ($exif_data['Flash'] == 16) {$ret['Flash'] = "Always OFF";}
				else if ($exif_data['Flash'] == 24) {$ret['Flash'] = "Auto(None)";}
				else if ($exif_data['Flash'] == 25) {$ret['Flash'] = "Auto(Light)";}
				else {$ret['Flash'] = $exif_data['Flash'];}
			}
			
			if (isset($exif_data['SubjectDistance'])) {
				$ret['Distance'] = $exif_data['SubjectDistance'].' m';
			}
			
			return true;
		}
		
		return false;
	}
	
	function calExifGeo(){
	
		if (empty($this->exifData['RAW'])) return false;
		
		$exif = $this->exifData['RAW'];
	
		$Lat = @ $exif['GPSLatitude'];
		$Lon = @ $exif['GPSLongitude'];
		$LatRef = @ $exif['GPSLatitudeRef'];
		$LonRef = @ $exif['GPSLongitudeRef'];
		if (!$Lat || !$Lon || !$LatRef || !$LonRef) return false;
	
		// replace N,E,W,S to '' or '-'
		$prefix = array( 'N' => '', 'S' => '-', 'E' => '', 'W' => '-' );
	
		$result = array();
		if (is_array($Lat)){
			foreach($Lat as $v){
				if (strstr($v, '/')){
					$x = explode('/', $v);
					$result['Lat'][] = $x[0] / $x[1];
				}
			}
			$result['Lat'] = $result['Lat'][0] + ($result['Lat'][1]/60) + ($result['Lat'][2]/(60*60));
		} else {
			$result['Lat'] = $Lat;
		}
		if (is_array($Lon)){
			foreach($Lon as $v){
				if (strstr($v, '/')){
					$x = explode('/', $v);
					$result['Lon'][] = $x[0] / $x[1];
				}
			}
			$result['Lon'] = $result['Lon'][0] + ($result['Lon'][1]/60) + ($result['Lon'][2]/(60*60));
		} else {
			$result['Lon'] = $Lon;
		}
	
		if (!$result['Lat'] && !$result['Lon']) return false;
	
		// TOKYO to WGS84
		if (stristr($exif['GPSMapDatum'], 'tokyo')){
			$result['Lat'] = $result['Lon'] - $result['Lon'] * 0.00010695  + $result['Lat'] * 0.000017464 + 0.0046017;
			$result['Lon'] = $result['Lat'] - $result['Lon'] * 0.000046038 - $result['Lat'] * 0.000083043 + 0.010040;
		}
	
		$result['Lat'] = (float)($prefix[$LatRef] . $result['Lat']);
		$result['Lon'] = (float)($prefix[$LonRef] . $result['Lon']);
		$result['Date'] = @ $exif['DateTimeOriginal'];
		
		return $result;
	}
	
	function get_exif_numbar ($dat, $APEX=FALSE, $format='') {
		if (preg_match('#^([\d.-]+)/([\d.-]+)$#',$dat,$match)) {
			if ($match[2]) {
				$dat = $match[1] / $match[2];
			} else {
				$dat = $match[1];
			}
		} else {
			$dat = (float)$dat;
		}
		if ($APEX == 'T') {
			$dat = pow(2, $dat);
		} else if ($APEX) {
			$dat = pow(sqrt(2), $dat);
		}
		if ($format !== 'long') {
			if ($format === 'fraction' && $dat <= 1) {
				$dat = '1/' . (int)(1/$dat);
			} else {
				$dat = round($dat * 100) / 100;
			}
		}
		return $dat;
	}
	
	function get_exif_ev ($exif_data) {
		$ev = $this->get_exif_numbar($exif_data['ExposureBiasValue'], FALSE, TRUE);
		//BrightnessValue+log2(ISOSpeedRatings/3.125)+ExposureBiasValue
		//$bv = $this->get_exif_numbar($exif_data['BrightnessValue'], FALSE, TRUE);
		//$sr = $this->get_exif_numbar($exif_data['ISOSpeedRatings'], FALSE, TRUE);
		//$ev = $bv + log($sr/3.125, 2) + $ev;
		return $ev;
	}
	
}
?>