// ==================== CORRECTED SCRIPT.JS (with event listeners) ====================
const API_BASE = 'api/';

// ==================== STUDENT DASHBOARD ====================
async function loadStudentDashboard() {
    const userId = localStorage.getItem('userId');
    if (!userId) return;
    const userName = localStorage.getItem('userName') || 'Student';
    document.getElementById('welcomeName').innerText = userName;
    document.getElementById('userName').innerText = userName;
    await loadEnrolledCourses();
    await loadUpcomingAssignments();
    await loadQuizzes();
    await loadRecommendations();
    await loadStats();
}

async function loadEnrolledCourses() {
    const container = document.getElementById('enrolledCourses');
    if (!container) return;
    try {
        const res = await fetch(API_BASE + 'enroll.php');
        const data = await res.json();
        const courses = data.courses || [];
        if (courses.length) {
            container.innerHTML = courses.map(course => `
                <div class="course-progress-item">
                    <img src="${course.image_url || 'https://via.placeholder.com/80'}" alt="${course.title}">
                    <div class="course-progress-info">
                        <h3>${course.title}</h3>
                        <p>${course.instructor}</p>
                        <div class="progress-bar"><div class="progress" style="width: ${course.progress || 0}%"></div></div>
                        <span class="progress-text">${course.progress || 0}% Complete</span>
                    </div>
                    <button onclick="continueCourse(${course.id})" class="btn btn-primary">Continue</button>
                </div>
            `).join('');
        } else {
            container.innerHTML = '<p class="no-results">You haven\'t enrolled in any courses yet. <a href="index.html#courses">Browse Courses</a></p>';
        }
    } catch (err) {
        container.innerHTML = '<p class="no-results">Error loading courses.</p>';
    }
}

async function loadUpcomingAssignments() {
    const container = document.getElementById('assignmentList');
    if (!container) return;
    try {
        const res = await fetch(API_BASE + 'assignments.php');
        const assignments = await res.json();
        const pending = assignments.filter(a => !a.submission_status);
        if (pending.length) {
            container.innerHTML = pending.map(ass => `
                <div class="assignment-item">
                    <div class="assignment-info">
                        <h4>${ass.title}</h4>
                        <p>${ass.course_title}</p>
                    </div>
                    <span class="due-date">Due: ${new Date(ass.due_date).toLocaleDateString()}</span>
                    <span class="badge pending">Pending</span>
                </div>
            `).join('');
        } else {
            container.innerHTML = '<p class="no-results">No pending assignments 🎉</p>';
        }
    } catch (err) {
        container.innerHTML = '<p class="no-results">Error loading assignments.</p>';
    }
}

async function loadQuizzes() {
    const container = document.getElementById('quizList');
    if (!container) return;
    try {
        const res = await fetch(API_BASE + 'quizzes.php');
        const quizzes = await res.json();
        if (quizzes.length) {
            container.innerHTML = quizzes.map(quiz => `
                <div class="quiz-item">
                    <div class="quiz-info">
                        <h4>${quiz.question.substring(0, 50)}...</h4>
                        <p>${quiz.attempted ? (quiz.is_correct ? '✓ Correct' : '✗ Incorrect') : 'Not attempted'}</p>
                    </div>
                    <span class="badge ${quiz.attempted ? 'completed' : 'upcoming'}">${quiz.attempted ? 'Completed' : 'New'}</span>
                </div>
            `).join('');
        } else {
            container.innerHTML = '<p class="no-results">No quizzes available for your courses.</p>';
        }
    } catch (err) {
        container.innerHTML = '<p class="no-results">Error loading quizzes.</p>';
    }
}

