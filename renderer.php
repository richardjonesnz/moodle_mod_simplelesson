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
 * Custom renderer for output of pages
 *
 * @package    mod_simplelesson
 * @copyright  2018 Richard Jones <richardnz@outlook.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @see https://github.com/moodlehq/moodle-mod_newmodule
 * @see https://github.com/justinhunt/moodle-mod_pairwork
 */

use mod_simplelesson\local\pages;
use mod_simplelesson\local\questions;
defined('MOODLE_INTERNAL') || die();

/**
 * Renderer for Simple lesson mod.
 */
class mod_simplelesson_renderer extends plugin_renderer_base {

    /**
     * Returns the header for the module.
     *
     * @param string $lessontitle the module name.
     * @param string $coursename the course name.
     * @return string header output
     */
    public function header($lessontitle, $coursename) {

        // Header setup.
        $this->page->set_title($this->page->course->shortname.": ".$coursename);
        $this->page->set_heading($this->page->course->fullname);
        $output = $this->output->header();

        $output .= $this->output->heading($lessontitle);

        return $output;
    }
    /**
     * Returns the header for the module.
     *
     * @param string $simplelesson the module name.
     * @param int $simplelessonid the module instance id.
     * @return string header output
     */
    public function fetch_intro($simplelesson, $simplelessonid) {

        $output = $this->output->box(format_module_intro(
                'simplelesson', $simplelesson, $simplelessonid),
                'generalbox mod_introbox', 'simplelessonintro');

        return $output;
    }
    /**
     * Returns the editing links for the intro (home) page
     * when there are no pages yet.
     * @param object $module the moduleinstance
     * @return string html
     */
    public function fetch_nopage_links($module) {

        $html = html_writer::start_div('mod_simplelesson_edit_links');
        // Add message and lastpage link.
        $html .= '<p>' .
                get_string('no_pages','mod_simplelesson') . '</p>';
        $url = new moodle_url('/mod/simplelesson/add_page.php',
                array('courseid' => $module->course,
                      'simplelessonid' => $module->instance,
                      'sequence' => 0,
                      'sesskey' => sesskey()));
        $html .= html_writer::link($url,
                    get_string('gotoaddpage', 'mod_simplelesson'));
        $html .= html_writer::end_div();

        return $html;
    }
    /**
     * Returns the html to show info about the lesson.
     *
     * @param string $data the attempts information
     * @return string html of lesson information
     */
    public function fetch_lesson_info($data) {
        return html_writer::div($data, 'mod_simplelesson_data');
    }

    /**
     * Show the current page.
     *
     * @param object $data object instance of current page
     * @return string html representation of page object
     */
    public function show_page($data) {

        $html = '';
        // Show page content.
        $html .= html_writer::start_div('mod_simplelesson_content');
        $html .= $this->output->heading($data->pagetitle, 4);
        $html .= $data->pagecontents;
        $html .= html_writer::end_div();
        return $html;
    }
    /**
     * Returns HTML to display a report tab
     *
     * @param int $courseid - course id
     * @param int $simplelessonid - course module id
     * @return string, a set of tabs
     */
    public function show_reports_tab($courseid, $simplelessonid) {

        $tabs = $row = $inactive = $activated = array();
        $currenttab = '';
        $viewpage = new moodle_url('/mod/simplelesson/view.php',
        array('simplelessonid' => $simplelessonid));
        $reportspage = new moodle_url('/mod/simplelesson/reports.php',
        array('courseid' => $courseid, 'simplelessonid' => $simplelessonid));

        $row[] = new tabobject('view', $viewpage,
                get_string('viewtab', 'mod_simplelesson'));
        $row[] = new tabobject('reports', $reportspage,
                get_string('reportstab', 'mod_simplelesson'));

        $tabs[] = $row;

        print_tabs($tabs, $currenttab, $inactive, $activated);

    }
    /**
     * Returns HTML to a basic report
     *
     * @param array $records - a set of module fields
     * @return string html table
     */
    public function show_basic_report($records) {

        $table = new html_table();
        $table->head = array(
                get_string('moduleid', 'mod_simplelesson'),
                get_string('simplelessonname', 'mod_simplelesson'),
                get_string('title', 'mod_simplelesson'),
                get_string('timecreated', 'mod_simplelesson'));
        $table->align = array('left', 'left', 'left');
        $table->wrap = array('nowrap', '', 'nowrap');
        $table->tablealign = 'left';
        $table->cellspacing = 0;
        $table->cellpadding = '2px';
        $table->width = '80%';
        foreach ($records as $record) {
            $data = array();
            $data[] = $record->id;
            $data[] = $record->name;
            $data[] = $record->title;
            $data[] = $record->timecreated;
            $table->data[] = $data;
        }

        return html_writer::table($table);
    }

