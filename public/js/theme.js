(function () {
    const storageKey = 'kulaza_theme';
    let lightMode = false;

    try {
        lightMode = localStorage.getItem(storageKey) === 'light';
    } catch (error) {
        console.error('Unable to read the saved theme preference.', error);
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
        document.querySelectorAll('[data-theme-toggle]').forEach(button => {
            button.addEventListener('click', () => updateTheme(!lightMode, true));
        });
        updateTheme(lightMode, false);
    });
})();
