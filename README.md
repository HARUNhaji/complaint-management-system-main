# Complaint Management System (PHP & MySQL)

## Project Description

The Complaint Management System is a web-based application designed to streamline the process of submitting, tracking, and resolving complaints in an educational institution. The system provides a centralized platform where students can submit their concerns and complaints, and administrators can efficiently manage, review, and resolve these issues.

**Who uses it:**
- **Students**: Submit complaints about exams, teachers, subjects, classes, or other institutional matters
- **Administrators**: Review, manage, and resolve complaints submitted by students

**Why it exists:**
- To provide a transparent and efficient complaint handling process
- To ensure all student concerns are properly documented and addressed
- To maintain institutional accountability and improve student satisfaction

## Features

### Student Features
- User registration and authentication
- Profile management (update personal information and profile picture)
- Submit complaints with detailed descriptions
- Track complaint status in real-time
- Receive notifications about complaint updates
- View complaint history
- Responsive design for mobile and desktop access

### Admin Features
- Comprehensive dashboard with complaint analytics
- Manage and review all submitted complaints
- Update complaint status (pending → in review → resolved)
- Add comments to complaints for communication
- Manage student accounts (view, activate/deactivate)
- Generate reports and analytics
- Notification management
- Audit logs for system activities

### System & Security Features
- Role-based access control (admin/student)
- Secure password hashing
- Prepared statements to prevent SQL injection
- File upload validation and security
- Session-based authentication
- Responsive design for all devices
- Clean, professional UI/UX

## Technologies Used

- **PHP**: Server-side scripting language
- **MySQL**: Database management system
- **HTML5**: Markup language
- **CSS3**: Styling and layout
- **Bootstrap 5**: Responsive front-end framework
- **JavaScript**: Client-side interactivity
- **XAMPP/WAMP**: Local development environment

## System Workflow

1. **Student Registration**: New students register with their details
2. **Complaint Submission**: Students submit complaints with category, description, and priority
3. **Admin Review**: Administrators review new complaints in their dashboard
4. **Status Update**: Admins update complaint status as they work on resolution
5. **Resolution**: Admins mark complaints as resolved when addressed
6. **Notification**: Students receive notifications about their complaint status
7. **Tracking**: Students can track all their complaints in one place

## Admin Login Credentials (TEST ONLY)

For testing purposes, use the following credentials:

- **Email**: admin@cms.com
- **Password**: admin123

## Installation Guide


   ```

2. **Import the database**:
   - Open phpMyAdmin in your local server (XAMPP/WAMP)
   - Create a new database named `complaint_management_system`
   - Import the SQL file: `database/complaint_management_system.sql`

3. **Configure database connection**:
   - Edit the file `config/db.php`
   - Update database credentials if needed (default: localhost, root, empty password)

4. **Run the project**:
   - Place the project folder in your web server's document root (htdocs for XAMPP)
   - Start your web server (Apache and MySQL)
   - Access the application via: `http://localhost/complaint-management-system/`

5. **Login**:
   - For admin access: Use the credentials mentioned above
   - For student access: Register a new account or use existing student credentials

## Security Notes

- Passwords are securely hashed using PHP's password_hash() function
- Role-based access control prevents unauthorized access to admin features
- All database queries use prepared statements to prevent SQL injection
- File uploads are validated for type and size to prevent malicious uploads
- Session management ensures secure user authentication
- Input sanitization is implemented throughout the application

## File Structure

```
complaint-management-system/
│
├── admin/
│   ├── dashboard.php
│   ├── manage_users.php
│   ├── manage_complaints.php
│   ├── reports.php
│   ├── view_complaints.php
│   └── sidebar.php
│
├── student/
│   ├── dashboard.php
│   ├── submit_complaint.php
│   ├── my_complaints.php
│   ├── profile.php
│   ├── edit_profile.php
│   └── sidebar.php
│
├── auth/
│   ├── login.php
│   ├── register.php
│   ├── logout.php
│   └── check_login.php
│
├── config/
│   └── db.php
│
├── includes/
│   ├── header.php
│   ├── footer.php
│   ├── mark_notifications_read.php
│   └── auth.php
│
├── assets/
│   ├── uploads/
│   │   ├── profile_images/
│   │   └── complaints/
│   ├── css/
│   ├── js/
│   └── images/
│
├── database/
│   └── complaint_management_system.sql
│
├── index.php
└── README.md
```

## Database Schema

The system uses the following tables:
- `users`: Stores user information (students and admins)
- `complaints`: Stores complaint details and status
- `complaint_comments`: Stores comments on complaints
- `notifications`: Stores notification messages
- `audit_logs`: Stores system activity logs
- `complaint_status_history`: Tracks complaint status changes

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Support

If you encounter any issues or have questions about the system, please open an issue in the repository.

## Project Team:
Name: Harun MOHAMED MOHAMUD ID: IT22129157
Name: Maryama Abdullaahi Hussein
name Ali mohamud ahmed suubiye IT 22129067
class:bit29b