# Insta-Lite Concept Document

## Project Overview
Insta-Lite is a lightweight Instagram clone built with PHP, MySQL, HTMX, and Alpine.js, designed to run on affordable web hosting. The goal is to demonstrate that modern web applications can be built with traditional technologies without requiring expensive cloud services.

## Technology Stack
- **Backend**: PHP 8.x
- **Database**: MySQL
- **Frontend**: HTMX + Alpine.js
- **UI Framework**: Tailwind CSS
- **File Upload**: Native PHP
- **Authentication**: Session-based

## Key Features

### 1. User System
- Simple registration/login system
- Profile management
- Session-based authentication
- Basic user settings

### 2. Post System
- Image upload with PHP
- Grid and single post views
- Like and comment functionality
- Hashtag support
- Basic search functionality

### 3. Social Features
- Follow/unfollow system
- Basic notifications
- User search
- Post search

### 4. Performance Optimizations
- Image optimization
- Lazy loading
- Caching
- Database query optimization

## Technical Approach

### 1. Database Structure
```sql
-- Users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    profile_picture VARCHAR(255),
    bio TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Posts table
CREATE TABLE posts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    image_path VARCHAR(255),
    caption TEXT,
    location VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Likes table
CREATE TABLE likes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    post_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_like (user_id, post_id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (post_id) REFERENCES posts(id)
);

-- Comments table
CREATE TABLE comments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    post_id INT,
    content TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (post_id) REFERENCES posts(id)
);

-- Follows table
CREATE TABLE follows (
    id INT PRIMARY KEY AUTO_INCREMENT,
    follower_id INT,
    following_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_follow (follower_id, following_id),
    FOREIGN KEY (follower_id) REFERENCES users(id),
    FOREIGN KEY (following_id) REFERENCES users(id)
);
```

### 2. File Structure
```
insta-lite/
├── api/              # API endpoints
├── assets/           # Static assets (images, CSS)
├── config/           # Configuration files
├── includes/         # Common PHP includes
├── templates/        # HTML templates
├── uploads/          # User uploaded files
├── .htaccess         # URL rewriting and security
├── index.php         # Main entry point
└── README.md         # Documentation
```

### 3. Security Measures
- Password hashing (bcrypt)
- CSRF protection
- XSS prevention
- SQL injection prevention
- File upload validation
- Rate limiting
- Session security

## Development Phases

### Phase 1: Core Infrastructure
- Database setup
- Basic PHP server
- Security configuration
- Basic routing
- Error handling

### Phase 2: User System
- Authentication
- Profile management
- Session handling
- Password security

### Phase 3: Post System
- Image upload
- Post creation
- Grid view
- Like system
- Comment system

### Phase 4: Social Features
- Follow system
- Notifications
- Search functionality
- Basic analytics

### Phase 5: Performance & Optimization
- Image optimization
- Caching
- Lazy loading
- Database optimization

## Hosting Requirements
- PHP 8.x
- MySQL
- Apache/Nginx with mod_rewrite
- Basic file upload support
- Minimum 512MB RAM
- 1GB disk space

## Deployment Strategy
- Simple FTP deployment
- Database migration scripts
- Environment configuration
- Backup procedures

## Future Enhancements
- Stories feature
- Direct messaging
- Advanced analytics
- Mobile app
- Cloud integration (optional)
