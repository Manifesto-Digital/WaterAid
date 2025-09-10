# Wateraid Webform Encrypt

This module is based off the webform_encrypt contrib module
with patch from https://www.drupal.org/node/2855702#comment-12097549.

However, the module has been modified so that all webform fields are encrypted (rather than allowing individual fields
to be set to use encryption).  Encryption is globally enabled/disabled across all webforms.

SETUP:
You first need to setup an Encryption key (admin/config/system/keys) and
Encryption profile (admin/config/system/encryption/profiles).  You can then enable webform encryption by going to
admin/config/system/wateraid_webform_encrypt
