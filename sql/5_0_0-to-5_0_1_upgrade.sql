--
--  Comment Meta Language Constructs:
--
--  #IfNotTable
--    argument: table_name
--    behavior: if the table_name does not exist,  the block will be executed

--  #IfTable
--    argument: table_name
--    behavior: if the table_name does exist, the block will be executed

--  #IfColumn
--    arguments: table_name colname
--    behavior:  if the table and column exist,  the block will be executed

--  #IfMissingColumn
--    arguments: table_name colname
--    behavior:  if the table exists but the column does not,  the block will be executed

--  #IfNotColumnType
--    arguments: table_name colname value
--    behavior:  If the table table_name does not have a column colname with a data type equal to value, then the block will be executed

--  #IfNotRow
--    arguments: table_name colname value
--    behavior:  If the table table_name does not have a row where colname = value, the block will be executed.

--  #IfNotRow2D
--    arguments: table_name colname value colname2 value2
--    behavior:  If the table table_name does not have a row where colname = value AND colname2 = value2, the block will be executed.

--  #IfNotRow3D
--    arguments: table_name colname value colname2 value2 colname3 value3
--    behavior:  If the table table_name does not have a row where colname = value AND colname2 = value2 AND colname3 = value3, the block will be executed.

--  #IfNotRow4D
--    arguments: table_name colname value colname2 value2 colname3 value3 colname4 value4
--    behavior:  If the table table_name does not have a row where colname = value AND colname2 = value2 AND colname3 = value3 AND colname4 = value4, the block will be executed.

--  #IfNotRow2Dx2
--    desc:      This is a very specialized function to allow adding items to the list_options table to avoid both redundant option_id and title in each element.
--    arguments: table_name colname value colname2 value2 colname3 value3
--    behavior:  The block will be executed if both statements below are true:
--               1) The table table_name does not have a row where colname = value AND colname2 = value2.
--               2) The table table_name does not have a row where colname = value AND colname3 = value3.

--  #IfRow2D
--    arguments: table_name colname value colname2 value2
--    behavior:  If the table table_name does have a row where colname = value AND colname2 = value2, the block will be executed.

--  #IfRow3D
--        arguments: table_name colname value colname2 value2 colname3 value3
--        behavior:  If the table table_name does have a row where colname = value AND colname2 = value2 AND colname3 = value3, the block will be executed.

--  #IfIndex
--    desc:      This function is most often used for dropping of indexes/keys.
--    arguments: table_name colname
--    behavior:  If the table and index exist the relevant statements are executed, otherwise not.

--  #IfNotIndex
--    desc:      This function will allow adding of indexes/keys.
--    arguments: table_name colname
--    behavior:  If the index does not exist, it will be created

--  #EndIf
--    all blocks are terminated with a #EndIf statement.

--  #IfNotListReaction
--    Custom function for creating Reaction List

--  #IfNotListOccupation
--    Custom function for creating Occupation List

--  #IfTextNullFixNeeded
--    desc: convert all text fields without default null to have default null.
--    arguments: none

--  #IfTableEngine
--    desc:      Execute SQL if the table has been created with given engine specified.
--    arguments: table_name engine
--    behavior:  Use when engine conversion requires more than one ALTER TABLE

--  #IfInnoDBMigrationNeeded
--    desc: find all MyISAM tables and convert them to InnoDB.
--    arguments: none
--    behavior: can take a long time.

#IfMissingColumn list_options edit_options
  ALTER TABLE `list_options` ADD `edit_options` TINYINT(1) NOT NULL DEFAULT '1';
#Endif

#IfMissingColumn list_options timestamp
ALTER TABLE `list_options` ADD `timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ;
#Endif

#IfNotTable multiple_db
  CREATE TABLE `multiple_db` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `namespace` varchar(255) NOT NULL,
    `username` varchar(255) NOT NULL,
    `password` text,
    `dbname` varchar(255) NOT NULL,
    `host` varchar(255) NOT NULL DEFAULT 'localhost',
    `port` smallint(4) NOT NULL DEFAULT '3306',
    `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
     UNIQUE KEY `namespace` (namespace),
     PRIMARY KEY (id)
  ) ENGINE=InnoDB;
#EndIf

#IfNotRow2D list_options list_id page_validation option_id therapy_groups_add#addGroup
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `notes`, `activity`) VALUES ('page_validation', 'therapy_groups_add#addGroup', '/interface/therapy_groups/index.php?method=addGroup', 120, '{group_name:{presence: true}}', 1);
#EndIf

#IfNotRow2D list_options list_id page_validation option_id therapy_groups_edit#editGroup
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `notes`, `activity`) VALUES ('page_validation', 'therapy_groups_edit#editGroup', '/interface/therapy_groups/index.php?method=groupDetails', 125, '{group_name:{presence: true}}', 1);
#EndIf

#IfNotRow2D list_options list_id page_validation option_id tg_add#add-participant-form
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `notes`, `activity`) VALUES ('page_validation', 'tg_add#add-participant-form', '/interface/therapy_groups/index.php?method=groupParticipants', 130, '{participant_name:{presence: true}, group_patient_start:{presence: true}}', 1);
#EndIf

