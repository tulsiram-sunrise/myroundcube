ALTER TABLE events ADD tzname VARCHAR( 255 ) NULL DEFAULT  'UTC' AFTER expires ;
ALTER TABLE events_cache ADD tzname VARCHAR( 255 ) NULL DEFAULT  'UTC' AFTER expires ;
ALTER TABLE events_caldav ADD tzname VARCHAR( 255 ) NULL DEFAULT 'UTC' AFTER expires ;