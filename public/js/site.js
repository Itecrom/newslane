const clock = document.getElementById('local-clock');

const themeToggle = document.querySelector('[data-theme-toggle]');
let savedTheme = null;

try {
    savedTheme = window.localStorage.getItem('newslane-theme');
} catch (error) {
    console.warn('Unable to read the saved NEWSLANE theme preference.', error);
}

const applyTheme = (theme) => {
    document.documentElement.dataset.theme = theme;

    if (themeToggle) {
        const isDark = theme === 'dark';
        themeToggle.setAttribute('aria-pressed', isDark ? 'true' : 'false');
        themeToggle.setAttribute('aria-label', `Enable ${isDark ? 'light' : 'dark'} mode`);
        themeToggle.querySelector('[data-theme-icon]').textContent = isDark ? '☀' : '◐';
        themeToggle.querySelector('.theme-toggle-label').textContent = isDark ? 'Light mode' : 'Dark mode';
    }
};

applyTheme(savedTheme === 'dark' || savedTheme === 'light'
    ? savedTheme
    : (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'));

themeToggle?.addEventListener('click', () => {
    const theme = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
    applyTheme(theme);

    try {
        window.localStorage.setItem('newslane-theme', theme);
    } catch (error) {
        console.warn('Unable to save the NEWSLANE theme preference.', error);
    }
});

if (clock) {
    const updateClock = () => {
        clock.textContent = new Intl.DateTimeFormat('en-GB', {
            timeZone: 'Africa/Blantyre',
            weekday: 'short',
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            hourCycle: 'h23',
        }).format(new Date()).replace(',', ' ·');
    };

    updateClock();
    window.setInterval(updateClock, 60_000);
}

let weatherWidget = document.querySelector('[data-weather-refresh-url]');

if (weatherWidget) {
    const refreshWeather = async () => {
        if (document.hidden) {
            return;
        }

        try {
            const response = await fetch(weatherWidget.dataset.weatherRefreshUrl, {
                headers: { Accept: 'text/html' },
                cache: 'no-store',
            });

            if (!response.ok) {
                throw new Error(`Weather refresh returned ${response.status}`);
            }

            const page = new DOMParser().parseFromString(await response.text(), 'text/html');
            const replacement = page.querySelector('[data-weather-refresh-url]');

            if (!replacement) {
                throw new Error('Weather refresh did not return the forecast widget.');
            }

            replacement.open = weatherWidget.open;
            weatherWidget.replaceWith(replacement);
            weatherWidget = replacement;
        } catch (error) {
            console.error('Unable to refresh the live weather forecast.', error);
        }
    };

    window.setInterval(refreshWeather, 600_000);
}

document.querySelectorAll('[data-share-url]').forEach((button) => {
    button.addEventListener('click', async () => {
        const shareData = { title: button.dataset.shareTitle, url: button.dataset.shareUrl };

        if (navigator.share) {
            await navigator.share(shareData);
            return;
        }

        try {
            await navigator.clipboard.writeText(shareData.url);
            const originalText = button.textContent;
            button.textContent = 'Link copied';
            window.setTimeout(() => { button.textContent = originalText; }, 1800);
        } catch {
            window.prompt('Copy this story link:', shareData.url);
        }
    });
});

document.querySelectorAll('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (!window.confirm(form.dataset.confirm)) {
            event.preventDefault();
        }
    });
});

const liveTimeline = document.querySelector('[data-live-updates-url]');

if (liveTimeline) {
    const refreshStatus = document.querySelector('.live-refresh-status');

    const pollLiveUpdates = async () => {
        const url = new URL(liveTimeline.dataset.liveUpdatesUrl, window.location.origin);
        url.searchParams.set('after', liveTimeline.dataset.lastUpdateId || '0');

        const response = await fetch(url, {
            headers: { Accept: 'application/json' },
            cache: 'no-store',
        });

        if (!response.ok) {
            throw new Error(`Live updates returned ${response.status}`);
        }

        const { updates } = await response.json();

        for (const update of updates) {
            const item = document.createElement('article');
            item.className = 'live-update';
            item.dataset.updateId = update.id;

            const timestamp = document.createElement('time');
            timestamp.dateTime = update.created_at;
            timestamp.append(`${update.time} CAT`);
            const date = document.createElement('span');
            date.textContent = update.date;
            timestamp.append(date);

            const content = document.createElement('div');
            const marker = document.createElement('span');
            marker.className = 'timeline-dot';
            const paragraph = document.createElement('p');
            paragraph.textContent = update.body;
            const byline = document.createElement('small');
            byline.textContent = `NEWSLANE · ${update.author}`;
            paragraph.append(byline);
            content.append(marker, paragraph);
            item.append(timestamp, content);
            liveTimeline.append(item);
            liveTimeline.dataset.lastUpdateId = update.id;
        }

        if (refreshStatus) {
            refreshStatus.textContent = 'Updates refresh automatically · just now.';
        }
    };

    const refresh = () => {
        pollLiveUpdates().catch((error) => {
            console.error('Unable to refresh live coverage.', error);
            if (refreshStatus) {
                refreshStatus.textContent = 'Live updates are temporarily unavailable. Please refresh the page.';
            }
        });
    };

    window.setInterval(refresh, 15_000);
}

document.querySelectorAll('[data-carousel]').forEach((carousel) => {
    const slides = Array.from(carousel.querySelectorAll('[data-carousel-slide]'));
    const dots = Array.from(carousel.querySelectorAll('[data-carousel-dot]'));

    if (slides.length < 2) {
        return;
    }

    let active = 0;
    let timer;

    const show = (next) => {
        active = (next + slides.length) % slides.length;

        slides.forEach((slide, index) => {
            slide.hidden = index !== active;
            slide.classList.toggle('is-active', index === active);
            slide.setAttribute('aria-hidden', index === active ? 'false' : 'true');
        });

        dots.forEach((dot, index) => {
            dot.setAttribute('aria-pressed', index === active ? 'true' : 'false');
        });
    };

    const stop = () => window.clearInterval(timer);
    const start = () => {
        stop();
        if (!document.hidden && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            timer = window.setInterval(() => show(active + 1), Number(carousel.dataset.carouselInterval) || 7000);
        }
    };

    carousel.querySelector('[data-carousel-prev]')?.addEventListener('click', () => { show(active - 1); start(); });
    carousel.querySelector('[data-carousel-next]')?.addEventListener('click', () => { show(active + 1); start(); });
    dots.forEach((dot) => dot.addEventListener('click', () => {
        show(Number(dot.dataset.carouselDot));
        start();
    }));

    carousel.addEventListener('mouseenter', stop);
    carousel.addEventListener('mouseleave', start);
    carousel.addEventListener('focusin', stop);
    carousel.addEventListener('focusout', start);
    document.addEventListener('visibilitychange', start);
    start();
});

document.querySelectorAll('[data-contact-type]').forEach((select) => {
    const form = select.form;

    const updateSections = () => {
        form.querySelectorAll('[data-contact-section]').forEach((section) => {
            const isVisible = section.dataset.contactSection === select.value;
            section.hidden = !isVisible;
            section.querySelectorAll('input, select, textarea').forEach((input) => {
                input.disabled = !isVisible;
            });
        });

        form.querySelectorAll('[data-contact-message-label] textarea').forEach((textarea) => {
            textarea.placeholder = select.value === 'news'
                ? 'Describe your original news tip or submission; include what happened, where and when.'
                : 'Tell us what we need to know (20–5,000 characters).';
        });
    };

    select.addEventListener('change', updateSections);
    updateSections();
});
