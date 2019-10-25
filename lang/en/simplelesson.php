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
 * English strings for simplelesson
 *
 * @package    mod_simplelesson
 * @copyright  2018 Richard Jones <richardnz@outlook.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @see https://github.com/moodlehq/moodle-mod_newmodule
 *
 */

defined('MOODLE_INTERNAL') || die();

// General module strings.
$string['modulename'] = 'Simple lesson';
$string['modulenameplural'] = 'Simple lessons';
$string['modulename_help'] = 'Use the Simple lesson module for a simple sequential display of pages with an optional index.
 The simplelesson module allows the creation and addition of multiple pages of content.
It allows the use of questions from a selected question bank.  User attempt data is recorded.';
$string['simplelessonfieldset'] = 'Custom example fieldset';
$string['simplelessonname'] = 'Simple lesson name';
$string['simplelessonname_help'] = 'Choose a suitable name for your Simple lesson.';
$string['pluginadministration'] = 'simplelesson administration';
$string['pluginname'] = 'Simple lesson';

// Events.
$string['simplelessonviewed'] = 'Simple lesson viewed';
$string['pagecreated'] = 'New page created';
$string['pageviewed'] = 'Simple lesson page viewed';
$string['pagedeleted'] = 'Simplelesson page deleted';
$string['attemptstarted'] = 'Simple lesson attempt started';
$string['attemptcompleted'] = 'Simple lesson attempt started';

// The mod_form settings.
$string['simplelesson_settings'] = 'Simplelesson settings';
$string['simplelesson_title'] = 'Title of this resource';
$string['showindex'] = 'Show the page index';
$string['showindex_help'] = 'The page index is optional and will show on the top right of every content page by default. This can be overriden by themes.';
$string['immediatefeedback'] = 'Immediate feedback';
$string['immediatecbm'] = 'Immediate feedback with CBM';
$string['adaptive'] = 'Adaptive feedback';
$string['behaviour'] = 'Question behaviour';
$string['behaviour_help'] = 'Immediate feedback has one attempt available and with CBM a certainty must be implemented as well.  With Adaptive feedback the user has multiple tries at each question.  This can only be set on a lesson basis, not per question';
$string['category_select'] = 'Select category';
$string['categoryid'] = 'Category';
$string['categoryid_help'] = 'Choose one category for the questions that can be added to this simplelesson.  You can return here later and add other categories from module settings.';
$string['nocategory'] = 'none';
$string['unlimited'] = 'Unlimited';

// Capabilities.
$string['simplelesson:manage'] = 'Manage Simple lesson';
$string['simplelesson:manageattempts'] = 'Manage attempt records';
$string['simplelesson:addinstance'] = 'Add a new Simple lesson';
$string['simplelesson:viewreports'] = 'View the reports tab';
$string['simplelesson:view'] = 'View Simple lesson';
$string['simplelesson:exportreportpages'] = 'Export report pages';
$string['simplelesson:exportpages'] = 'Export report pages';
$string['simplelesson:importpages'] = 'Export report pages';

// Page management.
$string['simplelesson_editing'] = 'Editing Simple lesson';
$string['manage_pages'] = 'Page management';
$string['sequence'] = 'Sequence';
$string['title'] = 'Page title';
$string['nextpage'] = 'Next page';
$string['prevpage'] = 'Previous page';
$string['actions'] = 'Actions';
$string['move_up'] = 'Move up';
$string['move_down'] = 'Move down';
$string['showpage'] = 'Preview page';
$string['autosequencelink'] = 'Auto-sequence pages';
$string['sequence_updated'] = 'Page sequences updated';
$string['hasquestion'] = 'Has question';
$string['exportpagelink'] = 'Export pages';
$string['importpagelink'] = 'Import pages';


// Question management.
$string['manage_questions'] = 'Manage questions';
$string['question_editing'] = 'Editing questions';
$string['qnumber'] = 'Question number';
$string['question_name'] = 'Question name';
$string['question_text'] = 'Question text';
$string['setpage'] = 'Page for question';
$string['add_question'] = 'Add question';
$string['question_adding'] = 'Adding questions to Simple lesson';
$string['questions_added'] = 'Added questions';
$string['select_questions'] = 'Select questions';
$string['selecting_page'] = 'Select the page for this question.';
$string['editing_question_page'] = 'Select the page';
$string['question_exists'] = 'That page has a question already';

