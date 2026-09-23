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
 * Core callbacks for Video Reflection.
 *
 * @package   mod_videoreflection
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_videoreflection\progress_manager;
use mod_videoreflection\reflection_manager;

/**
 * Declares Moodle features supported by the activity.
 *
 * @param string $feature Moodle feature constant.
 * @return bool|int|string|null
 */
function videoreflection_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_ARCHETYPE:
            return MOD_ARCHETYPE_RESOURCE;
        case FEATURE_GROUPS:
            return false;
        case FEATURE_GROUPINGS:
            return false;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_COMPLETION_HAS_RULES:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_CONTENT;
        default:
            return null;
    }
}

/**
 * Adds a Video Reflection instance.
 *
 * @param stdClass $data Submitted activity data.
 * @param mod_videoreflection_mod_form|null $mform Activity form.
 * @return int New instance id.
 */
function videoreflection_add_instance(stdClass $data, ?mod_videoreflection_mod_form $mform = null): int {
    global $DB;

    $now = time();
    $data->timecreated = $now;
    $data->timemodified = $now;
    $data->videourl = trim((string)($data->videourl ?? ''));
    $id = $DB->insert_record('videoreflection', $data);
    $data->id = $id;
    videoreflection_save_files($data);
    return $id;
}

/**
 * Updates a Video Reflection instance.
 *
 * @param stdClass $data Submitted activity data.
 * @param mod_videoreflection_mod_form|null $mform Activity form.
 * @return bool
 */
function videoreflection_update_instance(stdClass $data, ?mod_videoreflection_mod_form $mform = null): bool {
    global $DB;

    $data->id = $data->instance;
    $data->timemodified = time();
    $data->videourl = trim((string)($data->videourl ?? ''));
    $result = $DB->update_record('videoreflection', $data);
    videoreflection_save_files($data);
    return $result;
}

/**
 * Saves video and poster draft areas into the module context.
 *
 * @param stdClass $data Activity data.
 * @return void
 */
function videoreflection_save_files(stdClass $data): void {
    if (empty($data->coursemodule)) {
        return;
    }

    $context = context_module::instance((int)$data->coursemodule);
    if (($data->videosource ?? 'upload') === 'upload' && isset($data->videofile)) {
        file_save_draft_area_files(
            (int)$data->videofile,
            $context->id,
            'mod_videoreflection',
            'video',
            0,
            [
                'subdirs' => 0,
                'maxfiles' => 1,
                'accepted_types' => ['.mp4', '.webm', '.ogv', '.m4v', '.mov'],
            ]
        );
    } else if (($data->videosource ?? 'upload') !== 'upload') {
        get_file_storage()->delete_area_files($context->id, 'mod_videoreflection', 'video', 0);
    }
    if (isset($data->poster)) {
        file_save_draft_area_files(
            (int)$data->poster,
            $context->id,
            'mod_videoreflection',
            'poster',
            0,
            ['subdirs' => 0, 'maxfiles' => 1, 'accepted_types' => ['image']]
        );
    }
}

/**
 * Deletes a Video Reflection instance and all dependent records.
 *
 * @param int $id Instance id.
 * @return bool
 */
function videoreflection_delete_instance(int $id): bool {
    global $DB;

    $activity = $DB->get_record('videoreflection', ['id' => $id]);
    if (!$activity) {
        return false;
    }

    $cm = get_coursemodule_from_instance('videoreflection', $id, $activity->course, false, IGNORE_MISSING);
    if ($cm) {
        $context = context_module::instance($cm->id);
        get_file_storage()->delete_area_files($context->id, 'mod_videoreflection');
    }

    $transaction = $DB->start_delegated_transaction();
    $DB->delete_records('videoreflection_entries', ['videoreflectionid' => $id]);
    $DB->delete_records('videoreflection_progress', ['videoreflectionid' => $id]);
    $DB->delete_records('videoreflection_questions', ['videoreflectionid' => $id]);
    $DB->delete_records('videoreflection', ['id' => $id]);
    $transaction->allow_commit();
    return true;
}

/**
 * Serves protected video and poster files.
 *
 * @param stdClass $course Course record.
 * @param stdClass $cm Course module record.
 * @param context $context Module context.
 * @param string $filearea File area.
 * @param array $args Remaining path arguments.
 * @param bool $forcedownload Forced download flag.
 * @param array $options Additional serving options.
 * @return bool
 */
