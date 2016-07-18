<?php

/**
 * Show additional informations from bugtracker mails
 *
 * @version 0.2
 * @author Sandor Takacs
 * original author: Tim Gerundt
 */
class rc_bugheader extends rcube_plugin
{
    public $task = 'mail';

    private $_bugMailHeaders = array(
        'X-Trac-Ticket-URL',
        'X-Trac-Ticket-ID',
        'X-SourceForge-Tracker-itemid'
    );

    function init()
    {
        $rcmail = rcmail::get_instance();
        if ($rcmail->action == 'show' || $rcmail->action == 'preview') {
            $this->add_hook('storage_init', array($this, 'storage_init'));
            $this->add_hook('message_headers_output', array($this, 'message_headers_output'));
        } else if ($rcmail->action == '') {
            // with enabled_caching we're fetching additional headers before show/preview
            $this->add_hook('storage_init', array($this, 'storage_init'));
        }
    }

    function storage_init($p)
    {
        $p['fetch_headers'] = trim($p['fetch_headers'] . ' ' . strtoupper(join(' ', $this->_bugMailHeaders)));

        return $p;
    }

    function message_headers_output($p)
    {
        $title = '';
        $value = '';

        $tracTicketId = $p['headers']->others['x-trac-ticket-id'];
        if (!empty($tracTicketId)) {
            // If Trac Ticket
            $title         = 'Trac Ticket';
            $tracTicketId  = '#' . $tracTicketId;
            $tracTicketUrl = $p['headers']->others['x-trac-ticket-url'];
            if (!empty($tracTicketUrl)) {
                // If has a Trac Ticket URL
                $value = '<a href="' . rcube::Q($tracTicketUrl) . '" target="_blank">' . rcube::Q($tracTicketId) . '</a>';
            } else {
                //If NOT has ticket URL
                $value = rcube::Q($tracTicketId);
            }
        }

        $sfTrackerItemId = $p['headers']->others['x-sourceforge-tracker-itemid'];
        if (!empty($sfTrackerItemId)) {
            // If SourceForge tracker item
            $title = 'Tracker ID';
            $value = '<a href="http://sourceforge.net/support/tracker.php?aid=' . rcube::Q($sfTrackerItemId) . '" target="_blank">' . rcube::Q($sfTrackerItemId) . '</a>';
        }

        $references = $p['headers']->references;
        if (preg_match('#<(\w+)/(\w+)/commit/(\w+)@github\.com>#i', $references, $matches)) {
            // If GitHub Commit
            $title = 'GitHub Commit';
            $value = '<a href="https://github.com/' . rcube::Q($matches[1]) . '/' . rcube::Q($matches[2]) . '/commit/' . rcube::Q($matches[3]) . '" target="_blank">' . rcube::Q($matches[3]) . '</a>';
        }

        if (preg_match('#<(\w+)/(\w+)/issues/(\w+)@github\.com>#i', $references, $matches)) {
            // If GitHub Issue
            $title = 'GitHub Issue';
            $value = '<a href="https://github.com/' . rcube::Q($matches[1]) . '/' . rcube::Q($matches[2]) . '/issues/' . rcube::Q($matches[3]) . '" target="_blank">' . rcube::Q($matches[3]) . '</a>';
        }

        $message_id = $p['headers']->messageID;
        if (preg_match('#<(\w+)/(\w+)/pull/(\w+)@github\.com>#i', $message_id, $matches)) {
            // If GitHub Pull request
            $title = 'GitHub Pull';
            $value = '<a href="https://github.com/' . rcube::Q($matches[1]) . '/' . rcube::Q($matches[2]) . '/pull/' . rcube::Q($matches[3]) . '" target="_blank">' . rcube::Q($matches[3]) . '</a>';
        }

        if (!empty($title) && !empty($value)) {
            //if bug header found...
            $p['output'][$key] = array(
                'title' => $title,
                'value' => $value,
                'html'  => true
            );
        }

        return $p;
    }

}
?>