async function loadRecommendations() {
    const container = document.getElementById('recommendedCourses');
    if (!container) return;
    try {
        const allRes = await fetch(API_BASE + 'courses.php');
        const allCourses = await allRes.json();
        const enrolledRes = await fetch(API_BASE + 'enroll.php');
        const enrolledData = await enrolledRes.json();
        const enrolledIds = (enrolledData.courses || []).map(c => c.id);
        const recommended = allCourses.filter(c => !enrolledIds.includes(c.id)).slice(0, 3);
        if (recommended.length) {
            container.innerHTML = recommended.map(course => `
                <div class="recommended-card">
                    <img src="${course.image_url || 'https://via.placeholder.com/300x140'}" alt="${course.title}">
                    <h4>${course.title}</h4>
                    <p>${course.instructor}</p>
                    <button onclick="enrollCourse(${course.id})" class="btn btn-primary btn-small">Enroll</button>
                </div>
            `).join('');
        } else {
            container.innerHTML = '<p class="no-results">You\'re enrolled in all courses! 🎓</p>';
        }
    } catch (err) {
        container.innerHTML = '<p class="no-results">Error loading recommendations.</p>';
    }
}

async function loadStats() {
    const statsGrid = document.getElementById('statsGrid');
    if (!statsGrid) return;
    try {
        const enrolledRes = await fetch(API_BASE + 'enroll.php');
        const enrolledData = await enrolledRes.json();
        const enrolledCount = enrolledData.courses?.length || 0;
        const assignRes = await fetch(API_BASE + 'assignments.php');
        const assignments = await assignRes.json();
        const pendingCount = assignments.filter(a => !a.submission_status).length;
        statsGrid.innerHTML = `
            <div class="stat-card"><i class="fas fa-book-open stat-icon"></i><div class="stat-info"><h3>Enrolled Courses</h3><p class="stat-number">${enrolledCount}</p></div></div>
            <div class="stat-card"><i class="fas fa-tasks stat-icon"></i><div class="stat-info"><h3>Pending Assignments</h3><p class="stat-number">${pendingCount}</p></div></div>
           
        `;
    } catch (err) {}
}

async function unenrollCourse(courseId) {
    if (!confirm('Are you sure you want to unenroll? Your progress will be lost.')) return;
    const res = await fetch('api/enroll.php', {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ course_id: courseId })
    });
    const data = await res.json();
    if (data.success) {
        alert('You have been unenrolled from the course.');
        location.reload(); // refresh the page to update the list
    } else {
        alert('Failed to unenroll: ' + (data.message || 'Unknown error'));
    }
}
// ==================== TEACHER DASHBOARD ====================
async function loadTeacherDashboard() {
    const userId = localStorage.getItem('userId');
    if (!userId) return;
    const userName = localStorage.getItem('userName') || 'Teacher';
    document.getElementById('welcomeName').innerText = userName;
    document.getElementById('userName').innerText = userName;
    await loadTeacherStats();
    await loadTeacherCourses();
    await loadTeacherAssignments();   // <-- ADD THIS LINE
}

async function loadTeacherStats() {
    const statsGrid = document.getElementById('teacherStats');
    if (!statsGrid) return;
    try {
        const userId = localStorage.getItem('userId');
        const res = await fetch(API_BASE + `courses.php?teacher_id=${userId}`);
        const courses = await res.json();
        const totalCourses = courses.length;
        statsGrid.innerHTML = `
            <div class="stat-card"><i class="fas fa-book-open stat-icon"></i><div class="stat-info"><h3>Total Courses</h3><p class="stat-number">${totalCourses}</p></div></div>
            <div class="stat-card"><i class="fas fa-chart-line stat-icon"></i><div class="stat-info"><h3>Revenue</h3><p class="stat-number">$${totalCourses * 49}</p></div></div>
        `;
    } catch (err) {
        statsGrid.innerHTML = '<p class="no-results">Error loading stats.</p>';
    }
}