#IfNotRow2D list_options list_id page_validation option_id add_edit_event#theform_groups
INSERT INTO `list_options` (`list_id`,`option_id`,`title`,`seq`,`is_default`,`option_value`,`mapping`,`notes`,`codes`,`toggle_setting_1`,`toggle_setting_2`,`activity`,`subtype`)
VALUES ('page_validation','add_edit_event#theform_groups','/interface/main/calendar/add_edit_event.php?group=true',150,0,0,'','{form_group:{presence: true}}','',0,0,1,'');
#EndIf

#IfNotRow2D list_options list_id page_validation option_id common#new-encounter-form
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `notes`, `activity`) VALUES ('page_validation', 'common#new-encounter-form', '/interface/forms/newGroupEncounter/common.php', 160, '{pc_catid:{exclusion: ["_blank"]}}', 1);
#EndIf

#IfNotTable therapy_groups
CREATE TABLE `therapy_groups` (
  `group_id` int(11) NOT NULL auto_increment,
  `group_name` varchar(255) NOT NULL ,
  `group_start_date` date NOT NULL ,
  `group_end_date` date,
  `group_type` tinyint NOT NULL,
  `group_participation` tinyint NOT NULL,
  `group_status` int(11) NOT NULL,
  `group_notes` text,
  `group_guest_counselors` varchar(255),
  PRIMARY KEY  (`group_id`)
) ENGINE=InnoDB;
#EndIf

#IfNotTable therapy_groups_participants
CREATE TABLE `therapy_groups_participants` (
  `group_id` int(11) NOT NULL,
  `pid` int(11) NOT NULL ,
  `group_patient_status` int(11) NOT NULL,
  `group_patient_start` date NOT NULL ,
  `group_patient_end` date,
  `group_patient_comment` text,
  PRIMARY KEY (`group_id`,`pid`)
) ENGINE=InnoDB;
#EndIf

#IfNotTable therapy_groups_participant_attendance
CREATE TABLE `therapy_groups_participant_attendance` (
  `form_id` int(11) NOT NULL,
  `pid` int(11) NOT NULL ,
  `meeting_patient_comment` text ,
  `meeting_patient_status` varchar(15),
  PRIMARY KEY (`form_id`,`pid`)
) ENGINE=InnoDB;
#EndIf

#IfNotTable therapy_groups_counselors
CREATE TABLE `therapy_groups_counselors`(
	`group_id` int(11) NOT NULL,
	`user_id` int(11) NOT NULL,
	PRIMARY KEY (`group_id`,`user_id`)
) ENGINE=InnoDB;
#EndIf

#IfMissingColumn openemr_postcalendar_events pc_gid
ALTER TABLE openemr_postcalendar_events ADD pc_gid int(11) DEFAULT 0;
#EndIf

#IfNotRow2D list_options list_id lists option_id groupstat
INSERT INTO list_options (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`) VALUES ('lists', 'groupstat', 'Group Statuses', '13', '0', '0');
INSERT INTO list_options (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `notes`) VALUES ('groupstat', '-', '- None', '10', '0', '0', 'FEFDCF|0');
INSERT INTO list_options (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `notes`) VALUES ('groupstat', '=', '= Took Place', '20', '0', '0', 'FF2414|0');
INSERT INTO list_options (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `notes`) VALUES ('groupstat', '>', '> Did Not Take Place', '30', '0', '0', 'BFBFBF|0');
INSERT INTO list_options (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `notes`) VALUES ('groupstat', '<', '< Not Reported', '40', '0', '0', 'FEFDCF|0');
#EndIf

#IfNotRow openemr_postcalendar_categories pc_catname Group Therapy
INSERT INTO openemr_postcalendar_categories (`pc_catname`, `pc_catcolor`, `pc_recurrspec`, `pc_duration` ,`pc_cattype` , `pc_active` , `pc_seq`)
VALUES ('Group Therapy' , '#BFBFBF' , 'a:5:{s:17:"event_repeat_freq";s:1:"0";s:22:"event_repeat_freq_type";s:1:"0";s:19:"event_repeat_on_num";s:1:"1";s:19:"event_repeat_on_day";s:1:"0";s:20:"event_repeat_on_freq";s:1:"0";}', '3600', '3', '1', '90');
#EndIf


#IfNotTable form_groups_encounter
CREATE TABLE `form_groups_encounter` (
  `id` bigint(20) NOT NULL auto_increment,
  `date` datetime default NULL,
  `reason` longtext,
  `facility` longtext,
  `facility_id` int(11) NOT NULL default '0',
  `group_id` bigint(20) default NULL,
  `encounter` bigint(20) default NULL,
  `onset_date` datetime default NULL,
  `sensitivity` varchar(30) default NULL,
  `billing_note` text,
  `pc_catid` int(11) NOT NULL default '5' COMMENT 'event category from openemr_postcalendar_categories',
  `last_level_billed` int  NOT NULL DEFAULT 0 COMMENT '0=none, 1=ins1, 2=ins2, etc',
  `last_level_closed` int  NOT NULL DEFAULT 0 COMMENT '0=none, 1=ins1, 2=ins2, etc',
  `last_stmt_date`    date DEFAULT NULL,
  `stmt_count`        int  NOT NULL DEFAULT 0,
  `provider_id` INT(11) DEFAULT '0' COMMENT 'default and main provider for this visit',
  `supervisor_id` INT(11) DEFAULT '0' COMMENT 'supervising provider, if any, for this visit',
  `invoice_refno` varchar(31) NOT NULL DEFAULT '',
  `referral_source` varchar(31) NOT NULL DEFAULT '',
  `billing_facility` INT(11) NOT NULL DEFAULT 0,
  `external_id` VARCHAR(20) DEFAULT NULL,
  `pos_code` tinyint(4) default NULL,
  `counselors` VARCHAR (255),
  `appt_id` INT(11) default NULL,
  PRIMARY KEY  (`id`),
  KEY `pid_encounter` (`group_id`, `encounter`),
  KEY `encounter_date` (`date`)
) ENGINE=InnoDB AUTO_INCREMENT=1 ;
#EndIf

#IfMissingColumn forms therapy_group_id
ALTER TABLE  `forms` ADD  `therapy_group_id` INT(11) DEFAULT NULL;
#EndIf

#IfMissingColumn registry patient_encounter
ALTER TABLE `registry` ADD `patient_encounter` TINYINT NOT NULL DEFAULT '1';
#EndIf

#IfMissingColumn registry therapy_group_encounter
ALTER TABLE `registry` ADD `therapy_group_encounter` TINYINT NOT NULL DEFAULT '0';
#EndIf


#IfNotRow2D list_options list_id lists option_id attendstat
INSERT INTO list_options (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`) VALUES ('lists', 'attendstat', 'Group Attendance Statuses', '15', '0', '0');
INSERT INTO list_options (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `notes`, `toggle_setting_1`) VALUES ('attendstat', '-', '- Not Reported', '10', '0', '0', 'FEFDCF|0', '0');
INSERT INTO list_options (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `notes`, `toggle_setting_1`) VALUES ('attendstat', '@', '@ Attended', '20', '0', '0', 'FF2414|0', '1');
INSERT INTO list_options (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `notes`, `toggle_setting_1`) VALUES ('attendstat', '?', '? Did Not Attend', '30', '0', '0', 'BFBFBF|0', '1');
INSERT INTO list_options (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `notes`, `toggle_setting_1`) VALUES ('attendstat', '~', '~ Late Arrival', '40', '0', '0', 'BFBFBF|0', '1');
INSERT INTO list_options (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `notes`, `toggle_setting_1`) VALUES ('attendstat', 'x', 'x Cancelled', '50', '0', '0', 'FEFDCF|0', '0');
#EndIf

