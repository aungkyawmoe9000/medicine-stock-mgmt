# Medicine Stock Management System

This application is created for the following purposes:
1. To manage medicine data such as distribution, stock-in, stock-out, expiry, and hand-on balance.
2. To monitor live information on the dashboard.
3. To export monthly stock balance reports in Excel/CSV formats.
4. To securely manage user access. Only Admin and Data-Entry roles are used for authentication. Admins can create new users, modify user information, and delete unnecessary users.

## Tech Stack
The following tech stack is used in this project:
- **PHP:** 8.4.21
- **Framework:** Laravel 13
- **Package Manager:** Composer v2.9.8, Node.js v22.22.3
- **Starter Kit:** Laravel Breeze
- **Frontend:** Livewire 3.8 (Volt), Tailwind CSS
- **Database:** SQLite (for local development) / MySQL (for production deployment)

## Features
- The Home page displays only the Application name, Logo, and Login form.
- **Public Registration is Disabled:** New users cannot register themselves. 
- **Role-Based Access:** - **Admin:** Can create/manage internal users. Admins do not perform Data Entry tasks.
  - **Data-Entry:** Cannot manage users. Can only edit their own profile and password.
- **Reporting:** - Dashboards and Monthly Report exporting are available for both roles.
  - Monthly reports can be exported in `.csv` format.
  - Reports are filtered by "Report Date" and "Grant".
  - Retrieved data includes: *Items, Brand, Unit, Expire Date, Location, Project Code, Current Stock Balance, Monthly Average Consumption, Batch, and Will Expire at*.

## Getting Started

To get a local copy up and running, follow these simple steps.

### Prerequisites
Make sure you have PHP 8.4+, Composer, and Node.js installed on your machine.

### Installation

1. Clone the repository:
   ```bash
   git clone [https://github.com/aungkyawmoe9000/medicine-stock-mgmt.git]
   cd medicine-stock-mgmt


2. Install PHP dependencies:
   ```Bash
   composer install

3. Install frontend dependencies:
   ```Bash
   npm install

4. Setup the environment file:
   ```bash
   cp .env.example .env
   (Make sure to update your database credentials in the .env file if you are using MySQL. For SQLite, you can leave the default settings.)

5. Generate the application key:
   ```bash
   php artisan key:generate

6. Run database migrations and seed the initial Admin account:
   ```bash
   php artisan migrate --seed

7. Build frontend assets and start the local server:
   ```bash
   npm run dev
   npm artisan serve

### Usage
Since public registration is disabled, you must use the default admin credentials (generated via Database Seeder) to log in for the first time.
 * Username: woisa-admin
 * Password: 4321W0isa
   (Note: Please change this default password immediately after your first login.)
Once logged in, the Admin can create other standard user accounts for Data Entry via the User Management panel.

### Contributing
Contributions are what make the open-source community such an amazing place to learn, inspire, and create. Any contributions you make are greatly appreciated.
 1. Fork the project
 2. Create your Feature Branch (git checkout -b feature/YourFeature)
 3. Commit your Changes (git commit -m 'add some Amazing Features')
 4. Push to the Branch (git push origin feature/YourFeature)
 5. Open a Pull Request

### License
Distributed under the MIT License. See License file for more information.

### Contact / Acknowledgements
 * Project Link: [https://github.com/aungkyawmoe9000/medicine-stock-mgmt.git]
 * Thanks to Laravel, Livewire, and Tailwind CSS for their awesome frameworks.