async function loadTeacherCourses() {
    const tableBody = document.getElementById('teacherCourses');
    if (!tableBody) return;
    try {
        const userId = localStorage.getItem('userId');
        const res = await fetch(API_BASE + `courses.php?teacher_id=${userId}`);
        const courses = await res.json();
        if (courses.length) {
           tableBody.innerHTML = courses.map(course => `
                <tr>
                    <td><div class="course-info-cell"><img src="${course.image_url || 'https://via.placeholder.com/40'}" alt="">${course.title}</div></td>
                    <td>${course.students_count || 0}</td>
                   
                    <td>${new Date(course.created_at).toLocaleDateString()}</td>
                    <td>
                        <button class="btn-icon" onclick="editCourse(${course.id})"><i class="fas fa-edit"></i></button>
                        <button class="btn-icon" onclick="deleteCourse(${course.id})" style="color:#dc3545;"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
            `).join('');
        } else {
            tableBody.innerHTML = '<tr><td colspan="5">No courses added yet. <a href="add-course.html">Add your first course</a></td></tr>';
        }
    } catch (err) {
        tableBody.innerHTML = '<tr><td colspan="5">Error loading courses.</td></tr>';
    }
}

async function deleteCourse(courseId) {
    if (!confirm('Delete this course? All assignments, quizzes, videos, and enrollments will be removed.')) return;
    const res = await fetch(API_BASE + 'courses.php', {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ course_id: courseId })
    });
    const data = await res.json();
    if (data.success) {
        alert('Course deleted');
        location.reload();
    } else {
        alert('Failed: ' + (data.message || 'Unknown error'));
    }
}



function editCourse(courseId) {
    alert('Edit feature coming soon');
}

async function loadTeacherAssignments() {
    const tbody = document.getElementById('teacherAssignmentsList');
    if (!tbody) return;
    try {
        const res = await fetch(API_BASE + 'assignments.php');
        const assignments = await res.json();
        if (assignments.length) {
            tbody.innerHTML = assignments.map(ass => `
                <tr>
                    <td>${ass.title}</td>
                    <td>${ass.course_title}</td>
                    <td>${new Date(ass.due_date).toLocaleDateString()}</td>
                    <td>
                        <button class="btn-icon" onclick="deleteAssignment(${ass.id})" style="color:#dc3545;">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `).join('');
        } else {
            tbody.innerHTML = '<tr><td colspan="4">No assignments found.</td></tr>';
        }
    } catch(err) {
        tbody.innerHTML = '<tr><td colspan="4">Error loading assignments.</td></tr>';
    }
}

async function deleteAssignment(assignmentId) {
    if (!confirm('Delete this assignment? All student submissions will also be removed.')) return;
    const res = await fetch(API_BASE + 'assignments.php', {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ assignment_id: assignmentId })
    });
    const data = await res.json();
    if (data.success) {
        alert('Assignment deleted');
        loadTeacherAssignments(); // refresh list
    } else {
        alert('Failed: ' + (data.message || 'Unknown error'));
    }
}

// ==================== ENROLLMENT ====================
async function enrollCourse(courseId) {
    const isLoggedIn = localStorage.getItem('isLoggedIn') === 'true';
    if (!isLoggedIn) {
        alert('Please login first');
        window.location.href = 'login.html';
        return;
    }
    try {
        const res = await fetch(API_BASE + 'enroll.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ course_id: courseId })
        });
        const data = await res.json();
        if (data.success) {
            alert('Enrolled successfully!');
            window.location.href = 'student-dashboard.html';
        } else {
            alert(data.message || 'Enrollment failed');
        }
    } catch (err) {
        alert('Server error');
    }
}

function continueCourse(courseId) {
    window.location.href = `watch-videos.html?course_id=${courseId}`;
}

// ==================== TEACHER ACTIONS ====================
async function addCourse() {
    const title = document.getElementById('courseTitle').value;
    const instructor = document.getElementById('courseInstructor').value;
    const price = document.getElementById('coursePrice').value;
    const image = document.getElementById('courseImage').value;
    if (!title || !instructor || !price) {
        alert('Please fill all fields');
        return;
    }
    try {
        const res = await fetch(API_BASE + 'courses.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ title, instructor, price, image })
        });
        const data = await res.json();
        if (data.success) {
            alert('Course added successfully!');
            window.location.href = 'teacher-dashboard.html';
        } else {
            alert(data.error || 'Failed to add course');
        }
    } catch (err) {
        alert('Server error: ' + err.message);
    }
}

