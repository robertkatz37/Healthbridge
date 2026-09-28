<?php

namespace Database\Seeders;

use App\Models\CmsPage;
use App\Models\SeoMeta;
use Illuminate\Database\Seeder;

/**
 * Seeds the 6 default pages the phase specifically named (Home, About,
 * Contact, Privacy, Terms, Careers) with real, substantive content —
 * not placeholders. Without this, the public site would 404 on every
 * one of them despite the CMS infrastructure existing to create them,
 * since an admin has to actually author content before it can render.
 * Idempotent (updateOrCreate keyed on slug) so re-running the seeder
 * never duplicates pages or clobbers content an admin has since edited
 * — it only fills in a page if one with that slug doesn't exist yet.
 */
class CmsPageSeeder extends Seeder
{
    public function run(): void
    {
        $this->homePage();
        $this->aboutPage();
        $this->contactPage();
        $this->privacyPage();
        $this->termsPage();
        $this->careersPage();
    }

    private function homePage(): void
    {
        if (CmsPage::where('page_type', 'home')->exists()) {
            return;
        }

        $page = CmsPage::create([
            'slug' => 'home',
            'title' => 'Home',
            'body' => '',
            'page_type' => 'home',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $page->sections()->createMany([
            [
                'type' => 'hero',
                'content' => [
                    'heading' => 'Find Trusted Senior Care, Guided by Real People',
                    'subheading' => 'HealthsBridge connects families with verified senior care agencies through intelligent matching and a dedicated human advisor — at every step, not just the first click.',
                    'button_text' => 'Browse Agencies',
                    'button_url' => '/agencies',
                ],
                'sort_order' => 0,
            ],
            [
                'type' => 'features',
                'content' => [
                    'heading' => 'Why Families Choose HealthsBridge',
                    'items' => [
                        ['icon' => 'bi-search-heart', 'title' => 'Smart Matching', 'description' => 'Our matching engine weighs care needs, location, and budget to surface agencies that actually fit — not just the highest bidder.'],
                        ['icon' => 'bi-person-badge', 'title' => 'A Human Advisor', 'description' => 'Every family is paired with a dedicated advisor who coordinates tours, answers questions, and stays with you through move-in.'],
                        ['icon' => 'bi-shield-check', 'title' => 'Verified Reviews', 'description' => 'Every review comes from a family who completed a real move-in through our platform — no anonymous ratings, no pay-to-play.'],
                    ],
                ],
                'sort_order' => 1,
            ],
            [
                'type' => 'cta',
                'content' => [
                    'heading' => 'Ready to find the right care?',
                    'subheading' => 'Tell us what you need and we will match you with agencies and an advisor within one business day.',
                    'button_text' => 'Get Started',
                    'button_url' => '/register',
                ],
                'sort_order' => 2,
            ],
        ]);

        $page->seoMeta()->create([
            'meta_title' => 'HealthsBridge — Find Trusted Senior Care Near You',
            'meta_description' => 'Connect with verified senior care agencies through intelligent matching and a dedicated human advisor. Browse assisted living, memory care, home care, and more.',
        ]);
    }

    private function aboutPage(): void
    {
        $page = CmsPage::firstOrCreate(
            ['slug' => 'about'],
            [
                'title' => 'About HealthsBridge',
                'body' => $this->aboutBody(),
                'page_type' => 'about',
                'status' => 'published',
                'published_at' => now(),
            ]
        );

        $this->seedMeta($page, 'About HealthsBridge', 'Learn how HealthsBridge connects families with verified senior care agencies through intelligent matching and dedicated human advisors.');
    }

    private function aboutBody(): string
    {
        return <<<'HTML'
<p>HealthsBridge exists because finding the right senior care shouldn't mean sorting through dozens of directory listings with no way to tell which agencies are actually trustworthy, or which ones can even take on a new resident.</p>
<p>We built a platform that pairs intelligent matching — weighing care needs, location, budget, and availability — with a dedicated human advisor who stays with your family from that first search through move-in day and beyond. Every review on our platform comes from a family who completed a real move-in through HealthsBridge, verified against an actual referral, not an anonymous rating anyone could leave.</p>
<p>Our agency partners go through an application and verification process before they're listed, and our advisors are trained to understand the practical realities of memory care, assisted living, home care, and hospice — not just to read a script off a form.</p>
<p>We're a small team that believes this decision deserves better tools than a generic search engine, and better support than a call center.</p>
HTML;
    }

    private function contactPage(): void
    {
        $page = CmsPage::firstOrCreate(
            ['slug' => 'contact'],
            [
                'title' => 'Contact Us',
                'body' => $this->contactBody(),
                'page_type' => 'contact',
                'status' => 'published',
                'published_at' => now(),
            ]
        );

        $this->seedMeta($page, 'Contact HealthsBridge', 'Get in touch with the HealthsBridge team for questions about senior care matching, agency partnerships, or platform support.');
    }

    private function contactBody(): string
    {
        return <<<'HTML'
<p>We're glad to hear from you — whether you're a family looking for care, an agency interested in partnering with us, or you just have a question about how HealthsBridge works.</p>
<h2>Families</h2>
<p>If you're already working with an advisor, the fastest way to reach them is through your dashboard's message thread. If you haven't started yet, <a href="/register">create a free account</a> and you'll be matched with an advisor within one business day.</p>
<h2>Agencies</h2>
<p>Interested in joining our verified agency network? Visit our <a href="/agencies">agency directory</a> to see what a listing looks like, then reach out through your prospective advisor contact to start the application process.</p>
<h2>General Support</h2>
<p>For anything else — technical issues, billing questions, or general feedback — email <strong>support@healthsbridge.test</strong> and we'll get back to you within one business day.</p>
HTML;
    }

    private function privacyPage(): void
    {
        $page = CmsPage::firstOrCreate(
            ['slug' => 'privacy'],
            [
                'title' => 'Privacy Policy',
                'body' => $this->privacyBody(),
                'page_type' => 'privacy',
                'status' => 'published',
                'published_at' => now(),
            ]
        );

        $this->seedMeta($page, 'Privacy Policy', 'How HealthsBridge collects, uses, and protects your personal information.', 'noindex,follow');
    }

    private function privacyBody(): string
    {
        return <<<'HTML'
<p><em>Last updated: this page is maintained by HealthsBridge and should be reviewed by legal counsel before production use.</em></p>
<h2>Information We Collect</h2>
<p>When you create an account, request a match, or submit a review, we collect the information you provide directly — your name, contact details, care needs, and any messages exchanged with your advisor or an agency. We also collect standard technical information (IP address, browser type, pages visited) to keep the platform secure and improve how it works.</p>
<h2>How We Use Your Information</h2>
<p>We use your information to match you with relevant agencies and advisors, to facilitate communication between families, advisors, and agencies, to send account and referral-related notifications, and to improve our matching and moderation systems. We do not sell your personal information to third parties.</p>
<h2>Who We Share It With</h2>
<p>Your care needs and contact information are shared only with the advisor assigned to you and the specific agencies you choose to be referred to — never broadcast to our full agency network. Agency staff can only see referrals actually sent to them.</p>
<h2>Your Rights</h2>
<p>You can review, correct, or request deletion of your personal information at any time by contacting <strong>privacy@healthsbridge.test</strong>. Deleting your account removes your profile and message history; referral records tied to a completed move-in may be retained for legal and quality purposes.</p>
<h2>Contact</h2>
<p>Questions about this policy can be directed to <strong>privacy@healthsbridge.test</strong>.</p>
HTML;
    }

    private function termsPage(): void
    {
        $page = CmsPage::firstOrCreate(
            ['slug' => 'terms'],
            [
                'title' => 'Terms of Service',
                'body' => $this->termsBody(),
                'page_type' => 'terms',
                'status' => 'published',
                'published_at' => now(),
            ]
        );

        $this->seedMeta($page, 'Terms of Service', 'The terms governing use of the HealthsBridge platform for families, advisors, and agency partners.', 'noindex,follow');
    }

    private function termsBody(): string
    {
        return <<<'HTML'
<p><em>Last updated: this page is maintained by HealthsBridge and should be reviewed by legal counsel before production use.</em></p>
<h2>Using HealthsBridge</h2>
<p>HealthsBridge is a marketplace connecting families seeking senior care with independently operated care agencies, supported by human advisors. We facilitate introductions and referrals; we do not provide care services directly, and we are not a party to any care agreement a family enters into with an agency.</p>
<h2>Accounts</h2>
<p>You're responsible for the accuracy of the information you provide and for keeping your account credentials secure. Family accounts, advisor accounts, and agency accounts each carry different permissions on the platform, described in your dashboard.</p>
<h2>Reviews</h2>
<p>Reviews may only be submitted by families whose referral reached a completed move-in through the platform. Reviews must reflect a genuine experience; we reserve the right to remove reviews that violate this or our community guidelines, through the moderation process described in your account settings.</p>
<h2>Agency Listings</h2>
<p>Agencies are responsible for the accuracy of their own listing information, licensing, and services offered. Verification badges reflect information submitted during onboarding and do not constitute a guarantee of quality of care.</p>
<h2>Limitation of Liability</h2>
<p>HealthsBridge provides a matching and communication platform. Decisions about care arrangements remain the responsibility of the family and the chosen agency. To the fullest extent permitted by law, HealthsBridge is not liable for the quality of care provided by any listed agency.</p>
<h2>Changes to These Terms</h2>
<p>We may update these terms from time to time; continued use of the platform after a change constitutes acceptance of the updated terms.</p>
HTML;
    }

    private function careersPage(): void
    {
        $page = CmsPage::firstOrCreate(
            ['slug' => 'careers'],
            [
                'title' => 'Careers at HealthsBridge',
                'body' => $this->careersBody(),
                'page_type' => 'careers',
                'status' => 'published',
                'published_at' => now(),
            ]
        );

        $this->seedMeta($page, 'Careers at HealthsBridge', 'Join the HealthsBridge team — open roles for advisors and platform staff who care about doing right by families navigating senior care.');
    }

    private function careersBody(): string
    {
        return <<<'HTML'
<p>We're a small team building tools we'd want our own families to use when navigating senior care — thoughtful matching, honest reviews, and advisors who genuinely follow through.</p>
<h2>What We Look For</h2>
<p>Whether you're joining as an advisor working directly with families, or on the engineering and operations side, we look for people who take the weight of this decision seriously — families are usually making this choice at a difficult time, and the work should reflect that.</p>
<h2>Open Roles</h2>
<p>We don't have specific openings listed on this page right now, but we're always glad to hear from people with senior-care advisory experience, healthcare operations backgrounds, or relevant engineering skills. Reach out through our <a href="/contact">Contact page</a> with a short note about what you're looking for, and we'll follow up if there's a fit.</p>
HTML;
    }

    private function seedMeta(CmsPage $page, string $title, string $description, string $robots = 'index,follow'): void
    {
        SeoMeta::updateOrCreate(
            ['seo_metable_type' => CmsPage::class, 'seo_metable_id' => $page->id],
            ['meta_title' => $title, 'meta_description' => $description, 'robots' => $robots]
        );
    }
}
