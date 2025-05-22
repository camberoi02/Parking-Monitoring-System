/**
 * Aggressive overscroll prevention script
 * This handles various edge cases for touchpad, touch, and mouse wheel events
 */

(function() {
    // Apply immediately before DOM is ready for maximum effectiveness
    let html = document.documentElement;
    let body = document.body;
    
    // Function to lock scroll at boundaries
    function lockScroll() {
        // Save the current scroll position
        let scrollTop = Math.max(html.scrollTop, body.scrollTop);
        
        // Apply a function that keeps restoring the scroll position
        // This helps combat the elastic bounce effect
        let lockScrollPosition = function() {
            window.scrollTo(0, scrollTop);
        };
        
        // Apply immediately and then several times to catch any bounce
        lockScrollPosition();
        for (let i = 0; i < 10; i++) {
            setTimeout(lockScrollPosition, 10 * i);
        }
    }
    
    // Detect MacOS for special handling (more aggressive)
    const isMac = navigator.platform.toUpperCase().indexOf('MAC') >= 0;
    
    // Modern passive false events
    function preventDefault(e) {
        if (e.cancelable) {
            e.preventDefault();
        }
    }
    
    // Add complete set of event handlers
    document.addEventListener('DOMContentLoaded', function() {
        // Additional MacOS specific handling
        if (isMac) {
            // More aggressive for Mac touchpads
            window.addEventListener('scroll', function() {
                const scrollTop = Math.max(html.scrollTop, body.scrollTop);
                const scrollHeight = Math.max(html.scrollHeight, body.scrollHeight);
                const clientHeight = html.clientHeight;
                
                // Detect boundaries
                if (scrollTop <= 0 || scrollTop + clientHeight >= scrollHeight - 1) {
                    lockScroll();
                }
            }, { passive: true });
            
            // Add a class to the body for additional CSS handling
            body.classList.add('macos');
        }
        
        // Prevent default on touchmove at boundaries
        document.addEventListener('touchmove', function(e) {
            const scrollTop = Math.max(html.scrollTop, body.scrollTop);
            const scrollHeight = Math.max(html.scrollHeight, body.scrollHeight);
            const clientHeight = html.clientHeight;
            
            // Allow scrolling in specifically scrollable elements
            if (e.target.closest('.table-responsive, .overflow-auto, .modal-body, [style*="overflow"]')) {
                // Check if the scrollable element is at its boundary
                const element = e.target.closest('.table-responsive, .overflow-auto, .modal-body, [style*="overflow"]');
                if ((element.scrollTop <= 0 && e.touches[0].screenY > e.touches[0].screenY) || 
                    (element.scrollTop + element.clientHeight >= element.scrollHeight && e.touches[0].screenY < e.touches[0].screenY)) {
                    preventDefault(e);
                }
                return;
            }
            
            // Prevent for the main document
            if (scrollTop <= 0 || scrollTop + clientHeight >= scrollHeight - 1) {
                preventDefault(e);
            }
        }, { passive: false });
    });
    
    // Create style element with critical CSS
    function applyOverscrollCSS() {
        const style = document.createElement('style');
        style.textContent = `
            html, body {
                overscroll-behavior: none !important;
                overscroll-behavior-y: none !important;
                overflow-x: hidden;
                max-width: 100vw;
                touch-action: pan-y;
            }
            .macos {
                -webkit-overflow-scrolling: auto !important;
            }
        `;
        document.head.appendChild(style);
    }
    
    // Apply CSS as early as possible
    if (document.head) {
        applyOverscrollCSS();
    } else {
        document.addEventListener('DOMContentLoaded', applyOverscrollCSS);
    }
})();