#IfNotRow registry directory group_attendance
INSERT INTO registry (name, state, directory, sql_run, unpackaged, date, priority, category, nickname, patient_encounter, therapy_group_encounter) VALUES ('Group Attendance Form', 1, 'group_attendance', 1, 1, '2015-10-15 00:00:00', 0, 'Clinical', '',0,1);
#EndIf

#IfNotRow registry directory newGroupEncounter
INSERT INTO registry (name, state, directory, sql_run, unpackaged, date, priority, category, nickname, patient_encounter, therapy_group_encounter) VALUES ('New Group Encounter Form', 1, 'newGroupEncounter', 1, 1, '2015-10-15 00:00:00', 0, 'Clinical', '',0,1);
#EndIf

#IfNotTable form_therapy_groups_attendance
CREATE TABLE `form_therapy_groups_attendance` (
  id	bigint(20) auto_increment,
  date	date,
  group_id	int(11),
  user	varchar(255),
  groupname	varchar(255),
  authorized	tinyint(4),
  encounter_id	int(11),
  activity	tinyint(4),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ;
#EndIf

#IfNotRow2D list_options list_id lists option_id files_white_list
INSERT INTO list_options (`list_id`, `option_id`, `title`) VALUES ('lists', 'files_white_list', 'Files type white list');
#EndIf

#IfNotTable onsite_documents
CREATE TABLE `onsite_documents` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `pid` int(10) UNSIGNED DEFAULT NULL,
  `facility` int(10) UNSIGNED DEFAULT NULL,
  `provider` int(10) UNSIGNED DEFAULT NULL,
  `encounter` int(10) UNSIGNED DEFAULT NULL,
  `create_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `doc_type` varchar(255) NOT NULL,
  `patient_signed_status` smallint(5) UNSIGNED NOT NULL,
  `patient_signed_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `authorize_signed_time` datetime DEFAULT NULL,
  `accept_signed_status` smallint(5) NOT NULL,
  `authorizing_signator` varchar(50) NOT NULL,
  `review_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `denial_reason` varchar(255) NOT NULL,
  `authorized_signature` text,
  `patient_signature` text,
  `full_document` blob,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 ;
#EndIf

#IfNotTable onsite_mail
CREATE TABLE `onsite_mail` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `date` datetime DEFAULT NULL,
  `owner` bigint(20) DEFAULT NULL,
  `user` varchar(255) DEFAULT NULL,
  `groupname` varchar(255) DEFAULT NULL,
  `activity` tinyint(4) DEFAULT NULL,
  `authorized` tinyint(4) DEFAULT NULL,
  `header` varchar(255) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `body` longtext,
  `recipient_id` varchar(128) DEFAULT NULL,
  `recipient_name` varchar(255) DEFAULT NULL,
  `sender_id` varchar(128) DEFAULT NULL,
  `sender_name` varchar(255) DEFAULT NULL,
  `assigned_to` varchar(255) DEFAULT NULL,
  `deleted` tinyint(4) DEFAULT '0' COMMENT 'flag indicates note is deleted',
  `delete_date` datetime DEFAULT NULL,
  `mtype` varchar(128) DEFAULT NULL,
  `message_status` varchar(20) NOT NULL DEFAULT 'New',
  `mail_chain` int(11) DEFAULT NULL,
  `reply_mail_chain` int(11) DEFAULT NULL,
  `is_msg_encrypted` tinyint(2) DEFAULT '0' COMMENT 'Whether messsage encrypted 0-Not encrypted, 1-Encrypted',
  PRIMARY KEY (`id`),
  KEY `pid` (`owner`)
) ENGINE=InnoDB AUTO_INCREMENT=1 ;
#EndIf

#IfNotTable onsite_messages
CREATE TABLE `onsite_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(64) NOT NULL,
  `message` longtext,
  `ip` varchar(15) NOT NULL,
  `date` datetime NOT NULL,
  `sender_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'who sent id',
 `recip_id` varchar(255) NOT NULL COMMENT 'who to id array',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB COMMENT='Portal messages' AUTO_INCREMENT=1 ;
#EndIf

#IfNotTable onsite_online
CREATE TABLE `onsite_online` (
  `hash` varchar(32) NOT NULL,
  `ip` varchar(15) NOT NULL,
  `last_update` datetime NOT NULL,
  `username` varchar(64) NOT NULL,
  `userid` int(11) UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`hash`)
) ENGINE=InnoDB;
#EndIf

#IfNotTable onsite_portal_activity
CREATE TABLE `onsite_portal_activity` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `date` datetime DEFAULT NULL,
  `patient_id` bigint(20) DEFAULT NULL,
  `activity` varchar(255) DEFAULT NULL,
  `require_audit` tinyint(1) DEFAULT '1',
  `pending_action` varchar(255) DEFAULT NULL,
  `action_taken` varchar(255) DEFAULT NULL,
  `status` varchar(255) DEFAULT NULL,
  `narrative` longtext,
  `table_action` longtext,
  `table_args` longtext,
  `action_user` int(11) DEFAULT NULL,
  `action_taken_time` datetime DEFAULT NULL,
  `checksum` longtext,
  PRIMARY KEY (`id`),
  KEY `date` (`date`)
) ENGINE=InnoDB AUTO_INCREMENT=1 ;
#EndIf

#IfNotTable onsite_signatures
CREATE TABLE `onsite_signatures` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `status` varchar(128) NOT NULL DEFAULT 'waiting',
  `type` varchar(128) NOT NULL,
  `created` int(11) NOT NULL,
  `lastmod` datetime NOT NULL,
  `pid` bigint(20) DEFAULT NULL,
  `encounter` int(11) DEFAULT NULL,
  `user` varchar(255) DEFAULT NULL,
  `activity` tinyint(4) NOT NULL DEFAULT '0',
  `authorized` tinyint(4) DEFAULT NULL,
  `signator` varchar(255) NOT NULL,
  `sig_image` text,
  `signature` text,
  `sig_hash` varchar(128) NOT NULL,
  `ip` varchar(46) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pid` (`pid`,`user`),
  KEY `encounter` (`encounter`)
) ENGINE=InnoDB AUTO_INCREMENT=1 ;
#EndIf

