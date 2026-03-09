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
 * External function: get shared notes visible to the current user.
 *
 * @package   format_videoclass
 * @copyright 2026 Atlantis University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_videoclass\external;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/externallib.php");

use external_api;
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;

class get_shared_notes extends external_api {

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

        $isadmin = has_capability('moodle/course:update', $context);

        // Get notes that are either:
        // 1. Broadcast (no recipients) → visible to all
        // 2. Targeted to this user
        // 3. Created by this user
        // 4. Admin sees all
        $sql = "SELECT DISTINCT n.*, u.firstname, u.lastname, u.firstnamephonetic,
                       u.lastnamephonetic, u.middlename, u.alternatename
                  FROM {format_videoclass_shared_notes} n
                  JOIN {user} u ON u.id = n.userid
             LEFT JOIN {format_videoclass_note_recipients} r ON r.noteid = n.id
                 WHERE n.courseid = :courseid
                   AND n.sectionid = :sectionid
                   AND (
                       n.userid = :myid1
                       OR r.userid = :myid2
                       OR NOT EXISTS (
                           SELECT 1 FROM {format_videoclass_note_recipients} r2 WHERE r2.noteid = n.id
                       )
                   )
              ORDER BY n.timecreated DESC";

        if ($isadmin) {
            // Admins see all notes.
            $sql = "SELECT n.*, u.firstname, u.lastname, u.firstnamephonetic,
                           u.lastnamephonetic, u.middlename, u.alternatename
                      FROM {format_videoclass_shared_notes} n
                      JOIN {user} u ON u.id = n.userid
                     WHERE n.courseid = :courseid AND n.sectionid = :sectionid
                  ORDER BY n.timecreated DESC";
            $sqlparams = [
                'courseid'  => $params['courseid'],
                'sectionid' => $params['sectionid'],
            ];
        } else {
            $sqlparams = [
                'courseid'  => $params['courseid'],
                'sectionid' => $params['sectionid'],
                'myid1'     => $USER->id,
                'myid2'     => $USER->id,
            ];
        }

        $records = $DB->get_records_sql($sql, $sqlparams);

        $notes = [];
        foreach ($records as $r) {
            // Get recipient names for display.
            $recipients = $DB->get_records_sql(
                "SELECT u.id, u.firstname, u.lastname, u.firstnamephonetic, u.lastnamephonetic, u.middlename, u.alternatename
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
                'authorfullname' => fullname($r),
                'timecreated'    => userdate($r->timecreated, get_string('strftimedatetimeshort', 'langconfig')),
                'candelete'      => ($isadmin || (int) $r->userid === (int) $USER->id),
                'recipientnames' => implode(', ', $recipientnames),
                'isbroadcast'    => empty($recipientnames),
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
                'timecreated'    => new external_value(PARAM_TEXT, 'Formatted creation time'),
                'candelete'      => new external_value(PARAM_BOOL, 'Whether current user can delete'),
                'recipientnames' => new external_value(PARAM_TEXT, 'Comma-separated recipient names'),
                'isbroadcast'    => new external_value(PARAM_BOOL, 'Whether shared with everyone'),
            ])
        );
    }
}
