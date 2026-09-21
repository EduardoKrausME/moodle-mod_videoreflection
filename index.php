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
 * Lists Video Reflection activities in a course.
 *
 * @package   mod_videoreflection
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT);
$course = get_course($id);
require_course_login($course);

$PAGE->set_url('/mod/videoreflection/index.php', ['id' => $course->id]);
$PAGE->set_title(get_string('modulenameplural', 'videoreflection'));
$PAGE->set_heading(format_string($course->fullname));

$instances = get_all_instances_in_course('videoreflection', $course);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('modulenameplural', 'videoreflection'));
if (!$instances) {
    echo $OUTPUT->notification(get_string('noactivities', 'videoreflection'), 'info');
} else {
    $table = new html_table();
    $table->head = [get_string('name'), get_string('videosource', 'videoreflection')];
    foreach ($instances as $instance) {
        $url = new moodle_url('/mod/videoreflection/view.php', ['id' => $instance->coursemodule]);
        $table->data[] = [html_writer::link($url, format_string($instance->name)), s($instance->videosource)];
    }
    echo html_writer::table($table);
}
echo $OUTPUT->footer();