#IfNotRow categories name Onsite Portal
INSERT INTO categories select (select MAX(id) from categories) + 1, 'Onsite Portal', '', 1, rght, rght + 5 from categories where name = 'Categories';
INSERT INTO categories select (select MAX(id) from categories) + 1, 'Patient', '', (select id from categories where name = 'Onsite Portal'), rght + 1, rght + 2 from categories where name = 'Categories';
INSERT INTO categories select (select MAX(id) from categories) + 1, 'Reviewed', '', (select id from categories where name = 'Onsite Portal'), rght + 3, rght + 4 from categories where name = 'Categories';
UPDATE categories SET rght = rght + 6 WHERE name = 'Categories';
UPDATE categories_seq SET id = (select MAX(id) from categories);
#EndIf

#IfNotRow2D list_options list_id apptstat option_id ^
INSERT INTO list_options ( `list_id`, `option_id`, `title`, `seq`, `is_default`, `notes` ) VALUES ('apptstat','^','^ Pending',70,0,'FEFDCF|0');
#EndIf

#IfMissingColumn registry aco_spec
ALTER TABLE `registry` ADD `aco_spec` varchar(63) NOT NULL default 'encounters|notes';
UPDATE `registry` SET `aco_spec` = 'patients|appt'     WHERE directory = 'newpatient';
UPDATE `registry` SET `aco_spec` = 'patients|appt'     WHERE directory = 'newGroupEncounter';
UPDATE `registry` SET `aco_spec` = 'encounters|coding' WHERE directory = 'fee_sheet';
UPDATE `registry` SET `aco_spec` = 'encounters|coding' WHERE directory = 'misc_billing_options';
UPDATE `registry` SET `aco_spec` = 'patients|lab'      WHERE directory = 'procedure_order';
#EndIf

#IfNotColumnType lbf_data field_value longtext
ALTER TABLE `lbf_data` CHANGE `field_value` `field_value` longtext;
#EndIf

#IfMissingColumn issue_types aco_spec
ALTER TABLE `issue_types` ADD `aco_spec` varchar(63) NOT NULL default 'patients|med';
#EndIf

