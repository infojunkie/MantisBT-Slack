<?php
/**
 * Slack Integration
 * Copyright (C) 2014 Karim Ratib (karim.ratib@gmail.com)
 *
 * Slack Integration is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License 2
 * as published by the Free Software Foundation.
 *
 * Slack Integration is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Slack Integration; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA
 * or see http://www.gnu.org/licenses/.
 */

class SlackPlugin extends MantisPlugin {
    var $skip = false;

    function register() {
        $this->name = plugin_lang_get( 'title' );
        $this->description = plugin_lang_get( 'description' );
        $this->page = 'config';
        $this->version = '1.0';
        $this->requires = array(
            'MantisCore' => '>= 1.2.0',
        );
        $this->author = 'Karim Ratib';
        $this->contact = 'karim@meedan.com';
        $this->url = 'http://meedan.com';
    }

    function install() {
        if (version_compare(PHP_VERSION, '5.3.0', '<')) {
            plugin_error(ERROR_PHP_VERSION, ERROR);
            return false;
        }
        if (!extension_loaded('curl')) {
            plugin_error(ERROR_NO_CURL, ERROR);
            return false;
        }
        return true;
    }

    function config() {
        return array(
            'url_webhook' => '',
            'bot_name' => 'mantis',
            'bot_icon' => '',
            'skip_bulk' => true,
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

    function hooks() {
        return array(
            'EVENT_REPORT_BUG' => 'bug_report_update',
            'EVENT_UPDATE_BUG' => 'bug_report_update',
            'EVENT_BUG_DELETED' => 'bug_deleted',
            'EVENT_BUG_ACTION' => 'bug_action',
            'EVENT_BUGNOTE_ADD' => 'bugnote_add_edit',
            'EVENT_BUGNOTE_EDIT' => 'bugnote_add_edit',
            'EVENT_BUGNOTE_DELETED' => 'bugnote_deleted',
            'EVENT_BUGNOTE_ADD_FORM' => 'bugnote_add_form',
        );
    }

    function bugnote_add_form($event, $bug_id) {
        if ($_SERVER['PHP_SELF'] !== '/bug_update_page.php') return;

        echo '<tr><td class="center" colspan="6">';
        echo '<input ', helper_get_tab_index(), ' name="slack_skip" type="checkbox" >' . plugin_lang_get('skip') . '</input>';
        echo '</td></tr>';
    }

    function bug_report_update($event, $bug, $bug_id) {
        $this->skip = $this->skip || gpc_get_bool('slack_skip');

        $project = project_get_name($bug->project_id);
        $url = string_get_bug_view_url_with_fqdn($bug_id);
        $summary = $this->clean_summary($bug);
        $reporter = '@' . user_get_name(auth_get_current_user_id());
        $msg = sprintf(plugin_lang_get($event === 'EVENT_REPORT_BUG' ? 'bug_created' : 'bug_updated'),
            $project, $reporter, $url, $summary
        );
        $this->notify($msg, $this->get_channel($project), $this->get_attachment($bug));
    }

    function bug_action($event, $action, $bug_id) {
        $this->skip = $this->skip || gpc_get_bool('slack_skip') || plugin_config_get('skip_bulk');

        if ($action !== 'DELETE') {
            $bug = bug_get($bug_id);
            $this->bug_report_update('EVENT_UPDATE_BUG', $bug, $bug_id);
        }
    }

    function bug_deleted($event, $bug_id) {
        $this->skip = $this->skip || gpc_get_bool('slack_skip');

        $bug = bug_get($bug_id);
        $project = project_get_name($bug->project_id);
        $reporter = '@' . user_get_name(auth_get_current_user_id());
        $summary = $this->clean_summary($bug);
        $msg = sprintf(plugin_lang_get('bug_deleted'), $project, $reporter, $summary);
        $this->notify($msg, $this->get_channel($project));
    }

    function bugnote_add_edit($event, $bug_id, $bugnote_id) {
        $this->skip = $this->skip || gpc_get_bool('slack_skip');

        $bug = bug_get($bug_id);
        $url = string_get_bugnote_view_url_with_fqdn($bug_id, $bugnote_id);
        $project = project_get_name($bug->project_id);
        $summary = $this->clean_summary($bug);
        $reporter = '@' . user_get_name(auth_get_current_user_id());
        $note = bugnote_get_text($bugnote_id);
        $msg = sprintf(plugin_lang_get($event === 'EVENT_BUGNOTE_ADD' ? 'bugnote_created' : 'bugnote_updated'),
            $project, $reporter, $url, $summary, $note
        );
        $this->notify($msg, $this->get_channel($project));
    }

    function bugnote_deleted($event, $bug_id, $bugnote_id) {
        $this->skip = $this->skip || gpc_get_bool('slack_skip');

        $bug = bug_get($bug_id);
        $project = project_get_name($bug->project_id);
        $url = string_get_bug_view_url_with_fqdn($bug_id);
        $summary = $this->clean_summary($bug);
        $reporter = '@' . user_get_name(auth_get_current_user_id());
        $msg = sprintf(plugin_lang_get('bugnote_deleted'), $project, $reporter, $url, $summary);
        $this->notify($msg, $this->get_channel($project));
    }

    function clean_summary($bug) {
        $summary = bug_format_id($bug->id) . ': ' . string_display_line_links($bug->summary);
        return strip_tags(html_entity_decode($summary));
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
        $self = $this;
        $values = array(
            'id' => function($bug) { return sprintf('<%s|%s>', string_get_bug_view_url_with_fqdn($bug->id), $bug->id); },
            'project_id' => function($bug) { return project_get_name($bug->project_id); },
            'reporter_id' => function($bug) { return '@' . user_get_name($bug->reporter_id); },
            'handler_id' => function($bug) { return empty($bug->handler_id) ? plugin_lang_get('no_user') : ('@' . user_get_name($bug->handler_id)); },
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
            'summary' => function($bug) use($self) { return $self->clean_summary($bug); },
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
        return array_key_exists($project, $channels) ? $channels[$project] : plugin_config_get('default_channel');
    }

    function notify($msg, $channel, $attachment = FALSE) {
        if ($this->skip) return;
        if (empty($channel)) return;

        $ch = curl_init();
        // @see https://my.slack.com/services/new/incoming-webhook
        // remove istance and token and add plugin_Slack_url config , see configurations with url above
        $url = sprintf('%s', plugin_config_get('url_webhook'));
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