async function uploadVideo() {
    const title = document.getElementById('videoTitle').value;
    const courseName = document.getElementById('videoCourse').value;
    const url = document.getElementById('videoUrl').value;
    if (!title || !courseName || !url) {
        alert('Please fill all fields');
        return;
    }
    try {
        const coursesRes = await fetch(API_BASE + 'courses.php');
        const courses = await coursesRes.json();
        const course = courses.find(c => c.title.toLowerCase() === courseName.toLowerCase());
        if (!course) {
            alert('Course not found. Please check the course name.');
            return;
        }
        const res = await fetch(API_BASE + 'videos.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ course_id: course.id, title, youtube_url: url })
        });
        const data = await res.json();
        if (data.success) {
            alert('Video uploaded successfully!');
            window.location.href = 'teacher-dashboard.html';
        } else {
            alert(data.error || 'Upload failed');
        }
    } catch (err) {
        alert('Server error: ' + err.message);
    }
}

async function createAssignment() {
    const title = document.getElementById('assignmentTitle').value;
    const courseName = document.getElementById('assignmentCourse').value;
    const dueDate = document.getElementById('assignmentDue').value;
    const description = document.getElementById('assignmentDesc').value;
    if (!title || !courseName || !dueDate) {
        alert('Please fill all required fields');
        return;
    }
    try {
        const coursesRes = await fetch(API_BASE + 'courses.php');
        const courses = await coursesRes.json();
        const course = courses.find(c => c.title.toLowerCase() === courseName.toLowerCase());
        if (!course) {
            alert('Course not found. Please check the course name.');
            return;
        }
        const res = await fetch(API_BASE + 'assignments.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'create', course_id: course.id, title, description, due_date: dueDate })
        });
        const data = await res.json();
        if (data.success) {
            alert('Assignment created successfully!');
            window.location.href = 'teacher-dashboard.html';
        } else {
            alert(data.error || 'Creation failed');
        }
    } catch (err) {
        alert('Server error: ' + err.message);
    }
}

async function createQuiz() {
    alert("Button clicked! Function is running.");
    const courseName = document.getElementById('quizCourse').value;
    const question = document.getElementById('quizQuestion').value;
    const opt1 = document.getElementById('option1').value;
    const opt2 = document.getElementById('option2').value;
    const opt3 = document.getElementById('option3').value;
    const opt4 = document.getElementById('option4').value;
    const correct = document.getElementById('correctAnswer').value;

    if (!courseName || !question || !opt1 || !opt2 || !opt3 || !opt4) {
        alert('Please fill all fields');
        return;
    }

    try {
        // Fetch all courses to find the ID by name
        const coursesRes = await fetch(API_BASE + 'courses.php');
        const courses = await coursesRes.json();
        const course = courses.find(c => c.title.toLowerCase() === courseName.toLowerCase());
        if (!course) {
            alert('Course not found. Please check the course name.');
            return;
        }

        const res = await fetch(API_BASE + 'quizzes.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'create',
                course_id: course.id,
                question,
                opt1, opt2, opt3, opt4,
                correct: parseInt(correct)
            })
        });
        const data = await res.json();
        if (data.success) {
            alert('Quiz created successfully!');
            window.location.href = 'teacher-dashboard.html';
        } else {
            alert(data.error || 'Creation failed');
        }
    } catch (err) {
        console.error(err);
        alert('Server error: ' + err.message);
    }
}

