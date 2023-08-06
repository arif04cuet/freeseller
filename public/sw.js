// Cached core static resources
self.addEventListener("install", e => {
    e.waitUntil(caches.open('app'));
});

// Fatch resources
self.addEventListener("fetch", e => {
    e.respondWith(
        fetch(request).then(function (response) {
            return response;
        }).catch(function (error) {
            return caches.match(request).then(function (response) {
                return response;
            });
        })
    );
});

self.addEventListener('push', function (e) {
    if (!(self.Notification && self.Notification.permission === 'granted')) {
        //notifications aren't supported or permission not granted!
        return;
    }

    if (e.data) {
        var msg = e.data.json();
        console.log(msg)
        e.waitUntil(self.registration.showNotification(msg.title, {
            body: msg.body,
            icon: msg.icon,
            actions: msg.actions
        }));
    }
});
