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

    private const NOTES_MODULES = [
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
        $data = parent::export_for_template($output);

        $cms = [];
        if (!empty($data->cmlist) && !empty($data->cmlist->cms)) {
            $cms = $data->cmlist->cms;
        }

        [$resources, $notes, $qa] = $this->split_cms($cms);

        $data->videoclass = (object) [
            'sectionsnav' => $this->build_sections_nav(),
            'resources' => $this->wrap_cmlist($resources),
            'notes' => $this->wrap_cmlist($notes),
            'qa' => $this->wrap_cmlist($qa),
            'hasvideo' => $this->summary_has_video($data),
            'editurl' => (new \moodle_url('/course/editsection.php', ['id' => $data->id]))->out(false),
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
        if (empty($data->summary) || empty($data->summary->summarytext)) {
            return false;
        }

        $html = (string) $data->summary->summarytext;
        if (preg_match('/<(iframe|video|embed|source)\b/i', $html)) {
            return true;
        }

        return trim(strip_tags($html)) !== '';
    }

    /**
     * Split course modules into resources, notes, and Q&A buckets.
     *
     * @param array $cms
     * @return array
     */
    private function split_cms(array $cms): array
    {
        $resources = [];
        $notes = [];
        $qa = [];

        foreach ($cms as $cmwrapper) {
            if (empty($cmwrapper->cmitem)) {
                $resources[] = $cmwrapper;
                continue;
            }

            $cmitem = $cmwrapper->cmitem;
            $label = '';
            if (!empty($cmitem->cmformat) && !empty($cmitem->cmformat->cmname)) {
                $label = strip_tags((string) $cmitem->cmformat->cmname);
            }

            $bucket = $this->bucket_from_label($label);
            if ($bucket === '') {
                $module = $cmitem->module ?? '';
                if (in_array($module, self::QA_MODULES, true)) {
                    $bucket = 'qa';
                } else if (in_array($module, self::NOTES_MODULES, true)) {
                    $bucket = 'notes';
                } else if (in_array($module, self::RESOURCE_MODULES, true)) {
                    $bucket = 'resources';
                } else {
                    $bucket = 'resources';
                }
            }

            switch ($bucket) {
                case 'qa':
                    $qa[] = $cmwrapper;
                    break;
                case 'notes':
                    $notes[] = $cmwrapper;
                    break;
                default:
                    $resources[] = $cmwrapper;
                    break;
            }
        }

        return [$resources, $notes, $qa];
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

        if (preg_match('/^\[(nota|notas|notes?)\]/i', $label)) {
            return 'notes';
        }

        if (preg_match('/^\[(recurso|recursos|resource|material)\]/i', $label)) {
            return 'resources';
        }

        return '';
    }

    /**
     * Wrap a cms list in the expected structure for the cmlist template.
     *
     * @param array $cms
     * @return stdClass
     */
    private function wrap_cmlist(array $cms): stdClass
    {
        return (object) [
            'cms' => $cms,
            'hascms' => !empty($cms),
        ];
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
}
