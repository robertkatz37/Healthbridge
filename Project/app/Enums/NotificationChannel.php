<?php

namespace App\Enums;

enum NotificationChannel: string
{
    case Mail = 'mail';
    case Database = 'database';
    case Sms = 'sms';
    case Push = 'push';
    case Broadcast = 'broadcast';
}
