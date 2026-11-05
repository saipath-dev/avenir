<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Filters\CSRF;
use CodeIgniter\Filters\DebugToolbar;
use CodeIgniter\Filters\Honeypot;
use CodeIgniter\Filters\InvalidChars;
use CodeIgniter\Filters\SecureHeaders;

class Filters extends BaseConfig
{
    /**
     * Configures aliases for Filter classes to
     * make reading things nicer and simpler.
     *
     * @var array<string, array<int, string>|string> [filter_name => classname]
     *                                               or [filter_name => [classname1, classname2, ...]]
     * @phpstan-var array<string, class-string|list<class-string>>
     */
    public array $aliases = [
        'csrf'          => CSRF::class,
        'toolbar'       => DebugToolbar::class,
        'honeypot'      => Honeypot::class,
        'invalidchars'  => InvalidChars::class,
        'secureheaders' => SecureHeaders::class,
        // 'cors'          => Cors::class,
        'UserSessionFilter' => \App\Filters\UserSessionFilter::class,
        'UserPermissionFilter' => \App\Filters\UserPermissionFilter::class,
        'StripTagsFilter' => \App\Filters\StripTagsFilter::class,
        'corsFilter' => \App\Filters\CorsFilter::class,
    ];

    /**
     * List of filter aliases that are always
     * applied before and after every request.
     *
     * @var array<string, array<string, array<string, string>>>|array<string, array<string>>
     * @phpstan-var array<string, list<string>>|array<string, array<string, array<string, string>>>
     */
    public array $globals = [
        'before' => [
            'UserSessionFilter' => ['except' => ['api/*', '/', 'admin', 'admin/login', 'login', 'logout', 'vision/test', 'driver/*']],
            'UserPermissionFilter' => ['except' => ['api/*', '/', 'login', 'logout', '403', 'dashboard', 'driver_portal', 'driver_portal/*', 'my_profile', 'my_profile/*', 'navigation', 'message-center', 'vision/test', 'driver/*', 'master_files/daily_usd_rate/quick_save', 'master_files/daily_usd_rate/today']],
            // 'honeypot',
            // 'csrf',
            'invalidchars',
            'StripTagsFilter' => ['except' => ['chat/send_message', 'accounts/debtor_invoice_new/complete_payment', 'tour/reserve_hotel/hotel_reservation']],
            'corsFilter',
        ],
        'after' => [
            'toolbar',
            // 'honeypot',
            // 'secureheaders',
        ],
    ];

    /**
     * List of filter aliases that works on a
     * particular HTTP method (GET, POST, etc.).
     *
     * Example:
     * 'post' => ['foo', 'bar']
     *
     * If you use this, you should disable auto-routing because auto-routing
     * permits any HTTP method to access a controller. Accessing the controller
     * with a method you don't expect could bypass the filter.
     */
    public array $methods = [
        // 'post' => ['invalidchars']
    ];

    /**
     * List of filter aliases that should run on any
     * before or after URI patterns.
     *
     * Example:
     * 'isLoggedIn' => ['before' => ['account/*', 'profiles/*']]
     */
    public array $filters = [];
}