#IfMissingColumn categories aco_spec
ALTER TABLE `categories` ADD `aco_spec` varchar(63) NOT NULL default 'patients|docs';
#EndIf

#IfNotRow background_services name ccdaservice
INSERT INTO `background_services` (`name`, `title`, `execute_interval`, `function`, `require_once`, `sort_order`) VALUES ('ccdaservice', 'C-CDA Node Service', 1, 'runCheck', '/ccdaservice/ssmanager.php', 95);
ALTER TABLE `background_services` CHANGE `running` `running` TINYINT(1) NOT NULL DEFAULT '-1' COMMENT 'True indicates managed service is busy. Skip this interval.';
#EndIf

#IfNotColumnType onsite_mail owner varchar(128)
ALTER TABLE `onsite_mail` CHANGE `owner` `owner` varchar(128) DEFAULT NULL;
#Endif

#IfNotColumnType openemr_postcalendar_events pc_facility int(11)
ALTER TABLE `openemr_postcalendar_events` CHANGE `pc_facility` `pc_facility` int(11) NOT NULL DEFAULT '0' COMMENT 'facility id for this event';
#Endif

#IfMissingColumn form_misc_billing_options onset_date
ALTER TABLE `form_misc_billing_options` ADD `onset_date` date default NULL;
UPDATE `list_options` SET `option_id` = 'DK', `title` = 'Ordering Provider' WHERE `list_id` = 'provider_qualifier_code' AND `option_id` = 'dk';
UPDATE `list_options` SET `option_id` = 'DN', `title` = 'Referring Provider', `is_default` = '1' WHERE `list_id` = 'provider_qualifier_code' AND `option_id` = 'dn';
#EndIF

