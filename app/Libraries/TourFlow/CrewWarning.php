<?php

namespace App\Libraries\TourFlow;

/**
 * tf-crew-master-r1 (schema 44): the person picked for a crew line carries a warning — an expired driving or guide
 * licence, expired vehicle insurance or revenue licence, a category still to confirm. The engagement stops here so the
 * desk sees it clearly and goes on only by saying so (the same request again with `acknowledge`). Carries the list.
 */
final class CrewWarning extends \RuntimeException
{
    /** @var array<int, array{code:string, level:string, text:string}> */
    public array $warnings;

    public function __construct(string $message, array $warnings)
    {
        parent::__construct($message);
        $this->warnings = $warnings;
    }
}
