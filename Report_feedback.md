## Please use wp_enqueue commands
all suggestions fixed.

## Asks users to edit/writes to plugin
Mainly fixed, except for the following:

scoby-analytics/src/Helpers.php:133                                      copy(self::getProxySource(), self::getProxyTarget());

We are unsure on how to install our privacy proxy in the mu-plugins directory other than by copying it there during installation.

## Don't Use Error Reporting in Production Code

Mainly fixed, except for the following: 

scoby-analytics/src/Logger.php:18                       error_log(sprintf('%s: %s. Details: %s', $level, trim($message, '.'), json_encode($context)));

Users can enable logging in the Plugin settings, in which case - and only then - we produce comprehensive output in the error log. Please advise if there is a better way to do this. Happy to adjust.

## Calling files remotely
all suggestions fixed.

## Don't Force Set PHP Limits Globally
all suggestions fixed.

## Don't Use Error Reporting in Production Code
all suggestions fixed.

## Using CURL Instead of HTTP API
all suggestions fixed.

## Please use WordPress' file uploader
all suggestions fixed.

## Internationalization: Don't use variables or defines as text, context or text domain parameters.
all suggestions fixed.

## Do not use HEREDOC or NOWDOC syntax in your plugins
all suggestions fixed.

## Data Must be Sanitized, Escaped, and Validated
fixed in own code, cannot be fixed in ralouphie/getallheaders

## Processing the whole input
all suggestions fixed.

## Variables and options must be escaped when echo'd
all suggestions fixed.

