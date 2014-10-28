/**
 * CalDAV Client
 *
 * @version @package_version@
 * @author Daniel Morlock <daniel.morlock@awesome-it.de>
 *
 * Copyright (C) 2013, Awesome IT GbR <info@awesome-it.de>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

CREATE TABLE IF NOT EXISTS `calendars_caldav_props` (
  `obj_id` int(11) unsigned NOT NULL,
  `obj_type` enum('vcal','') NOT NULL,
  `url` varchar(255) NOT NULL,
  `tag` varchar(255) DEFAULT NULL,
  `user` varchar(255) DEFAULT NULL,
  `pass` varchar(1024) DEFAULT NULL,
  `last_change` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `obj_id` (`obj_id`,`obj_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `events_caldav_props` (
  `obj_id` int(11) unsigned NOT NULL,
  `obj_type` enum('vevent','vtodo','') NOT NULL,
  `url` varchar(255) NOT NULL,
  `tag` varchar(255) DEFAULT NULL,
  `user` varchar(255) DEFAULT NULL,
  `pass` varchar(1024) DEFAULT NULL,
  `last_change` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `obj_id` (`obj_id`,`obj_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `calendars_caldav_props`
  ADD CONSTRAINT `calendars_caldav_props_ibfk_1` FOREIGN KEY (`obj_id`) REFERENCES `calendars` (`calendar_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `events_caldav_props`
  ADD CONSTRAINT `events_caldav_props_ibfk_1` FOREIGN KEY (`obj_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE ON UPDATE CASCADE;
