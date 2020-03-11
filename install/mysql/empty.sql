CREATE TABLE `glpi_plugin_impacts_configs` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`assets` TEXT NULL,
  	`db_version` VARCHAR(10) NOT NULL,
	PRIMARY KEY (`id`)
)
COLLATE='utf8_general_ci'
ENGINE=InnoDB;

INSERT INTO `glpi_plugin_impacts_configs` (`id`, `assets`, `db_version`) VALUES (1, '[]', '1.0.0');


CREATE TABLE `glpi_plugin_impacts_impacts` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`itemtype_1` VARCHAR(255) NOT NULL DEFAULT '0' COLLATE 'utf8_unicode_ci',
	`items_id_1` INT(11) NOT NULL DEFAULT '0',
	`itemtype_2` VARCHAR(255) NOT NULL DEFAULT '0' COLLATE 'utf8_unicode_ci',
	`items_id_2` INT(11) NOT NULL DEFAULT '0',
	`date_creation` DATETIME NULL DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE INDEX `itemtype_1_items_id_1_itemtype_2_items_id_2` (`itemtype_1`, `items_id_1`, `itemtype_2`, `items_id_2`),
	INDEX `date_creation` (`date_creation`),
	INDEX `itemtype_1_items_id_1` (`itemtype_1`, `items_id_1`),
	INDEX `itemtype_2_items_id_2` (`itemtype_2`, `items_id_2`)
)
COLLATE='utf8_unicode_ci'
ENGINE=InnoDB;