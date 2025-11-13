# Security Policy

## Supported Versions

The `calendar` package follows semantic versioning. We support the latest major version with security updates. Once a new major release ships, the previous major receives security patches for six months.

| Version | Supported          |
|---------|--------------------|
| 1.x     | ✅ Active          |
| 0.x     | ❌ No longer supported |

## Reporting a Vulnerability

If you discover a security vulnerability, please email [security@lisoing.dev](mailto:security@lisoing.dev) with a detailed description and reproduction steps. Do **not** open a public issue.

We aim to acknowledge reports within 48 hours and provide a remediation timeline within 5 business days. Credit will be given to researchers who responsibly disclose vulnerabilities.

## Security Best Practices

- Always run the latest release (`composer update lisoing/calendar`).
- Lock down your API keys and environment secrets.
- Use HTTPS for all integrations consuming this package.
- Enable CI pipelines to run `composer audit` regularly.

