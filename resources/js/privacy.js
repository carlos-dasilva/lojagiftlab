const key = 'giftlab_privacy_v1';
const banner = document.querySelector('[data-cookie-banner]');
const dialog = document.querySelector('[data-cookie-dialog]');
let preferences;
try { preferences = JSON.parse(localStorage.getItem(key) || 'null'); } catch { preferences = null; }
const placeholder = () => {
    const box = document.createElement('div'); box.className = 'consent-placeholder';
    const text = document.createElement('p'); text.textContent = 'Permita mídia externa para assistir ao vídeo.';
    const button = document.createElement('button'); button.type = 'button'; button.dataset.cookieSettings = ''; button.textContent = 'Configurar privacidade';
    box.append(text, button); return box;
};
window.GiftLabMedia = (stage, source, title, video) => {
    stage.replaceChildren();
    stage.dataset.currentVideo = video ? source : '';
    stage.dataset.currentTitle = title;
    if (video && !preferences?.media) { stage.append(placeholder()); return; }
    const media = document.createElement(video ? 'iframe' : 'img');
    media.src = source;
    if (video) { media.title = title; media.allowFullscreen = true; } else media.alt = title;
    stage.append(media);
};
const refresh = () => {
    if (banner) banner.hidden = preferences !== null;
    document.querySelectorAll('[data-external-thumbnail]').forEach(img => {
        if (preferences?.media) img.src = img.dataset.externalThumbnail;
        else img.removeAttribute('src');
    });
    document.querySelectorAll('[data-media-stage]').forEach(stage => {
        const source = stage.dataset.currentVideo || stage.dataset.initialVideo;
        if (source) window.GiftLabMedia(stage, source, stage.dataset.currentTitle || stage.dataset.initialTitle || 'Vídeo', true);
        delete stage.dataset.initialVideo;
    });
};
const save = media => {
    preferences = { media: Boolean(media) };
    try { localStorage.setItem(key, JSON.stringify(preferences)); } catch {}
    if (dialog) dialog.hidden = true;
    refresh();
};
document.addEventListener('click', event => {
    if (event.target.closest('[data-cookie-settings]') && dialog) {
        dialog.hidden = false;
        dialog.querySelector('[data-cookie-media]').checked = Boolean(preferences?.media);
        dialog.querySelector('input:not([disabled])').focus();
    }
    if (event.target.closest('[data-cookie-accept]')) save(true);
    if (event.target.closest('[data-cookie-reject]')) save(false);
    if (event.target.closest('[data-cookie-save]')) save(dialog.querySelector('[data-cookie-media]').checked);
});
refresh();
