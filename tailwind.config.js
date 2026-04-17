import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Sora', 'Poppins', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                green: {
                    50:  '#f0fdf4',
                    100: '#d1fae5',
                    200: '#a7f3d0',
                    300: '#6ee7b7',
                    400: '#34d399',
                    500: '#2ecc71',
                    600: '#27ae60',
                    700: '#1e8449',
                    800: '#145a32',
                    900: '#0e4d25',
                    950: '#052e16',
                },
            },
        },
    },

    plugins: [forms],
};
