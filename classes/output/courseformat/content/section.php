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
 * Section output for VideoClass.
 *
 * @package   format_videoclass
 * @copyright 2026 Atlantis University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_videoclass\output\courseformat\content;

use core_courseformat\output\local\content\section as section_base;
use stdClass;

class section extends section_base
{
    private const RESOURCE_MODULES = [
        'resource',
        'url',
        'page',
        'book',
        'folder',
        'file',
        'imscp',
        'scorm',
        'h5pactivity'
    ];

    private const ASSESSMENT_MODULES = [
        'assign',
        'quiz',
        'lesson',
        'workshop',
        'feedback',
        'survey'
    ];

    private const QA_MODULES = [
        'forum',
        'chat',
        'choice',
        'wiki',
        'glossary'
    ];

    public function export_for_template(\renderer_base $output): stdClass
    {
        global $DB, $USER;

        $data = parent::export_for_template($output);

        // ── Pre-render any renderable / stdClass properties into plain strings ──
        // Moodle's parent export may return renderable objects for these;
        // our custom template outputs them with {{{...}}} which requires strings.

        // availability → string.
        if (isset($data->availability) && is_object($data->availability)) {
            try {
                $data->availability = $output->render($data->availability);
            } catch (\Throwable $e) {
                $data->availability = '';
            }
        }

        // summary.summarytext → string.
        if (isset($data->summary) && is_object($data->summary)) {
            if (isset($data->summary->summarytext) && is_object($data->summary->summarytext)) {
                try {
                    $data->summary->summarytext = $output->render($data->summary->summarytext);
                } catch (\Throwable $e) {
                    $data->summary->summarytext = '';
                }
            }
        }

        // cmcontrols → string.
        if (isset($data->cmcontrols) && is_object($data->cmcontrols)) {
            try {
                $data->cmcontrols = $output->render($data->cmcontrols);
            } catch (\Throwable $e) {
                $data->cmcontrols = '';
            }
        }

        // cmlist items may also contain renderables — sanitize recursively.
        if (isset($data->cmlist) && is_object($data->cmlist) && !empty($data->cmlist->cms)) {
            foreach ($data->cmlist->cms as $cmwrapper) {
                if (isset($cmwrapper->cmitem) && is_object($cmwrapper->cmitem)) {
                    $cmitem = $cmwrapper->cmitem;
                    // cmformat → cmname
                    if (isset($cmitem->cmformat) && is_object($cmitem->cmformat)) {
                        if (isset($cmitem->cmformat->cmname) && is_object($cmitem->cmformat->cmname)) {
                            try {
                                $cmitem->cmformat->cmname = $output->render($cmitem->cmformat->cmname);
                            } catch (\Throwable $e) {
                                $cmitem->cmformat->cmname = '';
                            }
                        }
                        if (isset($cmitem->cmformat->cmicon) && is_object($cmitem->cmformat->cmicon)) {
                            try {
                                $cmitem->cmformat->cmicon = $output->render($cmitem->cmformat->cmicon);
                            } catch (\Throwable $e) {
                                $cmitem->cmformat->cmicon = '';
                            }
                        }
                    }
                    // url → string
                    if (isset($cmitem->url) && $cmitem->url instanceof \moodle_url) {
                        $cmitem->url = $cmitem->url->out(false);
                    }
                }
                // cmcontrols on wrapper
                if (isset($cmwrapper->cmcontrols) && is_object($cmwrapper->cmcontrols)) {
                    try {
                        $cmwrapper->cmcontrols = $output->render($cmwrapper->cmcontrols);
                    } catch (\Throwable $e) {
                        $cmwrapper->cmcontrols = '';
                    }
                }
            }
        }

        $format = $this->format;
        $course = $format->get_course();
        $context = \context_course::instance($course->id);
        $sectioninfo = $this->section;
        $sectionid = $sectioninfo->id;

        // Build activity lists directly from modinfo (reliable source of truth).
        [$resources, $assessments, $qa] = $this->build_activity_lists($output);

        // Load personal notes for this user (with sharing info).
        $personalnotes = $this->load_personal_notes($course->id, $sectionid);

        // Load notes shared with the current user.
        $sharedwithme = $this->load_shared_with_me($course->id, $sectionid);

        $data->videoclass = (object) [
            'sectionsnav'    => $this->build_sections_nav(),
            'resources'      => $resources,
            'assessments'    => $assessments,
            'qa'             => $qa,
            'hasvideo'       => $this->summary_has_video($data),
            'editurl'        => (new \moodle_url('/course/editsection.php', ['id' => $data->id]))->out(false),
            'personalnotes'  => $personalnotes,
            'sharedwithme'   => $sharedwithme,
            'courseid'       => $course->id,
            'sectionid'      => $sectionid,
            'userid'         => $USER->id,
            'sesskey'        => sesskey(),
            'logourl'        => (new \moodle_url('/course/format/videoclass/pix/atlas_icon.png'))->out(false),
        ];

        return $data;
    }

