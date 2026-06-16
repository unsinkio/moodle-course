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

namespace format_videoclass\external;

defined('MOODLE_INTERNAL') || die();

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * Get personal notes for a section (with sharing info).
 *
 * @package   format_videoclass
 */
class get_personal_notes extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid'  => new external_value(PARAM_INT, 'Course ID'),
            'sectionid' => new external_value(PARAM_INT, 'Section ID'),
        ]);
    }

    public static function execute(int $courseid, int $sectionid): array {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid'  => $courseid,
            'sectionid' => $sectionid,
        ]);

        $context = \context_course::instance($params['courseid']);
        self::validate_context($context);

        if (!is_enrolled($context, $USER, '', true)) {
            throw new \moodle_exception('notenrolled', 'error');
        }

        $records = $DB->get_records('format_videoclass_notes', [
            'courseid'  => $params['courseid'],
            'sectionid' => $params['sectionid'],
            'userid'    => $USER->id,
        ], 'timemodified DESC');

        $notes = [];
        foreach ($records as $r) {
            // Check for recipients (sharing info).
            $recipients = $DB->get_records_sql(
                "SELECT u.id, u.firstname, u.lastname, u.firstnamephonetic,
                        u.lastnamephonetic, u.middlename, u.alternatename
                   FROM {format_videoclass_note_recipients} nr
                   JOIN {user} u ON u.id = nr.userid
                  WHERE nr.noteid = :noteid",
                ['noteid' => $r->id]
            );

            $recipientnames = [];
            foreach ($recipients as $recip) {
                $recipientnames[] = fullname($recip);
            }

            $notes[] = [
                'id'             => (int) $r->id,
                'content'        => $r->content,
                'timecreated'    => userdate($r->timecreated, get_string('strftimedatetimeshort', 'langconfig')),
                'timemodified'   => userdate($r->timemodified, get_string('strftimedatetimeshort', 'langconfig')),
                'isshared'       => !empty($recipientnames),
                'recipientnames' => implode(', ', $recipientnames),
            ];
        }

        return $notes;
    }

    public static function execute_returns(): external_multiple_structure {
        return new external_multiple_structure(
            new external_single_structure([
                'id'             => new external_value(PARAM_INT, 'Note ID'),
                'content'        => new external_value(PARAM_TEXT, 'Note content'),
                'timecreated'    => new external_value(PARAM_TEXT, 'Created time'),
                'timemodified'   => new external_value(PARAM_TEXT, 'Modified time'),
                'isshared'       => new external_value(PARAM_BOOL, 'Whether note is shared'),
                'recipientnames' => new external_value(PARAM_TEXT, 'Comma-separated recipient names'),
            ])
        );
    }
}
