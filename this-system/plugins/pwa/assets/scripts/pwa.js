if ('serviceWorker' in navigator && 'PushManager' in window)
{
  window.addEventListener('load', function()
  {
    navigator.serviceWorker.register('/service-worker.js')
      .then(function(registration) {
        // console.log('Service Worker registrado com sucesso:', registration);
      })
      .catch(function(error) {
        // console.log('Falha ao registrar o Service Worker:', error);
      });

    var deferredPrompt;
    var installButtons = document.querySelectorAll('[data-install-pwa]');

    window.addEventListener('beforeinstallprompt', function(event)
    {
      event.preventDefault();
      deferredPrompt = event;

      installButtons.forEach(function(button)
      {
        button.style.display = 'block';
        button.addEventListener('click', function()
        {
          deferredPrompt.prompt();
          deferredPrompt.userChoice
            .then(function(choiceResult)
            {
              if (choiceResult.outcome === 'accepted') {
                // console.log('Usuário aceitou a instalação do PWA');
              } else {
                // console.log('Usuário rejeitou a instalação do PWA');
              }
              deferredPrompt = null;
            });
        });
      });
    });

    window.addEventListener('appinstalled', function(event) {
      console.log('PWA instalado com sucesso');
      //localStorage.setItem('pwaInstalled', 'yes');
    });
  });
}
