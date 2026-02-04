/**
 * Auto Lightbox for Blocks
 * Automatically adds GLightbox to images in specified blocks
 * 
 * @package Block_Editor_Auto_Lightbox
 * @since 1.0.0
 */

(function () {
    'use strict';

    /**
     * Helper to extract the URL of the largest available image
     * 
     * @param {HTMLImageElement} img - The image element
     * @return {string|null} The original image URL or null
     */
    function getOriginalImageUrl(img) {
        // 1. Try to get the largest image from srcset (or data-srcset for lazy load)
        const srcsetAttr = img.getAttribute('data-srcset') || img.srcset;
        let urlFromSrcset = null;

        if (srcsetAttr) {
            const srcsetArray = srcsetAttr.split(',').map(item => {
                item = item.trim();

                // Safe split for URL and width descriptor
                const spaceIndex = item.lastIndexOf(' ');
                if (spaceIndex === -1) {
                    return { url: item, width: 0 };
                }

                const url = item.substring(0, spaceIndex);
                const widthStr = item.substring(spaceIndex + 1).replace('w', '');

                return {
                    url: url,
                    width: parseInt(widthStr) || 0
                };
            });

            // Sort by width descending
            srcsetArray.sort((a, b) => b.width - a.width);

            if (srcsetArray.length > 0) {
                urlFromSrcset = srcsetArray[0].url;
            }
        }

        // 2. Fallback to data-src or src
        let candidateUrl = urlFromSrcset || img.getAttribute('data-src') || img.src;

        // Skip invalid or empty URLs
        if (!candidateUrl || candidateUrl.startsWith('data:')) {
            return null;
        }

        // 3. Strip WordPress size suffixes (e.g. -150x150, -1024x768)
        const originalUrl = candidateUrl.replace(/-\d+x\d+(\.[a-zA-Z]+)$/, '$1');

        return originalUrl;
    }

    /**
     * Check if image should be excluded from lightbox
     * 
     * @param {HTMLImageElement} img - The image element
     * @return {boolean} True if should be excluded
     */
    function shouldExcludeImage(img) {
        // Check if image is lazy loaded (has data-src/srcset)
        const isLazy = img.getAttribute('data-src') || img.getAttribute('data-srcset');

        // Skip invalid images, SVGs, or placeholders (unless lazy loaded)
        if ((!img.src && !isLazy) ||
            (img.src && img.src.toLowerCase().endsWith('.svg')) ||
            (img.src && img.src.toLowerCase().includes('.svg?')) ||
            (img.src && img.src.startsWith('data:') && !isLazy) ||
            img.closest('iframe')) {
            return true;
        }

        return false;
    }

    // Track processed containers and global lightbox
    const processedContainers = new WeakSet();
    let globalLightbox = null;

    /**
     * Process images and add lightbox functionality
     * 
     * @param {string} blockSelector - CSS selector for target blocks
     * @param {boolean} groupPageImages - Whether to group ALL images on page
     */
    function processImages(blockSelector, groupPageImages) {
        if (!blockSelector) return;

        const selectors = blockSelector.split(',').map(s => s.trim()).filter(s => s.length > 0);

        selectors.forEach((selector, selectorIndex) => {
            const containers = document.querySelectorAll(selector);

            containers.forEach((container, containerIndex) => {
                // Prevent duplicate processing of the same container
                if (processedContainers.has(container)) {
                    return;
                }

                // Safety: If this container is a wp-block-image (single) BUT it is inside a wp-block-gallery, 
                // SKIP IT. Let the gallery handle it.
                if (container.classList.contains('wp-block-image') && container.closest('.wp-block-gallery')) {
                    return;
                }

                const images = container.querySelectorAll('img');
                if (images.length === 0) return;

                // Unique selector class for this block
                const blockUniqueId = `beal-group-${selectorIndex}-${containerIndex}`;
                const finalSelectorClass = groupPageImages ? 'beal-global' : blockUniqueId;

                images.forEach((img) => {
                    // Skip excluded images
                    if (shouldExcludeImage(img)) {
                        return;
                    }

                    const originalUrl = getOriginalImageUrl(img);
                    if (!originalUrl) {
                        return;
                    }

                    let link;
                    // Wrap image in a link if not already wrapped
                    if (img.parentElement.tagName !== 'A') {
                        link = document.createElement('a');
                        link.href = originalUrl;
                        link.classList.add('glightbox');
                        link.classList.add(finalSelectorClass);
                        link.setAttribute('data-description', img.alt || '');
                        link.setAttribute('aria-label', img.alt ? 'View larger image: ' + img.alt : 'View larger image');

                        img.parentNode.insertBefore(link, img);
                        link.appendChild(img);
                    } else {
                        // If already wrapped, ensure it works with lightbox
                        link = img.parentElement;
                        link.classList.add('glightbox');
                        link.classList.add(finalSelectorClass);

                        // Force href to original URL if it points to itself, is empty, or IS NOT an image link (e.g. attachment page)
                        const cleanHref = link.href.split('?')[0].toLowerCase();
                        const isImageLink = /\.(jpeg|jpg|gif|png|webp|bmp|svg)$/i.test(cleanHref);

                        if (!isImageLink || link.href.includes(img.src) || link.href === '') {
                            link.href = originalUrl;
                            link.setAttribute('data-type', 'image'); // Hint GLightbox it's an image
                        }

                        // Add description if not present
                        if (!link.getAttribute('data-description') && img.alt) {
                            link.setAttribute('data-description', img.alt);
                        }

                        // Add accessibility label if missing
                        if (!link.hasAttribute('aria-label')) {
                            link.setAttribute('aria-label', img.alt ? 'View larger image: ' + img.alt : 'View larger image');
                        }
                    }
                });

                // Initialize GLightbox for this specific block if not grouping globally
                if (!groupPageImages) {
                    GLightbox({
                        selector: '.' + blockUniqueId,
                        touchNavigation: bealSettings.touchNavigation,
                        loop: bealSettings.loop,
                        autoplayVideos: bealSettings.autoplayVideos,
                        closeButton: bealSettings.closeButton,
                        closeOnOutsideClick: bealSettings.closeOnOutsideClick,
                        preload: bealSettings.preload,
                        descPosition: false
                    });
                }

                processedContainers.add(container);
            });
        });

        // If global grouping is enabled, initialize/reload the global lightbox
        if (groupPageImages) {
            if (!globalLightbox) {
                globalLightbox = GLightbox({
                    selector: '.beal-global',
                    touchNavigation: bealSettings.touchNavigation,
                    loop: bealSettings.loop,
                    autoplayVideos: bealSettings.autoplayVideos,
                    closeButton: bealSettings.closeButton,
                    closeOnOutsideClick: bealSettings.closeOnOutsideClick,
                    preload: bealSettings.preload,
                    descPosition: false
                });
            } else {
                globalLightbox.reload();
            }
        }
    }

    /**
     * Initialize lightbox
     */
    function initLightbox() {
        // Check if GLightbox is loaded
        if (typeof GLightbox === 'undefined') {
            console.error('Block Editor Auto Lightbox: GLightbox library not loaded');
            return;
        }

        // Check if settings are available
        if (typeof bealSettings === 'undefined') {
            console.error('Block Editor Auto Lightbox: Settings not found');
            return;
        }

        // Build selector string
        let selectors = [];
        // Handle new standard flags (with fallbacks/defaults)
        if (bealSettings.hasAutoWpImage !== false) selectors.push('.wp-block-image');
        if (bealSettings.hasAutoWpGallery !== false) selectors.push('.wp-block-gallery');

        // Add custom selectors
        if (bealSettings.blockSelector) {
            // Split by comma or newline to handle textarea input
            const customSelectors = bealSettings.blockSelector.split(/[\n,]+/).map(s => s.trim()).filter(s => s.length > 0);
            selectors = selectors.concat(customSelectors);
        }

        // Deduplicate
        selectors = [...new Set(selectors)];
        const finalSelectorString = selectors.join(', ');

        // Process initial images
        processImages(finalSelectorString, bealSettings.groupPageImages);

        // Handle dynamically loaded content (e.g., infinite scroll, AJAX)
        const observer = new MutationObserver(function (mutations) {
            let shouldReprocess = false;
            mutations.forEach(function (mutation) {
                if (mutation.addedNodes.length) {
                    shouldReprocess = true;
                }
            });

            if (shouldReprocess) {
                processImages(finalSelectorString, bealSettings.groupPageImages);
            }
        });

        // Observe the entire document for changes
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initLightbox);
    } else {
        initLightbox();
    }

})();
