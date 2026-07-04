(function () {
    const doc = document;
    const defaultCenter = { lat: 31.5204, lng: 74.3587 };
    let googleMapsPromise = null;
    let leafletPromise = null;

    function openSubscriptionGate() {
        const modalEl = doc.getElementById('subscriptionGateModal');
        if (modalEl && typeof bootstrap !== 'undefined') {
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
            return;
        }
        if (modalEl?.dataset?.subscriptionPageUrl) {
            window.location.href = modalEl.dataset.subscriptionPageUrl;
            return;
        }
        if (window.MEDISPHERE?.baseUrl) {
            window.location.href = `${window.MEDISPHERE.baseUrl}/index.php?route=payments/subscriptions`;
        }
    }

    function isPremiumLocked(element) {
        return element && element.dataset && element.dataset.premiumLocked === '1';
    }

    function guardPremiumAction(event, element) {
        if (!isPremiumLocked(element)) return false;
        event && event.preventDefault();
        event && event.stopImmediatePropagation && event.stopImmediatePropagation();
        event && event.stopPropagation();
        openSubscriptionGate();
        return true;
    }

    function isMapPremiumLocked(state) {
        return state?.container?.dataset?.premiumLocked === '1';
    }

    const splashSeenKey = 'medisphereSplashSeen';

    function hasSplashBeenSeen() {
        try {
            return window.sessionStorage?.getItem(splashSeenKey) === '1';
        } catch (error) {
            return false;
        }
    }

    function markSplashSeen() {
        try {
            window.sessionStorage?.setItem(splashSeenKey, '1');
        } catch (error) { }
    }

    function initPremiumSplash() {
        const splash = doc.getElementById('premiumSplash');
        if (!splash) return;

        if (hasSplashBeenSeen()) {
            splash.remove();
            doc.documentElement.classList.remove('splash-first-visit');
            doc.documentElement.classList.add('splash-seen');
            doc.body.classList.add('splash-complete');
            return;
        }

        const duration = Number.parseInt(splash.dataset.duration || '5000', 10);
        const displayDuration = Number.isFinite(duration) && duration > 0 ? duration : 5000;
        const fadeDuration = 1200;
        const videos = Array.from(splash.querySelectorAll('[data-splash-video]'));
        const reduceMotion = window.matchMedia?.('(prefers-reduced-motion: reduce)')?.matches;
        let activeVideoIndex = 0;
        let sliderTimer = null;

        const playVideo = (video) => {
            if (!video) return;
            video.muted = true;
            video.playsInline = true;
            video.play().catch(() => { });
        };

        const showVideo = (nextIndex) => {
            if (!videos.length) return;
            activeVideoIndex = nextIndex % videos.length;
            videos.forEach((video, index) => {
                const active = index === activeVideoIndex;
                video.classList.toggle('is-active', active);
                if (active) playVideo(video);
            });
        };

        markSplashSeen();
        splash.style.setProperty('--splash-duration', `${displayDuration}ms`);
        doc.body.classList.add('splash-active');
        window.requestAnimationFrame(() => {
            splash.classList.add('is-running');
        });

        if (videos.length) {
            videos.forEach((video, index) => {
                video.muted = true;
                video.playsInline = true;
                video.classList.toggle('is-active', index === 0);
                if (index === 0) playVideo(video);
            });
        }

        if (videos.length > 1 && !reduceMotion) {
            const slideDuration = Math.max(1200, Math.floor(displayDuration / videos.length));
            sliderTimer = window.setInterval(() => {
                showVideo(activeVideoIndex + 1);
            }, slideDuration);
        }

        window.setTimeout(() => {
            if (sliderTimer) window.clearInterval(sliderTimer);
            splash.classList.add('is-hiding');
            doc.body.classList.remove('splash-active');
            doc.body.classList.add('splash-revealing');
            doc.documentElement.classList.remove('splash-first-visit');

            window.setTimeout(() => {
                videos.forEach((video) => video.pause());
                splash.remove();
                doc.body.classList.remove('splash-revealing');
                doc.body.classList.add('splash-complete');
                doc.documentElement.classList.add('splash-seen');
            }, fadeDuration);
        }, displayDuration);
    }

    function initAOS() {
        if (window.AOS) AOS.init({ duration: 900, once: true, easing: 'ease-out-cubic', offset: 60 });
    }

    function initScrollReveal() {
        const reveals = doc.querySelectorAll('.reveal');
        if (!reveals.length) return;
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
        reveals.forEach((el) => observer.observe(el));
    }

    function init3DTilt() {
        doc.querySelectorAll('.tilt-3d').forEach((card) => {
            card.addEventListener('mousemove', (e) => {
                const rect = card.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                const centerX = rect.width / 2;
                const centerY = rect.height / 2;
                const rotateX = ((y - centerY) / centerY) * -8;
                const rotateY = ((x - centerX) / centerX) * 8;
                card.style.setProperty('--mouse-x', `${(x / rect.width) * 100}%`);
                card.style.setProperty('--mouse-y', `${(y / rect.height) * 100}%`);
                card.style.transform = `perspective(800px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateY(-4px) scale(1.01)`;
            });
            card.addEventListener('mouseleave', () => {
                card.style.transform = '';
            });
        });
    }

    function initHero3D() {
        const scene = doc.getElementById('hero3dScene');
        const core = doc.getElementById('hero3dCore');
        if (!scene || !core) return;

        scene.addEventListener('mousemove', (e) => {
            const rect = scene.getBoundingClientRect();
            const x = (e.clientX - rect.left) / rect.width - 0.5;
            const y = (e.clientY - rect.top) / rect.height - 0.5;
            core.style.animationPlayState = 'paused';
            core.style.transform = `rotateY(${x * 30}deg) rotateX(${y * -20 + 10}deg)`;
        });

        scene.addEventListener('mouseleave', () => {
            core.style.animationPlayState = 'running';
            core.style.transform = '';
        });
    }

    function initMagneticButtons() {
        doc.querySelectorAll('.magnetic-btn').forEach((btn) => {
            btn.addEventListener('mousemove', (e) => {
                const rect = btn.getBoundingClientRect();
                const x = e.clientX - rect.left - rect.width / 2;
                const y = e.clientY - rect.top - rect.height / 2;
                btn.style.transform = `translate(${x * 0.15}px, ${y * 0.15}px) translateY(-3px) scale(1.02)`;
            });
            btn.addEventListener('mouseleave', () => {
                btn.style.transform = '';
            });
        });
    }

    function initParallaxOrbs() {
        const orbs = doc.querySelectorAll('.orb');
        if (!orbs.length) return;
        let ticking = false;
        window.addEventListener('scroll', () => {
            if (ticking) return;
            ticking = true;
            requestAnimationFrame(() => {
                const scrollY = window.scrollY;
                orbs.forEach((orb, i) => {
                    const speed = (i + 1) * 0.04;
                    orb.style.transform = `translateY(${scrollY * speed}px)`;
                });
                ticking = false;
            });
        }, { passive: true });
    }

    function initGSAPAnimations() {
        if (!window.gsap) return;
        const statCards = doc.querySelectorAll('.stat-card');
        statCards.forEach((card) => {
            const num = card.querySelector('h2');
            if (!num) return;
            const target = parseInt(num.textContent, 10);
            if (Number.isNaN(target)) return;
            num.textContent = '0';
            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (!entry.isIntersecting) return;
                    gsap.to({ val: 0 }, {
                        val: target,
                        duration: 1.6,
                        ease: 'power2.out',
                        onUpdate: function () {
                            num.textContent = Math.round(this.targets()[0].val);
                        }
                    });
                    observer.unobserve(card);
                });
            }, { threshold: 0.5 });
            observer.observe(card);
        });
    }

    function initTheme() {
        const stored = localStorage.getItem('medisphere-theme') || 'light';
        doc.documentElement.setAttribute('data-theme', stored);
        const toggle = doc.getElementById('themeToggle');
        if (toggle) {
            const updateIcon = () => {
                const isDark = doc.documentElement.getAttribute('data-theme') === 'dark';
                toggle.innerHTML = isDark
                    ? '<i class="fa-solid fa-sun"></i>'
                    : '<i class="fa-solid fa-moon"></i>';
            };
            updateIcon();
            toggle.addEventListener('click', () => {
                const current = doc.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
                doc.documentElement.setAttribute('data-theme', current);
                localStorage.setItem('medisphere-theme', current);
                updateIcon();
            });
        }
    }

    function initPageTranslator() {
        const config = window.MEDISPHERE || {};
        const locale = config.locale || 'en';
        const defaultLocale = config.defaultLocale || 'en';
        const endpoint = config.pageTranslatorUrl;
        if (!endpoint || !window.fetch || locale === defaultLocale || !doc.body) return;

        const attrNames = ['placeholder', 'aria-label', 'title', 'alt'];
        const buttonValueTypes = new Set(['button', 'submit', 'reset']);
        const skipSelector = [
            'script',
            'style',
            'noscript',
            'template',
            'svg',
            'canvas',
            'iframe',
            'video',
            'audio',
            'code',
            'pre',
            '[data-no-translate]',
            '[data-no-page-translate]',
            '[translate="no"]',
            '.notranslate'
        ].join(',');
        const letterPattern = (() => {
            try {
                return new RegExp('\\p{L}', 'u');
            } catch (error) {
                return /[A-Za-z]/;
            }
        })();
        const cacheKey = `medisphere-page-translations:${locale}`;
        const textState = new WeakMap();
        const attrState = new WeakMap();
        const pending = new Map();
        let memory = loadMemory();
        let pendingTimer = null;
        let applying = false;

        function loadMemory() {
            try {
                const cached = window.localStorage?.getItem(cacheKey);
                return cached ? JSON.parse(cached) || {} : {};
            } catch (error) {
                return {};
            }
        }

        function saveMemory() {
            try {
                const entries = Object.entries(memory).slice(-700);
                window.localStorage?.setItem(cacheKey, JSON.stringify(Object.fromEntries(entries)));
            } catch (error) { }
        }

        function normalizeText(value) {
            return String(value || '').replace(/\s+/g, ' ').trim();
        }

        function shouldTranslate(value) {
            const text = normalizeText(value);
            if (text.length < 2 || text.length > 1200 || !letterPattern.test(text)) return false;
            if (/^(https?:\/\/|www\.|mailto:)/i.test(text)) return false;
            if (/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(text)) return false;
            if (/^[\d\s.,:%$+\-()[\]#/\\]+$/.test(text)) return false;
            return true;
        }

        function parentElement(node) {
            return node.nodeType === Node.ELEMENT_NODE ? node : node.parentElement;
        }

        function isSkipped(node) {
            const element = parentElement(node);
            return !element || Boolean(element.closest(skipSelector));
        }

        function splitWhitespace(value) {
            const match = String(value).match(/^(\s*)([\s\S]*?)(\s*)$/);
            return {
                before: match ? match[1] : '',
                body: match ? match[2] : String(value),
                after: match ? match[3] : '',
            };
        }

        function hasMemory(source) {
            return Object.prototype.hasOwnProperty.call(memory, source);
        }

        function queueTranslation(source, apply) {
            if (hasMemory(source)) {
                if (memory[source]) apply(memory[source]);
                return;
            }

            if (!pending.has(source)) {
                pending.set(source, []);
            }
            pending.get(source).push(apply);

            window.clearTimeout(pendingTimer);
            pendingTimer = window.setTimeout(flushTranslations, 120);
        }

        function applyTextNode(node, source, translation) {
            if (!translation || normalizeText(translation) === source || !node.parentElement) return;
            const parts = splitWhitespace(node.data);
            applying = true;
            node.data = `${parts.before}${translation}${parts.after}`;
            applying = false;
            textState.set(node, { source, translation });
        }

        function queueTextNode(node) {
            if (!node || node.nodeType !== Node.TEXT_NODE || isSkipped(node)) return;
            const state = textState.get(node);
            if (state && node.data === state.translation) return;

            const source = normalizeText(node.data);
            if (!shouldTranslate(source)) return;
            queueTranslation(source, (translation) => applyTextNode(node, source, translation));
        }

        function elementAttrState(element) {
            let state = attrState.get(element);
            if (!state) {
                state = {};
                attrState.set(element, state);
            }
            return state;
        }

        function applyAttribute(element, attrName, source, translation) {
            if (!translation || normalizeText(translation) === source) return;
            applying = true;
            element.setAttribute(attrName, translation);
            applying = false;
            elementAttrState(element)[attrName] = { source, translation };
        }

        function queueAttribute(element, attrName) {
            if (!element?.hasAttribute?.(attrName) || isSkipped(element)) return;
            const current = element.getAttribute(attrName) || '';
            const state = elementAttrState(element)[attrName];
            if (state && current === state.translation) return;

            const source = normalizeText(current);
            if (!shouldTranslate(source)) return;
            queueTranslation(source, (translation) => applyAttribute(element, attrName, source, translation));
        }

        function queueElementAttributes(element, onlyAttr = null) {
            if (!element || element.nodeType !== Node.ELEMENT_NODE || isSkipped(element)) return;
            const attrs = onlyAttr ? [onlyAttr] : attrNames;
            attrs.forEach((attrName) => queueAttribute(element, attrName));

            if (
                (!onlyAttr || onlyAttr === 'value')
                && element.matches?.('input')
                && buttonValueTypes.has((element.getAttribute('type') || '').toLowerCase())
            ) {
                queueAttribute(element, 'value');
            }
        }

        function scanNode(root) {
            if (!root || isSkipped(root)) return;
            if (root.nodeType === Node.TEXT_NODE) {
                queueTextNode(root);
                return;
            }
            if (root.nodeType !== Node.ELEMENT_NODE) return;

            queueElementAttributes(root);
            root.querySelectorAll?.('*').forEach((element) => queueElementAttributes(element));

            const walker = doc.createTreeWalker(root, NodeFilter.SHOW_TEXT, {
                acceptNode(node) {
                    if (isSkipped(node) || !shouldTranslate(node.data)) {
                        return NodeFilter.FILTER_REJECT;
                    }
                    return NodeFilter.FILTER_ACCEPT;
                }
            });

            let node = walker.nextNode();
            while (node) {
                queueTextNode(node);
                node = walker.nextNode();
            }
        }

        async function requestTranslations(entries) {
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': config.csrf || '',
                },
                body: JSON.stringify({
                    locale,
                    texts: entries.map(([source]) => source),
                }),
            });

            if (!response.ok) {
                throw new Error('Page translation failed');
            }

            const payload = await response.json();
            return payload.translations || {};
        }

        async function flushTranslations() {
            if (!pending.size) return;
            const entries = Array.from(pending.entries());
            pending.clear();

            for (let index = 0; index < entries.length; index += 120) {
                const chunk = entries.slice(index, index + 120);
                try {
                    const translations = await requestTranslations(chunk);
                    chunk.forEach(([source, callbacks]) => {
                        const translation = translations[source];
                        if (typeof translation === 'string' && translation.trim() !== '') {
                            memory[source] = translation;
                            callbacks.forEach((callback) => callback(translation));
                        }
                    });
                } catch (error) {
                    console.warn('MediSphere page translator could not translate this batch.', error);
                }
            }

            saveMemory();
        }

        const observer = new MutationObserver((mutations) => {
            if (applying) return;
            mutations.forEach((mutation) => {
                if (mutation.type === 'childList') {
                    mutation.addedNodes.forEach((node) => scanNode(node));
                    return;
                }
                if (mutation.type === 'characterData') {
                    queueTextNode(mutation.target);
                    return;
                }
                if (mutation.type === 'attributes') {
                    queueElementAttributes(mutation.target, mutation.attributeName);
                }
            });
        });

        scanNode(doc.body);
        observer.observe(doc.body, {
            childList: true,
            subtree: true,
            characterData: true,
            attributes: true,
            attributeFilter: [...attrNames, 'value'],
        });
    }

    function initSmartSearch() {
        doc.querySelectorAll('[data-smart-search-form]').forEach((form) => {
            const input = form.querySelector('[data-smart-search-input]');
            const dropdown = form.querySelector('[data-smart-search-dropdown]');
            const endpoint = form.dataset.smartSearchUrl || form.action;
            if (!input || !dropdown || !endpoint || !window.fetch) return;

            let timer = null;
            let controller = null;
            let results = [];
            let groups = [];
            let activeIndex = -1;
            let requestId = 0;

            const setExpanded = (expanded) => {
                input.setAttribute('aria-expanded', expanded ? 'true' : 'false');
                dropdown.hidden = !expanded;
                form.classList.toggle('smart-search-open', expanded);
            };

            const closeDropdown = () => {
                results = [];
                groups = [];
                activeIndex = -1;
                dropdown.innerHTML = '';
                setExpanded(false);
            };

            const resultMarkup = (item, index) => `
                <a href="${escapeHtml(item.url || '#')}"
                   class="smart-search-result ${index === activeIndex ? 'active' : ''}"
                   role="option"
                   aria-selected="${index === activeIndex ? 'true' : 'false'}"
                   data-smart-search-index="${index}">
                    <span class="smart-search-result-icon"><i class="fa-solid ${escapeHtml(item.icon || 'fa-link')}"></i></span>
                    <span class="smart-search-result-copy">
                        <strong>${escapeHtml(item.title || 'Untitled result')}</strong>
                        ${item.subtitle ? `<small>${escapeHtml(item.subtitle)}</small>` : ''}
                        ${item.scope ? `<em>${escapeHtml(item.scope)}</em>` : ''}
                    </span>
                    <span class="smart-search-result-label">${escapeHtml(item.label || 'Open')}</span>
                </a>
            `;

            const groupMarkup = (group) => {
                const items = Array.isArray(group.items) ? group.items : [];
                if (!items.length) return '';
                return `
                    <div class="smart-search-group" role="presentation">
                        <div class="smart-search-group-title">
                            <span><i class="fa-solid ${escapeHtml(group.icon || 'fa-table-cells-large')}"></i>${escapeHtml(group.title || 'Results')}</span>
                            <strong>${items.length}</strong>
                        </div>
                        ${items.map((item) => {
                    const index = results.findIndex((candidate) => candidate.url === item.url && candidate.title === item.title);
                    return resultMarkup(item, index >= 0 ? index : 0);
                }).join('')}
                    </div>
                `;
            };

            const renderResults = (state = 'ready') => {
                if (state === 'loading') {
                    dropdown.innerHTML = '<div class="smart-search-state"><span class="smart-search-spinner"></span> Searching...</div>';
                    setExpanded(true);
                    return;
                }

                if (!results.length) {
                    dropdown.innerHTML = '<div class="smart-search-state">No direct matches found</div>';
                    setExpanded(true);
                    return;
                }

                dropdown.innerHTML = groups.length
                    ? groups.map(groupMarkup).join('')
                    : results.map(resultMarkup).join('');
                setExpanded(true);
            };

            const setActiveIndex = (nextIndex) => {
                if (!results.length) return;
                activeIndex = (nextIndex + results.length) % results.length;
                dropdown.querySelectorAll('[data-smart-search-index]').forEach((item) => {
                    const isActive = Number(item.dataset.smartSearchIndex) === activeIndex;
                    item.classList.toggle('active', isActive);
                    item.setAttribute('aria-selected', isActive ? 'true' : 'false');
                    if (isActive) item.scrollIntoView({ block: 'nearest' });
                });
            };

            const openResult = (index) => {
                const result = results[index];
                if (!result?.url) return;
                window.location.href = result.url;
            };

            const fetchResults = () => {
                const query = input.value.trim();
                if (query === '') {
                    controller?.abort();
                    closeDropdown();
                    return;
                }

                controller?.abort();
                controller = new AbortController();
                const currentRequest = ++requestId;
                const url = new URL(endpoint, window.location.href);
                url.searchParams.set('q', query);

                results = [];
                groups = [];
                activeIndex = -1;
                renderResults('loading');
                fetch(url.toString(), {
                    headers: { 'Accept': 'application/json' },
                    signal: controller.signal,
                })
                    .then((response) => response.ok ? response.json() : Promise.reject(new Error('Smart search failed')))
                    .then((payload) => {
                        if (currentRequest !== requestId) return;
                        results = Array.isArray(payload.results) ? payload.results : [];
                        groups = Array.isArray(payload.groups) ? payload.groups : [];
                        activeIndex = results.length ? 0 : -1;
                        renderResults();
                    })
                    .catch((error) => {
                        if (error.name === 'AbortError') return;
                        results = [];
                        groups = [];
                        activeIndex = -1;
                        dropdown.innerHTML = '<div class="smart-search-state">Search is unavailable right now</div>';
                        setExpanded(true);
                    });
            };

            input.addEventListener('input', () => {
                clearTimeout(timer);
                timer = setTimeout(fetchResults, 180);
            });

            input.addEventListener('focus', () => {
                if (results.length) {
                    renderResults();
                } else if (input.value.trim() !== '') {
                    fetchResults();
                }
            });

            input.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    closeDropdown();
                    return;
                }

                if (event.key === 'ArrowDown') {
                    event.preventDefault();
                    if (!results.length) {
                        fetchResults();
                        return;
                    }
                    setActiveIndex(activeIndex + 1);
                    return;
                }

                if (event.key === 'ArrowUp') {
                    event.preventDefault();
                    if (results.length) setActiveIndex(activeIndex - 1);
                    return;
                }

                if (event.key === 'Enter') {
                    event.preventDefault();
                    if (activeIndex >= 0) {
                        openResult(activeIndex);
                    } else if (results.length) {
                        openResult(0);
                    } else {
                        fetchResults();
                    }
                }
            });

            dropdown.addEventListener('mouseover', (event) => {
                const item = event.target.closest('[data-smart-search-index]');
                if (!item) return;
                setActiveIndex(Number(item.dataset.smartSearchIndex));
            });

            form.addEventListener('submit', (event) => {
                event.preventDefault();
                if (activeIndex >= 0) {
                    openResult(activeIndex);
                    return;
                }
                if (results.length) {
                    openResult(0);
                    return;
                }
                fetchResults();
            });

            doc.addEventListener('click', (event) => {
                if (!form.contains(event.target)) closeDropdown();
            });
        });
    }

    function initSidebar() {
        const sidebar = doc.getElementById('sidebar');
        const backdrop = doc.getElementById('sidebarBackdrop');
        const openBtn = doc.getElementById('sidebarToggle');
        const closeBtn = doc.getElementById('sidebarClose');
        const hamburgerIcon = doc.getElementById('hamburgerIcon');

        if (sidebar && sidebar.parentElement !== doc.body) {
            doc.body.appendChild(sidebar);
        }
        if (backdrop && backdrop.parentElement !== doc.body) {
            doc.body.appendChild(backdrop);
        }

        function openSidebar() {
            sidebar && sidebar.classList.add('open');
            backdrop && backdrop.classList.add('show');
            hamburgerIcon && hamburgerIcon.classList.add('active');
            openBtn && openBtn.setAttribute('aria-expanded', 'true');
            doc.body.classList.add('sidebar-is-open');
            doc.body.style.overflow = 'hidden';
        }

        function closeSidebar() {
            sidebar && sidebar.classList.remove('open');
            backdrop && backdrop.classList.remove('show');
            hamburgerIcon && hamburgerIcon.classList.remove('active');
            openBtn && openBtn.setAttribute('aria-expanded', 'false');
            doc.body.classList.remove('sidebar-is-open');
            doc.body.style.overflow = '';
        }

        function toggleSidebar() {
            if (sidebar && sidebar.classList.contains('open')) {
                closeSidebar();
            } else {
                openSidebar();
            }
        }

        openBtn && openBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            toggleSidebar();
        });
        closeBtn && closeBtn.addEventListener('click', closeSidebar);
        backdrop && backdrop.addEventListener('click', closeSidebar);

        sidebar && sidebar.querySelectorAll('.sidebar-link').forEach((link) => {
            link.addEventListener('click', () => {
                closeSidebar();
            });
        });

        doc.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && sidebar && sidebar.classList.contains('open')) {
                closeSidebar();
            }
        });
    }

    function initMobileNav() {
        const toggle = doc.getElementById('mobileNavToggle');
        const nav = doc.getElementById('mobileNav');
        if (!toggle || !nav) return;

        toggle.addEventListener('click', () => {
            const isOpen = nav.classList.toggle('open');
            toggle.innerHTML = isOpen
                ? '<i class="fa-solid fa-xmark"></i>'
                : '<i class="fa-solid fa-bars"></i>';
        });

        nav.querySelectorAll('.mobile-nav-link').forEach((link) => {
            link.addEventListener('click', () => {
                nav.classList.remove('open');
                toggle.innerHTML = '<i class="fa-solid fa-bars"></i>';
            });
        });
    }

    function initLogoutConfirm() {
        doc.querySelectorAll('[data-logout-link]').forEach((link) => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                Swal.fire({
                    title: 'Logout?',
                    text: 'You will be signed out of your account.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, logout'
                }).then((result) => {
                    if (result.isConfirmed) window.location.href = link.href;
                });
            });
        });
    }

    function initParticles() {
        const hero = doc.getElementById('particles-js');
        if (hero && window.particlesJS) {
            particlesJS('particles-js', {
                particles: {
                    number: { value: 60, density: { enable: true, value_area: 800 } },
                    color: { value: ['#0ea5e9', '#8b5cf6', '#14b8a6'] },
                    shape: { type: 'circle' },
                    opacity: { value: 0.4, random: true, anim: { enable: true, speed: 1, opacity_min: 0.1 } },
                    size: { value: 3, random: true, anim: { enable: true, speed: 2, size_min: 0.5 } },
                    line_linked: { enable: true, distance: 140, color: '#0ea5e9', opacity: 0.15, width: 1 },
                    move: { enable: true, speed: 1.2, direction: 'none', random: true, out_mode: 'out' }
                },
                interactivity: {
                    detect_on: 'canvas',
                    events: {
                        onhover: { enable: true, mode: 'grab' },
                        onclick: { enable: true, mode: 'push' },
                        resize: true
                    },
                    modes: {
                        grab: { distance: 160, line_linked: { opacity: 0.35 } },
                        push: { particles_nb: 3 }
                    }
                },
                retina_detect: true
            });
        }
    }

    function initCalendar() {
        const calendarEl = doc.getElementById('appointmentCalendar');
        if (calendarEl && window.FullCalendar) {
            const events = JSON.parse(calendarEl.dataset.events || '[]');
            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                events,
                headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek' },
                height: 'auto'
            });
            calendar.render();
        }
    }

    function initAdminChart() {
        const chartEl = doc.getElementById('adminAnalyticsChart');
        if (chartEl && window.Chart) {
            new Chart(chartEl, {
                type: 'bar',
                data: {
                    labels: ['Patients', 'Doctors', 'Hospitals', 'Appointments'],
                    datasets: [{
                        label: 'System totals',
                        data: [chartEl.dataset.patients, chartEl.dataset.doctors, chartEl.dataset.hospitals, chartEl.dataset.appointments],
                        backgroundColor: ['#0ea5e9', '#10b981', '#8b5cf6', '#f59e0b'],
                        borderRadius: 10
                    }]
                },
                options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
            });
        }
    }

    function initHealthMetricsChart() {
        const chartEl = doc.getElementById('healthMetricsChart');
        if (!chartEl || !window.Chart) return;

        let points = [];
        try {
            points = JSON.parse(chartEl.dataset.points || '[]');
        } catch (error) {
            points = [];
        }

        if (!Array.isArray(points) || !points.length) return;

        new Chart(chartEl, {
            type: 'line',
            data: {
                labels: points.map((point) => point.label || ''),
                datasets: [{
                    label: 'Primary value',
                    data: points.map((point) => Number(point.value || 0)),
                    borderColor: '#0ea5e9',
                    backgroundColor: 'rgba(14, 165, 233, 0.14)',
                    pointBackgroundColor: '#14b8a6',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    tension: 0.34,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { intersect: false, mode: 'index' }
                },
                interaction: { intersect: false, mode: 'index' },
                scales: {
                    x: { grid: { display: false }, ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: 6 } },
                    y: { beginAtZero: false, grid: { color: 'rgba(100, 116, 139, 0.14)' } }
                }
            }
        });
    }

    function initReportSearch() {
        const search = doc.getElementById('reportSearch');
        if (!search) return;
        search.addEventListener('input', () => {
            const q = search.value.toLowerCase();
            doc.querySelectorAll('[data-report-item]').forEach((item) => {
                item.hidden = !item.innerText.toLowerCase().includes(q);
            });
            doc.querySelectorAll('#reportsTable tbody tr').forEach((row) => {
                row.style.display = row.innerText.toLowerCase().includes(q) ? '' : 'none';
            });
        });
    }

    function initPasswordUI() {
        doc.querySelectorAll('.password-toggle').forEach((btn) => {
            btn.addEventListener('click', () => {
                const input = btn.parentElement.querySelector('.password-field');
                if (!input) return;
                input.type = input.type === 'password' ? 'text' : 'password';
            });
        });

        doc.querySelectorAll('.patient-password, .password-field').forEach((input) => {
            input.addEventListener('input', () => {
                const wrapper = input.closest('form')?.querySelector('.password-strength span');
                if (!wrapper) return;
                const value = input.value;
                let score = 10;
                if (value.length >= 8) score += 35;
                if (/[A-Z]/.test(value)) score += 20;
                if (/[0-9]/.test(value)) score += 20;
                if (/[^A-Za-z0-9]/.test(value)) score += 15;
                wrapper.style.width = Math.min(score, 100) + '%';
            });
        });
    }

    function initChat() {
        const form = doc.getElementById('chatForm');
        const shell = doc.querySelector('.chat-shell');
        if (!shell) return;
        const contactId = shell.dataset.contactId;
        const messagesEl = doc.getElementById('chatMessages');

        function renderMessages(messages) {
            if (!messagesEl) return;
            if (!messages.length) {
                messagesEl.innerHTML = '<div class="chat-empty-state"><div><i class="fa-regular fa-comments fa-2x mb-2"></i><div>No messages yet.</div></div></div>';
                return;
            }
            messagesEl.innerHTML = messages.map((message) => {
                const mine = String(message.sender_id) === String(window.MEDISPHERE.currentUserId);
                const safeMessage = String(message.message).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                return `<div class="message-bubble ${mine ? 'mine' : 'theirs'}"><div>${safeMessage}</div><small>${message.timestamp}</small></div>`;
            }).join('');
            messagesEl.scrollTop = messagesEl.scrollHeight;
        }

        function fetchMessages() {
            if (!contactId) return;
            fetch(`${window.MEDISPHERE.baseUrl}/index.php?route=api/messages&contact_id=${contactId}`)
                .then((r) => r.json())
                .then((data) => renderMessages(data.messages || []))
                .catch(() => { });
        }

        if (form) {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                const data = new FormData(form);
                const submitBtn = form.querySelector('button[type="submit"], button:not([type])');
                if (submitBtn) submitBtn.disabled = true;
                fetch(`${window.MEDISPHERE.baseUrl}/index.php?route=api/send-message`, {
                    method: 'POST',
                    body: data
                })
                    .then((r) => r.json())
                    .then((res) => {
                        if (res.success) {
                            form.reset();
                            fetchMessages();
                        } else {
                            Toastify({ text: res.message || 'Message could not be sent.', gravity: 'top', position: 'right', backgroundColor: '#dc3545' }).showToast();
                        }
                    })
                    .catch(() => {
                        Toastify({ text: 'Message could not be sent.', gravity: 'top', position: 'right', backgroundColor: '#dc3545' }).showToast();
                    })
                    .finally(() => {
                        if (submitBtn) submitBtn.disabled = false;
                    });
            });
        }

        if (contactId) {
            fetchMessages();
            setInterval(fetchMessages, 5000);
        }
    }

    function initScanner() {
        const input = doc.getElementById('scanImageInput');
        const preview = doc.getElementById('scanPreview');
        const previewWrap = doc.getElementById('scanPreviewWrap');
        const resultWrap = doc.getElementById('scanResult');
        const analyzeBtn = doc.getElementById('runScanBtn');
        const saveBtn = doc.getElementById('saveScanBtn');
        const form = doc.getElementById('scanForm');
        const modeInput = doc.getElementById('scanTypeInput');
        const bodyPartSelect = doc.getElementById('bodyPartSelect');
        const symptomInput = doc.getElementById('symptomInput');
        const imagePanel = doc.getElementById('imageScanPanel');
        const usageCounter = doc.getElementById('scannerUsageCounter');
        const googleResultsContainer = doc.getElementById('googleResultsContainer');
        const allAroundSuggestions = doc.getElementById('allAroundSuggestions');
        const googleResultsTitle = doc.getElementById('googleResultsTitle');
        const googleResultsSubtitle = doc.getElementById('googleResultsSubtitle');
        const googleResultsModule = doc.getElementById('googleResultsModule');
        const modeButtons = Array.from(doc.querySelectorAll('[data-scan-mode]'));
        const scannerVisualizer = doc.getElementById('scannerVisualizer');
        const scannerVisualStatus = doc.getElementById('scannerVisualStatus');
        const scannerLockLabel = doc.getElementById('scannerLockLabel');
        const bodyHotspots = Array.from(doc.querySelectorAll('[data-body-part-hotspot]'));
        const uploadHint = doc.getElementById('scanUploadHint');
        const cameraLab = doc.getElementById('scannerCameraLab');
        const cameraPreview = doc.getElementById('scannerCameraPreview');
        const cameraCanvas = doc.getElementById('scannerCameraCanvas');
        const cameraStatus = doc.getElementById('scannerCameraStatus');
        const cameraPlaceholder = doc.getElementById('scannerCameraPlaceholder');
        const frozenFrame = doc.getElementById('scannerFrozenFrame');
        const analyzingOverlay = doc.getElementById('scannerAnalyzingOverlay');
        const startCameraBtn = doc.getElementById('startCameraBtn');
        const lockFrameBtn = doc.getElementById('lockFrameBtn');
        const stopCameraBtn = doc.getElementById('stopCameraBtn');
        const dropzone = doc.getElementById('scannerDropzone');
        if (!form) return;

        let lastResult = null;
        let lastResultModule = '';
        let currentMode = modeInput ? modeInput.value : 'image';
        let cameraStream = null;
        let lastCameraBlob = null;
        let frozenFrameUrl = '';

        function activeScannerModule() {
            const activePane = doc.querySelector('#scannerTabContent .tab-pane.active.show, #scannerTabContent .tab-pane.active');
            if (activePane?.id === 'analyzer') return 'analyzer';
            const activeTab = doc.querySelector('#scannerTab .nav-link.active');
            return activeTab?.dataset?.bsTarget === '#analyzer' ? 'analyzer' : 'lens';
        }

        function updateResultHeader(moduleKey, result = null) {
            const isAnalyzer = moduleKey === 'analyzer';
            if (googleResultsModule) {
                googleResultsModule.textContent = isAnalyzer ? 'Module 2 report' : 'Module 1 lens result';
            }
            if (googleResultsTitle) {
                googleResultsTitle.innerHTML = isAnalyzer
                    ? '<i class="fa-solid fa-file-medical"></i> AI Symptom & Image Report'
                    : '<i class="fa-solid fa-camera-retro"></i> AI Lens Scan Report';
            }
            if (googleResultsSubtitle) {
                const body = result?.body_label || bodyPartLabel(result?.body_part || selectedBodyPart());
                googleResultsSubtitle.textContent = isAnalyzer
                    ? `Symptoms and uploaded evidence reviewed for ${body.toLowerCase()} care guidance.`
                    : `Locked camera frame reviewed for ${body.toLowerCase()} care guidance.`;
            }
        }

        function syncResultVisibility() {
            if (!googleResultsContainer || !lastResultModule) return;
            const shouldShow = activeScannerModule() === lastResultModule;
            googleResultsContainer.classList.toggle('d-none', !shouldShow);
        }

        function bodyPartLabel(part) {
            const option = bodyPartSelect ? Array.from(bodyPartSelect.options).find((item) => item.value === part) : null;
            return option ? option.textContent.trim() : String(part || 'general').replace(/_/g, ' ');
        }

        function selectedBodyPart() {
            return bodyPartSelect ? bodyPartSelect.value : 'general';
        }

        function moduleBodyPart(moduleKey) {
            if (moduleKey === 'analyzer') {
                return doc.getElementById('mod2BodyPartSelect')?.value || 'general';
            }
            return selectedBodyPart();
        }

        function moduleSymptoms(moduleKey) {
            if (moduleKey === 'analyzer') {
                return doc.getElementById('mod2SymptomInput')?.value.trim() || '';
            }
            return symptomInput?.value.trim() || '';
        }

        function scannerRequestData() {
            const data = new FormData();
            const token = form.querySelector('input[name="csrf_token"]')?.value || window.MEDISPHERE?.csrf || '';
            data.set('csrf_token', token);
            return data;
        }

        function attachModuleImage(data, moduleKey, capturedBlob = null, filePrefix = 'camera') {
            if (moduleKey === 'analyzer') {
                const mod2File = doc.getElementById('mod2FileInput')?.files?.[0] || null;
                if (mod2File) data.set('scan_image', mod2File);
                return;
            }

            const lensFile = input?.files?.[0] || null;
            if (lensFile) {
                data.set('scan_image', lensFile);
            } else if (capturedBlob) {
                data.set('scan_image', capturedBlob, `${filePrefix}-${Date.now()}.jpg`);
            }
        }

        function setVisualizerState(part = selectedBodyPart(), state = 'ready', detail = '') {
            if (!scannerVisualizer) return;
            const activePart = part || 'general';
            const label = bodyPartLabel(activePart);
            scannerVisualizer.dataset.activePart = activePart;
            scannerVisualizer.classList.remove('is-ready', 'is-scanning', 'is-locked', 'is-error');
            scannerVisualizer.classList.add(`is-${state}`);
            bodyHotspots.forEach((hotspot) => {
                hotspot.classList.toggle('is-active', hotspot.dataset.bodyPartHotspot === activePart);
            });
            if (scannerLockLabel) {
                scannerLockLabel.innerHTML = `<i class="fa-solid fa-crosshairs"></i> ${escapeHtml(label)}`;
            }
            if (scannerVisualStatus) {
                const statusMap = {
                    ready: ['Ready to scan', detail || 'Choose the affected body area, then run analysis.'],
                    scanning: ['Scanning movement active', detail || `Scanning ${label.toLowerCase()} and looking for care signals...`],
                    locked: ['Scanner locked', detail || `Locked on ${label.toLowerCase()}. Review AI triage below.`],
                    error: ['Scan needs attention', detail || 'Analysis could not complete. Please check the details and try again.'],
                };
                const status = statusMap[state] || statusMap.ready;
                scannerVisualStatus.innerHTML = `<span class="status-dot"></span><div><strong>${escapeHtml(status[0])}</strong><small>${escapeHtml(status[1])}</small></div>`;
            }
        }

        function setCameraStatus(message, tone = 'idle') {
            if (!cameraStatus) return;
            const icon = tone === 'live' ? 'fa-circle-dot' : (tone === 'error' ? 'fa-triangle-exclamation' : 'fa-circle');
            cameraStatus.innerHTML = `<i class="fa-solid ${icon}"></i> ${escapeHtml(message)}`;
            cameraStatus.dataset.tone = tone;
        }

        function setAnalyzing(active) {
            if (!analyzingOverlay) return;
            analyzingOverlay.hidden = !active;
            if (cameraLab) cameraLab.classList.toggle('is-analyzing', Boolean(active));
        }

        function clearFrozenFrame() {
            if (frozenFrameUrl) {
                URL.revokeObjectURL(frozenFrameUrl);
                frozenFrameUrl = '';
            }
            if (frozenFrame) {
                frozenFrame.src = '';
                frozenFrame.hidden = true;
            }
            if (cameraLab) cameraLab.classList.remove('has-locked-frame');
        }

        function showFrozenFrame(blob) {
            clearFrozenFrame();
            frozenFrameUrl = URL.createObjectURL(blob);
            if (frozenFrame) {
                frozenFrame.src = frozenFrameUrl;
                frozenFrame.hidden = false;
            }
            if (cameraLab) cameraLab.classList.add('has-locked-frame');
            cameraPlaceholder && cameraPlaceholder.setAttribute('hidden', 'hidden');
        }

        function wait(ms) {
            return new Promise((resolve) => window.setTimeout(resolve, ms));
        }

        async function waitForCameraFrame() {
            if (!cameraPreview) return;
            if (cameraPreview.readyState >= 2 && cameraPreview.videoWidth > 0) return;
            await new Promise((resolve) => {
                const done = () => {
                    cameraPreview.removeEventListener('loadedmetadata', done);
                    cameraPreview.removeEventListener('canplay', done);
                    resolve();
                };
                cameraPreview.addEventListener('loadedmetadata', done, { once: true });
                cameraPreview.addEventListener('canplay', done, { once: true });
                window.setTimeout(done, 1800);
            });
        }

        async function startCamera() {
            if (cameraStream) return cameraStream;
            if (!navigator.mediaDevices?.getUserMedia || !cameraPreview) {
                throw new Error('Camera is not available in this browser. Please upload a clear image instead.');
            }

            clearFrozenFrame();
            setCameraStatus('Requesting camera permission...', 'live');
            cameraLab && cameraLab.classList.add('is-requesting');
            try {
                cameraStream = await navigator.mediaDevices.getUserMedia({
                    audio: false,
                    video: {
                        facingMode: { ideal: 'environment' },
                        width: { ideal: 1280 },
                        height: { ideal: 720 },
                    },
                });
                cameraPreview.srcObject = cameraStream;
                await cameraPreview.play().catch(() => { });
                await waitForCameraFrame();
                cameraLab && cameraLab.classList.add('is-live');
                cameraLab && cameraLab.classList.remove('is-requesting');
                cameraPlaceholder && cameraPlaceholder.setAttribute('hidden', 'hidden');
                if (stopCameraBtn) stopCameraBtn.hidden = false;
                if (lockFrameBtn) lockFrameBtn.disabled = false;
                setCameraStatus('Camera live. Point at the affected area; scanner will capture a frame.', 'live');
                return cameraStream;
            } catch (error) {
                cameraLab && cameraLab.classList.remove('is-requesting', 'is-live');
                if (lockFrameBtn) lockFrameBtn.disabled = true;
                setCameraStatus('Camera permission failed. Upload an image instead.', 'error');
                throw new Error('Camera permission was blocked or unavailable. Please allow camera access or upload an image.');
            }
        }

        function stopCamera(options = {}) {
            if (cameraStream) {
                cameraStream.getTracks().forEach((track) => track.stop());
            }
            cameraStream = null;
            if (cameraPreview) cameraPreview.srcObject = null;
            cameraLab && cameraLab.classList.remove('is-live', 'is-requesting');
            if (!options.keepFrozenFrame) {
                clearFrozenFrame();
                cameraPlaceholder && cameraPlaceholder.removeAttribute('hidden');
            }
            if (stopCameraBtn) stopCameraBtn.hidden = true;
            if (lockFrameBtn) lockFrameBtn.disabled = true;
            setCameraStatus('Camera idle', 'idle');
        }

        async function captureCameraFrame() {
            await startCamera();
            await wait(850);
            if (!cameraCanvas || !cameraPreview || !cameraPreview.videoWidth) {
                throw new Error('Camera frame is not ready yet. Please try again.');
            }
            const width = cameraPreview.videoWidth;
            const height = cameraPreview.videoHeight || Math.round(width * 0.75);
            cameraCanvas.width = width;
            cameraCanvas.height = height;
            const context = cameraCanvas.getContext('2d');
            context.drawImage(cameraPreview, 0, 0, width, height);
            const blob = await new Promise((resolve) => cameraCanvas.toBlob(resolve, 'image/jpeg', 0.92));
            if (!blob) throw new Error('Could not capture camera frame. Please try again.');
            lastCameraBlob = blob;
            showFrozenFrame(blob);
            if (preview && previewWrap) {
                preview.src = URL.createObjectURL(blob);
                previewWrap.classList.remove('d-none');
            }
            stopCamera({ keepFrozenFrame: true });
            setCameraStatus('Frame locked and ready for analysis.', 'live');
            return blob;
        }

        input && input.addEventListener('change', () => {
            const file = input.files[0];
            if (!file) return;
            lastCameraBlob = null;
            clearFrozenFrame();
            const reader = new FileReader();
            reader.onload = (e) => {
                preview.src = e.target.result;
                previewWrap.classList.remove('d-none');
                if (dropzone) dropzone.classList.add('d-none');
            };
            reader.readAsDataURL(file);
        });

        const clearScanImageBtn = doc.getElementById('clearScanImageBtn');
        if (clearScanImageBtn) {
            clearScanImageBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                if (input) input.value = '';
                preview.src = '';
                previewWrap.classList.add('d-none');
                if (dropzone) dropzone.classList.remove('d-none');
            });
        }

        if (dropzone && input) {
            ['dragenter', 'dragover'].forEach((eventName) => {
                dropzone.addEventListener(eventName, (event) => {
                    event.preventDefault();
                    dropzone.classList.add('is-dragging');
                });
            });
            ['dragleave', 'drop'].forEach((eventName) => {
                dropzone.addEventListener(eventName, (event) => {
                    event.preventDefault();
                    dropzone.classList.remove('is-dragging');
                });
            });
            dropzone.addEventListener('drop', (event) => {
                const files = event.dataTransfer?.files;
                const file = files && files[0];
                if (!file || !/^image\/(jpeg|png|webp)$/i.test(file.type)) {
                    Toastify({ text: 'Drop a JPG, PNG, or WebP image.', gravity: 'top', position: 'right', backgroundColor: '#dc3545' }).showToast();
                    return;
                }
                input.files = files;
                input.dispatchEvent(new Event('change', { bubbles: true }));
            });
        }

        function setMode(mode) {
            currentMode = mode === 'symptom' ? 'symptom' : 'image';
            if (modeInput) modeInput.value = currentMode;
            if (imagePanel) imagePanel.classList.toggle('is-symptom-support', currentMode === 'symptom');
            modeButtons.forEach((button) => {
                const active = button.dataset.scanMode === currentMode;
                button.classList.toggle('is-active', active);
                button.setAttribute('aria-selected', active ? 'true' : 'false');
            });
            if (analyzeBtn) {
                analyzeBtn.innerHTML = currentMode === 'image'
                    ? '<i class="fa-solid fa-camera"></i> Scan Camera / Image'
                    : '<i class="fa-solid fa-brain"></i> Analyze Symptoms + Image';
            }
            if (uploadHint) {
                uploadHint.textContent = currentMode === 'image'
                    ? 'Upload a clear image, or click Analyze to scan with your device camera'
                    : 'Upload a related photo if you have one; symptoms are required for this mode';
            }
            if (resultWrap && !lastResult) {
                resultWrap.textContent = currentMode === 'image'
                    ? 'Start Scan to open your camera, lock a frame, upload a clear image, then analyze.'
                    : 'Write symptoms, optionally add a related photo, then analyze for a concise AI chat bubble.';
            }
            setVisualizerState(selectedBodyPart(), 'ready', currentMode === 'image' ? 'Start the camera and lock a scan frame, or upload a photo.' : 'Write symptoms and add a related photo if available.');
        }

        modeButtons.forEach((button) => {
            button.addEventListener('click', () => setMode(button.dataset.scanMode || 'image'));
        });
        setMode(currentMode);

        doc.querySelectorAll('#scannerTab [data-bs-toggle="pill"], .scanner-hero-actions [data-bs-toggle="pill"]').forEach((tab) => {
            tab.addEventListener('shown.bs.tab', () => {
                currentMode = activeScannerModule() === 'analyzer' ? 'symptom' : 'image';
                if (modeInput) modeInput.value = currentMode;
                syncResultVisibility();
            });
        });

        startCameraBtn && startCameraBtn.addEventListener('click', async (event) => {
            if (guardPremiumAction(event, startCameraBtn)) return;
            try {
                setVisualizerState(selectedBodyPart(), 'scanning', 'Camera live. Align the affected area inside the frame.');
                await startCamera();
            } catch (error) {
                setVisualizerState(selectedBodyPart(), 'error', error.message || 'Camera could not start.');
                Toastify({ text: error.message || 'Camera could not start.', gravity: 'top', position: 'right', backgroundColor: '#dc3545' }).showToast();
            }
        });
        lockFrameBtn && lockFrameBtn.addEventListener('click', async (event) => {
            if (guardPremiumAction(event, lockFrameBtn)) return;
            try {
                setVisualizerState(selectedBodyPart(), 'scanning', 'Locking the current camera frame...');
                await captureCameraFrame();
                setVisualizerState(selectedBodyPart(), 'locked', 'Frame frozen. Click Analyze to process this locked image.');
            } catch (error) {
                setVisualizerState(selectedBodyPart(), 'error', error.message || 'Frame could not be locked.');
                Toastify({ text: error.message || 'Frame could not be locked.', gravity: 'top', position: 'right', backgroundColor: '#dc3545' }).showToast();
            }
        });
        stopCameraBtn && stopCameraBtn.addEventListener('click', () => {
            lastCameraBlob = null;
            stopCamera();
            setVisualizerState(selectedBodyPart(), 'ready', 'Camera stopped. Start scan again or upload a symptom photo.');
        });
        window.addEventListener('beforeunload', () => {
            stopCamera();
            clearFrozenFrame();
        });

        bodyPartSelect && bodyPartSelect.addEventListener('change', () => {
            lastResult = null;
            if (saveBtn) saveBtn.disabled = true;
            setVisualizerState(selectedBodyPart(), 'ready', `Target set to ${bodyPartLabel(selectedBodyPart()).toLowerCase()}.`);
        });

        bodyHotspots.forEach((hotspot) => {
            hotspot.addEventListener('click', () => {
                const part = hotspot.dataset.bodyPartHotspot || 'general';
                if (bodyPartSelect) {
                    bodyPartSelect.value = part;
                    bodyPartSelect.dispatchEvent(new Event('change', { bubbles: true }));
                } else {
                    setVisualizerState(part, 'ready');
                }
            });
        });

        function urgencyClass(level) {
            if (level === 'urgent') return 'danger';
            if (level === 'soon') return 'warning';
            return 'success';
        }

        function money(value) {
            const amount = Number(value || 0);
            if (!Number.isFinite(amount) || amount <= 0) return 'Fee not listed';
            return `PKR ${amount.toLocaleString()}`;
        }

        function initials(name) {
            return String(name || 'Dr')
                .split(/\s+/)
                .filter(Boolean)
                .slice(0, 2)
                .map((part) => part.charAt(0).toUpperCase())
                .join('') || 'DR';
        }

        function normalizePhone(phone) {
            const raw = String(phone || '').trim();
            if (!raw || /^not listed$/i.test(raw)) return { raw: '', tel: '', whatsapp: '' };
            const tel = raw.replace(/[^\d+]/g, '');
            const whatsapp = tel.replace(/[^\d]/g, '');
            return {
                raw,
                tel: tel.length >= 7 ? `tel:${tel}` : '',
                whatsapp: whatsapp.length >= 7 ? `https://wa.me/${whatsapp}?text=${encodeURIComponent('Hello doctor, I need a video consultation after my AI scanner result. Please guide me.')}` : '',
            };
        }

        function actionLink({ href, icon, label, className = '', external = false }) {
            const iconClass = icon.startsWith('fa-brands') ? icon : `fa-solid ${icon}`;
            if (!href) {
                return `<span class="scanner-action-icon ${className} is-disabled"><i class="${escapeHtml(iconClass)}"></i><span>${escapeHtml(label)}</span></span>`;
            }
            const target = external ? ' target="_blank" rel="noopener"' : '';
            return `<a class="scanner-action-icon ${className}" href="${escapeHtml(href)}"${target}><i class="${escapeHtml(iconClass)}"></i><span>${escapeHtml(label)}</span></a>`;
        }

        function renderQuestions(items) {
            if (!Array.isArray(items) || !items.length) return '';
            return `<div class="scanner-result-section">
                <h4>Doctor visit questions</h4>
                ${renderList(items, 'fa-clipboard-question')}
            </div>`;
        }

        function renderList(items, icon) {
            if (!Array.isArray(items) || !items.length) return '<div class="scanner-muted">No items returned.</div>';
            return `<ul class="scanner-result-list">${items.map((item) => `<li><i class="fa-solid ${icon}"></i><span>${escapeHtml(item)}</span></li>`).join('')}</ul>`;
        }

        function primaryCondition(result) {
            const conditions = Array.isArray(result.possible_conditions) ? result.possible_conditions : [];
            return conditions[0] || {
                name: result.specialist_recommendation || 'General health concern',
                summary: result.summary || 'A qualified clinician should review this with history and examination.',
                match_score: result.confidence_score || 0,
            };
        }

        function statusPill(status) {
            const value = ['online', 'busy', 'offline'].includes(status) ? status : 'offline';
            return `<span class="scanner-status-pill status-${escapeHtml(value)}"><i class="fa-solid fa-circle"></i>${escapeHtml(value.charAt(0).toUpperCase() + value.slice(1))}</span>`;
        }

        function renderDoctorActionControls(doctor, phone) {
            const bookUrl = doctor.book_url || `${window.MEDISPHERE.baseUrl}/index.php?route=appointments`;
            const voiceNote = `<button class="scanner-action-icon voice-note" type="button" data-voice-note-trigger data-doctor-name="${escapeHtml(doctor.name || 'Doctor')}" data-doctor-phone="${escapeHtml(phone.raw)}" ${phone.raw ? '' : 'disabled'}><i class="fa-solid fa-microphone"></i><span>Voice Note</span></button>`;
            const callControl = Number(doctor.appointment_id || 0) > 0
                ? `<form method="POST" action="${escapeHtml(doctor.consultation_create_url || `${window.MEDISPHERE.baseUrl}/index.php?route=consultations/create`)}" class="scanner-call-form">
                    <input type="hidden" name="csrf_token" value="${escapeHtml(window.MEDISPHERE.csrf || '')}">
                    <input type="hidden" name="appointment_id" value="${Number(doctor.appointment_id || 0)}">
                    <button class="scanner-action-icon video" type="submit" name="call_type" value="video"><i class="fa-solid fa-video"></i><span>Call</span></button>
                </form>`
                : `<a class="scanner-action-icon video" href="${escapeHtml(bookUrl)}"><i class="fa-solid fa-video"></i><span>Call</span></a>`;

            return `<div class="scanner-provider-actions scanner-whatsapp-actions">
                ${actionLink({ href: phone.whatsapp, icon: 'fa-brands fa-whatsapp', label: 'Message', className: 'whatsapp', external: true })}
                ${voiceNote}
                ${callControl}
            </div>`;
        }

        function renderDoctors(doctors) {
            if (!Array.isArray(doctors) || !doctors.length) {
                return `<div class="scanner-muted scanner-rich-empty">
                    <i class="fa-solid fa-user-doctor"></i>
                    <div>
                        <strong>No exact specialist match yet</strong>
                        <span>Open Find Healthcare to search doctors by specialty, city, fee, and availability.</span>
                    </div>
                    <a href="${escapeHtml(window.MEDISPHERE.baseUrl)}/index.php?route=find-healthcare" class="btn btn-outline-primary btn-sm">Find doctors</a>
                </div>`;
            }
            return `<div class="scanner-provider-grid">
                ${doctors.map((doctor) => {
                const phone = normalizePhone(doctor.phone);
                return `<article class="scanner-provider-card scanner-doctor-chat-card">
                    <div class="scanner-provider-profile">
                        <div class="scanner-provider-avatar">${escapeHtml(initials(doctor.name))}</div>
                        <div>
                            ${statusPill(doctor.availability_status || 'offline')}
                            <strong>${escapeHtml(doctor.name || 'Doctor')}</strong>
                            <span>${escapeHtml(doctor.specialization || 'General')}</span>
                        </div>
                    </div>
                    <p><i class="fa-solid fa-hospital"></i>${escapeHtml(doctor.hospital_name || 'Independent clinic')}</p>
                    <a class="scanner-phone-link" href="${escapeHtml(phone.tel || doctor.book_url || '#')}"><i class="fa-solid fa-phone"></i>${escapeHtml(phone.raw || 'Contact through booking')}</a>
                    <p><i class="fa-solid fa-location-dot"></i>${escapeHtml([doctor.city, doctor.country].filter(Boolean).join(', ') || 'Coordinates not listed')}</p>
                    ${doctor.coordinates ? `<p><i class="fa-solid fa-map-pin"></i>${escapeHtml(doctor.coordinates)}</p>` : ''}
                    <div class="scanner-provider-meta">
                        <small>${Number(doctor.experience || 0)} yrs</small>
                        <small>${escapeHtml(money(doctor.fee))}</small>
                    </div>
                    ${renderDoctorActionControls(doctor, phone)}
                </article>`;
            }).join('')}
            </div>`;
        }

        function renderHospitals(hospitals) {
            if (!Array.isArray(hospitals) || !hospitals.length) {
                return `<div class="scanner-muted scanner-rich-empty">
                    <i class="fa-solid fa-hospital"></i>
                    <div>
                        <strong>No direct hospital match yet</strong>
                        <span>Use the map or Find Healthcare to compare nearby hospitals and emergency services.</span>
                    </div>
                    <a href="${escapeHtml(window.MEDISPHERE.baseUrl)}/index.php?route=map" class="btn btn-outline-primary btn-sm">Open map</a>
                </div>`;
            }
            return `<div class="scanner-provider-grid">
                ${hospitals.map((hospital) => {
                const phone = normalizePhone(hospital.phone);
                return `<article class="scanner-provider-card hospital">
                    <span>${escapeHtml(hospital.verified_status || 'pending')}</span>
                    <strong>${escapeHtml(hospital.name || 'Hospital')}</strong>
                    <p><i class="fa-solid fa-location-dot"></i>${escapeHtml([hospital.city, hospital.country].filter(Boolean).join(', ') || 'Location not listed')}</p>
                    <a class="scanner-phone-link" href="${escapeHtml(phone.tel || hospital.map_url || '#')}"><i class="fa-solid fa-phone"></i>${escapeHtml(phone.raw || 'Contact not listed')}</a>
                    ${hospital.coordinates ? `<p><i class="fa-solid fa-map-pin"></i>${escapeHtml(hospital.coordinates)}</p>` : ''}
                    <small>${escapeHtml(hospital.facilities || 'Facilities not listed')}</small>
                    <div class="scanner-provider-actions">
                        ${actionLink({ href: phone.tel, icon: 'fa-phone', label: 'Call', className: 'voice' })}
                        ${actionLink({ href: hospital.map_url || '#', icon: 'fa-map-location-dot', label: 'Map', className: 'map' })}
                    </div>
                </article>`;
            }).join('')}
            </div>`;
        }

        function renderAnalysis(result) {
            const tone = urgencyClass(result.urgency_level || 'routine');
            const condition = primaryCondition(result);
            const moduleKey = lastResultModule || activeScannerModule();
            const conditions = Array.isArray(result.possible_conditions) ? result.possible_conditions : [];
            const library = Array.isArray(result.disease_library) ? result.disease_library : [];

            if (googleResultsContainer) {
                googleResultsContainer.dataset.resultModule = moduleKey;
                googleResultsContainer.classList.remove('d-none');
            }
            updateResultHeader(moduleKey, result);

            resultWrap.innerHTML = `
                <div class="scanner-report-summary tone-${tone}">
                    <div>
                        <span class="scanner-report-kicker"><i class="fa-solid fa-circle-info"></i> ${escapeHtml(result.urgency_level || 'routine')} review</span>
                        <h3>${escapeHtml(condition.name || result.title || 'General health concern')}</h3>
                        <p>${escapeHtml(condition.summary || result.summary || 'A qualified clinician should review this result with history and examination.')}</p>
                        <div class="scanner-result-badges">
                            <span><i class="fa-solid fa-stethoscope"></i>${escapeHtml(result.specialist_recommendation || 'General physician')}</span>
                            <span><i class="fa-solid fa-location-crosshairs"></i>${escapeHtml(result.body_label || bodyPartLabel(result.body_part || 'general'))}</span>
                            <span><i class="fa-solid fa-clock"></i>${escapeHtml(result.generated_at || 'Generated now')}</span>
                        </div>
                    </div>
                    <strong>${Number(result.confidence_score || 0)}<span>%</span><small>AI confidence</small></strong>
                </div>

                <div class="scanner-report-grid">
                    <section class="scanner-result-card">
                        <div class="scanner-result-card-head"><span>Next steps</span><i class="fa-solid fa-list-check"></i></div>
                        ${renderList(result.next_steps, 'fa-check')}
                    </section>
                    <section class="scanner-result-card">
                        <div class="scanner-result-card-head"><span>Red flags</span><i class="fa-solid fa-triangle-exclamation"></i></div>
                        ${renderList(result.red_flags, 'fa-triangle-exclamation')}
                    </section>
                    <section class="scanner-result-card">
                        <div class="scanner-result-card-head"><span>Doctor questions</span><i class="fa-solid fa-clipboard-question"></i></div>
                        ${renderList(result.doctor_questions, 'fa-clipboard-question')}
                    </section>
                    <section class="scanner-result-card">
                        <div class="scanner-result-card-head"><span>Possible matches</span><strong>${conditions.length || 1}</strong></div>
                        ${conditions.length ? `<div class="scanner-condition-grid scanner-condition-grid-compact">
                            ${conditions.slice(0, 4).map((item) => `<article>
                                <strong>${escapeHtml(item.name || 'Condition')}</strong>
                                <p>${escapeHtml(item.summary || 'Review this possibility with a clinician.')}</p>
                                <small>${Number(item.match_score || 0)}% match</small>
                            </article>`).join('')}
                        </div>` : `<div class="scanner-muted">No narrow condition list returned. Start with a general physician review.</div>`}
                    </section>
                    ${library.length ? `<section class="scanner-result-card scanner-result-card-wide">
                        <div class="scanner-result-card-head"><span>Disease library</span><i class="fa-solid fa-book-medical"></i></div>
                        <div class="scanner-library-grid">
                            ${library.slice(0, 4).map((item) => `<a href="${escapeHtml(item.url || '#')}">
                                <strong>${escapeHtml(item.name || 'Disease guide')}</strong>
                                <p>${escapeHtml(item.summary || 'Open this guide and discuss it with your clinician.')}</p>
                                <span>${escapeHtml(item.risk_level || 'Review')}</span>
                            </a>`).join('')}
                        </div>
                    </section>` : ''}
                </div>
            `;

            if (allAroundSuggestions) {
                allAroundSuggestions.innerHTML = `
                    <section class="scanner-suggestion-column">
                        <h4><i class="fa-brands fa-whatsapp text-success"></i> Doctor suggestions</h4>
                        ${renderDoctors(result.doctors)}
                    </section>
                    <section class="scanner-suggestion-column">
                        <h4><i class="fa-solid fa-hospital text-primary"></i> Hospital suggestions</h4>
                        ${renderHospitals(result.hospitals)}
                    </section>
                `;
            }
            const disclaimer = doc.getElementById('footerDisclaimer');
            if (disclaimer && result.disclaimer) disclaimer.textContent = result.disclaimer;
            syncResultVisibility();
        }

        async function runAnalysisLogic(btn, isLensModule) {
            const resultModule = isLensModule ? 'lens' : 'analyzer';
            const originalBtnHtml = btn.innerHTML;
            const text = moduleSymptoms(resultModule);
            if (!isLensModule && text.length < 4) {
                Toastify({ text: 'Please write symptoms or disease concern first.', gravity: 'top', position: 'right', backgroundColor: '#dc3545' }).showToast();
                return;
            }

            if (isLensModule) {
                setVisualizerState(selectedBodyPart(), 'locked'); // triggers Google Lens CSS lock animation
                btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Locking target...';
                await new Promise(r => setTimeout(r, 1500)); // Google Lens locking delay
            }

            setVisualizerState(selectedBodyPart(), 'scanning');
            lastResultModule = resultModule;
            if (googleResultsContainer) {
                googleResultsContainer.dataset.resultModule = resultModule;
                googleResultsContainer.classList.remove('d-none');
            }
            updateResultHeader(resultModule);
            resultWrap.innerHTML = '<div class="scanner-thinking"><span class="scanner-thinking-orbit"><i class="fa-solid fa-wand-magic-sparkles"></i></span><div><strong>Analyzing securely...</strong><span>Checking medical pathways and suggesting local care.</span></div></div>';
            if (allAroundSuggestions) allAroundSuggestions.innerHTML = '';
            syncResultVisibility();

            setAnalyzing(true);
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Analyzing...';

            try {
                let capturedBlob = lastCameraBlob;
                const hasUploadedImage = Boolean(input && input.files && input.files.length);

                if (isLensModule && !hasUploadedImage && !capturedBlob) {
                    setVisualizerState(selectedBodyPart(), 'scanning', 'Camera opening. Point at the affected area while the scanner locks a frame.');
                    capturedBlob = await captureCameraFrame();
                }

                const data = scannerRequestData();
                data.set('scan_type', isLensModule ? 'image' : 'symptom');
                const actualBodyPart = moduleBodyPart(resultModule);
                data.set('body_part', actualBodyPart);
                data.set('symptoms', text);
                attachModuleImage(data, resultModule, capturedBlob, 'camera');

                const response = await fetch(`${window.MEDISPHERE.baseUrl}/index.php?route=scanner/analyze`, { method: 'POST', body: data });
                const payload = await response.json();
                if (!response.ok || !payload.success) {
                    throw new Error(payload.message || 'Analysis failed.');
                }
                lastResult = payload.analysis;
                lastResultModule = resultModule;
                if (usageCounter && typeof payload.usage_remaining !== 'undefined') {
                    usageCounter.textContent = `${payload.usage_remaining} analyses left this hour`;
                }
                if (saveBtn) saveBtn.disabled = false;
                setVisualizerState(lastResult.body_part || actualBodyPart, 'locked', 'Target locked. AI triage and contact options are ready below.');
                renderAnalysis(lastResult);
            } catch (error) {
                setVisualizerState(selectedBodyPart(), 'error', error.message || 'Could not analyze right now.');
                resultWrap.innerHTML = `<div class="alert alert-danger"><i class="fa-solid fa-triangle-exclamation"></i> ${escapeHtml(error.message || 'Could not analyze right now.')}</div>`;
                Toastify({ text: error.message || 'Could not analyze right now.', gravity: 'top', position: 'right', backgroundColor: '#dc3545' }).showToast();
            } finally {
                setAnalyzing(false);
                btn.disabled = false;
                btn.innerHTML = originalBtnHtml;
            }
        }

        analyzeBtn && analyzeBtn.addEventListener('click', (event) => {
            if (guardPremiumAction(event, analyzeBtn)) return;
            runAnalysisLogic(analyzeBtn, true);
        });

        const mod2AnalyzeBtn = doc.getElementById('mod2AnalyzeBtn');
        mod2AnalyzeBtn && mod2AnalyzeBtn.addEventListener('click', (event) => {
            if (guardPremiumAction(event, mod2AnalyzeBtn)) return;
            runAnalysisLogic(mod2AnalyzeBtn, false);
        });

        const mod2FileInput = doc.getElementById('mod2FileInput');
        const mod2PreviewState = doc.getElementById('mod2PreviewState');
        const mod2ImagePreview = doc.getElementById('mod2ImagePreview');
        const mod2UploadState = doc.getElementById('mod2UploadState');
        const mod2ClearBtn = doc.getElementById('mod2ClearBtn');

        if (mod2FileInput) {
            mod2FileInput.addEventListener('change', () => {
                const file = mod2FileInput.files[0];
                if (!file) return;
                const reader = new FileReader();
                reader.onload = (e) => {
                    if (mod2ImagePreview) mod2ImagePreview.src = e.target.result;
                    if (mod2PreviewState) mod2PreviewState.classList.remove('d-none');
                    if (mod2UploadState) mod2UploadState.classList.add('d-none');
                };
                reader.readAsDataURL(file);
            });
        }

        if (mod2ClearBtn) {
            mod2ClearBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                if (mod2FileInput) mod2FileInput.value = '';
                if (mod2ImagePreview) mod2ImagePreview.src = '';
                if (mod2PreviewState) mod2PreviewState.classList.add('d-none');
                if (mod2UploadState) mod2UploadState.classList.remove('d-none');
            });
        }


        saveBtn && saveBtn.addEventListener('click', (event) => {
            if (guardPremiumAction(event, saveBtn)) return;
            if (!lastResult) return;
            const moduleKey = lastResultModule || (lastResult.scan_type === 'symptom' ? 'analyzer' : 'lens');
            const data = scannerRequestData();
            data.set('scan_type', lastResult.scan_type || (moduleKey === 'analyzer' ? 'symptom' : 'image'));
            data.set('body_part', lastResult.body_part || moduleBodyPart(moduleKey));
            data.set('symptoms', moduleSymptoms(moduleKey));
            data.set('ai_result', `${lastResult.title || 'AI analysis'} - ${(lastResult.possible_conditions && lastResult.possible_conditions[0]?.name) || lastResult.specialist_recommendation || 'Care suggestion'}`);
            data.set('confidence_score', String(lastResult.confidence_score || 0));
            data.set('urgency_level', lastResult.urgency_level || 'routine');
            data.set('specialist_recommendation', lastResult.specialist_recommendation || '');
            data.set('care_recommendations', JSON.stringify(lastResult));
            attachModuleImage(data, moduleKey, lastCameraBlob, 'camera-saved');
            saveBtn.disabled = true;
            fetch(`${window.MEDISPHERE.baseUrl}/index.php?route=scanner/save`, {
                method: 'POST',
                body: data
            })
                .then((r) => r.json())
                .then((res) => {
                    if (res.success) {
                        Toastify({ text: 'Scan saved successfully.', gravity: 'top', position: 'right', backgroundColor: '#198754' }).showToast();
                    } else {
                        throw new Error(res.message || 'Scan could not be saved.');
                    }
                })
                .catch((error) => {
                    Toastify({ text: error.message || 'Scan could not be saved.', gravity: 'top', position: 'right', backgroundColor: '#dc3545' }).showToast();
                })
                .finally(() => {
                    saveBtn.disabled = false;
                });
        });

        function initScannerVoiceNotes() {
            let sheet = doc.getElementById('scannerVoiceNoteSheet');
            if (!sheet) {
                sheet = doc.createElement('div');
                sheet.id = 'scannerVoiceNoteSheet';
                sheet.className = 'scanner-voice-sheet';
                sheet.hidden = true;
                sheet.innerHTML = `
                    <div class="scanner-voice-card" role="dialog" aria-modal="true" aria-labelledby="scannerVoiceTitle">
                        <button class="scanner-voice-close" type="button" data-voice-close aria-label="Close voice note"><i class="fa-solid fa-xmark"></i></button>
                        <div class="scanner-voice-head">
                            <span><i class="fa-solid fa-microphone-lines"></i></span>
                            <div>
                                <h3 id="scannerVoiceTitle">Voice note</h3>
                                <p id="scannerVoiceDoctor">Select a doctor to record a note.</p>
                            </div>
                        </div>
                        <button class="scanner-record-toggle" type="button" data-record-toggle>
                            <i class="fa-solid fa-microphone"></i>
                            <span>Start recording</span>
                        </button>
                        <div class="scanner-record-status" id="scannerRecordStatus">Microphone idle</div>
                        <audio id="scannerVoicePreview" controls hidden></audio>
                        <div class="scanner-voice-actions">
                            <button class="scanner-action-icon voice-note" type="button" data-share-voice disabled><i class="fa-solid fa-share-nodes"></i><span>Send File</span></button>
                            <a class="scanner-action-icon whatsapp is-disabled" data-voice-whatsapp target="_blank" rel="noopener"><i class="fa-brands fa-whatsapp"></i><span>WhatsApp</span></a>
                            <a class="scanner-action-icon map is-disabled" data-download-voice><i class="fa-solid fa-download"></i><span>Download</span></a>
                        </div>
                    </div>
                `;
                doc.body.appendChild(sheet);
            }

            const title = sheet.querySelector('#scannerVoiceDoctor');
            const recordBtn = sheet.querySelector('[data-record-toggle]');
            const closeBtn = sheet.querySelector('[data-voice-close]');
            const statusEl = sheet.querySelector('#scannerRecordStatus');
            const audio = sheet.querySelector('#scannerVoicePreview');
            const shareBtn = sheet.querySelector('[data-share-voice]');
            const whatsappLink = sheet.querySelector('[data-voice-whatsapp]');
            const downloadLink = sheet.querySelector('[data-download-voice]');
            let recorder = null;
            let stream = null;
            let chunks = [];
            let activeDoctor = { name: 'Doctor', phone: '' };
            let audioUrl = '';
            let audioFile = null;

            function setRecordStatus(message) {
                if (statusEl) statusEl.textContent = message;
            }

            function setRecordButton(recording) {
                if (!recordBtn) return;
                recordBtn.classList.toggle('is-recording', recording);
                recordBtn.innerHTML = recording
                    ? '<i class="fa-solid fa-stop"></i><span>Stop recording</span>'
                    : '<i class="fa-solid fa-microphone"></i><span>Start recording</span>';
            }

            function resetVoicePreview() {
                if (audioUrl) URL.revokeObjectURL(audioUrl);
                audioUrl = '';
                audioFile = null;
                chunks = [];
                if (audio) {
                    audio.removeAttribute('src');
                    audio.hidden = true;
                }
                if (shareBtn) shareBtn.disabled = true;
                if (downloadLink) {
                    downloadLink.removeAttribute('href');
                    downloadLink.removeAttribute('download');
                    downloadLink.classList.add('is-disabled');
                }
            }

            function closeSheet() {
                if (recorder && recorder.state === 'recording') recorder.stop();
                if (stream) stream.getTracks().forEach((track) => track.stop());
                stream = null;
                sheet.hidden = true;
            }

            function whatsappUrl(phone, name) {
                const digits = String(phone || '').replace(/[^\d]/g, '');
                if (digits.length < 7) return '';
                return `https://wa.me/${digits}?text=${encodeURIComponent(`Hello ${name}, I recorded a voice note about my symptoms. I will share the audio file from my device. Please guide me.`)}`;
            }

            async function startRecording() {
                if (!navigator.mediaDevices?.getUserMedia || typeof MediaRecorder === 'undefined') {
                    Toastify({ text: 'Audio recording is unavailable in this browser.', gravity: 'top', position: 'right', backgroundColor: '#dc3545' }).showToast();
                    return;
                }
                resetVoicePreview();
                stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                chunks = [];
                try {
                    recorder = new MediaRecorder(stream, { mimeType: 'audio/webm;codecs=opus' });
                } catch (error) {
                    recorder = new MediaRecorder(stream);
                }
                recorder.ondataavailable = (event) => {
                    if (event.data && event.data.size > 0) chunks.push(event.data);
                };
                recorder.onstop = () => {
                    if (stream) stream.getTracks().forEach((track) => track.stop());
                    stream = null;
                    const blob = new Blob(chunks, { type: recorder?.mimeType || 'audio/webm' });
                    audioFile = new File([blob], `voice-note-${Date.now()}.webm`, { type: blob.type || 'audio/webm' });
                    audioUrl = URL.createObjectURL(audioFile);
                    if (audio) {
                        audio.src = audioUrl;
                        audio.hidden = false;
                    }
                    if (downloadLink) {
                        downloadLink.href = audioUrl;
                        downloadLink.download = audioFile.name;
                        downloadLink.classList.remove('is-disabled');
                    }
                    if (shareBtn) shareBtn.disabled = false;
                    setRecordButton(false);
                    setRecordStatus('Voice note ready to preview and send.');
                };
                recorder.start();
                setRecordButton(true);
                setRecordStatus('Recording... tap Stop when finished.');
            }

            function stopRecording() {
                if (recorder && recorder.state === 'recording') {
                    recorder.stop();
                }
            }

            doc.addEventListener('click', (event) => {
                const trigger = event.target.closest('[data-voice-note-trigger]');
                if (!trigger) return;
                if (trigger.disabled) return;
                activeDoctor = {
                    name: trigger.dataset.doctorName || 'Doctor',
                    phone: trigger.dataset.doctorPhone || '',
                };
                resetVoicePreview();
                if (title) title.textContent = `To ${activeDoctor.name}`;
                const url = whatsappUrl(activeDoctor.phone, activeDoctor.name);
                if (whatsappLink) {
                    whatsappLink.href = url || '#';
                    whatsappLink.classList.toggle('is-disabled', !url);
                }
                setRecordButton(false);
                setRecordStatus('Microphone idle');
                sheet.hidden = false;
            });

            closeBtn && closeBtn.addEventListener('click', closeSheet);
            sheet.addEventListener('click', (event) => {
                if (event.target === sheet) closeSheet();
            });
            recordBtn && recordBtn.addEventListener('click', async () => {
                if (recorder && recorder.state === 'recording') {
                    stopRecording();
                    return;
                }
                try {
                    await startRecording();
                } catch (error) {
                    setRecordButton(false);
                    setRecordStatus('Microphone permission denied or unavailable.');
                    Toastify({ text: 'Microphone permission denied or unavailable.', gravity: 'top', position: 'right', backgroundColor: '#dc3545' }).showToast();
                }
            });
            shareBtn && shareBtn.addEventListener('click', async () => {
                if (!audioFile) return;
                try {
                    if (navigator.canShare && navigator.canShare({ files: [audioFile] })) {
                        await navigator.share({
                            files: [audioFile],
                            title: `Voice note for ${activeDoctor.name}`,
                            text: 'Patient voice note for consultation.',
                        });
                        setRecordStatus('Voice note sent through your device share sheet.');
                        return;
                    }
                    throw new Error('File sharing is not supported by this browser.');
                } catch (error) {
                    Toastify({ text: error.message || 'Could not share this audio file.', gravity: 'top', position: 'right', backgroundColor: '#dc3545' }).showToast();
                }
            });
        }

        initScannerVoiceNotes();
    }


    function initNotificationsPolling() {
        const dropdownList = doc.getElementById('notificationDropdownList');
        const badge = doc.getElementById('notificationBadgeCount');
        const dot = doc.getElementById('notificationDot');
        if (!dropdownList || !window.MEDISPHERE.currentUserId) return;

        function render(data) {
            const unread = Number(data.unread_count || 0);
            if (badge) {
                badge.textContent = String(unread);
                badge.classList.toggle('d-none', unread <= 0);
            }
            if (dot) dot.classList.toggle('d-none', unread <= 0);

            const notifications = Array.isArray(data.notifications) ? data.notifications : [];
            if (!notifications.length) {
                dropdownList.innerHTML = '<div class="notification-item" id="notificationEmptyState">No notifications yet.</div>';
                return;
            }

            dropdownList.innerHTML = notifications.map((notification) => {
                const title = escapeHtml(notification.title || 'Notification');
                const message = escapeHtml(notification.message || '');
                const createdAt = escapeHtml(notification.created_at || '');
                const url = escapeHtml(notification.action_url || `${window.MEDISPHERE.baseUrl}/index.php?route=notifications`);
                const unreadClass = Number(notification.is_read) === 0 ? 'unread' : '';
                return `<a class="notification-item-link ${unreadClass}" href="${url}"><div class="notification-item-title">${title}</div><div class="notification-item">${message}</div><div class="notification-time">${createdAt}</div></a>`;
            }).join('');
        }

        function fetchNotifications() {
            fetch(`${window.MEDISPHERE.baseUrl}/index.php?route=api/notifications`)
                .then((response) => response.ok ? response.json() : null)
                .then((data) => {
                    if (data) render(data);
                })
                .catch(() => { });
        }

        fetchNotifications();
        setInterval(fetchNotifications, 15000);
    }

    function initChatbot() {
        const toggle = doc.getElementById('chatbotToggle');
        const panel = doc.getElementById('chatbotPanel');
        const close = doc.getElementById('chatbotClose');
        const clear = doc.getElementById('chatbotClear');
        const messages = doc.getElementById('chatbotMessages');
        const form = doc.getElementById('chatbotForm');
        const input = doc.getElementById('chatbotInput');
        const sendButton = doc.getElementById('chatbotSend');
        const suggestionsWrap = doc.getElementById('chatbotSuggestions');
        if (!toggle || !panel || !messages || !form || !input) return;

        const storageKey = 'medisphere_chatbot_history_v3';
        const maxSuggestions = 3;
        const defaultSuggestions = [
            'How do I book an appointment?',
            'Find doctors near me',
            'Show emergency hospitals on map'
        ];
        let history = [];
        let loadingBubble = null;

        const assistantIntro = 'Hi, I can help you reach the right care step quickly. Ask about booking, doctors, reports, AI scanner, emergency map, billing, login, or privacy.';
        const assistantLogoHtml = `
            <span class="chatbot-logo chatbot-logo-message" aria-hidden="true">
                <span class="chatbot-logo-ring"></span>
                <span class="chatbot-logo-core"><i class="fa-solid fa-heart-pulse"></i></span>
            </span>
        `;

        const scrollToBottom = () => {
            messages.scrollTop = messages.scrollHeight;
        };

        const persistHistory = () => {
            try {
                localStorage.setItem(storageKey, JSON.stringify(history.slice(-18)));
            } catch (error) { }
        };

        const messageTime = () => new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

        const messageLabel = (role) => role === 'user' ? 'You' : 'MediSphere Assistant';

        const cardHtml = (cards = []) => {
            if (!Array.isArray(cards) || !cards.length) return '';
            return `<div class="chatbot-answer-cards">${cards.map((card) => `
                <a class="chatbot-answer-card" href="${escapeHtml(card.url || '#')}">
                    <span>${escapeHtml(card.tag || 'Open')}</span>
                    <strong>${escapeHtml(card.title || 'Open')}</strong>
                    <small>${escapeHtml(card.text || '')}</small>
                </a>
            `).join('')}</div>`;
        };

        const appendMessage = (role, text, cards = [], save = true) => {
            const isUser = role === 'user';
            const turn = doc.createElement('div');
            turn.className = `chatbot-turn ${isUser ? 'user' : 'assistant'}`;
            turn.innerHTML = `
                ${isUser ? '' : `<div class="chatbot-avatar">${assistantLogoHtml}</div>`}
                <div class="${isUser ? 'user-msg' : 'bot-msg'}">
                    <div class="chatbot-message-meta">${messageLabel(role)} · ${messageTime()}</div>
                    <div class="chatbot-message-text">${escapeHtml(text || '').replace(/\n/g, '<br>')}</div>
                    ${cardHtml(cards)}
                </div>
            `;
            messages.appendChild(turn);
            scrollToBottom();

            if (save) {
                history.push({ role: role === 'user' ? 'user' : 'assistant', text: text || '', cards: cards || [] });
                persistHistory();
            }
            return turn;
        };

        const showTyping = () => {
            loadingBubble = doc.createElement('div');
            loadingBubble.className = 'chatbot-turn assistant';
            loadingBubble.innerHTML = `
                <div class="chatbot-avatar">${assistantLogoHtml}</div>
                <div class="bot-msg chatbot-typing"><span></span><span></span><span></span></div>
            `;
            messages.appendChild(loadingBubble);
            scrollToBottom();
        };

        const hideTyping = () => {
            if (loadingBubble) {
                loadingBubble.remove();
                loadingBubble = null;
            }
        };

        const normalizeSuggestions = (suggestions = []) => {
            const source = Array.isArray(suggestions) && suggestions.length ? suggestions : defaultSuggestions;
            const seen = new Set();
            return source
                .map((question) => String(question || '').trim())
                .filter((question) => {
                    if (!question || seen.has(question.toLowerCase())) return false;
                    seen.add(question.toLowerCase());
                    return true;
                })
                .slice(0, maxSuggestions);
        };

        const suggestionIcon = (question) => {
            const value = String(question || '').toLowerCase();
            if (/emergency|hospital|map|near|location|pharmac/.test(value)) return 'fa-location-dot';
            if (/doctor|appointment|book|consultation/.test(value)) return 'fa-user-doctor';
            if (/report|upload|document|file/.test(value)) return 'fa-file-medical';
            if (/scanner|scan|ai/.test(value)) return 'fa-wand-magic-sparkles';
            if (/payment|invoice|refund|bill/.test(value)) return 'fa-receipt';
            if (/login|password|register|verification/.test(value)) return 'fa-key';
            return 'fa-arrow-right';
        };

        const setSuggestions = (suggestions = []) => {
            if (!suggestionsWrap) return;
            const nextSuggestions = normalizeSuggestions(suggestions);
            suggestionsWrap.innerHTML = '';
            suggestionsWrap.hidden = !nextSuggestions.length;
            nextSuggestions.forEach((question) => {
                const button = doc.createElement('button');
                button.type = 'button';
                button.className = 'btn btn-light btn-sm quick-reply';
                button.dataset.question = question;
                button.innerHTML = `<i class="fa-solid ${suggestionIcon(question)}" aria-hidden="true"></i><span>${escapeHtml(question)}</span>`;
                button.addEventListener('click', () => sendQuestion(question));
                suggestionsWrap.appendChild(button);
            });
        };

        const renderIntro = () => {
            messages.innerHTML = '';
            appendMessage('assistant', assistantIntro, [], false);
            setSuggestions(defaultSuggestions);
        };

        const setBusy = (busy) => {
            input.disabled = busy;
            if (sendButton) sendButton.disabled = busy;
            panel.classList.toggle('chatbot-busy', busy);
            panel.setAttribute('aria-busy', busy ? 'true' : 'false');
        };

        const setOpen = (open) => {
            panel.classList.toggle('open', open);
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            if (open) {
                window.setTimeout(() => input.focus(), 120);
            }
        };

        const sendQuestion = (rawQuestion) => {
            const question = String(rawQuestion || '').trim();
            if (!question || input.disabled) return;

            setOpen(true);
            input.value = '';
            appendMessage('user', question);
            setBusy(true);
            showTyping();

            const payload = new FormData();
            payload.append('csrf_token', window.MEDISPHERE.csrf || '');
            payload.append('message', question);
            payload.append('history', JSON.stringify(history.slice(-8).map((item) => ({
                role: item.role,
                text: item.text
            }))));

            fetch(`${window.MEDISPHERE.baseUrl}/index.php?route=api/chatbot`, {
                method: 'POST',
                body: payload,
                headers: { Accept: 'application/json' }
            })
                .then((response) => response.json().then((data) => ({ ok: response.ok, data })))
                .then(({ ok, data }) => {
                    hideTyping();
                    if (!ok || !data?.success) {
                        throw new Error(data?.message || 'Assistant could not answer right now.');
                    }
                    appendMessage('assistant', data.answer || 'I found a project result for you.', data.cards || []);
                    setSuggestions(data.suggestions || []);
                })
                .catch((error) => {
                    hideTyping();
                    appendMessage('assistant', error.message || 'Assistant could not answer right now. Please try again.');
                })
                .finally(() => {
                    setBusy(false);
                    input.focus();
                });
        };

        try {
            const saved = JSON.parse(localStorage.getItem(storageKey) || '[]');
            if (Array.isArray(saved) && saved.length) {
                history = saved.slice(-18);
                messages.innerHTML = '';
                history.forEach((item) => appendMessage(item.role, item.text, item.cards || [], false));
                setSuggestions(defaultSuggestions);
            } else {
                renderIntro();
            }
        } catch (error) {
            renderIntro();
        }

        toggle.addEventListener('click', () => {
            setOpen(!panel.classList.contains('open'));
        });
        close && close.addEventListener('click', () => setOpen(false));
        clear && clear.addEventListener('click', () => {
            history = [];
            try {
                localStorage.removeItem(storageKey);
            } catch (error) { }
            renderIntro();
            input.focus();
        });
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            sendQuestion(input.value);
        });
        doc.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && panel.classList.contains('open')) {
                setOpen(false);
                toggle.focus();
            }
        });
    }

    function setMapOverlay(container, state, text) {
        container.dataset.overlayText = text;
        container.classList.remove('loading', 'no-key', 'error');
        if (state) container.classList.add(state);
    }

    function setMapDiscoveryLoading(state, loading, text = 'Loading healthcare markers...') {
        if (!state?.container || state.mode !== 'explorer') return;
        state.container.classList.toggle('nearby-loading', Boolean(loading));
        state.container.setAttribute('aria-busy', loading ? 'true' : 'false');
        if (loading) {
            state.container.dataset.busyText = text;
        }

        const list = doc.getElementById('nearbyResultsList');
        if (loading && list && !state.nearbyFacilities.length) {
            list.innerHTML = `<div class="nearby-loading-row">${escapeHtml(text)}</div>`;
        }
    }

    function loadGoogleMaps() {
        if (window.google && window.google.maps) return Promise.resolve(window.google.maps);
        const key = window.MEDISPHERE.googleMapsKey;
        if (!key) return Promise.reject(new Error('GOOGLE_MAPS_KEY_MISSING'));
        if (googleMapsPromise) return googleMapsPromise;

        googleMapsPromise = new Promise((resolve, reject) => {
            window.__initMediSphereMaps = () => resolve(window.google.maps);
            const script = doc.createElement('script');
            script.src = `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(key)}&libraries=places,geometry&callback=__initMediSphereMaps`;
            script.async = true;
            script.defer = true;
            script.onerror = () => reject(new Error('GOOGLE_MAPS_LOAD_FAILED'));
            doc.head.appendChild(script);
        });

        return googleMapsPromise;
    }

    function loadLeafletMaps() {
        if (window.L && window.L.map) return Promise.resolve(window.L);
        if (leafletPromise) return leafletPromise;

        leafletPromise = new Promise((resolve, reject) => {
            const existingCss = doc.querySelector('link[data-leaflet-css]');
            if (!existingCss) {
                const link = doc.createElement('link');
                link.rel = 'stylesheet';
                link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
                link.integrity = 'sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=';
                link.crossOrigin = '';
                link.dataset.leafletCss = 'true';
                doc.head.appendChild(link);
            }

            const script = doc.createElement('script');
            script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
            script.integrity = 'sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=';
            script.crossOrigin = '';
            script.async = true;
            script.onload = () => resolve(window.L);
            script.onerror = () => reject(new Error('LEAFLET_LOAD_FAILED'));
            doc.head.appendChild(script);
        });

        return leafletPromise;
    }

    function parseCoordinates(value) {
        if (!value || typeof value !== 'string') return null;
        const parts = value.split(',').map((part) => Number(part.trim()));
        if (parts.length !== 2 || Number.isNaN(parts[0]) || Number.isNaN(parts[1])) return null;
        return { lat: parts[0], lng: parts[1] };
    }

    function hashString(value) {
        return String(value || '').split('').reduce((hash, char) => {
            return ((hash << 5) - hash) + char.charCodeAt(0);
        }, 0);
    }

    function fallbackCoordinates(city, country, seed) {
        const cityKey = String(city || '').toLowerCase().trim();
        const centers = {
            lahore: { lat: 31.5204, lng: 74.3587 },
            karachi: { lat: 24.8607, lng: 67.0011 },
            islamabad: { lat: 33.6844, lng: 73.0479 },
            rawalpindi: { lat: 33.5651, lng: 73.0169 },
            faisalabad: { lat: 31.4504, lng: 73.1350 },
            multan: { lat: 30.1575, lng: 71.5249 },
            peshawar: { lat: 34.0151, lng: 71.5249 },
            quetta: { lat: 30.1798, lng: 66.9750 },
            hyderabad: { lat: 25.3960, lng: 68.3578 },
            gujranwala: { lat: 32.1877, lng: 74.1945 },
            sialkot: { lat: 32.4945, lng: 74.5229 },
        };
        const matchedCity = Object.keys(centers).find((key) => cityKey.includes(key));
        const base = centers[matchedCity] || (String(country || '').toLowerCase().includes('pakistan') ? defaultCenter : defaultCenter);
        const hash = Math.abs(hashString(`${seed || ''}${city || ''}`));
        const latOffset = ((hash % 180) - 90) / 9000;
        const lngOffset = (((Math.floor(hash / 180)) % 180) - 90) / 9000;
        return { lat: base.lat + latOffset, lng: base.lng + lngOffset };
    }

    function degreesToRadians(value) {
        return value * (Math.PI / 180);
    }

    function calculateDistanceKm(pointA, pointB) {
        if (!pointA || !pointB) return null;
        const earthRadius = 6371;
        const dLat = degreesToRadians(pointB.lat - pointA.lat);
        const dLng = degreesToRadians(pointB.lng - pointA.lng);
        const a = Math.sin(dLat / 2) ** 2 +
            Math.cos(degreesToRadians(pointA.lat)) * Math.cos(degreesToRadians(pointB.lat)) * Math.sin(dLng / 2) ** 2;
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        return earthRadius * c;
    }

    function escapeHtml(value) {
        return String(value ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }

    function googleMapsSearchUrl(query) {
        return `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(String(query || 'healthcare near me').trim() || 'healthcare near me')}`;
    }

    function facilityGoogleQuery(facility) {
        if (!facility) return 'healthcare near me';
        return [facility.name, facility.displayAddress || facility.address, facility.city, facility.country]
            .filter(Boolean)
            .join(', ') || `${facility.position?.lat || ''},${facility.position?.lng || ''}`;
    }

    function facilityTypeLabel(type) {
        const labels = {
            hospital: 'Hospital',
            global_hospital: 'Featured hospital',
            doctor: 'Doctor clinic',
            pharmacy: 'Pharmacy',
            emergency: 'Emergency'
        };
        return labels[type] || 'Healthcare';
    }

    function mapTypeImage(type) {
        const images = {
            hospital: 'https://images.unsplash.com/photo-1586773860418-d37222d8fce3?auto=format&fit=crop&w=700&q=80',
            global_hospital: 'https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?auto=format&fit=crop&w=700&q=80',
            doctor: 'https://images.unsplash.com/photo-1559839734-2b71ea197ec2?auto=format&fit=crop&w=700&q=80',
            pharmacy: 'https://images.unsplash.com/photo-1587854692152-cbe660dbde88?auto=format&fit=crop&w=700&q=80',
            emergency: 'https://images.unsplash.com/photo-1584982751601-97dcc096659c?auto=format&fit=crop&w=700&q=80'
        };
        return images[type] || images.hospital;
    }

    function withFacilityImage(facility) {
        return {
            ...facility,
            image_url: facility.image_url || mapTypeImage(facility.type)
        };
    }

    function getStateCenter(state) {
        if (state.provider === 'leaflet') {
            const center = state.map.getCenter();
            return { lat: center.lat, lng: center.lng };
        }
        const center = state.map.getCenter();
        return { lat: center.lat(), lng: center.lng() };
    }

    function googleQueryForState(state, fallbackQuery = '') {
        if (state.selectedFacility) return facilityGoogleQuery(state.selectedFacility);
        const cleanFallback = String(fallbackQuery || '').trim();
        if (cleanFallback) return cleanFallback;
        if (state.currentOrigin) return `${state.currentOrigin.lat},${state.currentOrigin.lng}`;
        const center = getStateCenter(state);
        return `${center.lat},${center.lng}`;
    }

    function syncGoogleMapLinks(state, query = '') {
        const url = googleMapsSearchUrl(googleQueryForState(state, query));
        doc.querySelectorAll('[data-google-map-link]').forEach((link) => {
            link.href = url;
        });
    }

    function runMapPulse(container) {
        if (!container) return;
        container.classList.remove('map-focus-pulse');
        void container.offsetWidth;
        container.classList.add('map-focus-pulse');
        window.setTimeout(() => container.classList.remove('map-focus-pulse'), 1400);
    }

    function highlightMapArea(state, facility = null) {
        const target = facility?.position || state.currentOrigin || getStateCenter(state);
        runMapPulse(state.container);

        if (state.provider === 'leaflet') {
            if (state.highlightCircle) {
                state.map.removeLayer(state.highlightCircle);
            }
            state.highlightCircle = window.L.circle([target.lat, target.lng], {
                radius: facility ? 950 : 2200,
                color: '#0ea5e9',
                weight: 2,
                opacity: 0.9,
                fillColor: '#0ea5e9',
                fillOpacity: 0.12
            }).addTo(state.map);
            state.map.setView([target.lat, target.lng], facility ? 15 : Math.max(state.map.getZoom(), 12));
            window.setTimeout(() => {
                if (state.highlightCircle) {
                    state.map.removeLayer(state.highlightCircle);
                    state.highlightCircle = null;
                }
            }, 3600);
            return;
        }

        if (state.highlightCircle) {
            state.highlightCircle.setMap(null);
        }
        state.highlightCircle = new window.google.maps.Circle({
            map: state.map,
            center: target,
            radius: facility ? 950 : 2200,
            strokeColor: '#0ea5e9',
            strokeOpacity: 0.9,
            strokeWeight: 2,
            fillColor: '#0ea5e9',
            fillOpacity: 0.12
        });
        state.map.panTo(target);
        state.map.setZoom(facility ? 15 : Math.max(state.map.getZoom(), 12));
        window.setTimeout(() => {
            if (state.highlightCircle) {
                state.highlightCircle.setMap(null);
                state.highlightCircle = null;
            }
        }, 3600);
    }

    function bindGoogleAreaLinks(state, searchInput, searchHandler) {
        if (state.googleLinksBound) return;
        state.googleLinksBound = true;
        const links = Array.from(doc.querySelectorAll('[data-google-map-link]'));
        if (!links.length) return;

        const updateLinks = () => syncGoogleMapLinks(state, searchInput?.value.trim() || '');
        updateLinks();

        searchInput && searchInput.addEventListener('input', updateLinks);
        links.forEach((link) => {
            link.addEventListener('click', () => {
                const query = searchInput?.value.trim() || '';
                syncGoogleMapLinks(state, query);
                if (!state.selectedFacility && query && searchHandler) {
                    searchHandler(state, query);
                }
                highlightMapArea(state, state.selectedFacility);
            });
        });
    }

    function requestedFocusTypes(state) {
        const focusType = state.container.dataset.focusType || '';
        if (!focusType) return null;
        if (focusType === 'hospital' || focusType === 'global_hospital') return ['hospital', 'global_hospital'];
        if (['doctor', 'pharmacy', 'emergency'].includes(focusType)) return [focusType];
        return null;
    }

    function applyRequestedFocusFilter(state) {
        const types = requestedFocusTypes(state);
        if (!types) return;
        state.activeFilters = new Set(types);
    }

    function syncFilterButtonsFromState(state) {
        if (state.mode !== 'explorer') return;
        doc.querySelectorAll('#facilityFilterGroup [data-filter-type]').forEach((button) => {
            const type = button.dataset.filterType;
            const types = type === 'hospital' ? ['hospital', 'global_hospital'] : [type];
            const isActive = types.some((item) => state.activeFilters.has(item));
            button.classList.toggle('active', isActive);
        });
    }

    function autoOpenMapCanvas(state) {
        if (state.container.dataset.autoOpen !== '1' || state.autoOpened) return;
        state.autoOpened = true;
        window.setTimeout(() => {
            state.container.scrollIntoView({ behavior: 'smooth', block: 'center' });
            runMapPulse(state.container);
            if (state.provider === 'leaflet') {
                state.map.invalidateSize();
            }
        }, 180);
    }

    function mapSearchVariants(query) {
        const clean = String(query || '').trim();
        if (!clean) return [];
        const variants = [clean];
        const karachiFixed = clean.replace(/\bkarach\b/ig, 'karachi');
        if (karachiFixed !== clean) variants.push(karachiFixed);
        return variants;
    }

    function getMarkerOptions(googleMaps, facility) {
        const palette = {
            hospital: '#0ea5e9',
            global_hospital: '#0f172a',
            doctor: '#10b981',
            pharmacy: '#f59e0b',
            emergency: '#ef4444'
        };

        const labels = {
            hospital: 'H',
            global_hospital: '*',
            doctor: 'D',
            pharmacy: 'P',
            emergency: 'E'
        };

        return {
            icon: {
                path: googleMaps.SymbolPath.CIRCLE,
                scale: 10,
                fillColor: palette[facility.type] || '#1976D2',
                fillOpacity: 1,
                strokeColor: '#ffffff',
                strokeWeight: 2
            },
            label: {
                text: labels[facility.type] || 'M',
                color: '#ffffff',
                fontSize: '10px',
                fontWeight: '700'
            },
            title: facility.name
        };
    }

    function normalizeFacilities(payload) {
        const hospitals = Array.isArray(payload.hospitals) ? payload.hospitals : [];
        const worldHospitals = Array.isArray(payload.world_hospitals) ? payload.world_hospitals : [];
        const doctors = Array.isArray(payload.doctors) ? payload.doctors : [];
        const supportPlaces = Array.isArray(payload.support_places) ? payload.support_places : [];
        const normalized = [];
        const positionUseCount = {};

        worldHospitals.forEach((item) => {
            const parsedPosition = parseCoordinates(item.coordinates);
            const position = parsedPosition || fallbackCoordinates(item.city, item.country, item.name);
            normalized.push(withFacilityImage({
                ...item,
                position,
                coordinates_source: parsedPosition ? 'provided' : 'estimated',
                displayAddress: [item.address, item.city, item.country].filter(Boolean).join(', '),
                type: 'global_hospital'
            }));
        });

        hospitals.forEach((item) => {
            const parsedPosition = parseCoordinates(item.coordinates);
            const position = parsedPosition || fallbackCoordinates(item.city, item.country, item.name);
            normalized.push(withFacilityImage({
                ...item,
                position,
                coordinates_source: parsedPosition ? 'provided' : 'estimated',
                displayAddress: [item.address, item.city, item.country].filter(Boolean).join(', '),
                type: 'hospital'
            }));
        });

        doctors.forEach((item) => {
            const parsedPosition = parseCoordinates(item.hospital_coordinates);
            const basePosition = parsedPosition || fallbackCoordinates(item.hospital_city, item.hospital_country, item.name);
            const key = `${basePosition.lat.toFixed(4)},${basePosition.lng.toFixed(4)}`;
            const index = positionUseCount[key] || 0;
            positionUseCount[key] = index + 1;
            const offset = index * 0.00025;
            normalized.push(withFacilityImage({
                ...item,
                position: {
                    lat: basePosition.lat + offset,
                    lng: basePosition.lng + offset
                },
                coordinates_source: parsedPosition ? 'provided' : 'estimated',
                displayAddress: [item.hospital_address, item.hospital_city, item.hospital_country].filter(Boolean).join(', '),
                type: 'doctor'
            }));
        });

        supportPlaces.forEach((item) => {
            const safeType = ['pharmacy', 'emergency'].includes(item.type) ? item.type : 'pharmacy';
            const parsedPosition = parseCoordinates(item.coordinates);
            const position = parsedPosition || fallbackCoordinates(item.city, item.country, item.name);
            normalized.push(withFacilityImage({
                ...item,
                position,
                coordinates_source: parsedPosition ? 'provided' : 'estimated',
                displayAddress: [item.address, item.city, item.country].filter(Boolean).join(', '),
                type: safeType
            }));
        });

        return normalized;
    }

    function buildFacilityInfoHtml(facility, distanceKm) {
        const meta = [];
        if (facility.type === 'doctor') {
            meta.push(`<div><strong>Specialization:</strong> ${escapeHtml(facility.specialization || 'General')}</div>`);
            meta.push(`<div><strong>Hospital:</strong> ${escapeHtml(facility.hospital_name || 'Independent')}</div>`);
            meta.push(`<div><strong>Fee:</strong> PKR ${Number(facility.consultation_fee || 0).toLocaleString()}</div>`);
        } else if (facility.type === 'hospital') {
            meta.push(`<div><strong>Facilities:</strong> ${escapeHtml(facility.facilities || 'Not specified')}</div>`);
            meta.push(`<div><strong>Status:</strong> ${escapeHtml(facility.verified_status || 'pending')}</div>`);
        } else if (facility.type === 'global_hospital') {
            meta.push(`<div><strong>Global rank:</strong> #${escapeHtml(facility.global_rank || '')}</div>`);
            meta.push(`<div><strong>Recognition:</strong> ${escapeHtml(facility.recognition || 'World hospital spotlight')}</div>`);
            meta.push(`<div><strong>Source:</strong> ${escapeHtml(facility.source || 'Newsweek/Statista 2026')}</div>`);
        } else if (facility.type === 'pharmacy' || facility.type === 'emergency') {
            meta.push(`<div><strong>Care status:</strong> ${escapeHtml(facility.rating || 'Available')}</div>`);
        }
        if (distanceKm !== null) {
            meta.push(`<div><strong>Distance:</strong> ${distanceKm.toFixed(1)} km</div>`);
        }
        if (facility.coordinates_source === 'estimated') {
            meta.push('<div><strong>Map pin:</strong> Geocoding from address</div>');
        } else if (facility.coordinates_source === 'fallback_nearby') {
            meta.push('<div><strong>Map pin:</strong> Nearby fallback when live places are unavailable</div>');
        }
        if (facility.position) {
            meta.push(`<div><strong>Coordinates:</strong> ${Number(facility.position.lat).toFixed(5)}, ${Number(facility.position.lng).toFixed(5)}</div>`);
        }
        meta.push(`<div><strong>Address:</strong> ${escapeHtml(facility.displayAddress || facility.address || 'Not available')}</div>`);
        const image = facility.image_url
            ? `<img class="map-popup-photo" src="${escapeHtml(facility.image_url)}" alt="${escapeHtml(facility.name)}">`
            : '';
        return `
            <div class="map-infowindow">
                ${image}
                <h6 class="mb-1">${escapeHtml(facility.name)}</h6>
                <div class="small text-muted text-capitalize mb-2">${escapeHtml(facilityTypeLabel(facility.type))}</div>
                <div class="small d-grid gap-1">${meta.join('')}</div>
                <div class="map-popup-note mt-2">Selected on the live map</div>
            </div>
        `;
    }

    function updateExplorerInfoCard(state, facility, distanceKm) {
        const infoCard = doc.getElementById('mapInfoCard');
        if (!infoCard || state.mode !== 'explorer') return;
        const subtitle = facility.type === 'doctor'
            ? `${facility.specialization || 'General'} - ${facility.hospital_name || 'Independent'}`
            : `${facility.city || ''} ${facility.country ? '- ' + facility.country : ''}`.trim();
        infoCard.innerHTML = `
            ${facility.image_url ? `<img class="map-info-photo" src="${escapeHtml(facility.image_url)}" alt="${escapeHtml(facility.name)}">` : ''}
            <div class="small text-muted text-uppercase fw-semibold mb-2">Selected facility</div>
            <h5 class="mb-1">${escapeHtml(facility.name)}</h5>
            <p class="text-muted mb-2">${escapeHtml(subtitle || facilityTypeLabel(facility.type))}</p>
            <div class="map-info-meta small text-muted mb-3">${escapeHtml(facility.displayAddress || facility.address || 'Address not available')}</div>
            <div class="d-grid gap-1 small">
                ${distanceKm !== null ? `<div><strong>Distance:</strong> ${distanceKm.toFixed(1)} km</div>` : ''}
                ${facility.phone ? `<div><strong>Phone:</strong> ${escapeHtml(facility.phone)}</div>` : ''}
                ${facility.email ? `<div><strong>Email:</strong> ${escapeHtml(facility.email)}</div>` : ''}
                ${facility.consultation_fee ? `<div><strong>Consultation:</strong> PKR ${Number(facility.consultation_fee).toLocaleString()}</div>` : ''}
                ${facility.facilities ? `<div><strong>Facilities:</strong> ${escapeHtml(facility.facilities)}</div>` : ''}
                ${facility.coordinates_source === 'estimated' ? '<div><strong>Map pin:</strong> Geocoding from address</div>' : ''}
                ${facility.coordinates_source === 'fallback_nearby' ? '<div><strong>Map pin:</strong> Nearby fallback when live places are unavailable</div>' : ''}
                ${facility.position ? `<div><strong>Coordinates:</strong> ${Number(facility.position.lat).toFixed(5)}, ${Number(facility.position.lng).toFixed(5)}</div>` : ''}
            </div>
            <div class="map-info-actions">
                <button class="btn btn-outline-primary btn-sm" type="button" data-map-selected-center><i class="fa-solid fa-crosshairs"></i> Center</button>
                <button class="btn btn-primary btn-sm" type="button" data-map-selected-route><i class="fa-solid fa-route"></i> Route</button>
                <button class="btn btn-outline-primary btn-sm" type="button" data-map-selected-google><i class="fa-brands fa-google"></i> Google</button>
            </div>
        `;
        const centerButton = infoCard.querySelector('[data-map-selected-center]');
        const routeButton = infoCard.querySelector('[data-map-selected-route]');
        const googleButton = infoCard.querySelector('[data-map-selected-google]');
        syncGoogleMapLinks(state, facilityGoogleQuery(facility));
        centerButton && centerButton.addEventListener('click', () => {
            if (state.provider === 'leaflet') {
                state.map.setView([facility.position.lat, facility.position.lng], 15);
                return;
            }
            state.map.panTo(facility.position);
            state.map.setZoom(15);
        });
        routeButton && routeButton.addEventListener('click', () => {
            if (state.provider === 'leaflet') {
                requestLeafletDirections(state, facility);
                return;
            }
            requestDirections(state, facility);
        });
        googleButton && googleButton.addEventListener('click', () => {
            highlightMapArea(state, facility);
            window.open(googleMapsSearchUrl(facilityGoogleQuery(facility)), '_blank', 'noopener');
        });
    }

    function updateDirectoryDistances(state) {
        const origin = state.currentOrigin;
        doc.querySelectorAll('.directory-card').forEach((card) => {
            const rawLat = String(card.dataset.providerLat || '').trim();
            const rawLng = String(card.dataset.providerLng || '').trim();
            const lat = Number(rawLat);
            const lng = Number(rawLng);
            const valueEl = card.querySelector('.distance-value');
            if (!valueEl) return;
            if (!rawLat || !rawLng || Number.isNaN(lat) || Number.isNaN(lng)) {
                valueEl.textContent = 'N/A';
                return;
            }
            if (!origin) {
                valueEl.textContent = '-';
                return;
            }
            const distance = calculateDistanceKm(origin, { lat, lng });
            valueEl.textContent = distance !== null ? `${distance.toFixed(1)} km` : '-';
        });
    }

    function setDirectionsButtonsState(state, enabled) {
        const explorerGet = doc.getElementById('getDirectionsBtn');
        const explorerClear = doc.getElementById('clearDirectionsBtn');
        const directoryClear = doc.getElementById('directoryClearDirectionsBtn');
        if (state.mode === 'explorer') {
            if (explorerGet) explorerGet.disabled = !enabled;
            if (explorerClear) explorerClear.disabled = !state.hasActiveRoute;
        }
        if (directoryClear) directoryClear.disabled = !state.hasActiveRoute;
    }

    function ensureRouteSummary(state) {
        if (state.routeSummaryEl) return state.routeSummaryEl;
        const summary = doc.createElement('div');
        summary.className = 'map-route-summary d-none';
        state.container.appendChild(summary);
        state.routeSummaryEl = summary;
        return summary;
    }

    function updateRouteSummary(state, leg) {
        const summary = ensureRouteSummary(state);
        if (!leg) {
            summary.classList.add('d-none');
            summary.innerHTML = '';
            return;
        }
        summary.classList.remove('d-none');
        summary.innerHTML = `
            <strong>Active Route</strong>
            <div class="route-meta mt-1">${escapeHtml(leg.start_address)} to ${escapeHtml(leg.end_address)}</div>
            <div class="mt-2 d-flex gap-3 small">
                <span><strong>${escapeHtml(leg.distance?.text || '')}</strong> distance</span>
                <span><strong>${escapeHtml(leg.duration?.text || '')}</strong> duration</span>
            </div>
        `;
    }

    function renderNearbyList(state) {
        const list = doc.getElementById('nearbyResultsList');
        const count = doc.getElementById('mapNearbyCount');
        if (count) count.textContent = String(state.nearbyFacilities.length);
        if (!list || state.mode !== 'explorer') return;

        if (!state.nearbyFacilities.length) {
            list.innerHTML = '<div class="text-muted small">Nearby healthcare options will appear here.</div>';
            return;
        }

        list.innerHTML = state.nearbyFacilities.map((facility, index) => `
            <button type="button" class="nearby-result-item text-start" data-nearby-index="${index}">
                <strong>${escapeHtml(facility.name)}</strong>
                <small>${escapeHtml(facilityTypeLabel(facility.type))} - ${escapeHtml(facility.displayAddress || facility.address || '')}</small>
                <small>${facility.distanceKm !== null ? `${facility.distanceKm.toFixed(1)} km away` : 'Distance unavailable'}</small>
            </button>
        `).join('');

        list.querySelectorAll('[data-nearby-index]').forEach((button) => {
            button.addEventListener('click', () => {
                const facility = state.nearbyFacilities[Number(button.dataset.nearbyIndex)];
                if (!facility) return;
                if (state.provider === 'leaflet') {
                    selectLeafletFacility(state, facility);
                    state.map.setView([facility.position.lat, facility.position.lng], 15);
                } else {
                    selectFacility(state, facility);
                    state.map.panTo(facility.position);
                    state.map.setZoom(15);
                }
            });
        });
    }

    function refreshMarkerVisibility(state) {
        state.markers.forEach((entry) => {
            const visible = state.activeFilters.has(entry.facility.type);
            entry.marker.setMap(visible ? state.map : null);
        });
        state.nearbyMarkers.forEach((entry) => {
            const visible = state.activeFilters.has(entry.facility.type);
            entry.marker.setMap(visible ? state.map : null);
        });
    }

    function selectFacility(state, facility, marker = null) {
        state.selectedFacility = facility;
        const distance = state.currentOrigin ? calculateDistanceKm(state.currentOrigin, facility.position) : null;
        state.infoWindow.setContent(buildFacilityInfoHtml(facility, distance));
        if (marker) {
            state.infoWindow.open({ anchor: marker, map: state.map });
        } else {
            state.infoWindow.setPosition(facility.position);
            state.infoWindow.open({ map: state.map });
        }
        updateExplorerInfoCard(state, facility, distance);
        syncGoogleMapLinks(state, facilityGoogleQuery(facility));
        setDirectionsButtonsState(state, true);
    }

    function focusFacilityById(state, providerId) {
        const match = state.markers.find((entry) => String(entry.facility.id) === String(providerId));
        if (!match) return;
        state.map.panTo(match.facility.position);
        state.map.setZoom(15);
        selectFacility(state, match.facility, match.marker);
    }

    function clearDirections(state) {
        state.directionsRenderer.set('directions', null);
        state.hasActiveRoute = false;
        updateRouteSummary(state, null);
        setDirectionsButtonsState(state, !!state.selectedFacility);
        const directionState = doc.getElementById('mapDirectionsState');
        if (directionState && state.mode === 'explorer') directionState.textContent = 'Ready';
    }

    function requestDirections(state, destinationFacility = null) {
        if (isMapPremiumLocked(state)) {
            openSubscriptionGate();
            return;
        }
        const facility = destinationFacility || state.selectedFacility;
        if (!facility || !state.currentOrigin) {
            Toastify({ text: 'Use your location or search an area before getting directions.', gravity: 'top', position: 'right', backgroundColor: '#dc3545' }).showToast();
            return;
        }

        state.directionsService.route({
            origin: state.currentOrigin,
            destination: facility.position,
            travelMode: window.google.maps.TravelMode.DRIVING
        }, (result, status) => {
            if (status !== 'OK' || !result.routes.length) {
                Toastify({ text: 'Directions could not be generated for this route.', gravity: 'top', position: 'right', backgroundColor: '#dc3545' }).showToast();
                return;
            }
            state.directionsRenderer.setDirections(result);
            state.hasActiveRoute = true;
            const leg = result.routes[0]?.legs?.[0] || null;
            updateRouteSummary(state, leg);
            setDirectionsButtonsState(state, true);
            const directionState = doc.getElementById('mapDirectionsState');
            if (directionState && state.mode === 'explorer') directionState.textContent = leg?.duration?.text || 'Active';
        });
    }

    function updateOrigin(state, position, zoom = 13) {
        state.currentOrigin = position;
        state.map.panTo(position);
        state.map.setZoom(zoom);
        if (!state.userMarker) {
            state.userMarker = new window.google.maps.Marker({
                map: state.map,
                position,
                title: 'Your location',
                icon: {
                    path: window.google.maps.SymbolPath.CIRCLE,
                    scale: 8,
                    fillColor: '#111827',
                    fillOpacity: 1,
                    strokeColor: '#ffffff',
                    strokeWeight: 2
                }
            });
        } else {
            state.userMarker.setPosition(position);
        }
        updateDirectoryDistances(state);
        if (state.selectedFacility) {
            const distance = calculateDistanceKm(position, state.selectedFacility.position);
            updateExplorerInfoCard(state, state.selectedFacility, distance);
        }
        discoverNearbyOptions(state, position, false);
    }

    function useBrowserLocation(state) {
        if (isMapPremiumLocked(state)) {
            openSubscriptionGate();
            return;
        }
        if (!navigator.geolocation) {
            Toastify({ text: 'Geolocation is not supported by your browser.', gravity: 'top', position: 'right', backgroundColor: '#dc3545' }).showToast();
            return;
        }
        navigator.geolocation.getCurrentPosition((result) => {
            updateOrigin(state, { lat: result.coords.latitude, lng: result.coords.longitude }, 14);
        }, () => {
            Toastify({ text: 'Location access was denied or unavailable.', gravity: 'top', position: 'right', backgroundColor: '#dc3545' }).showToast();
        }, { enableHighAccuracy: true, timeout: 10000 });
    }

    function searchMapLocation(state, query) {
        const variants = mapSearchVariants(query);
        if (!variants.length) return;
        const loweredVariants = variants.map((variant) => variant.toLowerCase());
        const match = state.markers.find((entry) => {
            const facility = entry.facility;
            return [
                facility.name,
                facility.displayAddress,
                facility.city,
                facility.country,
                facility.specialization,
                facility.hospital_name
            ].filter(Boolean).some((value) => {
                const loweredValue = String(value).toLowerCase();
                return loweredVariants.some((lowered) => loweredValue.includes(lowered));
            });
        });

        if (match) {
            state.map.panTo(match.facility.position);
            state.map.setZoom(15);
            selectFacility(state, match.facility, match.marker);
            discoverNearbyOptions(state, match.facility.position, false);
            highlightMapArea(state, match.facility);
            return;
        }

        const tryVariant = (index = 0) => state.geocoder.geocode({ address: variants[index] }, (results, status) => {
            if (status !== 'OK' || !results.length) {
                if (index + 1 < variants.length) {
                    tryVariant(index + 1);
                    return;
                }
                discoverNearbyOptions(state, getStateCenter(state));
                return;
            }
            const location = results[0].geometry.location;
            updateOrigin(state, { lat: location.lat(), lng: location.lng() }, 13);
            highlightMapArea(state);
        });
        tryVariant();
    }

    function loadNearbyPlaces(state, center) {
        if (!state.placesService) return;
        state.nearbyMarkers.forEach((entry) => entry.marker.setMap(null));
        state.nearbyMarkers = [];
        state.nearbyFacilities = [];
        renderNearbyList(state);

        const collected = [];
        let completed = 0;
        const finish = () => {
            completed += 1;
            if (completed < 2) return;
            state.nearbyFacilities = collected;
            state.nearbyFacilities.forEach((facility) => {
                const marker = new window.google.maps.Marker({
                    map: state.activeFilters.has(facility.type) ? state.map : null,
                    position: facility.position,
                    ...getMarkerOptions(window.google.maps, facility)
                });
                marker.addListener('click', () => selectFacility(state, facility, marker));
                state.nearbyMarkers.push({ facility, marker });
            });
            renderNearbyList(state);
        };

        const pharmacyRequest = { location: center, radius: 6000, type: 'pharmacy' };
        state.placesService.nearbySearch(pharmacyRequest, (results, status) => {
            if (status === window.google.maps.places.PlacesServiceStatus.OK && Array.isArray(results)) {
                results.slice(0, 8).forEach((place) => {
                    if (!place.geometry?.location) return;
                    const position = { lat: place.geometry.location.lat(), lng: place.geometry.location.lng() };
                    collected.push({
                        id: place.place_id,
                        type: 'pharmacy',
                        name: place.name,
                        rating: place.rating || null,
                        address: place.vicinity || '',
                        displayAddress: place.vicinity || '',
                        position,
                        distanceKm: calculateDistanceKm(center, position)
                    });
                });
            }
            finish();
        });

        const emergencyRequest = { location: center, radius: 10000, keyword: 'emergency hospital' };
        state.placesService.nearbySearch(emergencyRequest, (results, status) => {
            if (status === window.google.maps.places.PlacesServiceStatus.OK && Array.isArray(results)) {
                results.slice(0, 8).forEach((place) => {
                    if (!place.geometry?.location) return;
                    const position = { lat: place.geometry.location.lat(), lng: place.geometry.location.lng() };
                    collected.push({
                        id: place.place_id,
                        type: 'emergency',
                        name: place.name,
                        rating: place.rating || null,
                        address: place.vicinity || '',
                        displayAddress: place.vicinity || '',
                        position,
                        distanceKm: calculateDistanceKm(center, position)
                    });
                });
            }
            finish();
        });
    }

    function bindMapStyleControl(state) {
        if (state.mode !== 'explorer' || state.styleControlBound) return;
        const buttons = Array.from(doc.querySelectorAll('#mapStyleControl [data-map-style]'));
        if (!buttons.length) return;
        state.styleControlBound = true;

        const setActiveButton = (style) => {
            buttons.forEach((button) => {
                const isActive = button.dataset.mapStyle === style;
                button.classList.toggle('active', isActive);
                button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
            });
        };

        const setMapStyle = (style) => {
            const nextStyle = style === 'satellite' ? 'satellite' : 'road';
            setActiveButton(nextStyle);

            if (state.provider === 'leaflet') {
                const roadLayer = state.baseLayers?.road;
                const satelliteLayer = state.baseLayers?.satellite;
                if (!roadLayer || !satelliteLayer || !state.map) return;

                const activeLayer = nextStyle === 'satellite' ? satelliteLayer : roadLayer;
                const inactiveLayer = nextStyle === 'satellite' ? roadLayer : satelliteLayer;
                if (state.map.hasLayer(inactiveLayer)) {
                    state.map.removeLayer(inactiveLayer);
                }
                if (!state.map.hasLayer(activeLayer)) {
                    activeLayer.addTo(state.map);
                }
                return;
            }

            if (state.map?.setMapTypeId && window.google?.maps?.MapTypeId) {
                state.map.setMapTypeId(
                    nextStyle === 'satellite'
                        ? window.google.maps.MapTypeId.SATELLITE
                        : window.google.maps.MapTypeId.ROADMAP
                );
            }
        };

        buttons.forEach((button) => {
            button.addEventListener('click', (event) => {
                if (guardPremiumAction(event, button)) return;
                setMapStyle(button.dataset.mapStyle || 'road');
            });
        });
        const requestedStyle = new URLSearchParams(window.location.search).get('map_style');
        setMapStyle(!isMapPremiumLocked(state) && requestedStyle === 'satellite' ? 'satellite' : 'road');
    }

    function bindExplorerControls(state) {
        if (state.mode !== 'explorer') return;
        const searchInput = doc.getElementById('mapSearchInput');
        const searchBtn = doc.getElementById('mapSearchBtn');
        const useLocationBtn = doc.getElementById('mapUseLocationBtn');
        const resetBtn = doc.getElementById('mapResetViewBtn');
        const getDirectionsBtn = doc.getElementById('getDirectionsBtn');
        const clearDirectionsBtn = doc.getElementById('clearDirectionsBtn');
        const filterButtons = doc.querySelectorAll('#facilityFilterGroup [data-filter-type]');
        const hospitalCount = doc.getElementById('mapHospitalCount');
        const doctorCount = doc.getElementById('mapDoctorCount');

        bindMapStyleControl(state);
        bindGoogleAreaLinks(state, searchInput, searchMapLocation);
        hospitalCount && (hospitalCount.textContent = String(state.facilities.filter((facility) => facility.type === 'hospital' || facility.type === 'global_hospital').length));
        doctorCount && (doctorCount.textContent = String(state.facilities.filter((facility) => facility.type === 'doctor').length));

        searchBtn && searchBtn.addEventListener('click', () => {
            const query = searchInput?.value.trim();
            state.selectedFacility = null;
            syncGoogleMapLinks(state, query);
            searchMapLocation(state, query);
        });
        searchInput && searchInput.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                state.selectedFacility = null;
                syncGoogleMapLinks(state, searchInput.value.trim());
                searchMapLocation(state, searchInput.value.trim());
            }
        });
        if (searchInput?.value.trim()) {
            setTimeout(() => searchMapLocation(state, searchInput.value.trim()), 450);
        }
        useLocationBtn && useLocationBtn.addEventListener('click', (event) => {
            if (guardPremiumAction(event, useLocationBtn)) return;
            useBrowserLocation(state);
        });
        resetBtn && resetBtn.addEventListener('click', () => {
            discoverNearbyOptions(state);
            clearDirections(state);
        });
        getDirectionsBtn && getDirectionsBtn.addEventListener('click', (event) => {
            if (guardPremiumAction(event, getDirectionsBtn)) return;
            requestDirections(state);
        });
        clearDirectionsBtn && clearDirectionsBtn.addEventListener('click', () => clearDirections(state));

        filterButtons.forEach((button) => {
            button.addEventListener('click', () => {
                const type = button.dataset.filterType;
                const types = type === 'hospital' ? ['hospital', 'global_hospital'] : [type];
                const isActive = types.every((item) => state.activeFilters.has(item));
                if (isActive) {
                    types.forEach((item) => state.activeFilters.delete(item));
                    button.classList.remove('active');
                } else {
                    types.forEach((item) => state.activeFilters.add(item));
                    button.classList.add('active');
                }
                refreshMarkerVisibility(state);
                discoverNearbyOptions(state, state.currentOrigin || getStateCenter(state), false);
            });
        });
    }

    function bindDirectoryControls(state) {
        const useLocationBtn = doc.getElementById('directoryUseLocationBtn');
        const clearDirectionsBtn = doc.getElementById('directoryClearDirectionsBtn');
        useLocationBtn && useLocationBtn.addEventListener('click', () => useBrowserLocation(state));
        clearDirectionsBtn && clearDirectionsBtn.addEventListener('click', () => clearDirections(state));

        doc.querySelectorAll('[data-map-focus-id]').forEach((button) => {
            button.addEventListener('click', () => focusFacilityById(state, button.dataset.mapFocusId));
        });

        doc.querySelectorAll('[data-map-directions-id]').forEach((button) => {
            button.addEventListener('click', () => {
                const match = state.markers.find((entry) => String(entry.facility.id) === String(button.dataset.mapDirectionsId));
                if (!match) return;
                selectFacility(state, match.facility, match.marker);
                requestDirections(state, match.facility);
            });
        });

        doc.querySelectorAll('.directory-card').forEach((card) => {
            const providerId = card.dataset.providerId;
            const focusBtn = card.querySelector('.focus-on-map-btn');
            const directionsBtn = card.querySelector('.directions-btn');
            focusBtn && focusBtn.addEventListener('click', () => focusFacilityById(state, providerId));
            directionsBtn && directionsBtn.addEventListener('click', () => {
                const match = state.markers.find((entry) => String(entry.facility.id) === String(providerId));
                if (!match) return;
                selectFacility(state, match.facility, match.marker);
                requestDirections(state, match.facility);
            });
        });
    }

    function createLeafletIcon(type) {
        const icons = {
            hospital: 'fa-hospital',
            global_hospital: 'fa-star-of-life',
            doctor: 'fa-user-doctor',
            pharmacy: 'fa-capsules',
            emergency: 'fa-truck-medical'
        };

        return window.L.divIcon({
            className: '',
            html: `<div class="leaflet-health-marker ${escapeHtml(type)}"><i class="fa-solid ${icons[type] || 'fa-location-dot'}"></i></div>`,
            iconSize: [38, 38],
            iconAnchor: [19, 19],
            popupAnchor: [0, -18]
        });
    }

    function selectLeafletFacility(state, facility, marker = null) {
        state.selectedFacility = facility;
        const distance = state.currentOrigin ? calculateDistanceKm(state.currentOrigin, facility.position) : null;
        const html = buildFacilityInfoHtml(facility, distance);
        [...state.markers, ...state.nearbyMarkers].forEach((entry) => {
            entry.marker.getElement()?.querySelector('.leaflet-health-marker')?.classList.remove('marker-active');
        });

        if (marker) {
            marker.getElement()?.querySelector('.leaflet-health-marker')?.classList.add('marker-active');
            marker.bindPopup(html).openPopup();
        } else {
            window.L.popup()
                .setLatLng([facility.position.lat, facility.position.lng])
                .setContent(html)
                .openOn(state.map);
        }

        updateExplorerInfoCard(state, facility, distance);
        syncGoogleMapLinks(state, facilityGoogleQuery(facility));
        setDirectionsButtonsState(state, true);
    }

    function focusLeafletFacilityById(state, providerId) {
        const match = state.markers.find((entry) => String(entry.facility.id) === String(providerId));
        if (!match) return;
        state.map.setView([match.facility.position.lat, match.facility.position.lng], 15);
        selectLeafletFacility(state, match.facility, match.marker);
    }

    function clearLeafletDirections(state) {
        if (state.routeLine) {
            state.map.removeLayer(state.routeLine);
            state.routeLine = null;
        }
        state.hasActiveRoute = false;
        updateRouteSummary(state, null);
        setDirectionsButtonsState(state, !!state.selectedFacility);
        const directionState = doc.getElementById('mapDirectionsState');
        if (directionState && state.mode === 'explorer') directionState.textContent = 'Ready';
    }

    function requestLeafletDirections(state, destinationFacility = null) {
        if (isMapPremiumLocked(state)) {
            openSubscriptionGate();
            return;
        }
        const facility = destinationFacility || state.selectedFacility;
        if (!facility || !state.currentOrigin) {
            Toastify({ text: 'Use your location or search an area before getting directions.', gravity: 'top', position: 'right', backgroundColor: '#dc3545' }).showToast();
            return;
        }

        if (state.routeLine) {
            state.map.removeLayer(state.routeLine);
        }

        const origin = [state.currentOrigin.lat, state.currentOrigin.lng];
        const destination = [facility.position.lat, facility.position.lng];
        state.routeLine = window.L.polyline([origin, destination], {
            color: '#0ea5e9',
            weight: 5,
            opacity: 0.85,
            dashArray: '8 10'
        }).addTo(state.map);

        state.map.fitBounds(state.routeLine.getBounds(), { padding: [40, 40] });
        state.hasActiveRoute = true;

        const distance = calculateDistanceKm(state.currentOrigin, facility.position) || 0;
        const durationMinutes = Math.max(5, Math.round((distance / 35) * 60));
        updateRouteSummary(state, {
            start_address: 'Current/search location',
            end_address: facility.name,
            distance: { text: `${distance.toFixed(1)} km` },
            duration: { text: `${durationMinutes} min est.` }
        });
        setDirectionsButtonsState(state, true);

        const directionState = doc.getElementById('mapDirectionsState');
        if (directionState && state.mode === 'explorer') directionState.textContent = `${durationMinutes} min`;
    }

    function renderLeafletNearby(state, center) {
        renderDynamicNearby(state, center);
    }

    function updateLeafletOrigin(state, position, zoom = 13) {
        state.currentOrigin = position;
        state.map.setView([position.lat, position.lng], zoom);

        if (!state.userMarker) {
            state.userMarker = window.L.circleMarker([position.lat, position.lng], {
                radius: 8,
                color: '#ffffff',
                weight: 2,
                fillColor: '#111827',
                fillOpacity: 1
            }).addTo(state.map).bindPopup('Your location');
        } else {
            state.userMarker.setLatLng([position.lat, position.lng]);
        }

        updateDirectoryDistances(state);
        if (state.selectedFacility) {
            const distance = calculateDistanceKm(position, state.selectedFacility.position);
            updateExplorerInfoCard(state, state.selectedFacility, distance);
        }
        discoverNearbyOptions(state, position, false);
    }

    function useLeafletBrowserLocation(state) {
        if (isMapPremiumLocked(state)) {
            openSubscriptionGate();
            return;
        }
        if (!navigator.geolocation) {
            Toastify({ text: 'Geolocation is not supported by your browser.', gravity: 'top', position: 'right', backgroundColor: '#dc3545' }).showToast();
            return;
        }

        navigator.geolocation.getCurrentPosition((result) => {
            updateLeafletOrigin(state, { lat: result.coords.latitude, lng: result.coords.longitude }, 14);
        }, () => {
            Toastify({ text: 'Location access was denied or unavailable.', gravity: 'top', position: 'right', backgroundColor: '#dc3545' }).showToast();
        }, { enableHighAccuracy: true, timeout: 10000 });
    }

    function searchLeafletLocation(state, query) {
        const variants = mapSearchVariants(query);
        if (!variants.length) return;
        const loweredVariants = variants.map((variant) => variant.toLowerCase());
        const match = state.markers.find((entry) => {
            const facility = entry.facility;
            return [
                facility.name,
                facility.displayAddress,
                facility.city,
                facility.country,
                facility.specialization,
                facility.hospital_name
            ].filter(Boolean).some((value) => {
                const loweredValue = String(value).toLowerCase();
                return loweredVariants.some((lowered) => loweredValue.includes(lowered));
            });
        });

        if (match) {
            updateLeafletOrigin(state, match.facility.position, 14);
            selectLeafletFacility(state, match.facility, match.marker);
            highlightMapArea(state, match.facility);
            return;
        }

        const tryVariant = (index = 0) => fetch(`https://nominatim.openstreetmap.org/search?format=json&limit=1&q=${encodeURIComponent(variants[index])}`)
            .then((response) => response.ok ? response.json() : [])
            .then((results) => {
                if (!Array.isArray(results) || !results.length) {
                    if (index + 1 < variants.length) {
                        tryVariant(index + 1);
                        return;
                    }
                    discoverNearbyOptions(state, getStateCenter(state));
                    return;
                }
                updateLeafletOrigin(state, { lat: Number(results[0].lat), lng: Number(results[0].lon) }, 13);
                highlightMapArea(state);
            })
            .catch(() => {
                if (index + 1 < variants.length) {
                    tryVariant(index + 1);
                    return;
                }
                Toastify({ text: 'Search is unavailable right now.', gravity: 'top', position: 'right', backgroundColor: '#dc3545' }).showToast();
            });
        tryVariant();
    }

    function refreshLeafletMarkerVisibility(state) {
        state.markers.forEach((entry) => {
            const visible = state.activeFilters.has(entry.facility.type);
            if (visible && !state.map.hasLayer(entry.marker)) {
                entry.marker.addTo(state.map);
            }
            if (!visible && state.map.hasLayer(entry.marker)) {
                entry.marker.remove();
            }
        });
        state.nearbyMarkers.forEach((entry) => {
            const visible = state.activeFilters.has(entry.facility.type);
            if (visible && !state.map.hasLayer(entry.marker)) {
                entry.marker.addTo(state.map);
            }
            if (!visible && state.map.hasLayer(entry.marker)) {
                entry.marker.remove();
            }
        });
    }

    function facilityMarkerKey(facility) {
        return `${facility.type}:${facility.id || facility.name || ''}`;
    }

    function addFacilityMarker(state, facility) {
        if (state.provider === 'leaflet') {
            const marker = window.L.marker([facility.position.lat, facility.position.lng], {
                icon: createLeafletIcon(facility.type),
                title: facility.name
            });
            if (state.activeFilters.has(facility.type)) {
                marker.addTo(state.map);
            }
            marker.on('click', () => selectLeafletFacility(state, facility, marker));
            state.markers.push({ facility, marker });
            return;
        }

        const marker = new window.google.maps.Marker({
            map: state.activeFilters.has(facility.type) ? state.map : null,
            position: facility.position,
            ...getMarkerOptions(window.google.maps, facility)
        });
        marker.addListener('click', () => selectFacility(state, facility, marker));
        state.markers.push({ facility, marker });
    }

    function facilityGeocodeQuery(facility) {
        return [
            facility.name,
            facility.displayAddress || facility.address,
            facility.city,
            facility.country,
            facility.hospital_name,
            facility.hospital_city,
            facility.hospital_country
        ].filter(Boolean).join(', ');
    }

    function moveMarkerToPosition(entry, position, provider) {
        entry.facility.position = position;
        entry.facility.coordinates_source = provider === 'google' ? 'google_geocoded' : 'osm_geocoded';
        if (provider === 'leaflet') {
            entry.marker.setLatLng([position.lat, position.lng]);
            return;
        }
        entry.marker.setPosition(position);
    }

    function geocodeEstimatedFacilities(state) {
        const estimatedEntries = state.markers
            .filter((entry) => entry.facility.coordinates_source === 'estimated')
            .slice(0, 12);
        if (!estimatedEntries.length) return;

        if (state.provider === 'google' && state.geocoder) {
            estimatedEntries.forEach((entry, index) => {
                const query = facilityGeocodeQuery(entry.facility);
                if (!query) return;
                window.setTimeout(() => {
                    state.geocoder.geocode({ address: query }, (results, status) => {
                        if (status !== 'OK' || !results?.length) return;
                        const location = results[0].geometry.location;
                        moveMarkerToPosition(entry, { lat: location.lat(), lng: location.lng() }, 'google');
                    });
                }, index * 160);
            });
            return;
        }

        estimatedEntries.forEach((entry, index) => {
            const query = facilityGeocodeQuery(entry.facility);
            if (!query) return;
            window.setTimeout(() => {
                fetch(`https://nominatim.openstreetmap.org/search?format=json&limit=1&q=${encodeURIComponent(query)}`)
                    .then((response) => response.ok ? response.json() : [])
                    .then((results) => {
                        if (!Array.isArray(results) || !results.length) return;
                        const lat = Number(results[0].lat);
                        const lng = Number(results[0].lon);
                        if (Number.isNaN(lat) || Number.isNaN(lng)) return;
                        moveMarkerToPosition(entry, { lat, lng }, 'leaflet');
                    })
                    .catch(() => { });
            }, index * 220);
        });
    }

    function generatedNearbyFacilities(center) {
        const templates = [
            { id: 'nearby-hospital-a', type: 'hospital', name: 'Nearby General Hospital', latOffset: 0.018, lngOffset: 0.014, facilities: 'Emergency, diagnostics, inpatient care' },
            { id: 'nearby-hospital-b', type: 'hospital', name: 'Community Care Hospital', latOffset: -0.017, lngOffset: 0.018, facilities: 'Outpatient care, pharmacy, lab services' },
            { id: 'nearby-doctor-a', type: 'doctor', name: 'Nearby Doctor Clinic', latOffset: 0.012, lngOffset: -0.018, specialization: 'General Medicine', hospital_name: 'Independent clinic', consultation_fee: 0 },
            { id: 'nearby-doctor-b', type: 'doctor', name: 'Specialist Consultation Point', latOffset: -0.014, lngOffset: -0.014, specialization: 'Specialist Care', hospital_name: 'Care network clinic', consultation_fee: 0 },
            { id: 'nearby-pharmacy-a', type: 'pharmacy', name: 'Nearby Pharmacy', latOffset: 0.006, lngOffset: 0.023, rating: 'Open access' },
            { id: 'nearby-pharmacy-b', type: 'pharmacy', name: 'After-hours Pharmacy', latOffset: -0.006, lngOffset: -0.024, rating: 'After-hours support' },
            { id: 'nearby-emergency-a', type: 'emergency', name: 'Emergency Response Point', latOffset: 0.024, lngOffset: -0.004, rating: 'Urgent care' },
            { id: 'nearby-emergency-b', type: 'emergency', name: 'Ambulance Access Point', latOffset: -0.023, lngOffset: 0.002, rating: 'Rapid response' }
        ];

        return templates.map((item) => {
            const position = {
                lat: center.lat + item.latOffset,
                lng: center.lng + item.lngOffset
            };
            return withFacilityImage({
                ...item,
                id: `dynamic-${item.id}`,
                position,
                coordinates_source: 'fallback_nearby',
                address: 'Near selected map area',
                displayAddress: 'Near selected map area',
                city: '',
                country: '',
                distanceKm: calculateDistanceKm(center, position)
            });
        });
    }

    function clearNearbyMarkers(state) {
        state.nearbyMarkers.forEach((entry) => {
            if (state.provider === 'leaflet') {
                if (state.map.hasLayer(entry.marker)) entry.marker.remove();
            } else {
                entry.marker.setMap(null);
            }
        });
        state.nearbyMarkers = [];
    }

    function addNearbyMarker(state, facility) {
        if (state.provider === 'leaflet') {
            const marker = window.L.marker([facility.position.lat, facility.position.lng], {
                icon: createLeafletIcon(facility.type),
                title: facility.name
            });
            if (state.activeFilters.has(facility.type)) {
                marker.addTo(state.map);
            }
            marker.on('click', () => selectLeafletFacility(state, facility, marker));
            state.nearbyMarkers.push({ facility, marker });
            return;
        }

        const marker = new window.google.maps.Marker({
            map: state.activeFilters.has(facility.type) ? state.map : null,
            position: facility.position,
            ...getMarkerOptions(window.google.maps, facility)
        });
        marker.addListener('click', () => selectFacility(state, facility, marker));
        state.nearbyMarkers.push({ facility, marker });
    }

    function placePhotoUrl(place, type) {
        if (place?.photos?.length && typeof place.photos[0].getUrl === 'function') {
            return place.photos[0].getUrl({ maxWidth: 720, maxHeight: 420 });
        }
        return mapTypeImage(type);
    }

    function beginNearbyRequest(state, center) {
        state.nearbyRequestId = (state.nearbyRequestId || 0) + 1;
        const requestId = state.nearbyRequestId;
        state.lastNearbyCenter = center;
        setMapDiscoveryLoading(state, true);
        if (state.nearbyLoadingTimer) {
            window.clearTimeout(state.nearbyLoadingTimer);
        }
        state.nearbyLoadingTimer = window.setTimeout(() => {
            if (!isCurrentNearbyRequest(state, requestId)) return;
            if (!state.nearbyMarkers.length) {
                renderNearbyCollection(state, [], center, requestId);
                return;
            }
            completeNearbyRequest(state, requestId);
        }, 9000);
        return requestId;
    }

    function isCurrentNearbyRequest(state, requestId) {
        return !requestId || requestId === state.nearbyRequestId;
    }

    function completeNearbyRequest(state, requestId) {
        if (isCurrentNearbyRequest(state, requestId)) {
            if (state.nearbyLoadingTimer) {
                window.clearTimeout(state.nearbyLoadingTimer);
                state.nearbyLoadingTimer = null;
            }
            setMapDiscoveryLoading(state, false);
        }
    }

    function fitNearbyCollection(state, center) {
        const entries = state.nearbyMarkers.filter((entry) => state.activeFilters.has(entry.facility.type));
        if (!entries.length || !center) return;

        if (state.provider === 'leaflet') {
            const bounds = entries.map((entry) => [entry.facility.position.lat, entry.facility.position.lng]);
            bounds.push([center.lat, center.lng]);
            state.map.fitBounds(bounds, { padding: [52, 52], maxZoom: 14 });
            return;
        }

        const bounds = new window.google.maps.LatLngBounds();
        entries.forEach((entry) => bounds.extend(entry.facility.position));
        bounds.extend(center);
        state.map.fitBounds(bounds, { top: 64, right: 64, bottom: 64, left: 64 });
        window.google.maps.event.addListenerOnce(state.map, 'idle', () => {
            if (state.map.getZoom() > 14) state.map.setZoom(14);
        });
    }

    function renderNearbyCollection(state, facilities, center, requestId = null) {
        if (!center || state.mode !== 'explorer') return;
        if (!isCurrentNearbyRequest(state, requestId)) return;

        const nearby = facilities
            .map((facility) => withFacilityImage({
                ...facility,
                distanceKm: facility.distanceKm ?? calculateDistanceKm(center, facility.position)
            }))
            .filter((facility) => facility.position && facility.distanceKm !== null && state.activeFilters.has(facility.type));

        const markerFacilities = nearby.length
            ? nearby
            : generatedNearbyFacilities(center).filter((facility) => state.activeFilters.has(facility.type));

        clearNearbyMarkers(state);
        markerFacilities.forEach((facility) => addNearbyMarker(state, facility));
        fitNearbyCollection(state, center);

        const registered = state.facilities
            .map((facility) => ({
                ...facility,
                distanceKm: calculateDistanceKm(center, facility.position)
            }))
            .filter((facility) => facility.distanceKm !== null && state.activeFilters.has(facility.type));

        state.nearbyFacilities = [...markerFacilities, ...registered]
            .sort((a, b) => (a.distanceKm ?? 999999) - (b.distanceKm ?? 999999))
            .slice(0, 14);
        renderNearbyList(state);
        focusRequestedMapTarget(state);
        completeNearbyRequest(state, requestId);
    }

    function googlePlaceToFacility(place, type, center) {
        if (!place?.geometry?.location) return null;
        const position = { lat: place.geometry.location.lat(), lng: place.geometry.location.lng() };
        return withFacilityImage({
            id: place.place_id,
            type,
            name: place.name || facilityTypeLabel(type),
            rating: place.rating || null,
            address: place.vicinity || '',
            displayAddress: place.vicinity || '',
            position,
            coordinates_source: 'google_places',
            image_url: placePhotoUrl(place, type),
            distanceKm: calculateDistanceKm(center, position)
        });
    }

    function loadGoogleNearbyFacilities(state, center, requestId = null) {
        if (!state.placesService || !window.google?.maps?.places) {
            renderNearbyCollection(state, [], center, requestId);
            return;
        }

        const serviceStatus = window.google.maps.places.PlacesServiceStatus;
        const requests = [
            { type: 'hospital', request: { location: center, radius: 16000, type: 'hospital' } },
            { type: 'doctor', request: { location: center, radius: 16000, keyword: 'doctor clinic' } },
            { type: 'pharmacy', request: { location: center, radius: 12000, type: 'pharmacy' } },
            { type: 'emergency', request: { location: center, radius: 18000, keyword: 'emergency hospital' } }
        ];

        Promise.all(requests.map(({ type, request }) => new Promise((resolve) => {
            state.placesService.nearbySearch(request, (results, status) => {
                if (status !== serviceStatus.OK || !Array.isArray(results)) {
                    resolve([]);
                    return;
                }
                resolve(results.slice(0, 8).map((place) => googlePlaceToFacility(place, type, center)).filter(Boolean));
            });
        }))).then((groups) => {
            renderNearbyCollection(state, groups.flat(), center, requestId);
        }).catch(() => renderNearbyCollection(state, [], center, requestId));
    }

    function osmTypeFromTags(tags = {}) {
        if (tags.amenity === 'pharmacy') return 'pharmacy';
        if (tags.amenity === 'doctors' || tags.amenity === 'clinic') return 'doctor';
        if (tags.emergency || tags.healthcare === 'emergency') return 'emergency';
        return 'hospital';
    }

    function loadOpenStreetMapNearbyFacilities(state, center, requestId = null) {
        const radius = 16000;
        const query = `
            [out:json][timeout:14];
            (
              nwr["amenity"~"hospital|clinic|doctors|pharmacy"](around:${radius},${center.lat},${center.lng});
              nwr["emergency"](around:${radius},${center.lat},${center.lng});
              nwr["healthcare"~"hospital|clinic|doctor|pharmacy"](around:${radius},${center.lat},${center.lng});
            );
            out center tags 60;
        `;
        if (state.nearbyAbortController) {
            state.nearbyAbortController.abort();
        }
        const controller = window.AbortController ? new AbortController() : null;
        state.nearbyAbortController = controller;

        fetch('https://overpass-api.de/api/interpreter', {
            method: 'POST',
            body: `data=${encodeURIComponent(query)}`,
            headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
            signal: controller?.signal
        })
            .then((response) => response.ok ? response.json() : null)
            .then((payload) => {
                const elements = Array.isArray(payload?.elements) ? payload.elements : [];
                const seen = new Set();
                const facilities = elements.map((element) => {
                    const lat = Number(element.lat ?? element.center?.lat);
                    const lng = Number(element.lon ?? element.center?.lon);
                    if (Number.isNaN(lat) || Number.isNaN(lng)) return null;
                    const tags = element.tags || {};
                    const type = osmTypeFromTags(tags);
                    const name = tags.name || facilityTypeLabel(type);
                    const key = `${type}:${name}:${lat.toFixed(5)}:${lng.toFixed(5)}`;
                    if (seen.has(key)) return null;
                    seen.add(key);
                    const position = { lat, lng };
                    return withFacilityImage({
                        id: `osm-${element.type}-${element.id}`,
                        type,
                        name,
                        address: [tags['addr:housenumber'], tags['addr:street'], tags['addr:city']].filter(Boolean).join(' '),
                        displayAddress: [tags['addr:housenumber'], tags['addr:street'], tags['addr:city']].filter(Boolean).join(' ') || 'OpenStreetMap healthcare location',
                        position,
                        coordinates_source: 'openstreetmap',
                        distanceKm: calculateDistanceKm(center, position)
                    });
                }).filter(Boolean);
                renderNearbyCollection(state, facilities, center, requestId);
            })
            .catch((error) => {
                if (error?.name === 'AbortError') return;
                renderNearbyCollection(state, [], center, requestId);
            });
    }

    function renderDynamicNearby(state, center) {
        if (!center || state.mode !== 'explorer') return;
        const requestId = beginNearbyRequest(state, center);
        if (state.provider === 'google') {
            loadGoogleNearbyFacilities(state, center, requestId);
            return;
        }
        loadOpenStreetMapNearbyFacilities(state, center, requestId);
    }

    function discoverNearbyOptions(state, center = null, pulse = true) {
        if (isMapPremiumLocked(state)) {
            const list = doc.getElementById('nearbyResultsList');
            if (list && state.mode === 'explorer') {
                list.innerHTML = '<button type="button" class="nearby-result-item text-start" data-premium-locked="1"><strong>Premium nearby discovery</strong><small>Subscribe to scan live nearby hospitals, pharmacies, emergency points, and routes.</small></button>';
                list.querySelector('[data-premium-locked="1"]')?.addEventListener('click', (event) => guardPremiumAction(event, event.currentTarget));
            }
            return;
        }
        const target = center || state.currentOrigin || getStateCenter(state);
        if (pulse) runMapPulse(state.container);
        renderDynamicNearby(state, target);
        syncGoogleMapLinks(state, `${target.lat},${target.lng}`);
    }

    function focusRequestedMapTarget(state) {
        const focusType = state.container.dataset.focusType || '';
        if (!focusType || state.focusTargetHandled) return;

        const entries = [...state.nearbyMarkers, ...state.markers];
        const match = entries.find((entry) => entry.facility.type === focusType);
        if (!match) return;

        state.focusTargetHandled = true;
        if (state.provider === 'leaflet') {
            state.map.setView([match.facility.position.lat, match.facility.position.lng], 15);
            selectLeafletFacility(state, match.facility, match.marker);
        } else {
            state.map.panTo(match.facility.position);
            state.map.setZoom(15);
            selectFacility(state, match.facility, match.marker);
        }
        highlightMapArea(state, match.facility);
    }

    function autoDiscoverNearby(state) {
        if (state.mode !== 'explorer') return;
        if (isMapPremiumLocked(state)) {
            discoverNearbyOptions(state, getStateCenter(state), false);
            return;
        }
        const discoveryToken = (state.autoDiscoveryToken || 0) + 1;
        state.autoDiscoveryToken = discoveryToken;
        runMapPulse(state.container);
        const list = doc.getElementById('nearbyResultsList');
        if (list) {
            list.innerHTML = '<div class="text-muted small">Finding nearby healthcare options...</div>';
        }
        window.setTimeout(() => {
            if (state.autoDiscoveryToken === discoveryToken && !state.currentOrigin) {
                discoverNearbyOptions(state, getStateCenter(state), false);
            }
        }, 650);

        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition((result) => {
                if (state.autoDiscoveryToken !== discoveryToken) return;
                state.autoDiscoveryToken += 1;
                const position = { lat: result.coords.latitude, lng: result.coords.longitude };
                if (state.provider === 'leaflet') {
                    updateLeafletOrigin(state, position, 13);
                } else {
                    updateOrigin(state, position, 13);
                }
            }, () => {
                if (state.autoDiscoveryToken === discoveryToken) {
                    discoverNearbyOptions(state, getStateCenter(state), false);
                }
            }, { enableHighAccuracy: true, timeout: 4500, maximumAge: 60000 });
            return;
        }

        discoverNearbyOptions(state, getStateCenter(state), false);
    }

    function updateExplorerCounts(state) {
        if (state.mode !== 'explorer') return;
        const hospitalCount = doc.getElementById('mapHospitalCount');
        const doctorCount = doc.getElementById('mapDoctorCount');
        if (hospitalCount) {
            hospitalCount.textContent = String(state.facilities.filter((facility) => facility.type === 'hospital' || facility.type === 'global_hospital').length);
        }
        if (doctorCount) {
            doctorCount.textContent = String(state.facilities.filter((facility) => facility.type === 'doctor').length);
        }
    }

    function syncFacilitiesFromApi(state) {
        const endpoint = state.container.dataset.apiEndpoint;
        if (!endpoint) return;

        fetch(endpoint, { headers: { Accept: 'application/json' } })
            .then((response) => response.ok ? response.json() : null)
            .then((payload) => {
                if (!payload) return;
                const incoming = normalizeFacilities({
                    hospitals: Array.isArray(payload.hospitals) ? payload.hospitals : [],
                    doctors: Array.isArray(payload.doctors) ? payload.doctors : []
                });
                if (!incoming.length) return;

                const existing = new Set(state.facilities.map(facilityMarkerKey));
                let added = 0;
                incoming.forEach((facility) => {
                    const key = facilityMarkerKey(facility);
                    if (existing.has(key)) return;
                    existing.add(key);
                    state.facilities.push(facility);
                    addFacilityMarker(state, facility);
                    added += 1;
                });

                if (!added) return;
                geocodeEstimatedFacilities(state);
                updateExplorerCounts(state);
                const center = state.currentOrigin || getStateCenter(state);
                renderDynamicNearby(state, center);
            })
            .catch(() => { });
    }

    function startFacilityLiveSync(state) {
        if (state.liveSyncStarted || !state.container.dataset.apiEndpoint || isMapPremiumLocked(state)) return;
        state.liveSyncStarted = true;
        window.setInterval(() => syncFacilitiesFromApi(state), 45000);
    }

    function fitLeafletEntries(state, entries) {
        if (!entries.length) {
            state.map.setView([defaultCenter.lat, defaultCenter.lng], 11);
            return;
        }

        if (entries.length === 1) {
            const facility = entries[0].facility;
            state.map.setView([facility.position.lat, facility.position.lng], 14);
            return;
        }

        const bounds = entries.map((entry) => [entry.facility.position.lat, entry.facility.position.lng]);
        state.map.fitBounds(bounds, { padding: [44, 44], maxZoom: 13 });
    }

    function fitLeafletFacilities(state) {
        const visibleEntries = state.markers.filter((entry) => state.activeFilters.has(entry.facility.type));
        const localEntries = visibleEntries.filter((entry) => entry.facility.type !== 'global_hospital');
        const nearbyEntries = state.nearbyMarkers.filter((entry) => state.activeFilters.has(entry.facility.type));
        fitLeafletEntries(state, localEntries.length ? localEntries : (nearbyEntries.length ? nearbyEntries : visibleEntries));
    }

    function bindLeafletExplorerControls(state) {
        if (state.mode !== 'explorer') return;
        const searchInput = doc.getElementById('mapSearchInput');
        const searchBtn = doc.getElementById('mapSearchBtn');
        const useLocationBtn = doc.getElementById('mapUseLocationBtn');
        const resetBtn = doc.getElementById('mapResetViewBtn');
        const getDirectionsBtn = doc.getElementById('getDirectionsBtn');
        const clearDirectionsBtn = doc.getElementById('clearDirectionsBtn');
        const filterButtons = doc.querySelectorAll('#facilityFilterGroup [data-filter-type]');
        const hospitalCount = doc.getElementById('mapHospitalCount');
        const doctorCount = doc.getElementById('mapDoctorCount');

        bindMapStyleControl(state);
        bindGoogleAreaLinks(state, searchInput, searchLeafletLocation);
        hospitalCount && (hospitalCount.textContent = String(state.facilities.filter((facility) => facility.type === 'hospital' || facility.type === 'global_hospital').length));
        doctorCount && (doctorCount.textContent = String(state.facilities.filter((facility) => facility.type === 'doctor').length));
        renderNearbyList(state);

        searchBtn && searchBtn.addEventListener('click', () => {
            const query = searchInput?.value.trim();
            state.selectedFacility = null;
            syncGoogleMapLinks(state, query);
            searchLeafletLocation(state, query);
        });
        searchInput && searchInput.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                state.selectedFacility = null;
                syncGoogleMapLinks(state, searchInput.value.trim());
                searchLeafletLocation(state, searchInput.value.trim());
            }
        });
        if (searchInput?.value.trim()) {
            setTimeout(() => searchLeafletLocation(state, searchInput.value.trim()), 450);
        }
        useLocationBtn && useLocationBtn.addEventListener('click', (event) => {
            if (guardPremiumAction(event, useLocationBtn)) return;
            useLeafletBrowserLocation(state);
        });
        resetBtn && resetBtn.addEventListener('click', () => {
            discoverNearbyOptions(state);
            clearLeafletDirections(state);
        });
        getDirectionsBtn && getDirectionsBtn.addEventListener('click', (event) => {
            if (guardPremiumAction(event, getDirectionsBtn)) return;
            requestLeafletDirections(state);
        });
        clearDirectionsBtn && clearDirectionsBtn.addEventListener('click', () => clearLeafletDirections(state));

        filterButtons.forEach((button) => {
            button.addEventListener('click', () => {
                const type = button.dataset.filterType;
                const types = type === 'hospital' ? ['hospital', 'global_hospital'] : [type];
                const isActive = types.every((item) => state.activeFilters.has(item));
                if (isActive) {
                    types.forEach((item) => state.activeFilters.delete(item));
                    button.classList.remove('active');
                } else {
                    types.forEach((item) => state.activeFilters.add(item));
                    button.classList.add('active');
                }
                refreshLeafletMarkerVisibility(state);
                fitLeafletFacilities(state);
                const center = state.currentOrigin || state.map.getCenter();
                renderLeafletNearby(state, { lat: center.lat, lng: center.lng });
            });
        });
    }

    function bindLeafletDirectoryControls(state) {
        const useLocationBtn = doc.getElementById('directoryUseLocationBtn');
        const clearDirectionsBtn = doc.getElementById('directoryClearDirectionsBtn');
        useLocationBtn && useLocationBtn.addEventListener('click', () => useLeafletBrowserLocation(state));
        clearDirectionsBtn && clearDirectionsBtn.addEventListener('click', () => clearLeafletDirections(state));

        doc.querySelectorAll('[data-map-focus-id]').forEach((button) => {
            button.addEventListener('click', () => focusLeafletFacilityById(state, button.dataset.mapFocusId));
        });

        doc.querySelectorAll('[data-map-directions-id]').forEach((button) => {
            button.addEventListener('click', () => {
                const match = state.markers.find((entry) => String(entry.facility.id) === String(button.dataset.mapDirectionsId));
                if (!match) return;
                selectLeafletFacility(state, match.facility, match.marker);
                requestLeafletDirections(state, match.facility);
            });
        });

        doc.querySelectorAll('.directory-card').forEach((card) => {
            const providerId = card.dataset.providerId;
            const focusBtn = card.querySelector('.focus-on-map-btn');
            const directionsBtn = card.querySelector('.directions-btn');
            focusBtn && focusBtn.addEventListener('click', () => focusLeafletFacilityById(state, providerId));
            directionsBtn && directionsBtn.addEventListener('click', () => {
                const match = state.markers.find((entry) => String(entry.facility.id) === String(providerId));
                if (!match) return;
                selectLeafletFacility(state, match.facility, match.marker);
                requestLeafletDirections(state, match.facility);
            });
        });
    }

    function createLeafletHealthcareMap(container) {
        const payload = JSON.parse(container.dataset.facilities || '{}');
        const hasWorldSpotlight = Array.isArray(payload.world_hospitals) && payload.world_hospitals.length > 0 && (container.dataset.mapMode || 'directory') === 'directory';
        const facilities = normalizeFacilities(payload);
        const map = window.L.map(container, {
            zoomControl: true,
            scrollWheelZoom: true
        }).setView([defaultCenter.lat, defaultCenter.lng], facilities.length ? 7 : 6);

        const roadLayer = window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors'
        });
        const satelliteLayer = window.L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
            maxZoom: 19,
            attribution: 'Tiles &copy; Esri, Maxar, Earthstar Geographics, and the GIS User Community'
        });
        roadLayer.addTo(map);

        const state = {
            provider: 'leaflet',
            container,
            map,
            baseLayers: {
                road: roadLayer,
                satellite: satelliteLayer
            },
            mode: container.dataset.mapMode || 'directory',
            facilities,
            markers: [],
            nearbyMarkers: [],
            nearbyFacilities: [],
            activeFilters: new Set(['hospital', 'global_hospital', 'doctor', 'pharmacy', 'emergency']),
            selectedFacility: null,
            currentOrigin: null,
            userMarker: null,
            nearbyRequestId: 0,
            nearbyAbortController: null,
            nearbyLoadingTimer: null,
            hasActiveRoute: false,
            routeLine: null,
            routeSummaryEl: null
        };
        applyRequestedFocusFilter(state);

        if (facilities.length) {
            facilities.forEach((facility) => {
                addFacilityMarker(state, facility);
            });
            geocodeEstimatedFacilities(state);

            if (hasWorldSpotlight) {
                map.setView([32, 5], window.innerWidth < 768 ? 1 : 2);
            } else {
                fitLeafletFacilities(state);
            }
        }

        bindLeafletExplorerControls(state);
        syncFilterButtonsFromState(state);
        bindLeafletDirectoryControls(state);
        autoOpenMapCanvas(state);
        if (state.mode === 'explorer') {
            if (container.dataset.autoNearby === '1') {
                autoDiscoverNearby(state);
            } else {
                const center = map.getCenter();
                renderLeafletNearby(state, { lat: center.lat, lng: center.lng });
            }
        }
        startFacilityLiveSync(state);
        updateDirectoryDistances(state);
        setDirectionsButtonsState(state, false);

        if (!window.MEDISPHERE.maps) window.MEDISPHERE.maps = {};
        window.MEDISPHERE.maps[state.mode] = state;
        container.classList.remove('loading', 'no-key', 'error');
        [150, 600, 1400].forEach((delay) => setTimeout(() => map.invalidateSize(), delay));
        window.addEventListener('load', () => map.invalidateSize(), { once: true });
        if (window.ResizeObserver) {
            const observer = new ResizeObserver(() => map.invalidateSize());
            observer.observe(container);
        }
    }

    function createHealthcareMap(container) {
        const googleMaps = window.google.maps;
        const payload = JSON.parse(container.dataset.facilities || '{}');
        const hasWorldSpotlight = Array.isArray(payload.world_hospitals) && payload.world_hospitals.length > 0 && (container.dataset.mapMode || 'directory') === 'directory';
        const facilities = normalizeFacilities(payload);
        const map = new googleMaps.Map(container, {
            center: defaultCenter,
            zoom: facilities.length ? 7 : 6,
            mapTypeId: googleMaps.MapTypeId.ROADMAP,
            mapTypeControl: false,
            streetViewControl: false,
            fullscreenControl: true,
            styles: [
                { featureType: 'poi.medical', stylers: [{ visibility: 'on' }] },
                { featureType: 'poi.business', stylers: [{ saturation: -10 }] },
                { featureType: 'road', elementType: 'geometry', stylers: [{ lightness: 10 }] }
            ]
        });

        const state = {
            provider: 'google',
            container,
            map,
            mode: container.dataset.mapMode || 'directory',
            facilities,
            markers: [],
            nearbyMarkers: [],
            nearbyFacilities: [],
            activeFilters: new Set(['hospital', 'global_hospital', 'doctor', 'pharmacy', 'emergency']),
            infoWindow: new googleMaps.InfoWindow(),
            geocoder: new googleMaps.Geocoder(),
            directionsService: new googleMaps.DirectionsService(),
            directionsRenderer: new googleMaps.DirectionsRenderer({ suppressMarkers: false, preserveViewport: true }),
            placesService: googleMaps.places ? new googleMaps.places.PlacesService(map) : null,
            selectedFacility: null,
            currentOrigin: null,
            userMarker: null,
            nearbyRequestId: 0,
            nearbyAbortController: null,
            nearbyLoadingTimer: null,
            hasActiveRoute: false,
            routeSummaryEl: null
        };
        applyRequestedFocusFilter(state);

        state.directionsRenderer.setMap(map);

        if (facilities.length) {
            const bounds = new googleMaps.LatLngBounds();
            facilities.forEach((facility) => {
                addFacilityMarker(state, facility);
                bounds.extend(facility.position);
            });
            geocodeEstimatedFacilities(state);
            if (facilities.length > 1 && hasWorldSpotlight) {
                map.setCenter({ lat: 32, lng: 5 });
                map.setZoom(window.innerWidth < 768 ? 1 : 2);
            } else if (facilities.length > 1) {
                map.fitBounds(bounds);
            } else {
                map.setCenter(facilities[0].position);
                map.setZoom(14);
            }
        }

        bindExplorerControls(state);
        syncFilterButtonsFromState(state);
        bindDirectoryControls(state);
        autoOpenMapCanvas(state);
        if (state.mode === 'explorer') {
            if (container.dataset.autoNearby === '1') {
                autoDiscoverNearby(state);
            } else {
                discoverNearbyOptions(state, getStateCenter(state), false);
            }
        }
        startFacilityLiveSync(state);
        updateDirectoryDistances(state);
        setDirectionsButtonsState(state, false);

        if (!window.MEDISPHERE.maps) window.MEDISPHERE.maps = {};
        window.MEDISPHERE.maps[state.mode] = state;
        container.classList.remove('loading', 'no-key', 'error');
    }


    function initConsultationRoom() {
        const app = doc.getElementById('consultationApp');
        if (!app) return;

        const sessionId = Number(app.dataset.sessionId || 0);
        const initiatorId = Number(app.dataset.initiatorId || 0);
        const selfId = Number(window.MEDISPHERE.currentUserId || 0);
        const callType = app.dataset.callType || 'video';
        const premiumLocked = app.dataset.premiumLocked === '1';
        if (!premiumLocked && !window.RTCPeerConnection) return;
        const recordingConsent = app.dataset.consentRecording === '1';
        const localVideo = doc.getElementById('localVideo');
        const remoteVideo = doc.getElementById('remoteVideo');
        const remoteConnectionState = doc.getElementById('remoteConnectionState');
        const localMediaState = doc.getElementById('localMediaState');
        const waitingRoomMessage = doc.getElementById('waitingRoomMessage');
        const statusLabel = doc.getElementById('consultationStatusLabel');
        const connectionLog = doc.getElementById('connectionLog');
        const toggleMicBtn = doc.getElementById('toggleMicBtn');
        const toggleCameraBtn = doc.getElementById('toggleCameraBtn');
        const shareScreenBtn = doc.getElementById('shareScreenBtn');
        const recordSessionBtn = doc.getElementById('recordSessionBtn');
        const hangupBtn = doc.getElementById('hangupBtn');
        const chatInput = doc.getElementById('chatInputMessage');
        const sendChatBtn = doc.getElementById('sendChatBtn');
        const chatMessagesArea = doc.getElementById('chatMessagesArea');

        const state = {
            pc: null,
            localStream: null,
            remoteStream: typeof MediaStream !== 'undefined' ? new MediaStream() : null,
            lastSignalId: 0,
            offerSent: false,
            isScreenSharing: false,
            originalVideoTrack: null,
            sessionEnded: app.dataset.status === 'ended',
            remoteJoined: false,
            mediaRecorder: null,
            recordedChunks: [],
            recording: false,
        };

        if (callType === 'voice') {
            if (toggleCameraBtn) toggleCameraBtn.disabled = true;
            if (shareScreenBtn) shareScreenBtn.disabled = true;
        }

        const iceConfig = {
            iceServers: [
                { urls: 'stun:stun.l.google.com:19302' },
                { urls: 'stun:stun1.l.google.com:19302' },
            ]
        };

        function log(message) {
            if (!connectionLog) return;
            const item = doc.createElement('div');
            item.className = 'log-item';
            item.textContent = `${new Date().toLocaleTimeString()} - ${message}`;
            connectionLog.prepend(item);
        }

        function appendChatMessage(text, mine = false, sentAt = null) {
            if (!chatMessagesArea || !text) return;
            const item = doc.createElement('div');
            item.className = `call-chat-message${mine ? ' mine' : ''}`;
            const body = doc.createElement('span');
            body.textContent = String(text).slice(0, 700);
            const time = doc.createElement('small');
            time.textContent = sentAt || new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            item.append(body, time);
            chatMessagesArea.appendChild(item);
            chatMessagesArea.scrollTop = chatMessagesArea.scrollHeight;
        }

        function updateWaitingMessage(message) {
            if (waitingRoomMessage) waitingRoomMessage.textContent = message;
        }

        if (premiumLocked) {
            if (remoteConnectionState) remoteConnectionState.textContent = 'Premium live media locked';
            if (localMediaState) localMediaState.textContent = 'Subscribe to start camera and microphone';
            updateWaitingMessage('Room details are available. Live consultation media unlocks with Premium.');
            log('Premium subscription required before starting live media.');
            [toggleMicBtn, toggleCameraBtn, shareScreenBtn, recordSessionBtn].forEach((button) => {
                if (!button) return;
                button.dataset.premiumLocked = '1';
                button.addEventListener('click', (event) => guardPremiumAction(event, button));
            });
            return;
        }

        function safeJsonParse(value) {
            try {
                return value ? JSON.parse(value) : {};
            } catch (error) {
                return {};
            }
        }

        async function apiRequest(route, method = 'GET', body = null) {
            const options = { method };
            if (body) options.body = body;
            const response = await fetch(`${window.MEDISPHERE.baseUrl}/index.php?route=${route}`, options);
            if (!response.ok) throw new Error(`Request failed for ${route}`);
            return response.json();
        }

        function createPeerConnection() {
            if (state.pc) return state.pc;
            state.pc = new RTCPeerConnection(iceConfig);
            state.remoteStream = new MediaStream();
            if (remoteVideo) remoteVideo.srcObject = state.remoteStream;

            state.pc.ontrack = (event) => {
                event.streams[0].getTracks().forEach((track) => state.remoteStream.addTrack(track));
                if (remoteVideo) remoteVideo.srcObject = state.remoteStream;
                if (remoteConnectionState) remoteConnectionState.textContent = 'Remote participant connected';
                updateWaitingMessage('Both participants are connected. Consultation is live.');
                log('Remote media track received.');
            };

            state.pc.onicecandidate = async (event) => {
                if (!event.candidate || state.sessionEnded) return;
                const payload = JSON.stringify(event.candidate);
                const form = new FormData();
                form.append('csrf_token', window.MEDISPHERE.csrf);
                form.append('session_id', String(sessionId));
                form.append('signal_type', 'candidate');
                form.append('payload', payload);
                await fetch(`${window.MEDISPHERE.baseUrl}/index.php?route=api/consultations/signal`, { method: 'POST', body: form }).catch(() => { });
            };

            state.pc.onconnectionstatechange = () => {
                const currentState = state.pc.connectionState;
                if (remoteConnectionState) remoteConnectionState.textContent = currentState;
                log(`Peer connection state: ${currentState}`);
                if (currentState === 'connected') {
                    updateWaitingMessage('Secure peer-to-peer connection established.');
                }
                if (['failed', 'disconnected', 'closed'].includes(currentState)) {
                    updateWaitingMessage('Connection interrupted or ended.');
                }
            };

            state.pc.oniceconnectionstatechange = () => {
                log(`ICE state: ${state.pc.iceConnectionState}`);
            };

            return state.pc;
        }

        async function initLocalMedia() {
            try {
                const constraints = {
                    audio: true,
                    video: callType === 'video' ? { width: { ideal: 1280 }, height: { ideal: 720 } } : false,
                };
                state.localStream = await navigator.mediaDevices.getUserMedia(constraints);
                if (localVideo) localVideo.srcObject = state.localStream;
                if (localMediaState) localMediaState.textContent = callType === 'video' ? 'Camera and microphone ready' : 'Microphone ready for voice consultation';
                createPeerConnection();
                state.localStream.getTracks().forEach((track) => state.pc.addTrack(track, state.localStream));
                state.originalVideoTrack = state.localStream.getVideoTracks()[0] || null;
                log('Local media initialized.');
            } catch (error) {
                log('Could not access local media devices. Please allow microphone/camera permissions.');
                if (localMediaState) localMediaState.textContent = 'Media permission denied or unavailable';
            }
        }

        let ws = null;
        async function sendSignal(signalType, payload = {}) {
            if (ws && ws.readyState === WebSocket.OPEN) {
                ws.send(JSON.stringify({
                    type: 'signal',
                    session_id: sessionId,
                    user_id: selfId,
                    signal_type: signalType,
                    payload: typeof payload === 'string' ? payload : JSON.stringify(payload)
                }));
            } else {
                log(`Failed to send ${signalType} signal (WebSocket not connected).`);
            }
        }

        async function createOfferIfNeeded() {
            if (state.offerSent || state.sessionEnded || !state.remoteJoined || selfId !== initiatorId || !state.pc) return;
            state.offerSent = true;
            try {
                const offer = await state.pc.createOffer();
                await state.pc.setLocalDescription(offer);
                await sendSignal('offer', offer);
                log('Offer created and sent.');
            } catch (error) {
                log('Failed to create offer.');
            }
        }

        async function handleSignal(signal) {
            const payload = safeJsonParse(signal.payload);
            state.lastSignalId = Math.max(state.lastSignalId, Number(signal.id || 0));

            switch (signal.signal_type) {
                case 'presence':
                    state.remoteJoined = true;
                    updateWaitingMessage('The other participant has joined the waiting room. Connecting...');
                    log('Remote participant announced presence.');
                    createOfferIfNeeded();
                    break;
                case 'offer':
                    createPeerConnection();
                    try {
                        await state.pc.setRemoteDescription(new RTCSessionDescription(payload));
                        const answer = await state.pc.createAnswer();
                        await state.pc.setLocalDescription(answer);
                        await sendSignal('answer', answer);
                        updateWaitingMessage('Answer sent. Finalizing peer connection...');
                        log('Offer received and answer sent.');
                    } catch (error) {
                        log('Could not process incoming offer.');
                    }
                    break;
                case 'answer':
                    try {
                        await state.pc.setRemoteDescription(new RTCSessionDescription(payload));
                        updateWaitingMessage('Answer received. Establishing call...');
                        log('Answer received.');
                    } catch (error) {
                        log('Could not process answer.');
                    }
                    break;
                case 'candidate':
                    try {
                        if (payload && payload.candidate) {
                            await state.pc.addIceCandidate(new RTCIceCandidate(payload));
                            log('ICE candidate added.');
                        }
                    } catch (error) {
                        log('Could not add ICE candidate.');
                    }
                    break;
                case 'hangup':
                    log('Remote participant ended the session.');
                    updateWaitingMessage('The other participant ended the session.');
                    state.sessionEnded = true;
                    closePeerConnection();
                    break;
                case 'screen-share':
                    log('Remote participant changed screen sharing state.');
                    break;
                case 'status':
                    if (payload.kind === 'chat') {
                        appendChatMessage(payload.text || '', false, payload.sent_at || null);
                        break;
                    }
                    log(payload.message || 'Session status updated.');
                    break;
                default:
                    break;
            }
        }

        function initWebSocket() {
            const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
            const host = window.location.hostname || 'localhost';
            ws = new WebSocket(`${protocol}//${host}:8080`);

            ws.onopen = () => {
                log('WebSocket connected. Real-time signaling active.');
                ws.send(JSON.stringify({
                    type: 'join',
                    session_id: sessionId,
                    user_id: selfId
                }));
                announcePresence();
            };

            ws.onmessage = async (event) => {
                if (state.sessionEnded) return;
                try {
                    const signal = JSON.parse(event.data);
                    await handleSignal(signal);
                } catch (error) {
                    log('Error processing incoming signal.');
                }
            };

            ws.onclose = () => {
                if (!state.sessionEnded) {
                    log('WebSocket disconnected. Reconnecting in 2s...');
                    setTimeout(initWebSocket, 2000);
                }
            };

            ws.onerror = () => {
                log('WebSocket encountered an error.');
            };
        }

        async function pollSessionInfo() {
            if (state.sessionEnded) return;
            try {
                const data = await apiRequest(`api/consultations/session&session_id=${sessionId}`);
                if (!data.success || !data.session) return;
                const session = data.session;
                if (statusLabel) statusLabel.textContent = session.status;
                state.remoteJoined = Number(data.participant_count || 0) >= 2;
                if (state.remoteJoined) createOfferIfNeeded();
                if (session.status === 'active') {
                    updateWaitingMessage('Session is active.');
                } else if (session.status === 'ended') {
                    updateWaitingMessage('Session ended.');
                    state.sessionEnded = true;
                    closePeerConnection();
                }
            } catch (error) {
                log('Session status polling interrupted.');
            }
        }

        function closePeerConnection() {
            if (state.pc) {
                state.pc.ontrack = null;
                state.pc.onicecandidate = null;
                state.pc.close();
                state.pc = null;
            }
            if (state.localStream) {
                state.localStream.getTracks().forEach((track) => track.stop());
            }
            if (state.mediaRecorder && state.recording) {
                state.mediaRecorder.stop();
            }
        }

        async function toggleMic() {
            if (!state.localStream) return;
            const track = state.localStream.getAudioTracks()[0];
            if (!track) return;
            track.enabled = !track.enabled;
            toggleMicBtn.classList.toggle('is-muted', !track.enabled);
            toggleMicBtn.setAttribute('aria-label', track.enabled ? 'Mute microphone' : 'Unmute microphone');
            toggleMicBtn.innerHTML = track.enabled ? '<i class="fa-solid fa-microphone"></i>' : '<i class="fa-solid fa-microphone-slash"></i>';
            log(track.enabled ? 'Microphone enabled.' : 'Microphone muted.');
        }

        async function toggleCamera() {
            if (!state.localStream) return;
            const track = state.localStream.getVideoTracks()[0];
            if (!track) {
                log('No local camera track is available.');
                return;
            }
            track.enabled = !track.enabled;
            toggleCameraBtn.classList.toggle('is-off', !track.enabled);
            toggleCameraBtn.setAttribute('aria-label', track.enabled ? 'Turn camera off' : 'Turn camera on');
            toggleCameraBtn.innerHTML = track.enabled ? '<i class="fa-solid fa-video"></i>' : '<i class="fa-solid fa-video-slash"></i>';
            log(track.enabled ? 'Camera enabled.' : 'Camera disabled.');
        }

        async function toggleScreenShare() {
            if (!state.pc || callType !== 'video') {
                log('Screen sharing is available for video consultations only.');
                return;
            }
            try {
                if (!state.isScreenSharing) {
                    const displayStream = await navigator.mediaDevices.getDisplayMedia({ video: true });
                    const screenTrack = displayStream.getVideoTracks()[0];
                    const sender = state.pc.getSenders().find((item) => item.track && item.track.kind === 'video');
                    if (sender && screenTrack) {
                        await sender.replaceTrack(screenTrack);
                    }
                    if (localVideo) localVideo.srcObject = displayStream;
                    state.isScreenSharing = true;
                    shareScreenBtn.classList.add('is-active');
                    shareScreenBtn.setAttribute('aria-label', 'Stop screen sharing');
                    shareScreenBtn.innerHTML = '<i class="fa-solid fa-display"></i>';
                    log('Screen sharing started.');
                    await sendSignal('screen-share', { active: true });
                    screenTrack.onended = async () => {
                        await restoreCameraAfterShare();
                    };
                } else {
                    await restoreCameraAfterShare();
                }
            } catch (error) {
                log('Screen sharing was cancelled or unavailable.');
            }
        }

        async function restoreCameraAfterShare() {
            const sender = state.pc?.getSenders().find((item) => item.track && item.track.kind === 'video');
            if (sender && state.originalVideoTrack) {
                await sender.replaceTrack(state.originalVideoTrack);
                if (localVideo && state.localStream) localVideo.srcObject = state.localStream;
            }
            state.isScreenSharing = false;
            shareScreenBtn.classList.remove('is-active');
            shareScreenBtn.setAttribute('aria-label', 'Share screen');
            shareScreenBtn.innerHTML = '<i class="fa-solid fa-display"></i>';
            log('Returned to camera feed.');
            await sendSignal('screen-share', { active: false });
        }

        function downloadRecording(blob) {
            const url = URL.createObjectURL(blob);
            const link = doc.createElement('a');
            link.href = url;
            link.download = `consultation-session-${sessionId}.webm`;
            link.click();
            setTimeout(() => URL.revokeObjectURL(url), 1000);
        }

        function toggleRecording() {
            if (!recordingConsent) {
                Toastify({ text: 'Recording requires prior consent for this session.', gravity: 'top', position: 'right', backgroundColor: '#dc3545' }).showToast();
                return;
            }
            if (!state.localStream || typeof MediaRecorder === 'undefined') {
                log('MediaRecorder is unavailable in this browser.');
                return;
            }
            if (!state.recording) {
                state.recordedChunks = [];
                const preferredMimeType = callType === 'voice' ? 'audio/webm;codecs=opus' : 'video/webm;codecs=vp8,opus';
                try {
                    state.mediaRecorder = new MediaRecorder(state.localStream, { mimeType: preferredMimeType });
                } catch (error) {
                    state.mediaRecorder = new MediaRecorder(state.localStream);
                }
                state.mediaRecorder.ondataavailable = (event) => {
                    if (event.data.size > 0) state.recordedChunks.push(event.data);
                };
                state.mediaRecorder.onstop = () => {
                    const blob = new Blob(state.recordedChunks, { type: state.mediaRecorder?.mimeType || (callType === 'voice' ? 'audio/webm' : 'video/webm') });
                    downloadRecording(blob);
                    recordSessionBtn?.classList.remove('is-recording');
                    if (recordSessionBtn) {
                        recordSessionBtn.setAttribute('aria-label', 'Record locally');
                        recordSessionBtn.innerHTML = '<i class="fa-solid fa-record-vinyl"></i>';
                    }
                    log('Local recording saved to your device.');
                };
                state.mediaRecorder.start();
                state.recording = true;
                recordSessionBtn.classList.add('is-recording');
                recordSessionBtn.setAttribute('aria-label', 'Stop local recording');
                recordSessionBtn.innerHTML = '<i class="fa-solid fa-stop"></i>';
                log('Local recording started.');
            } else {
                state.mediaRecorder.stop();
                state.recording = false;
                recordSessionBtn.classList.remove('is-recording');
                recordSessionBtn.setAttribute('aria-label', 'Record locally');
                recordSessionBtn.innerHTML = '<i class="fa-solid fa-record-vinyl"></i>';
            }
        }

        async function sendChatMessage() {
            const text = chatInput?.value.trim() || '';
            if (!text || state.sessionEnded) return;
            chatInput.value = '';
            const sentAt = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            appendChatMessage(text, true, sentAt);
            await sendSignal('status', { kind: 'chat', text, sent_at: sentAt });
        }

        async function endSessionFromClient() {
            state.sessionEnded = true;
            await sendSignal('hangup', { ended_by: selfId });
            closePeerConnection();
            const form = new FormData();
            form.append('csrf_token', window.MEDISPHERE.csrf);
            form.append('session_id', String(sessionId));
            try {
                await fetch(`${window.MEDISPHERE.baseUrl}/index.php?route=consultations/end`, { method: 'POST', body: form, redirect: 'follow' });
            } catch (error) {
                log('Could not sync session end with server.');
            }
            window.location.href = `${window.MEDISPHERE.baseUrl}/index.php?route=consultations`;
        }

        async function announcePresence() {
            await sendSignal('presence', { user_id: selfId, role: app.dataset.selfRole || 'participant' });
            log('Presence announced to signaling service.');
        }

        toggleMicBtn && toggleMicBtn.addEventListener('click', toggleMic);
        toggleCameraBtn && toggleCameraBtn.addEventListener('click', toggleCamera);
        shareScreenBtn && shareScreenBtn.addEventListener('click', toggleScreenShare);
        recordSessionBtn && recordSessionBtn.addEventListener('click', toggleRecording);
        hangupBtn && hangupBtn.addEventListener('click', endSessionFromClient);
        sendChatBtn && sendChatBtn.addEventListener('click', sendChatMessage);
        chatInput && chatInput.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                sendChatMessage();
            }
        });

        if (state.sessionEnded) {
            updateWaitingMessage('This consultation has ended. You can review the summary and feedback below.');
            log('Session is already ended. Live media is disabled.');
            [toggleMicBtn, toggleCameraBtn, shareScreenBtn, recordSessionBtn, hangupBtn].forEach((button) => { if (button) button.disabled = true; });
            return;
        }

        initLocalMedia().then(() => {
            initWebSocket();
            pollSessionInfo();
            setInterval(pollSessionInfo, 4000);
        });
    }


    function initSignaturePad() {
        const canvas = doc.getElementById('doctorSignatureCanvas');
        const hiddenInput = doc.getElementById('doctorSignatureData');
        const clearBtn = doc.getElementById('clearSignaturePadBtn');
        if (!canvas || !hiddenInput) return;

        const context = canvas.getContext('2d');
        context.lineWidth = 2.2;
        context.lineCap = 'round';
        context.strokeStyle = '#16324f';
        let drawing = false;
        let hasDrawn = false;

        function resizeCanvasForDisplay() {
            const ratio = Math.max(window.devicePixelRatio || 1, 1);
            const rect = canvas.getBoundingClientRect();
            if (!rect.width) return;
            canvas.width = Math.floor(rect.width * ratio);
            canvas.height = Math.floor(220 * ratio);
            context.setTransform(ratio, 0, 0, ratio, 0, 0);
            context.lineWidth = 2.2;
            context.lineCap = 'round';
            context.strokeStyle = '#16324f';
            context.clearRect(0, 0, canvas.width, canvas.height);
            hiddenInput.value = '';
            hasDrawn = false;
        }

        function pointFromEvent(event) {
            const rect = canvas.getBoundingClientRect();
            const source = event.touches ? event.touches[0] : event;
            return { x: source.clientX - rect.left, y: source.clientY - rect.top };
        }

        function startDrawing(event) {
            drawing = true;
            const point = pointFromEvent(event);
            context.beginPath();
            context.moveTo(point.x, point.y);
            event.preventDefault();
        }

        function draw(event) {
            if (!drawing) return;
            const point = pointFromEvent(event);
            context.lineTo(point.x, point.y);
            context.stroke();
            hasDrawn = true;
            event.preventDefault();
        }

        function stopDrawing() {
            if (!drawing) return;
            drawing = false;
            context.closePath();
            hiddenInput.value = hasDrawn ? canvas.toDataURL('image/png') : '';
        }

        resizeCanvasForDisplay();
        window.addEventListener('resize', resizeCanvasForDisplay);
        canvas.addEventListener('mousedown', startDrawing);
        canvas.addEventListener('mousemove', draw);
        canvas.addEventListener('mouseup', stopDrawing);
        canvas.addEventListener('mouseleave', stopDrawing);
        canvas.addEventListener('touchstart', startDrawing, { passive: false });
        canvas.addEventListener('touchmove', draw, { passive: false });
        canvas.addEventListener('touchend', stopDrawing);
        clearBtn && clearBtn.addEventListener('click', () => resizeCanvasForDisplay());
    }


    function initRichEditors() {
        const textareas = Array.from(doc.querySelectorAll('textarea[data-rich-editor]'));
        if (!textareas.length || typeof document.execCommand !== 'function') return;

        textareas.forEach((textarea, index) => {
            const wrapper = doc.createElement('div');
            wrapper.className = 'rich-editor-wrap';
            const toolbar = doc.createElement('div');
            toolbar.className = 'rich-editor-toolbar';
            const editor = doc.createElement('div');
            editor.className = 'rich-editor-canvas';
            editor.contentEditable = 'true';
            editor.dataset.editorIndex = String(index);
            editor.innerHTML = textarea.value || '<p><br></p>';

            const tools = [
                { cmd: 'bold', label: '<i class="fa-solid fa-bold"></i>' },
                { cmd: 'italic', label: '<i class="fa-solid fa-italic"></i>' },
                { cmd: 'insertUnorderedList', label: '<i class="fa-solid fa-list-ul"></i>' },
                { cmd: 'insertOrderedList', label: '<i class="fa-solid fa-list-ol"></i>' },
                { cmd: 'formatBlock', value: 'h3', label: 'H3' },
                { cmd: 'formatBlock', value: 'blockquote', label: '<i class="fa-solid fa-quote-left"></i>' },
                { cmd: 'createLink', prompt: 'Enter URL', label: '<i class="fa-solid fa-link"></i>' },
                { cmd: 'removeFormat', label: '<i class="fa-solid fa-eraser"></i>' },
            ];

            tools.forEach((tool) => {
                const button = doc.createElement('button');
                button.type = 'button';
                button.className = 'btn btn-outline-primary btn-sm';
                button.innerHTML = tool.label;
                button.addEventListener('click', () => {
                    editor.focus();
                    if (tool.prompt) {
                        const value = window.prompt(tool.prompt, 'https://');
                        if (value) document.execCommand(tool.cmd, false, value);
                    } else if (tool.value) {
                        document.execCommand(tool.cmd, false, tool.value);
                    } else {
                        document.execCommand(tool.cmd, false);
                    }
                    textarea.value = editor.innerHTML;
                });
                toolbar.appendChild(button);
            });

            editor.addEventListener('input', () => {
                textarea.value = editor.innerHTML;
            });

            textarea.classList.add('d-none');
            textarea.parentNode.insertBefore(wrapper, textarea);
            wrapper.appendChild(toolbar);
            wrapper.appendChild(editor);
            wrapper.appendChild(textarea);
        });
    }

    function initClinicalRecordTemplates() {
        const templateButtons = Array.from(doc.querySelectorAll('[data-prescription-template], [data-document-template]'));
        if (!templateButtons.length) return;

        const getAppointmentMeta = (form) => {
            const option = form.querySelector('select[name="appointment_id"]')?.selectedOptions?.[0];
            const patient = option?.dataset.patientName || 'the patient';
            const date = option?.dataset.appointmentDate || 'the selected appointment date';
            const time = option?.dataset.appointmentTime || '';
            return { patient, date, time };
        };

        const setField = (form, selector, value) => {
            const field = form.querySelector(selector);
            if (!field) return;
            field.value = value;
            field.dispatchEvent(new Event('input', { bubbles: true }));
            field.dispatchEvent(new Event('change', { bubbles: true }));
        };

        const setActiveTemplate = (button) => {
            const row = button.closest('.clinical-template-row');
            if (!row) return;
            row.querySelectorAll('.clinical-template-btn').forEach((item) => {
                item.classList.toggle('is-active', item === button);
            });
        };

        const prescriptionTemplates = {
            consultation: () => ({
                diagnosis: 'Clinical consultation completed - assessment documented',
                medications: [
                    'Medication name - strength - route - frequency - duration',
                    'Instruction / investigation - timing - notes',
                ].join('\n'),
                notes: 'History, examination findings, allergies, precautions, and relevant risk factors.',
                advice: 'Rest, hydration, warning signs, lifestyle guidance, and follow-up instructions explained.',
            }),
            followup: () => ({
                diagnosis: 'Follow-up review - response to treatment assessed',
                medications: [
                    'Continue / adjust medication - strength - frequency - duration',
                    'Monitoring instruction - parameter - review timing',
                ].join('\n'),
                notes: 'Current symptoms, treatment response, side effects, adherence, and updated clinical findings.',
                advice: 'Continue agreed care plan, monitor warning signs, and return earlier if symptoms worsen.',
            }),
            labreview: () => ({
                diagnosis: 'Lab / report review - findings discussed with patient',
                medications: [
                    'Medication or supplement - strength - frequency - duration',
                    'Repeat test / referral - timeframe - reason',
                ].join('\n'),
                notes: 'Relevant report findings, comparison with prior results, and clinical interpretation.',
                advice: 'Report results explained. Follow-up testing, diet, activity, and review plan discussed.',
            }),
        };

        const documentTemplates = {
            medical_certificate: (meta) => ({
                title: 'Medical Certificate',
                summary: `Medical certificate for ${meta.patient} based on the linked appointment.`,
                content: [
                    `This is to certify that ${meta.patient} was evaluated on ${meta.date}${meta.time ? ` at ${meta.time}` : ''}.`,
                    '',
                    'Clinical impression:',
                    'Recommendation / rest period:',
                    'Fitness or restriction advice:',
                    'Review instructions:',
                    '',
                    'This certificate is issued through the verified clinical records system with doctor signature and audit trail.',
                ].join('\n'),
            }),
            treatment_plan: (meta) => ({
                title: 'Treatment Plan',
                summary: `Treatment plan prepared for ${meta.patient}.`,
                content: [
                    `Patient: ${meta.patient}`,
                    `Appointment: ${meta.date}${meta.time ? ` ${meta.time}` : ''}`,
                    '',
                    'Clinical problem list:',
                    'Treatment goals:',
                    'Medication / therapy plan:',
                    'Investigations or referrals:',
                    'Lifestyle and safety advice:',
                    'Follow-up schedule:',
                ].join('\n'),
            }),
            discharge_summary: (meta) => ({
                title: 'Discharge Summary',
                summary: `Discharge summary for ${meta.patient}.`,
                content: [
                    `Patient: ${meta.patient}`,
                    `Discharge / review date: ${meta.date}`,
                    '',
                    'Reason for visit / admission:',
                    'Key findings:',
                    'Treatment provided:',
                    'Condition at discharge:',
                    'Discharge medications / instructions:',
                    'Follow-up and warning signs:',
                ].join('\n'),
            }),
            follow_up_note: (meta) => ({
                title: 'Follow-up Note',
                summary: `Follow-up note for ${meta.patient}.`,
                content: [
                    `Follow-up review for ${meta.patient} on ${meta.date}${meta.time ? ` at ${meta.time}` : ''}.`,
                    '',
                    'Progress since last visit:',
                    'Current concerns:',
                    'Medication adherence / side effects:',
                    'Updated plan:',
                    'Next review:',
                ].join('\n'),
            }),
        };

        doc.querySelectorAll('[data-prescription-template]').forEach((button) => {
            button.addEventListener('click', () => {
                const form = button.closest('form');
                const key = button.dataset.prescriptionTemplate;
                const template = prescriptionTemplates[key]?.();
                if (!form || !template) return;
                setField(form, '[data-prescription-diagnosis]', template.diagnosis);
                setField(form, '[data-prescription-medications]', template.medications);
                setField(form, '[data-prescription-notes]', template.notes);
                setField(form, '[data-prescription-advice]', template.advice);
                setActiveTemplate(button);
            });
        });

        doc.querySelectorAll('[data-document-template]').forEach((button) => {
            button.addEventListener('click', () => {
                const form = button.closest('form');
                const key = button.dataset.documentTemplate;
                if (!form || !documentTemplates[key]) return;
                const template = documentTemplates[key](getAppointmentMeta(form));
                setField(form, '[data-document-type-select]', key);
                setField(form, '[data-document-title]', template.title);
                setField(form, '[data-document-summary]', template.summary);
                setField(form, '[data-document-content]', template.content);
                setActiveTemplate(button);
            });
        });
    }

    function initHealthcareMaps() {
        const containers = Array.from(doc.querySelectorAll('.healthcare-map-canvas[data-facilities]'));
        if (!containers.length) return;

        containers.forEach((container) => setMapOverlay(container, 'loading', 'Loading healthcare map...'));

        const renderWithLeaflet = () => loadLeafletMaps()
            .then(() => {
                containers.forEach((container) => createLeafletHealthcareMap(container));
            })
            .catch(() => {
                containers.forEach((container) => {
                    setMapOverlay(container, 'error', 'Map assets could not load. Check your network connection and try again.');
                });
            });

        if (!window.MEDISPHERE.googleMapsKey) {
            renderWithLeaflet();
            return;
        }

        loadGoogleMaps()
            .then(() => {
                containers.forEach((container) => createHealthcareMap(container));
            })
            .catch(() => {
                renderWithLeaflet();
            });
    }

    function initHomePagination() {
        const setupPaging = (containerId, itemsClass, prevBtnId, nextBtnId, dotsContainerId, itemsPerPage) => {
            const container = doc.getElementById(containerId);
            const prevBtn = doc.getElementById(prevBtnId);
            const nextBtn = doc.getElementById(nextBtnId);
            const dotsContainer = doc.getElementById(dotsContainerId);
            if (!container || !prevBtn || !nextBtn || !dotsContainer) return;

            const items = Array.from(container.querySelectorAll(itemsClass));
            if (items.length <= itemsPerPage) {
                prevBtn.style.display = 'none';
                nextBtn.style.display = 'none';
                dotsContainer.style.display = 'none';
                return;
            }

            const pageCount = Math.ceil(items.length / itemsPerPage);
            let currentPage = 0;

            dotsContainer.innerHTML = '';
            for (let i = 0; i < pageCount; i++) {
                const dot = doc.createElement('button');
                dot.className = `paging-dot ${i === 0 ? 'active' : ''}`;
                dot.setAttribute('aria-label', `Go to page ${i + 1}`);
                dot.addEventListener('click', () => showPage(i));
                dotsContainer.appendChild(dot);
            }

            const showPage = (pageIndex) => {
                if (pageIndex < 0 || pageIndex >= pageCount || pageIndex === currentPage) return;

                const oldPageItems = items.filter((_, idx) => {
                    const page = Math.floor(idx / itemsPerPage);
                    return page === currentPage;
                });

                const newPageItems = items.filter((_, idx) => {
                    const page = Math.floor(idx / itemsPerPage);
                    return page === pageIndex;
                });

                oldPageItems.forEach(item => {
                    item.classList.add('fade-out');
                });

                setTimeout(() => {
                    items.forEach((item, idx) => {
                        const page = Math.floor(idx / itemsPerPage);
                        if (page === pageIndex) {
                            item.style.display = '';
                            item.classList.add('fade-in');
                            item.classList.remove('fade-out');
                        } else {
                            item.style.display = 'none';
                            item.classList.remove('fade-in', 'fade-out');
                        }
                    });

                    container.offsetHeight; // force reflow

                    newPageItems.forEach(item => {
                        item.classList.remove('fade-in');
                    });

                    currentPage = pageIndex;
                    updateControls();
                }, 300);
            };

            const updateControls = () => {
                prevBtn.disabled = currentPage === 0;
                nextBtn.disabled = currentPage === pageCount - 1;

                const dots = dotsContainer.querySelectorAll('.paging-dot');
                dots.forEach((dot, i) => {
                    dot.classList.toggle('active', i === currentPage);
                });
            };

            items.forEach((item, idx) => {
                const page = Math.floor(idx / itemsPerPage);
                if (page !== 0) {
                    item.style.display = 'none';
                }
                item.classList.add('paging-item');
            });

            prevBtn.addEventListener('click', () => showPage(currentPage - 1));
            nextBtn.addEventListener('click', () => showPage(currentPage + 1));
            updateControls();
        };

        setupPaging('featuredDoctorsContainer', '.doctor-card-col', 'doctorsPrevBtn', 'doctorsNextBtn', 'doctorsPagingDots', 3);
        setupPaging('featuredHospitalsContainer', '.hospital-card-col', 'hospitalsPrevBtn', 'hospitalsNextBtn', 'hospitalsPagingDots', 3);
    }

    function initVoicedArticleVideos() {
        doc.querySelectorAll('[data-voice-player]').forEach((player) => {
            const video = player.querySelector('[data-voice-video]');
            const toggleBtn = player.querySelector('[data-voice-toggle]');
            if (!video || !toggleBtn) return;

            let voiceOpen = false;
            video.controls = false;
            video.muted = true;
            video.loop = true;
            video.playsInline = true;

            const updateToggle = () => {
                toggleBtn.setAttribute('aria-pressed', voiceOpen ? 'true' : 'false');
                toggleBtn.setAttribute('aria-label', voiceOpen ? 'Close video voice' : 'Open video voice');
                toggleBtn.innerHTML = voiceOpen
                    ? '<i class="fa-solid fa-volume-high"></i><span>Voice Close</span>'
                    : '<i class="fa-solid fa-volume-xmark"></i><span>Voice Open</span>';
            };

            const openVoice = async () => {
                voiceOpen = true;
                video.muted = false;
                video.volume = 1;
                updateToggle();
                await video.play().catch(() => { });
            };

            const closeVoice = () => {
                voiceOpen = false;
                video.muted = true;
                updateToggle();
            };

            video.play().catch(() => { });
            updateToggle();

            toggleBtn.addEventListener('click', () => {
                if (voiceOpen) {
                    closeVoice();
                    return;
                }
                openVoice();
            });
        });
    }

    function initSubscriptionGate() {
        const modalEl = doc.getElementById('subscriptionGateModal');
        if (!modalEl) return;

        doc.querySelectorAll('[data-premium-locked="1"]').forEach((trigger) => {
            trigger.addEventListener('click', (event) => {
                event.preventDefault();
                openSubscriptionGate();
            });
        });

        if (modalEl.getAttribute('data-subscription-auto-open') === '1') {
            setTimeout(() => openSubscriptionGate(), 250);
        }
    }

    function initSubscriptionPricing() {
        const shells = Array.from(doc.querySelectorAll('[data-subscription-pricing]'));
        if (!shells.length) return;

        shells.forEach((shell) => {
            const cycleButtons = Array.from(shell.querySelectorAll('[data-billing-cycle-button]'));
            const priceBlocks = Array.from(shell.querySelectorAll('[data-plan-price]'));
            const pkrBlocks = Array.from(shell.querySelectorAll('[data-plan-pkr]'));
            const cycleInputs = Array.from(shell.querySelectorAll('[data-billing-cycle-input]'));

            const updatePrices = (cycle) => {
                const normalizedCycle = cycle === 'yearly' ? 'yearly' : 'monthly';
                shell.dataset.billingCycle = normalizedCycle;

                cycleButtons.forEach((button) => {
                    const active = button.dataset.billingCycleButton === normalizedCycle;
                    button.classList.toggle('active', active);
                    button.setAttribute('aria-pressed', active ? 'true' : 'false');
                });

                cycleInputs.forEach((input) => {
                    input.value = normalizedCycle;
                });

                priceBlocks.forEach((block) => {
                    const amount = block.querySelector('[data-price-amount]');
                    const currency = block.querySelector('[data-price-currency]');
                    const interval = block.querySelector('[data-price-interval]');
                    const nextPrice = block.dataset[`${normalizedCycle}Price`] || block.dataset.monthlyPrice || '0';
                    const nextInterval = block.dataset[`${normalizedCycle}Interval`] || block.dataset.monthlyInterval || 'month';
                    const isFree = Number(String(nextPrice).replace(/,/g, '')) === 0;

                    block.classList.add('is-switching');
                    setTimeout(() => {
                        if (amount) amount.textContent = isFree ? 'Free' : nextPrice;
                        if (currency) currency.style.display = isFree ? 'none' : '';
                        if (interval) interval.textContent = `/ ${nextInterval}`;
                        block.classList.remove('is-switching');
                    }, 130);
                });

                pkrBlocks.forEach((block) => {
                    const amount = block.querySelector('[data-pkr-amount]');
                    const nextPkr = block.dataset[`${normalizedCycle}Pkr`] || block.dataset.monthlyPkr || '0';
                    if (amount) amount.textContent = nextPkr;
                });
            };

            cycleButtons.forEach((button) => {
                button.addEventListener('click', () => updatePrices(button.dataset.billingCycleButton));
            });

            updatePrices(shell.dataset.billingCycle || 'monthly');
        });

        doc.querySelectorAll('[data-subscription-checkout-form]').forEach((form) => {
            if (form.dataset.checkoutHandlerAttached === '1') return;
            form.dataset.checkoutHandlerAttached = '1';
            form.addEventListener('submit', (event) => {
                if (form.dataset.submitting === '1') return;
                event.preventDefault();
                form.dataset.submitting = '1';

                const button = form.querySelector('button[type="submit"], button:not([type])');
                if (button) {
                    const loadingLabel = button.dataset.loadingLabel || 'Processing Securely...';
                    button.disabled = true;
                    button.classList.add('is-loading');
                    button.innerHTML = `<span class="pricing-spinner" aria-hidden="true"></span><span>${loadingLabel}</span>`;
                }

                setTimeout(() => form.submit(), 450);
            });
        });
    }

    function initDiseaseLibrary() {
        const root = doc.querySelector('[data-disease-library]');
        if (!root) return;

        const dataEl = doc.getElementById('diseaseLibraryData');
        const resultsEl = root.querySelector('[data-disease-results]');
        const emptyEl = root.querySelector('[data-disease-empty]');
        const searchInput = root.querySelector('[data-disease-search]');
        const searchForm = root.querySelector('[data-disease-search-form]');
        const clearBtn = root.querySelector('[data-disease-clear]');
        const countEl = root.querySelector('[data-disease-result-count]');
        const statusEl = root.querySelector('[data-disease-filter-status]');
        const headingEl = root.querySelector('[data-disease-results-heading]');
        const categoryButtons = Array.from(root.querySelectorAll('[data-disease-category]'));
        const letterButtons = Array.from(root.querySelectorAll('[data-disease-letter]'));
        const riskButtons = Array.from(root.querySelectorAll('[data-disease-risk]'));
        const apiUrl = root.dataset.apiUrl || '';
        let allItems = [];
        let activeCategory = 'All';
        let activeLetter = 'all';
        let activeRisk = 'all';
        let fetchTimer = null;
        let fetchController = null;

        try {
            allItems = JSON.parse(dataEl?.textContent || '[]');
        } catch (error) {
            allItems = [];
        }

        const categoryNames = categoryButtons.map((button) => button.dataset.diseaseCategory).filter(Boolean);

        const escape = (value) => String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');

        const cardMarkup = (item) => {
            const riskClass = String(item.risk_level || 'low').toLowerCase().replace(/[^a-z0-9]+/g, '-');
            return `
            <a href="${escape(item.url)}" class="disease-atlas-card" data-disease-card data-category="${escape(item.category)}" data-letter="${escape(item.letter)}" data-risk="${escape(item.risk_level || 'Low')}" data-search="${escape(item.search)}" aria-label="Open ${escape(item.name)}">
                <div class="disease-card-media">
                    <img src="${escape(item.image)}" alt="${escape(item.name)} clinical visual" loading="lazy">
                    <span class="risk-${escape(riskClass)}">${escape(item.risk_level || 'Low')}</span>
                </div>
                <div class="disease-card-body">
                    <small><i class="fa-solid ${escape(item.icon || 'fa-book-medical')}"></i>${escape(item.category || 'Guide')}</small>
                    <h3>${escape(item.name)}</h3>
                    <p>${escape(item.summary)}</p>
                    <div class="disease-card-footer">
                        <span>${escape(item.type || 'Clinical condition')}</span>
                        <strong>Details <i class="fa-solid fa-arrow-right"></i></strong>
                    </div>
                </div>
            </a>`;
        };

        const filteredItems = () => {
            const query = (searchInput?.value || '').trim().toLowerCase();
            return allItems.filter((item) => {
                const categoryMatch = activeCategory === 'All' || item.category === activeCategory;
                const letterMatch = activeLetter === 'all' || item.letter === activeLetter;
                const riskMatch = activeRisk === 'all' || item.risk_level === activeRisk;
                const queryMatch = !query || String(item.search || '').toLowerCase().includes(query) || String(item.name || '').toLowerCase().includes(query);
                return categoryMatch && letterMatch && riskMatch && queryMatch;
            });
        };

        const updateControls = (visibleItems) => {
            const query = (searchInput?.value || '').trim();
            const counts = Object.fromEntries(categoryNames.map((category) => [category, 0]));
            counts.All = allItems.length;

            allItems.forEach((item) => {
                counts[item.category] = (counts[item.category] || 0) + 1;
            });

            categoryButtons.forEach((button) => {
                const category = button.dataset.diseaseCategory || 'All';
                const active = category === activeCategory;
                button.classList.toggle('active', active);
                button.setAttribute('aria-pressed', active ? 'true' : 'false');
                const count = button.querySelector('[data-disease-category-count]');
                if (count) count.textContent = String(counts[category] || 0);
            });

            const availableLetters = new Set(allItems.map((item) => item.letter));
            letterButtons.forEach((button) => {
                const letter = button.dataset.diseaseLetter || 'all';
                const active = letter === activeLetter;
                button.classList.toggle('active', active);
                button.setAttribute('aria-pressed', active ? 'true' : 'false');
                button.disabled = letter !== 'all' && !availableLetters.has(letter);
            });

            riskButtons.forEach((button) => {
                const risk = button.dataset.diseaseRisk || 'all';
                const active = risk === activeRisk;
                button.classList.toggle('active', active);
                button.setAttribute('aria-pressed', active ? 'true' : 'false');
            });

            if (countEl) countEl.textContent = String(visibleItems.length);
            if (headingEl) headingEl.textContent = query ? `Guides matching "${query}"` : 'Global disease and pathogen index';
            if (statusEl) {
                const parts = [];
                if (activeCategory !== 'All') parts.push(activeCategory);
                if (activeRisk !== 'all') parts.push(`${activeRisk} risk`);
                if (activeLetter !== 'all') parts.push(`starts with ${activeLetter}`);
                if (query) parts.push('live search');
                statusEl.textContent = parts.length ? `Showing ${parts.join(', ')}` : 'Showing all specialties';
            }
        };

        const render = () => {
            const visibleItems = filteredItems();
            if (resultsEl) {
                resultsEl.innerHTML = visibleItems.map(cardMarkup).join('');
            }
            if (emptyEl) {
                emptyEl.classList.toggle('d-none', visibleItems.length > 0);
            }
            updateControls(visibleItems);
            if (window.AOS) window.AOS.refreshHard();
        };

        const fetchDiseases = () => {
            const query = (searchInput?.value || '').trim();
            if (!apiUrl || !window.fetch) {
                render();
                return;
            }

            if (fetchController) fetchController.abort();
            fetchController = new AbortController();
            const url = new URL(apiUrl, window.location.href);
            if (query) url.searchParams.set('q', query);

            root.classList.add('is-filtering');
            fetch(url.toString(), { signal: fetchController.signal })
                .then((response) => response.ok ? response.json() : Promise.reject(new Error('Disease search failed')))
                .then((payload) => {
                    if (Array.isArray(payload.diseases)) {
                        allItems = payload.diseases;
                    }
                    render();
                })
                .catch((error) => {
                    if (error.name !== 'AbortError') render();
                })
                .finally(() => root.classList.remove('is-filtering'));
        };

        searchForm && searchForm.addEventListener('submit', (event) => {
            event.preventDefault();
            fetchDiseases();
        });

        searchInput && searchInput.addEventListener('input', () => {
            render();
            clearTimeout(fetchTimer);
            fetchTimer = setTimeout(fetchDiseases, 220);
        });

        categoryButtons.forEach((button) => {
            button.addEventListener('click', () => {
                activeCategory = button.dataset.diseaseCategory || 'All';
                render();
            });
        });

        letterButtons.forEach((button) => {
            button.addEventListener('click', () => {
                if (button.disabled) return;
                activeLetter = button.dataset.diseaseLetter || 'all';
                render();
            });
        });

        riskButtons.forEach((button) => {
            button.addEventListener('click', () => {
                activeRisk = button.dataset.diseaseRisk || 'all';
                render();
            });
        });

        clearBtn && clearBtn.addEventListener('click', () => {
            activeCategory = 'All';
            activeLetter = 'all';
            activeRisk = 'all';
            if (searchInput) searchInput.value = '';
            fetchDiseases();
        });

        root.addEventListener('click', (event) => {
            const lockedVideo = event.target.closest('[data-premium-video-lock]');
            if (!lockedVideo) return;
            event.preventDefault();
            const modalEl = doc.getElementById('diseasePremiumVideoModal');
            if (modalEl && typeof bootstrap !== 'undefined') {
                bootstrap.Modal.getOrCreateInstance(modalEl).show();
            }
        });

        root.addEventListener('keydown', (event) => {
            if (!['Enter', ' '].includes(event.key)) return;
            const lockedVideo = event.target.closest('[data-premium-video-lock]');
            if (!lockedVideo) return;
            event.preventDefault();
            lockedVideo.click();
        });

        render();
    }

    function initEducationVideoPlayers() {
        doc.querySelectorAll('[data-education-video-player]').forEach((player) => {
            const video = player.querySelector('[data-education-video]');
            const toggle = player.querySelector('[data-education-video-toggle]');
            const progress = player.querySelector('[data-education-video-progress]');
            if (!video || !toggle) return;

            const updateToggle = () => {
                const playing = !video.paused && !video.ended;
                player.classList.toggle('is-playing', playing);
                toggle.setAttribute('aria-label', playing ? 'Pause video' : 'Play video');
                toggle.innerHTML = playing ? '<i class="fa-solid fa-pause"></i>' : '<i class="fa-solid fa-play"></i>';
            };

            const togglePlayback = () => {
                if (video.paused || video.ended) {
                    video.play().catch(() => { });
                    return;
                }
                video.pause();
            };

            toggle.addEventListener('click', togglePlayback);
            video.addEventListener('click', togglePlayback);
            video.addEventListener('play', updateToggle);
            video.addEventListener('pause', updateToggle);
            video.addEventListener('ended', updateToggle);
            video.addEventListener('timeupdate', () => {
                if (!progress || !video.duration) return;
                progress.style.width = `${Math.min(100, (video.currentTime / video.duration) * 100)}%`;
            });
            updateToggle();
        });
    }

    function initProfileImagePreview() {
        doc.querySelectorAll('[data-profile-image-input]').forEach((input) => {
            const preview = doc.querySelector(input.dataset.previewTarget || '');
            const placeholder = doc.querySelector(input.dataset.placeholderTarget || '');
            const removeToggle = doc.querySelector(input.dataset.removeTarget || '');

            input.addEventListener('change', () => {
                const file = input.files && input.files[0] ? input.files[0] : null;
                if (!file || !file.type || !file.type.startsWith('image/')) return;

                const reader = new FileReader();
                reader.addEventListener('load', () => {
                    if (preview) {
                        preview.src = String(reader.result || '');
                        preview.classList.remove('d-none');
                    }
                    if (placeholder) placeholder.classList.add('d-none');
                    if (removeToggle) removeToggle.checked = false;
                });
                reader.readAsDataURL(file);
            });
        });
    }

    function initUxSteppers() {
        doc.querySelectorAll('[data-stepper]').forEach((stepper, stepperIndex) => {
            const tabs = Array.from(stepper.querySelectorAll('[data-stepper-tab]'));
            const panels = Array.from(stepper.querySelectorAll('[data-stepper-panel]'));
            if (!tabs.length || !panels.length) return;

            panels.forEach((panel, panelIndex) => {
                if (!panel.id) panel.id = `ux-stepper-${stepperIndex + 1}-panel-${panelIndex + 1}`;
                panel.setAttribute('role', 'tabpanel');
                panel.setAttribute('tabindex', '0');
            });

            tabs.forEach((tab, tabIndex) => {
                const panel = panels[tabIndex] || panels[0];
                const targetId = tab.dataset.stepperTarget || panel.id;
                tab.dataset.stepperTarget = targetId;
                tab.setAttribute('type', 'button');
                tab.setAttribute('role', 'tab');
                tab.setAttribute('aria-controls', targetId);
            });

            const activate = (targetId, focusTab = false) => {
                const nextPanel = panels.find((panel) => panel.id === targetId) || panels[0];
                const nextId = nextPanel.id;

                panels.forEach((panel) => {
                    const active = panel === nextPanel;
                    panel.hidden = !active;
                    panel.classList.toggle('is-active', active);
                });

                tabs.forEach((tab) => {
                    const active = tab.dataset.stepperTarget === nextId;
                    tab.classList.toggle('is-active', active);
                    tab.setAttribute('aria-selected', active ? 'true' : 'false');
                    tab.setAttribute('tabindex', active ? '0' : '-1');
                    if (active && focusTab) tab.focus({ preventScroll: true });
                });

                stepper.dataset.activeStep = String(panels.indexOf(nextPanel) + 1);
                if (window.AOS) {
                    if (typeof AOS.refreshHard === 'function') AOS.refreshHard();
                    else if (typeof AOS.refresh === 'function') AOS.refresh();
                }
            };

            tabs.forEach((tab, tabIndex) => {
                tab.addEventListener('click', () => activate(tab.dataset.stepperTarget));
                tab.addEventListener('keydown', (event) => {
                    if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
                    event.preventDefault();
                    let nextIndex = tabIndex;
                    if (event.key === 'ArrowRight') nextIndex = (tabIndex + 1) % tabs.length;
                    if (event.key === 'ArrowLeft') nextIndex = (tabIndex - 1 + tabs.length) % tabs.length;
                    if (event.key === 'Home') nextIndex = 0;
                    if (event.key === 'End') nextIndex = tabs.length - 1;
                    activate(tabs[nextIndex].dataset.stepperTarget, true);
                });
            });

            const initialTab = tabs.find((tab) => tab.classList.contains('is-active')) || tabs[0];
            activate(initialTab.dataset.stepperTarget);
        });
    }

    function initAppointmentBookingFilters() {
        const params = new URLSearchParams(window.location.search || '');
        const shouldOpenBooking = params.get('book') === '1' || window.location.hash === '#bookAppointmentModal';
        let bookingModalOpened = false;
        const openBookingModal = () => {
            const modal = doc.getElementById('bookAppointmentModal');
            if (!modal || !window.bootstrap?.Modal) return;
            bootstrap.Modal.getOrCreateInstance(modal).show();
        };

        doc.querySelectorAll('[data-appointment-booking-form]').forEach((form) => {
            const hospitalSelect = form.querySelector('[data-appointment-hospital-select]');
            const doctorSelect = form.querySelector('[data-appointment-doctor-select]');
            const emptyState = form.querySelector('[data-appointment-doctor-empty]');
            if (!doctorSelect) return;

            const getDoctorOptions = () => Array.from(doctorSelect.options).filter((option) => option.value);
            const doctorOptions = getDoctorOptions();
            const defaultDoctorId = form.dataset.defaultDoctorId || params.get('doctor_id') || '';
            let defaultHospitalId = form.dataset.defaultHospitalId || params.get('hospital_id') || '';
            const defaultDoctorOption = doctorOptions.find((option) => option.value === defaultDoctorId);
            if (!defaultHospitalId && defaultDoctorOption?.dataset?.hospitalId) {
                defaultHospitalId = defaultDoctorOption.dataset.hospitalId;
            }

            const refreshDoctors = () => {
                const hospitalId = hospitalSelect?.value || '';
                let visibleCount = 0;

                getDoctorOptions().forEach((option) => {
                    const optionHospitalId = option.dataset.hospitalId || '';
                    const visible = !hospitalId || optionHospitalId === hospitalId;
                    option.hidden = !visible;
                    option.disabled = !visible;
                    if (visible) visibleCount += 1;
                });

                const selectedOption = doctorSelect.selectedOptions[0];
                if (selectedOption && selectedOption.disabled) {
                    doctorSelect.value = '';
                }

                doctorSelect.disabled = false;
                if (emptyState) {
                    emptyState.hidden = visibleCount > 0;
                    emptyState.setAttribute('aria-hidden', visibleCount > 0 ? 'true' : 'false');
                }
            };

            if (hospitalSelect && defaultHospitalId) {
                hospitalSelect.value = defaultHospitalId;
            }

            hospitalSelect?.addEventListener('change', refreshDoctors);
            refreshDoctors();

            if (defaultDoctorOption && !defaultDoctorOption.disabled) {
                doctorSelect.value = defaultDoctorId;
            }

            if (shouldOpenBooking && !bookingModalOpened) {
                const modal = form.closest('.modal') || doc.getElementById('bookAppointmentModal');
                if (modal && window.bootstrap?.Modal) {
                    window.setTimeout(() => {
                        bootstrap.Modal.getOrCreateInstance(modal).show();
                    }, 120);
                    bookingModalOpened = true;
                }
            }
        });

        doc.querySelectorAll('[data-open-booking-modal]').forEach((button) => {
            button.addEventListener('click', () => {
                window.setTimeout(openBookingModal, 0);
            });
        });
    }

    initPremiumSplash();
    window.MEDISPHERE.currentUserId = doc.querySelector('meta[name="current-user-id"]')?.content || '';
    initAOS();
    initTheme();
    initSmartSearch();
    initSidebar();
    initMobileNav();
    initLogoutConfirm();
    initParticles();
    initScrollReveal();
    init3DTilt();
    initHero3D();
    initMagneticButtons();
    initParallaxOrbs();
    initGSAPAnimations();
    initCalendar();
    initAdminChart();
    initHealthMetricsChart();
    initReportSearch();
    initPasswordUI();
    initChat();
    initScanner();
    initChatbot();
    initNotificationsPolling();
    initConsultationRoom();
    initSignaturePad();
    initRichEditors();
    initClinicalRecordTemplates();
    initHealthcareMaps();
    initHomePagination();
    initVoicedArticleVideos();
    initSubscriptionGate();
    initSubscriptionPricing();
    initDiseaseLibrary();
    initEducationVideoPlayers();
    initProfileImagePreview();
    initUxSteppers();
    initAppointmentBookingFilters();
    initPageTranslator();
})();
