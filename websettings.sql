-- SQL for the `site_settings` table
-- This table uses a key-value pair structure to store website content.

CREATE TABLE `site_settings` (
  `setting_name` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  PRIMARY KEY (`setting_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert some default values so the fields exist on the first load
INSERT INTO `site_settings` (`setting_name`, `setting_value`) VALUES
('about_us', '<p>Welcome to News Week! We are dedicated to bringing you the latest and most accurate news from around the globe.</p>'),
('contact_info', '<p>For inquiries, please email us at: contact@newsweek.example.com</p><p>Our office is located at: 123 News Lane, Media City</p>'),
('privacy_policy', '<h1>Privacy Policy</h1><p>Your privacy is important to us. It is News Week\'s policy to respect your privacy regarding any information we may collect from you across our website.</p>'),
('social_facebook', 'https://facebook.com/your-page'),
('social_twitter', 'https://twitter.com/your-handle'),
('social_instagram', 'https://instagram.com/your-handle'),
('social_linkedin', 'https://linkedin.com/your-page');

