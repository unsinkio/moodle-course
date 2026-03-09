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

require_once("$CFG->libdir/externallib.php");

use external_api;
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;

/**
 * Get notes shared with the current user (Shared Notes tab).
 *
 * @package   format_videoclass
 */
class get_shared_with_me extends external_api {

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

        $sql = "SELECT n.*, u.firstname, u.lastname, u.firstnamephonetic,
                       u.lastnamephonetic, u.middlename, u.alternatename,
                       nr.timeshared
                  FROM {format_videoclass_note_recipients} nr
                  JOIN {format_videoclass_notes} n ON n.id = nr.noteid
                  JOIN {user} u ON u.id = n.userid
                 WHERE nr.userid = :myid
                   AND n.courseid = :courseid
                   AND n.sectionid = :sectionid
              ORDER BY nr.timeshared DESC";

        $records = $DB->get_records_sql($sql, [
            'myid'      => $USER->id,
            'courseid'  => $params['courseid'],
            'sectionid' => $params['sectionid'],
        ]);

        $notes = [];
        foreach ($records as $r) {
            $notes[] = [
                'id'             => (int) $r->id,
                'content'        => format_string($r->content),
                'authorfullname' => fullname($r),
                'timeshared'     => userdate($r->timeshared, get_string('strftimedatetimeshort', 'langconfig')),
            ];
        }

        return $notes;
    }

    public static function execute_returns(): external_multiple_structure {
        return new external_multiple_structure(
            new external_single_structure([
                'id'             => new external_value(PARAM_INT, 'Note ID'),
                'content'        => new external_value(PARAM_TEXT, 'Note content'),
                'authorfullname' => new external_value(PARAM_TEXT, 'Author full name'),
                'timeshared'     => new external_value(PARAM_TEXT, 'When it was shared'),
            ])
        );
    }
}
