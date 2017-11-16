<?php

namespace STHApi;

use Symfony\Component\HttpFoundation\Request;
use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Response;

class ApiFiletreeController implements ControllerProviderInterface
{

    public function connect(Application $app)
    {
        // creates a new controller based on the default route
        $controllers = $app['controllers_factory'];
        $controllers->get('/', 'STHApi\ApiFiletreeController::getFiletree');
        $controllers->post('/', 'STHApi\ApiFiletreeController::getFiletree');
        $controllers->options('/', function () { return 'OK'; });
        return $controllers;
    }

    private function hasSubDir($directory, Application $app) {
        $nbDir = 0;
        if (file_exists($directory)) {
            //$app['monolog']->debug("Exists : ".$directory);
            $files = scandir($directory);
            natcasesort($files);
            if (count($files) > 2) { // The 2 accounts for . and ..
                //$app['monolog']->debug("count >2");
                foreach ($files as $file) {
                    //$app['monolog']->debug("Check for ".$directory .'/'. $file);
                    if (file_exists($directory .'/'. $file) && $file != '.' && $file != '..') {
                        if (is_dir($directory .'/'. $file)) {
                            // $app['monolog']->debug("Directory found ".$directory .'/'. $file);

                            $nbDir++;
                            break;
                        }
                    }
                }
            }
        }
        //$app['monolog']->debug("nbDirs : ". $nbDir);
        return ($nbDir>0);
    }

    public function getFiletree(Application $app, Request $request)
    {
        //$postParams = $app['request_stack']->getCurrentRequest()->request->all();

        $postParams = array();
        $content = $request->getContent();
        if (!empty($content))
        {
            $postParams = json_decode($content, true); // 2nd param to get as array
        }

        $root = $app['repository'];

        $dir = rawurldecode(isset($postParams['dir']) ? $postParams['dir'] : '/');
        $postDir = rawurldecode($root . (isset($postParams['dir']) ? $postParams['dir'] : null));

        // set checkbox if multiSelect set to true
        $onlyFolders = (isset($postParams['onlyFolders']) && $postParams['onlyFolders'] == 'true') ? true : false;
        $onlyFiles = (isset($postParams['onlyFiles']) && $postParams['onlyFiles'] == 'true') ? true : false;


//        $app['monolog']->debug($postDir);
//        $app['monolog']->debug($dir);

        if (file_exists($postDir)) {
            $result['info'] = ["level" => substr_count($dir, '/') - 1];
            $files = scandir($postDir);
            $returnDir = substr($postDir, strlen($root));
            natcasesort($files);
            if (count($files) > 2) { // The 2 accounts for . and ..
                foreach ($files as $file) {
                    $htmlRel = htmlentities($returnDir . $file);
                    $htmlName = htmlentities($file);
                    $ext = preg_replace('/^.*\./', '', $file);
                    if (file_exists($postDir . $file) && $file != '.' && $file != '..') {
                        if (is_dir($postDir . $file) && (!$onlyFiles || $onlyFolders)) {
                            $result['folders'][] = ["rel" => $htmlRel, "name" => $htmlName, "hasSubDir"=> $this->hasSubDir($postDir . $file, $app)];
                        } else if (!$onlyFolders || $onlyFiles)
                            $result['files'][] = ["rel" => $htmlRel, "ext" => $ext, "name" => $htmlName];
                    }
                }
            }
            return json_encode($result);
        } else {
            return new Response("400 bad request", 400);
        }
    }


}