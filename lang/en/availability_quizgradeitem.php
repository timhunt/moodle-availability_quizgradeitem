<?php
// This file is part of Moodle - https://moodle.org/
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
 * Restriction by quiz part score language strings.
 *
 * @package   availability_quizgradeitem
 * @category  string
 * @copyright 2026 Tim Hunt, Dustin Schiele, Andreas Steiger and Christine Lent
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['cachedef_quizinfo'] = 'Quiz information for evaluating conditional availability';
$string['cachedef_scores'] = 'User quiz grade sores cached for evaluating conditional availability';
$string['description'] = 'This plugin allows you to limit access to another Moodle activity based just on the score a student got for just some of the sections in a quiz.';
$string['error_backwardrange'] = 'When specifying a grade range, the minimum must be lower than the maximum.';
$string['error_selectgradeitem'] = 'You must select a quiz grade item.';
$string['error_selectquiz'] = 'You must select a quiz.';
$string['label_gradeitem'] = 'Which part grade';
$string['label_max'] = 'Maximum grade percentage (exclusive)';
$string['label_min'] = 'Minimum grade percentage (inclusive)';
$string['missinggradeitem'] = '(missing grade item)';
$string['option_max'] = 'must be <';
$string['option_min'] = 'must be ≥';
$string['pluginname'] = 'Restriction by quiz part score';
$string['privacy:metadata'] = 'The Restriction by quiz part score plugin does not store any personal data.';
$string['requires_max'] = 'You receive a score lower than <strong>{$a->max}</strong> for <strong>{$a->quizgradeitem}</strong> in <strong>{$a->quizname}</strong>';
$string['requires_min'] = 'You receive a score at least <strong>{$a->min}</strong> for <strong>{$a->quizgradeitem}</strong> in <strong>{$a->quizname}</strong>';
$string['requires_notmax'] = 'You receive a score at least <strong>{$a->min}</strong> for <strong>{$a->quizgradeitem}</strong> in <strong>{$a->quizname}</strong>';
$string['requires_notmin'] = 'You receive a score lower than <strong>{$a->max}</strong> for <strong>{$a->quizgradeitem}</strong> in <strong>{$a->quizname}</strong>';
$string['requires_notrange'] = 'You receive a score outside the range <strong>{$a->min}</strong> to <strong>{$a->max}</strong> for <strong>{$a->quizgradeitem}</strong> in <strong>{$a->quizname}</strong>';
$string['requires_range'] = 'You receive a score between <strong>{$a->min}</strong> and <strong>{$a->max}</strong> for <strong>{$a->quizgradeitem}</strong> in <strong>{$a->quizname}</strong>';
$string['title'] = 'Quiz part grade';
