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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Test data generator for Video Reflection.
 *
 * @package   mod_videoreflection
 * @category  test
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


/**
 * Creates Video Reflection activity instances for tests.
 *
 * @package   mod_videoreflection
 * @category  test
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_videoreflection_generator extends testing_module_generator {
    /**
     * Creates an activity with safe defaults that do not require draft files.
     *
     * @param array|stdClass|null $record Activity data.
     * @param array|null $options Module creation options.
     * @return stdClass
     */
    public function create_instance($record = null, ?array $options = null) {
        $record = (array)$record + [
            'name' => 'Video Reflection',
            'videosource' => 'url',
            'videourl' => 'https://example.com/video.mp4',
            'resumeplayback' => 1,
            'allowseek' => 1,
            'privacymode' => 0,
            'completionpercent' => 80,
            'completionreflections' => 1,
            'completionrequiredquestions' => 1,
        ];

        return parent::create_instance($record, (array)$options);
    }
}
