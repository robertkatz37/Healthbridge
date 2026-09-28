<?php

namespace Database\Seeders;

use App\Models\CmsPage;
use App\Models\Menu;
use Illuminate\Database\Seeder;

/**
 * Seeds the 3 menus the public layout expects (header, footer, mobile)
 * AND real navigation items linking to the pages CmsPageSeeder creates
 * — an empty menu would leave the public site with no navigation at
 * all, which defeats the point of "the public website is now fully
 * CMS-driven, no hardcoded navigation". Depends on CmsPageSeeder having
 * already run (registered after it in DatabaseSeeder). Idempotent:
 * only adds items to a menu that doesn't already have any, so an
 * admin's own menu edits are never overwritten by a re-seed.
 */
class MenuSeeder extends Seeder
{
    public function run(): void
    {
        $header = Menu::updateOrCreate(['slug' => 'header'], ['name' => 'Header Navigation']);
        $footer = Menu::updateOrCreate(['slug' => 'footer'], ['name' => 'Footer Navigation']);
        Menu::updateOrCreate(['slug' => 'mobile'], ['name' => 'Mobile Navigation']);

        $about = CmsPage::where('slug', 'about')->first();
        $contact = CmsPage::where('slug', 'contact')->first();
        $careers = CmsPage::where('slug', 'careers')->first();
        $privacy = CmsPage::where('slug', 'privacy')->first();
        $terms = CmsPage::where('slug', 'terms')->first();

        if ($header->items()->count() === 0) {
            $headerLinks = [
                ['label' => 'Find Care', 'url' => '/agencies'],
                ['label' => 'Locations', 'url' => '/locations'],
                ['label' => 'Services', 'url' => '/services'],
                ['label' => 'Blog', 'url' => '/blog'],
            ];
            if ($about) {
                $headerLinks[] = ['label' => 'About', 'cms_page_id' => $about->id];
            }
            foreach ($headerLinks as $index => $link) {
                $header->items()->create($link + ['sort_order' => $index]);
            }
        }

        if ($footer->items()->count() === 0) {
            $company = $footer->items()->create(['label' => 'Company', 'url' => '#', 'sort_order' => 0]);
            if ($about) {
                $company->children()->create(['menu_id' => $footer->id, 'label' => 'About Us', 'cms_page_id' => $about->id, 'sort_order' => 0]);
            }
            if ($careers) {
                $company->children()->create(['menu_id' => $footer->id, 'label' => 'Careers', 'cms_page_id' => $careers->id, 'sort_order' => 1]);
            }
            if ($contact) {
                $company->children()->create(['menu_id' => $footer->id, 'label' => 'Contact', 'cms_page_id' => $contact->id, 'sort_order' => 2]);
            }

            $resources = $footer->items()->create(['label' => 'Resources', 'url' => '#', 'sort_order' => 1]);
            $resources->children()->create(['menu_id' => $footer->id, 'label' => 'Find Care', 'url' => '/agencies', 'sort_order' => 0]);
            $resources->children()->create(['menu_id' => $footer->id, 'label' => 'Blog', 'url' => '/blog', 'sort_order' => 1]);
            $resources->children()->create(['menu_id' => $footer->id, 'label' => 'Locations', 'url' => '/locations', 'sort_order' => 2]);

            $legal = $footer->items()->create(['label' => 'Legal', 'url' => '#', 'sort_order' => 2]);
            if ($privacy) {
                $legal->children()->create(['menu_id' => $footer->id, 'label' => 'Privacy Policy', 'cms_page_id' => $privacy->id, 'sort_order' => 0]);
            }
            if ($terms) {
                $legal->children()->create(['menu_id' => $footer->id, 'label' => 'Terms of Service', 'cms_page_id' => $terms->id, 'sort_order' => 1]);
            }
        }
    }
}
