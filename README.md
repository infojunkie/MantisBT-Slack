# Changes

MantisBT-Slack forked from https://github.com/infojunkie/MantisBT-Slack
I added the possibility to map bug severity/bug handler with slack channels.

Setup:
Activate "Use severity" in the plugin configuration pages.
Then you need to map your bug severities to Slack channels by setting the *plugin_Slack_severity_channels* option in the configuration report screen from MantisBT.
Example value for this setting in configuration report:
            
            All users, All projects, complex
            array (
                10 => '#integration',
                70 => '#installation'
            )
            
Activate "Use handler" in the plugin configuration pages.
Then you need to map your bug handlers to Slack channels by setting the *plugin_Slack_handler_channels* option in the configuration report screen from MantisBT.
Example value for this setting in configuration report:

            All users, All projects, complex
            array (
                 'support' => '#support',
            )

A [MantisBT](http://www.mantisbt.org/) plugin to send bug updates to [Slack](https://slack.com/) and [Mattermost](https://about.mattermost.com/) channels.


# Setup
* The `master` branch requires Mantis 2.0.x, while the `master-1.2.x` branch works for Mantis 1.2.x.
* Extract this repo to your *Mantis folder/plugins/Slack*.
* On the Slack side, add a new "Incoming Webhooks" integration and note the URL that Slack generates for you.
* On the MantisBT side, access the plugin's configuration page and fill in your Slack webhook URL.
* You can map your MantisBT projects to Slack channels by setting the *plugin_Slack_channels* option in Mantis.  Follow the instructions on the plugin's configuration page to get there. Make sure the *plugin_Slack_channels* configuration option is set to "All Users", with type "complex".
    Example value for this setting:

            array (
              'My First Mantis Project' => '#general',
              'My Second Mantis Project' => '#second-project'
            )

* You can specify which bug fields appear in the Slack notifications. Edit the *plugin_Slack_columns* configuration option for this purpose.  Follow the instructions on the plugin configuration page.
