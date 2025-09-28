ClueBot NG — Report Interface
=============================

The report interface handles false positive submission reports & interacts with the local database.

## Runtime Configuration

The local MySQL database (written to via the bot) is a hard runtime dependencies.

For specific functionality, such as the 'live' API call, the core is also a dependency.

All details are contained within `web-settings.php`, which should be considered sensitive.
