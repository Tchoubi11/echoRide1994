document.addEventListener('DOMContentLoaded', function () {
    // Récupérer l'URL de recherche depuis l'attribut data
    const searchRoutePath = document.querySelector('#searchRoute').getAttribute('data-search-route');
    
    const form = document.querySelector('form');
    form.addEventListener('submit', function (event) {
        event.preventDefault();

        const formData = new FormData(form);

        // Effectuer la requête de recherche
        fetch(searchRoutePath, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            // Mise à jour dynamique des résultats
            const resultsContainer = document.querySelector('.covoiturages-list');
            resultsContainer.innerHTML = ''; // Réinitialiser les résultats

            if (data.rides.length === 0) {
                resultsContainer.innerHTML = `<p>Aucun covoiturage trouvé.</p>`;
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
                        <button class="btn btn-primary reservation-btn" data-ride-id="${ride.id}">Réserver</button>
                    `;
                    resultsContainer.appendChild(rideElement);
                });

                // Ajouter les gestionnaires d'événements pour les boutons de réservation
                document.querySelectorAll('.reservation-btn').forEach(button => {
                    button.addEventListener('click', function () {
                        const rideId = this.dataset.rideId;

                        fetch(`/covoiturage/${rideId}/reservation`, { method: 'POST' })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    alert('Réservation confirmée !');
                                    location.reload();
                                } else {
                                    alert('Erreur : ' + data.message);
                                }
                            })
                            .catch(error => console.error('Erreur:', error));
                    });
                });
            }
        })
        .catch(error => console.error('Erreur:', error));
    });
});
