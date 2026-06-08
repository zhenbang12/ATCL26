-- Add Section 4 Button text and URL to landing page settings

ALTER TABLE landing_settings
ADD COLUMN section_4_url VARCHAR(1024) NOT NULL DEFAULT '' AFTER section_4_caption,
ADD COLUMN section_4_button_text VARCHAR(255) NOT NULL DEFAULT 'View Booklet' AFTER section_4_url;

UPDATE landing_settings SET
    section_4_url = 'https://online.anyflip.com/qpove/nerx/mobile/index.html?fbclid=IwY2xjawSS5X9leHRuA2FlbQIxMQBicmlkETFldkVnN1NCUFllejBsWDhUc3J0YwZhcHBfaWQQMjIyMDM5MTc4ODIwMDg5MgABHqDKuLxet1P2Uxtt-Hrl2HK2EQuuqlE_wBmFyQhIYTTvEq3MG5GtDX5O1ePk_aem_cQIa2hhARUL9p4tym7omCA',
    section_4_button_text = 'View Booklet'
WHERE id = 1;
