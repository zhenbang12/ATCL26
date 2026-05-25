-- Add per-group max_per_group column to event_groups table.
-- When set to 0, the global event_group_settings.max_per_group is used as fallback.
ALTER TABLE event_groups ADD COLUMN max_per_group INT NOT NULL DEFAULT 0;