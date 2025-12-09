-- 1. Users Table (Must be created first as other tables depend on it)
CREATE TABLE Users (
    UserID INT AUTO_INCREMENT PRIMARY KEY,
    Username VARCHAR(100) NOT NULL,
    Email VARCHAR(150) NOT NULL UNIQUE,
    PasswordHash VARCHAR(255) NOT NULL,
    Role ENUM('Admin', 'Organizer', 'Customer') NOT NULL,
    IsVerified TINYINT(1) DEFAULT 0, -- 0 for false, 1 for true
    RegistrationDate DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. Locations Table
CREATE TABLE Locations (
    LocationID INT AUTO_INCREMENT PRIMARY KEY,
    Name VARCHAR(100) NOT NULL,
    Address VARCHAR(255) NOT NULL,
    City VARCHAR(100) NOT NULL,
    State VARCHAR(100) NOT NULL,
    PostalCode VARCHAR(20) NOT NULL
) ENGINE=InnoDB;

-- 3. Promotions Table
CREATE TABLE Promotions (
    PromoID INT AUTO_INCREMENT PRIMARY KEY,
    Code VARCHAR(50) NOT NULL UNIQUE,
    Description TEXT,
    DiscountType ENUM('Percentage', 'FixedAmount') NOT NULL,
    DiscountValue DECIMAL(10,2) NOT NULL,
    StartDate DATETIME NOT NULL,
    EndDate DATETIME NOT NULL
) ENGINE=InnoDB;

-- 4. Events Table (Links to Users and Locations)
CREATE TABLE Events (
    EventID INT AUTO_INCREMENT PRIMARY KEY,
    OrganizerID INT NOT NULL,
    LocationID INT NOT NULL,
    Title VARCHAR(255) NOT NULL,
    Description TEXT,
    EventDateTime DATETIME NOT NULL,
    DurationMinutes INT,
    ApprovalStatus ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    FOREIGN KEY (OrganizerID) REFERENCES Users(UserID) ON DELETE CASCADE,
    FOREIGN KEY (LocationID) REFERENCES Locations(LocationID) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 5. TicketTypes Table (Links to Events)
CREATE TABLE TicketTypes (
    TicketTypeID INT AUTO_INCREMENT PRIMARY KEY,
    EventID INT NOT NULL,
    Name VARCHAR(100) NOT NULL, -- e.g., "VIP", "General Admission"
    Price DECIMAL(10,2) NOT NULL,
    TotalCapacity INT NOT NULL,
    FOREIGN KEY (EventID) REFERENCES Events(EventID) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 6. Orders Table (Links to Users/Customers)
CREATE TABLE Orders (
    OrderID INT AUTO_INCREMENT PRIMARY KEY,
    CustomerID INT NOT NULL,
    OrderDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    TotalAmount DECIMAL(10,2) NOT NULL,
    Status ENUM('Pending', 'Paid', 'Cancelled') DEFAULT 'Pending',
    PaymentMethod VARCHAR(50),
    FOREIGN KEY (CustomerID) REFERENCES Users(UserID) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 7. Tickets Table (The actual ticket instances, links to Orders and TicketTypes)
CREATE TABLE Tickets (
    TicketID INT AUTO_INCREMENT PRIMARY KEY,
    OrderID INT NOT NULL,
    TicketTypeID INT NOT NULL,
    UniqueBarcode VARCHAR(255) NOT NULL UNIQUE,
    PurchasePrice DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (OrderID) REFERENCES Orders(OrderID) ON DELETE CASCADE,
    FOREIGN KEY (TicketTypeID) REFERENCES TicketTypes(TicketTypeID) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 8. EventPromotions (Bridge Table for Many-to-Many relationship)
CREATE TABLE EventPromotions (
    EventID INT NOT NULL,
    PromoID INT NOT NULL,
    PRIMARY KEY (EventID, PromoID),
    FOREIGN KEY (EventID) REFERENCES Events(EventID) ON DELETE CASCADE,
    FOREIGN KEY (PromoID) REFERENCES Promotions(PromoID) ON DELETE CASCADE
) ENGINE=InnoDB;