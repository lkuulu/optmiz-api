<?php
namespace STHImage;

class GenericMetaData implements ImageMetaDataInterface {
  protected $img;
  protected $file;
  protected $imageData;
  protected $metadataFile;

  public function setImageMeta(&$imageData) {
    $this->imageData = $imageData;
  }

  public function loadImage($imageFile) {
    // Use imagick object to read the file
    $this->imageFile = $imageFile;
    
    // on charge les metadata dans un fichier description
    $this->metadataFile = $this->imageFile.'.json';
    if (file_exists($this->metadataFile)) {
      $jsonData = json_decode(file_get_contents($this->metadataFile));	
    } else {
    	$jsonData=json_encode(array());
    }
  }

  public function postMetaData($jsonData) {
  	// save json to $metadataFile
  	file_put_contents($this->metadataFile, $jsonData);
  }
  
  public function saveImage() {
  	// in this case, nothing to do, image don't store anything
  }
  
  public function getMetaData() {
    // in those imageformats, data are store in a file next to image file
    if (file_exists($this->metadataFile)) {
      $jsonStringValue = file_get_contents($this->metadataFile);	
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