// ==================== AUTHENTICATION ====================
async function handleLogin(event) {
    event.preventDefault();
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    const userType = document.querySelector('input[name="userType"]:checked').value;
    try {
        const captcha = document.getElementById('captcha') ? document.getElementById('captcha').value : '';
        const res = await fetch(API_BASE + 'auth.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'login', email, password, role: userType, captcha: captcha })
        });
        const data = await res.json();
        if (data.success) {
            localStorage.setItem('isLoggedIn', 'true');
            localStorage.setItem('userType', data.role);
            localStorage.setItem('userName', data.fullname || 'User');
            localStorage.setItem('userId', data.user_id);
            window.location.href = data.role === 'student' ? 'student-dashboard.html' : 'teacher-dashboard.html';
        } else {
            alert(data.message || 'Invalid credentials');
        }
    } catch (err) {
        alert('Server error');
    }
}

async function handleRegister(event) {
    event.preventDefault();
    const fullname = document.getElementById('fullName').value;
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    const confirm = document.getElementById('confirmPassword').value;
    const userType = document.querySelector('input[name="userType"]:checked').value;
    const terms = document.getElementById('terms').checked;
    if (password !== confirm) {
        alert('Passwords do not match');
        return;
    }
    if (!terms) {
        alert('Accept terms');
        return;
    }
    try {
        const res = await fetch(API_BASE + 'auth.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'register', fullname, email, password, role: userType })
        });
        const data = await res.json();
        if (data.success) {
            alert('Registration successful!');
            window.location.href = data.role === 'student' ? 'student-dashboard.html' : 'teacher-dashboard.html';
        } else {
            alert(data.message || 'Registration failed');
        }
    } catch (err) {
        alert('Server error');
    }
}

async function logout() {
    await fetch(API_BASE + 'auth.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'logout' })
    });
    localStorage.clear();
    window.location.href = 'index.html';
}

// ==================== EVENT LISTENERS ====================
function setupEventListeners() {
    const loginForm = document.getElementById('loginForm');
    if (loginForm) loginForm.addEventListener('submit', handleLogin);
    
    const registerForm = document.getElementById('registerForm');
    if (registerForm) registerForm.addEventListener('submit', handleRegister);
    
    const newsletterForm = document.getElementById('newsletterForm');
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', (e) => {
            e.preventDefault();
            alert('Subscribed!');
        });
    }
}

// ==================== INITIALIZATION ====================
document.addEventListener('DOMContentLoaded', async () => {
    // Attach event listeners FIRST 
    setupEventListeners();
    
    // Check login status
    try {
        const res = await fetch(API_BASE + 'auth.php');
        const data = await res.json();
        if (data.logged_in) {
            localStorage.setItem('isLoggedIn', 'true');
            localStorage.setItem('userType', data.role);
            localStorage.setItem('userName', data.fullname);
            localStorage.setItem('userId', data.user_id);
        } else {
            localStorage.clear();
        }
    } catch (err) {}

    const page = window.location.pathname.split('/').pop();
    if (page === 'student-dashboard.html') {
        loadStudentDashboard();
    } else if (page === 'teacher-dashboard.html') {
        loadTeacherDashboard();
    } else if (page === 'index.html' || page === '') {
        const container = document.getElementById('courses-container');
        if (container) {
            const res = await fetch(API_BASE + 'courses.php');
            const courses = await res.json();
            container.innerHTML = courses.map(course => `
                <div class="course-card">
                    <img src="${course.image_url || 'https://via.placeholder.com/300x200'}" alt="${course.title}">
                    <div class="course-info">
                        <h3>${course.title}</h3>
                        <p>By ${course.instructor}</p>
                        <p>Price: $${course.price}</p>
                        <button onclick="enrollCourse(${course.id})" class="btn btn-primary">Enroll Now</button>
                    </div>
                </div>
            `).join('');
        }
    }
});

// Make functions global
window.enrollCourse = enrollCourse;
window.continueCourse = continueCourse;
window.logout = logout;
window.handleLogin = handleLogin;
window.handleRegister = handleRegister;
window.addCourse = addCourse;
window.uploadVideo = uploadVideo;
window.createAssignment = createAssignment;
window.createQuiz = createQuiz;
window.editCourse = editCourse;
window.unenrollCourse = unenrollCourse;