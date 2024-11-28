# Custom Anonymous Redirect

This module grants users with admin privileges the ability to redirect all anonymous users to any internal or external URLs while allowing authenticated users to access the site as usual.

## Installation

No special installation steps are necessary to use this module. For further information on installing modules in Drupal, refer to the [official documentation](https://www.drupal.org/docs/extending-drupal/installing-modules).

## Configuration

Visit `/admin/config/system/custom_anonymous-redirect`. From here, you will be able to:

- Turn on and off anonymous redirects
- Set the path that anonymous users are redirected to
- Use `<front>` or `/path_name` for internal URLs, and `http://website_url.com` for external links
- Wildcards (*) are supported for URL overrides
