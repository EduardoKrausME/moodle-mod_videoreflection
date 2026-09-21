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

namespace mod_videoreflection\completion;

use core_completion\activity_custom_completion;
use mod_videoreflection\progress_manager;
use mod_videoreflection\reflection_manager;

/**
 * Evaluates Video Reflection custom completion rules.
 *
 * @package   mod_videoreflection
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class custom_completion extends activity_custom_completion {
    /**
     * Returns the completion state for one custom rule.
     *
     * @param string $rule Rule identifier.
     * @return int
     */
    public function get_state(string $rule): int {
        global $DB;

        $this->validate_rule($rule);
        $activity = $DB->get_record('videoreflection', ['id' => $this->cm->instance], '*', MUST_EXIST);
        $manager = new reflection_manager();

        if ($rule === 'completionpercent') {
            $progress = (new progress_manager())->get_progress((int)$activity->id, (int)$this->userid);
            return (float)$progress->percent >= (float)$activity->completionpercent
                ? COMPLETION_COMPLETE
                : COMPLETION_INCOMPLETE;
        }
        if ($rule === 'completionreflections') {
            return $manager->count_user_entries((int)$activity->id, (int)$this->userid) >= (int)$activity->completionreflections
                ? COMPLETION_COMPLETE
                : COMPLETION_INCOMPLETE;
        }

        if (empty($activity->completionrequiredquestions)) {
            return COMPLETION_COMPLETE;
        }
        $required = $manager->get_required_state((int)$activity->id, (int)$this->userid);
        return $required['answered'] >= $required['total'] ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE;
    }

    /**
     * Returns all custom completion rule identifiers.
     *
     * @return array
     */
    public static function get_defined_custom_rules(): array {
        return ['completionpercent', 'completionreflections', 'completionrequiredquestions'];
    }

    /**
     * Returns descriptions displayed by Moodle.
     *
     * @return array
     */
    public function get_custom_rule_descriptions(): array {
        global $DB;

        $activity = $DB->get_record('videoreflection', ['id' => $this->cm->instance], '*', MUST_EXIST);
        return [
            'completionpercent' => get_string('completiondetail:percent', 'videoreflection', $activity->completionpercent),
            'completionreflections' => get_string(
                'completiondetail:reflections',
                'videoreflection',
                $activity->completionreflections
            ),
            'completionrequiredquestions' => get_string('completiondetail:requiredquestions', 'videoreflection'),
        ];
    }

    /**
     * Returns the preferred display order for completion rules.
     *
     * @return string[]
     */
    public function get_sort_order(): array {
        return [
            'completionview',
            'completionpercent',
            'completionreflections',
            'completionrequiredquestions',
        ];
    }
}
