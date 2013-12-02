<?php
namespace zeus\web;

use zeus\Zeus;
use zeus\utils\Watermark;
use zeus\utils\Thumb;
use zeus\utils\mkdir;
use zeus\config\ConfigInterface;

class Uploader
{   
    private $_dir;
    private $_time;
    private $_type;  
    private $_fieldName;
    private $_maxSize;
    
    private $_waterMark;
    private $_thumb;
 
    public function __construct($fieldName = 'attach', $type = 'jpg|gif|png', $maxSize = 1024)
    {
        $this->_fieldName = $fieldName;
        $this->_type = explode('|', $type);
        $this->_maxSize = $maxSize * 1024;
        $this->_time = time();
        $this->setDir('./public/uploads/');
    }
    
    public function setWaterMark(Watermark $_waterMark)
    {
    	$this->_waterMark = $_waterMark;
    }
    
    public function setThumb(Thumb $_thumb)
    {
    	$this->_thumb = $_thumb;
    }

    public function setDir($uploadDir)
    {
        $this->_dir = Zeus::getInstance()->getConfig()->webRoot()."/".$uploadDir . date('Ym', $this->_time) . '/';
       	
        mkdir($this->_dir);
    }

    public function execute()
    {
        $files = array();
        $fieldName = $this->_fieldName;
        $keys = array_keys($_FILES[$fieldName]['name']);
        foreach ($keys as $key) {
            if (!$_FILES[$fieldName]['name'][$key]) continue;
            
            $fileExt = getFileExt($_FILES[$fieldName]['name'][$key]);
            $fileName = date('dHis', $this->_time) . mt_rand(100, 999) . '.' . $fileExt;
            $fileDir = $this->_dir;
            $fileSize = $_FILES[$fieldName]['size'][$key];
            
            if (!in_array($fileExt,$this->_type)) {
                $files[$key]['name'] = $_FILES[$fieldName]['name'][$key];
                $files[$key]['flag'] = -1;
                continue;
            }
 
            if ($fileSize > $this->_maxSize) {
                $files[$key]['name'] = $_FILES[$fieldName]['name'][$key];
                $files[$key]['flag'] = -2;
                continue;
            }
            $files[$key]['name'] = $fileName;
            $files[$key]['dir'] = $fileDir;
            $files[$key]['size'] = $fileSize;

            if (is_uploaded_file($_FILES[$fieldName]['tmp_name'][$key])) {
                move_uploaded_file($_FILES[$fieldName]['tmp_name'][$key], $fileDir . $fileName);
                @unlink($_FILES[$fieldName]['tmp_name'][$key]);
                $files[$key]['flag'] = 1;

                if (in_array($fileExt, array('jpg', 'png', 'gif'))) {
                    if ($this->_thumbWidth) 
                    {
                        if ($this->_thumb->render($fileDir . $fileName, $fileDir . 'thumb_' . $fileName)) {
                            $files[$key]['thumb'] = 'thumb_' . $fileName;
                        }
                    }
                    
                    $this->_waterMark->render($fileDir . $fileName);
                }
            }
        }
        return $files;
    }
}
