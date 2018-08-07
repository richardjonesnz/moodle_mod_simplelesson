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
use \mod_simplelesson\local\pages;
namespace mod_simplelesson\output;
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
     * @return $data object, data for rendering mustache template
     */
    public static function get_firstpage_links($cm, $pageid,
        $attemptlink) {

        $data = new \stdClass;
        $data->class = 'mod_simplelesson_page_links';
        $data->buttonclass = 'btn btn-primary';
        $data->linkdata = array();
        $link = new \moodle_url('/mod/simplelesson/showpage.php',
                ['courseid' => $cm->course,
                 'simplelessonid' => $cm->instance,
                 'pageid' => $pageid,
                 'mode' => 'preview']);
        $data->linkdata[] = ['link' => $link->out(false),
                'text' => get_string('preview', 'mod_simplelesson')];

        // If there are questions, show an attempt button too.
        if ($attemptlink) {
            $link = new \moodle_url('/mod/simplelesson/start_attempt.php',
                    ['courseid' => $cm->course,
                          'simplelessonid' => $cm->instance,
                          'pageid' => $pageid]);
            $data->linkdata[] = ['link' => $link->out(false),
                'text' => get_string('attempt', 'mod_simplelesson')];
        }

        return $data;
    }
    /**
     * Return data for the edit links
     *
     * @param $cm the course module object
     * @return $data object, data for rendering mustache template
     */

    public static function get_edit_links($cm) {

        $data = new \stdClass;

        $baseurl = 'mod/simplelesson/';
        $baseparams = ['courseid' => $cm->course,
                'simplelessonid' => $cm->instance];
        $data->class = 'mod_simplelesson_edit_links';
        $data->name = get_string('editing', 'mod_simplelesson');
        $data->linkdata = array();

        // Page management.
        $page = 'edit.php';
        $link = new \moodle_url($baseurl . $page);
        $data->linkdata[] =
                ['link' => $link->out(false, $baseparams),
                 'text' => get_string(
                 'manage_pages', 'mod_simplelesson')];

        // The Question Management page.
        $page = 'edit_questions.php';
        $link = new \moodle_url($baseurl . $page);
        $data->linkdata[] =
                ['link' => $link->out(false, $baseparams),
                 'text' => get_string(
                 'manage_questions', 'mod_simplelesson')];

        return $data;
    }
    /**
     * Return data for the navigation links on a page.
     *
     * @param $cm the course module object
     * @return $data object, data for rendering mustache template
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

        // Link to previous (if exists)
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
}