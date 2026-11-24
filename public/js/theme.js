(function () {
    const storageKey = 'kulaza_theme';
    let lightMode = false;

    try {
        lightMode = localStorage.getItem(storageKey) === 'light';
    } catch (error) {
        console.error('Unable to read the saved theme preference.', error);
    }

    function ensureAuthScenery() {
        if (!document.body || !document.body.classList.contains('auth-scene-page')) return;

        const dayLandscape = document.createElement('div');
        dayLandscape.className = 'day-landscape';
        dayLandscape.setAttribute('aria-hidden', 'true');
        dayLandscape.innerHTML = [
            '<span class="day-sun"></span>',
            '<span class="day-cloud day-cloud-one"></span>',
            '<span class="day-cloud day-cloud-two"></span>',
            '<span class="day-cloud day-cloud-three"></span>',
            '<span class="day-hill day-hill-back"></span>',
            '<span class="day-hill day-hill-middle"></span>',
            '<span class="day-hill day-hill-front"></span>',
            '<span class="day-animal day-dog"><span class="day-creature">🐕</span></span>',
            '<span class="day-animal day-cat"><span class="day-creature">🐈</span></span>',
            '<span class="day-animal day-bunny"><span class="day-creature">🐇</span></span>',
            '<span class="day-animal day-hamster"><span class="day-creature">🐹</span></span>'
        ].join('');
        document.body.appendChild(dayLandscape);

        const nightLandscape = document.createElement('div');
        nightLandscape.className = 'night-landscape';
        nightLandscape.setAttribute('aria-hidden', 'true');
        nightLandscape.innerHTML = [
            '<div class="night-sky"><span class="night-moon">☾</span><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i></div>',
            '<div class="night-pet-landscape">',
            '<span class="night-hill night-hill-back"></span>',
            '<span class="night-hill night-hill-front"></span>',
            '<span class="night-animal night-dog"><span class="sleep-pillow"></span><span class="night-pet">🐕</span><span class="sleep-zzz">Zzz</span></span>',
            '<span class="night-animal night-cat"><span class="sleep-pillow"></span><span class="night-pet">🐈</span><span class="sleep-zzz">Zzz</span></span>',
            '<span class="night-animal night-bunny"><span class="sleep-pillow"></span><span class="night-pet">🐇</span><span class="sleep-zzz">Zzz</span></span>',
            '<span class="night-animal night-hamster"><span class="sleep-pillow"></span><span class="night-pet">🐹</span><span class="sleep-zzz">Zzz</span></span>',
            '</div>'
        ].join('');
        document.body.appendChild(nightLandscape);
    }

    function updateTheme(enabled, save) {
        lightMode = enabled;
        document.documentElement.classList.toggle('light-mode', lightMode);

        document.querySelectorAll('[data-theme-toggle]').forEach(button => {
            button.setAttribute('aria-pressed', String(lightMode));
            button.setAttribute('aria-label', `Switch to ${lightMode ? 'dark' : 'light'} mode`);
            button.querySelector('[data-theme-icon="sun"]').classList.toggle('is-active', lightMode);
            button.querySelector('[data-theme-icon="moon"]').classList.toggle('is-active', !lightMode);
        });

        if (save) {
            try {
                localStorage.setItem(storageKey, lightMode ? 'light' : 'dark');
            } catch (error) {
                console.error('Unable to save the theme preference.', error);
            }
        }
    }

    updateTheme(lightMode, false);
    document.addEventListener('DOMContentLoaded', () => {
        ensureAuthScenery();
        document.querySelectorAll('[data-theme-toggle]').forEach(button => {
            button.addEventListener('click', () => updateTheme(!lightMode, true));
        });
        updateTheme(lightMode, false);
    });
})();
