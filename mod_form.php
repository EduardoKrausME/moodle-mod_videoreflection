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
 * Main activity form for Video Reflection.
 *
 * @package   mod_videoreflection
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/course/moodleform_mod.php');

/**
 * Defines the Video Reflection activity settings form.
 */
class mod_videoreflection_mod_form extends moodleform_mod {
    /**
     * Defines form fields.
     *
     * @return void
     */
    public function definition(): void {
        $mform = $this->_form;

        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement('text', 'name', get_string('videoreflectionname', 'videoreflection'), ['size' => 64]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $this->standard_intro_elements();

        $mform->addElement('html', '<h3>' . get_string('sourceheader', 'videoreflection') . '</h3>');
        $mform->addElement('select', 'videosource', get_string('videosource', 'videoreflection'), [
            'upload' => get_string('sourceupload', 'videoreflection'),
            'url' => get_string('sourceurl', 'videoreflection'),
            'youtube' => get_string('sourceyoutube', 'videoreflection'),
            'vimeo' => get_string('sourcevimeo', 'videoreflection'),
        ]);
        $mform->setDefault('videosource', 'upload');
        $mform->setType('videosource', PARAM_ALPHA);

        $mform->addElement('filemanager', 'videofile', get_string('videofile', 'videoreflection'), null, [
            'subdirs' => 0,
            'accepted_types' => ['.mp4', '.webm', '.ogv', '.m4v', '.mov'],
        ]);
        $mform->hideIf('videofile', 'videosource', 'neq', 'upload');

        $mform->addElement('text', 'videourl', get_string('videourl', 'videoreflection'), ['size' => 80]);
        $mform->setType('videourl', PARAM_RAW_TRIMMED);
        $mform->addHelpButton('videourl', 'videourl', 'videoreflection');
        $mform->hideIf('videourl', 'videosource', 'eq', 'upload');

        $mform->addElement('filemanager', 'poster', get_string('poster', 'videoreflection'), null, [
            'subdirs' => 0,
            'accepted_types' => ['image'],
        ]);

        $mform->addElement('html', '<h3>' . get_string('playbackheader', 'videoreflection') . '</h3>');
        $mform->addElement('select', 'resumeplayback', get_string('resumeplayback', 'videoreflection'), [
            1 => get_string('resumeautomatic', 'videoreflection'),
            2 => get_string('resumeask', 'videoreflection'),
            0 => get_string('resumefromstart', 'videoreflection'),
        ]);
        $mform->setDefault('resumeplayback', 1);
        $mform->addElement('selectyesno', 'allowseek', get_string('allowseek', 'videoreflection'));
        $mform->setDefault('allowseek', 1);
        $mform->addHelpButton('allowseek', 'allowseek', 'videoreflection');

        $mform->addElement('html', '<h3>' . get_string('reflectionsettings', 'videoreflection') . '</h3>');
        $mform->addElement('select', 'privacymode', get_string('privacymode', 'videoreflection'), [
            0 => get_string('private', 'videoreflection'),
            1 => get_string('shared', 'videoreflection'),
        ]);
        $mform->setDefault('privacymode', 0);

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    /**
     * Validates submitted activity settings.
     *
     * @param array $data Submitted data.
     * @param array $files Submitted files.
     * @return array Validation errors.
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        $percentname = $this->get_suffixed_name('completionpercent');
        $reflectionsname = $this->get_suffixed_name('completionreflections');
        $percent = (int)($data[$percentname] ?? 0);
        if ($percent < 0 || $percent > 100) {
            $errors[$percentname] = get_string('completionpercent', 'videoreflection') . ': 0-100';
        }
        if ((int)($data[$reflectionsname] ?? 0) < 0) {
            $errors[$reflectionsname] = get_string('completionreflections', 'videoreflection') . ': >= 0';
        }
        $source = (string)($data['videosource'] ?? 'upload');
        $value = trim((string)($data['videourl'] ?? ''));
        if ($source === 'upload') {
            $draftid = (int)($data['videofile'] ?? 0);
            if ($draftid <= 0 || empty(file_get_draft_area_info($draftid)['filecount'])) {
                $errors['videofile'] = get_string('required');
            }
        } else if ($value === '') {
            $errors['videourl'] = get_string('required');
        } else if ($source === 'url') {
            $scheme = strtolower((string)parse_url($value, PHP_URL_SCHEME));
            $extension = strtolower(pathinfo((string)parse_url($value, PHP_URL_PATH), PATHINFO_EXTENSION));
            if (!filter_var($value, FILTER_VALIDATE_URL) || !in_array($scheme, ['http', 'https'], true) ||
                !in_array($extension, ['mp4', 'webm', 'ogv', 'm4v', 'mov'], true)) {
                $errors['videourl'] = get_string('invalidvideourl', 'videoreflection');
            }
        } else if ($source === 'youtube' && \mod_videoreflection\player_helper::youtube_id($value) === '') {
            $errors['videourl'] = get_string('invalidyoutube', 'videoreflection');
        } else if ($source === 'vimeo' && \mod_videoreflection\player_helper::vimeo_id($value) === '') {
            $errors['videourl'] = get_string('invalidvimeo', 'videoreflection');
        }
        foreach (['videofile', 'poster'] as $field) {
            $draftid = (int)($data[$field] ?? 0);
            if ($draftid > 0) {
                $draftinfo = file_get_draft_area_info($draftid);
                if ((int)$draftinfo['filecount'] > 1) {
                    $errors[$field] = get_string('errormaxfiles', 'videoreflection');
                }
            }
        }
        return $errors;
    }

    /**
     * Prepares existing video and poster file areas for editing.
     *
     * @param array $defaultvalues Existing activity values.
     * @return void
     */
    public function data_preprocessing(&$defaultvalues): void {
        parent::data_preprocessing($defaultvalues);
        foreach (['completionpercent', 'completionreflections', 'completionrequiredquestions'] as $field) {
            if (array_key_exists($field, $defaultvalues)) {
                $defaultvalues[$this->get_suffixed_name($field)] = $defaultvalues[$field];
            }
        }
        if (empty($this->current->instance)) {
            return;
        }
        $context = $this->context;
        $draftvideo = file_get_submitted_draft_itemid('videofile');
        file_prepare_draft_area($draftvideo, $context->id, 'mod_videoreflection', 'video', 0, [
            'subdirs' => 0,
            'maxfiles' => 1,
            'accepted_types' => ['.mp4', '.webm', '.ogv', '.m4v', '.mov'],
        ]);
        $defaultvalues['videofile'] = $draftvideo;

        $draftposter = file_get_submitted_draft_itemid('poster');
        file_prepare_draft_area($draftposter, $context->id, 'mod_videoreflection', 'poster', 0, [
            'subdirs' => 0,
            'maxfiles' => 1,
            'accepted_types' => ['image'],
        ]);
        $defaultvalues['poster'] = $draftposter;
    }

    /**
     * Adds custom completion rules to Moodle's completion section.
     *
     * @return array Field names used by completion.
     */
    public function add_completion_rules(): array {
        $mform = $this->_form;
        $percent = $this->get_suffixed_name('completionpercent');
        $reflections = $this->get_suffixed_name('completionreflections');
        $requiredquestions = $this->get_suffixed_name('completionrequiredquestions');

        $mform->addElement('text', $percent, get_string('completionpercent', 'videoreflection'), ['size' => 6]);
        $mform->setType($percent, PARAM_INT);
        $mform->setDefault($percent, 80);
        $mform->addHelpButton($percent, 'completionpercent', 'videoreflection');

        $mform->addElement('text', $reflections, get_string('completionreflections', 'videoreflection'), ['size' => 6]);
        $mform->setType($reflections, PARAM_INT);
        $mform->setDefault($reflections, 1);
        $mform->addHelpButton($reflections, 'completionreflections', 'videoreflection');

        $mform->addElement('advcheckbox', $requiredquestions, get_string('completionrequiredquestions', 'videoreflection'));
        $mform->setDefault($requiredquestions, 1);
        $mform->addHelpButton($requiredquestions, 'completionrequiredquestions', 'videoreflection');
        return [$percent, $reflections, $requiredquestions];
    }

    /**
     * Indicates whether custom completion rules are enabled.
     *
     * @param array $data Form data.
     * @return bool
     */
    public function completion_rule_enabled($data): bool {
        return !empty($data[$this->get_suffixed_name('completionpercent')]) ||
            !empty($data[$this->get_suffixed_name('completionreflections')]) ||
            !empty($data[$this->get_suffixed_name('completionrequiredquestions')]);
    }

    /**
     * Normalises suffixed completion fields back to database column names.
     *
     * @return stdClass|false
     */
    public function get_data() {
        $data = parent::get_data();
        if (!$data) {
            return $data;
        }
        foreach (['completionpercent', 'completionreflections', 'completionrequiredquestions'] as $field) {
            $suffixed = $this->get_suffixed_name($field);
            if (property_exists($data, $suffixed)) {
                $data->{$field} = $data->{$suffixed};
                unset($data->{$suffixed});
            }
        }
        return $data;
    }

    /**
     * Returns the completion form field name used by this activity.
     *
     * @param string $field Base field name.
     * @return string
     */
    private function get_suffixed_name(string $field): string {
        return $field . '_videoreflection';
    }
}
