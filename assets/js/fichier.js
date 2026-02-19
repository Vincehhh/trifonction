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


function showNavbarSubItem (event, item) {
  const subItem = item.getElementsByTagName('ul');
  if (subItem && subItem.length) {
    subItem[0].classList.toggle('hide');
  }
}


document.addEventListener("DOMContentLoaded", function (event) {
  const navbarItems = document.querySelectorAll("#navbar-Discussions > ul > li");
  navbarItems.forEach((item) => {
    item.addEventListener('click', (e) => showNavbarSubItem(e, item))
  })

});

document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('.questionnaire-form');
    
    if (!form) return;

    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        const submitBtn = document.getElementById('submit-btn');
        const messageDiv = document.getElementById('form-message');
        const resultsDiv = document.getElementById('ai-results');
        const resultsContent = document.getElementById('results-content');

        const experience = document.getElementById('experience').value;
        const swimDistance = document.getElementById('swim-distance').value;
        const poolSize = document.getElementById('pool-size').value;

        if (!experience || !swimDistance || !poolSize) {
            showMessage(messageDiv, 'Tous les champs requis doivent être remplis', 'error');
            return;
        }

        const formData = new FormData(form);

        submitBtn.disabled = true;
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<span class="form-loading"></span> Génération en cours...';

        try {
            const response = await fetch('assets/php/gemini.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                showMessage(messageDiv, 
                    `✓ Programme généré avec succès! Appels restants: ${data.quota_remaining}`, 
                    'success'
                );

                resultsContent.textContent = data.message;
                resultsDiv.style.display = 'block';

                resultsDiv.scrollIntoView({ behavior: 'smooth', block: 'start' });

            } else {
                showMessage(messageDiv, data.error || 'Une erreur s\'est produite', 'error');
            }

        } catch (error) {
            console.error('Erreur:', error);
            showMessage(messageDiv, 'Erreur réseau. Veuillez réessayer.', 'error');

        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    });
});


function showMessage(element, text, type) {
    element.textContent = text;
    element.className = `form-message ${type}`;
    element.style.display = 'block';

    if (type === 'success') {
        setTimeout(() => {
            element.style.display = 'none';
        }, 5000);
    }
}


function closeResults() {
    const resultsDiv = document.getElementById('ai-results');
    if (resultsDiv) {
        resultsDiv.style.display = 'none';
    }
}
