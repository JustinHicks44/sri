-- Migration: Add Categories Table and Link to Events
-- Run this after the initial schema (-- 1.sql)

-- 1. Create Categories Table
CREATE TABLE Categories (
    CategoryID INT AUTO_INCREMENT PRIMARY KEY,
    Name VARCHAR(100) NOT NULL UNIQUE,
    Description TEXT,
    CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. Add CategoryID column to Events table
ALTER TABLE Events ADD COLUMN CategoryID INT AFTER LocationID;

-- 3. Add foreign key constraint
ALTER TABLE Events ADD CONSTRAINT fk_events_category 
    FOREIGN KEY (CategoryID) REFERENCES Categories(CategoryID) ON DELETE SET NULL;

-- 4. Populate sample categories
INSERT INTO Categories (Name, Description) VALUES
('Music', 'Concerts, festivals, and live music performances'),
('Sports', 'Athletic events, competitions, and matches'),
('Technology', 'Tech conferences, expos, and product launches'),
('Art', 'Exhibitions, gallery openings, and art shows'),
('Comedy', 'Stand-up shows and comedy performances'),
('Theater', 'Plays, musicals, and theatrical performances'),
('Food & Drink', 'Food festivals, tastings, and dining events'),
('Business', 'Conferences, seminars, and networking events');

-- 5. Update existing events with categories (example)
-- Adjust these based on your actual event titles
UPDATE Events SET CategoryID = (SELECT CategoryID FROM Categories WHERE Name = 'Music') 
    WHERE Title LIKE '%Rock%' OR Title LIKE '%Jazz%' OR Title LIKE '%Concert%';
UPDATE Events SET CategoryID = (SELECT CategoryID FROM Categories WHERE Name = 'Technology') 
    WHERE Title LIKE '%Tech%' OR Title LIKE '%Expo%';
