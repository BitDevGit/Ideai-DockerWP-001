# IAM Permissions for Lightsail Deployment

Required AWS IAM permissions for deploying to Lightsail.

## Policy Definition

The policy is defined in `lightsail-policy.json`:

```json
{
    "Version": "2012-10-17",
    "Statement": [
        {
            "Effect": "Allow",
            "Action": [
                "lightsail:GetInstances",
                "lightsail:GetInstance",
                "lightsail:CreateInstances",
                "lightsail:DeleteInstance",
                "lightsail:StartInstance",
                "lightsail:StopInstance",
                "lightsail:RebootInstance",
                "lightsail:GetInstanceAccessDetails",
                "lightsail:GetInstancePortStates",
                "lightsail:OpenInstancePublicPorts",
                "lightsail:CloseInstancePublicPorts",
                "lightsail:UpdateInstanceMetadataOptions",
                "lightsail:GetInstanceSnapshots",
                "lightsail:CreateInstanceSnapshot",
                "lightsail:GetRegions",
                "lightsail:GetBlueprints",
                "lightsail:GetBundles",
                "lightsail:GetDomains",
                "lightsail:GetDomain",
                "lightsail:CreateDomain",
                "lightsail:DeleteDomain",
                "lightsail:GetLoadBalancers",
                "lightsail:GetLoadBalancer",
                "lightsail:CreateLoadBalancer",
                "lightsail:GetDistributions",
                "lightsail:GetDistribution"
            ],
            "Resource": "*"
        }
    ]
}
```

## Creating the Policy

### Using AWS CLI

```bash
aws iam create-policy \
  --policy-name LightsailDeploymentPolicy \
  --policy-document file://lightsail-policy.json \
  --description "Permissions for WordPress Lightsail deployment"
```

### Using AWS Console

1. Go to IAM → Policies
2. Click "Create policy"
3. Select JSON tab
4. Paste policy from `lightsail-policy.json`
5. Name: `LightsailDeploymentPolicy`
6. Create policy

## Attaching to User

### Using AWS CLI

```bash
# Get your account ID
ACCOUNT_ID=$(aws sts get-caller-identity --query Account --output text)

# Attach policy to user
aws iam attach-user-policy \
  --user-name YOUR_USERNAME \
  --policy-arn arn:aws:iam::${ACCOUNT_ID}:policy/LightsailDeploymentPolicy
```

### Using AWS Console

1. Go to IAM → Users
2. Select your user
3. Click "Add permissions"
4. Select "Attach policies directly"
5. Search for `LightsailDeploymentPolicy`
6. Attach

## Verifying Permissions

```bash
# Test permissions
aws lightsail get-instances --region eu-west-2

# Should return list of instances (or empty array if none)
```

## Required Permissions Explained

- **GetInstances/GetInstance**: List and view instance details
- **CreateInstances**: Create new Lightsail instances
- **StartInstance/StopInstance/RebootInstance**: Control instance state
- **GetInstanceAccessDetails**: Get SSH access information
- **GetInstancePortStates**: Check firewall rules
- **OpenInstancePublicPorts/CloseInstancePublicPorts**: Manage firewall
- **GetRegions/GetBlueprints/GetBundles**: Get available options
- **GetDomains/CreateDomain**: Domain management
- **GetLoadBalancers/CreateLoadBalancer**: Load balancer management
- **GetDistributions/GetDistribution**: CloudFront distribution access

## Security Best Practices

1. **Principle of Least Privilege**: Only grant necessary permissions
2. **Use IAM Users**: Don't use root account
3. **Rotate Credentials**: Regularly rotate access keys
4. **Monitor Usage**: Check CloudTrail for access logs

## Troubleshooting

### Error: AccessDeniedException

**Solution:**
- Verify policy is attached
- Check policy ARN is correct
- Ensure user has correct permissions

### Error: Policy doesn't exist

**Solution:**
- Create policy first
- Verify policy name matches
- Check region (some services are region-specific)
