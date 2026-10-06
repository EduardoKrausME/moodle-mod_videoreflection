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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace mod_videoreflection;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

/**
 * Runtime coverage for the activity backup and restore implementation.
 *
 * @package   mod_videoreflection
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversNothing
 */
final class backup_restore_test extends \advanced_testcase {
    /**
     * Verifies that an activity can be backed up and restored in the same course.
     *
     * @return void
     */
    public function test_backup_restore_round_trip(): void {
        global $DB, $USER;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $activity = $this->getDataGenerator()->create_module('videoreflection', [
            'course' => $course->id,
            'name' => 'Backup round-trip',
            'videosource' => 'url',
            'videourl' => 'https://example.com/video.mp4',
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
            'completionpercent' => 80,
            'completionreflections' => 1,
            'completionrequiredquestions' => 0,
        ]);
        $cm = get_coursemodule_from_instance('videoreflection', $activity->id, $course->id, false, MUST_EXIST);

        $questionid = $DB->insert_record('videoreflection_questions', (object)[
            'videoreflectionid' => $activity->id,
            'questiontext' => 'What did you learn?',
            'timepoint' => 10,
            'required' => 0,
            'purpose' => 'reflection',
            'sortorder' => 1,
            'enabled' => 1,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);
        $DB->insert_record('videoreflection_entries', (object)[
            'videoreflectionid' => $activity->id,
            'questionid' => $questionid,
            'userid' => $USER->id,
            'timepoint' => 10,
            'entrytype' => 'reflection',
            'reflectiontext' => 'Backup test reflection',
            'shared' => 0,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);
        $DB->insert_record('videoreflection_progress', (object)[
            'videoreflectionid' => $activity->id,
            'userid' => $USER->id,
            'duration' => 120,
            'lastposition' => 90,
            'uniquewatched' => 90,
            'totalwatchtime' => 95,
            'percent' => 75,
            'watchedsegments' => '[[0,90]]',
            'completed' => 0,
            'sequence' => 1,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $controller = new \backup_controller(
            \backup::TYPE_1ACTIVITY,
            $cm->id,
            \backup::FORMAT_MOODLE,
            \backup::INTERACTIVE_NO,
            \backup::MODE_GENERAL,
            $USER->id
        );
        foreach (['blocks'] as $settingname) {
            $plan = $controller->get_plan();
            if (!$plan->setting_exists($settingname)) {
                continue;
            }
            $setting = $plan->get_setting($settingname);
            if ($setting->get_status() !== \base_setting::NOT_LOCKED) {
                $setting->set_status(\base_setting::NOT_LOCKED);
            }
            if ($setting->get_status() === \base_setting::NOT_LOCKED) {
                $setting->set_value(false);
            }
        }

        $backupid = $controller->get_backupid();
        $controller->execute_plan();
        $controller->destroy();

        $restore = new \restore_controller(
            $backupid,
            $course->id,
            \backup::INTERACTIVE_NO,
            \backup::MODE_GENERAL,
            $USER->id,
            \backup::TARGET_CURRENT_ADDING
        );
        $this->assertTrue($restore->execute_precheck());
        $restore->execute_plan();
        $restore->destroy();

        $records = $DB->get_records('videoreflection', ['course' => $course->id], 'id ASC');
        $this->assertCount(2, $records);
        $restored = end($records);
        $this->assertSame('Backup round-trip', $restored->name);
        $this->assertSame(80, (int)$restored->completionpercent);
        $this->assertEquals(
            1,
            $DB->count_records('videoreflection_questions', ['videoreflectionid' => $restored->id])
        );
        $this->assertEquals(
            1,
            $DB->count_records('videoreflection_entries', ['videoreflectionid' => $restored->id])
        );
        $this->assertEquals(
            1,
            $DB->count_records('videoreflection_progress', ['videoreflectionid' => $restored->id])
        );
    }
}