    /**
     * Decide whether section summary contains a video embed or text.
     *
     * @param stdClass $data
     * @return bool
     */
    private function summary_has_video(stdClass $data): bool
    {
        if (empty($data->summary)) {
            return false;
        }

        // summary might be a renderable or a stdClass — extract text safely.
        $summarytext = '';
        if (is_string($data->summary)) {
            $summarytext = $data->summary;
        } elseif (is_object($data->summary) && isset($data->summary->summarytext)) {
            $st = $data->summary->summarytext;
            $summarytext = is_string($st) ? $st : '';
        }

        if ($summarytext === '') {
            return false;
        }

        if (preg_match('/<(iframe|video|embed|source)\b/i', $summarytext)) {
            return true;
        }

        return trim(strip_tags($summarytext)) !== '';
    }

    /**
     * Build activity lists directly from modinfo for this section.
     *
     * This bypasses the parent export's cmlist (which may have an opaque
     * structure) and reads cm_info objects directly — always reliable.
     *
     * @param \renderer_base $output
     * @return array [$resources, $assessments, $qa]
     */
    private function build_activity_lists(\renderer_base $output): array
    {
        $resources = [];
        $assessments = [];
        $qa = [];

        $format = $this->format;
        $modinfo = $format->get_modinfo();
        $section = $this->section;
        $editing = $format->show_editor();

        // Get all cm_info objects in this section.
        if (empty($modinfo->sections[$section->section])) {
            return [$resources, $assessments, $qa];
        }

        foreach ($modinfo->sections[$section->section] as $cmid) {
            $cminfo = $modinfo->get_cm($cmid);

            // Skip hidden or deleted modules.
            if (!$cminfo->uservisible) {
                continue;
            }

            // Skip labels (they have no URL).
            if ($cminfo->modname === 'label') {
                continue;
            }

            $name = $cminfo->name;

            // Determine bucket from name prefix first.
            $bucket = $this->bucket_from_label($name);

            // If no prefix match, classify by module type.
            if ($bucket === '') {
                if (in_array($cminfo->modname, self::QA_MODULES, true)) {
                    $bucket = 'qa';
                } else if (in_array($cminfo->modname, self::ASSESSMENT_MODULES, true)) {
                    $bucket = 'assessments';
                } else {
                    $bucket = 'resources';
                }
            }

            // Build icon HTML.
            $icon = '';
            $iconurl = $cminfo->get_icon_url($output);
            if ($iconurl) {
                $icon = '<img src="' . $iconurl->out(false) . '" class="activityicon" alt="" role="presentation">';
            }

            // Build URL.
            $url = $cminfo->url ? $cminfo->url->out(false) : '';

            // Build controls for editing mode.
            $cmcontrols = '';

            $item = (object) [
                'id'         => $cminfo->id,
                'url'        => $url,
                'name'       => format_string($name),
                'icon'       => $icon,
                'editing'    => $editing,
                'cmcontrols' => $cmcontrols,
            ];

            switch ($bucket) {
                case 'qa':
                    $qa[] = $item;
                    break;
                case 'assessments':
                    $assessments[] = $item;
                    break;
                default:
                    $resources[] = $item;
                    break;
            }
        }

        return [$resources, $assessments, $qa];
    }

