<?php
namespace STHImage;

class JpgMetaData implements ImageMetaDataInterface {
  protected $info;
  protected $content;
  protected $imageData;
  protected function iptc_make_tag($rec, $data, $value) {
      $length = strlen($value);
      $retval = chr(0x1C) . chr($rec) . chr($data);
  
      if($length < 0x8000) {
          $retval .= chr($length >> 8) .  chr($length & 0xFF);
      } else {
          $retval .= chr(0x80) . 
                     chr(0x04) . 
                     chr(($length >> 24) & 0xFF) . 
                     chr(($length >> 16) & 0xFF) . 
                     chr(($length >> 8) & 0xFF) . 
                     chr($length & 0xFF);
      }
  
      return $retval . $value;
  }

  public function setImageMeta(&$imageData) {
    $this->imageData = $imageData;
    
  }

  public function loadImage($imageFile) {
    // don't realy load image, not necessary
    $this->imageFile = $imageFile;
    $this->imageSize = getimagesize($this->imageFile, $this->info);

    // check compliance to IPTC
/*
    if(!isset($this->info['APP13'])) {
      header($_SERVER["SERVER_PROTOCOL"]." 415 Unsupported Media Type", true, 415);
      die('Erreur : Données IPTC trouvées dans l\'image, nous ne pouvons continuer');
    }
*/    
  }
  
  public function postMetaData($jsonData) {
    // case else, iptc
    // Définit le drapeau IPTC
    $iptc = array(
      '2#120' => $jsonData
    );
    // Conversion du drapeau IPTC en code binaire
    $data = '';

    foreach($iptc as $tag => $string) {
      $tag = substr($tag, 2);
      $data .= $this->iptc_make_tag(2, $tag, $string);
    }
    // get content from image row file and $datas
    $this->content = iptcembed($data, $this->imageFile);
    $this->saveImage();
  }
  public function saveImage() {
    // Load image and write new payload.
    $fp = fopen($this->imageFile, "wb");
    fwrite($fp, $this->content);
    fclose($fp);
  }
  
  public function getMetaData() {
    if(is_array($this->info)) {   
      if (isset($this->info["APP13"])) {
        $iptc    = iptcparse($this->info["APP13"]);
        if (isset($iptc['2#120'])) {
          $rawJson =  json_decode($iptc['2#120'][0], true);
          $this->imageData->setRawCropRatio($rawJson["crops"]);
          $this->imageData->setRawPoi($rawJson["poi"]);
        }
      }
    }
    return (isset($rawJson)) ? $rawJson : array('crops'=>false,'poi'=>false);  
  }
    
};