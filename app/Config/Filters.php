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
        'HelpWidgetFilter' => \App\Filters\HelpWidgetFilter::class, // tf-help-r1
        'AccessPolicyFilter' => \App\Filters\AccessPolicyFilter::class, // tf-e8-access-r1
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
            'AccessPolicyFilter', // tf-e8-access-r1: Accounts access control (route registry) — after the sign-in check, before the old menu rule
            'UserPermissionFilter' => ['except' => ['api/*', '/', 'login', 'logout', '403', 'help', 'help/*', 'dashboard', 'driver_portal', 'driver_portal/*', 'my_profile', 'my_profile/*', 'navigation', 'message-center', 'vision/test', 'driver/*', 'master_files/daily_usd_rate/quick_save', 'master_files/daily_usd_rate/today', 'inventory/stock/header_stock_data', 'notification/*', 'tour/print', 'tour/print/*']],
            // 'honeypot',
            // 'csrf',
            'invalidchars',
            'StripTagsFilter' => ['except' => ['tour/intake/comms/*', 'chat/send_message', 'accounts/debtor_invoice_new/complete_payment', 'tour/reserve_hotel/hotel_reservation']],
            'corsFilter',
        ],
        'after' => [
            'toolbar',
            'AccessPolicyFilter', // tf-e8-access-r1: audit of each Accounts change; the current security token in a response header
            'HelpWidgetFilter' => ['except' => ['api/*', 'help', 'help/*', 'login', 'logout', 'driver/*', 'driver_portal', 'driver_portal/*', 'tour/print', 'tour/print/*']], // tf-help-r1
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