    /**
     * Split course modules into resources, assessments, and Q&A buckets.
     *
     * @param array $cms
     * @return array
     */
    private function split_cms(array $cms): array
    {
        $resources = [];
        $assessments = [];
        $qa = [];

        // Build a cmid → modname map from modinfo so we can classify by module type.
        $modinfo = $this->format->get_modinfo();
        $cminfos = $modinfo->get_cms(); // Keyed by cmid.

        foreach ($cms as $cmwrapper) {
            if (empty($cmwrapper->cmitem)) {
                $resources[] = $cmwrapper;
                continue;
            }

            $cmitem = $cmwrapper->cmitem;

            // Extract the display name for prefix-based classification.
            $label = '';
            if (!empty($cmitem->cmformat) && !empty($cmitem->cmformat->cmname)) {
                $cmname = $cmitem->cmformat->cmname;
                if (is_string($cmname)) {
                    $label = strip_tags($cmname);
                } elseif (is_object($cmname) && method_exists($cmname, 'get_displayvalue')) {
                    $label = strip_tags($cmname->get_displayvalue());
                } elseif (is_object($cmname) && isset($cmname->displayvalue)) {
                    $label = strip_tags($cmname->displayvalue);
                }
            }

            $bucket = $this->bucket_from_label($label);

            // If no prefix match, classify by module type using modinfo.
            if ($bucket === '') {
                $cmid = $cmitem->id ?? 0;
                $modname = '';
                if ($cmid && isset($cminfos[$cmid])) {
                    $modname = $cminfos[$cmid]->modname;
                }

                if (in_array($modname, self::QA_MODULES, true)) {
                    $bucket = 'qa';
                } else if (in_array($modname, self::ASSESSMENT_MODULES, true)) {
                    $bucket = 'assessments';
                } else {
                    $bucket = 'resources';
                }
            }

            switch ($bucket) {
                case 'qa':
                    $qa[] = $cmwrapper;
                    break;
                case 'assessments':
                    $assessments[] = $cmwrapper;
                    break;
                default:
                    $resources[] = $cmwrapper;
                    break;
            }
        }

        return [$resources, $assessments, $qa];
    }

    /**
     * Determine bucket from name prefixes like [Q&A] or [Notas].
     *
     * @param string $label
     * @return string
     */
    private function bucket_from_label(string $label): string
    {
        $label = trim($label);
        if ($label === '') {
            return '';
        }

        if (preg_match('/^\[(qa|q&a|preguntas|foro)\]/i', $label)) {
            return 'qa';
        }

        if (preg_match('/^\[(nota|notas|notes?|assignment|assignments?|assessment|assessments?|tarea|tareas|quiz|examen)\]/i', $label)) {
            return 'assessments';
        }

        if (preg_match('/^\[(recurso|recursos|resource|material)\]/i', $label)) {
            return 'resources';
        }

        return '';
    }

