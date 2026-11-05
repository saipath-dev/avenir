<?php

namespace App\Controllers\MasterFiles;

use App\Controllers\BaseController;
use App\Libraries\MenuLoader;
use App\Libraries\TourFlow\Installer;
use App\Libraries\TourFlow\PhotoLibrary;
use App\Libraries\TourFlow\Staff;

/**
 * Master Files → Tour Photos — master_files/tour_photos (schema 27)
 *
 * The one place the tour photos are kept: three to five good pictures for each destination,
 * attraction or activity — Sigiriya, Dambulla, Kandy, Tea Country, Safari, Galle, Colombo,
 * the beaches — uploaded, previewed, replaced, taken out and added to at any time, each
 * tagged to the places it shows, the best marked preferred. The client quotation picks a
 * picture for every day of the itinerary from here (PhotoLibrary::forIntake); the Tour
 * Inquiry screen lets staff change a day's picture by hand. A presentation layer only:
 * nothing here is a cost, a rate or a price, and no other screen's data changes.
 *
 * One menu URL = one privilege (UserPermissionFilter). Granted to the System Administrator
 * by the installer and to Management by the desks' grant (Desks::TOUR_DESK_V6); Admin
 * grants it further under Group Privileges.
 */
class TourPhotosController extends BaseController
{
    public const URL = 'master_files/tour_photos';

    private ?array $menuData = null;

    public function __construct()
    {
        date_default_timezone_set('Asia/Colombo');
    }

    private function user(): string
    {
        return (string) session()->get('username');
    }

    private function userId(): ?int
    {
        $id = (int) session()->get('user_id');
        return $id > 0 ? $id : null;
    }

    private function db()
    {
        return \Config\Database::connect();
    }

    private function library(): PhotoLibrary
    {
        return new PhotoLibrary($this->db(), Staff::displayName($this->db(), $this->userId(), $this->user()));
    }

    private function menuData(): array
    {
        if ($this->menuData === null) {
            try {
                $this->menuData = (new MenuLoader())->loadMenuData();
            } catch (\Throwable $e) {
                $this->menuData = ['user_role' => session()->get('group_id'), 'allowed_menu_list' => []];
            }
        }
        return $this->menuData;
    }

    private function json(array $payload, int $code = 200)
    {
        return $this->response->setStatusCode($code)->setJSON($payload);
    }

    private function body(): array
    {
        $json = $this->request->getJSON(true);
        return is_array($json) ? $json : ($this->request->getPost() ?: []);
    }

    private function fail(\Throwable $e, int $status = 400)
    {
        return $this->json(['status' => $status, 'message' => $e->getMessage()]);
    }

    /**
     * The files that came with a multipart POST under one field name, as the library takes
     * them: their temporary path, the name they had on the sender's computer, their size.
     * A file that did not arrive whole is left out with its reason.
     *
     * @return array{files: array<int, array{tmp:string, name:string, size:int}>, skipped: array<int, string>}
     */
    private function uploads(string $field): array
    {
        $list = null;
        $req  = $this->request;
        if (method_exists($req, 'getFileMultiple')) {
            $list = $req->getFileMultiple($field);
        }
        if (!$list && method_exists($req, 'getFile')) {
            $one  = $req->getFile($field);
            $list = $one ? [$one] : [];
        }
        $out = ['files' => [], 'skipped' => []];
        foreach ((array) $list as $f) {
            if (!$f) {
                continue;
            }
            $name = method_exists($f, 'getClientName') ? (string) $f->getClientName() : (method_exists($f, 'getName') ? (string) $f->getName() : 'photo');
            if (!$f->isValid()) {
                $why = method_exists($f, 'getErrorString') ? (string) $f->getErrorString() : 'did not arrive whole';
                $out['skipped'][] = $name . ': ' . $why;
                continue;
            }
            $tmp = method_exists($f, 'getTempName') ? (string) $f->getTempName() : (method_exists($f, 'getPathname') ? (string) $f->getPathname() : '');
            $out['files'][] = ['tmp' => $tmp, 'name' => $name, 'size' => (int) (method_exists($f, 'getSize') ? $f->getSize() : filesize($tmp))];
        }
        return $out;
    }

    /** PHP's own ceiling on an upload, in megabytes: the smaller of upload_max_filesize and post_max_size. */
    public static function uploadLimitMb(): ?float
    {
        $mb = static function (string $v): ?float {
            $v = trim($v);
            if ($v === '' || $v === '-1' || $v === '0') {
                return null;   // no limit set
            }
            $n = (float) $v;
            switch (strtolower(substr($v, -1))) {
                case 'g': $n *= 1024; break;
                case 'm': break;
                case 'k': $n /= 1024; break;
                default: $n = (float) $v / 1048576;
            }
            return $n;
        };
        $a = $mb((string) ini_get('upload_max_filesize'));
        $b = $mb((string) ini_get('post_max_size'));
        if ($a === null) {
            return $b;
        }
        return $b === null ? $a : min($a, $b);
    }

    /** Why a POST arrived without its files: most often PHP dropped it whole for being over post_max_size. */
    private function nothingArrived(): string
    {
        $len = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
        $limit = self::uploadLimitMb();
        if ($len > 0 && $limit !== null && $len > $limit * 1048576) {
            return 'The upload was bigger than this server takes in one go (' . rtrim(rtrim(number_format($limit, 1), '0'), '.') . ' MB: PHP\'s post_max_size / upload_max_filesize). Send fewer or smaller photos at a time, or ask the administrator to raise the limit in php.ini.';
        }
        return 'Choose one or more photos to upload.';
    }