// Page editing.
$string['edit_page'] = 'Edit page';
$string['add_page'] = 'Add page';
$string['delete_page'] = 'Delete page';
$string['page_adding'] = 'Add a new page';
$string['page_saved'] = 'Page saved';
$string['pagetitle'] = 'Title of the page';
$string['pagecontents'] = 'page content';
$string['getprevpage'] = 'Previous page';
$string['getnextpage'] = 'Next page';
$string['nolink'] = 'none';
$string['no_pages'] = 'There are no pages yet, add a page';
$string['numpages'] = 'Number of pages: {$a}';
$string['gotoaddpage'] = 'Add page';
$string['gotoeditpage'] = 'Edit page';
$string['gotodeletepage'] = 'Delete page';
$string['page_editing'] = 'Editing page';
$string['page_updated'] = 'Page updated';
$string['page_deleted'] = 'Page deleted';

// Page navigation.
$string['firstpagelink'] = 'First page';
$string['homelink'] = 'Home';
$string['gotonextpage'] = 'Next';
$string['gotoprevpage'] = 'Previous';
$string['page_index_header'] = 'Index';
$string['preview_completed'] = 'preview completed';

// Reporting.
$string['moduleid'] = 'id';
$string['viewtab'] = 'view';
$string['reportstab'] = 'reports';
$string['timecreated'] = 'Time created';
$string['basic_report'] = 'Basic report';
$string['answer_report'] = 'User responses';
$string['attempt_report'] = 'User attempts';
$string['date'] = 'Attempt date';
$string['lessonname'] = 'Lesson';
$string['firstname'] = 'First name';
$string['lastname'] = 'Last name';
$string['status'] = 'Status';
$string['sessionscore'] = 'Correct';
$string['maxscore'] = 'Out of';
$string['timetaken'] = 'Time taken (s)';
$string['userreportdownload'] = 'Download user report (csv)';
$string['attemptid'] = "Attempt";
$string['questionsummary'] = 'Question';
$string['action'] = 'Action';

// Admin settings.
$string['enablereports'] = 'Show reports tab';
$string['enablereports_desc'] = 'Check to allow teachers to see reports';
$string['enableindex'] = 'Show page index';
$string['enableindex_desc'] = 'Check to show page index';

// Attempts.
$string['maxattempts'] = "Max attempts";
$string['gotosummary'] = "Summary page";
$string['end_lesson'] = "Exit lesson";
$string['preview'] = "Preview";
$string['attempt'] = "Attempt lesson";
$string['starting_attempt'] = "Starting Attempt";
$string['previewcompleted'] = "Preview completed";
$string['summary_header'] = "Attempt summary";
$string['summary_user'] = 'User report for {$a}';
$string['summary_score'] = 'Score for this attempt: {$a}';
$string['summary_time'] = 'Total time for this attempt: {$a} (Seconds)';
$string['firstname'] = "First name";
$string['lastname'] = "Last name";
$string['question'] = "Question";
$string['rightanswer'] = "Right answer";
$string['youranswer'] = "Your answer";
$string['mark'] = "Mark";
$string['timetaken'] = "Time (s)";
$string['max_attempts_exceeded'] = "No more attempts allowed";
$string['numattempts'] = 'Attempts made: {$a} of';
$string['unlimited_attempts'] = 'Unlimited attempts';
$string['no_questions'] = 'There are no questions to attempt (use preview)';
$string['finishreview'] = 'Finish review';
$string['answerquestions'] = 'Please answer all the questions';

// Attempts management.
$string['manage_attempts'] = 'Manage attempts';
$string['delete'] = 'delete ';
$string['attempt_deleted'] = 'Attempt deleted';
$string['attempt_not_deleted'] = 'Attempt to delete record failed';

// Privacy.
$string['privacy:metadata:simplelesson_attempts'] = 'Information about users simplelesson attempts including completion status, the number of correct responses (a score) and the time taken (seconds) for the attempt.';
$string['privacy:metadata:simplelesson_attempts:userid'] = 'The id of the user taking the attempt.';
$string['privacy:metadata:simplelesson_attempts:status'] = 'The completion status of the attempt.';
$string['privacy:metadata:simplelesson_attempts:sessionscore'] = 'The score achieved on the attempt.';
$string['privacy:metadata:simplelesson_attempts:timetaken'] = 'The timetaken to complete the attempt (seconds).';

$string['privacy:metadata:simplelesson_answers'] = 'Information about users simplelesson attempts at individual questions including their response and a score.';
$string['privacy:metadata:simplelesson_answers:mark'] = 'The score of the user answering the question.';
$string['privacy:metadata:simplelesson_answers:youranswer'] = 'The response of the user to the question.';
