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
 * External functions for Video Reflection.
 *
 * @package   mod_videoreflection
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'mod_videoreflection_update_progress' => [
        'classname' => 'mod_videoreflection\\external\\update_progress',
        'methodname' => 'execute',
        'description' => 'Store server-authoritative watched progress and resume position.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/videoreflection:view',
    ],
    'mod_videoreflection_add_reflection' => [
        'classname' => 'mod_videoreflection\\external\\add_reflection',
        'methodname' => 'execute',
        'description' => 'Create a learner reflection linked to the current video moment or a configured prompt.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/videoreflection:submit',
    ],
    'mod_videoreflection_delete_reflection' => [
        'classname' => 'mod_videoreflection\\external\\delete_reflection',
        'methodname' => 'execute',
        'description' => 'Delete one reflection owned by the current learner.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/videoreflection:submit',
    ],
];
