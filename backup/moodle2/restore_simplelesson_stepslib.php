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
* @package    mod_simplelesson
 * @copyright  2018 Richard Jones <richardnz@outlook.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @see https://github.com/moodlehq/moodle-mod_newmodule
 *
 */
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

        $paths = array();
        $paths[] = new restore_path_element('simplelesson', '/activity/simplelesson');

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

        if ($data->grade < 0) {
            // Scale found, get mapping.
            $data->grade = -($this->get_mappingid('scale', abs($data->grade)));
        }

        // Create the simplelesson instance.
        $newitemid = $DB->insert_record('simplelesson', $data);
        $this->apply_activity_instance($newitemid);
    }

    protected function process_simplelesson_page($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->simplelessonid = $this->get_new_parentid('simplelesson');

        // We'll remap all the prevpageid and nextpageid at the end
        // when we know how :)

        $newitemid = $DB->insert_record('simplelesson_pages', $data);
        $this->set_mapping('simplelesson_page', $oldid, $newitemid, true);

    }
    /**
     * Post-execution actions
     */
    protected function after_execute() {
        // Add simplelesson related files, no need to match by itemname (just internally handled context).
        $this->add_related_files('mod_simplelesson', 'intro', null);
        $this->add_related_files('mod_simplelesson', 'pagecontents', 'simplelesson_pages');

        // Check here, may have to remap the page links (prev, next)
        // At the moment, user would have to go to manage pages to fix that up.
    }
}
