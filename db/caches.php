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

defined('MOODLE_INTERNAL') || die();

/**
 * Cache definitions.
 *
 * @package availability_quizgradeitem
 * @copyright 2026 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
$definitions = [
    // Used to cache the necessary info about quizzes in a particular course.
    // Keys course id. Value structure documented in the PHPdoc for
    // \availability_quizgradeitem\condition::get_cached_quiz_info().
    'quizinfo' => [
        'mode' => cache_store::MODE_APPLICATION,
        'staticacceleration' => true,
        'staticaccelerationsize' => 2, // Likely only needed for the quizzes in one course at a time.
        'ttl' => 3600,
    ],

    // Used to cache user part scores for conditional availability purposes.
    // Key is userid. Value structure documented in the PHPdoc for
    // \availability_quizgradeitem\condition::get_cached_quiz_grade_item_score().
    'scores' => [
        'mode' => cache_store::MODE_APPLICATION,
        'staticacceleration' => true,
        'staticaccelerationsize' => 2, // Likely only needed for one user at a time.
        'ttl' => 3600,
    ],
];
