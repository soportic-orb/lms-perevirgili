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

use local_pspvshop\tests\shop_testcase;

/**
 * Tests of the callbacks other plugins and the theme call.
 *
 * @package    local_pspvshop
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversFunction('local_pspvshop_render_navbar_output')]
#[\PHPUnit\Framework\Attributes\CoversFunction('local_pspvshop_theme_pspv_course_card')]
#[\PHPUnit\Framework\Attributes\CoversFunction('local_pspvshop_theme_pspv_catalogue_url')]
final class lib_test extends shop_testcase {
    public function test_theme_cards_get_the_page_and_the_price_of_what_is_on_sale(): void {
        global $CFG;
        require_once($CFG->dirroot . '/local/pspvshop/lib.php');

        set_config('taxrate', '10', 'local_pspvshop');
        set_config('pricesincludetax', 0, 'local_pspvshop');
        $onsale = $this->create_product(100);
        $free = $this->create_product(0);
        $off = $this->create_product(50, ['enabled' => 0]);
        $plain = $this->getDataGenerator()->create_course();

        $card = local_pspvshop_theme_pspv_course_card((int) $onsale->courseid);
        $this->assertStringContainsString('/local/pspvshop/product.php?id=' . $onsale->id, $card['url']);
        $this->assertTrue($card['hasprice']);
        $this->assertSame(money::format(11000), $card['price']);
        $this->assertSame('Free', local_pspvshop_theme_pspv_course_card((int) $free->courseid)['price']);
        $this->assertSame([], local_pspvshop_theme_pspv_course_card((int) $off->courseid));
        $this->assertSame([], local_pspvshop_theme_pspv_course_card((int) $plain->id));

        // The shop has a catalogue page once its category exists.
        $this->setGuestUser();
        set_config('category', 'shop-catalog', 'local_pspvshop');
        $this->assertNull(local_pspvshop_theme_pspv_catalogue_url());
        $this->getDataGenerator()->create_category(['idnumber' => 'shop-catalog']);
        $this->assertStringContainsString('/local/pspvshop/index.php', local_pspvshop_theme_pspv_catalogue_url()->out(false));
    }

    public function test_top_bar_links_to_the_cart_while_it_has_something(): void {
        global $CFG, $PAGE;
        require_once($CFG->dirroot . '/local/pspvshop/lib.php');

        $PAGE->set_url('/');
        $renderer = $PAGE->get_renderer('core');
        $this->setUser($this->getDataGenerator()->create_user());
        $this->assertSame('', local_pspvshop_render_navbar_output($renderer));

        cart::add((int) $this->create_product(10)->id);
        cart::add((int) $this->create_product(20)->id);

        $html = local_pspvshop_render_navbar_output($renderer);
        $this->assertStringContainsString('/local/pspvshop/cart.php', $html);
        $this->assertStringContainsString('>2<', $html);
    }
}
