-- ============================================================
-- FLOOR MAP: Add canvas layout columns to rooms table
-- Run this SQL in your school_management database
-- ============================================================

ALTER TABLE rooms
    ADD COLUMN IF NOT EXISTS `x_pos`    INT            DEFAULT NULL COMMENT 'Canvas X position',
    ADD COLUMN IF NOT EXISTS `y_pos`    INT            DEFAULT NULL COMMENT 'Canvas Y position',
    ADD COLUMN IF NOT EXISTS `width`    INT            DEFAULT NULL COMMENT 'Canvas width',
    ADD COLUMN IF NOT EXISTS `height`   INT            DEFAULT NULL COMMENT 'Canvas height',
    ADD COLUMN IF NOT EXISTS `color`    VARCHAR(20)    DEFAULT '#85C1E2' COMMENT 'Room color on floor map';

-- ============================================================
-- Seed default rooms (matching the original hardcoded layout)
-- Only inserts if buildings table has at least one building.
-- Adjust building_id as needed.
-- ============================================================

-- First, ensure a default building exists
INSERT IGNORE INTO buildings (id, building_name, building_code, location)
VALUES (1, 'Main Building', 'MAIN', 'Main Campus');

-- Insert default rooms with canvas positions
INSERT INTO rooms (room_number, building_id, floor, room_type, x_pos, y_pos, width, height, color) VALUES
('AVP Office',        1, '1', 'Administrative', 10,  15,  200, 95,  '#F4D03F'),
('College Classroom', 1, '1', 'Classroom',      210, 15,  185, 95,  '#85C1E2'),
('Computer Lab',      1, '1', 'Classroom',      395, 15,  175, 95,  '#85C1E2'),
('Clinic',            1, '1', 'Service',        570, 15,  185, 95,  '#7DCEA0'),
('BED Principal',     1, '1', 'Administrative', 10,  155, 115, 105, '#F4D03F'),
('Vice President',    1, '1', 'Administrative', 125, 155, 115, 105, '#F4D03F'),
('Registrar',         1, '1', 'Administrative', 10,  260, 230, 165, '#F4D03F'),
('Quadrangle',        1, '1', 'Common Area',    280, 200, 275, 225, '#F1948A'),
('MIS Office',        1, '1', 'Administrative', 400, 155, 355, 45,  '#F4D03F'),
('CR 5',              1, '1', 'Service',        755, 155, 130, 45,  '#7DCEA0'),
('Marketing',         1, '1', 'Administrative', 555, 200, 100, 45,  '#F4D03F'),
('CCTI',              1, '1', 'Administrative', 755, 200, 130, 45,  '#F4D03F'),
('BSBA Office',       1, '1', 'Administrative', 555, 245, 100, 55,  '#F4D03F'),
('Guidance',          1, '1', 'Administrative', 755, 245, 130, 55,  '#F4D03F'),
('Playgroup',         1, '1', 'Classroom',      585, 335, 135, 45,  '#85C1E2'),
('CR 1',              1, '1', 'Service',        755, 335, 130, 45,  '#7DCEA0'),
('Lounging Room',     1, '1', 'Service',        585, 380, 135, 45,  '#7DCEA0'),
('CR 2',              1, '1', 'Service',        755, 380, 130, 45,  '#7DCEA0'),
('CR 3',              1, '1', 'Service',        755, 425, 130, 45,  '#7DCEA0'),
('CR 4',              1, '1', 'Service',        755, 470, 130, 35,  '#7DCEA0'),
('Banko Maximo',      1, '1', 'Service',        10,  495, 115, 115, '#7DCEA0'),
('HR',                1, '1', 'Administrative', 125, 495, 115, 115, '#F4D03F'),
('Chapel',            1, '1', 'Common Area',    575, 510, 310, 100, '#F1948A')
ON DUPLICATE KEY UPDATE
    x_pos   = VALUES(x_pos),
    y_pos   = VALUES(y_pos),
    width   = VALUES(width),
    height  = VALUES(height),
    color   = VALUES(color);
