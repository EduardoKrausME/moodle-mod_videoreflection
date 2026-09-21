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
 * Restore structure for Video Reflection.
 *
 * @package   mod_videoreflection
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Restores activity settings, prompts, reflections and progress.
 */
class restore_videoreflection_activity_structure_step extends restore_activity_structure_step {
    /**
     * Defines XML paths handled by this step.
     *
     * @return restore_path_element[]
     */
    protected function define_structure(): array {
        $paths = [
            new restore_path_element('videoreflection', '/activity/videoreflection'),
            new restore_path_element('videoreflection_question', '/activity/videoreflection/questions/question'),
        ];
        if ($this->get_setting_value('userinfo')) {
            $paths[] = new restore_path_element('videoreflection_entry', '/activity/videoreflection/entries/entry');
            $paths[] = new restore_path_element('videoreflection_progress', '/activity/videoreflection/progresses/progress');
        }
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Restores the activity record.
     *
     * @param array $data Backup data.
     * @return void
     */
    protected function process_videoreflection(array $data): void {
        global $DB;
        $record = (object)$data;
        $oldid = $record->id;
        $record->course = $this->get_courseid();
        $record->id = $DB->insert_record('videoreflection', $record);
        $this->apply_activity_instance($record->id);
        $this->set_mapping('videoreflection', $oldid, $record->id, true);
    }

    /**
     * Restores a teacher prompt.
     *
     * @param array $data Backup data.
     * @return void
     */
    protected function process_videoreflection_question(array $data): void {
        global $DB;
        $record = (object)$data;
        $oldid = $record->id;
        $record->videoreflectionid = $this->get_new_parentid('videoreflection');
        $record->id = $DB->insert_record('videoreflection_questions', $record);
        $this->set_mapping('videoreflection_question', $oldid, $record->id);
    }

    /**
     * Restores a learner reflection.
     *
     * @param array $data Backup data.
     * @return void
     */
    protected function process_videoreflection_entry(array $data): void {
        global $DB;
        $record = (object)$data;
        $record->videoreflectionid = $this->get_new_parentid('videoreflection');
        $record->userid = $this->get_mappingid('user', $record->userid, 0);
        if (!empty($record->questionid)) {
            $record->questionid = $this->get_mappingid('videoreflection_question', $record->questionid, 0);
        }
        if ($record->userid) {
            $DB->insert_record('videoreflection_entries', $record);
        }
    }

    /**
     * Restores watched progress.
     *
     * @param array $data Backup data.
     * @return void
     */
    protected function process_videoreflection_progress(array $data): void {
        global $DB;
        $record = (object)$data;
        $record->videoreflectionid = $this->get_new_parentid('videoreflection');
        $record->userid = $this->get_mappingid('user', $record->userid, 0);
        if ($record->userid) {
            $DB->insert_record('videoreflection_progress', $record);
        }
    }

    /**
     * Restores activity files after records exist.
     *
     * @return void
     */
    protected function after_execute(): void {
        $this->add_related_files('mod_videoreflection', 'video', null);
        $this->add_related_files('mod_videoreflection', 'poster', null);
    }
}
