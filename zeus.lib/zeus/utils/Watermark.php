<?php
namespace zeus\utils;

class Watermark
{
	private $_watermarkFile;
	private $_watermarkPos;
	private $_watermarkTrans;
	
	public function __construct($file, $pos = 6, $trans = 80)
	{
		$this->_watermarkFile = $file;
		$this->_watermarkPos = $pos;
		$this->_watermarkTrans = $trans;
	}
	
	public function render($file)
	{
		if (!file_exists($this->_watermarkFile) || !file_exists($file)) return;
		if (!function_exists('getImageSize')) return;
		
		$gd_allow_types = array();
		if (function_exists('ImageCreateFromGIF')) $gd_allow_types['image/gif'] = 'ImageCreateFromGIF';
		if (function_exists('ImageCreateFromPNG')) $gd_allow_types['image/png'] = 'ImageCreateFromPNG';
		if (function_exists('ImageCreateFromJPEG')) $gd_allow_types['image/jpeg'] = 'ImageCreateFromJPEG';
		
		$fileInfo = getImageSize($file);
		$wmInfo = getImageSize($this->_watermarkFile);
		if ($fileInfo[0] < $wmInfo[0] || $fileInfo[1] < $wmInfo[1]) return;
		if (array_key_exists($fileInfo['mime'], $gd_allow_types)) {
			if (array_key_exists($wmInfo['mime'], $gd_allow_types)) {
		
				$temp = $gd_allow_types[$fileInfo['mime']]($file);
				$temp_wm = $gd_allow_types[$wmInfo['mime']]($this->_watermarkFile);
		
				switch ($this->_watermarkPos) {
					case 1 : $dst_x = 0; $dst_y = 0; break;
					case 2 : $dst_x = ($fileInfo[0] - $wmInfo[0]) / 2; $dst_y = 0; break;
					case 3 : $dst_x = $fileInfo[0]; $dst_y = 0; break;
					case 4 : $dst_x = 0; $dst_y = $fileInfo[1]; break;
					case 5 : $dst_x = ($fileInfo[0] - $wmInfo[0]) / 2; $dst_y = $fileInfo[1]; break;
					case 6 : $dst_x = $fileInfo[0]-$wmInfo[0]; $dst_y = $fileInfo[1]-$wmInfo[1]; break;
					default : $dst_x = mt_rand(0,$fileInfo[0]-$wmInfo[0]); $dst_y = mt_rand(0,$fileInfo[1]-$wmInfo[1]);
				}
				if (function_exists('ImageAlphaBlending')) ImageAlphaBlending($temp_wm, True);
				if (function_exists('ImageSaveAlpha')) ImageSaveAlpha($temp_wm, True);
		
				if (function_exists('imageCopyMerge')) {
					ImageCopyMerge($temp, $temp_wm, $dst_x, $dst_y, 0, 0, $wmInfo[0], $wmInfo[1], $this->_watermarkTrans);
				} else {
					ImageCopyMerge($temp, $temp_wm, $dst_x, $dst_y, 0, 0, $wmInfo[0], $wmInfo[1]);
				}
		
				switch ($fileInfo['mime']) {
					case 'image/jpeg' : @imageJPEG($temp,$file); break;
					case 'image/png' : @imagePNG($temp,$file); break;
					case 'image/gif' : @imageGIF($temp,$file); break;
				}
		
				@imageDestroy($temp);
				@imageDestroy($temp_wm);
			}
		}
	}
}