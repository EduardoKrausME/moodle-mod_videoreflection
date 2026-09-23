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
 * AJAX endpoint that creates learner reflections.
 *
 * @package   mod_videoreflection
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class add_reflection extends external_api {
    /**
     * Defines input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module id'),
            'questionid' => new external_value(PARAM_INT, 'Prompt id or zero'),
            'timepoint' => new external_value(PARAM_FLOAT, 'Current video time'),
            'entrytype' => new external_value(PARAM_ALPHA, 'reflection or doubt'),
            'reflectiontext' => new external_value(PARAM_RAW, 'Reflection text'),
        ]);
    }

    /**
     * Creates one reflection.
     *
     * @param int $cmid Course module id.
     * @param int $questionid Prompt id.
     * @param float $timepoint Video time.
     * @param string $entrytype Entry category.
     * @param string $reflectiontext Reflection text.
     * @return array
     */
    public static function execute(int $cmid, int $questionid, float $timepoint, string $entrytype,
                                   string $reflectiontext): array {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'cmid' => $cmid,
            'questionid' => $questionid,
            'timepoint' => $timepoint,
            'entrytype' => $entrytype,
            'reflectiontext' => $reflectiontext,
        ]);
        $cm = get_coursemodule_from_id('videoreflection', $params['cmid'], 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/videoreflection:submit', $context);
        $activity = $DB->get_record('videoreflection', ['id' => $cm->instance], '*', MUST_EXIST);

        $text = trim((string)$params['reflectiontext']);
        if ($text === '') {
            throw new \invalid_parameter_exception(get_string('reflectionrequired', 'videoreflection'));
        }
        if (\core_text::strlen($text) > 10000) {
            throw new \invalid_parameter_exception(get_string('reflectiontoolong', 'videoreflection'));
        }
        if (!in_array($params['entrytype'], ['reflection', 'doubt'], true)) {
            throw new \invalid_parameter_exception('Invalid entry type.');
        }

        $manager = new reflection_manager();
        $entry = $manager->add_entry(
            $activity,
            (int)$USER->id,
            (int)$params['questionid'],
            (float)$params['timepoint'],
            (string)$params['entrytype'],
            $text
        );
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
            'id' => (int)$entry->id,
            'questionid' => (int)$entry->questionid,
            'timepoint' => (float)$entry->timepoint,
            'timeformatted' => reflection_manager::format_time((float)$entry->timepoint),
            'entrytype' => (string)$entry->entrytype,
            'reflectiontext' => (string)$entry->reflectiontext,
            'shared' => (bool)$entry->shared,
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
            'id' => new external_value(PARAM_INT, 'Reflection id'),
            'questionid' => new external_value(PARAM_INT, 'Prompt id'),
            'timepoint' => new external_value(PARAM_FLOAT, 'Video time'),
            'timeformatted' => new external_value(PARAM_TEXT, 'Formatted video time'),
            'entrytype' => new external_value(PARAM_ALPHA, 'Entry type'),
            'reflectiontext' => new external_value(PARAM_RAW, 'Reflection text'),
            'shared' => new external_value(PARAM_BOOL, 'Whether visible to classmates'),
            'reflectioncount' => new external_value(PARAM_INT, 'User reflection count'),
            'requiredanswered' => new external_value(PARAM_INT, 'Required prompts answered'),
            'requiredtotal' => new external_value(PARAM_INT, 'Required prompts total'),
            'complete' => new external_value(PARAM_BOOL, 'Completion state'),
        ]);
    }
}
