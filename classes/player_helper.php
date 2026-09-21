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

namespace mod_videoreflection;

/**
 * Resolves configured video sources into browser-safe player configuration.
 *
 * @package   mod_videoreflection
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class player_helper {
    /**
     * Builds player source data for the current module context.
     *
     * @param \stdClass $activity Activity record.
     * @param \context_module $context Module context.
     * @return array
     */
    public static function get_config(\stdClass $activity, \context_module $context): array {
        $source = (string)$activity->videosource;
        $url = '';
        if ($source === 'upload') {
            $url = self::first_file_url($context, 'video');
        } else if ($source === 'youtube') {
            $url = self::youtube_id((string)$activity->videourl);
        } else if ($source === 'vimeo') {
            $url = self::vimeo_id((string)$activity->videourl);
        } else {
            $source = 'url';
            $url = trim((string)$activity->videourl);
        }

        return [
            'source' => $source,
            'url' => $url,
            'poster' => self::first_file_url($context, 'poster'),
        ];
    }

    /**
     * Returns a pluginfile URL for the first file in an area.
     *
     * @param \context_module $context Module context.
     * @param string $filearea File area.
     * @return string
     */
    private static function first_file_url(\context_module $context, string $filearea): string {
        $files = get_file_storage()->get_area_files(
            $context->id,
            'mod_videoreflection',
            $filearea,
            0,
            'filename ASC',
            false
        );
        if (!$files) {
            return '';
        }
        $file = reset($files);
        return \moodle_url::make_pluginfile_url(
            $context->id,
            'mod_videoreflection',
            $filearea,
            0,
            $file->get_filepath(),
            $file->get_filename(),
            false
        )->out(false);
    }

    /**
     * Extracts a YouTube video id from common URL forms.
     *
     * @param string $value URL or id.
     * @return string
     */
    public static function youtube_id(string $value): string {
        $value = trim($value);
        if (preg_match('/^[A-Za-z0-9_-]{6,20}$/', $value)) {
            return $value;
        }
        $patterns = [
            '/[?&]v=([A-Za-z0-9_-]{6,20})/',
            '#youtu\.be/([A-Za-z0-9_-]{6,20})#',
            '#youtube\.com/(?:embed|shorts)/([A-Za-z0-9_-]{6,20})#',
        ];
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $value, $match)) {
                return $match[1];
            }
        }
        return '';
    }

    /**
     * Extracts a Vimeo numeric video id.
     *
     * @param string $value URL or id.
     * @return string
     */
    public static function vimeo_id(string $value): string {
        $value = trim($value);
        if (preg_match('/^\d+$/', $value)) {
            return $value;
        }
        if (preg_match('#vimeo\.com/(?:video/)?(\d+)#', $value, $match)) {
            return $match[1];
        }
        return '';
    }
}
