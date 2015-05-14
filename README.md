MantisBT-Slack
==============

A [MantisBT](http://www.mantisbt.org/) plugin to send bug updates to [Slack](https://slack.com/) channels.


# Setup
* Extract this repo to your *Mantis folder/plugins/Slack*.
* On the Slack side, add a new "Incoming Webhooks" integration and note the URL that Slack generates for you.
* On the MantisBT side, access the plugin's configuration page and fill in your Slack webhook URL.
* You can map your MantisBT projects to Slack channels by following the instructions on the plugin's configuration page. Make sure the *plugin_Slack_channels* configuration option is set to "All Users".
* You can specify which bug fields appear in the Slack notifications. Edit the *plugin_Slack_columns* configuration option for this purpose.

