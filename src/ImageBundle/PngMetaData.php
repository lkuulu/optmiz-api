<?php
namespace STHImage;

class PngMetaData implements ImageMetaDataInterface {
  protected $img;
  protected $file;
  protected $imageData;

  public function setImageMeta(&$imageData) {
    $this->imageData = $imageData;
  }

  public function loadImage($imageFile) {
    // Use imagick object to read the file
    $this->imageFile = $imageFile;
    $this->img = new \imagick($this->imageFile);
    $jsonData = $this->img->getImageProperties("Exif:UserComment");
  }

  public function postMetaData($jsonData) {
    $this->img->setImageProperty('Exif:UserComment', $jsonData);
    $this->saveImage();
  }
  
  public function saveImage() {
    // Use imagick object to write the file
    $this->img->writeImage($this->imageFile);
  }
  
  public function getMetaData() {
    // in png image, data are store in Exif:UserComment property
    $jsonData = $this->img->getImageProperties("Exif:UserComment");
   
    // Are there informations?
    if (isset($jsonData['Exif:UserComment'])) {
      $jsonStringValue=$jsonData['Exif:UserComment'];
      $rawJson=json_decode($jsonStringValue, true);
    } else {
      $rawJson=array('crops'=>false, 'poi'=>false);
    }
    
    // Populate
    $this->imageData->setRawCropRatio($rawJson["crops"]);
    $this->imageData->setRawPoi($rawJson["poi"]);

    return $rawJson;
  }
};
