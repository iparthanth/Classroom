# E-Learning Class Management System

## Overview
A simple, clean e-learning platform built for educational institutions. This system allows teachers to create courses, manage assignments, use an interactive whiteboard, and chat with students in real-time.

## Features

### For Students
- Browse and enroll in courses
- Submit assignments with file uploads
- View grades and feedback
- Participate in real-time chat
- View teacher's whiteboard sessions

### For Teachers  
- Create and manage courses
- Create assignments with due dates
- Grade student submissions
- Interactive whiteboard for drawing/teaching
- Real-time chat with students
- View online student presence

### For Administrators
- User management (activate/deactivate)
- System statistics dashboard
- Course oversight

## Technology Stack
- **Frontend**: HTML, CSS, JavaScript, Tailwind CSS
- **Backend**: PHP (Vanilla)
- **Database**: MySQL
- **Server**: Apache (via XAMPP/WAMP)

## Quick Setup

### Prerequisites
- XAMPP or WAMP server
- PHP 7.0+
- MySQL 5.6+

### Installation

1. **Start your server**
   ```bash
   # Start XAMPP or WAMP
   # Ensure Apache and MySQL are running
   ```

2. **Setup Database**
   - Open phpMyAdmin: `http://localhost/phpmyadmin`
   - Create database: `elearning_system`
   - Import: `database/schema.sql`

3. **Configure Database** (if needed)
   - Edit `config/database.php`
   - Update database credentials if different from defaults

4. **Access the Application**
   - Visit: `http://localhost/Edu-Home-Virtual-Classroom/`
   - Default accounts (password: "password"):
     - Admin: `admin@elearning.com`
     - Teacher: `teacher@elearning.com` 
     - Student: `student@elearning.com`

## Project Structure
```
├── admin/           # Admin dashboard
├── config/          # Database configuration
├── database/        # SQL schema
├── includes/        # PHP utilities (auth, functions)
├── student/         # Student pages
├── teacher/         # Teacher pages
├── uploads/         # File uploads directory
├── index.php        # Landing page
├── chat.php         # Real-time chat
├── whiteboard.php   # Interactive whiteboard
└── dashboard.php    # Role-based routing
```

## Usage

1. **Getting Started**
   - Register as a student or teacher
   - Teachers can create courses immediately
   - Students can browse and enroll in available courses

2. **Creating Content**
   - Teachers: Create courses → Add assignments → Use whiteboard/chat
   - Students: Enroll in courses → Submit assignments → Participate in discussions

3. **Real-time Features**
   - Whiteboard: Teachers draw, students view (auto-refresh)
   - Chat: Polling-based real-time messaging with presence tracking

## Development Notes

This is a clean, student-friendly implementation focusing on:
- Simple PHP without frameworks
- Tailwind CSS for modern UI
- Minimal dependencies
- Clear code structure
- Essential e-learning features only

Built as an educational project demonstrating core web development concepts and e-learning system architecture.

