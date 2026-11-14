<?php

namespace App\Libraries\TourFlow;

/** An action only the Super Admin may take: refused for everybody else, whatever the screen shows. */
final class SuperAdminOnly extends \RuntimeException
{
}
