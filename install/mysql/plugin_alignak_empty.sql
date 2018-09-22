DROP TABLE IF EXISTS `glpi_plugin_alignak_alignaks`;
CREATE TABLE `glpi_plugin_alignak_alignaks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `comment` text,
  `tag` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `username` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_plugin_alignak_computers`;
CREATE TABLE `glpi_plugin_alignak_computers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entities_id` int(11) NOT NULL DEFAULT '0',
  `itemtype` varchar(255) DEFAULT NULL,
  `items_id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `comment` text,
  PRIMARY KEY  (`id`),
  KEY `computer` (`itemtype`, `items_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_plugin_alignak_computercounterstemplates`;
CREATE TABLE `glpi_plugin_alignak_computercounterstemplates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `itemtype` varchar(100) DEFAULT NULL,
  `items_id` int(11) NOT NULL DEFAULT '0',
  `plugin_alignak_counters_template_id` int(11) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_plugin_alignak_counters`;
CREATE TABLE `glpi_plugin_alignak_counters` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `comment` text,
  `type_counter` enum('INTEGER', 'FLOAT', 'POURCENTAGE', 'OCTETS') NOT NULL,
  `cumulatif` tinyint(1) NOT NULL DEFAULT '0',
  `plugin_alignak_counters_template_id` int(11) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_plugin_alignak_counterstemplates`;
