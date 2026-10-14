-- Add editable registration CTA card settings to landing_settings table

ALTER TABLE landing_settings
ADD COLUMN cta_title VARCHAR(255) NOT NULL DEFAULT 'Ready to join?' AFTER main_caption,
ADD COLUMN cta_caption TEXT NULL AFTER cta_title,
ADD COLUMN cta_mode VARCHAR(50) NOT NULL DEFAULT 'closed_walkin' AFTER cta_caption,
ADD COLUMN walkin_location VARCHAR(255) NOT NULL DEFAULT 'DTAR' AFTER cta_mode,
ADD COLUMN walkin_time VARCHAR(255) NOT NULL DEFAULT 'tomorrow 1PM' AFTER walkin_location,
ADD COLUMN cta_custom_notice TEXT NULL AFTER walkin_time;

UPDATE landing_settings
SET cta_title = 'Ready to join?',
    cta_caption = 'Takes only a minute. You will need your student ID and TAR UMT student email.',
    cta_mode = 'closed_walkin',
    walkin_location = 'DTAR',
    walkin_time = 'tomorrow 1PM'
WHERE id = 1;
