#!/bin/bash
set -e

# CloudFront CDN Setup Script
# This script helps set up CloudFront distribution for WordPress multisite

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

ORIGIN_DOMAIN="${ORIGIN_DOMAIN:-yourdomain.com}"
CDN_DOMAIN="${CDN_DOMAIN:-cdn.yourdomain.com}"
REGION="${AWS_REGION:-us-east-1}"

echo -e "${GREEN}Setting up CloudFront CDN for WordPress Multisite...${NC}"

# Check if AWS CLI is installed
if ! command -v aws &> /dev/null; then
    echo -e "${RED}AWS CLI is not installed. Please install it first.${NC}"
    exit 1
fi

# Create CloudFront distribution configuration
cat > cloudfront-config.json << EOF
{
  "CallerReference": "wordpress-multisite-$(date +%s)",
  "Comment": "WordPress Multisite CDN Distribution",
  "DefaultCacheBehavior": {
    "TargetOriginId": "wordpress-origin",
    "ViewerProtocolPolicy": "redirect-to-https",
    "AllowedMethods": {
      "Quantity": 7,
      "Items": ["GET", "HEAD", "OPTIONS", "PUT", "POST", "PATCH", "DELETE"],
      "CachedMethods": {
        "Quantity": 2,
        "Items": ["GET", "HEAD"]
      }
    },
    "ForwardedValues": {
      "QueryString": true,
      "Cookies": {
        "Forward": "all"
      },
      "Headers": {
        "Quantity": 1,
        "Items": ["Host"]
      }
    },
    "MinTTL": 0,
    "DefaultTTL": 86400,
    "MaxTTL": 31536000,
    "Compress": true
  },
  "Origins": {
    "Quantity": 1,
    "Items": [
      {
        "Id": "wordpress-origin",
        "DomainName": "$ORIGIN_DOMAIN",
        "CustomOriginConfig": {
          "HTTPPort": 443,
          "HTTPSPort": 443,
          "OriginProtocolPolicy": "https-only",
          "OriginSslProtocols": {
            "Quantity": 1,
            "Items": ["TLSv1.2"]
          }
        }
      }
    ]
  },
  "Enabled": true,
  "PriceClass": "PriceClass_100"
}
EOF

echo -e "${YELLOW}CloudFront configuration created.${NC}"
echo -e "${YELLOW}To create the distribution, run:${NC}"
echo -e "aws cloudfront create-distribution --distribution-config file://cloudfront-config.json --region $REGION"

echo -e "\n${GREEN}After creating the distribution:${NC}"
echo -e "1. Update your DNS to point $CDN_DOMAIN to the CloudFront distribution"
echo -e "2. Update WordPress settings to use $CDN_DOMAIN for static assets"
echo -e "3. Configure SSL certificate for $CDN_DOMAIN in CloudFront"

