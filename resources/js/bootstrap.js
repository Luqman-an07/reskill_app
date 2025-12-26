import axios from 'axios';
import './echo';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.Echo.connector.pusher.connection.bind('message', (payload) => {
    console.log('Fungsi Debug Reverb:', payload);
});