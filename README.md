# Bittracker
Bittorrent tracker

## Usage
See `index.php` and `.htaccess` for a basic implementation.

## Settings
Setting | Value type(Default) | Description
--- | --- | ---
db_host | string(localhost) | Database host
db_name | string(tracker) | Name of database
db_user | string(tracker) | Database username
db_password | string(tracker) | Database user password
db_prefix | string(tracker_) | Prefix for tracker tables
numwant_max | int(100) | Maximum number of peers in an announce response
numwant_max_force | boolean(false) | Force client to ask for lower amount
announce_interval | int(5400) | Requested time between announcements in minutes
announce_interval_min | int(5400) | Requested minimum time between announcements in minutes
private | boolean(false) | Force passkey to be set
auto_track | boolean(true) | Start tracking any infohash in announcements
expire_interval | string(2 HOUR) | How long until a peer is dead
save_stats | boolean(false) | Don't remove peers when finished
full_scrape | boolean(false) | Allow full scrapes of tracker
ratio_limit | boolean(false) | Force users to have a certain ratio
ratio_min_limit | float(0.5) | Minimum ratio limit before not allowed to leech
ratio_grace_time | float(24) | Ignore ratio for first amount of hours
log_file | string(log.txt) | Where to store the log file
log_level | int(0 \| 1) | Print debug messages in log file
time_format | string(Y-m-d H:i:s) | Time format in log file
