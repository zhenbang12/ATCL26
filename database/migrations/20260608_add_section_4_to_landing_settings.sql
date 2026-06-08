-- Add Section 4 (Join the Team) to landing page settings and images

ALTER TABLE landing_images MODIFY COLUMN slot ENUM('hero', 'feature_1', 'feature_2', 'feature_3') NOT NULL;

INSERT IGNORE INTO landing_images (slot, filename, alt_text) VALUES ('feature_3', NULL, '');

ALTER TABLE landing_settings
ADD COLUMN section_4_title VARCHAR(255) NOT NULL DEFAULT 'Join the Team' AFTER section_3_caption,
ADD COLUMN section_4_caption TEXT NOT NULL AFTER section_4_title;

UPDATE landing_settings SET
    section_4_title = 'Join the Team',
    section_4_caption = 'Want to make a difference? Apply as a facilitator or volunteer in our upcoming camp segments.'
WHERE id = 1;
