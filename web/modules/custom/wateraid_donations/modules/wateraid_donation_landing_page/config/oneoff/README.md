# Features: Oneoff

## Why
This folder lists config originally part of the feature which is not required to be reverted in a config updates.
It is only required if the feature is being enabled for the first time or has never been enabled before on a site.

* This config will not be included automatically in future feature "writes" (unless specifically enabled)
* This config will not be reverted in config updates
* This config can be safely changed on existing Wateraid sites

## Fresh Install
At the time of writing, the Features system does not support separating config within a feature to run once only.
If the content is required for future installs then these files will need to be migrated back to this feature's install (or optional) folder. 
Or a custom install hook could be written.
