document.addEventListener('DOMContentLoaded', function() {
    // Handle adding parking spots via AJAX
    document.querySelectorAll('.add-spot-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const button = this.querySelector('button');
            button.disabled = true;

            fetch('includes/handlers/manage_spots.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Add the new spot to the parking spots table specifically
                    const parkingSpotsSection = Array.from(document.querySelectorAll('.card-header h3'))
                        .find(header => header.textContent.trim() === 'Parking Spots Management')
                        ?.closest('.card');
                    
                    const spotsTable = parkingSpotsSection?.querySelector('.table tbody');
                    
                    if (spotsTable) {
                        const newRow = document.createElement('tr');
                        newRow.className = 'align-middle';
                        newRow.innerHTML = `
                            <td class="fw-medium">${data.spot.spot_number}</td>
                            <td>
                                <span class="badge bg-primary rounded-pill px-3 py-2">${data.spot.sector_name}</span>
                            </td>
                            <td>
                                <span class="badge bg-success rounded-pill px-3 py-2">Available</span>
                            </td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-danger delete-spot-btn"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#deleteSpotModal"
                                        data-spot-id="${data.spot.id}"
                                        data-spot-number="${data.spot.spot_number}">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </td>
                        `;
                        spotsTable.insertBefore(newRow, spotsTable.firstChild);
                    } else {
                        // If no table exists yet, create the initial table
                        if (parkingSpotsSection) {
                            const cardBody = parkingSpotsSection.querySelector('.card-body');
                            if (cardBody) {
                                // Remove the "no spots" message if it exists
                                const noSpotsMessage = cardBody.querySelector('p');
                                if (noSpotsMessage) {
                                    noSpotsMessage.remove();
                                }
                                
                                // Create new table
                                const tableHTML = `
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover border">
                                            <thead class="table-light">
                                                <tr>
                                                    <th class="fw-semibold">Spot Number</th>
                                                    <th class="fw-semibold">Sector</th>
                                                    <th class="fw-semibold">Status</th>
                                                    <th class="fw-semibold text-end">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr class="align-middle">
                                                    <td class="fw-medium">${data.spot.spot_number}</td>
                                                    <td>
                                                        <span class="badge bg-primary rounded-pill px-3 py-2">${data.spot.sector_name}</span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-success rounded-pill px-3 py-2">Available</span>
                                                    </td>
                                                    <td class="text-end">
                                                        <button type="button" class="btn btn-sm btn-danger delete-spot-btn"
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#deleteSpotModal"
                                                                data-spot-id="${data.spot.id}"
                                                                data-spot-number="${data.spot.spot_number}">
                                                            <i class="fas fa-trash"></i> Delete
                                                        </button>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                `;
                                cardBody.innerHTML = tableHTML;
                            }
                        }
                    }
                    showToast(data.message, 'success');
                } else {
                    showToast(data.message || 'Error adding parking spot', 'danger');
                }
            })
            .catch(error => {
                showToast('Error adding parking spot: ' + error, 'danger');
            })
            .finally(() => {
                button.disabled = false;
            });
        });
    });

    // Handle deleting parking spots via AJAX
    const deleteSpotModal = document.getElementById('deleteSpotModal');
    if (deleteSpotModal) {
        deleteSpotModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const spotId = button.getAttribute('data-spot-id');
            const spotNumber = button.getAttribute('data-spot-number');
            
            const spotIdInput = this.querySelector('#delete_spot_id');
            const spotNumberSpan = this.querySelector('#delete-spot-number');
            
            if (spotIdInput && spotNumberSpan) {
                spotIdInput.value = spotId;
                spotNumberSpan.textContent = spotNumber;
            }
        });
    }

    const deleteSpotForm = document.getElementById('deleteSpotForm');
    if (deleteSpotForm) {
        deleteSpotForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitButton = this.querySelector('#confirmDeleteSpot');
            const modal = bootstrap.Modal.getInstance(deleteSpotModal);
            
            if (!formData.get('spot_id')) {
                showToast('Error: No spot ID provided', 'danger');
                return;
            }
            
            submitButton.disabled = true;
            
            fetch('includes/handlers/manage_spots.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const spotId = formData.get('spot_id');
                    const deleteButton = document.querySelector(`button[data-spot-id="${spotId}"]`);
                    if (deleteButton) {
                        const row = deleteButton.closest('tr');
                        if (row) {
                            // Find the spots management section
                            const parkingSpotsSection = Array.from(document.querySelectorAll('.card')).find(card => {
                                const header = card.querySelector('.card-header h3');
                                return header && header.textContent.trim() === 'Parking Spots Management';
                            });
                            
                            row.remove();
                            
                            // Check if this was the last spot
                            if (parkingSpotsSection) {
                                const tbody = parkingSpotsSection.querySelector('tbody');
                                if (!tbody || !tbody.querySelector('tr')) {
                                    const cardBody = parkingSpotsSection.querySelector('.card-body');
                                    if (cardBody) {
                                        cardBody.innerHTML = '<p class="text-center">No parking spots defined yet. Add one using the button above.</p>';
                                    }
                                }
                            }
                        }
                    }
                    showToast(data.message, 'success');
                    modal.hide();
                } else {
                    showToast(data.message || 'Error deleting parking spot', 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error deleting parking spot', 'danger');
            })
            .finally(() => {
                submitButton.disabled = false;
            });
        });
    }
}); 