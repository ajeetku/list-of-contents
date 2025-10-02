/**
 * List of Contents Plugin - Frontend Scripts
 */

(function($) {
    'use strict';

    // Initialize when DOM is ready
    $(document).ready(function() {
        initTableOfContents();
        initDynamicUpdates();
    });

    /**
     * Initialize Table of Contents functionality
     */
    function initTableOfContents() {
        const tocTitle = document.getElementById('list-table-of-contents');
        if (!tocTitle) return;

        const tocNav = tocTitle.nextElementSibling;
        if (!tocNav) return;

        // Get design class from parent
        const designClass = tocTitle.parentNode.className;
        
        // Handle different design behaviors
        if (designClass.includes('design6')) {
            handleDesign6Toggle(tocTitle);
        } else if (designClass.includes('design4') || designClass.includes('design5')) {
            handleColumnLayout(tocNav);
        }

        // Handle general toggle functionality
        if (tocTitle.style.cursor === 'pointer') {
            handleGeneralToggle(tocTitle, tocNav);
        }

        // Add smooth scrolling to TOC links
        addSmoothScrolling();
    }

    /**
     * Handle Design 6 specific toggle behavior
     */
    function handleDesign6Toggle(tocTitle) {
        tocTitle.addEventListener('click', function() {
            const parent = tocTitle.parentNode;
            if (parent.className.includes('loc-closed')) {
                parent.classList.remove('loc-closed');
            } else {
                parent.classList.add('loc-closed');
            }
        });
    }

    /**
     * Handle general toggle functionality
     */
    function handleGeneralToggle(tocTitle, tocNav) {
        // Initially hide if toggle is enabled
        if (tocTitle.style.cursor === 'pointer') {
            tocNav.style.display = 'none';
        }

        tocTitle.addEventListener('click', function() {
            if (tocNav.style.display === 'none') {
                tocNav.style.display = 'block';
                tocTitle.classList.add('active');
            } else {
                tocNav.style.display = 'none';
                tocTitle.classList.remove('active');
            }
        });
    }

    /**
     * Handle column layout for designs 4 and 5
     */
    function handleColumnLayout(tocNav) {
        const tocItems = tocNav.querySelectorAll('li');
        const totalItems = tocItems.length;
        
        if (totalItems > 0) {
            const midPoint = Math.ceil(totalItems / 2);
            
            // Create two columns
            const leftColumn = document.createElement('ol');
            const rightColumn = document.createElement('ol');
            
            leftColumn.className = 'toc-column left-column';
            rightColumn.className = 'toc-column right-column';
            
            tocItems.forEach((item, index) => {
                if (index < midPoint) {
                    leftColumn.appendChild(item.cloneNode(true));
                } else {
                    rightColumn.appendChild(item.cloneNode(true));
                }
            });
            
            // Clear and replace with columns
            tocNav.innerHTML = '';
            tocNav.appendChild(leftColumn);
            tocNav.appendChild(rightColumn);
        }
    }

    /**
     * Add smooth scrolling to TOC links
     */
    function addSmoothScrolling() {
        const tocLinks = document.querySelectorAll('.loc-toc a[href^="#"]');
        
        tocLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                
                const targetId = this.getAttribute('href').substring(1);
                const targetElement = document.getElementById(targetId);
                
                if (targetElement) {
                    const headerOffset = 80; // Adjust based on your header height
                    const elementPosition = targetElement.getBoundingClientRect().top;
                    const offsetPosition = elementPosition + window.pageYOffset - headerOffset;
                    
                    window.scrollTo({
                        top: offsetPosition,
                        behavior: 'smooth'
                    });
                    
                    // Add highlight effect
                    targetElement.classList.add('toc-highlight');
                    setTimeout(() => {
                        targetElement.classList.remove('toc-highlight');
                    }, 2000);
                }
            });
        });
    }

    /**
     * Initialize dynamic updates
     */
    function initDynamicUpdates() {
        // Listen for content changes (useful for dynamic content loading)
        if (typeof MutationObserver !== 'undefined') {
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'childList') {
                        // Check if new TOC elements were added
                        mutation.addedNodes.forEach(function(node) {
                            if (node.nodeType === 1 && node.classList && node.classList.contains('loc-toc')) {
                                // Reinitialize TOC for new content
                                setTimeout(initTableOfContents, 100);
                            }
                        });
                    }
                });
            });

            // Observe the document body for changes
            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
        }

        // Handle AJAX content updates
        $(document).on('ajaxComplete', function(event, xhr, settings) {
            if (settings.url && settings.url.includes('admin-ajax.php')) {
                setTimeout(initTableOfContents, 100);
            }
        });
    }

    /**
     * Update TOC when content changes (for use with AJAX)
     */
    window.updateTOCContent = function(postId, content, nonce) {
        $.ajax({
            url: ajaxurl || '/wp-admin/admin-ajax.php',
            type: 'POST',
            data: {
                action: 'update_toc_content',
                post_id: postId,
                content: content,
                nonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    // Update TOC blocks in the content
                    updateTOCBlocks(response.data.toc_blocks);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error updating TOC:', error);
            }
        });
    };

    /**
     * Update TOC blocks in the content
     */
    function updateTOCBlocks(tocBlocks) {
        tocBlocks.forEach(function(blockData) {
            const blockElement = document.querySelector(`[data-toc-block="${blockData.attributes.design}"]`);
            if (blockElement) {
                // Update the block content
                blockElement.innerHTML = blockData.block;
            }
        });
        
        // Reinitialize TOC functionality
        initTableOfContents();
    }

    /**
     * Highlight current section in TOC while scrolling
     */
    function initScrollSpy() {
        const sections = document.querySelectorAll('h1, h2, h3, h4, h5, h6');
        const tocLinks = document.querySelectorAll('.loc-toc a[href^="#"]');
        
        if (sections.length === 0 || tocLinks.length === 0) return;
        
        window.addEventListener('scroll', function() {
            let current = '';
            
            sections.forEach(section => {
                const sectionTop = section.offsetTop;
                const sectionHeight = section.clientHeight;
                
                if (window.pageYOffset >= (sectionTop - 200)) {
                    current = section.getAttribute('id');
                }
            });
            
            tocLinks.forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href') === `#${current}`) {
                    link.classList.add('active');
                }
            });
        });
    }

    // Initialize scroll spy
    $(window).on('load', function() {
        setTimeout(initScrollSpy, 500);
    });

})(jQuery);