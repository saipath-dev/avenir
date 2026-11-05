<?php

namespace App\Controllers;

use App\Filters\AccessPolicyFilter;

/**
 * tf-e8-access-r1 — the tombstone of a changing link converted to POST (E8, 3.5). The old GET address stays for one
 * stabilisation period: it answers 405, changes nothing, and records the caller in the audit trail
 * (access_policy_tombstone) so any page still using the old link can be found and fixed.
 */
class AccessPolicyController extends BaseController
{
    public function tombstone()
    {
        $path = trim((string) service('uri')->getRoutePath(), '/');
        $ids = [];
        foreach (['id', 'tour_id', 'type', 'status', 'flag'] as $k) {
            $v = $this->request->getGet($k);
            if (is_scalar($v) && (string) $v !== '' && mb_strlen((string) $v) <= 40 && preg_match('#^[A-Za-z0-9 _./\-]+$#', (string) $v)) {
                $ids[$k] = (string) $v;
            }
        }
        AccessPolicyFilter::audit($this->request, 'access_policy_tombstone', [
            'mode' => AccessPolicyFilter::mode(), 'method' => 'GET', 'path' => $path, 'group' => (int) session()->get('group_id'),
            'status' => 405, 'rule' => 'tombstone', 'ids' => $ids,
            'referer' => mb_substr((string) parse_url($this->request->getHeaderLine('Referer'), PHP_URL_PATH), 0, 200),   // the calling page, without its query
        ], 'old GET link used; nothing was changed');
        return $this->response->setStatusCode(405)->setHeader('Allow', 'POST')->setJSON([
            'status' => 405, 'msg' => 'Refused',
            'message' => 'This action now needs a POST from the page — reload the page and try again. Nothing was changed.',
        ]);
    }
}
