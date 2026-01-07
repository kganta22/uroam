// Tour Detail Page Functionality

(function () {
  // ========================================
  // GALLERY FUNCTIONALITY
  // ========================================

  const mainImage = document.getElementById('mainImage');
  const galleryThumbnails = document.getElementById('galleryThumbnails');
  const thumbnails = document.querySelectorAll('.thumbnail');
  const prevBtn = document.getElementById('galleryPrev');
  const nextBtn = document.getElementById('galleryNext');

  let currentImageIndex = 0;

  // Collect all images from thumbnails
  const allImages = Array.from(thumbnails).map(thumb => {
    const img = thumb.querySelector('img');
    return img ? img.src : null;
  }).filter(src => src !== null);

  // Handle thumbnail click
  thumbnails.forEach((thumb, index) => {
    thumb.addEventListener('click', () => {
      currentImageIndex = index;
      updateGalleryDisplay();
    });
  });

  // Navigation buttons
  if (prevBtn) {
    prevBtn.addEventListener('click', () => {
      currentImageIndex = (currentImageIndex - 1 + allImages.length) % allImages.length;
      updateGalleryDisplay();
    });
  }

  if (nextBtn) {
    nextBtn.addEventListener('click', () => {
      currentImageIndex = (currentImageIndex + 1) % allImages.length;
      updateGalleryDisplay();
    });
  }

  function updateGalleryDisplay() {
    if (allImages[currentImageIndex]) {
      mainImage.style.opacity = '0.7';
      mainImage.src = allImages[currentImageIndex];

      setTimeout(() => {
        mainImage.style.opacity = '1';
      }, 100);
    }

    // Update active thumbnail
    thumbnails.forEach((thumb, index) => {
      thumb.classList.toggle('active', index === currentImageIndex);
    });

    // Scroll thumbnails into view
    if (thumbnails[currentImageIndex]) {
      const activeThumb = thumbnails[currentImageIndex];
      const thumbContainer = galleryThumbnails;
      const scrollLeft = activeThumb.offsetLeft - thumbContainer.offsetWidth / 2 + activeThumb.offsetWidth / 2;

      thumbContainer.scrollTo({
        left: Math.max(0, scrollLeft),
        behavior: 'smooth'
      });
    }
  }

  // Keyboard navigation
  document.addEventListener('keydown', (e) => {
    if (e.key === 'ArrowLeft') {
      currentImageIndex = (currentImageIndex - 1 + allImages.length) % allImages.length;
      updateGalleryDisplay();
    } else if (e.key === 'ArrowRight') {
      currentImageIndex = (currentImageIndex + 1) % allImages.length;
      updateGalleryDisplay();
    }
  });

  // ========================================
  // ACCORDION FUNCTIONALITY
  // ======================================== 

  const accordionHeaders = document.querySelectorAll('.accordion-header');

  accordionHeaders.forEach(header => {
    header.addEventListener('click', () => {
      const accordionItem = header.closest('.accordion-item');

      // Toggle current accordion
      accordionItem.classList.toggle('active');

      // Smooth scroll to expanded content if needed
      if (accordionItem.classList.contains('active') && window.innerWidth <= 768) {
        setTimeout(() => {
          accordionItem.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 300);
      }
    });

    // Open first accordion by default (Description)
    if (header.dataset.section === 'description') {
      header.closest('.accordion-item').classList.add('active');
    }
  });

  // ========================================
  // PRICE DROPDOWN
  // ========================================

  const priceToggle = document.querySelector('.price-toggle');
  const priceDropdown = document.querySelector('.price-dropdown');
  const priceToggleIcon = document.querySelector('.price-toggle-icon');

  if (priceToggle && priceDropdown) {
    const setPriceOpen = (open) => {
      priceToggle.setAttribute('aria-expanded', String(open));
      priceDropdown.hidden = !open;
      priceDropdown.classList.toggle('is-open', open);
      if (priceToggleIcon) {
        priceToggleIcon.style.transform = open ? 'rotate(180deg)' : 'rotate(0deg)';
      }
    };

    // Ensure collapsed on load
    setPriceOpen(false);

    priceToggle.addEventListener('click', () => {
      const isExpanded = priceToggle.getAttribute('aria-expanded') === 'true';
      setPriceOpen(!isExpanded);
    });
  }

  // ========================================
  // DOWNLOAD ITINERARY
  // ========================================

  const downloadBtn = document.querySelector('.btn-download-itinerary');
  if (downloadBtn) {
    downloadBtn.addEventListener('click', () => {
      const filePath = downloadBtn.getAttribute('data-file');

      if (filePath && filePath.trim() !== '') {
        // Create a temporary link and trigger download
        const link = document.createElement('a');
        link.href = filePath;
        link.download = filePath.split('/').pop(); // Use filename from path
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
      } else {
        alert('Itinerary file not available for this tour.');
      }
    });
  }

  // ========================================
  // MAIN IMAGE TRANSITION
  // ========================================

  if (mainImage) {
    mainImage.style.transition = 'opacity 0.3s ease';
  }

  // ========================================
  // SHARE FUNCTIONALITY (Optional Enhancement)
  // ========================================

  function shareOnSocial(platform) {
    const url = window.location.href;
    const title = document.querySelector('.tour-title')?.textContent || 'Check out this tour!';

    const shareUrls = {
      twitter: `https://twitter.com/intent/tweet?url=${encodeURIComponent(url)}&text=${encodeURIComponent(title)}`,
      facebook: `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`,
      whatsapp: `https://wa.me/?text=${encodeURIComponent(title + ' ' + url)}`
    };

    if (shareUrls[platform]) {
      window.open(shareUrls[platform], '_blank', 'width=600,height=400');
    }
  }

  // Expose function globally if needed
  window.shareOnSocial = shareOnSocial;

})();
