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

namespace mod_videoreflection;

/**
 * Provides reflection counts, required-prompt state and completion decisions.
 *
 * @package   mod_videoreflection
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class reflection_manager {
    /**
     * Returns enabled prompts in configured order.
     *
     * @param int $activityid Activity id.
     * @return array
     */
    public function get_questions(int $activityid): array {
        global $DB;
        return $DB->get_records(
            'videoreflection_questions',
            ['videoreflectionid' => $activityid, 'enabled' => 1],
            'sortorder ASC, id ASC'
        );
    }

    /**
     * Returns all reflections owned by a learner.
     *
     * @param int $activityid Activity id.
     * @param int $userid User id.
     * @return array
     */
    public function get_user_entries(int $activityid, int $userid): array {
        global $DB;
        return $DB->get_records(
            'videoreflection_entries',
            ['videoreflectionid' => $activityid, 'userid' => $userid],
            'timecreated ASC, id ASC'
        );
    }

    /**
     * Counts all entries submitted by a learner.
     *
     * @param int $activityid Activity id.
     * @param int $userid User id.
     * @return int
     */
    public function count_user_entries(int $activityid, int $userid): int {
        global $DB;
        return $DB->count_records('videoreflection_entries', [
            'videoreflectionid' => $activityid,
            'userid' => $userid,
        ]);
    }

    /**
     * Returns required prompt counts for a learner.
     *
     * @param int $activityid Activity id.
     * @param int $userid User id.
     * @return array{answered:int,total:int}
     */
    public function get_required_state(int $activityid, int $userid): array {
        global $DB;

        $questions = $DB->get_records('videoreflection_questions', [
            'videoreflectionid' => $activityid,
            'required' => 1,
            'enabled' => 1,
        ]);
        if (!$questions) {
            return ['answered' => 0, 'total' => 0];
        }
        $answered = 0;
        foreach ($questions as $question) {
            if ($DB->record_exists('videoreflection_entries', [
                'videoreflectionid' => $activityid,
                'questionid' => $question->id,
                'userid' => $userid,
            ])) {
                $answered++;
            }
        }
        return ['answered' => $answered, 'total' => count($questions)];
    }

    /**
     * Decides whether the learner satisfies every configured completion condition.
     *
     * @param \stdClass $activity Activity record.
     * @param int $userid User id.
     * @return bool
     */
    public function is_complete(\stdClass $activity, int $userid): bool {
        $progress = (new progress_manager())->get_progress((int)$activity->id, $userid);
        if ((float)$progress->percent + 0.001 < (float)$activity->completionpercent) {
            return false;
        }
        if ($this->count_user_entries((int)$activity->id, $userid) < (int)$activity->completionreflections) {
            return false;
        }
        if (empty($activity->completionrequiredquestions)) {
            return true;
        }
        $required = $this->get_required_state((int)$activity->id, $userid);
        return $required['answered'] >= $required['total'];
    }

    /**
     * Creates a reflection and returns its database record.
     *
     * @param \stdClass $activity Activity record.
     * @param int $userid User id.
     * @param int $questionid Prompt id or zero.
     * @param float $timepoint Video time.
     * @param string $entrytype Reflection category.
     * @param string $text Reflection text.
     * @return \stdClass
     */
    public function add_entry(\stdClass $activity, int $userid, int $questionid, float $timepoint,
                              string    $entrytype, string $text): \stdClass {
        global $DB;

        if ($questionid) {
            $question = $DB->get_record('videoreflection_questions', [
                'id' => $questionid,
                'videoreflectionid' => $activity->id,
                'enabled' => 1,
            ], '*', MUST_EXIST);
            // Responses to a teacher prompt inherit the prompt time exactly.
            // A general prompt therefore remains general with timepoint -1.
            $timepoint = (float)$question->timepoint;
        }
        $now = time();
        $record = (object)[
            'videoreflectionid' => (int)$activity->id,
            'questionid' => $questionid,
            'userid' => $userid,
            'timepoint' => max(-1.0, $timepoint),
            'entrytype' => in_array($entrytype, ['reflection', 'doubt'], true) ? $entrytype : 'reflection',
            'reflectiontext' => trim($text),
            'shared' => !empty($activity->privacymode) ? 1 : 0,
            'timecreated' => $now,
            'timemodified' => $now,
        ];
        $record->id = $DB->insert_record('videoreflection_entries', $record);
        return $record;
    }

    /**
     * Formats seconds as MM:SS or HH:MM:SS.
     *
     * @param float $seconds Seconds.
     * @return string
     */
    public static function format_time(float $seconds): string {
        if ($seconds < 0) {
            return get_string('generalprompt', 'videoreflection');
        }
        $total = max(0, (int)round($seconds));
        $hours = intdiv($total, 3600);
        $minutes = intdiv($total % 3600, 60);
        $secs = $total % 60;
        return $hours > 0
            ? sprintf('%d:%02d:%02d', $hours, $minutes, $secs)
            : sprintf('%02d:%02d', $minutes, $secs);
    }

    /**
     * Parses seconds, MM:SS or HH:MM:SS. Empty input represents a general prompt.
     *
     * @param string $value User-entered timecode.
     * @return float|null Null when invalid.
     */
    public static function parse_time(string $value): ?float {
        $value = trim($value);
        if ($value === '') {
            return -1.0;
        }
        if (is_numeric($value)) {
            return (float)$value >= 0 ? (float)$value : null;
        }
        $parts = explode(':', $value);
        if (count($parts) < 2 || count($parts) > 3) {
            return null;
        }
        foreach ($parts as $part) {
            if ($part === '' || !ctype_digit($part)) {
                return null;
            }
        }
        if (count($parts) === 2) {
            [$minutes, $seconds] = array_map('intval', $parts);
            return $seconds < 60 ? ($minutes * 60 + $seconds) : null;
        }
        [$hours, $minutes, $seconds] = array_map('intval', $parts);
        if ($minutes >= 60 || $seconds >= 60) {
            return null;
        }
        return $hours * 3600 + $minutes * 60 + $seconds;
    }
}
