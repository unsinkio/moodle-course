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
 * External function: share a note.
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
use external_single_structure;
use external_value;

class share_note extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid'  => new external_value(PARAM_INT, 'Course ID'),
            'sectionid' => new external_value(PARAM_INT, 'Section ID'),
            'content'   => new external_value(PARAM_TEXT, 'Note content'),
        ]);
    }

    public static function execute(int $courseid, int $sectionid, string $content): array {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid'  => $courseid,
            'sectionid' => $sectionid,
            'content'   => $content,
        ]);

        $context = \context_course::instance($params['courseid']);
        self::validate_context($context);

        // Must be enrolled.
        if (!is_enrolled($context, $USER, '', true)) {
            throw new \moodle_exception('notenrolled', 'error');
        }

        $content = clean_param(trim($params['content']), PARAM_TEXT);
        if (empty($content)) {
            throw new \invalid_parameter_exception('Content cannot be empty.');
        }

        $record = (object) [
            'courseid'    => $params['courseid'],
            'sectionid'   => $params['sectionid'],
            'userid'      => $USER->id,
            'content'     => $content,
            'timecreated' => time(),
        ];

        $record->id = $DB->insert_record('format_videoclass_shared_notes', $record);

        return [
            'id'             => $record->id,
            'content'        => $record->content,
            'authorfullname' => fullname($USER),
            'timecreated'    => userdate($record->timecreated, get_string('strftimedatetimeshort', 'langconfig')),
        ];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'id'             => new external_value(PARAM_INT, 'Note ID'),
            'content'        => new external_value(PARAM_TEXT, 'Note content'),
            'authorfullname' => new external_value(PARAM_TEXT, 'Author full name'),
            'timecreated'    => new external_value(PARAM_TEXT, 'Formatted creation time'),
        ]);
    }
}
