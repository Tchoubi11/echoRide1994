document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('form'); 

    if (!form) {
        console.warn(" Le formulaire n'a pas été trouvé !");
        return;
    }

    form.addEventListener('submit', function (event) {
        event.preventDefault(); 

        const formData = new FormData(form);
        const params = new URLSearchParams(formData).toString(); 

        fetch(`{{ searchRoute }}?${params}`, {  
            method: 'GET'  
        })
        .then(response => response.json()) 
        .then(data => {
            const resultsContainer = document.querySelector('.covoiturages-list');
            if (!resultsContainer) {
                console.warn("⚠️ Conteneur de résultats non trouvé !");
                return;
            }

            resultsContainer.innerHTML = ''; 

            if (!data.rides || data.rides.length === 0) {
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
        
    });
});
