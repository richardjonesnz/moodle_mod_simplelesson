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
use \mod_simplelesson\local\questions;
use \mod_simplelesson\local\reporting;
namespace mod_simplelesson\local;
require_once('../../config.php');
require_once($CFG->libdir . '/questionlib.php');

defined('MOODLE_INTERNAL') || die();
/**
 * Utility class for question usage actions
 *
 * @package    mod_simplelesson
 * @copyright  2018 Richard Jones https://richardnz.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class attempts  {
    /**
     * Handles data relating to attempts, including question usages
     *
     * @param $context - module context
     * @param $behaviour - question behaviour
     * @param $entries - questions selected by user (edit.php)
     * @param $simplelessonid - module instance id
     * @return $qubaid - the id of the question engine usage.
     */
    public static function create_usage($context,
            $behaviour, $entries, $simplelessonid) {
        global $DB;
        $quba = \question_engine::make_questions_usage_by_activity(
                'mod_simplelesson',
                $context);
        $quba->set_preferred_behaviour($behaviour);
        foreach ($entries as $entry) {
            $questiondef = \question_bank::load_question($entry->qid);
            $slot = $quba->add_question($questiondef,
                    $entry->defaultmark);
            self::set_slot($simplelessonid, $entry->pageid, $slot);
        }
        $quba->start_all_questions();
        \question_engine::save_questions_usage_by_activity($quba);
        $qubaid = $quba->get_id();
        $DB->set_field('simplelesson',
                    'qubaid', $qubaid,
                    array('id' => $simplelessonid));
        return $qubaid;
    }
    /**
     * Set the slot number in the questions table
     *
     * @param $simplelessonid - module instance id
     * @param $pageid - id of page to set slot
     * @param $slot - question slot number
     */
    public static function set_slot($simplelessonid, $pageid, $slot) {
        global $DB;
        $DB->set_field('simplelesson_questions',
                'slot', $slot,
                array('simplelessonid' => $simplelessonid,
                'pageid' => $pageid));
    }
    /**
     * Get the usage id for a simplelesson instance
     *
     * @param $simplelessonid - module instance id
     * @return $qubaid - the question usage id associated with this lesson
     */
    public static function get_usageid($simplelessonid) {
        global $DB;
        return $DB->get_field('simplelesson',
                'qubaid',
                array('id' => $simplelessonid));
    }
    /**
     * Remove the usage id for a simplelesson instance
     * Also clean up Moodle's attempt data as this doesn't
     * seem to get done by question engine.
     *
     * Will also make it easier when dealing with GDPR.
     *
     * @param $simplelessonid - module instance id
     */
    public static function remove_usage_data($qubaid) {
        global $DB;

        // Delete these records explicitly, we have the
        // attempt data we need in our attempts table.
        $ataids = $DB->get_records('question_attempts',
                array('questionusageid'=>$qubaid));
        foreach ($ataids as $ataid) {
            // get the attempt step id's
            $atsteps = $DB->get_records('question_attempt_steps',
                    array('questionattemptid'=>$ataid->id));
                foreach ($atsteps as $atstep) {
                    // Get the step data out
                    $DB->delete_records(
                            'question_attempt_step_data',
                            array('attemptstepid'=>$atstep->id));
                }
                // get the attempt steps cleaned out
                $DB->delete_records('question_attempt_steps',
                        array('questionattemptid'=>$ataid->id));
            }
            // Delete the attempt data.
            $DB->delete_records('question_attempts',
                   array('questionusageid'=>$qubaid));

        $DB->delete_records('question_usages',
                array('id'=> $qubaid));
    }
    /**
     * Return the wanted row from question attempts
     *
     * @param $qubaid usage id
     * @param $slot question attempt slot
     * @return object corresponding row in question attempts
     */
    public static function get_question_attempt_id(
            $qubaid, $slot) {
        global $DB;
        $data = $DB->get_record('question_attempts',
                  array('questionusageid' => $qubaid,
                  'slot' => $slot),
                  '*', MUST_EXIST);
        return $data;
    }
    /**
     * Return an array of lesson answers and associated data
     *
     * @param $attemptid int id of attempt (simplelesson_attempts)
     * @return object array with one or more rows of answer data
     */
    public static function get_lesson_answer_data($attemptid) {
        global $DB;

        $answerdata = $DB->get_records('simplelesson_answers',
                array('attemptid' => $attemptid));
        // var_dump($answerdata);exit;
        // Add the data for the summary table.
        foreach ($answerdata as $data) {

            // Add the page title
            $data->pagename = pages::get_page_title($data->pageid);

            // If correct, maxmarks (may need to do more later)
            if ($data->youranswer == $data->rightanswer) {
                $data->mark = (int) $data->maxmark;
            } else {
                $data->mark = 0;
            }

            // Calculate the elapsed time (s)
            $data->timetaken = (int) ($data->timecompleted
                    - $data->timestarted);
        }
        return $answerdata;
    }
    /**
     * Make an entry in the attempts table
     *
     * @param $data data to insert (from start_attempt.php)
     * @return int record->id
     */
    public static function set_attempt_start($data) {
        global $DB;
        return $DB->insert_record(
                'simplelesson_attempts',
                $data);
    }
    /**
     * Get the user record for an attempt
     *
     * @param $attemptid the attempt record id
     * @return object - user data from the users table
     */
    public static function get_attempt_user($attemptid) {
        global $DB;
        $data = $DB->get_record('simplelesson_attempts',
                array('id' => $attemptid), 'userid', MUST_EXIST);
        return $DB->get_record('user',
                array('id' => $data->userid), '*', MUST_EXIST);
    }
    /**
     * Set status the attempts table
     *
     * Need some constants here: 0, 1 (started), 2 (complete).
     * @param $attemptid - record id to update
     * @param $sessionscore - Score for this attempt
     */
    public static function set_attempt_completed($attemptid,
            $sessiondata) {
        global $DB;
        $DB->set_field('simplelesson_attempts',
                'status', MOD_SIMPLELESSON_ATTEMPT_COMPLETE,
                array('id' => $attemptid));
        $DB->set_field('simplelesson_attempts',
                'sessionscore', $sessiondata->score,
                array('id' => $attemptid));
        $DB->set_field('simplelesson_attempts',
                'timetaken', $sessiondata->stime,
                array('id' => $attemptid));
    }
    /**
     * Add up the marks and times in the answer data
     *
     * @param $answerdata - array of objects
     * @return object overall mark and time for the attempt
     */
    public static function get_sessiondata($answerdata) {
        $score = 0.0;
        $stime = 0;
        foreach ($answerdata as $data) {
            $score += $data->mark;
            $stime += $data->timetaken;
        }
        $returndata = new \stdClass();
        $returndata->score = $score;
        $returndata->stime = $stime;
        return $returndata;
    }
    /**
     * Get the user attempts at this lesson instance
     *
     * @param $userid - relevant user
     * @param $simplelessonid - relevant lesson
     * @return int number of attempts by user
     *         on this lesson and course
     */
    public static function get_number_of_attempts($userid, $simplelessonid) {
        global $DB;
        return $DB->count_records('simplelesson_attempts',
                array('userid' => $userid,
                'simplelessonid' => $simplelessonid));
    }
    /**
     * save answer data
     *
     * @param $answerdata - array of objects
     * @return none
     */
    public static function save_lesson_answerdata($answerdata) {
        global $DB;
        // var_dump($answerdata);exit;
        foreach ($answerdata as $answer) {
            $data = new \stdClass();
            $data->id = $answer->id;
            $data->simplelessonid = $answer->simplelessonid;
            $data->qatid = 0; // Data will be removed by cleanup.
            $data->attemptid = $answer->attemptid;
            $data->pageid = $answer->pageid;
            $data->maxmark = $answer->maxmark;
            $data->mark = $answer->mark;
            $data->questionsummary = $answer->questionsummary;
            $data->rightanswer = $answer->rightanswer;
            $data->youranswer = $answer->youranswer;
            $data->timestarted = $answer->timestarted;
            $data->timecompleted = $answer->timecompleted;

            $DB->update_record('simplelesson_answers', $data);
        }
    }
    /**
     * Delete the record for an attempt
     *
     * @param $attemptid the attempt record id
     */
    public static function delete_attempt($attemptid) {
        global $DB;
        return $DB->delete_records('simplelesson_attempts',
                array('id' => $attemptid));
    }
}
