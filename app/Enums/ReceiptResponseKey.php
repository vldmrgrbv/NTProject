<?php

declare(strict_types=1);

namespace App\Enums;

enum ReceiptResponseKey: string
{
    case REGISTRATION = 'registration';
    case REGISTRATION_FAILED = 'registration_failed';
    case MATCHING_SENT = 'matching_sent';
    case MATCHING_RESULT = 'matching_result';
    case FNS = 'fns';
    case RECOGNITION = 'recognition';
    case UPDATE_STATUS_CHECK = 'update_status_check';
    case INTEGRATION_CHECK = 'integration_check';
}