function mod_videoreflection_pluginfile($course, $cm, $context, string $filearea, array $args,
                                        bool $forcedownload, array $options = []): bool {
    if ($context->contextlevel !== CONTEXT_MODULE || !in_array($filearea, ['video', 'poster'], true)) {
        return false;
    }

    require_login($course, true, $cm);
    require_capability('mod/videoreflection:view', $context);

    $itemid = (int)array_shift($args);
    if ($itemid !== 0) {
        return false;
    }
    $filename = array_pop($args);
    $filepath = '/' . ($args ? implode('/', $args) . '/' : '');
    $file = get_file_storage()->get_file(
        $context->id,
        'mod_videoreflection',
        $filearea,
        0,
        $filepath,
        $filename
    );
    if (!$file || $file->is_directory()) {
        return false;
    }

    send_stored_file($file, 0, 0, $forcedownload, $options);
}

/**
 * Returns file areas exposed by the activity.
 *
 * @param stdClass $course Course record.
 * @param stdClass $cm Course module record.
 * @param context $context Module context.
 * @return array
 */
function videoreflection_get_file_areas($course, $cm, $context): array {
    return [
        'video' => get_string('videofile', 'videoreflection'),
        'poster' => get_string('poster', 'videoreflection'),
    ];
}

/**
 * Builds cached course module information.
 *
 * @param stdClass $cm Course module record.
 * @return cached_cm_info|null
 */
function videoreflection_get_coursemodule_info(stdClass $cm): ?cached_cm_info {
    global $DB;

    $activity = $DB->get_record(
        'videoreflection',
        ['id' => $cm->instance],
        'id,name,intro,introformat,completionpercent,completionreflections,completionrequiredquestions'
    );
    if (!$activity) {
        return null;
    }

    $info = new cached_cm_info();
    $info->name = $activity->name;
    if ($cm->showdescription) {
        $info->content = format_module_intro('videoreflection', $activity, $cm->id, false);
    }
    if ((int)$cm->completion === COMPLETION_TRACKING_AUTOMATIC) {
        $requiredquestions = $DB->count_records('videoreflection_questions', [
            'videoreflectionid' => $activity->id,
            'required' => 1,
            'enabled' => 1,
        ]);
        $info->customdata = [
            'customcompletionrules' => [
                'completionpercent' => (int)$activity->completionpercent,
                'completionreflections' => (int)$activity->completionreflections,
                'completionrequiredquestions' => !empty($activity->completionrequiredquestions) && $requiredquestions > 0 ? 1 : 0,
            ],
        ];
    }
    return $info;
}

/**
 * Returns active completion rule descriptions.
 *
 * @param cached_cm_info $cm Cached module info.
 * @return array
 */
function videoreflection_get_completion_active_rule_descriptions(cached_cm_info $cm): array {
    if ((int)$cm->completion !== COMPLETION_TRACKING_AUTOMATIC || empty($cm->customdata['customcompletionrules'])) {
        return [];
    }

    $rules = $cm->customdata['customcompletionrules'];
    $descriptions = [];
    if (!empty($rules['completionpercent'])) {
        $descriptions[] = get_string('completiondetail:percent', 'videoreflection', $rules['completionpercent']);
    }
    if (!empty($rules['completionreflections'])) {
        $descriptions[] = get_string('completiondetail:reflections', 'videoreflection', $rules['completionreflections']);
    }
    if (!empty($rules['completionrequiredquestions'])) {
        $descriptions[] = get_string('completiondetail:requiredquestions', 'videoreflection');
    }
    return $descriptions;
}

/**
 * Legacy completion callback used by supported Moodle versions.
 *
 * @param stdClass $course Course record.
 * @param stdClass $cm Course module record.
 * @param int $userid User id.
 * @param bool $type Expected completion state.
 * @return bool
 */
function videoreflection_get_completion_state($course, $cm, int $userid, bool $type): bool {
    global $DB;

    $activity = $DB->get_record('videoreflection', ['id' => $cm->instance], '*', MUST_EXIST);
    return (new reflection_manager())->is_complete($activity, $userid);
}

/**
 * Adds module-specific links to the settings navigation.
 *
 * @param settings_navigation $settingsnav Settings navigation.
 * @param navigation_node $videoreflectionnode Activity settings node.
 * @return void
 */
function videoreflection_extend_settings_navigation(settings_navigation $settingsnav,
                                                    navigation_node $videoreflectionnode): void {
    global $PAGE;

    $context = $PAGE->context;
    if (!$context instanceof context_module) {
        return;
    }
    if (has_capability('mod/videoreflection:managequestions', $context)) {
        $videoreflectionnode->add(
            get_string('managequestions', 'videoreflection'),
            new moodle_url('/mod/videoreflection/questions.php', ['id' => $PAGE->cm->id]),
            navigation_node::TYPE_SETTING
        );
    }
    if (has_capability('mod/videoreflection:viewreports', $context)) {
        $videoreflectionnode->add(
            get_string('report', 'videoreflection'),
            new moodle_url('/mod/videoreflection/report.php', ['id' => $PAGE->cm->id]),
            navigation_node::TYPE_SETTING
        );
    }
}
