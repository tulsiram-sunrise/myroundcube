ALTER TABLE  [events] ADD  [tzname] nvarchar( 255 ) NULL DEFAULT 'UTC'
GO
ALTER TABLE  [events_cache] ADD  [tzname] nvarchar( 255 ) NULL DEFAULT 'UTC'
GO
ALTER TABLE  [events_caldav] ADD  [tzname] nvarchar( 255 ) NULL DEFAULT 'UTC'
GO
UPDATE [system] SET [value]='initial|20130512' WHERE [name]='myrc_calendar'
GO
