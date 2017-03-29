<?php

if(!defined('DOKU_INC')) die();

class action_plugin_publish_banner extends DokuWiki_Action_Plugin {

    /**
     * @var helper_plugin_publish
     */
    private $hlp;

    function __construct() {
        $this->hlp = plugin_load('helper','publish');
    }

    function register(Doku_Event_Handler $controller) {
        $controller->register_hook('TPL_ACT_RENDER', 'BEFORE', $this, 'handle_display_banner', array());
    }

    function handle_display_banner(&$event, $param) {
        global $INFO;

        if (!$this->hlp->isActive()) {
            return;
        }

        if ($event->data != 'show') {
            return;
        }

        if (!$INFO['exists']) {
            return;
        }

        $meta = $INFO['meta'];

        if (!$meta['approval']) {
            $meta['approval'] = array();
        }
        
        if (!$meta['review']) {
            $meta['review'] = array();
        }

        if($INFO['perm'] <= AUTH_READ && $this->getConf('hidereaderbanner')){
            return;
        }

        if ($this->hlp->isCurrentRevisionApproved() && $this->getConf('hide_approved_banner')) {
            return;
        }

        if ($this->hlp->isCurrentRevisionReviewed() && $this->getConf('hide_reviewed_banner')) {
            return;
        }


        $this->showBanner();
        return;
    }

    function difflink($id, $rev1, $rev2) {
        if($rev1 == $rev2) {
            return '';
        }

        $difflink = $this->hlp->getDifflink($id,$rev1,$rev2);

        return '<a href="' . $difflink .
            '" class="approved_diff_link">' .
            '<img src="'.DOKU_BASE.'lib/images/diff.png" class="approved_diff_link" alt="Diff" />' .
            '</a>';
    }

    function showBanner() {
        if ($this->hlp->isCurrentRevisionApproved()) {
            $class = 'approval approved_yes';
            $func = 'Approved';
        } else {
            if ($this->hlp->isHiddenForUser()) {
                return;
            }
            if ($this->hlp->isCurrentRevisionReviewed()) {
	            $class = 'review reviewed_yes';
	            $func = "Reviewed";
	        } else {
		        $class = 'approval approved_no review reviewed_no';
		        $func = "Draft";
	        }
            
        }

        printf('<div class="%s">', $class);
        
		switch ($func) {
		    case 'Approved':
		    	//$this->showLatestDraftIfNewer();
		        $this->showLatestApprovedVersion();
		        $this->showApproved();
		        //$this->showPreviousApproved();
		        break;
		    case 'Reviewed':
		        $this->showLatestReviewedVersion();
		        $this->showReviewed();
		        $this->showPreviousReviewed();
		        $this->showApproveAction();
		        break;
		    default: // Draft
		        $this->showLatestDraftIfNewer();
                $this->showLatestApprovedVersion();
		        $this->showDraft();
		        $this->showReviewAction();
		        break;
		}
       
        echo '</div>';

        global $INFO;
        if ($this->getConf('apr_mail_receiver') !== '' && $INFO['isadmin']) {
            $validator                      = new EmailAddressValidator();
            $validator->allowLocalAddresses = true;
            $addr = $this->getConf('apr_mail_receiver');
            if(!$validator->check_email_address($addr)) {
                msg(sprintf($this->getLang('mail_invalid'),htmlspecialchars($addr)),-1);
            }

        }
    }

    function showInternalNote() {
        $note = trim($this->getConf('internal note'));
        if ($note === '') {
            return;
        }
        if (!$this->hlp->isHidden()) {
            return;
        }

        printf('<span>%s</span>', hsc($note));
    }

    function showLatestDraftIfNewer() {
        global $ID;
        $revision = $this->hlp->getRevision();
        $latestRevision = $this->hlp->getLastestRevision();

        if ($revision >= $latestRevision) {
            return;
        }
        if ($this->hlp->isRevisionApproved($latestRevision)) {
            return;
        }

        echo '<span class="approval_latest_draft">';
        printf($this->getLang('apr_recent_draft'), wl($ID, 'force_rev=1'));
        echo $this->difflink($ID, null, $revision) . '</span>';
    }

    function showLatestApprovedVersion() {
        global $ID;
        $revision = $this->hlp->getRevision();
        $latestApprovedRevision = $this->hlp->getLatestApprovedRevision();

        if ($latestApprovedRevision <= $revision) {
            return;
        }

        $latestRevision = $this->hlp->getLastestRevision();
        if ($latestApprovedRevision == $latestRevision) {
            //$latestApprovedRevision = '';
        }
        echo '<span class="approval_outdated">';
        printf($this->getLang('apr_outdated'), wl($ID, 'rev=' . $latestApprovedRevision));
        echo $this->difflink($ID, $latestApprovedRevision, $revision) . '</span>';
    }

