# Security Best Practices for LC Bingo

## Handling Secrets

### Environment Variables

This project uses environment variables to store sensitive information like passwords, API keys, and other secrets. The following files contain environment variable definitions:

- `.env`: Default values for the environment variables needed by the app
- `.env.local`: Uncommitted file with local overrides (not tracked by Git)
- `.env.$APP_ENV`: Environment-specific defaults (dev, prod, test)
- `.env.$APP_ENV.local`: Uncommitted environment-specific overrides (not tracked by Git)

### Best Practices

1. **Never commit real secrets to the repository**
   - All `.env` files in the repository should use placeholder values like `!ChangeMe!` for secrets
   - Real secrets should only be stored in `.env.local` and `.env.$APP_ENV.local` files, which are not tracked by Git

2. **Production Secrets**
   - For production, use environment variables set on the server or a secrets management service
   - Never store production secrets in files that are committed to the repository
   - Consider using a secrets management service like Docker secrets, Kubernetes secrets, or a dedicated service like HashiCorp Vault

3. **Handling Secrets in Development**
   - Create a `.env.local` file with your development secrets (this file is ignored by Git)
   - Each developer should have their own `.env.local` file with their own development secrets

4. **Secrets That Need to Be Updated**
   - `APP_SECRET`: Used for CSRF protection and other security features
   - `POSTGRES_PASSWORD`: Database password
   - `MERCURE_JWT_SECRET`: JWT secret for Mercure

### Setting Up Your Local Environment

1. Copy the `.env` file to `.env.local`
2. Replace all placeholder values (`!ChangeMe!`) with real values
3. Make sure `.env.local` is in your `.gitignore` file (it should be by default)

### Setting Up Production Environment

1. Set environment variables on your production server or use a secrets management service
2. Make sure the `.env.prod` file in the repository does not contain real secrets
3. If using Docker, consider using Docker secrets or environment variables in your deployment

## Reporting Security Issues

If you discover a security vulnerability in this project, please report it by sending an email to [robin@semche.ovh](mailto:robin@semche.ovh). Please do not create a public GitHub issue.
