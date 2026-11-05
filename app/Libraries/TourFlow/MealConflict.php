<?php

namespace App\Libraries\TourFlow;

/** A meal the hotel's plan already gives that night: the screen offers the HB switch or "arrange it outside as well". */
final class MealConflict extends \InvalidArgumentException
{
    public bool $canSwitch;

    public function __construct(string $message, bool $canSwitch)
    {
        parent::__construct($message);
        $this->canSwitch = $canSwitch;
    }
}
