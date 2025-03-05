
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.cancel-reservation').forEach(button => {
        button.addEventListener('click', function () {
            const reservationId = this.getAttribute('data-id');
            if (!confirm('Voulez-vous vraiment annuler cette réservation ?')) return;

            fetch(`/reservation/${reservationId}/cancel`, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/json',
                },
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById(`reservation-${reservationId}`).remove();
                    alert('Réservation annulée avec succès.');
                } else {
                    alert('Erreur : ' + data.message);
                }
            })
            .catch(error => console.error('Erreur AJAX :', error));
        });
    });
});

