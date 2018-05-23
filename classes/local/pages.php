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

/**
 * Page utilities for simplelesson
 *
 * @package    mod_simplelesson
 * @copyright  Richard Jones https://richardnz.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_simplelesson\local;

defined('MOODLE_INTERNAL') || die();
/**
 * Utility class for counting pages and so on
 *
 * @package    mod_simplelesson
 * @copyright  2018 Richard Jones https://richardnz.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class pages  {

    /**
     * Count the number of pages in a simplelesson mod
     *
     * @param int $simplelessonid the id of a simplelesson
     * @return int the number of pages in the database that lesson has
     */
    public static function count_pages($simplelessonid) {
        global $DB;

        return $DB->count_records('simplelesson_pages',
                array('simplelessonid' => $simplelessonid));
    }
    /**
     * Get the page titles for the prev/next drop downs
     * keys are the page values, text is the page title
     *
     * @param int $simplelessonid the id of a simplelesson
     * @return array of pageid=>titles of pages in the simplelesson
     */
    public static function fetch_page_titles($simplelessonid) {
        $pagetitles = array();
        $pagecount = self::count_pages($simplelessonid);
        if ($pagecount != 0) {
            for ($p = 1; $p <= $pagecount; $p++) {
                $pid = self::get_page_id_from_sequence($simplelessonid, $p);
                $data = self::get_page_record($pid);
                $pagetitles[$pid] = $data->pagetitle;
            }
        }
        // Add a "none" link.
        $pagetitles[0] = get_string('nolink', 'mod_simplelesson');

        return $pagetitles;
    }
    /**
     * Get the page links for the simplelesson index
     *
     * @param int $course id
     * @param int $simplelessonid the id of a simplelesson
     * @param int $pageid, page index is on
     * @return array of links to pages in the simplelesson
     */
    public static function fetch_page_links($courseid, $simplelessonid,
            $pageid) {
        global $CFG;
        require_once($CFG->libdir . '/weblib.php');
        require_once($CFG->libdir . '/outputcomponents.php');
        $pagelinks = array();

        // Count the content pages and make the links.
        $pagecount = self::count_pages($simplelessonid);
        if ($pagecount != 0) {
            for ($p = 1; $p <= $pagecount; $p++) {
                $pid = self::get_page_id_from_sequence(
                        $simplelessonid, $p);
                $data = self::get_page_record($pid);
                $pageurl = new
                        \moodle_url('/mod/simplelesson/showpage.php',
                        array('courseid' => $courseid,
                        'simplelessonid' => $data->simplelessonid,
                        'pageid' => $pid));
                // No html link to self.
                if ($pid == $pageid) {
                    $link = $data->pagetitle;
                } else {
                    $link = \html_writer::link($pageurl,
                            $data->pagetitle);
                }
                $pagelinks[] = $link;
            }
        }
        return $pagelinks;
    }
    /**
     * Add a page record to the pages table.
     *
     * @param $data object - the data to add
     * @param $context object - our module context
     * @return $id - the id of the inserted record
     */
    public static function add_page_record($data, $context) {
        global $DB;

        $pagecontentsoptions = simplelesson_get_editor_options($context);

        // Insert a dummy record and get the id.
        $data->timecreated = time();
        $data->timemodified = time();
        $data->pagecontents = ' ';
        $data->pagecontentsformat = FORMAT_HTML;
        $dataid = $DB->insert_record('simplelesson_pages', $data);

        $data->id = $dataid;

        // Massage the data into a form for saving.
        $data = file_postupdate_standard_editor(
                $data,
                'pagecontents',
                $pagecontentsoptions,
                $context,
                'mod_simplelesson',
                'pagecontents',
                $data->id);
        // Update the record with full editor data.
        $DB->update_record('simplelesson_pages', $data);

        return $data->id;
    }

    /**
     * Given a page id return the data for that page record
     *
     * @param int $pageid the page id
     * @return object representing the record
     */
    public static function get_page_record($pageid) {
        global $DB;
        return $DB->get_record('simplelesson_pages',
                array('id' => $pageid), '*', MUST_EXIST);
    }

    /**
     * Given a simplelesson id and sequence number, find that page record
     *
     * @param int $simplelessonid the instance id
     * @param int $sequence, where the page is in the lesson sequence
     * @return int pageid, the id of the page in the pages table
     */

    public static function get_page_id_from_sequence($simplelessonid,
            $sequence) {
        global $DB;
        $data = $DB->get_record('simplelesson_pages',
                array('simplelessonid' => $simplelessonid,
                'sequence' => $sequence));
        return $data->id;
    }
    /**
     * Given a pageid return its sequence number
     *
     * @param int $pageid the
     * @return int $sequence, where the page is in the lesson sequence
     */

    public static function get_page_sequence_from_id($pageid) {
        global $DB;

        return $DB->get_field('simplelesson_pages',
                'sequence',  array('id' => $pageid));
    }
    /**
     * Given a simplelesson page id return its title
     *
     * @param int $pageid, where the page is in the lesson sequence
     * @return string page title
     */
    public static function get_page_title($pageid) {
        global $DB;

        return $DB->get_field('simplelesson_pages',
                'pagetitle',  array('id' => $pageid));
    }

    /**
     * Check if this is the last page of the instance
     *
     * @param object $data the simplelesson object
     * @return boolean true if this is the last page
     */
    public static function is_last_page($data) {
        return ($data->sequence == self::count_pages($data->simplelessonid));
    }
    /**
     * Given a simplelesson and sequence number
     * Move the page by exchanging sequence numbers
     *
     * If there is a question slot, move that too.
     *
     * Note: User is not presented with the choice to move
     * pages at the top and bottom of the sequence.
     *
     * @param int $simplelessonid the simplelesson instance
     * @param int $sequence the page sequence number
     * @return none
     */
    public static function move_page_up($simplelessonid, $sequence) {
        global $DB;

        $pageidup = self::get_page_id_from_sequence(
                $simplelessonid, $sequence);
        $pageiddown = self::get_page_id_from_sequence(
                $simplelessonid, ($sequence - 1));

        self::decrement_page_sequence($pageidup);
        self::increment_page_sequence($pageiddown);
        self::exchange_question_slots($simplelessonid,
                $pageiddown, $pageidup);
    }

    /**
     * Given a simplelesson and sequence number
     * Move the page by exchanging sequence numbers
     *
     * If there is a question slot, move that too.
     *
     * @param int $simplelessonid the simplelesson instance
     * @param int $sequence the page sequence number
     * @return none
     */
    public static function move_page_down($simplelessonid, $sequence) {
        global $DB;

        $pageiddown = self::get_page_id_from_sequence(
                $simplelessonid, $sequence);
        $pageidup = self::get_page_id_from_sequence(
                $simplelessonid, ($sequence + 1));

        self::increment_page_sequence($pageiddown);
        self::decrement_page_sequence($pageidup);
        self::exchange_question_slots($simplelessonid,
                $pageiddown, $pageidup);
    }

    /**
     * Given a page record id
     * decrease the sequence number by 1
     *
     * @param int $pageid
     * @return none
     */
    public static function decrement_page_sequence($pageid) {
        global $DB;
        $sequence = $DB->get_field('simplelesson_pages',
                'sequence', array('id' => $pageid));
        $DB->set_field('simplelesson_pages',
                'sequence', ($sequence - 1), array('id' => $pageid));
    }
    /**
     * Given a page record id
     * increase the sequence number by 1
     *
     * @param int $pageid
     * @return none
     */
    public static function increment_page_sequence($pageid) {
        global $DB;
        $sequence = $DB->get_field('simplelesson_pages',
                'sequence',
                array('id' => $pageid));
        $DB->set_field('simplelesson_pages',
                'sequence', ($sequence + 1),
                array('id' => $pageid));
    }
    /**
     * Given two page record id's
     * Exchange their question slot numbers (if any)
     *
     * @param simplelessonid - make sure it is just this lesson
     * @param $p1 int $pageid
     * @param $p2 int $pageid
     * @return none
     */
    public static function exchange_question_slots($simplelessonid,
            $p1, $p2) {
        global $DB;
        $p1slot = $DB->get_field('simplelesson_questions',
                'slot',
                array('pageid' => $p1,
                'simplelessonid' => $simplelessonid));
        $p2slot = $DB->get_field('simplelesson_questions',
                'slot',
                array('pageid' => $p2,
                'simplelessonid' => $simplelessonid));

        $DB->set_field('simplelesson_questions',
                'slot', ($p2slot),
                array('pageid' => $p1,
                'simplelessonid' => $simplelessonid));
        $DB->set_field('simplelesson_questions',
                'slot', ($p1slot),
                array('pageid' => $p2,
                'simplelessonid' => $simplelessonid));
    }
    /**
     * Update a page record
     *
     * @param int $data from edit_page form
     * @param object $context, the module context
     * @return none
     */
    public static function update_page_record($data, $context) {
        global $DB;

        $pagecontentsoptions = simplelesson_get_editor_options($context);
        $data->timemodified = time();

        $data = file_postupdate_standard_editor(
                $data,
                'pagecontents',
                $pagecontentsoptions,
                $context,
                'mod_simplelesson',
                'pagecontents',
                $data->id);

        $DB->update_record('simplelesson_pages', $data);
    }
    /**
     * Fix the links when a page is deleted
     *
     * @param int $simplelessonid instance the page is in
     * @param int $pageid of deleted page
     * @param object $context, the module context
     */
    public static function fix_page_links($simplelessonid, $pageid) {
        global $DB;

        $pagedata = self::get_page_record($pageid);

        // Pages to process.
        $pagecount = self::count_pages($simplelessonid);
        if ($pagecount != 0) {
            for ($p = 1; $p <= $pagecount; $p++) {
                $pid = self::get_page_id_from_sequence($simplelessonid, $p);
                // Don't worry about this page.
                if ($pid != $pageid) {
                    $data = self::get_page_record($pid);
                    if ($data->nextpageid == $pageid) {
                        // Link to the page following the deleted page.
                        $DB->set_field('simplelesson_pages',
                                'nextpageid', $pagedata->nextpageid,
                                 array('id' => $pid));
                    }
                    if ($data->prevpageid == $pageid) {
                        // Link to the page preceding the deleted page.
                        $DB->set_field('simplelesson_pages',
                                'prevpageid', $pagedata->prevpageid,
                                 array('id' => $pid));
                    }
                }
            }
        }
    }
    /**
     * Given a simplelesson id
     * Fix the pages so that next and previous match page order
     * as it is on the page management screen
     *
     * @param int $simplelessonid
     * @return none
     */
    public static function fix_page_sequence($simplelessonid) {
        global $DB;

        $pagecount = self::count_pages($simplelessonid);

        if ($pagecount != 0) {
            for ($p = 1; $p <= $pagecount; $p++) {
                $pid = self::get_page_id_from_sequence($simplelessonid,
                        $p);
                // ID of previous page in sequence (unless first).
                if ($p != 1) {
                    $previd = self::get_page_id_from_sequence(
                            $simplelessonid, ($p - 1));
                } else {
                    $previd = 0;
                }
                // Next id (unless last).
                if ($p == $pagecount) {
                    $nextid = 0;
                } else {
                    $nextid = self::get_page_id_from_sequence(
                            $simplelessonid, ($p + 1));
                }

                $DB->set_field('simplelesson_pages',
                        'prevpageid', ($previd),
                        array('id' => $pid));
                $DB->set_field('simplelesson_pages',
                        'nextpageid', ($nextid),
                        array('id' => $pid));
           }
       }
    }
}