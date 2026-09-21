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
 * Class and individual reports for Video Reflection.
 *
 * @package   mod_videoreflection
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT);
$userid = optional_param('userid', 0, PARAM_INT);

$cm = get_coursemodule_from_id('videoreflection', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$activity = $DB->get_record('videoreflection', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);
require_login($course, true, $cm);
require_capability('mod/videoreflection:viewreports', $context);

$PAGE->set_url('/mod/videoreflection/report.php', ['id' => $cm->id, 'userid' => $userid ?: null]);
$PAGE->set_title(get_string('report', 'videoreflection') . ': ' . format_string($activity->name));
$PAGE->set_heading(format_string($course->fullname));

$manager = new \mod_videoreflection\reflection_manager();
$progressmanager = new \mod_videoreflection\progress_manager();
$participants = get_enrolled_users(
    $context,
    'mod/videoreflection:submit',
    0,
    'u.id,u.firstname,u.lastname,u.firstnamephonetic,u.lastnamephonetic,u.middlename,u.alternatename,u.email'
);

$backurl = new moodle_url('/mod/videoreflection/view.php', ['id' => $cm->id]);

echo $OUTPUT->header();

if ($userid) {
    if (!isset($participants[$userid])) {
        throw new moodle_exception('invaliduser');
    }
    $user = $participants[$userid];
    echo $OUTPUT->heading(get_string('individualreport', 'videoreflection') . ': ' . fullname($user));
    $progress = $progressmanager->get_progress((int)$activity->id, (int)$userid);
    $required = $manager->get_required_state((int)$activity->id, (int)$userid);
    $entries = $manager->get_user_entries((int)$activity->id, (int)$userid);

    $summary = new html_table();
    $summary->data = [
        [get_string('progress', 'videoreflection'), number_format((float)$progress->percent, 1) . '%'],
        [get_string('reflections', 'videoreflection'), count($entries)],
        [get_string('requiredanswered', 'videoreflection'), $required['answered'] . '/' . $required['total']],
        [get_string('lastaccess', 'videoreflection'), $progress->timemodified ? userdate($progress->timemodified) : '-'],
    ];
    echo html_writer::table($summary);

    if (!$entries) {
        echo $OUTPUT->notification(get_string('noreflections', 'videoreflection'), 'info');
    } else {
        $table = new html_table();
        $table->head = [
            get_string('moment', 'videoreflection'),
            get_string('entrytype', 'videoreflection'),
            get_string('reflectiontext', 'videoreflection'),
            get_string('lastaccess', 'videoreflection'),
        ];
        foreach ($entries as $entry) {
            $time = \mod_videoreflection\reflection_manager::format_time((float)$entry->timepoint);
            if ((float)$entry->timepoint >= 0) {
                $time = html_writer::link(
                    new moodle_url('/mod/videoreflection/view.php', ['id' => $cm->id, 't' => (float)$entry->timepoint]),
                    $time
                );
            }
            $table->data[] = [
                $time,
                get_string('entrytype_' . $entry->entrytype, 'videoreflection'),
                s($entry->reflectiontext),
                userdate($entry->timecreated),
            ];
        }
        echo html_writer::table($table);
    }
    echo $OUTPUT->single_button(
        new moodle_url('/mod/videoreflection/report.php', ['id' => $cm->id]),
        get_string('backtoreport', 'videoreflection'),
        'get'
    );
    echo $OUTPUT->footer();
    exit;
}

echo $OUTPUT->heading(get_string('classreport', 'videoreflection'));

$rows = [];
$sumprogress = 0.0;
foreach ($participants as $user) {
    $progress = $progressmanager->get_progress((int)$activity->id, (int)$user->id);
    $required = $manager->get_required_state((int)$activity->id, (int)$user->id);
    $entrycount = $manager->count_user_entries((int)$activity->id, (int)$user->id);
    $sumprogress += (float)$progress->percent;
    $rows[] = [
        fullname($user),
        number_format((float)$progress->percent, 1) . '%',
        $entrycount,
        $required['answered'] . '/' . $required['total'],
        $progress->timemodified ? userdate($progress->timemodified) : '-',
        html_writer::link(
            new moodle_url('/mod/videoreflection/report.php', ['id' => $cm->id, 'userid' => $user->id]),
            get_string('viewdetails', 'videoreflection')
        ),
    ];
}

$totalentries = $DB->count_records('videoreflection_entries', ['videoreflectionid' => $activity->id]);
$totaldoubts = $DB->count_records('videoreflection_entries', [
    'videoreflectionid' => $activity->id,
    'entrytype' => 'doubt',
]);
$average = $participants ? $sumprogress / count($participants) : 0;

$summary = new html_table();
$summary->data = [
    [get_string('totalparticipants', 'videoreflection'), count($participants)],
    [get_string('totalreflections', 'videoreflection'), $totalentries],
    [get_string('totaldoubts', 'videoreflection'), $totaldoubts],
    [get_string('averageprogress', 'videoreflection'), number_format($average, 1) . '%'],
];
echo html_writer::table($summary);

$table = new html_table();
$table->head = [
    get_string('participant', 'videoreflection'),
    get_string('progress', 'videoreflection'),
    get_string('reflections', 'videoreflection'),
    get_string('requiredanswered', 'videoreflection'),
    get_string('lastaccess', 'videoreflection'),
    get_string('actions'),
];
$table->data = $rows;
echo html_writer::table($table);

$allentries = $DB->get_records('videoreflection_entries', ['videoreflectionid' => $activity->id]);
$buckets = [];
foreach ($allentries as $entry) {
    if ((float)$entry->timepoint < 0) {
        continue;
    }
    $bucket = (int)(floor((float)$entry->timepoint / 30) * 30);
    if (!isset($buckets[$bucket])) {
        $buckets[$bucket] = ['total' => 0, 'doubts' => 0];
    }
    $buckets[$bucket]['total']++;
    if ($entry->entrytype === 'doubt') {
        $buckets[$bucket]['doubts']++;
    }
}
uasort($buckets, static function (array $a, array $b): int {
    return $b['total'] <=> $a['total'];
});
$buckets = array_slice($buckets, 0, 10, true);

echo $OUTPUT->heading(get_string('mostreflectedmoments', 'videoreflection'), 3);
if (!$buckets) {
    echo $OUTPUT->notification(get_string('noanalytics', 'videoreflection'), 'info');
} else {
    $hot = new html_table();
    $hot->head = [get_string('moment', 'videoreflection'),
        get_string('reflectioncount', 'videoreflection'), get_string('doubtcount', 'videoreflection')];
    foreach ($buckets as $start => $bucket) {
        $label = \mod_videoreflection\reflection_manager::format_time((float)$start) . '–' .
            \mod_videoreflection\reflection_manager::format_time((float)$start + 29);
        $hot->data[] = [
            html_writer::link(new moodle_url('/mod/videoreflection/view.php', ['id' => $cm->id, 't' => $start]), $label),
            $bucket['total'],
            $bucket['doubts'],
        ];
    }
    echo html_writer::table($hot);
}

$questions = $manager->get_questions((int)$activity->id);
if ($questions) {
    echo $OUTPUT->heading(get_string('questionresponses', 'videoreflection'), 3);
    $qtable = new html_table();
    $qtable->head = [get_string('questiontext', 'videoreflection'),
        get_string('moment', 'videoreflection'), get_string('reflectioncount', 'videoreflection')];
    foreach ($questions as $question) {
        $responses = $DB->count_records('videoreflection_entries', [
            'videoreflectionid' => $activity->id,
            'questionid' => $question->id,
        ]);
        $qtable->data[] = [
            s($question->questiontext),
            \mod_videoreflection\reflection_manager::format_time((float)$question->timepoint),
            $responses,
        ];
    }
    echo html_writer::table($qtable);
}

echo $OUTPUT->single_button($backurl, get_string('backtoactivity', 'videoreflection'), 'get');
echo $OUTPUT->footer();
