# Quick Deploy to London Instance

## ✅ Instance Created!
- **Name**: wordpress-multisite
- **IP**: 18.130.255.19
- **Region**: London (eu-west-2)

## 🚀 Deploy in 3 Steps:

### Step 1: Download SSH Key
1. Open: https://lightsail.aws.amazon.com/ls/webapp/eu-west-2/instances/wordpress-multisite/connect
2. Click "Download default key" or "Connect using SSH"
3. Save the key file (usually `LightsailDefaultKeyPair-wordpress-multisite.pem`)

### Step 2: Set Key Permissions
```bash
chmod 400 ~/Downloads/LightsailDefaultKeyPair-wordpress-multisite.pem
```

### Step 3: Run Deployment
```bash
./scripts/deploy-to-instance.sh wordpress-multisite 18.130.255.19 ubuntu ~/Downloads/LightsailDefaultKeyPair-wordpress-multisite.pem
```

Or if key is in default location:
```bash
./scripts/deploy-to-instance.sh wordpress-multisite 18.130.255.19 ubuntu
```

## What Happens:
1. ✅ Installs Docker & Docker Compose
2. ✅ Uploads WordPress files
3. ✅ Configures environment
4. ✅ Starts all services
5. ✅ WordPress available at http://18.130.255.19

## After Deployment:
1. Open http://18.130.255.19 in browser
2. Complete WordPress installation
3. Enable multisite network
4. Configure firewall (ports 80, 443) in Lightsail console

