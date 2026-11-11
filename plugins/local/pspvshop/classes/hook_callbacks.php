<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace local_pspvshop;

/**
 * Hook callbacks: the metadata search engines and social networks read on a product page.
 *
 * @package    local_pspvshop
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {
    /** @var string[] HTML to add to the head of the current page. */
    protected static array $head = [];

    /**
     * Sets the metadata of the product page being shown.
     *
     * @param array $card A product as returned by catalogue::card().
     * @param \stdClass $product
     */
    public static function describe_product(array $card, \stdClass $product): void {
        global $SITE;

        $description = $card['summary'];
        $meta = [
            ['name', 'description', $description],
            ['property', 'og:type', 'product'],
            ['property', 'og:title', $card['name']],
            ['property', 'og:description', $description],
            ['property', 'og:url', $card['url']],
            ['property', 'og:site_name', format_string($SITE->fullname)],
        ];
        if ($card['hasimage']) {
            $meta[] = ['property', 'og:image', $card['image']];
        }
        self::$head = [];
        foreach ($meta as [$attribute, $name, $content]) {
            self::$head[] = \html_writer::empty_tag('meta', [$attribute => $name, 'content' => $content]);
        }
        self::$head[] = \html_writer::empty_tag('link', ['rel' => 'canonical', 'href' => $card['url']]);

        [, , $topay] = money::split(money::cents($product->price), product::tax_rate($product));
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Course',
            'name' => $card['name'],
            'description' => $description,
            'url' => $card['url'],
            'provider' => ['@type' => 'Organization', 'name' => format_string($SITE->fullname)],
            'offers' => [
                '@type' => 'Offer',
                'price' => money::decimal($topay),
                'priceCurrency' => money::currency(),
                'category' => $topay === 0 ? 'Free' : 'Paid',
                'url' => $card['url'],
            ],
        ];
        if ($card['hasimage']) {
            $schema['image'] = $card['image'];
        }
        $json = json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP);
        self::$head[] = \html_writer::tag('script', $json, ['type' => 'application/ld+json']);
    }

    /**
     * Adds the metadata to the head of the page.
     *
     * @param \core\hook\output\before_standard_head_html_generation $hook
     */
    public static function before_standard_head_html_generation(
        \core\hook\output\before_standard_head_html_generation $hook,
    ): void {
        foreach (self::$head as $html) {
            $hook->add_html($html . "\n");
        }
    }
}
