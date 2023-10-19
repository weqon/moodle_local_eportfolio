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
 * Overview page for ePortfolio
 *
 * @package local_eportfolio
 * @category overview
 * @copyright 2023 weQon UG {@link https://weqon.net}
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once('locallib.php');
require_once('renderer.php');
require_once($CFG->libdir . '/tablelib.php');

// First check, if user is logged in before accessing this page.
require_login();

if (isguestuser()) {
    redirect(new moodle_url($CFG->wwwroot),
            get_string('error:noguestaccess', 'local_eportfolio'),
            null, \core\output\notification::NOTIFY_ERROR);
}

// Params used for deleting, undo sharing & use for selected entry.
$action = optional_param('action', '', PARAM_ALPHA);
$id = optional_param('id', 0, PARAM_INT);
$courseid = optional_param('courseid', 0, PARAM_INT);
$confirm = optional_param('confirm', '', PARAM_ALPHANUM);

$tsort = optional_param('tsort', '', PARAM_ALPHA);
$tdir = optional_param('tdir', 0, PARAM_INT);

$url = new moodle_url('/local/eportfolio/index.php');
$context = context_user::instance($USER->id);

if ($action == 'delete') {

    if ($confirm != md5($id)) {

        // First check if this file was shared for viewing only.
        // ePortfolios shared for grading will be only deleted in user context, but stay in course module context.
        $eportfolioshared = $DB->get_records('local_eportfolio_share', ['fileitemid' => $id]);

        $fileshared = false;

        if ($eportfolioshared) {

            $courses = array();
            $fileshared = true;

            // Get courses.
            foreach ($eportfolioshared as $es) {
                $course = $DB->get_record('course', ['id' => $es->courseid]);
                $courses[] = $course->fullname;
            }

            $courses = implode(', ', $courses);

        }

        $fs = get_file_storage();
        $file = $fs->get_file_by_id($id);

        $optionsyes = array(
                'id' => $id,
                'action' => 'delete',
                'delete' => $id,
                'confirm' => md5($id),
                'fileshared' => $fileshared,
        );

        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('delete:header', 'local_eportfolio'));

        $deleteurl = new moodle_url($url, $optionsyes);
        $deletebutton = new single_button($deleteurl,
                get_string('delete:confirm', 'local_eportfolio'), 'post');

        $stringparams = array(
                'filename' => $file->get_filename(),
                'courses' => (isset($courses)) ? $courses : get_string('delete:nocourses', 'local_eportfolio'),
        );

        echo $OUTPUT->confirm(get_string('delete:checkconfirm', 'local_eportfolio', $stringparams), $deletebutton, $deleteurl);
        echo $OUTPUT->footer();
        die;

    } else if (data_submitted()) {

        $data = data_submitted();

        // Get file storage for further processing.
        $fs = get_file_storage();

        // First check if fileshared is set to true.
        if ($data->fileshared) {

            // Get all entries for this file with the shareoption = share.
            $eportfolioshared = $DB->get_records('local_eportfolio_share', ['fileitemid' => $data->id, 'shareoption' => 'share']);

            foreach ($eportfolioshared as $es) {

                // Let's delete the files in course context.
                $coursecontext = context_course::instance($es->courseid);

                $file = $fs->get_file_by_id($es->fileidcontext);
                $file->delete();

                // Delete the entry in eportfolio_share table.
                $DB->delete_records('local_eportfolio_share', ['id' => $es->id]);

            }
        }

        $file = $fs->get_file_by_id($data->id);

        // We use the pathnamehash to get the H5P file
        $pathnamehash = $file->get_pathnamehash();

        $h5pfile = $DB->get_record('h5p', ['pathnamehash' => $pathnamehash]);

        // If H5P, delete it from the H5P table as well.
        // Note: H5P will create an entry when the file was viewed for the first time.
        if ($h5pfile) {

            $DB->delete_records('h5p', ['id' => $h5pfile->id]);
            // Also delete from files where context = 1, itemid = h5p id component core_h5p, filearea content
            $fs->delete_area_files('1', 'core_h5p', 'content', $h5pfile->id);

        }

        if ($file->delete()) {

            // Trigger event for withdrawing sharing of ePortfolio.
            \local_eportfolio\event\eportfolio_deleted::create([
                    'other' => [
                            'description' => get_string('event:eportfolio:deleted', 'local_eportfolio',
                                    array('userid' => $USER->id, 'filename' => $file->get_filename(),
                                            'itemid' => $file->get_id())),
                    ],
            ])->trigger();

            redirect(new moodle_url('/local/eportfolio/index.php'),
                    get_string('delete:success', 'local_eportfolio'),
                    null, \core\output\notification::NOTIFY_SUCCESS);

        } 

    } else {

        redirect(new moodle_url('/local/eportfolio/index.php'),
                get_string('delete:error', 'local_eportfolio'),
                null, \core\output\notification::NOTIFY_ERROR);
    }
}

