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
 * Download page for ePortfolio
 *
 * @package local_eportfolio
 * @category overview
 * @copyright 2023 weQon UG {@link https://weqon.net}
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

// First check, if user is logged in before accessing this page.
require_login();

$ids = optional_param('fileids', '0', PARAM_RAW);

if ($ids) {

    $downloadids = array();

    foreach ($ids as $id) {
        $cleanid = clean_param($id, PARAM_INT);

        $downloadids[] = $cleanid;
    }
}

// Set user context.
$context = context_user::instance($USER->id);

// Get all files for specific user context.
$fs = get_file_storage();
$files = $fs->get_area_files($context->id, 'local_eportfolio', 'eportfolio');
$files = array_reverse($files);

if (empty($files)) {
    throw new \moodle_exception('download:error', 'local_eportfolio');
}

// Trigger event.

// Raise time limit in case a lot of files will be downloaded.
core_php_time_limit::raise();

// Close the session.
\core\session\manager::write_close();

$plugin = get_string('pluginname', 'local_eportfolio');
$username = fullname($USER);

$filenameraw = $plugin . '_' . $username;

$zipname = format_string($filenameraw, true, ["context" => $context]);
$filename = shorten_filename(clean_filename($zipname . "-" . date("Ymd")) . ".zip");
$zipwriter = \core_files\archive_writer::get_stream_writer($filename, \core_files\archive_writer::ZIP_WRITER);

foreach ($files as $file) {
    if ($file->is_directory()) {
        continue;
    }

    // Only download specified files.
    if ($downloadids) {

        if (in_array($file->get_id(), $downloadids)) {
            $pathinzip = $file->get_filepath() . $file->get_filename();
            $zipwriter->add_file_from_stored_file($pathinzip, $file);
        }
    } else {
        $pathinzip = $file->get_filepath() . $file->get_filename();
        $zipwriter->add_file_from_stored_file($pathinzip, $file);
    }
}

// Finish the archive.
$zipwriter->finish();
exit();