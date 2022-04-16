This customization adds a popup window for the error log.  This was developed to easily check the error log while doing other development work.

This avoids heavily trying to modify too much aspects of the code and requires javascript and JQuery to be functional.

There is a feature in which you can add this early in in your PHP stack to auto include it in SMF without needing the hooks to be installed.  I won't provide any configuration examples for this, but one way to do this is to configure the php.ini setting auto_prepend_file for your forum directory.