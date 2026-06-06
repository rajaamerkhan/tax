<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case Draft = 'draft';
    case Validated = 'validated';
    case Submitted = 'submitted';
    case Failed = 'failed';
    case Editable = 'editable';
    case Locked = 'locked';
    case Cancelled = 'cancelled';

    public function isSubmittedFamily(): bool
    {
        return in_array($this, [self::Submitted, self::Editable, self::Locked], true);
    }
}