#IfNotRow2D list_options list_id provider_qualifier_code option_id DQ
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`) VALUES ('provider_qualifier_code', 'DQ', 'Supervising Provider', '30', '0');
#EndIf

#IfMissingColumn users main_menu_role
ALTER TABLE `users` ADD `main_menu_role` VARCHAR(50) NOT NULL DEFAULT 'standard';
#EndIf

#IfNotRow2D list_options list_id lists option_id apps
INSERT INTO list_options (list_id,option_id,title) VALUES ('lists','apps','Apps');
INSERT INTO list_options (list_id,option_id,title,seq,is_default,activity) VALUES ('apps','*OpenEMR','main/main_screen.php',10,1,0);
INSERT INTO list_options (list_id,option_id,title,seq,is_default,activity) VALUES ('apps','Calendar','main/calendar/index.php',20,0,0);
#EndIf

#IfMissingColumn insurance_companies freeb_type
ALTER TABLE insurance_companies  ADD COLUMN `freeb_type` TINYINT(2) DEFAULT NULL;
#EndIf

#IfMissingColumn procedure_providers mirth_lab_name
ALTER TABLE procedure_providers  ADD COLUMN `mirth_lab_name` VARCHAR(255) DEFAULT NULL;
#EndIf

#IfMissingColumn procedure_providers local_requisition
ALTER TABLE `procedure_providers` ADD COLUMN `local_requisition` TINYINT(4) DEFAULT '0';
#EndIf

#IfMissingColumn procedure_providers mirth_lab_id
ALTER TABLE `procedure_providers` ADD COLUMN `mirth_lab_id` SMALLINT(6) DEFAULT NULL;
#EndIf

#IfMissingColumn procedure_order psc_hold
ALTER TABLE `procedure_order` ADD COLUMN `psc_hold` VARCHAR(30) DEFAULT NULL;
#EndIf

#IfMissingColumn procedure_order result_file_url
ALTER TABLE `procedure_order` ADD COLUMN `result_file_url` VARCHAR(50) DEFAULT NULL;
#EndIf

#IfMissingColumn procedure_order return_comments
ALTER TABLE `procedure_order` ADD COLUMN `return_comments` TEXT;
#EndIf

#IfMissingColumn procedure_order review_comments
ALTER TABLE `procedure_order` ADD COLUMN `review_comments` TEXT;
#EndIf

#IfMissingColumn procedure_order reviewed_by
ALTER TABLE `procedure_order` ADD COLUMN `reviewed_by` VARCHAR(255) DEFAULT NULL;
#EndIf

#IfMissingColumn procedure_order signed_by
ALTER TABLE `procedure_order` ADD COLUMN `signed_by` VARCHAR(255) DEFAULT NULL;
#EndIf

#IfMissingColumn procedure_order ord_group
ALTER TABLE `procedure_order` ADD COLUMN `ord_group` INT(10) DEFAULT '0';
#EndIf

#IfMissingColumn procedure_order cor
ALTER TABLE `procedure_order` ADD COLUMN `cor` VARCHAR(30) DEFAULT 'No';
#EndIf

#IfMissingColumn procedure_order courtesy_copy
ALTER TABLE `procedure_order` ADD COLUMN `courtesy_copy` TEXT COMMENT 'where to send the copy of result; only for labcorp';
#EndIf

#IfMissingColumn procedure_order billto
ALTER TABLE `procedure_order` ADD COLUMN `billto` VARCHAR(5) DEFAULT NULL;
#EndIf

#IfMissingColumn procedure_order internal_comments
ALTER TABLE `procedure_order` ADD COLUMN `internal_comments` TEXT;
#EndIf

#IfMissingColumn procedure_order abn
ALTER TABLE `procedure_order` ADD COLUMN `abn` VARCHAR(30) DEFAULT 'No';
#EndIf

#IfMissingColumn procedure_order storage_type
ALTER TABLE `procedure_order` ADD COLUMN `storage_type` TINYINT(4) DEFAULT NULL;
#EndIf
 
#IfMissingColumn procedure_order couch_rev_id
ALTER TABLE `procedure_order` ADD COLUMN `couch_rev_id` VARCHAR(255) DEFAULT NULL;
#EndIf

#IfMissingColumn procedure_order requisition_file_url 
ALTER TABLE `procedure_order` ADD COLUMN `requisition_file_url` VARCHAR(50) DEFAULT NULL;
#EndIf

#IfMissingColumn procedure_order_code specimen_details
ALTER TABLE `procedure_order_code` ADD COLUMN `specimen_details` TEXT COMMENT 'specimen details only for LabCorp';
#EndIf

#IfMissingColumn procedure_order_code procedure_suffix
ALTER TABLE `procedure_order_code` ADD COLUMN `procedure_suffix` VARCHAR (50) DEFAULT NULL;
#EndIf

#IfMissingColumn procedure_order_code patient_instructions
ALTER TABLE `procedure_order_code` ADD COLUMN `patient_instructions` TEXT;
#EndIf

#IfMissingColumn procedure_type mirth_lab_id
ALTER TABLE `procedure_type` ADD COLUMN `mirth_lab_id` SMALLINT(6) DEFAULT NULL;
#EndIf

#IfMissingColumn procedure_type suffix
ALTER TABLE `procedure_type` ADD COLUMN `suffix` VARCHAR(50) NOT NULL DEFAULT '';
#EndIf

#IfMissingColumn procedure_type pap_indicator
ALTER TABLE `procedure_type` ADD COLUMN `pap_indicator` VARCHAR(5) DEFAULT NULL;
#EndIf

#IfMissingColumn procedure_type specimen_state
ALTER TABLE `procedure_type` ADD COLUMN `specimen_state` VARCHAR(5) DEFAULT NULL;
#EndIf

#IfMissingColumn procedure_result order_title
ALTER TABLE `procedure_result` ADD COLUMN `order_title` VARCHAR(255) DEFAULT NULL;
#EndIf

#IfMissingColumn procedure_result profile_title
ALTER TABLE `procedure_result` ADD COLUMN `profile_title` VARCHAR(255) DEFAULT NULL;
#EndIf

#IfMissingColumn procedure_result code_suffix
ALTER TABLE `procedure_result` ADD COLUMN `code_suffix` VARCHAR(255) DEFAULT NULL;
#EndIf

#IfMissingColumn procedure_questions mirth_lab_id
ALTER TABLE `procedure_questions` ADD COLUMN `mirth_lab_id` SMALLINT(6) DEFAULT NULL;
#EndIf

#IfMissingColumn procedure_questions specimen_case
ALTER TABLE `procedure_questions` ADD COLUMN `specimen_case` CHAR(1) DEFAULT NULL COMMENT 'Specimen case';
#EndIf

#IfMissingColumn procedure_questions question_component 
ALTER TABLE `procedure_questions` ADD COLUMN `question_component` VARCHAR(255) DEFAULT NULL;
#EndIf
 
#IfMissingColumn procedure_questions options_value
ALTER TABLE `procedure_questions` ADD COLUMN `options_value` TEXT COMMENT 'option values of select';
#EndIf

#IfMissingColumn procedure_questions hl7_segment 
ALTER TABLE `procedure_questions` ADD COLUMN `hl7_segment` VARCHAR(25) DEFAULT NULL COMMENT 'hl7 segment position for labcorp';
#EndIf

#IfNotColumnType procedure_questions seq INT(11)
ALTER TABLE `procedure_questions` MODIFY COLUMN `seq` INT(11) DEFAULT '0' COMMENT 'sequence number for ordering';
#EndIf

#IfNotColumnType procedure_questions fldtype CHAR(1)
ALTER TABLE `procedure_questions` MODIFY COLUMN `fldtype` CHAR(1) DEFAULT 'T' COMMENT 'Text, Number, Select, Multiselect, Date, Gestational-age';
#EndIf

#IfMissingColumn patient_data parent_first
ALTER TABLE `patient_data` ADD COLUMN parent_first VARCHAR (255) DEFAULT NULL;
#EndIf

#IfMissingColumn patient_data parent_last
ALTER TABLE `patient_data` ADD COLUMN `parent_last` VARCHAR (255) DEFAULT NULL;
#EndIf

#IfMissingColumn patient_data parent_mid
ALTER TABLE `patient_data` ADD COLUMN `parent_mid` VARCHAR (255) DEFAULT NULL;
#EndIf

#IfMissingColumn patient_data parent_add1
ALTER TABLE `patient_data` ADD COLUMN `parent_add1` TEXT;
#EndIf

#IfMissingColumn patient_data parent_add2
ALTER TABLE `patient_data` ADD COLUMN `parent_add2` TEXT;
#EndIf

#IfMissingColumn patient_data parent_city
ALTER TABLE `patient_data` ADD COLUMN `parent_city` TEXT;
#EndIf

#IfMissingColumn patient_data parent_state
ALTER TABLE `patient_data` ADD COLUMN `parent_state` TEXT;
#EndIf

#IfMissingColumn patient_data parent_zip
ALTER TABLE `patient_data` ADD COLUMN `parent_zip` TEXT;
#EndIf

#IfMissingColumn patient_data parent_phone
ALTER TABLE `patient_data` ADD COLUMN `parent_phone` TEXT;
#EndIf
  
#IfNotColumnType procedure_questions question_text VARCHAR(255)
ALTER TABLE `procedure_questions` CHANGE `question_text` `question_text` VARCHAR(255) CHARSET utf8 COLLATE utf8_general_ci DEFAULT '' NULL COMMENT 'descriptive text for question_code'; 
#EndIf

#IfNotColumnType procedure_questions required TINYINT(1)
ALTER TABLE `procedure_questions` CHANGE `required` `required` TINYINT(1) DEFAULT 0 NULL COMMENT '1 = required, 0 = not'; 
#EndIf

#IfNotColumnType procedure_questions maxsize INT(11)
ALTER TABLE `procedure_questions` CHANGE `maxsize` `maxsize` INT(11) DEFAULT 0 NULL COMMENT 'maximum length if text input field';
#EndIf

#IfNotColumnType procedure_questions tips VARCHAR(255)
ALTER TABLE `procedure_questions` CHANGE `tips` `tips` VARCHAR(255) CHARSET utf8 COLLATE utf8_general_ci DEFAULT '' NULL COMMENT 'Additional instructions for answering the question'; 
#EndIf

#IfNotTable module_menu
CREATE TABLE `module_menu` (
  `menu_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `module_id` INT(10) UNSIGNED NOT NULL,
  `menu_name` VARCHAR(45) NOT NULL,
  `parent_id` INT(10) UNSIGNED NOT NULL,
  `controller_name` VARCHAR(45) NOT NULL,
  `action` VARCHAR(45) NOT NULL,
  `icon` VARCHAR(45) NOT NULL,
  `status` TINYINT(1) UNSIGNED NOT NULL,
  `group_id` INT(10) UNSIGNED NOT NULL,
  `order_id` INT(10) UNSIGNED NOT NULL,
  `url` VARCHAR(225) NOT NULL,
  PRIMARY KEY (`menu_id`)
) ENGINE=INNODB;

