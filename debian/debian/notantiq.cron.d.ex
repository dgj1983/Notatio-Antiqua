#
# Regular cron jobs for the notantiq package
#
0 4	* * *	root	[ -x /usr/bin/notantiq_maintenance ] && /usr/bin/notantiq_maintenance
