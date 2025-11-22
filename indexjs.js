// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, initializing...');
    
    // Track scroll position
    let lastScrollTop = 0;

    // Add scroll event listener
    window.addEventListener('scroll', function() {
        try {
            const header = document.querySelector('.header');
            const hero = document.querySelector('.hero');
            const searchInput = document.querySelector('.search-bar input');
            const logo = document.querySelector('.logo img');
            
            if (!header || !hero || !searchInput || !logo) return;
            
            // Check if we're on mobile or tablet with hamburger menu (965px or below)
            const isMobileOrTablet = window.innerWidth <= 965;
            
            // Skip scroll effects on mobile/tablet to maintain responsive design
            if (isMobileOrTablet) {
                return;
            }
            
            const currentScroll = window.pageYOffset || document.documentElement.scrollTop;
            const heroTop = hero.offsetTop;
            
            if (currentScroll > lastScrollTop) {
                // Scrolling down - only apply on desktop
                header.style.background = '#ffffff';
                header.style.boxShadow = '0 2px 10px rgba(0, 0, 0, 0.1)';
                header.style.height = '100px';
                logo.style.height = '60px';
                logo.style.width = '80px';
                searchInput.style.backgroundColor = '#f5f5f5';
                searchInput.style.borderColor = '#e0e0e0';
                document.body.style.paddingTop = '100px';
            } else if (currentScroll <= heroTop) {
                // Scrolling up and reached hero section - only apply on desktop
                header.style.background = 'radial-gradient(#e6eeff 50%, #d4e0ff 100%)';
                header.style.boxShadow = 'none';
                header.style.height = '170px';
                logo.style.height = '150px';
                logo.style.width = '200px';
                searchInput.style.backgroundColor = '#e6eeff';
                searchInput.style.borderColor = '#000000';
                document.body.style.paddingTop = '170px';
            }
            
            lastScrollTop = currentScroll <= 0 ? 0 : currentScroll;
        } catch (error) {
            console.log('Scroll error:', error);
        }
    });

    // Carousel functionality
    let currentSlideIndex = 0;
    let slides = [];
    let dots = [];
    let slideInterval;

    // Function to show specific slide
    function showSlide(index) {
        try {
            // Hide all slides
            slides.forEach(slide => {
                slide.classList.remove('active', 'prev', 'next');
            });
            
            // Remove active class from all dots
            dots.forEach(dot => {
                dot.classList.remove('active');
            });
            
            // Show current slide
            if (slides[index]) {
                slides[index].classList.add('active');
            }
            
            // Activate corresponding dot
            if (dots[index]) {
                dots[index].classList.add('active');
            }
            
            currentSlideIndex = index;
        } catch (error) {
            console.log('Show slide error:', error);
        }
    }

    // Function to go to next slide
    function nextSlide() {
        const nextIndex = (currentSlideIndex + 1) % slides.length;
        showSlide(nextIndex);
    }

    // Function to go to previous slide
    function prevSlide() {
        const prevIndex = (currentSlideIndex - 1 + slides.length) % slides.length;
        showSlide(prevIndex);
    }

    // Function to handle dot clicks
    function currentSlide(index) {
        const targetIndex = index - 1; // Adjust for 0-based indexing
        showSlide(targetIndex);
        resetInterval();
    }

    // Function to start auto-sliding
    function startAutoSlide() {
        if (slides.length > 1) {
            slideInterval = setInterval(nextSlide, 4000);
        }
    }

    // Function to reset interval
    function resetInterval() {
        clearInterval(slideInterval);
        startAutoSlide();
    }

    // Initialize carousel
    function initCarousel() {
        try {
            // Get all slides and dots
            slides = document.querySelectorAll('.ad');
            dots = document.querySelectorAll('.dot');
            
            console.log('Found slides:', slides.length);
            console.log('Found dots:', dots.length);
            
            if (slides.length > 0) {
                // First, hide all slides
                slides.forEach(slide => {
                    slide.classList.remove('active', 'prev', 'next');
                });
                
                // Remove active from all dots
                dots.forEach(dot => {
                    dot.classList.remove('active');
                });
                
                // Show first slide
                showSlide(0);
                console.log('First slide should be visible now');
                
                // Start auto-sliding
                startAutoSlide();
                
                // Add click event listeners to dots
                dots.forEach((dot, index) => {
                    dot.addEventListener('click', () => {
                        showSlide(index);
                        resetInterval();
                    });
                });
                
                // Pause auto-sliding on hover
                const offerSection = document.querySelector('.offer');
                if (offerSection) {
                    offerSection.addEventListener('mouseenter', () => {
                        clearInterval(slideInterval);
                    });
                    
                    offerSection.addEventListener('mouseleave', () => {
                        startAutoSlide();
                    });
                }
            } else {
                console.log('No slides found!');
            }
        } catch (error) {
            console.log('Carousel init error:', error);
        }
    }

    // Initialize carousel
    initCarousel();

    // Add keyboard navigation
    document.addEventListener('keydown', function(e) {
        if (e.key === 'ArrowLeft') {
            prevSlide();
            resetInterval();
        } else if (e.key === 'ArrowRight') {
            nextSlide();
            resetInterval();
        }
    });

    // Add dropdown delay functionality for user profile
    const userProfiles = document.querySelectorAll('.modern-user-profile');
    
    userProfiles.forEach(profile => {
        let timeoutId;
        
        profile.addEventListener('mouseenter', function() {
            clearTimeout(timeoutId);
            const dropdown = this.querySelector('.modern-profile-dropdown');
            if (dropdown) {
                dropdown.style.display = 'block';
                setTimeout(() => {
                    dropdown.style.opacity = '1';
                    dropdown.style.transform = 'translateY(0)';
                }, 10);
            }
        });
        
        profile.addEventListener('mouseleave', function() {
            timeoutId = setTimeout(() => {
                const dropdown = this.querySelector('.modern-profile-dropdown');
                if (dropdown) {
                    dropdown.style.opacity = '0';
                    dropdown.style.transform = 'translateY(10px)';
                    setTimeout(() => {
                        dropdown.style.display = 'none';
                    }, 300);
                }
            }, 300); // 300ms delay before closing
        });
        
        // Add click functionality for profile link
        const profileLink = profile.querySelector('.modern-profile-link');
        if (profileLink) {
            const toggleDropdown = function(e) {
                e.preventDefault();
                e.stopPropagation();
                const dropdown = profile.querySelector('.modern-profile-dropdown');
                if (dropdown) {
                    const isVisible = dropdown.style.display === 'block';
                    
                    // Close all other dropdowns first
                    document.querySelectorAll('.modern-profile-dropdown').forEach(d => {
                        if (d !== dropdown) {
                            d.style.display = 'none';
                            d.style.opacity = '0';
                        }
                    });
                    
                    if (isVisible) {
                        // Hide dropdown
                        dropdown.style.opacity = '0';
                        setTimeout(() => {
                            dropdown.style.display = 'none';
                        }, 300);
                    } else {
                        // Show dropdown
                        dropdown.style.display = 'block';
                        dropdown.style.position = 'absolute';
                        
                        // Center on mobile, right-align on desktop
                        if (window.innerWidth <= 769) {
                            dropdown.style.left = '50%';
                            dropdown.style.right = 'auto';
                            dropdown.style.transform = 'translateX(-50%)';
                        } else {
                            dropdown.style.left = 'auto';
                            dropdown.style.right = '0';
                            dropdown.style.transform = 'none';
                        }
                        
                        dropdown.style.top = 'calc(100% + 5px)';
                        dropdown.style.bottom = 'auto';
                        dropdown.style.zIndex = '1001';
                        dropdown.style.minWidth = '200px';
                        dropdown.style.maxWidth = '280px';
                        setTimeout(() => {
                            dropdown.style.opacity = '1';
                        }, 10);
                    }
                }
            };
            
            profileLink.addEventListener('click', toggleDropdown);
            profileLink.addEventListener('touchstart', toggleDropdown, { passive: false });
        }
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.modern-user-profile')) {
            document.querySelectorAll('.modern-profile-dropdown').forEach(dropdown => {
                dropdown.style.opacity = '0';
                setTimeout(() => {
                    dropdown.style.display = 'none';
                }, 300);
            });
        }
    });

    // Mobile Menu Toggle Functionality
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    const nav = document.querySelector('.nav');
    
    if (mobileMenuToggle && nav) {
        mobileMenuToggle.addEventListener('click', function() {
            // Toggle the active class on navigation
            nav.classList.toggle('active');
            
            // Toggle hamburger animation
            this.classList.toggle('active');
            
            // Animate hamburger lines
            const spans = this.querySelectorAll('span');
            if (this.classList.contains('active')) {
                spans[0].style.transform = 'rotate(45deg) translate(5px, 5px)';
                spans[1].style.opacity = '0';
                spans[2].style.transform = 'rotate(-45deg) translate(7px, -6px)';
            } else {
                spans[0].style.transform = 'none';
                spans[1].style.opacity = '1';
                spans[2].style.transform = 'none';
            }
        });
        
        // Close mobile menu when clicking on nav links
        const navLinks = nav.querySelectorAll('a');
        navLinks.forEach(link => {
            link.addEventListener('click', () => {
                nav.classList.remove('active');
                mobileMenuToggle.classList.remove('active');
                
                // Reset hamburger animation
                const spans = mobileMenuToggle.querySelectorAll('span');
                spans[0].style.transform = 'none';
                spans[1].style.opacity = '1';
                spans[2].style.transform = 'none';
            });
        });
        
        // Close mobile menu when clicking outside
        document.addEventListener('click', function(event) {
            if (!mobileMenuToggle.contains(event.target) && !nav.contains(event.target)) {
                nav.classList.remove('active');
                mobileMenuToggle.classList.remove('active');
                
                // Reset hamburger animation
                const spans = mobileMenuToggle.querySelectorAll('span');
                spans[0].style.transform = 'none';
                spans[1].style.opacity = '1';
                spans[2].style.transform = 'none';
            }
        });
        
        // Handle window resize - close mobile menu on larger screens
        window.addEventListener('resize', function() {
            if (window.innerWidth > 965) {
                nav.classList.remove('active');
                mobileMenuToggle.classList.remove('active');
                
                // Reset hamburger animation
                const spans = mobileMenuToggle.querySelectorAll('span');
                spans[0].style.transform = 'none';
                spans[1].style.opacity = '1';
                spans[2].style.transform = 'none';
            }
        });
    }

    console.log('Initialization complete');
});