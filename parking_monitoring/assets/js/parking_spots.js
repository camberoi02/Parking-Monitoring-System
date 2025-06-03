document.addEventListener('DOMContentLoaded', function() {
    // Function to update sector count in Area Management table
    function updateSectorSpotCount(sectorId, increment = true) {
        const sectorRow = document.querySelector(`tr[data-sector-id="${sectorId}"]`);
        if (sectorRow) {
            const spotCountCell = sectorRow.querySelector('td:nth-child(3)');
            if (spotCountCell) {
                // Extract current count from the text (e.g., "5 spots" -> 5)
                const currentText = spotCountCell.textContent;
                const currentCount = parseInt(currentText) || 0;
                // Update the count based on whether we're incrementing or decrementing
                const newCount = increment ? currentCount + 1 : Math.max(0, currentCount - 1);
                spotCountCell.textContent = `${newCount} spots`;
                
                // Update the delete button's data attribute and state
                const deleteButton = sectorRow.querySelector('.delete-sector-btn');
                if (deleteButton) {
                    deleteButton.setAttribute('data-spots-count', newCount);
                    
                    // Update delete button state based on spot count
                    if (newCount > 0) {
                        deleteButton.disabled = true;
                        deleteButton.title = 'Cannot delete sector with existing spots';
                    } else {
                        deleteButton.disabled = false;
                        deleteButton.removeAttribute('title');
                    }
                }
            }
        }
    }

    // Handle adding parking spots via AJAX
    document.querySelectorAll('.dropdown-item-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const sectorId = formData.get('sector_id');
            const button = this.querySelector('button');
            button.disabled = true;

            fetch('includes/handlers/manage_spots.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Find the Parking Spots Management section
                    let parkingSpotsSection = Array.from(document.querySelectorAll('.card-header h3'))
                        .find(header => header.textContent.trim() === 'Parking Spots Management')
                        ?.closest('.card');

                    if (parkingSpotsSection) {
                        let spotsTable = parkingSpotsSection.querySelector('table tbody');
                        
                        // If no table exists yet (first spot), create the table structure
                        if (!spotsTable) {
                            const cardBody = parkingSpotsSection.querySelector('.card-body');
                            if (cardBody) {
                                cardBody.innerHTML = `
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Spot Number</th>
                                                    <th>Sector</th>
                                                    <th>Status</th>
                                                    <th class="text-end">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody></tbody>
                                        </table>
                                    </div>`;
                                spotsTable = parkingSpotsSection.querySelector('table tbody');
                            }
                        }

                        // Create and insert the new row
                        const newRow = document.createElement('tr');
                        newRow.innerHTML = `
                            <td>${data.spot.spot_number}</td>
                            <td>
                                <span class="badge bg-info rounded-pill px-3 py-2" data-sector-id="${sectorId}">${data.spot.sector_name}</span>
                            </td>
                            <td>
                                <span class="badge bg-success rounded-pill px-3 py-2">Available</span>
                            </td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-danger delete-spot-btn"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#deleteSpotModal"
                                        data-spot-id="${data.spot.id}"
                                        data-spot-number="${data.spot.spot_number}"
                                        data-sector-id="${sectorId}">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </td>
                        `;
                        spotsTable.insertBefore(newRow, spotsTable.firstChild);
                    }
                    
                    // Update sector spot count
                    updateSectorSpotCount(sectorId, true);
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
            const sectorId = button.getAttribute('data-sector-id');
            
            const spotIdInput = this.querySelector('#delete_spot_id');
            const spotNumberSpan = this.querySelector('#delete-spot-number');
            const sectorIdInput = document.createElement('input');
            sectorIdInput.type = 'hidden';
            sectorIdInput.name = 'sector_id';
            sectorIdInput.value = sectorId;
            
            if (spotIdInput && spotNumberSpan) {
                spotIdInput.value = spotId;
                spotNumberSpan.textContent = spotNumber;
                // Add sector_id to the form if it doesn't exist
                const existingSectorInput = this.querySelector('input[name="sector_id"]');
                if (!existingSectorInput) {
                    spotIdInput.parentNode.appendChild(sectorIdInput);
                } else {
                    existingSectorInput.value = sectorId;
                }
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
            const spotId = formData.get('spot_id');
            const sectorId = formData.get('sector_id');
            
            if (!spotId) {
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

                            // Update sector spot count (decrement)
                            if (sectorId) {
                                updateSectorSpotCount(sectorId, false);
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