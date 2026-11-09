const clock = document.getElementById('local-clock');

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
