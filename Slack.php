<?php

class SlackPlugin extends MantisPlugin {
    function register() {
        $this->name = plugin_lang_get( 'title' );
        $this->description = plugin_lang_get( 'description' );
        $this->page = 'config';
        $this->version = '0.1';
        $this->requires = array(
            'MantisCore' => '>= 1.2.0',
        );
        $this->author = 'Karim Ratib';
        $this->contact = 'kratib@meedan.net';
        $this->url = 'http://meedan.org';
    }

    function install() {
        if (!extension_loaded('curl')) {
            error_parameters(plugin_lang_get('error_no_curl'));
            trigger_error(ERROR_PLUGIN_INSTALL_FAILED, ERROR);
            return false;
        } else {
            return true;
        }
    }

    function hooks() {
        return array(
            'EVENT_REPORT_BUG' => 'new_update_bug',
            'EVENT_UPDATE_BUG' => 'new_update_bug',
            'EVENT_BUGNOTE_ADD' => 'new_update_bugnote',
        );
    }

    function config() {
        return array(
            'instance' => '',
            'token' => '',
            'bot_name' => 'mantis',
            'bot_icon' => '',
            'channels' => array(),
            'default_channel' => '#general',
        );
    }

    function new_update_bug($event, $bug, $bug_id) {
        $url = string_get_bug_view_url_with_fqdn($bug_id);
        $summary = bug_format_summary($bug_id, SUMMARY_FIELD);
        $project = project_get_name($bug->project_id);
        $reporter = '@' . user_get_name($bug->reporter_id);
        $handler = empty($bug->handler_id) ? 'no one' : ('@' . user_get_name($bug->handler_id));
        $status = get_enum_element( 'status', $bug->status );
        switch ($event) {
            case 'EVENT_REPORT_BUG':
                $msg = sprintf('[%s] %s created <%s|%s> and assigned it to %s.',
                    $project, $reporter, $url, $summary, $handler
                );
                break;
            case 'EVENT_UPDATE_BUG':
                $msg = sprintf('[%s] <%s|%s> is now marked as "%s", assigned to %s.',
                    $project, $url, $summary, $status, $handler
                );
                break;
        }
        $this->notify($msg, $this->get_channel($project));
    }

    function new_update_bugnote($event, $bug_id, $bugnote_id) {
        $url = string_get_bugnote_view_url_with_fqdn($bug_id, $bugnote_id);
        $bug = bug_get($bug_id);
        $project = project_get_name($bug->project_id);
        $summary = bug_format_summary($bug_id, SUMMARY_FIELD);
        $reporter_id = bugnote_get_field($bugnote_id, 'reporter_id');
        $reporter = '@' . user_get_name($reporter_id);
        $note = bugnote_get_text($bugnote_id);
        switch ($event) {
            case 'EVENT_BUGNOTE_ADD':
                $msg = sprintf("[%s] %s commented on <%s|%s> saying:\n%s",
                    $project, $reporter, $url, $summary, $note
                );
                break;
        }
        $this->notify($msg, $this->get_channel($project));
    }

    function get_channel($project) {
        $channels = plugin_config_get('channels');
        return isset($channels[$project]) ? $channels[$project] : plugin_config_get('default_channel');
    }

    function notify($msg, $channel) {
        $ch = curl_init();
        // @see https://my.slack.com/services/new/incoming-webhook
        $url = sprintf('https://%s.slack.com/services/hooks/incoming-webhook?token=%s', 
            plugin_config_get('instance'), plugin_config_get('token')
        );
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $data = 'payload=' . json_encode(array(
            'channel' => $channel,
            'username' => plugin_config_get('bot_name'),
            'text' => $msg,
        ));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $result = curl_exec($ch);
        curl_close($ch);
    }

}
