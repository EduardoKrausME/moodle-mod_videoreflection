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
 * English strings for Video Reflection.
 *
 * @package   mod_videoreflection
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$string['activitycomplete'] = 'Activity complete';
$string['activityincomplete'] = 'Activity in progress';
$string['addquestion'] = 'Add reflection prompt';
$string['addreflection'] = 'Add reflection at this moment';
$string['allowseek'] = 'Allow seeking to unwatched positions';
$string['allowseek_help'] = 'When disabled, learners cannot jump forward beyond the furthest contiguous watched position. They can still seek backward.';
$string['allparticipants'] = 'All participants';
$string['answerprompt'] = 'Respond to this prompt';
$string['averageprogress'] = 'Average watched progress';
$string['backtoactivity'] = 'Back to activity';
$string['backtoreport'] = 'Back to class report';
$string['cancel'] = 'Cancel';
$string['classreport'] = 'Class report';
$string['completiondetail:percent'] = 'Watch at least {$a}% of the video';
$string['completiondetail:reflections'] = 'Submit at least {$a} reflection(s)';
$string['completiondetail:requiredquestions'] = 'Answer all required reflection prompts';
$string['completionpercent'] = 'Minimum watched percentage';
$string['completionpercent_help'] = 'The unique portion of the video that must be watched. Rewatching the same segment does not increase this percentage.';
$string['completionreflections'] = 'Minimum number of reflections';
$string['completionreflections_help'] = 'Minimum number of reflections or doubts the learner must submit. Required prompts must also be answered.';
$string['completionrequiredquestions'] = 'Require all prompts marked as required';
$string['completionrequiredquestions_help'] = 'When enabled, every enabled reflection prompt marked as required must be answered before the activity can be completed.';
$string['completionrules'] = '';
$string['deletequestion'] = 'Delete reflection prompt';
$string['deletequestionconfirm'] = 'Delete this reflection prompt? Existing learner reflections linked to it will be preserved as free reflections.';
$string['deletereflection'] = 'Delete';
$string['doubtcount'] = 'Doubts';
$string['editquestion'] = 'Edit reflection prompt';
$string['enabled'] = 'Enabled';
$string['entrytype'] = 'Type';
$string['entrytype_doubt'] = 'Doubt / unclear point';
$string['entrytype_reflection'] = 'Reflection';
$string['errormaxfiles'] = 'Only one file can be uploaded.';
$string['eventcourse_module_viewed'] = 'Video Reflection viewed';
$string['generalprompt'] = 'General';
$string['individualreport'] = 'Individual report';
$string['invalidquestion'] = 'Invalid reflection prompt.';
$string['invalidtimecode'] = 'Invalid video time. Use seconds, MM:SS, or HH:MM:SS.';
$string['invalidvideourl'] = 'Enter a valid HTTP or HTTPS URL ending in .mp4, .webm, .ogv, .m4v or .mov.';
$string['invalidvimeo'] = 'Enter a valid Vimeo video URL or numeric video ID.';
$string['invalidyoutube'] = 'Enter a valid YouTube video URL or video ID.';
$string['lastaccess'] = 'Last update';
$string['managequestions'] = 'Manage reflection prompts';
$string['modulename'] = 'Video Reflection';
$string['modulenameplural'] = 'Video Reflections';
$string['moment'] = 'Moment';
$string['mostreflectedmoments'] = 'Most reflected moments';
$string['movedown'] = 'Move down';
$string['moveup'] = 'Move up';
$string['noactivities'] = 'No Video Reflection activities were found in this course.';
$string['noanalytics'] = 'There are not enough timed reflections to build the timeline analysis yet.';
$string['noquestions'] = 'No reflection prompts have been configured yet.';
$string['noreflections'] = 'No reflections yet.';
$string['participant'] = 'Participant';
$string['playbackheader'] = 'Playback';
$string['pluginadministration'] = 'Video Reflection administration';
$string['pluginname'] = 'Video Reflection';
$string['poster'] = 'Poster image';
$string['privacy:metadata:videoreflection_entries'] = 'Stores reflections and doubts submitted by learners.';
$string['privacy:metadata:videoreflection_entries:entrytype'] = 'Whether the entry is a reflection or a doubt.';
$string['privacy:metadata:videoreflection_entries:questionid'] = 'The optional teacher prompt identifier.';
$string['privacy:metadata:videoreflection_entries:reflectiontext'] = 'The reflection text submitted by the learner.';
$string['privacy:metadata:videoreflection_entries:shared'] = 'Whether the entry is shared with classmates.';
$string['privacy:metadata:videoreflection_entries:timecreated'] = 'When the reflection was created.';
$string['privacy:metadata:videoreflection_entries:timepoint'] = 'The video moment associated with the reflection.';
$string['privacy:metadata:videoreflection_entries:userid'] = 'The user who submitted the reflection.';
$string['privacy:metadata:videoreflection_entries:videoreflectionid'] = 'The Video Reflection activity identifier.';
$string['privacy:metadata:videoreflection_progress'] = 'Stores watched progress and resume information.';
$string['privacy:metadata:videoreflection_progress:lastposition'] = 'The latest video position used for resume playback.';
$string['privacy:metadata:videoreflection_progress:percent'] = 'The unique watched percentage.';
$string['privacy:metadata:videoreflection_progress:timemodified'] = 'When the progress was last updated.';
$string['privacy:metadata:videoreflection_progress:userid'] = 'The user whose progress is stored.';
$string['privacy:metadata:videoreflection_progress:videoreflectionid'] = 'The Video Reflection activity identifier.';
$string['privacy:metadata:videoreflection_progress:watchedsegments'] = 'The watched video segments used to calculate progress.';
$string['privacymode'] = 'Reflection visibility';
$string['private'] = 'Private: visible only to the learner and teachers';
$string['progress'] = 'Watched progress';
$string['purpose'] = 'Prompt category';
$string['purpose_doubt'] = 'Doubt / unclear point';
$string['purpose_reflection'] = 'Reflection';
$string['questionresponses'] = 'Responses by prompt';
$string['questiontext'] = 'Reflection prompt';
$string['reflectionadded'] = 'Reflection saved.';
$string['reflectioncount'] = 'Reflections';
$string['reflectiondeleted'] = 'Reflection deleted.';
$string['reflectionprompt'] = 'Write what this moment made you think, understand, question or connect to practice.';
$string['reflectionquestions'] = 'Reflection questions';
$string['reflectionrequired'] = 'Write a reflection before saving.';
$string['reflections'] = 'Reflections';
$string['reflectionsettings'] = 'Reflection settings';
$string['reflectiontext'] = 'Reflection';
$string['reflectiontoolong'] = 'The reflection is too long.';
$string['report'] = 'Report';
$string['required'] = 'Required for completion';
$string['requiredanswered'] = 'Required prompts answered';
$string['requiredtotal'] = 'Required prompts';
$string['resumeask'] = 'Ask before resuming';
$string['resumeautomatic'] = 'Automatically resume from the last position';
$string['resumeconfirm'] = 'Resume from {$a}?';
$string['resumefromstart'] = 'Always start from the beginning';
$string['resumeplayback'] = 'Resume playback';
$string['savetoreflection'] = 'Save reflection';
$string['seekreflection'] = 'Go to {$a}';
$string['shared'] = 'Shared: visible to classmates after submission';
$string['sharedreflections'] = 'Class reflections';
$string['sortorder'] = 'Order';
$string['sourceheader'] = 'Video source';
$string['sourceupload'] = 'Uploaded video';
$string['sourceurl'] = 'Direct media URL';
$string['sourcevimeo'] = 'Vimeo';
$string['sourceyoutube'] = 'YouTube';
$string['timecode'] = 'Video time';
$string['timecode_help'] = 'Leave empty for a general prompt. Otherwise use seconds, MM:SS, or HH:MM:SS.';
$string['timedprompt'] = 'At {$a}';
$string['totaldoubts'] = 'Total doubts';
$string['totalparticipants'] = 'Participants';
$string['totalreflections'] = 'Total reflections';
$string['videofile'] = 'Video file';
$string['videoreflection:addinstance'] = 'Add a new Video Reflection activity';
$string['videoreflection:managequestions'] = 'Manage reflection prompts';
$string['videoreflection:submit'] = 'Submit reflections';
$string['videoreflection:view'] = 'View Video Reflection activities';
$string['videoreflection:viewreports'] = 'View Video Reflection reports';
$string['videoreflection:viewshared'] = 'View reflections shared with the class';
$string['videoreflectionname'] = 'Video Reflection name';
$string['videosource'] = 'Video source';
$string['videourl'] = 'Video URL or video ID';
$string['videourl_help'] = 'For Direct media URL, enter a browser-playable media URL. For YouTube or Vimeo, you may paste the normal video URL or only its video ID.';
$string['viewdetails'] = 'View details';
$string['yourreflections'] = 'Your reflections';
