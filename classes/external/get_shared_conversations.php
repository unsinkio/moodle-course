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
 * Get conversations shared with the current user.
 *
 * @package   format_videoclass
 */
class get_shared_conversations extends external_api {

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

        $sql = "SELECT c.id, c.title, c.timemodified, c.userid,
                       u.firstname, u.lastname, u.firstnamephonetic,
                       u.lastnamephonetic, u.middlename, u.alternatename
                  FROM {format_videoclass_chat_conv_recipients} r
                  JOIN {format_videoclass_chat_conversations} c ON c.id = r.conversationid
                  JOIN {user} u ON u.id = c.userid
                 WHERE r.userid = :recipientid
                   AND c.courseid = :courseid
                   AND c.sectionid = :sectionid
              ORDER BY c.timemodified DESC";

        $records = $DB->get_records_sql($sql, [
            'recipientid' => $USER->id,
            'courseid'    => $params['courseid'],
            'sectionid'   => $params['sectionid'],
        ]);

        $result = [];
        foreach ($records as $r) {
            $result[] = [
                'id'           => (int) $r->id,
                'title'        => $r->title,
                'ownername'    => fullname($r),
                'timemodified' => (int) $r->timemodified,
            ];
        }

        return $result;
    }

    public static function execute_returns(): external_multiple_structure {
        return new external_multiple_structure(
            new external_single_structure([
                'id'           => new external_value(PARAM_INT, 'Conversation ID'),
                'title'        => new external_value(PARAM_TEXT, 'Conversation title'),
                'ownername'    => new external_value(PARAM_TEXT, 'Owner full name'),
                'timemodified' => new external_value(PARAM_INT, 'Last modified timestamp'),
            ])
        );
    }
}
