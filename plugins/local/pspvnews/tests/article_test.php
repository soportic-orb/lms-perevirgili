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

namespace local_pspvnews;

/**
 * Tests of the news articles: what is public, how they are listed and what the theme gets.
 *
 * @package    local_pspvnews
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(article::class)]
#[\PHPUnit\Framework\Attributes\CoversFunction('local_pspvnews_theme_pspv_frontpage_news')]
final class article_test extends \advanced_testcase {
    #[\Override]
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();
    }

    /**
     * Creates an article.
     *
     * @param string $title
     * @param array $data
     * @return \stdClass
     */
    protected function article(string $title, array $data = []): \stdClass {
        return article::save((object) ($data + [
            'title' => $title, 'summary' => '', 'body' => '<p>Text of ' . $title . '</p>', 'published' => 1,
            'timepublished' => time() - HOURSECS,
        ]));
    }

    public function test_only_published_articles_whose_date_has_come_are_public(): void {
        $now = time();
        $old = $this->article('Old', ['timepublished' => $now - WEEKSECS]);
        $new = $this->article('New', ['timepublished' => $now - DAYSECS]);
        $draft = $this->article('Draft', ['published' => 0]);
        $future = $this->article('Future', ['timepublished' => $now + DAYSECS]);

        $this->assertTrue(article::is_public($new));
        $this->assertFalse(article::is_public($draft));
        $this->assertFalse(article::is_public($future));
        // Newest first.
        $this->assertSame(['New', 'Old'], array_column(article::published(), 'title'));
        $this->assertSame(2, article::count_published());
        $this->assertSame(['New'], array_column(article::published(1), 'title'));
        $this->assertSame(['Old'], array_column(article::published(1, 1), 'title'));

        // Publishing a draft, and changing an article, keeps its identity.
        $changed = article::save((object) [
            'id' => $draft->id, 'title' => ' Draft no more ', 'body' => '<p>x</p>', 'published' => 1, 'timepublished' => $now,
        ]);
        $this->assertEquals($draft->id, $changed->id);
        $this->assertSame('Draft no more', $changed->title);
        $this->assertSame(3, article::count_published());

        article::delete((int) $old->id);
        $this->assertSame(['Draft no more', 'New'], array_column(article::published(), 'title'));
    }

    public function test_cards_show_the_summary_or_the_start_of_the_text_and_the_cover(): void {
        global $CFG;

        $plain = $this->article('Home <b>care</b> & more', ['body' => '<p>First   paragraph.</p><p>Second &amp; last.</p>']);
        $summarised = $this->article('With summary', ['summary' => ' A short summary. ']);

        $card = article::card($plain);
        $this->assertSame('Home care &amp; more', $card['title']);
        $this->assertSame('First paragraph. Second & last.', $card['summary']);
        $this->assertStringContainsString('/local/pspvnews/view.php?id=' . $plain->id, $card['url']);
        $this->assertFalse($card['hasimage']);
        $this->assertSame(date('Y-m-d', (int) $plain->timepublished), $card['isodate']);
        $this->assertSame('A short summary.', article::card($summarised)['summary']);

        // A cover replaces the previous one and goes with the article when it is deleted.
        $image = $CFG->dirroot . '/lib/tests/fixtures/gd-logo.png';
        article::set_cover_from_path((int) $plain->id, $image, 'first.png');
        article::set_cover_from_path((int) $plain->id, $image, 'second.png');
        $card = article::card($plain);
        $this->assertTrue($card['hasimage']);
        $this->assertStringContainsString('/local_pspvnews/cover/' . $plain->id . '/second.png', $card['image']);
        $fs = get_file_storage();
        $contextid = \core\context\system::instance()->id;
        $this->assertCount(1, $fs->get_area_files($contextid, 'local_pspvnews', article::FILEAREA, $plain->id, 'id', false));
        article::delete((int) $plain->id);
        $this->assertCount(0, $fs->get_area_files($contextid, 'local_pspvnews', article::FILEAREA, $plain->id, 'id', false));
    }

    public function test_the_theme_gets_the_latest_news_for_the_front_page(): void {
        global $CFG;
        require_once($CFG->dirroot . '/local/pspvnews/lib.php');

        $this->assertSame([], local_pspvnews_theme_pspv_frontpage_news(4)['cards']);
        foreach ([5, 4, 3, 2, 1] as $daysago) {
            $this->article("News $daysago", ['timepublished' => time() - $daysago * DAYSECS]);
        }
        $this->article('Draft', ['published' => 0]);

        $news = local_pspvnews_theme_pspv_frontpage_news(4);
        $this->assertStringContainsString('/local/pspvnews/index.php', $news['url']);
        $this->assertSame(['News 1', 'News 2', 'News 3', 'News 4'], array_column($news['cards'], 'title'));
    }
}
