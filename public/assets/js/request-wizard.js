/**
 * Service Request Wizard & Evidence Upload Handler
 * headworld / Headworld Campus Operations
 */
document.addEventListener('DOMContentLoaded', () => {
    // 1. Building Selector Toggle for 'Other'
    const buildingSelect = document.getElementById('buildingSelect');
    const customBuildingWrapper = document.getElementById('customBuildingWrapper');
    const customBuildingInput = document.getElementById('customBuildingInput');

    if (buildingSelect && customBuildingWrapper) {
        const toggleCustomBuilding = () => {
            if (buildingSelect.value === 'Other') {
                customBuildingWrapper.classList.remove('d-none');
                if (customBuildingInput) {
                    customBuildingInput.setAttribute('required', 'required');
                    customBuildingInput.focus();
                }
            } else {
                customBuildingWrapper.classList.add('d-none');
                if (customBuildingInput) {
                    customBuildingInput.removeAttribute('required');
                }
            }
        };

        buildingSelect.addEventListener('change', toggleCustomBuilding);
        toggleCustomBuilding();
    }

    // 2. Drag & Drop Evidence Photo Upload with DataTransfer Sync
    const dropzone = document.getElementById('dropzoneArea');
    const fileInput = document.getElementById('evidenceFileInput');
    const previewGrid = document.getElementById('evidencePreviewGrid');

    if (dropzone && fileInput && previewGrid) {
        const dataTransfer = new DataTransfer();

        const syncAndRender = () => {
            fileInput.files = dataTransfer.files;
            renderPreviews();
        };

        const renderPreviews = () => {
            previewGrid.innerHTML = '';
            const files = Array.from(dataTransfer.files);

            files.forEach((file, index) => {
                const item = document.createElement('div');
                item.className = 'preview-item position-relative rounded-3 overflow-hidden border shadow-sm';
                item.style.width = '100px';
                item.style.height = '100px';

                const img = document.createElement('img');
                img.alt = file.name;
                img.className = 'w-100 h-100 object-fit-cover';

                const reader = new FileReader();
                reader.onload = (e) => {
                    img.src = e.target.result;
                };
                reader.readAsDataURL(file);

                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'preview-remove btn btn-danger btn-sm p-0 position-absolute top-0 end-0 m-1 rounded-circle d-flex align-items-center justify-content-center';
                removeBtn.style.width = '24px';
                removeBtn.style.height = '24px';
                removeBtn.innerHTML = '<i class="bi bi-x fs-6"></i>';
                removeBtn.title = 'Remove photo';

                removeBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    dataTransfer.items.remove(index);
                    syncAndRender();
                });

                item.appendChild(img);
                item.appendChild(removeBtn);
                previewGrid.appendChild(item);
            });
        };

        const addFiles = (newFiles) => {
            const currentCount = dataTransfer.files.length;
            const maxFiles = 5;

            Array.from(newFiles).forEach((file) => {
                if (dataTransfer.files.length >= maxFiles) {
                    if (window.Toast) {
                        Toast.warning('File Limit', 'Maximum 5 evidence photos allowed.');
                    }
                    return;
                }

                if (!file.type.startsWith('image/')) {
                    if (window.Toast) {
                        Toast.warning('Invalid Format', `${file.name} is not an image file.`);
                    }
                    return;
                }

                if (file.size > 10 * 1024 * 1024) {
                    if (window.Toast) {
                        Toast.warning('File Too Large', `${file.name} exceeds the 10MB limit.`);
                    }
                    return;
                }

                dataTransfer.items.add(file);
            });

            syncAndRender();
        };

        dropzone.addEventListener('click', () => fileInput.click());

        dropzone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropzone.classList.add('dragover');
        });

        dropzone.addEventListener('dragleave', () => {
            dropzone.classList.remove('dragover');
        });

        dropzone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropzone.classList.remove('dragover');
            if (e.dataTransfer && e.dataTransfer.files) {
                addFiles(e.dataTransfer.files);
            }
        });

        fileInput.addEventListener('change', (e) => {
            if (e.target.files) {
                addFiles(e.target.files);
            }
        });
    }
});

