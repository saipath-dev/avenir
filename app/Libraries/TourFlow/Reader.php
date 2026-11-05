<?php

namespace App\Libraries\TourFlow;

/**
 * A service that can be handed an email and a JSON schema and made to answer in
 * that shape. Which one is in use is a setting, not a code change — see
 * EmailExtractor::make().
 *
 * A reader is only responsible for getting a well-shaped answer back. It is never
 * responsible for deciding whether that answer is trustworthy: every value is
 * checked against the email afterwards by EmailExtractor, whichever service
 * produced it.
 */
interface Reader
{
    /** Short key stored in settings: 'anthropic', 'openai'. */
    public function name(): string;

    /** What to call it on screen. */
    public function label(): string;

    /** False when no API key is configured — the panel then offers manual entry. */
    public function available(): bool;

    public function model(): string;

    /**
     * @param  string $system  the instructions
     * @param  string $user    the email
     * @param  array  $schema  JSON Schema the answer must conform to
     * @return array           the decoded answer
     *
     * @throws \RuntimeException with a message fit to show a person
     */
    public function read(string $system, string $user, array $schema): array;
}