    function showLatestReviewedVersion() {
        global $ID;
        $revision = $this->hlp->getRevision();
        $latestReviewedRevision = $this->hlp->getLatestReviewedRevision();

        if ($latestReviewedRevision <= $revision) {
            return;
        }

        $latestRevision = $this->hlp->getLastestRevision();
        if ($latestReviewedRevision == $latestRevision) {
            //$latestApprovedRevision = '';
        }
        echo '<span class="review_outdated">';
        printf($this->getLang('apr_outdated'), wl($ID, 'rev=' . $latestReviewedRevision));
        echo $this->difflink($ID, $latestReviewedRevision, $revision) . '</span>';
    }

    function showDraft() {
        $revision = $this->hlp->getRevision();

        if ($this->hlp->isCurrentRevisionReviewed()) {
            return;
        }

        $reviews = $this->hlp->getReviewsOnRevision($this->hlp->getRevision());
        $reviewCount = count($reviews);

        echo '<span class="review_draft">';
        printf($this->getLang('apr_draft'), '<span class="review_date">' . dformat($revision) . '</span>');
        echo '<br />';
        printf(' ' . $this->getLang('reviews'), $reviewCount, $this->getConf('number_of_reviewed'));
        if ($reviewCount != 0) {
            printf(' ' . $this->getLang('reviewed by'), implode(', ', $this->hlp->getReviewers()));
        }
        echo '</span>';
    }
    
    function showReviewed() {
        $revision = $this->hlp->getRevision();

        if (!$this->hlp->isCurrentRevisionReviewed()) {
            return;
        }

        $reviews = $this->hlp->getReviewsOnRevision($this->hlp->getRevision());
        $reviewCount = count($reviews);

        echo '<span class="review_reviewed">';
        printf($this->getLang('apr_reviewed'), '<span class="review_date">' . dformat($revision) . '</span>',
        	implode(', ', $this->hlp->getReviewers()));
        echo '<br />';
/*
        if ($reviewCount != 0) {
            printf(' ' . $this->getLang('reviewed by'), implode(', ', $this->hlp->getReviewers()));
        }
*/
		printf(' ' . $this->getLang('reviews'), $reviewCount, $this->getConf('number_of_reviewed'));
        echo '</span>';
    }

    function showApproved() {
        if (!$this->hlp->isCurrentRevisionApproved()) {
            return;
        }

        echo '<span class="approval_approved">';
        printf($this->getLang('apr_approved'),
            '<span class="approval_date">' . dformat($this->hlp->getApprovalDate()) . '</span>',
            implode(', ', $this->hlp->getApprovers()));
        echo '</span>';
    }
    
/*
    function showReviewed() {
        if (!$this->hlp->isCurrentRevisionApproved()) {
            return;
        }

        echo '<span class="revire_reviewed">';
        printf($this->getLang('apr_reviewed'),
            '<span class="review_date">' . dformat($this->hlp->getReviewDate()) . '</span>',
            implode(', ', $this->hlp->getReviewes()));
        echo '</span>';
    }
*/

    function showPreviousApproved() {
        global $ID;
        $previousApproved = $this->hlp->getPreviousApprovedRevision();
        if (!$previousApproved) {
            return;
        }
        echo '<span class="approval_previous">';
        printf($this->getLang('apr_previous'),
            wl($ID, 'rev=' . $previousApproved),
            dformat($previousApproved));
        echo $this->difflink($ID, $previousApproved, $this->hlp->getRevision()) . '</span>';
    }
    
    function showPreviousReviewed() {
        global $ID;
        $previousReviewed = $this->hlp->getPreviousReviewedRevision();
        if (!$previousApproved) {
            return;
        }
        echo '<span class="review_previous">';
        printf($this->getLang('apr_previous'),
            wl($ID, 'rev=' . $previousReviewed),
            dformat($previousReviewed));
        echo $this->difflink($ID, $previousReviewed, $this->hlp->getRevision()) . '</span>';
    }

    private function showApproveAction() {
        global $ID;
        global $REV;
        global $USERINFO;
        if (!$this->hlp->canApprove()) {
            return;
        }

        $approvals = $this->hlp->getApprovalsOnRevision($this->hlp->getRevision());
        foreach ($approvals as $approve) {
            if ($approve[1] == $_SERVER['REMOTE_USER']) {
                return;
            }
            if ($approve[1] == $USERINFO['mail']) {
                return;
            }
        }

        echo '<span class="approval_action">';
        echo '<a href="' . wl($ID, array('rev' => $REV, 'publish_approve'=>1)) . '">';
        echo $this->getLang('approve action');
        echo '</a>';
        echo '</span> ';
    }
    
    private function showReviewAction() {
        global $ID;
        global $REV;
        global $USERINFO;
        if (!$this->hlp->canReview()) {
            return;
        }

        $reviews = $this->hlp->getReviewsOnRevision($this->hlp->getRevision());
        foreach ($reviews as $review) {
            if ($review[1] == $_SERVER['REMOTE_USER']) {
                return;
            }
            if ($review[1] == $USERINFO['mail']) {
                return;
            }
        }

        echo '<span class="review_action">';
        echo '<a href="' . wl($ID, array('rev' => $REV, 'publish_review'=>1)) . '">';
        echo $this->getLang('review action');
        echo '</a>';
        echo '</span> ';
    }
}
