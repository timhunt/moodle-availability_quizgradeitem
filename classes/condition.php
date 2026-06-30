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

/**
 * Restriction by quiz part score condition main class.
 *
 * The configuration for this plugin is this JSON structure:
 * {
 *     "quizid": 123, -- the id of the quiz this depends on.
 *     "gradeitemid": 4567 -- the id of a quiz_grade_item in that quiz this depends on.
 *     "min": 2.0 -- optional, lowest accepted grade (>=)
 *     "max": 7.2 -- optional, highest accepted grade (<)
 * }
 * at least one of min and max must be set.
 *
 * @package availability_quizgradeitem
 * @copyright 2026 Tim Hunt, Dustin Schiele, Andreas Steiger and Christine Lent
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class condition extends \core_availability\condition {
    /** @var int the id of the quiz this depends on. */
    protected $quizid;

    /** @var int the id of the question bank entry in the quiz that this depends on. */
    protected $quizgradeitemid;

    /** @var float|null the minimum grade (must be >= this) or null if none. */
    protected ?float $min;

    /** @var float|null the maximum grade (must be < this) or null if none. */
    protected ?float $max;

    /**
     * Constructor.
     *
     * @param \stdClass $structure Data structure from JSON decode (as in class comment).
     * @throws \coding_exception If invalid data structure.
     */
    public function __construct(\stdClass $structure) {

        if (isset($structure->quizid) && is_int($structure->quizid)) {
            $this->quizid = $structure->quizid;
        } else {
            throw new \coding_exception('Invalid quizid for quizgradeitem condition.');
        }

        if (isset($structure->quizgradeitemid) && is_int($structure->quizgradeitemid)) {
            $this->quizgradeitemid = $structure->quizgradeitemid;
        } else {
            throw new \coding_exception('Invalid quizgradeitemid for quizgradeitem condition.');
        }

        // Get min and max.
        if (!property_exists($structure, 'min')) {
            $this->min = null;
        } else if (is_float($structure->min) || is_int($structure->min)) {
            $this->min = $structure->min;
        } else {
            throw new \coding_exception('Invalid ->min for quizgradeitem condition.');
        }
        if (!property_exists($structure, 'max')) {
            $this->max = null;
        } else if (is_float($structure->max) || is_int($structure->max)) {
            $this->max = $structure->max;
        } else {
            throw new \coding_exception('Invalid ->max for quizgradeitem condition.');
        }

        if ($this->min === null && $this->max === null) {
            throw new \coding_exception('Either ->min or ->max must be set for a quizgradeitem condition.');
        }
    }

    #[\Override]
    public function save(): \stdClass {
        return self::get_json(
            $this->quizid,
            $this->quizgradeitemid,
            $this->min,
            $this->max,
        );
    }

    /**
     * Returns a JSON object which corresponds to a condition of this type.
     *
     * Intended for unit testing, as normally the JSON values are constructed
     * by JavaScript code.
     *
     * @param int $quizid id of the quiz we are depending on.
     * @param int $quizgradeitemid id of the quiz grade item we are depending on.
     * @param float|null $min min grade (or null if no min).
     * @param float|null $max max grade (or null if no max).
     * @return \stdClass Object representing condition.
     */
    public static function get_json(
        int $quizid,
        int $quizgradeitemid,
        ?float $min,
        ?float $max,
    ): \stdClass {
        $config = (object)[
            'type' => 'quizgradeitem',
            'quizid' => $quizid,
            'quizgradeitemid' => $quizgradeitemid,
        ];
        if ($min !== null) {
            $config->min = $min;
        }
        if ($max !== null) {
            $config->max = $max;
        }
        return $config;
    }

    #[\Override]
    protected function get_debug_string(): string {
        return " quiz: #$this->quizid, quizgradeitemid: #$this->quizgradeitemid" .
            ($this->min !== null ? ", min: $this->min" : '') .
            ($this->max !== null ? ", max: $this->max" : '');
    }

    public function is_available($not, \core_availability\info $info, $grabthelot, $userid): bool {
        $allow = $this->requirements_fulfilled($userid);

        if ($not) {
            $allow = !$allow;
        }

        return $allow;
    }

    /**
     * Determine if the target question is in the expected state.
     *
     * @param int $userid id of the user we are checking for.
     * @return bool true if the question is in the expected state. Else false.
     */
    protected function requirements_fulfilled(int $userid): bool {
        $this->update_question_id_to_question_bank_entry_id_if_required();

        $attempts = quiz_get_user_attempts($this->quizid, $userid, 'finished', true);

        if (count($attempts) > 0) {

            if (class_exists('\\mod_quiz\\quiz_attempt')) {
                $attemptobj = \mod_quiz\quiz_attempt::create(end($attempts)->id);
            } else {
                $attemptobj = \quiz_attempt::create(end($attempts)->id);
            }

            foreach ($attemptobj->get_slots() as $slot) {
                $qa = $attemptobj->get_question_attempt($slot);
                $question = \question_bank::load_question_data($qa->get_question_id());

                if ($question->questionbankentryid == $this->questionbankentryid) {
                    return $qa->get_state() === $this->requiredstate ||
                            // If the teacher has manually graded, the state will acutally be something like
                            // mangrright, so handle that case too by comparing CSS class strings.
                            $qa->get_state()->get_feedback_class() === $this->requiredstate->get_feedback_class();
                }
            }
        }

        return false;
    }

    public function get_description($full, $not, \core_availability\info $info): string {
        global $DB;
        $this->update_question_id_to_question_bank_entry_id_if_required();

        $quiz = $DB->get_record('quiz', ['id' => $this->quizid]);
        $question = $DB->get_record_sql("
                SELECT q.*
                  FROM {question_bank_entries} qbe
                  JOIN {question_versions} qv ON qv.questionbankentryid = qbe.id
                            AND qv.version = (
                                SELECT MAX(v.version)
                                  FROM {question_versions} v
                                 WHERE v.questionbankentryid = qbe.id
                                   AND v.status <> ?)
                  JOIN {question} q ON q.id = qv.questionid
                 WHERE qbe.id = ?
                ", [question_version_status::QUESTION_STATUS_DRAFT, $this->questionbankentryid], IGNORE_MISSING);

        if ($quiz && $question) {
            $a = [
                'quizurl' => (new \moodle_url('/mod/quiz/view.php', ['q' => $quiz->id]))->out(),
                'quizname' => format_string($quiz->name),
                'questiontext' => shorten_text(\question_utils::to_plain_text($question->questiontext,
                        $question->questiontextformat,
                        ['noclean' => true, 'para' => false, 'filter' => false])),
                'requiredstate' => $this->requiredstate->default_string(true),
            ];
            if ($not) {
                return  get_string('requires_quizquestionnot', 'availability_quizgradeitem', $a);
            } else {
                return  get_string('requires_quizquestion', 'availability_quizgradeitem', $a);
            }
        }

        return '';
    }

    /**
     * If this was set up under Moodle 3.x (that is, before upgrade, or from a backup)
     * upgrade it now.
     */
    protected function update_question_id_to_question_bank_entry_id_if_required(): void {
        if ($this->questionbankentryid) {
            // Nothing to do, really.
            $this->questionid = null;
            return;
        }

        $questiondata = \question_bank::load_question_data($this->questionid);
        $this->questionbankentryid = $questiondata->questionbankentryid;
        $this->questionid = null;
    }

    public function update_after_restore($restoreid, $courseid, \base_logger $logger, $name): bool {
        global $DB;

        // Recode question bank entry id.
        // If we don't find the new questionid, it is not ideal, but for
        // now do nothing. The check below will probably generate a warning
        // about the situation.
        $questionidchanged = false;
        if ($this->questionbankentryid) {
            // Modern backup being restored.
            $rec = \restore_dbops::get_backup_ids_record($restoreid, 'question_bank_entry', $this->questionbankentryid);
            if ($rec && $rec->newitemid) {
                // New question id found.
                $this->questionbankentryid = (int) $rec->newitemid;
                $questionidchanged = true;
            }

        } else {
            // Restonrign 3.x backup. Work out questionbankentryid from old question id.
            $rec = \restore_dbops::get_backup_ids_record($restoreid, 'question', $this->questionid);
            if ($rec && $rec->newitemid) {
                // New question id found.
                $this->questionid = (int) $rec->newitemid;
                $questionidchanged = true;

                $this->update_question_id_to_question_bank_entry_id_if_required();
            }
        }

        // Recode quiz id.
        $rec = \restore_dbops::get_backup_ids_record($restoreid, 'quiz', $this->quizid);
        if ($rec && $rec->newitemid) {
            // New quiz id found.
            $this->quizid = (int) $rec->newitemid;
            return true;
        }

        // If we are on the same course (e.g. duplicate) then we can just
        // use the existing one.
        if ($DB->record_exists('quiz',
                ['id' => $this->quizid, 'course' => $courseid])) {
            return $questionidchanged;
        }

        // Otherwise it's a warning.
        $this->quizid = 0;
        $logger->process('Restored item (' . $name .
                ') has availability condition on module that was not restored',
                \backup::LOG_WARNING);
        return $questionidchanged;
    }
}
