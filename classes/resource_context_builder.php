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
 * Builds resource context for the AI tutor from section activities.
 *
 * Extracts text content from PDFs, pages, labels, and file metadata
 * to provide the AI tutor with context about the current section.
 *
 * @package   format_videoclass
 * @copyright 2026 Atlantis University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_videoclass;

defined('MOODLE_INTERNAL') || die();

class resource_context_builder {

    /** Maximum characters of resource context to send to the LLM. */
    private const MAX_CONTEXT_CHARS = 12000;

    /**
     * Build a text context string from all resources in a section.
     *
     * @param int $courseid Course ID
     * @param int $sectionid Section ID (course_sections.id)
     * @return string Combined text context, truncated to MAX_CONTEXT_CHARS
     */
    public static function build(int $courseid, int $sectionid): string {
        $modinfo = get_fast_modinfo($courseid);
        $section = null;

        // Find the section by ID.
        foreach ($modinfo->get_section_info_all() as $si) {
            if ((int) $si->id === $sectionid) {
                $section = $si;
                break;
            }
        }

        if (!$section) {
            return '[No resources available for this section]';
        }

        $sectionnum = $section->section;
        $cms = $modinfo->sections[$sectionnum] ?? [];

        if (empty($cms)) {
            return '[No resources available for this section]';
        }

        $chunks = [];
        $totalchars = 0;

        foreach ($cms as $cmid) {
            if ($totalchars >= self::MAX_CONTEXT_CHARS) {
                break;
            }

            $cm = $modinfo->cms[$cmid] ?? null;
            if (!$cm || !$cm->uservisible) {
                continue;
            }

            $chunk = self::extract_content($cm, $courseid);
            if (!empty($chunk)) {
                $remaining = self::MAX_CONTEXT_CHARS - $totalchars;
                if (strlen($chunk) > $remaining) {
                    $chunk = substr($chunk, 0, $remaining) . '... [truncated]';
                }
                $chunks[] = $chunk;
                $totalchars += strlen($chunk);
            }
        }

        if (empty($chunks)) {
            return '[No extractable resource content in this section]';
        }

        return implode("\n\n---\n\n", $chunks);
    }

    /**
     * Extract text content from a single course module.
     *
     * @param \cm_info $cm Course module info
     * @param int $courseid Course ID
     * @return string Extracted text content
     */
    private static function extract_content(\cm_info $cm, int $courseid): string {
        $modname = $cm->modname;
        $name = $cm->name;

        switch ($modname) {
            case 'page':
                return self::extract_page($cm, $name);

            case 'resource':
            case 'file':
                return self::extract_file_resource($cm, $name, $courseid);

            case 'url':
                return self::extract_url($cm, $name);

            case 'label':
                return self::extract_label($cm, $name);

            case 'book':
                return self::extract_book($cm, $name);

            case 'folder':
                return self::extract_folder($cm, $name);

            default:
                // For quizzes, assignments, forums, etc. — just include the name and intro.
                return self::extract_generic($cm, $name);
        }
    }

    /**
     * Extract content from a Page module.
     */
    private static function extract_page(\cm_info $cm, string $name): string {
        global $DB;
        $page = $DB->get_record('page', ['id' => $cm->instance], 'intro, content');
        if (!$page) {
            return '';
        }

        $text = "[Page: {$name}]\n";
        if (!empty($page->intro)) {
            $text .= strip_tags($page->intro) . "\n";
        }
        if (!empty($page->content)) {
            $text .= strip_tags($page->content);
        }
        return trim($text);
    }

    /**
     * Extract content from a File/Resource module.
     * For PDFs and text files, attempt to read content.
     * For others, provide metadata only.
     */
    private static function extract_file_resource(\cm_info $cm, string $name, int $courseid): string {
        global $DB;

        $resource = $DB->get_record('resource', ['id' => $cm->instance], 'intro');
        $context = \context_module::instance($cm->id);
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'mod_resource', 'content', 0, 'sortorder DESC, id ASC', false);

        if (empty($files)) {
            $text = "[Resource: {$name}]";
            if (!empty($resource->intro)) {
                $text .= "\n" . strip_tags($resource->intro);
            }
            return $text;
        }

        $file = reset($files);
        $filename = $file->get_filename();
        $mimetype = $file->get_mimetype();

        $text = "[Resource: {$name} — {$filename}]\n";
        if (!empty($resource->intro)) {
            $text .= strip_tags($resource->intro) . "\n";
        }

