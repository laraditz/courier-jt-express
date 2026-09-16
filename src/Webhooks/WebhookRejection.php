<?php

namespace Laraditz\Courier\JtExpress\Webhooks;

/**
 * Why an inbound J&T callback was rejected, and the code J&T expects back.
 *
 * Codes and messages are taken verbatim from the 状态码 table in J&T Malaysia's
 * Tracking Info Callback reference, so this file can be diffed against theirs when
 * the doc changes.
 *
 * Two documented codes are deliberately absent: 145003010 ("API account does not
 * exist") and 145003012 ("API account has no interface permissions") describe state
 * inside J&T's own console. Neither is determinable from an inbound request, and
 * sending one would be a guess presented to carrier support as a finding.
 */
enum WebhookRejection: string
{
    case ApiAccountEmpty = 'api_account_empty';
    case ApiAccountMismatch = 'api_account_mismatch';
    case TimestampEmpty = 'timestamp_empty';
    case TimestampInvalid = 'timestamp_invalid';
    case DigestEmpty = 'digest_empty';
    case DigestMismatch = 'digest_mismatch';

    public function code(): string
    {
        return match ($this) {
            self::ApiAccountEmpty => '145003051',
            self::TimestampEmpty => '145003053',
            self::TimestampInvalid => '145003050',
            self::DigestEmpty => '145003052',
            self::ApiAccountMismatch, self::DigestMismatch => '145003030',
        };
    }

    public function message(): string
    {
        return match ($this) {
            self::ApiAccountEmpty => 'apiAccount is empty!',
            self::TimestampEmpty => 'timestamp is empty!',
            self::TimestampInvalid => 'Illegal parameters',
            self::DigestEmpty => 'digest is empty!',
            self::ApiAccountMismatch, self::DigestMismatch => 'headers signature verification failed',
        };
    }
}
