/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./public/**/*.html",
    "./public/**/*.js",
    "./server/**/*.js"
  ],
  theme: {
    extend: {
      colors: {
        primary: {
          50: '#eef2ff',
          100: '#e0e7ff',
          500: '#4361ee',
          600: '#3a56d4',
          700: '#3748c9',
          900: '#1e1b4b',
        },
        secondary: {
          500: '#4cc9f0',
        },
        accent: {
          500: '#f72585',
          600: '#e3277e',
        },
        success: {
          500: '#06d6a0',
        },
        warning: {
          500: '#ffd166',
        },
        danger: {
          500: '#ef476f',
        }
      },
      fontFamily: {
        'inter': ['Inter', 'system-ui', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'Roboto', 'Helvetica Neue', 'Arial', 'sans-serif'],
      },
      boxShadow: {
        'card': '0 4px 20px rgba(67, 97, 238, 0.1)',
        'header': '0 4px 20px rgba(67, 97, 238, 0.15)',
      },
      backgroundImage: {
        'gradient-primary': 'linear-gradient(135deg, #4361ee 0%, #4cc9f0 100%)',
        'gradient-button': 'linear-gradient(135deg, #4361ee 0%, #3a56d4 100%)',
      }
    },
  },
  plugins: [],
}