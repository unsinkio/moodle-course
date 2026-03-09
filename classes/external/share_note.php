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
 * Share a saved personal note with selected classmates.
 *
 * @package   format_videoclass
 */
class share_note extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'noteid'     => new external_value(PARAM_INT, 'Note ID to share'),
            'recipients' => new external_multiple_structure(
                new external_value(PARAM_INT, 'User ID of a recipient'),
                'List of recipient user IDs'
            ),
        ]);
    }

    public static function execute(int $noteid, array $recipients): array {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'noteid'     => $noteid,
            'recipients' => $recipients,
        ]);

        $note = $DB->get_record('format_videoclass_notes', [
            'id'     => $params['noteid'],
            'userid' => $USER->id,
        ], '*', MUST_EXIST);

        $context = \context_course::instance($note->courseid);
        self::validate_context($context);

        if (empty($params['recipients'])) {
            throw new \invalid_parameter_exception('At least one recipient is required.');
        }

        $now = time();
        $recipientnames = [];

        foreach ($params['recipients'] as $recipientid) {
            // Skip if already shared with this user.
            if ($DB->record_exists('format_videoclass_note_recipients', [
                'noteid' => $note->id,
                'userid' => $recipientid,
            ])) {
                continue;
            }

            $DB->insert_record('format_videoclass_note_recipients', (object) [
                'noteid'    => $note->id,
                'userid'    => $recipientid,
                'timeshared' => $now,
            ]);

            $ruser = $DB->get_record('user', ['id' => $recipientid],
                'id,firstname,lastname,firstnamephonetic,lastnamephonetic,middlename,alternatename');
            if ($ruser) {
                $recipientnames[] = fullname($ruser);
            }
        }

        return [
            'success'        => true,
            'recipientnames' => implode(', ', $recipientnames),
        ];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success'        => new external_value(PARAM_BOOL, 'Whether sharing succeeded'),
            'recipientnames' => new external_value(PARAM_TEXT, 'Comma-separated recipient names'),
        ]);
    }
}
