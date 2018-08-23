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
     * We will backup attempts and answers if userdata is requested
     * but we will not backup questions as the old question
     * categories will not be available in a new course.
     *
     * @return backup_nested_element
     */
    protected function define_structure() {

        // Are we including userinfo?
        $userinfo = $this->get_setting_value('userinfo');

        // Define the root element describing the simplelesson instance.
        $simplelesson = new backup_nested_element('simplelesson',
                array('id'),
                array('course', 'name', 'intro', 'introformat',
                'title', 'showindex', 'allowreview', 'allowincomplete',
                'categoryid', 'behaviour', 'maxattempts', 'grademethod',
                'grade', 'timecreated', 'timemodified'));

        // Define the child elements.
        $pages = new backup_nested_element('pages');
        $page = new backup_nested_element('page', array('id'),
                array('simplelessonid', 'sequence', 'prevpageid',
                'nextpageid', 'pagetitle', 'pagecontents',
                'pagecontentsformat', 'showindex', 'timecreated',
                'timemodified'));

        $attempts = new backup_nested_element('attempts');
        $attempt = new backup_nested_element('attempt',
                array('id'),
                array('simplelessonid', 'qubaid', 'userid', 'status',
                'sessionscore', 'maxscore', 'timetaken', 'timecreated',
                'timemodified'));

        $answers = new backup_nested_element('answers');
        $answer = new backup_nested_element('answer',
                array('id'),
                array('simplelessonid', 'qatid', 'attemptid', 'pageid',
                'maxmark', 'mark', 'questionsummary', 'qtype', 'rightanswer',
                'youranswer', 'timetaken', 'timestarted', 'timecompleted'));

        // Build the tree.
        $simplelesson->add_child($pages);
        $pages->add_child($page);

        $simplelesson->add_child($attempts);
        $attempts->add_child($attempt);

        $simplelesson->add_child($answers);
        $answers->add_child($answer);

        // Define data sources.
        $simplelesson->set_source_table('simplelesson', array('id' => backup::VAR_ACTIVITYID));

        // Backup pages.
        $page->set_source_table('simplelesson_pages',
                array('simplelessonid' => backup::VAR_PARENTID));

        // If there is no user data, don't back up attempts
        // or answers.

        if ($userinfo) {
            // Backup attempts - table has a userid.
            $attempt->set_source_table('simplelesson_attempts',
                    array('simplelessonid' =>
                    backup::VAR_PARENTID));
            $attempt->annotate_ids('user', 'userid');

            // Backup answers.
            $answer->set_source_table('simplelesson_answers',
                    array('simplelessonid' =>
                    backup::VAR_PARENTID));
        }

        // Define file annotations.
        $simplelesson->annotate_files('mod_simplelesson', 'intro',
                null);
        $page->annotate_files('mod_simplelesson', 'pagecontents',
                'id');

        // Return the root element (simplelesson), wrapped into standard activity structure.
        return $this->prepare_activity_structure($simplelesson);
    }
}
