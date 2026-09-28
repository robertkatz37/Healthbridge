<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use Illuminate\Database\Seeder;

/**
 * Seeds one template per system email event wired up to
 * EmailTemplateService so far (the 5 Agency moderation notifications,
 * Phase 8). Each is inactive-by-default-safe: if an admin never touches
 * this screen, RendersFromEmailTemplate falls back to the notification's
 * own hardcoded content, so seeding these rows is not required for the
 * app to function — it only unlocks customization.
 */
class EmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'key' => 'agency_approved',
                'name' => 'Agency Application Approved',
                'subject' => 'Your agency listing is now live — {{agency_name}}',
                'body' => "<p>Congratulations!</p><p>Your agency \"{{agency_name}}\" has been approved and is now published on HealthsBridge.</p><p>Families can now discover your listing and reach out about care.</p><p><a href=\"{{dashboard_url}}\">View Your Dashboard</a></p><p>Thank you for partnering with HealthsBridge.</p>",
                'description' => 'Available variables: {{agency_name}}, {{dashboard_url}}',
            ],
            [
                'key' => 'agency_rejected',
                'name' => 'Agency Application Rejected',
                'subject' => 'Update on your agency application — {{agency_name}}',
                'body' => "<p>Hello,</p><p>After review, your application for \"{{agency_name}}\" was not approved at this time.</p><p>Reason: {{reason}}</p><p>If you believe this was in error or would like to discuss further, please contact our support team.</p>",
                'description' => 'Available variables: {{agency_name}}, {{reason}}',
            ],
            [
                'key' => 'agency_changes_requested',
                'name' => 'Agency Changes Requested',
                'subject' => 'Action needed on your agency application — {{agency_name}}',
                'body' => "<p>Hello,</p><p>Our team reviewed your application for \"{{agency_name}}\" and needs a few changes before it can be approved.</p><p>Requested changes: {{notes}}</p><p><a href=\"{{dashboard_url}}\">Update Your Listing</a></p><p>Once you've made the changes, you can resubmit for review from your dashboard.</p>",
                'description' => 'Available variables: {{agency_name}}, {{notes}}, {{dashboard_url}}',
            ],
            [
                'key' => 'agency_suspended',
                'name' => 'Agency Suspended',
                'subject' => 'Your agency listing has been suspended — {{agency_name}}',
                'body' => "<p>Hello,</p><p>Your agency listing \"{{agency_name}}\" has been suspended and is no longer visible to families.</p><p>Reason: {{reason}}</p><p>Please contact our support team to resolve this and restore your listing.</p>",
                'description' => 'Available variables: {{agency_name}}, {{reason}}',
            ],
            [
                'key' => 'agency_reactivated',
                'name' => 'Agency Reactivated',
                'subject' => 'Your agency listing is active again — {{agency_name}}',
                'body' => "<p>Good news!</p><p>Your agency listing \"{{agency_name}}\" has been reactivated and is now visible to families again.</p><p><a href=\"{{dashboard_url}}\">View Your Dashboard</a></p>",
                'description' => 'Available variables: {{agency_name}}, {{dashboard_url}}',
            ],
            [
                'key' => 'lead_assigned',
                'name' => 'Lead Assigned to Advisor',
                'subject' => 'New lead assigned — {{family_name}}',
                'body' => "<p>Hello,</p><p>A new lead has been assigned to you: <strong>{{family_name}}</strong>.</p><p><a href=\"{{lead_url}}\">View Lead</a></p><p>Please reach out to the family as soon as possible.</p>",
                'description' => 'Available variables: {{family_name}}, {{lead_url}}',
            ],
        ];

        foreach ($templates as $template) {
            EmailTemplate::updateOrCreate(
                ['key' => $template['key']],
                array_merge(['is_active' => true], $template)
            );
        }
    }
}
