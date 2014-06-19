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
require_once('FastJSON.class.php5');

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

    function hooks() {
        return array(
            'EVENT_REPORT_BUG' => 'bug_report_update',
            'EVENT_UPDATE_BUG' => 'bug_report_update',
            'EVENT_BUG_DELETED' => 'bug_deleted',
            'EVENT_BUG_ACTION' => 'bug_action',
            'EVENT_BUGNOTE_ADD' => 'bugnote_add_edit',
            'EVENT_BUGNOTE_EDIT' => 'bugnote_add_edit',
            'EVENT_BUGNOTE_DELETED' => 'bugnote_deleted',
        );
    }

    function bug_report_update($event, $bug, $bug_id) {
        $project = project_get_name($bug->project_id);
        $url = string_get_bug_view_url_with_fqdn($bug_id);
        $summary = SlackPlugin::clean_summary(bug_format_summary($bug_id, SUMMARY_FIELD));
        $reporter = '@' . user_get_name(auth_get_current_user_id());
        $msg = sprintf(plugin_lang_get($event === 'EVENT_REPORT_BUG' ? 'bug_created' : 'bug_updated'), 
            $project, $reporter, $url, $summary
        );
        $this->notify($msg, $this->get_channel($project), $this->get_attachment($bug));
    }

    function bug_action($event, $action, $bug_id) {
        if ($action !== 'DELETE') {
            $bug = bug_get($bug_id);
            $this->bug_report_update('EVENT_UPDATE_BUG', $bug, $bug_id);
        }
    }

    function bug_deleted($event, $bug_id) {
        $bug = bug_get($bug_id);
        $project = project_get_name($bug->project_id);
        $reporter = '@' . user_get_name(auth_get_current_user_id());
        $summary = SlackPlugin::clean_summary(bug_format_summary($bug_id, SUMMARY_FIELD));
        $msg = sprintf(plugin_lang_get('bug_deleted'), $project, $reporter, $summary);
        $this->notify($msg, $this->get_channel($project));
    }

    function bugnote_add_edit($event, $bug_id, $bugnote_id) {
        $bug = bug_get($bug_id);
        $url = string_get_bugnote_view_url_with_fqdn($bug_id, $bugnote_id);
        $project = project_get_name($bug->project_id);
        $summary = SlackPlugin::clean_summary(bug_format_summary($bug_id, SUMMARY_FIELD));
        $reporter = '@' . user_get_name(auth_get_current_user_id());
        $note = bugnote_get_text($bugnote_id);
        $msg = sprintf(plugin_lang_get($event === 'EVENT_BUGNOTE_ADD' ? 'bugnote_created' : 'bugnote_updated'), 
            $project, $reporter, $url, $summary, $note
        );
        $this->notify($msg, $this->get_channel($project));
    }

    function bugnote_deleted($event, $bug_id, $bugnote_id) {
        $bug = bug_get($bug_id);
        $project = project_get_name($bug->project_id);
        $url = string_get_bug_view_url_with_fqdn($bug_id);
        $summary = SlackPlugin::clean_summary(bug_format_summary($bug_id, SUMMARY_FIELD));
        $reporter = '@' . user_get_name(auth_get_current_user_id());
        $msg = sprintf(plugin_lang_get('bugnote_deleted'), $project, $reporter, $url, $summary);
        $this->notify($msg, $this->get_channel($project));
    }

    static function clean_summary($summary) {
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

	function customIterationFunc($bug, $t_id) {
        return custom_field_get_value( $t_id, $bug->id );
    }

    function format_value($bug, $field_name) {
        $values = array(
            'id' => idFunc,
            'project_id' => projIdFunc,
            'reporter_id' => reporterIdFunc,
            'handler_id' => handlerIdFunc,
            'duplicate_id' => duplicateIdFunc,
            'priority' => priorityFunc,
            'severity' => severityFunc,
            'reproducibility' => reproducibilityFunc,
            'status' => statusFunc,
            'resolution' => resolutionFunc,
            'projection' => projectionFunc,
            'category_id' => categoryIdFunc,
            'eta' => etaFunc,
            'view_state' => viewStateFunc,
            'sponsorship_total' => sponsorshipTotalFunc,
            'os' => osFunc,
            'os_build' => osBuildFunc,
            'platform' => platformFunc,
            'version' => versionFunc,
            'fixed_in_version' => fixedInVersionFunc,
            'target_version' => targetVersionFunc,
            'build' => buildFunc,
            'summary' => summaryFunc,
            'last_updated' => lastUpdatedFunc,
            'date_submitted' => dateSubmittedFunc,
            'due_date' => dueDateFunc,
            'description' => descriptionFunc,
            'steps_to_reproduce' => stepsToReproduceFunc,
            'additional_information' => additionalInformationFunc,
        );

		if( !function_exists('idFunc') )
		{
			function idFunc($bug) { return sprintf('<%s|%s>', string_get_bug_view_url_with_fqdn($bug->id), $bug->id); }
			function projIdFunc($bug) { return project_get_name($bug->project_id); }
			function reporterIdFunc($bug) { return '@' . user_get_name($bug->reporter_id); }
			function handlerIdFunc($bug) { return empty($bug->handler_id) ? plugin_lang_get('no_user') : ('@' . user_get_name($bug->handler_id)); }
			function duplicateIdFunc($bug) { return sprintf('<%s|%s>', string_get_bug_view_url_with_fqdn($bug->duplicate_id), $bug->duplicate_id); }
			function priorityFunc($bug) { return get_enum_element( 'priority', $bug->priority ); }
			function severityFunc($bug) { return get_enum_element( 'severity', $bug->severity ); }
			function reproducibilityFunc($bug) { return get_enum_element( 'reproducibility', $bug->reproducibility ); }
			function statusFunc($bug) { return get_enum_element( 'status', $bug->status ); }
			function resolutionFunc($bug) { return get_enum_element( 'resolution', $bug->resolution ); }
			function projectionFunc($bug) { return get_enum_element( 'projection', $bug->projection ); }
			function categoryIdFunc($bug) { return category_full_name( $bug->category_id, false ); }
			function etaFunc($bug) { return get_enum_element( 'eta', $bug->eta ); }
			function viewStateFunc($bug) { return $bug->view_state == VS_PRIVATE ? lang_get('private') : lang_get('public'); }
			function sponsorshipTotalFunc($bug) { return sponsorship_format_amount( $bug->sponsorship_total ); }
			function osFunc($bug) { return $bug->os; }
			function osBuildFunc($bug) { return $bug->os_build; }
			function platformFunc($bug) { return $bug->platform; }
			function versionFunc($bug) { return $bug->version; }
			function fixedInVersionFunc($bug) { return $bug->fixed_in_version; }
			function targetVersionFunc($bug) { return $bug->target_version; }
			function buildFunc($bug) { return $bug->build; }
			function summaryFunc($bug) { return SlackPlugin::clean_summary(bug_format_summary($bug->id, SUMMARY_FIELD)); }
			function lastUpdatedFunc($bug) { return date( config_get( 'short_date_format' ), $bug->last_updated ); }
			function dateSubmittedFunc($bug) { return date( config_get( 'short_date_format' ), $bug->date_submitted ); }
			function dueDateFunc($bug) { return date( config_get( 'short_date_format' ), $bug->due_date ); }
			function descriptionFunc($bug) { return string_display_links( $bug->description ); }
			function stepsToReproduceFunc($bug) { return string_display_links( $bug->steps_to_reproduce ); }
			function additionalInformationFunc($bug) { return string_display_links( $bug->additional_information ); }
		}
        
        // Discover custom fields.
        $t_related_custom_field_ids = custom_field_get_linked_ids( $bug->project_id );
        foreach ( $t_related_custom_field_ids as $t_id ) {
            $t_def = custom_field_get_definition( $t_id );
            $values['custom_' . $t_def['name']] = $this->customIterationFunc($bug, $t_id);
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
        $data = array('payload' => FastJSON::($payload));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $result = curl_exec($ch);
        curl_close($ch);
    }

}
