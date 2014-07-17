IF NOT EXISTS (SELECT * FROM sysobjects WHERE id = object_id(N'[dbo].[events]') AND OBJECTPROPERTY(id, N'IsUserTable') = 1)
CREATE TABLE [events] (
  [event_id] int NOT NULL IDENTITY(1,1),
  [uid] nvarchar(MAX),
  [recurrence_id] int DEFAULT NULL,
  [exdates] nvarchar(MAX),
  [user_id] int NOT NULL DEFAULT 0,
  [start] int NOT NULL DEFAULT 0,
  [end] int NOT NULL DEFAULT 0,
  [expires] int NOT NULL DEFAULT 0,
  [rr] nvarchar(1) DEFAULT NULL,
  [recurring] nvarchar(MAX) NOT NULL,
  [occurrences] int DEFAULT 0,
  [byday] nvarchar(MAX),
  [bymonth] nvarchar(MAX),
  [bymonthday] nvarchar(MAX),
  [summary] nvarchar(255) NOT NULL,
  [description] nvarchar(MAX) NOT NULL,
  [location] nvarchar(255) NOT NULL DEFAULT '',
  [categories] nvarchar(255) NOT NULL DEFAULT '',
  [group] nvarchar(MAX),
  [caldav] nvarchar(MAX),
  [url] nvarchar(MAX),
  [timestamp] smalldatetime NULL DEFAULT CURRENT_TIMESTAMP,
  [del] int NOT NULL DEFAULT 0,
  [reminder] int DEFAULT NULL,
  [reminderservice] nvarchar(MAX),
  [remindermailto] nvarchar(MAX),
  [remindersent] int DEFAULT NULL,
  [notified] int NOT NULL DEFAULT 0,
  [client] nvarchar(MAX),
  PRIMARY KEY ([event_id])
)
GO
CREATE NONCLUSTERED INDEX [user_id_fk_events] ON [events] ([user_id])
GO

IF NOT EXISTS (SELECT * FROM sysobjects WHERE id = object_id(N'[dbo].[events_cache]') AND OBJECTPROPERTY(id, N'IsUserTable') = 1)
CREATE TABLE [events_cache] (
  [event_id] int NOT NULL IDENTITY(1,1),
  [uid] nvarchar(MAX),
  [recurrence_id] int DEFAULT NULL,
  [exdates] nvarchar(MAX),
  [user_id] int NOT NULL DEFAULT 0,
  [start] int NOT NULL DEFAULT 0,
  [end] int NOT NULL DEFAULT 0,
  [expires] int NOT NULL DEFAULT 0,
  [rr] nvarchar(1) DEFAULT NULL,
  [recurring] nvarchar(MAX) NOT NULL,
  [occurrences] int DEFAULT 0,
  [byday] nvarchar(MAX),
  [bymonth] nvarchar(MAX),
  [bymonthday] nvarchar(MAX),
  [summary] nvarchar(255) NOT NULL,
  [description] nvarchar(MAX) NOT NULL,
  [location] nvarchar(255) NOT NULL DEFAULT '',
  [categories] nvarchar(255) NOT NULL DEFAULT '',
  [group] nvarchar(MAX),
  [caldav] nvarchar(MAX),
  [url] nvarchar(MAX),
  [timestamp] smalldatetime NULL DEFAULT CURRENT_TIMESTAMP,
  [del] int NOT NULL DEFAULT 0,
  [reminder] int DEFAULT NULL,
  [reminderservice] nvarchar(MAX),
  [remindermailto] nvarchar(MAX),
  [remindersent] int DEFAULT NULL,
  [notified] int NOT NULL DEFAULT 0,
  [client] nvarchar(MAX),
  PRIMARY KEY ([event_id])
)
GO
CREATE NONCLUSTERED INDEX [user_id_fk_events_cache] ON [events_cache] ([user_id])
GO

