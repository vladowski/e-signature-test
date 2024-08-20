<?php

namespace App\Services;

enum SignatureRequestStatus: string
{
    case Pending = 'pending';
    case PendingSigning = 'pending_signing';
    case Signed = 'signed';
    case Denied = 'denied';
    case Deleted = 'deleted';
    case Error = 'error';
}