        // Try to extract text from common file types.
        if ($mimetype === 'application/pdf') {
            $extracted = self::extract_pdf_text($file);
            if ($extracted) {
                $text .= $extracted;
            } else {
                $text .= "[PDF content — extraction not available]";
            }
        } elseif (strpos($mimetype, 'text/') === 0) {
            // Plain text, HTML, CSV, etc.
            $content = $file->get_content();
            if ($mimetype === 'text/html') {
                $text .= strip_tags($content);
            } else {
                $text .= $content;
            }
        } elseif (strpos($mimetype, 'officedocument.wordprocessingml') !== false) {
            $text .= "[Word document — content available for download]";
        } elseif (strpos($mimetype, 'officedocument.presentationml') !== false) {
            $text .= "[PowerPoint presentation — content available for download]";
        } else {
            $text .= "[File type: {$mimetype}]";
        }

        return trim($text);
    }

    /**
     * Try to extract text from a PDF file.
     *
     * Uses a simple approach: read the raw PDF stream and extract text between
     * BT/ET markers. This is a basic extraction that works for many PDFs.
     */
    private static function extract_pdf_text(\stored_file $file): string {
        $content = $file->get_content();
        if (empty($content)) {
            return '';
        }

        // Simple PDF text extraction — look for text streams.
        $text = '';

        // Method 1: Extract text between BT/ET (Begin Text / End Text) markers.
        if (preg_match_all('/BT\s*(.*?)\s*ET/s', $content, $matches)) {
            foreach ($matches[1] as $block) {
                // Extract text from Tj and TJ operators.
                if (preg_match_all('/\(([^)]*)\)\s*Tj/s', $block, $tjmatches)) {
                    $text .= implode(' ', $tjmatches[1]) . "\n";
                }
                if (preg_match_all('/\[(.*?)\]\s*TJ/s', $block, $tjmatches)) {
                    foreach ($tjmatches[1] as $arr) {
                        if (preg_match_all('/\(([^)]*)\)/', $arr, $parts)) {
                            $text .= implode('', $parts[1]);
                        }
                    }
                    $text .= "\n";
                }
            }
        }

        // Clean up the extracted text.
        $text = preg_replace('/\\\\[nrt]/', ' ', $text);
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        if (strlen($text) < 20) {
            // Extraction failed or too little content — return empty.
            return '';
        }

        return $text;
    }

    /**
     * Extract content from a URL module.
     */
    private static function extract_url(\cm_info $cm, string $name): string {
        global $DB;
        $url = $DB->get_record('url', ['id' => $cm->instance], 'intro, externalurl');
        if (!$url) {
            return '';
        }

        $text = "[URL: {$name}]\n";
        $text .= "Link: {$url->externalurl}\n";
        if (!empty($url->intro)) {
            $text .= strip_tags($url->intro);
        }
        return trim($text);
    }

    /**
     * Extract content from a Label module.
     */
    private static function extract_label(\cm_info $cm, string $name): string {
        global $DB;
        $label = $DB->get_record('label', ['id' => $cm->instance], 'intro');
        if (!$label || empty($label->intro)) {
            return '';
        }
        return "[Label]\n" . strip_tags($label->intro);
    }

    /**
     * Extract content from a Book module.
     */
    private static function extract_book(\cm_info $cm, string $name): string {
        global $DB;
        $book = $DB->get_record('book', ['id' => $cm->instance], 'intro');
        $chapters = $DB->get_records('book_chapters', ['bookid' => $cm->instance], 'pagenum ASC', 'title, content');

        $text = "[Book: {$name}]\n";
        if (!empty($book->intro)) {
            $text .= strip_tags($book->intro) . "\n";
        }

        foreach ($chapters as $ch) {
            $text .= "\n### {$ch->title}\n";
            $text .= strip_tags($ch->content) . "\n";
        }

        return trim($text);
    }

    /**
     * Extract file listing from a Folder module.
     */
    private static function extract_folder(\cm_info $cm, string $name): string {
        global $DB;
        $folder = $DB->get_record('folder', ['id' => $cm->instance], 'intro');
        $context = \context_module::instance($cm->id);
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'mod_folder', 'content', 0, 'filepath, filename', false);

        $text = "[Folder: {$name}]\n";
        if (!empty($folder->intro)) {
            $text .= strip_tags($folder->intro) . "\n";
        }
        $text .= "Contains:\n";
        foreach ($files as $f) {
            $text .= "  • {$f->get_filepath()}{$f->get_filename()} ({$f->get_mimetype()})\n";
        }

        return trim($text);
    }

    /**
     * Extract basic info from any other module type.
     */
    private static function extract_generic(\cm_info $cm, string $name): string {
        global $DB;
        $tablename = $cm->modname;

        try {
            $record = $DB->get_record($tablename, ['id' => $cm->instance], 'intro', IGNORE_MISSING);
        } catch (\Exception $e) {
            $record = null;
        }

        $text = "[{$cm->modname}: {$name}]";
        if ($record && !empty($record->intro)) {
            $text .= "\n" . strip_tags($record->intro);
        }

        return $text;
    }
}
