import axios from 'axios';
import { cookie } from '@/helpers';

export default axios.create({
  timeout: 30000,
  headers: {
    'Content-type': 'application/json',
    'X-CSRF-TOKEN': cookie.getCookie('token'),
  },
});
