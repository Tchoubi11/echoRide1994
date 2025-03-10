document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('form'); 
    form.addEventListener('submit', function (event) {
        event.preventDefault(); 

        const formData = new FormData(form); 

        
        fetch('{{ searchRoute }}', { // Remplacer `{{ searchRoute }}` par la route dynamique de recherche
            method: 'GET', 
            body: formData 
        })
        .then(response => response.json()) 
        .then(data => {
            const resultsContainer = document.querySelector('.covoiturages-list'); // Conteneur des résultats
            resultsContainer.innerHTML = ''; // Effacer les résultats précédents

            if (data.rides.length === 0) {
                resultsContainer.innerHTML = '<p>Aucun covoiturage trouvé.</p>'; 
            } else {
                
                data.rides.forEach(ride => {
                    const rideElement = document.createElement('div');
                    rideElement.classList.add('covoiturage-item');
                    rideElement.innerHTML = `
                        <p><strong>Pseudo :</strong> ${ride.driver.pseudo}</p>
                        <p><strong>Places restantes :</strong> ${ride.nbPlace}</p>
                        <p><strong>Prix :</strong> ${ride.prixPersonne} €</p>
                        <p><strong>Date et Heure de départ :</strong> ${ride.dateDepart} à ${ride.heureDepart}</p>
                        <a href="/covoiturage/${ride.id}" class="btn btn-secondary">Détail</a>
                    `;
                    resultsContainer.appendChild(rideElement);
                });
            }
        })
        .catch(error => console.error('Erreur:', error)); 
    });
});

