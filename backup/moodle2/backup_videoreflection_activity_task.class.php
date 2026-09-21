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
 * Backup task for Video Reflection.
 *
 * @package   mod_videoreflection
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/mod/videoreflection/backup/moodle2/backup_videoreflection_stepslib.php');

/**
 * Defines the activity backup task.
 */
class backup_videoreflection_activity_task extends backup_activity_task {
    /**
     * Adds the structure backup step.
     *
     * @return void
     */
    protected function define_my_steps(): void {
        $this->add_step(new backup_videoreflection_activity_structure_step(
            'videoreflection_structure',
            'videoreflection.xml'
        ));
    }

    /**
     * Defines activity-specific settings.
     *
     * @return void
     */
    protected function define_my_settings(): void {
    }

    /**
     * Encodes activity links in content.
     *
     * @param string $content Content.
     * @return string
     */
    public static function encode_content_links($content): string {
        global $CFG;
        $base = preg_quote($CFG->wwwroot, '#');
        return preg_replace(
            "#{$base}/mod/videoreflection/view.php\?id=([0-9]+)#",
            '$@VIDEOREFLECTIONVIEWBYID*$1@$',
            $content
        );
    }
}
