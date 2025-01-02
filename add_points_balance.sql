-- Add points_balance column to users table
ALTER TABLE users ADD COLUMN points_balance INT DEFAULT 0;

-- Add translations for points balance
INSERT INTO translations (language_code, translation_key, translation_value, context) VALUES
('en', 'user.points_balance', 'Points Balance', 'shop'),
('hu', 'user.points_balance', 'Pontegyenleg', 'shop'),
('en', 'user.points', '{points} points', 'shop'),
('hu', 'user.points', '{points} pont', 'shop'); 