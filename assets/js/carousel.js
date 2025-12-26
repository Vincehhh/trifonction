document.addEventListener('DOMContentLoaded', () => {
    const cards = document.querySelectorAll('.dash-card');
    const prevBtn = document.getElementById('prev-dash');
    const nextBtn = document.getElementById('next-dash');
    const dots = document.querySelectorAll('.dash-indicators .dot');
    
    let currentIndex = 0;
    const totalCards = cards.length;

    function updateCarousel() {
        cards.forEach(card => {
            card.classList.remove('active', 'prev', 'next');
            card.style.zIndex = 0;
        });

        dots.forEach(dot => dot.classList.remove('active'));

        const prevIndex = (currentIndex - 1 + totalCards) % totalCards;
        const nextIndex = (currentIndex + 1) % totalCards;

    
        cards[currentIndex].classList.add('active');
        cards[prevIndex].classList.add('prev');
        cards[nextIndex].classList.add('next');

        dots[currentIndex].classList.add('active');
    }

    nextBtn.addEventListener('click', () => {
        currentIndex = (currentIndex + 1) % totalCards;
        updateCarousel();
    });

    prevBtn.addEventListener('click', () => {
        currentIndex = (currentIndex - 1 + totalCards) % totalCards;
        updateCarousel();
    });

    
    updateCarousel();
});