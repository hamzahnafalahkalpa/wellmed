/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'media', // ⬅️ penting
    content: [
        './resources/**/*.blade.php',
        './projects/**/src/Resources/Views/**/*.blade.php',
    ],
    important: false,
    presets: [
        require('./tailwind.preset.js'),
    ],
};
