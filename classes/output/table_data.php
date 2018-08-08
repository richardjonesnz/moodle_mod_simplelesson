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
use \mod_simplelesson\local\questions;
defined('MOODLE_INTERNAL') || die;

/**
 * Utility class for creating mustache template data for lists
 * of links.
 *
 * @package    mod_simplelesson
 * @copyright  2018 Richard Jones https://richardnz.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class table_data {

    /**
     * Return data for the table header row
     *
     */
    public static function get_edit_table_headers() {

        $headerdata = array();
        $headerdata[] = get_string('sequence', 'mod_simplelesson');
        $headerdata[] = get_string('pagetitle', 'mod_simplelesson');
        $headerdata[] = get_string('prevpage', 'mod_simplelesson');
        $headerdata[] = get_string('nextpage', 'mod_simplelesson');
        $headerdata[] = get_string('hasquestion', 'mod_simplelesson');
        $headerdata[] = get_string('actions', 'mod_simplelesson');

        return $headerdata;
    }
    /**
     * Return data for the table rows
     *
     */
    public static function get_edit_table_data($cm) {

        $table = new \stdClass();
        $table->class = 'mod_simplelesson_pages_table';
        $table->caption =
            get_string('page_editing', 'mod_simplelesson');
        $table->tableheaders = self::get_edit_table_headers();
        $table->tabledata = array();

        $numpages = pages::count_pages($cm->instance);
        $sequence = 1;
        $url = new \moodle_url('/mod/lesson/edit.php',
                array('courseid' => $cm->course,
                      'simplelessonid' => $cm->instance));

        $context = \context_module::instance($cm->id);

        while ($sequence <= $numpages) {

            // We need the page id for it's record.
            $pageid = pages::get_page_id_from_sequence($cm->instance,
                    $sequence);

            $data = array();
            $pagedata = pages::get_page_record($pageid);

            // Change page id's to sequence numbers for display.
            $prevpage = pages::get_page_sequence_from_id(
                    $pagedata->prevpageid);
            $nextpage = pages::get_page_sequence_from_id(
                    $pagedata->nextpageid);

            $data['sequence'] = $pagedata->sequence;
            $data['title'] = $pagedata->pagetitle;
            $data['previous'] = $prevpage;
            $data['next'] = $nextpage;

            // Indicate if there is a question.
            if (questions::page_has_question($cm->instance,
                    $pageid)) {
                $data['question'] = ['icon' => 'i/valid',
                           'component' => 'core',
                           'alt'=> '*'];
            } else {
                $data['question'] = ['icon' => 'i/invalid',
                           'component' => 'core',
                           'alt'=> 'x'];
            }
            // If we have permission, see the action links.
            if (has_capability('mod/simplelesson:manage',
                    $context)) {
                $data['action'] = self::get_edit_table_actions($cm,
                        $pagedata);
            } else {
                $data['action'] = '';
            }
            $table->tabledata[] = $data;
            $sequence++;
        }

        return $table;
    }
    /**
     * Return data for the table action column
     *
     */
    public static function get_edit_table_actions($cm, $pagedata) {

        $actions = array();
        $baseparams = ['courseid' => $cm->course,
                'simplelessonid' => $cm->instance];

        // Add edit and delete links.
        $link = new \moodle_url('add_page.php', $baseparams);
        $icon = ['icon' => 't/edit', 'component' => 'core',
            'alt'=> get_string('gotoeditpage', 'mod_simplelesson')];
        $actions['add'] = ['link' => $link->out(false,
                     ['sequence' => $pagedata->sequence + 1]),
                      'icon' => $icon];

        // Preview = showpage.
        $link = new \moodle_url('showpage.php', $baseparams);
        $icon = ['icon' => 't/preview', 'component' => 'core',
            'alt'=> get_string('showpage', 'mod_simplelesson')];
        $actions['preview'] = ['link' => $link->out(false,
                     ['sequence' => $pagedata->sequence]),
                      'icon' => $icon];

        // Delete page.
        $link = new \moodle_url('delete_page.php', $baseparams);
        $icon = ['icon' => 't/delete', 'component' => 'core',
            'alt' => get_string('gotodeletepage',
            'mod_simplelesson')];
        $actions['delete'] = ['link' => $link->out(false,
                     ['sequence' => $pagedata->sequence,
                      'returnto' => 'edit']), 'icon' => $icon];

        // Move page up.
        if ($pagedata->sequence != 1) {
            $link = new \moodle_url('edit.php', $baseparams);
            $icon = ['icon' => 't/up', 'component' => 'core',
                    'alt' => get_string('move_up',
                    'mod_simplelesson')];
            $actions['moveup'] = ['link' => $link->out(false,
                    ['sequence' => $pagedata->sequence,
                     'action' => 'move_up']), 'icon' => $icon];
        }

        // Move down.
        if (!pages::is_last_page($pagedata)) {
            $link = new \moodle_url('edit.php', $baseparams);
            $icon = ['icon' => 't/down', 'component' => 'core',
                    'alt' => get_string('move_down',
                    'mod_simplelesson')];
            $actions['movedown'] = ['link' => $link->out(false,
                    ['sequence' => $pagedata->sequence,
                     'action' => 'move_down']), 'icon' => $icon];
        }
        return $actions;
    }
}