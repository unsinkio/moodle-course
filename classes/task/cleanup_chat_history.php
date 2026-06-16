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
 * Scheduled task to clean up old AI tutor chat history.
 *
 * Deletes conversations and messages older than the configured retention period.
 *
 * @package   format_videoclass
 * @copyright 2026 Atlantis University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_videoclass\task;

defined('MOODLE_INTERNAL') || die();

class cleanup_chat_history extends \core\task\scheduled_task {

    /**
     * Return the task name.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('task_cleanup_chat', 'format_videoclass');
    }

    /**
     * Execute the cleanup task.
     */
    public function execute(): void {
        global $DB;

        $days = (int) get_config('format_videoclass', 'chat_retention_days');
        if ($days <= 0) {
            // 0 = keep forever.
            mtrace('  Chat retention set to 0 (keep forever). Skipping cleanup.');
            return;
        }

        $cutoff = time() - ($days * DAYSECS);
        mtrace("  Cleaning up chat data older than {$days} days (before " . userdate($cutoff) . ")...");

        // 1. Find old conversations.
        $oldconvids = $DB->get_fieldset_select(
            'format_videoclass_chat_conversations',
            'id',
            'timemodified < :cutoff',
            ['cutoff' => $cutoff]
        );

        if (empty($oldconvids)) {
            mtrace('  No old conversations found. Done.');
            return;
        }

        $count = count($oldconvids);

        // 2. Delete messages for old conversations.
        list($insql, $inparams) = $DB->get_in_or_equal($oldconvids, SQL_PARAMS_NAMED, 'conv');
        $DB->delete_records_select('format_videoclass_chat_history', "conversationid {$insql}", $inparams);

        // 3. Delete recipient records for old conversations.
        $DB->delete_records_select('format_videoclass_chat_conv_recipients', "conversationid {$insql}", $inparams);

        // 4. Delete the conversations themselves.
        $DB->delete_records_select('format_videoclass_chat_conversations', "id {$insql}", $inparams);

        mtrace("  Deleted {$count} old conversations and their associated data.");
    }
}
