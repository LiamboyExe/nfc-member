# 🏛️ Temple Street — Multi-Branch Membership Management Demo

A beginner-friendly local demo system built with **PHP, MySQL, HTML, CSS, and JavaScript** — no frameworks required.

---

## 📁 File Structure

```
membership-demo/
├── index.php          → Homepage + Admin Login
├── dashboard.php      → Stats dashboard
├── members.php        → Member management (add/edit/delete/search)
├── branches.php       → Branch management
├── nfc.php            → NFC card assignment & simulation
├── profile.php        → Public member profile page
├── logout.php         → Ends session
├── config.php         → Database connection (PDO) + session helpers
├── database.sql       → SQL to create DB, tables, and seed data
├── assets/
│   ├── css/style.css  → All styles (dark theme, responsive)
│   └── js/app.js      → Shared JavaScript (modals, clipboard, NFC sim)
├── includes/
│   └── layout.php     → Shared sidebar/topbar layout functions
└── README.md          → This file
```

---

## ⚙️ XAMPP Setup (Step by Step)

### Step 1 — Start XAMPP Services
1. Open **XAMPP Control Panel**
2. Click **Start** next to **Apache**
3. Click **Start** next to **MySQL**

### Step 2 — Copy the Project
Make sure the project folder is at:
```
C:\xampp\htdocs\membership-demo\
```

### Step 3 — Import the Database
1. Open your browser and go to: `http://localhost/phpmyadmin`
2. Click **Import** in the top menu
3. Click **Choose File** → select `database.sql` from this folder
4. Click **Go** / **Import**

That will create the `membership_demo` database with all tables and 20 sample members.

### Step 4 — Open the App
Go to: **http://localhost/membership-demo/**

---

## 🔑 Demo Login Credentials

| Role        | Username      | Password   | Access                    |
|-------------|---------------|------------|---------------------------|
| Main Admin  | `admin`       | `admin123` | All branches + management |
| Local Admin | `north_admin` | `north123` | North Star Branch only    |
| Local Admin | `south_admin` | `south123` | South Bay Branch only     |

> ⚠️ **Demo Only:** These are plain-text passwords in the database. This is intentional for simplicity. Do NOT use this approach in any real application.

---

## 🌐 Key URLs

| Page                  | URL                                                   |
|-----------------------|-------------------------------------------------------|
| Login / Home          | http://localhost/membership-demo/                     |
| Dashboard             | http://localhost/membership-demo/dashboard.php        |
| Members               | http://localhost/membership-demo/members.php          |
| Branches              | http://localhost/membership-demo/branches.php         |
| NFC Cards             | http://localhost/membership-demo/nfc.php              |
| Sample Public Profile | http://localhost/membership-demo/profile.php?slug=alice-reyes-b1m1 |

---

## 🎯 Features

- **Dashboard** — total/active members, branches, NFC card stats with branch breakdown chart
- **Member Management** — paginated table with search, filter by status/branch, modal add/edit forms, delete confirmation
- **Branch Management** — branch cards showing member counts, add/edit/delete (Main Admin only)
- **NFC Card Management** — visual NFC card display, assign card to member, auto-fill profile URL, copy URL, simulate NFC write, update card status
- **Public Profile Page** — accessible at `/profile.php?slug=...`, shows approved member info only
- **Role Simulation** — Main Admin sees all branches; Local Admin sees only their branch (filter-based, not real auth)

---

## ⚠️ Important Notes

- This is a **local demo** — not suitable for production use.
- **Role simulation is NOT secure authentication.** Session variables are set at login and filter data client-side. Anyone who modifies their session can bypass it.
- All member data is **fictional** — do not enter real personal information.
- Actual **NFC writing is not implemented**. The "Simulate Write" button is a visual demo only.
- To write a URL to a real NFC card, copy the profile URL and use an app like **NFC Tools** (Android/iOS).

---

## 🛠️ Tech Stack

| Technology | Purpose |
|------------|---------|
| PHP 7.4+   | Server-side logic, PDO database access |
| MySQL/MariaDB (XAMPP) | Data storage |
| HTML5      | Page structure |
| CSS3       | Styling (dark theme, responsive grid, animations) |
| Vanilla JS | Modals, clipboard, NFC simulation, live search |
| Google Fonts (Inter) | Typography |

---

*Built for educational and demonstration purposes only.*

