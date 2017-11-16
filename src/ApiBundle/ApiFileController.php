<?php

namespace STHApi;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

class ApiFileController implements ControllerProviderInterface
{

    public function connect(Application $app)
    {
        // creates a new controller based on the default route
        $controllers = $app['controllers_factory'];

        $controllers->get('/', 'STHApi\ApiFileController::getRootFolder');
        $controllers->get('/{path}', 'STHApi\ApiFileController::getFolder')->assert('path', '.+');
        $controllers->put('/', 'STHApi\ApiFileController::renameFolder');
        $controllers->put('/{path}', 'STHApi\ApiFileController::moveFile')->assert('path', '.+');
        $controllers->post('/', 'STHApi\ApiFileController::createFolder');
        $controllers->delete('/{path}', 'STHApi\ApiFileController::delete')->assert('path', '.+');
        $controllers->options('/', function () {
            return 'OK';
        });
        $controllers->options('/{path}', function ($path) {
            return 'OK';
        })->assert('path', '.+');;

        return $controllers;
    }

    function listDirectory($app, $path)
    {
        $files = array();

        $contents = $app['flysystems']['repository']->listContents($path, false);
        foreach ($contents as $object) {
            if ($object['type'] != 'dir') {
                $object['path'] = $object['path'];
                $files[] = $object;
            }
        }
        return $files;
    }

    public function delete($path, Application $app, Request $request)
    {
        $fullpath = $app['repository'] . '/' . $path;
        $fs = new Filesystem();
        try {
            $fs->remove($fullpath);
        } catch (IOExceptionInterface $e) {
            return new Response('bad request', 400);
            //return "An error occurred while removing your directory at " . $e->getPath();
        }
        return new Response('Removed', 204);
        //return "$path removed.";
    }


    public function getRootFolder(Application $app, Request $request)
    {
        return $this->getFolder('/', $app, $request);
    }

    public function getFolder($path, Application $app, Request $request)
    {
        return json_encode($this->listDirectory($app, $path));
    }

    public function createFolder(Application $app, Request $request)
    {
        $postParams = array();
        $content = $request->getContent();
        if (!empty($content))
        {
            $postParams = json_decode($content, true); // 2nd param to get as array
        }

        //$postParams = $app['request_stack']->getCurrentRequest()->request->all();
        $postDir = $postDir = (isset($postParams['dir']) ? $postParams['dir'] : null);
        $postFolder = (isset($postParams['newfolder']) ? $postParams['newfolder'] : null);

        $fs = new Filesystem();
        try {
            $fs->mkdir($app['repository'] . $postDir . $postFolder, 0755);
        } catch (IOExceptionInterface $e) {
            return new Response('bad request', 400);
            //return "An error occurred while creating your directory at " . $e->getPath();
        }
        return new Response('Created', 201);
        //return $app['repository'] . $postDir . $postFolder . " => $postFolder created.";
    }

    public function renameFolder(Application $app, Request $request)
    {
        $data = array();
        $content = $request->getContent();
        if (!empty($content))
        {
            $data = json_decode($content, true); // 2nd param to get as array
        }


        //$data = json_decode($request->getContent(), true);


        $postDir = $data['source'];
        $postFolder = $data['destination'];
        $postDir = rtrim($postDir, '/');

        $app['monolog']->debug($postDir);
        $app['monolog']->debug($postFolder);

        // pop last directory from path and compose twice parameters old & new folder
        $relativepath = explode('/', $postDir);
        $relativepath = array_slice($relativepath, 0, count($relativepath) - 1);
        $parentpath = implode('/', $relativepath) . '/';

        $fs = new Filesystem();
        try {
            $fs->rename($app['repository'] . $postDir, $app['repository'] . $parentpath . $postFolder);
        } catch (IOExceptionInterface $e) {
            return new Response('bad request', 400);
            //return "An error occurred while renaming your directory at " . $e->getPath();
        }
        return new Response('Modified', 204);
        //return $app['repository'] . $parentpath . $postFolder . " correctly renamed.";
    }

    public function moveFile($path, Application $app, Request $request)
    {
        $data = array();
        $content = $request->getContent();
        if (!empty($content))
        {
            $data = json_decode($content, true); // 2nd param to get as array
        }

        //$data = $app['request_stack']->getCurrentRequest()->request->all();

        $source = '/' . $path;
        $destination = $data['destination'];
        $source = rtrim($source, '/');

//        $app['monolog']->debug($app['repository'] . $source);
//        $app['monolog']->debug($app['repository'] . $destination );

        $fs = new Filesystem();
        try {
            $fs->rename($app['repository'] . $source, $app['repository'] . $destination ); //. $sourceFolder
        } catch (IOExceptionInterface $e) {
            return new Response('bad request', 400);
            //return "An error occurred while moving your directory at " . $e->getPath();
        }
        return new Response('Moved', 204);
        //return "moved";
    }

}