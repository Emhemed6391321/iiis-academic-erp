/** @type {import('tailwindcss').Config} */
module.exports = {
  darkMode: 'class',
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
    "./public/**/*.js",
    "./public/**/*.html",
  ],
  theme: {
    extend: {
      fontFamily: {
        sans: ['Cairo', 'IBM Plex Sans Arabic', 'Plus Jakarta Sans', 'system-ui', 'sans-serif'],
        mono: ['Courier Prime', 'monospace'],
      },
      colors: {
        primary: {
          DEFAULT: '#2b78a5',
          dark: '#14268d',
          50: '#f0f7fb',
          100: '#e0eff7',
          200: '#c1dfef',
          300: '#92c7e3',
          400: '#5ba9d3',
          500: '#2b78a5',
          600: '#24658d',
          700: '#1d5273',
          800: '#17415b',
          900: '#14268d',
          950: '#0b164f',
        },
        brand: {
          50: '#f0f7fb',
          100: '#dbeff7',
          500: '#2b78a5',
          600: '#1e5e84',
          700: '#184764',
          900: '#14268d',
        },
        andalusian: {
          blue: '#2b78a5',
          navy: '#14268d',
          dark: '#0d1838',
          gold: '#d97706',
          goldLight: '#f59e0b',
        },
        islamic: {
          50: '#f4f9f6',
          500: '#1e824c',
          600: '#16693d',
          700: '#10522e',
        },
        gold: {
          100: '#fef3c7',
          300: '#fcd34d',
          500: '#f59e0b',
          600: '#d97706',
          700: '#b45309',
        },
        navy: {
          800: '#14268d',
          900: '#0f172a',
          950: '#020617',
        },
        burgundy: {
          700: '#9d174d',
          800: '#831843',
          900: '#500724',
          950: '#330317',
        },
      },
      borderRadius: {
        'main': '20px',
        'sm-custom': '12px',
      },
      boxShadow: {
        'soft': '0 12px 24px rgba(15, 23, 42, 0.06)',
        'hover': '0 18px 32px rgba(15, 23, 42, 0.10)',
        'glow': '0 0 35px -5px rgba(43, 120, 165, 0.25)',
        'glow-lg': '0 0 50px -10px rgba(20, 38, 141, 0.35)',
        'card': '0 20px 40px -15px rgba(15, 23, 42, 0.08), 0 0 0 1px rgba(226, 232, 240, 0.8)',
        'sticky-rtl': '-6px 0 16px -4px rgba(0, 0, 0, 0.08)',
        'bottom-sheet': '0 -10px 25px -5px rgba(0, 0, 0, 0.2)',
      },
    },
  },
  plugins: [],
}
