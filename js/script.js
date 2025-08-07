

        // Global Variables
        let currentUser = null;
        let currentLanguage = 'en';
        let currentTheme = 'light';
        let selectedRating = 0;
        let currentCourse = null;

        // Language translations
        const translations = {
            en: {
                home: 'Home',
                courses: 'Courses',
                about: 'About',
                contact: 'Contact',
                joinUs: 'Join Us',
                heroTitle: 'Transform Your Learning Journey',
                heroDescription: 'Discover world-class courses, connect with expert instructors, and unlock your potential with JanEDx\'s comprehensive learning platform.',
                searchPlaceholder: 'Search for courses, topics, or instructors...',
                browseDept: 'Browse by Department',
                allDept: 'All Departments',
                featuredCourses: 'Featured Courses',
                exploreDescription: 'Explore our comprehensive range of courses designed for your success'
            },
            si: {
                home: 'මුල් පිටුව',
                courses: 'පාඨමාලා',
                about: 'අප ගැන',
                contact: 'සම්බන්ධතා',
                joinUs: 'එක්වන්න',
                heroTitle: 'ඔබේ ඉගෙනුම් ගමන වෙනස් කරන්න',
                heroDescription: 'ලෝක මට්ටමේ පාඨමාලා සොයා ගන්න, ප්‍රවීණ උපදේශකයින් සමඟ සම්බන්ධ වන්න, JanEDx හි විස්තීර්ණ ඉගෙනුම් වේදිකාව සමඟ ඔබේ හැකියාව විකාශ කරන්න.',
                searchPlaceholder: 'පාඨමාලා, මාතෘකා හෝ උපදේශකයින් සොයන්න...',
                browseDept: 'අංශය අනුව සාරිත් කරන්න',
                allDept: 'සියලු අංශ',
                featuredCourses: 'විශේෂ පාඨමාලා',
                exploreDescription: 'ඔබේ සාර්ථකත්වය සඳහා නිර්මාණය කර ඇති අපගේ විස්තීර්ණ පාඨමාලා පරාසය ගවේෂණය කරන්න'
            }
        };

        // Theme Toggle
        function toggleTheme() {
            const body = document.body;
            const themeIcon = document.getElementById('theme-icon');
            
            if (currentTheme === 'light') {
                body.setAttribute('data-theme', 'dark');
                themeIcon.className = 'fas fa-sun';
                currentTheme = 'dark';
            } else {
                body.setAttribute('data-theme', 'light');
                themeIcon.className = 'fas fa-moon';
                currentTheme = 'light';
            }
        }

        // Language Toggle
        function toggleLanguage() {
            const langText = document.getElementById('lang-text');
            currentLanguage = currentLanguage === 'en' ? 'si' : 'en';
            langText.textContent = currentLanguage === 'en' ? 'සිං' : 'EN';
            updateLanguage();
        }

        function updateLanguage() {
            const lang = translations[currentLanguage];
            
            // Update navigation
            document.querySelectorAll('.nav-link').forEach((link, index) => {
                const keys = ['home', 'courses', 'about', 'contact'];
                if (keys[index]) {
                    link.textContent = lang[keys[index]];
                }
            });

            // Update hero section
            document.querySelector('.hero-title').textContent = lang.heroTitle;
            document.querySelector('.hero-description').textContent = lang.heroDescription;
            document.getElementById('main-search').placeholder = lang.searchPlaceholder;
            document.getElementById('course-search').placeholder = lang.searchPlaceholder;

            // Update other sections
            document.querySelector('.department-filters h3').textContent = lang.browseDept;
            document.querySelector('.courses-section h2').textContent = lang.featuredCourses;
            document.querySelector('.courses-section .lead').textContent = lang.exploreDescription;
        }

        // Search Functions
        function searchCourses() {
            const query = document.getElementById('main-search').value.toLowerCase();
            filterCoursesByQuery(query);
        }

        function filterCourses() {
            const query = document.getElementById('course-search').value.toLowerCase();
            filterCoursesByQuery(query);
        }

        function filterCoursesByQuery(query) {
            const courses = document.querySelectorAll('.course-item');
            courses.forEach(course => {
                const title = course.querySelector('.course-title').textContent.toLowerCase();
                const description = course.querySelector('.course-description').textContent.toLowerCase();
                
                if (title.includes(query) || description.includes(query)) {
                    course.style.display = 'block';
                } else {
                    course.style.display = 'none';
                }
            });
        }

        // Department Filter
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                // Remove active class from all buttons
                document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                // Add active class to clicked button
                this.classList.add('active');
                
                const dept = this.getAttribute('data-dept');
                const courses = document.querySelectorAll('.course-item');
                
                courses.forEach(course => {
                    if (dept === 'all' || course.getAttribute('data-dept') === dept) {
                        course.style.display = 'block';
                    } else {
                        course.style.display = 'none';
                    }
                });
            });
        });

        // User Authentication
        function joinUs() {
            showRegister();
        }

        function showLogin() {
            const registerModal = bootstrap.Modal.getInstance(document.getElementById('registerModal'));
            if (registerModal) registerModal.hide();
            
            const loginModal = new bootstrap.Modal(document.getElementById('loginModal'));
            loginModal.show();
        }

        function showRegister() {
            const loginModal = bootstrap.Modal.getInstance(document.getElementById('loginModal'));
            if (loginModal) loginModal.hide();
            
            const registerModal = new bootstrap.Modal(document.getElementById('registerModal'));
            registerModal.show();
        }

        function googleLogin() {
            // Simulate Google login
            alert('Google login integration would be implemented here with OAuth 2.0');
            // In real implementation, you would integrate with Google OAuth API
        }

        // Course Enrollment
        function enrollCourse(courseId) {
            if (!currentUser) {
                showLogin();
                currentCourse = courseId;
                return;
            }
            
            // Show rating modal for enrolled users
            showRatingModal(courseId);
        }

        function showRatingModal(courseId) {
            currentCourse = courseId;
            const modal = new bootstrap.Modal(document.getElementById('ratingModal'));
            document.getElementById('course-name-rating').textContent = getCourseNameById(courseId);
            modal.show();
        }

        function getCourseNameById(courseId) {
            const courseNames = {
                'electronics-engineering': 'Electronics Engineering',
                'electrical-engineering': 'Electrical Engineering',
                'civil-engineering': 'Civil Engineering',
                'business-admin': 'Business Administration',
                'tourism-hospitality': 'Tourism & Hospitality',
                'fullstack-dev': 'Full Stack Development',
                'english-comm': 'Advanced English Communication'
            };
            return courseNames[courseId] || 'Course';
        }

        // Rating System
        document.querySelectorAll('.rating-star').forEach(star => {
            star.addEventListener('click', function() {
                selectedRating = parseInt(this.getAttribute('data-rating'));
                updateStarDisplay();
            });
            
            star.addEventListener('mouseover', function() {
                const rating = parseInt(this.getAttribute('data-rating'));
                highlightStars(rating);
            });
        });

        document.querySelector('.rating-stars').addEventListener('mouseleave', function() {
            updateStarDisplay();
        });

        function highlightStars(rating) {
            document.querySelectorAll('.rating-star').forEach((star, index) => {
                if (index < rating) {
                    star.style.color = '#fbbf24';
                } else {
                    star.style.color = '#d1d5db';
                }
            });
        }

        function updateStarDisplay() {
            highlightStars(selectedRating);
        }

        // Explore Games
        function exploreGames() {
            // In a real implementation, this would redirect to a games page
            window.open('games.html', '_blank');
        }

        // Form Submissions
        document.getElementById('login-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const email = document.getElementById('login-email').value;
            const password = document.getElementById('login-password').value;
            
            // Simulate login process
            currentUser = { email: email, name: email.split('@')[0] };
            
            const modal = bootstrap.Modal.getInstance(document.getElementById('loginModal'));
            modal.hide();
            
            alert('Login successful! Welcome to JanEDx');
            
            if (currentCourse) {
                showRatingModal(currentCourse);
            }
        });

        document.getElementById('register-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const name = document.getElementById('reg-name').value;
            const email = document.getElementById('reg-email').value;
            const password = document.getElementById('reg-password').value;
            const confirmPassword = document.getElementById('reg-confirm-password').value;
            
            if (password !== confirmPassword) {
                alert('Passwords do not match!');
                return;
            }
            
            // Simulate registration process
            currentUser = { email: email, name: name };
            
            const modal = bootstrap.Modal.getInstance(document.getElementById('registerModal'));
            modal.hide();
            
            alert('Registration successful! Welcome to JanEDx');
        });

        document.getElementById('rating-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const comment = document.getElementById('rating-comment').value;
            
            if (selectedRating === 0) {
                alert('Please select a rating!');
                return;
            }
            
            // Simulate rating submission
            alert(`Thank you for rating this course ${selectedRating} stars!`);
            
            const modal = bootstrap.Modal.getInstance(document.getElementById('ratingModal'));
            modal.hide();
            
            // Reset form
            selectedRating = 0;
            document.getElementById('rating-comment').value = '';
            updateStarDisplay();
        });

        document.getElementById('contact-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const name = document.getElementById('name').value;
            const email = document.getElementById('email').value;
            const subject = document.getElementById('subject').value;
            const message = document.getElementById('message').value;
            
            // Simulate form submission
            alert('Thank you for your message! We will get back to you soon.');
            
            // Reset form
            this.reset();
        });

        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Initialize rating stars
        updateStarDisplay();

        // Add entrance animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('fade-in');
                }
            });
        }, observerOptions);

        // Observe course cards for animation
        document.querySelectorAll('.course-card').forEach(card => {
            observer.observe(card);
        });
    