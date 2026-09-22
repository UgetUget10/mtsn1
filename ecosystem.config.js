module.exports = {
  apps: [
    {
      name: "mtsn1-web",
      cwd: "/var/www/mtsn1/frontend",
      script: "node_modules/next/dist/bin/next",
      args: "start -p 3000",
      interpreter: "node",
      env: { NODE_ENV: "production" },
      autorestart: true,
      max_restarts: 10,
    },
  ],
};
