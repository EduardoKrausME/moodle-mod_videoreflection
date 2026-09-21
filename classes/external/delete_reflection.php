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

namespace mod_videoreflection\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use mod_videoreflection\progress_manager;
use mod_videoreflection\reflection_manager;

/**
 * AJAX endpoint that deletes a reflection owned by the current learner.
 *
 * @package   mod_videoreflection
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class delete_reflection extends external_api {
    /**
     * Defines input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module id'),
            'reflectionid' => new external_value(PARAM_INT, 'Reflection id'),
        ]);
    }

    /**
     * Deletes one reflection.
     *
     * @param int $cmid Course module id.
     * @param int $reflectionid Reflection id.
     * @return array
     */
    public static function execute(int $cmid, int $reflectionid): array {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'cmid' => $cmid,
            'reflectionid' => $reflectionid,
        ]);
        $cm = get_coursemodule_from_id('videoreflection', $params['cmid'], 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/videoreflection:submit', $context);
        $activity = $DB->get_record('videoreflection', ['id' => $cm->instance], '*', MUST_EXIST);

        $entry = $DB->get_record('videoreflection_entries', [
            'id' => $params['reflectionid'],
            'videoreflectionid' => $activity->id,
            'userid' => $USER->id,
        ], '*', MUST_EXIST);
        $DB->delete_records('videoreflection_entries', ['id' => $entry->id]);

        $manager = new reflection_manager();
        $required = $manager->get_required_state((int)$activity->id, (int)$USER->id);
        $count = $manager->count_user_entries((int)$activity->id, (int)$USER->id);
        $complete = $manager->is_complete($activity, (int)$USER->id);
        $progress = (new progress_manager())->get_progress((int)$activity->id, (int)$USER->id);
        if (!empty($progress->id) && (int)$progress->completed !== (int)$complete) {
            $DB->set_field('videoreflection_progress', 'completed', $complete ? 1 : 0, ['id' => $progress->id]);
        }
        $completion = new \completion_info(get_course($cm->course));
        $completion->update_state($cm, COMPLETION_UNKNOWN, $USER->id);

        return [
            'deleted' => true,
            'reflectioncount' => $count,
            'requiredanswered' => $required['answered'],
            'requiredtotal' => $required['total'],
            'complete' => $complete,
        ];
    }

    /**
     * Defines return data.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'deleted' => new external_value(PARAM_BOOL, 'Whether the reflection was deleted'),
            'reflectioncount' => new external_value(PARAM_INT, 'User reflection count'),
            'requiredanswered' => new external_value(PARAM_INT, 'Required prompts answered'),
            'requiredtotal' => new external_value(PARAM_INT, 'Required prompts total'),
            'complete' => new external_value(PARAM_BOOL, 'Completion state'),
        ]);
    }
}
