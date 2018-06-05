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
 * Provides the restore activity task class
 *
 * @package mod_simplelesson
 * @copyright 2018 Richard Jones <richardnz@outlook.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @see https://github.com/moodlehq/moodle-mod_newmodule
 *
 */
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/mod/simplelesson/backup/moodle2/restore_simplelesson_stepslib.php');

/**
 * Restore task for the simplelesson activity module
 *
 * Provides all the settings and steps to perform complete restore of the activity.
 *
 * @package   mod_simplelesson
 * @category  backup
 * @copyright 2016 Your Name <your@email.address>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_simplelesson_activity_task extends restore_activity_task {

    /**
     * Define (add) particular settings this activity can have
     */
    protected function define_my_settings() {
        // No particular settings for this activity.
    }

    /**
     * Define (add) particular steps this activity can have
     */
    protected function define_my_steps() {
        // We have just one structure step here.
        $this->add_step(new restore_simplelesson_activity_structure_step('simplelesson_structure', 'simplelesson.xml'));
    }

    /**
     * Define the contents in the activity that must be
     * processed by the link decoder
     */
    static public function define_decode_contents() {
        $contents = array();

        $contents[] = new restore_decode_content('simplelesson', array('intro'), 'simplelesson');
        $contents[] = new restore_decode_content('simplelesson_pages', array('pagecontents'), 'simplelesson_page');
        return $contents;
    }

    /**
     * Define the decoding rules for links belonging
     * to the activity to be executed by the link decoder
     */
    static public function define_decode_rules() {
        $rules = array();

        $rules[] = new restore_decode_rule('MULTIPAGEVIEWBYID', '/mod/simplelesson/view.php?id=$1', 'course_module');

        $rules[] = new restore_decode_rule(
               'MULTIPAGEINDEX',
               '/mod/simplelesson/index.php?id=$1',
               'course');

        $rules[] = new restore_decode_rule(
                'MULTIPAGESHOWPAGE',
                '/mod/simplelesson/showpage.php?id=$1&pageid=$2',
                array('course', 'simplelesson_pages'));
        return $rules;
    }

    /**
     * Define the restore log rules that will be applied
     * by the {@link restore_logs_processor} when restoring
     * simplelesson logs. It must return one array
     * of {@link restore_log_rule} objects
     */
    static public function define_restore_log_rules() {
        $rules = array();

        $rules[] = new restore_log_rule('simplelesson',
                'add', 'view.php?id={course_module}',
                '{simplelesson}');
        $rules[] = new restore_log_rule('simplelesson',
                'edit', 'view.php?id={course_module}',
                '{simplelesson}');
        $rules[] = new restore_log_rule('simplelesson',
                'view', 'view.php?id={course_module}',
                '{simplelesson}');
        $rules[] = new restore_log_rule('simplelesson',
                'update', 'view.php?id={course_module}',
                '{simplelesson}');

        return $rules;
    }

    /**
     * Define the restore log rules that will be applied
     * by the {@link restore_logs_processor} when restoring
     * course logs. It must return one array
     * of {@link restore_log_rule} objects
     *
     * Note this rules are applied when restoring course logs
     * by the restore final task, but are defined here at
     * activity level. All them are rules not linked to any module instance (id = 0)
     */
    static public function define_restore_log_rules_for_course() {
        $rules = array();

        $rules[] = new restore_log_rule('simplelesson',
                'view all', 'index.php?id={course}', null);

        return $rules;
    }
}
