<?php

namespace App\Libraries\TourFlow;

/**
 * tf-greet-r1 (26 Nov 2026, Shantha): the name of the PERSON at the agent (or the direct customer) who writes to us — the
 * name an e-mail greets ("Dear Kurumi,"), never the agency ("Dear LION TOURS") and never a traveller.
 *
 * Taken, in this order: the Agent contact field on the inquiry (typed by staff, or read from the e-mail by the extractor
 * with the words that prove it); else the sign-off of the e-mail itself ("Thank you. / Kurumi", "Best regards, / Shoji
 * Tanaka / LION TOURS"), else the sender's display name on a pasted "From: Kurumi Fujiwara <…>" line. A name that is not
 * clearly a person's — a company, a department, an address, one of the travellers, or the Avenir colleague the e-mail
 * greets — is not used: the answer is then null and staff type the name. Nothing is guessed.
 */
final class AgentContact
{
    private const CLOSING = '/^(?:thank\s*you(?:\s+(?:very\s+much|so\s+much|in\s+advance))?|thanks(?:\s+(?:and|&)\s+(?:best\s+)?regards)?|many\s+thanks|best\s+regards|kind\s+regards|warm(?:est)?\s+regards|regards|best\s+wishes|best|sincerely|yours\s+sincerely|yours\s+faithfully|yours\s+truly|cheers|br|rgds|with\s+(?:best|kind)\s+regards)\b[\s,.!~、。]*/iu';
    private const NOT_A_NAME = '/\b(?:ltd|limited|co\b|company|corp|corporation|inc|llc|pvt|tours?|travels?|travel|holidays?|agency|department|dept|team|office|division|section|group|sales|reservations?|operations?|japan|tokyo|osaka|tel|fax|phone|mobile|e-?mail|www|http|address|manager|director|assistant|planner|staff|san\b)/iu';

    /**
     * @param string   $text     the e-mail as pasted or received
     * @param string[] $exclude  names that are never the contact (the travellers)
     * @return array{name:string, how:string}|null
     */
    public static function fromEmail(string $text, array $exclude = []): ?array
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        if (trim($text) === '') {
            return null;
        }
        $lines = explode("\n", $text);
        // the newest message only: from the top down to the next quoted header ("From:", "-----Original Message-----", "On … wrote:")
        $start = 0;
        $n = count($lines);
        // skip a pasted header block at the very top
        for ($i = 0; $i < min($n, 12); $i++) {
            if (preg_match('/^\s*(?:from|sent|to|cc|subject|date|差出人|送信日時|宛先|件名)\s*:/iu', $lines[$i])) {
                $start = $i + 1;
            }
        }
        $end = $n;
        for ($i = $start + 1; $i < $n; $i++) {
            if (preg_match('/^\s*(?:from\s*:|差出人\s*:|-{3,}\s*original message|on .{4,80} wrote:)/iu', $lines[$i])) {
                $end = $i;
                break;
            }
        }
        $body = array_slice($lines, $start, $end - $start);
        // the colleague the e-mail greets ("Dear Malindu san,") is ours, not theirs
        $greeted = [];
        foreach ($body as $l) {
            if (preg_match('/^\s*(?:dear|hi|hello)\s+(.+?)[\s,]*$/iu', $l, $m)) {
                $greeted[] = self::norm(preg_replace('/\b(?:san|sama|sir|madam|mr|ms|mrs)\b\.?/iu', '', $m[1]));
            }
        }
        $no = array_merge(array_map([self::class, 'norm'], $exclude), $greeted);
        // 1. the sign-off: the last closing line of the newest message, and the person's name on it or just below
        for ($i = count($body) - 1; $i >= 0; $i--) {
            $l = trim($body[$i]);
            if ($l === '' || !preg_match(self::CLOSING, $l, $m)) {
                continue;
            }
            $rest = trim(mb_substr($l, mb_strlen($m[0])));
            $cands = $rest !== '' ? [$rest] : [];
            for ($j = $i + 1, $k = 0; $j < count($body) && $k < 3; $j++) {
                if (trim($body[$j]) === '') {
                    continue;
                }
                $cands[] = trim($body[$j]);
                $k++;
            }
            foreach (array_slice($cands, 0, 1) as $c) {   // only the line straight after the sign-off: a name lower down is a guess
                $name = self::person($c, $no);
                if ($name !== null) {
                    return ['name' => $name, 'how' => 'from the sign-off of the e-mail ("' . mb_substr($l, 0, 40) . '")'];
                }
            }
            break;   // the last sign-off said no clear name: stop, do not look further up
        }
        // 2. a pasted "From: Kurumi Fujiwara <kurumi@…>" line with a person's display name
        foreach (array_slice($lines, 0, 12) as $l) {
            if (preg_match('/^\s*(?:from|差出人)\s*:\s*"?([^"<]+?)"?\s*<[^>]+@[^>]+>/iu', $l, $m)) {
                $name = self::person($m[1], $no);
                if ($name !== null) {
                    return ['name' => $name, 'how' => 'from the sender\'s name on the e-mail'];
                }
            }
        }
        return null;
    }

    /** A person's name, or null: 1–4 words of letters, no digits, no "@", no company or department word, not excluded. */
    private static function person(string $s, array $no): ?string
    {
        $s = trim((string) preg_split('/\s*[\/|｜]\s*/u', $s)[0]);   // "Shoji Tanaka / LION TOURS" → "Shoji Tanaka"
        $s = trim((string) preg_replace('/^[\-–—~*•]+\s*|[\s,.!]+$/u', '', $s));
        if ($s === '' || mb_strlen($s) > 40 || preg_match('/[0-9@:;()\[\]{}<>]/u', $s) || preg_match(self::NOT_A_NAME, $s)) {
            return null;
        }
        $words = preg_split('/\s+/u', $s) ?: [];
        if (count($words) < 1 || count($words) > 4) {
            return null;
        }
        foreach ($words as $w) {
            if (!preg_match('/^[\p{L}][\p{L}\'’.\-]*$/u', $w)) {
                return null;
            }
        }
        if (preg_match('/^\p{Lu}{2,}(?:\s+\p{Lu}{2,})*$/u', $s) && count($words) > 2) {
            return null;   // a line all in capitals of three words or more reads as a company
        }
        $noWords = [];
        foreach ($no as $x) {
            foreach (preg_split('/\s+/u', $x) ?: [] as $w) {
                if (mb_strlen($w) >= 3) {
                    $noWords[$w] = true;
                }
            }
        }
        foreach ($words as $w) {
            if (isset($noWords[self::norm($w)])) {
                return null;   // a traveller, or the Avenir colleague the e-mail greets
            }
        }
        // the greeting uses the name as signed; one shouted word ("KURUMI") is written as a name
        return preg_match('/^\p{Lu}+$/u', $s) ? mb_convert_case(mb_strtolower($s), MB_CASE_TITLE) : $s;
    }

    private static function norm(string $s): string
    {
        return mb_strtolower(trim((string) preg_replace('/\s+/u', ' ', (string) preg_replace('/\b(?:mr|ms|mrs|miss|mstr|master)\b\.?/iu', '', $s))));
    }
}
