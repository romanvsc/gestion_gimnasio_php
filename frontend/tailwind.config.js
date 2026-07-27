/** @type {import('tailwindcss').Config} */
export default {
  content: [
    './index.html',
    './src/**/*.{vue,js,jsx}',
  ],
  theme: {
    extend: {
      colors: {
        forest: { 0: '#151311', 900: '#151311', 800: '#241F1B', 700: '#332B25', 100: '#EED3BA' },
        slate:  { 0: '#4B262F', 800: '#351B22', 100: '#E8CDD2' },
        timber: { 0: '#EED3BA', 800: '#4B262F', 100: '#F7E8D8' },
        paper:  { 0: '#F8F3EC', 100: '#EFE7DE', 200: '#DCCDBC', 300: '#C9B9A7' },
        ink:    { 0: '#151311', 700: '#2A2420', 500: '#6F5D55' },
        lime:   { DEFAULT: '#EED3BA', dark: '#4B262F' },
        dark:   { DEFAULT: '#151311', 900: '#151311', 800: '#241F1B', 700: '#2A2420', 600: '#6F5D55', 500: '#9B8578' },
        glass:  { DEFAULT: '#F8F3EC', border: '#C9B9A7' },
      },
      fontFamily: {
        heading: ['Inter', 'sans-serif'],
        body:    ['Inter', 'sans-serif'],
      },
      backdropBlur: {
        xs: '2px',
      },
    },
  },
  plugins: [],
}
