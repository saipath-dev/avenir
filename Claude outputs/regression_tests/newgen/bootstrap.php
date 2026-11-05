<?php
/**
 * Bootstrap for the new-generation regression battery (PHP side): the real application's classes against the TEST
 * database named in the copy's .env. CodeIgniter's own test bootstrap is not used because it forces the 'tests'
 * database group, which this application does not declare; this one boots the framework in production mode with
 * debugging off (no Kint), so the .env of the copy decides the database — and NewGenTestCase refuses to run unless
 * that database's name ends in "_test". Never run this against the live database: it will refuse, by design.
 */
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

$_SERVER['CI_ENVIRONMENT'] = 'production';
define('ENVIRONMENT', 'production');
define('CI_DEBUG', false);

$root = realpath(__DIR__ . '/../..');
chdir($root);
defined('HOMEPATH')   || define('HOMEPATH', $root . DIRECTORY_SEPARATOR);
defined('CONFIGPATH') || define('CONFIGPATH', realpath($root . '/app/Config') . DIRECTORY_SEPARATOR);
defined('PUBLICPATH') || define('PUBLICPATH', realpath($root . '/public') . DIRECTORY_SEPARATOR);

require CONFIGPATH . 'Paths.php';
$paths = new Config\Paths();
defined('APPPATH')    || define('APPPATH', realpath(rtrim($paths->appDirectory, '\\/ ')) . DIRECTORY_SEPARATOR);
defined('ROOTPATH')   || define('ROOTPATH', realpath(APPPATH . '../') . DIRECTORY_SEPARATOR);
defined('SYSTEMPATH') || define('SYSTEMPATH', realpath(rtrim($paths->systemDirectory, '\\/')) . DIRECTORY_SEPARATOR);
defined('WRITEPATH')  || define('WRITEPATH', realpath(rtrim($paths->writableDirectory, '\\/ ')) . DIRECTORY_SEPARATOR);
defined('TESTPATH')   || define('TESTPATH', realpath(HOMEPATH . 'tests/') . DIRECTORY_SEPARATOR);
defined('CIPATH')     || define('CIPATH', realpath(SYSTEMPATH . '../') . DIRECTORY_SEPARATOR);
defined('FCPATH')     || define('FCPATH', realpath(PUBLICPATH) . DIRECTORY_SEPARATOR);
defined('SUPPORTPATH')   || define('SUPPORTPATH', realpath(TESTPATH . 'newgen/_support/') . DIRECTORY_SEPARATOR);
defined('COMPOSER_PATH') || define('COMPOSER_PATH', (string) realpath(HOMEPATH . 'vendor/autoload.php'));
defined('VENDORPATH')    || define('VENDORPATH', realpath(HOMEPATH . 'vendor') . DIRECTORY_SEPARATOR);

require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootTest($paths);
CodeIgniter\Config\Services::routes()->loadRoutes();

require __DIR__ . '/_support/NewGenTestCase.php';
