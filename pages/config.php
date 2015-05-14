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

auth_reauthenticate( );
access_ensure_global_level( config_get( 'manage_plugin_threshold' ) );

html_page_top( plugin_lang_get( 'title' ) );

print_manage_menu( );

?>

<br />

<form action="<?php echo plugin_page( 'config_edit' )?>" method="post">
<?php echo form_security_field( 'plugin_Slack_config_edit' ) ?>
  <table align="center" class="width75" cellspacing="1">

    <tr>
      <td class="form-title" colspan="3">
        <?php echo plugin_lang_get( 'title' ) . ' : ' . plugin_lang_get( 'config' )?>
      </td>
    </tr>

    <tr <?php echo helper_alternate_class( )?>>
      <td class="category">
        <?php echo plugin_lang_get( 'url_webhook' )?>
      </td>
      <td  colspan="2">
        <input type="text" name="url_webhook" value="<?php echo plugin_config_get( 'url_webhook' )?>" />
      </td>
    </tr>


    <tr <?php echo helper_alternate_class( )?>>
      <td class="category">
        <?php echo plugin_lang_get( 'bot_name' )?>
      </td>
      <td  colspan="2">
        <input type="text" name="bot_name" value="<?php echo plugin_config_get( 'bot_name' )?>" />
      </td>
    </tr>

    <tr <?php echo helper_alternate_class( )?>>
      <td class="category">
        <?php echo plugin_lang_get( 'bot_icon' )?>
      </td>
      <td  colspan="2">
        <input type="text" name="bot_icon" value="<?php echo plugin_config_get( 'bot_icon' )?>" />
      </td>
    </tr>

    <tr <?php echo helper_alternate_class( )?>>
      <td class="category">
        <?php echo plugin_lang_get( 'skip_bulk' )?>
      </td>
      <td  colspan="2">
        <input type="checkbox" name="skip_bulk" <?php if (plugin_config_get( 'skip_bulk' )) echo "checked"; ?> />
      </td>
    </tr>

    <tr <?php echo helper_alternate_class( )?>>
      <td class="category">
        <?php echo plugin_lang_get( 'default_channel' )?>
      </td>
      <td  colspan="2">
        <input type="text" name="default_channel" value="<?php echo plugin_config_get( 'default_channel' )?>" />
      </td>
    </tr>

    <tr <?php echo helper_alternate_class( )?>>
      <td class="category">
        <?php echo plugin_lang_get( 'channels' )?>
      </td>
      <td  colspan="2">
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

    <tr <?php echo helper_alternate_class( )?>>
      <td class="category">
        <?php echo plugin_lang_get( 'columns' )?>
      </td>
      <td  colspan="2">
        <p>
          Specifies the bug fields that should be attached to the Slack notifications.
        </p>
        <p>
          Option name is <strong>plugin_Slack_columns</strong> and is an array of bug column names.
          Array options must be set using the <a href="adm_config_report.php">Configuration Report</a> screen.
          <?php
            $t_columns = columns_get_all( $t_project_id );
            $t_all = implode( ', ', $t_columns );
          ?>
          Available column names are:<div><textarea name="all_columns" readonly="readonly" cols="80" rows="5"><?php echo $t_all ?></textarea></div>
          The current value of this option is:<pre><?php var_export(plugin_config_get( 'columns' ))?></pre>
        </p>
      </td>
    </tr>

    <tr>
      <td class="center" colspan="3">
        <input type="submit" class="button" value="<?php echo lang_get( 'change_configuration' )?>" />
      </td>
    </tr>

  </table>
</form>

<?php
html_page_bottom();
