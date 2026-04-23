# E-Learning Platform

A complete, fully functional e-learning web application where **students** can enroll in courses, watch video lectures, submit assignments, attempt quizzes, and chat with instructors.  
**Teachers** can create courses, upload YouTube video lessons, post assignments, design quizzes, and reply to student messages.

> Built with PHP, MySQL, HTML/CSS/JS, and a responsive modern UI.

---

# Features

### For Students
- Secure login/registration (with CAPTCHA protection)
- Browse and enroll in courses
- Watch video lectures (YouTube embedded)
- Track course progress
- Submit assignments (file upload)
- Attempt auto-graded quizzes
- Chat with instructors (real-time polling)
- View recommended courses

### For Teachers
- Create and manage courses
- Upload video lectures (YouTube URL)
- Create assignments with due dates
- Build quizzes (multiple choice)
- Review student submissions
- Reply to student messages in course chats

### General
- Role‑based dashboard (Student / Teacher)
- Modern responsive design (works on mobile & desktop)
- Session management & logout

---

## 🛠️ Tech Stack

| Category       | Technology                          |
|----------------|-------------------------------------|
| Frontend       | HTML5, CSS3, JavaScript             |
| Backend        | PHP (no framework, vanilla PDO)     |
| Database       | MySQL                               |
| Libraries      | FontAwesome, Google Fonts            |
| HTTP Requests  | Fetch API (async/await)              |
| Authentication | PHP sessions + bcrypt password hash  |
| Security       | CAPTCHA image, prepared statements   |

---

## 🗂️ Project Structure
```plain
📂 elearning-platform/
│
├── 📄 index.html
├── 📄 student-dashboard.html
├── 📄 teacher-dashboard.html
├── 📄 login.html
├── 📄 register.html
├── 📄 my-courses.html
├── 📄 assignments.html
├── 📄 quiz.html
├── 📄 watch-videos.html
├── 📄 student-chat.html
├── 📄 teacher-chat.html
├── 📄 teacher-chat-conversation.html
├── 📄 add-course.html
├── 📄 upload-video.html
├── 📄 create-assignment.html
├── 📄 create-quiz.html
├── 📄 style.css
├── 📄 script.js
├── 📄 captcha.php
│
├── 📂 api/
│   ├── 📄 config.php
│   ├── 📄 auth.php
│   ├── 📄 courses.php
│   ├── 📄 enroll.php
│   ├── 📄 assignments.php
│   ├── 📄 quizzes.php
│   ├── 📄 videos.php
│   └── 📄 chat.php
│
└── 📂 uploads/
    └── 📂 assignments/
---

## 🚀 Installation (Local Setup)

### 1. Requirements
- A local PHP server: [XAMPP](https://www.apachefriends.org/) / [WAMP](https://www.wampserver.com/) / [MAMP](https://www.mamp.info/)
- PHP ≥ 7.4 (with PDO MySQL extension)
- MySQL 5.7 or higher

### 2. Steps

1. **Clone or download** this repository into your server's document root.  
   For XAMPP: `C:\xampp\htdocs\elearning-platform\`

2. **Create a database** named `elearning_db` (or any name you prefer).

3. **Import the database schema**  
   Use the following SQL (copy and run in phpMyAdmin or MySQL shell):

```sql
-- Create tables (run this entire block)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('student','teacher') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    instructor VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) DEFAULT 0,
    image_url VARCHAR(500),
    category VARCHAR(100),
    description TEXT,
    teacher_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    course_id INT NOT NULL,
    progress INT DEFAULT 0,
    enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

CREATE TABLE videos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    youtube_url VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

CREATE TABLE assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    due_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

CREATE TABLE submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT NOT NULL,
    student_id INT NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    status ENUM('submitted','late') DEFAULT 'submitted',
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (assignment_id) REFERENCES assignments(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE quizzes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    question TEXT NOT NULL,
    option1 VARCHAR(255),
    option2 VARCHAR(255),
    option3 VARCHAR(255),
    option4 VARCHAR(255),
    correct_answer INT(1) NOT NULL,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

CREATE TABLE quiz_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quiz_id INT NOT NULL,
    student_id INT NOT NULL,
    selected_answer INT(1),
    is_correct TINYINT(1) DEFAULT 0,
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    course_id INT NOT NULL,
    message TEXT NOT NULL,
    `timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

4 Configure database connection 
    Copy api/config-sample.php to api/config.php (or create api/config.php).
    Update the credentials inside:

      php
      <?php
      $host = 'localhost';
      $dbname = 'elearning_db';
      $username = 'your MySQL username';     // your MySQL username
      $password = 'your MySQL password';         // your MySQL password
      // ... rest of the code (PDO connection)

5 Make sure uploads folder is writable

      Create a folder uploads/assignments/ inside your project root.
      Give write permissions (on Windows it's usually automatic; on Linux chmod 777).

6 Start your local server

       Open XAMPP Control Panel → Start Apache and MySQL.

7 Access the website

       Open browser → http://localhost/elearning-platform/
