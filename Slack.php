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
            'EVENT_BUGNOTE_EDIT' => 'new_update_bugnote',
            'EVENT_BUG_ACTION' => 'bug_action',
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
            'columns' => array(
                'status',
                'handler_id',
                'target_version',
                'priority',
                'severity',
            ),
        );
    }

    function new_update_bug($event, $bug, $bug_id) {
        $url = string_get_bug_view_url_with_fqdn($bug_id);
        $summary = SlackPlugin::clean_summary(bug_format_summary($bug_id, SUMMARY_FIELD));
        $project = project_get_name($bug->project_id);
        $reporter = '@' . user_get_name($bug->reporter_id);
        $handler = empty($bug->handler_id) ? plugin_lang_get('no_user') : ('@' . user_get_name($bug->handler_id));
        $modifier = '@' . user_get_name(auth_get_current_user_id());
        $status = get_enum_element( 'status', $bug->status );
        switch ($event) {
            case 'EVENT_REPORT_BUG':
                $msg = sprintf(plugin_lang_get('bug_created'),
                    $project, $reporter, $url, $summary
                );
                break;
            case 'EVENT_UPDATE_BUG':
                $msg = sprintf(plugin_lang_get('bug_updated'),
                    $project, $modifier, $url, $summary
                );
                break;
        }
        $this->notify($msg, $this->get_channel($project), $this->get_attachment($bug));
    }

    function new_update_bugnote($event, $bug_id, $bugnote_id) {
        $url = string_get_bugnote_view_url_with_fqdn($bug_id, $bugnote_id);
        $bug = bug_get($bug_id);
        $project = project_get_name($bug->project_id);
        $summary = SlackPlugin::clean_summary(bug_format_summary($bug_id, SUMMARY_FIELD));
        $reporter_id = bugnote_get_field($bugnote_id, 'reporter_id');
        $reporter = '@' . user_get_name($reporter_id);
        $note = bugnote_get_text($bugnote_id);
        switch ($event) {
            case 'EVENT_BUGNOTE_ADD':
                $msg = sprintf(plugin_lang_get('bugnote_created'),
                    $project, $reporter, $url, $summary, $note
                );
                break;
            case 'EVENT_BUGNOTE_EDIT':
                $msg = sprintf(plugin_lang_get('bugnote_updated'),
                    $project, $reporter, $url, $summary, $note
                );
                break;                
        }
        $this->notify($msg, $this->get_channel($project));
    }

    function bug_action($event, $action, $bug_id) {
        $bug = bug_get($bug_id);
        $this->new_update_bug('EVENT_UPDATE_BUG', $bug, $bug_id);
    }

    static function clean_summary($summary) {
        return strip_tags(preg_replace('/\[<a (.*)\/a>\]/', '', $summary));
    }

    function get_attachment($bug) {
        $attachment = array('fallback' => '');
        $t_columns = (array)plugin_config_get('columns');
        foreach ($t_columns as $t_column) {
            $title = column_get_title( $t_column );
            $value = $this->format_value($bug, $t_column);
            
            if ($title && $value) {
                $attachment['fallback'] .= $title . ': ' . $value . "\n";
                $attachment['fields'][] = array(
                    'title' => $title,
                    'value' => $value,
                    'short' => !column_is_extended( $t_column ),
                );
            }
        }
        return $attachment;
    }

    function format_value($bug, $field_name) {
        $values = array(
            'id' => function($bug) { return sprintf('<%s|%s>', string_get_bug_view_url_with_fqdn($bug->id), $bug->id); },
            'project_id' => function($bug) { return project_get_name($bug->project_id); },
            'reporter_id' => function($bug) { return '@' . user_get_name($bug->reporter_id); },
            'handler_id' => function($bug) { return '@' . user_get_name($bug->handler_id); },
            'duplicate_id' => function($bug) { return sprintf('<%s|%s>', string_get_bug_view_url_with_fqdn($bug->duplicate_id), $bug->duplicate_id); },
            'priority' => function($bug) { return get_enum_element( 'priority', $bug->priority ); },
            'severity' => function($bug) { return get_enum_element( 'severity', $bug->severity ); },
            'reproducibility' => function($bug) { return get_enum_element( 'reproducibility', $bug->reproducibility ); },
            'status' => function($bug) { return get_enum_element( 'status', $bug->status ); },
            'resolution' => function($bug) { return get_enum_element( 'resolution', $bug->resolution ); },
            'projection' => function($bug) { return get_enum_element( 'projection', $bug->projection ); },
            'category_id' => function($bug) { return category_full_name( $bug->category_id, false ); },
            'eta' => function($bug) { return get_enum_element( 'eta', $bug->eta ); },
            'view_state' => function($bug) { return $bug->view_state == VS_PRIVATE ? lang_get('private') : lang_get('public'); },
            'sponsorship_total' => function($bug) { return sponsorship_format_amount( $bug->sponsorship_total ); },
            'os' => function($bug) { return $bug->os; },
            'os_build' => function($bug) { return $bug->os_build; },
            'platform' => function($bug) { return $bug->platform; },
            'version' => function($bug) { return $bug->version; },
            'fixed_in_version' => function($bug) { return $bug->fixed_in_version; },
            'target_version' => function($bug) { return $bug->target_version; },
            'build' => function($bug) { return $bug->build; },
            'summary' => function($bug) { return SlackPlugin::clean_summary(bug_format_summary($bug->id, SUMMARY_FIELD)); },
            'last_updated' => function($bug) { return date( config_get( 'short_date_format' ), $bug->last_updated ); },
            'date_submitted' => function($bug) { return date( config_get( 'short_date_format' ), $bug->date_submitted ); },
            'due_date' => function($bug) { return date( config_get( 'short_date_format' ), $bug->due_date ); },
            'description' => function($bug) { return string_display_links( $bug->description ); },
            'steps_to_reproduce' => function($bug) { return string_display_links( $bug->steps_to_reproduce ); },
            'additional_information' => function($bug) { return string_display_links( $bug->additional_information ); },
        );
        // Discover custom fields.
        $t_related_custom_field_ids = custom_field_get_linked_ids( $bug->project_id );
        foreach ( $t_related_custom_field_ids as $t_id ) {
            $t_def = custom_field_get_definition( $t_id );
            $values['custom_' . $t_def['name']] = function($bug) use ($t_id) {
                return custom_field_get_value( $t_id, $bug->id );
            };
        }
        if (isset($values[$field_name])) {
            $func = $values[$field_name];
            return $func($bug);
        }
        else {
            return sprintf(plugin_lang_get('unknown_field'), $field_name);
        }
    }

    function get_channel($project) {
        $channels = plugin_config_get('channels');
        return isset($channels[$project]) ? $channels[$project] : plugin_config_get('default_channel');
    }

    function notify($msg, $channel, $attachment = FALSE) {
        $ch = curl_init();
        // @see https://my.slack.com/services/new/incoming-webhook
        $url = sprintf('https://%s.slack.com/services/hooks/incoming-webhook?token=%s', 
            plugin_config_get('instance'), plugin_config_get('token')
        );
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $payload = array(
            'channel' => $channel,
            'username' => plugin_config_get('bot_name'),
            'text' => $msg,
        );
        $bot_icon = plugin_config_get('bot_icon');
        if (preg_match('/^:[a-z0-9_\-]+:$/i', $bot_icon)) {
            $payload['icon_emoji'] = $bot_icon;
        } elseif ($bot_icon) {
            $payload['icon_url'] = $bot_icon;
        }
        if ($attachment) {
            $payload['attachments'] = array($attachment);
        }
        $data = array('payload' => json_encode($payload));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $result = curl_exec($ch);
        curl_close($ch);
    }

}
