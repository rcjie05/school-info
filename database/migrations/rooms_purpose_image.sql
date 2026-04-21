-- ============================================================
-- Add purpose and image_url columns to rooms table
-- Run this in your school_management database
-- ============================================================

ALTER TABLE rooms
    ADD COLUMN IF NOT EXISTS `purpose`   TEXT           DEFAULT NULL COMMENT 'Room purpose / description',
    ADD COLUMN IF NOT EXISTS `image_url` VARCHAR(500)   DEFAULT NULL COMMENT 'Room photo path';

-- Update existing default rooms with purposes
UPDATE rooms SET purpose = 'Office of the Associate Vice President for Academic Affairs. Handles academic policy and faculty coordination.' WHERE room_number = 'AVP Office';
UPDATE rooms SET purpose = 'General-purpose college classroom for lectures and discussions.' WHERE room_number = 'College Classroom';
UPDATE rooms SET purpose = 'Computer laboratory equipped with desktop PCs for IT and programming subjects.' WHERE room_number = 'Computer Lab';
UPDATE rooms SET purpose = 'Medical clinic providing first aid and health services to students and staff.' WHERE room_number = 'Clinic';
UPDATE rooms SET purpose = 'Office of the Basic Education Department Principal.' WHERE room_number = 'BED Principal';
UPDATE rooms SET purpose = 'Office of the College Vice President.' WHERE room_number = 'Vice President';
UPDATE rooms SET purpose = 'Registrar office for enrollment, records, and student documents.' WHERE room_number = 'Registrar';
UPDATE rooms SET purpose = 'Open quadrangle area used for events, activities, and student gatherings.' WHERE room_number = 'Quadrangle';
UPDATE rooms SET purpose = 'Management Information Systems office handling IT infrastructure and systems.' WHERE room_number = 'MIS Office';
UPDATE rooms SET purpose = 'Comfort room for students and staff on the first floor.' WHERE room_number = 'CR 5';
UPDATE rooms SET purpose = 'Marketing and communications office for school promotions and enrollment campaigns.' WHERE room_number = 'Marketing';
UPDATE rooms SET purpose = 'CCTI office for technical-vocational programs.' WHERE room_number = 'CCTI';
UPDATE rooms SET purpose = 'Office for the Bachelor of Science in Business Administration program.' WHERE room_number = 'BSBA Office';
UPDATE rooms SET purpose = 'Guidance and counseling office providing student support and career advice.' WHERE room_number = 'Guidance';
UPDATE rooms SET purpose = 'Pre-school playgroup classroom for early childhood education.' WHERE room_number = 'Playgroup';
UPDATE rooms SET purpose = 'Comfort room.' WHERE room_number IN ('CR 1','CR 2','CR 3','CR 4');
UPDATE rooms SET purpose = 'Lounging area for students to rest and relax between classes.' WHERE room_number = 'Lounging Room';
UPDATE rooms SET purpose = 'School canteen operated by Banko Maximo serving meals and snacks.' WHERE room_number = 'Banko Maximo';
UPDATE rooms SET purpose = 'Human Resources office managing faculty and staff records, payroll, and recruitment.' WHERE room_number = 'HR';
UPDATE rooms SET purpose = 'College chapel used for daily masses, prayer services, and spiritual activities.' WHERE room_number = 'Chapel';
