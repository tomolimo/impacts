CREATE TABLE `glpi_plugin_impacts_configs` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
  	`db_version` VARCHAR(10) NOT NULL,
	PRIMARY KEY (`id`)
)
COLLATE='utf8_general_ci'
ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `glpi_plugin_impacts_itemtypes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `url_path_pics` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) 
COLLATE='utf8_general_ci'
ENGINE=InnoDB;

INSERT INTO `glpi_plugin_impacts_configs` (`id`, `db_version`) VALUES (1, '2.0.0');
