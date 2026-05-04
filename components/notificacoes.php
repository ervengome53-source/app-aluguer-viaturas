<?php
// components/notificacoes.php
function showNotification($message, $type = 'success') {
    echo "
    <div class='notification {$type}' id='notification'>
        {$message}
    </div>
    <script>
        setTimeout(() => {
            const notification = document.getElementById('notification');
            if(notification) notification.remove();
        }, 3000);
    </script>
    ";
}
?>