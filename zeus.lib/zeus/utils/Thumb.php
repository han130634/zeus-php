<?php
namespace zeus\utils;

class Thumb
{
	private $_thumbWidth;
	private $_thumbHeight;
	
	public function __construct($width = 0, $height = 0)
	{
		$this->_thumbWidth = $width;
		$this->_thumbHeight = $height;
	}
	
	public function render($srcFile, $thumbFile)
	{
		$t_width  = $this->_thumbWidth;
		$t_height = $this->_thumbHeight;
		if (!file_exists($srcFile)) return false;
		$srcInfo = getImageSize($srcFile);
		
		if ($srcInfo[0] <= $t_width && $srcInfo[1] <= $t_height) {
			if (!copy($srcFile, $thumbFile)) {
				return false;
			}
			return true;
		}
		
		if ($srcInfo[0] - $t_width > $srcInfo[1] - $t_height) {
			$t_height = ($t_width / $srcInfo[0]) * $srcInfo[1];
		} else {
			$t_width = ($t_height / $srcInfo[1]) * $srcInfo[0];
		}
		
		$fileExt = get_fileext($srcFile);
		switch ($fileExt) {
			case 'jpg' : $srcImg = ImageCreateFromJPEG($srcFile); break;
			case 'png' : $srcImg = ImageCreateFromPNG($srcFile); break;
			case 'gif' : $srcImg = ImageCreateFromGIF($srcFile); break;
		}
		
		$thumbImg = @ImageCreateTrueColor($t_width, $t_height);
		
		if (function_exists('imagecopyresampled')) {
			@ImageCopyResampled($thumbImg, $srcImg, 0, 0, 0, 0, $t_width, $t_height, $srcInfo[0], $srcInfo[1]);
		} else {
			@ImageCopyResized($thumbImg, $srcImg, 0, 0, 0, 0, $t_width, $t_height, $srcInfo[0], $srcInfo[1]);
		}
		
		switch ($fileExt) {
			case 'jpg' : ImageJPEG($thumbImg,$thumbFile); break;
			case 'gif' : ImageGIF($thumbImg,$thumbFile); break;
			case 'png' : ImagePNG($thumbImg,$thumbFile); break;
		}
		
		@ImageDestroy($srcImg);
		@ImageDestroy($thumbImg);
		return true;
	}
}