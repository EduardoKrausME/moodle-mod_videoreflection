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
 * Backup structure for Video Reflection.
 *
 * @package   mod_videoreflection
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Defines the XML structure stored in activity backups.
 */
class backup_videoreflection_activity_structure_step extends backup_activity_structure_step {
    /**
     * Builds the backup structure.
     *
     * @return backup_nested_element
     */
    protected function define_structure() {
        $userinfo = $this->get_setting_value('userinfo');

        $activity = new backup_nested_element('videoreflection', ['id'], [
            'course', 'name', 'intro', 'introformat', 'videosource', 'videourl',
            'resumeplayback', 'allowseek', 'privacymode', 'completionpercent',
            'completionreflections', 'completionrequiredquestions', 'timecreated', 'timemodified',
        ]);
        $questions = new backup_nested_element('questions');
        $question = new backup_nested_element('question', ['id'], [
            'questiontext', 'timepoint', 'required', 'purpose', 'sortorder', 'enabled',
            'timecreated', 'timemodified',
        ]);
        $entries = new backup_nested_element('entries');
        $entry = new backup_nested_element('entry', ['id'], [
            'questionid', 'userid', 'timepoint', 'entrytype', 'reflectiontext', 'shared',
            'timecreated', 'timemodified',
        ]);
        $progresses = new backup_nested_element('progresses');
        $progress = new backup_nested_element('progress', ['id'], [
            'userid', 'duration', 'lastposition', 'uniquewatched', 'totalwatchtime',
            'percent', 'watchedsegments', 'completed', 'sequence', 'timecreated', 'timemodified',
        ]);

        $activity->add_child($questions);
        $questions->add_child($question);
        $activity->add_child($entries);
        $entries->add_child($entry);
        $activity->add_child($progresses);
        $progresses->add_child($progress);

        $activity->set_source_table('videoreflection', ['id' => backup::VAR_ACTIVITYID]);
        $question->set_source_table('videoreflection_questions', [
            'videoreflectionid' => backup::VAR_PARENTID,
        ]);
        if ($userinfo) {
            $entry->set_source_table('videoreflection_entries', [
                'videoreflectionid' => backup::VAR_PARENTID,
            ]);
            $progress->set_source_table('videoreflection_progress', [
                'videoreflectionid' => backup::VAR_PARENTID,
            ]);
        }

        $entry->annotate_ids('user', 'userid');
        $progress->annotate_ids('user', 'userid');
        $activity->annotate_files('mod_videoreflection', 'video', null);
        $activity->annotate_files('mod_videoreflection', 'poster', null);

        return $this->prepare_activity_structure($activity);
    }
}
