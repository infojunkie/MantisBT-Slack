MantisBT-Slack
==============

A [MantisBT](http://www.mantisbt.org/) plugin to send bug updates to [Slack](https://slack.com/) channels.


# Setup
* Setup a new Slack instance and note the subdomain used.
* On the Slack side, add a new "Incoming Webhooks" integration and note the token that Slack generates for you.
* On the MantisBT side, access the plugin's configuration page and fill in your Slack subdomain and the webhook token.
* You can also map your MantisBT projects to Slack channels by following the instructions on the plugin's configuration page. Make sure the *plugin_Slack_channels* configuration option is set to "All Users".

