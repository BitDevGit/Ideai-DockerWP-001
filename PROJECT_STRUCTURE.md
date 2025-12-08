# Project Structure

Complete file structure and organization.

## Directory Tree

```
Ideai-DockerWP-001/
├── README.md                      # Main project documentation
├── PROJECT_STRUCTURE.md           # This file
├── Makefile                       # Development commands
├── .gitignore                     # Git ignore rules
├── .env.example                   # Environment variables template
│
├── docker-compose.yml             # Main Docker Compose configuration
│
├── docs/                          # Documentation
│   ├── deployment/                # Deployment guides
│   │   ├── QUICKSTART.md          # Quick start guide
│   │   ├── DEPLOYMENT.md          # Full deployment guide
│   │   ├── LIGHTSAIL.md          # AWS Lightsail deployment
│   │   └── IAM_PERMISSIONS.md     # IAM permissions guide
│   ├── troubleshooting/           # Troubleshooting
│   │   └── TROUBLESHOOTING.md     # Common issues and solutions
│   └── architecture/              # Architecture docs
│       └── SCALING.md             # Scaling strategies
│
├── nginx/                         # Nginx configuration
│   ├── nginx.conf                 # Main Nginx configuration
│   └── conf.d/                    # Site configurations
│       ├── default.conf           # Default site config
│       └── default.conf.production # Production config
│
├── wordpress/                     # WordPress configuration
│   ├── Dockerfile                 # Custom WordPress image (optional)
│   ├── php.ini                    # PHP configuration
│   ├── uploads.ini                # PHP upload settings
│   └── configure-multisite.sh    # Multisite setup script
│
├── scripts/                        # Automation scripts
│   ├── deployment/                # Deployment scripts
│   │   ├── create-and-deploy-london.sh
│   │   ├── deploy-to-instance.sh
│   │   ├── deploy-with-cli.sh
│   │   ├── deploy-background.sh
│   │   ├── deploy-fast.sh
│   │   ├── deploy-minimal.sh
│   │   ├── deploy-simple.sh
│   │   ├── prepare-deployment.sh
│   │   └── setup-cloudfront.sh
│   ├── maintenance/               # Maintenance scripts
│   │   ├── health-check.sh
│   │   └── init-ssl.sh
│   └── backup/                    # Backup/restore scripts
│       ├── backup.sh
│       └── restore.sh
│
├── .github/                       # GitHub configuration
│   └── workflows/
│       └── deploy.yml             # CI/CD pipeline
│
├── lightsail-policy.json         # IAM policy for Lightsail
│
└── logs/                          # Application logs
    └── nginx/
        ├── access.log
        └── error.log
```

## File Descriptions

### Core Files

- **README.md**: Main project documentation and quick start
- **docker-compose.yml**: Docker Compose configuration for all services
- **Makefile**: Common development commands
- **.env.example**: Template for environment variables

### Documentation (`docs/`)

- **deployment/**: Deployment guides for different environments
- **troubleshooting/**: Common issues and solutions
- **architecture/**: Architecture and scaling documentation

### Configuration

- **nginx/**: Nginx reverse proxy configuration
- **wordpress/**: WordPress and PHP configuration
- **.github/workflows/**: CI/CD pipeline configuration

### Scripts (`scripts/`)

Organized by purpose:
- **deployment/**: Scripts for deploying to various environments
- **maintenance/**: Health checks and maintenance tasks
- **backup/**: Backup and restore operations

### AWS Configuration

- **lightsail-policy.json**: IAM policy for AWS Lightsail access

## Naming Conventions

### Files
- **UPPERCASE.md**: Main documentation files
- **lowercase.sh**: Shell scripts
- **kebab-case.yml**: Configuration files

### Directories
- **lowercase/**: Standard directories
- **kebab-case/**: Multi-word directories

## Semantic Organization

### By Purpose
- **docs/**: All documentation
- **scripts/**: All automation scripts
- **nginx/**: All Nginx configs
- **wordpress/**: All WordPress configs

### By Environment
- **default.conf**: Development config
- **default.conf.production**: Production config

### By Function
- **deployment/**: Deployment-related scripts
- **maintenance/**: Maintenance scripts
- **backup/**: Backup scripts

## Best Practices

1. **Keep related files together**: All deployment scripts in `scripts/deployment/`
2. **Document everything**: Each major component has documentation
3. **Use semantic names**: File names describe their purpose
4. **Organize by function**: Group files by what they do, not where they're used
5. **Version control**: Keep configs in git, secrets in `.env`

## Maintenance

When adding new files:
1. Place in appropriate directory
2. Follow naming conventions
3. Update this file if structure changes
4. Add to `.gitignore` if sensitive

