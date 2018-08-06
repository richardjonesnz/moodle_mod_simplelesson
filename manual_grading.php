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

use \mod_simplelesson\local\reporting;
use \mod_simplelesson\local\attempts;
use \core\output\notification;
require_once('../../config.php');
require_once($CFG->libdir . '/formslib.php');

/**
 * Manual grading for essay questions
 *
 * @package   mod_simplelesson
 * @copyright 2018 Richard Jones https://richardnz.net
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define a form for grading essay questions
 */
class simplelesson_essaygrading_form extends moodleform {
    /**
     * Defines forms elements
     */
    public function definition() {
        global $CFG;
        $mform = $this->_form;

        // Marks available.
        $marks = array();
        for ($m = 0; $m <= $this->_customdata['maxmark']; $m++) {
            $marks[$m] = '' . $m;
        }
        $mform->addElement('select', 'mark',
                get_string('allocate_mark', 'mod_simplelesson'),
                $marks);

        $mform->addElement('hidden', 'courseid',
                $this->_customdata['courseid']);
        $mform->addElement('hidden', 'simplelessonid',
                $this->_customdata['simplelessonid']);
        $mform->addElement('hidden', 'answerid',
                $this->_customdata['answerid']);

        $mform->setType('courseid', PARAM_INT);
        $mform->setType('simplelessonid', PARAM_INT);
        $mform->setType('answerid', PARAM_INT);

        $this->add_action_buttons();
    }
}
global $DB;

$courseid = required_param('courseid', PARAM_INT);
$simplelessonid = required_param('simplelessonid', PARAM_INT);
$answerid = required_param('answerid', PARAM_INT);

$moduleinstance  = $simplelessonid;
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$cm = get_coursemodule_from_instance('simplelesson',
        $simplelessonid, $courseid, false, MUST_EXIST);

$pageurl = new moodle_url(
        '/mod/simplelesson/manage_grading.php',
        array('courseid' => $courseid,
        'simplelessonid' => $simplelessonid,
        'answerid' => $answerid));
$PAGE->set_url($pageurl);
require_login($course, true, $cm);

$reportsurl = new moodle_url('/mod/simplelesson/reports.php',
        array('courseid' => $courseid,
        'simplelessonid' => $simplelessonid,
        'report' => 'menu'));

$coursecontext = context_course::instance($courseid);
$modulecontext = context_module::instance($cm->id);

$PAGE->set_context($modulecontext);
$PAGE->set_pagelayout('course');
$renderer = $PAGE->get_renderer('mod_simplelesson');

$answerdata = reporting::fetch_essay_answer_record($answerid);

// Process the form.
$mform = new simplelesson_essaygrading_form(null,
        array('maxmark' => $answerdata->maxmark,
        'courseid' => $courseid,
        'simplelessonid' => $simplelessonid,
        'answerid' => $answerid,
        'action' => 'edit'));

if ($mform->is_cancelled()) {
    redirect($reportsurl, get_string('cancelled'), 2,
            notification::NOTIFY_INFO);
}

if ($data = $mform->get_data()) {
    // Update the attempt and answer data.
    attempts::update_attempt_score($answerid, $data->mark);
    redirect($reportsurl,
            get_string('grade_saved', 'mod_simplelesson'), 2,
            notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();
echo $renderer->grading_header($answerdata);
echo $renderer->essay_text($answerdata->youranswer);
$mform->display();
echo $OUTPUT->footer();
return;