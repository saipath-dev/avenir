<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * tf-mailfix-r1 (10 Oct 2026) — the company's outgoing mail server.
 *
 * Avenir's mailboxes (…@avenirholidays.com) are hosted at Lolipop (the domain's MX is mx01.lolipop.jp).
 * The old server name mail.avenirholidays.com no longer exists, so every company mailbox sends through the
 * server below, whatever host its user account still carries. This is the setting the hotel-mail test of
 * 8 Oct 2026 sent through successfully (smtp.lolipop.jp, port 465, SSL, login = the full address).
 * Mailboxes of other domains keep the host on their user account.
 *
 * Nothing secret is kept here: each mailbox's password stays on its user account.
 * Each value can be overridden in .env, for example:  companyMail.smtpHost = smtp.lolipop.jp
 */
class CompanyMail extends BaseConfig
{
    /** Mailboxes of this domain (and its sub-domains) use the company server. Empty = off. */
    public string $domain = 'avenirholidays.com';

    public string $smtpHost = 'smtp.lolipop.jp';

    public int $smtpPort = 465;

    /** 'ssl' (port 465) or 'tls' (STARTTLS, port 587). */
    public string $smtpCrypto = 'ssl';
}