    /**
     * Flatten cm renderables into simple arrays the template can iterate.
     *
     * @param array $cms  Array of cm wrapper objects from parent export.
     * @param \renderer_base $output
     * @return array  Flat list of objects with id, icon, url, name, editing, cmcontrols.
     */
    private function flatten_cmlist(array $cms, \renderer_base $output): array
    {
        $flat = [];
        foreach ($cms as $cmwrapper) {
            $cmitem = $cmwrapper->cmitem ?? null;
            if (!$cmitem) {
                continue;
            }

            // Safely extract URL.
            $url = '';
            if (!empty($cmitem->url)) {
                if ($cmitem->url instanceof \moodle_url) {
                    $url = $cmitem->url->out(false);
                } elseif (is_string($cmitem->url)) {
                    $url = $cmitem->url;
                }
            }

            // Safely extract name.
            $name = '';
            if (!empty($cmitem->cmformat) && !empty($cmitem->cmformat->cmname)) {
                $cmname = $cmitem->cmformat->cmname;
                if (is_string($cmname)) {
                    $name = strip_tags($cmname);
                } else {
                    try {
                        $name = strip_tags($output->render($cmname));
                    } catch (\Throwable $e) {
                        // Fallback: try common properties.
                        if (isset($cmname->displayvalue)) {
                            $name = strip_tags(is_string($cmname->displayvalue) ? $cmname->displayvalue : '');
                        } elseif (isset($cmname->name)) {
                            $name = strip_tags(is_string($cmname->name) ? $cmname->name : '');
                        }
                    }
                }
            }

            // Safely extract icon.
            $icon = '';
            if (!empty($cmitem->cmformat) && !empty($cmitem->cmformat->cmicon)) {
                $cmicon = $cmitem->cmformat->cmicon;
                if (is_string($cmicon)) {
                    $icon = $cmicon;
                } else {
                    try {
                        $icon = $output->render($cmicon);
                    } catch (\Throwable $e) {
                        $icon = '';
                    }
                }
            }

            // Safely extract controls.
            $cmcontrols = '';
            if (!empty($cmwrapper->cmcontrols)) {
                if (is_string($cmwrapper->cmcontrols)) {
                    $cmcontrols = $cmwrapper->cmcontrols;
                } else {
                    try {
                        $cmcontrols = $output->render($cmwrapper->cmcontrols);
                    } catch (\Throwable $e) {
                        $cmcontrols = '';
                    }
                }
            }

            $flat[] = (object) [
                'id'         => $cmitem->id ?? 0,
                'url'        => $url,
                'name'       => $name,
                'icon'       => $icon,
                'editing'    => !empty($cmwrapper->editing),
                'cmcontrols' => $cmcontrols,
            ];
        }
        return $flat;
    }

    /**
     * Build navigation items for all visible sections.
     *
     * @return array
     */
    private function build_sections_nav(): array
    {
        $format = $this->format;
        $course = $format->get_course();
        $modinfo = $format->get_modinfo();

        $current = $format->get_sectionnum();
        if ($current === null || $current < 0) {
            $current = 0;
        }

        $last = $format->get_last_section_number();
        $items = [];

        foreach ($modinfo->get_section_info_all() as $section) {
            if (!$section) {
                continue;
            }

            if ($section->section > $last) {
                continue;
            }

            if (!$format->is_section_visible($section)) {
                continue;
            }

            $name = get_section_name($course, $section);
            $url = new \moodle_url('/course/view.php', [
                'id' => $course->id,
                'section' => $section->section,
            ]);

            $items[] = (object) [
                'name' => $name,
                'url' => $url->out(false),
                'current' => ((int) $section->section === (int) $current),
            ];
        }

        return $items;
    }

    /**
     * Load personal notes for the current user in a section (with sharing info).
     *
     * @param int $courseid
     * @param int $sectionid
     * @return array
     */
    private function load_personal_notes(int $courseid, int $sectionid): array
    {
        global $DB, $USER;

        $records = $DB->get_records('format_videoclass_notes', [
            'courseid'  => $courseid,
            'sectionid' => $sectionid,
            'userid'    => $USER->id,
        ], 'timemodified DESC');

        $notes = [];
        foreach ($records as $r) {
            // Fetch recipients for this note.
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

            $notes[] = (object) [
                'id'             => (int) $r->id,
                'content'        => format_string($r->content),
                'timecreated'    => userdate($r->timecreated, get_string('strftimedatetimeshort', 'langconfig')),
                'timemodified'   => userdate($r->timemodified, get_string('strftimedatetimeshort', 'langconfig')),
                'isshared'       => !empty($recipientnames),
                'recipientnames' => implode(', ', $recipientnames),
            ];
        }

        return $notes;
    }

    /**
     * Load notes shared with the current user in a section.
     *
     * @param int $courseid
     * @param int $sectionid
     * @return array
     */
    private function load_shared_with_me(int $courseid, int $sectionid): array
    {
        global $DB, $USER;

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
            'courseid'  => $courseid,
            'sectionid' => $sectionid,
        ]);

        $notes = [];
        foreach ($records as $r) {
            $notes[] = (object) [
                'id'             => (int) $r->id,
                'content'        => format_string($r->content),
                'authorfullname' => fullname($r),
                'timeshared'     => userdate($r->timeshared, get_string('strftimedatetimeshort', 'langconfig')),
            ];
        }

        return $notes;
    }
}
