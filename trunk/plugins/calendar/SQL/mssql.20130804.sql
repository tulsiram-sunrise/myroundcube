ALTER TABLE  [events] ADD  [component] nvarchar( 6 ) NOT NULL DEFAULT 'vevent'
GO
ALTER TABLE  [events] ADD  [organizer] nvarchar( 255 ) DEFAULT NULL
GO
ALTER TABLE  [events] ADD  [attendees] nvarchar( 255 ) DEFAULT NULL
GO
ALTER TABLE  [events] ADD  [status] nvarchar( 25 ) DEFAULT NULL
GO
ALTER TABLE  [events] ADD  [due] int NOT NULL DEFAULT 0
GO
ALTER TABLE  [events] ADD  [complete] int NOT NULL DEFAULT 0
GO
ALTER TABLE  [events] ADD  [priority] int NOT NULL DEFAULT 0
GO
ALTER TABLE  [events] DROP COLUMN [group]
GO
ALTER TABLE  [events_cache] ADD  [component] nvarchar( 6 ) NOT NULL DEFAULT 'vevent'
GO
ALTER TABLE  [events_cache] ADD  [organizer] nvarchar( 255 ) DEFAULT NULL
GO
ALTER TABLE  [events_cache] ADD  [attendees] nvarchar( 255 ) DEFAULT NULL
GO
ALTER TABLE  [events_cache] ADD  [status] nvarchar( 25 ) DEFAULT NULL
GO
ALTER TABLE  [events_cache] ADD  [due] int NOT NULL DEFAULT 0
GO
ALTER TABLE  [events_cache] ADD  [complete] int NOT NULL DEFAULT 0
GO
ALTER TABLE  [events_cache] ADD  [priority] int NOT NULL DEFAULT 0
GO
ALTER TABLE  [events_cache] DROP COLUMN [group]
GO
ALTER TABLE  [events_caldav] ADD  [component] nvarchar( 6 ) NOT NULL DEFAULT 'vevent'
GO
ALTER TABLE  [events_caldav] ADD  [organizer] nvarchar( 255 ) DEFAULT NULL
GO
ALTER TABLE  [events_caldav] ADD  [attendees] nvarchar( 255 ) DEFAULT NULL
GO
ALTER TABLE  [events_caldav] ADD  [status] nvarchar( 25 ) DEFAULT NULL
GO
ALTER TABLE  [events_caldav] ADD  [due] int NOT NULL DEFAULT 0
GO
ALTER TABLE  [events_caldav] ADD  [complete] int NOT NULL DEFAULT 0
GO
ALTER TABLE  [events_caldav] ADD  [priority] int NOT NULL DEFAULT 0
GO
ALTER TABLE  [events_caldav] DROP COLUMN [group]
GO
UPDATE [system] SET [value]='initial|20130512|20130804' WHERE [name]='myrc_calendar'
GO
