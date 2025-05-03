# Insta-Lite

A lightweight Instagram clone built with PHP, MySQL, and modern front-end technologies.

## Security

### Database Configuration
1. The `config/database.php` file contains your actual database credentials and should never be committed to the repository
2. We've included `config/database.php.example` as a template
3. When setting up the application for the first time:
   - Copy `config/database.php.example` to `config/database.php`
   - Update the database credentials with your own values
4. The actual `config/database.php` file is already in `.gitignore` to prevent accidental commits

### Environment Variables
- Create a `.env` file for environment-specific configurations
- Add `.env` to your `.gitignore`

### Security Best Practices
1. Always use prepared statements for database queries
2. Never expose sensitive credentials in your code
3. Use HTTPS in production
4. Regularly update dependencies
5. Implement proper error handling without exposing sensitive information

## Directory Structure

The application follows a clean, organized directory structure:

```
insta-lite/
├── public/            # Public-facing files
│   ├── assets/        # CSS, JS, and images
│   ├── index.php      # Main entry point
│   └── .htaccess      # URL rewriting rules
├── src/               # Application source code
│   ├── auth/          # Authentication-related files
│   ├── config/        # Configuration files
│   ├── debug/         # Debugging tools
│   ├── email/         # Email-related functionality
│   ├── pages/         # Main application pages
│   └── router.php     # URL routing system
└── vendor/            # Composer dependencies
```

## Setup

1. Clone the repository
2. Copy `src/config/database.php.example` to `src/config/database.php` (if available)
3. Update database credentials in `src/config/database.php`
4. Start the PHP development server with the public directory as the document root:
   ```bash
   php -S localhost:8000 -t public
   ```
5. Access the application at `http://localhost:8000`

## Debug Tools

The application includes several debugging tools to help with development:

- `/debug/mail` - View and manage user verification emails
- `/debug/session` - Debug session information
- `/debug/verification` - Test the verification process
- `/debug/generate-token` - Generate new verification tokens
- `/debug/db` - Test database connection
