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
 * Creates, edits and deletes teacher reflection prompts.
 *
 * @package   mod_videoreflection
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once("{$CFG->libdir}/formslib.php");

$id = required_param('id', PARAM_INT);
$questionid = optional_param('questionid', 0, PARAM_INT);
$delete = optional_param('delete', 0, PARAM_BOOL);
$confirm = optional_param('confirm', 0, PARAM_BOOL);

$cm = get_coursemodule_from_id('videoreflection', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$activity = $DB->get_record('videoreflection', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);
require_login($course, true, $cm);
require_capability('mod/videoreflection:managequestions', $context);

$listurl = new moodle_url('/mod/videoreflection/questions.php', ['id' => $cm->id]);
$PAGE->set_url('/mod/videoreflection/question.php', ['id' => $cm->id, 'questionid' => $questionid]);
$PAGE->set_title(format_string($activity->name));
$PAGE->set_heading(format_string($course->fullname));

$question = null;
if ($questionid) {
    $question = $DB->get_record('videoreflection_questions', [
        'id' => $questionid,
        'videoreflectionid' => $activity->id,
    ], '*', MUST_EXIST);
}

if ($delete && $question) {
    if ($confirm && confirm_sesskey()) {
        $DB->set_field('videoreflection_entries', 'questionid', 0, [
            'videoreflectionid' => $activity->id,
            'questionid' => $question->id,
        ]);
        $DB->delete_records('videoreflection_questions', ['id' => $question->id]);
        rebuild_course_cache($course->id, true);
        redirect($listurl);
    }
    echo $OUTPUT->header();
    $confirmurl = new moodle_url('/mod/videoreflection/question.php', [
        'id' => $cm->id,
        'questionid' => $question->id,
        'delete' => 1,
        'confirm' => 1,
        'sesskey' => sesskey(),
    ]);
    echo $OUTPUT->confirm(get_string('deletequestionconfirm', 'videoreflection'), $confirmurl, $listurl);
    echo $OUTPUT->footer();
    exit;
}

$form = new \mod_videoreflection\form\question_form(null, null, 'post', '', null, true);
if ($form->is_cancelled()) {
    redirect($listurl);
}
if ($data = $form->get_data()) {
    $timepoint = \mod_videoreflection\reflection_manager::parse_time((string)$data->timecode);
    if ($timepoint === null) {
        throw new moodle_exception('invalidtimecode', 'videoreflection');
    }
    $now = time();
    if ($question) {
        $question->questiontext = trim($data->questiontext);
        $question->timepoint = $timepoint;
        $question->purpose = $data->purpose;
        $question->required = empty($data->required) ? 0 : 1;
        $question->enabled = empty($data->enabled) ? 0 : 1;
        $question->timemodified = $now;
        $DB->update_record('videoreflection_questions', $question);
    } else {
        $sortorder = (int)$DB->get_field_sql(
            'SELECT COALESCE(MAX(sortorder), 0) FROM {videoreflection_questions} WHERE videoreflectionid = ?',
            [$activity->id]
        );
        $DB->insert_record('videoreflection_questions', (object)[
            'videoreflectionid' => $activity->id,
            'questiontext' => trim($data->questiontext),
            'timepoint' => $timepoint,
            'required' => empty($data->required) ? 0 : 1,
            'purpose' => $data->purpose,
            'sortorder' => $sortorder + 1,
            'enabled' => empty($data->enabled) ? 0 : 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
    }
    rebuild_course_cache($course->id, true);
    redirect($listurl);
}

$defaults = (object)[
    'id' => $cm->id,
    'questionid' => $question ? $question->id : 0,
    'questiontext' => $question ? $question->questiontext : '',
    'timecode' => $question && (float)$question->timepoint >= 0
        ? \mod_videoreflection\reflection_manager::format_time((float)$question->timepoint)
        : '',
    'purpose' => $question ? $question->purpose : 'reflection',
    'required' => $question ? $question->required : 0,
    'enabled' => $question ? $question->enabled : 1,
];
$form->set_data($defaults);

echo $OUTPUT->header();
echo $OUTPUT->heading($question ? get_string('editquestion', 'videoreflection') : get_string('addquestion', 'videoreflection'));
$form->display();
echo $OUTPUT->footer();
