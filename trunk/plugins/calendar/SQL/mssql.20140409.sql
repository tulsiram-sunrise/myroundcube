ALTER TABLE  [events] ADD  [labels] nvarchar( 255 ) NOT NULL DEFAULT ''
GO
ALTER TABLE  [events] ADD  [created] datetime2 DEFAULT NULL
GO
ALTER TABLE  [events] ADD  [modified] datetime2 DEFAULT NULL
GO
ALTER TABLE  [events_cache] ADD  [labels] nvarchar( 255 ) NOT NULL DEFAULT ''
GO
ALTER TABLE  [events_cache] ADD  [created] datetime2 DEFAULT NULL
GO
ALTER TABLE  [events_cache] ADD  [modified] datetime2 DEFAULT NULL
GO
ALTER TABLE  [events_caldav] ADD  [labels] nvarchar( 255 ) NOT NULL DEFAULT ''
GO
ALTER TABLE  [events_caldav] ADD  [created] datetime2 DEFAULT NULL
GO
ALTER TABLE  [events_caldav] ADD  [modified] datetime2 DEFAULT NULL
GO
UPDATE [system] SET [value]='initial|20130512|20130804|20140409' WHERE [name]='myrc_calendar'
GO
