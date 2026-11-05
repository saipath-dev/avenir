<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;
use Config\Cors as CorsConfig;

class CorsFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $corsConfig = config(CorsConfig::class);
        $origin     = trim((string) $request->getHeaderLine('Origin'));

        // Only reflect an origin that is explicitly allow-listed — never echo back an arbitrary value
        if ($origin !== '' && in_array($origin, $corsConfig->default['allowedOrigins'], true)) {
            header('Access-Control-Allow-Origin: ' . $origin);
            header('Vary: Origin');
            header('Access-Control-Allow-Methods: ' . implode(', ', $corsConfig->default['allowedMethods']));
            header('Access-Control-Allow-Headers: ' . implode(', ', $corsConfig->default['allowedHeaders']));
            if ($corsConfig->default['supportsCredentials']) {
                header('Access-Control-Allow-Credentials: true');
            }

            // Preflight — respond immediately with 204
            if (strtolower($request->getMethod()) === 'options') {
                header('HTTP/1.1 204 No Content');
                exit;
            }
        } elseif ($origin !== '' && strtolower($request->getMethod()) === 'options') {
            // Preflight from a non-allow-listed origin — reject
            header('HTTP/1.1 403 Forbidden');
            exit;
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // CORS headers are emitted in before(); nothing to do here.
    }
}
