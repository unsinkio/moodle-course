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
use external_single_structure;
use external_value;

/**
 * Save or update a personal note for a section.
 *
 * @package   format_videoclass
 * @copyright 2026 Atlantis University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class save_personal_note extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid'  => new external_value(PARAM_INT, 'Course ID'),
            'sectionid' => new external_value(PARAM_INT, 'Section ID'),
            'content'   => new external_value(PARAM_TEXT, 'Note content'),
            'noteid'    => new external_value(PARAM_INT, 'Note ID (0 for new)', VALUE_DEFAULT, 0),
        ]);
    }

    public static function execute(int $courseid, int $sectionid, string $content, int $noteid = 0): array {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid'  => $courseid,
            'sectionid' => $sectionid,
            'content'   => $content,
            'noteid'    => $noteid,
        ]);

        $context = \context_course::instance($params['courseid']);
        self::validate_context($context);

        if (!is_enrolled($context, $USER, '', true)) {
            throw new \moodle_exception('notenrolled', 'error');
        }

        $content = clean_param(trim($params['content']), PARAM_TEXT);
        if (empty($content)) {
            throw new \invalid_parameter_exception('Content cannot be empty.');
        }

        $now = time();

        if ($params['noteid'] > 0) {
            // Update existing note owned by this user.
            $note = $DB->get_record('format_videoclass_notes', [
                'id' => $params['noteid'],
                'userid' => $USER->id,
            ], '*', MUST_EXIST);

            $note->content = $content;
            $note->timemodified = $now;
            $DB->update_record('format_videoclass_notes', $note);
        } else {
            // Create new note.
            $note = (object) [
                'courseid'     => $params['courseid'],
                'sectionid'    => $params['sectionid'],
                'userid'       => $USER->id,
                'content'      => $content,
                'timecreated'  => $now,
                'timemodified' => $now,
            ];
            $note->id = $DB->insert_record('format_videoclass_notes', $note);
        }

        return [
            'id'           => (int) $note->id,
            'content'      => $note->content,
            'timecreated'  => userdate($note->timecreated ?? $now, get_string('strftimedatetimeshort', 'langconfig')),
            'timemodified' => userdate($note->timemodified ?? $now, get_string('strftimedatetimeshort', 'langconfig')),
        ];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'id'           => new external_value(PARAM_INT, 'Note ID'),
            'content'      => new external_value(PARAM_TEXT, 'Note content'),
            'timecreated'  => new external_value(PARAM_TEXT, 'Created time'),
            'timemodified' => new external_value(PARAM_TEXT, 'Modified time'),
        ]);
    }
}
