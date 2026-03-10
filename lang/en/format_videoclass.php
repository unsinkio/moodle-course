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
 * Strings for VideoClass format.
 *
 * @package   format_videoclass
 * @copyright 2026 Atlantis University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'VideoClass';
$string['pluginname_help'] = 'A structured, media-driven format where short instructional videos serve as the primary content delivery method for each section.';
$string['coursedescription'] = 'Course Description';
$string['sectionname'] = 'Topic';
$string['section0name'] = 'General';
$string['section1name'] = 'Topic 1';

// Sidebar.
$string['sidebarheader'] = 'Topics';
$string['sidebarempty'] = 'No topics available.';
$string['sidebarnavlabel'] = 'Course topics';

// Tabs.
$string['tabresources'] = 'Resources';
$string['tabassessments'] = 'Assessments';
$string['tabqa'] = 'Q&A';
$string['tabmynotes'] = 'My Notes';
$string['tabsharednotes'] = 'Shared Notes';
$string['tabslabel'] = 'Section resources';

// Section content.
$string['editsection'] = 'Edit this topic';
$string['videoplaceholder'] = 'Add a video to the topic summary to display it here.';
$string['noresources'] = 'No resources assigned to this topic.';
$string['noassessments'] = 'No assessments assigned to this topic.';
$string['noqaprefix'] = 'Add activities with the [Q&A] prefix or forums to show them here.';

// My Notes.
$string['nomynotes'] = 'No notes for this topic yet. Write one above!';
$string['savenote'] = 'Save';
$string['mynoteplaceholder'] = 'Write a personal note...';
$string['deletemynote'] = 'Delete';
$string['deletemynoteconfirm'] = 'Are you sure you want to delete this note?';
$string['sharethistnote'] = 'Share';
$string['unsharenote'] = 'Unshare';
$string['sharedbadge'] = 'Shared';
$string['sharedwith'] = 'Shared with: ';
$string['searchstudents'] = 'Search classmates...';
$string['sharenoteprompt'] = 'Select classmates to share this note with:';

// Shared Notes (Shared with me).
$string['nosharednotes'] = 'No classmates have shared notes with you for this topic yet.';
$string['sharedby'] = 'Shared by';

// AI Tutor.
$string['aitutor'] = 'AI Tutor';
$string['aitutorplaceholder'] = 'Ask the tutor about this topic...';
$string['aitutorsend'] = 'Send';
$string['aitutorcollapse'] = 'Hide tutor';
$string['aitutorexpand'] = 'Show AI Tutor';
$string['aitutorsavetonotes'] = 'Save to Notes';
$string['aitutorsaved'] = 'Saved!';
$string['aitutorclear'] = 'New conversation';
$string['aitutorclearconfirm'] = 'Start a new conversation? Chat history will be cleared.';
$string['aitutorwelcome'] = 'Hi! I am your AI tutor for this section. Ask me anything about the resources and content here.';
$string['aitutorerror'] = 'Sorry, something went wrong. Please try again.';
$string['aitutortyping'] = 'Thinking...';
$string['aitutorconversations'] = 'Conversations';
$string['aitutornewconversation'] = 'New conversation';
$string['aitutornoconversations'] = 'No conversations yet.';
$string['aitutordeleteconversation'] = 'Delete conversation';
$string['aitutordeleteconfirm'] = 'Delete this conversation? All messages will be lost.';
$string['aitutorlogoalt'] = 'ATLAS AI Tutor';

// Settings.
$string['settings_aitutor_heading'] = 'AI Tutor';
$string['settings_aitutor_desc'] = 'Configure the AI tutor chatbot that helps students with section content.';
$string['settings_campusmcp_url'] = 'CampusMCP URL';
$string['settings_campusmcp_url_desc'] = 'Base URL of the CampusMCP server (without trailing slash).';
$string['settings_campusmcp_apikey'] = 'CampusMCP API Key';
$string['settings_campusmcp_apikey_desc'] = 'Shared secret (Bearer token) for authenticating with CampusMCP.';
$string['settings_aitutor_prompt'] = 'System Prompt';
$string['settings_aitutor_prompt_desc'] = 'The system prompt sent to the AI. Use placeholders: <code>{coursename}</code>, <code>{sectionname}</code>, <code>{resources}</code>';
