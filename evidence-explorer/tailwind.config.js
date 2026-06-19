/** @type {import('tailwindcss').Config} */
export default {
    content: ['./index.html', './src/**/*.{vue,js}'],
    darkMode: 'class',
    theme: {
        extend: {
            colors: {
                brand: {
                    600: '#4f46e5',
                    700: '#4338ca',
                },
            },
            fontFamily: {
                sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
            },
        },
    },
    plugins: [],
};
