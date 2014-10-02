/**
 * This extension enhances profile creation for Grants.
 * 
 * 
 * Copyright (C) 2012 JMA Consulting
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 * 
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 * 
 * Support: https://github.com/JMAConsulting/biz.jmaconsulting.grantprofiles/issues
 * 
 * Contact: info@jmaconsulting.biz
 *          JMA Consulting
 *          215 Spadina Ave, Ste 400
 *          Toronto, ON  
 *          Canada   M5T 2C7
 */
--
-- Table structure for table `civicrm_grant_app_page`

CREATE TABLE IF NOT EXISTS `civicrm_grant_app_page` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Grant Application Page Id.',
  `title` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Grant Application Page title. For top of page display.',
  `intro_text` text COLLATE utf8_unicode_ci COMMENT 'Text and html allowed. Displayed below title.',
  `footer_text` text COLLATE utf8_unicode_ci COMMENT 'Text and html allowed. Displayed at the bottom of the first page of the contribution wizard.',
  `grant_type_id` int(10) unsigned NOT NULL COMMENT 'Grant type assigned to applications submitted via this page.',
  `default_amount` decimal(20,2) DEFAULT NULL COMMENT 'Default amount of grant applied for.',
  `thankyou_title` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Title for Thank-you page (header title tag, and display at the top of the page).',
  `thankyou_text` text COLLATE utf8_unicode_ci COMMENT 'Text and html allowed. Displayed above result on success page',
  `thankyou_footer` text COLLATE utf8_unicode_ci COMMENT 'Text and html allowed. displayed at the bottom of the success page. Common usage is to include link(s) to other pages such as tell-a-friend, etc.',
  `is_email_receipt` tinyint(4) DEFAULT '0' COMMENT 'If true, receipt is automatically emailed to contact on success',
  `receipt_from_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'FROM email name used for receipts generated by applications to this grant application page.',
  `receipt_from_email` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'FROM email address used for receipts generated by applications to this grant application page.',
  `cc_receipt` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Comma-separated list of email addresses to cc each time a receipt is sent',
  `bcc_receipt` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Comma-separated list of email addresses to bcc each time a receipt is sent',
  `receipt_text` text COLLATE utf8_unicode_ci COMMENT 'Text to include above standard receipt info on receipt email. emails are text-only, so do not allow html for now',
  `is_active` tinyint(4) DEFAULT NULL COMMENT 'Is this grant application page active?',
  `start_date` datetime DEFAULT NULL COMMENT 'Date and time that this page starts.',
  `end_date` datetime DEFAULT NULL COMMENT 'Date and time that this page ends. May be NULL if no defined end date/time',
  `created_id` int(10) unsigned DEFAULT NULL COMMENT 'FK to civicrm_contact, who created this contribution page',
  `created_date` datetime DEFAULT NULL COMMENT 'Date and time that grant application page was created.',
  PRIMARY KEY (`id`),
  KEY `FK_civicrm_grant_app_page_created_id` (`created_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `civicrm_navigation`

SELECT @parentId := id FROM civicrm_navigation WHERE name = 'Grants';

INSERT INTO `civicrm_navigation` (`domain_id`, `label`, `name`, `url`, `permission`, `permission_operator`, `parent_id`, `is_active`, `has_separator`, `weight`) VALUES
(1, 'New Grant Application Page', 'New Grant Application Page', 'civicrm/admin/grant/apply?reset=1&action=add', 'access CiviGrant', 'AND', @parentId, 1, 1, 4);

SELECT @optionGroupId := id FROM `civicrm_option_group` WHERE `name` = 'activity_type';

SELECT @maxValue := MAX( CAST( `value` AS UNSIGNED ) ) + 1 FROM  `civicrm_option_value` WHERE `option_group_id` = @optionGroupId;

SELECT @maxWeight := MAX( CAST( `weight` AS UNSIGNED ) ) + 1 FROM  `civicrm_option_value` WHERE `option_group_id` = @optionGroupId;

SELECT @activityTypeId := id FROM `civicrm_option_value` WHERE `name` = 'Grant';

INSERT IGNORE INTO `civicrm_option_value` (`id`, `option_group_id`, {localize field='label'}`label`{/localize}, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`, {localize field='description'}`description`{/localize}, `is_optgroup`, `is_reserved`, `is_active`, `component_id`, `domain_id`, `visibility_id`) VALUES
(@activityTypeId, @optionGroupId, {localize}'{ts escape="sql"}Grant{/ts}'{/localize}, @maxValue, 'Grant', NULL, 1, NULL, @maxWeight, {localize}'Online Grant Application'{/localize}, 0, 1, 1, 5, NULL, NULL);

SELECT @dashId := id FROM `civicrm_option_group` WHERE `name` = 'user_dashboard_options';

SELECT @maxValue := MAX( CAST( `value` AS UNSIGNED ) ) + 1 FROM  `civicrm_option_value` WHERE `option_group_id` = @dashId;

SELECT @maxWeight := MAX( CAST( `weight` AS UNSIGNED ) ) + 1 FROM  `civicrm_option_value` WHERE `option_group_id` = @dashId;

INSERT IGNORE INTO `civicrm_option_value` (`option_group_id`, {localize field='label'}`label`{/localize}, `value`, `name`, `weight`, {localize field='description'}`description`{/localize}, `is_active`) VALUES (@dashId, {localize}'{ts escape="sql"}Grants{/ts}'{/localize}, @maxValue, 'CiviGrant', @maxWeight, {localize}'Grants on dashboard'{/localize}, 1);
