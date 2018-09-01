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
 * Determine grade according to grade options selected in mod form.
 *
 * @package    mod_simplelesson
 * @copyright  2018 Richard Jones http://richardnz/net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use \mod_simplelesson\local\constants;
use \mod_simplelesson\local\display_options;
namespace mod_simplelesson\local;
defined('MOODLE_INTERNAL') || die();

/**
 * Control grade via grade options
 */
class grading {
    /**
     * Calculate user grade.
     * @param object $cm course module instance
     * @return array $attempts attempt data from lib get_user_grades
     */
    public static function grade_user($cm, $attempts) {
        global $DB;

        if (!$attempts) { return 0; }
        $options = new display_options();

        // Grading methods are in instance settings.
        switch($cm->grademethod) {

            case constants::MOD_SIMPLELESSON_GRADE_HIGHEST:
                $maxscore = 0;
                foreach ($attempts as $attempt) {
                    $attemptscore = $attempt->sessionscore;
                    $maxscore = ($attemptscore > $maxscore) ?
                            $attemptscore : $maxscore;
                }
                return $maxscore;
            break;

            case constants::MOD_SIMPLELESSON_GRADE_AVERAGE:
                $score = 0.0;
                foreach ($attempts as $attempt) {
                    $score += $attempt->sessionscore;
                }
                $n = count($attempts);
                \mod_simplelesson\local\debugging::logit('grade average ',
                        $score);
                return $score / $n;
            break;

            case constants::MOD_SIMPLELESSON_GRADE_LAST:
                $latest = 0;
                foreach ($attempts as $attempt) {
                    $time = $attempt->timecreated;
                    if ($time > $latest) {
                      $latest = $time;
                      $score = $attempt->sessionscore;
                    }
                }
                return $score;
        }
    }
}