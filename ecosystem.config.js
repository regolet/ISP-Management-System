module.exports = {
  apps: [{
    name: 'isp',
    script: 'server/app.js',
    instances: 1,
    autorestart: true,
    watch: false,
    max_memory_restart: '1G',
    env: {
      NODE_ENV: 'production',
      PORT: 3000
    },
    error_file: './logs/err.log',
    out_file: './logs/out.log',
    log_file: './logs/combined.log',
    time: true,
    // Restart settings
    min_uptime: '10s',
    max_restarts: 10,
    restart_delay: 5000,
    // Auto restart on crash
    exp_backoff_restart_delay: 100,
    // Process monitoring
    listen_timeout: 8000,
    kill_timeout: 5000,
    // Windows specific
    windowsHide: true
  }]
};