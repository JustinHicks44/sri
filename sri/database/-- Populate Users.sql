-- Populate Users
INSERT INTO Users (Username, Email, PasswordHash, Role, IsVerified) VALUES
('AdminAlice', 'alice@myticket.com', 'hash123', 'Admin', 1),
('OrganizerBob', 'bob@events.com', 'hash456', 'Organizer', 1),
('CustomerCharlie', 'charlie@gmail.com', 'hash789', 'Customer', 0);

-- Populate Locations
INSERT INTO Locations (Name, Address, City, State, PostalCode) VALUES
('City Stadium', '123 Main St', 'New York', 'NY', '10001'),
('Jazz Club', '456 Beale St', 'Memphis', 'TN', '38103'),
('Convention Center', '789 Tech Blvd', 'San Francisco', 'CA', '94105');

-- Populate Promotions
INSERT INTO Promotions (Code, DiscountType, DiscountValue, StartDate, EndDate) VALUES
('EARLYBIRD', 'Percentage', 10.00, '2023-01-01', '2023-12-31'),
('SUMMERFUN', 'FixedAmount', 5.00, '2023-06-01', '2023-08-31'),
('VIP20', 'Percentage', 20.00, '2023-01-01', '2023-12-31');

-- Populate Events (OrganizerID 2 is Bob, LocationIDs 1-3)
INSERT INTO Events (OrganizerID, LocationID, Title, Description, EventDateTime, ApprovalStatus) VALUES
(2, 1, 'Rock Fest 2023', 'The biggest rock concert of the year', '2023-11-15 18:00:00', 'Approved'),
(2, 2, 'Smooth Jazz Night', 'Relaxing jazz music', '2023-12-01 20:00:00', 'Approved'),
(2, 3, 'Tech Expo', 'Future technology showcase', '2024-01-10 09:00:00', 'Pending');

-- Populate TicketTypes
INSERT INTO TicketTypes (EventID, Name, Price, TotalCapacity) VALUES
(1, 'General Admission', 50.00, 5000),
(1, 'VIP Pit', 150.00, 500),
(2, 'Table Seat', 75.00, 100);

-- Populate EventPromotions (Linking Events to Promos)
INSERT INTO EventPromotions (EventID, PromoID) VALUES
(1, 1), -- Rock Fest gets Earlybird
(1, 3), -- Rock Fest gets VIP20
(2, 2); -- Jazz Night gets Summerfun

-- Populate Orders (CustomerID 3 is Charlie)
INSERT INTO Orders (CustomerID, TotalAmount, Status, PaymentMethod) VALUES
(3, 100.00, 'Paid', 'Credit Card'),
(3, 150.00, 'Paid', 'PayPal'),
(3, 75.00, 'Pending', 'Credit Card');

-- Populate Tickets (Linked to Orders and TicketTypes)
INSERT INTO Tickets (OrderID, TicketTypeID, UniqueBarcode, PurchasePrice) VALUES
(1, 1, 'ABC-11111', 50.00), -- Charlie bought 2 GA tickets in Order 1
(1, 1, 'ABC-11112', 50.00),
(2, 2, 'VIP-22222', 150.00); -- Charlie bought 1 VIP ticket in Order 2