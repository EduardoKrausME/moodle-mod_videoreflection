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
 * Lists and orders teacher reflection prompts.
 *
 * @package   mod_videoreflection
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);
$questionid = optional_param('questionid', 0, PARAM_INT);

$cm = get_coursemodule_from_id('videoreflection', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$activity = $DB->get_record('videoreflection', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);
require_login($course, true, $cm);
require_capability('mod/videoreflection:managequestions', $context);

$PAGE->set_url('/mod/videoreflection/questions.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($activity->name));
$PAGE->set_heading(format_string($course->fullname));

if ($questionid && in_array($action, ['up', 'down'], true)) {
    require_sesskey();
    $questions = array_values($DB->get_records('videoreflection_questions', [
        'videoreflectionid' => $activity->id,
    ], 'sortorder ASC, id ASC'));
    foreach ($questions as $index => $question) {
        if ((int)$question->id !== $questionid) {
            continue;
        }
        $otherindex = $action === 'up' ? $index - 1 : $index + 1;
        if (isset($questions[$otherindex])) {
            $other = $questions[$otherindex];
            $a = (int)$question->sortorder;
            $b = (int)$other->sortorder;
            if ($a === $b) {
                $a = $index + 1;
                $b = $otherindex + 1;
            }
            $DB->set_field('videoreflection_questions', 'sortorder', $b, ['id' => $question->id]);
            $DB->set_field('videoreflection_questions', 'sortorder', $a, ['id' => $other->id]);
            rebuild_course_cache($course->id, true);
        }
        break;
    }
    redirect($PAGE->url);
}

$questions = $DB->get_records('videoreflection_questions', [
    'videoreflectionid' => $activity->id,
], 'sortorder ASC, id ASC');

$addurl = new moodle_url('/mod/videoreflection/question.php', ['id' => $cm->id]);
$backurl = new moodle_url('/mod/videoreflection/view.php', ['id' => $cm->id]);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('managequestions', 'videoreflection'));
echo $OUTPUT->single_button($addurl, get_string('addquestion', 'videoreflection'), 'get');

if (!$questions) {
    echo $OUTPUT->notification(get_string('noquestions', 'videoreflection'), 'info');
} else {
    $table = new html_table();
    $table->head = [
        get_string('questiontext', 'videoreflection'),
        get_string('timecode', 'videoreflection'),
        get_string('purpose', 'videoreflection'),
        get_string('required', 'videoreflection'),
        get_string('enabled', 'videoreflection'),
        get_string('actions'),
    ];
    foreach ($questions as $question) {
        $editurl = new moodle_url('/mod/videoreflection/question.php', [
            'id' => $cm->id,
            'questionid' => $question->id,
        ]);
        $deleteurl = new moodle_url('/mod/videoreflection/question.php', [
            'id' => $cm->id,
            'questionid' => $question->id,
            'delete' => 1,
        ]);
        $upurl = new moodle_url('/mod/videoreflection/questions.php', [
            'id' => $cm->id,
            'questionid' => $question->id,
            'action' => 'up',
            'sesskey' => sesskey(),
        ]);
        $downurl = new moodle_url('/mod/videoreflection/questions.php', [
            'id' => $cm->id,
            'questionid' => $question->id,
            'action' => 'down',
            'sesskey' => sesskey(),
        ]);
        $actions = implode(' &middot; ', [
            html_writer::link($editurl, get_string('edit')),
            html_writer::link($deleteurl, get_string('delete')),
            html_writer::link($upurl, get_string('moveup', 'videoreflection')),
            html_writer::link($downurl, get_string('movedown', 'videoreflection')),
        ]);
        $table->data[] = [
            s($question->questiontext),
            \mod_videoreflection\reflection_manager::format_time((float)$question->timepoint),
            get_string('purpose_' . $question->purpose, 'videoreflection'),
            $question->required ? get_string('yes') : get_string('no'),
            $question->enabled ? get_string('yes') : get_string('no'),
            $actions,
        ];
    }
    echo html_writer::table($table);
}

echo $OUTPUT->single_button($backurl, get_string('backtoactivity', 'videoreflection'), 'get');
echo $OUTPUT->footer();