CREATE TABLE `glpi_plugin_alignak_counterstemplates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `entities_id` int(11) DEFAULT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_plugin_alignak_dashboards`;
CREATE TABLE `glpi_plugin_alignak_dashboards` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `is_active` tinyint(1) DEFAULT '0',
  `name` varchar(255) DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `clients_id` int(11) DEFAULT '-1',
  `plugin_alignak_mail_notifications_id` int(11) DEFAULT '-1',
  `page_counters` tinyint(1) DEFAULT '1',
  `page_counters_refresh` int(4) DEFAULT '0',
  `page_monitoring` tinyint(1) DEFAULT '1',
  `page_monitoring_refresh` int(4) DEFAULT '0',
  `page_map` tinyint(1) DEFAULT '1',
  `page_map_refresh` int(4) DEFAULT '0',
  `page_tree` tinyint(1) DEFAULT '1',
  `page_tree_refresh` int(4) DEFAULT '0',
  `page_tickets` tinyint(1) DEFAULT '1',
  `page_tickets_refresh` int(4) DEFAULT '0',
  `page_groups` tinyint(1) DEFAULT '1',
  `page_groups_refresh` int(4) DEFAULT '0',
  `page_kiosks` tinyint(1) DEFAULT '1',
  `page_kiosks_refresh` int(4) DEFAULT '0',
  `page_services` tinyint(1) DEFAULT '1',
  `page_services_refresh` int(4) DEFAULT '0',
  `page_daily_counters` tinyint(1) DEFAULT '1',
  `page_daily_counters_refresh` int(4) DEFAULT '0',
  `page_availability` tinyint(1) DEFAULT '1',
  `page_availability_refresh` int(4) DEFAULT '0',
  `page_payments` tinyint(1) DEFAULT '1',
  `page_payments_refresh` int(4) DEFAULT '0',
  `page_printing` tinyint(1) DEFAULT '1',
  `page_printing_refresh` int(4) DEFAULT '0',
  `page_rfid` tinyint(1) DEFAULT '1',
  `page_rfid_refresh` int(4) DEFAULT '0',
  `page_counters_main` tinyint(1) DEFAULT '1',
  `page_counters_main_refresh` int(4) DEFAULT '10',
  `page_counters_main_collapsed` tinyint(1) DEFAULT '0',
  `page_counters_barcharts` tinyint(1) DEFAULT '1',
  `page_counters_barcharts_refresh` int(4) DEFAULT '10',
  `page_counters_barcharts_collapsed` tinyint(1) DEFAULT '0',
  `page_counters_helpdesk` tinyint(1) DEFAULT '1',
  `page_counters_helpdesk_refresh` int(4) DEFAULT '10',
  `page_counters_helpdesk_collapsed` tinyint(1) DEFAULT '0',
  `page_counters_geotraffic` tinyint(1) DEFAULT '1',
  `page_counters_geotraffic_refresh` int(4) DEFAULT '10',
  `page_counters_geotraffic_collapsed` tinyint(1) DEFAULT '0',
  `component_1` int(11) DEFAULT '-1',
  `component_2` int(11) DEFAULT '-1',
  `component_3` int(11) DEFAULT '-1',
  `component_4` int(11) DEFAULT '-1',
  `component_5` int(11) DEFAULT '-1',
  `page_monitoring_minemap` tinyint(1) DEFAULT '1',
  `page_monitoring_minemap_refresh` int(4) DEFAULT '10',
  `page_monitoring_minemap_collapsed` tinyint(1) DEFAULT '0',
  `page_monitoring_kiosks` tinyint(1) DEFAULT '1',
  `page_monitoring_kiosks_refresh` int(4) DEFAULT '10',
  `page_monitoring_kiosks_collapsed` tinyint(1) DEFAULT '0',
  `page_monitoring_services` tinyint(1) DEFAULT '1',
  `page_monitoring_services_refresh` int(4) DEFAULT '10',
  `page_monitoring_services_collapsed` tinyint(1) DEFAULT '0',
  `navbar_config` tinyint(1) NOT NULL DEFAULT '0',
  `navbar_notif` tinyint(1) NOT NULL DEFAULT '0',
  `navbar_select` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `name` (`clients_id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_plugin_alignak_entities`;
CREATE TABLE `glpi_plugin_alignak_entities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entities_id` int(11) NOT NULL DEFAULT '0',
  `name` varchar(255) DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `plugin_alignak_entitites_id` int(11) NOT NULL DEFAULT '0',
  `plugin_alignak_alignak_id` int(11) NOT NULL DEFAULT '0',
  `plugin_alignak_monitoring_template_id` int(11) NOT NULL DEFAULT '0',
  `plugin_alignak_counters_template_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_plugin_alignak_mailnotifications`;
CREATE TABLE `glpi_plugin_alignak_mailnotifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entities_id` int(11) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `name` varchar(64) NOT NULL DEFAULT '',
  `user_to_id` int(11) NOT NULL DEFAULT '-1',
  `user_cc_1_id` int(11) NOT NULL DEFAULT '-1',
  `user_cc_2_id` int(11) NOT NULL DEFAULT '-1',
  `user_cc_3_id` int(11) NOT NULL DEFAULT '-1',
  `user_bcc_id` int(11) NOT NULL DEFAULT '-1',
  `daily_mail` tinyint(1) NOT NULL DEFAULT '0',
  `daily_subject_template` varchar(255) NOT NULL DEFAULT 'Daily counters (#date#)',
  `weekly_mail` tinyint(1) NOT NULL DEFAULT '0',
  `weekly_subject_template` varchar(255) NOT NULL DEFAULT 'Weekly counters (#date#)',
  `weekly_mail_day` int(11) NOT NULL DEFAULT '1',
  `monthly_mail` tinyint(1) NOT NULL DEFAULT '0',
  `monthly_subject_template` varchar(255) NOT NULL DEFAULT 'Monthly counters (#date#)',
  `monthly_mail_day` int(11) NOT NULL DEFAULT '1',
  `component_1` int(11) NOT NULL DEFAULT '-1',
  `component_2` int(11) NOT NULL DEFAULT '-1',
  `component_3` int(11) NOT NULL DEFAULT '-1',
  `component_4` int(11) NOT NULL DEFAULT '-1',
  `component_5` int(11) NOT NULL DEFAULT '-1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_plugin_alignak_monitoringtemplates`;
CREATE TABLE `glpi_plugin_alignak_monitoringtemplates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entities_id` int(11) NOT NULL DEFAULT '0',
  `name` varchar(255) DEFAULT NULL,
  `comment` text DEFAULT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
