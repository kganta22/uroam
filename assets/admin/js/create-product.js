document.getElementById("itineraryInput").addEventListener("change", function () {
    if (this.files.length > 0) {
        document.getElementById("itineraryStatus").textContent =
            "Uploaded: " + this.files[0].name;
    } else {
        document.getElementById("itineraryStatus").textContent = "";
    }
});

document.addEventListener("DOMContentLoaded", () => {
    const photoInput = document.getElementById("photoInput");
    const photoGrid = document.getElementById("photoGrid");
    const uploadContainer = document.getElementById("uploadContainer");
    const photoStatus = document.getElementById("photoStatus");

    const MAX_PHOTOS = 4;
    let uploadedFiles = [];

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

        slot.appendChild(deleteBtn);
    }

    // Function to update file input with DataTransfer
    function updateFileInput() {
        const dataTransfer = new DataTransfer();
        uploadedFiles.forEach(file => {
            dataTransfer.items.add(file);
        });
        photoInput.files = dataTransfer.files;
    }

    // Handle file input change
    if (photoInput) {
        photoInput.addEventListener('change', function () {
            const files = Array.from(this.files);
            const filledSlots = countFilledSlots();

            // Validasi: cek total files yang mau di-upload + filled slots
            if (files.length + filledSlots > MAX_PHOTOS) {
                photoStatus.textContent = `Cannot upload ${files.length} photos. You can only upload ${MAX_PHOTOS - filledSlots} more photo(s).`;
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
                    const emptySlot = emptySlots[index];

                    if (emptySlot) {
                        emptySlot.classList.remove('empty');
                        emptySlot.setAttribute('data-type', 'new');
                        emptySlot.setAttribute('data-file-index', uploadedFiles.length - files.length + index);
                        emptySlot.innerHTML = `<img src="${e.target.result}" class="photo-thumb">`;

                        addDeleteButton(emptySlot);

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
    }

    // Handle delete
    if (photoGrid) {
        photoGrid.addEventListener('click', (e) => {
            if (e.target.classList.contains('photo-delete')) {
                e.preventDefault();
                const slot = e.target.closest('.slot');

                // Remove from uploadedFiles
                if (slot.getAttribute('data-type') === 'new') {
                    const fileIndex = parseInt(slot.getAttribute('data-file-index'));
                    if (!isNaN(fileIndex) && fileIndex >= 0) {
                        uploadedFiles.splice(fileIndex, 1);
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
                slot.removeAttribute('data-file-index');

                renderGrid();
            }
        });
    }

    // Initial render
    renderGrid();
});

document.addEventListener("DOMContentLoaded", () => {
    const chips = document.querySelectorAll('.chip');
    const allChip = document.querySelector('.chip-all');
    const categoryInput = document.getElementById('categoryIds');

    function syncCategories() {
        const selected = [];

        chips.forEach(chip => {
            if (!chip.classList.contains('chip-all') &&
                chip.classList.contains('active')) {
                selected.push(chip.dataset.id);
            }
        });

        categoryInput.value = selected.join(',');
    }

    chips.forEach(chip => {
        chip.addEventListener('click', () => {

            // ALL CATEGORIES
            if (chip.classList.contains('chip-all')) {
                const isActive = chip.classList.toggle('active');

                chips.forEach(c => {
                    if (!c.classList.contains('chip-all')) {
                        c.classList.toggle('active', isActive);
                    }
                });

                syncCategories();
                return;
            }

            // INDIVIDUAL CATEGORY
            chip.classList.toggle('active');

            // Jika ada 1 saja yang tidak aktif → matikan All
            if (allChip) {
                const allSelected = [...chips].every(c =>
                    c.classList.contains('chip-all') ||
                    c.classList.contains('active')
                );

                allChip.classList.toggle('active', allSelected);
            }

            syncCategories();
        });
    });
});
