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
 * Set of display options for simplelesson question
 * From previewlib.php and re-written for this filter
 *
 * @package    mod_simplelesson
 * @copyright  2010 The Open University
 * Modified by Richard Jones http://richardnz/net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_simplelesson\local;
require_once('../../config.php');
require_once($CFG->libdir . '/questionlib.php');
require_once('../../question/previewlib.php');
require_once('../../question/engine/lib.php');
defined('MOODLE_INTERNAL') || die();
/**
 * Control question display options
 */
class display_options  {
    /**
     * Set the display options for a question
     * @param int $maxvariant The maximum number of variants previewable.
     * @return array $options the display options
     */
    public static function get_options($feedback) {
        $options = array();
        // Question options - note just 1 question in the attempt.
        $options = new \question_display_options();
        $options->marks = \question_display_options::MAX_ONLY;
        $options->markdp = 2; // Mark display.
        $options->feedback = $feedback;
        $options->generalfeedback = \question_display_options::HIDDEN;
        $options->variant = 100;

        return $options;
    }
}