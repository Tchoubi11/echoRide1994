// passager.js

// Sélectionner l'élément de l'icône et le conteneur des boutons
const icon = document.getElementById('toggle-passenger');
const passengerControls = document.getElementById('passenger-controls');

// Ajouter un événement de clic à l'icône
icon.addEventListener('click', function() {
    // Basculer l'affichage des boutons (+ et -)
    if (passengerControls.style.display === 'none') {
        passengerControls.style.display = 'flex'; // Afficher les boutons
    } else {
        passengerControls.style.display = 'none'; // Masquer les boutons
    }
});

// Incrémentation
document.getElementById('increment').addEventListener('click', function() {
    let numPeopleInput = document.getElementById('num_people');
    let currentValue = parseInt(numPeopleInput.value, 10);
    if (currentValue < 10) {
        numPeopleInput.value = currentValue + 1;
    }
});

// Décrémentation
document.getElementById('decrement').addEventListener('click', function() {
    let numPeopleInput = document.getElementById('num_people');
    let currentValue = parseInt(numPeopleInput.value, 10);
    if (currentValue > 1) {
        numPeopleInput.value = currentValue - 1;
    }
});