IF NOT EXISTS (SELECT * FROM sysobjects WHERE id = object_id(N'[dbo].[events_caldav]') AND OBJECTPROPERTY(id, N'IsUserTable') = 1)
CREATE TABLE [events_caldav] (
  [event_id] int NOT NULL IDENTITY(1,1),
  [uid] nvarchar(MAX),
  [recurrence_id] int DEFAULT NULL,
  [exdates] nvarchar(MAX),
  [user_id] int NOT NULL DEFAULT 0,
  [start] int DEFAULT 0,
  [end] int DEFAULT 0,
  [expires] int DEFAULT 0,
  [rr] nvarchar(1) DEFAULT NULL,
  [recurring] nvarchar(MAX) NOT NULL,
  [occurrences] int DEFAULT 0,
  [byday] nvarchar(MAX),
  [bymonth] nvarchar(MAX),
  [bymonthday] nvarchar(MAX),
  [summary] nvarchar(255) NOT NULL,
  [description] nvarchar(MAX) NOT NULL,
  [location] nvarchar(255) NOT NULL DEFAULT '',
  [categories] nvarchar(255) NOT NULL DEFAULT '',
  [group] nvarchar(MAX),
  [caldav] nvarchar(MAX),
  [url] nvarchar(MAX),
  [timestamp] smalldatetime NULL DEFAULT CURRENT_TIMESTAMP,
  [del] int NOT NULL DEFAULT 0,
  [reminder] int DEFAULT NULL,
  [reminderservice] nvarchar(MAX),
  [remindermailto] nvarchar(MAX),
  [remindersent] int DEFAULT NULL,
  [notified] int NOT NULL DEFAULT 0,
  [client] nvarchar(MAX),
  PRIMARY KEY ([event_id])
)
GO
CREATE NONCLUSTERED INDEX [user_id_fk_events_caldav] ON [events_caldav] ([user_id])
GO

IF NOT EXISTS (SELECT * FROM sysobjects WHERE id = object_id(N'[dbo].[reminders]') AND OBJECTPROPERTY(id, N'IsUserTable') = 1)
CREATE TABLE [reminders] (
  [reminder_id] int NOT NULL IDENTITY(2299,1),
  [user_id] int NOT NULL,
  [events] int DEFAULT NULL,
  [cache] int DEFAULT NULL,
  [caldav] int DEFAULT NULL,
  [type] nvarchar(MAX),
  [props] nvarchar(MAX),
  [runtime] int NOT NULL,
  PRIMARY KEY ([reminder_id])
)
GO
CREATE NONCLUSTERED INDEX [reminders_ibfk_1] ON [reminders] ([user_id])
GO


IF NOT EXISTS (SELECT * FROM sysobjects WHERE id = object_id(N'[dbo].[system]') AND OBJECTPROPERTY(id, N'IsUserTable') = 1)
CREATE TABLE [system] (
 [name] nvarchar(64) NOT NULL,
 [value] nvarchar(MAX),
 PRIMARY KEY([name])
)
GO

ALTER TABLE [events]
  ADD CONSTRAINT [events_ibfk_1] FOREIGN KEY ([user_id]) REFERENCES [users] ([user_id]) ON DELETE CASCADE ON UPDATE CASCADE
GO
  
ALTER TABLE [events_cache]
  ADD CONSTRAINT [events_cache_ibfk_1] FOREIGN KEY ([user_id]) REFERENCES [users] ([user_id]) ON DELETE CASCADE ON UPDATE CASCADE
GO
  
ALTER TABLE [events_caldav]
  ADD CONSTRAINT [events_caldav_ibfk_1] FOREIGN KEY ([user_id]) REFERENCES [users] ([user_id]) ON DELETE CASCADE ON UPDATE CASCADE
GO

ALTER TABLE [reminders]
  ADD CONSTRAINT [reminders_ibfk_1] FOREIGN KEY ([user_id]) REFERENCES [users] ([user_id]) ON DELETE CASCADE ON UPDATE CASCADE
GO

INSERT INTO [system] (name, value) VALUES ('myrc_calendar', 'initial')
GO
