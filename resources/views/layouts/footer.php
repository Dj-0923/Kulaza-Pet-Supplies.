<?php
// Shared bottom part of every page: closes the layout and loads the shared scripts.
/** @var array $categories */
?>
    </main>
<div id="toast" class="fixed bottom-6 right-6 bg-[#2e7d32] px-4 py-2 rounded-lg text-sm hidden"></div>
<script>
    const peso = n => '₱' + Number(n).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    (() => {
        const toggle = document.getElementById('mobile-nav-toggle');
        const sidebar = document.getElementById('app-sidebar');
        const backdrop = document.getElementById('mobile-nav-backdrop');
        if (!toggle || !sidebar || !backdrop) return;

        const closeMenu = (restoreFocus = false) => {
            sidebar.classList.add('-translate-x-full');
            backdrop.classList.add('hidden');
            toggle.setAttribute('aria-expanded', 'false');
            toggle.setAttribute('aria-label', 'Open navigation menu');
            document.body.classList.remove('overflow-hidden');
            if (restoreFocus) toggle.focus();
        };
        const openMenu = () => {
            sidebar.classList.remove('-translate-x-full');
            backdrop.classList.remove('hidden');
            toggle.setAttribute('aria-expanded', 'true');
            toggle.setAttribute('aria-label', 'Close navigation menu');
            document.body.classList.add('overflow-hidden');
            sidebar.querySelector('a')?.focus();
        };

        toggle.addEventListener('click', () => {
            if (toggle.getAttribute('aria-expanded') === 'true') closeMenu();
            else openMenu();
        });
        backdrop.addEventListener('click', () => closeMenu(true));
        sidebar.querySelectorAll('a').forEach(link => link.addEventListener('click', () => closeMenu()));
        document.addEventListener('keydown', event => {
            if (event.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') closeMenu(true);
        });
        window.addEventListener('resize', () => {
            if (window.innerWidth >= 768) closeMenu();
        });
    })();
    document.querySelectorAll('input[type=date],input[type=month]').forEach(i => {
        const w = document.createElement('span'); w.className = 'dw';
        i.parentNode.insertBefore(w, i); w.appendChild(i);
        w.insertAdjacentHTML('beforeend', '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>');
    });
    function makeTabs(box, current, onPick, availableCategories = []) {
        const categories = ['All', ...availableCategories];
        let tabs = [...box.querySelectorAll('button[data-category-tab]')];
        const canReuseTabs = tabs.length === categories.length
            && tabs.every((tab, index) => tab.dataset.categoryTab === categories[index]);

        if (!canReuseTabs) {
            box.replaceChildren();
            tabs = categories.map(category => {
                const button = document.createElement('button');
                button.type = 'button';
                button.dataset.categoryTab = category;
                button.textContent = category;
                box.appendChild(button);
                return button;
            });
        }

        tabs.forEach((button, index) => {
            const category = categories[index];
            button.className = 'pill' + (category === current ? ' on' : '');
            button.setAttribute('aria-pressed', String(category === current));
            button.onclick = () => onPick(category);
        });
    }
    function toast(m) { const t = document.getElementById('toast'); t.textContent = m; t.classList.remove('hidden'); setTimeout(() => t.classList.add('hidden'), 2200); }
</script>
<?= $extraScript ?? '' ?>
</body>
</html>
