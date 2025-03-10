function cancelReservation(reservationId) {
    fetch(`/reservation/${reservationId}/cancel`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ reservationId: reservationId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            document.getElementById(`reservation-${reservationId}`).remove(); 
        } else {
            alert("Erreur : " + data.message);
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert("Une erreur est survenue.");
    });
}

document.querySelectorAll('.cancel-reservation').forEach(button => {
    button.addEventListener('click', function() {
        cancelReservation(this.dataset.id);
    });
});
