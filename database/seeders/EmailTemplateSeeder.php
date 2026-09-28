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
            [
                'key' => 'payment_received',
                'name' => 'Payment Received',
                'subject' => 'Payment received — Invoice {{invoice_number}}',
                'body' => "<p>Hello,</p><p>We've received your payment of \${{amount}} for invoice {{invoice_number}}.</p><p>Thank you for your business.</p>",
                'description' => 'Available variables: {{invoice_number}}, {{amount}}',
            ],
            [
                'key' => 'payment_failed',
                'name' => 'Payment Failed',
                'subject' => 'Payment failed — Invoice {{invoice_number}}',
                'body' => "<p>Hello,</p><p>We were unable to process your payment for invoice {{invoice_number}}.</p><p>Reason: {{reason}}</p><p>Please update your payment details to avoid interruption to your service.</p>",
                'description' => 'Available variables: {{invoice_number}}, {{reason}}',
            ],
            [
                'key' => 'subscription_renewed',
                'name' => 'Subscription Renewed',
                'subject' => 'Your subscription has renewed',
                'body' => "<p>Hello,</p><p>Your HealthsBridge subscription has renewed successfully — \${{amount}} was charged for invoice {{invoice_number}}.</p>",
                'description' => 'Available variables: {{invoice_number}}, {{amount}}',
            ],
            [
                'key' => 'subscription_expiring',
                'name' => 'Subscription Expiring',
                'subject' => 'Your subscription is ending soon',
                'body' => "<p>Hello,</p><p>Your {{plan_name}} subscription is scheduled to end on {{ends_at}}.</p><p>If you'd like to keep your current plan, you can resume anytime before it ends.</p>",
                'description' => 'Available variables: {{plan_name}}, {{ends_at}}',
            ],
            [
                'key' => 'trial_ending',
                'name' => 'Trial Ending',
                'subject' => 'Your free trial ends soon',
                'body' => "<p>Hello,</p><p>Your trial of the {{plan_name}} plan ends on {{trial_ends_at}}.</p><p>Your card on file will be charged automatically once the trial ends, unless you cancel first.</p>",
                'description' => 'Available variables: {{plan_name}}, {{trial_ends_at}}',
            ],
            [
                'key' => 'invoice_generated',
                'name' => 'Invoice Generated',
                'subject' => 'New invoice — {{invoice_number}}',
                'body' => "<p>Hello,</p><p>A new invoice for \${{amount}} has been generated, due {{due_date}}.</p>",
                'description' => 'Available variables: {{invoice_number}}, {{amount}}, {{due_date}}',
            ],
            [
                'key' => 'refund_processed',
                'name' => 'Refund Processed',
                'subject' => 'Your refund has been processed',
                'body' => "<p>Hello,</p><p>A refund of \${{amount}} for invoice {{invoice_number}} has been processed.</p><p>It may take 5-10 business days to appear on your statement.</p>",
                'description' => 'Available variables: {{invoice_number}}, {{amount}}',
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
