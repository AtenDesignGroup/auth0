Drupal Auth0 Module (5.x)
====

This module integrates Auth0's Universal Login with Drupal, providing social, passwordless, and enterprise connection login as well as additional security, multifactor authentication, and user analytics.

The 5.x version uses Auth0's Universal Login exclusively and integrates with Drupal's External Auth module for streamlined user authentication and management.

**Important:** This version (5.x) no longer supports the embedded Auth0 Lock widget. All authentication is handled through Auth0's Universal Login page, providing better security and feature support.

## Table of Contents

- [What's New in 5.x](#whats-new-in-5x)
- [Installation](#installation)
- [Getting Started](#getting-started)
- [Contribution](#contribution)
- [Support + Feedback](#support--feedback)
- [Vulnerability Reporting](#vulnerability-reporting)
- [What is Auth0](#what-is-auth0)
- [License](#license)

## What's New in 5.x

The 5.x version represents a major modernization of the Auth0 Drupal module with significant architectural changes:

### Key Changes

- **Universal Login Only:** Removed the embedded Auth0 Lock widget. All authentication now uses Auth0's Universal Login page for better security and feature compatibility.

- **External Auth Integration:** Now uses Drupal's External Auth module for user authentication handling, providing better integration with Drupal's user system.

- **Streamlined Configuration:** Simplified configuration options focused on essential settings. Removed obsolete options like Widget CDN, Lock settings, and form customization.

- **Enhanced Security:** New Cookie Secret requirement and improved Key module integration for secure credential storage.

- **Modern Architecture:** Refactored authentication service with clean interfaces and improved error handling.

### Removed Features

- Embedded Lock widget/form
- Widget CDN configuration
- Lock extra settings and CSS customization
- Form title and signup settings (now handled by Auth0's Universal Login)

### Migration from 4.x

If upgrading from 4.x, note that:
- The embedded login form will no longer appear - users will be redirected to Auth0
- Lock-specific configurations will be ignored
- You'll need to configure a Cookie Secret
- Consider using the Key module for credential security

## Installation

Before you start, **make sure the admin user has a valid email that you own**. This module delegates the site authentication to Auth0 using Universal Login. That means that you won't be using the Drupal database to authenticate users (user records will still be created) and users will be redirected to Auth0's login page.

**Please note:** Auth0 authentication will not work until the module has been configured (see [Getting Started](#getting-started) below).

### Dependencies

This module requires:
- **External Auth module** (automatically installed via Composer) - handles user authentication integration
- **Key module** (automatically installed via Composer) - provides secure storage for Auth0 credentials

### Install from Drupal.org with Composer

1. From the root of your Drupal project run:

```bash
$ composer require drupal/auth0
```

1. In Manage > Extend, scroll down to the Auth0 module, click the checkbox, then click **Install**

## Getting Started

### 1. Configure your Auth0 Application

Once the module is installed, you'll need to create an Application for your Drupal site in the Auth0 dashboard. 

1. If you haven't already, [sign up for a free Auth0 account here](https://auth0.com/signup).
2. Go to Applications and click **Create Application** on the top right. 
3. Give your Application a name, click **Regular Web Application**, the **Create**.
4. Click the **Settings** tab at the top.
5. In the "Allowed Callback URLs" field, add your site's homepage with a path of `/auth0/callback` like:

```
https://yourdomain.com/auth0/callback
```

1. In the "Allowed Web Origins," "Allowed Logout URLs," and "Allowed Origins (CORS)" fields, add the domain of your Drupal site including the protocol but without a trailing slash like:

```
https://yourdomain.com
```

1. Scroll down and click **Save Changes**.

Leave this tab open to copy the configuration needed in the next section. 

### 2. Configure the Auth0 module

1. Go to Manage > Configuration > System > Auth0
2. Under the **Settings** tab, configure the required Auth0 Application settings:

   - **Domain:** Your Auth0 domain (e.g., `your-tenant.auth0.com`)
   - **Client ID:** From your Auth0 Application settings
   - **Client Secret:** Use either the Key module (recommended for security) or enter directly
   - **Cookie Secret:** Required for session security - use Key module or enter directly (min 32 characters)

   For production environments, it's recommended to use the Key module for storing secrets securely.

3. Alternatively, you can override these settings using Drupal's configuration override system in your `settings.php` file:

```php
$config['auth0.settings']['auth0_client_id'] = getenv('AUTH0_CLIENT_ID');
$config['auth0.settings']['auth0_client_secret'] = getenv('AUTH0_CLIENT_SECRET');
$config['auth0.settings']['auth0_domain'] = getenv('AUTH0_DOMAIN');
$config['auth0.settings']['auth0_cookie_secret'] = getenv('AUTH0_COOKIE_SECRET');
```

1. Click **Save** and Auth0 authentication should now be active. To test this, open a new browser (or private/incognito window) and navigate to your login page at `/user/login`. You should be redirected to Auth0's Universal Login page.

#### Legacy Login Access

If you need to access the standard Drupal login form (for emergencies or admin access), you can still reach it at `/user/login/legacy`. This bypass allows you to log in with local Drupal accounts when needed.

### 3. Advanced Configuration

Click on the **Advanced** tab to configure additional user mapping and authentication options:

#### User Authentication Options

- **Requires verified email:** Enable this setting to require Auth0 users to have a verified email address to log in. This will prevent users without verified emails (e.g., some social login users) from accessing your site.

#### User Mapping

- **Map Auth0 claims to Drupal username:** Specify which Auth0 ID token claim to use for the Drupal username (default: `nickname`). Common options include `email`, `preferred_username`, or `nickname`.

#### Profile Field Mapping

- **Mapping of Claims to Profile Fields:** Map Auth0 user profile data to Drupal user fields. Enter one mapping per line in the format:
  ```
  auth0_claim_name|drupal_field_name
  ```
  Example:
  ```
  given_name|field_first_name
  family_name|field_last_name
  picture|field_user_picture
  ```

- **Sync claim mapping on login:** When enabled, profile fields will be updated from Auth0 data on every login, keeping user profiles in sync.

#### Role Mapping

- **Mapping of Claim Role Values to Drupal Roles:** Map Auth0 role/group data to Drupal roles. Enter one mapping per line in the format:
  ```
  auth0_role_value|drupal_role_name
  ```
  Example:
  ```
  admin|administrator
  poweruser|content_editor
  ```

- **Sync role mapping on login:** When enabled, user roles will be updated from Auth0 data on every login.

**Note:** The External Auth module handles the actual user provisioning and role assignment based on these mappings.

## Contribution

We appreciate feedback and contribution to this module! Before you get started, please see the following:

- [Auth0's general contribution guidelines](https://github.com/auth0/open-source-template/blob/master/GENERAL-CONTRIBUTING.md)
- [Auth0's code of conduct guidelines](https://github.com/auth0/open-source-template/blob/master/CODE-OF-CONDUCT.md)

## Support + Feedback

Please use one of the following methods to ask questions or request support:

- Use [Issues](https://github.com/auth0/auth0-drupal/issues) in GitHub for code-level support
- Use [Community](hhttps://community.auth0.com/tags/drupal) for usage, questions, and specific cases
- You can also use the [DO support forum](https://www.drupal.org/project/issues/auth0?categories=All)

## Vulnerability Reporting

Please do not report security vulnerabilities on the public GitHub issue tracker. The [Responsible Disclosure Program](https://auth0.com/whitehat) details the procedure for disclosing security issues.

## What is Auth0?

Auth0 helps you to easily:

- implement authentication with multiple identity providers, including social (e.g., Google, Facebook, Microsoft, LinkedIn, GitHub, Twitter, etc), or enterprise (e.g., Windows Azure AD, Google Apps, Active Directory, ADFS, SAML, etc.)
- log in users with username/password databases, passwordless, or multi-factor authentication
- link multiple user accounts together
- generate signed JSON Web Tokens to authorize your API calls and flow the user identity securely
- access demographics and analytics detailing how, when, and where users are logging in
- enrich user profiles from other data sources using customizable JavaScript rules

[Why Auth0?](https://auth0.com/why-auth0)

## License

The Drupal Module for Auth0 is licensed under GPLv2 - [LICENSE](LICENSE)
