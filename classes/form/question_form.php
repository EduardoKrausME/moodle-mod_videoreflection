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

namespace mod_videoreflection\form;

use moodleform;

/**
 * Form used to create and edit teacher reflection prompts.
 *
 * @package   mod_videoreflection
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_form extends moodleform {
    /**
     * Defines prompt fields.
     *
     * @return void
     */
    public function definition(): void {
        $mform = $this->_form;
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'questionid');
        $mform->setType('questionid', PARAM_INT);

        $mform->addElement('textarea', 'questiontext', get_string('questiontext', 'videoreflection'), [
            'rows' => 5,
            'cols' => 80,
        ]);
        $mform->setType('questiontext', PARAM_TEXT);
        $mform->addRule('questiontext', null, 'required', null, 'client');

        $mform->addElement('text', 'timecode', get_string('timecode', 'videoreflection'), ['size' => 20]);
        $mform->setType('timecode', PARAM_RAW_TRIMMED);
        $mform->addHelpButton('timecode', 'timecode', 'videoreflection');

        $mform->addElement('select', 'purpose', get_string('purpose', 'videoreflection'), [
            'reflection' => get_string('purpose_reflection', 'videoreflection'),
            'doubt' => get_string('purpose_doubt', 'videoreflection'),
        ]);
        $mform->setDefault('purpose', 'reflection');
        $mform->addElement('advcheckbox', 'required', get_string('required', 'videoreflection'));
        $mform->addElement('selectyesno', 'enabled', get_string('enabled', 'videoreflection'));
        $mform->setDefault('enabled', 1);

        $this->add_action_buttons(true);
    }

    /**
     * Validates the entered timecode.
     *
     * @param array $data Submitted data.
     * @param array $files Submitted files.
     * @return array
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        if (\mod_videoreflection\reflection_manager::parse_time((string)($data['timecode'] ?? '')) === null) {
            $errors['timecode'] = get_string('invalidtimecode', 'videoreflection');
        }
        return $errors;
    }
}