INSERT INTO `module_menu` (`menu_id`,`module_id`,`menu_name`,`parent_id`,`controller_name`,`action`,`icon`,`status`,`group_id`,`order_id`,`url`) VALUES 
 (1,1,'Procedure Order',0,'lab','order','icon-multiple',1,10,1,'');
INSERT INTO `module_menu` (`menu_id`,`module_id`,`menu_name`,`parent_id`,`controller_name`,`action`,`icon`,`status`,`group_id`,`order_id`,`url`) VALUES 
 (2,1,'Results',0,'result','index','icon-labresultsingle',1,20,1,'');
INSERT INTO `module_menu` (`menu_id`,`module_id`,`menu_name`,`parent_id`,`controller_name`,`action`,`icon`,`status`,`group_id`,`order_id`,`url`) VALUES 
 (4,1,'Batch Results',2,'resultnew','index','icon-sum',1,20,2,'');
INSERT INTO `module_menu` (`menu_id`,`module_id`,`menu_name`,`parent_id`,`controller_name`,`action`,`icon`,`status`,`group_id`,`order_id`,`url`) VALUES 
 (5,2,'Test',0,'test','index','test',1,10,1,'');
INSERT INTO `module_menu` (`menu_id`,`module_id`,`menu_name`,`parent_id`,`controller_name`,`action`,`icon`,`status`,`group_id`,`order_id`,`url`) VALUES 
 (6,1,'Result Entry',2,'result','resultEntry','icon-resultentry',1,20,3,'');
INSERT INTO `module_menu` (`menu_id`,`module_id`,`menu_name`,`parent_id`,`controller_name`,`action`,`icon`,`status`,`group_id`,`order_id`,`url`) VALUES 
 (7,1,'Unassociated Results',2,'unassociated','index','icon-unassociatedresult',1,20,4,'');
INSERT INTO `module_menu` (`menu_id`,`module_id`,`menu_name`,`parent_id`,`controller_name`,`action`,`icon`,`status`,`group_id`,`order_id`,`url`) VALUES 
 (8,1,'Pull Compendium',0,'pull','index','icon-pull',1,30,1,'');
INSERT INTO `module_menu` (`menu_id`,`module_id`,`menu_name`,`parent_id`,`controller_name`,`action`,`icon`,`status`,`group_id`,`order_id`,`url`) VALUES 
 (9,1,'Procedure Configuration',8,'configuration','index','icon-config',1,30,2,'');
INSERT INTO `module_menu` (`menu_id`,`module_id`,`menu_name`,`parent_id`,`controller_name`,`action`,`icon`,`status`,`group_id`,`order_id`,`url`) VALUES 
 (10,1,'Specimen Collection',0,'specimen','index','icon-specimencollection',1,40,1,'');
INSERT INTO `module_menu` (`menu_id`,`module_id`,`menu_name`,`parent_id`,`controller_name`,`action`,`icon`,`status`,`group_id`,`order_id`,`url`) VALUES 
 (11,1,'Providers',0,'provider','index','icon-providers',1,50,1,'');
 #EndIf

