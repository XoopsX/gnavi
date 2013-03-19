<?php

class GnaviExif
{
	var $file;
	var $exifRaw;
	
	function GnaviExif($file = null) {
		$this->file = $file;
		$this->exifRaw = null;
	}
	
	function parseExif($file = null) {
		if (! extension_loaded('exif')) return false;
		
		if (is_null($file)) {
			$file = $this->file;
		} else {
			$this->file = $file;
		}
		$this->exifRaw = null;
		
		if (! $is = @getimagesize($file) or ($is[2] !== IMAGETYPE_JPEG and $is[2] !== IMAGETYPE_JPEG2000)) return false;
	
		$ret = array();
		if ($ret['RAW'] = @ exif_read_data($file)) {
			
			$this->exifRaw = $exif_data = $ret['RAW'];
			
			// set GPS
			if ($gps = $this->calExifGeo()) {
				$ret['GPS'] = $gps;
			} else {
				$ret['GPS'] = array();
			}
	
			if (isset($exif_data['DateTimeOriginal']))
				$ret['Date'] = $exif_data['DateTimeOriginal'];

			if (isset($exif_data['Model']))
				$ret['Camera'] = $exif_data['Model'];
				
			if (isset($exif_data['Make'])) {
				if (strpos($ret['Camera'], $exif_data['Make']) !== 0){
					$ret['Camera'] = $exif_data['Make'] . ' ' . $ret['Camera'];
				}
			}
			
			if (isset($exif_data['FocalLength']))
				$ret['Lens'] = $this->get_exif_numbar($exif_data['FocalLength']).' mm';
			if (isset($exif_data['FocalLengthIn35mmFilm']))
				@$ret['Lens'] .= '(35mm:' . $this->get_exif_numbar($exif_data['FocalLengthIn35mmFilm']).' mm)';
			if (isset($exif_data['MaxApertureValue']))
				@$ret['Lens'] .= '/F '.$this->get_exif_numbar($exif_data['MaxApertureValue'], 'A');
			
			if (isset($exif_data['ExposureTime']))
				$ret['Exposure'] = $this->get_exif_numbar($exif_data['ExposureTime'], FALSE, 'fraction').' sec';
				
			if (isset($exif_data['ApertureValue'])) {
				$ret['Aperture'] = 'F '.$this->get_exif_numbar($exif_data['ApertureValue'], 'A');
			} else if (isset($exif_data['FNumber'])) {
				$ret['Aperture'] = 'F '.$this->get_exif_numbar($exif_data['FNumber']);
			}
			
			if (isset($exif_data['ExposureBiasValue'])) {
				$ret['Exp.Bias'] = $this->get_exif_numbar($exif_data['ExposureBiasValue'], FALSE).' EV';
			}
			
			if (isset($exif_data['ISOSpeedRatings'])) {
				$ret['ISO'] = $this->get_exif_numbar($exif_data['ISOSpeedRatings'], FALSE);
			}
			
			// ExposureMode
			$this->setKnownExifTag($ret, $exif_data, 'ExposureMode');
			
			// ExposureProgram
			$this->setKnownExifTag($ret, $exif_data, 'ExposureProgram');
						
			// MeteringMode
			$this->setKnownExifTag($ret, $exif_data, 'MeteringMode');
			
			// WhiteBalance
			$this->setKnownExifTag($ret, $exif_data, 'WhiteBalance');
			
			// SceneCaptureType
			$this->setKnownExifTag($ret, $exif_data, 'SceneCaptureType');
			
			// Flash
			$this->setKnownExifTag($ret, $exif_data, 'Flash');
				
			if (isset($exif_data['SubjectDistance'])) {
				$ret['Distance'] = $exif_data['SubjectDistance'].' m';
			}
				
			return $ret;
		}
	
		return false;
	}
	
	function calExifGeo(){
	
		if (empty($this->exifRaw)) return false;
	
		$exif = $this->exifRaw;
	
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
		if (stristr(@ $exif['GPSMapDatum'], 'tokyo')){
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
				$dat = '1/' . round(1/$dat);
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
	
	function setKnownExifTag(& $ret, $exif_data, $exifKey) {
		if (! isset($exif_data[$exifKey])) return;
		$_exifval = intval($exif_data[$exifKey]);
		$val_const = '_MD_GNAV_'.strtoupper($exifKey) . '_' . $_exifval;
		if (defined($val_const)) {
			$ret[$exifKey] = $val_const;
		} else {
			$ret[$exifKey] = '_MD_GNAV_EXIF_UNKNOWN';
		}
	}
}
