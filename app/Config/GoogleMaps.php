<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Google Maps Platform settings for the quotation's road distances.
 *
 * The key is read from the environment, never from source: set GOOGLE_MAPS_API_KEY in
 * the project's .env file. Same pattern as Config\OpenAI and Config\Anthropic. The
 * distances are asked for from the server (Distance Matrix web service), so the key has
 * to be one that is allowed to be used from a server — a key restricted to HTTP
 * referrers is refused by Google for this, and the panel says so on the leg.
 */
class GoogleMaps extends BaseConfig
{
    public string $apiKey = '';

    public string $distanceUrl = 'https://maps.googleapis.com/maps/api/distancematrix/json';

    /** Region bias for place names, so "Kandy" is Kandy in Sri Lanka and not elsewhere. */
    public string $region = 'lk';

    /** Appended to a place name that does not already say which country it is in. */
    public string $country = 'Sri Lanka';

    public string $language = 'en';

    /** Seconds allowed for one distance lookup. */
    public int $timeout = 15;

    /** How many lookups are sent to Google at once when a whole itinerary is planned. */
    public int $parallel = 6;

    public function __construct()
    {
        parent::__construct();
        $this->apiKey = trim((string) env('GOOGLE_MAPS_API_KEY', ''));
    }
}
