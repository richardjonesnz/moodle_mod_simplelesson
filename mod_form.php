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
 * The main simplelesson configuration form
 *
 * It uses the standard core Moodle formslib. For more info about them, please
 * visit: http://docs.moodle.org/en/Development:lib/formslib.php
 *
 * @package    mod_simplelesson
 * @copyright  2018 Richard Jones <richardnz@outlook.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @see https://github.com/moodlehq/moodle-mod_newmodule
 *
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->libdir . '/questionlib.php');
/**
 * Module instance settings form (from moodle-mod_newmodule)
 *
 * @package    mod_simplelesson
 * @copyright  2016 Your Name <your@email.address>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_simplelesson_mod_form extends moodleform_mod {

    /**
     * Defines forms elements
     */
    public function definition() {
        global $CFG, $DB;

        $mform = $this->_form;

        // Adding the "general" fieldset, where all the common settings are showed.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('simplelessonname', 'simplelesson'),
                array('size' => '64'));

        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }

        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'simplelessonname', 'simplelesson');

        // Adding the standard "intro" and "introformat" fields.
        if ($CFG->branch >= 29) {
            $this->standard_intro_elements();
        } else {
            $this->add_intro_editor();
        }

        // Additional settings for the module.
        $mform->addElement('header', 'label', get_string('simplelesson_settings', 'mod_simplelesson'));

        $mform->addElement('text', 'title', get_string('simplelesson_title', 'mod_simplelesson'));
        $mform->setType('title', PARAM_TEXT);

        // Allow the page index.
        $mform->addElement('advcheckbox', 'showindex',
                get_string('showindex', 'mod_simplelesson'));
        $mform->setDefault('showindex', 1);
        $mform->addHelpButton('showindex', 'showindex',
                'simplelesson');

        // Select the category for the questions that can be added.
        $categories = array();
        $cats = $DB->get_records('question_categories',
                null, null, 'id, name');
        foreach ($cats as $cat) {
            $questions = $DB->count_records(
                    'question', array('category' => $cat->id));
            if ($questions > 0) {
                $categories[$cat->id] = $cat->name . ' (' . $questions . ')';
            }
            $categories[0] = get_string('nocategory', 'mod_simplelesson');
        }

        $mform->addElement('select', 'categoryid', get_string('category_select', 'mod_simplelesson'), $categories);
        $mform->addHelpButton('categoryid', 'categoryid', 'mod_simplelesson');
        $mform->setType('categoryid', PARAM_INT);
        $mform->setDefault('categoryid', 0);

        // Attempts.
        $attemptoptions = array(0 => get_string('unlimited', 'mod_simplelesson'),
            1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5');
        $mform->addElement('select', 'maxattempts', get_string('maxattempts', 'mod_simplelesson'), $attemptoptions);
        $mform->setType('maxattempts', PARAM_INT);

        // Returns a list of available question behaviour options.
        $boptions = array(
                'immediatefeedback' => get_string('immediatefeedback',
                'mod_simplelesson'),
                'immediatefeedbackcbm' => get_string('immediatecbm',
                'mod_simplelesson'),
                'adaptive' => get_string('adaptive',
                'mod_simplelesson'));

        $mform->addElement('select', 'behaviour',
                get_string('behaviour', 'mod_simplelesson'),
                $boptions);
        $mform->setType('behaviour', PARAM_TEXT);
        $mform->addHelpButton('behaviour', 'behaviour',
                'mod_simplelesson');
        // Question usage field.
        $mform->addElement('hidden', 'qubaid', 0);
        $mform->setType('qubaid', PARAM_INT);

        // Add standard grading elements.
        $this->standard_grading_coursemodule_elements();

        // Add standard elements, common to all modules.
        $this->standard_coursemodule_elements();

        // Add standard buttons, common to all modules.
        $this->add_action_buttons();
    }
}
