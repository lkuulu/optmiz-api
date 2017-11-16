<?php
namespace STHImage;

class ImageMetaData {
  protected $imagedata;
  protected $imageFile;
  public $poi;
  public $cropRatio;
  public $size;
  public $weight;
 
  function getCropRatio() {
    return $this->cropRatio;
  }
  
  function setCropRatio($preset, $x, $y, $cx, $cy) {
    $this->cropRatio[$preset]=array('x'=>$x, 'y'=>$y, 'w'=>$cx, 'h'=>$cy);
  }  

  function setRawCropRatio($cropRatio) {
    $this->cropRatio = $cropRatio;
  }  

  function getPoi() {
    return $this->poi;
  }
  
  function setRawPoi($poi) {
    return $this->poi=$poi;
  }
  
  function setPoi($x, $y) {
    $this->poi=array('x'=>$x, 'y'=>$y);
  }  

  public function __construct(ImageMetaDataInterface $imagedata) {
    $this->imagedata = $imagedata;
    $this->imagedata->setImageMeta($this);
  }
  
  public function getJsonData() {
    // iterate on $this->cropArray and concatenate $this->poi
    $datas['crops'] = $this->getCropRatio();
    $datas['poi']   = $this->getPoi();
    return json_encode($datas);
  }
  
  
  /*
   * Map generic methods to imageData interface methods
   */
  public function loadImage($imagefile) {
    return $this->imagedata->loadImage($imagefile);
  }
  
  public function postMetaData($jsonData) {
    return $this->imagedata->postMetaData($jsonData);
  }

  public function getMetaData() {
    return $this->imagedata->getMetaData();
  }
  
  public function saveImage() {
    return $this->imagedata->saveImage();
  }

  /*
     Instanciate main class implements method 
     corresponding to image format
  */
  public static function initMetaDataImage($file) {
    // get mime-type 
    $size = @getimagesize($file, $info);
    if ($size!==false) {
      $imageType = $size['mime'];
      // find correct class for the mime-type
      if ($imageType == 'image/png') {
        $className='STHImage\PngMetaData';
      } elseif ($imageType == 'image/jpeg') {
        $className='STHImage\JpgMetaData';
      } else {
      	// use a generic class 
        $className='STHImage\GenericMetaData';
      }
      
      
      // instanciate correct class
      $instance = new ImageMetaData(new $className);
      $instance->size=$size;
      $instance->loadImage($file);
      $instance->getMetaData();
      return $instance;
    } else return false; 
  }
}