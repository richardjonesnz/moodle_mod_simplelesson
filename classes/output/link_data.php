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
 * Question attempt and related utilities for simplelesson
 *
 * @package    mod_simplelesson
 * @copyright  Richard Jones https://richardnz.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_simplelesson\output;
use \mod_simplelesson\local\pages;
defined('MOODLE_INTERNAL') || die;

/**
 * Utility class for creating mustache template data for lists
 * of links.
 *
 * @package    mod_simplelesson
 * @copyright  2018 Richard Jones https://richardnz.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class link_data {

    /**
     * Return data for the first page links
     *
     * @param $cm the course module object
     * @param $pageid int id of page to link to
     * @param $attemptlink boolean true if attempt link is to be shown
     * @return $ret object, data for rendering mustache template
     */
    public static function get_firstpage_links($cm, $pageid,
        $attemptlink) {

        $ret = new \stdClass;
        $ret->class = 'mod_simplelesson_page_links';
        $ret->buttonclass = 'btn btn-primary';
        $ret->linkdata = array();
        $link = new \moodle_url('/mod/simplelesson/showpage.php',
                ['courseid' => $cm->course,
                 'simplelessonid' => $cm->instance,
                 'pageid' => $pageid,
                 'mode' => 'preview']);
        $ret->linkdata[] = ['link' => $link->out(false),
                'text' => get_string('preview', 'mod_simplelesson')];

        // If there are questions, show an attempt button too.
        if ($attemptlink) {
            $link = new \moodle_url('/mod/simplelesson/start_attempt.php',
                    ['courseid' => $cm->course,
                          'simplelessonid' => $cm->instance,
                          'pageid' => $pageid]);
            $ret->linkdata[] = ['link' => $link->out(false),
                'text' => get_string('attempt', 'mod_simplelesson')];
        }

        return $ret;
    }
    /**
     * Return data for the edit links on the front page
     *
     * @param $cm the course module object
     * @return $ret object, data for rendering mustache template
     */

    public static function get_edit_links($cm) {

        $ret = new \stdClass;

        $baseparams = ['courseid' => $cm->course,
                'simplelessonid' => $cm->instance];
        $ret->class = 'mod_simplelesson_edit_links';
        $ret->buttonclass = 'btn btn-default';
        $ret->name = get_string('editing', 'mod_simplelesson');
        $ret->linkdata = self::get_managing_links($baseparams);

        return $ret;
    }

    /**
     * Return data for the navigation links on a page.
     *
     * @param object $ret the page data
     * @param object $cm the course module instance
     * @param string $mode the mode param of showpage.php
     * @param int $attemptid the aattemptid parameter
     * @param bool $lastpage true if this is the last page of the lesson
     * @return $ret object, data for rendering mustache template
     */
    public static function get_nav_links($data, $cm, $mode,
            $attemptid, $lastpage) {

        // Data for the links template
        $ret = new \stdClass;
        $ret->class = 'mod_simplelesson_page_links';
        $ret->name = get_string('navigation', 'mod_simplelesson');
        $ret->linkdata = array();
        $ret->buttonclass = 'btn btn-info';
        $url = new \moodle_url('/mod/simplelesson/showpage.php',
                array('courseid' => $cm->course,
                'simplelessonid' => $cm->instance,
                'mode' => $mode,
                'attemptid' => $attemptid));

        // Home link.
        $ret->linkdata[] = self::get_home_link($cm,
                get_string('homelink', 'mod_simplelesson'));

        // Link to previous (if exists).
        if ($data->prevpageid != 0) {
            $ret->linkdata[] = ['link' => $url->out(false,
                    ['pageid' => $data->prevpageid]),
                    'text' => get_string('gotoprevpage',
                    'mod_simplelesson')];
        }
        // Link to next.
        if ($data->nextpageid != 0) {
            $ret->linkdata[] = ['link' => $url->out(false,
                    ['pageid' => $data->nextpageid]),
                    'text'=> get_string('gotonextpage',
                    'mod_simplelesson')];
        }
        // Exit button.
        if ($lastpage) {
            $url = new \moodle_url('/mod/simplelesson/summary.php',
                    array('courseid' => $cm->course,
                          'simplelessonid' => $cm->instance,
                          'mode' => $mode,
                          'pageid' => $data->id,
                          'attemptid' => $attemptid));
            $ret->linkdata[] = ['link' => $url->out(false),
                    'text' => get_string('end_lesson',
                    'mod_simplelesson')];
        }

        return $ret;
    }
    /**
     * Return data for the manage page links.
     *
     * @param object $data the page data
     * @param object $cm the course module instance
     * @return $ret object, data for rendering mustache template
     */
    public static function get_manage_links($data, $cm) {

        $baseparams = ['courseid' => $cm->course,
                'simplelessonid' => $cm->instance];
        $ret = new \stdClass;
        $ret->class = 'mod_simplelesson_nav_links';
        $ret->buttonclass = 'btn btn-default';
        $ret->linkdata = array();

        // Add edit and delete links.
        $link = new \moodle_url('add_page.php', $baseparams);
        $ret->linkdata[] = ['link' => $link->out(false,
                ['sequence' =>$data->sequence + 1]),
                'text' => get_string('gotoaddpage', 'mod_simplelesson')];

        $link = new \moodle_url('edit_page.php', $baseparams);
        $ret->linkdata[] = ['link' => $link->out(false,
                ['sequence' => $data->sequence]),
                 'text' =>
                 get_string('gotoeditpage', 'mod_simplelesson')];

        $link = new \moodle_url('delete_page.php', $baseparams);
        $ret->linkdata[] = ['link' => $link->out(false,
                ['sequence' => $data->sequence, 'returnto' => 'view']),
                 'text' =>
                 get_string('gotodeletepage', 'mod_simplelesson')];

        // Page and question management.
        $ret->linkdata = array_merge($ret->linkdata, self::get_managing_links($baseparams));

        return $ret;
    }
    /**
     * Return data for the manage page links.
     *
     * @param string $baseparams url link parameters
     * @return array of link data for mustache template
     */
    public static function get_managing_links($baseparams) {

        $links = array();

        // Page management.
        $link = new \moodle_url('edit.php');
        $links[] = ['link' => $link->out(false, $baseparams),
                    'text' => get_string(
                    'manage_pages', 'mod_simplelesson')];

        // The Question Management page.
        $link = new \moodle_url('edit_questions.php');
        $links[] =
                ['link' => $link->out(false, $baseparams),
                 'text' => get_string(
                 'manage_questions', 'mod_simplelesson')];
        return $links;
    }
    /**
     * Return data for the home page link.
     *
     * @param object $cm the course module instance
     * @param string $label text for the link
     * @return array link data for mustache template
     */
    public static function get_home_link($cm, $label) {

        $link = new \moodle_url('view.php',
        ['simplelessonid' => $cm->instance]);

        return ['link' => $link->out(false),
               'text' => $label];
    }
    /**
     * Return button with home page link.
     *
     * @param object $cm the course module instance
     * @param string $label text for the button
     * @return array link data for mustache template
     */
    public static function get_home_button($cm, $label) {

       $baseparams = ['courseid' => $cm->course,
                       'simplelessonid' => $cm->instance];
        $ret = new \stdClass;
        $ret->class = 'mod_simplelesson_nav_links';
        $ret->buttonclass = 'btn btn-primary';
        $ret->linkdata = array();

        $ret->linkdata[] = self::get_home_link($cm, $label);

        return $ret;
    }


    /**
     * Return data for the page management link.
     *
     * @param object $cm the course module instance
     * @return array link data for mustache template
     */
     public static function get_pagemanagement_links($cm) {

        $baseparams = ['courseid' => $cm->course,
                       'simplelessonid' => $cm->instance];
        $ret = new \stdClass;
        $ret->class = 'mod_simplelesson_nav_links';
        $ret->buttonclass = 'btn btn-primary';
        $ret->linkdata = array();

        $ret->linkdata[] = self::get_home_link($cm,
                get_string('homelink', 'mod_simplelesson'));

        // Link to Add page
        $numpages = pages::count_pages($cm->instance);
        $link = new \moodle_url('add_page.php', $baseparams);
        $ret->linkdata[] = ['link' => $link->out(false,
                ['sequence' =>$numpages + 1]),
                'text' => get_string('gotoaddpage', 'mod_simplelesson')];

        // Link to auto-sequencing page.
        $link = new \moodle_url('autosequence.php', $baseparams);
        $ret->linkdata[] = ['link' =>$link->out(false),
                'text' => get_string('autosequencelink',
                'mod_simplelesson')];

        return $ret;
    }
    /**
     * Return data for the question page management link.
     *
     * @param object $cm the course module instance
     * @return array link data for mustache template
     */
     public static function get_questionpage_links($cm) {

        $baseparams = ['courseid' => $cm->course,
                       'simplelessonid' => $cm->instance];
        $ret = new \stdClass;
        $ret->class = 'mod_simplelesson_nav_links';
        $ret->buttonclass = 'btn btn-primary';
        $ret->linkdata = array();

        $ret->linkdata[] = self::get_home_link($cm,
                get_string('homelink', 'mod_simplelesson'));

        // Link to Add question page
        $link = new \moodle_url('add_question.php', $baseparams);
        $ret->linkdata[] = ['link' => $link->out(false),
                'text' => get_string('add_question',
                'mod_simplelesson')];

        // Link Page management.
        $link = new \moodle_url('edit.php', $baseparams);
        $ret->linkdata[] = ['link' =>$link->out(false),
                'text' => get_string('manage_pages',
                'mod_simplelesson')];

        return $ret;
    }
}