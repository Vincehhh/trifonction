// Créer un cookie
function setCookie(name, value, days) {
    let expires = "";
    if(days) {
        const date = new Date();
        date.setTime(date.getTime() + (days*24*60*60*1000));
        expires = "; expires=" + date.toUTCString();
    }
    document.cookie = name + "=" + (value || "") + expires + "; path=/";
}

// Lire un cookie
function getCookie(name) {
    const nameEQ = name + "=";
    const ca = document.cookie.split(';');
    for(let i=0;i<ca.length;i++) {
        let c = ca[i].trim();
        if(c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length);
    }
    return null;
}

// Gérer l'affichage de la bannière
document.addEventListener('DOMContentLoaded', () => {
    const banner = document.getElementById('cookie-banner');
    const acceptBtn = document.getElementById('accept-cookies');

    // Si cookie déjà accepté, cacher la bannière
    if(getCookie('cookiesAccepted')) {
        banner.style.display = 'none';
    }

    // Cliquer pour accepter
    acceptBtn.addEventListener('click', () => {
        setCookie('cookiesAccepted', 'true', 365); // expire dans 1 an
        banner.style.display = 'none';
    });
});
