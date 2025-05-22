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
            
            modalUserId.value = userId;
            modalUsername.value = username;
            
            // Select the correct option
            for (let i = 0; i < modalRole.options.length; i++) {
                if (modalRole.options[i].value === role) {
                    modalRole.options[i].selected = true;
                    break;
                }
            }
        });
        
        // Handle save changes button
        document.getElementById('saveUserChanges').addEventListener('click', function() {
            document.getElementById('editUserForm').submit();
        });
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
        deleteSectorModal.addEventListener('show.bs.modal', function(event) {
            // Button that triggered the modal
            const button = event.relatedTarget;
            
            // Extract info from data attributes
            const sectorId = button.getAttribute('data-sector-id');
            const sectorName = button.getAttribute('data-sector-name');
            const spotsCount = parseInt(button.getAttribute('data-spots-count'));
            
            // Update the modal's content
            const modalSectorId = deleteSectorModal.querySelector('#delete_sector_id');
            const modalSectorName = deleteSectorModal.querySelector('#delete-sector-name');
            const sectorHasSpots = deleteSectorModal.querySelector('#sector-has-spots');
            const sectorSpotsCount = deleteSectorModal.querySelector('#sector-spots-count');
            const deleteButton = deleteSectorModal.querySelector('#deleteSectorBtn');
            
            modalSectorId.value = sectorId;
            modalSectorName.textContent = sectorName;
            
            // Show warning and disable delete button if sector has spots
            if (spotsCount > 0) {
                sectorHasSpots.classList.remove('d-none');
                sectorSpotsCount.textContent = spotsCount;
                deleteButton.disabled = true;
            } else {
                sectorHasSpots.classList.add('d-none');
                deleteButton.disabled = false;
            }
        });
    }
});
