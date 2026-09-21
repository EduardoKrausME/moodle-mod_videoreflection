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

/**
 * Restore task for Video Reflection.
 *
 * @package   mod_videoreflection
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/mod/videoreflection/backup/moodle2/restore_videoreflection_stepslib.php');

/**
 * Defines the activity restore task.
 */
class restore_videoreflection_activity_task extends restore_activity_task {
    /**
     * Adds the structure restore step.
     *
     * @return void
     */
    protected function define_my_steps(): void {
        $this->add_step(new restore_videoreflection_activity_structure_step(
            'videoreflection_structure',
            'videoreflection.xml'
        ));
    }

    /**
     * Defines content decoding rules.
     *
     * @return restore_decode_content[]
     */
    public static function define_decode_contents(): array {
        return [new restore_decode_content('videoreflection', ['intro'], 'videoreflection')];
    }

    /**
     * Defines URL decoding rules.
     *
     * @return restore_decode_rule[]
     */
    public static function define_decode_rules(): array {
        return [
            new restore_decode_rule('VIDEOREFLECTIONVIEWBYID', '/mod/videoreflection/view.php?id=$1', 'course_module'),
        ];
    }

    /**
     * Defines restore log rules.
     *
     * @return restore_log_rule[]
     */
    public static function define_restore_log_rules(): array {
        return [
            new restore_log_rule('videoreflection', 'view', 'view.php?id={course_module}', '{videoreflection}'),
        ];
    }
}