if ($action == 'undo') {

    if ($confirm != md5($id)) {

        $eportfolio = $DB->get_record('local_eportfolio_share', ['id' => $id]);

        $course = $DB->get_record('course', ['id' => $eportfolio->courseid]);

        $fs = get_file_storage();
        $file = $fs->get_file_by_id($eportfolio->fileitemid);

        $optionsyes = array(
                'id' => $id,
                'action' => 'undo',
                'undo' => $id,
                'confirm' => md5($id),
                'courseid' => $course->id,
                'itemid' => $eportfolio->fileitemid,
                'fileidcontext' => $eportfolio->fileidcontext,
        );

        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('undo:header', 'local_eportfolio'));

        $undourl = new moodle_url($url, $optionsyes);
        $undobutton = new single_button($undourl,
                get_string('undo:confirm', 'local_eportfolio'), 'post');

        echo $OUTPUT->confirm(get_string('undo:checkconfirm', 'local_eportfolio',
                array('filename' => $file->get_filename(), 'course' => $course->fullname,
                        'shareoption' => get_string('overview:shareoption:' . $eportfolio->shareoption, 'local_eportfolio'))),
                $undobutton, $undourl);
        echo $OUTPUT->footer();
        die;

    } else if (data_submitted()) {

        $data = data_submitted();

        // First delete the shared file from course context.
        $coursecontext = context_course::instance($data->courseid);

        $fs = get_file_storage();
        $file = $fs->get_file_by_id($data->fileidcontext);

        // Delete the file from H5P-Table
        $pathnamehash = $file->get_pathnamehash();

        $h5pfile = $DB->get_record('h5p', ['pathnamehash' => $pathnamehash]);

        if ($h5pfile) {
            $DB->delete_records('h5p', ['id' => $h5pfile->id]);
        }

        // Now delete the file
        $file->delete();

        if ($DB->delete_records('local_eportfolio_share', ['id' => $data->id])) {

            // Trigger event for withdrawing sharing of ePortfolio.
            \local_eportfolio\event\eportfolio_shared::create([
                    'other' => [
                            'description' => get_string('event:eportfolio:undo', 'local_eportfolio',
                                    array('userid' => $USER->id, 'filename' => $file->get_filename(), 'itemid' => $data->itemid)),
                    ],
            ])->trigger();

            redirect(new moodle_url('/local/eportfolio/index.php'),
                    get_string('undo:success', 'local_eportfolio'),
                    null, \core\output\notification::NOTIFY_SUCCESS);

        } else {

            redirect(new moodle_url('/local/eportfolio/index.php'),
                    get_string('undo:error', 'local_eportfolio'),
                    null, \core\output\notification::NOTIFY_ERROR);
        }
    }
}

