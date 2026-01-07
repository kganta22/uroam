
document.addEventListener('DOMContentLoaded', () => {
    const photoInput = document.getElementById('photoInput');
    const photoGrid = document.getElementById('photoGrid');
    const uploadBox = document.getElementById('uploadBox');
    const photoStatus = document.getElementById('photoStatus');
    const uploadContainer = document.getElementById('uploadContainer');

    const MAX_PHOTOS = 4;
    let newPhotosCount = 0;
    let uploadedFiles = []; // Track uploaded files

    // Function to count filled slots
    function countFilledSlots() {
        return photoGrid.querySelectorAll('.slot:not(.empty)').length;
    }

    // Function to get empty slots
    function getEmptySlots() {
        return photoGrid.querySelectorAll('.slot.empty');
    }

    // Function to render grid
    function renderGrid() {
        const filled = countFilledSlots();

        // Sembunyikan upload jika sudah penuh
        if (filled >= MAX_PHOTOS) {
            uploadContainer.style.display = 'none';
            photoStatus.textContent = `Maximum photos reached (${filled}/${MAX_PHOTOS}). Delete a photo to add more.`;
        } else {
            uploadContainer.style.display = 'flex';
            photoStatus.textContent = filled > 0 ? `${filled}/${MAX_PHOTOS} photos` : '';
        }
    }

    // Function untuk add delete button ke foto
    function addDeleteButton(slot) {
        if (slot.querySelector('.photo-delete')) return;

        const deleteBtn = document.createElement('button');
        deleteBtn.type = 'button';
        deleteBtn.className = 'photo-delete';
        deleteBtn.textContent = '✕';
        deleteBtn.title = 'Delete photo';

        // Event listener akan dihandle oleh event delegation di bawah
        slot.appendChild(deleteBtn);
    }

    // Handle file input change
    photoInput.addEventListener('change', function () {
        const files = Array.from(this.files);
        const filledSlots = countFilledSlots();

        // Validasi: cek total files yang mau di-upload + filled slots
        if (files.length + filledSlots > MAX_PHOTOS) {
            photoStatus.textContent = `⚠️ Cannot upload ${files.length} photos. You can only upload ${MAX_PHOTOS - filledSlots} more photo(s).`;
            photoStatus.style.color = '#e74c3c';
            this.value = '';
            return;
        }

        // Cegah upload jika sudah penuh
        if (filledSlots >= MAX_PHOTOS) {
            photoStatus.textContent = '⚠️ Maximum photos reached. Delete a photo to add more.';
            photoStatus.style.color = '#e74c3c';
            this.value = '';
            return;
        }

        // Ambil semua empty slots sekaligus (array)
        const emptySlots = Array.from(getEmptySlots());

        // Add files ke uploadedFiles array
        files.forEach((file, index) => {
            uploadedFiles.push(file);

            const reader = new FileReader();

            reader.onload = (e) => {
                // Ambil slot kosong berdasarkan index
                const emptySlot = emptySlots[index];

                if (emptySlot) {
                    emptySlot.classList.remove('empty');
                    emptySlot.setAttribute('data-type', 'new');
                    emptySlot.setAttribute('data-file-index', uploadedFiles.length - files.length + index);
                    emptySlot.innerHTML = `<img src="${e.target.result}" class="photo-thumb">`;

                    addDeleteButton(emptySlot);
                    newPhotosCount++;

                    // Re-render setelah foto terakhir di-load
                    if (index === files.length - 1) {
                        photoStatus.style.color = '';
                        updateFileInput();
                        renderGrid();
                    }
                }
            };

            reader.readAsDataURL(file);
        });
    });

    // Function to update file input with DataTransfer
    function updateFileInput() {
        const dataTransfer = new DataTransfer();
        uploadedFiles.forEach(file => {
            dataTransfer.items.add(file);
        });
        photoInput.files = dataTransfer.files;
    }

    // Handle delete dari existing photos
    photoGrid.addEventListener('click', (e) => {
        if (e.target.classList.contains('photo-delete')) {
            e.preventDefault();
            const slot = e.target.closest('.slot');

            // Track deleted current photos
            if (slot.getAttribute('data-type') === 'current') {
                const photoPath = slot.getAttribute('data-path');
                if (photoPath) {
                    let deletedInput = document.getElementById('deletedPhotos');
                    if (!deletedInput) {
                        deletedInput = document.createElement('input');
                        deletedInput.type = 'hidden';
                        deletedInput.id = 'deletedPhotos';
                        deletedInput.name = 'deleted_photos';
                        deletedInput.value = '';
                        document.querySelector('form').appendChild(deletedInput);
                    }
                    const currentDeleted = deletedInput.value ? deletedInput.value.split(',') : [];
                    currentDeleted.push(photoPath);
                    deletedInput.value = currentDeleted.join(',');
                }
            }

            // Remove from uploadedFiles if it's a new upload
            if (slot.getAttribute('data-type') === 'new') {
                const fileIndex = parseInt(slot.getAttribute('data-file-index'));
                if (!isNaN(fileIndex) && fileIndex >= 0) {
                    uploadedFiles.splice(fileIndex, 1);
                    // Update file input
                    updateFileInput();
                    // Update remaining file indexes
                    photoGrid.querySelectorAll('.slot[data-type="new"]').forEach(s => {
                        const idx = parseInt(s.getAttribute('data-file-index'));
                        if (idx > fileIndex) {
                            s.setAttribute('data-file-index', idx - 1);
                        }
                    });
                }
            }

            // Kosongkan slot tanpa menghapus element-nya
            slot.innerHTML = '';
            slot.classList.add('empty');
            slot.removeAttribute('data-type');
            slot.removeAttribute('data-path');
            slot.removeAttribute('data-file-index');

            renderGrid();
        }
    });

    // Initial render
    photoGrid.querySelectorAll('.slot:not(.empty)').forEach(slot => {
        addDeleteButton(slot);
    });
    renderGrid();
});

// ====== ITINERARY ======
document.getElementById("itineraryInput").addEventListener("change", function () {
    if (this.files.length > 0) {
        document.getElementById("itineraryStatus").textContent =
            "Uploaded: " + this.files[0].name;
    } else {
        document.getElementById("itineraryStatus").textContent = "";
    }
});

// ====== CATEGORIES ======
document.addEventListener("DOMContentLoaded", () => {
    const chips = document.querySelectorAll('.chip');
    const allChip = document.querySelector('.chip-all');
    const input = document.getElementById('categoryIds');

    function sync() {
        const ids = [];
        chips.forEach(c => {
            if (!c.classList.contains('chip-all') && c.classList.contains('active')) {
                ids.push(c.dataset.id);
            }
        });
        input.value = ids.join(',');
    }

    chips.forEach(chip => {
        chip.addEventListener('click', () => {

            if (chip.classList.contains('chip-all')) {
                const active = chip.classList.toggle('active');
                chips.forEach(c => {
                    if (!c.classList.contains('chip-all')) {
                        c.classList.toggle('active', active);
                    }
                });
                sync();
                return;
            }

            chip.classList.toggle('active');

            const allActive = [...chips].every(c =>
                c.classList.contains('chip-all') || c.classList.contains('active')
            );

            allChip.classList.toggle('active', allActive);
            sync();
        });
    });
});