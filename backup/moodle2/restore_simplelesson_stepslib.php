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
 * Define all the restore steps that will be used by the restore_simplelesson_activity_task
 *
 * @package mod_simplelesson
 * @copyright 2018 Richard Jones <richardnz@outlook.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @see https://github.com/moodlehq/moodle-mod_newmodule
 *
 */
use \mod_simplelesson\local\pages;
defined('MOODLE_INTERNAL') || die();

/**
 * Structure step to restore one simplelesson activity
 *
 * @package   mod_simplelesson
 * @category  backup
 * @copyright 2016 Your Name <your@email.address>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_simplelesson_activity_structure_step extends restore_activity_structure_step {

    /**
     * Defines structure of path elements to be processed during the restore
     *
     * @return array of {@link restore_path_element}
     */
    protected function define_structure() {
        $userinfo = $this->get_setting_value('userinfo');
        $paths = array();

        $paths[] = new restore_path_element('simplelesson',
                '/activity/simplelesson');

        $paths[] = new restore_path_element('simplelesson_page',
                '/activity/simplelesson/pages/page');
        // Backup if user info available/selected
        if ($userinfo) {
            $paths[] = new restore_path_element(
                    'simplelesson_attempt',
                    '/activity/simplelesson/attempts/attempt');

            $paths[] = new restore_path_element(
                    'simplelesson_answer',
                    '/activity/simplelesson/answers/answer');
        }

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Process the given restore path element data
     *
     * @param array $data parsed element data
     */
    protected function process_simplelesson($data) {
        global $DB;
        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        if (empty($data->timecreated)) {
            $data->timecreated = time();
        }

        if (empty($data->timemodified)) {
            $data->timemodified = time();
        }

        // Create the simplelesson instance.
        $newitemid = $DB->insert_record('simplelesson', $data);
        $this->apply_activity_instance($newitemid);
    }

    protected function process_simplelesson_page($data) {
        global $DB;
        $data = (object)$data;
        $oldid = $data->id;
        $data->simplelessonid =
                $this->get_new_parentid('simplelesson');

        $newitemid = $DB->insert_record('simplelesson_pages', $data);
        $this->set_mapping('simplelesson_page', $oldid, $newitemid, true);

    }
    protected function process_simplelesson_attempt($data) {
        global $DB;
        $data = (object)$data;
        $oldid = $data->id;
        $data->simplelessonid =
                $this->get_new_parentid('simplelesson');

        $data->userid = $this->get_mappingid('user', $data->userid);

        $newitemid = $DB->insert_record('simplelesson_attempts', $data);
        $this->set_mapping('simplelesson_attempt', $oldid, $newitemid,
                true);

    }
    protected function process_simplelesson_answer($data) {
        global $DB;
        $data = (object)$data;
        $oldid = $data->id;
        $data->simplelessonid =
                $this->get_new_parentid('simplelesson');

        $newitemid = $DB->insert_record('simplelesson_answers', $data);
        $this->set_mapping('simplelesson_answer', $oldid, $newitemid, true);

    }

    /**
     * Post-execution actions
     */
    protected function after_execute() {
        global $DB;
        // Add simplelesson related files.
        $this->add_related_files('mod_simplelesson', 'intro',
                null);
        $this->add_related_files('mod_simplelesson',
            'pagecontents', 'simplelesson_page');

        // Fix up page id's using the sequence number.
        $simplelessonid = $this->get_new_parentid('simplelesson');

        // How many pages to fix:
        $pagecount = pages::count_pages($simplelessonid);

        for ($p = 1; $p <= $pagecount; $p++) {
            $newpageid = pages::get_page_id_from_sequence($simplelessonid,
            $p);
            $nextpageid = ($p == $pagecount) ? 0 :
                    pages::get_page_id_from_sequence($simplelessonid,
                            $p + 1);
            $prevpageid = ($p == 1) ? 0 :
                    pages::get_page_id_from_sequence($simplelessonid,
                            $p - 1);

            $DB->set_field('simplelesson_pages', 'nextpageid',
                    $nextpageid,
                    array('id' => $newpageid,
                    'simplelessonid' => $simplelessonid));
            $DB->set_field('simplelesson_pages', 'prevpageid',
                    $prevpageid,
                    array('id' => $newpageid,
                    'simplelessonid' => $simplelessonid));
        }
    }
}
