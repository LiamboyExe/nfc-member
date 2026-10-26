-- ============================================================
-- Multi-Branch Membership Management System Demo
-- Database: membership_demo
-- Run this file in phpMyAdmin > Import, or via MySQL CLI
-- ============================================================

CREATE DATABASE IF NOT EXISTS membership_demo
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE membership_demo;

-- Branches
CREATE TABLE IF NOT EXISTS branches (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  name       VARCHAR(100) NOT NULL,
  location   VARCHAR(150) NOT NULL,
  manager    VARCHAR(100) NOT NULL,
  phone      VARCHAR(30)  NOT NULL,
  email      VARCHAR(100) NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Admins (demo only - plain text passwords, NOT for production)
CREATE TABLE IF NOT EXISTS admins (
  id        INT AUTO_INCREMENT PRIMARY KEY,
  username  VARCHAR(60)  NOT NULL UNIQUE,
  password  VARCHAR(255) NOT NULL,
  role      ENUM('main','local') NOT NULL DEFAULT 'local',
  branch_id INT NULL,
  full_name VARCHAR(100) NOT NULL,
  FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Members
CREATE TABLE IF NOT EXISTS members (
  id               INT AUTO_INCREMENT PRIMARY KEY,
  branch_id        INT NOT NULL,
  first_name       VARCHAR(60)  NOT NULL,
  last_name        VARCHAR(60)  NOT NULL,
  email            VARCHAR(100) NOT NULL,
  phone            VARCHAR(30)  NOT NULL,
  address          TEXT,
  membership_type  ENUM('Regular','Premium','Student','Senior') NOT NULL DEFAULT 'Regular',
  status           ENUM('Active','Inactive','Pending') NOT NULL DEFAULT 'Active',
  join_date        DATE NOT NULL,
  profile_slug     VARCHAR(80)  NOT NULL UNIQUE,
  approved         TINYINT(1) NOT NULL DEFAULT 1,
  created_at       DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- NFC Cards
CREATE TABLE IF NOT EXISTS nfc_cards (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  member_id   INT NOT NULL UNIQUE,
  card_uid    VARCHAR(40)  NOT NULL,
  profile_url VARCHAR(255) NOT NULL,
  card_status ENUM('Active','Inactive','Lost') NOT NULL DEFAULT 'Active',
  issued_date DATE NOT NULL,
  notes       TEXT,
  FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- SEED DATA
-- ============================================================

INSERT INTO branches (name, location, manager, phone, email) VALUES
('North Star Branch', '123 Aurora Ave, Northville',  'Margaret Santos',   '(02) 8123-4567',  'north@membersdemo.local'),
('South Bay Branch',  '45 Coral St, Southport',       'Robert dela Cruz',   '(032) 234-5678', 'south@membersdemo.local');

INSERT INTO admins (username, password, role, branch_id, full_name) VALUES
('admin',       'admin123', 'main',  NULL, 'Main Administrator'),
('north_admin', 'north123', 'local', 1,    'North Branch Admin'),
('south_admin', 'south123', 'local', 2,    'South Branch Admin');

INSERT INTO members (branch_id,first_name,last_name,email,phone,address,membership_type,status,join_date,profile_slug,approved) VALUES
(1,'Alice',   'Reyes',     'alice.reyes@mail.demo','09171234001','12 Maple St, Northville',     'Premium','Active',  '2023-01-15','alice-reyes-b1m1',   1),
(1,'Carlos',  'Mendoza',   'carlos.m@mail.demo',   '09181234002','34 Oak Ave, Northville',      'Regular','Active',  '2023-02-20','carlos-mendoza-b1m2',1),
(1,'Diana',   'Villanueva','diana.v@mail.demo',    '09191234003','56 Pine Rd, Northville',      'Student','Active',  '2023-03-10','diana-v-b1m3',       1),
(1,'Eduardo', 'Lim',       'eduardo.lim@mail.demo','09201234004','78 Elm Blvd, Northville',     'Regular','Inactive','2023-04-05','eduardo-lim-b1m4',   1),
(1,'Fatima',  'Castro',    'fatima.c@mail.demo',   '09211234005','90 Birch Ln, Northville',     'Senior', 'Active',  '2023-05-18','fatima-c-b1m5',      1),
(1,'George',  'Navarro',   'george.n@mail.demo',   '09221234006','101 Cedar Way, Northville',   'Premium','Active',  '2023-06-22','george-n-b1m6',      1),
(1,'Hannah',  'Torres',    'hannah.t@mail.demo',   '09231234007','202 Walnut Dr, Northville',   'Student','Pending', '2023-07-30','hannah-t-b1m7',      0),
(1,'Ivan',    'Flores',    'ivan.f@mail.demo',     '09241234008','303 Ash St, Northville',      'Regular','Active',  '2023-08-14','ivan-f-b1m8',        1),
(1,'Julia',   'Santos',    'julia.s@mail.demo',    '09251234009','404 Poplar Ave, Northville',  'Premium','Active',  '2023-09-09','julia-s-b1m9',       1),
(1,'Kevin',   'Aquino',    'kevin.a@mail.demo',    '09261234010','505 Spruce Blvd, Northville', 'Regular','Active',  '2023-10-01','kevin-a-b1m10',      1),
(2,'Laura',   'Cruz',      'laura.c@mail.demo',    '09271234011','11 Seaside Rd, Southport',    'Regular','Active',  '2023-01-28','laura-c-b2m1',       1),
(2,'Marco',   'Bautista',  'marco.b@mail.demo',    '09281234012','22 Harbor St, Southport',     'Senior', 'Active',  '2023-02-14','marco-b-b2m2',       1),
(2,'Nina',    'Dela Rosa', 'nina.dr@mail.demo',    '09291234013','33 Bay Ave, Southport',       'Premium','Active',  '2023-03-22','nina-dr-b2m3',       1),
(2,'Oscar',   'Ramos',     'oscar.r@mail.demo',    '09301234014','44 Wave Blvd, Southport',     'Regular','Inactive','2023-04-18','oscar-r-b2m4',       1),
(2,'Patricia','Gomez',     'patricia.g@mail.demo', '09311234015','55 Tide Ln, Southport',       'Student','Active',  '2023-05-05','patricia-g-b2m5',    1),
(2,'Quincy',  'Macaraeg',  'quincy.m@mail.demo',   '09321234016','66 Coral Ct, Southport',      'Regular','Active',  '2023-06-11','quincy-m-b2m6',      1),
(2,'Rachel',  'Fernandez', 'rachel.f@mail.demo',   '09331234017','77 Shell Way, Southport',     'Premium','Pending', '2023-07-23','rachel-f-b2m7',      0),
(2,'Samuel',  'Dizon',     'samuel.d@mail.demo',   '09341234018','88 Reef Rd, Southport',       'Regular','Active',  '2023-08-30','samuel-d-b2m8',      1),
(2,'Teresa',  'Aguilar',   'teresa.a@mail.demo',   '09351234019','99 Lagoon Dr, Southport',     'Senior', 'Active',  '2023-09-15','teresa-a-b2m9',      1),
(2,'Ulysses', 'Pascual',   'ulysses.p@mail.demo',  '09361234020','100 Dune Blvd, Southport',    'Student','Active',  '2023-10-20','ulysses-p-b2m10',    1);

INSERT INTO nfc_cards (member_id,card_uid,profile_url,card_status,issued_date,notes) VALUES
(1, 'NFC-A1B2C3D4','http://localhost/membership-demo/profile.php?slug=alice-reyes-b1m1',   'Active',  '2023-01-20','Primary card'),
(2, 'NFC-E5F6A7B8','http://localhost/membership-demo/profile.php?slug=carlos-mendoza-b1m2','Active',  '2023-02-25','Primary card'),
(3, 'NFC-C9D0E1F2','http://localhost/membership-demo/profile.php?slug=diana-v-b1m3',       'Active',  '2023-03-15','Primary card'),
(4, 'NFC-G3H4I5J6','http://localhost/membership-demo/profile.php?slug=eduardo-lim-b1m4',   'Inactive','2023-04-10','Member deactivated'),
(5, 'NFC-K7L8M9N0','http://localhost/membership-demo/profile.php?slug=fatima-c-b1m5',      'Active',  '2023-05-22','Primary card'),
(11,'NFC-O1P2Q3R4','http://localhost/membership-demo/profile.php?slug=laura-c-b2m1',       'Active',  '2023-02-01','Primary card'),
(12,'NFC-S5T6U7V8','http://localhost/membership-demo/profile.php?slug=marco-b-b2m2',       'Active',  '2023-02-18','Primary card'),
(13,'NFC-W9X0Y1Z2','http://localhost/membership-demo/profile.php?slug=nina-dr-b2m3',       'Active',  '2023-03-25','Primary card'),
(14,'NFC-A3B4C5D6','http://localhost/membership-demo/profile.php?slug=oscar-r-b2m4',       'Lost',    '2023-04-20','Reported lost'),
(15,'NFC-E7F8G9H0','http://localhost/membership-demo/profile.php?slug=patricia-g-b2m5',    'Active',  '2023-05-10','Primary card');
