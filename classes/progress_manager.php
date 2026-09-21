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
 * Maintains server-calculated unique watched progress.
 *
 * @package   mod_videoreflection
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class progress_manager {
    /**
     * Maximum accepted contiguous heartbeat segment in seconds.
     */
    private const MAX_SEGMENT = 20.0;

    /**
     * Returns the user's progress record or an unsaved default record.
     *
     * @param int $activityid Activity id.
     * @param int $userid User id.
     * @return \stdClass
     */
    public function get_progress(int $activityid, int $userid): \stdClass {
        global $DB;

        $record = $DB->get_record('videoreflection_progress', [
            'videoreflectionid' => $activityid,
            'userid' => $userid,
        ]);
        if ($record) {
            return $record;
        }
        return (object)[
            'id' => 0,
            'videoreflectionid' => $activityid,
            'userid' => $userid,
            'duration' => 0,
            'lastposition' => 0,
            'uniquewatched' => 0,
            'totalwatchtime' => 0,
            'percent' => 0,
            'watchedsegments' => '[]',
            'completed' => 0,
            'sequence' => 0,
            'timecreated' => 0,
            'timemodified' => 0,
        ];
    }

    /**
     * Merges a trusted-sized playback heartbeat into the stored watched segments.
     *
     * @param int $activityid Activity id.
     * @param int $userid User id.
     * @param float $position Current playhead position.
     * @param float $duration Video duration.
     * @param float $watchedstart Start of newly watched continuous segment, or -1.
     * @param float $watchedend End of newly watched continuous segment, or -1.
     * @param int $sequence Client diagnostic sequence.
     * @return \stdClass Updated progress record.
     */
    public function update(int $activityid, int $userid, float $position, float $duration,
                           float $watchedstart, float $watchedend, int $sequence): \stdClass {
        global $DB;

        $now = time();
        $record = $this->get_progress($activityid, $userid);
        $record->duration = max((float)$record->duration, max(0.0, $duration));
        $maxposition = $record->duration > 0 ? $record->duration : max(0.0, $position);
        $record->lastposition = min(max(0.0, $position), $maxposition);
        $record->sequence = max((int)$record->sequence, $sequence);

        $segments = $this->decode_segments((string)$record->watchedsegments);
        if ($watchedstart >= 0 && $watchedend >= $watchedstart) {
            $start = max(0.0, $watchedstart);
            $end = $record->duration > 0 ? min($record->duration, $watchedend) : $watchedend;
            $length = $end - $start;
            if ($length > 0 && $length <= self::MAX_SEGMENT) {
                $segments[] = [$start, $end];
                $record->totalwatchtime = (float)$record->totalwatchtime + $length;
            }
        }

        $segments = $this->merge_segments($segments);
        $record->watchedsegments = json_encode($segments, JSON_UNESCAPED_SLASHES);
        $record->uniquewatched = $this->sum_segments($segments);
        $record->percent = $record->duration > 0
            ? min(100.0, round(((float)$record->uniquewatched / (float)$record->duration) * 100, 2))
            : 0.0;
        $record->timemodified = $now;

        if (empty($record->id)) {
            $record->timecreated = $now;
            $record->id = $DB->insert_record('videoreflection_progress', $record);
        } else {
            $DB->update_record('videoreflection_progress', $record);
        }
        return $record;
    }

    /**
     * Returns the furthest continuously watched point from video start.
     *
     * @param \stdClass $progress Progress record.
     * @return float
     */
    public function get_contiguous_end(\stdClass $progress): float {
        $segments = $this->decode_segments((string)$progress->watchedsegments);
        $end = 0.0;
        foreach ($segments as $segment) {
            if ((float)$segment[0] > $end + 1.5) {
                break;
            }
            $end = max($end, (float)$segment[1]);
        }
        return $end;
    }

    /**
     * Converts stored JSON into safe numeric segments.
     *
     * @param string $json Stored JSON.
     * @return array
     */
    private function decode_segments(string $json): array {
        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            return [];
        }
        $segments = [];
        foreach ($decoded as $segment) {
            if (!is_array($segment) || count($segment) !== 2 || !is_numeric($segment[0]) || !is_numeric($segment[1])) {
                continue;
            }
            $start = max(0.0, (float)$segment[0]);
            $end = max($start, (float)$segment[1]);
            if ($end > $start) {
                $segments[] = [$start, $end];
            }
        }
        return $segments;
    }

    /**
     * Merges overlapping or almost-adjacent playback segments.
     *
     * @param array $segments Segments.
     * @return array
     */
    private function merge_segments(array $segments): array {
        usort($segments, static function (array $a, array $b): int {
            return $a[0] <=> $b[0];
        });
        $merged = [];
        foreach ($segments as $segment) {
            if (!$merged) {
                $merged[] = $segment;
                continue;
            }
            $last = count($merged) - 1;
            if ($segment[0] <= $merged[$last][1] + 1.0) {
                $merged[$last][1] = max($merged[$last][1], $segment[1]);
            } else {
                $merged[] = $segment;
            }
        }
        return $merged;
    }

    /**
     * Sums unique duration represented by merged segments.
     *
     * @param array $segments Merged segments.
     * @return float
     */
    private function sum_segments(array $segments): float {
        $total = 0.0;
        foreach ($segments as $segment) {
            $total += max(0.0, (float)$segment[1] - (float)$segment[0]);
        }
        return round($total, 3);
    }
}