    // GET master_files/tour_photos
    public function index()
    {
        $data = $this->menuData();
        $data['user']       = $this->user();
        $data['page_url']   = self::URL;
        $data['intake_url'] = 'tour/intake';
        $data['installed']  = false;
        try {
            $v = Installer::installedVersion($this->db());
            $data['installed'] = $v !== null && $v >= Installer::SCHEMA_VERSION;
        } catch (\Throwable $e) {
            $data['error'] = $e->getMessage();
        }
        return view('pages/master_files/tour_photos', $data);
    }

    // GET master_files/tour_photos/data[?all=1] — every tag with its count, every photo (the removed ones too with all=1), and where the files go
    public function data()
    {
        try {
            $lib = $this->library();
            $all = (string) $this->request->getGet('all') === '1';
            $dir = $lib->dir();
            $writable = is_dir($dir) ? is_writable($dir) : (is_dir(dirname($dir)) && is_writable(dirname($dir)));
            return $this->json(['status' => 200, 'data' => [
                'tags'   => $lib->tags(true),
                'photos' => $lib->photos(null, $all),
                'kinds'  => PhotoLibrary::KINDS,
                'folder' => $lib->folder(),
                'folder_ok' => $writable,
                'resize' => function_exists('imagecreatefromstring'),
                'upload_limit_mb' => self::uploadLimitMb(),
                'in_use' => (int) ($this->db()->query('SELECT COUNT(DISTINCT intake_id) n FROM tf_intake_day_photo WHERE photo_id IS NOT NULL')->getRowArray()['n'] ?? 0),
            ]]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST master_files/tour_photos/tag {code, name, kind, keywords, active, sort_order} — add a destination or attraction, or change one
    public function tag()
    {
        try {
            $row = $this->library()->saveTag($this->body());
            return $this->json(['status' => 200, 'message' => 'Saved. Every quotation built from now on reads it; a day already pictured keeps its picture unless the day changes.', 'data' => $row]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST master_files/tour_photos/tag/delete/(:segment) — off the list; switched off instead when a photo carries it
    public function tagDelete($code)
    {
        try {
            $msg = $this->library()->deleteTag((string) $code);
            return $this->json(['status' => 200, 'message' => $msg]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST master_files/tour_photos/upload — multipart: files[] (one or many), tags (codes, comma separated), caption, credit, preferred
    public function upload()
    {
        try {
            $lib = $this->library();
            $up  = $this->uploads('files');
            if ($up['files'] === [] && $up['skipped'] === []) {
                $up = $this->uploads('file');
            }
            if ($up['files'] === []) {
                return $this->json(['status' => 400, 'message' => $up['skipped'] ? implode(' ', $up['skipped']) : $this->nothingArrived()]);
            }
            $meta = ['tags' => $this->request->getPost('tags'), 'caption' => $this->request->getPost('caption'), 'credit' => $this->request->getPost('credit'), 'preferred' => $this->request->getPost('preferred')];
            $added = [];
            $errors = $up['skipped'];
            foreach ($up['files'] as $f) {
                try {
                    $added[] = $lib->addPhoto($f, $meta);
                } catch (\Throwable $e) {
                    $errors[] = $f['name'] . ': ' . $e->getMessage();
                }
            }
            $n = count($added);
            $msg = ($n ? $n . ' photo' . ($n === 1 ? '' : 's') . ' added.' : 'Nothing was added.') . ($errors ? ' ' . implode(' ', $errors) : '');
            return $this->json(['status' => $n ? 200 : 400, 'message' => $msg, 'data' => $added, 'errors' => $errors]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST master_files/tour_photos/photo/(:num)/save {caption, credit, preferred, sort_order, tags}
    public function save($id)
    {
        try {
            $row = $this->library()->savePhoto((int) $id, $this->body());
            return $this->json(['status' => 200, 'message' => 'Saved.', 'data' => $row]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST master_files/tour_photos/photo/(:num)/replace — multipart: file — a better picture under the same entry
    public function replace($id)
    {
        try {
            $up = $this->uploads('file');
            if ($up['files'] === []) {
                $up = $this->uploads('files');
            }
            if ($up['files'] === []) {
                return $this->json(['status' => 400, 'message' => $up['skipped'] ? implode(' ', $up['skipped']) : 'Choose the new photo.']);
            }
            $row = $this->library()->replacePhoto((int) $id, $up['files'][0]);
            return $this->json(['status' => 200, 'message' => 'Replaced. Every quotation that carries this picture shows the new one.', 'data' => $row]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST master_files/tour_photos/photo/(:num)/remove — out of the library; kept on disk, off every itinerary it was on
    public function remove($id)
    {
        try {
            $row = $this->library()->removePhoto((int) $id);
            return $this->json(['status' => 200, 'message' => 'Taken out of the library. Any day it was pictured on chooses another picture; it can be put back from "Show removed".', 'data' => $row]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST master_files/tour_photos/photo/(:num)/restore
    public function restore($id)
    {
        try {
            $row = $this->library()->restorePhoto((int) $id);
            return $this->json(['status' => 200, 'message' => 'Back in the library.', 'data' => $row]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }
}
