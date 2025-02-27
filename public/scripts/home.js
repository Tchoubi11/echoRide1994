// Fonction pour échapper les caractères spéciaux
function escapeHTML(str) {
    const element = document.createElement('div');
    if (str) {
        element.innerText = str;
        element.textContent = str;
    }
    return element.innerHTML;
}

// Fonction de validation des entrées utilisateurs
function validateInput(input) {
    const pattern = /^[a-zA-Z0-9\s]*$/; 
    return pattern.test(input);
}

// Gestion du carrousel
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

// Affichage de la première diapositive par défaut
showSlide(currentSlide);

// Gestion du formulaire de recherche
document.getElementById('searchForm').addEventListener('submit', function (event) {
    event.preventDefault();

    // Récupération des valeurs des champs de formulaire
    const departure = document.getElementById('departure').value;
    const destination = document.getElementById('destination').value;

    // Validation des entrées utilisateur
    if (!validateInput(departure) || !validateInput(destination)) {
        alert('Veuillez entrer des valeurs valides (lettres, chiffres et espaces seulement).');
        return;
    }

    // Limite la longueur des champs à 100 caractères
    if (departure.length > 100 || destination.length > 100) {
        alert('Les valeurs doivent être inférieures à 100 caractères.');
        return;
    }

    // Ici  c'est pour échapper les caractères spéciaux pour éviter les attaques XSS
    const safeDeparture = escapeHTML(departure);
    const safeDestination = escapeHTML(destination);

    // Affichage de l'alerte avec les valeurs échappées
    alert(`Recherche de trajet de ${safeDeparture} à ${safeDestination}`);
});
