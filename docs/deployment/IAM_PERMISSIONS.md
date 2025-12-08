# IAM Permissions Needed for Lightsail Deployment

## Current Issue
Your IAM user `docker-wp` needs Lightsail permissions to deploy automatically.

## Quick Fix - Option 1: Attach Full Lightsail Policy (Easiest)

1. Go to AWS IAM Console: https://console.aws.amazon.com/iam/
2. Click **Users** → Find `docker-wp`
3. Click **Add permissions** → **Attach policies directly**
4. Search for: `AmazonLightsailFullAccess`
5. Check the box and click **Next** → **Add permissions**

This gives full Lightsail access (create, read, update, delete instances).

## Option 2: Custom Policy (More Secure - Recommended)

Create a custom policy with only what's needed:

1. Go to IAM → **Policies** → **Create policy**
2. Click **JSON** tab and paste:

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
                "lightsail:GetBundles"
            ],
            "Resource": "*"
        }
    ]
}
```

3. Name it: `LightsailDeploymentPolicy`
4. Click **Create policy**
5. Go back to **Users** → `docker-wp` → **Add permissions** → **Attach policies directly**
6. Search for `LightsailDeploymentPolicy` and attach it

## Minimum Permissions Needed for Deployment Script

The deployment script needs:
- `lightsail:GetInstances` - List instances
- `lightsail:GetInstance` - Get instance details (IP address)
- `lightsail:GetInstanceAccessDetails` - Get SSH connection info

For creating instances (if needed):
- `lightsail:CreateInstances` - Create new instance
- `lightsail:GetBlueprints` - List available OS images
- `lightsail:GetBundles` - List instance sizes

## After Adding Permissions

1. Wait 1-2 minutes for permissions to propagate
2. Test access:
   ```bash
   aws lightsail get-instances --region us-east-1
   ```
3. If successful, run deployment:
   ```bash
   ./scripts/deploy-lightsail.sh
   ```

## Alternative: Use AWS Console

If you prefer not to modify IAM, you can:
1. Create Lightsail instance manually in AWS Console
2. Use the manual deployment steps in `DEPLOY_TO_LIGHTSAIL.md`

