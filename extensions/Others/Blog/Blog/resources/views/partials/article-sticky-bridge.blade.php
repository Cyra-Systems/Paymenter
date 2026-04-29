@once
    <script>
        (function () {
            var cleanup = null;

            function visibleTopOffset() {
                var candidates = Array.from(document.querySelectorAll('nav, header, [data-sticky-header], .sticky, .fixed'));
                var tallest = 0;

                candidates.forEach(function (node) {
                    if (!(node instanceof HTMLElement)) {
                        return;
                    }

                    var style = window.getComputedStyle(node);
                    var rect = node.getBoundingClientRect();

                    if (!['fixed', 'sticky'].includes(style.position)) {
                        return;
                    }

                    if (rect.bottom <= 0 || rect.top > 24 || rect.height <= 0) {
                        return;
                    }

                    tallest = Math.max(tallest, Math.ceil(rect.bottom));
                });

                return Math.max(72, tallest + 16);
            }

            function resetSidebar(sidebar, panel) {
                sidebar.style.minHeight = '';
                panel.style.position = '';
                panel.style.top = '';
                panel.style.left = '';
                panel.style.width = '';
                panel.style.maxHeight = '';
                panel.style.overflowY = '';
            }

            function initStickySidebar() {
                var sidebar = document.querySelector('.blog-article-sidebar');
                var panel = sidebar ? sidebar.querySelector('.blog-article-sidebar-panel') : null;
                var layout = document.querySelector('.blog-article-layout');
                var main = document.querySelector('.blog-article-main');

                if (!(sidebar instanceof HTMLElement) || !(panel instanceof HTMLElement) || !(layout instanceof HTMLElement) || !(main instanceof HTMLElement)) {
                    return function () {};
                }

                var mediaQuery = window.matchMedia('(min-width: 1024px)');
                var frameId = null;

                function sync() {
                    frameId = null;

                    if (!mediaQuery.matches) {
                        resetSidebar(sidebar, panel);
                        return;
                    }

                    var top = visibleTopOffset();
                    var scrollY = window.scrollY;
                    var sidebarRect = sidebar.getBoundingClientRect();
                    var mainRect = main.getBoundingClientRect();
                    var sidebarTop = scrollY + sidebarRect.top;
                    var articleTop = scrollY + mainRect.top;
                    var articleHeight = main.offsetHeight;

                    resetSidebar(sidebar, panel);

                    // Read the panel in normal flow first, then decide whether it should sit
                    // inline, pin to the viewport, or park at the end of the article.
                    var naturalPanelHeight = panel.offsetHeight;
                    var stopTop = articleTop + articleHeight - naturalPanelHeight;
                    var fixedWidth = Math.round(sidebarRect.width);
                    var fixedLeft = Math.round(sidebarRect.left);
                    var maxHeight = Math.max(240, Math.round(window.innerHeight - top - 24));

                    sidebar.style.minHeight = Math.max(articleHeight, naturalPanelHeight) + 'px';
                    panel.style.maxHeight = maxHeight + 'px';
                    panel.style.overflowY = 'auto';

                    if (naturalPanelHeight >= articleHeight || scrollY + top <= sidebarTop) {
                        panel.style.position = 'relative';
                        panel.style.top = '0';
                        panel.style.left = '0';
                        panel.style.width = '100%';

                        return;
                    }

                    if (scrollY + top >= stopTop) {
                        panel.style.position = 'absolute';
                        panel.style.top = Math.max(0, articleHeight - naturalPanelHeight) + 'px';
                        panel.style.left = '0';
                        panel.style.width = '100%';

                        return;
                    }

                    panel.style.position = 'fixed';
                    panel.style.top = top + 'px';
                    panel.style.left = fixedLeft + 'px';
                    panel.style.width = fixedWidth + 'px';
                }

                function queueSync() {
                    if (frameId !== null) {
                        window.cancelAnimationFrame(frameId);
                    }

                    frameId = window.requestAnimationFrame(sync);
                }

                queueSync();
                window.addEventListener('scroll', queueSync, { passive: true });
                window.addEventListener('resize', queueSync, { passive: true });

                if (typeof mediaQuery.addEventListener === 'function') {
                    mediaQuery.addEventListener('change', queueSync);
                } else if (typeof mediaQuery.addListener === 'function') {
                    mediaQuery.addListener(queueSync);
                }

                return function () {
                    if (frameId !== null) {
                        window.cancelAnimationFrame(frameId);
                    }

                    window.removeEventListener('scroll', queueSync);
                    window.removeEventListener('resize', queueSync);

                    if (typeof mediaQuery.removeEventListener === 'function') {
                        mediaQuery.removeEventListener('change', queueSync);
                    } else if (typeof mediaQuery.removeListener === 'function') {
                        mediaQuery.removeListener(queueSync);
                    }

                    resetSidebar(sidebar, panel);
                };
            }

            function bootStickySidebar() {
                if (typeof cleanup === 'function') {
                    cleanup();
                }

                // Livewire navigation and theme shells can rebuild this part of the page,
                // so we always restart the sticky controller from a clean state.
                cleanup = initStickySidebar();
            }

            bootStickySidebar();

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', bootStickySidebar, { once: true });
            }

            window.addEventListener('load', bootStickySidebar, { passive: true });
            document.addEventListener('livewire:navigated', bootStickySidebar);
        })();
    </script>
@endonce
