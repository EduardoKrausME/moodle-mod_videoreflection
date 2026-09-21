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
 * Learner view for Video Reflection.
 *
 * @package   mod_videoreflection
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$id = optional_param('id', 0, PARAM_INT);
$v = optional_param('v', 0, PARAM_INT);
$t = optional_param('t', -1, PARAM_FLOAT);
if ($id) {
    $cm = get_coursemodule_from_id('videoreflection', $id, 0, false, MUST_EXIST);
    $activity = $DB->get_record('videoreflection', ['id' => $cm->instance], '*', MUST_EXIST);
} else {
    $activity = $DB->get_record('videoreflection', ['id' => $v], '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('videoreflection', $activity->id, $activity->course, false, MUST_EXIST);
}
$course = get_course($cm->course);
$context = context_module::instance($cm->id);
require_login($course, true, $cm);
require_capability('mod/videoreflection:view', $context);

$PAGE->set_url('/mod/videoreflection/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($activity->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

$completion = new completion_info($course);
$completion->set_module_viewed($cm);
$event = \mod_videoreflection\event\course_module_viewed::create([
    'objectid' => $activity->id,
    'context' => $context,
]);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('videoreflection', $activity);
$event->trigger();

$reflectionmanager = new \mod_videoreflection\reflection_manager();
$progressmanager = new \mod_videoreflection\progress_manager();
$progress = $progressmanager->get_progress((int)$activity->id, (int)$USER->id);
$questions = $reflectionmanager->get_questions((int)$activity->id);
$entries = $reflectionmanager->get_user_entries((int)$activity->id, (int)$USER->id);
$required = $reflectionmanager->get_required_state((int)$activity->id, (int)$USER->id);
$entrycount = count($entries);
$complete = $reflectionmanager->is_complete($activity, (int)$USER->id);

$answeredquestions = [];
foreach ($entries as $entry) {
    if (!empty($entry->questionid)) {
        $answeredquestions[(int)$entry->questionid] = true;
    }
}

$questiondata = [];
foreach ($questions as $question) {
    $questiondata[] = [
        'id' => (int)$question->id,
        'questiontext' => $question->questiontext,
        'timepoint' => (float)$question->timepoint,
        'timeformatted' => \mod_videoreflection\reflection_manager::format_time((float)$question->timepoint),
        'timed' => (float)$question->timepoint >= 0,
        'required' => !empty($question->required),
        'answered' => isset($answeredquestions[(int)$question->id]),
        'purpose' => $question->purpose,
        'purposelabel' => get_string('purpose_' . $question->purpose, 'videoreflection'),
    ];
}

$entrydata = [];
foreach ($entries as $entry) {
    $entrydata[] = [
        'id' => (int)$entry->id,
        'questionid' => (int)$entry->questionid,
        'timepoint' => (float)$entry->timepoint,
        'timeformatted' => \mod_videoreflection\reflection_manager::format_time((float)$entry->timepoint),
        'timed' => (float)$entry->timepoint >= 0,
        'entrytype' => $entry->entrytype,
        'entrytypelabel' => get_string('entrytype_' . $entry->entrytype, 'videoreflection'),
        'reflectiontext' => $entry->reflectiontext,
        'timecreated' => userdate($entry->timecreated),
    ];
}

$shareddata = [];
if (!empty($activity->privacymode) && has_capability('mod/videoreflection:viewshared', $context)) {
    $sql = "SELECT e.*, u.firstname, u.lastname, u.firstnamephonetic, u.lastnamephonetic,
                   u.middlename, u.alternatename
              FROM {videoreflection_entries} e
              JOIN {user} u ON u.id = e.userid
             WHERE e.videoreflectionid = :activityid
               AND e.shared = 1
               AND e.userid <> :userid
          ORDER BY e.timecreated DESC, e.id DESC";
    $shared = $DB->get_records_sql($sql, ['activityid' => $activity->id, 'userid' => $USER->id], 0, 100);
    foreach ($shared as $entry) {
        $shareddata[] = [
            'author' => fullname($entry),
            'timepoint' => (float)$entry->timepoint,
            'timeformatted' => \mod_videoreflection\reflection_manager::format_time((float)$entry->timepoint),
            'timed' => (float)$entry->timepoint >= 0,
            'entrytypelabel' => get_string('entrytype_' . $entry->entrytype, 'videoreflection'),
            'reflectiontext' => $entry->reflectiontext,
            'timecreated' => userdate($entry->timecreated),
        ];
    }
}

$player = \mod_videoreflection\player_helper::get_config($activity, $context);
$cansubmit = has_capability('mod/videoreflection:submit', $context);
$viewdata = [
    'intro' => format_module_intro('videoreflection', $activity, $cm->id, false),
    'hasintro' => trim((string)$activity->intro) !== '',
    'questions' => array_values($questiondata),
    'hasquestions' => !empty($questiondata),
    'entries' => array_values($entrydata),
    'hasentries' => !empty($entrydata),
    'sharedentries' => array_values($shareddata),
    'hassharedentries' => !empty($shareddata),
    'isshared' => !empty($activity->privacymode),
    'cansubmit' => $cansubmit,
    'percent' => number_format((float)$progress->percent, 1),
    'percentraw' => (float)$progress->percent,
    'entrycount' => $entrycount,
    'requiredanswered' => $required['answered'],
    'requiredtotal' => $required['total'],
    'complete' => $complete,
    'completionlabel' => $complete
        ? get_string('activitycomplete', 'videoreflection')
        : get_string('activityincomplete', 'videoreflection'),
];

$jsconfig = [
    'cmid' => (int)$cm->id,
    'source' => $player['source'],
    'url' => $player['url'],
    'poster' => $player['poster'],
    'resumeMode' => (int)$activity->resumeplayback,
    'allowSeek' => !empty($activity->allowseek),
    'lastPosition' => (float)$progress->lastposition,
    'contiguousEnd' => $progressmanager->get_contiguous_end($progress),
    'canSubmit' => $cansubmit,
    'initialPosition' => (float)$t,
    'strings' => [
        'resumeConfirm' => get_string(
            'resumeconfirm',
            'videoreflection',
            \mod_videoreflection\reflection_manager::format_time((float)$progress->lastposition)
        ),
        'reflectionRequired' => get_string('reflectionrequired', 'videoreflection'),
        'deleteConfirm' => get_string('delete') . '?',
        'reflectionAdded' => get_string('reflectionadded', 'videoreflection'),
        'reflectionDeleted' => get_string('reflectiondeleted', 'videoreflection'),
        'typeReflection' => get_string('entrytype_reflection', 'videoreflection'),
        'typeDoubt' => get_string('entrytype_doubt', 'videoreflection'),
        'delete' => get_string('deletereflection', 'videoreflection'),
        'complete' => get_string('activitycomplete', 'videoreflection'),
        'incomplete' => get_string('activityincomplete', 'videoreflection'),
    ],
];
$PAGE->requires->js_call_amd('mod_videoreflection/player', 'init', [$jsconfig]);

$manageurl = new moodle_url('/mod/videoreflection/questions.php', ['id' => $cm->id]);
$reporturl = new moodle_url('/mod/videoreflection/report.php', ['id' => $cm->id]);

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($activity->name));
if (has_capability('mod/videoreflection:managequestions', $context)) {
    echo $OUTPUT->single_button($manageurl, get_string('managequestions', 'videoreflection'), 'get');
}
if (has_capability('mod/videoreflection:viewreports', $context)) {
    echo $OUTPUT->single_button($reporturl, get_string('report', 'videoreflection'), 'get');
}
echo $OUTPUT->render_from_template('mod_videoreflection/view', $viewdata);
echo $OUTPUT->footer();