#IfNotTable procedure_specimen
CREATE TABLE `procedure_specimen` (
  `procedure_specimen_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `specimen_descriptor` varchar(255) DEFAULT NULL,
  `specimen_term` varchar(255) DEFAULT NULL,
 `specimen_type` CHAR(1) NULL,
  PRIMARY KEY (`procedure_specimen_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
 #EndIf

#IfNotTable procedure_result_unassociated
CREATE TABLE `procedure_result_unassociated` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `patient_name` varchar(255) DEFAULT NULL,
  `file_pid` bigint(20) DEFAULT NULL,
  `file_order_id` varchar(255) DEFAULT NULL,
  `file_location` varchar(500) DEFAULT NULL,
  `attached` tinyint(4) DEFAULT '0',
  `comment` varchar(1000) DEFAULT NULL,
  PRIMARY KEY (`id`)
)ENGINE=InnoDB;
 #EndIf

#IfNotTable procedure_subtest_result
CREATE TABLE `procedure_subtest_result` (
  `procedure_subtest_result_id` BIGINT(20) NOT NULL AUTO_INCREMENT,
  `procedure_report_id` BIGINT(20) NOT NULL COMMENT 'references procedure_report.procedure_report_id',
  `subtest_code` VARCHAR(30) NOT NULL DEFAULT '',
  `subtest_desc` VARCHAR(255) NOT NULL DEFAULT '',
  `result_value` VARCHAR(255) NOT NULL DEFAULT '',
  `units` VARCHAR(30) NOT NULL DEFAULT '',
  `range` VARCHAR(255) NOT NULL DEFAULT '',
  `abnormal_flag` VARCHAR(31) NOT NULL DEFAULT '' COMMENT 'no,yes,high,low',
  `result_status` VARCHAR(31) NOT NULL DEFAULT '' COMMENT 'preliminary, cannot be done, final, corrected, incompete...etc.',
  `result_time` DATETIME DEFAULT NULL,
  `provider_name` VARCHAR(255) DEFAULT NULL,
  `comments` TEXT NOT NULL COMMENT 'comments of subtest',
  `order_title` VARCHAR(255) DEFAULT NULL,
  `code_suffix` VARCHAR(255) DEFAULT NULL,
  `profile_title` VARCHAR(255) DEFAULT NULL,
  `providers_id` INT(11) DEFAULT NULL,
  `facility` VARCHAR(255) DEFAULT NULL,
  `last_modified_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last modified date',
  PRIMARY KEY (`procedure_subtest_result_id`),
  KEY `procedure_report_id` (`procedure_report_id`)
) ENGINE=INNODB;
 #EndIf

#IfNotTable procedure_subtest_result_only
CREATE TABLE `procedure_subtest_result_only` (
  `lab_result_id` bigint(20) NOT NULL,
  `subtest_code` varchar(30) NOT NULL DEFAULT '',
  `subtest_desc` varchar(255) NOT NULL DEFAULT '',
  `result_value` varchar(255) NOT NULL DEFAULT '',
  `units` varchar(30) NOT NULL DEFAULT '',
  `range` varchar(255) NOT NULL DEFAULT '',
  `abnormal_flag` varchar(31) NOT NULL DEFAULT '',
  `result_status` varchar(31) NOT NULL DEFAULT '',
  `provider_name` varchar(255) DEFAULT NULL,
  `result_time` datetime DEFAULT NULL,
  `performing_lab` varchar(255) DEFAULT NULL,
  `comments` text,
  `order_title` varchar(255) DEFAULT NULL
) ENGINE=InnoDB;
 #EndIf
 
#IfNotTable procedure_result_only
CREATE TABLE `procedure_result_only` (
  `lab_result_id` BIGINT (20) NOT NULL,
  `patient_lname` VARCHAR (255) DEFAULT NULL,
  `patient_fname` VARCHAR (255) DEFAULT NULL,
  `patient_dob` DATE DEFAULT NULL,
  `patient_gender` VARCHAR (30) DEFAULT NULL,
  `patient_home_phone` VARCHAR (255) DEFAULT NULL,
  `patient_work_phone` VARCHAR (255) DEFAULT NULL,
  `patient_ss_no` VARCHAR (255) DEFAULT NULL,
  `date` DATETIME DEFAULT NULL,
  `result_status` VARCHAR (255) DEFAULT NULL,
  `specimen_id` VARCHAR (255) DEFAULT NULL,
  `order_level_comment` VARCHAR (255) DEFAULT NULL,
  `performing_lab_addr1` VARCHAR (50) DEFAULT NULL,
  `performing_lab_addr2` VARCHAR (50) DEFAULT NULL,
  `performing_lab_city` VARCHAR (50) DEFAULT NULL,
  `performing_lab_state` VARCHAR (30) DEFAULT NULL,
  `performing_lab_zip` VARCHAR (20) DEFAULT NULL,
  `performing_lab_phone` VARCHAR (20) DEFAULT NULL,
  `performing_lab_provider` VARCHAR (255) DEFAULT NULL,
  `performing_lab_name` VARCHAR (255) DEFAULT NULL,
  `procedure_order_id` BIGINT (20) DEFAULT NULL
) ENGINE = INNODB ;
 #EndIf