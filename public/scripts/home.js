
let currentSlide = 0;

const slides = document.querySelectorAll('.carousel-item');
const totalSlides = slides.length;

function showSlide(index) {
    slides.forEach((slide, i) => {
        slide.style.display = (i === index) ? 'block' : 'none';
    });
}

function moveCarousel(direction) {
    if (direction === 'next') {
        currentSlide = (currentSlide + 1) % totalSlides;
    } else if (direction === 'prev') {
        currentSlide = (currentSlide - 1 + totalSlides) % totalSlides;
    }
    showSlide(currentSlide);
}


showSlide(currentSlide);


document.getElementById('searchForm').addEventListener('submit', function (event) {
    event.preventDefault();
    const departure = document.getElementById('departure').value;
    const destination = document.getElementById('destination').value;
    alert(`Recherche de trajet de ${departure} à ${destination}`);
});
