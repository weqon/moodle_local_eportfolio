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

namespace local_eportfolio\forms;

use contenttype_h5p\content;
use core_h5p\api;
use core_h5p\editor as h5peditor;
use core_h5p\factory;
use core_h5p\helper;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");

class createform extends \moodleform {

    protected $contextid;

    protected $id;

    private $h5peditor;

    public function __construct(string $action = null, array $customdata = null, string $method = 'post') {
        parent::__construct($action, $customdata, $method);
        $this->contextid = $customdata['contextid'];
        $this->id = $customdata['id'];

        $mform =& $this->_form;
        $mform->addElement('hidden', 'contextid', $this->contextid);
        $this->_form->setType('contextid', PARAM_INT);

        $mform->addElement('hidden', 'id', $this->id);
        $this->_form->setType('id', PARAM_INT);
    }

    protected function definition() {
        global $OUTPUT;

        $mform = $this->_form;
        $errors = [];
        $notifications = [];

        // H5P content type to create.
        $library = $this->_customdata['library'];

        if (empty($library)) {
            $returnurl = new \moodle_url('/local/eportfolio/index.php', ['contextid' => $this->_customdata['contextid']]);
            throw new \moodle_exception('invalidcontentid', 'error', $returnurl);
        }

        $this->h5peditor = new h5peditor();

        $this->set_display_vertical();
        $mform->addElement('html', $OUTPUT->heading(get_string('contenteditor', 'local_eportfolio'), 2));

        // The H5P editor needs the H5P content type library name for a new content.
        $mform->addElement('hidden', 'library', $library);
        $mform->setType('library', PARAM_TEXT);
        $this->h5peditor->set_library($library, $this->_customdata['contextid'], 'local_eportfolio', 'eportfolio');

        $mformid = 'coolh5peditor';
        $mform->setAttributes(array('id' => $mformid) + $mform->getAttributes());

        if ($errors || $notifications) {
            // Show the error messages and a Cancel button.
            foreach ($errors as $error) {
                $mform->addElement('warning', $error->code, 'notify', $error->message);
            }
            foreach ($notifications as $key => $notification) {
                $mform->addElement('warning', 'notification_' . $key, 'notify', $notification);
            }
            $mform->addElement('cancel', 'cancel', get_string('back'));
        } else {
            $this->h5peditor->add_editor_to_form($mform);
            $this->add_action_buttons();
        }
    }

    public function save_content(\stdClass $data) {
        global $DB;

        // The H5P libraries expect data->id as the H5P content id.
        // The method H5PCore::saveContent throws an error if id is set but empty.
        if (empty($data->id)) {
            unset($data->id);
        }

        $h5pcontentid = $this->h5peditor->save_content($data);

        $factory = new factory();
        $h5pfs = $factory->get_framework();

        // Needs the H5P file id to create or update the content bank record.
        $h5pcontent = $h5pfs->loadContent($h5pcontentid);
        $fs = get_file_storage();
        $file = $fs->get_file_by_hash($h5pcontent['pathnamehash']);

        return $file->get_id();
    }

}
