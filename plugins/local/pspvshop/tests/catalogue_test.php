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

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/cohort/lib.php');

use local_pspvshop\tests\shop_testcase;

/**
 * Tests of the public catalogue.
 *
 * @package    local_pspvshop
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(catalogue::class)]
final class catalogue_test extends shop_testcase {
    /**
     * Names of the courses a search returns.
     *
     * @param array $filters
     * @return string[]
     */
    protected function names(array $filters = []): array {
        return array_column(catalogue::search($filters, 0, 0)['cards'], 'name');
    }

    public function test_courses_with_accreditation_credits_show_them_and_can_be_filtered(): void {
        global $DB;

        // The field is the one local_pspvaccredit creates; here the test makes its own if it is missing.
        if (!catalogue::credits_field()) {
            $generator = $this->getDataGenerator()->get_plugin_generator('core_customfield');
            $category = $generator->create_category(['component' => 'core_course', 'area' => 'course']);
            $generator->create_field([
                'categoryid' => $category->get('id'), 'type' => 'number', 'shortname' => catalogue::CREDITS_FIELD,
                'configdata' => ['decimalplaces' => 1],
            ]);
        }
        $this->assertGreaterThan(0, catalogue::credits_field());
        $this->setGuestUser();
        $this->create_product(40, [], ['fullname' => 'Accredited', 'customfield_' . catalogue::CREDITS_FIELD => 2.5]);
        $this->create_product(40, [], ['fullname' => 'Plain']);

        $this->assertEqualsCanonicalizing(['Accredited', 'Plain'], $this->names());
        $this->assertSame(['Accredited'], $this->names(['credits' => 1]));
        $cards = array_column(catalogue::search([], 0, 0)['cards'], null, 'name');
        $this->assertTrue($cards['Accredited']['hascredits']);
        $this->assertStringContainsString(format_float(2.5, 1), $cards['Accredited']['credits']);
        $this->assertFalse($cards['Plain']['hascredits']);

        // Without the field there is nothing to filter by.
        $DB->delete_records('customfield_field', ['id' => catalogue::credits_field()]);
        $this->assertSame(0, catalogue::credits_field());
        $this->assertEqualsCanonicalizing(['Accredited', 'Plain'], $this->names(['credits' => 1]));
    }

    public function test_internal_training_is_listed_as_free_courses_for_who_can_see_it(): void {
        $generator = $this->getDataGenerator();
        $internal = $generator->create_category(['name' => 'Internal', 'idnumber' => 'shop-internal']);
        $sub = $generator->create_category(['name' => 'Nursing', 'parent' => $internal->id]);
        set_config('internalcategory', 'shop-internal', 'local_pspvshop');
        $now = time();
        $hygiene = $generator->create_course([
            'fullname' => 'Hand hygiene', 'category' => $internal->id, 'startdate' => $now + WEEKSECS, 'summary' => 'Mandatory',
        ]);
        $generator->create_course(['fullname' => 'Wound care basics', 'category' => $sub->id, 'startdate' => $now - WEEKSECS]);
        $generator->create_course(['fullname' => 'Hidden internal', 'category' => $internal->id, 'visible' => 0]);
        $this->create_product(40, ['mode' => 'online'], ['fullname' => 'Paid course', 'startdate' => $now + DAYSECS]);
        // An internal course that is also on sale is listed once, as a product.
        $this->create_product(15, [], ['fullname' => 'Internal on sale', 'category' => $internal->id]);
        $this->setAdminUser();

        $this->assertEqualsCanonicalizing(
            ['Hand hygiene', 'Wound care basics', 'Paid course', 'Internal on sale'],
            $this->names(),
        );
        // Not started yet first, soonest first, whatever they are.
        $this->assertSame(['Paid course', 'Hand hygiene'], array_slice($this->names(), 0, 2));

        $cards = array_column(catalogue::search([], 0, 0)['cards'], null, 'name');
        $card = $cards['Hand hygiene'];
        $this->assertTrue($card['internal']);
        $this->assertTrue($card['isfree']);
        $this->assertFalse($card['haslimit']);
        $this->assertSame('Internal', $card['category']);
        $this->assertStringContainsString('/course/view.php?id=' . $hygiene->id, $card['url']);
        $this->assertFalse($cards['Paid course']['internal']);
        $this->assertFalse($cards['Internal on sale']['internal']);

        // The internal categories can be chosen in the list of areas.
        $areas = catalogue::areas();
        $this->assertSame('Internal', $areas[$internal->id]);
        $this->assertSame('Nursing', $areas[$sub->id]);
        $this->assertSame(['Wound care basics'], $this->names(['area' => $sub->id]));
        $this->assertEqualsCanonicalizing(['Hand hygiene', 'Internal on sale'], $this->names(['area' => $internal->id]));

        // The filters apply to them too; a mode or a paid price range leaves them out.
        $this->assertSame(['Hand hygiene'], $this->names(['q' => 'mandatory']));
        $this->assertEqualsCanonicalizing(['Paid course', 'Hand hygiene'], $this->names(['start' => 'upcoming']));
        $this->assertSame(['Paid course'], $this->names(['mode' => 'online']));
        $this->assertNotContains('Hand hygiene', $this->names(['price' => '50to150']));
        $this->assertContains('Hand hygiene', $this->names(['price' => 'upto50']));

        // Who cannot see the internal category does not see its courses or its areas.
        $guestroles = get_archetype_roles('guest');
        $context = \core\context\coursecat::instance($internal->id);
        assign_capability('moodle/category:viewcourselist', CAP_PREVENT, reset($guestroles)->id, $context->id, true);
        $this->setGuestUser();
        $this->assertSame(['Paid course'], $this->names());
        $this->assertArrayNotHasKey($internal->id, catalogue::areas());

        // It can be switched off.
        $this->setAdminUser();
        set_config('internalcategory', '', 'local_pspvshop');
        $this->assertEqualsCanonicalizing(['Paid course', 'Internal on sale'], $this->names());
    }

    public function test_search_lists_what_is_on_sale_and_filters_it(): void {
        $generator = $this->getDataGenerator();
        $nursing = $generator->create_category(['name' => 'Nursing']);
        $geriatrics = $generator->create_category(['name' => 'Geriatrics']);
        $now = time();
        $this->create_product(40, ['mode' => 'online'], [
            'fullname' => 'Wound care', 'category' => $nursing->id, 'startdate' => $now - WEEKSECS,
            'summary' => 'Pressure injuries',
        ]);
        $this->create_product(120, ['mode' => 'blended'], [
            'fullname' => 'Geriatric assessment', 'category' => $geriatrics->id, 'startdate' => $now + WEEKSECS,
        ]);
        $this->create_product(200, ['mode' => 'onsite'], [
            'fullname' => 'Falls', 'category' => $geriatrics->id, 'startdate' => $now + 2 * WEEKSECS,
        ]);
        $this->create_product(10, ['enabled' => 0], ['fullname' => 'Not on sale']);
        $this->create_product(10, [], ['fullname' => 'Hidden course', 'visible' => 0]);
        $this->create_product(10, ['saleend' => $now - DAYSECS], ['fullname' => 'Sale closed']);
        $generator->create_course(['fullname' => 'No product']);
        $this->setGuestUser();

        // Courses that have not started come first, soonest first.
        $this->assertSame(['Geriatric assessment', 'Falls', 'Wound care'], $this->names());
        $this->assertSame(['Geriatric assessment', 'Falls'], $this->names(['area' => $geriatrics->id]));
        $this->assertSame(['Wound care'], $this->names(['mode' => 'online']));
        $this->assertSame(['Wound care'], $this->names(['price' => 'upto50']));
        $this->assertSame(['Geriatric assessment'], $this->names(['price' => '50to150']));
        $this->assertSame(['Falls'], $this->names(['price' => 'over150']));
        $this->assertSame(['Geriatric assessment', 'Falls'], $this->names(['start' => 'upcoming']));
        $this->assertSame(['Wound care'], $this->names(['start' => 'open']));
        $this->assertSame(['Wound care'], $this->names(['q' => 'pressure']));
        $this->assertSame(['Falls'], $this->names(['q' => 'FALL']));
        $this->assertSame([], $this->names(['q' => 'nothing like this']));
        // Unknown filter values are ignored, not trusted.
        $this->assertCount(3, $this->names(['mode' => "x' OR 1=1", 'price' => 'cheap', 'start' => 'whenever']));

        $this->assertEqualsCanonicalizing(['Nursing', 'Geriatrics'], array_values(catalogue::areas()));

        $page = catalogue::search([], 1, 2);
        $this->assertSame(3, $page['total']);
        $this->assertSame(['Wound care'], array_column($page['cards'], 'name'));
    }

    public function test_cards_show_the_price_of_whoever_looks(): void {
        set_config('taxrate', '21', 'local_pspvshop');
        set_config('pricesincludetax', 0, 'local_pspvshop');
        $cohort = $this->getDataGenerator()->create_cohort();
        $this->create_product(100, ['seats' => 5, 'mode' => 'online'], ['fullname' => 'Wound care'], [$cohort->id => 80]);
        $member = $this->getDataGenerator()->create_user();
        cohort_add_member($cohort->id, $member->id);

        $this->setGuestUser();
        $card = catalogue::search([], 0, 0)['cards'][0];
        // Tax is added to the price, and that is what the card shows.
        $this->assertSame("121.00\u{00A0}€", $card['price']);
        $this->assertFalse($card['hasreduction']);
        $this->assertSame('Online', $card['mode']);
        $this->assertTrue($card['haslimit']);
        $this->assertSame(5, $card['seats']);
        $this->assertStringContainsString('/local/pspvshop/product.php?id=', $card['url']);

        $this->setUser($member);
        $card = catalogue::search([], 0, 0)['cards'][0];
        $this->assertSame("96.80\u{00A0}€", $card['price']);
        $this->assertTrue($card['hasreduction']);
        $this->assertSame("121.00\u{00A0}€", $card['generalprice']);
    }
}
