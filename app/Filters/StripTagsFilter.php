<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class StripTagsFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $request_data = [];
        $validated = true;
        $invalid_input = '';

        $request_type = $request->getMethod();
        $request_type = $request_type != null ? strtolower($request_type) : null;

        if ($request_type == 'post') {
            $request_data = $_POST;
        } else if ($request_type == 'get') {
            $request_data = $_GET;
        }

        if (!empty($request_data && is_array($request_data))) {
            // sanitize each item in the request data
            foreach ($request_data as $key => $value) {
                if (!is_array($value)) {
                    if (is_string($value)) {
                        $sanitized_value = strip_tags(trim($value));
                        if ($sanitized_value != trim($value)) {
                            $invalid_input = $value;
                            $validated = false;
                            log_message('critical', 'StripTagsFilter - Invalid Input: ' . $key . ' => ' . $value . ' sanitized to ' . $sanitized_value);
                        }
                    }
                }
            }
        }

        if (!$validated) {
            return service('response')->setStatusCode(400)->setJSON(['message' => 'Invalid input ' . $invalid_input . ' found in the request. Please check your input and try again.']);
        }

        return $request;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do something after the route has executed
    }
}
