document.addEventListener('DOMContentLoaded', () => {
    const observerOptions = {
        root: null,
        rootMargin: '0px',
        threshold: 0.1
    };

    const observer = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    // Quan sát content1-fan-img
    const content1FanImg = document.querySelector('.content1-fan-img');
    if (content1FanImg) {
        observer.observe(content1FanImg);
    }

    // Quan sát các thẻ a trong content-2
    const content2Links = document.querySelectorAll('.content-2 a');
    content2Links.forEach(link => {
        observer.observe(link);
    });

    // Quan sát các product-card
    const productCards = document.querySelectorAll('.product-card');
    productCards.forEach(card => {
        observer.observe(card);
    });
});