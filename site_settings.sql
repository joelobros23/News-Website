-- Remove the old, generic contact_info setting
DELETE FROM `site_settings` WHERE `setting_name` = 'contact_info';

-- Add the new, specific fields for contact information with default values
INSERT INTO `site_settings` (`setting_name`, `setting_value`) VALUES
('contact_telephone', '(012) 345-6789'),
('contact_mobile', '+63 912 345 6789'),
('contact_email', 'contact@newsweek.example.com'),
('contact_location', '123 News Lane, Media City, 12345')
ON DUPLICATE KEY UPDATE setting_name=setting_name; -- This does nothing if the keys already exist, preventing errors on re-run