if ($action == 'reuse') {

    // First we need the course context.
    $coursecontext = context_course::instance($courseid);

    // Get the file we want to create a copy of.
    $fs = get_file_storage();
    $file = $fs->get_file_by_id($id);

    // Create a new itemid to avoid conflicts.
    $itemid = file_get_unused_draft_itemid();

    $newfile = new stdClass();
    $newfile->contextid = $context->id; // User context.
    $newfile->userid = $USER->id;
    $newfile->itemid = $itemid;

    $filecopy = $fs->create_file_from_storedfile($newfile, $file);

    // Also we have to add a new entry in the h5p table.
    // First get h5p file by "old" contenthash.
    $contenthash = $file->get_contenthash();
    $pathnamehash = $file->get_pathnamehash();

    $newh5pfile = $DB->get_record('h5p', ['pathnamehash' => $pathnamehash]);

    // Override contenthash & pathnamehash to new file.
    $newh5pfile->contenthash = $filecopy->get_contenthash();;
    $newh5pfile->pathnamehash = $filecopy->get_pathnamehash();

    // We need this for the next step.
    $oldh5pfileid = $newh5pfile->id;

    unset($newh5pfile->id);

    $newh5pfileid = $DB->insert_record('h5p', $newh5pfile);

    // We need to create a copy of the H5P content as well in case the file contains additional content like images.
    $h5pcontentfiles = $DB->get_records('files', ['itemid' => $oldh5pfileid, 'component' => 'core_h5p', 'filearea' => 'content']);

    foreach ($h5pcontentfiles as $h5pcontent) {

        // Get the file we want to create a copy of.
        $file = $fs->get_file_by_id($h5pcontent->id);

        $itemid = $newh5pfileid;

        $newcontentfile = new stdClass();
        $newcontentfile->contextid = '1';
        $newcontentfile->userid = $USER->id;
        $newcontentfile->itemid = $itemid;

        $filecontentcopy = $fs->create_file_from_storedfile($newcontentfile, $file);
    }

    if ($filecopy && $newh5pfileid) {

        // After the new file in user context was created direct to the H5P Editor.
        $fileurl = moodle_url::make_pluginfile_url($filecopy->get_contextid(), $filecopy->get_component(),
                $filecopy->get_filearea(), $filecopy->get_itemid(), $filecopy->get_filepath(),
                $filecopy->get_filename(), false);

        // H5P core edit will redirect the user to this URL after editing the content.
        $returnurl = $CFG->wwwroot . '/local/eportfolio/index.php';

        $editurl = $CFG->wwwroot . '/h5p/edit.php?url=' . $fileurl . '&returnurl=' . $returnurl;

        redirect($editurl, get_string('use:template:success', 'local_eportfolio'),
                null, \core\output\notification::NOTIFY_SUCCESS);

    } else {

        redirect(new moodle_url('/local/eportfolio/index.php'),
                get_string('use:template:error', 'local_eportfolio'),
                null, \core\output\notification::NOTIFY_ERROR);

    }

}

// Set page layout.
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title(get_string('overview:header', 'local_eportfolio'));
$PAGE->set_heading(get_string('overview:header', 'local_eportfolio'));
$PAGE->set_pagelayout('base');
$PAGE->add_body_class('limitedwith');
$PAGE->set_pagetype('user-files');

// Print the header.
echo $OUTPUT->header();

$data = new stdClass();

$data->uploadh5pfile = 'upload.php';
$data->createh5pfile = 'create.php';

$data->helpfaqurl = new moodle_url('/local/eportfolio/helpfaq.php');

echo $OUTPUT->render_from_template('local_eportfolio/eportfolio_overview_tabs_header', $data);

// Output for ePortfolios. Workaround, since mustache templates can't handle flexible tables.
renderer_output_myeportfolios($tsort, $tdir);
renderer_output_mysharedeportfolios($tsort, $tdir);
renderer_output_mysharedeportfoliosgrade($tsort, $tdir);
renderer_output_sharedeportfolios($tsort, $tdir);
renderer_output_sharedeportfoliosgrade($tsort, $tdir);
renderer_output_eportfolio_templates($tsort, $tdir);

echo $OUTPUT->render_from_template('local_eportfolio/eportfolio_overview_tabs_footer', $data);

echo $OUTPUT->footer();
