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
    protected int $quizid;

    /** @var int the id of the question bank entry in the quiz that this depends on. */
    protected int $quizgradeitemid;

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

    #[\Override]
    public function is_available($not, \core_availability\info $info, $grabthelot, $userid): bool {
        $score = $this->get_cached_quiz_grade_item_score(
            $userid,
            $this->quizgradeitemid,
            $this->quizid,
        );

        $allow = $score !== false &&
            ($this->min === null || $score >= $this->min) &&
            ($this->max === null || $score < $this->max);

        if ($not) {
            $allow = !$allow;
        }

        return $allow;
    }

    #[\Override]
    public function get_description($full, $not, \core_availability\info $info): string {
        if ($this->max === null) {
            $string = 'min';
        } else if ($this->min === null) {
            $string = 'max';
        } else {
            $string = 'range';
        }
        if ($not) {
            $string = 'not' . $string;
        }
        return  get_string(
            'requires_' . $string,
            'availability_quizgradeitem',
            [
                'quizurl' => (new \moodle_url('/mod/quiz/view.php', ['q' => $this->quizid]))->out(),
                'quizname' => $this->description_callback(['quizname', $this->quizid, '']),
                'quizgradeitem' => $this->description_callback(['quizgradeitem', $this->quizid, $this->quizgradeitemid]),
                'min' => $this->min ? $this->description_callback(['score', $this->quizid, $this->min]) : null,
                'max' => $this->max ? $this->description_callback(['score', $this->quizid, $this->max]) : null,
            ],
        );
    }

    /**
     * Callback to get information needed in the display at the right time, and efficiently.
     *
     * This is sort-of defined in the base class, but for some reason we can't mark it an override.
     *
     * @param \course_modinfo $modinfo Modinfo
     * @param \context $context Context
     * @param string[] $params Parameters (just grade item id)
     * @return string Text value
     */
    public static function get_description_callback_value(
        \course_modinfo $modinfo,
        \context $context,
        array $params,
    ): string {
        if (count($params) !== 3) {
            return '<!-- Invalid quizgradeitem description callback -->';
        }
        [$type, $quizid, $value] = $params;
        $cm = $modinfo->instances['quiz'][$quizid];
        switch ($type) {
            case 'quizname':
                return $cm->get_formatted_name();

            case 'quizgradeitem':
                $quizinfo = self::get_cached_quiz_info($modinfo, $quizid);
                if (!$quizinfo) {
                    return '<!-- Invalid quizgradeitem description callback -->';
                }
                $gradeitemnames = $quizinfo['gradeitemnames'];
                // Return name from cached item or a lang string.
                if (isset($gradeitemnames[$value])) {
                    return format_string($gradeitemnames[$value]);
                } else {
                    return get_string('missinggradeitem', 'availability_quizgradeitem');
                }

            case 'score':
                $quizinfo = self::get_cached_quiz_info($modinfo, $quizid);
                if (!$quizinfo || $value === null) {
                    return '<!-- Invalid quizgradeitem description callback -->';
                }
                return format_float($value, $quizinfo['decimalpoints']);

            default:
                return '<!-- Invalid quizgradeitem description callback -->';
        }
    }

    /**
     * Obtains relevant data (for get_description_callback_value) for the quizzes in a course.
     *
     * Uses a cahse, which stores, for each course, we store an array
     *   [
     *     quizid => [
     *       'decimalpoints' => 2,
     *       'gradeitemnames' => [ id => name ],
     *     ],
     *   ]
     *
     * @param \course_modinfo $modinfo course id
     * @param int $quizid quiz id
     * @return array|null sufficent information about each quiz in the course, or null if not found.
     */
    protected static function get_cached_quiz_info(\course_modinfo $modinfo, int $quizid): ?array {
        global $DB;

        // Get all quiz grade item names from cache, or using db query.
        $cache = \cache::make('availability_quizgradeitem', 'quizinfo');
        $quizzesinfo = $cache->get($modinfo->courseid);
        if ($quizzesinfo === false) {
            $quizids = [];
            foreach ($modinfo->get_instances_of('quiz') as $cm) {
                $quizids[] = $cm->instance;
            }

            [$quizidcondition, $params] = $DB->get_in_or_equal($quizids, onemptyitems: '= 0');
            $quizsettings = $DB->get_records_select_menu(
                'quiz',
                'id ' . $quizidcondition,
                $params,
                'id',
                'id, decimalpoints',
            );

            $quizzesinfo = [];
            foreach ($quizsettings as $id => $decimalpoints) {
                $quizzesinfo[$id] = [
                    'decimalpoints' => $decimalpoints,
                    'gradeitemnames' => [],
                ];
            }

            $gradeitems = $DB->get_records_select(
                'quiz_grade_items',
                'quizid ' . $quizidcondition,
                $params,
                'quizid, id',
                'id, quizid, name',
            );
            foreach ($gradeitems as $gradeitem) {
                $quizzesinfo[$gradeitem->quizid]['gradeitemnames'][$gradeitem->id] = $gradeitem->name;
            }

            $cache->set($modinfo->courseid, $quizzesinfo);
        }

        return $quizzesinfo[$quizid] ?? null;
    }

    /**
     * Get the score for a given user on a given quiz grade item.
     *
     * Note that this score should not be displayed to
     * the user, because review options might prohibit that.
     *
     * Uses caching internally.
     *
     * @param int $userid user we want the grad for.
     * @param int $quizgradeitemid Quiz grade item ID we're interested in.
     * @param int $quizid Quiz id
     * @return float|null Grade score as a percentage in range 0-100 (e.g. 100.0
     *   or 37.21), or false if user does not have a grade yet
     */
    protected static function get_cached_quiz_grade_item_score(
        int $userid,
        int $quizgradeitemid,
        int $quizid,
    ): ?float {
        $cache = \cache::make('availability_quizgradeitem', 'scores');
        $cachedgrades = $cache->get($userid);
        if ($cachedgrades === false) {
            $cachedgrades = [];
        }
        if (!isset($cachedgrades[$quizid])) {
            $cachedgrades[$quizid] = self::load_grade_item_scores($userid, $quizid);
            $cache->set($userid, $cachedgrades);
        }

        return $cachedgrades[$quizid][$quizgradeitemid] ?? null;
    }

    /**
     * Compute the scores for each grade item for a students lastest attempt at a quiz.
     *
     * @param int $userid the user id
     * @param int $quizid the quiz id
     * @return array [ grade item id => float|null score ]
     */
    protected static function load_grade_item_scores(int $userid, int $quizid): array {
        $attempts = quiz_get_user_attempts($quizid, $userid, 'finished', true);
        if (!$attempts) {
            return [];
        }

        $attemptobj = \mod_quiz\quiz_attempt::create(end($attempts)->id);

        $scores = [];
        foreach ($attemptobj->get_grade_item_totals() as $gradeitemid => $gradeoutof) {
            $scores[$gradeitemid] = $gradeoutof->grade;
        }

        return $scores;
    }

    #[\Override]
    public function update_after_restore($restoreid, $courseid, \base_logger $logger, $name): bool {
        global $DB;
        $changed = false;

        // Recode quiz id.
        $rec = \restore_dbops::get_backup_ids_record($restoreid, 'quiz', $this->quizid);
        if ($rec && $rec->newitemid) {
            // New quiz id found from the restore.
            $this->quizid = (int) $rec->newitemid;
            $changed = true;
        } else if (!$DB->record_exists('quiz', ['id' => $this->quizid, 'course' => $courseid])) {
            $this->quizid = 0;
            $changed = true;
            $logger->process(
                'Restored item (' . $name . ') has availability condition on a quiz that was not restored',
                \backup::LOG_WARNING,
            );
        }
        // Else we are on the same course (e.g. duplicate) and can just use the existing one.

        // Recode quiz grade item id.
        $rec = \restore_dbops::get_backup_ids_record($restoreid, 'quiz_grade_item', $this->quizgradeitemid);
        if ($rec && $rec->newitemid) {
            // New quiz grade item id found from the restore.
            $this->quizgradeitemid = $rec->newitemid;
            $changed = true;
        } else if (
            !$DB->record_exists(
                'quiz_grade_items',
                ['id' => $this->quizgradeitemid, 'quizid' => $this->quizid],
            )
        ) {
            $this->quizid = 0;
            $changed = true;
            $logger->process(
                'Restored item (' . $name . ') has availability condition on a quiz grade item that was not restored',
                \backup::LOG_WARNING,
            );
        }

        return $changed;
    }
}
