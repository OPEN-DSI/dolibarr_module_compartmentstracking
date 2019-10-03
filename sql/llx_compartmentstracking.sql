-- ========================================================================
-- Copyright (C) 2017 		Open-DSI      <support@open-dsi.fr>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program. If not, see <http://www.gnu.org/licenses/>.
--
-- ========================================================================

CREATE TABLE `llx_entrepotcompartments` (
	`rowid`            integer       AUTO_INCREMENT PRIMARY KEY,
	`fk_entrepot`      integer       NOT NULL UNIQUE,
	`ref`              varchar(24)   DEFAULT '' NOT NULL,
	`column`           integer       NOT NULL,
	`shelf`            integer       NOT NULL,
	`drawer`           integer       NOT NULL,
	`column_is_alpha`  tinyint       DEFAULT 0 NOT NULL,
	`shelf_is_alpha`   tinyint       DEFAULT 0 NOT NULL,
	`drawer_is_alpha`  tinyint       DEFAULT 0 NOT NULL,
	`separator`        varchar(24)   DEFAULT '' NOT NULL,
	`datec`            datetime      DEFAULT CURRENT_TIMESTAMP,
	`entity`           int(11)       DEFAULT 1,
	INDEX (`fk_entrepot`),
	FOREIGN KEY (`fk_entrepot`) REFERENCES `llx_entrepot` (`rowid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;


CREATE TABLE `llx_compartment` (
	`rowid`            integer       AUTO_INCREMENT PRIMARY KEY,
	`fk_entrepot`      integer       NOT NULL,
	`ref`              varchar(48)   DEFAULT '' NOT NULL,
	`column`           integer       NOT NULL,
	`shelf`            integer       NOT NULL,
	`drawer`           integer       NOT NULL,
	`status`           tinyint       DEFAULT 0 NOT NULL,
	`datec`            datetime      DEFAULT CURRENT_TIMESTAMP,
	`entity`           int(11)       DEFAULT 1,
	INDEX (`fk_entrepot`),
	FOREIGN KEY (`fk_entrepot`) REFERENCES `llx_entrepot` (`rowid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;


CREATE TABLE `llx_compartmentproduct` (
	`rowid`            integer       AUTO_INCREMENT PRIMARY KEY,
	`fk_product`       integer       NOT NULL,
	`fk_compartment`   integer       NOT NULL,
	`qty`              integer       DEFAULT 0 NOT NULL,
	`preferred`        tinyint       DEFAULT 0 NOT NULL,
	UNIQUE (`fk_product`, `fk_compartment`),
	FOREIGN KEY (`fk_product`) REFERENCES `llx_product` (`rowid`) ON DELETE CASCADE ON UPDATE CASCADE,
	FOREIGN KEY (`fk_compartment`) REFERENCES `llx_compartment` (`rowid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