    /**
     * Returns the html for the page index
     * module's instance settings page
     * @param string array $pagelinks for the lesson page index
     * @return string html to display the links.
     */
    public function fetch_index($pagelinks) {

        $html = html_writer::start_div(
                'mod_simplelesson_page_index_container');
        $html .= $this->output->heading(
                get_string('page_index_header', 'mod_simplelesson'), 4, 'main');
        $html .= html_writer::start_div('mod_simplelesson_page_index');
        $html .= html_writer::alist($pagelinks, null, 'ul');
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();

        return $html;
    }
    /**
     *
     * Render the question form on a page
     *
     * @param moodle_url $actionurl - form action url
     * @param array mixed $options - question display options
     * @param int $slot - slot number for question usage
     * @param object $quba - question usage object
     * @param int $starttime, time question was first presented to user
     * @param string $qtype, the question type - to identify an essay
     * @return string, html representation of the question
     */
    public function render_question_form(
            $actionurl, $options, $slot, $quba,
            $starttime, $qtype) {

        $html = html_writer::start_div('mod_simplelesson_question');
        $headtags = '';
        $headtags .= $quba->render_question_head_html($slot);

        // Start the question form.
        $html .= html_writer::start_tag('form',
                array('method' => 'post', 'action' => $actionurl,
                'enctype' => 'multipart/form-data',
                'accept-charset' => 'utf-8',
                'id' => 'responseform'));
        $html .= html_writer::start_tag('div');
        $html .= html_writer::empty_tag('input',
                array('type' => 'hidden',
                'name' => 'sesskey', 'value' => sesskey()));
        $html .= html_writer::empty_tag('input',
                array('type' => 'hidden',
                'name' => 'slots', 'value' => $slot));
        $html .= html_writer::empty_tag('input',
                array('type' => 'hidden',
                'name' => 'starttime', 'value' => $starttime));
        $html .= html_writer::end_tag('div');

        // Output the question. slot = display number.
        $html .= $quba->render_question($slot, $options);

        // If it's an essay question, output a save button.
        // If it's deferred feedback add a save button.

        if ( ($qtype == 'essay') || ($quba->get_preferred_behaviour()
                == 'deferredfeedback') ){
            $html .= html_writer::start_div(
                    'mod_simplelesson_save_button');
            $label = ($qtype == 'essay') ?
                    get_string('saveanswer', 'mod_simplelesson') :
                    get_string('save', 'mod_simplelesson');
            $html .= $this->output->single_button($actionurl,
                    $label);
            $html .= html_writer::end_div();
        }

        // Finish the question form.
        $html .= html_writer::end_tag('form');
        $html .= html_writer::end_div('div');
        return $html;
    }
    /**
     *
     * Output the details of the attempt
     *
     * @param object array $answerdata an array of data
     *        relating to user responses to questions.
     * @param int $markdp - numer of decimal places in mark
     * @return $html table with summary data on user's attempt
     */
    public function lesson_summary($answerdata, $markdp) {

        $table = new html_table();

        $table->head = array(
        get_string('question', 'mod_simplelesson'),
        get_string('pagetitle', 'mod_simplelesson'),
        get_string('rightanswer', 'mod_simplelesson'),
        get_string('youranswer', 'mod_simplelesson'),
        get_string('mark', 'mod_simplelesson'),
        get_string('outof', 'mod_simplelesson'),
        get_string('timetaken', 'mod_simplelesson'));

        $table->align =
                array('left', 'left', 'left',
                'left', 'right', 'right', 'left');
        $table->wrap = array('', '', '', '', '', '', '');
        $table->tablealign = 'center';
        $table->cellspacing = 0;
        $table->cellpadding = '2px';
        $table->width = '80%';
        $table->data = array();
        foreach ($answerdata as $answer) {
            $data = array();
            $data[] = $answer->question;
            $data[] = $answer->pagename;
            $data[] = $answer->rightanswer;
            $data[] = $answer->youranswer;
            $mark = round($answer->mark, $markdp);
            if ($mark < 0) {
                // Question not yet graded (eg essay).
                $data[] = get_string('ungraded', 'mod_simplelesson');
            } else {
                $data[] = $mark;
            }
            $data[] = round($answer->maxmark, $markdp);
            $data[] = $answer->timetaken;
            $table->data[] = $data;
        }

        return html_writer::table($table);
    }
    /**
     * Returns the html for attempt summary page
     * @param object $sessiondata - score, maxscore and time
     * @return string, html to show summary
     */
    public function show_summary_data($sessiondata) {

        $html = '<p>';
        $html .= get_string('summary_score', 'mod_simplelesson',
            $sessiondata->score) . ' ('
            . $sessiondata->maxscore . ') | ';
        $html .= get_string('summary_time', 'mod_simplelesson',
            $sessiondata->stime) . '</p>';
        return $html;
    }
    /**
     * Returns the html which heads up the manual grading page
     *
     * @param $object $answerdata, the relevant users answer data
     * @return string $html
     */
    public function grading_header($answerdata) {

        $html = html_writer::start_div('mod_simplelesson_grading_header');

        $html .= $this->output->heading(get_string('essay_grading',
                'mod_simplelesson'), 2);
        $html .= get_string('essay_grading_page', 'mod_simplelesson')
                . '<br /><br />';

        $html .= '<p>'. get_string('userdetail', 'mod_simplelesson',
                $answerdata->firstname . ' ' . $answerdata->lastname)
                . '</p>';
        $html .= '<p>'. get_string('essaydate', 'mod_simplelesson',
                date("Y-m-d H:i:s", $answerdata->timecompleted))
                . '</p>';
        $html .= '<p>'. get_string('maxmark', 'mod_simplelesson',
                (int) $answerdata->maxmark) . '</p>';

        $html .= html_writer::end_div();

        return $html;
    }
    /**
     * Return the html for the essay text
     *
     * @param $object $text, the relevant users answer
     * @return string $html
     */
    public function essay_text($text) {

        $html = html_writer::start_div('mod_simplelesson_essay_text');
        $html .= $text;
        $html .= html_writer::end_div();

        return $html;
    }
}