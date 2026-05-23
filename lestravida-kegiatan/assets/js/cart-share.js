/** Cart Share Script
 * ============================================
 * This script enables sharing the cart page URL using the native Share API if available,
 * or falls back to copying the URL to the clipboard, and finally alerts the user to copy manually if all else fails.
 * 
 * Usage:
 * Add the attribute `data-lv-share` to any element (e.g., a button) that should trigger the share functionality when clicked.
 * 
 * Example:
 * <button data-lv-share>Share Cart</button>
 * 
 * Note: This script should be included in the cart page where the share functionality is needed.
 * ============================================ 
 */
(function () {

    function handleShare(e) {

        e.preventDefault();

        var url = window.location.href;
        var title = document.title;

        /**
         * ============================================
         * NATIVE SHARE API
         * ============================================
         */
        if (navigator.share) {

            navigator.share({
                title: title,
                url: url
            });

            return;
        }

        /**
         * ============================================
         * CLIPBOARD FALLBACK
         * ============================================
         */
        if (navigator.clipboard) {

            navigator.clipboard
                .writeText(url)
                .then(function () {

                    alert('Link disalin 👍');

                })
                .catch(function () {

                    alert('Salin link manual ya 😊');

                });

            return;
        }

        /**
         * ============================================
         * LAST FALLBACK
         * ============================================
         */
        alert('Salin link manual ya 😊');
    }

    /**
     * ================================================
     * INIT
     * ================================================
     */
    function initShareButtons() {

        document
            .querySelectorAll('[data-lv-share]')
            .forEach(function (el) {

                /**
                 * Prevent duplicate listener
                 */
                if (el.dataset.lvShareReady) {
                    return;
                }

                el.dataset.lvShareReady = '1';

                el.addEventListener(
                    'click',
                    handleShare
                );
            });
    }

    /**
     * ================================================
     * DOM READY
     * ================================================
     */
    if (document.readyState === 'loading') {

        document.addEventListener(
            'DOMContentLoaded',
            initShareButtons
        );

    } else {

        initShareButtons();
    }

})();