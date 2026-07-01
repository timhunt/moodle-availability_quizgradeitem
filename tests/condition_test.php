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

namespace availability_quizgradeitem;

use mod_quiz\external\create_grade_item_per_section;
use mod_quiz\quiz_attempt;
use mod_quiz\quiz_settings;

/**
 * Unit tests for the condition class.
 *
 * @package availability_quizgradeitem
 * @copyright 2026 Tim Hunt, Dustin Schiele, Andreas Steiger and Christine Lent
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(condition::class)]
final class condition_test extends \advanced_testcase {
    public function test_constructor_min_only(): void {
        $cond = new condition((object) [
            'quizid' => 123,
            'quizgradeitemid' => 456,
            'min' => 2.0,
        ]);
        $this->assertEquals(
            '{quizgradeitem: quiz: #123, quizgradeitemid: #456, min: 2}',
            (string) $cond,
        );
    }

    public function test_constructor_max_only(): void {
        $cond = new condition((object) [
            'quizid' => 123,
            'quizgradeitemid' => 456,
            'max' => 3.14,
        ]);
        $this->assertEquals(
            '{quizgradeitem: quiz: #123, quizgradeitemid: #456, max: 3.14}',
            (string) $cond,
        );
    }

    public function test_constructor_range(): void {
        $cond = new condition((object) [
            'quizid' => 123,
            'quizgradeitemid' => 456,
            'min' => 2,
            'max' => 3.14,
        ]);
        $this->assertEquals(
            '{quizgradeitem: quiz: #123, quizgradeitemid: #456, min: 2, max: 3.14}',
            (string) $cond,
        );
    }

    public function test_constructor_invalid_quizid(): void {
        $this->expectExceptionMessage('Invalid quizid for quizgradeitem condition.');
        new condition((object) [
                'quizid' => 'wrong', 'quizgradeitemid' => 456, 'min' => 2]);
    }

    public function test_constructor_invalid_quizgradeitemid(): void {
        $this->expectExceptionMessage('Invalid quizgradeitemid for quizgradeitem condition.');
        new condition((object) [
                'quizid' => 123, 'quizgradeitemid' => 'wrong', 'min' => 2]);
    }

    public function test_constructor_invalid_min(): void {
        $this->expectExceptionMessage('Invalid ->min for quizgradeitem condition.');
        new condition((object) [
            'quizid' => 123,
            'quizgradeitemid' => 456,
            'min' => 'wrong',
            'max' => 3.14,
        ]);
    }

    public function test_constructor_invalid_max(): void {
        $this->expectExceptionMessage('Invalid ->max for quizgradeitem condition.');
        new condition((object) [
            'quizid' => 123,
            'quizgradeitemid' => 456,
            'min' => 2,
            'max' => 'wrong',
        ]);
    }

    public function test_constructor_no_range(): void {
        $this->expectExceptionMessage('Either ->min or ->max must be set for a quizgradeitem condition.');
        new condition((object) [
            'quizid' => 123,
            'quizgradeitemid' => 456,
        ]);
    }

    public function test_save_min_only(): void {
        $structure = (object) [
            'quizid' => 123,
            'quizgradeitemid' => 456,
            'min' => 2,
        ];
        $cond = new condition($structure);
        $structure->type = 'quizgradeitem';
        $this->assertEquals($structure, $cond->save());
    }

    public function test_save_max_only(): void {
        $structure = (object) [
            'quizid' => 123,
            'quizgradeitemid' => 456,
            'max' => 3.14,
        ];
        $cond = new condition($structure);
        $structure->type = 'quizgradeitem';
        $this->assertEquals($structure, $cond->save());
    }

    public function test_save_range(): void {
        $structure = (object) [
            'quizid' => 123,
            'quizgradeitemid' => 456,
            'min' => 2,
            'max' => 3.14,
        ];
        $cond = new condition($structure);
        $structure->type = 'quizgradeitem';
        $this->assertEquals($structure, $cond->save());
    }

    public function test_usage_range(): void {
        [$student, $course, $quizobj] = $this->create_quiz_in_course_with_student();
        $structure = $quizobj->get_structure();
        $gradeitems = array_values($structure->get_grade_items());
        $gradeitem = reset($gradeitems);

        $info = new \core_availability\mock_info($course, $student->id);

        // Test grade must be in a range.
        $cond = new condition((object)[
            'quizid' => (int) $quizobj->get_quizid(),
            'quizgradeitemid' => (int) $gradeitem->id,
            'min' => 2,
            'max' => 3.14,
        ]);

        // Not available because not attempt yet.
        $this->assertFalse($cond->is_available(false, $info, true, $student->id));
        $this->assertEquals(
            'You receive a score between <strong>2.00</strong> and <strong>3.14</strong> for ' .
            '<strong>New grade item 1</strong> in <strong>Quiz 1</strong>',
            $info->format_info($cond->get_description(false, false, $info), $course),
        );

        // Check with not.
        $this->assertTrue($cond->is_available(true, $info, true, $student->id));
        $this->assertEquals(
            'You receive a score outside the range <strong>2.00</strong> to <strong>3.14</strong> for ' .
            '<strong>New grade item 1</strong> in <strong>Quiz 1</strong>',
            $info->format_info($cond->get_description(false, true, $info), $course),
        );

        // User attempts the quiz and get the question right.
        $this->attempt_quiz($quizobj, $student, true);

        // Recheck - still not a pass, grade is out of range.
        $this->assertFalse($cond->is_available(false, $info, true, $student->id));
        $this->assertTrue($cond->is_available(true, $info, true, $student->id));
        $this->assertEquals(
            'You receive a score outside the range <strong>2.00</strong> to <strong>3.14</strong> for ' .
            '<strong>New grade item 1</strong> in <strong>Quiz 1</strong>',
            $info->format_info($cond->get_description(false, true, $info), $course),
        );
    }

    public function test_usage_min(): void {
        [$student, $course, $quizobj] = $this->create_quiz_in_course_with_student();
        $structure = $quizobj->get_structure();
        $gradeitems = array_values($structure->get_grade_items());
        $gradeitem = reset($gradeitems);

        $info = new \core_availability\mock_info($course, $student->id);

        // Case where grade must be above a level.
        $cond = new condition((object) [
            'quizid' => (int) $quizobj->get_quizid(),
            'quizgradeitemid' => (int)$gradeitem->id,
            'min' => 0.5,
        ]);

        // Not available because not attempt yet.
        $this->assertFalse($cond->is_available(false, $info, true, $student->id));
        $this->assertEquals(
            'You receive a score at least <strong>0.50</strong> for ' .
            '<strong>New grade item 1</strong> in <strong>Quiz 1</strong>',
            $info->format_info($cond->get_description(false, false, $info), $course),
        );

        // User attempts the quiz and get the question wrong.
        $this->attempt_quiz($quizobj, $student, false);

        // Not available because score too low.
        $this->assertFalse($cond->is_available(false, $info, true, $student->id));
        $this->assertTrue($cond->is_available(true, $info, true, $student->id));
        $this->assertEquals(
            'You receive a score at least <strong>0.50</strong> for ' .
            '<strong>New grade item 1</strong> in <strong>Quiz 1</strong>',
            $info->format_info($cond->get_description(false, false, $info), $course),
        );

        // User attempts the quiz again and get the question right.
        $this->attempt_quiz($quizobj, $student, true);

        // TODO fix this - clear caches.
        $cache = \cache::make('availability_quizgradeitem', 'scores')->purge();

        // Now available because score is good enough.
        $this->assertTrue($cond->is_available(false, $info, true, $student->id));
        $this->assertFalse($cond->is_available(true, $info, true, $student->id));
        $this->assertEquals(
            'You receive a score at least <strong>0.50</strong> for ' .
            '<strong>New grade item 1</strong> in <strong>Quiz 1</strong>',
            $info->format_info($cond->get_description(false, false, $info), $course),
        );
    }

    /**
     * Create a common test setup.
     *
     * @return array [$student, $course, $quizobj]
     */
    protected function create_quiz_in_course_with_student(): array {
        global $CFG;
        require_once($CFG->dirroot . '/availability/tests/fixtures/mock_info.php');

        $this->resetAfterTest();
        $this->setAdminUser();
        $CFG->enableavailability = true;

        // Make a test course and user.
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $context = \context_course::instance($course->id);
        $student = $generator->create_user();
        $generator->enrol_user($student->id, $course->id);

        // Create a quiz with a question.
        $quiz = $generator->create_module('quiz', ['course' => $course->id]);
        /** @var \core_question_generator $questiongenerator */
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $category = $questiongenerator->create_question_category(
            ['contextid' => $context->id]);
        $question = $questiongenerator->create_question('numerical', null,
            ['category' => $category->id]);
        $question = \question_bank::load_question_data($question->id); // Reload to get questionbankentryid.
        quiz_add_quiz_question($question->id, $quiz);
        $quizobj = \mod_quiz\quiz_settings::create($quiz->id, $student->id);
        $quizobj->get_grade_calculator()->recompute_quiz_sumgrades();
        create_grade_item_per_section::execute($quizobj->get_quizid());

        return [$student, $course, $quizobj];
    }

    /**
     * Create an attempt for a student at a quiz, assumes one numerical question.
     *
     * @param quiz_settings $quizobj
     * @param \stdClass $student
     * @param bool $right
     */
    protected function attempt_quiz(quiz_settings $quizobj, \stdClass $student, bool $right): void {
        $this->setUser($student);
        $attempt = $this->getDataGenerator()->get_plugin_generator('mod_quiz')
            ->create_attempt($quizobj->get_quizid(), $student->id);
        $attemptobj = quiz_attempt::create($attempt->id);
        $attemptobj->process_submitted_actions(
            time(),
            false,
            [1 => ['answer' => $right ? '3.14' : '2']],
        );
        $attemptobj->process_submit(time(), false);
        $attemptobj->process_grade_submission(time());
    }
}
