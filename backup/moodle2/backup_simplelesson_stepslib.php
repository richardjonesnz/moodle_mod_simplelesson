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
 * Define all the backup steps that will be used by the backup_simplelesson_activity_task
 *
 * @package    mod_simplelesson
 * @copyright  2018 Richard Jones <richardnz@outlook.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @see https://github.com/moodlehq/moodle-mod_newmodule
 *
 */

defined('MOODLE_INTERNAL') || die;
require_once($CFG->dirroot . '/mod/simplelesson/lib.php');
/**
 * Define the complete simplelesson structure for backup, with file and id annotations
 *
 * @package   mod_simplelesson
 * @category  backup
 * @copyright 2016 Your Name <your@email.address>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_simplelesson_activity_structure_step extends backup_activity_structure_step {

    /**
     * Defines the backup structure of the module
     *
     * @return backup_nested_element
     */
    protected function define_structure() {

        // Are we including userinfo?
        $userinfo = $this->get_setting_value('userinfo');

        // Define the root element describing the simplelesson instance.
        $simplelesson = new backup_nested_element('simplelesson', array('id'), array(
            'name', 'intro', 'introformat', 'title', 'timecreated','timemodified', 'grade'));

        // Build the tree here.
        $pages = new backup_nested_element('pages');
        $page = new backup_nested_element('page', array('id'), 
                array('simplelessonid', 'sequence', 'prevpageid','nextpageid',
                'pagetitle','pagecontents','pagecontentsformat','timecreated',
                'timemodified','title'));

        $simplelesson->add_child($pages);
        $pages->add_child($page);

        // Define data sources.
        $simplelesson->set_source_table('simplelesson', array('id' => backup::VAR_ACTIVITYID));

        // Pages are ordered by sequence number
        $page->set_source_table('simplelesson_pages', 
                array('simplelessonid' => backup::VAR_PARENTID), 
                'sequence ASC');

        // Define file annotations
        $simplelesson->annotate_files('mod_simplelesson', 'intro', null);
        $page->annotate_files('mod_simplelesson', 'pagecontents', 'id');

        // Return the root element (simplelesson), wrapped into standard activity structure.
        return $this->prepare_activity_structure($simplelesson);
    }
}
