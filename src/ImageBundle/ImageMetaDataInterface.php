<?php
namespace STHImage;
/*
 *  Interface define image specific methods 
 */
interface ImageMetaDataInterface {
  public function loadImage($imageFile);
  public function saveImage();
  public function postMetaData($json);
  public function getMetaData();
}
