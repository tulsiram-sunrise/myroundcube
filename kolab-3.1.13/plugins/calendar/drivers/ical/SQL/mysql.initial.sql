/**
 * Roundcube Calendar
 *
 * Plugin to add a calendar to Roundcube.
 *
 * @version @package_version@
 * @author Lazlo Westerhof
 * @author Thomas Bruederli
 * @url http://rc-calendar.lazlo.me
 * @licence GNU AGPL
 * @copyright (c) 2010 Lazlo Westerhof - Netherlands
 *
 **/

CREATE TABLE IF NOT EXISTS `calendars_ical_props` (
  `obj_id` int(11) unsigned NOT NULL,
  `obj_type` enum('ical','') NOT NULL,
  `url` varchar(255) NOT NULL,
  `user` varchar(255) DEFAULT NULL,
  `pass` varchar(1024) DEFAULT NULL,
  `last_change` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `obj_id` (`obj_id`,`obj_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `events_ical_props` (
  `obj_id` int(11) unsigned NOT NULL,
  `obj_type` enum('vevent','vtodo','') NOT NULL,
  `url` varchar(255) NOT NULL,
  `user` varchar(255) DEFAULT NULL,
  `pass` varchar(1024) DEFAULT NULL,
  `last_change` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `obj_id` (`obj_id`,`obj_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `calendars_ical_props`
  ADD CONSTRAINT `calendars_ical_props_ibfk_1` FOREIGN KEY (`obj_id`) REFERENCES `calendars` (`calendar_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `events_ical_props`
  ADD CONSTRAINT `events_ical_props_ibfk_1` FOREIGN KEY (`obj_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE ON UPDATE CASCADE;