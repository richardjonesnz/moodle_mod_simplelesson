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
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_simplelesson
 * @copyright 2015 Justin Hunt, modified 2018 Richard Jones https://richardnz.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// General details
$string['modulename'] = 'Simple lesson';
$string['modulenameplural'] = 'Simple lessons';
$string['modulename_help'] = 'Simple lesson help';
$string['pluginname'] = 'Simple lesson';
$string['simplelesson'] = 'simplelesson';
$string['pluginadministration'] = 'Simple lesson Administration';

// Instance settings
// $string['simplelessonfieldset'] = 'Custom example fieldset';
$string['simplelessonname'] = 'Lesson name';
$string['simplelessonname_help'] = 'Required: the name of this Simple lesson.';
$string['simplelessonsettings'] = 'Simple lesson settings';
$string['firstpage'] ='Lesson introduction';
$string['firstpage_help'] ='Add some snappy text and any necessary instructions here';
$string['defaultfirstpagetext'] = 'Introductory text for the first page';
$string['lessontitle'] = 'Lesson title';
$string['lessontitle_help'] = 'Lesson title - appears on first page only';
$string['category'] = 'Select question category';
$string['category_help'] = 'You can use only one category per simplelesson, if necessary, create a special category for this simple lesson';
$string['category_select'] = 'Select question category';
$string['maxattempts'] ='Max. Attempts';
$string['unlimited'] ='unlimited';
$string['show_index'] ='Display page index';
$string['show_index_text'] ='Will display if checked';
$string['edit_settings'] ='Edit settings';

// Admin settings page
$string['someadminsetting'] = 'Some Admin Setting';
$string['someadminsetting_details'] = 'More info about Some Admin Setting';

$string['simplelesson:addinstance'] = 'Add a new simplelesson';
$string['simplelesson:view'] = 'View simplelesson';
$string['simplelesson:preview'] = 'Preview simplelesson';
$string['simplelesson:itemview'] = 'View items';
$string['simplelesson:itemedit'] = 'Edit items';

// First page and page editing strings
$string['addpage'] = 'Add a page';
$string['gotofirstpage'] = 'Continue the lesson';
$string['gotonextpage'] = 'Next page';
$string['gotoprevpage'] = 'Previous page';
$string['nopages'] = 'There are no pages yet, add a page';
$string['has_pages'] = 'There are {$a} pages in the lesson';
$string['editingpage'] = 'Simple lesson page editor';
$string['pagetitle'] = 'Page title';
$string['pagecontents'] = 'Page content';
$string['nopagenumber'] = 'Invalid page specified';
$string['page_editing'] = 'Editing page';
$string['gotoeditpage'] = 'Edit page';
$string['gotoaddpage'] = 'Add page';
$string['gotodeletepage'] = 'Delete page';
$string['page_adding'] = 'Adding page';
$string['page_saved'] = 'Page saved';
$string['page_updated'] = 'Page updated';
$string['page_deleted'] = 'Page deleted';
$string['page_index_header'] = 'Index of pages:';
$string['homelink'] = 'Home page';
$string['getnextpage'] = 'Select link to next page';
$string['getprevpage'] = 'Select link to previous page';
$string['afterpage'] = 'Add after page';
$string['nolink'] = 'None';

// Lesson editing (page management)
$string['edit_lesson'] = 'Simple Lesson editing';
$string['lesson_editing'] = 'Editing a Simple lesson';
$string['manage_pages'] = 'Manage lesson pages';
$string['pagetitle'] = 'page title';
$string['nextpage'] = 'Next page';
$string['prevpage'] = 'Previous page';
$string['actions'] = 'Actions';
$string['showpage'] = 'Preview page';
$string['sequence'] = 'Sequence';
$string['move_up'] = 'Move page up';
$string['move_down'] = 'Move page down';

// Question selection and placement (question management)
$string['manage_questions'] = 'Manage lesson questions';
$string['add_question'] = 'Add question';
$string['question_adding'] = 'Adding a question';
$string['questions_added'] = 'Questions added';
$string['question_select'] = 'Select question';
$string['select_questions'] = 'Check the questions you wish to include in this lesson';
$string['qnumber'] = '#';
$string['question_name'] = 'Question name';
$string['question_text'] = 'Question text';
$string['question_editing'] = 'Manage questions';
$string['pagetitle'] = 'Page title';
$string['pagetitle_help'] = 'Select the page title where you want to place this question (one question per page only).';
$string['selecting_page'] = 'Select page';
$string['editing_question'] = 'Select the page for this question: {$a}';
$string['setpage'] = 'Set page';
$string['pagehasquestion'] = 'Another page already has this question'; 

$string['id']='ID';
$string['name']='Name';
$string['timecreated']='Time Created';
$string['basicheading']='Basic Report';
$string['overview']='Overview';
$string['overview_help']='Overview Help';
$string['view']='View';
$string['preview']='Preview';
$string['viewreports']='View Reports';
$string['reports']='Reports';
$string['basicreport']='Basic Report';
$string['returntoreports']='Return to Reports';
$string['exportexcel']='Export to CSV';
$string['mingradedetails'] = 'The minimum grade required to "complete" this activity.';
$string['mingrade'] = 'Minimum Grade';
$string['deletealluserdata'] = 'Delete all user data';

$string['gradeoptions'] ='Grade Options';
$string['gradenone'] ='No grade';
$string['gradelowest'] ='lowest scoring attempt';
$string['gradehighest'] ='highest scoring attempt';
$string['gradelatest'] ='score of latest attempt';
$string['gradeaverage'] ='average score of all attempts';
$string['defaultsettings'] ='Default Settings';
$string['exceededattempts'] ='You have completed the maximum {$a} attempts.';
$string['simplelessontask'] ='simplelesson Task';
$string['enablereports'] ='Enable Reports';
$string['enablereset'] ='Enable Reset';
$string['enablereports_desc'] ='';
$string['enablereset_desc'] ='';
