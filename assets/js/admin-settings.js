function caioShowToast(message, isError) {
    isError = isError || false;
    var existingToast = document.getElementById("caio-toast-notification");
    if (existingToast) {
        existingToast.remove();
    }
    var toast = document.createElement('div');
    toast.id = 'caio-toast-notification';
    toast.className = 'caio-toast show' + (isError ? ' error' : '');
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(function() {
        if (toast) {
            toast.className = toast.className.replace('show', '');
            setTimeout(function() {
                if (toast && toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 500);
        }
    }, 3000);
}