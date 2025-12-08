#!/bin/bash
# Check deployment status on Lightsail

SSH_KEY="${1:-/Users/sv/_MYUI/Dev/LightsailDefaultKey-eu-west-2.pem}"
INSTANCE_IP="${2:-18.130.255.19}"
SSH_USER="${3:-ubuntu}"

echo "=== Deployment Status Check ==="
echo ""

# Check if deployment is running
echo "Checking deployment process..."
ssh -i "$SSH_KEY" -o StrictHostKeyChecking=no "$SSH_USER@$INSTANCE_IP" << 'ENDSSH'
    if [ -f /tmp/deploy.pid ]; then
        PID=$(cat /tmp/deploy.pid)
        if ps -p $PID > /dev/null 2>&1; then
            echo "✓ Deployment process running (PID: $PID)"
        else
            echo "⚠ Deployment process finished"
        fi
    else
        echo "? Deployment PID file not found"
    fi
    
    echo ""
    echo "=== Latest Log (last 10 lines) ==="
    if [ -f /tmp/deploy.log ]; then
        tail -10 /tmp/deploy.log
    else
        echo "Log file not found yet"
    fi
    
    echo ""
    echo "=== Docker Services Status ==="
    cd /opt/wordpress-multisite 2>/dev/null || echo "Directory not ready"
    sudo docker-compose ps 2>&1 || echo "Services not started yet"
    
    echo ""
    echo "=== Docker Build Status ==="
    sudo docker ps -a 2>&1 | head -5 || echo "No containers"
ENDSSH

echo ""
echo "=== To monitor live ==="
echo "ssh -i $SSH_KEY $SSH_USER@$INSTANCE_IP 'tail -f /tmp/deploy.log'"

