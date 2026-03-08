document.addEventListener('DOMContentLoaded', function () {
    const menuTrigger = document.querySelector('.header__menu-trigger');
    const nav = document.querySelector('.header__nav');

    if (menuTrigger && nav) {
        menuTrigger.addEventListener('click', function () {
            this.classList.toggle('active');
            nav.classList.toggle('active');
        });
    }

    // Scroll Animations (Fade-up)
    const observerOptions = {
        root: null,
        rootMargin: '0px',
        threshold: 0.15
    };

    const scrollObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target); // Animate only once
            }
        });
    }, observerOptions);

    const animatedElements = document.querySelectorAll('.section__title, .section__lead, .mission__text, .cycle-card, .product-card, .achievement__item, .news-list li, .btn');
    animatedElements.forEach((el, index) => {
        el.classList.add('js-fade-up');
        // Add a slight stagger delay to grid items like cycle-cards, product-cards, and list items
        if (el.classList.contains('cycle-card') || el.classList.contains('product-card')) {
            el.style.transitionDelay = `${(index % 4) * 0.1}s`;
        } else if (el.classList.contains('achievement__item') || el.tagName.toLowerCase() === 'li') {
            el.style.transitionDelay = `${(index % 10) * 0.1}s`;
        }
        scrollObserver.observe(el);
    });

    // Contact Form AJAX Submission
    const contactForm = document.getElementById('contactForm');
    const formMessage = document.getElementById('formMessage');

    if (contactForm) {
        contactForm.addEventListener('submit', function (e) {
            e.preventDefault(); // Prevent normal refresh

            const submitBtn = contactForm.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.innerText;

            // Show loading state
            submitBtn.innerText = '送信中...';
            submitBtn.disabled = true;
            formMessage.style.display = 'none';

            const formData = new FormData(contactForm);

            fetch('php/contact.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    formMessage.style.display = 'block';
                    if (data.result === 'success') {
                        formMessage.style.backgroundColor = '#d4edda';
                        formMessage.style.color = '#155724';
                        formMessage.style.border = '1px solid #c3e6cb';
                        formMessage.innerText = 'お問い合わせを送信しました。内容を確認次第、担当者よりご連絡いたします。';
                        contactForm.reset(); // clear form
                    } else {
                        formMessage.style.backgroundColor = '#f8d7da';
                        formMessage.style.color = '#721c24';
                        formMessage.style.border = '1px solid #f5c6cb';
                        formMessage.innerText = data.message || 'エラーが発生しました。時間をおいて再度お試しください。';
                    }
                })
                .catch(error => {
                    formMessage.style.display = 'block';
                    formMessage.style.backgroundColor = '#f8d7da';
                    formMessage.style.color = '#721c24';
                    formMessage.style.border = '1px solid #f5c6cb';
                    formMessage.innerText = '通信エラーが発生しました。ネットワーク状況をご確認ください。';
                    console.error('Error:', error);
                })
                .finally(() => {
                    submitBtn.innerText = originalBtnText;
                    submitBtn.disabled = false;
                });
        });
    }
});
