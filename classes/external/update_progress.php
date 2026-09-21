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
 * AJAX endpoint that stores watched progress.
 *
 * @package   mod_videoreflection
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class update_progress extends external_api {
    /**
     * Defines input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module id'),
            'position' => new external_value(PARAM_FLOAT, 'Current playhead position'),
            'duration' => new external_value(PARAM_FLOAT, 'Video duration'),
            'watchedstart' => new external_value(PARAM_FLOAT, 'Continuous watched segment start, or -1'),
            'watchedend' => new external_value(PARAM_FLOAT, 'Continuous watched segment end, or -1'),
            'sequence' => new external_value(PARAM_INT, 'Client heartbeat sequence'),
        ]);
    }

    /**
     * Stores watched progress and returns completion state.
     *
     * @param int $cmid Course module id.
     * @param float $position Current position.
     * @param float $duration Duration.
     * @param float $watchedstart Segment start.
     * @param float $watchedend Segment end.
     * @param int $sequence Sequence.
     * @return array
     */
    public static function execute(int $cmid, float $position, float $duration, float $watchedstart,
                                   float $watchedend, int $sequence): array {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'cmid' => $cmid,
            'position' => $position,
            'duration' => $duration,
            'watchedstart' => $watchedstart,
            'watchedend' => $watchedend,
            'sequence' => $sequence,
        ]);

        $cm = get_coursemodule_from_id('videoreflection', $params['cmid'], 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/videoreflection:view', $context);
        $activity = $DB->get_record('videoreflection', ['id' => $cm->instance], '*', MUST_EXIST);

        $manager = new progress_manager();
        $progress = $manager->update(
            (int)$activity->id,
            (int)$USER->id,
            (float)$params['position'],
            (float)$params['duration'],
            (float)$params['watchedstart'],
            (float)$params['watchedend'],
            (int)$params['sequence']
        );

        $complete = (new reflection_manager())->is_complete($activity, (int)$USER->id);
        if ((int)$progress->completed !== (int)$complete) {
            $progress->completed = $complete ? 1 : 0;
            $DB->set_field('videoreflection_progress', 'completed', $progress->completed, ['id' => $progress->id]);
        }
        $completion = new \completion_info(get_course($cm->course));
        $completion->update_state($cm, COMPLETION_UNKNOWN, $USER->id);

        return [
            'percent' => (float)$progress->percent,
            'lastposition' => (float)$progress->lastposition,
            'contiguousend' => $manager->get_contiguous_end($progress),
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
            'percent' => new external_value(PARAM_FLOAT, 'Unique watched percentage'),
            'lastposition' => new external_value(PARAM_FLOAT, 'Stored resume position'),
            'contiguousend' => new external_value(PARAM_FLOAT, 'Furthest continuously watched point'),
            'complete' => new external_value(PARAM_BOOL, 'Whether all completion conditions are satisfied'),
        ]);
    }
}
