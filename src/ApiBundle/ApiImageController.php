<?php

namespace STHApi;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use STHImage\ImageMetaData;

class ApiImageController implements ControllerProviderInterface
{

   // const ROOT = '/var/www/optmiz/image/repo1/files';

    public function connect(Application $app)
    {
        // creates a new controller based on the default route
        $controllers = $app['controllers_factory'];
        $controllers->patch('/{path}', 'STHApi\ApiImageController::updateImage')->assert('path', '.+');
        $controllers->get('/{path}', 'STHApi\ApiImageController::getImage')->assert('path', '.+');
        $controllers->post('/', 'STHApi\ApiImageController::createImage');
        $controllers->delete('/{path}', 'STHApi\ApiImageController::deleteImage')->assert('path', '.+');

        $controllers->options('/{path}', 'STHApi\ApiImageController::optionValidation')->assert('path', '.+');
        $controllers->options('/', 'STHApi\ApiImageController::optionRootValidation');

        return $controllers;
    }

    function updateMetaDatas($path, $fileObject, $app)
    {
        $metasData = ImageMetaData::initMetaDataImage($app['repository'] .'/'. $path);
        if ($metasData) {
            if (isset($fileObject['crops'])) {
                foreach ($fileObject['crops'] as $key => $v) {
                    $metasData->setCropRatio($key, $v['x'], $v['y'], $v['w'], $v['h']);
                }
            }
            if (isset($fileObject['poi'])) {
                $metasData->setPoi($fileObject['poi']['x'], $fileObject['poi']['y']);
            }
            $metasData->postMetaData($metasData->getJsonData());
            return 'OK Object patched';
        } else
            return new Response('Invalid file format for '. $path, 400);
    }



    /**
     * @param $file
     * @param $fullpath
     * @return normalized file info
     * {
        "type":"file",
        "path":"2016\/07-g8-familienfoto-im-strandkorb,property=Download.jpg",
        "timestamp":1486385406,
        "size":3725558,
        "dirname":"2016",
        "basename":"07-g8-familienfoto-im-strandkorb,property=Download.jpg",
        "extension":"jpg",
        "filename":"07-g8-familienfoto-im-strandkorb,property=Download"
        }
     */
    function getFileInfo($file, $fullpath, $app)
    {
        $path_parts = pathinfo($file);
        $normalized = [
            'type' => filetype($fullpath),
            'path' => $path_parts['basename'],
            'timestamp' => filectime($fullpath),
            'size' => fileSize($fullpath),
            'dirname' => $path_parts['dirname'],
            'basename' => $path_parts['basename'],
            'extension' => $path_parts['extension'],
            'filename' => $path_parts['filename']
        ];

        return $normalized;
    }

    public function getImage($path, Application $app, Request $request)
    {
        $fullpath = $app['repository'] . '/' . $path;

        if (file_exists($fullpath)) {
            $metasData = ImageMetaData::initMetaDataImage($fullpath);

            if ($metasData) {
                $metas = array("path" => $path,
                    "file" => $this->getFileInfo($path, $fullpath, $app),
                    "size" => $metasData->size,
                    "crops" => $metasData->getCropRatio(),
                    "poi" => $metasData->getPoi());
                return json_encode($metas);
            } else {
                // no metadata can be found, bad image format
                return new Response('400 bad request', 400);
            }
        } else {
            return new Response('404 not found', 404);

        }
    }


    public function optionValidation($path, Application $app, Request $request)
    {
        return "OK";
    }

    public function optionRootValidation( Application $app, Request $request)
    {
        return "OK";
    }

    public function deleteImage($path, Application $app, Request $request)
    {
        $postParams = array();
        $content = $request->getContent();
        if (!empty($content))
        {
            $postParams = json_decode($content, true); // 2nd param to get as array
        }

        $fullpath = $app['repository'] . '/' . $path;
        $fs = new Filesystem();
        try {
            $fs->remove($fullpath);
        } catch (IOExceptionInterface $e) {
            return "An error occurred while removing your directory at " . $e->getPath();
        }
        return json_encode($this->listDirectory($app, $postParams['folder']));
    }

    function listDirectory($app, $path) {
        $files = array();
        $contents = $app['flysystems']['repository']->listContents($path, false);
        foreach ($contents as $object) {
            if ($object['type']!='dir') {
                $object['path'] = $object['path'];
                $files[]=$object;
            }
        }

        return $files;
    }

    public function updateImage($path, Application $app, Request $request)
    {
        $postParams = array();
        $content = $request->getContent();
        if (!empty($content))
        {
            $postParams = json_decode($content, true); // 2nd param to get as array
        }

        //$postParams = $app['request_stack']->getCurrentRequest()->request->all();
        return $this->updateMetaDatas($path, $postParams, $app);
    }

    public function createImage(Application $app, Request $request)
    {
        $postParams = array();
        $content = $request->getContent();
        if (!empty($content))
        {
            $postParams = json_decode($content, true); // 2nd param to get as array
        }
        $app['monolog']->debug(serialize($postParams));
//die();

        $postParams = $app['request_stack']->getCurrentRequest()->request->all();
        //$app['monolog']->debug(serialize($postParams));
        // ltrim first '/' to match FileInfo prerequirements
        $folder = ltrim($postParams['dist_dir'], '/');

        $file = $app['request_stack']->getCurrentRequest()->files->get('file');
        if (!empty($file)) {
            if (isset($file->error)) {
                return new Response("file error ". $file->getErrorMessage(), 400);
            } else {
                $file->move($app['repository'] .'/'. $folder, $file->getClientOriginalName());

                // Is Metadata in post payload form data
                if (isset($postParams['fileObject'])) {
                    if (is_array($postParams['fileObject'])) {
                        $fileObject = (isset($postParams['fileObject']) ? $postParams['fileObject'] : null );
                    } else {
                        $fileObject = json_decode( rawurldecode((isset($postParams['fileObject']) ? $postParams['fileObject'] : null )), true);
                    }
                    if (null!==$fileObject) {
                        $this->updateMetaDatas($folder.$file->getClientOriginalName(), $fileObject, $app);
                    }
                }

                // generate a fileoject result
                $fsFileName = $folder.$file->getClientOriginalName();
                $pathFileName = $app['repository'].'/'.$folder.$file->getClientOriginalName();

                $ofile = $this->getFileInfo($fsFileName, $pathFileName, $app);
//                $ofile['path'] = $ofile['dirname'].'/'.$ofile['path'];
//                $fileinfo=pathinfo($app['repository'].$folder.$_FILES["file"]["name"]);
//                $ofile['type'] = 'file';
//                $ofile['path'] = $folder.$file->getClientOriginalName();
//                $ofile['timestamp'] = filectime(time();
//                $ofile['size'] = .$file->getClientSize();
//                $ofile['dirname'] = "";
//                $ofile['basename'] = $fileinfo['basename'];
//                $ofile['extension'] = $fileinfo['extension'];
//                $ofile['filename']  = $fileinfo['filename'];*/
                return json_encode($ofile);
            }
        }
    }

}