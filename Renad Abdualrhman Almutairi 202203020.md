# Network Asset Discrepancy System

A web-based system for verifying and tracking discrepancies between
recorded network asset data (pipe length, diameter, material) and
actual field survey measurements — developed as a graduation project
during a training program at the Saudi National Water Company (NWC).

## Problem

Recorded network asset data (in the system) often does not match
what field technicians find during physical inspection. These
discrepancies affect maintenance planning, cost estimation, and the
accuracy of any system that relies on this data — including efforts
to reduce non-revenue water (network losses).

## Features

- **Role-based access**: Technician, Engineer, and Admin, each with
  different permissions, enforced on the server side.
- **Automated discrepancy calculation**: A Python script computes a
  weighted severity score comparing recorded vs. surveyed data.
- **Unified dashboard**: Quick-stat cards, a severity chart, an
  interactive map, and a sortable discrepancy table — all powered by
  internal REST API endpoints.
- **Review workflow**: Engineers can review each discrepancy and
  update its status (Pending / In Review / Resolved).

## Tech Stack

| Layer | Technology |
|---|---|
| Frontend | HTML5, CSS3, Bootstrap 5, JavaScript, Chart.js, Leaflet.js |
| Backend | PHP (procedural, mysqli with prepared statements) |
| Calculation engine | Python 3 |
| Database | MySQL |
| APIs | Internal REST endpoints (JSON) consumed by the frontend |

## Project Structure

```
asset-system/
├── db_connection.php          # Shared database connection
├── login.php                  # Login + role-based redirect
├── logout.php
├── dashboard.php              # Unified dashboard (engineer/admin)
├── add_survey.php             # Technician: submit a field survey
├── pending_assets.php         # Technician: assets awaiting survey
├── manage_users.php           # Admin: user management
├── discrepancy_details.php    # Engineer: review one discrepancy
├── calculate_discrepancy.py   # Severity score calculation engine
└── api/
    ├── get_discrepancies.php  # Chart data
    ├── get_summary_stats.php  # Aggregate statistics
    └── get_map_data.php       # Asset locations + severity
```

## Database Design

See `schema_network_assets.sql` for the full schema (4 core tables:
`users`, `network_assets`, `field_surveys`, `discrepancies`).

Design diagrams (ERD, Use Case, Sequence) are included in the
project report.

## Setup (Local Development with XAMPP)

1. Start Apache and MySQL in XAMPP.
2. Import `schema_network_assets.sql` via phpMyAdmin.
3. Run `setup_map_columns.php` once (adds map coordinates).
4. Run `create_test_user.php` and `seed_demo_data.php` once to add
   sample data.
5. Open `login.php` in your browser.

## Author

Graduation project — [your name], [your university], [term/year].
