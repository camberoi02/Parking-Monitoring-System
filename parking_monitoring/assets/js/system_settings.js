document.addEventListener('DOMContentLoaded', function() {
    // Check if there's a tab parameter in the URL
    const urlParams = new URLSearchParams(window.location.search);
    const activeTab = urlParams.get('active_tab');
    
    // If a tab parameter exists, activate that tab
    if (activeTab) {
        const tabElement = document.getElementById(activeTab + '-tab');
        if (tabElement) {
            const tab = new bootstrap.Tab(tabElement);
            tab.show();
        }
    }
    
    // Add event listeners to all tabs
    document.querySelectorAll('.nav-link[data-bs-toggle="tab"]').forEach(tab => {
        tab.addEventListener('shown.bs.tab', function(event) {
            // Get the active tab ID without "-tab" suffix
            const tabId = event.target.id.replace('-tab', '');
            
            // Update URL without reloading the page
            const url = new URL(window.location);
            url.searchParams.set('active_tab', tabId); 
            window.history.replaceState({}, '', url);
            
            // Also store in sessionStorage as a backup method
            sessionStorage.setItem('activeSettingsTab', tabId);
            
            // Update all forms with the active tab
            updateFormsWithActiveTab(tabId);
        });
    });
    
    // Check sessionStorage if URL param is not available (e.g., direct navigation)
    if (!activeTab) {
        const storedTab = sessionStorage.getItem('activeSettingsTab');
        if (storedTab) {
            const tabElement = document.getElementById(storedTab + '-tab');
            if (tabElement) {
                const tab = new bootstrap.Tab(tabElement);
                tab.show();
                
                // Update URL to match the restored tab
                const url = new URL(window.location);
                url.searchParams.set('active_tab', storedTab);
                window.history.replaceState({}, '', url);
            }
        }
    }
    
    // Function to update all forms with the active tab
    function updateFormsWithActiveTab(tabId) {
        document.querySelectorAll('form').forEach(form => {
            // Check if the form already has an active_tab input
            let inputField = form.querySelector('input[name="active_tab"]');
            
            if (!inputField) {
                // Create a new input field if it doesn't exist
                inputField = document.createElement('input');
                inputField.type = 'hidden';
                inputField.name = 'active_tab';
                form.appendChild(inputField);
            }
            
            // Update the value to the current active tab
            inputField.value = tabId;
        });
    }
    
    // Initialize forms with the current active tab
    const currentTab = activeTab || document.querySelector('.nav-link.active')?.id.replace('-tab', '') || 'general';
    updateFormsWithActiveTab(currentTab);
    
    // Handle edit user modal
    const editUserModal = document.getElementById('editUserModal');
    if (editUserModal) {
        editUserModal.addEventListener('show.bs.modal', function(event) {
            // Button that triggered the modal
            const button = event.relatedTarget;
            
            // Extract info from data attributes
            const userId = button.getAttribute('data-user-id');
            const username = button.getAttribute('data-username');
            const role = button.getAttribute('data-role');
            
            // Update the modal's content
            const modalUserId = editUserModal.querySelector('#edit_user_id');
            const modalUsername = editUserModal.querySelector('#edit_username');
            const modalRole = editUserModal.querySelector('#edit_role');
            
            if (modalUserId) modalUserId.value = userId;
            if (modalUsername) modalUsername.value = username;
            
            // Select the correct option
            if (modalRole) {
            for (let i = 0; i < modalRole.options.length; i++) {
                if (modalRole.options[i].value === role) {
                    modalRole.options[i].selected = true;
                    break;
                    }
                }
            }
        });
        
        // Handle save changes button
        const saveUserChanges = document.getElementById('saveUserChanges');
        if (saveUserChanges) {
            saveUserChanges.addEventListener('click', function() {
                const editUserForm = document.getElementById('editUserForm');
                if (editUserForm) editUserForm.submit();
        });
        }
    }
    
    // Handle delete user modal
    const deleteUserModal = document.getElementById('deleteUserModal');
    if (deleteUserModal) {
        deleteUserModal.addEventListener('show.bs.modal', function(event) {
            // Button that triggered the modal
            const button = event.relatedTarget;
            
            // Extract info from data attributes
            const userId = button.getAttribute('data-user-id');
            const username = button.getAttribute('data-username');
            
            // Update the modal's content
            const modalUserId = deleteUserModal.querySelector('#delete_user_id');
            const modalUsername = deleteUserModal.querySelector('#delete-username');
            
            modalUserId.value = userId;
            modalUsername.textContent = username;
        });
    }
    
    // Handle delete spot modal
    const deleteSpotModal = document.getElementById('deleteSpotModal');
    if (deleteSpotModal) {
        deleteSpotModal.addEventListener('show.bs.modal', function(event) {
            // Button that triggered the modal
            const button = event.relatedTarget;
            
            // Extract info from data attributes
            const spotId = button.getAttribute('data-spot-id');
            const spotNumber = button.getAttribute('data-spot-number');
            
            // Update the modal's content
            const modalSpotId = deleteSpotModal.querySelector('#delete_spot_id');
            const modalSpotNumber = deleteSpotModal.querySelector('#delete-spot-number');
            
            modalSpotId.value = spotId;
            modalSpotNumber.textContent = spotNumber;
        });
    }
    
    // Handle add sector modal
    document.getElementById('saveSectorBtn').addEventListener('click', function() {
        document.getElementById('addSectorForm').submit();
    });
    
    // Handle edit sector modal
    const editSectorModal = document.getElementById('editSectorModal');
    if (editSectorModal) {
        editSectorModal.addEventListener('show.bs.modal', function(event) {
            // Button that triggered the modal
            const button = event.relatedTarget;
            
            // Extract info from data attributes
            const sectorId = button.getAttribute('data-sector-id');
            const sectorName = button.getAttribute('data-sector-name');
            const sectorDescription = button.getAttribute('data-sector-description');
            
            // Update the modal's content
            const modalSectorId = editSectorModal.querySelector('#edit_sector_id');
            const modalSectorName = editSectorModal.querySelector('#edit_sector_name');
            const modalSectorDescription = editSectorModal.querySelector('#edit_sector_description');
            
            modalSectorId.value = sectorId;
            modalSectorName.value = sectorName;
            modalSectorDescription.value = sectorDescription || '';
        });
        
        // Handle save changes button
        document.getElementById('updateSectorBtn').addEventListener('click', function() {
            document.getElementById('editSectorForm').submit();
        });
    }
    
    // Handle delete sector modal
    const deleteSectorModal = document.getElementById('deleteSectorModal');
    if (deleteSectorModal) {
        let sectorToDeleteId = null;
        
        deleteSectorModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const sectorId = button.getAttribute('data-sector-id');
            const sectorName = button.getAttribute('data-sector-name');
            const spotsCount = button.getAttribute('data-spots-count');
            
            sectorToDeleteId = sectorId;
            
            document.getElementById('delete-sector-name').textContent = sectorName;
            const spotsWarning = document.getElementById('sector-has-spots');
            const spotsCountSpan = document.getElementById('sector-spots-count');
            
            if (spotsCount > 0) {
                spotsWarning.style.display = 'block';
                spotsCountSpan.textContent = spotsCount;
                document.getElementById('deleteSectorBtn').disabled = true;
            } else {
                spotsWarning.style.display = 'none';
                document.getElementById('deleteSectorBtn').disabled = false;
            }
        });

        document.getElementById('deleteSectorBtn').addEventListener('click', function() {
            if (!sectorToDeleteId) return;
            
            const formData = new FormData();
            formData.append('sector_id', sectorToDeleteId);
            
            fetch('includes/handlers/delete_sector.php', {
                    method: 'POST',
                    body: formData
                })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                    // Close modal
                    const modal = bootstrap.Modal.getInstance(deleteSectorModal);
                    modal.hide();
                    // Reload page after a short delay
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showToast(data.message, 'danger');
                }
                })
                .catch(error => {
                    showToast('Error deleting sector: ' + error, 'danger');
                });
            });
    }

    // Handle edit sector form submission
    const editSectorForm = document.getElementById('editSectorForm');
    if (editSectorForm) {
        editSectorForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('includes/handlers/edit_sector.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                    // Close modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('editSectorModal'));
                    modal.hide();
                    // Reload page after a short delay
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showToast(data.message, 'danger');
                }
            })
            .catch(error => {
                showToast('Error updating sector: ' + error, 'danger');
            });
        });
    }

    // Handle deleting parking spots via AJAX
    document.querySelector('#deleteSpotModal').addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const spotId = button.getAttribute('data-spot-id');
        const spotNumber = button.getAttribute('data-spot-number');
        const sectorId = button.closest('tr').querySelector('td:nth-child(2) .badge').getAttribute('data-sector-id');
        
        this.querySelector('#confirmDeleteSpot').setAttribute('data-spot-id', spotId);
        this.querySelector('#confirmDeleteSpot').setAttribute('data-sector-id', sectorId);
        this.querySelector('#delete-spot-number').textContent = spotNumber;
    });

    document.querySelector('#confirmDeleteSpot').addEventListener('click', function() {
        const spotId = this.getAttribute('data-spot-id');
        const sectorId = this.getAttribute('data-sector-id');
        const modal = bootstrap.Modal.getInstance(document.querySelector('#deleteSpotModal'));
        this.disabled = true;

        const formData = new FormData();
        formData.append('action', 'delete_spot');
        formData.append('spot_id', spotId);

        fetch('includes/handlers/manage_spots.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Remove the row from the table
                const row = document.querySelector(`button[data-spot-id="${spotId}"]`).closest('tr');
                row.remove();
                
                // Update the sector spot count (decrease by 1)
                const sectorRow = document.querySelector(`tr[data-sector-id="${sectorId}"]`);
                if (sectorRow) {
                    const spotCountCell = sectorRow.querySelector('td:nth-child(3)');
                    if (spotCountCell) {
                        const currentText = spotCountCell.textContent;
                        const currentCount = parseInt(currentText) || 0;
                        spotCountCell.textContent = `${Math.max(0, currentCount - 1)} spots`;
                        
                        // Update delete button state
                        const deleteButton = sectorRow.querySelector('.delete-sector-btn');
                        if (deleteButton) {
                            const newCount = Math.max(0, currentCount - 1);
                            deleteButton.setAttribute('data-spots-count', newCount);
                            if (newCount === 0) {
                                deleteButton.disabled = false;
                                deleteButton.removeAttribute('title');
                            }
                        }
                    }
                }
                
                showToast(data.message, 'success');
            } else {
                showToast(data.message || 'Error deleting parking spot', 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Error deleting parking spot: ' + error.message, 'danger');
        })
        .finally(() => {
            this.disabled = false;
            modal.hide();
        });
    });

    // Handle add sector form submission
    const addSectorForm = document.getElementById('addSectorForm');
    if (addSectorForm) {
        addSectorForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('includes/handlers/add_sector.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                    // Close modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('addSectorModal'));
                    modal.hide();
                    // Reload page after a short delay
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showToast(data.message, 'danger');
                }
            })
            .catch(error => {
                showToast('Error adding sector: ' + error, 'danger');
            });
        });
    }

    // Prevent form resubmission on page refresh
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }
});
