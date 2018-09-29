<?php
/**
 * Slack Integration
 * Copyright (C) Karim Ratib (karim@meedan.com)
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

access_ensure_global_level( config_get( 'manage_plugin_threshold' ) );

layout_page_header( plugin_lang_get( 'title' ) );

layout_page_begin( 'manage_overview_page.php' );

print_manage_menu( 'manage_plugin_page.php' );

?>

<div class="col-md-12 col-xs-12">
<div class="space-10"></div>
<div class="form-container">
<form action="<?php echo plugin_page( 'config' ) ?>" method="post">
<fieldset>
<div class="widget-box widget-color-blue2">
<div class="widget-header widget-header-small">
    <h4 class="widget-title lighter">
        <i class="ace-icon fa fa-exchange"></i>
        <?php echo plugin_lang_get( 'title' ) ?>
    </h4>
</div>

<?php echo form_security_field( 'plugin_Slack_config' ) ?>
<div class="widget-body">
<div class="widget-main no-padding">
<div class="table-responsive">
<table class="table table-bordered table-condensed table-striped">

    <tr>
        <td class="category">
            <?php echo plugin_lang_get( 'url_webhook' ) ?>
        </td>
        <td>
            <input size="80" type="text" name="url_webhook" value="<?php echo plugin_config_get( 'url_webhook' )?>" />
            <input type="submit" name="url_webhook_test" value="<?php echo plugin_lang_get( 'url_webhook_test' )?>" />
        </td>
    </tr>

    <tr>
      <td class="category">
        <?php echo plugin_lang_get( 'url_webhooks' )?>
      </td>
      <td colspan="2">
        <p>
          Specifies the mapping between Mantis project names and Slack webhooks.
        </p>
        <p>
          Option name is <strong>plugin_Slack_url_webhooks</strong> and is an array of 'Mantis project name' => 'Slack webhook'.
          Array options must be set using the <a href="adm_config_report.php">Configuration Report</a> screen.
          The current value of this option is:<pre><?php var_export(plugin_config_get( 'url_webhooks' ))?></pre>
        </p>
      </td>
    </tr>

    <tr>
      <td class="category">
        <?php echo plugin_lang_get( 'bot_name' )?>
      </td>
      <td colspan="2">
        <input type="text" name="bot_name" value="<?php echo plugin_config_get( 'bot_name' )?>" />
      </td>
    </tr>

    <tr>
      <td class="category">
        <?php echo plugin_lang_get( 'bot_icon' )?>
      </td>
      <td colspan="2">
        <p>
          Can be either a URL pointing to small image or an emoji of the form :emoji:</br>
          Defaults to the Mantis logo.
        </p>
        <input type="text" name="bot_icon" value="<?php echo plugin_config_get( 'bot_icon' )?>" />
      </td>
    </tr>

    <tr>
      <td class="category">
        <?php echo plugin_lang_get( 'notifications' )?>
      </td>
      <td colspan="2">
        <input type="checkbox" name="notification_bug_report" <?php if (plugin_config_get( 'notification_bug_report' )) echo "checked"; ?> /> <?php echo plugin_lang_get( 'notification_bug_report' )?> <br>
        <input type="checkbox" name="notification_bug_update" <?php if (plugin_config_get( 'notification_bug_update' )) echo "checked"; ?> /> <?php echo plugin_lang_get( 'notification_bug_update' )?> <br>
        <input type="checkbox" name="notification_bug_deleted" <?php if (plugin_config_get( 'notification_bug_deleted' )) echo "checked"; ?> /> <?php echo plugin_lang_get( 'notification_bug_deleted' )?> <br>
        <input type="checkbox" name="notification_bugnote_add" <?php if (plugin_config_get( 'notification_bugnote_add' )) echo "checked"; ?> /> <?php echo plugin_lang_get( 'notification_bugnote_add' )?> <br>
        <input type="checkbox" name="notification_bugnote_edit" <?php if (plugin_config_get( 'notification_bugnote_edit' )) echo "checked"; ?> /> <?php echo plugin_lang_get( 'notification_bugnote_edit' )?> <br>
        <input type="checkbox" name="notification_bugnote_deleted" <?php if (plugin_config_get( 'notification_bugnote_deleted' )) echo "checked"; ?> /> <?php echo plugin_lang_get( 'notification_bugnote_deleted' )?> <br>
        <input type="checkbox" name="skip_private" <?php if (plugin_config_get( 'skip_private' )) echo "checked"; ?> /> <?php echo plugin_lang_get( 'skip_private' )?> <br>
        <input type="checkbox" name="skip_bulk" <?php if (plugin_config_get( 'skip_bulk' )) echo "checked"; ?> /> <?php echo plugin_lang_get( 'skip_bulk' )?>
      </td>
    </tr>

    <tr>
      <td class="category">
        <?php echo plugin_lang_get( 'link_names' )?>
      </td>
      <td colspan="2">
        <input type="checkbox" name="link_names" <?php if (plugin_config_get( 'link_names' )) echo "checked"; ?> />
      </td>
    </tr>

    <tr>
      <td class="category">
        <?php echo plugin_lang_get( 'default_channel' )?>
      </td>
      <td colspan="2">
        <input type="text" name="default_channel" value="<?php echo plugin_config_get( 'default_channel' )?>" />
      </td>
    </tr>

    <tr>
      <td class="category">
        <?php echo plugin_lang_get( 'channels' )?>
      </td>
      <td colspan="2">
        <p>
          Specifies the mapping between Mantis project names and Slack #channels.
        </p>
        <p>
          Option name is <strong>plugin_Slack_channels</strong> and is an array of 'Mantis project name' => 'Slack channel name'.
          Array options must be set using the <a href="adm_config_report.php">Configuration Report</a> screen.
          The current value of this option is:<pre><?php var_export(plugin_config_get( 'channels' ))?></pre>
        </p>
      </td>
    </tr>

    <tr>
      <td class="category">
        <?php echo plugin_lang_get( 'columns' )?>
      </td>
      <td colspan="2">
        <p>
          Specifies the bug fields that should be attached to the Slack notifications.
        </p>
        <p>
          Option name is <strong>plugin_Slack_columns</strong> and is an array of bug column names.
          Array options must be set using the <a href="adm_config_report.php">Configuration Report</a> screen.
          <?php
            $t_columns = columns_get_all( @$t_project_id );
            $t_all = implode( ', ', $t_columns );
          ?>
          Available column names are:<div><textarea name="all_columns" readonly="readonly" cols="80" rows="5"><?php echo $t_all ?></textarea></div>
          The current value of this option is:<pre><?php var_export(plugin_config_get( 'columns' ))?></pre>
        </p>
      </td>
    </tr>

    <tr>
      <td class="category">
        <?php echo plugin_lang_get( 'usernames' )?>
      </td>
      <td colspan="2">
        <p>
          Specifies the mapping between Mantis and Slack user names.
        </p>
        <p>
          Option name is <strong>plugin_Slack_usernames</strong> and is an array of 'Mantis user name' => 'Slack user name'.
          Array options must be set using the <a href="adm_config_report.php">Configuration Report</a> screen.
          The current value of this option is:<pre><?php var_export(plugin_config_get( 'usernames' ))?></pre>
        </p>
      </td>
    </tr>

</table>
</div>
</div>
<div class="widget-toolbox padding-8 clearfix">
    <input type="submit" class="btn btn-primary btn-white btn-round" value="<?php echo plugin_lang_get( 'action_update' ) ?>" />
</div>
</div>
</div>
</fieldset>
</form>
</div>
</div>

<?php
layout_page_end();
