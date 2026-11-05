<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * tf-help-r1 — puts the "User Manual" button on every screen.
 *
 * After a page is built, for a signed-in person and an ordinary HTML page only (not AJAX, not
 * JSON, not a file or a print), adds the help's stylesheet and script just before </body>. The
 * button opens the help panel; nothing else on the page is touched.
 */
class HelpWidgetFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        try {
            if (!session()->get('logged_in') || !session()->get('group_id')) {
                return null;
            }
            if ($request->isAJAX() || strtolower($request->getMethod()) !== 'get') {
                return null;
            }
            $type = strtolower($response->getHeaderLine('Content-Type'));
            if ($type !== '' && !str_contains($type, 'text/html')) {
                return null;
            }
            if ($response->getStatusCode() !== 200 || $response->hasHeader('Content-Disposition')) {
                return null;
            }
            $body = (string) $response->getBody();
            $pos = strripos($body, '</body>');
            if ($pos === false || str_contains($body, 'id="avh-root"') || str_contains($body, 'assets/help/help-widget.js')) {
                return null;
            }
            $path = trim(service('uri')->getRoutePath(), '/');
            $v = 'r1-' . substr(md5((string) @filemtime(FCPATH . 'assets/help/help-widget.js') . (string) @filemtime(FCPATH . 'assets/help/help-widget.css')), 0, 8);
            $base = base_url();
            $h = static fn ($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
            $tag = "\n<!-- tf-help-r1: User Manual -->\n"
                . '<link rel="stylesheet" href="' . $h($base . 'assets/help/help-widget.css?v=' . $v) . '">' . "\n"
                . '<script src="' . $h($base . 'assets/help/help-widget.js?v=' . $v) . '" data-base="' . $h($base) . '" data-path="' . $h($path) . '" defer></script>' . "\n";
            $response->setBody(substr($body, 0, $pos) . $tag . substr($body, $pos));
        } catch (\Throwable $e) {
            // help must never break a page
        }
        return null;
    }
}
