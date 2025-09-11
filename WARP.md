# WARP.md

This file provides guidance to WARP (warp.dev) when working with code in this repository.

## Project Overview

**EduHome - Virtual Classroom System** is a comprehensive e-learning platform built with PHP and MySQL. The system provides online education capabilities including course management, video tutorials, quizzes, user authentication, and an integrated bookshop.

## Architecture Overview

### Core System Structure

The application follows a modular architecture with three main components:

1. **Frontend Landing Page** (`index.php`) - Main entry point with sign-up/sign-in functionality
2. **Admin/Teacher Portal** (`uniquedeveloper/`) - Course and content management system  
3. **E-commerce Module** (`grocery store/`) - Integrated bookshop for educational materials

### Database Architecture

The system uses MySQL with the following core entities:
- **Users Management**: `users`, `imgupload` tables for authentication and profiles
- **Course System**: `courses`, `video_tutorials`, `questions` tables for educational content
- **Assessment**: `quiz_results`, `commentsection` tables for student interaction
- **E-commerce**: Separate database for the bookshop functionality

### Key Components

- **CourseManager Class** (`uniquedeveloper/classes/CourseManager.php`) - Central class for course operations
- **Authentication System** (`includes/` directory) - Session-based user management
- **Video Tutorial System** - Embedded video content with progress tracking
- **Quiz System** (`online_quize/`) - MCQ-based assessments with scoring

## Development Environment Setup

### Prerequisites
- **XAMPP** or **WAMP** server (Apache + MySQL + PHP)
- **PHP 7.0+** 
- **MySQL 5.6+**

### Database Setup Commands

```bash
# Start XAMPP/WAMP services
# Navigate to phpMyAdmin at localhost/phpmyadmin

# Create main database
CREATE DATABASE loginsystemtut;

# Import database schema
mysql -u root -p loginsystemtut < setup_database.sql

# For additional features, import:
mysql -u root -p uniquedeveloper < uniquedeveloper.sql
```

### Local Development Server

```bash
# Ensure XAMPP/WAMP is running
# Place project in htdocs/www directory
# Access via: http://localhost/Edu-Home-Virtual-Classroom/
```

## Common Development Commands

### Database Operations

```bash
# Import initial schema
mysql -u root -p loginsystemtut < setup_database.sql

# Import additional tables for quiz system
mysql -u root -p uniquedeveloper < uniquedeveloper.sql

# Create video progress tracking
mysql -u root -p loginsystemtut < create_video_progress.sql

# Add video rating system
mysql -u root -p loginsystemtut < create_video_ratings.sql
```

### Testing User Accounts

```php
// Default admin access
// Login: uniquedeveloper/login.php
// Use credentials created through registration system
```

### File Structure Navigation

```bash
# Main application entry
index.php

# Admin/Teacher dashboard
uniquedeveloper/index.php

# Course management
uniquedeveloper/classes/CourseManager.php

# Authentication handlers
includes/login.inc.php
includes/signup1.inc.php

# E-commerce bookshop
grocery store/shop.php
```

## Architecture Insights

### Session Management
- PHP sessions handle user authentication across the platform
- Session validation occurs in `header.php` and admin sections
- Logout functionality clears sessions and redirects appropriately

### Course Content Flow
1. **Admin Creates Course** → `CourseManager::addCourse()`
2. **Add Video Content** → `CourseManager::addVideo()`
3. **Student Access** → Video player with comment system
4. **Assessment** → Quiz system tracks results in `quiz_results`

### Data Flow Patterns
- **Input Validation**: All forms use mysqli_real_escape_string for SQL injection prevention
- **Error Handling**: PHP error parameters passed via GET for form validation feedback
- **File Uploads**: Profile images and course materials handled through dedicated upload scripts

### Security Considerations
- Database credentials stored in `includes/dbh.inc.php` (should be environment variables in production)
- Password hashing implemented for user authentication
- Form validation on both client and server side

### Multi-Module Integration
The system integrates three distinct modules:
1. **Main Platform**: Course delivery and user management
2. **Admin Portal**: Content creation and management tools  
3. **E-commerce**: Book purchasing with separate user system

This modular approach allows independent development while maintaining data consistency through shared database connections.

## Development Workflow

### Adding New Courses
1. Access admin panel at `uniquedeveloper/login.php`
2. Use CourseManager class methods for CRUD operations
3. Video content stored as URLs with metadata in `video_tutorials` table

### User Management
- Registration creates entries in both `users` and `imgupload` tables
- Profile management handles image uploads and user data updates
- Session persistence maintains login state across modules

### Testing Quiz Functionality
1. Navigate to `online_quize/quizhome.php`
2. Questions managed through `questions` table with answer validation
3. Results automatically calculated and stored for progress tracking
