# Pet Care Platform

A comprehensive web application connecting pet owners with reliable pet care service providers. This platform facilitates booking pet care services, managing pets, tracking appointments, and sharing educational resources for pet owners.

## Features

### For Pet Owners
- User registration and profile management
- Pet profiles creation and management
- Service browsing and booking
- Appointment scheduling and tracking
- Payment processing
- Service provider ratings and reviews
- Educational content access

### For Service Providers
- Business profile management
- Service listing and management
- Appointment scheduling
- Booking management
- Client communication
- Review management
- Analytics dashboard

### General Features
- Responsive design
- User authentication and authorization
- Real-time notifications
- Search and filtering
- Rating and review system
- Educational articles and resources

## Installation

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache, Nginx, etc.)
- Composer (optional, for dependency management)

### Steps

1. **Clone the repository**
   ```
   git clone https://github.com/Sukhpreet-Sandhu/Pet-Care-Services-Platform.git
   ```

2. **Database Setup**
   - Create a MySQL database named `pet_care_platform`
   - Import the database schema from `database/schema.sql`
   - (Optional) Import sample data from `database/sample_data.sql` for testing

3. **Configuration**
   - Open `includes/config.php`
   - Update the database connection credentials:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_USER', 'your_db_username');
     define('DB_PASS', 'your_db_password');
     define('DB_NAME', 'pet_care_platform');
     ```
   - Update the application URL:
     ```php
     define('APP_URL', 'http://your-domain/pet_care_platform');
     ```

4. **File Permissions**
   - Ensure the `uploads` directory has write permissions:
     ```
     chmod -R 755 uploads/
     ```

5. **Web Server Configuration**
   - Point your web server document root to the project directory
   - Ensure the server has proper PHP configurations

## Usage

### User Types

1. **Pet Owners**
   - Register an account as a Pet Owner
   - Add pets to your profile
   - Browse services and book appointments
   - Track appointments and make payments
   - Leave reviews for service providers

2. **Service Providers**
   - Register an account as a Service Provider
   - Create a business profile
   - Add services you offer
   - Manage booking requests
   - Track appointments and payments
   - View analytics and feedback

3. **Administrators**
   - Access admin dashboard at `/admin/dashboard.php`
   - Manage users, services, and bookings
   - Approve service providers
   - Manage educational content

### Quick Setup

For a quick demo setup, you can use the following utilities:
- `activate_all_accounts.php` - Activates all user accounts
- `fix_services.php` - Fixes service status issues (admin access required)

## Directory Structure

```
pet_care_platform/
├── admin/               # Administrator area
├── assets/              # Static assets (CSS, JS, images)
│   ├── css/
│   ├── js/
│   └── images/
├── includes/            # Core PHP includes
│   ├── config.php       # Configuration file
│   ├── db.php           # Database connection
│   ├── functions.php    # Helper functions
│   ├── header.php       # Common header
│   └── footer.php       # Common footer
├── pet_owner/           # Pet owner area
├── service_provider/    # Service provider area
├── educational/         # Educational content
├── services/            # Services listing and details
├── uploads/             # File uploads
└── index.php            # Main entry point
```

## Technologies Used

- PHP 7.4+
- MySQL 5.7+
- HTML5
- CSS3
- JavaScript
- Bootstrap 5
- Font Awesome
- jQuery

## Security Features

- Password hashing with PHP's password_hash()
- Prepared statements for database queries
- Input validation and sanitization
- Session-based authentication
- Role-based access control

## Contributing

1. Fork the repository
2. Create a new branch (`git checkout -b feature-branch`)
3. Commit your changes (`git commit -am 'Add new feature'`)
4. Push to the branch (`git push origin feature-branch`)
5. Create a new Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Support

For support, please contact support@petcareplatform.com
