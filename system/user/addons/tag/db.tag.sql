CREATE TABLE IF NOT EXISTS `exp_tag_tags` (
	`tag_id`				int(10) unsigned		NOT NULL AUTO_INCREMENT,
	`tag_alpha`				char(3)					NOT NULL DEFAULT '',
	`tag_name`				varchar(200)			NOT NULL DEFAULT '',
	`site_id`				int(5) unsigned			NOT NULL DEFAULT 1,
	`author_id`				int(10) unsigned		NOT NULL DEFAULT 0,
	`entry_date`			int(10)					NOT NULL DEFAULT 0,
	`edit_date`				int(10)					NOT NULL DEFAULT 0,
	`clicks`				int(10) unsigned		NOT NULL DEFAULT 0,
	`total_entries`			int(10) unsigned		NOT NULL DEFAULT 0,
	`total_entries_1`		int(10) unsigned		NOT NULL DEFAULT 0,
	`channel_entries`		int(10) unsigned		NOT NULL DEFAULT 0,
	PRIMARY KEY				(`tag_id`),
	KEY `tag_name`			(`tag_name`),
	KEY `tag_alpha`			(`tag_alpha`),
	KEY `author_id`			(`author_id`),
	KEY `site_id`			(`site_id`)
) CHARACTER SET utf8 COLLATE utf8_general_ci ;;

CREATE TABLE IF NOT EXISTS `exp_tag_bad_tags` (
	`tag_id`				int(10) unsigned		NOT NULL AUTO_INCREMENT,
	`tag_name`				varchar(150)			NOT NULL DEFAULT '',
	`site_id`				int(5) unsigned			NOT NULL DEFAULT '1',
	`author_id`				int(10) unsigned		NOT NULL DEFAULT 0,
	`edit_date`				int(10)					NOT NULL DEFAULT 0,
	PRIMARY KEY				(`tag_id`),
	KEY `tag_name`			(`tag_name`),
	KEY `site_id`			(`site_id`),
	KEY `author_id`			(`author_id`)
) CHARACTER SET utf8 COLLATE utf8_general_ci ;;

CREATE TABLE IF NOT EXISTS `exp_tag_entries` (
	`id`					int(10) unsigned		NOT NULL AUTO_INCREMENT,
	`entry_id`				int(10) unsigned		NOT NULL DEFAULT 0,
	`tag_id`				int(10) unsigned		NOT NULL DEFAULT 0,
	`channel_id`			int(5) unsigned			NOT NULL DEFAULT 0,
	`site_id`				int(5) unsigned			NOT NULL DEFAULT 1,
	`author_id`				int(10) unsigned		NOT NULL DEFAULT 0,
	`ip_address`			varchar(40)				NOT NULL DEFAULT '0',
	`type`					varchar(16)				NOT NULL DEFAULT 'channel',
	`tag_group_id`			int(10) unsigned		NOT NULL DEFAULT 1,
	`remote`				char(1)					NOT NULL DEFAULT 'n',
	PRIMARY KEY				(`id`),
	KEY `entry_id`			(`entry_id`),
	KEY `tag_id`			(`tag_id`),
	KEY `channel_id`		(`channel_id`),
	KEY `site_id`			(`site_id`),
	KEY `author_id`			(`author_id`)
) CHARACTER SET utf8 COLLATE utf8_general_ci ;;

CREATE TABLE IF NOT EXISTS `exp_tag_preferences` (
	`tag_preference_id`		int(10) unsigned		NOT NULL AUTO_INCREMENT,
	`tag_preference_name`	varchar(100)			NOT NULL DEFAULT '',
	`tag_preference_value`	varchar(100)			NOT NULL DEFAULT '',
	`site_id`				int(5) unsigned			NOT NULL DEFAULT 1,
	PRIMARY KEY				(`tag_preference_id`),
	KEY `site_id`			(`site_id`)
) CHARACTER SET utf8 COLLATE utf8_general_ci ;;

CREATE TABLE IF NOT EXISTS `exp_tag_groups` (
	`tag_group_id`			int(10) unsigned		NOT NULL AUTO_INCREMENT,
	`tag_group_name`		varchar(150)			NOT NULL DEFAULT '',
	`tag_group_short_name`	varchar(150)			NOT NULL DEFAULT '',
	PRIMARY KEY				(`tag_group_id`)
) CHARACTER SET utf8 COLLATE utf8_general_ci ;;