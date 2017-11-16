<?php
namespace STHApi\Hooks;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CorsHook
{
    public function __invoke(Request $request, Response $response)
    {
        $responseHeaders = $response->headers;
        $responseHeaders->set('Access-Control-Allow-Headers', 'Origin, Content-type, Accept, Authorization');
        $responseHeaders->set('Access-Control-Allow-Origin', '*');
        $responseHeaders->set('Access-Control-Allow-Methods', 'GET, POST, GET, PUT, DELETE, PATCH, OPTIONS, HEAD');
    }
}
