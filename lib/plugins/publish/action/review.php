<?php

if(!defined('DOKU_INC')) die();

class action_plugin_publish_review extends DokuWiki_Action_Plugin {

    /**
     * @var helper_plugin_publish
     */
    private $helper;

    function __construct() {
        $this->helper = plugin_load('helper', 'publish');
    }

    function register(Doku_Event_Handler $controller) {
        $controller->register_hook('ACTION_ACT_PREPROCESS', 'BEFORE', $this, 'handle_io_write', array());
        $controller->register_hook('AJAX_CALL_UNKNOWN', 'BEFORE', $this, 'reviewNS', array());
    }

    function reviewNS(Doku_Event &$event, $param) {
        if ($event->data !== 'plugin_publish_reviewNS') {
            return;
        }
        //no other ajax call handlers needed
        $event->stopPropagation();
        $event->preventDefault();

        //e.g. access additional request variables
        global $INPUT; //available since release 2012-10-13 "Adora Belle"
        $namespace = $INPUT->str('namespace');
        $pages = $this->helper->getPagesFromNamespace($namespace);
        $pages = $this->helper->removeSubnamespacePages($pages, $namespace);

        global $ID, $INFO;
        $original_id = $ID;
        foreach ($pages as $page) {
            $ID = $page[0];
            $INFO = pageinfo();
            if (!$this->helper->canReview()) {
                continue;
            }
            $this->addReview();
        }
        $ID = $original_id;
    }

    function handle_io_write(Doku_Event &$event, $param) {
        # This is the only hook I could find which runs on save,
        # but late enough to have lastmod set (ACTION_ACT_PREPROCESS
        # is too early)
        global $ACT;
        global $INPUT;
        global $ID;

        if ($ACT != 'show') {
            return;
        }

        if (!$INPUT->has('publish_review')) {
            return;
        }

        if (!$this->helper->canReview()) {
            msg($this->getLang('wrong permissions to review'), -1);
            return;
        }

        $this->addReview();
        send_redirect(wl($ID, array('rev' => $this->helper->getRevision()), true, '&'));
    }

    function addReview() {
        global $USERINFO;
        global $ID;
        global $INFO;

        if (!$INFO['exists']) {
            msg($this->getLang('cannot review a non-existing revision'), -1);
            return;
        }

        $reviewRevision = $this->helper->getRevision();
        $reviews = $this->helper->getReviews();

        if (!isset($reviews[$reviewRevision])) {
            $reviews[$reviewRevision] = array();
        }

        $reviews[$reviewRevision][$INFO['client']] = array(
            $INFO['client'],
            $_SERVER['REMOTE_USER'],
            $USERINFO['mail'],
            time()
        );

        $success = p_set_metadata($ID, array('review' => $reviews), true, true);
        if ($success) {
            msg($this->getLang('version reviewed'), 1);

            $data = array();
            $data['rev'] = $reviewRevision;
            $data['id'] = $ID;
            $data['reviewer'] = $_SERVER['REMOTE_USER'];
            $data['reviewer_info'] = $USERINFO;
            if ($this->getConf('send_mail_on_review') && $this->helper->isRevisionReviewed($reviewRevision)) {
                /** @var action_plugin_publish_mail $mail */
                $mail = plugin_load('action','publish_mail');
                $mail->send_review_mail();
            }
            trigger_event('PLUGIN_PUBLISH_REVIEW', $data);
        } else {
            msg($this->getLang('cannot review error'), -1);
        }

    }

}
