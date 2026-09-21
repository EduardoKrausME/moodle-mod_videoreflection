# Video Reflection (mod_videoreflection)

Video Reflection is a Moodle activity module for reflection and metacognition around video. It combines watched-progress
tracking, resume playback, timeline markers, teacher-defined prompts and learner reflections attached to specific
moments.

The module was designed with `mod_videoprogress` as a reference for player, tracking, timeline, sources, resume,
completion and reporting concepts.

## Main features

- Video sources: uploaded file, direct media URL, YouTube and Vimeo.
- Real watched-progress tracking based on contiguous playback segments rather than only the final playhead position.
- Resume from the last position automatically, after confirmation, or always from the beginning.
- General reflection prompts or prompts linked to a specific video time.
- Required prompts that participate in activity completion.
- Learner-created reflections and doubts while the video is playing.
- Click any timed reflection or prompt to seek the player to that moment.
- Private mode or shared-class mode selected by the teacher.
- Completion rules for minimum watched percentage and minimum number of reflections, plus all required prompts.
- Individual and class reports, including the moments that concentrate the most reflections and doubts.
- Moodle Privacy API and activity backup/restore support.

## Requirements

- Moodle 4.4 or later.
- PHP version supported by the installed Moodle version.

## Installation

Copy the `videoreflection` directory to `mod/videoreflection` and visit Site administration > Notifications.
