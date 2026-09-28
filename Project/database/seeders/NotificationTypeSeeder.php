<?php

namespace Database\Seeders;

use App\Models\NotificationType;
use Illuminate\Database\Seeder;

class NotificationTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['code' => 'new_lead', 'name' => 'New Lead', 'default_channels' => ['mail', 'database']],
            ['code' => 'tour_confirmed', 'name' => 'Tour Confirmed', 'default_channels' => ['mail', 'database', 'sms']],
            ['code' => 'tour_cancelled', 'name' => 'Tour Cancelled', 'default_channels' => ['mail', 'database']],
            ['code' => 'review_received', 'name' => 'Review Received', 'default_channels' => ['mail', 'database']],
            ['code' => 'review_reply', 'name' => 'Review Reply Posted', 'default_channels' => ['database']],
            ['code' => 'invoice_due', 'name' => 'Invoice Due', 'default_channels' => ['mail', 'database']],
            ['code' => 'invoice_overdue', 'name' => 'Invoice Overdue', 'default_channels' => ['mail', 'database', 'sms']],
            ['code' => 'subscription_renewed', 'name' => 'Subscription Renewed', 'default_channels' => ['mail']],
            ['code' => 'subscription_failed', 'name' => 'Subscription Payment Failed', 'default_channels' => ['mail', 'database', 'sms']],
            ['code' => 'advisor_assigned', 'name' => 'Advisor Assigned', 'default_channels' => ['mail', 'database']],
            ['code' => 'message_received', 'name' => 'New Message', 'default_channels' => ['database', 'broadcast']],
            ['code' => 'document_expiring', 'name' => 'Document Expiring Soon', 'default_channels' => ['mail', 'database']],
        ];

        foreach ($types as $type) {
            NotificationType::updateOrCreate(['code' => $type['code']], $type);
        }
    }
}
